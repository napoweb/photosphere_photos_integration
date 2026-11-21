# Public tour URLs – Nextcloud 35 compatibility

Native, public app routes: `/apps/photosphere_photos_integration/tour/{token}`. URLs are generated through Nextcloud `IURLGenerator` on the server and `OC.generateUrl` in the browser.

Nextcloud 35 `OC\AppFramework\Routing\RouteParser::buildRootPrefix` restricts root routes (e.g. `/t/{token}`) to core-whitelisted apps via `rootUrlApps`; this app is not on that list. Setting `root => ""` does NOT enable root URLs for third-party apps. A compatible, standalone Nextcloud app must therefore NOT generate `/t/{token}` unless a separately configured proxy/webserver alias is verified.

Do not modify core RouteParser or the Nextcloud-managed `.htaccess`. Hosts may optionally configure their own short aliases; these are not portable and not a requirement for the app. If a previous Apache rewrite rule was added (`photosphere-shortlinks.conf`), it is optional and can be disabled after verifying the native URLs.
