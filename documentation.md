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

### Audio erstellen