<?php


class Converter
{
    private $config;
    
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    /**
     * Generates silence file
     *
     * @param string $silenceName
     * @param int $duration in seconds
     */
    public function generateSilence(string $silenceName, int $duration)
    {
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
     * in the right order with silences
     * @param $allCards
     */
    public function prepareAudioListFile($allCards)
    {
        $audioPaths = [];
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
            $audioPaths[] = "file '../silences/long-silence.mp3'";
            $audioPaths[] = "file '$wordAudioUrl'";
            $audioPaths[] = "file '../silences/long-silence.mp3'";
            $audioPaths[] = "file '$definitionAudioUrl'";
            //    echo '<a href="'.$wordAudioUrl.'">'.$card['word'].'</a> | <a href="'.$definitionAudioUrl.'">'.$card['definition'].'</a><br>';
        }
        
        // Populate the files.txt with all the paths to the audio files
        file_put_contents($this->config['output_dir'] . '/files.txt', implode(PHP_EOL, $audioPaths));
    }
    
    public function concatAndOutputAudio()
    {
        $cmd = 'ffmpeg -f concat -safe 0 -protocol_whitelist file,http,https,tcp,tls -y -i ' .
            $this->config['output_dir'] . '/files.txt' . ' -c copy ' . $this->config['output_dir'] . '/output/all.mp3';
        shell_exec($cmd);
    }
}
