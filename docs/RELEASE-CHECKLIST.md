# Veröffentlichungsvorbereitung – Photo Sphere 0.4.62.92

Status: **UNSIGNIERTER VERÖFFENTLICHUNGSKANDIDAT – NOCH NICHT IM APP STORE EINREICHEN**

## Bereits belegt
- [x] Nextcloud 35.0.3 + PostgreSQL auf ARM64, PHP 8.5 – App im Alltag getestet.
- [x] Nextcloud 35.0.3 + MariaDB 11.4 auf ARM64, PHP 8.5 – App aktiviert, Touren und App-Cache sowie Cron geprüft.
- [x] Öffentliche Tourlinks: Passworttrennung, Link deaktivieren/widerrufen, Bild-URL nach Widerruf und manipulierte Panorama-ID praktisch geprüft.
- [x] Mehrbenutzer-Trennung in der Tourenübersicht und Anonymisierung fremder Cache-Namen in der Adminstatistik geprüft.

## Vor Veröffentlichung offen
- [ ] Signierte offizielle App-Zertifikatsdatei (.crt) und privaten Signierschlüssel sicher vorliegen haben; Schlüssel nie dem Chat oder Repository anvertrauen.
- [ ] Datei-Lizenzhinweise, Bild-/Icon-Rechte und externe Komponenten prüfen; die im Repository vorhandene AGPL-3.0-Lizenz ins Release übernehmen.
- [ ] Testinstallation von 0.4.62.92 in separater Testinstanz; kein Upgrade der produktiven Version ungeprüft.
- [ ] Kompatibilität mit mindestens einer weiteren für Nextcloud 35 unterstützten PHP-Version prüfen.
- [ ] x86-64 Praxischeck; SQLite optional ergänzen.
- [ ] Automatische Löschung nach der siebentägigen Cache-Schutzfrist kontrollieren (geplanter Termin 28.11.2026).
- [ ] Unberechtigte direkte API-Zugriffe auf fremde Touren und Schreibfunktionen testen.
- [ ] Release-Screenshots prüfen und Tour-Ansichten ergänzen.
- [ ] README, CHANGELOG, info.xml-Version und Git-Tag abgleichen.
- [ ] Release-Dateien nach letzter Änderung mit `occ integrity:sign-app` signieren und `occ integrity:check-app` in einer Testinstanz prüfen.
- [ ] Erst dann die signierte .tar.gz auf eine stabile HTTPS-URL (z. B. GitHub Release) hochladen und im Nextcloud App Store registrieren.

## Geprüfte offizielle Quellen
- https://docs.nextcloud.com/server/stable/developer_manual/app_publishing_maintenance/release_process.html
- https://docs.nextcloud.com/server/stable/developer_manual/app_publishing_maintenance/code_signing.html
- https://nextcloudappstore.readthedocs.io/en/latest/developer.html
