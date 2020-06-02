# Technische Dokumentation
## Einleitung
### Technologien
* **Programmiersprache**: PHP 7.4
* **Entwicklungs-Umgebung**: Windows 10 computer mit einem lokalen apache Websever  
* **Produktive Umgebung**: Apache-Webserver auf einem Linux CentOS 8 Server
* **Medienbearbeitungs-Programm**: FFMPEG

### Struktur des Projektes
* Im Ordner `config` gibt es die Datei `public_config.php`, welche die Konfigurationswerte hat, die im ganzen Programm benötigt werden.   
* Der Ordner `frontend` enthält alle Dateien, die Ansicht der Webseite angehen. Also Stil und Inhalte.
* In dem Ordner `logic` wird das ganze rechnen, schneiden usw. gemacht. Die Audio-Dateien werden dort generiert.
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
Die Struktur und Inhalte dieser Seite sind wurden in der Datei `index.html` definiert. 

### Nach dem Absenden
Nach dem Absenden der Benutzereingaben, erscheint einen Loader mit der Information, dass gewartet werden muss.  
Die Javascript datei `frontend/js/main.js`, welche verantwortlich ist für das Interaktive auf der Webseite, zeigt diesen Loader. 
Der Strukur des Loaders und Overlays ist in `index.html` festgelegt unter `<div id="overlay">`. Für die Stilisierung und das Aussehen ist die CSS-Datei 
`frontend/css/loader.css` und `frontend/css/style.css` verwantwortlich. 
  
Die Eingaben wurden an `frontend/convert_page.php` übermittelt, welcher es weitergibt an `logic/convert.php`. Dort wird das generieren der 
Audio-Datei gesteuert.
 
### Audio Erstellung
#### Ausführungszeit erhöhen
Das erste was `logic/convert.php` macht, ist die Maximale Ausführungszeit vom Programm auf 900 Sekunden erhöhen. Standardmässig darf ein PHP-Programm nur 30 Sekunden 
ausgefürt werden, denn über dieser Zeit wird angenommen, dass ein Fehler passiert ist. In unserem Fall jedoch, dauert das herunterladen, konvertieren und
generieren der neuen Datei viel längere Zeit. 
```php
ini_set('max_execution_time', '900');
```

#### Zeiterfassung
Um zu enticklen ist es praktisch zu wissen wie viel Zeit jeder schritt benötigt. So wird ein Timer gestartet, welcher nach jedem Schritt eine Ausgabe macht 
der schon verloffenen Zeit. Produktiv wird diese Ausgabe jedoch nicht gemacht so wird diesen Schritt nicht weiter beschrieben. 

#### Prüfen der Eingaben
Damit das Programm arbeiten muss, braucht es die definierten Benutzereingaben. So wird geprüft, ob die Werte in den Variablen gesetzt sind und ob der eingegebene
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
In `logic/convert.php` die Stuereung gemacht. Dort werden die Befehle in der richtigen Reihenfolge erzeugt. Die Funktionen für das eigentliche 
Verarbeiten der Audio-Dateien befindet sich in der Klasse `logic/Converter.php`.   
Eine **Klasse** kann man sich vorstellen wie einen Bauplan von z.B. einem Auto. Es befinden sich alle Informationen darin aber das auto ist noch nicht gebaut, es kann
noch nicht damit gefahren werden. Die Farbe ist auch noch nicht definiert.
Dies wird gemacht während dem sogenannten **Instanziieren** der Klasse. 
```php
$converter = new Converter($config);
```
Jetzt haben wir eine Instanz von dem `Converter` in der Variable `$converter` gespeichert und wir können damit arbeiten. Diese Instanz können wir auch **Objekt** nennen. 
Als Parameter zur Erstellung des Objektes, werden die in `config/public_config.php` definierten Konfigurationswerten (z.B. wo die Ausgaben gemacht werden sollen) übergeben. 
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

In dieser Funktion wird geprüft ob die eingegebene Dauer 0 ist. Wenn ja, wird es automatisch auf eine Sekunde gesetzt. 
```php
if ($duration === 0.0 || $duration === 0 || $duration === '0') {
    $duration = 1;
}
```
Dann wird der Befehl zuerst zusammengestellt und dann ausgeführt. Dieser wird von dem Programm FFMEPG interpretiert, welcher die Audio-Datei erstellt.
```php
$cmd = 'ffmpeg -f lavfi -y -i anullsrc=channel_layout=5.1:sample_rate=32000 -b:a 48K -t ' . $duration . ' ' . $this->config['silence_dir'] . '/' . $silenceName;
shell_exec($cmd);
```
