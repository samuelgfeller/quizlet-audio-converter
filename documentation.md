# Technische Dokumentation
## Einleitung
### Technologien
* **Programmiersprache**: PHP 7.4
* **Entwicklungs-Umgebung**: Windows 10 computer mit einem lokalen apache Websever  
* **Produktive Umgebung**: Apache-Webserver auf einem Linux CentOS 8 Server
* **Medienbearbeitungs-Programm**: ffmpeg

### Struktur des Projektes
* Im Ordner `config` gibt es die Datei `public_config.php`, welche die Konfigurationswerte hat, die im ganzen Programm benötigt werden.   
* Der Ordner `frontend` enthält alle Dateien, die die Ansicht der Webseite angehen. Also Stil und Inhalte.
* In dem Ordner `logic` wird das ganze Rechnen, Schneiden und Zusammenführen usw. gemacht. Die Audio-Dateien werden dort generiert.
* `output` enthält die generierten Audio-Dateien.
* In `silences` werden die generierten "Pausen" gelagert, welche benutzt werden zwischen zwei Wörter. 

## Funktionsweise
### Startseite 
Auf der Startseite gibt es folgende Eingabefelder:
* Ein Textfeld für die Eingabe eines Quizlet-Lernset Linkes.
* Ein Feld für die Angabe der Dauer der Pausen (in Sekunden) zwischen einem Wort und seiner Übersetzung.
* Ein anderes Feld, in welchem die Zeit der Pause zum nächsten Wortpaar definiert werden kann (in Sekunden).
* Ein Feld für die Pause bevor die Wörter und Übersetzung anfangen soll. 
* Eine Checkbox, um einen Testlauf machen zu können um das Resultat zu prüfen.  

Die Eingaben können mit dem Knopf "Audio generieren" an den Server gesendet werden, welcher die erstellung der Audiodatei beginnt.  
Die Struktur und Inhalte dieser Seite wurde in der Datei `index.html` definiert. 

### Nach dem Absenden
Nach dem Absenden der Benutzereingaben, erscheint einen Loader mit der Information, dass gewartet werden muss.  
Die Javascript datei `frontend/js/main.js`, welche verantwortlich ist für das Interaktive auf der Webseite, zeigt diesen Loader. 
Der Strukur des Loaders und Overlays ist in `index.html` festgelegt unter `<div id="overlay">`. Für die Stilisierung und das Aussehen ist die CSS-Datei 
`frontend/css/loader.css` und `frontend/css/style.css` verwantwortlich. 
  
Die Eingaben wurden an `frontend/convert_page.php` übermittelt, welcher es weitergibt an `logic/convert.php`. Dort wird das Generieren der 
Audio-Datei gesteuert.
 
### Audio Erstellung
#### Ausführungszeit erhöhen
Das erste was `logic/convert.php` macht, ist die Maximale Ausführungszeit vom Programm auf 900 Sekunden erhöhen. Standardmässig darf ein PHP-Programm nur 30 Sekunden 
ausgefürt werden, denn über dieser Zeit wird angenommen, dass ein Fehler passiert ist. In unserem Fall jedoch, dauert das Herunterladen, Konvertieren und
Generieren der neuen Datei viel länger. 
```php
ini_set('max_execution_time', '900');
```

#### Zeiterfassung
Um zu enticklen ist es praktisch zu wissen wie viel Zeit jeder schritt benötigt. So wird ein Timer gestartet, welcher nach jedem Schritt eine Ausgabe macht 
der schon verloffenen Zeit. Produktiv wird diese Ausgabe jedoch nicht gemacht so wird diesen Schritt nicht weiter beschrieben. 

#### Prüfen der Eingaben
Damit das Programm arbeiten kann, braucht es die definierten Benutzereingaben. So wird geprüft, ob die Werte in den Variablen gesetzt sind und ob der eingegebene
Link valide ist. 
```php
if (isset($_POST['shortSilenceDuration'], $_POST['longSilenceDuration'], $_POST['quizletLink'], $_POST['beginSilenceDuration']) && 
    filter_var($_POST['quizletLink'], FILTER_VALIDATE_URL)) { ... }
```
Wenn nicht, wird das Programm beendet und es erscheint eine Ausgabe, welche dem Benutzer bekanntgibt, dass die Eingaben nicht korrekt übermittelt wurden.
```php
else{
    echo 'Parameters not set';
}
```

