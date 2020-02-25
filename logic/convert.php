<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../frontend/style.css">
    <title>Quizlet audio converter</title>
</head>
<body>
<?php
ini_set('max_execution_time', '900');

require_once __DIR__ . '/Converter.php';
require_once __DIR__ . '/Timer.php';

// DEBUG set false in prod
$timer = new Timer(true);

$config = include __DIR__ . '/../config/public_config.php';

$converter = new Converter($config);

//$quizletUrl = 'https://quizlet.com/es/487468389/test-portugisisch-flash-cards/'; // only quizlet
//$quizletUrl = 'https://quizlet.com/484664798/french-flash-cards/'; // 100 French words on amazon
$quizletUrl = 'https://quizlet.com/373763301/deutsch-franzosisch-flash-cards/'; // 16 only quizlet

$betweenWordSilenceDuration = 1;
$nextSetSilenceDuration = 2;

$allCards = $converter->retrieveCardInfos($quizletUrl);

$timer->displayTime('Retrieve card info');

$converter->generateSilence('short-silence.mp3',$betweenWordSilenceDuration);
$converter->generateSilence('long-silence.mp3',$nextSetSilenceDuration);

$timer->displayTime('Generate silences');

$blockFiles = $converter->prepareAudioBlockFiles($allCards);

$timer->displayTime('prepareAudioBlockFiles');

$converter->createAudioBlocksAndControlFile($blockFiles);

$timer->displayTime('createAudioBlocksAndControlFile');


/*$blockFiles = [
  'block-1',
  'block-2',
  'block-3',
  'block-4',
  'block-5',
  
];*/

$converter->createFinalFile($blockFiles);

$timer->displayTime('createFinalFile');


?>
</body>
</html>

