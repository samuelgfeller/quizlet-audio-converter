<?php


class Converter
{
    private $config;
    private $relativeSilenceDir = '../silences';
    private $relativeOutputDir = '../output';
    
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    /**
     * Create directory if it doesn't exist already
     *
     * @param string $dir
     */
    private function createDirectory(string $dir)
    {
        if (!file_exists($dir) && !mkdir(
                $concurrentDirectory = $dir,
                0777,
                true
            ) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }
    
    /**
     * Generates silence file
     *
     * @param string $silenceName
     * @param int $duration in seconds
     */
    public function generateSilence(string $silenceName, int $duration)
    {
        $this->createDirectory($this->config['silence_dir']);
        
        $cmd = 'ffmpeg -f lavfi -y -i anullsrc=channel_layout=5.1:sample_rate=32000 -t ' . $duration . ' ' . $this->config['silence_dir'] . '/' . $silenceName;
        shell_exec($cmd);
    }
    
    /**
     * Download the page content and only keep the card infos
     * which are then decoded and returned as array
     *
     * @param $quizletUrl
     * @return array allCards
     */
    public function retrieveCardInfos($quizletUrl)
    {
        $homepage = file_get_contents($quizletUrl);
        $removedBefore = str_replace(
            'window.Quizlet["setPageData"] = ',
            '',
            strstr($homepage, 'window.Quizlet["setPageData"] = ')
        );
        $removedAfterAndBefore = strstr($removedBefore, '; QLoad("Quizlet.setPageData");', true);
        
        // return all cards with their mapping as array
        return json_decode($removedAfterAndBefore, true)['termIdToTermsMap'];
    }
    
    /**
     * Convert audio sample rate
     *
     * @param string $inputFile
     * @param string $outputName
     * @return string path to converted file
     */
    public function convertSampleRate(string $inputFile, string $outputName): string
    {
        $staticWordOutputDir = $this->config['output_dir'].'/words';
        $relativeWordOutputDir = $this->relativeOutputDir.'/words';
        $this->createDirectory($staticWordOutputDir);
        
        $cmd = 'ffmpeg -protocol_whitelist file,http,https,tcp,tls,crypto -y -i "'.$inputFile.'" -ar 32000 '.$staticWordOutputDir.'/'.$outputName. '> wtf.txt';
        shell_exec($cmd);
        return $relativeWordOutputDir.'/'.$outputName;
    }
    
    /**
     * Prepare file which contains list of audio files
     * in the right order with silences.
     *
     * The rule is that each 20 words are played in a loop (18 times).
     * For that they are grouped and saved in the 2d array allBlocks
     * and written to the corresponding block files and the filenames
     * are returned.
     *
     * @param array $allCards
     * @return array text and audio file name without extension
     */
    public function prepareAudioBlockFiles(array $allCards)
    {
//        $allCards = array_slice($allCards, 0, 41); // for debugging
        
        $cardsAmount = count($allCards);
        $allBlocks = [];
        $iteratingBlockValues = [];
        foreach ($allCards as $key => $card) {
            // Are the definition and word audio hosted externally or not? (if yes they have a different sample rate than quizlet has)
            $wordExternal = false;
            $definitionExternal = false;
            // Set audio urls with default value as with quizlet domain
            $wordAudioUrl = $this->config['quizlet_domain'] . $card['_wordAudioUrl'];
            $definitionAudioUrl = $this->config['quizlet_domain'] . $card['_definitionAudioUrl'];
            
            // The URL is either without the base (when its quizlet.com domain) or full url when the audio is on amazon server
            if (strpos($card['_wordAudioUrl'],'http') !== false){
                // Full url is in the _wordAudioUrl
                $wordAudioUrl =  $card['_wordAudioUrl'];
                $wordExternal = true;
            }
            if (strpos($card['_definitionAudioUrl'],'http') !== false){
                // Full url is in the _wordAudioUrl
                $definitionAudioUrl = $card['_definitionAudioUrl'];
                $definitionExternal = true;
            }
            
            // Relative paths are mandatory for the command
            $iteratingBlockValues[] = "file '$this->relativeSilenceDir/long-silence.mp3'";
            $iteratingBlockValues[] = "file '".($wordExternal ? $this->convertSampleRate($wordAudioUrl,$key.'-word.mp3') : $wordAudioUrl)."'";
            $iteratingBlockValues[] = "file '$this->relativeSilenceDir/short-silence.mp3'";
            $iteratingBlockValues[] = "file '".($definitionExternal ? $this->convertSampleRate($definitionAudioUrl,$key.'-def.mp3') : $definitionAudioUrl) ."'";
            //    echo '<a href="'.$wordAudioUrl.'">'.$card['word'].'</a> | <a href="'.$definitionAudioUrl.'">'.$card['definition'].'</a><br>';
            
            // 4 lines are added each time so to have 20 words the number has to be multiplied by 4
            // Check array contains
            if ($cardsAmount >= 20 && count($iteratingBlockValues) === 80) {
                // Save the cards from the first block to the general array
                $allBlocks[] = $iteratingBlockValues;
                
                // Reset cards for iterating block
                $iteratingBlockValues = [];
                // Remove 20 to the card amount
                $cardsAmount -= 20;
            } // If the amount of cards is less than 20 a block has to be filled with the last cards
            elseif ($cardsAmount < 20 && count($iteratingBlockValues) === $cardsAmount * 4) {
                // Save the cards from the first block to the general array
                $allBlocks[] = $iteratingBlockValues;
                // Reset cards for iterating block
                $iteratingBlockValues = [];
                // Remove 20 to the card amount
                $cardsAmount -= $cardsAmount;
            }
        }
        
        // Create output folder if it doesn't exist
        $this->createDirectory($this->config['output_dir']);
        
        $allFileBlocks = [];
        $i = 1;
        foreach ($allBlocks as $block) {
            $fileNameWithoutExtension = 'block-' . $i;
            // Populate the files.txt with all the paths to the audio files
            file_put_contents(
                $this->config['output_dir'] . '/' . $fileNameWithoutExtension . '.txt',
                implode(PHP_EOL, $block)
            );
            $allFileBlocks[] = $fileNameWithoutExtension;
            $i++;
        }
        
        return $allFileBlocks;
    }
    
    /**
     * Put audio files together
     *
     * @param string $inputFileListName MUST be in the output folder
     * @param string $outputName WILL be in the output folder
     */
    public function concatAndOutputAudio(string $inputFileListName, string $outputName)
    {
        $cmd = 'ffmpeg -f concat -safe 0 -protocol_whitelist file,http,https,tcp,tls -y -i ' .
            $this->config['output_dir'] . '/' . $inputFileListName . ' -c copy ' . $this->config['output_dir'] . '/' . $outputName;
//        echo '<textarea rows="5" cols="200" onclick="this.focus();this.select()" readonly="readonly">' . $cmd . '</textarea>';
        shell_exec($cmd);
    }
    
    /**
     * The control file is an audio document
     * with all words without duplication
     *
     * @param $blockFiles array text and audio file name without extension
     */
    public function createAudioBlocksAndControlFile($blockFiles)
    {
        $linesForControlFile = [];
        foreach ($blockFiles as $file){
            $this->concatAndOutputAudio($file.'.txt',$file.'.mp3');
            // The files are in the output so the relative path to this file is just the file name
            $linesForControlFile[] = "file '$file.mp3'";
        }
        
        file_put_contents($this->config['output_dir'] . '/control-file.txt', implode(PHP_EOL, $linesForControlFile));
        
        $this->concatAndOutputAudio('control-file.txt','control.mp3');
    }
    
    /**
     * Create final file where each block is
     * redundantly played 18 times
     *
     * @param $blockFiles array text and audio file name without extension
     */
    public function createFinalFile($blockFiles)
    {
        $linesForFinalFile = [];
        foreach ($blockFiles as $file){
            for ($i=0; $i<18;$i++) {
                // mp3 files are created while control file is created
                // The files are in the output so the relative path to this file is just the file name
                $linesForFinalFile[] = "file '$file.mp3'";
            }
            $linesForFinalFile[] = "file '../silences/long-silence.mp3'";
            $linesForFinalFile[] = "file '../silences/long-silence.mp3'";
            $linesForFinalFile[] = "file '../silences/long-silence.mp3'";
        }
    
        file_put_contents($this->config['output_dir'] . '/final-file.txt', implode(PHP_EOL, $linesForFinalFile));
    
        $this->concatAndOutputAudio('final-file.txt','final.mp3');
    }
}