#### Instanziieren des Objektes
In `logic/convert.php` wird die Stuereung gemacht. Dort werden die Befehle in der richtigen Reihenfolge erzeugt. Die Funktionen für das eigentliche 
Verarbeiten der Audio-Dateien befindet sich in der Klasse `logic/Converter.php`.   
Eine **Klasse** kann man sich vorstellen wie einen Bauplan von z.B. einem Auto. Es befinden sich alle Informationen darin aber das auto ist noch nicht gebaut, es kann
noch nicht damit gefahren werden. Die Farbe ist auch noch nicht definiert.
Dies wird gemacht während dem sogenannten **Instanziieren** der Klasse. 
```php
$converter = new Converter($config);
```
Jetzt haben wir eine Instanz von dem `Converter` in der Variable `$converter` gespeichert und wir können damit arbeiten. Diese Instanz können wir auch **Objekt** nennen. 
Als Parameter zur Erstellung des Objektes, werden die in `config/public_config.php` definierten Konfigurationswerten (z.B. die Domain der Applikation) übergeben.   
Das ist wie die Farbe des Autos. Ein Element mit einer spezifischen Einstellung fixiert. Das Objekt wurde erstellt nicht mit einer spezifischen Farbe sondern 
mit Konfigurationswerten. 


#### Karteninformationen vom Quizlet extrahieren
In der Klasse `Converter` gibt es eine Funktion `retrieveCardInfos`, welche folgendermassen aufgerufen wird in `logic/convert.php`:
```php
$allCards = $converter->retrieveCardInfos($_POST['quizletLink']);
```
Es wird der Link von dem Quizlet-Lernset mitgegeben. 
In dieser Funktion von `logic/Converter.php` wird zuerst der Inhalt der Quizlet-Seite heruntergeladen und in der Variable `$homepage` gespeichert.
```php
$homepage = file_get_contents($quizletUrl);
```
Der Inhalt besteht jetzt aus dem Quellcode der Webseite. Daraus müssen jetzt die Interessanten Werte herausgefiltert werden. 
Um genau zu sein, gibt es einen gewissen Bereich vom Seiteninhalt den uns interessiert. 
Um diesen Bereich zu extrahieren, muss alles vor der gewissen Zeichenfolge `'window.Quizlet["setPageData"] = '` gelöscht werden. Dies wird mit den PHP-Funktionen 
`str_replace` und `strstr` gemacht. 
```php
$removedBefore = str_replace('window.Quizlet["setPageData"] = ',  '', 
    strstr($homepage, 'window.Quizlet["setPageData"] = '));
```
Jetzt muss noch alles nach `'; QLoad("Quizlet.setPageData");'` weggenommen werden. Auch dies wird mit `strstr` gemacht. 
```php
$removedAfterAndBefore = strstr($removedBefore, '; QLoad("Quizlet.setPageData");', true);
```
Das Resultat ist jetzt in der Variable `$removedAfterAndBefore`. Das Format in welchem die Daten vorhanden sind heisst `JSON`. Damit kann PHP nicht direkt arbeiten.  
Es gibt aber eine Funktion, welche sie ganz einfach umwandelt in ein Array. **Ein Array ist eine Sammlung von Daten in einem strukturierten Format**. 
Dieses Array wird zurückgegeben. 
```php
return json_decode($removedAfterAndBefore, true)['termIdToTermsMap'];
```

#### Pausen generieren
Jetzt werden die 3 Pausen generiert nach den Benutzereingaben. Dies macht die Funktion `generateSilence`. 
```php
$converter->generateSilence('short-silence.mp3', (float)$_POST['shortSilenceDuration']);
$converter->generateSilence('long-silence.mp3', (float) $_POST['longSilenceDuration']);
$converter->generateSilence('begin-silence.mp3', (float) $_POST['beginSilenceDuration'] * 60); // In minutes
```

