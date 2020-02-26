<?php
ini_set('max_execution_time', '900');

require_once __DIR__ . '/Converter.php';
require_once __DIR__ . '/Timer.php';

// DEBUG set false in prod
$timer = new Timer(true);

$config = include __DIR__ . '/../config/public_config.php';

if (isset($_POST['shortSilenceDuration'], $_POST['longSilenceDuration'], $_POST['quizletLink']) && filter_var($_POST['quizletLink'],
        FILTER_VALIDATE_URL)) {
    $converter = new Converter($config);

//$quizletUrl = 'https://quizlet.com/es/487468389/test-portugisisch-flash-cards/'; // only quizlet
//$quizletUrl = 'https://quizlet.com/484664798/french-flash-cards/'; // 100 French words on amazon
    $quizletUrl = 'https://quizlet.com/373763301/deutsch-franzosisch-flash-cards/'; // 16 only quizlet


    $allCards = $converter->retrieveCardInfos($_POST['quizletLink']);

    $timer->displayTime('Retrieve card info');

    $converter->generateSilence('short-silence.mp3', (float)$_POST['shortSilenceDuration']);
    $converter->generateSilence('long-silence.mp3', (float) $_POST['longSilenceDuration']);

    $timer->displayTime('Generate silences');

    $isTest = isset($_POST['isTest']) && $_POST['isTest'] === 'on';
    $blockFiles = $converter->prepareAudioBlockFiles($allCards,$isTest);

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

}