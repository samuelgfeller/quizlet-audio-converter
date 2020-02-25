<?php


class Converter
{
    private $config;
    
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
        if (!file_exists($dir) && !mkdir($concurrentDirectory = $dir,
                0777, true) && !is_dir($concurrentDirectory)) {
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
     * Prepare file which contains list of audio files
     * in the right order with silences.
     *
     * The rule is that each 20 words are played in a loop (21 times).
     * For that they are grouped and saved in the 2d array allBlocks
     * and written to the corresponding block files and the filenames
     * are returned.
     *
     * @param array $allCards
     * @return array
     */
    public function prepareAudioBlockFiles(array $allCards)
    {
        $cardsAmount = count($allCards);
        $allBlocks = [];
        $iteratingBlockValues = [];
        foreach ($allCards as $card) {
            // The is either without the base (when its quizlet.com domain) or full url when the audio is on amazon server
            $wordAudioUrl = strpos(
                $card['_wordAudioUrl'],
                'http'
            ) !== false ? $card['_wordAudioUrl'] : $this->config['quizlet_domain'] . $card['_wordAudioUrl'];
            $definitionAudioUrl = strpos(
                $card['_definitionAudioUrl'],
                'http'
            ) !== false ? $card['_definitionAudioUrl'] : $this->config['quizlet_domain'] . $card['_definitionAudioUrl'];
            
            // Relative paths are mandatory for the command
            $iteratingBlockValues[] = "file '../silences/long-silence.mp3'";
            $iteratingBlockValues[] = "file '$wordAudioUrl'";
            $iteratingBlockValues[] = "file '../silences/short-silence.mp3'";
            $iteratingBlockValues[] = "file '$definitionAudioUrl'";
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
            }
            // If the amount of cards is less than 20 a block has to be filled with the last cards
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
        foreach ($allBlocks as $block){
            $fileNameWithoutExtension = 'block-'.$i;
            // Populate the files.txt with all the paths to the audio files
            file_put_contents($this->config['output_dir'] . '/'.$fileNameWithoutExtension.'.txt', implode(PHP_EOL, $block));
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
            $this->config['output_dir'] . '/' .$inputFileListName . ' -c copy ' . $this->config['output_dir'] . '/'.$outputName;
        shell_exec($cmd);
    }
}