In dieser Funktion wird geprüft ob die eingegebene Dauer *eine Variante von 0* ist. Wenn ja, wird es automatisch auf eine Sekunde gesetzt. 
```php
if ($duration === 0.0 || $duration === 0 || $duration === '0') {
    $duration = 1;
}
```
Dann wird der Befehl zuerst zusammengestellt und dann ausgeführt. Dieser wird von dem Programm `ffmpeg` interpretiert, welcher die Audio-Datei erstellt.
```php
$cmd = 'ffmpeg -f lavfi -y -i anullsrc=channel_layout=5.1:sample_rate=32000 -b:a 48K -t ' . $duration . ' ' . $this->config['silence_dir'] . '/' . $silenceName;
shell_exec($cmd);
```

#### Audio Block Vorbereitung
##### Regel
Die Vorgabe ist, während ungefähr 30 Minuten sollen die gleichen 20 Wörter gespielt werden. Die ersten 20 Begriffe mit ihren Übersetzungen formen 
den ersten "Block". Der zweite Block besteht aus den nächsten 20 Wörter usw.. Jeder Block wird 18 Mal hintereinander durchgespielt bevor das selbe 
gemacht wird mit dem nächsten Block, was mehroderweniger 30 Minuten bedeutet.
 
##### Umsetzung
Die Funktion wird aufgerufen von `logic/convert.php` wie gewohnt. Hier werden die Karten-Werte mitgegeben und ob es sich um einen Test handelt oder nicht.
```php
$isTest = isset($_POST['isTest']) && $_POST['isTest'] === 'on';
$blockFiles = $converter->prepareAudioBlockFiles($allCards,$isTest);
```
Wenn es ein Test ist, werden in der Funktion von `logic/Converter.php` nur die ersten 10 Einträge behalten
```php
if ($isTest === true) {
    $allCards = array_slice($allCards, 0, 10);
}
```
Jetzt werden zuerst die Benötigten Variablen initialisiert.
```php
$cardsAmount = count($allCards);
$allBlocks = [];
$iteratingBlockValues = [];

$linesPerWordPair = 4; // depending on silences and which words are put into the array
$amountCardsInBlock = 20;
$cardWithNoAudioAmount = 0; // If the word or definition contains no url one is added to this var
``` 
Jetzt wird über alle Wörter gegangen in einer Schlaufe. Bei jedem Durchlauf mit `foreach` hat man zugang zu einem einzelnen Wert. 
Der folgende code ist also jetzt in dieser Schlaufe und es wird mit nur einem Wortpaar jeweils gearbeitet. 
```php
foreach ($allCards as $key => $card) {
```
##### Url der Wörter
In den abgeholten Daten von Quizlet befinden sich die Links zu den Audiodateien wobei das Wort in der entsprechenden Sprache gesprochen wird. 
Es gibt aber zwei mögliche Quellen, denn Quizlet hat die einten Wörter auf Amazon-Server und die Anderen bei ihnen selber also quizlet.com. Das Problem ist, dass 
wenn die Wörter nicht bei Amazon sind, ist nicht die ganze URL vorhanden und somit muss zuerst abgefragt werden ob es eine Amazon URL ist oder von Quizlet und die 
benötigten Anpassungen machen. Die URLs werden in den Variablen `$wordAudioUrl` und `$definitionAudioUrl` gespeichert. 
```php
 // The URL is either without the base (when its quizlet.com domain) or full url when the audio is on amazon server
 $wordAudioUrl = strpos(
     $card['_wordAudioUrl'], 'http'
 ) !== false ? $card['_wordAudioUrl'] : $this->config['quizlet_domain'] . $card['_wordAudioUrl'];
 $definitionAudioUrl = strpos(
     $card['_definitionAudioUrl'], 'http'
 ) !== false ? $card['_definitionAudioUrl'] : $this->config['quizlet_domain'] . $card['_definitionAudioUrl'];
``` 
Im nächsten Schritt werden die Audio-Dateien heruntergeladen und lokal gespeichert.   

