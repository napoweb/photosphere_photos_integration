## 0.4.63

- App Store-compatible release version.
- Same tested functionality as 0.4.62.140.
- No functional changes.

## 0.4.62.138 (Testversion)
- Gemeinsame, responsive Fehlerdarstellung für ungültige, abgelaufene und deaktivierte öffentliche Tour- und Startseiten-Links.
- Statuscodes und öffentliche Zugriffsprüfungen unverändert; keine Änderung an Tour-Passwortfreigaben.

## 0.4.62.137
- Im Startseiten-Reiter „Vorlagen“ werden Standard- und eigene Vorlagen links als klickbare Miniaturkarten mit dezentem Schatten angezeigt.
- Die bestehende Live-Vorschau wird rechts im Vorlagen-Reiter wiederverwendet; Auswahl aktualisiert das Design nur ungespeichert.
- Responsive Anordnung für schmale Bildschirme.

## 0.4.62.136 – Vorlagen als Reiter im Startseiten-Editor

- Vorlagenverwaltung als Reiter 3 direkt hinter „Touren wählen“; Layout und Freigabe sind Reiter 4 und 5.
- Kein separater Vorlagenpunkt in der oberen Hauptnavigation.
- Laden einer Vorlage wirkt nur auf die gerade bearbeitete Startseite und wechselt zur Layout-Vorschau.
- Aktualisieren einer eigenen Vorlage übernimmt die aktuellen Layout-Felder.
- „Vorlage speichern“ bleibt oberhalb der Live-Vorschau im Layout-Reiter.

## 0.4.62.135 – Zentrale Startseiten-Vorlagenverwaltung

- Neuer Hauptmenüpunkt „Vorlagen“ neben „360°-Touren“ und „Startseiten“.
- Auswahl, Zuordnung und Verwaltung aller Startseiten-Designvorlagen in einer eigenen Übersicht.
- „Vorlage speichern“ nur im Layout-Tab direkt über der Live-Vorschau.
- Mitgelieferte Designs lassen sich als persönliche Vorlagen kopieren und dann ändern, exportieren oder wieder importieren.
- Beim Laden wird die Zielstartseite im Layout-Editor geöffnet; erst „Änderungen speichern“ schreibt das Design in die Startseite.
- Texte, Tourzuordnungen und Freigabeeinstellungen bleiben unangetastet.
- Beinhaltet die Menü-Ausblendekorrektur und die reduzierten inneren Vorschaubild-Schatten.

## 0.4.62.133 (Docker test – Menüsteuerung)
- Hide the top-level 360° Touren / Startseiten switch while editing a tour or choosing panoramas.
- Restore the switch when returning to the tour overview; keep Startseiten behavior unchanged.
- No changes to tour/share data, cache, permissions or background jobs.
- Local test build is unsigned; a signed release requires re-signing by the app publisher.

## 0.4.62.132 (Integration Beta – Docker test only)
- Merge permission-scoped Nextcloud tag selection from the productive 0.4.62.110 package into the 0.4.62.131 tour editor, preserving the newer start-page tools.
- Restore filename-or-tag picker mode and selected tag-ID filtering with user-visible tag validation.
- Restore cumulative JPEG size filters: under 20 MB and over 20/30/40/50 MB, and panorama counts.
- Restore secure app-cache statistics: show source name and file ID only when resolvable through the administrator's own readable Nextcloud mounts or own tour references.
- Leave background-job deletion rules and seven-day safety grace unchanged. Run server PHP lint, JS syntax check, tag-rights tests, and Docker testcloud integration tests before any production use.

## 0.4.62.92
- Refresh App Store metadata to describe the 360-degree tour editor, hotspots, public sharing, responsive viewer and app-owned panorama cache.
- Update documentation and publication checklist; no changes to application runtime behavior, routes, access controls or cache deletion rules.
- Release candidate for compatibility and signed-package validation; do not publish as stable before the remaining checks are complete.

## 0.4.62.91 (Security Beta)
- Fix removing the legacy tour share password inadvertently stripping password hashes from independent additional tour share links. The additional links keep their own password protection intact.
- All unrelated viewer, tour, app navigation, cache, watermark and cleanup functionality unchanged.

## 0.4.62.90 (Beta)
- Optional admin-controlled 360°-Touren navigation shortcut using Nextcloud navigation event.
- Admin settings grouped as Wasserzeichen, Zoom und Blickwinkel, Panorama-Cache and Navigation.
- Navigation off by default, existing tours and panorama cache unchanged.

## 0.4.62.89 – Cache-Statistik: verschiebbare Spaltentrenner
- Cache-Tabellenspalten über ziehbare Kopfzeilen-Trenner anpassbar (auch per Tastatur).
- Lange Dateinamen und Zellen bleiben innerhalb ihrer Spalten, Tabelle horizontal scrollbar.
- Keine Änderungen an Cache-Bereinigung, Berechtigungen oder Tour-Viewer.

## 0.4.62.88 (Beta)
- Admin-Cache-Statistik: feste Spaltenbreiten und echte horizontale Scrollfläche beheben überlappende Dateinamen und Tournamen.
- Lange Dateinamen werden gekürzt angezeigt; der vollständige Name ist per Mauszeiger verfügbar. Barrierearme Tastatur-Navigation für die Tabelle.
- Datenschutz, Bereinigung, Schutzfrist, Tour-Viewer und Cache-Erzeugung unverändert.

## 0.4.62.87 (Beta)
- Cache-Statistik: datei- und tourbezogene Namen ausschließlich aus eigenen gespeicherten Touren; für fremde oder nicht zuordenbare Einträge keine Namen oder Datei-IDs in der API-Antwort.
- Eigene Panoramen: zugehörige Tournamen, auch bei Verwendung in mehreren Touren.
- Cache-Tabelle mit eigenständiger Tournamenspalte, größeren Abständen und horizontalem Scrollen.
- Unverändert: Hintergrundbereinigung, 7-Tage-Sicherheitsfrist, Tour-Viewer und Bild-Cache.

