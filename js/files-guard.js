/* Photo Sphere – Files default viewer guard (v0.3.20, NC35).
   Only in Files UI and public file/folder shares. Does not patch Nextcloud or the Photo Sphere app on disk. */
(() => {
  'use strict';
  const eligibleRoute = () => /(?:\/index\.php)?\/apps\/files(?:\/|$)/.test(location.pathname)
    || /(?:\/index\.php)?\/s\/[^/]+(?:\/|$)/.test(location.pathname);
  if (!eligibleRoute()) return;
  if (window.__ppiFilesActionGuardInstalled) return;
  window.__ppiFilesActionGuardInstalled = true;

  function guard(action) {
    if (!action || typeof action._getAction !== 'function' || action.__ppiFilesGuarded) return;
    try {
      const originalGetAction = action._getAction;
      action._getAction = function (...args) {
        const registered = originalGetAction.apply(this, args);
        if (registered?.id === 'photosphereviewer-image') {
          // The original action only takes over panoramas. Disabling just that
          // default action here lets the standard Nextcloud image viewer win.
          registered.enabled = () => false;
        }
        return registered;
      };
      // The legacy JPEG 'view' handler would otherwise override the original
      // handler. Leave it untouched in all other apps/routes.
      if (typeof action._registerLegacyActions === 'function') {
        const originalLegacy = action._registerLegacyActions;
        action._registerLegacyActions = function (...args) {
          if (eligibleRoute()) return;
          return originalLegacy.apply(this, args);
        };
      }
      Object.defineProperty(action, '__ppiFilesGuarded', { value: true, configurable: true });
    } catch (error) {
      console.warn('[Photosphere Photos] Dateiaktion konnte nicht vorbereitet werden', error);
    }
  }

  // The original app places its action object on window before calling init()
  // on DOMContentLoaded. Capture it even when it is registered later.
  const key = 'photoSphereViewerFileAction';
  const old = Object.getOwnPropertyDescriptor(window, key);
  if (old && !old.configurable) {
    guard(window[key]);
    return;
  }
  let value = window[key];
  Object.defineProperty(window, key, {
    configurable: true,
    enumerable: old ? old.enumerable : true,
    get() { return value; },
    set(next) { value = next; guard(next); },
  });
  guard(value);
})();
