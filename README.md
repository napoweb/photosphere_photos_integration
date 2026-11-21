# Photo Sphere – 360° Panorama

**Version 0.4.62.92 (Veröffentlichungskandidat)** · Entwickler: **Stefan Sührer** · Nextcloud 35

Photo Sphere erweitert die Nextcloud **Fotos-App** und **Dateien-App** um eine interaktive 360°-Panoramaansicht. Geeignete Panoramabilder werden automatisch erkannt, **aber nicht automatisch geöffnet**. Die gewohnte Navigation und die normale Bildanzeige bleiben erhalten. Die 360°-Ansicht startet erst nach einer bewussten Benutzeraktion.

## Funktionen

- 360°-Darstellung per WebGL, Maus- und Tastatursteuerung sowie Touch-Gesten
- Vergrößerter Öffnungsbereich auf kleinen Touch-Geräten: 360°-Symbol, Steuerungshinweise und Zwischenraum bilden eine gemeinsame Touch-Schaltfläche
- Optionale Gyroskop-Steuerung auf unterstützten Smartphones
- Einstellbare Zoomgrenzen und Start-Blickwinkel, separat für Desktop und Mobilgeräte (mobiler Standard-Startwinkel: 75°)
- Sanft beschleunigender und abbremsender Tastatur-Zoom
- Ladeanimation mit tatsächlichem Downloadfortschritt und sanftem Bildübergang
- Zentrale Wasserzeichen-Verwaltung (PNG, Position und Größe)
- Wasserzeichen pro Ordner mit Vererbung an Unterordner
- Öffentliche Fotos-Alben und Dateifreigaben, einschließlich geschützter Freigaben
- Keine Veränderung der ursprünglichen JPEG-Dateien

## Verwaltung

Unter **Einstellungen → Verwaltung → 360° Panorama** lassen sich die Wasserzeichen und der Zoom konfigurieren. Die Bereiche sind thematisch getrennt.

Unter **Gespeicherte Wasserzeichen → Ordnerzuweisungen** lassen sich Ordner über einen Auswahldialog zuordnen. Der Dialog zeigt nur Ordner, die das aktuell angemeldete Konto in seinem Dateien-Bereich erreichen kann; andere Benutzerkonten lassen sich nicht frei durchsuchen. Unterordner erben die zugewiesene Grafik. Die am nächsten liegende spezifische Ordnerregel hat Vorrang; sonst gilt das Standard-Wasserzeichen.

Öffentliche Freigaben werden beim Auflösen ordnerspezifischer Wasserzeichen serverseitig geprüft. Bei nicht eindeutig bestimmbarer Zuordnung kommt die globale Standardauswahl zum Einsatz.


## 360°-Touren und Hotspots

Angemeldete Benutzer können eigene Touren aus ihren berechtigten Panorama-Dateien zusammenstellen, Bilder mit Hotspots verknüpfen und Touren verwalten. Öffentliche Tourfreigaben besitzen eigenständige Links und können je nach Freigabe mit Passwort, Ablaufdatum und Aktivierungsstatus verwaltet werden. Die bestehende Tourenübersicht kann optional über einen zusätzlichen Eintrag im Nextcloud-App-Menü geöffnet werden.

## Tour-Cache und Hintergrundjobs

Die optionale Optimierung erzeugt über Nextcloud-Hintergrundjobs getrennte Desktop-/Mobil-JPEGs im privaten AppData-Verzeichnis. Sie benötigt PHP Imagick und einen funktionierenden Hintergrundjob-Runner. Die Administrationsoberfläche zeigt Cache-Größe, Tourreferenzen und Bereinigungskandidaten, ohne fremde Datei- oder Tournamen offenzulegen. Ungenutzte App-eigene Ableitungen werden frühestens nach einer siebentägigen Schutzfrist bereinigt. Originaldateien bleiben unverändert. Die automatische Löschung nach Fristende ist vor der Freigabe noch abschließend zu verifizieren.

## Kompatibilitätsnachweis (Stand 21.11.2026)

- Nextcloud 35.0.3, ARM64, PostgreSQL, PHP 8.5: produktive Benutzung mit Viewer, Touren und Cache bestätigt.
- Nextcloud 35.0.3, ARM64, MariaDB 11.4, PHP 8.5: Installation, Tourdaten, Bildoptimierung, Cron und Cache-Statistik bestätigt.
- Noch offen: x86-64, unterstützte ältere PHP-Versionen, signiertes Paket und Abschlussprüfung der automatischen Bereinigung. Andere Nextcloud-Hauptversionen werden nicht deklariert.

## Installation und Updates

Für den regulären Einsatz nach Veröffentlichung die **offiziell signierte App-Store-Version** bevorzugen. Ein manuell installiertes Test-ZIP kann eine Nextcloud-Integritätswarnung verursachen. Vor jedem Update die bestehende App sichern. Die Wasserzeichen-Galerie und Einstellungen werden im Nextcloud-AppData-/Konfigurationsspeicher verwaltet und sollen beim App-Update erhalten bleiben.

Die Kompatibilitätsdeklaration in `appinfo/info.xml` lautet Nextcloud 35. Vor einer Veröffentlichung die jeweilige Nextcloud-Version sowie eingeloggte und öffentliche Freigaben praktisch testen.

## Versionshistorie

