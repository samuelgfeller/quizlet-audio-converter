
<?php
ini_set('max_execution_time', '900');

require_once __DIR__ . '/backend/Converter.php';
$config = include __DIR__ . '/config/public_config.php';

$converter = new Converter($config);

//$quizletUrl = 'https://quizlet.com/es/487468389/test-portugisisch-flash-cards/'; // only quizlet
$quizletUrl = 'https://quizlet.com/484664798/french-flash-cards/'; // 100 French words with aws
//$quizletUrl = 'https://quizlet.com/373763301/deutsch-franzosisch-flash-cards/'; // 16 only quizlet

$betweenWordSilenceDuration = 1;
$nextSetSilenceDuration = 2;


$allCards = $converter->retrieveCardInfos($quizletUrl);

$converter->generateSilence('short-silence.mp3',$betweenWordSilenceDuration);
$converter->generateSilence('long-silence.mp3',$nextSetSilenceDuration);

$blockFiles = $converter->prepareAudioBlockFiles($allCards);




$linesForControlFile = [];
foreach ($blockFiles as $file){
    $converter->concatAndOutputAudio($file.'.txt',$file.'.mp3');
    // The files are in the output so the relative path to this file is just the file name
    $linesForControlFile[] = "file '$file.mp3'";
}


file_put_contents($config['output_dir'] . '/control-file.txt', implode(PHP_EOL, $linesForControlFile));

$converter->concatAndOutputAudio('control-file.txt','control.mp3');