## 0.4.62.86 (Beta)
- Admin read-only panorama cache statistics: total cached bytes, active tour references across users, currently used / unused / grace-period panoramas, filtered per-ID details.
- Admin statistics endpoint fails closed if a full cross-user tour-reference scan fails; no caching, image conversion or deletion is triggered by viewing statistics.
- Daily orphan cleanup job marked TIME_INSENSITIVE per Nextcloud 35 API guidance.
- Existing cache files, seven-day grace markers, tour viewer and resolution profiles unchanged.

## 0.4.62.85 – Unused tour panorama cleanup after safety grace (Beta)
- Introduce a daily Nextcloud TimedJob to inspect only the private panorama_derivatives_v1 app cache, even when the owner has removed their last tour; no server path or external utilities assumed.
- Identify unused file IDs across ALL saved Photo Sphere user tour preferences through Nextcloud's IDBConnection QueryBuilder; no PostgreSQL-specific JSON functions, compatible in principle with MariaDB/MySQL.
- Retain orphaned derivatives for at least 7 full days from first confirmed absence. Changes to the matching cache filenames reset the grace period; recheck all tour references immediately before deletion.
- Process at most 40 cache IDs per daily job. If references cannot be read completely, appear malformed, or scan limits are reached, fail closed and do not delete.
- Preserve the old in-tour stale-version cleanup, previously generated derivatives, admin settings, authorized public routes and viewer/preload logic.
- Original panoramas and global Nextcloud preview cache are never deleted. The existing optimization enable switch also pauses cleanup.
- The new orphan behavior needs a live integration test on Nextcloud, including a separate MariaDB/MySQL test installation before release.

## 0.4.62.84 – Safe automatic cleanup of outdated panorama versions (Beta)
- When a tour is visited, queue at most one lightweight cleanup job per user every 24 hours, using the existing Nextcloud background-job system.
- Process up to 20 distinct images from that user’s saved tours per cleanup run; inspect only the private app-owned panorama_derivatives_v1 folder, never global previews.
- Delete only older derivative versions of a verified, still-readable tour source when BOTH current desktop/mobile derivatives already exist; limit deletions to 40 per run.
- Rotate a per-user cursor between runs, to avoid repeatedly checking only the first 20 images in larger tour libraries.
- Also prune stale versions when both current derivatives are already cached, without re-encoding the panorama.
- Never delete cache entries just because a tour or image was removed. Orphan cleanup remains deliberately out of scope until sharing and multi-tour references are fully accounted for.
- Continue using the existing enabled toggle, worker settings, existing cache filenames, authorisation checks, tour viewer and preload behaviour.

## 0.4.62.83 – Panorama cache settings CSRF fix (Beta)
- Allow admin-only read-only panorama-cache status GET without CSRF token, as recommended for Nextcloud GET endpoints; writing POST retains CSRF protection and admin access checks.
- Improve request-token lookup fallback for saving the admin settings.
- Cache preparation, existing derivatives, zoom/renderer, public tours and viewer scripts unchanged.

## 0.4.62.82 – Tour cache administration (Beta)
- Moved Panorama-Optimierung directly below Zoom und Blickwinkel with explicit tour-only scope.
- Fixed disabled switch when the status request fails and improved UI error feedback.
- Configurable one to three concurrent panorama preparations (default one), single-host file locking slots.
- Existing appdata derivatives, public tours, renderer and browser preloading remain unchanged.
- A job that finds every slot occupied is skipped and may be requeued by a later tour visit.

## 0.4.62.81 – Panorama background stability (Beta)

- Add administrator switch for pausing/resuming panorama derivative generation; cached files continue to be served while paused.
- Check the switch when queueing and again when jobs execute; no changes to Nextcloud global Cron configuration.
- Use a nonblocking single-worker file lock for image decoding on a single-host installation; skip conflicting jobs (a later authorised tour visit can requeue them).
- Limit ImageMagick worker threads to one where supported; no global ImageMagick policy changes.
- Record derivative-creation success and relevant failures in the Nextcloud log.
- Keep existing cache filenames and data, private authorization checks, resolution profiles, original-image fallback, and tour viewer unchanged.
- Existing per-file stale derivative cleanup remains; orphan cleanup and distributed multi-node locking are not yet implemented.

## 0.4.62.79 – Automatic device-aware 360° tour resolution

- Public tours select a 4096×2048 or 8192×4096 server-preview profile automatically based on WebGL texture limits, coarse pointer, available device memory/CPU hints, and connection/data-saving hints.
- Keep a single consistent image URL/profile for each in-flight preload and foreground navigation; avoid reusing cached neighbours across profile changes.
- After two consecutive slow desktop GPU uploads (650 ms or more), future panoramas use the 4096 profile. A failed 8192 texture load retries the current panorama once at 4096.
- Preserve all existing authorised public-tour image routes, `no-store` headers, original downloads, hotspot/zoom behaviour, and Firefox ImageBitmap orientation handling.
- Existing 4096 maximum previews must be regenerated individually if 8192 previews are desired; no global preview-cache cleanup is performed by this release.

## 0.4.62.77 – Server-Vorschauen auch auf Desktop (Test)

- Öffentliche Desktop-Touren nutzen den bereits vorhandenen, token-/passwortgeschützten Nextcloud-Vorschau-Endpunkt mit Zielgröße 8192×4096 (ohne Zuschneiden).
- Smartphones bleiben bei der bestätigten Variante 4096×2048; Vorladen, laufende Download-Übernahme, vorbereitete Texturen und Ladeanimation bleiben unverändert.
- Debug-Protokoll unterscheidet das mobile und Desktop-Profil; tatsächliche Bildauflösung wird wie bisher bei „Vollständig vorgeladen“ ausgegeben.
- `?ppi_server_preview=0` deaktiviert die Server-Vorschau zur Gegenprobe. Die tatsächlich generierte Größe kann durch Nextcloud begrenzt sein. Bei Vorschaufehlern greift weiterhin der autorisierte Originalstream.