Die vollständige Liste aller Änderungen steht in [`CHANGELOG.md`](CHANGELOG.md). Für den Nextcloud App Store muss diese Datei im **Root des veröffentlichten App-Archivs** vorhanden sein.

## Sicherheit und Lizenz

- Administrations- und Schreibzugriffe sind auf berechtigte Konten beschränkt.
- Die Ordnerauswahl prüft Berechtigungen serverseitig; reine UI-Einschränkungen sind kein Ersatz dafür.
- Ein eingeblendetes Wasserzeichen schützt **nicht** vor dem Herunterladen der unveränderten Originaldatei.
- Lizenz: **AGPL-3.0-or-later**.
- Projekt: https://github.com/napoweb/photosphere_photos_integration

### Automatic resolution for public 360° tours (0.4.62.79)
The public tour viewer chooses 4096×2048 for touch/constrained devices and 8192×4096 for suitable desktops after checking WebGL texture limits and available device/connection hints. Consecutive slow GPU uploads or a failed large texture trigger a fallback to the smaller profile. Nextcloud may still return an older smaller cached maximum preview: these are not removed automatically. Public routes retain token/password checks and `Cache-Control: private, no-store`. Use `?ppi_preload_debug=1` for browser-local diagnostics.

### App-specific panorama image cache (0.4.62.80, experimental)
When the server has the PHP Imagick extension, panorama derivatives are prepared in the app's own private appdata directory through Nextcloud queued background jobs, after tour save or an authorized tour visit. This does not change Nextcloud's global preview limits or delete global previews. The app serves them only through existing authorized tour routes; when not yet ready or unsupported, the previous Nextcloud preview/original path remains functional. Existing tours are progressively prepared when listed by their owner or visited via an authorized public link. Background jobs require Nextcloud's configured job runner to execute.

### Background resource safeguards (0.4.62.81 Beta)
The administrator can pause/resume automatic panorama derivative processing under Photo Sphere settings. Existing derivatives are never deleted by pausing. On single-host installations a nonblocking process lock avoids two large concurrent image decoding jobs; an overlapping job is skipped and eligible for re-queue on a later authorized visit. Where supported, the worker limits ImageMagick to one processing thread. No changes are made to global Nextcloud preview settings or Cron. Admin setting does not abort an already running decode. Automatic orphan cleanup and multi-node/distributed lock coordination remain future work.

## Panorama-Cache: Bereinigung (Beta 0.4.62.84)

Bei einer Tour-Nutzung wird pro Benutzer hoechstens einmal innerhalb von 24 Stunden ein kleiner Aufraeumjob eingeplant. Er prueft jeweils hoechstens 20 unterschiedliche Bilder aus gespeicherten Touren und entfernt maximal 40 eindeutig veraltete Vorschaudateien. Alte Versionen werden nur geloescht, wenn die aktuelle Originaldatei fuer den Tour-Besitzer lesbar ist **und** sowohl die aktuelle Desktop- als auch die aktuelle Mobil-Vorschau existiert. Die aktuellen Vorschaudateien bleiben erhalten. Eine zweite Aufraeumpruefung findet bei einer spaeteren Bildvorbereitung statt, auch wenn deren aktuelle Cache-Version bereits vorhanden ist. Die Originalbilder sowie globale Nextcloud-Vorschauen bleiben unveraendert.

Der Bereinigungsjob erstellt **keine** neuen Bilddateien und ist an die vorhandene Aktivierung der Panorama-Optimierung gekoppelt.

### Ungenutzte Tour-Panoramen (Beta 0.4.62.85)
Ein separater Nextcloud-TimedJob prüft täglich den eigenen Panorama-Cache und die gespeicherten Tourreferenzen sämtlicher Benutzer über die datenbankunabhängige Nextcloud-QueryBuilder-Schnittstelle. Für Cache-Dateien, deren Datei-ID in keiner gespeicherten Tour mehr referenziert wird, beginnt eine Sicherheitsfrist von **7 vollen Tagen** ab der ersten Erkennung. Erst danach und nach einer erneuten vollständigen Referenzprüfung entfernt der Job ausschließlich die betroffenen App-Cache-Dateien. Neu angelegte oder wiederverwendete Cache-Versionen setzen die Frist zurück. Pro Lauf werden höchstens 40 Datei-IDs überprüft. Ein unbekannter oder fehlerhafter Datenzustand verhindert die Löschung (fail closed); große Installationen oberhalb der Sicherheitslimits bleiben unverändert und benötigen eine spätere skalierbare Lösung. Die Originaldateien, Nextclouds globaler Vorschau-Cache, die bestehenden Touren und der Viewer bleiben unberührt.

Wichtig: Die Sicherheitsfrist startet erst nach Installation dieser Version und nachdem der tägliche Job eine ungenutzte Datei erstmalig festgestellt hat. Für die öffentliche App-Store-Veröffentlichung sind Kompatibilitäts- und Berechtigungstests auf PostgreSQL sowie MariaDB/MySQL nötig.

### Cache-Statistik (Admin)

Der Administrationsbereich zeigt die Anzahl und Größe der im App-Cache gespeicherten Panorama-Vorschaudateien, ihre Verwendung in gespeicherten 360°-Touren sowie vorgemerkte Löschkandidaten (7-Tage-Schutzfrist). Die Ansicht ist rein lesend und für Administratoren reserviert; bei unvollständig lesbaren Tourreferenzen wird die Auswertung verweigert. Sie nutzt Nextclouds QueryBuilder und Appdata-API.
