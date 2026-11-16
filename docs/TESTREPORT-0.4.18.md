# Photo Sphere – 360° Panorama
## Test Report – Version 0.4.18 RC

Test date: 2026-11-16
Test environment: Isolated Docker installation

### Environment

- Nextcloud: 35.0.3
- Operating system: Ubuntu 26.04.1 LTS
- Architecture: ARM64 / aarch64
- Docker-based test installation
- Nextcloud Photos: 8.0.0
- Nextcloud Viewer: 8.0.0
- Test application: Photo Sphere 0.4.18

### Static checks

| Test | Result |
| --- | --- |
| PHP syntax checks | PASS |
| JavaScript syntax checks | PASS |
| XML validation | PASS |
| Application activation | PASS |

### Functional tests

| Test | Result |
| --- | --- |
| Panorama viewer in Files | PASS |
| Panorama viewer in Photos | PASS |
| Mouse controls and zoom | PASS |
| Arrow-key navigation | PASS |
| Watermark settings interface | PASS |
| Folder management interface | PASS |

### Public sharing tests

| Test | Result |
| --- | --- |
| Public share without password | PASS |
| Incorrect share password rejected | PASS |
| Correct share password accepted | PASS |
| Panorama viewer in public share | PASS |
| Watermark displayed in public share | PASS |

### Protected watermark endpoint

Endpoint:
`/apps/photosphere_photos_integration/watermark/context-image`

Test: Direct request to the same protected
file-specific watermark resource.

- Without authenticated share session: HTTP 404
- With valid authenticated share session: HTTP 200
- Result: PASS

Share tokens, credentials, and private files
are intentionally excluded from this report.

### Limitations

Tests were performed manually in an isolated
Nextcloud 35.0.3 environment.

The results do not constitute a comprehensive
security audit or prove compatibility with
other Nextcloud versions.

### Conclusion

All documented checks passed in the tested
environment.

Version 0.4.18 remains a release candidate
pending final publication checks and signing.
