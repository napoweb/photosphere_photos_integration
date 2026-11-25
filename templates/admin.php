<?php
/** @var array $_ */
?>
<div id="ppi-watermark-admin" class="section"
     data-zoom-settings-url="<?php p($_['zoomSettingsUrl']); ?>"
     data-zoom-save-url="<?php p($_['zoomSaveUrl']); ?>"
     data-assign-folder-url="<?php p($_['assignFolderUrl']); ?>"
     data-folder-owners-url="<?php p($_['folderOwnersUrl']); ?>"
     data-browse-folders-url="<?php p($_['browseFoldersUrl']); ?>"
     data-unassign-folder-url="<?php p($_['unassignFolderUrl']); ?>"
     data-image-url="<?php p($_['imageUrl']); ?>"
     data-preview-base-url="<?php p($_['previewBaseUrl']); ?>"
     data-tour-share-url="<?php p($_['tourShareUrl']); ?>"
     data-list-url="<?php p($_['listUrl']); ?>"
     data-upload-url="<?php p($_['uploadUrl']); ?>"
     data-select-url="<?php p($_['selectUrl']); ?>"
     data-delete-url="<?php p($_['deleteUrl']); ?>"
     data-options-url="<?php p($_['optionsUrl']); ?>"
     data-reset-url="<?php p($_['resetUrl']); ?>"
     data-restore-default-url="<?php p($_['restoreDefaultUrl']); ?>">
  <div class="ppi-settings-panel ppi-settings-intro">
    <h2>360° Panorama – Einstellungen</h2>
    <p>Verwalte Wasserzeichen, Blickwinkel, Tour-Cache und die optionale Menüverknüpfung. Bestehende Touren und Originalbilder bleiben unverändert.</p>
  </div>
  <style>
    /* Uniform bordered layout: all top-level sections share the same outer width. */
    #ppi-watermark-admin { --ppi-panel-max: 840px; }
    #ppi-watermark-admin .ppi-settings-panel {
      box-sizing: border-box;
      width: 100%; max-width: var(--ppi-panel-max);
      border: 1px solid var(--color-border,#ccc);
      border-radius: 12px; padding: 20px;
      margin: 0 0 16px;
    }
    #ppi-watermark-admin .ppi-settings-group {box-sizing:border-box;width:100%;max-width:var(--ppi-panel-max);margin:24px 0 30px;padding:18px;border:2px solid var(--color-border-dark,var(--color-border,#bbb));border-radius:16px;background:var(--color-background-dark,rgba(127,127,127,.035));}
    #ppi-watermark-admin .ppi-settings-group .ppi-section-heading {margin:0 0 16px;padding:0 0 12px;border-bottom:1px solid var(--color-border,#ccc);}
    #ppi-watermark-admin .ppi-settings-group .ppi-settings-panel {max-width:none;background:var(--color-main-background,var(--color-background-plain,#fff));}
    @media (max-width:600px) { #ppi-watermark-admin .ppi-settings-group {padding:10px;margin:18px 0;} }
    #ppi-watermark-admin .ppi-settings-panel > h3:first-child { margin:0 0 10px; }
    #ppi-watermark-admin .ppi-section-heading { box-sizing:border-box; max-width:var(--ppi-panel-max); margin:24px 0 12px; font-size:19px; font-weight:700; }
    #ppi-watermark-admin .ppi-settings-intro h2 { margin:0 0 8px; font-size:22px; }
    #ppi-watermark-admin .ppi-settings-intro p { margin:0; }
    #ppi-watermark-admin .ppi-settings-panel > p { margin: 0 0 16px; }
    #ppi-watermark-admin .ppi-settings-panel--action { padding: 14px 20px; }
    #ppi-watermark-admin .ppi-settings-panel--action button { margin: 0; }
    #ppi-watermark-admin .ppi-settings-panel--activation { padding: 16px 20px; }
    #ppi-watermark-admin .ppi-settings-panel--activation label { display: flex; align-items: center; gap: 10px; font-weight: 600; cursor: pointer; }
    #ppi-watermark-admin .ppi-gallery-panel #ppi-wm-gallery { margin: 16px 0 0 !important; }
    #ppi-watermark-admin .ppi-upload-panel #ppi-watermark-form { margin-top: 0 !important; }
    #ppi-watermark-admin .ppi-upload-panel #ppi-watermark-feedback { margin-bottom: 0; }
    #ppi-watermark-admin .ppi-upload-panel .ppi-note { font-size: 13px; color: var(--color-text-maxcontrast,#999); }
    #ppi-watermark-admin .ppi-mode-options { box-sizing: border-box; max-width: none; width: 100%; margin: 14px 0 0; }
    #ppi-watermark-admin .ppi-mode-options { display:grid; grid-template-columns:repeat(auto-fit,minmax(245px,1fr)); gap:12px; }
    #ppi-watermark-admin .ppi-mode-choice { display:flex; gap:12px; align-items:flex-start; border:1px solid var(--color-border,#ccc); border-radius:12px; padding:16px; background:var(--color-background-dark,rgba(127,127,127,.08)); cursor:pointer; }
    #ppi-watermark-admin .ppi-mode-choice:has(input:checked) { border:2px solid var(--color-primary-element,#0082c9); padding:15px; }
    #ppi-watermark-admin .ppi-mode-choice input[type="radio"] { flex-shrink:0; margin-top:3px; }
    #ppi-watermark-admin .ppi-mode-choice strong { display:block; margin-bottom:6px; }
    #ppi-watermark-admin .ppi-mode-choice small { display:block; line-height:1.5; color:var(--color-text-maxcontrast,#999); font-size:13px; }

    #ppi-watermark-admin .ppi-settings-panel h3 { margin:0 0 9px; }
    #ppi-watermark-admin .ppi-settings-panel p { margin:0 0 18px; }
    #ppi-watermark-admin .ppi-control-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(235px,1fr)); gap:16px; align-items:start; }
    #ppi-watermark-admin .ppi-control-card { display:flex; flex-direction:column; gap:10px; background:var(--color-background-dark,rgba(127,127,127,.08)); border:1px solid var(--color-border,#ccc); border-radius:10px; padding:16px; min-width:0; }
    #ppi-watermark-admin .ppi-control-card label { font-weight:600; display:flex; justify-content:space-between; gap:10px; align-items:center; }
    #ppi-watermark-admin .ppi-control-card output { font-variant-numeric:tabular-nums; font-weight:700; white-space:nowrap; }
    #ppi-watermark-admin .ppi-control-card input[type="range"] { display:block; width:100%; margin:0; height:26px; writing-mode:horizontal-tb; direction:ltr; touch-action:auto; }
    #ppi-watermark-admin .ppi-control-card.ppi-full-width { margin-bottom:16px; }
    #ppi-watermark-admin .ppi-device-heading { font-weight:700; margin-bottom:2px; }
    #ppi-watermark-admin .ppi-device-hint { color:var(--color-text-maxcontrast,#888); font-size:12px; }
    #ppi-watermark-admin .ppi-wm-card button { cursor:pointer; min-height:34px; }
    @media(max-width:600px) { #ppi-watermark-admin .ppi-settings-panel {padding:14px;} #ppi-watermark-admin .ppi-control-row {grid-template-columns:1fr;} #ppi-watermark-admin .ppi-mode-options {grid-template-columns:1fr;} }

    #ppi-watermark-admin .ppi-rule-details { width:100%; box-sizing:border-box; border-top:1px solid var(--color-border,#ccc); padding-top:9px; }
    #ppi-watermark-admin .ppi-rule-details summary { cursor:pointer; font-weight:600; font-size:12px; }
    #ppi-watermark-admin .ppi-rule-list { font-size:12px; margin:9px 0; padding:0; list-style:none; }
    #ppi-watermark-admin .ppi-rule-list li { padding:8px; border-radius:6px; margin-bottom:6px; background:var(--color-background-hover,rgba(127,127,127,.15)); overflow-wrap:anywhere; }
    #ppi-watermark-admin .ppi-rule-form { display:flex; flex-direction:column; gap:7px; }
    #ppi-watermark-admin .ppi-rule-form input { box-sizing:border-box; width:100%; min-width:0; }
    #ppi-watermark-admin .ppi-rule-form button { width:100%; }
    #ppi-watermark-admin .ppi-folder-picker { border:1px solid var(--color-border,#ccc); border-radius:14px; background:var(--color-main-background,#fff); color:var(--color-main-text,#222); width:min(570px,calc(100vw - 30px)); max-height:85dvh; overflow:auto; padding:20px; box-shadow:0 12px 42px rgba(0,0,0,.36); }
    #ppi-watermark-admin .ppi-folder-picker::backdrop { background:rgba(0,0,0,.64); }
    #ppi-watermark-admin .ppi-folder-picker h3 { margin:0 0 8px; }
    #ppi-watermark-admin .ppi-picker-list { display:flex; flex-direction:column; gap:5px; margin:8px 0 14px; max-height:42dvh; overflow:auto; }
    #ppi-watermark-admin .ppi-picker-list button { display:block; width:100%; text-align:left; padding:10px; border-radius:8px; height:auto; overflow-wrap:anywhere; }
    #ppi-watermark-admin .ppi-picker-actions { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; margin-top:14px; }
    #ppi-watermark-admin .ppi-picker-owner { width:100%; box-sizing:border-box; margin:6px 0; }
    #ppi-watermark-admin .ppi-picker-path { overflow-wrap:anywhere; padding:9px; border-radius:7px; background:var(--color-background-dark,rgba(127,127,127,.08)); }
    #ppi-watermark-admin .ppi-help summary { cursor:pointer; font-weight:700; }
    #ppi-watermark-admin .ppi-help li { margin:6px 0; }
    /* Cache help: native, keyboard-accessible disclosure with no JavaScript dependency. */
    #ppi-watermark-admin .ppi-cache-help{margin-top:18px;border:1px solid var(--color-border,#ccc);border-radius:10px;background:var(--color-background-hover,rgba(128,128,128,.07));padding:0}
    #ppi-watermark-admin .ppi-cache-help>summary{display:list-item;cursor:pointer;font-weight:700;padding:13px 16px;list-style-position:inside}
    #ppi-watermark-admin .ppi-cache-help[open]>summary{border-bottom:1px solid var(--color-border,#ccc)}
    #ppi-watermark-admin .ppi-cache-help-body{padding:14px 16px 16px;font-size:14px;line-height:1.65}
    #ppi-watermark-admin .ppi-cache-help-body h4{font-size:15px;font-weight:700;margin:12px 0 6px}
    #ppi-watermark-admin .ppi-cache-help-body h4:first-child{margin-top:0}
    #ppi-watermark-admin .ppi-cache-help-body p{margin:5px 0 10px}
    #ppi-watermark-admin .ppi-cache-help-body ul,#ppi-watermark-admin .ppi-cache-help-body ol{padding-left:24px;margin:6px 0 12px}
    #ppi-watermark-admin .ppi-cache-help-body li{margin:4px 0}
    #ppi-watermark-admin .ppi-cache-help-body code{overflow-wrap:anywhere;font-size:12px;background:var(--color-main-background,#fff);padding:2px 5px;border-radius:4px}
    #ppi-watermark-admin .ppi-cache-help-body a{overflow-wrap:anywhere}

    /* Visual overview of the separate 360° tour cache; remains readable on mobile. */
    #ppi-watermark-admin .ppi-cache-flow{max-width:640px;width:100%;box-sizing:border-box;margin:12px auto 20px;padding:13px;display:flex;flex-direction:column;align-items:center;gap:4px;border:1px solid var(--color-border,#ccc);border-radius:12px;background:var(--color-main-background,#fff);color:var(--color-main-text,#222)}
    #ppi-watermark-admin .ppi-cache-flow-step{width:100%;min-width:0;box-sizing:border-box;padding:13px 15px;display:flex;flex-direction:column;align-items:center;gap:3px;text-align:center;border:1px solid var(--color-border,#ccc);border-radius:10px;background:var(--color-background-hover,rgba(127,127,127,.07));overflow-wrap:anywhere}
    #ppi-watermark-admin .ppi-cache-flow-step strong{font-size:14px}
    #ppi-watermark-admin .ppi-cache-flow-step small{font-size:12px;line-height:1.45;color:var(--color-text-maxcontrast,#666)}
    #ppi-watermark-admin .ppi-cache-flow-icon{font-size:22px;line-height:1.25}
    #ppi-watermark-admin .ppi-cache-flow-arrow{font-size:24px;line-height:1.05;color:var(--color-text-maxcontrast,#666);padding:1px 0}
    #ppi-watermark-admin .ppi-cache-flow-profiles{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;width:100%}
    #ppi-watermark-admin .ppi-cache-flow-profiles .ppi-cache-flow-step{height:100%}
    #ppi-watermark-admin .ppi-cache-flow-number{font-size:clamp(14px,2.4vw,19px);font-weight:700;line-height:1.3;white-space:nowrap}
    @media(max-width:540px){#ppi-watermark-admin .ppi-cache-flow{padding:9px}#ppi-watermark-admin .ppi-cache-flow-profiles{grid-template-columns:1fr;gap:7px}#ppi-watermark-admin .ppi-cache-flow-step{padding:10px 8px}#ppi-watermark-admin .ppi-cache-flow-number{font-size:17px}}


  </style>

  <section class="ppi-settings-group" aria-labelledby="ppi-watermark-group-title">
  <h3 class="ppi-section-heading" id="ppi-watermark-group-title">Wasserzeichen</h3>
  <div class="ppi-settings-panel ppi-settings-panel--activation">
    <label for="ppi-wm-enabled">
      <input id="ppi-wm-enabled" type="checkbox" checked /> Wasserzeichen anzeigen
    </label>
  </div>

  <div class="ppi-settings-panel ppi-mode-panel" role="group" aria-label="Positionierungsart auswählen">
    <h3>Positionierung des Wasserzeichens</h3>
    <div class="ppi-mode-options">
      <label class="ppi-mode-choice">
        <input type="radio" name="ppi-wm-mode" value="panorama" checked />
        <span><strong>Im 360°-Panorama</strong>
        <small>Das Wasserzeichen ist an einer festen Stelle im Bild verankert. Wenn du das Panorama drehst, bewegt es sich mit und kann aus dem sichtbaren Bereich verschwinden.</small></span>
      </label>
      <label class="ppi-mode-choice">
        <input type="radio" name="ppi-wm-mode" value="screen" />
        <span><strong>Fest auf dem Bildschirm</strong>
        <small>Das Wasserzeichen bleibt immer an derselben Bildschirmposition sichtbar – unabhängig davon, wohin du das Panorama drehst oder wie du zoomst.</small></span>
      </label>
    </div>
  </div>

  <div id="ppi-wm-panorama-settings" class="ppi-settings-panel">
    <h3>Position im 360°-Panorama</h3>
    <p>Die horizontale Position gilt für beide Geräte. Positive Höhenwerte verschieben das Wasserzeichen in den Himmel.</p>
    <div class="ppi-control-card ppi-full-width">
      <label for="ppi-wm-yaw">Horizontale Position <output id="ppi-wm-yaw-out"></output></label>
      <input id="ppi-wm-yaw" type="range" min="-180" max="180" step="1" />
    </div>
    <div class="ppi-control-row">
      <div class="ppi-control-card">
        <div class="ppi-device-heading">Desktop / PC</div>
        <span class="ppi-device-hint">Position des Wasserzeichens am Desktop</span>
        <label for="ppi-wm-pitchDesktop">Höhe <output id="ppi-wm-pitchDesktop-out"></output></label>
        <input id="ppi-wm-pitchDesktop" type="range" min="-80" max="80" step="1" />
      </div>
      <div class="ppi-control-card">
        <div class="ppi-device-heading">Smartphone / Handy</div>
        <span class="ppi-device-hint">Position des Wasserzeichens am Smartphone</span>
        <label for="ppi-wm-pitchMobile">Höhe <output id="ppi-wm-pitchMobile-out"></output></label>
        <input id="ppi-wm-pitchMobile" type="range" min="-80" max="80" step="1" />
      </div>
    </div>
  </div>

  <div id="ppi-wm-screen-settings" class="ppi-settings-panel" style="display:none;">
    <h3>Feste Bildschirmposition</h3>
    <p>Position in Prozent – bleibt auch beim Drehen des Panoramas gleich.</p>
    <div class="ppi-control-row">
      <div class="ppi-control-card">
        <label for="ppi-wm-screenX">Horizontal (links → rechts) <output id="ppi-wm-screenX-out"></output></label>
        <input id="ppi-wm-screenX" type="range" min="0" max="100" step="1" />
      </div>
      <div class="ppi-control-card">
        <label for="ppi-wm-screenY">Vertikal (oben → unten) <output id="ppi-wm-screenY-out"></output></label>
        <input id="ppi-wm-screenY" type="range" min="0" max="100" step="1" />
      </div>
    </div>
  </div>

  <div class="ppi-settings-panel">
    <h3>Größe des Wasserzeichens</h3>
    <div class="ppi-control-row">
      <div class="ppi-control-card">
        <div class="ppi-device-heading">Desktop / PC</div>
        <label for="ppi-wm-sizeDesktop">Breite <output id="ppi-wm-sizeDesktop-out"></output></label>
        <input id="ppi-wm-sizeDesktop" type="range" min="40" max="500" step="2" />
      </div>
      <div class="ppi-control-card">
        <div class="ppi-device-heading">Smartphone / Handy</div>
        <label for="ppi-wm-sizeMobile">Breite <output id="ppi-wm-sizeMobile-out"></output></label>
        <input id="ppi-wm-sizeMobile" type="range" min="30" max="300" step="2" />
      </div>
    </div>
  </div>
  <div class="ppi-settings-panel ppi-settings-panel--action">
    <button class="primary" type="button" id="ppi-wm-save-options">Einstellungen speichern</button>
  </div>

  <div class="ppi-settings-panel ppi-gallery-panel">
    <h3>Gespeicherte Wasserzeichen</h3>
  <p>Bis zu fünf eigene PNG-Grafiken. Die mitgelieferte Standardgrafik kann aus der Galerie entfernt und später wiederhergestellt werden. Ohne Auswahl wird kein Wasserzeichen angezeigt.</p>
  <div id="ppi-wm-gallery" style="display:flex;flex-wrap:wrap;gap:12px;margin:14px 0;" role="group" aria-label="Wasserzeichen auswählen"></div>
  </div>

  <div class="ppi-settings-panel ppi-upload-panel">
    <h3>Wasserzeichen hochladen</h3>
    <form id="ppi-watermark-form" enctype="multipart/form-data" style="display:flex;align-items:center;flex-wrap:wrap;gap:12px;margin-top:12px;">
    <label for="ppi-watermark-file">Neue PNG-Grafik (max. 2 MB, maximal 4096 × 4096 Pixel):</label>
    <input id="ppi-watermark-file" name="watermark" type="file" accept="image/png,.png" required />
    <button type="submit" class="primary">Hochladen und auswählen</button>
  </form>
  <p><button id="ppi-wm-restore-default" type="button" style="display:none;">360°-Standardgrafik wieder einblenden</button></p>
  <p id="ppi-watermark-feedback" role="status" aria-live="polite"></p>
  <p class="ppi-note"><em>Für die Vorschau der Änderungen ein geöffnetes Panorama schließen und neu öffnen.</em></p>
  </div>

  <div class="ppi-settings-panel ppi-help">
    <details>
      <summary>Hilfe zur Ordnerverwaltung</summary>
      <p>Ein Wasserzeichen aus der Galerie bleibt der Standard für alle Panoramen ohne besondere Ordnerzuweisung.</p>
      <ol>
        <li>Öffne bei der gewünschten Grafik den Bereich <strong>Ordnerzuweisungen</strong>.</li>
        <li>Klicke auf <strong>Ordner auswählen</strong> und navigiere zum gewünschten Ordner. Es werden nur Ordner angezeigt, auf die dein angemeldetes Nextcloud-Konto Zugriff hat. Eine manuelle Pfadeingabe ist nicht erforderlich.</li>
        <li>Klicke auf <strong>Diesen Ordner zuweisen</strong>. Der Ordner und sämtliche Unterordner verwenden dieses Wasserzeichen.</li>
        <li>Hat ein Unterordner eine eigene Regel, gilt dort diese speziellere Regel.</li>
        <li>Ohne passende Regel erscheint das ausgewählte Standard-Wasserzeichen. Löscht du eine Zuordnung, bleiben alle Dateien unverändert.</li>
      </ol>
      <p>Beispiel: <code>/Fotos/MTB</code> → MTB-Logo; <code>/Fotos/MTB/Harz</code> erbt es automatisch. Öffentliche Dateifreigaben nutzen die Zuordnung, wenn der Zugriff sicher zugeordnet werden kann. Andere öffentliche Alben verwenden das Standard-Wasserzeichen.</p>
      <p>Die Ordner werden anhand ihrer Nextcloud-ID gespeichert. Ändert sich der Pfad durch Umbenennen, bleibt die Regel bestehen; die angezeigte Pfadangabe ist dann möglicherweise veraltet.</p>
    </details>
  </div>

  </section>
  <section class="ppi-settings-group" aria-labelledby="ppi-zoom-group-title">
  <h3 class="ppi-section-heading" id="ppi-zoom-group-title">Zoom und Blickwinkel</h3>
  <div class="ppi-settings-panel" id="ppi-zoom-settings-panel">
    <h3>Zoom-Einstellungen</h3>
    <p>Blickwinkel in Grad: Ein kleiner Wert bedeutet stärkeres Hineinzoomen. Der Startwert muss zwischen den beiden Grenzen liegen.</p>
    <div class="ppi-control-row">
      <div class="ppi-control-card">
        <div class="ppi-device-heading">Desktop / PC</div>
        <label for="ppi-zoom-desktopMin">Maximaler Zoom-in <output id="ppi-zoom-desktopMin-out">60°</output></label>
        <input id="ppi-zoom-desktopMin" type="range" min="20" max="70" step="1" value="60" />
        <label for="ppi-zoom-desktopStart">Start-Blickwinkel <output id="ppi-zoom-desktopStart-out">110°</output></label>
        <input id="ppi-zoom-desktopStart" type="range" min="60" max="145" step="1" value="110" />
        <label for="ppi-zoom-desktopMax">Maximaler Zoom-out <output id="ppi-zoom-desktopMax-out">135°</output></label>
        <input id="ppi-zoom-desktopMax" type="range" min="80" max="150" step="1" value="135" />
      </div>
      <div class="ppi-control-card">
        <div class="ppi-device-heading">Smartphone / Tablet</div>
        <label for="ppi-zoom-mobileMin">Maximaler Zoom-in <output id="ppi-zoom-mobileMin-out">30°</output></label>
        <input id="ppi-zoom-mobileMin" type="range" min="20" max="70" step="1" value="30" />
        <label for="ppi-zoom-mobileStart">Start-Blickwinkel <output id="ppi-zoom-mobileStart-out">75°</output></label>
        <input id="ppi-zoom-mobileStart" type="range" min="60" max="145" step="1" value="75" />
        <label for="ppi-zoom-mobileMax">Maximaler Zoom-out <output id="ppi-zoom-mobileMax-out">110°</output></label>
        <input id="ppi-zoom-mobileMax" type="range" min="80" max="150" step="1" value="110" />
      </div>
    </div>
    <p style="margin:12px 0 8px;"><button type="button" class="primary" id="ppi-zoom-save">Zoom-Einstellungen speichern</button> <button type="button" id="ppi-zoom-reset" style="margin-left:10px;">Standardwerte wiederherstellen</button></p>
    <p id="ppi-zoom-feedback" role="status" aria-live="polite"></p>
  </div>
  </section>
  <section class="ppi-settings-group" aria-labelledby="ppi-cache-group-title">
  <h3 class="ppi-section-heading" id="ppi-cache-group-title">Panorama-Cache</h3>
  <div class="ppi-settings-panel" id="ppi-panorama-cache-panel"
       data-statistics-url="<?php p($_['panoramaCacheStatisticsUrl']); ?>"
       data-status-url="<?php p($_['panoramaCacheStatusUrl']); ?>"
       data-save-url="<?php p($_['panoramaCacheSaveUrl']); ?>">
    <h3>Automatische Cache-Erstellung für 360°-Touren</h3>
    <p><strong>Hinweis:</strong> Diese Funktion gilt ausschließlich für den Bereich <strong>360°-Touren</strong> der App. Die normalen Panoramaansichten in Nextcloud Dateien und Fotos werden dadurch nicht verändert.</p>
    <p>Für Touren erzeugt die App im Hintergrund JPEG-Vorschauen mit bis zu 8192 × 4096 Pixeln (Desktop) und 4096 × 2048 Pixeln (Mobil). Bereits vorhandene Vorschaudateien bleiben bei einer Pause verfügbar.</p>
    <label for="ppi-panorama-cache-enabled" style="display:flex;align-items:center;gap:10px;cursor:pointer;">
      <input id="ppi-panorama-cache-enabled" type="checkbox" checked>
      Automatische Panorama-Optimierung aktivieren
    </label>
    <div style="margin-top:16px;">
      <label for="ppi-panorama-cache-workers"><strong>Gleichzeitig zu verarbeitende Panoramen</strong></label>
      <select id="ppi-panorama-cache-workers" style="display:block;max-width:100%;margin-top:7px;">
        <option value="1" selected>1 Panorama (empfohlen)</option>
        <option value="2">2 Panoramen</option>
        <option value="3">3 Panoramen</option>
      </select>
      <p class="ppi-note" style="margin-top:8px;">Jedes gleichzeitig verarbeitete Panorama benötigt zusätzlich CPU und Arbeitsspeicher. Für kleine Server und große 120-Megapixel-Bilder empfehlen wir 1. Die Auswahl gilt für diesen Server; sie kann laufende Berechnungen nicht rückwirkend stoppen.</p>
    </div>
    <p style="margin-top:12px;"><button type="button" class="primary" id="ppi-panorama-cache-save">Einstellungen speichern</button></p>
    <p id="ppi-panorama-cache-feedback" role="status" aria-live="polite"></p>
    <div style="margin-top:20px;padding-top:14px;border-top:1px solid var(--color-border, #ccc);" id="ppi-panorama-cache-statistics">
      <h3>Cache-Statistik der 360°-Touren</h3>
      <p class="ppi-note">Nur App-eigene optimierte Vorschaubilder. Die Bereinigung berücksichtigt Tourreferenzen aller Benutzer. In der Anzeige bleiben Dateinamen, Tournamen und Datei-IDs anderer Benutzer geschützt. Datei-IDs und Namen werden für Originale angezeigt, auf die der angemeldete Administrator aktuell über seine Nextcloud-Dateiansicht zugreifen darf. Nicht zugängliche Cache-Dateien bleiben anonym. Die Statistik verändert oder löscht keine Dateien.</p>
      <p><button type="button" id="ppi-panorama-cache-stats-refresh">Statistik aktualisieren</button></p>
      <p id="ppi-panorama-cache-stats-feedback" role="status" aria-live="polite">Statistik wird geladen …</p>
      <div id="ppi-panorama-cache-stats-content" hidden>
        <div id="ppi-panorama-cache-stats-summary" style="display:flex;flex-wrap:wrap;gap:10px;margin:10px 0;"></div>
        <div style="display:flex;align-items:center;flex-wrap:wrap;gap:12px;margin:18px 0 14px;">
        <label for="ppi-panorama-cache-stats-filter"><strong>Dateien anzeigen</strong></label>
        <select id="ppi-panorama-cache-stats-filter" style="max-width:100%;min-width:220px;margin:0;">
          <option value="used" selected>Aktuell in Touren verwendet</option>
          <option value="marked">Für Bereinigung vorgemerkt</option>
          <option value="unused">Nicht in Touren verwendet</option>
          <option value="all">Alle Cache-Panoramen</option>
        </select>
        </div>
        <style>
          #ppi-panorama-cache-statistics .ppi-cache-stats-scroll {
            display:block;width:100%;max-width:100%;overflow-x:auto;overflow-y:hidden;
            overscroll-behavior-x:contain;scrollbar-gutter:stable;
            border:1px solid var(--color-border,#ddd);border-radius:6px;
          }
          #ppi-panorama-cache-statistics .ppi-cache-stats-table {
            width:1290px;min-width:1290px;max-width:none;
            table-layout:fixed;border-collapse:separate;border-spacing:0;text-align:left;
          }
          #ppi-panorama-cache-statistics .ppi-cache-stats-table th,
          #ppi-panorama-cache-statistics .ppi-cache-stats-table td {
            box-sizing:border-box;min-width:0;max-width:none;padding:10px 12px;
            text-align:left;vertical-align:middle;overflow:hidden;
            border-bottom:1px solid var(--color-border,#ddd);
          }
          #ppi-panorama-cache-statistics .ppi-cache-stats-table th {
            position:relative;font-weight:600;white-space:nowrap;
            background:var(--color-main-background,#fff);
          }
          #ppi-panorama-cache-statistics .ppi-cache-stats-table td {white-space:nowrap;text-overflow:ellipsis;}
          #ppi-panorama-cache-statistics .ppi-cache-stats-table .ppi-cache-filename,
          #ppi-panorama-cache-statistics .ppi-cache-stats-table .ppi-cache-tourname {
            display:block;width:100%;min-width:0;max-width:100%;overflow:hidden;
            white-space:nowrap;text-overflow:ellipsis;
          }
          #ppi-panorama-cache-statistics .ppi-cache-resize-handle {
            position:absolute;right:0;top:0;bottom:0;width:12px;z-index:2;
            cursor:col-resize;touch-action:none;user-select:none;
            border-right:2px solid var(--color-border-dark,#aaa);
          }
          #ppi-panorama-cache-statistics .ppi-cache-resize-handle:hover,
          #ppi-panorama-cache-statistics .ppi-cache-resize-handle:focus-visible {
            background:var(--color-primary-element-light,#e5f3fa);
            border-right-color:var(--color-primary-element,#0082c9);
            outline:2px solid var(--color-primary-element,#0082c9);outline-offset:-2px;
          }
          #ppi-panorama-cache-statistics .ppi-cache-stats-scroll:focus-visible {
            outline:2px solid var(--color-primary-element,#0082c9);outline-offset:2px;
          }
          #ppi-panorama-cache-statistics .ppi-cache-stats-pager {
            display:flex;align-items:center;justify-content:space-between;
            gap:8px 14px;flex-wrap:wrap;margin:12px 0 6px;
            border-top:1px solid var(--color-border,#ddd);padding-top:12px;
          }
          #ppi-panorama-cache-statistics .ppi-cache-stats-pager[hidden]{display:none!important}
          #ppi-panorama-cache-statistics .ppi-cache-stats-pager-controls {
            display:flex;align-items:center;gap:5px;flex-wrap:wrap;
          }
          #ppi-panorama-cache-statistics .ppi-cache-stats-pager button {
            min-width:34px;min-height:34px;padding:5px 9px;
            border:1px solid var(--color-border,#ddd);border-radius:6px;
            background:var(--color-main-background,#fff);color:var(--color-main-text,#222);
            cursor:pointer;
          }
          #ppi-panorama-cache-statistics .ppi-cache-stats-pager button[aria-current="page"]{
            background:var(--color-primary-element,#0082c9);
            border-color:var(--color-primary-element,#0082c9);color:var(--color-primary-element-text,#fff);
            font-weight:700;
          }
          #ppi-panorama-cache-statistics .ppi-cache-stats-pager button:disabled{
            opacity:.45;cursor:default;
          }
          #ppi-panorama-cache-statistics .ppi-cache-stats-pager button:focus-visible {
            outline:2px solid var(--color-primary-element,#0082c9);outline-offset:2px;
          }
          #ppi-panorama-cache-statistics .ppi-cache-stats-pager-range{font-size:13px;color:var(--color-text-maxcontrast,#666)}

        </style>
        <p class="ppi-note">Die Trennlinien in der Tabellen-Kopfzeile lassen sich mit der Maus ziehen (per Tastatur mit Pfeiltasten anpassen). Bei wenig Platz kannst du die Tabelle seitlich scrollen. Vollständige Dateinamen sind über den Mauszeiger lesbar.</p>
        <div class="ppi-cache-stats-scroll" tabindex="0" role="region" aria-label="Cache-Dateitabelle horizontal scrollbar">
          <table aria-label="Cache-Panoramen" class="ppi-cache-stats-table" id="ppi-cache-stats-table">
            <colgroup><col style="width:86px"><col style="width:350px"><col style="width:260px"><col style="width:130px"><col style="width:90px"><col style="width:80px"><col style="width:130px"><col style="width:164px"></colgroup>
            <thead><tr><th scope="col">Datei-ID</th><th scope="col">Dateiname</th><th scope="col">Verwendet in Tour</th><th scope="col">Status</th><th scope="col">Desktop</th><th scope="col">Mobil</th><th scope="col">Cache-Größe</th><th scope="col">Schutzfrist bis</th></tr></thead>
            <tbody id="ppi-panorama-cache-stats-rows"></tbody>
          </table>
        </div>
        <nav id="ppi-cache-stats-pager" class="ppi-cache-stats-pager" aria-label="Seiten der Cache-Dateiliste" hidden>
          <span id="ppi-cache-stats-pager-range" class="ppi-cache-stats-pager-range" aria-live="polite"></span>
          <div class="ppi-cache-stats-pager-controls">
            <button type="button" id="ppi-cache-stats-prev" aria-label="Vorherige 10 Cache-Einträge">← Zurück</button>
            <span id="ppi-cache-stats-pages" class="ppi-cache-stats-pager-controls" aria-label="Seite wählen"></span>
            <button type="button" id="ppi-cache-stats-next" aria-label="Nächste 10 Cache-Einträge">Weitere 10 →</button>
          </div>
        </nav>
        <p id="ppi-panorama-cache-stats-limit" class="ppi-note"></p>
      </div>
    </div>
    <p class="ppi-note">Eine laufende Bildberechnung wird bei einer Pause nicht abgebrochen. Neue Berechnungen werden nicht eingeplant; bereits wartende Jobs überspringen die Verarbeitung. Der normale Nextcloud-Cron bleibt aktiv. Falls alle Verarbeitungsplätze belegt sind, wird der Job übersprungen und kann später erneut eingeplant werden.</p>
    <details class="ppi-cache-help" id="ppi-panorama-cache-help">
      <summary>Hilfe &amp; Diagnose – Panorama-Cache und Vorschaugrößen</summary>
      <div class="ppi-cache-help-body">
        <h4>Was macht der Panorama-Cache?</h4>
        <p>Diese Optimierung gilt nur für gespeicherte 360°-Touren. Beim Speichern oder bei einem berechtigten Touraufruf werden passende Panoramen über Nextcloud-Hintergrundjobs zur Verarbeitung vorgemerkt. Mit PHP Imagick erzeugt die App verkleinerte JPEG-Dateien aus dem Original und speichert sie getrennt im privaten AppData-Cache. Die Original-JPEGs und Nextclouds allgemeiner Vorschau-Cache werden nicht verändert.</p>
        <h4 id="ppi-cache-flow-heading">So funktioniert unsere Lösung</h4>
        <figure class="ppi-cache-flow" aria-labelledby="ppi-cache-flow-heading">
          <div class="ppi-cache-flow-step">
            <span class="ppi-cache-flow-icon" aria-hidden="true">🖼️</span>
            <strong>Original-Panorama</strong>
            <small>Beispiel: 15.520 × 7.760 Pixel · rund 120 Megapixel</small>
          </div>
          <div class="ppi-cache-flow-arrow" aria-hidden="true">↓</div>
          <div class="ppi-cache-flow-step">
            <strong>Photo Sphere + PHP Imagick</strong>
            <small>Liest die Originaldatei direkt und erzeugt optimierte JPEGs – ohne Nextclouds Vorschau-API.</small>
          </div>
          <div class="ppi-cache-flow-arrow" aria-hidden="true">↓</div>
          <div class="ppi-cache-flow-profiles">
            <div class="ppi-cache-flow-step">
              <span class="ppi-cache-flow-icon" aria-hidden="true">🖥️</span>
              <strong>Desktop</strong>
              <span class="ppi-cache-flow-number">8192 × 4096</span>
              <small>max. 33,6 MP · JPEG</small>
            </div>
            <div class="ppi-cache-flow-step">
              <span class="ppi-cache-flow-icon" aria-hidden="true">📱</span>
              <strong>Mobil</strong>
              <span class="ppi-cache-flow-number">4096 × 2048</span>
              <small>max. 8,4 MP · JPEG</small>
            </div>
          </div>
          <div class="ppi-cache-flow-arrow" aria-hidden="true">↓</div>
          <div class="ppi-cache-flow-step">
            <span class="ppi-cache-flow-icon" aria-hidden="true">🗂️</span>
            <strong>Eigener Photo-Sphere-Cache</strong>
            <small>Privates Nextcloud-AppData-Verzeichnis · bereits erzeugte Profile werden dem berechtigten Tour-Viewer bereitgestellt.</small>
          </div>
        </figure>
        <p>Anders als bei Nextclouds normaler Vorschaugenerierung greift <code>preview_max_x</code> bei dieser direkten Imagick-Verarbeitung nicht. Die App ändert diese Nextcloud-Grenze nicht. Fehlt ein optimiertes Profil, versucht der Viewer zuerst die Nextcloud-Vorschau und kann anschließend auf das berechtigte Original zurückfallen.</p>
        <h4>Welche zwei Bildgrößen entstehen?</h4>
        <ul>
          <li><strong>Desktop:</strong> bis zu <strong>8192 × 4096 Pixel</strong> (2:1, JPEG-Qualität 85).</li>
          <li><strong>Mobil:</strong> bis zu <strong>4096 × 2048 Pixel</strong> (2:1, JPEG-Qualität 85).</li>
        </ul>
        <p>Kleinere Originale werden nicht hochskaliert. Der App-Cache verarbeitet nur geeignete 2:1-JPEG-Panoramen bis maximal 150 MB Quelldateigröße. Die Berechnung benötigt Imagick, freie Serverressourcen und laufende Cron-Hintergrundjobs. Vorhandene App-Vorschaubilder können auch nach einer Pause weiterverwendet werden. Das Vorschauraster in der Tour-Bildauswahl verwendet dagegen Nextclouds normale kleine Vorschauen (320 × 180 Pixel), <em>nicht</em> diese Tour-JPEGs.</p>
        <h4>Wie funktioniert das automatische Löschen im Cache?</h4>
        <p>Die App bereinigt ausschließlich die <strong>eigenen optimierten Tour-JPEGs</strong>. Weder Panorama-Originaldateien noch Touren oder Nextclouds allgemeine Vorschauen werden gelöscht. Es gibt zwei voneinander getrennte Bereinigungswege:</p>
        <ol>
          <li><strong>Veraltete Bildversionen:</strong> Wird ein noch verwendetes Panorama geändert, entstehen neue Desktop- und Mobil-Dateien. Sobald <em>beide aktuellen Profile</em> vollständig vorhanden sind und das Original weiterhin zugänglich ist, dürfen alte App-Cache-Versionen entfernt werden. Bei der benutzerbezogenen Bereinigung nach Tour-Nutzung werden höchstens 20 verschiedene Panoramen und 40 veraltete Dateien pro Auftrag bearbeitet; diese Prüfung wird pro Benutzer höchstens einmal innerhalb von 24 Stunden eingeplant.</li>
          <li><strong>Nicht mehr verwendete Panoramen:</strong> Ein eigenständiger Nextcloud-Hintergrundjob ist auf einen Abstand von <strong>24 Stunden</strong> eingestellt. Er kontrolliert pro Lauf höchstens <strong>40 Panorama-Datei-IDs</strong> und prüft dafür die gespeicherten Touren <em>aller Benutzer</em>. Ist ein Panorama nirgendwo mehr verknüpft, wird es zunächst nur als <strong>„Für Bereinigung vorgemerkt“</strong> gekennzeichnet. Erst nach <strong>7 vollen Tagen</strong> ohne Verwendung und einer erneuten vollständigen Prüfung dürfen seine App-Cache-Dateien gelöscht werden.</li>
        </ol>
        <p><strong>Sicherheitsregeln:</strong> Taucht ein vorgemerktes Panorama wieder in einer Tour auf, wird die Vormerkung aufgehoben. Ändert sich sein Cache-Dateibestand, beginnt die Schutzfrist erneut. Bei fehlerhaften oder unvollständigen Prüfungen wird vorsichtshalber nichts gelöscht. Eine abgelaufene Schutzfrist bedeutet nicht, dass die Löschung genau zu diesem Zeitpunkt erfolgt: Sie geschieht erst bei einem passenden späteren Hintergrundjob.</p>
        <p><strong>Steuerung:</strong> Die Bereinigung läuft nur bei aktivierter <em>automatischer Panorama-Optimierung</em> und funktionierendem Nextcloud-Cron. Das Pausieren stoppt neue Bereinigungsdurchläufe, löscht aber keine vorhandenen Bilder. In der Cache-Statistik zeigt <em>„Schutzfrist bis“</em> den frühestmöglichen Löschzeitpunkt an, nicht einen verbindlichen Ausführungstermin.</p>
        <h4>Warum kann Nextcloud selbst kleinere oder keine Vorschauen liefern?</h4>
        <p>Die integrierte Nextcloud-Vorschaugenerierung hat eigene Sicherheits- und Ressourcengrenzen. Die dokumentierten Standardwerte sind <code>preview_max_x = 4096</code> und <code>preview_max_y = 4096</code> Pixel; für den GD-Bildgenerator gilt standardmäßig <code>preview_max_filesize_image = 50</code> MB. Darüber hinaus können <code>preview_max_memory</code>, PHP/ImageMagick-Ressourcen, unterstützte Vorschaugeneratoren und bereits vorhandene kleinere Cachevarianten eine Rolle spielen. Das sind <strong>Nextcloud-Standardwerte</strong>, nicht automatisch die Einstellungen dieses Servers.</p>
        <h4>Was kann ich bei zu kleinen oder fehlenden Panoramen tun?</h4>
        <ol>
          <li>Zuerst prüfen, ob die <strong>automatische Panorama-Optimierung</strong> aktiviert ist und die App-Cache-Statistik beide Profile anzeigt. Bei großen Panoramen zunächst nur <strong>einen</strong> Worker einstellen.</li>
          <li>Nextcloud-Cron, PHP Imagick, freien Arbeitsspeicher und Nextcloud-Protokolle kontrollieren. Solange die App-Vorschau nicht fertig ist, versucht die Tour zunächst die normale Nextcloud-Vorschau und bei Fehlern das berechtigte Original.</li>
          <li>Falls die <em>allgemeinen</em> Nextcloud-Vorschauen verbessert werden sollen, die aktuellen Werte für <code>preview_max_x</code>, <code>preview_max_y</code>, <code>preview_max_filesize_image</code> und <code>preview_max_memory</code> mit <code>occ config:system:get</code> prüfen. Erst nach Sicherung und Ressourcenprüfung vorsichtig anpassen; größere Werte können CPU, RAM und Speicherbedarf deutlich erhöhen.</li>
          <li>Bestehende kleine Vorschauen werden durch geänderte Limits nicht zwingend sofort neu erzeugt. Den globalen Nextcloud-Vorschau-Cache <strong>nicht pauschal löschen</strong>; für Touren zuerst die App-eigenen optimierten Profile verwenden. Einzelheiten stehen in der <a href="https://docs.nextcloud.com/server/stable/admin_manual/configuration_files/previews_configuration.html" target="_blank" rel="noopener noreferrer">offiziellen Nextcloud-Vorschau-Dokumentation</a>.</li>
        </ol>
        <h4>Diagnose-Links (nur für Administratoren)</h4>
        <p>Die beiden folgenden Endpunkte gehören zur App, liefern lesbare JSON-Daten und ändern nichts:</p>
        <ul>
          <li><a href="<?php p($_['panoramaCacheStatusUrl']); ?>" target="_blank" rel="noopener noreferrer">Cache-Status anzeigen</a> – meldet, ob die Optimierung aktiviert ist und wie viele Worker eingestellt sind.</li>
          <li><a href="<?php p($_['panoramaCacheStatisticsUrl']); ?>" target="_blank" rel="noopener noreferrer">Cache-Statistik (JSON) anzeigen</a> – zeigt den App-Cache-Status und die geschützte Zuordnung zu Touren.</li>
        </ul>
        <h4>Diagnose direkt im öffentlichen Tour-Viewer</h4>
        <p>Hänge an <strong>deinen eigenen öffentlichen Tour-Link</strong> den Parameter <code>?ppi_preload_debug=1</code> an (wenn die URL bereits ein <code>?</code> enthält, <code>&amp;ppi_preload_debug=1</code>). Dann zeigt der Browser ein lokales Diagnosefenster zu Ladezeiten, Auflösungswahl, Vorladen und Texturverarbeitung. Das hilft bei langsamer Darstellung. Die Tour-Freigabeberechtigungen gelten unverändert; bitte öffentliche Freigabelinks und vertrauliche Diagnosedaten nicht ungeschützt weitergeben.</p>
        <p>Hinweis: Die JSON-Links oben sind Admin-Diagnosefunktionen, während <code>ppi_preload_debug=1</code> eine browserseitige Viewer-Diagnose ist. Es werden dadurch keine globalen Nextcloud-Vorschauen gelöscht oder Grenzen geändert.</p>
      </div>
    </details>

  </div>
  </section>
  <section class="ppi-settings-group" aria-labelledby="ppi-navigation-group-title">
    <h3 class="ppi-section-heading" id="ppi-navigation-group-title">Navigation · 360°-Touren</h3>
    <div class="ppi-settings-panel" id="ppi-tour-navigation-panel"
         data-status-url="<?php p($_['tourNavigationStatusUrl']); ?>"
         data-save-url="<?php p($_['tourNavigationSaveUrl']); ?>">
      <h3>Verknüpfung im Nextcloud-App-Menü</h3>
      <p>Öffnet mit einem eigenen Menüeintrag direkt die persönliche Tourenübersicht. Der bisherige Zugang bleibt bestehen. Die Einstellung gilt für alle angemeldeten Benutzer dieser Nextcloud-Installation.</p>
      <label for="ppi-tour-navigation-enabled" style="display:flex;align-items:center;gap:10px;cursor:pointer;">
        <input type="checkbox" id="ppi-tour-navigation-enabled">
        <strong>360°-Touren im Nextcloud-Menü anzeigen</strong>
      </label>
      <p style="margin-top:14px;"><button type="button" class="primary" id="ppi-tour-navigation-save">Menüeinstellung speichern</button></p>
      <p id="ppi-tour-navigation-feedback" role="status" aria-live="polite"></p>
      <p class="ppi-note">Nach dem Speichern die Seite neu laden. Das Menü zeigt ausschließlich den Zugang zu den eigenen Touren; öffentliche Freigaben und Dateiberechtigungen werden dadurch nicht verändert.</p>
    </div>
  </section>

</div>
