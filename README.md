# Photo Sphere – 360° Panorama

**Version 0.4.27** · Entwickler: **Stefan Sührer** · Nextcloud 35

Photo Sphere erweitert die Nextcloud **Fotos-App** und **Dateien-App** um eine interaktive 360°-Panoramaansicht. Geeignete Panoramabilder werden automatisch erkannt, **aber nicht automatisch geöffnet**. Die gewohnte Navigation und die normale Bildanzeige bleiben erhalten. Die 360°-Ansicht startet erst nach einer bewussten Benutzeraktion.

## Funktionen

- 360°-Darstellung per WebGL, Maus- und Tastatursteuerung sowie Touch-Gesten
- Optionale Gyroskop-Steuerung auf unterstützten Smartphones
- Einstellbare Zoomgrenzen und Start-Blickwinkel, separat für Desktop und Mobilgeräte
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

## Installation und Updates

Für den regulären Einsatz die **offiziell signierte App-Store-Version** bevorzugen. Ein manuell installiertes Test-ZIP kann eine Nextcloud-Integritätswarnung verursachen. Vor jedem Update die bestehende App sichern. Die Wasserzeichen-Galerie und Einstellungen werden im Nextcloud-AppData-/Konfigurationsspeicher verwaltet und sollen beim App-Update erhalten bleiben.

Die Kompatibilitätsdeklaration in `appinfo/info.xml` lautet Nextcloud 35. Vor einer Veröffentlichung die jeweilige Nextcloud-Version sowie eingeloggte und öffentliche Freigaben praktisch testen.

## Versionshistorie

Die vollständige Liste aller Änderungen steht in [`CHANGELOG.md`](CHANGELOG.md). Für den Nextcloud App Store muss diese Datei im **Root des veröffentlichten App-Archivs** vorhanden sein.

## Sicherheit und Lizenz

- Administrations- und Schreibzugriffe sind auf berechtigte Konten beschränkt.
- Die Ordnerauswahl prüft Berechtigungen serverseitig; reine UI-Einschränkungen sind kein Ersatz dafür.
- Ein eingeblendetes Wasserzeichen schützt **nicht** vor dem Herunterladen der unveränderten Originaldatei.
- Lizenz: **AGPL-3.0-or-later**.
- Projekt: https://github.com/napoweb/photosphere_photos_integration