## 0.4.62.76 – Mobile 360°-Servervorschauen (Test)

- Token- und passwortgeschützter Abruf von Panorama-Varianten über den bestehenden Nextcloud-Vorschaucache (ohne Zuschnitt).
- Mobile Touren nutzen versuchsweise Server-Vorschauen bis 4096×2048. Desktop vorerst unverändert.
- ?ppi_server_preview=0 deaktiviert die Optimierung sofort für Vergleichstests.
- Originale bleiben unverändert; bei Vorschaufehlern Fallback auf Originalstream.
- Nextcloud kann Vorschaugrößen selbst begrenzen; Bildqualität und Performance müssen geprüft werden.

## 0.4.62.71 – Ladeübergang im Stil des Dateien/Fotos-Viewers

- Die frühere harte Wischkante der dunklen Ladeebene wurde entfernt.
- Wie im Dateien/Fotos-Viewer werden nun zwei unterschiedlich weichgezeichnete Vorschauebenen verwendet; die zweite baut sich von oben nach unten auf und geht anschließend weich in das fertige Panorama über.
- Indeterminierter Ladebalken bleibt korrekt, da der Tour-Viewer wegen Image()-Download und Speicher-Cache keine verlässlichen Download-Prozentwerte besitzt.
- Unterstützung für reduzierte Bewegung, gleiche Logik in Tour-Editor und öffentlichem Viewer.
- Vorladen, WebGL-Orientierung, Textur-Cache, Sicherheit und Freigabeverwaltung unverändert.

## 0.4.62.70 – Panorama-Aufbau von oben nach unten

- Zeigt fertig gerenderte Panoramen in öffentlichen Touren und im Editor mit einem 1,1-Sekunden-Übergang von oben nach unten an.
- Bestehende Ladeanimation bleibt bis zum ersten fertigen WebGL-Frame sichtbar; danach wird die Ladeebene zeilenweise zurückgezogen.
- Reduzierte-Bewegung-Einstellung respektiert. Hintergrund-Vorladen und Textur-Cache bleiben unverändert.

## 0.4.62.65 – Kleine Pin-Vorschauen und sanftes Vorladen

- Public Pin miniatures use the token-authorized Nextcloud preview endpoint instead of full-size panoramas.
- Restart sequential idle-only preloading after each successful panorama view (one mobile, two desktop), with foreground navigation priority.
- Reuse decoded preloaded image on navigation; retain immediate indeterminate loading overlay.

## 0.4.62.62 — Ladeanzeige sofort beim Panoramawechsel

- Zeigt im Editor und in öffentlichen Touren die Ladeanimation bereits vor dem Abruf der Zoom-Konfiguration und vor dem WebGL-Aufbau.
- Erhält dieselbe Ladeanimation über den Aufbau des WebGL-Viewers hinweg, bis das Panorama fertig gerendert wurde.
- Verwendet für den unscharfen Hintergrund der öffentlichen Ladeanzeige ein tokengebundenes Thumbnail statt der vollständigen Panorama-Datei.
- Erweiterte unabhängige Freigabelinks aus 0.4.62.61 bleiben erhalten.

## 0.4.62.61 – Unabhängige Tour-Freigabelinks (Testversion)
- Bis zu zwölf zusätzliche Freigabelinks je Tour mit eigener Bezeichnung, Passwort und Ablaufdatum.
- Zusätzliche Links unabhängig deaktivieren, reaktivieren oder endgültig widerrufen.
- Passwortprüfung verwendet Nextcloud-Freigaberichtlinien. Zugriff auf Bilder, Thumbnails, Manifest und Wasserzeichen ist je Link geprüft.
- Bisherigen Hauptlink und dessen Einstellungen unverändert beibehalten.
- Keine Migration oder Datenbankänderungen erforderlich; Links werden im Benutzerdatensatz der Tour gespeichert.

## 0.4.62.56
- Darstellung: obere Reihe Startpanorama, Wasserzeichen mit berechtigter Vorschau, SVG-Pin-Farbe mit gleichgroßem Farbfeld und Schnellfarben darunter sowie Hotspot-Design. Tourname oberhalb der Reihe.
- Neue authentifizierte Wasserzeichen-Thumbnail-Route gibt nur für Touren freigegebene Grafiken aus. Bestehende Tour-Einstellungen unverändert.

## 0.4.62.50 – Tour-Wasserzeichen mit zentraler Panorama-Positionierung (Testversion)
- Tour-Wasserzeichen verhalten sich nun wie im Photo-Sphere-Datei-Viewer: Im Modus „Im 360°-Panorama“ bleiben sie räumlich verankert und verschwinden außerhalb des Blickfelds.
- Admin-Einstellungen für horizontale Position, getrennte Höhen und Größen auf Desktop/Mobil sowie der alternative Modus „Fest auf dem Bildschirm“ bleiben maßgeblich.
- Wasserzeichen-Auswahl und Zugriffsschutz aus Version 0.4.62.49 unverändert.

## 0.4.62.49 – Wasserzeichen aus Admin-Galerie für Touren (Testversion)
- Administratoren können bestehende Wasserzeichen pro Grafik für Touren freigeben; standardmäßig ist keines freigegeben.
- Normale Benutzer wählen im Tab „Darstellung“ nur freigegebene Wasserzeichen und können sie für die Tour ein- oder ausschalten.
- Öffentliche Touren beziehen das gewählte PNG über eine eigene token- und gegebenenfalls passwortgeschützte Bildroute.
- Entzogene oder gelöschte Wasserzeichen werden in öffentlichen Touren nicht mehr ausgeliefert.
- Erste Ausbaustufe: Wasserzeichen liegt als feste Bildschirmeinblendung über der Tour, mit zentral definierten Desktop-/Mobilgrößen und Bildschirmposition.

