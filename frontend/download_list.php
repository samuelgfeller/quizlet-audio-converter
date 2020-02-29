
<?php
$pathControlFile = '../output/control.mp3';
$pathFinalFile = '../output/final.mp3';

if (file_exists($pathControlFile)){
    echo '<a href="'.$pathControlFile.'"><p class="infoP">Alle Worte ohne Widerholung (Kontrolle)</p></a>';
}else{
    echo '<p class="infoP">Kontroll-Datei nicht gefunden</p>';
}

if (file_exists($pathFinalFile)){
    echo '<a href="'.$pathFinalFile.'"><p class="infoP">Datei mit Wiederholungen</p></a>';
}else{
    echo '<p class="infoP">Datei mit Wiederholungen nicht gefunden</p>';

}



