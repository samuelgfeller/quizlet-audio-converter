
<?php
ini_set('max_execution_time', '900');

$basePath = $_SERVER['DOCUMENT_ROOT'].'/quizlet-audio-converter/';


$quizletUrl = 'https://quizlet.com/es/487468389/test-portugisisch-flash-cards/';
//$quizletUrl = 'https://quizlet.com/484664798/french-flash-cards/';


$homepage = file_get_contents($quizletUrl);
//file_put_contents('test.html', $homepage);
$removedBefore = str_replace('window.Quizlet["setPageData"] = ', '', strstr($homepage, 'window.Quizlet["setPageData"] = '));
$removedAfterAndBefore = strstr($removedBefore, '; QLoad("Quizlet.setPageData");', true);

$allCards = json_decode($removedAfterAndBefore, true)['termIdToTermsMap'];
//var_dump($allCards);

$audios =null;

//$baseUrl = 'https://quizlet.com/';
//foreach ($allCards as $card) {
//    // The is either without the base (when its quizlet.com domain) or full url when the audio is on amazon server
//    $wordAudioUrl = strpos($card['_wordAudioUrl'], 'http') !== false ? $card['_wordAudioUrl'] : $baseUrl.$card['_wordAudioUrl'];
//    $definitionAudioUrl = strpos($card['_definitionAudioUrl'], 'http') !== false ? $card['_definitionAudioUrl'] : $baseUrl.$card['_definitionAudioUrl'];
//
//    $audios .= file_get_contents($wordAudioUrl) . file_get_contents('1-sec-silence.mp3') .
//        file_get_contents($definitionAudioUrl) . file_get_contents('2-sec-silence.mp3');
//
//    echo '<a href="'.$wordAudioUrl.'">'.$card['word'].'</a> | <a href="'.$definitionAudioUrl.'">'.$card['definition'].'</a><br>';
//}
//var_dump($audios);
//file_put_contents('combined.mp3',$audios);

$audioUrls = [];

$baseUrl = 'https://quizlet.com/';
foreach ($allCards as $card) {
    // The is either without the base (when its quizlet.com domain) or full url when the audio is on amazon server
    $wordAudioUrl = strpos($card['_wordAudioUrl'], 'http') !== false ? $card['_wordAudioUrl'] : $baseUrl.$card['_wordAudioUrl'];
    $definitionAudioUrl = strpos($card['_definitionAudioUrl'], 'http') !== false ? $card['_definitionAudioUrl'] : $baseUrl.$card['_definitionAudioUrl'];

    $audioUrls[] = $wordAudioUrl;
    $audioUrls[] = $definitionAudioUrl;
    file_put_contents($card['word'].'.mp3',file_get_contents($wordAudioUrl));
    file_put_contents($card['definition'].'.mp3',file_get_contents($definitionAudioUrl));
    $audios[] = $card['word'].'.mp3';
    $audios[] = $card['definition'].'.mp3';

    echo '<a href="'.$wordAudioUrl.'">'.$card['word'].'</a> | <a href="'.$definitionAudioUrl.'">'.$card['definition'].'</a><br>';
}

$cmd = 'ffmpeg -protocol_whitelist file,https,concat,tls,tcp -i "concat:'.implode('|',$audios).'" '.$basePath.'/output/test.mp3';
echo $cmd;
shell_exec($cmd);




//file_put_contents('test.json', $removedAfterAndBefore);
//file_put_contents('test.php', var_export(json_decode($removedAfterAndBefore, true), true));