## 0.4.62.44 – Pin-Bildebenen und dezenter Vorschau-Rahmen (Testversion)
- Kleine Panorama-Vorschau hinter die SVG-Pin-Kontur gelegt; Schatten weiterhin dahinter.
- Rahmen der vergrößerten Vorschau auf Desktop und Mobil von 3 auf 2 Pixel reduziert.
- Hover-, Touch- und Doppelhüpfer-Bedienung bleiben unverändert.

## 0.4.62.42 – SVG-Pin-Zielbild und animierte Zoomvorschau (Testversion)
- Kleine Zielpanorama-Vorschau innerhalb der transparenten Kontur der drei SVG-Pins (Map, Label, Kontur).
- Kleines Bild hüpft synchron mit Pin und Schlagschatten; keine Änderungen an Männchen oder blauem Guide.
- Ein erster Klick/Tipp zoomt die Vorschau weich aus dem Pin; ein zweiter Klick/Tipp öffnet das verknüpfte Panorama. Tipp auf das Panorama schließt die Vorschau.
- Bestehende Tour-Daten, Touchsteuerung, Sichtbarkeitsbereich und Berechtigungen bleiben unverändert.

## 0.4.62.41 – Kompakter Größenregler und Sprechblasen-Pin aus Auswahl entfernt
- Hilfstexte „Etwas kleiner“ und „Etwas größer“ am Größenregler entfernt; Höhe an Startblickrichtung angeglichen.
- Sprechblasen-Pin steht nicht mehr zur Auswahl. Bereits gespeicherte Touren mit diesem Design werden im Viewer als Map-Pin dargestellt und beim nächsten Speichern auf Map-Pin übernommen.
- Bestehende Tour-Daten, SVG-Farben, Schatten und Animationen bleiben erhalten.

## 0.4.62.34 – SVG-Pins: dezenter doppelter Hüpfer beim Passieren der Bildschirmmitte (Testversion)
- Ausschließlich für Map-Pin, Sprechblasen-Pin, Label-Pin und Kontur-Pin.
- Zwei sanfte Aufwärtsbewegungen (13 und 9 px) in 920 ms, nur beim tatsächlichen Passieren der horizontalen Bildmitte.
- Bestehende zentrierte Sichtbarkeit, Zielpanorama-Vorschau und die zweistufige mobile Touch-Bedienung bleiben unverändert.
- Auf `prefers-reduced-motion` wird Rücksicht genommen; kein Dauerhüpfen.

## 0.4.62.31 – Zentrierte Hotspot-Sichtbarkeit (Testversion)
- Tour-Hotspots im mittleren horizontalen Bildschirmbereich: 50 % auf Desktop, 65 % auf Mobil-/Touchgeräten.
- Sanfte Ease-in/Ease-out-Überblendung über 8 % der Anzeigebreite an beiden Grenzen; außerhalb des Bereichs sind Marker weder sichtbar noch anklickbar.
- Gilt für Tour-Editor und öffentlichen Viewer, alle sechs Designs und die zugehörigen Zielpanorama-Vorschauen.
- Keine zusätzliche Option im Darstellungs-Reiter; Tour-Daten und Koordinaten bleiben unverändert.

## 0.4.62.26
- Two additional tour-wide hotspot designs: map pin and speech bubble, based on the supplied SVG icons.
- Existing target panorama previews stay linked to all hotspot designs; new pin previews appear on hover/focus and touch.
- Hotspot design remains a single setting for the entire tour; per-connection design selection is not introduced.

## 0.4.62.21 – Kompakter Darstellungs-Tab und dynamischer Messenger-Hinweis (Testversion)
- Startpanorama- und Hotspot-Auswahl jeweils direkt unter die zugehörige Vorschau verschoben.
- Die übrigen Felder in zwei unabhängig hohen Spalten gebündelt, um unnötige Leerflächen zu vermeiden.
- Statusanzeige verdeutlicht die bereits serverseitig vorhandene dynamische Open-Graph-Regel: ohne Passwort Tourname/Beschreibung/Startpanorama (Modus cover); mit Passwort neutrale Angaben und neutrale Grafik.
- Bestehende gespeicherte Auswahlwerte, öffentliche Sicherheitsprüfungen und Tour-Daten bleiben unverändert.

## 0.4.62.20 – Speicherbutton im Darstellungs-Reiter (Testversion)
- „Darstellung speichern“ von unten in die obere Aktionsleiste neben „Tour löschen“ verschoben. Je Reiter wird nur der passende Speicherbutton angezeigt; Speichervorgang bleibt unverändert.

## 0.4.62.15 – Öffentliche Touren mit Passwortschutz (Testversion)
- Der Tour-Eigentümer kann für einen aktiven Freigabelink ein Passwort setzen, ändern oder entfernen (10–128 Zeichen).
- Passwörter werden serverseitig mit password_hash gespeichert und nie an die Tour-API ausgeliefert.
- Öffentliche Touransicht zeigt bei geschütztem Link ein Passwortformular, statt Viewer-Skripte zu laden.
- Erfolgreiche Entsperrung gilt nur für die Besuchersitzung und wird nach Passwort-/Tokenwechsel ungültig.
- Manifest, JPEG-Originale und Vorschaubilder prüfen neben Token und Ablauf auch die Besuchersitzung.
- Fehlversuche werden über die Nextcloud-Brute-Force-Erkennung gedrosselt.
- Tour-Daten und bestehende ungeschützte Links bleiben unverändert.

## 0.4.62.13 – Freigabeverwaltung, Schritt 1 (Testversion)
- Optionales Ablaufdatum für bestehende und neue öffentliche Tourlinks.
- Ablaufprüfung für Tour-Seite, Manifest, Thumbnail und JPEG-Zugriff.
- Sichere Token-Erneuerung mit Widerruf des alten Links.
- Bestehende Freigaben bleiben ohne gesetztes Ablaufdatum unverändert gültig.
- Passwortschutz und individuell wählbare Tokens folgen in weiteren Versionen.

## 0.4.62.12 – Verteilen von Pfeil-Anschlussstellen

