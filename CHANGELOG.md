## 0.4.27 – 2026-11-18

### Verbessert
- Admin-Bereich thematisch geordnet: Wasserzeichen-Einstellungen, Galerie, Upload und Ordnerhilfe bilden einen gemeinsamen Abschnitt; Zoom und Blickwinkel stehen anschließend separat.
- Klar erkennbare Überschriften für die beiden Einstellungsgruppen.
- CHANGELOG.md für den Nextcloud App Store in einem einheitlichen, kompatiblen Versionsformat gepflegt (## Versionsnummer).

### Unverändert
- Gyroskop- und Touchsteuerung, Zoom-Berechnung, Wasserzeichen, Ordnerregeln und öffentliche Freigaben unverändert.

## 0.4.26 – Testversion – 2026-11-18

- Gyroskop-Menü: Text und Ein/Aus-Schaltfläche bleiben zueinander zentriert und sind innerhalb der Lasche gemeinsam etwas nach links versetzt.
- Eingeklappte Lasche ragt geringfügig weiter hervor: 14 statt 10 Pixel.
- Zweimalige Hinweisanimation verwendet weiterhin einen zusätzlichen Ausfahrweg von 20 Pixeln.
- Gyroskop-Glättung moderat angepasst: Zeitkonstante 0,18 statt 0,22 Sekunden; winzige Sensorabweichungen unter etwa 0,06 Grad werden nicht mehr nachgeführt.
- Keine Änderungen an Gyroskop-Achsen, übriger Touchsteuerung, Zoom, Wasserzeichenverwaltung und öffentlichen Freigaben.

## 0.4.25 – Testversion – 2026-11-18

- Gyroskop-Lasche: rechte Rahmenlinie entfernt; obere, untere und linke Rahmenlinie bleiben erhalten.
- Schmalerer sichtbarer Griff im eingeklappten Zustand und insgesamt kompaktere Ausfahrweite.
- Beschriftung und Gyroskop-Schaltfläche innerhalb der einteiligen Menüfläche horizontal zentriert.
- Gyroskop-Nachführung leicht stärker geglättet (Zeitkonstante 0,22 statt 0,16 Sekunden).
- Die gesamte ausgefahrene Menüfläche lässt sich zum Einklappen antippen; der unabhängige Gyroskop-Schalter löst nur seine eigene Aktion aus.
- Anzeigepunkt nach dem Laden, zweimalige Hinweisbewegung, Sensorachsen, Sicherheit und Wasserzeichen unverändert.

## 0.4.24 – Gyroskop-Lasche wieder anklickbar

- Fehler bei der einteiligen Gyroskop-Lasche behoben: Im eingeklappten Zustand bleibt nun der anklickbare Griff sichtbar statt des ausgeblendeten Menüs.
- Bestehende Gyroskop-Bewegung, Ladezeitpunkt, Hinweisanimation und alle übrigen Funktionen unverändert.

## 0.4.23 – 2026-11-18

- Gyroskop-Menü und seitlicher Griff zu einem einzigen verschiebbaren, gemeinsam umrandeten Bedienelement zusammengeführt.
- Die schmale Lasche erscheint erst nach Abschluss der Panorama-Ladeanzeige und dem anschließenden Blur-Übergang.
- Zweimalige Hinweisanimation nach dem Laden beibehalten; bei reduzierten Bewegungen deaktiviert.
- Gyroskopbewegung, Zoom, Wasserzeichen und öffentliche Freigaben unverändert.

## 0.4.22 – 2026-11-18

- Gyroskop-Lasche: Beim Öffnen fährt der Griff nach links, das Bedienfeld erscheint rechts daneben; nach Auswahl wird automatisch geschlossen.
- Griff schmaler und dezenter: Hintergrundtransparenz und Randfarbe an „360° schließen“ angeglichen; Symbol entfernt.
- Zweimalige kurze Hinweisbewegung der Lasche nach dem ersten gerenderten Bild; deaktiviert bei reduzierter Bewegung.
- Admin-Bereich: Einheitliche Einstellungsüberschrift in einem gerahmten Einleitungsabschnitt.
- Gyroskop-Achsen, Zoomverhalten, Wasserzeichen und Sicherheitslogik unverändert.

## 0.4.21 – Testversion – 2026-11-18

- Gyroskop: Horizontalrichtung (links/rechts) korrigiert; vertikale Bewegung unverändert.
- Ausklappbare Gyroskop-Lasche im unteren Bildschirmviertel, schmaler und platzsparender gestaltet.
- Gyroskop-Menü klappt nach Ein-/Ausschalten automatisch wieder ein.
- Zoom-Standardwerte Desktop: maximaler Zoom-in 60°, Start-Blickwinkel 110°, Zoom-out weiterhin 135°.
- Bereits gespeicherte individuelle Zoomwerte bleiben erhalten; „Standardwerte wiederherstellen“ übernimmt die neuen Werte.
- Wasserzeichen, Dateizugriffe und öffentliche Freigaben unverändert.

## 0.4.20 – Testversion – 2026-11-18

- Stärkeres Ease-in/Ease-out für Tastatur-Zoom (0,90 s Beschleunigung / 0,70 s Abbremsen).
- Zoomgeschwindigkeit in Blickwinkel-Grad pro Sekunde berechnet, unabhängig von eingestellten Zoomgrenzen.
- Gyroskop über geräteorientierte Kameraprojektion statt rohen Euler-Winkeln berechnet, Achsen korrigiert, Sprungfilter und Kalibrierung bei Drehung.
- Gyroskop-Bedienung als kompakte, ausklappbare Lasche am rechten Bildschirmrand.
- Admin-Aktion „Standardwerte wiederherstellen“ speichert die ursprünglichen Zoom-Einstellungen wieder.
- Bestehende Wasserzeichen-Verwaltung und öffentliche Freigabeprüfung aus 0.4.19 unverändert.

## 0.4.19 – Testversion – 2026-11-18

- Sanftes Beschleunigen und Abbremsen beim Tastatur-Zoom (Strg + Pfeil oben/unten), ohne Änderung der direkten Touch-Zoomgeste.
- Neuer, separater Zoom-Einstellungsbereich: Zoom-in, Startwinkel und Zoom-out pro Desktop und Smartphone.
- Optionale Gyroskop-Steuerung im mobilen Panorama-Viewer, standardmäßig deaktiviert, bei Bedarf mit Sensorfreigabe.
- App-Beschreibung überarbeitet: Fotos- und Dateien-Integration und ausschließlich manuelles Öffnen ausdrücklich hervorgehoben.
- Bestehende Wasserzeichen-/Ordner-Funktionalität und öffentliche Freigaben unverändert.

## 0.4.18

### Security
- Added share type and read permission checks for folder-specific watermark resolution.
- Added frontend session authentication checks for password-protected public links.
- Uses Nextcloud's share manager for share validity checks.

### Notes
- Release candidate tested on Nextcloud 35.0.3.
- Manual tests covered Photos, Files, panorama controls, watermarks and public shares.

## 0.4.17 – 2026-11-15

- App-Beschreibung in den App-Details sprachlich überarbeitet und kompakt auf die Kernfunktionen konzentriert.
- Hinweis zur Ordnerauswahl an die Sicherheitsänderung aus 0.4.16 angepasst: keine Benutzerwahl mehr.
- Sonstige Funktionen, Rechteprüfungen und gespeicherte Einstellungen unverändert.

## 0.4.16 – Sicherheitskorrektur

- Kritische Einschränkung: Der Ordnerdialog kann nur noch den Dateien-Bereich des angemeldeten Administrator-Kontos einschließlich berechtigt zugänglicher Freigaben auflisten.
- Keine Benutzerkonto-Suche und kein Durchsuchen fremder Benutzerverzeichnisse mehr.
- Serverseitige Rechteprüfung auch bei direkten Browser-, Zuordnungs- und Löschanfragen.
- Alte Ordnerregeln werden beibehalten, aber fremde, nicht erreichbare Ordner nicht mehr in der Verwaltung angezeigt.
- Die 360°-Darstellung und die öffentlichen Freigaben bleiben unverändert.

## 0.4.15 – 2026-11-15

- Ordnerzuweisungen jetzt über einen klickbaren Nextcloud-Ordnerbrowser statt manuell eingegebener Pfade.
- Ordnerbesitzer per Auswahlliste, Ordnernavigation mit Zurück und direkter Übernahme.
- Speicherung weiterhin über bestehende Ordner-ID-Regeln; Unterordner-Vererbung, Galeriefunktionen und öffentliche Freigaben bleiben erhalten.
- Hilfetext für die Ordnerauswahl aktualisiert.

## 0.4.14 – 2026-11-15

- Ordnerbezogene Wasserzeichen: Vererbung an Unterordner; spezifischste Regel gewinnt, sonst Standard-Wasserzeichen.
- Verwaltung direkt in den Galerie-Kacheln, mit eingeklappter Hilfe am unteren Seitenrand.
- Autor: Stefan Sührer; kürzere Funktionsbeschreibung.
- Mitgeliefertes Wasserzeichen durch neues 360°-Drohnenmotiv ersetzt.
- Öffentliche Dateifreigaben: Ordnerzuordnung nur nach Prüfung des Freigabetokens; sonst Standardauswahl.
- Nextcloud 35 einschließlich 35.0.3 in der Versionsdeklaration. Keine Aussage über App-Store-Verfügbarkeit.
- Bestehende Steuerung, Fortschrittsanzeige, AppData-Galerie und Positionierungsoptionen unverändert.

## 0.4.13 – 2026-11-10

- Maximalen Zoom-out auf Touch-Geräten deutlich von 128° auf 110° begrenzt; mobiler Start-Blickwinkel bleibt 90°, Desktop unverändert.
- Echten JPEG-Downloadfortschritt integriert: Prozentangabe, übertragene MB, Fortschrittsbalken und Aufbau der verschwommenen Bildvorschau von oben nach unten während des Dateitransfers.
- Bei fehlendem Content-Length wird bewusst nur ein unbestimmter Ladeindikator statt erfundener Prozentwerte gezeigt.
- Der fertige WebGL-Panorama-Frame geht danach sanft von weichgezeichnet nach scharf über; nachträgliche künstliche Ladeanimation entfernt.
- Abbruch laufender JPEG-Downloads beim Schließen, Freigaben und Wasserzeichenverwaltung erhalten.

## 0.4.12 – 2026-11-10

- Auf Smartphone/Touch-Geräten maximalen Zoom-out leicht reduziert (Blickwinkel 128° statt 135°).
- Mobiler Startblickwinkel von 86° auf 90° erweitert (etwas weiter herausgezoomt).
- Ladeeffekt für Desktop und Smartphone: fertiges 360°-Bild baut sich wieder von oben nach unten sichtbar auf; anschließend wird das weichgezeichnete Panorama sanft scharf.
- Desktop-Zoom, Panorama-Steuerung, Wasserzeichen-Verwaltung und öffentliche Freigaben unverändert.

## 0.4.11 – 2026-11-09

- Einheitliche Breite (bis 840 px) für alle eingerahmten Abschnitte der Wasserzeichen-Verwaltung.
- Positionierung, Panorama-/Bildschirmposition, Größe, Ein/Aus, Speichern, Galerie und PNG-Upload übersichtlich in eigene Rahmen gegliedert.
- Keine Änderungen an Speicherung, Upload, Wasserzeichen-Auswahl oder dem 360°-Betrachter.

## 0.4.10 – 2026-11-09

- Doppelte Schaltfläche „Kein Wasserzeichen auswählen“ unterhalb der Galerie entfernt; die Galerie-Kachel bleibt erhalten.
- Positionierung als zwei direkt auswählbare Radiokarten statt Dropdown, mit Erklärungen zu Bildschirm- und Panoramapositionierung.
- Bestehende gespeicherte Einstellungen, Galerie-API und Panorama-Steuerung unverändert.

## 0.4.9 – 2026-11-09

- Verwaltungsregler wieder vollständig horizontal; Desktop- und Smartphone-Bereiche klar getrennt.
- Größenregler direkt unter der zugehörigen Bezeichnung und dem aktuellen Wert platziert.
- Auswählen prüft nun die vom Server bestätigte aktive Grafik und meldet Fehler sichtbar.
- Mitgelieferte NapoHD-Standardgrafik aus der Galerie ausblendbar und wiederherstellbar, ohne die App-Datei zu verändern.
- Galerie zählt nur selbst gespeicherte PNGs für die Maximalzahl fünf.
- Sonstige Darstellung und 360°-Steuerung aus 0.4.8 unverändert.

## 0.4.8 – 2026-11-09

- Galerie und Vorschau: GET-Abfragen von der CSRF-Pflicht ausgenommen, weiterhin nur mit Administratorrechten; Schreibaktionen bleiben CSRF-geschützt.
- Neues Standardverhalten: keine Wasserzeichengrafik ausgewählt, vorhandene Grafikdateien werden beibehalten.
- Einstelloption „Kein Wasserzeichen“ in der Galerie und Zurücksetzen-Schaltfläche.
- Höhenregler für Desktop, Mobilgerät und feste Bildschirmposition vertikal angeordnet.
- Vorhandene Panorama-Steuerung und öffentliche Freigaben unverändert.

## 0.4.7 – 2026-11-09

- Erweiterte Wasserzeichenverwaltung unter Einstellungen → Verwaltung → 360° Panorama.
- Ein-/Aus-Schalter für Wasserzeichen in allen 360°-Ansichten.
- Umschalten zwischen fester Bildschirmposition und fester Position innerhalb des Panoramas.
- Konfigurierbare Position: horizontale/höhenbezogene Winkel für Panorama (getrennte Höhen für Desktop und Smartphone), Bildschirmposition in Prozent.
- Konfigurierbare Wasserzeichenbreite getrennt für Desktop und Mobilgerät.
- Galerie mit maximal fünf eigenen PNG-Wasserzeichen plus mitgelieferter Standardgrafik; vorhandenes Wasserzeichen aus 0.4.6 wird übernommen.
- Hochladen, Auswählen und Löschen der gespeicherten Grafiken; die Standardgrafik bleibt dauerhaft verfügbar.
- Einstellungen werden in Nextcloud-App-Konfiguration, Grafiken in AppData gespeichert; öffentliche Freigaben erhalten nur das aktive Wasserzeichen.
- Die eigentliche Panorama-Darstellung, das Touch-Verhalten, die Tastatursteuerung, die Ladeanimation und Freigabeprüfung bleiben unverändert.

## 0.4.6 – 2026-11-09

- Wasserzeichen in der 360°-Welt auf Desktop leicht, auf Smartphones deutlich höher positioniert.
- Eigener Nextcloud-Verwaltungsbereich „360° Panorama“ zum Hochladen einer PNG-Datei (bis 2 MB), zur Vorschau und zum Zurücksetzen auf die NapoHD-Standardgrafik.
- Hochgeladene Grafiken werden im privaten AppData-Speicher abgelegt und bleiben bei App-Updates erhalten.
- Öffentliche Freigaben können die eingestellte Wasserzeichengrafik anzeigen; nur Administratoren dürfen sie ändern.
- Funktionierende Version 0.4.5 als Basis unverändert übernommen.

## 0.4.5 – 2026-11-09

- Automatisches PNG-Wasserzeichen in jeder 360°-Ansicht ergänzt.
- Wasserzeichen ist fest im Panorama verankert und erscheint im oberen Bildbereich (Himmel).
- Verwendet die mitgelieferte Grafik `img/watermark-napohd.png`.
- Anzeige nur im Viewer; Originaldateien und Freigaben bleiben unverändert.

## 0.4.4 – 2026-11-09

- Direkte mobile Ein-Finger-Steuerung: Das Panorama folgt ohne zeitliche Nachführung unmittelbar der Fingerbewegung.
- Mobilen Zwei-Finger-Zoom ebenfalls direkter an die Geste gekoppelt.
- Desktop-Startblickwinkel leicht von 110° auf 114° erweitert, sodass beim Öffnen etwas mehr Umgebung sichtbar ist.
- Mobiler Startblickwinkel bleibt bei 86°, maximaler Zoom-out bei 135°.
- PC-Tastatursteuerung, Design, Lade-/Blur-Übergang und öffentliche Freigaben unverändert.

## 0.4.3 – 2026-11-09

- Touch-Wischsteuerung direkter abgestimmt: geringere Verzögerung, höhere Wischempfindlichkeit.
- 360°-Öffnen-Grafik auf kleinen Touch-Geräten weiter verkleinert.
- Zweizeilige Steuerungshinweise auf kleinen Touch-Geräten kompakter gestaltet.
- Sanfter Übergang: fertig gerendertes 3D-Panorama wird während der Auflösung der Ladeanimation von leicht unscharf zu scharf überblendet.
- PC-Blickwinkel, Dateien-/Fotos-Integration, öffentliche Freigaben und bestehende Tastatursteuerung unverändert.

## 0.4.2 – 2026-11-09

- Zoom-Schaltflächen in der mobilen 360°-Ansicht wieder entfernt; Zwei-Finger-Zoom bleibt.
- Mobile Wischsteuerung mit gedämpfter Nachführung und geringerer Empfindlichkeit verbessert.
- Engerer Standardblickwinkel beim Öffnen auf Touch-Geräten; PC-Startzoom unverändert.
- Öffnen-Grafik auf schmalen Touch-Geräten verkleinert.
- Ladeanimation auf Mobilgeräten mindestens kurz sichtbar; Hintergrund nutzt nach Möglichkeit die bereits geladene Bildvorschau.

## 0.4.1

- Größerer sichtbarer Bildbereich beim Öffnen und Herauszoomen (bis 135°).
- Zwei-Finger-Zoom auf Touch-Geräten sowie zusätzliche Zoom-Tasten.
- Ladebild mit Weichzeichnung und animiertem Kreis bis zur ersten gerenderten Panoramaansicht.
- Alle bisherigen Funktionen, Dateien- und Fotos-App sowie öffentliche Freigaben beibehalten.

# Liste der Veränderungen

## 0.4.0 – 2026-11-09

### Neu
- Integrierter 360°-WebGL-Betrachter ohne Abhängigkeit von `files_photospheres`.
- Eigene Kugelprojektion für equirektanguläre JPEG-Panoramen mit Maus, Zoom und sanfter Pfeiltastensteuerung.
- XMP-Fallback aus JPEG-Headern bei angemeldeten Benutzern ohne die vorherige WebDAV-Viewer-Erweiterung.
- Bestehende Bedienoberfläche und öffentliche Freigaben übernommen.

### Hinweis
- Experimentelle Erstversion: Browserprüfung einschließlich großer und zugeschnittener Panoramen erforderlich.

## 0.3.20 – 2026-11-09

### Behoben
- Einbindung der Erweiterung auf öffentlichen Datei- und Ordnerfreigabeseiten ergänzt.
- Erkennung von öffentlichen JPEG-Dateiquellen über den vorhandenen Freigabezugriff ergänzt.

### Geändert
- App-Beschreibung auf die dauerhaften Funktionen konzentriert; Versionshinweise in diese separate `CHANGELOG.md` ausgelagert.
- Dateiaktionsschutz auf öffentliche Dateifreigaben erweitert, ohne die Original-App zu verändern.

## 0.3.19 – 2026-11-09

### Hinzugefügt
- Unterstützung des normalen Bildbetrachters in der Nextcloud Dateien-App; 360°-Ansicht wird auf Wunsch gestartet.

### Geändert
- Sanftes Beschleunigen und Abbremsen mit den Pfeiltasten verstärkt.

## 0.3.18 – 2026-11-08

### Hinzugefügt
- Sanftes Beschleunigen, Abbremsen und weiche Richtungswechsel bei Pfeiltastensteuerung.

## 0.3.17 – 2026-11-08

### Geändert
- Weiße Umrandung und dezenter Schlagschatten für die gesamte Steuerungshilfe.

## 0.3.16 – 2026-11-08

### Geändert
- Pfeiltastensymbole hervorgehoben und Druckeffekt für Schließen-Button ergänzt.

## 0.3.15 – 2026-11-08

### Hinzugefügt
- Sanfter Hover- und Druckeffekt für die Öffnen-Grafik.

## 0.3.14 – 2026-11-08

### Geändert
- Öffnen-Grafik verkleinert und Steuerungshinweise auf zwei Zeilen verteilt.

## 0.3.13 – 2026-11-08

### Hinzugefügt
- Eigenes grafisches 360°-Öffnen-Motiv und Steuerungshilfe.

## 0.3.12 – 2026-11-08

### Hinzugefügt
- Unterstützung öffentlicher Nextcloud Fotos-Alben mit XMP-Erkennung aus freigegebenen JPEG-Dateien.
