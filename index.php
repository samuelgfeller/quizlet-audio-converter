
<?php
ini_set('max_execution_time', '900');

require_once __DIR__ . '/backend/Converter.php';
$config = include __DIR__ . '/config/public_config.php';

$converter = new Converter($config);

//$quizletUrl = 'https://quizlet.com/es/487468389/test-portugisisch-flash-cards/';
$quizletUrl = 'https://quizlet.com/484664798/french-flash-cards/';

$betweenWordSilenceDuration = 1;
$nextSetSilenceDuration = 2;


$allCards = $converter->retrieveCardInfos($quizletUrl);

$converter->generateSilence('short-silence.mp3',$betweenWordSilenceDuration);
$converter->generateSilence('long-silence.mp3',$nextSetSilenceDuration);

$blockFiles = $converter->prepareAudioBlockFiles($allCards);

$linesForFinalFile = [];
foreach ($blockFiles as $file){
    $converter->concatAndOutputAudio($file.'.txt',$file.'.mp3');
    // The files are in the output so the relative path to this file is just the file name
    $linesForFinalFile[] = "file '$file.mp3'";
}


file_put_contents($config['output_dir'] . '/final-file.txt', implode(PHP_EOL, $linesForFinalFile));

$converter->concatAndOutputAudio('final-file.txt','final.mp3');