- Mehrere ein- und ausgehende Verbindungen erhalten getrennte Anschlusspunkte pro Panorama-Kartenseite.
- Gegenläufige Verbindungen behalten eine stabile Reihenfolge; Hindernisrouting, geglättete Kurven, Löschfunktion und Tour-Daten bleiben unverändert.

## 0.4.62.11 – CSS-Ausgabe repariert
- Die Hover- und Hotspot-Vorschau-CSS-Regeln liegen wieder innerhalb des Stylesheets, statt als Text auf der Seite zu erscheinen.
- Alle anderen Funktionen aus 0.4.62.10 unverändert.

## 0.4.62.10 – Intelligentere Verbindungslinien und echte Hotspot-Vorschau
- Abstandsbewertung für benachbarte Pfeilpfade und zusätzliche Umwegkorridore; weniger Überlagerungen bei langen Verbindungen.
- Bei Hover/Fokus auf einer Panorama-Kachel werden ihre direkten Verbindungen hervorgehoben und andere transparent dargestellt.
- Klassische Hotspot-Vorschau verwendet die identische gestrichelte SVG-Figur des 360°-Viewers statt einer abweichenden Strichfigur.
- In der Vorschau erscheint jeweils nur das gewählte Hotspot-Design.
- Keine Änderungen an Tour-Daten, öffentlicher Freigabe, Admin-Zoomwerten oder maximal drei Panorama-Spalten.

## 0.4.62.8
- Tour-Verwaltung: dauerhaft auf eine maximale Breite von 1040 px begrenzt, damit die Seitenbreite unabhängig von der Touranzahl stabil bleibt.
- Desktop-Verbindungsgraph: maximal drei Panorama-Karten pro Reihe, auch bei zehn oder mehr Panoramen.
- Die Admin-Zoomwerte und übrigen Funktionen aus 0.4.62.7 bleiben unverändert.

## 0.4.62.5
- Tour-Übersicht: responsive Kartenanordnung je nach Tour-Anzahl (1 breit, 2 nebeneinander, ab 3 erste Karte breit und weitere zweispaltig). Keine Änderungen an Tour-Daten und öffentlichen Freigaben.

## 0.4.61 – Mausrad-Zoom in öffentlichen Touren
- Öffentliche 360°-Tour: Mausrad zoomt direkt wie im Datei-Panorama-Viewer.
- Tour-Editor: Mausrad-Scrollen bleibt unverändert, um die Konfigurationsansicht nicht zu blockieren.
- Touch/Gyro/Hotspot-Steuerung unverändert.

## 0.4.59 – Öffentliche Tour-Startseite (Test)
- Öffentliche Links beginnen mit einer Startseite mit großem Startpanorama, Tour-Titel und Startknopf.
- Erst nach bewusstem Start wird der bildschirmfüllende 360°-Viewer geladen.
- Vorhandene sichere Freigabe-Token, Gyro- und Viewer-Bedienung unverändert.
- Passwort/Ablaufdatum noch nicht Bestandteil dieses Test-Builds.

## 0.4.58.4 – Öffentliche Tour im Panorama-Vollbildlayout

- Öffentliches Tour-Panorama bildschirmfüllend auf Desktop und Mobilgeräten mit halbtransparenter Toolbar und Navigation.
- Browser-Vollbild per sichtbarer Schaltfläche (Browser benötigen Benutzerinteraktion).
- Gyro-Seitenlasche und Geräteausrichtung auf unterstützten Mobilgeräten.
- Tastatursteuerung mit sanftem Anlauf/Abbremsen und Strg+Pfeil hoch/runter für Zoom.
- Blauer Guide: dezenter Hover, höheres Zielbild und zusammenhängender Hoverbereich.

## 0.4.58.3 (test)
- Selectable blue guide hotspot, with target image preview on hover/focus (visible on touch).
- Includes layout-after-save fix from 0.4.58.2.

## 0.4.55 – Hotspot-Sichtbarkeit und Tour-Pfeilführung
- Die ausgeblendeten Hotspot-Figuren respektieren wieder die Sichtbarkeitsprüfung in Editor und öffentlicher Tour (CSS display:flex!important wird durch [hidden] überschrieben).
- Gegenläufige Verbindungspfeile erhalten getrennte, gebogene Lanes mit passend versetzten Andockpunkten, ohne unabhängig kreuzende Endpunkt-Sortierung.
- Gespeicherte Tour-Daten, Wechselpunkte und Scrolling bleiben unverändert.

## 0.4.54 – Tour-Verbindungen und sichtbare Hotspots
- Wechselpunkte nur anzeigen, wenn die vollständige Figur innerhalb des sichtbaren Panoramas liegt; keine am Bildrand klebenden Figuren.
- Die Position der Hotspots wird beim Drehen neu projiziert; Tourdaten bleiben unverändert.
- Zielpanorama-Tooltips ohne übliche Bilddateiendungen.
- Verbindungspfeile an geteilten Kachelseiten automatisch auf getrennte Andockpunkte verteilen.

## 0.4.53 – Tour-Hotspots mit Zielbild und persönliche Einstellungen
- 0.4.51/0.4.52: Kompakter Tour-Editor mit Zielbildleiste und Ladeanimation.
- Neuer anklickbarer SVG-Figuren-Hotspot, der das dynamische Vorschaubild des Ziels trägt (Tour-Editor und öffentlicher Tour-Viewer).
- Zielname als Tooltip und Barrierefreiheitsbeschriftung; bestehende Tour-Verbindungen bleiben unverändert.
- Lösch-X per CSS-Kreuz unabhängig von Schriftmetriken zentriert.
- Einstieg in die Tour-Verwaltung in der linken Seitenleiste unter Persönliche Einstellungen.

## 0.4.52 – Tour-Editor: Ladeanimation
- Übernimmt den unscharfen Vorschauhintergrund, Spinner und animierten Ladebalken aus dem fertigen Panorama-Viewer.
- Ladeanzeige verschwindet erst nach erfolgreichem Aufbau der 360°-Ansicht; bei Fehlern erscheint die bestehende Statusmeldung.
- Da der Tour-Editor Bilder direkt per Image lädt, wird bewusst kein vorgetäuschter Prozentwert angezeigt.
- Scroll-Layout, Touren-Navigation und restliche Oberfläche bleiben unverändert.

