
<?php
ini_set('max_execution_time', '900');

require_once __DIR__ . '/backend/Converter.php';
$config = include __DIR__ . '/config/public_config.php';

$converter = new Converter($config);

$quizletUrl = 'https://quizlet.com/es/487468389/test-portugisisch-flash-cards/';
//$quizletUrl = 'https://quizlet.com/484664798/french-flash-cards/';
$betweenWordSilenceDuration = 1;
$nextSetSilenceDuration = 2;


$allCards = $converter->retrieveCardInfos($quizletUrl);

$converter->generateSilence('short-silence.mp3',$betweenWordSilenceDuration);
$converter->generateSilence('long-silence.mp3',$nextSetSilenceDuration);

$converter->prepareAudioListFile($allCards);

$converter->concatAndOutputAudio();



