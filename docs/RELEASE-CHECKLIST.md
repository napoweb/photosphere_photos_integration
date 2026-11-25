# Veröffentlichungsvorbereitung – Photo Sphere 0.4.62.140

**Stand: 25.11.2026.** Status: **TECHNISCHER VOR-AUDIT BESTANDEN, RELEASE WEITERHIN GESPERRT.**
Die App ist weder final signiert noch für den Nextcloud App Store freigegeben.
Der Release-PR #8 bleibt bis zum Abschluss der offenen Tests als Draft bestehen; Produktivinstanz und `main` werden nicht verändert.

## Bereits nachgewiesen

- [x] Nextcloud 35.0.3, ARM64, PHP 8.5.13: Docker-Testumgebung mit MariaDB aktiv; Touren, Freigaben, App-Cache und Cron praktisch geprüft.
- [x] Bisherige produktive Nextcloud-35.0.3-Installation mit PostgreSQL auf ARM64 wurde bei früheren App-Versionen im Alltag geprüft; **kein Nachweis einer vollständigen Release-Paket-Installation von 0.4.62.140**.
- [x] Vor-Audit für `0.4.62.140`: **0 kritische Abweichungen** zwischen GitHub-Commit `51b2eb4b1e195401c42e96544efa41764456bd90` und den 59 geprüften Dateien im Docker-Testcontainer (Code, Templates, Bilder, App-Metadaten). Lokale `README.md` und `CHANGELOG.md` weichen ab und wurden absichtlich nicht überschrieben.
- [x] PHP-Lint: **25/25** Dateien bestanden; JavaScript-Syntax: **9/9** Dateien bestanden; Wartungsmodus im Testcontainer aus.
- [x] `appinfo/info.xml` in Docker nach Git-Blob-Prüfung und Sicherheitsvergleich auf Version `0.4.62.140` aktualisiert; nur `version`, `summary`, `description` und `screenshot` unterschieden sich, technische Einstellungen waren identisch. Original gesichert unter `/home/stefan/docker-app-backups/ppi-infoxml-20261125-091545/info.xml.original` (Testserver; kein portabler App-Pfad).
- [x] App-ID, AGPL-3.0-or-later, Nextcloud-35-Kompatibilitätsmetadaten und die drei referenzierten Screenshot-Dateien im Repository geprüft.
- [x] Testarchiv-Trockenlauf: ca. **2,91 MB** ohne `signature.json`; ein einzelnes oberstes Verzeichnis `photosphere_photos_integration/`; unter 20 MB.
- [x] Öffentliche Tourlinks: Passworttrennung, Deaktivieren/Widerrufen, Bild-URL nach Widerruf, manipulierte Panorama-ID und Benutzertrennung praktisch geprüft (frühere Tests).
- [x] Ablaufdatum: serverseitig Sperrung **ab 00:00 Uhr am gewählten Tag** in der Nextcloud-Zeitzone des Freigabebesitzers (Fallback `core.timezone` → `default_timezone` → UTC); 20/20 Grenztests bestanden.
- [x] Abgelaufene Tour- und Startseitenlinks inklusive Bild-Endpunkten und bestehender Sitzungen serverseitig gesperrt; andere unabhängige Links bleiben erreichbar.
- [x] Firefox: Ablaufstatus und 00:00-Uhr-Beschriftung bei Touren und Startseiten sichtbar; bei einem abgelaufenen zusätzlichen Tourlink ist die Aktivieren-/Deaktivieren-Schaltfläche ausgeblendet.
- [x] Existenz eines privaten Signierschlüssels und Zertifikats an den geprüften Standardorten festgestellt; **Schlüssel-Zertifikat-Zusammengehörigkeit und Signatur noch nicht geprüft**.

## Vor Veröffentlichung offen