## 0.4.51 (Tour editor viewport layout)
- Panorama scales to viewport height while reserving room for the target-thumbnail strip.
- Target thumbnails are always shown for a tour with two or more panoramas, even before placing a hotspot.
- Compact horizontal thumbnail scrolling and collapsible help text; single scrolling dialog remains the fallback.
- Clicking a target before hotspot placement gives guidance rather than saving a link.
- All other tour behavior and the existing settings-navigation placement are unchanged.

## 0.4.50 – Tour-Menü, Löschsymbole und Panorama-Größe (Testversion)

- „360° Touren“ über die persönliche Nextcloud-Menünavigation (type=settings) statt die obere App-Leiste.
- Verbindungs-Löschbuttons entlang der tatsächlichen halben SVG-Pfadlänge ausrichten.
- X-Symbole in Panorama-Kacheln und Verbindungen einheitlich zentrieren.
- 360°-Tour-Editor responsiv vergrößern: Dialog 92vw / maximal 1800px, Panorama 70dvh / maximal 900px, mit mobilen Anpassungen.
- Die seit 0.4.47 funktionierende scrollbare Dialogstruktur bleibt unverändert.

## 0.4.49 (Tour thumbnail card usability)
- Größere Panorama-Karten mit direktem Öffnen über das Vorschaubild (Tastaturbedienung inklusive).
- Entfernen-Schaltfläche oben rechts über dem Thumbnail; sichtbar bei Hover, Tastaturfokus und auf Touchgeräten.
- Kartenränder für Verbindungspfeile angepasst; bisherige Scroll-Lösung beibehalten.

## 0.4.48 (Tour graph delete hit area)
- Keep graph delete controls clickable when crossing thumbnail cards using a short hover grace period.
- Raise delete controls above panorama cards and increase hit area to 32 px.
- Preserve the 0.4.47 scrolling behavior unchanged.

## 0.4.47 (Tour graph centre & scrolling)
- Verbindungs-Lösch-X bei Hover auf die geometrische Kurvenmitte gesetzt.
- Panorama-Einstellungsdialog als einziger Scrollcontainer, sticky Titel, erreichbare Zielgalerie.
- Scrollbarkeit des Tour-Hauptbereichs in Nextcloud verbessert.

## 0.4.46 – Tour-Graph und Theme-Kontrast (Testversion)
- Pfeilspitzen vergrößert, Linien kontrastreicher und dicker.
- Verbindungs-Löschknöpfe versetzt; am Desktop beim Zeigen auf die zugehörige Linie, mobil sichtbar.
- Überschrift, Untertitel und Erfolgs-/Fehlermeldungen in einem themeabhängigen Content-Container.
- Tour-Daten, Viewer, Hotspots und öffentliche Freigaben unverändert.

## 0.4.44 – Interaktive Tour-Übersicht (Testversion)
- Panorama-Karten mit echten Vorschaubildern, Dateinamen, Öffnen und Entfernen.
- Gebogene Richtungspfeile und direktes Löschen einzelner Verbindungen.
- Doppelte Bild- und Verbindungsliste entfernt.
- Zielvorschaubild übernimmt bei gesetztem Wechselpunkt sofort die Verbindung in den Entwurf.
- Originaldateien werden beim Entfernen aus einer Tour nicht gelöscht.

# 0.4.43 – 2026-11-19

- Tour-Hotspot-Editor: Zielpanorama über anklickbare Vorschaubilder auswählen statt nur über Dropdown.
- Sichtbare Zielbildgalerie nach Setzen eines Wechselpunktes; vorhandene Verbindungen und Speicherung bleiben unverändert.
- Modal-Layout mit kompakterer 360°-Fläche, scrollbarem Einstellungsbereich und eigener scrollbarerer Zielbildgalerie.
- Mausrad-Weiterleitung auf die Panorama-Fläche begrenzt, damit die Bedienelemente natürlich scrollbar bleiben.
- Basierend ausschließlich auf Version 0.4.42; Wasserzeichen, Bildauswahl und öffentliche Freigaben unverändert.

# 0.4.42 – 2026-11-19

- Tour-Editor und Panoramaansicht deutlich vergrößert (nahezu bildschirmfüllender Dialog).
- Fester Dialogkopf und unabhängig scrollbarerer Einstellungsbereich, einschließlich Mausrad-Weiterleitung vom Panorama zum Dialog; Strg + Mausrad bleibt Zoom.
- Tour-Verbindungslinien als gerichtete Bézier-Kurven mit Pfeilspitzen; Hin- und Rückwege sind getrennt sichtbar.
- Öffentlicher Tour-Viewer ebenfalls größer.
- Bisherige Panorama-/Wasserzeichenfunktionen und Tour-Freigabe unverändert.

# 0.4.41 – Öffentliche Tour-Testfreigabe und Scrollkorrektur

- Öffentliche, zufällig erzeugte Tour-Links mit explizitem Aktivieren und Widerrufen.
- Gäste sehen ausschließlich Bilder und Navigation der freigegebenen Tour; Bild-Endpunkte prüfen Token, Tour-Zugehörigkeit und aktuelle Lesbarkeit.
- Fremde Nextcloud-Datei-IDs außerhalb einer Tour werden abgewiesen.
- Tour-Editor als einheitlich scrollbar aufgebaut; Ctrl + Mausrad zoomt im Panorama, gewöhnliches Mausrad scrollt das Editorfenster.
- Vorhandene Touren, Wasserzeichenverwaltung und normale Einzel-Panoramaansicht bleiben unverändert.

## 0.4.40 – Tour-Editor: Scroll- und Bedienbereich

