<?php

$zip = new ZipArchive();

$zipPath= '/output/test.zip';

if(file_exists($basePath.$zipPath)) {

    unlink ($basePath.$zipPath);

}
if ($zip->open($basePath.$zipPath, ZIPARCHIVE::CREATE) !== true) {
    die ('Could not open archive');
}

foreach ($allCards as $card) {
    // The is either without the base (when its quizlet.com domain) or full url when the audio is on amazon server
    $wordAudioUrl = strpos($card['_wordAudioUrl'], 'http') !== false ? $card['_wordAudioUrl'] : $baseUrl.$card['_wordAudioUrl'];
    $definitionAudioUrl = strpos($card['_definitionAudioUrl'], 'http') !== false ? $card['_definitionAudioUrl'] : $baseUrl.$card['_definitionAudioUrl'];

    $zip->addFromString($card['word'].'.mp3',file_get_contents($wordAudioUrl));
    $zip->addFromString($card['definition'].'.mp3',file_get_contents($definitionAudioUrl));

//    $audios .= file_get_contents($wordAudioUrl) . file_get_contents('1-sec-silence.mp3') .
//        file_get_contents($definitionAudioUrl) . file_get_contents('2-sec-silence.mp3');

    echo '<a href="'.$wordAudioUrl.'">'.$card['word'].'</a> | <a href="'.$definitionAudioUrl.'">'.$card['definition'].'</a><br>';
}
//var_dump($audios);
//file_put_contents('combined.mp3',$audios);

// close and save archive

$zip->close();

rename($basePath.$zipPath,$basePath.'/output/test.mp3');