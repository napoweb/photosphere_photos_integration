# Photo Sphere – 360° Panorama

An interactive 360° panorama viewer for Nextcloud 35.

**Version:** 0.4.18 (release candidate)
**Developer:** Stefan Sührer
**License:** AGPL-3.0-or-later

## English

Photo Sphere adds an interactive WebGL-based 360° panorama
viewer to Nextcloud Photos, Files and supported public shares.

### Features

- Detection of suitable 360° JPEG panoramas using XMP metadata
- Interactive equirectangular panorama viewer
- Mouse, keyboard and touch navigation
- Smooth keyboard movement and zoom controls
- JPEG loading progress with visual feedback
- Configurable PNG watermarks
- Watermark gallery with custom images
- Folder-specific watermark assignments
- Watermark inheritance for subfolders
- Administrative folder browser
- Support for public file and folder shares
- No modifications to original JPEG files

### Requirements

- Nextcloud 35
- Nextcloud Photos and Viewer
- Browser with WebGL support

The separate files_photospheres app is not required
for the integrated panorama renderer.

### Security

Folder assignments are restricted to directories accessible
to the currently signed-in administrator.

Public-share watermark resolution includes checks for
share type, read permissions and password authentication.

Version 0.4.18 is a release candidate.
Final integration and security tests are still required.

## Deutsch

Photo Sphere erweitert Nextcloud Fotos, Dateien und
unterstützte öffentliche Freigaben um einen interaktiven
360°-Panoramabetrachter.

### Funktionen

- Erkennung geeigneter 360°-JPEGs anhand von XMP-Metadaten
- Interaktiver WebGL-Panoramabetrachter
- Steuerung mit Maus, Tastatur und Touch
- Sanfte Pfeiltastenbewegung und Zoom
- JPEG-Ladefortschritt mit visueller Rückmeldung
- Konfigurierbare PNG-Wasserzeichen
- Galerie für eigene Wasserzeichengrafiken
- Ordnerbezogene Wasserzeichen mit Unterordner-Vererbung
- Ordnerbrowser für Administratoren
- Unterstützung öffentlicher Datei- und Ordnerfreigaben
- Keine Veränderung der Original-JPEG-Dateien

### Voraussetzungen

- Nextcloud 35
- Nextcloud Fotos und Viewer
- Browser mit WebGL-Unterstützung

Die separate App files_photospheres wird für den
integrierten Panoramabetrachter nicht benötigt.

### Sicherheit

Ordnerzuweisungen sind auf Ordner beschränkt, die
für das angemeldete Administratorkonto erreichbar sind.

Die öffentliche Wasserzeichenauflösung enthält Prüfungen
für Freigabetyp, Leseberechtigung und Passwortauthentifizierung.

Version 0.4.18 ist ein Release-Kandidat.
Abschließende Funktions- und Sicherheitstests stehen noch aus.

## Installation

The application is currently being prepared for publication
in the official Nextcloud App Store.

Manual installation and updates should be performed only
after creating a backup of the existing installation.

## License

AGPL-3.0-or-later. See LICENSE.