- Verbindungssteuerung aus dem Panorama-Scrollbereich herausgelöst: eigener sichtbarer Fußbereich unter dem Panorama.
- Verbindungsfußbereich und Vorschaubereich separat scrollbar.
- Panoramaansicht in der Höhe angepasst, damit die Schaltflächen auf Desktop und Smartphones erreichbar bleiben.
- Öffentliche Tour-Links noch nicht freigeschaltet; Datei- und Berechtigungsprüfung wird dafür separat umgesetzt.

# 0.4.39 – Tour-Editor Test

- Editieren von Navigationspunkten direkt im 360°-Panorama (Yaw/Pitch).
- Anklickbare Wechselpunkte öffnen verlinkte Panoramen im Tour-Editor.
- Tour-Vorschau mit scrollbarerer Inhaltsfläche und festem Kopfbereich.
- JPEG-Streaming über eine ausschließlich angemeldeten Benutzern zugängliche Route; Nextcloud-Dateiberechtigungen werden geprüft.
- Tour-Links speichern Koordinaten; bisherige Verbindungen ohne Positionsangaben bleiben lesbar.
- Originaldateien und normale Nextcloud Files/Photos-Einzelansichten bleiben unverändert.
- Öffentliche Tour-Freigaben sind weiterhin deaktiviert.

# 0.4.38 – Tour-Editor Test

- Dateiauswahl erhält eine eigene scrollbare Fläche mit feststehender Auswahlleiste.
- Pano-Vorschaubilder im Editor werden sofort geladen (nicht lazy).
- Dateiauswahl zeigt je Panorama einen eigenen Vorschau-Button.
- Vorschaufenster wird aus Nextcloud-Scrollcontainern herausgenommen.
- Bereits zur Tour hinzugefügte Panoramen können in der Vorschau direkt verknüpft werden.
- Der interaktive 360°-Hotspot-Editor ist weiterhin nicht Bestandteil dieser Teststufe.

## 0.4.37 – Tour-Editor Test
- Neue Kachelansicht für Ordner und JPEG-Panoramen mit Nextcloud-Vorschaubildern.
- Mehrfachauswahl über Checkboxen; gesammeltes Hinzufügen auch aus mehreren Ordnern.
- Bereits hinzugefügte Panoramen haben eine anklickbare Vorschau und eine Schaltfläche „Vorschau öffnen“.
- Bestehende Tourdaten und Panorama-Viewer nicht verändert.

## 0.4.36 (Tour-Test)
- „Panorama hinzufügen“ zeigt die Dateiauswahl sichtbar an und scrollt sie in den Blickbereich.
- Explizites Ein-/Ausblenden der Abschnitte auch bei abweichenden Nextcloud-Themes; Lade- und Fehlerhinweise im Dialog bleiben sichtbar.
- Tourverwaltung und Panorama-Betrachter ansonsten unverändert.

## 0.4.35 — Tour-Auswahl repariert

- Panorama-Dateiauswahl öffnet sofort mit Ladezustand und verständlicher Fehlermeldung.
- Ordnerauswahl nutzt geprüfte Nextcloud-Ordner-IDs, analog zur Wasserzeichenverwaltung.
- Bestehender Panorama-Viewer, Wasserzeichen, Tour-Erstellen und Tour-Löschen unverändert.

# 0.4.34 – Tour-Verwaltung (erste Teststufe)

- Eigene Nextcloud-App-Navigation „360° Touren“ für alle angemeldeten Benutzer.
- Benutzerbezogene Tour-Entwürfe erstellen, benennen, bearbeiten und löschen.
- Lesbare JPEGs aus Nextcloud-Dateien auswählen und bis zu 50 Bilder pro Tour zusammenstellen.
- Verbindungen zwischen Tour-Bildern anlegen und als grafische Übersicht anzeigen.
- Sicherheitsprüfung beim Speichern jedes Bildes gegen die Dateiberechtigungen des aktuellen Benutzers.
- Öffentliche Tour-Freigaben und Hotspots im 360°-Viewer sind in dieser Teststufe noch nicht aktiviert.
- Bestehender Panorama-Betrachter, globale Wasserzeichen-Adminverwaltung und Freigaben bleiben unverändert.

## 0.4.33 – Testversion – 2026-11-19

### Verbessert
- Schnellere Panorama-Erkennung: Nextcloud-Bildwechsel werden zusätzlich unmittelbar über DOM-Änderungen erkannt, ohne auf den nächsten 450-ms-Prüflauf warten zu müssen.
- Vorhandene periodische Erkennung bleibt als Fallback aktiv; Mehrfachprüfungen werden pro Animationsframe zusammengefasst.

### Unverändert
- Vollständiger Funktionsstand der 0.4.32: mobile Öffnungsgrafik, Gyroskop, Zoom, Wasserzeichen, Ordnerzuweisungen, Galerie, Ladeanimation, öffentliche Freigaben und Einstellungen.

## 0.4.32 – Testversion – 2026-11-19

### Verbessert
- Auf Smartphones werden die Steuerungshinweise in der ungeöffneten Panorama-Vorschau nicht mehr angezeigt.
- Die 360°-Öffnungsgrafik ist mobil etwa 15 % größer (145 statt 126 Pixel maximale mobile Basisbreite; relative Breite entsprechend angepasst).
- Die Grafik wird exakt mittig auf der aktiven Nextcloud-Bildfläche positioniert; nach Laden der Grafik wird ihre tatsächliche Höhe berücksichtigt.
- Auf Smartphones bleibt die Grafik eine echte Öffnen-Schaltfläche, die bisherige Touch-Erkennung bleibt erhalten.

### Unverändert
- Desktop-Grafik und Bedienhinweise, Nextcloud-Navigationspfeile links/rechts, Gyroskop, Zoom, Wasserzeichen, Sicherheitsprüfungen und öffentliche Freigaben bleiben unverändert.
- Interne Testversion. Beim nächsten öffentlichen Release werden sämtliche Änderungen seit 0.4.27 zusammengefasst.

## 0.4.31 – Testversion – 2026-11-19