```php
$iteratingBlockValues[] = "file '$this->relativeSilenceDir/long-silence.mp3'";
$card['_definitionAudioUrl'] !== null ? $iteratingBlockValues[] = "file '" . $this->convertSampleRate(
        $definitionAudioUrl,
        $key . '-def.mp3'
    ) . "'" : $cardWithNoAudioAmount++;
$iteratingBlockValues[] = "file '$this->relativeSilenceDir/short-silence.mp3'";
$card['_wordAudioUrl'] !== null ? $iteratingBlockValues[] = "file '" . $this->convertSampleRate(
        $wordAudioUrl,
        $key . '-word.mp3'
    ) . "'" : $cardWithNoAudioAmount++;
```
Die Wörter von Amazon und Quizlet haben leider nicht die selbe "Sample Rate", was Probleme macht später beim zusammenführen der Dateien. 
So muss diese konvertiert werden in ein einheitlichen Wert. Das ist der wichtige Inhalt von der Funktion `convertSampleRate()`. Die Konverstion macht `ffmpeg`
```php
$cmd = 'ffmpeg -protocol_whitelist file,http,https,tcp,tls,crypto -y -i "' . $inputFile . '" -ar 32000 -ac 2 ' . $this->staticWordOutputDir . '/' . $outputName . '> wtf.txt';
shell_exec($cmd);
return $relativeWordOutputDir . '/' . $outputName;
```
Im nächsten Schritt werden diese Wörter vorbereitet und gruppiert in einem grossen Array `$allBlocks`. Es werden auch die Pausen eingeführt. 
```php
// 4 lines are added each time so to have 20 words the number has to be multiplied by 4
// Check array contains
if ($cardsAmount >= $amountCardsInBlock && count($iteratingBlockValues)
    === ($amountCardsInBlock * $linesPerWordPair) - $cardWithNoAudioAmount) {
    // Save the cards from the first block to the general array
    $allBlocks[] = $iteratingBlockValues;
    // Reset cards for iterating block
    $iteratingBlockValues = [];
    // Remove 20 to the card amount
    $cardsAmount -= $amountCardsInBlock;
} // If the amount of cards is less than 20 a block has to be filled with the last cards
elseif ($cardsAmount < $amountCardsInBlock && count($iteratingBlockValues)
    === ($cardsAmount * $linesPerWordPair) - $cardWithNoAudioAmount) {
    // Save the cards from the first block to the general array
    $allBlocks[] = $iteratingBlockValues;
    // Reset cards for iterating block
    $iteratingBlockValues = [];
    // Remove 20 to the card amount
    $cardsAmount -= $cardsAmount;
}
```

In diesem Schritt wird eine Textdatei erstellt mit auf jeder Zeile der Pfad einer Audio-Datei. Es ist in der Reihenfolge wie sie später zusammengeführt 
 werden sollen. Diese Text-Dateien werden zurückgegeben und das ist das Ende dieser Funktion.
```php
$allFileBlocks = [];
$i = 1;
foreach ($allBlocks as $block) {
    $fileNameWithoutExtension = 'block-' . $i;
    // Populate the files.txt with all the paths to the audio files
    file_put_contents(
        $this->config['output_dir'] . '/' . $fileNameWithoutExtension . '.txt',
        implode(PHP_EOL, $block)
    );
    $allFileBlocks[] = $fileNameWithoutExtension;
    $i++;
}
return $allFileBlocks;
```
Für jeden Block gibt es jetzt also eine Text-Datei mit den 20 Wörter und den Pausen. 
Der Inhalt der Text-Datei kann so aussehen (ersten 6 Linien):
```text
file '../silences/long-silence.mp3'
file '../output/words/12905570352-def.mp3'
file '../silences/short-silence.mp3'
file '../output/words/12905570352-word.mp3'
file '../silences/long-silence.mp3'
file '../output/words/12905570353-def.mp3'
```

