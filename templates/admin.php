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
     data-list-url="<?php p($_['listUrl']); ?>"
     data-upload-url="<?php p($_['uploadUrl']); ?>"
     data-select-url="<?php p($_['selectUrl']); ?>"
     data-delete-url="<?php p($_['deleteUrl']); ?>"
     data-options-url="<?php p($_['optionsUrl']); ?>"
     data-reset-url="<?php p($_['resetUrl']); ?>"
     data-restore-default-url="<?php p($_['restoreDefaultUrl']); ?>">
  <div class="ppi-settings-panel ppi-settings-intro">
    <h2>360° Panorama – Einstellungen</h2>
    <p>Verwalte die Wasserzeichen und Zoom-Einstellungen für deine Panoramaansichten. Die Einstellungen gelten auch für öffentliche Freigaben; Originalbilder bleiben unverändert.</p>
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
  </style>

  <h3 class="ppi-section-heading">Wasserzeichen-Verwaltung</h3>
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

  <h3 class="ppi-section-heading">Zoom und Blickwinkel</h3>
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
        <label for="ppi-zoom-mobileStart">Start-Blickwinkel <output id="ppi-zoom-mobileStart-out">90°</output></label>
        <input id="ppi-zoom-mobileStart" type="range" min="60" max="145" step="1" value="90" />
        <label for="ppi-zoom-mobileMax">Maximaler Zoom-out <output id="ppi-zoom-mobileMax-out">110°</output></label>
        <input id="ppi-zoom-mobileMax" type="range" min="80" max="150" step="1" value="110" />
      </div>
    </div>
    <p style="margin:12px 0 8px;"><button type="button" class="primary" id="ppi-zoom-save">Zoom-Einstellungen speichern</button> <button type="button" id="ppi-zoom-reset" style="margin-left:10px;">Standardwerte wiederherstellen</button></p>
    <p id="ppi-zoom-feedback" role="status" aria-live="polite"></p>
  </div>
</div>