### Verbessert
- Mobile Panorama-Vorschau: Das 360°-Öffnungssymbol sitzt nun ungefähr in der Bildmitte (8 Pixel darüber); die Steuerungshinweise stehen unmittelbar darunter.
- Unsichtbares Abstandselement zwischen 360°-Grafik und Hinweisen entfernt. Beide bleiben gemeinsam ein einziges Touch-Ziel.
- Position wird nach dem Laden der Öffnungsgrafik erneut anhand ihrer tatsächlichen Höhe berechnet.

### Unverändert
- Seitliche Nextcloud-Bildnavigation, Desktop-Positionierung, Gyroskop, Zoom, Wasserzeichen und Zugriffsprüfung bleiben unverändert.
- Interne Testversion. Beim nächsten öffentlichen Release werden alle Änderungen seit 0.4.27 zusammengefasst.

## 0.4.30 – Testversion – 2026-11-19

### Behoben
- Mobile Panorama-Vorschau: 360°-Grafik, Zwischenraum und Steuerungshinweise sind nun eine gemeinsame semantische Öffnen-Schaltfläche mit einheitlicher Touch-Fläche.
- Zusätzlich werden Touch-Bestätigungen im Capture-Abschnitt abgefangen, bevor der Nextcloud Viewer sie als eigene Bildgeste konsumieren kann; Wischbewegungen bleiben unbeeinflusst.
- Der Öffnungsbereich bleibt mittig und schmal. Die seitlichen Nextcloud-Pfeile zum vorherigen/nächsten Bild bleiben frei.

### Unverändert
- Desktop-Bedienung, Gyroskop, Zoom (mobiler Startwert 75°), Wasserzeichen, Freigaben und normale Nextcloud-Bildnavigation.
- Interne Testversion; beim nächsten öffentlichen Release werden alle Änderungen seit 0.4.27 zusammengefasst.

## 0.4.29 – Testversion – 2026-11-19

### Behoben
- Mobile Öffnungsfläche: 360°-Symbol und Steuerungshinweise sind eigenständige, anklickbare Elemente; ein schmaler mittiger Bereich zwischen ihnen öffnet das Panorama ebenfalls.
- Die linke und rechte Nextcloud-Bildnavigation bleibt vollständig außerhalb der vergrößerten Touch-Ziele. Es wird weder das gesamte Vorschaubild noch ein breiter unsichtbarer Klickbereich überlagert.

### Unverändert
- Desktop-Bedienung, mobiler Start-Blickwinkel 75°, Gyroskop, Wasserzeichen, Freigaben und bestehende Nextcloud-Navigation.
- Interne Testversion: Änderungen seit der letzten öffentlichen Version 0.4.27 werden beim nächsten öffentlichen Release zusammengefasst.

## 0.4.28 – Testversion – 2026-11-19

### Neu und verbessert
- Auf kleinen Smartphones mit Touch-Bedienung öffnet ein Tipp auf das 360°-Symbol, die Steuerungshinweise oder den dazwischenliegenden Bereich das Panorama. Größe und Darstellung von Symbol und Hinweis bleiben unverändert; Desktop bleibt unverändert.
- Mobiler Standard-Start-Blickwinkel von 90° auf 75° geändert. Bereits gespeicherte individuelle Zoomwerte bleiben bestehen; die Funktion „Standardwerte wiederherstellen“ setzt künftig 75°.
- Die beiden Wasserzeichen-Punkte in der App-Store-Beschreibung zu einer zusammenhängenden Funktion zusammengefasst.

### Unverändert
- Gyroskop, Ladeanimation, Panorama-Navigation, Zoomdynamik, Wasserzeichen-Rechteprüfung und öffentliche Freigaben.

### Veröffentlichungshinweis
- Dies ist eine interne Testversion. Für den nächsten offiziellen App-Store-Release werden alle Änderungen seit der letzten veröffentlichten Version 0.4.27 im Abschnitt des neuen öffentlichen Releases zusammengefasst.

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

### 0.4.60 – Tour navigation thumbnails and welcome description
- Public navigation displays token-scoped cached small previews rather than filename-only controls.
- Editable optional welcome text in the tour design tab, validated and stored with each tour.
- Public landing displays welcome description as plain text.

## 0.4.62.19 – Open Graph link previews
- Metadata for title, description and optional start panorama on public tours.
- Password-protected tours expose only generic social metadata; image route denies access.
- Social preview choices in Darstellung: cover, neutral, off.
- All social image requests check active share token and original image read permissions.\n
### 0.4.62.80 – Isolated panorama derivative cache (experimental)
- Cache 4096/8192 panorama JPEG derivatives in private appdata, independent of global Nextcloud preview limits.
- Schedule background preparation for saved tours and upon opening existing authorized tours.
- Detect Imagick dynamically, preserve server resource limits; retain normal Nextcloud preview/original fallback when unavailable.
- Cache keyed by source identity and ETag; original files and public-share checks remain unchanged.

## 0.4.62.139 – Startseitenbezogene Tour-Passwortfreigabe (Testversion)

- Startseiten-Freigabe mit drei Regeln: Tour-Passwörter beibehalten (Standard), alle eingebundenen Touren freischalten, einzelne Touren freischalten.
- Freigabe nur nach Anmeldung an einer passwortgeschützten Startseite; pro Browser-Sitzung und ausschließlich für den tatsächlich über diese Startseite eingebundenen Tour-Link.
- Server prüft bei jeder Viewer-, Manifest-, Vorschau-, Bild-, Cache- und Wasserzeichen-Anfrage erneut Startseiten-Passwortsitzung, freigegebene Tour-Zuordnung, Modus und gültige Tour-Freigabe.
- Tour-Links und vorhandene Tour-Passwörter werden nicht verändert. Passwortänderungen und widerrufene Startseiten- bzw. Tour-Freigaben beenden die entsprechende Berechtigung.
- Sicherheitshinweis: Die Version ist für Funktionstests auf einer separaten Docker-Testinstallation gedacht, noch kein unabhängiges Security-Audit.