#### Audio-Datei erstellen für jeden Block
In diesem Schritt wird eine Audio-Datei erstellt für jeden Block. Also 20 Wörter mit Pausen und ihren Übersetzungen. 
Parallel zu der Ausgabe Datei, welche alle Regeln beachtet mit den Wiederholungen, gibt es eine Datei zur Prüfung ob die Wörter und Pausen Stimmen.   
`logic/convert.php` ruft die entprechende Funktion auf.
```php
$converter->createAudioBlocksAndControlFile($blockFiles);
``` 
In dieser Funktion werden die Audio-Dateien erstellt anhand der Text-Dateien für jeden Block. So gibt es jetzt eine Audio-Datei für jeden Block. 
```php
public function createAudioBlocksAndControlFile($blockFiles)
{
    $linesForControlFile = [];
    foreach ($blockFiles as $file) {
        $this->concatAndOutputAudio($file . '.txt', $file . '.mp3');
        // The files are in the output so the relative path to this file is just the file name
        $linesForControlFile[] = "file '$file.mp3'";
    }
    file_put_contents($this->config['output_dir'] . '/control-file.txt', implode(PHP_EOL, $linesForControlFile));
    $this->concatAndOutputAudio('control-file.txt', 'control.mp3');
}
```

#### Finale Datei erstellen
Jetzt sind alle Elemente vorhanden um die gewünschte Datei zu kreieren.
```php
$converter->createFinalFile($blockFiles);
```
In der Funktion wird zuerst eine Text-Datei erstellt mit der Reihenfolge der Audio-Dateien. 
Die erse Linie ist die Anfangspause. 
```php
$linesForFinalFile[] = "file '../silences/begin-silence.mp3'";
``` 
Jetzt wird wieder mit der Funktion `foreach` über alle Blöcke iteriert und bei jedem Durchlauf werden 18 Linien von einem Block hinereinander hinzugefügt so werden 
die 20 Wörter ungefähr während 30 Minuten durchlaufen.   
Nach den 18 Repetitionen gibt es eine Pause von der Dauer einer dreifacher Pause zu dem nächsten Wortpaar. 
```php
foreach ($blockFiles as $file) {
    for ($i = 0; $i < 18; $i++) {
        // mp3 files are created while control file is created
        // The files are in the output so the relative path to this file is just the file name
        $linesForFinalFile[] = "file '$file.mp3'";
    }
    $linesForFinalFile[] = "file '../silences/long-silence.mp3'";
    $linesForFinalFile[] = "file '../silences/long-silence.mp3'";
    $linesForFinalFile[] = "file '../silences/long-silence.mp3'";
}
```
Jetzt kann die Finale Text-Datei erstellt werden.
```php
file_put_contents($this->config['output_dir'] . '/final-file.txt', implode(PHP_EOL, $linesForFinalFile));
```
Der Inhalt könnte so aussehen:
```text
file '../silences/begin-silence.mp3'
file 'block-1.mp3'
file 'block-1.mp3'
file 'block-1.mp3'
... (18x)
file '../silences/long-silence.mp3'
file '../silences/long-silence.mp3'
file '../silences/long-silence.mp3'
file 'block-2.mp3'
file 'block-2.mp3'
file 'block-2.mp3'
... (18x)
file '../silences/long-silence.mp3'
file '../silences/long-silence.mp3'
file '../silences/long-silence.mp3'
file 'block-3.mp3'
file 'block-3.mp3'
file 'block-3.mp3'
... (18x)
file '../silences/long-silence.mp3'
file '../silences/long-silence.mp3'
file '../silences/long-silence.mp3'
```
Daraus muss nur noch die Audio Datei erstellt werden daraus. Dafür wird die Funktion `concatAndOutputAudio()` aufgerufen.
```php
$this->concatAndOutputAudio('final-file.txt', 'final.mp3');
```
In dieser Funktion passiert schlussendlich die Magie. Der Befehl an `ffmpeg` wird konstruiert und ausgeführt.
```php
$cmd = 'ffmpeg -f concat -safe 0 -protocol_whitelist file,http,https,tcp,tls -y -i ' . 
    $this->config['output_dir'] . '/' . $inputFileListName . ' -b:a 48K -c copy ' .
    $this->config['output_dir'] . '/' . $outputName;
shell_exec($cmd);
```
Das Resultat ist jetzt `final.mp3`. 

#### Aufräumen
Für jeden Begriff wurde eine Audio-Datei erstellt, welche jetzt nicht mehr relevant ist aber Platz benötigt. So können diese gelöscht werden.
```php
array_map('unlink', glob($this->staticWordOutputDir . '/*.*'));
```