- [ ] Firefox-Gegentest der ausgeblendeten Aktivieren-/Deaktivieren-Schaltfläche bei **abgelaufenen Startseiten-Freigaben**, einschließlich Haupt- und Zusatzlinks soweit vorhanden.
- [ ] Ende-zu-Ende-Prüfung der Tourerstellung, Hotspots, Startseiten, öffentlichen Links und mobiler Darstellung auf dem finalen Stand.
- [x] **Beschleunigter Bereinigungstest im isolierten MariaDB-Dockercontainer (25.11.2026):** Cache-ID `120` nach Prüfung der Dateiliste und des Fingerprints mit vorgezogenem Orphan-Markierungszeitpunkt auf >7 Tage gesetzt; Original-Nextcloud-Job `CleanupUnusedPanoramaCacheJob` (ID `154684225133686784`) mit `--force-execute` erfolgreich ausgeführt. Ergebnis: **exakt zwei optimierte Cachedateien der ID 120 gelöscht**, Vergleichskandidat **ID 139 unverändert**; Sicherung und Nachweise unter `/home/stefan/docker-app-backups/ppi-orphan-test-120-20261125-094307-2856766` (nur Docker-Testserver). App-Code und Produktivinstanz unverändert.
- [ ] **Regulären zeitgesteuerten Ablauf separat prüfen:** Im Docker lag die ursprüngliche Fälligkeit von ID 120 bei **29.11.2026 21:50:50 CET**, die von ID 139 bei **01.12.2026 22:01:15 CET**; für ID 120 ist der simulierte Test bereits bestanden. Der Termin **28.11.2026** bezieht sich auf eine getrennte **Produktiv-Nextcloud**-Erinnerung und ist kein Docker-Löschtermin. Der gezielt erzwungene Test beweist nicht die normale Cron-Ausführung an einem real abgelaufenen Kandidaten.
- [ ] Direktzugriffe auf fremde Touren/Startseiten, Schreib-APIs, Tokens, Passwörter und Bild-/Cache-Endpunkte als unberechtigter Nutzer testen.
- [ ] Test des **final gepackten und signierten Release-Artefakts** in einer getrennten Nextcloud-35-Testinstanz (Neuinstallation und Upgrade-Szenario).
- [ ] Kompatibilität mit mindestens einer weiteren unterstützten PHP-Version und x86-64 prüfen; SQLite optional. Keine plattformspezifischen Pfade oder Installationsänderungen voraussetzen.
- [ ] Lizenzhinweise und Rechte an Bildern, Icons, Bundles und Fremdbibliotheken für öffentlichen Vertrieb prüfen.
- [ ] App-Store-Screenshots prüfen, Tour-/Startseitenansichten ergänzen und offenes Darstellungsproblem [usercontent.apps.nextcloud.com#35](https://github.com/nextcloud/usercontent.apps.nextcloud.com/issues/35) separat beachten.
- [ ] Öffentliche `README.md`, `CHANGELOG.md`, `info.xml`, Release-Checkliste und später Git-Tag/Release-Notizen auf denselben Stand abgleichen. **Lokale README-/Changelog-Abweichungen im Docker-Test wurden bewusst nicht automatisch überschrieben.**
- [ ] Signierschlüssel und Herausgeberzertifikat auf Zusammengehörigkeit und Gültigkeit prüfen, **ohne privaten Schlüssel zu veröffentlichen**.
- [ ] Release-Dateien nach letzter Änderung mit `occ integrity:sign-app` signieren und `occ integrity:check-app` im separaten Test prüfen; Paketinhalt und Archivstruktur vor App-Store-Upload erneut prüfen.
- [ ] Erst nach Freigabe Draft-PR #8 mergen, Release-Tag erstellen und signierte `.tar.gz` unverändert unter stabiler HTTPS-URL (GitHub Release) bereitstellen; anschließend Nextcloud-App-Store-Update veröffentlichen.

## Offizielle Referenzen

- https://docs.nextcloud.com/server/stable/developer_manual/app_publishing_maintenance/release_process.html
- https://docs.nextcloud.com/server/stable/developer_manual/app_publishing_maintenance/code_signing.html
- https://nextcloudappstore.readthedocs.io/en/latest/developer.html
