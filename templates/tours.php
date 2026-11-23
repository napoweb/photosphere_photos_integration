<?php
/** @var array $_ */
?>
<div id="ppi-tours-app"
     data-start-pages-url="<?php p($_['startPagesUrl']); ?>"
     data-api-url="<?php p($_['apiUrl']); ?>"
     data-files-url="<?php p($_['filesUrl']); ?>"
     data-picker-preferences-url="<?php p($_['pickerPreferencesUrl']); ?>"
     data-picker-panorama-only="<?php p($_['pickerPanoramaOnly'] ? '1' : '0'); ?>"
     data-image-url="<?php p($_['imageUrl']); ?>"
     data-watermark-library-url="<?php p($_['watermarkLibraryUrl']); ?>"
     data-watermark-thumbnail-url="<?php p($_['watermarkThumbnailUrl']); ?>"
     style="width:100%;max-width:1040px;min-width:0;box-sizing:border-box;margin:22px auto;padding:0 20px 50px;font-family:system-ui,sans-serif;">
  <div class="ppi-tour-intro">
  <header class="ppi-tour-intro-header">
    <span id="ppi-section-icon" style="font-size:36px" aria-hidden="true">🌐</span>
    <div class="ppi-tour-intro-heading"><h1 id="ppi-section-heading" style="font-size:26px;font-weight:700;margin:0">Meine 360°-Touren</h1>
      <p id="ppi-section-subtitle" style="margin:4px 0 0;color:var(--color-text-maxcontrast)">Touren aus deinen Nextcloud-Panoramen zusammenstellen</p></div>
    <button class="primary" id="ppi-tour-create" type="button">+ Neue Tour erstellen</button>
    <button class="primary" id="ppi-start-page-create" type="button" hidden>+ Neue Startseite</button>
  </header>
  <p id="ppi-tour-message" role="status" aria-live="polite" hidden></p>
  </div>
  <nav class="ppi-startpage-switch" id="ppi-overview-switch" aria-label="Verwaltungsbereich auswählen">
    <button type="button" id="ppi-mode-tours" aria-pressed="true">🌐 360°-Touren</button>
    <button type="button" id="ppi-mode-startpages" aria-pressed="false">▦ Startseiten</button>
  </nav>
  <section class="ppi-tour-card" id="ppi-start-pages-overview" hidden>
    <h2>Meine Startseiten</h2>
    <p>Eigene 360°-Startseiten mit einer Auswahl deiner Touren. Öffentliche Freigaben folgen nach dem Sicherheitstest.</p>
    <div id="ppi-start-pages-list" class="ppi-tour-list"></div>
  </section>
  <section class="ppi-tour-card" id="ppi-start-page-editor" hidden>
    <div class="ppi-start-page-toolbar">
      <button type="button" id="ppi-start-page-back">← Alle Startseiten</button>
      <div><button type="button" id="ppi-start-page-delete">Startseite löschen</button> <button type="button" class="primary" id="ppi-start-page-save">Änderungen speichern</button></div>
    </div>
    <h2>Startseite gestalten</h2>
    <nav class="ppi-startpage-editor-tabs" aria-label="Startseite einrichten">
      <button type="button" data-startpage-tab="start" aria-current="step">1 · Startseite</button>
      <button type="button" data-startpage-tab="tours">2 · Touren wählen</button>
      <button type="button" data-startpage-tab="layout">3 · Layout</button>
      <button type="button" data-startpage-tab="share">4 · Freigabe</button>
    </nav>
    <div class="ppi-startpage-workspace">
      <div class="ppi-start-page-fields">
        <section class="ppi-startpage-editor-panel" data-startpage-panel="start">
          <h3>Überschrift und Texte</h3>
          <label>Name der Startseite (intern) <input id="ppi-start-page-name" maxlength="100"></label>
          <label>Überschrift für Besucher <input id="ppi-start-page-headline" maxlength="120"></label>
          <label>Beschreibung <textarea id="ppi-start-page-description" maxlength="1000" rows="4"></textarea></label>
        </section>
        <section class="ppi-startpage-editor-panel" data-startpage-panel="layout" hidden>
          <h3>Layout und Hintergrund</h3>
          <label>Layout <select id="ppi-start-page-layout"><option value="hero">Vollbild – Hintergrund über gesamte Fläche</option><option value="split">Kopfbereich – Hintergrund nur oben</option><option value="gallery">Galerie – kompakt ohne großen Kopfbereich</option></select></label>
          <label>Hintergrundfarbe (Tourenbereich) <input id="ppi-start-page-background-color" type="color" value="#000000"></label>
          <label id="ppi-start-page-header-color-row" hidden>Kopfbereich-Farbe (ohne Hintergrundbild) <input id="ppi-start-page-header-color" type="color" value="#000000"></label>
          <label>Überschriftenfarbe <input id="ppi-start-page-heading-color" type="color" value="#ffffff"></label>
          <label>Beschreibungsfarbe <input id="ppi-start-page-description-color" type="color" value="#ffffff"></label>
          <fieldset class="ppi-startpage-effect-picker">
            <legend>Design-Effekt</legend>
            <p>Wähle zwischen Dezent, Glas und Glanz-Glas. Alle Varianten behalten die Schriftkontur und die dezenten Schatten.</p>
            <div class="ppi-startpage-effect-options" role="group" aria-label="Design-Effekt wählen">
              <button type="button" data-startpage-effect="subtle" aria-pressed="true">
                <span class="ppi-effect-mini ppi-effect-mini-subtle" aria-hidden="true"><i>360°</i></span>
                <span>Dezent</span>
              </button>
              <button type="button" data-startpage-effect="glass" aria-pressed="false">
                <span class="ppi-effect-mini ppi-effect-mini-glass" aria-hidden="true"><i>360°</i></span>
                <span>Glas</span>
              </button>
              <button type="button" data-startpage-effect="glossy" aria-pressed="false">
                <span class="ppi-effect-mini ppi-effect-mini-glossy" aria-hidden="true"><i>360°</i></span>
                <span>Glanz-Glas</span>
              </button>
            </div>
            <input type="hidden" id="ppi-start-page-effect" value="subtle">
          </fieldset>
          <fieldset class="ppi-startpage-layout-presets">
            <legend>Tour-Kacheln anordnen</legend>
            <p>Klicke auf eine Anordnung. Die Vorschau passt sich sofort an.</p>
            <div id="ppi-start-page-tile-presets" class="ppi-startpage-layout-presets-grid" role="group" aria-label="Kachelanordnung auswählen">
              <button type="button" class="ppi-startpage-layout-preset" data-columns="auto" aria-pressed="true" title="Automatisch: 1 groß, 2 nebeneinander, bei 3 eine große Kachel">
                <span class="ppi-layout-mini ppi-layout-mini-auto" aria-hidden="true"><i></i><i></i><i></i></span><span>Automatisch</span>
              </button>
              <button type="button" class="ppi-startpage-layout-preset" data-columns="2" aria-pressed="false" title="Zwei Kacheln pro Reihe">
                <span class="ppi-layout-mini ppi-layout-mini-2" aria-hidden="true"><i></i><i></i><i></i><i></i></span><span>2 Spalten</span>
              </button>
              <button type="button" class="ppi-startpage-layout-preset" data-columns="3" aria-pressed="false" title="Drei Kacheln pro Reihe">
                <span class="ppi-layout-mini ppi-layout-mini-3" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></span><span>3 Spalten</span>
              </button>
              <button type="button" class="ppi-startpage-layout-preset" data-columns="4" aria-pressed="false" title="Vier Kacheln pro Reihe">
                <span class="ppi-layout-mini ppi-layout-mini-4" aria-hidden="true"><i></i><i></i><i></i><i></i></span><span>4 Spalten</span>
              </button>
              <button type="button" class="ppi-startpage-layout-preset" data-columns="5" aria-pressed="false" title="Fünf Kacheln pro Reihe">
                <span class="ppi-layout-mini ppi-layout-mini-5" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span><span>5 Spalten</span>
              </button>
            </div>
            <input id="ppi-start-page-tile-columns" type="hidden" value="auto">
          </fieldset>
          <fieldset class="ppi-startpage-background-picker">
            <legend>Hintergrundbild aus Nextcloud-Dateien</legend>
            <p>Wähle ein JPEG aus deinen Dateien. Die Vorschau lädt nur eine optimierte Nextcloud-Vorschau statt des großen Originals. Neue Bilder kannst du vorher in Nextcloud „Dateien“ hochladen.</p>
            <div class="ppi-startpage-bg-toolbar">
              <button type="button" id="ppi-start-page-bg-browse">Bilder auswählen</button>
              <button type="button" id="ppi-start-page-bg-remove">Hintergrundbild entfernen</button>
            </div>
            <p id="ppi-start-page-bg-selected">Kein Bild ausgewählt</p>
            <div id="ppi-start-page-bg-browser" hidden>
              <div class="ppi-startpage-bg-toolbar">
                <button type="button" id="ppi-start-page-bg-up">← Übergeordneter Ordner</button>
                <span id="ppi-start-page-bg-path">/</span>
              </div>
              <div id="ppi-start-page-bg-files" class="ppi-startpage-bg-files"></div>
            </div>
          </fieldset>
        </section>
        <section class="ppi-startpage-editor-panel" data-startpage-panel="tours" hidden>
          <h3>Touren auswählen</h3>
          <p>Wähle Touren aus und ziehe sie am Griff ☰ per Drag-and-drop in deine gewünschte Reihenfolge. Mit ↑ und ↓ kannst du die Reihenfolge auch ohne Maus ändern. Die Anordnung der Kacheln legst du unter „Layout“ fest.</p>
          <fieldset><legend>Eigene Touren</legend><div id="ppi-start-page-tours"></div></fieldset>
        </section>
        <section class="ppi-startpage-editor-panel" data-startpage-panel="share" hidden>
          <h3>Freigabe</h3>
          <p class="ppi-startpage-private-note">Ein eigener Startseiten-Link kann optional mit Passwort und Ablaufdatum geschützt werden. Touren behalten ihre bisherigen Freigaberegeln. Nur bereits freigegebene Touren erscheinen für Besucher.</p>
          <label>Ablaufdatum (optional) <input type="date" id="ppi-start-page-share-expiry"></label>
          <label>Startseiten-Passwort (optional, leer lassen zum Beibehalten) <input type="password" id="ppi-start-page-share-password" maxlength="128" autocomplete="new-password"></label>
          <p id="ppi-start-page-share-status">Noch kein Freigabelink erstellt</p>
          <div class="ppi-startpage-bg-toolbar">
            <button type="button" id="ppi-start-page-share-enable">Freigabelink erstellen / Einstellungen speichern</button>
            <button type="button" id="ppi-start-page-share-remove-password">Passwort entfernen</button>
            <button type="button" id="ppi-start-page-share-disable">Freigabelink deaktivieren</button>
          </div>
          <label>Startseiten-Link <input type="text" id="ppi-start-page-share-url" readonly></label>
          <div class="ppi-startpage-bg-toolbar">
            <button type="button" id="ppi-start-page-share-copy">Link kopieren</button>
            <button type="button" id="ppi-start-page-share-open">Startseite im neuen Tab öffnen</button>
          </div>
        </section>
      </div>
      <aside class="ppi-startpage-preview-column" aria-label="Live-Vorschau der Startseite">
        <div class="ppi-startpage-preview-heading">
          <h3>Live-Vorschau</h3>
          <span id="ppi-startpage-dirty" role="status">Gespeicherter Stand</span>
        </div>
        <div id="ppi-startpage-preview" class="ppi-startpage-live-preview" data-layout="hero">
          <div class="ppi-startpage-preview-hero">
            <div class="ppi-startpage-preview-header">
              <h2 id="ppi-startpage-preview-title"></h2>
              <p id="ppi-startpage-preview-description"></p>
            </div>
          </div>
          <div id="ppi-startpage-preview-grid" class="ppi-startpage-preview-grid"></div>
        </div>
        <p class="ppi-startpage-preview-note">Die Vorschau reagiert sofort. Erst „Änderungen speichern“ übernimmt deine Einstellungen dauerhaft. Tour-Kacheln sind hier noch nicht öffentlich anklickbar.</p>
      </aside>
    </div>
  </section>
  <section class="ppi-tour-card" id="ppi-tour-overview">
    <h2>Meine Touren</h2>
    <div id="ppi-tour-list" class="ppi-tour-list"></div>
  </section>
  <section class="ppi-tour-card" id="ppi-tour-editor" hidden>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:space-between">
      <button type="button" id="ppi-tour-back">← Alle Touren</button>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <button type="button" id="ppi-tour-delete">Tour löschen</button>
        <button type="button" class="primary" id="ppi-tour-save">Änderungen speichern</button>
        <button type="button" class="primary" id="ppi-design-save" hidden>Darstellung speichern</button>
      </div>
    </div>
    <nav class="ppi-setup-tabs" aria-label="Tour einrichten">
      <button type="button" class="ppi-setup-tab" data-step="edit" aria-current="step">1 · Tour bearbeiten</button>
      <button type="button" class="ppi-setup-tab" data-step="design">2 · Darstellung</button>
      <button type="button" class="ppi-setup-tab" data-step="share">3 · Freigabe</button>
    </nav>
    <section id="ppi-step-design" hidden aria-label="Darstellung der Tour konfigurieren">
      <h2>Darstellung der Tour konfigurieren</h2>
      <p class="ppi-step-help">Bestimme, wie Besucher deine Tour erleben. Die Einstellungen gelten für die öffentliche Tour und bleiben beim Bearbeiten erhalten.</p>
      <div class="ppi-design-name-row"><div class="ppi-design-field ppi-design-name"><label for="ppi-tour-name">Name der Tour</label><input id="ppi-tour-name" maxlength="100" placeholder="Zum Beispiel: Gartenrundgang" required><p>In der Tourenübersicht und optional auf der öffentlichen Startseite sichtbar.</p></div></div>
      <div class="ppi-design-top">
        <div class="ppi-design-mini"><label for="ppi-design-start">Startpanorama</label><div class="ppi-design-mini-image"><img id="ppi-design-start-preview" alt="Vorschau des ausgewählten Startpanoramas"></div><select id="ppi-design-start" aria-label="Startpanorama auswählen"></select></div>
        <div class="ppi-design-mini ppi-design-watermark" aria-label="Wasserzeichen der Tour">
          <div class="ppi-design-watermark-heading">
            <label for="ppi-design-watermark-id" title="Nur vom Administrator für Touren freigegebene Wasserzeichen">Wasserzeichen <span class="ppi-design-info-mark" aria-label="Nur freigegebene Wasserzeichen" title="Nur vom Administrator freigegebene Wasserzeichen sind auswählbar">ⓘ</span></label>
            <label class="ppi-design-watermark-toggle" title="Wasserzeichen in der Tour anzeigen"><input type="checkbox" id="ppi-design-watermark-enabled"> Anzeigen</label>
          </div>
          <div class="ppi-design-mini-image ppi-design-watermark-preview" aria-live="polite"><img id="ppi-design-watermark-preview" alt="Vorschau des gewählten Wasserzeichens" hidden><span id="ppi-design-watermark-placeholder">Kein Wasserzeichen</span></div>
          <div id="ppi-design-watermark-choice" hidden>
            <select id="ppi-design-watermark-id" aria-label="Wasserzeichen aus der Admin-Galerie"><option value="none">Keine freigegebenen Wasserzeichen</option></select>
          </div>
          <p id="ppi-design-watermark-info" class="ppi-design-watermark-info" aria-live="polite">Nur freigegebene Wasserzeichen verfügbar.</p>
        </div>
        <div class="ppi-design-mini ppi-svg-pin-colors" id="ppi-design-pin-colors">
          <label for="ppi-design-pin-color">Farbe der SVG-Pins</label>
          <div class="ppi-design-mini-image ppi-design-color-preview" title="Farbe auswählen">
            <span id="ppi-design-color-chip" aria-hidden="true"></span>
            <input type="color" id="ppi-design-pin-color" value="#1687bc" aria-label="Farbe der SVG-Pins wählen">
          </div>
          <output id="ppi-design-pin-color-hex" aria-live="polite">#1687BC</output>
          <div class="ppi-svg-color-swatches" aria-label="Voreingestellte Farben"><button type="button" data-pin-color="#1687bc" aria-label="Blau" title="Blau" style="--swatch:#1687bc"></button><button type="button" data-pin-color="#e54848" aria-label="Rot" title="Rot" style="--swatch:#e54848"></button><button type="button" data-pin-color="#32a36b" aria-label="Grün" title="Grün" style="--swatch:#32a36b"></button><button type="button" data-pin-color="#f09629" aria-label="Orange" title="Orange" style="--swatch:#f09629"></button><button type="button" data-pin-color="#ffffff" aria-label="Weiß" title="Weiß" style="--swatch:#ffffff"></button><button type="button" data-pin-color="#111111" aria-label="Schwarz" title="Schwarz" style="--swatch:#111111"></button></div>
          <p class="ppi-design-color-note">Nur für SVG-Pins</p>
        </div>
        <div class="ppi-design-mini"><label for="ppi-design-style">Hotspot-Design</label><div class="ppi-design-mini-image ppi-design-hotspot-preview"><img id="ppi-design-style-preview" alt="Vorschau des ausgewählten Hotspot-Designs"><span id="ppi-design-pin-preview" role="img" aria-label="Vorschau des ausgewählten SVG-Pins" hidden></span><svg id="ppi-design-classic-preview" viewBox="0 0 90 66" aria-label="Klassisches Wechselpunkt-Männchen"><path d="M 13 8 Q 20 28 33 31 L 40 39 M 77 8 Q 68 27 56 31 L 50 39 M 40 39 Q 32 49 31 66 M 50 39 Q 59 49 60 66" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="6 4"/><ellipse cx="45" cy="24" rx="10" ry="13" fill="none" stroke="currentColor" stroke-width="4" stroke-dasharray="6 3"/></svg></div><select id="ppi-design-style" aria-label="Hotspot-Design auswählen"><option value="classic">Klassisches Männchen</option><option value="blue">Blauer 360°-Guide – Zielbild bei Hover</option><option value="map-pin">Map-Pin – mit Zielpanorama</option><option value="label-pin">Label-Pin – mit Zielpanorama</option><option value="outline-pin">Kontur-Pin – mit Zielpanorama</option></select></div>
      </div>
      <div class="ppi-design-layout">
        <div class="ppi-design-group" aria-label="Ansicht und Bedienung">
          <div class="ppi-design-field"><label for="ppi-design-yaw">Startblickrichtung (horizontal)</label><div class="ppi-design-range"><input type="range" id="ppi-design-yaw" min="-180" max="180" step="1" value="0"><output id="ppi-design-yaw-value">0°</output></div></div>
          <div class="ppi-design-field ppi-design-social"><label for="ppi-design-social">Linkvorschau in WhatsApp und anderen Messengern</label><select id="ppi-design-social"><option value="cover">Automatisch: Startpanorama oder neutrales Motiv</option><option value="neutral">Immer neutrale 360°-Vorschau</option><option value="off">Keine zusätzlichen Open-Graph-Metadaten</option></select><p id="ppi-design-social-status" role="status" aria-live="polite"></p><p>Bei Passwortschutz bleiben private Tourinhalte verborgen. Messenger können bereits geladene Vorschauen zwischenspeichern.</p></div>
          <div class="ppi-design-field ppi-design-options"><label><input type="checkbox" id="ppi-design-title" checked> Tour-Titel anzeigen</label><label><input type="checkbox" id="ppi-design-nav" checked> Panorama-Navigation anzeigen</label><label><input type="checkbox" id="ppi-design-fullscreen" checked> Vollbildschaltfläche anzeigen</label></div>
        </div>
        <div class="ppi-design-group" aria-label="Öffentliche Präsentation">
          <div class="ppi-design-field"><label for="ppi-design-size">Größe des Hotspot-Designs</label><div class="ppi-design-range"><input type="range" id="ppi-design-size" min="0" max="4" step="1" value="2" aria-describedby="ppi-design-size-label"><output id="ppi-design-size-label">Mittel</output></div></div>
          <div class="ppi-design-field ppi-design-intro">
            <label for="ppi-design-intro">Beschreibung auf der öffentlichen Startseite</label>
            <div class="ppi-intro-toolbar" role="toolbar" aria-label="Beschreibung formatieren">
              <button type="button" data-intro-insert="bullet" title="Aufzählung einfügen">• Aufzählung</button>
              <button type="button" data-intro-insert="number" title="Nummerierung einfügen">1. Nummerierung</button>
              <button type="button" data-intro-insert="link" title="Sicheren Internetlink einfügen">🔗 Link</button>
              <button type="button" id="ppi-intro-icons-toggle" aria-controls="ppi-intro-icons" aria-expanded="false" title="Symbolauswahl öffnen">▦ Symbole</button>
            </div>
            <div class="ppi-intro-icons" id="ppi-intro-icons" role="group" aria-label="Symbole für die Tourbeschreibung einfügen" hidden>
              <div class="ppi-intro-icon-group"><strong>Quad, MTB & Outdoor</strong><div class="ppi-intro-icon-grid">
                <button type="button" data-intro-icon=":quad:" title="Quad / ATV" aria-label="Quad / ATV"><svg viewBox="0 0 64 36" aria-hidden="true" focusable="false"><circle cx="14" cy="27" r="7"/><circle cx="50" cy="27" r="7"/><path d="M12 19 L20 18 L26 14 L41 14 L48 18 L55 19 M20 18 L25 26 L42 26 L48 18 M27 13 L30 9 L38 9 M43 14 L44 8 L50 8"/></svg></button>
                <button type="button" data-intro-icon="🏁" title="Offroad" aria-label="Offroad">🏁</button>
                <button type="button" data-intro-icon="🚵" title="MTB" aria-label="MTB">🚵</button>
                <button type="button" data-intro-icon="🚴" title="Radtour" aria-label="Radtour">🚴</button>
                <button type="button" data-intro-icon="🚲" title="Fahrrad" aria-label="Fahrrad">🚲</button>
                <button type="button" data-intro-icon="🛞" title="Reifen" aria-label="Reifen">🛞</button>
                <button type="button" data-intro-icon="⛰️" title="Berge" aria-label="Berge">⛰️</button>
                <button type="button" data-intro-icon="🥾" title="Wandern" aria-label="Wandern">🥾</button>
                <button type="button" data-intro-icon="🌲" title="Wald" aria-label="Wald">🌲</button>
                <button type="button" data-intro-icon="🧭" title="Kompass" aria-label="Kompass">🧭</button>
                <button type="button" data-intro-icon="🏕️" title="Camping" aria-label="Camping">🏕️</button>
              </div></div>
              <div class="ppi-intro-icon-group"><strong>Foto, Video & 360°</strong><div class="ppi-intro-icon-grid">
                <button type="button" data-intro-icon=":360:" title="360°-Panorama" aria-label="360°-Panorama"><span class="ppi-intro-360-badge">360°</span></button>
                <button type="button" data-intro-icon=":drone:" title="Kameradrohne" aria-label="Kameradrohne"><svg viewBox="0 0 64 36" aria-hidden="true" focusable="false"><circle cx="12" cy="11" r="6"/><circle cx="52" cy="11" r="6"/><path d="M18 13 L27 19 L37 19 L46 13 M18 11 H26 M38 11 H46 M27 19 H37 L40 25 H24 Z"/><rect x="28" y="24" width="8" height="7" rx="2"/></svg></button>
                <button type="button" data-intro-icon="📷" title="Fotokamera" aria-label="Fotokamera">📷</button>
                <button type="button" data-intro-icon="📸" title="Fotoaufnahme" aria-label="Fotoaufnahme">📸</button>
                <button type="button" data-intro-icon="🎥" title="Film-Kamera" aria-label="Film-Kamera">🎥</button>
                <button type="button" data-intro-icon="📹" title="Videokamera" aria-label="Videokamera">📹</button>
                <button type="button" data-intro-icon="🎬" title="Film" aria-label="Film">🎬</button>
                <button type="button" data-intro-icon="🎞️" title="Filmrolle" aria-label="Filmrolle">🎞️</button>
                <button type="button" data-intro-icon="🖼️" title="Galerie" aria-label="Galerie">🖼️</button>
                <button type="button" data-intro-icon="🤳" title="Selfie" aria-label="Selfie">🤳</button>
                <button type="button" data-intro-icon="🌄" title="Panorama" aria-label="Panorama">🌄</button>
                <button type="button" data-intro-icon="🏞️" title="Landschaft" aria-label="Landschaft">🏞️</button>
                <button type="button" data-intro-icon="🔍" title="Zoom" aria-label="Zoom">🔍</button>
                <button type="button" data-intro-icon="🎦" title="Video" aria-label="Video">🎦</button>
                <button type="button" data-intro-icon="📱" title="Handyfoto" aria-label="Handyfoto">📱</button>
                <button type="button" data-intro-icon="👁️" title="Ansicht" aria-label="Ansicht">👁️</button>
                <button type="button" data-intro-icon="✨" title="Besonderes Motiv" aria-label="Besonderes Motiv">✨</button>
                <button type="button" data-intro-icon="🌐" title="Rundumblick" aria-label="Rundumblick">🌐</button>
              </div></div>
              <div class="ppi-intro-icon-group"><strong>Urlaub & Orte</strong><div class="ppi-intro-icon-grid">
                <button type="button" data-intro-icon="🏖️" title="Urlaub" aria-label="Urlaub">🏖️</button>
                <button type="button" data-intro-icon="✈️" title="Flugreise" aria-label="Flugreise">✈️</button>
                <button type="button" data-intro-icon="🧳" title="Reise" aria-label="Reise">🧳</button>
                <button type="button" data-intro-icon="🏠" title="Home" aria-label="Home">🏠</button>
                <button type="button" data-intro-icon="📍" title="Standort" aria-label="Standort">📍</button>
                <button type="button" data-intro-icon="🗺️" title="Karte" aria-label="Karte">🗺️</button>
                <button type="button" data-intro-icon="🌍" title="Welt" aria-label="Welt">🌍</button>
                <button type="button" data-intro-icon="☀️" title="Sonne" aria-label="Sonne">☀️</button>
                <button type="button" data-intro-icon="🌅" title="Sonnenuntergang" aria-label="Sonnenuntergang">🌅</button>
                <button type="button" data-intro-icon="⭐" title="Highlight" aria-label="Highlight">⭐</button>
                <button type="button" data-intro-icon="❤️" title="Favorit" aria-label="Favorit">❤️</button>
                <button type="button" data-intro-icon="🏡" title="Ferienhaus" aria-label="Ferienhaus">🏡</button>
              </div></div>
            </div>            <textarea id="ppi-design-intro" rows="4" maxlength="300" placeholder="Begrüßung, Aufzählungen, Links und Symbole"></textarea>
            <p>Optional, maximal 300 Zeichen (einschließlich Listenzeichen, Links und Symbolcodes). Listen, Links und Symbole erscheinen formatiert auf der öffentlichen Startseite. Quad, Drohne und 360° erscheinen dort als eigene Vektorsymbole.</p>
          </div>
        </div>
      </div>
      <div class="ppi-design-future"><strong>Für kommende Versionen vorgesehen:</strong> Automatische Drehung.</div>

    </section>
    <div id="ppi-step-share" hidden>
    <h2>Tour freigeben</h2>
    <p class="ppi-step-help">Hier verwaltest du deinen öffentlichen Tour-Link, sein Ablaufdatum und die Erneuerung des Tokens. Passwortschutz, Ablaufdatum und Token-Verwaltung stehen hier zur Verfügung.</p>
    <section class="ppi-tour-share-box" aria-label="Öffentliche Tour-Freigabe">
      <div class="ppi-share-heading"><strong>Bisheriger Hauptlink</strong><span id="ppi-share-state">Nicht freigegeben</span></div>
      <p>Nur die gespeicherten Panoramen dieser Tour werden über den Link erreichbar. Jeder mit dem Link kann diese Bilder ansehen.</p>
      <div class="ppi-share-actions">
        <button type="button" id="ppi-share-enable">Öffentlichen Link erstellen</button>
        <button type="button" id="ppi-share-disable" hidden>Hauptlink deaktivieren</button>
      </div>
      <div class="ppi-share-expiry-row"><label for="ppi-share-expiry">Ablaufdatum (optional)</label><input type="date" id="ppi-share-expiry" aria-label="Ablaufdatum der öffentlichen Freigabe"/><button type="button" id="ppi-share-expiry-save" hidden>Ablaufdatum speichern</button><span class="ppi-share-expiry-hint">Ohne Datum bleibt der Link gültig, bis du ihn deaktivierst.</span></div>
      <div class="ppi-share-password-row">
        <label for="ppi-share-password">Passwortschutz (optional)</label>
        <span id="ppi-share-password-state">Kein Passwort eingerichtet</span>
        <div class="ppi-share-password-controls">
          <input id="ppi-share-password" type="password" autocomplete="new-password" maxlength="128" placeholder="Passwort nach Nextcloud-Freigaberichtlinie" />
          <button type="button" id="ppi-share-password-save">Passwort setzen / ändern</button>
          <button type="button" id="ppi-share-password-remove" hidden>Passwort entfernen</button>
        </div>
        <span class="ppi-share-expiry-hint">Es gelten die Nextcloud-Admin-Einstellungen „Passwortrichtlinien freigeben“. Passwörter werden nur gehasht gespeichert; Änderungen sperren frühere Besuchersitzungen.</span>
      </div>
      <div class="ppi-share-custom-row" aria-label="Individuellen Freigabelink festlegen">
        <label for="ppi-share-custom-token">Individueller Link-Token (optional)</label>
        <div class="ppi-share-custom-controls"><input id="ppi-share-custom-token" type="text" maxlength="64" minlength="6" spellcheck="false" autocomplete="off" placeholder="z. B. meine-insel-tour" /><button type="button" id="ppi-share-custom-save">Wunsch-Token übernehmen</button></div>
        <span class="ppi-share-expiry-hint">6–64 Zeichen: Kleinbuchstaben, Ziffern und Bindestriche. Ein eigener Token ist leichter zu erraten: Verwende dafür möglichst Passwortschutz. Beim Ändern wird der alte Link ungültig.</span>
      </div>
      <div id="ppi-share-link-wrap" hidden><label for="ppi-share-link">Tour-Link</label>
        <div class="ppi-share-url-row"><input id="ppi-share-link" type="text" readonly aria-label="Öffentlicher Tour-Link" />
          <button type="button" id="ppi-share-copy">Link kopieren</button>
          <button type="button" id="ppi-share-open">Testlink öffnen</button><button type="button" id="ppi-share-rotate">Token erneuern</button></div>
      </div>
    </section>
    <section class="ppi-tour-share-box" aria-label="Zusätzliche Freigabelinks">
      <div class="ppi-share-heading"><strong>Weitere Freigabelinks</strong><span>Unabhängige Links pro Tour</span></div>
      <p>Erstelle zusätzliche Links mit eigener Bezeichnung, Ablaufdatum und eigenem Passwort. Bestehende Links bleiben unverändert.</p>
      <div class="ppi-extra-create">
        <label>Bezeichnung<input type="text" id="ppi-extra-name" maxlength="60" placeholder="z. B. Freunde, Familie oder Verein"></label>
        <label>Ablaufdatum (optional)<input type="date" id="ppi-extra-expiry"></label>
        <label>Passwort (optional)<input type="password" id="ppi-extra-password" maxlength="128" autocomplete="new-password" placeholder="Nach Nextcloud-Richtlinie"></label>
        <button type="button" id="ppi-extra-create">+ Weiteren Link erstellen</button>
      </div>
      <div id="ppi-extra-list" class="ppi-extra-list" aria-live="polite"></div>
    </section>
    </div><div id="ppi-step-edit">
    <div style="display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-top:22px">
      <h2>Panoramen und Verbindungen</h2>
      <button type="button" id="ppi-tour-add">+ Panorama hinzufügen</button>
    </div>
    <p class="ppi-graph-desktop-hint" style="color:var(--color-text-maxcontrast)">Die Linien zeigen deine Verbindungen. Für genaue Wechselpunkte öffne ein Panorama und platziere den Hotspot direkt in der 360°-Ansicht.</p>
    <p class="ppi-graph-mobile-hint">Tippe auf ein Panorama, um Wechselpunkte zu setzen. Vorhandene Verbindungen stehen direkt unter dem jeweiligen Bild.</p>
    <div id="ppi-tour-graph" style="border:1px solid var(--color-border);border-radius:12px;background:var(--color-background-dark);min-height:200px;overflow:auto"></div>
    <div class="ppi-tour-link-legacy" hidden>
      <select id="ppi-tour-from" aria-label="Ausgangspanorama"></select>
      <select id="ppi-tour-to" aria-label="Zielpanorama"></select>
      <button id="ppi-tour-link" type="button">Verbindung hinzufügen</button>
    </div>
    <p style="color:var(--color-text-maxcontrast);margin-top:22px">Wechselpunkte erscheinen im Tour-Editor und in freigegebenen Touren – niemals beim normalen Öffnen einzelner Bilder in Nextcloud Dateien oder Fotos.</p>
    </div>
  </section>
  <section class="ppi-tour-card" id="ppi-tour-picker" hidden tabindex="-1" aria-label="Panorama aus Dateien auswählen">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px"><h2>Panorama aus Dateien auswählen</h2><button id="ppi-picker-close" type="button">Schließen</button></div>
    <p id="ppi-picker-path" style="word-break:break-all;color:var(--color-text-maxcontrast)">/</p>
    <div class="ppi-picker-toolbar"><button id="ppi-picker-up" type="button">← Eine Ebene zurück</button><span id="ppi-picker-selection-count">0 ausgewählt</span><button id="ppi-picker-add-selected" class="primary" type="button" disabled>Panoramen hinzufügen</button></div>
    <div class="ppi-picker-section-title"><h3 class="ppi-picker-heading">Ordner</h3></div>
    <div id="ppi-picker-folders" class="ppi-picker-folder-grid"></div>
    <div class="ppi-picker-section-title"><h3 class="ppi-picker-heading">Panorama-Vorschaubilder</h3><span id="ppi-picker-results" aria-live="polite"></span></div>
    <div class="ppi-picker-filters" role="search" aria-label="Panorama-Suche und Filter">
      <div class="ppi-picker-filter-search ppi-picker-search-combined"><div class="ppi-picker-search-labels"><label for="ppi-picker-search">Suche</label><label for="ppi-picker-search-mode" id="ppi-picker-search-mode-label">Dateiname + Tags</label></div><div class="ppi-picker-search-control"><input id="ppi-picker-search" type="search" autocomplete="off" placeholder="Suchbegriff eingeben …" aria-label="Suchbegriff"><div class="ppi-picker-search-mode-wrap"><select id="ppi-picker-search-mode" aria-label="Suchbereich wählen" title="Suchbereich wählen"><option value="both">Dateiname + Tags</option><option value="name">Dateiname</option><option value="tags">Tags</option></select><span class="ppi-picker-search-chevron" aria-hidden="true">⌄</span></div></div></div>
      <label>Sortieren<select id="ppi-picker-sort"><option value="name_asc">Name A–Z</option><option value="name_desc">Name Z–A</option><option value="newest">Neueste zuerst</option><option value="oldest">Älteste zuerst</option><option value="size_desc">Größte zuerst</option></select></label>
      <label>Tourstatus<select id="ppi-picker-status"><option value="unused" selected>Noch nicht in dieser Tour</option><option value="all">Alle Panoramen</option><option value="used">Bereits in dieser Tour</option></select></label>
      <label>Geändert<select id="ppi-picker-date"><option value="any">Beliebiges Datum</option><option value="30d">Letzte 30 Tage</option><option value="year">Dieses Jahr</option></select></label>
      <label>Dateigröße<select id="ppi-picker-size"><option value="any">Beliebige Größe</option><option value="lt10">Unter 10 MB</option><option value="10to50">10–50 MB</option><option value="gt50">Über 50 MB</option></select></label>
      <div class="ppi-picker-filter-actions">
        <div class="ppi-picker-switches" role="group" aria-label="Ordner- und Panorama-Anzeige">
          <label class="ppi-picker-inline"><input type="checkbox" id="ppi-picker-recursive"> Unterordner einbeziehen</label>
          <label class="ppi-picker-inline" title="Die 360°-Filtereinstellung wird dauerhaft in deinem Nextcloud-Konto gespeichert"><input id="ppi-picker-panorama-only" type="checkbox" <?php if ($_['pickerPanoramaOnly']) { print_unescaped('checked'); } ?>> Nur erkannte 360°-Panoramen anzeigen</label>
        </div>
        <button id="ppi-picker-reset-filters" type="button">Filter zurücksetzen</button>
      </div>
    </div>
    <p id="ppi-picker-limit-warning" role="status" hidden></p>
    <div id="ppi-picker-images" class="ppi-picker-image-grid"></div>
    <p style="color:var(--color-text-maxcontrast);margin-top:12px">Mehrere JPEG-Dateien markieren und mit „Panoramen hinzufügen“ übernehmen. Die Bilder müssen für die spätere 360°-Darstellung geeignete Panorama-Metadaten besitzen. Dateivorschauen werden von Nextcloud geladen.</p>
  </section>
  <div id="ppi-tour-preview" class="ppi-tour-preview" role="dialog" aria-modal="true" aria-label="360°-Tour-Panorama" hidden>
    <div class="ppi-preview-window" tabindex="-1">
      <div class="ppi-preview-header"><strong id="ppi-preview-title">Panorama</strong><button type="button" id="ppi-preview-close">Schließen ×</button></div>
      <div class="ppi-preview-scroll" tabindex="0" aria-label="Panorama und Verbindungseinstellungen – scrollbar">
      <div class="ppi-preview-body">
        <div id="ppi-preview-sphere" class="ppi-preview-sphere" hidden aria-label="Interaktives 360°-Panorama"></div>
        <img id="ppi-preview-image" alt="Panorama-Vorschau" />
        <p id="ppi-preview-status" role="status">Panorama-Vorschau</p>
        <details class="ppi-preview-help"><summary>Bedienung und Hinweise</summary><p>Mit Maus oder Finger drehen, mit Mausrad oder zwei Fingern zoomen. Zum Verknüpfen „Wechselpunkt im Bild setzen“ auswählen, eine Stelle im Panorama anklicken und anschließend unten ein Zielpanorama wählen. Vorhandene Pfeile öffnen das verknüpfte Panorama. Einzelbilder in Nextcloud Dateien bleiben ohne Wechselpunkte.</p></details>
      </div>
      <div class="ppi-preview-action-footer">
        <div id="ppi-preview-link-controls" hidden class="ppi-preview-link-controls">
          <button type="button" id="ppi-preview-place" class="primary">+ Wechselpunkt im Bild setzen</button>
          <div class="ppi-preview-target-heading">Zielpanoramen dieser Tour</div>
          <div id="ppi-preview-target-gallery" class="ppi-preview-target-gallery" role="group" aria-label="Zielpanorama auswählen"></div>
          <label for="ppi-preview-link-target" class="ppi-preview-target-fallback">Zielpanorama</label>
          <select id="ppi-preview-link-target" class="ppi-preview-target-fallback" aria-label="Zielpanorama als Liste"></select>
          <div id="ppi-preview-placement" hidden>
            <span id="ppi-preview-coordinates">Position im Panorama wählen</span>
            <button type="button" class="primary" id="ppi-preview-link-add">Ausgewählte Verbindung speichern</button>
            <button type="button" id="ppi-preview-place-cancel">Abbrechen</button>
          </div>
        </div>
      </div>
      </div><!-- /.ppi-preview-scroll -->
    </div>
  </div>
</div>
<style>
/* 0.4.62.60: Mobile tour editor - long filenames must never widen the
   preview header. This also prevents horizontal scroll when Close gets focus. */
@media (max-width: 620px) {
  #ppi-tour-preview .ppi-preview-window,
  #ppi-tour-preview .ppi-preview-scroll,
  #ppi-tour-preview .ppi-preview-body,
  #ppi-tour-preview .ppi-preview-header { min-width:0!important; max-width:100%!important; box-sizing:border-box!important; }
  #ppi-tour-preview .ppi-preview-header { display:flex!important; flex-wrap:nowrap!important; overflow:hidden!important; width:100%!important; }
  #ppi-tour-preview #ppi-preview-title { display:block!important; flex:1 1 0!important; min-width:0!important; max-width:100%!important; overflow:hidden!important; text-overflow:ellipsis!important; white-space:nowrap!important; overflow-wrap:normal!important; }
  #ppi-tour-preview #ppi-preview-close { flex:0 0 auto!important; min-width:0!important; max-width:40%!important; white-space:nowrap!important; }
}

/* 0.4.46: Accessible theme-aware header and clearer editable tour graph. */
#ppi-tours-app #ppi-tour-graph{min-height:250px;overflow:auto;padding:8px;}
#ppi-tours-app .ppi-tour-board{position:relative;isolation:isolate;min-width:0;}
#ppi-tours-app .ppi-tour-arrows{position:absolute;left:0;top:0;pointer-events:none;overflow:visible;}
#ppi-tours-app .ppi-tour-graph-card{position:absolute;transform:translate(-50%,-50%);width:205px;box-sizing:border-box;padding:8px;border:1px solid var(--color-border);border-radius:12px;background:var(--color-main-background);box-shadow:0 3px 15px #0003;z-index:2;display:flex;flex-direction:column;gap:7px;transition:box-shadow .15s ease;}
#ppi-tours-app .ppi-tour-graph-card:hover{box-shadow:0 5px 19px #0004}
#ppi-tours-app .ppi-tour-graph-thumb{appearance:none;display:flex;align-items:center;justify-content:center;position:relative;width:100%;height:108px;min-height:108px;padding:0!important;border:0!important;border-radius:7px;overflow:hidden;cursor:pointer;background:var(--color-background-dark);transition:filter .15s ease}
#ppi-tours-app .ppi-tour-graph-thumb:hover{filter:brightness(1.08)}
#ppi-tours-app .ppi-tour-graph-thumb:focus-visible{outline:3px solid var(--color-primary-element);outline-offset:2px}
#ppi-tours-app .ppi-tour-graph-thumb img{width:100%;height:100%;object-fit:cover}
#ppi-tours-app .ppi-tour-graph-name{font-size:12px;font-weight:650;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-height:18px}
#ppi-tours-app .ppi-tour-card-delete{position:absolute;top:13px;right:13px;z-index:4;display:grid;place-items:center;appearance:none;padding:0!important;width:30px;min-width:30px;height:30px;min-height:30px;border-radius:50%!important;border:1px solid rgba(150,30,30,.4)!important;background:var(--color-main-background)!important;color:#b92d2d!important;font-size:22px!important;line-height:1!important;box-shadow:0 2px 8px #0005;opacity:0;pointer-events:none;transition:opacity .14s ease}
#ppi-tours-app .ppi-tour-graph-card:hover .ppi-tour-card-delete,#ppi-tours-app .ppi-tour-graph-card:focus-within .ppi-tour-card-delete{opacity:1;pointer-events:auto}
#ppi-tours-app .ppi-tour-card-delete:hover{background:#bd3434!important;color:#fff!important}
@media (hover:none){#ppi-tours-app .ppi-tour-card-delete{opacity:1;pointer-events:auto}}
@media (prefers-reduced-motion:reduce){#ppi-tours-app .ppi-tour-graph-card,#ppi-tours-app .ppi-tour-graph-thumb,#ppi-tours-app .ppi-tour-card-delete{transition:none}}
#ppi-tours-app .ppi-tour-edge-delete{position:absolute;transform:translate(-50%,-50%);z-index:10;border-radius:50%!important;padding:0!important;width:25px;height:25px;min-height:25px;background:var(--color-main-background)!important;color:#bd3434!important;border:1px solid #bd3434!important;line-height:20px;font-size:18px!important;box-shadow:0 2px 6px #0005}
#ppi-tours-app .ppi-tour-edge-delete:hover{background:#bd3434!important;color:#fff!important}

/* Private start page management: same visual language as the existing tour cards. */
#ppi-tours-app .ppi-startpage-switch{display:flex;gap:8px;margin:0 0 14px;flex-wrap:wrap}
#ppi-tours-app .ppi-startpage-switch button{flex:1;min-width:150px;border:1px solid var(--color-border);border-radius:10px;padding:12px!important;background:var(--color-main-background);color:var(--color-main-text)}
#ppi-tours-app .ppi-startpage-switch button[aria-pressed="true"]{border:2px solid var(--color-primary-element);background:var(--color-primary-element-light);font-weight:700}
#ppi-tours-app .ppi-start-page-toolbar{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
#ppi-tours-app .ppi-start-page-fields{display:grid;gap:15px;max-width:760px;margin-top:15px}
#ppi-tours-app .ppi-start-page-fields label{display:grid;gap:6px;font-weight:600}
#ppi-tours-app .ppi-start-page-fields input,#ppi-tours-app .ppi-start-page-fields select,#ppi-tours-app .ppi-start-page-fields textarea{width:100%;box-sizing:border-box}
#ppi-tours-app .ppi-startpage-tour-option{display:flex;align-items:center;gap:10px;padding:8px;border-bottom:1px solid var(--color-border)}
#ppi-tours-app .ppi-startpage-tour-option input{width:auto}
#ppi-tours-app .ppi-startpage-private-note{padding:12px;border:1px solid var(--color-border);border-radius:10px;background:var(--color-background-hover);margin-top:15px}
/* 0.4.62.103: Accessible four-step editor with immediate private tour tile preview. */
#ppi-tours-app .ppi-startpage-editor-tabs{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:7px;margin:15px 0;padding:0}
#ppi-tours-app .ppi-startpage-editor-tabs button{white-space:normal;min-height:46px;border:1px solid var(--color-border);border-radius:9px;background:var(--color-main-background);color:var(--color-main-text);font-weight:600;padding:8px!important}
#ppi-tours-app .ppi-startpage-editor-tabs button[aria-current="step"]{border:2px solid var(--color-primary-element);background:var(--color-primary-element-light);font-weight:750}
#ppi-tours-app .ppi-startpage-editor-tabs button:focus-visible{outline:3px solid var(--color-primary-element);outline-offset:2px}
#ppi-tours-app .ppi-startpage-workspace{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.2fr);gap:18px;align-items:start}
#ppi-tours-app .ppi-start-page-fields{display:block;min-width:0;margin-top:0}
#ppi-tours-app .ppi-startpage-editor-panel{display:grid;gap:14px;min-width:0}
#ppi-tours-app .ppi-startpage-editor-panel[hidden]{display:none!important}
#ppi-tours-app .ppi-startpage-editor-panel h3{margin:0}
#ppi-tours-app .ppi-startpage-editor-panel label{display:grid;gap:7px}
#ppi-tours-app .ppi-startpage-editor-panel input[type="color"]{height:44px;width:85px;padding:3px;cursor:pointer}
#ppi-tours-app .ppi-startpage-preview-column{min-width:0;position:sticky;top:12px}
#ppi-tours-app .ppi-startpage-preview-heading{display:flex;align-items:center;justify-content:space-between;gap:12px}
#ppi-tours-app .ppi-startpage-preview-heading h3{margin:0 0 10px}
#ppi-tours-app #ppi-startpage-dirty{font-size:12px;color:var(--color-text-maxcontrast)}
#ppi-tours-app .ppi-startpage-live-preview{--ppi-startpage-bg:#1d3849;--ppi-startpage-heading:#ffffff;box-sizing:border-box;min-height:330px;overflow:hidden;border-radius:13px;padding:20px 14px 25px;background:var(--ppi-startpage-bg);color:#f4f7fb;border:1px solid rgba(255,255,255,.22);box-shadow:0 6px 21px #0003}
#ppi-tours-app .ppi-startpage-preview-header{text-align:center;padding:22px 6px}
#ppi-tours-app .ppi-startpage-preview-header h2{color:var(--ppi-startpage-heading)!important;font-size:clamp(18px,2vw,29px);overflow-wrap:anywhere;margin:0 0 10px}
#ppi-tours-app .ppi-startpage-preview-header p{color:#f4f7fb!important;font-size:13px;white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.5}
#ppi-tours-app .ppi-startpage-preview-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
#ppi-tours-app .ppi-startpage-preview-tile{min-width:0;overflow:hidden;border-radius:9px;background:#ffffff;color:#1d2732;box-shadow:0 5px 14px #0004}
#ppi-tours-app .ppi-startpage-preview-tile img{display:block;width:100%;aspect-ratio:16/9;object-fit:cover;background:#213848}
#ppi-tours-app .ppi-startpage-preview-tile strong{display:block;font-size:12px;padding:9px;overflow-wrap:anywhere}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"]{padding:0 0 18px}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"] .ppi-startpage-preview-header{padding:35px 12px;background:#0003}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"] .ppi-startpage-preview-grid{margin:15px 14px 0}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="gallery"]{background:var(--color-main-background);color:var(--color-main-text)}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="gallery"] .ppi-startpage-preview-header h2{color:var(--color-main-text)!important}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="gallery"] .ppi-startpage-preview-header p{color:var(--color-main-text)!important}
#ppi-tours-app .ppi-startpage-preview-empty{grid-column:1/-1;color:#ffffff!important;text-align:center;font-size:13px}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="gallery"] .ppi-startpage-preview-empty{color:var(--color-main-text)!important}
#ppi-tours-app .ppi-startpage-preview-note{font-size:12px;color:var(--color-text-maxcontrast);line-height:1.5;margin:10px 0}
@media(max-width:900px){#ppi-tours-app .ppi-startpage-workspace{grid-template-columns:1fr}#ppi-tours-app .ppi-startpage-preview-column{position:static}}
@media(max-width:600px){#ppi-tours-app .ppi-startpage-editor-tabs{grid-template-columns:repeat(2,minmax(0,1fr))}#ppi-tours-app .ppi-startpage-live-preview{min-height:270px}}

/* Start page 0.4.62.104: background selection and responsive card grids. */
#ppi-tours-app .ppi-startpage-tour-option{display:flex!important;flex-direction:row!important;align-items:center!important;gap:12px!important;cursor:pointer;border:1px solid var(--color-border);border-radius:10px;padding:9px!important;margin:0 0 9px;min-width:0}
#ppi-tours-app .ppi-startpage-tour-option img{width:125px;height:72px;max-width:32%;object-fit:cover;border-radius:7px;flex:0 0 auto;background:var(--color-background-dark)}
#ppi-tours-app .ppi-startpage-tour-info{flex:1;min-width:0;display:flex;flex-direction:column;gap:3px}
#ppi-tours-app .ppi-startpage-tour-info strong{font-size:14px;overflow-wrap:anywhere}
#ppi-tours-app .ppi-startpage-tour-info small{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.4;color:var(--color-text-maxcontrast);font-size:12px;font-weight:400}
#ppi-tours-app .ppi-startpage-tour-option input[type=checkbox]{width:20px!important;height:20px!important;flex:0 0 20px}
#ppi-tours-app .ppi-startpage-bg-toolbar{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
#ppi-tours-app .ppi-startpage-bg-toolbar button{min-height:36px}
#ppi-tours-app #ppi-start-page-bg-browser{max-height:300px;overflow:auto;border:1px solid var(--color-border);padding:8px;border-radius:9px}
#ppi-tours-app #ppi-start-page-bg-browser[hidden]{display:none!important}
#ppi-tours-app .ppi-startpage-bg-files{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}
#ppi-tours-app .ppi-startpage-bg-files button{display:flex;flex-direction:column;gap:5px;min-width:0;padding:7px;border:1px solid var(--color-border);border-radius:8px;overflow-wrap:anywhere;background:var(--color-main-background);font-size:12px}
#ppi-tours-app .ppi-startpage-bg-files img{width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:5px}
#ppi-tours-app .ppi-startpage-live-preview{background-image:linear-gradient(#0006,#0006),var(--ppi-startpage-image,none);background-size:cover;background-position:center}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="auto"]{grid-template-columns:repeat(2,minmax(0,1fr))}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="auto"] .ppi-startpage-preview-tile:only-child{grid-column:1/-1}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="auto"]:has(> .ppi-startpage-preview-tile:nth-child(3):last-child) .ppi-startpage-preview-tile:first-child{grid-column:1/-1}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="2"]{grid-template-columns:repeat(2,minmax(0,1fr))}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="3"]{grid-template-columns:repeat(3,minmax(0,1fr))}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="4"]{grid-template-columns:repeat(4,minmax(0,1fr))}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="5"]{grid-template-columns:repeat(5,minmax(0,1fr))}
#ppi-tours-app .ppi-startpage-preview-tile small{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;padding:0 9px 9px;font-size:11px;line-height:1.4;color:#52606e}
@media(max-width:600px){#ppi-tours-app .ppi-startpage-preview-grid[data-cols]{grid-template-columns:repeat(2,minmax(0,1fr))}#ppi-tours-app .ppi-startpage-tour-option img{width:90px;height:60px}}
@media(max-width:420px){#ppi-tours-app .ppi-startpage-preview-grid[data-cols]{grid-template-columns:1fr}#ppi-tours-app .ppi-startpage-preview-grid .ppi-startpage-preview-tile{grid-column:auto!important}}


/* 0.4.62.105: fast illustrated layout choice, preview cards and true split header */
#ppi-tours-app .ppi-startpage-layout-presets{min-width:0;border:1px solid var(--color-border);border-radius:10px;padding:11px}
#ppi-tours-app .ppi-startpage-layout-presets-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(102px,1fr));gap:8px}
#ppi-tours-app .ppi-startpage-layout-preset{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;min-height:88px;padding:9px 5px!important;border:2px solid var(--color-border)!important;background:var(--color-main-background)!important;color:var(--color-main-text)!important;border-radius:10px;cursor:pointer}
#ppi-tours-app .ppi-startpage-layout-preset[aria-pressed="true"]{border-color:var(--color-primary-element)!important;background:var(--color-primary-element-light)!important;box-shadow:0 0 0 1px var(--color-primary-element)}
#ppi-tours-app .ppi-startpage-layout-preset:focus-visible{outline:3px solid var(--color-primary-element);outline-offset:2px}
#ppi-tours-app .ppi-startpage-layout-preset>span:last-child{font-size:12px;font-weight:650}
#ppi-tours-app .ppi-layout-mini{display:grid;gap:3px;width:66px;height:36px;grid-template-columns:repeat(2,1fr);grid-auto-rows:1fr}
#ppi-tours-app .ppi-layout-mini i{min-width:0;min-height:0;border-radius:2px;background:var(--color-primary-element,#1768a1);opacity:.8}
#ppi-tours-app .ppi-layout-mini-auto i:first-child{grid-column:1/-1}
#ppi-tours-app .ppi-layout-mini-3{grid-template-columns:repeat(3,minmax(0,1fr))}
#ppi-tours-app .ppi-layout-mini-4{grid-template-columns:repeat(4,minmax(0,1fr))}
#ppi-tours-app .ppi-layout-mini-5{grid-template-columns:repeat(5,minmax(0,1fr))}
#ppi-tours-app .ppi-startpage-preview-header p{color:var(--ppi-startpage-description,#ffffff)!important}
#ppi-tours-app .ppi-startpage-preview-tile{border:3px solid #fff!important;border-radius:11px!important;box-shadow:0 7px 22px rgba(0,0,0,.65)!important;background:#fff!important;color:#1d2732!important}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-copy{padding:9px;background:#fff;color:#1d2732}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-copy strong{padding:0 0 5px!important;font-size:13px}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-copy small{padding:0!important;color:#4c5664}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"]{background-image:none!important;background-color:var(--color-main-background)!important}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"] .ppi-startpage-preview-header{background-image:linear-gradient(#0006,#0006),var(--ppi-startpage-image,none);background-color:var(--ppi-startpage-bg);background-size:cover;background-position:center;min-height:140px}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"] .ppi-startpage-preview-grid{margin:15px 14px 0}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="gallery"] .ppi-startpage-preview-header p{color:var(--color-main-text)!important}
#ppi-tours-app #ppi-start-pages-list.ppi-startpages-single{display:grid!important;grid-template-columns:1fr!important}
#ppi-tours-app #ppi-start-pages-list.ppi-startpages-single>.ppi-overview-tile{grid-column:1/-1;width:100%;max-width:none}
#ppi-tours-app #ppi-start-pages-list.ppi-startpages-double{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:16px}
#ppi-tours-app #ppi-start-pages-list.ppi-startpages-multi{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:16px}
#ppi-tours-app #ppi-start-pages-list.ppi-startpages-multi>.ppi-overview-tile:first-child:nth-last-child(3){grid-column:1/-1}
#ppi-tours-app .ppi-startpage-overview-cover{display:block!important;position:relative;min-height:150px;background:#172331 linear-gradient(140deg,#12161e,#34465b);background-position:center;background-size:cover;border-radius:8px 8px 0 0}
#ppi-tours-app .ppi-startpage-overview-cover .ppi-overview-placeholder{background:transparent!important}
@media(min-width:1300px){#ppi-tours-app #ppi-start-pages-list.ppi-startpages-multi:not(:has(>.ppi-overview-tile:nth-child(3):last-child)){grid-template-columns:repeat(3,minmax(0,1fr))!important}}
@media(max-width:620px){#ppi-tours-app #ppi-start-pages-list.ppi-startpages-double,#ppi-tours-app #ppi-start-pages-list.ppi-startpages-multi{grid-template-columns:1fr!important}#ppi-tours-app #ppi-start-pages-list .ppi-overview-tile{grid-column:1/-1!important}}

/* Contrast remains correct over custom Nextcloud background images, in light and dark themes. */
#ppi-tours-app .ppi-tour-intro{padding:18px 20px 14px;border:1px solid var(--color-border);border-radius:14px;background:var(--color-main-background);color:var(--color-main-text);margin-bottom:14px;box-shadow:0 2px 12px rgba(0,0,0,.10)}
#ppi-tours-app .ppi-tour-intro h1{color:var(--color-main-text);line-height:1.25}
#ppi-tours-app .ppi-tour-intro p:not(#ppi-tour-message){color:var(--color-text-maxcontrast)}
#ppi-tours-app #ppi-tour-message{color:var(--color-main-text);background:var(--color-background-hover);border:1px solid var(--color-border);padding:11px 14px;border-radius:9px;margin:12px 0 0;overflow-wrap:anywhere;font-weight:600}
#ppi-tours-app #ppi-tour-message.ppi-notice-success{border-left:4px solid var(--color-success, #16804a)}
#ppi-tours-app #ppi-tour-message.ppi-notice-error{border-left:4px solid var(--color-error, #ba3131)}
/* A light, soft elevation under both connection strokes and their arrowheads. */
#ppi-tours-app .ppi-tour-arrows .ppi-tour-edge-visible{filter:drop-shadow(0 2px 2px rgba(0,0,0,.34));transition:stroke-width .12s ease,stroke .12s ease}
#ppi-tours-app .ppi-tour-arrows marker path{filter:drop-shadow(0 1px 1px rgba(0,0,0,.28))}
#ppi-tours-app .ppi-tour-arrows .ppi-tour-edge-visible.ppi-edge-active{stroke:var(--color-primary-element, #1467a0);stroke-width:5}
#ppi-tours-app .ppi-tour-edge-delete{width:32px;height:32px;min-height:32px;font-weight:700;opacity:0;pointer-events:none;transition:opacity .13s ease;}
#ppi-tours-app .ppi-tour-edge-delete.ppi-edge-active,#ppi-tours-app .ppi-tour-edge-delete:focus-visible{opacity:1;pointer-events:auto}
@media (hover:none){#ppi-tours-app .ppi-tour-edge-delete{opacity:1;pointer-events:auto}}
@media (prefers-reduced-motion:reduce){#ppi-tours-app .ppi-tour-edge-delete,#ppi-tours-app .ppi-tour-arrows .ppi-tour-edge-visible{transition:none}}
 #ppi-tours-app [hidden]{display:none!important}
 #ppi-tours-app #ppi-tour-picker{scroll-margin-top:85px;border:2px solid var(--color-primary-element);box-shadow:0 6px 22px rgba(0,0,0,.15);max-height:calc(100dvh - 110px);overflow-y:auto;overscroll-behavior:contain;scrollbar-gutter:stable;}
 #ppi-tours-app #ppi-tour-picker .ppi-picker-toolbar{position:sticky;top:-1px;z-index:3;background:var(--color-main-background);padding:8px 0;border-bottom:1px solid var(--color-border)}
 #ppi-tours-app .ppi-tour-share-box{border:1px solid var(--color-border);border-radius:12px;padding:14px;margin:16px 0;background:var(--color-background-hover)}
 #ppi-tours-app .ppi-share-heading,.ppi-share-actions,.ppi-share-url-row{display:flex;align-items:center;flex-wrap:wrap;gap:10px}
 #ppi-tours-app .ppi-share-heading{justify-content:space-between}
 #ppi-tours-app .ppi-tour-share-box p{margin:10px 0;font-size:13px}
 #ppi-tours-app #ppi-share-link-wrap{margin-top:12px}
 #ppi-tours-app #ppi-share-link{flex:1 1 300px;min-width:0;max-width:100%}
 #ppi-tours-app .ppi-share-expiry-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:12px 0}
 #ppi-tours-app .ppi-share-password-row{display:flex;flex-direction:column;gap:9px;margin:18px 0 12px}
 #ppi-tours-app .ppi-share-password-controls{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
 #ppi-tours-app #ppi-share-password{min-width:240px;flex:1 1 260px;max-width:400px}
 #ppi-tours-app .ppi-share-custom-row{display:flex;flex-direction:column;gap:8px;margin:15px 0}
 #ppi-tours-app .ppi-share-custom-controls{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
 #ppi-tours-app #ppi-share-custom-token{flex:1 1 230px;min-width:0;max-width:390px}
 #ppi-tours-app .ppi-share-expiry-row input{max-width:205px}
 #ppi-tours-app .ppi-share-expiry-hint{font-size:12px;color:var(--color-text-maxcontrast)}
 #ppi-tours-app .ppi-extra-create{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;align-items:end;margin:14px 0}
 #ppi-tours-app .ppi-extra-create label{display:flex;flex-direction:column;gap:5px;font-size:13px;min-width:0}
 #ppi-tours-app .ppi-extra-create input{width:100%;min-width:0;box-sizing:border-box}
 #ppi-tours-app .ppi-extra-create button{align-self:end}
 #ppi-tours-app .ppi-extra-list{display:grid;gap:12px;margin-top:12px}
 #ppi-tours-app .ppi-extra-entry{border:1px solid var(--color-border);border-radius:10px;padding:12px;min-width:0;background:var(--color-main-background)}
 #ppi-tours-app .ppi-extra-fields{display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin:9px 0}
 #ppi-tours-app .ppi-extra-fields label{display:flex;flex-direction:column;gap:5px;min-width:130px;flex:1 1 160px}
 #ppi-tours-app .ppi-extra-fields input{width:100%;min-width:0;box-sizing:border-box}
 #ppi-tours-app .ppi-extra-link{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
 #ppi-tours-app .ppi-extra-link input{min-width:0;flex:1 1 250px}
 #ppi-tours-app .ppi-extra-link button{flex:0 0 auto}
 #ppi-tours-app .ppi-extra-entry button{min-height:36px}

 #ppi-tours-app .ppi-tour-card{padding:20px;border:1px solid var(--color-border);border-radius:14px;background:var(--color-main-background);margin:0 0 18px}
 #ppi-tours-app h2{font-size:18px;font-weight:650;margin:0}
 #ppi-tours-app button{cursor:pointer}
 #ppi-tours-app .ppi-tour-tile{border:1px solid var(--color-border);border-radius:12px;padding:15px;text-align:left;background:var(--color-background-hover);cursor:pointer;min-height:88px}
 #ppi-tours-app .ppi-tour-tile:hover{border-color:var(--color-primary-element)}
 #ppi-tours-app .ppi-tour-image-row{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px;border:1px solid var(--color-border);border-radius:10px}
 #ppi-tours-app .ppi-picker-item{padding:10px 12px;border:1px solid var(--color-border);border-radius:8px;display:flex;gap:10px;align-items:center;text-align:left;background:transparent;cursor:pointer}
 #ppi-tours-app .ppi-picker-item:hover{background:var(--color-background-hover)}
 #ppi-tours-app #ppi-tour-graph svg{display:block;max-width:none;min-width:0}

 #ppi-tours-app .ppi-picker-toolbar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin:12px 0}
 #ppi-tours-app #ppi-picker-selection-count{margin-left:auto;font-weight:600}
 #ppi-tours-app .ppi-picker-heading{font-size:15px;margin:8px 0 6px}
 #ppi-tours-app #ppi-tour-picker .ppi-picker-toolbar{margin:8px 0 4px}
 #ppi-tours-app #ppi-tour-picker .ppi-picker-section-title:first-of-type .ppi-picker-heading{margin-top:4px}
 #ppi-tours-app .ppi-picker-section-title{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
 #ppi-tours-app .ppi-picker-inline{display:flex;align-items:center;gap:6px;font-size:13px}
 #ppi-tours-app .ppi-picker-filter-actions{display:flex;align-items:center;justify-content:space-between;gap:8px 20px;flex:1 0 100%;min-width:0;flex-wrap:wrap;margin-top:2px}
 #ppi-tours-app .ppi-picker-filter-actions .ppi-picker-switches{display:flex;align-items:center;justify-content:flex-start;flex-wrap:wrap;gap:8px 20px;margin:0;padding:0;background:transparent}
 #ppi-tours-app .ppi-picker-filter-actions .ppi-picker-switches .ppi-picker-inline{display:inline-flex;align-items:center;flex:0 0 auto;width:auto;min-width:0;gap:6px;font-size:13px;line-height:1.3;white-space:nowrap;cursor:pointer}
 #ppi-tours-app .ppi-picker-filter-actions .ppi-picker-inline input[type="checkbox"]{display:inline-block;flex:0 0 16px;width:16px!important;min-width:16px!important;max-width:16px!important;height:16px!important;min-height:16px!important;max-height:16px!important;margin:0!important;padding:0!important;box-sizing:border-box;vertical-align:middle}
 #ppi-tours-app .ppi-picker-filters{display:flex;align-items:end;flex-wrap:wrap;gap:8px;margin:4px 0 14px}
 #ppi-tours-app .ppi-picker-filters>label{display:flex;flex:1 1 135px;min-width:120px;flex-direction:column;gap:4px;font-size:12px;line-height:16px}
 #ppi-tours-app .ppi-picker-filters .ppi-picker-filter-search{flex:2 1 300px;min-width:220px;width:auto}
 #ppi-tours-app .ppi-picker-search-combined{display:flex;flex-direction:column;gap:4px;margin-bottom:4px;align-self:flex-end}
 #ppi-tours-app .ppi-picker-search-labels{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;column-gap:8px;min-height:16px;height:16px;box-sizing:border-box}
 #ppi-tours-app .ppi-picker-filters .ppi-picker-search-labels>label{display:block;flex:none;min-width:0;width:auto;margin:0;padding:0;font-size:12px;line-height:16px;white-space:nowrap}
 #ppi-tours-app #ppi-picker-search-mode-label{font-size:11px;color:var(--color-text-maxcontrast);max-width:165px;overflow:hidden;text-overflow:ellipsis;text-align:right;cursor:pointer}
 #ppi-tours-app .ppi-picker-search-control{display:flex;align-items:stretch;min-width:0;width:100%;height:38px;min-height:38px;max-height:38px;box-sizing:border-box;border:1px solid color-mix(in srgb, var(--color-main-text) 55%, var(--color-main-background));border-radius:7px;overflow:hidden;background:var(--color-main-background)}
 #ppi-tours-app .ppi-picker-filters .ppi-picker-search-control input{flex:1 1 auto;min-width:0;width:0;height:36px!important;min-height:36px!important;max-height:36px!important;box-sizing:border-box;margin:0!important;border:0!important;border-radius:0!important;background:transparent;box-shadow:none!important;font-size:14px;line-height:36px;padding:0 10px!important;appearance:none}
 #ppi-tours-app .ppi-picker-search-control input:focus{outline:0;box-shadow:none}
 #ppi-tours-app .ppi-picker-search-control:focus-within{outline:2px solid var(--color-primary-element);outline-offset:1px}
 #ppi-tours-app .ppi-picker-search-control:hover{border-color:color-mix(in srgb, var(--color-main-text) 75%, var(--color-main-background))}
 #ppi-tours-app .ppi-picker-search-mode-wrap{position:relative;flex:0 0 36px;width:36px;border-left:1px solid color-mix(in srgb, var(--color-main-text) 28%, var(--color-main-background));background:var(--color-main-background)}
 #ppi-tours-app .ppi-picker-filters .ppi-picker-search-mode-wrap select{position:absolute;inset:0;width:100%;height:100%;min-height:0;opacity:0;cursor:pointer;z-index:2}
 #ppi-tours-app .ppi-picker-search-chevron{display:flex;align-items:center;justify-content:center;position:absolute;inset:0;pointer-events:none;font-size:0;color:var(--color-main-text)}
 #ppi-tours-app .ppi-picker-search-chevron::after{content:'';display:block;width:0;height:0;border-left:4px solid transparent;border-right:4px solid transparent;border-top:5px solid currentColor}
 #ppi-tours-app .ppi-picker-search-mode-wrap:focus-within{background:var(--color-background-hover)}
 #ppi-tours-app .ppi-picker-filters input:not([type="checkbox"]),#ppi-tours-app .ppi-picker-filters select{min-width:0;width:100%;min-height:38px;box-sizing:border-box}
 #ppi-tours-app #ppi-picker-reset-filters{min-height:38px;margin-left:auto}
 #ppi-tours-app #ppi-picker-results{font-size:14px;font-weight:600;line-height:1.4;color:var(--color-text-maxcontrast)}
 #ppi-tours-app #ppi-picker-limit-warning{padding:8px;background:var(--color-background-hover);border-radius:6px;font-size:13px}
 @media(max-width:620px){#ppi-tours-app .ppi-picker-filters>label{flex:1 1 45%}#ppi-tours-app .ppi-picker-filters .ppi-picker-filter-search{flex-basis:100%;min-width:0}#ppi-tours-app .ppi-picker-search-control input{font-size:14px;padding:0 8px}}
 @media(max-width:620px){#ppi-tours-app .ppi-picker-search-combined{margin-bottom:0}#ppi-tours-app .ppi-picker-filter-actions .ppi-picker-switches{gap:8px 12px}#ppi-tours-app .ppi-picker-filter-actions .ppi-picker-switches .ppi-picker-inline{white-space:normal}}
 #ppi-tours-app .ppi-picker-folder-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:8px}
 #ppi-tours-app .ppi-folder-tile{border:1px solid var(--color-border);background:var(--color-background-hover);border-radius:10px;padding:12px;text-align:left;display:flex;align-items:center;gap:10px;min-height:55px;overflow-wrap:anywhere}
 #ppi-tours-app .ppi-folder-icon{font-size:23px}
 #ppi-tours-app .ppi-picker-image-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px}
 #ppi-tours-app .ppi-pano-card{display:flex;flex-direction:column;position:relative;gap:8px;border:2px solid var(--color-border);border-radius:11px;padding:8px;cursor:pointer;background:var(--color-main-background);min-width:0}
 #ppi-tours-app .ppi-pano-card.ppi-selected{border-color:var(--color-primary-element);background:var(--color-background-hover)}
 #ppi-tours-app .ppi-pano-card input{position:absolute;right:15px;top:15px;z-index:2;accent-color:var(--color-primary-element)}
 #ppi-tours-app .ppi-picker-thumb{position:relative;height:100px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:var(--color-background-dark);border-radius:7px}
 #ppi-tours-app .ppi-pano-thumb{display:block;width:100%;height:100%;object-fit:contain}
 #ppi-tours-app .ppi-thumb-fallback{display:none;font-size:25px}
 #ppi-tours-app .ppi-no-thumb .ppi-thumb-fallback{display:block}
 #ppi-tours-app .ppi-picker-filename{font-size:12px;overflow-wrap:anywhere;line-height:1.4}
 #ppi-tours-app .ppi-picker-view{font-size:12px;min-height:34px;margin-top:auto}
 #ppi-tours-app .ppi-tour-image-row{display:grid;grid-template-columns:115px minmax(100px,1fr) auto;gap:13px;align-items:center}
 #ppi-tours-app .ppi-tour-thumb-wrap{width:115px;height:66px;overflow:hidden;border-radius:7px;background:var(--color-background-dark);display:flex;align-items:center;justify-content:center;cursor:pointer}
 #ppi-tours-app .ppi-image-name{overflow-wrap:anywhere;font-weight:600}
 #ppi-tours-app .ppi-image-controls{display:flex;gap:7px;flex-wrap:wrap}
 .ppi-tour-preview[hidden]{display:none!important}
  .ppi-tour-preview{position:fixed;inset:0;z-index:2147483646;background:rgba(0,0,0,.88);display:flex;align-items:center;justify-content:center;padding:8px;box-sizing:border-box;overflow:hidden!important}
  .ppi-preview-window{box-sizing:border-box;width:min(99vw,1800px);height:calc(100dvh - 16px);max-height:calc(100dvh - 16px);min-height:0;display:flex;flex-direction:column;overflow:hidden;border-radius:12px;background:var(--color-main-background);padding:12px;margin:0;box-shadow:0 14px 65px rgba(0,0,0,.5)}
  .ppi-preview-scroll{display:block;flex:1 1 auto;min-height:0;overflow-y:auto!important;overflow-x:hidden;overscroll-behavior:contain;scrollbar-gutter:stable;touch-action:pan-y;padding:0 5px 18px;}
  .ppi-preview-body{display:block;min-height:0;overflow:visible!important;padding:0 4px 12px;}
 .ppi-preview-header{position:sticky;top:0;z-index:3;background:var(--color-main-background);padding:4px 0 10px}
  .ppi-preview-sphere{width:100%;height:clamp(350px,69dvh,920px);position:relative;overflow:hidden;background:#080e18;border-radius:9px;touch-action:none}
 .ppi-preview-sphere canvas{width:100%;height:100%;display:block;touch-action:none}
 .ppi-hotspot{position:absolute;z-index:2;min-width:43px;min-height:43px;border-radius:50%;border:2px solid #fff!important;background:#1767a0!important;color:white!important;font-size:22px!important;box-shadow:0 3px 16px #000a;transform:translate(-50%,-50%);cursor:pointer}
 .ppi-hotspot:focus-visible{outline:3px solid orange;outline-offset:3px}
 .ppi-hotspot[data-pending="true"]{background:#b45d10!important}
 .ppi-preview-help{line-height:1.5}
 .ppi-preview-header{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px}
 .ppi-preview-window img{display:block;width:100%;max-height:72vh;object-fit:contain;background:#10151b}
/* The transparent guide image must not inherit the preview window image background. */
.ppi-preview-window img.ppi-hotspot-blue-figure{background:transparent!important;}
 .ppi-preview-window p{font-size:12px;color:var(--color-text-maxcontrast);margin-top:8px}
  .ppi-preview-action-footer{display:block;overflow:visible!important;border-top:1px solid var(--color-border);padding:14px 2px;margin-top:9px;background:var(--color-main-background)}
 .ppi-preview-link-controls{display:flex;gap:12px;align-items:center;flex-wrap:wrap;padding:10px 12px;border:1px solid var(--color-border);border-radius:10px;margin:0}
 #ppi-preview-placement{display:flex;align-items:center;gap:9px;flex-wrap:wrap}
 #ppi-preview-placement[hidden]{display:none!important}
 #ppi-preview-coordinates{font-size:12px}
 .ppi-preview-link-controls[hidden]{display:none!important}
 .ppi-preview-link-controls select{min-width:180px;max-width:100%;flex:1}
 @media(max-width:620px){.ppi-tour-preview{padding:2px}.ppi-preview-window{width:100%;height:calc(100dvh - 4px);max-height:calc(100dvh - 4px);padding:8px;border-radius:7px}.ppi-preview-sphere{height:clamp(240px,58dvh,620px)}.ppi-preview-action-footer{max-height:none}.ppi-preview-link-controls{gap:8px}.ppi-preview-link-controls button{min-height:38px}#ppi-tours-app .ppi-tour-image-row{grid-template-columns:95px 1fr}#ppi-tours-app .ppi-tour-thumb-wrap{width:95px;height:60px}#ppi-tours-app .ppi-image-controls{grid-column:1/-1}#ppi-tours-app .ppi-picker-image-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}

/* Hover preview floats above the scrolling destination gallery without clipping. */
.ppi-target-hover-preview{
  position:fixed!important;z-index:2147483647!important;
  pointer-events:none!important;box-sizing:border-box!important;
  padding:6px!important;border:2px solid #fff!important;
  border-radius:12px!important;background:#102033!important;
  box-shadow:0 12px 38px rgba(0,0,0,.68),0 0 0 1px rgba(255,255,255,.20)!important;
}
.ppi-target-hover-preview[hidden]{display:none!important}
.ppi-target-hover-preview img{
  display:block!important;width:100%!important;height:auto!important;
  aspect-ratio:16/9;max-height:none!important;object-fit:cover!important;
  border-radius:6px!important;background:#132434!important;
}

/* 0.4.43: Scrollbarer Tour-Dialog mit kompakter, stets erreichbarer Zielbildgalerie. */
.ppi-tour-preview{padding:8px!important;align-items:center!important;justify-content:center!important;overscroll-behavior:contain}
.ppi-preview-window{height:calc(100dvh - 16px)!important;max-height:calc(100dvh - 16px)!important;width:min(99vw,1800px)!important;min-height:0!important;display:flex!important;flex-direction:column!important;overflow:hidden!important;}
.ppi-preview-header{position:relative!important;flex:0 0 auto!important;z-index:4!important;}
.ppi-preview-scroll{flex:1 1 auto!important;min-height:0!important;max-height:100%!important;overflow-y:scroll!important;overflow-x:hidden!important;scrollbar-gutter:stable;overscroll-behavior:contain;display:flex!important;flex-direction:column!important;gap:6px;}
.ppi-preview-body{flex:0 0 auto!important;min-height:0!important;padding-bottom:0!important;}
.ppi-preview-sphere{height:clamp(260px,55dvh,720px)!important;max-height:55dvh!important;min-height:260px!important;}
.ppi-preview-action-footer{flex:0 0 auto!important;margin-top:4px!important;padding:10px 2px 20px!important;min-height:0!important;}
.ppi-preview-link-controls{display:flex!important;flex-direction:column!important;align-items:stretch!important;gap:8px!important;}
.ppi-preview-link-controls[hidden]{display:none!important;}
#ppi-preview-placement:not([hidden]){display:flex!important;flex-direction:column!important;align-items:stretch!important;width:100%;gap:9px;}
.ppi-preview-target-heading{font-weight:650;font-size:14px;}
.ppi-preview-target-gallery{display:grid;grid-template-columns:repeat(auto-fill,minmax(148px,1fr));gap:10px;max-height:190px;min-height:96px;overflow-y:auto;overscroll-behavior:contain;padding:4px 6px 9px;border:1px solid var(--color-border);border-radius:10px;background:var(--color-background-hover);scrollbar-gutter:stable;}
.ppi-target-card{display:flex;flex-direction:column;align-items:stretch;gap:6px;min-width:0;padding:7px!important;border:2px solid var(--color-border)!important;border-radius:9px!important;background:var(--color-main-background)!important;cursor:pointer!important;text-align:left!important;}
.ppi-target-card[aria-pressed="true"]{border-color:var(--color-primary-element)!important;box-shadow:0 0 0 1px var(--color-primary-element);}
.ppi-target-card img{display:block!important;height:72px!important;width:100%!important;object-fit:cover!important;border-radius:5px!important;max-height:72px!important;}
.ppi-target-card span{font-size:12px;line-height:1.3;overflow-wrap:anywhere;}
.ppi-preview-target-fallback{position:absolute!important;width:1px!important;height:1px!important;overflow:hidden!important;clip-path:inset(50%)!important;white-space:nowrap!important;}
.ppi-preview-window .ppi-preview-help{font-size:12px!important;line-height:1.35!important;margin:5px 0!important;}
@media(max-width:620px){.ppi-preview-sphere{height:42dvh!important;max-height:42dvh!important;min-height:220px!important}.ppi-preview-target-gallery{grid-template-columns:repeat(2,minmax(0,1fr));max-height:190px}.ppi-preview-window{height:calc(100dvh - 6px)!important;max-height:calc(100dvh - 6px)!important}.ppi-preview-scroll{padding-bottom:26px!important}}

/* 0.4.47: Scroll the whole preview window instead of nesting a clipped scroller.
   A single scrolling box ensures that target thumbnails and save controls are reachable. */
#ppi-tour-preview.ppi-tour-preview{overflow-y:hidden!important;align-items:center!important;}
#ppi-tour-preview .ppi-preview-window{
  display:block!important;overflow-y:auto!important;overflow-x:hidden!important;
  overscroll-behavior:contain;scrollbar-gutter:stable;
  height:calc(100dvh - 16px)!important;max-height:calc(100dvh - 16px)!important;
  min-height:0!important;touch-action:pan-y;
}
#ppi-tour-preview .ppi-preview-header{
  position:sticky!important;top:-12px!important;z-index:10!important;
  background:var(--color-main-background);padding:8px 3px 10px;
}
#ppi-tour-preview .ppi-preview-scroll{
  display:block!important;flex:none!important;max-height:none!important;height:auto!important;
  overflow:visible!important;min-height:0!important;padding-bottom:28px!important;
}
#ppi-tour-preview .ppi-preview-body,#ppi-tour-preview .ppi-preview-action-footer{overflow:visible!important;}
#ppi-tour-preview .ppi-preview-target-gallery{max-height:260px;overflow-y:auto!important;}
/* Keep the full tour workspace reachable in Nextcloud's content viewport. */
#content:has(#ppi-tours-app){overflow-y:auto!important;overflow-x:hidden!important;}
#ppi-tours-app{height:auto!important;min-height:100%;padding-bottom:90px!important;}
#ppi-tours-app #ppi-tour-editor{overflow:visible!important;}

/* 0.4.50: centred deletion glyphs and viewport-aware panorama sizing.
   Preserve 0.4.47's single scrolling dialog (overflow-y:auto on preview-window). */
#ppi-tours-app .ppi-tour-card-delete,
#ppi-tours-app .ppi-tour-edge-delete {
  display:flex!important;
  align-items:center!important;
  justify-content:center!important;
  box-sizing:border-box!important;
  text-align:center!important;
  line-height:1!important;
  text-indent:0!important;
}
#ppi-tours-app .ppi-delete-glyph {
  display:block;
  line-height:1!important;
  font-family:Arial,sans-serif;
  font-size:20px;
  font-weight:600;
  transform:translateY(-1px);
  pointer-events:none;
}
#ppi-tour-preview.ppi-tour-preview { padding:8px!important; }
#ppi-tour-preview .ppi-preview-window {
  width:min(92vw,1800px)!important;
  height:94dvh!important;
  max-height:94dvh!important;
  overflow-y:auto!important;
  overflow-x:hidden!important;
}
#ppi-tour-preview .ppi-preview-sphere {
  height:clamp(380px,70dvh,900px)!important;
  max-height:900px!important;
  min-height:0!important;
}
#ppi-tour-preview #ppi-preview-image {
  max-height:min(70dvh,900px)!important;
}
@media(max-width:620px) {
  #ppi-tour-preview .ppi-preview-window {
    width:calc(100vw - 8px)!important;
    height:calc(100dvh - 8px)!important;
    max-height:calc(100dvh - 8px)!important;
  }
  #ppi-tour-preview .ppi-preview-sphere {
    height:48dvh!important;
    max-height:48dvh!important;
    min-height:220px!important;
  }
}

/* 0.4.51: Always-visible compact target strip and space-reserving panorama.
   Keep the single scrollable preview-window from 0.4.47 as fallback. */
#ppi-tour-preview .ppi-preview-window {
  width:min(94vw,1800px)!important;
  height:calc(100dvh - 16px)!important;
  max-height:calc(100dvh - 16px)!important;
}
#ppi-tour-preview .ppi-preview-header {margin-bottom:4px!important;padding:4px 2px 6px!important;}
#ppi-tour-preview .ppi-preview-body {padding-bottom:2px!important;}
#ppi-tour-preview .ppi-preview-sphere {
  height:clamp(210px,calc(100dvh - 315px),900px)!important;
  max-height:calc(100dvh - 315px)!important;
  min-height:0!important;
}
#ppi-tour-preview #ppi-preview-image {max-height:calc(100dvh - 315px)!important;}
#ppi-tour-preview .ppi-preview-action-footer {margin-top:2px!important;padding:5px 2px 8px!important;}
#ppi-tour-preview .ppi-preview-link-controls {gap:4px!important;padding:5px 9px!important;}
#ppi-tour-preview .ppi-preview-target-heading {font-size:12px!important;line-height:1.1!important;}
#ppi-tour-preview .ppi-preview-target-gallery {
  display:flex!important;flex-wrap:nowrap!important;align-items:stretch!important;
  width:100%;box-sizing:border-box;gap:8px!important;
  max-height:118px!important;min-height:0!important;
  overflow-x:auto!important;overflow-y:hidden!important;padding:3px 5px!important;
  scrollbar-gutter:auto!important;
}
#ppi-tour-preview .ppi-target-card {flex:0 0 132px!important;width:132px!important;gap:3px!important;padding:4px!important;}
#ppi-tour-preview .ppi-target-card img {height:66px!important;max-height:66px!important;}
#ppi-tour-preview .ppi-target-card span {font-size:11px!important;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
#ppi-tour-preview .ppi-preview-help {margin:3px 0!important;font-size:11px!important;}
#ppi-tour-preview .ppi-preview-help summary {cursor:pointer;color:var(--color-text-maxcontrast);}
#ppi-tour-preview .ppi-preview-help p {margin:3px 0!important;}
#ppi-tour-preview #ppi-preview-status {margin:3px 0!important;line-height:1.15!important;}
#ppi-tour-preview #ppi-preview-placement:not([hidden]) {flex-direction:row!important;flex-wrap:wrap!important;gap:6px!important;}
@media(max-height:680px) {
  #ppi-tour-preview .ppi-preview-sphere {height:max(190px,calc(100dvh - 300px))!important;max-height:calc(100dvh - 300px)!important;}
  #ppi-tour-preview .ppi-preview-target-gallery {max-height:96px!important;}
  #ppi-tour-preview .ppi-target-card img {height:50px!important;max-height:50px!important;}
}
@media(max-width:620px) {
  #ppi-tour-preview .ppi-preview-window {width:calc(100vw - 8px)!important;height:calc(100dvh - 8px)!important;max-height:calc(100dvh - 8px)!important;}
  #ppi-tour-preview .ppi-preview-sphere {height:max(215px,calc(100dvh - 320px))!important;max-height:calc(100dvh - 320px)!important;}
  #ppi-tour-preview .ppi-target-card {flex-basis:118px!important;width:118px!important;}
}

/* 0.4.52: Tour loading veil, matching the completed 360° viewer animation. */
#ppi-tour-preview .ppi-sphere-loading{position:absolute;inset:0;z-index:9;overflow:hidden;background:#121820;display:flex;align-items:center;justify-content:center;color:white;font:600 15px/1.3 system-ui,sans-serif;opacity:1;transition:opacity .52s ease-out;pointer-events:none;border-radius:inherit}
#ppi-tour-preview .ppi-sphere-loading-done{opacity:0}
#ppi-tour-preview .ppi-sphere-loading-done .ppi-sphere-loading-content{opacity:0;transition:opacity .15s ease-out}
#ppi-tour-preview .ppi-sphere-loading-preview{position:absolute;inset:-24px;background-position:center;background-size:cover;filter:blur(17px);transform:scale(1.05);opacity:.65}
#ppi-tour-preview .ppi-sphere-loading-progress-preview{position:absolute;inset:-24px;background-position:center;background-size:cover;filter:blur(12px);transform:scale(1.05);opacity:.83;clip-path:inset(0 0 100% 0);transition:clip-path .55s cubic-bezier(.35,0,.25,1);pointer-events:none}
#ppi-tour-preview .ppi-sphere-loading-preview-complete{clip-path:inset(0 0 0 0)}
#ppi-tour-preview .ppi-sphere-loading-content{position:relative;z-index:1;padding:20px 24px;width:min(330px,86%);box-sizing:border-box;border-radius:16px;background:rgba(8,16,25,.55);border:1px solid rgba(255,255,255,.2);backdrop-filter:blur(8px);text-align:center}
#ppi-tour-preview .ppi-sphere-loading-spinner{width:36px;height:36px;border:3px solid rgba(255,255,255,.26);border-top-color:#fff;border-radius:50%;margin:0 auto 15px;animation:ppi-tour-spin .9s linear infinite}
#ppi-tour-preview .ppi-sphere-loading-detail{font:500 12px/1.4 system-ui,sans-serif;margin-top:6px;opacity:.85}
#ppi-tour-preview .ppi-sphere-loading-track{width:100%;height:7px;border-radius:999px;overflow:hidden;background:rgba(255,255,255,.24);margin-top:16px}
#ppi-tour-preview .ppi-sphere-loading-fill{width:32%;height:100%;border-radius:inherit;background:#fff;animation:ppi-tour-loading 1.5s ease-in-out infinite}
@keyframes ppi-tour-spin{to{transform:rotate(360deg)}}
@keyframes ppi-tour-loading{0%{transform:translateX(-120%)}50%{transform:translateX(120%)}100%{transform:translateX(340%)}}
@media(prefers-reduced-motion:reduce){#ppi-tour-preview .ppi-sphere-loading-spinner,#ppi-tour-preview .ppi-sphere-loading-fill{animation:none}#ppi-tour-preview .ppi-sphere-loading{transition:none}}

/* 0.4.55: Outside-the-frustum markers must remain hidden even with flex!important. */
.ppi-preview-sphere .ppi-hotspot[hidden], #ppi-public-sphere .ppi-hotspot[hidden]{display:none!important;}

/* 0.4.53: Tour guide with a real destination preview, scoped to tour viewers only. */
.ppi-preview-sphere .ppi-hotspot-person, #ppi-public-sphere .ppi-hotspot-person{
  display:flex!important; flex-direction:column;align-items:center;justify-content:flex-start;
  width:96px!important;height:116px!important;min-width:0!important;min-height:0!important;
  padding:0!important;border:0!important;border-radius:0!important;background:transparent!important;
  box-shadow:none!important;color:#fff!important; transform:translate(-50%,-70%)!important;
  cursor:pointer;overflow:visible!important;isolation:isolate;
}
.ppi-hotspot-person .ppi-hotspot-photo{display:block;box-sizing:border-box;width:92px;height:53px;padding:3px;
  border:3px dashed #fff;border-radius:8px;background:#17202b;box-shadow:0 2px 9px #000a;transform:rotate(-3deg);}
.ppi-hotspot-person .ppi-hotspot-thumb{display:block!important;width:100%!important;height:100%!important;max-height:none!important;
  object-fit:cover!important;border-radius:3px!important;background:#2a343d!important;}
.ppi-hotspot-person .ppi-hotspot-figure{display:block;width:80px;height:63px;margin-top:-2px;filter:drop-shadow(1px 1px 2px #000);pointer-events:none;}
.ppi-hotspot-person:hover .ppi-hotspot-photo,.ppi-hotspot-person:focus-visible .ppi-hotspot-photo{border-color:#ffffffcc;box-shadow:0 3px 13px #0009;}
@media(max-width:650px){.ppi-preview-sphere .ppi-hotspot-person,#ppi-public-sphere .ppi-hotspot-person{transform:translate(-50%,-70%) scale(.8)!important}}

/* The centre of the cross is the centre of its button, independent of text metrics. */
#ppi-tours-app .ppi-delete-glyph {position:relative!important;display:block!important;width:15px!important;height:15px!important;font-size:0!important;transform:none!important;line-height:0!important;flex:0 0 15px!important;}
#ppi-tours-app .ppi-delete-glyph::before,#ppi-tours-app .ppi-delete-glyph::after {content:"";position:absolute;left:0;top:6.5px;width:15px;height:2px;background:currentColor;border-radius:2px;transform:rotate(45deg);}
#ppi-tours-app .ppi-delete-glyph::after{transform:rotate(-45deg)}
/* 0.4.62.38: Place removal control with its centre over the thumbnail’s rounded top-right corner. */
#ppi-tours-app .ppi-tour-graph-card .ppi-tour-card-delete{top:8px!important;right:8px!important;transform:translate(50%,-50%)!important;}

/* 0.4.57: Tour-Karten und Setup-Navigation */
#ppi-tours-app .ppi-tour-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,310px),1fr));gap:16px;margin-top:16px}
#ppi-tours-app .ppi-overview-tile{min-width:0;overflow:hidden;border:1px solid var(--color-border);border-radius:13px;background:var(--color-main-background);box-shadow:0 2px 8px #0001}
#ppi-tours-app .ppi-overview-thumb{width:100%;height:150px;object-fit:cover;display:block;background:var(--color-background-dark)}
#ppi-tours-app .ppi-overview-placeholder{height:150px;display:grid;place-items:center;font-size:42px;background:var(--color-background-hover)}
#ppi-tours-app .ppi-overview-body{padding:14px;display:flex;flex-direction:column;gap:10px}
#ppi-tours-app .ppi-overview-name{font-size:17px;margin:0;overflow-wrap:anywhere}
#ppi-tours-app .ppi-overview-status{font-size:12px;color:var(--color-text-maxcontrast)}
#ppi-tours-app .ppi-overview-actions{display:flex;flex-wrap:wrap;gap:7px}
#ppi-tours-app .ppi-overview-actions button{font-size:12px;min-height:34px;padding:6px 10px}
#ppi-tours-app .ppi-setup-tabs{display:flex;flex-wrap:wrap;gap:8px;margin:18px 0;padding:8px;background:var(--color-background-hover);border-radius:12px}
#ppi-tours-app .ppi-setup-tab{flex:1 1 180px;min-height:42px;text-align:center;border:1px solid var(--color-border);border-radius:9px;background:var(--color-main-background)}
#ppi-tours-app .ppi-setup-tab[aria-current="step"]{border-color:var(--color-primary-element);box-shadow:inset 0 -3px var(--color-primary-element);font-weight:650}
#ppi-tours-app .ppi-setup-tab:disabled{opacity:.55;cursor:not-allowed}
#ppi-tours-app .ppi-step-help{color:var(--color-text-maxcontrast);margin-top:8px}
#ppi-tours-app #ppi-step-edit[hidden],#ppi-tours-app #ppi-step-share[hidden],#ppi-tours-app #ppi-step-design[hidden]{display:none!important}
/* 0.4.58: Tour display settings */
#ppi-tours-app .ppi-design-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,320px),1fr));gap:15px;margin:18px 0}
#ppi-tours-app .ppi-design-field{border:1px solid var(--color-border);border-radius:12px;padding:15px;background:var(--color-main-background);display:flex;flex-direction:column;gap:9px}
#ppi-tours-app .ppi-design-field>label:first-child{font-weight:650}
#ppi-tours-app .ppi-design-field select,#ppi-tours-app .ppi-design-field textarea{width:100%;max-width:100%;box-sizing:border-box}
#ppi-tours-app .ppi-design-field textarea{resize:vertical;min-height:100px;padding:10px;color:var(--color-main-text);background:var(--color-main-background);border:1px solid var(--color-border);border-radius:8px;font:inherit;line-height:1.5}
#ppi-tours-app .ppi-design-field p{font-size:12px;color:var(--color-text-maxcontrast);margin:0}
#ppi-tours-app .ppi-design-field label:has(input[type=checkbox]){display:flex;gap:10px;align-items:center;min-height:32px}
#ppi-tours-app .ppi-design-range{display:flex;align-items:center;gap:12px}
#ppi-tours-app .ppi-design-range input{flex:1;min-width:0}
#ppi-tours-app .ppi-design-future{padding:12px;background:var(--color-background-hover);border-radius:10px;margin:14px 0;font-size:13px}

/* 0.4.58.3: Optional blue guide; keep anchor and screen-fade geometry unchanged. */
.ppi-hotspot-person .ppi-hotspot-blue-figure{display:none!important}
.ppi-preview-sphere .ppi-hotspot-blue,#ppi-public-sphere .ppi-hotspot-blue{width:96px!important;height:116px!important}
.ppi-hotspot-blue .ppi-hotspot-figure{display:none!important}
.ppi-hotspot-blue .ppi-hotspot-blue-figure{display:block!important;position:absolute;left:50%;bottom:0;width:auto!important;height:110px!important;max-width:none!important;transform:translateX(-50%);pointer-events:none;filter:drop-shadow(0 2px 5px #0009)}
.ppi-hotspot-blue .ppi-hotspot-photo{position:absolute!important;z-index:5;left:calc(50% + 34px);top:-13px;width:115px;height:72px;opacity:0;visibility:hidden;transform:translate(-7px,0) scale(.95);transition:opacity .2s ease,transform .2s ease,visibility .2s;border:2px solid rgba(255,255,255,.9);background:#142b43;pointer-events:auto}
.ppi-hotspot-blue::after{content:"";position:absolute;z-index:4;left:72px;top:-18px;width:56px;height:100px;pointer-events:auto;background:transparent;}
.ppi-hotspot-blue:hover .ppi-hotspot-photo,.ppi-hotspot-blue:focus-visible .ppi-hotspot-photo,.ppi-hotspot-blue:focus-within .ppi-hotspot-photo{opacity:1;visibility:visible;transform:translate(0,0) scale(1)}
.ppi-hotspot-blue:hover .ppi-hotspot-blue-figure,.ppi-hotspot-blue:focus-visible .ppi-hotspot-blue-figure{filter:drop-shadow(0 3px 7px #000a)}
@media(hover:none){.ppi-hotspot-blue .ppi-hotspot-photo{opacity:1;visibility:visible;transform:none}}
@media(prefers-reduced-motion:reduce){.ppi-hotspot-blue .ppi-hotspot-photo{transition:none}}


/* 0.4.62.4: separated create action, compact display settings, responsive editor. */
#ppi-tours-app .ppi-tour-intro-header{display:flex;align-items:center;gap:14px;margin-bottom:10px;flex-wrap:wrap}
#ppi-tours-app .ppi-tour-intro-heading{flex:1 1 270px;min-width:0}
#ppi-tours-app #ppi-tour-create{margin-left:auto;min-height:42px;white-space:normal}
#ppi-tours-app #ppi-tour-overview>h2{margin-bottom:14px}
#ppi-tours-app #ppi-tour-create[hidden]{display:none!important}
#ppi-tours-app #ppi-step-design .ppi-design-grid{grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr));gap:10px;margin:12px 0}
#ppi-tours-app #ppi-step-design .ppi-design-field{padding:11px 12px;gap:6px;border-radius:9px}
#ppi-tours-app #ppi-step-design .ppi-design-field p{font-size:11px;line-height:1.35}
#ppi-tours-app #ppi-step-design .ppi-design-field textarea{min-height:75px}
#ppi-tours-app #ppi-step-design .ppi-design-field input:not([type="checkbox"]):not([type="range"]){width:100%;max-width:100%;min-height:38px;box-sizing:border-box}
#ppi-tours-app #ppi-step-design .ppi-design-name{grid-column:1/-1}
#ppi-tours-app #ppi-step-design .ppi-design-name input{max-width:520px!important}
#ppi-tours-app #ppi-step-design .ppi-design-future{margin:8px 0 12px;padding:9px 12px}
#ppi-tours-app .ppi-graph-mobile-hint{display:none}
#ppi-tours-app .ppi-mobile-pano{border:1px solid var(--color-border);background:var(--color-main-background);border-radius:11px;padding:10px;display:flex;flex-direction:column;gap:9px;min-width:0}
#ppi-tours-app .ppi-mobile-pano-title{font-size:13px;font-weight:650;overflow-wrap:anywhere}
#ppi-tours-app .ppi-mobile-pano-photo{width:100%;height:130px;display:flex;align-items:center;justify-content:center;overflow:hidden;border:0!important;padding:0!important;border-radius:8px;background:var(--color-background-dark)}
#ppi-tours-app .ppi-mobile-pano-photo img{width:100%;height:100%;object-fit:cover}
#ppi-tours-app .ppi-mobile-pano-links{display:flex;flex-direction:column;gap:6px}
#ppi-tours-app .ppi-mobile-link{display:flex;align-items:center;gap:8px;min-width:0;padding:6px 8px;border-radius:7px;background:var(--color-background-hover);font-size:12px}
#ppi-tours-app .ppi-mobile-link span{min-width:0;flex:1;overflow-wrap:anywhere}
#ppi-tours-app .ppi-mobile-link button{flex-shrink:0;min-width:38px;min-height:38px}
#ppi-tours-app .ppi-mobile-pano-actions{display:flex;gap:8px;flex-wrap:wrap}
#ppi-tours-app .ppi-mobile-pano-actions button{flex:1;min-height:42px}
@media(max-width:700px){
 #ppi-tours-app{padding:0 10px 30px!important;margin:10px auto!important}
 #ppi-tours-app .ppi-tour-intro{padding:13px!important}
 #ppi-tours-app .ppi-tour-intro-header{gap:9px}
 #ppi-tours-app #ppi-tour-create{width:100%;margin-left:0}
 #ppi-tours-app .ppi-tour-card{padding:12px!important}
 #ppi-tours-app .ppi-setup-tabs{gap:5px;padding:5px;flex-wrap:nowrap}
 #ppi-tours-app .ppi-setup-tab{flex:1 1 0;min-width:0;min-height:48px;padding:5px!important;font-size:12px!important;line-height:1.25}
 #ppi-tours-app .ppi-graph-desktop-hint{display:none}
 #ppi-tours-app .ppi-graph-mobile-hint{display:block;font-size:13px;color:var(--color-text-maxcontrast)}
 #ppi-tours-app #ppi-tour-graph{overflow:visible;padding:8px;min-height:0}
 #ppi-tours-app .ppi-mobile-graph{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,235px),1fr));gap:12px;min-width:0}
 #ppi-tours-app #ppi-step-design .ppi-design-grid{grid-template-columns:minmax(0,1fr)}
}

/* 0.4.62.9: compact, content-sized display configuration and two reactive previews. */
#ppi-tours-app #ppi-step-design .ppi-design-top{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,1fr);gap:10px;align-items:start;margin:12px 0 10px}
#ppi-tours-app #ppi-step-design .ppi-design-name{grid-column:auto;min-width:0}
#ppi-tours-app #ppi-step-design .ppi-design-name input{max-width:100%!important}
#ppi-tours-app #ppi-step-design .ppi-design-previews{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px;min-width:0}
#ppi-tours-app #ppi-step-design .ppi-design-mini{border:1px solid var(--color-border);border-radius:9px;padding:7px;min-width:0;background:var(--color-main-background)}
#ppi-tours-app #ppi-step-design .ppi-design-mini strong{display:block;font-size:12px;margin-bottom:5px}
#ppi-tours-app #ppi-step-design .ppi-design-mini-image{height:86px;border-radius:6px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:var(--color-background-dark)}
#ppi-tours-app #ppi-step-design .ppi-design-mini-image>img{width:100%;height:100%;object-fit:cover}
#ppi-tours-app #ppi-step-design .ppi-design-hotspot-preview>img{width:auto;height:80px;max-width:100%;object-fit:contain;filter:drop-shadow(0 1px 3px #0006);transform:scale(var(--ppi-mini-scale,1))}
#ppi-tours-app #ppi-step-design .ppi-design-hotspot-preview>svg{height:68px;width:90px;color:var(--color-main-text);transform:scale(var(--ppi-mini-scale,1))}
#ppi-tours-app #ppi-step-design .ppi-design-layout{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;align-items:start}
#ppi-tours-app #ppi-step-design .ppi-design-group{display:flex;flex-direction:column;gap:10px;min-width:0}
#ppi-tours-app #ppi-step-design .ppi-design-layout .ppi-design-field{min-width:0}
#ppi-tours-app #ppi-step-design .ppi-design-intro textarea{min-height:105px;resize:vertical}
#ppi-tours-app #ppi-step-design .ppi-design-options{gap:5px;padding-top:12px}
#ppi-tours-app #ppi-step-design .ppi-design-range-ends{display:flex;justify-content:space-between;gap:8px;font-size:10px;color:var(--color-text-maxcontrast)}
#ppi-tours-app #ppi-step-design .ppi-design-range output{min-width:86px;text-align:right;font-size:12px}
@media(max-width:700px){#ppi-tours-app #ppi-step-design .ppi-design-top,#ppi-tours-app #ppi-step-design .ppi-design-layout{grid-template-columns:minmax(0,1fr)}}
@media(max-width:380px){#ppi-tours-app #ppi-step-design .ppi-design-previews{grid-template-columns:repeat(2,minmax(0,1fr))}}

/* 0.4.62.41: match the height of the adjacent start-direction field. */
#ppi-tours-app #ppi-step-design .ppi-design-group .ppi-design-field:has(>label[for="ppi-design-size"]){padding-bottom:12px}
/* 0.4.62.6: keep the outer tour area fixed to available space as cards change count. */
#ppi-tours-app,#ppi-tours-app #ppi-tour-overview,#ppi-tours-app .ppi-tour-list{min-width:0;max-width:100%;box-sizing:border-box}
#ppi-tours-app #ppi-tour-overview{width:100%}
#ppi-tours-app .ppi-tour-list{width:100%;min-width:0}
#ppi-tours-app .ppi-overview-tile{min-width:0;max-width:100%;box-sizing:border-box}
#ppi-tours-app .ppi-overview-body,#ppi-tours-app .ppi-overview-actions{min-width:0;max-width:100%;box-sizing:border-box}
#ppi-tours-app .ppi-overview-actions button{max-width:100%;min-width:0}
/* 0.4.62.5: adaptive tour overview; the first card is featured for 3+ tours. */
#ppi-tours-app .ppi-tour-list{grid-template-columns:repeat(2,minmax(0,1fr));align-items:stretch}
#ppi-tours-app .ppi-tour-list.ppi-tours-single{grid-template-columns:minmax(0,1fr)}
#ppi-tours-app .ppi-tour-list.ppi-tours-featured>.ppi-overview-tile:first-child{grid-column:1/-1}
#ppi-tours-app .ppi-tour-list.ppi-tours-single .ppi-overview-thumb,
#ppi-tours-app .ppi-tour-list.ppi-tours-featured>.ppi-overview-tile:first-child .ppi-overview-thumb{height:clamp(190px,32vw,300px)}
#ppi-tours-app .ppi-tour-list.ppi-tours-single .ppi-overview-placeholder,
#ppi-tours-app .ppi-tour-list.ppi-tours-featured>.ppi-overview-tile:first-child .ppi-overview-placeholder{height:clamp(190px,32vw,300px)}
@media(max-width:700px){
  #ppi-tours-app .ppi-tour-list{grid-template-columns:minmax(0,1fr)}
  #ppi-tours-app .ppi-tour-list.ppi-tours-featured>.ppi-overview-tile:first-child{grid-column:auto}
  #ppi-tours-app .ppi-tour-list.ppi-tours-single .ppi-overview-thumb,
  #ppi-tours-app .ppi-tour-list.ppi-tours-featured>.ppi-overview-tile:first-child .ppi-overview-thumb{height:180px}
  #ppi-tours-app .ppi-tour-list.ppi-tours-single .ppi-overview-placeholder,
  #ppi-tours-app .ppi-tour-list.ppi-tours-featured>.ppi-overview-tile:first-child .ppi-overview-placeholder{height:180px}
}


/* 0.4.62.11: focus only connected routes on card hover, preserve delete hover. */
#ppi-tours-app .ppi-tour-arrows .ppi-tour-edge-visible{transition:stroke-width .16s ease,stroke .16s ease,opacity .16s ease}
#ppi-tours-app .ppi-tour-arrows.ppi-arrows-card-focused .ppi-tour-edge-visible{opacity:.17}
#ppi-tours-app .ppi-tour-arrows.ppi-arrows-card-focused .ppi-tour-edge-visible.ppi-edge-related{opacity:1;stroke-width:4.2}
#ppi-tours-app .ppi-tour-arrows .ppi-tour-edge-visible.ppi-edge-active{opacity:1!important}
#ppi-tours-app #ppi-step-design .ppi-design-hotspot-preview>[hidden]{display:none!important}


/* 0.4.62.21: preview-and-select pairs and truly compact independent columns. */
#ppi-tours-app #ppi-step-design .ppi-design-top{grid-template-columns:minmax(0,1.6fr) repeat(2,minmax(0,1fr));gap:10px;align-items:stretch}
#ppi-tours-app #ppi-step-design .ppi-design-mini{display:flex;flex-direction:column;gap:7px}
#ppi-tours-app #ppi-step-design .ppi-design-mini>label{font-size:12px;font-weight:650}
#ppi-tours-app #ppi-step-design .ppi-design-mini>select{width:100%;min-width:0;max-width:100%;box-sizing:border-box;min-height:36px;margin-top:auto}
#ppi-tours-app #ppi-step-design .ppi-design-name{align-self:stretch}
#ppi-tours-app #ppi-step-design .ppi-design-group{gap:10px}
#ppi-tours-app #ppi-step-design .ppi-design-social #ppi-design-social-status{font-weight:600;color:var(--color-main-text)}
@media(max-width:700px){
 #ppi-tours-app #ppi-step-design .ppi-design-top{grid-template-columns:repeat(2,minmax(0,1fr))}
 #ppi-tours-app #ppi-step-design .ppi-design-name{grid-column:1/-1}
}
@media(max-width:440px){
 #ppi-tours-app #ppi-step-design .ppi-design-top{grid-template-columns:minmax(0,1fr)}
 #ppi-tours-app #ppi-step-design .ppi-design-name{grid-column:auto}
}


/* 0.4.62.22: evenly grouped settings, compact layout without stretching cards. */
#ppi-tours-app #ppi-step-design .ppi-design-top{align-items:start}
#ppi-tours-app #ppi-step-design .ppi-design-name{align-self:start}
#ppi-tours-app #ppi-step-design .ppi-design-layout{align-items:start;gap:10px}
#ppi-tours-app #ppi-step-design .ppi-design-group{align-self:start;gap:10px}
#ppi-tours-app #ppi-step-design .ppi-design-options{padding-top:11px;padding-bottom:11px}
#ppi-tours-app #ppi-step-design .ppi-design-options label{min-height:28px}
#ppi-tours-app #ppi-step-design .ppi-design-intro textarea{min-height:90px}


/* 0.4.62.23: move size control opposite the description and fill available vertical space. */
#ppi-tours-app #ppi-step-design .ppi-design-layout{align-items:stretch}
#ppi-tours-app #ppi-step-design .ppi-design-layout>.ppi-design-group{align-self:stretch;min-height:0}
#ppi-tours-app #ppi-step-design .ppi-design-intro{display:flex;flex-direction:column;flex:1 1 auto;min-height:175px}
#ppi-tours-app #ppi-step-design .ppi-design-intro textarea{flex:1 1 auto;min-height:128px;height:100%;box-sizing:border-box;resize:vertical}
#ppi-tours-app #ppi-step-design .ppi-design-intro>p{flex-shrink:0}
@media(max-width:700px){
 #ppi-tours-app #ppi-step-design .ppi-design-intro{min-height:0;flex:0 1 auto}
 #ppi-tours-app #ppi-step-design .ppi-design-intro textarea{height:auto;min-height:140px}
}


/* 0.4.62.24: balanced two-column presentation; description fills the lower right. */
#ppi-tours-app #ppi-step-design .ppi-design-layout{grid-template-columns:minmax(0,9fr) minmax(0,11fr);align-items:stretch;gap:10px}
#ppi-tours-app #ppi-step-design .ppi-design-layout>.ppi-design-group{display:flex;flex-direction:column;align-self:stretch;min-width:0}
#ppi-tours-app #ppi-step-design .ppi-design-intro{flex:1 1 auto;min-height:190px}
#ppi-tours-app #ppi-step-design .ppi-design-intro textarea{flex:1 1 auto;min-height:145px;height:100%;width:100%;box-sizing:border-box}
@media(max-width:700px){
 #ppi-tours-app #ppi-step-design .ppi-design-layout{grid-template-columns:minmax(0,1fr)}
 #ppi-tours-app #ppi-step-design .ppi-design-intro{flex:0 1 auto;min-height:0}
 #ppi-tours-app #ppi-step-design .ppi-design-intro textarea{flex:0 1 auto;height:auto;min-height:140px}
}


/* 0.4.62.25: align lower split precisely with the first upper card;
   reduce preview height to lift the start-direction field. */
@media(min-width:701px){
 #ppi-tours-app #ppi-step-design .ppi-design-top{margin-bottom:6px}
 #ppi-tours-app #ppi-step-design .ppi-design-mini-image{height:74px}
 /* Upper columns: 1.6fr / 1fr / 1fr with two 10px gaps.
    Split the lower row at the same left edge as the upper second column. */
 #ppi-tours-app #ppi-step-design .ppi-design-layout{
  grid-template-columns:minmax(0,calc(44.444444% - 8.888889px)) minmax(0,1fr)
 }
}


/* 0.4.62.26: Two additional tour-wide pin designs, each paired with a target preview. */
#ppi-tours-app #ppi-step-design .ppi-design-hotspot-preview #ppi-design-pin-preview{width:auto!important;height:76px!important;max-width:100%;object-fit:contain;transform:scale(var(--ppi-mini-scale,1));background:transparent!important;filter:drop-shadow(0 2px 3px #0004)}
.ppi-hotspot-person .ppi-hotspot-pin-figure{display:none!important}
.ppi-preview-sphere .ppi-hotspot-map-pin,.ppi-preview-sphere .ppi-hotspot-chat-pin,
#ppi-public-sphere .ppi-hotspot-map-pin,#ppi-public-sphere .ppi-hotspot-chat-pin{width:96px!important;height:116px!important}
.ppi-hotspot-map-pin .ppi-hotspot-figure,.ppi-hotspot-map-pin .ppi-hotspot-blue-figure,
.ppi-hotspot-chat-pin .ppi-hotspot-figure,.ppi-hotspot-chat-pin .ppi-hotspot-blue-figure{display:none!important}
.ppi-hotspot-map-pin .ppi-hotspot-pin-figure,.ppi-hotspot-chat-pin .ppi-hotspot-pin-figure{display:block!important;position:absolute;bottom:0;left:50%;width:76px!important;height:100px!important;max-width:none!important;transform:translateX(-50%);object-fit:contain;pointer-events:none;background:#fffef2;border-radius:13px;padding:5px;box-sizing:border-box;filter:drop-shadow(0 3px 5px #000a)}
.ppi-hotspot-map-pin .ppi-hotspot-photo,.ppi-hotspot-chat-pin .ppi-hotspot-photo{position:absolute!important;z-index:5;left:calc(50% + 31px);top:0;width:112px;height:69px;opacity:0;visibility:hidden;transform:translate(-7px,0) scale(.95) rotate(0);transition:opacity .2s ease,transform .2s ease,visibility .2s;border:2px solid #fff;background:#142b43;pointer-events:auto}
.ppi-hotspot-map-pin::after,.ppi-hotspot-chat-pin::after{content:"";position:absolute;left:65px;top:0;width:69px;height:93px;pointer-events:auto;background:transparent}
.ppi-hotspot-map-pin:hover .ppi-hotspot-photo,.ppi-hotspot-map-pin:focus-visible .ppi-hotspot-photo,
.ppi-hotspot-chat-pin:hover .ppi-hotspot-photo,.ppi-hotspot-chat-pin:focus-visible .ppi-hotspot-photo{opacity:1;visibility:visible;transform:translate(0,0) scale(1)}
@media(hover:none){.ppi-hotspot-map-pin .ppi-hotspot-photo,.ppi-hotspot-chat-pin .ppi-hotspot-photo{opacity:1;visibility:visible;transform:none}}
@media(prefers-reduced-motion:reduce){.ppi-hotspot-map-pin .ppi-hotspot-photo,.ppi-hotspot-chat-pin .ppi-hotspot-photo{transition:none}}


/* 0.4.62.27: label-pin uses the same preview and keyboard/touch behavior as other pin designs. */
.ppi-preview-sphere .ppi-hotspot-label-pin,#ppi-public-sphere .ppi-hotspot-label-pin{width:96px!important;height:116px!important}
.ppi-preview-sphere .ppi-hotspot-outline-pin,#ppi-public-sphere .ppi-hotspot-outline-pin{width:96px!important;height:116px!important}
.ppi-hotspot-label-pin .ppi-hotspot-figure,.ppi-hotspot-label-pin .ppi-hotspot-blue-figure{display:none!important}
.ppi-hotspot-outline-pin .ppi-hotspot-figure,.ppi-hotspot-outline-pin .ppi-hotspot-blue-figure{display:none!important}
.ppi-hotspot-label-pin .ppi-hotspot-pin-figure{display:block!important;position:absolute;bottom:0;left:50%;width:76px!important;height:100px!important;max-width:none!important;transform:translateX(-50%);object-fit:contain;pointer-events:none;background:#fffef2;border-radius:13px;padding:5px;box-sizing:border-box;filter:drop-shadow(0 3px 5px #000a)}
.ppi-hotspot-outline-pin .ppi-hotspot-pin-figure{display:block!important;position:absolute;bottom:0;left:50%;width:76px!important;height:100px!important;max-width:none!important;transform:translateX(-50%);object-fit:contain;pointer-events:none;background:#fffef2;border-radius:13px;padding:5px;box-sizing:border-box;filter:drop-shadow(0 3px 5px #000a)}
.ppi-hotspot-label-pin .ppi-hotspot-photo{position:absolute!important;z-index:5;left:calc(50% + 31px);top:0;width:112px;height:69px;opacity:0;visibility:hidden;transform:translate(-7px,0) scale(.95) rotate(0);transition:opacity .2s ease,transform .2s ease,visibility .2s;border:2px solid #fff;background:#142b43;pointer-events:auto}
.ppi-hotspot-outline-pin .ppi-hotspot-photo{position:absolute!important;z-index:5;left:calc(50% + 31px);top:0;width:112px;height:69px;opacity:0;visibility:hidden;transform:translate(-7px,0) scale(.95) rotate(0);transition:opacity .2s ease,transform .2s ease,visibility .2s;border:2px solid #fff;background:#142b43;pointer-events:auto}
.ppi-hotspot-label-pin::after{content:"";position:absolute;left:65px;top:0;width:69px;height:93px;pointer-events:auto;background:transparent}
.ppi-hotspot-outline-pin::after{content:"";position:absolute;left:65px;top:0;width:69px;height:93px;pointer-events:auto;background:transparent}
.ppi-hotspot-label-pin:hover .ppi-hotspot-photo,.ppi-hotspot-label-pin:focus-visible .ppi-hotspot-photo{opacity:1;visibility:visible;transform:translate(0,0) scale(1)}
.ppi-hotspot-outline-pin:hover .ppi-hotspot-photo,.ppi-hotspot-outline-pin:focus-visible .ppi-hotspot-photo{opacity:1;visibility:visible;transform:translate(0,0) scale(1)}
@media(hover:none){.ppi-hotspot-label-pin .ppi-hotspot-photo{opacity:1;visibility:visible;transform:none}}
@media(hover:none){.ppi-hotspot-outline-pin .ppi-hotspot-photo{opacity:1;visibility:visible;transform:none}}
@media(prefers-reduced-motion:reduce){.ppi-hotspot-label-pin .ppi-hotspot-photo{transition:none}}
@media(prefers-reduced-motion:reduce){.ppi-hotspot-outline-pin .ppi-hotspot-photo{transition:none}}


/* 0.4.62.29: SVG pins retain genuine transparency in editor (including the button underneath). */
#ppi-tours-app .ppi-preview-sphere .ppi-hotspot.ppi-hotspot-map-pin,
#ppi-tours-app .ppi-preview-sphere .ppi-hotspot.ppi-hotspot-chat-pin,
#ppi-tours-app .ppi-preview-sphere .ppi-hotspot.ppi-hotspot-label-pin,
#ppi-tours-app .ppi-preview-sphere .ppi-hotspot.ppi-hotspot-outline-pin{
  background:transparent!important;border:0!important;border-radius:0!important;box-shadow:none!important;
}
#ppi-tours-app .ppi-preview-window .ppi-hotspot-map-pin .ppi-hotspot-pin-figure,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-chat-pin .ppi-hotspot-pin-figure,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-label-pin .ppi-hotspot-pin-figure,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-outline-pin .ppi-hotspot-pin-figure{
  background:transparent!important;border:0!important;border-radius:0!important;padding:0!important;
}

/* 0.4.62.30: SVG-only color picker, preserving the original transparent silhouettes. */
#ppi-tours-app #ppi-design-pin-colors[hidden]{display:none!important}
#ppi-tours-app .ppi-svg-color-controls{display:flex;align-items:center;gap:10px;margin:7px 0}
#ppi-tours-app .ppi-svg-color-controls input[type=color]{width:55px;height:38px;padding:2px;cursor:pointer}
#ppi-tours-app .ppi-svg-color-swatches{display:flex;gap:7px;flex-wrap:wrap;margin:4px 0 7px}
#ppi-tours-app .ppi-svg-color-swatches button{width:25px;height:25px;min-width:25px;min-height:25px;padding:0;border:1px solid var(--color-border-dark,#888);border-radius:50%;background:var(--swatch);cursor:pointer}
#ppi-tours-app #ppi-step-design .ppi-design-hotspot-preview #ppi-design-pin-preview{display:block;width:64px!important;height:72px!important;flex:none;transform:scale(var(--ppi-mini-scale,1));background:var(--ppi-svg-pin-color,#1687bc)!important;-webkit-mask-position:center;-webkit-mask-size:contain;-webkit-mask-repeat:no-repeat;mask-position:center;mask-size:contain;mask-repeat:no-repeat;filter:drop-shadow(0 1px 2px #0006)}
#ppi-tours-app #ppi-design-pin-preview[hidden]{display:none!important}
#ppi-tours-app .ppi-preview-window .ppi-hotspot-map-pin .ppi-hotspot-pin-figure,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-chat-pin .ppi-hotspot-pin-figure,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-label-pin .ppi-hotspot-pin-figure,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-outline-pin .ppi-hotspot-pin-figure{background:var(--ppi-svg-pin-color,#1687bc)!important;padding:0!important;border:0!important;border-radius:0!important;-webkit-mask-position:center;-webkit-mask-size:contain;-webkit-mask-repeat:no-repeat;mask-position:center;mask-size:contain;mask-repeat:no-repeat}

/* 0.4.62.32: On touch devices show a destination thumbnail only after the
   first tap. Second tap navigates; tapping the canvas dismisses the preview.
   The existing desktop hover/focus behaviour remains untouched. */
@media (hover:none), (pointer:coarse) {
  .ppi-preview-sphere .ppi-hotspot-person .ppi-hotspot-photo,
  #ppi-public-sphere .ppi-hotspot-person .ppi-hotspot-photo {
    opacity:0!important;
    visibility:hidden!important;
    pointer-events:none!important;
    transform:translate(-7px,0) scale(.95)!important;
  }
  .ppi-preview-sphere .ppi-hotspot-person.ppi-hotspot-preview-open .ppi-hotspot-photo,
  #ppi-public-sphere .ppi-hotspot-person.ppi-hotspot-preview-open .ppi-hotspot-photo {
    opacity:1!important;
    visibility:visible!important;
    pointer-events:auto!important;
    transform:translate(0,0) scale(1)!important;
  }
  /* The classic stick figure should not reserve an invisible image slot. */
  .ppi-preview-sphere .ppi-hotspot-person:not(.ppi-hotspot-blue):not(.ppi-hotspot-map-pin):not(.ppi-hotspot-chat-pin):not(.ppi-hotspot-label-pin):not(.ppi-hotspot-outline-pin) .ppi-hotspot-photo,
  #ppi-public-sphere .ppi-hotspot-person:not(.ppi-hotspot-blue):not(.ppi-hotspot-map-pin):not(.ppi-hotspot-chat-pin):not(.ppi-hotspot-label-pin):not(.ppi-hotspot-outline-pin) .ppi-hotspot-photo {
    position:absolute!important;
    left:calc(50% + 30px);
    top:0;
    z-index:5;
    transition:opacity .2s ease,visibility .2s,transform .2s ease;
  }
  .ppi-preview-sphere .ppi-hotspot-person:not(.ppi-hotspot-blue):not(.ppi-hotspot-map-pin):not(.ppi-hotspot-chat-pin):not(.ppi-hotspot-label-pin):not(.ppi-hotspot-outline-pin) .ppi-hotspot-figure,
  #ppi-public-sphere .ppi-hotspot-person:not(.ppi-hotspot-blue):not(.ppi-hotspot-map-pin):not(.ppi-hotspot-chat-pin):not(.ppi-hotspot-label-pin):not(.ppi-hotspot-outline-pin) .ppi-hotspot-figure {margin-top:20px;}
}

/* 0.4.62.35: subtle contour shadow for SVG pin masks only.
   No rectangular background or shadow on the preview thumbnail. */
#ppi-tours-app .ppi-preview-window .ppi-hotspot-map-pin .ppi-hotspot-pin-figure,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-chat-pin .ppi-hotspot-pin-figure,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-label-pin .ppi-hotspot-pin-figure,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-outline-pin .ppi-hotspot-pin-figure{
  filter:drop-shadow(0 3px 3px rgba(0,0,0,.48))!important;
}

/* 0.4.62.40: SVG pin shadow (correct pseudo-element selectors). Blur the outer unmasked shadow layer,
   while its pseudo-element carries the SVG silhouette. This produces a soft
   edge; JS translates this layer in sync with the double-hop pin animation. */
#ppi-tours-app .ppi-preview-window .ppi-hotspot-pin-shadow{display:none!important;}
#ppi-tours-app .ppi-preview-window .ppi-hotspot-map-pin .ppi-hotspot-pin-shadow,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-chat-pin .ppi-hotspot-pin-shadow,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-label-pin .ppi-hotspot-pin-shadow,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-outline-pin .ppi-hotspot-pin-shadow{
  display:block!important;
  position:absolute;left:50%;bottom:0;
  width:76px!important;height:100px!important;max-width:none!important;
  box-sizing:border-box;border:0!important;padding:0!important;
  border-radius:0!important;background:transparent!important;
  -webkit-mask-image:none!important;mask-image:none!important;
  transform:translateX(-50%) translateY(3px);
  filter:blur(4px)!important;
  opacity:.85;
  z-index:0;pointer-events:none;
}
#ppi-tours-app .ppi-preview-window .ppi-hotspot-map-pin .ppi-hotspot-pin-shadow::before,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-chat-pin .ppi-hotspot-pin-shadow::before,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-label-pin .ppi-hotspot-pin-shadow::before,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-outline-pin .ppi-hotspot-pin-shadow::before{
  content:"";position:absolute;inset:0;
  background:rgba(0,0,0,.8);
  -webkit-mask-image:var(--ppi-shadow-mask,none);
  mask-image:var(--ppi-shadow-mask,none);
  -webkit-mask-position:center;-webkit-mask-size:contain;-webkit-mask-repeat:no-repeat;
  mask-position:center;mask-size:contain;mask-repeat:no-repeat;
}
#ppi-tours-app .ppi-preview-window .ppi-hotspot-map-pin .ppi-hotspot-pin-figure,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-chat-pin .ppi-hotspot-pin-figure,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-label-pin .ppi-hotspot-pin-figure,
#ppi-tours-app .ppi-preview-window .ppi-hotspot-outline-pin .ppi-hotspot-pin-figure{
  z-index:1;filter:none!important;
}

/* 0.4.62.42 – SVG pins: destination thumbnail lives inside the pin;
   first click zooms it out, second click enters the destination panorama.
   No visual or interaction changes to the blue guide / classic figure. */
.ppi-preview-sphere .ppi-hotspot-pin-mini,
#ppi-public-sphere .ppi-hotspot-pin-mini{
  display:none!important;
}
.ppi-preview-sphere .ppi-hotspot-map-pin .ppi-hotspot-pin-mini,
.ppi-preview-sphere .ppi-hotspot-label-pin .ppi-hotspot-pin-mini,
.ppi-preview-sphere .ppi-hotspot-outline-pin .ppi-hotspot-pin-mini,
#ppi-public-sphere .ppi-hotspot-map-pin .ppi-hotspot-pin-mini,
#ppi-public-sphere .ppi-hotspot-label-pin .ppi-hotspot-pin-mini,
#ppi-public-sphere .ppi-hotspot-outline-pin .ppi-hotspot-pin-mini{
  display:block!important;position:absolute;z-index:2;pointer-events:none;
  left:50%;bottom:0;width:76px;height:100px;
  max-width:none;transform:translateX(-50%);
}
.ppi-preview-sphere .ppi-hotspot-pin-mini img,
#ppi-public-sphere .ppi-hotspot-pin-mini img{
  position:absolute;display:block;width:100%;height:100%;object-fit:cover;
  max-width:none;max-height:none;background:transparent!important;
}
/* A clear opening inset from each icon's outline – no opaque rectangle. */
.ppi-preview-sphere .ppi-hotspot-map-pin .ppi-hotspot-pin-mini img,
#ppi-public-sphere .ppi-hotspot-map-pin .ppi-hotspot-pin-mini img{
  left:29%;top:18%;width:42%;height:34%;border-radius:50%;
}
.ppi-preview-sphere .ppi-hotspot-label-pin .ppi-hotspot-pin-mini img,
#ppi-public-sphere .ppi-hotspot-label-pin .ppi-hotspot-pin-mini img{
  left:23%;top:15%;width:54%;height:42%;border-radius:8px;
}
.ppi-preview-sphere .ppi-hotspot-outline-pin .ppi-hotspot-pin-mini img,
#ppi-public-sphere .ppi-hotspot-outline-pin .ppi-hotspot-pin-mini img{
  left:26%;top:15%;width:48%;height:37%;border-radius:50%;
}
/* Override older hover and touch-auto-show styles. Only an explicit click
   opens the SVG's enlarged preview; it grows from the centre of the pin. */
.ppi-preview-sphere .ppi-hotspot-map-pin .ppi-hotspot-photo,
.ppi-preview-sphere .ppi-hotspot-label-pin .ppi-hotspot-photo,
.ppi-preview-sphere .ppi-hotspot-outline-pin .ppi-hotspot-photo,
#ppi-public-sphere .ppi-hotspot-map-pin .ppi-hotspot-photo,
#ppi-public-sphere .ppi-hotspot-label-pin .ppi-hotspot-photo,
#ppi-public-sphere .ppi-hotspot-outline-pin .ppi-hotspot-photo{
  position:absolute!important;left:50%!important;top:-92px!important;
  width:185px!important;height:111px!important;box-sizing:border-box!important;
  z-index:10!important;opacity:0!important;visibility:hidden!important;
  pointer-events:none!important;transform-origin:50% 90%!important;
  transform:translate(-50%,54px) scale(.19)!important;
  border:2px solid #fff!important;border-radius:11px!important;
  background:#142b43!important;box-shadow:0 9px 23px rgba(0,0,0,.5)!important;
  transition:transform .46s cubic-bezier(.22,1,.36,1),opacity .2s ease,visibility .2s!important;
}
.ppi-preview-sphere .ppi-hotspot-map-pin.ppi-hotspot-preview-open .ppi-hotspot-photo,
.ppi-preview-sphere .ppi-hotspot-label-pin.ppi-hotspot-preview-open .ppi-hotspot-photo,
.ppi-preview-sphere .ppi-hotspot-outline-pin.ppi-hotspot-preview-open .ppi-hotspot-photo,
#ppi-public-sphere .ppi-hotspot-map-pin.ppi-hotspot-preview-open .ppi-hotspot-photo,
#ppi-public-sphere .ppi-hotspot-label-pin.ppi-hotspot-preview-open .ppi-hotspot-photo,
#ppi-public-sphere .ppi-hotspot-outline-pin.ppi-hotspot-preview-open .ppi-hotspot-photo{
  opacity:1!important;visibility:visible!important;pointer-events:auto!important;
  transform:translate(-50%,-7px) scale(1)!important;
}
@media(prefers-reduced-motion:reduce){
  .ppi-preview-sphere .ppi-hotspot-map-pin .ppi-hotspot-photo,
  .ppi-preview-sphere .ppi-hotspot-label-pin .ppi-hotspot-photo,
  .ppi-preview-sphere .ppi-hotspot-outline-pin .ppi-hotspot-photo,
  #ppi-public-sphere .ppi-hotspot-map-pin .ppi-hotspot-photo,
  #ppi-public-sphere .ppi-hotspot-label-pin .ppi-hotspot-photo,
  #ppi-public-sphere .ppi-hotspot-outline-pin .ppi-hotspot-photo{
    transition:none!important;
  }
}

/* 0.4.62.44: Layer the inset panorama beneath the pin's colored outline,
   preserving the hollow SVG opening, shadow and shared hop animation. */
.ppi-preview-sphere .ppi-hotspot-map-pin .ppi-hotspot-pin-figure,
.ppi-preview-sphere .ppi-hotspot-label-pin .ppi-hotspot-pin-figure,
.ppi-preview-sphere .ppi-hotspot-outline-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-map-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-label-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-outline-pin .ppi-hotspot-pin-figure{
  z-index:3!important;
}
.ppi-preview-sphere .ppi-hotspot-map-pin .ppi-hotspot-pin-mini,
.ppi-preview-sphere .ppi-hotspot-label-pin .ppi-hotspot-pin-mini,
.ppi-preview-sphere .ppi-hotspot-outline-pin .ppi-hotspot-pin-mini,
#ppi-public-sphere .ppi-hotspot-map-pin .ppi-hotspot-pin-mini,
#ppi-public-sphere .ppi-hotspot-label-pin .ppi-hotspot-pin-mini,
#ppi-public-sphere .ppi-hotspot-outline-pin .ppi-hotspot-pin-mini{
  z-index:2!important;
}

/* 0.4.62.45: Slightly thicker enlarged SVG preview border only on desktop.
   Touch/coarse-pointer devices retain the approved 2px border. */
@media (hover: hover) and (pointer: fine) {
  .ppi-preview-sphere .ppi-hotspot-map-pin .ppi-hotspot-photo,
  .ppi-preview-sphere .ppi-hotspot-label-pin .ppi-hotspot-photo,
  .ppi-preview-sphere .ppi-hotspot-outline-pin .ppi-hotspot-photo,
  #ppi-public-sphere .ppi-hotspot-map-pin .ppi-hotspot-photo,
  #ppi-public-sphere .ppi-hotspot-label-pin .ppi-hotspot-photo,
  #ppi-public-sphere .ppi-hotspot-outline-pin .ppi-hotspot-photo {
    border-width:3px!important;
  }
}


/* 0.4.62.51: Hide the classic guide's arms/legs under SVG pins in the
   editor. The public tour was already rendering these correctly. The hidden
   attribute is also set in tour-sphere.js as a browser/theme-safe fallback. */
#ppi-tours-app .ppi-preview-sphere .ppi-hotspot .ppi-hotspot-figure[hidden],
#ppi-tours-app .ppi-preview-sphere .ppi-hotspot .ppi-hotspot-blue-figure[hidden],
#ppi-tours-app .ppi-preview-sphere .ppi-hotspot-map-pin > .ppi-hotspot-figure,
#ppi-tours-app .ppi-preview-sphere .ppi-hotspot-label-pin > .ppi-hotspot-figure,
#ppi-tours-app .ppi-preview-sphere .ppi-hotspot-outline-pin > .ppi-hotspot-figure{
  display:none!important;
}
/* 0.4.62.53: Avoid a second copy of the SVG mask underneath editor pins.
   The pin SVGs have square viewBoxes while the masked surface is 76 x 100px;
   repeat on either mask layer produces the upper arch again below the tip.
   Scope the override to the editor only; the public tour is unchanged. */
#ppi-tours-app .ppi-preview-sphere .ppi-hotspot-map-pin > .ppi-hotspot-pin-figure,
#ppi-tours-app .ppi-preview-sphere .ppi-hotspot-label-pin > .ppi-hotspot-pin-figure,
#ppi-tours-app .ppi-preview-sphere .ppi-hotspot-outline-pin > .ppi-hotspot-pin-figure,
#ppi-tours-app .ppi-preview-sphere .ppi-hotspot-map-pin > .ppi-hotspot-pin-shadow::before,
#ppi-tours-app .ppi-preview-sphere .ppi-hotspot-label-pin > .ppi-hotspot-pin-shadow::before,
#ppi-tours-app .ppi-preview-sphere .ppi-hotspot-outline-pin > .ppi-hotspot-pin-shadow::before {
  -webkit-mask-repeat:no-repeat!important;
  mask-repeat:no-repeat!important;
  -webkit-mask-position:center center!important;
  mask-position:center center!important;
  -webkit-mask-size:contain!important;
  mask-size:contain!important;
}

/* 0.4.62.54 – unified SVG pin mask geometry. The confirmed Firefox fix
   prevents tiling under the pin and aligns the inset destination preview.
   Kept in CSS as well as JS for immediately correct initial paint. */
#ppi-preview-sphere .ppi-hotspot-map-pin .ppi-hotspot-pin-figure,
#ppi-preview-sphere .ppi-hotspot-label-pin .ppi-hotspot-pin-figure,
#ppi-preview-sphere .ppi-hotspot-outline-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-map-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-label-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-outline-pin .ppi-hotspot-pin-figure {
  -webkit-mask-repeat:no-repeat!important;
  mask-repeat:no-repeat!important;
  -webkit-mask-size:100% 100%!important;
  mask-size:100% 100%!important;
  -webkit-mask-position:center!important;
  mask-position:center!important;
}

/* 0.4.62.56: four compact cards on the first presentation row.
   The tour title stays above; previews have identical heights. */
#ppi-tours-app #ppi-step-design .ppi-design-name-row{margin:12px 0 10px}
#ppi-tours-app #ppi-step-design .ppi-design-name-row .ppi-design-name{width:100%;box-sizing:border-box}
#ppi-tours-app #ppi-step-design .ppi-design-top{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;align-items:stretch;margin:0 0 10px}
#ppi-tours-app #ppi-step-design .ppi-design-top>.ppi-design-mini{min-width:0;display:flex;flex-direction:column;gap:7px;align-self:stretch}
#ppi-tours-app #ppi-step-design .ppi-design-top .ppi-design-mini-image{height:74px;min-height:74px;box-sizing:border-box}
#ppi-tours-app #ppi-step-design .ppi-design-top .ppi-design-mini>label:first-child{font-size:12px;font-weight:650;line-height:18px}
#ppi-tours-app #ppi-step-design #ppi-design-pin-colors{display:flex!important}
#ppi-tours-app #ppi-step-design .ppi-design-color-preview{position:relative;cursor:pointer}
#ppi-tours-app #ppi-step-design #ppi-design-color-chip{display:block;position:absolute;inset:0;background:var(--ppi-svg-pin-color,#1687bc);border-radius:6px}
#ppi-tours-app #ppi-step-design #ppi-design-pin-color{position:absolute;inset:0;width:100%;height:100%;padding:0;opacity:0;cursor:pointer;z-index:1}
#ppi-tours-app #ppi-step-design #ppi-design-pin-color:focus-visible{opacity:.25;outline:2px solid var(--color-primary-element);outline-offset:-3px}
#ppi-tours-app #ppi-step-design .ppi-svg-color-swatches{display:flex;flex-wrap:nowrap;gap:5px;justify-content:space-between;margin:0;min-height:27px}
#ppi-tours-app #ppi-step-design .ppi-svg-color-swatches button{width:24px;height:24px;min-width:0;min-height:24px;flex:1 1 0;max-width:26px;border-radius:50%}
#ppi-tours-app #ppi-step-design #ppi-design-pin-color-hex{display:block;text-align:center;font-size:11px;line-height:17px}
#ppi-tours-app #ppi-step-design .ppi-design-color-note{margin:0;text-align:center;font-size:10px;opacity:.75}
#ppi-tours-app #ppi-step-design .ppi-design-watermark-preview{position:relative;background:repeating-conic-gradient(#d8dce0 0% 25%,#fff 0% 50%) 50%/16px 16px}
#ppi-tours-app #ppi-step-design .ppi-design-watermark-preview>img{width:100%;height:100%;object-fit:contain;padding:5px;box-sizing:border-box;position:relative;z-index:1}
#ppi-tours-app #ppi-step-design .ppi-design-watermark-preview>img[hidden]{display:none!important}
#ppi-tours-app #ppi-step-design #ppi-design-watermark-placeholder{font-size:11px;text-align:center;background:var(--color-main-background);padding:3px 6px;border-radius:5px;color:var(--color-text-maxcontrast);position:relative}
#ppi-tours-app #ppi-step-design #ppi-design-watermark-placeholder[hidden]{display:none!important}
#ppi-tours-app #ppi-step-design .ppi-design-watermark-toggle{display:flex;align-items:center;gap:6px;font-size:12px!important;line-height:22px;min-height:23px}
#ppi-tours-app #ppi-step-design .ppi-design-watermark-toggle input{margin:0}
#ppi-tours-app #ppi-step-design #ppi-design-watermark-choice{width:100%;min-width:0}
#ppi-tours-app #ppi-step-design #ppi-design-watermark-choice[hidden]{display:none!important}
#ppi-tours-app #ppi-step-design #ppi-design-watermark-choice select{box-sizing:border-box;max-width:100%;width:100%;min-width:0;min-height:36px}
#ppi-tours-app #ppi-step-design .ppi-design-watermark-info{font-size:10px;line-height:1.35;margin:0;overflow-wrap:anywhere;color:var(--color-text-maxcontrast)}
@media(max-width:800px){#ppi-tours-app #ppi-step-design .ppi-design-top{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:440px){#ppi-tours-app #ppi-step-design .ppi-design-top{grid-template-columns:minmax(0,1fr)}}

/* 0.4.62.57 – aligned presentation cards with framed previews. Keep native
   form elements and IDs unchanged so all persistence and permissions survive. */
#ppi-tours-app #ppi-step-design .ppi-design-top{
  grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;align-items:stretch;
}
#ppi-tours-app #ppi-step-design .ppi-design-top>.ppi-design-mini{
  display:flex;flex-direction:column;align-self:stretch;box-sizing:border-box;
  padding:10px;gap:9px;min-height:223px;
  border:1px solid var(--color-border,#d6d6d6);
  border-radius:10px;background:var(--color-main-background,#fff);
}
#ppi-tours-app #ppi-step-design .ppi-design-top .ppi-design-mini>label:first-child{
  display:flex;align-items:center;gap:5px;min-height:18px;
  font-size:12px;font-weight:650;line-height:18px;margin:0;
}
#ppi-tours-app #ppi-step-design .ppi-design-info-mark{
  font-size:12px;font-weight:400;color:var(--color-text-maxcontrast,#666);cursor:help;
}
#ppi-tours-app #ppi-step-design .ppi-design-top .ppi-design-mini-image{
  position:relative;flex:0 0 95px;height:95px!important;min-height:95px!important;
  max-height:95px;border:1px solid #333!important;border-radius:7px;
  box-sizing:border-box;overflow:hidden;background:var(--color-background-dark,#eee);
}
#ppi-tours-app #ppi-step-design .ppi-design-top .ppi-design-mini-image>img{
  max-width:100%;max-height:100%;
}
#ppi-tours-app #ppi-step-design .ppi-design-top .ppi-design-mini>select{
  min-height:36px;margin-top:auto;width:100%;
}
#ppi-tours-app #ppi-step-design .ppi-design-color-preview{background:transparent!important}
#ppi-tours-app #ppi-step-design .ppi-design-color-preview #ppi-design-color-chip{
  position:absolute;display:block!important;inset:0!important;z-index:0;
  width:100%;height:100%;border-radius:0;
  background-color:var(--ppi-svg-pin-color,#1687bc);
}
#ppi-tours-app #ppi-step-design .ppi-design-color-preview #ppi-design-pin-color{z-index:2}
#ppi-tours-app #ppi-step-design .ppi-svg-color-swatches{
  margin-top:auto;min-height:26px;gap:5px;
}
#ppi-tours-app #ppi-step-design .ppi-svg-color-swatches button{
  width:25px;height:25px;min-height:25px;
  border:1px solid #555!important;
  box-shadow:0 0 0 1px #00000012;
}
#ppi-tours-app #ppi-step-design .ppi-svg-color-swatches button[data-pin-color="#ffffff"]{
  border-color:#444!important;
}
#ppi-tours-app #ppi-step-design #ppi-design-pin-color-hex{
  font-size:11px;line-height:16px;min-height:16px;
}
#ppi-tours-app #ppi-step-design .ppi-design-color-note{
  display:none;
}
#ppi-tours-app #ppi-step-design .ppi-design-watermark-preview{
  background:repeating-conic-gradient(#d9dde0 0% 25%,#fff 0% 50%) 50%/16px 16px;
}
#ppi-tours-app #ppi-step-design .ppi-design-watermark-preview>img{
  object-fit:contain;padding:5px;
}
#ppi-tours-app #ppi-step-design .ppi-design-watermark-toggle{
  min-height:22px;flex:none;margin:0;
}
#ppi-tours-app #ppi-step-design #ppi-design-watermark-choice{
  margin-top:auto;
}
/* Keep live accessibility/status hints available without consuming an entire
   additional row. The checkbox and selectable library remain in place. */
#ppi-tours-app #ppi-step-design .ppi-design-watermark-info{
  position:absolute;width:1px;height:1px;padding:0;margin:-1px;
  overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;
}
#ppi-tours-app #ppi-step-design .ppi-design-hotspot-preview>img{
  max-height:90px;
}
@media(max-width:800px){
  #ppi-tours-app #ppi-step-design .ppi-design-top{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(max-width:440px){
  #ppi-tours-app #ppi-step-design .ppi-design-top{grid-template-columns:minmax(0,1fr)}
}


/* 0.4.62.58 – larger four-card headings, watermark toggle alongside title,
   readable HEX value above the preset swatches. IDs and save flow unchanged. */
#ppi-tours-app #ppi-step-design .ppi-design-top .ppi-design-mini>label:first-child,
#ppi-tours-app #ppi-step-design .ppi-design-watermark-heading>label:first-child{
  font-size:14px;font-weight:700;line-height:20px;min-height:20px;
}
#ppi-tours-app #ppi-step-design .ppi-design-watermark-heading{
  display:flex;align-items:center;justify-content:space-between;gap:5px;min-height:20px;
}
#ppi-tours-app #ppi-step-design .ppi-design-watermark-heading>label:first-child{
  display:inline-flex;align-items:center;gap:4px;min-width:0;margin:0;
}
#ppi-tours-app #ppi-step-design .ppi-design-watermark-heading .ppi-design-watermark-toggle{
  display:inline-flex;align-items:center;justify-content:flex-end;flex-shrink:0;
  min-height:20px;gap:4px;margin:0;font-size:12px!important;line-height:20px;
  white-space:nowrap;cursor:pointer;
}
#ppi-tours-app #ppi-step-design #ppi-design-pin-color-hex{
  font-size:17px;font-weight:700;line-height:24px;min-height:24px;
  letter-spacing:.035em;text-align:center;margin:1px 0 0;
}
#ppi-tours-app #ppi-step-design .ppi-svg-color-swatches{
  margin-top:0;
}
@media(max-width:440px){
  #ppi-tours-app #ppi-step-design .ppi-design-watermark-heading{flex-wrap:wrap;}
}

/* 0.4.62.59 – keep the watermark thumbnail level with the other cards.
   The watermark has a composite heading (title + checkbox), unlike the other
   cards' plain labels. Reserve the exact same heading row height for both. */
#ppi-tours-app #ppi-step-design .ppi-design-top .ppi-design-watermark-heading{
  box-sizing:border-box!important;
  height:20px!important;
  min-height:20px!important;
  max-height:20px!important;
  flex:0 0 20px!important;
  padding:0!important;
  margin:0!important;
  align-self:stretch;
}
#ppi-tours-app #ppi-step-design .ppi-design-top .ppi-design-watermark-heading>label{
  box-sizing:border-box;
  height:20px;
  margin:0!important;
  padding:0!important;
}
#ppi-tours-app #ppi-step-design .ppi-design-top .ppi-design-watermark-preview{
  margin-top:0!important;
  align-self:stretch;
}

/* Compact safe-formatting toolbar for tour landing descriptions. */
#ppi-tours-app #ppi-step-design .ppi-intro-toolbar{display:flex;gap:6px;flex-wrap:wrap;margin:6px 0 4px}
#ppi-tours-app #ppi-step-design .ppi-intro-toolbar button,
#ppi-tours-app #ppi-step-design .ppi-intro-icons button{min-height:32px;min-width:0;width:auto;padding:5px 10px;font-size:12px;border:1px solid var(--color-border);border-radius:7px;background:var(--color-background-hover);color:var(--color-main-text);cursor:pointer}
#ppi-tours-app #ppi-step-design .ppi-intro-toolbar button:hover,
#ppi-tours-app #ppi-step-design .ppi-intro-icons button:hover{border-color:var(--color-primary-element)}
#ppi-tours-app #ppi-step-design .ppi-intro-icons{display:block;max-height:270px;overflow-y:auto;padding:10px;border:1px solid var(--color-border);border-radius:9px;background:var(--color-background-hover);margin:0 0 7px}
#ppi-tours-app #ppi-step-design .ppi-intro-icons[hidden]{display:none!important}
#ppi-tours-app #ppi-step-design .ppi-intro-icon-group+.ppi-intro-icon-group{margin-top:12px}
#ppi-tours-app #ppi-step-design .ppi-intro-icon-group>strong{display:block;font-size:12px;margin:0 0 6px;color:var(--color-text-maxcontrast)}
#ppi-tours-app #ppi-step-design .ppi-intro-icon-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(42px,1fr));gap:6px}
#ppi-tours-app #ppi-step-design .ppi-intro-icon-grid button{display:flex;align-items:center;justify-content:center;min-height:42px;height:42px;width:100%;min-width:0;padding:4px;font-size:24px;line-height:1.1;background:var(--color-main-background);border:1px solid var(--color-border);border-radius:8px;cursor:pointer}
#ppi-tours-app #ppi-step-design .ppi-intro-icon-grid button:hover,#ppi-tours-app #ppi-step-design .ppi-intro-icon-grid button:focus-visible{border-color:var(--color-primary-element);outline:1px solid var(--color-primary-element)}
#ppi-tours-app #ppi-step-design .ppi-intro-icon-grid svg{width:31px;height:24px;fill:none;stroke:currentColor;stroke-width:3;stroke-linecap:round;stroke-linejoin:round}
#ppi-tours-app #ppi-step-design .ppi-intro-360-badge{display:inline-flex;align-items:center;justify-content:center;border:2px solid currentColor;border-radius:50%;font-weight:800;font-size:13px;letter-spacing:-.04em;width:33px;height:30px}

/* 0.4.62.106: gallery background, readable preview cards, distinct header and selection depth */
#ppi-tours-app .ppi-startpage-live-preview[data-layout="gallery"]{
  background-color:var(--ppi-startpage-bg,#000000)!important;
  background-image:none!important;
  color:#fff!important;
  padding:12px 14px 24px!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="gallery"] .ppi-startpage-preview-header{
  padding:10px 6px 16px!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="gallery"] .ppi-startpage-preview-header h2{
  color:var(--ppi-startpage-heading,#ffffff)!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="gallery"] .ppi-startpage-preview-header p{
  color:var(--ppi-startpage-description,#ffffff)!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="gallery"] .ppi-startpage-preview-empty{
  color:var(--ppi-startpage-description,#ffffff)!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"] .ppi-startpage-preview-header{
  position:relative;z-index:1;
  box-shadow:0 8px 14px rgba(0,0,0,.28)!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"] .ppi-startpage-preview-grid{
  position:relative;z-index:0;
}
#ppi-tours-app .ppi-startpage-tour-option{
  background:linear-gradient(125deg,var(--color-background-hover,#f4f5f7),var(--color-main-background,#fff))!important;
  box-shadow:0 3px 10px rgba(0,0,0,.12)!important;
  transition:box-shadow .15s ease,transform .15s ease;
}
#ppi-tours-app .ppi-startpage-tour-option:hover,
#ppi-tours-app .ppi-startpage-tour-option:focus-within{
  box-shadow:0 5px 16px rgba(0,0,0,.19)!important;
}
#ppi-tours-app .ppi-startpage-preview-tile{
  display:flex!important;flex-direction:column!important;
  min-width:0;min-height:0;overflow:hidden!important;
  border:3px solid #fff!important;
  border-radius:10px!important;
  box-shadow:0 5px 16px rgba(0,0,0,.42)!important;
  background:#fff!important;
  color:#192b37!important;
}
#ppi-tours-app .ppi-startpage-preview-tile > img{
  display:block!important;flex:none!important;
  width:100%!important;max-width:100%!important;height:auto!important;
  aspect-ratio:16/9!important;object-fit:cover!important;
  border-radius:6px 6px 0 0!important;
}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-copy{
  display:block!important;flex:1 0 auto!important;
  position:static!important;visibility:visible!important;
  background:#fff!important;color:#192b37!important;
  min-height:52px!important;padding:9px!important;
  text-align:left!important;box-sizing:border-box!important;
}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-copy strong{
  display:block!important;color:#192b37!important;
  font-weight:700!important;font-size:13px!important;line-height:1.35!important;
  padding:0 0 4px!important;white-space:normal!important;overflow-wrap:anywhere!important;
}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-copy small{
  display:-webkit-box!important;-webkit-box-orient:vertical!important;
  -webkit-line-clamp:2;overflow:hidden!important;
  color:#4e5b68!important;font-size:11px!important;line-height:1.4!important;
  padding:0!important;white-space:normal!important;
}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="3"]{grid-template-columns:repeat(3,minmax(0,1fr))}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="4"]{grid-template-columns:repeat(4,minmax(0,1fr))}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="5"]{grid-template-columns:repeat(5,minmax(0,1fr))}
@media(max-width:800px){
 #ppi-tours-app .ppi-startpage-preview-grid[data-cols="3"],
 #ppi-tours-app .ppi-startpage-preview-grid[data-cols="4"],
 #ppi-tours-app .ppi-startpage-preview-grid[data-cols="5"]{
   grid-template-columns:repeat(2,minmax(0,1fr))!important;
 }
}
@media(max-width:420px){
 #ppi-tours-app .ppi-startpage-preview-grid[data-cols]{grid-template-columns:1fr!important}
}


/* 0.4.62.108: selectable editorial / glassmorphism presets, usable on any background. */
#ppi-tours-app .ppi-startpage-effect-picker{min-width:0;padding:12px;border:1px solid var(--color-border);border-radius:11px}
#ppi-tours-app .ppi-startpage-effect-picker p{font-size:12px;line-height:1.5;margin:5px 0 10px;color:var(--color-text-maxcontrast)}
#ppi-tours-app .ppi-startpage-effect-options{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
#ppi-tours-app .ppi-startpage-effect-options button{display:flex;flex-direction:column;align-items:center;gap:7px;padding:10px!important;border:2px solid var(--color-border)!important;border-radius:11px!important;background:var(--color-main-background)!important;color:var(--color-main-text)!important;font-weight:700;min-width:0;cursor:pointer}
#ppi-tours-app .ppi-startpage-effect-options button[aria-pressed="true"]{border-color:var(--color-primary-element)!important;box-shadow:0 0 0 1px var(--color-primary-element)}
#ppi-tours-app .ppi-startpage-effect-options button:focus-visible{outline:3px solid var(--color-primary-element);outline-offset:3px}
#ppi-tours-app .ppi-effect-mini{display:grid;place-items:center;width:96px;height:52px;max-width:100%;border-radius:8px;background:linear-gradient(130deg,#142335,#4c7891);overflow:hidden}
#ppi-tours-app .ppi-effect-mini i{display:grid;place-items:center;width:68px;height:32px;font-size:16px;font-style:normal;color:#fff;text-shadow:0 2px 3px #0008}
#ppi-tours-app .ppi-effect-mini-glass i{background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.45);border-radius:8px;box-shadow:0 5px 12px #0004;backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px)}
#ppi-tours-app .ppi-startpage-preview-header{
  --ppi-startpage-shadow:rgba(0,0,0,.40);
  --ppi-startpage-shadow-soft:rgba(0,0,0,.20);
  --ppi-startpage-stroke:rgba(0,0,0,.40);
}
#ppi-tours-app .ppi-startpage-preview-header h2{
  -webkit-text-stroke:.45px var(--ppi-startpage-stroke)!important;
  paint-order:stroke fill;
  text-shadow:0 2px 5px var(--ppi-startpage-shadow),0 1px 2px var(--ppi-startpage-shadow)!important;
}
#ppi-tours-app .ppi-startpage-preview-header p{
  text-shadow:0 1px 3px var(--ppi-startpage-shadow-soft)!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-effect="subtle"] .ppi-startpage-preview-header{
  border-radius:0;background-color:transparent;
  backdrop-filter:none;-webkit-backdrop-filter:none;
}
#ppi-tours-app .ppi-startpage-live-preview[data-effect="glass"] .ppi-startpage-preview-header{
  position:relative;isolation:isolate;
  width:calc(100% - 16px);max-width:100%;box-sizing:border-box;
  margin:10px auto 16px!important;padding:24px 15px!important;
  border:1px solid rgba(255,255,255,.42)!important;
  border-radius:16px!important;
  background:rgba(25,39,54,.27)!important;
  box-shadow:0 12px 28px rgba(0,0,0,.24),inset 0 1px 0 rgba(255,255,255,.22)!important;
  backdrop-filter:blur(12px) saturate(125%);-webkit-backdrop-filter:blur(12px) saturate(125%);
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"][data-effect="glass"] .ppi-startpage-preview-header{
  margin:12px auto 20px!important;
  box-shadow:0 10px 22px rgba(0,0,0,.26),inset 0 1px 0 rgba(255,255,255,.22)!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="gallery"][data-effect="glass"] .ppi-startpage-preview-header{
  background:rgba(255,255,255,.10)!important;
}
@media(prefers-reduced-transparency:reduce){
  #ppi-tours-app .ppi-startpage-live-preview[data-effect="glass"] .ppi-startpage-preview-header{
    backdrop-filter:none;-webkit-backdrop-filter:none;background:rgba(17,25,32,.86)!important;
  }
}


/* Glass keeps the chosen Nextcloud background image in gallery mode too. */
#ppi-tours-app .ppi-startpage-live-preview[data-layout="gallery"][data-effect="glass"] {
  background-color:var(--ppi-startpage-bg,#000)!important;
  background-image:linear-gradient(#0005,#0005),var(--ppi-startpage-image,none)!important;
  background-size:cover!important;
  background-position:center!important;
}
#ppi-tours-app .ppi-startpage-editor-panel[data-startpage-panel="share"] input{width:100%;max-width:680px}

/* 0.4.62.110: preserve chosen overview gradient under decorative icon. */
#ppi-tours-app #ppi-start-pages-list .ppi-startpage-overview-cover{
  background-position:center!important;
  background-size:cover!important;
  isolation:isolate;
}
#ppi-tours-app #ppi-start-pages-list .ppi-startpage-overview-cover .ppi-overview-placeholder{
  position:relative;
  background:none!important;
  background-color:transparent!important;
  color:rgba(255,255,255,.82)!important;
  text-shadow:0 2px 8px rgba(0,0,0,.45);
  height:150px;
}

/* 0.4.62.111: full-width share settings and subtler typographic hierarchy. */
#ppi-tours-app .ppi-startpage-workspace.ppi-startpage-share-only {
  grid-template-columns:minmax(0,1fr)!important;
}
#ppi-tours-app .ppi-startpage-workspace.ppi-startpage-share-only .ppi-startpage-preview-column,
#ppi-tours-app .ppi-startpage-preview-column[hidden] {display:none!important}
#ppi-tours-app .ppi-startpage-workspace.ppi-startpage-share-only .ppi-start-page-fields {
  max-width:920px;width:100%;
}
#ppi-tours-app .ppi-startpage-preview-header h2 {
  -webkit-text-stroke:.35px var(--ppi-startpage-stroke)!important;
  text-shadow:0 2px 4px rgba(0,0,0,.40)!important;
}
#ppi-tours-app .ppi-startpage-preview-header p {
  -webkit-text-stroke:0!important;
  text-shadow:0 1px 2px rgba(0,0,0,.20)!important;
}

/* 0.4.62.112: split preview mirrors the real guest page: backdrop on the
   hero container, frosted glass on a separate child, user's color below. */
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"] {
  background-color:var(--ppi-startpage-bg,#000000)!important;
  background-image:none!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"] .ppi-startpage-preview-hero {
  display:grid;align-items:center;justify-items:center;
  min-height:170px;padding:16px 12px;
  background-color:var(--ppi-startpage-bg,#000000)!important;
  background-image:linear-gradient(rgba(0,0,0,.32),rgba(0,0,0,.32)),var(--ppi-startpage-image,none)!important;
  background-size:cover!important;background-position:center!important;
  box-shadow:0 8px 18px rgba(0,0,0,.30)!important;
  isolation:isolate;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"] .ppi-startpage-preview-header {
  width:100%;max-width:100%;min-height:0;
  margin:0!important;padding:20px 14px!important;
  background:transparent!important;background-image:none!important;
  box-shadow:none!important;border-color:transparent!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"][data-effect="glass"] .ppi-startpage-preview-header {
  width:calc(100% - 8px);margin:0!important;padding:22px 16px!important;
  background:rgba(25,39,54,.27)!important;background-image:none!important;
  border:1px solid rgba(255,255,255,.42)!important;
  border-radius:16px!important;
  box-shadow:0 12px 28px rgba(0,0,0,.24),inset 0 1px 0 rgba(255,255,255,.22)!important;
  backdrop-filter:blur(12px) saturate(125%);
  -webkit-backdrop-filter:blur(12px) saturate(125%);
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"] .ppi-startpage-preview-grid {
  margin:24px 15px 10px!important;
}
#ppi-tours-app .ppi-startpage-preview-header h2 {
  -webkit-text-stroke:.65px var(--ppi-startpage-stroke)!important;
  paint-order:stroke fill;
  text-shadow:0 2px 4px rgba(0,0,0,.40)!important;
}
#ppi-tours-app .ppi-startpage-preview-header p {
  -webkit-text-stroke:0!important;
  text-shadow:0 1px 2px rgba(0,0,0,.20)!important;
}
@media(prefers-reduced-transparency:reduce){
  #ppi-tours-app .ppi-startpage-live-preview[data-layout="split"][data-effect="glass"] .ppi-startpage-preview-header {
    backdrop-filter:none;-webkit-backdrop-filter:none;
    background:rgba(17,25,32,.85)!important;
  }
}


/* 0.4.62.113: glossy glass, responsive matched widths and automatic split seam. */
#ppi-tours-app .ppi-startpage-effect-options{grid-template-columns:repeat(3,minmax(0,1fr))}
#ppi-tours-app .ppi-effect-mini-glossy i{
 background:linear-gradient(145deg,rgba(255,255,255,.48) 0%,rgba(255,255,255,.12) 38%,rgba(255,255,255,.02) 65%);
 border:1px solid rgba(255,255,255,.65);border-radius:8px;
 box-shadow:inset 0 1px 0 rgba(255,255,255,.5),0 6px 13px rgba(0,0,0,.32);
 backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px)
}
#ppi-tours-app .ppi-startpage-live-preview[data-effect="glossy"] .ppi-startpage-preview-header{
 position:relative;isolation:isolate;overflow:hidden;
 box-sizing:border-box;width:calc(100% - 16px);max-width:100%;
 margin:10px auto 16px!important;padding:24px 15px!important;
 border:1px solid rgba(255,255,255,.62)!important;border-radius:16px!important;
 background:rgba(24,39,54,.23)!important;background-image:none!important;
 backdrop-filter:blur(15px) saturate(140%);-webkit-backdrop-filter:blur(15px) saturate(140%);
 box-shadow:inset 0 1px 0 rgba(255,255,255,.60),inset 0 -1px 0 rgba(255,255,255,.10),0 13px 30px rgba(0,0,0,.30)!important
}
#ppi-tours-app .ppi-startpage-live-preview[data-effect="glossy"] .ppi-startpage-preview-header::before{
 content:"";pointer-events:none;position:absolute;inset:0;z-index:0;
 background:linear-gradient(157deg,rgba(255,255,255,.28) 0%,rgba(255,255,255,.11) 25%,transparent 39%,transparent 70%,rgba(255,255,255,.07) 100%);
 border-radius:inherit
}
#ppi-tours-app .ppi-startpage-live-preview[data-effect="glossy"] .ppi-startpage-preview-header::after{
 content:"";pointer-events:none;position:absolute;inset:0 0 auto;z-index:0;height:40%;
 background:linear-gradient(to bottom,rgba(255,255,255,.16),rgba(255,255,255,0));
 border-radius:inherit
}
#ppi-tours-app .ppi-startpage-live-preview[data-effect="glossy"] .ppi-startpage-preview-header>h2,
#ppi-tours-app .ppi-startpage-live-preview[data-effect="glossy"] .ppi-startpage-preview-header>p{
 position:relative;z-index:1
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"] .ppi-startpage-preview-hero{
 border-bottom:1px solid var(--ppi-startpage-divider,rgba(255,255,255,.58))!important;
 box-shadow:0 8px 18px rgba(0,0,0,.25),0 1px 2px var(--ppi-startpage-divider,rgba(255,255,255,.58))!important
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"][data-effect="glossy"] .ppi-startpage-preview-header{
 width:calc(100% - 8px);margin:0!important
}
@media(max-width:520px){
 #ppi-tours-app .ppi-startpage-effect-options{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(prefers-reduced-transparency:reduce){
 #ppi-tours-app .ppi-startpage-live-preview[data-effect="glossy"] .ppi-startpage-preview-header{
 backdrop-filter:none;-webkit-backdrop-filter:none;background:rgba(17,25,32,.87)!important
 }
}


/* 0.4.62.114: sortable selected tours, visible drop target and keyboard controls. */
/* In Vollbild the text/glass block spans the same usable width as the tile grid. */
#ppi-tours-app .ppi-startpage-live-preview[data-layout="hero"] .ppi-startpage-preview-header {
 width:100%!important;max-width:none!important;box-sizing:border-box!important;
 margin:0 auto 16px!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="hero"] .ppi-startpage-preview-hero {
 width:100%!important;
}

#ppi-tours-app .ppi-startpage-tour-option{position:relative}
#ppi-tours-app .ppi-startpage-tour-option[draggable=true]{cursor:grab}
#ppi-tours-app .ppi-startpage-tour-option.ppi-tour-dragging{opacity:.48;cursor:grabbing}
#ppi-tours-app .ppi-startpage-tour-option.ppi-tour-drop-before{
  box-shadow:inset 0 3px 0 var(--color-primary-element,#1768a1),0 3px 12px rgba(0,0,0,.13)!important
}
#ppi-tours-app .ppi-startpage-tour-option.ppi-tour-drop-after{
  box-shadow:inset 0 -3px 0 var(--color-primary-element,#1768a1),0 3px 12px rgba(0,0,0,.13)!important
}
#ppi-tours-app .ppi-startpage-tour-move{
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;
  flex:0 0 34px;min-width:34px;align-self:stretch
}
#ppi-tours-app .ppi-startpage-tour-grip{font-size:22px;color:var(--color-text-maxcontrast);cursor:grab;user-select:none;line-height:1}
#ppi-tours-app .ppi-startpage-tour-move button{
  display:grid;place-items:center;min-height:25px!important;height:25px!important;min-width:30px!important;
  padding:0!important;border:1px solid var(--color-border)!important;border-radius:6px!important;
  background:var(--color-main-background)!important;color:var(--color-main-text)!important;
  font-size:14px;cursor:pointer
}
#ppi-tours-app .ppi-startpage-tour-move button:disabled{opacity:.35;cursor:default}
#ppi-tours-app .ppi-startpage-tour-move button:focus-visible{outline:2px solid var(--color-primary-element)}
@media(max-width:480px){#ppi-tours-app .ppi-startpage-tour-option img{max-width:27%!important}}

/* 0.4.62.115: gallery-preview alignment and high-gloss reflections. */
#ppi-tours-app .ppi-startpage-live-preview[data-layout="gallery"] .ppi-startpage-preview-hero {
  width:100%!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="gallery"] .ppi-startpage-preview-header {
  width:100%!important;
  max-width:none!important;
  box-sizing:border-box!important;
  margin:0 auto 15px!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-effect="glossy"] .ppi-startpage-preview-header {
  background:rgba(10,24,37,.15)!important;
  background-image:none!important;
  border:1px solid rgba(255,255,255,.80)!important;
  box-shadow:inset 0 2px 0 rgba(255,255,255,.72),
    inset 1px 0 0 rgba(255,255,255,.32),
    inset -1px 0 0 rgba(255,255,255,.18),
    inset 0 -1px 0 rgba(255,255,255,.25),
    0 15px 38px rgba(0,0,0,.34)!important;
  backdrop-filter:blur(17px) saturate(135%);
  -webkit-backdrop-filter:blur(17px) saturate(135%);
}
#ppi-tours-app .ppi-startpage-live-preview[data-effect="glossy"] .ppi-startpage-preview-header::before {
  background:linear-gradient(117deg,transparent 0%,rgba(255,255,255,.09) 11%,rgba(255,255,255,.33) 22%,rgba(255,255,255,.06) 32%,transparent 43%),
    linear-gradient(166deg,rgba(255,255,255,.30) 0%,rgba(255,255,255,.08) 19%,transparent 49%,rgba(255,255,255,.07) 100%)!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-effect="glossy"] .ppi-startpage-preview-header::after {
  height:52%!important;
  background:radial-gradient(ellipse at 35% -30%,rgba(255,255,255,.46) 0%,rgba(255,255,255,.13) 35%,transparent 76%)!important;
}
@media(prefers-reduced-transparency:reduce){
 #ppi-tours-app .ppi-startpage-live-preview[data-effect="glossy"] .ppi-startpage-preview-header{
   backdrop-filter:none!important;-webkit-backdrop-filter:none!important;
   background:rgba(16,31,44,.90)!important;
 }
}

/* 0.4.62.116: align split preview with card grid and show gallery JPEG in
   all effect presets, as in the real public start page. */
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"] .ppi-startpage-preview-hero{
  box-sizing:border-box!important;width:100%!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"] .ppi-startpage-preview-header{
  box-sizing:border-box!important;width:calc(100% - 28px)!important;
  max-width:none!important;margin:0 auto!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"] .ppi-startpage-preview-grid{
  box-sizing:border-box!important;width:calc(100% - 28px)!important;
  margin:24px 14px 10px!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="gallery"]{
  background-color:var(--ppi-startpage-bg,#000)!important;
  background-image:linear-gradient(rgba(0,0,0,.20),rgba(0,0,0,.20)),var(--ppi-startpage-image,none)!important;
  background-size:cover!important;background-position:center!important;
}

/* 0.4.62.118: the editor mirrors the public tile composition. */
#ppi-tours-app .ppi-startpage-preview-tile {
 height:clamp(155px,21vw,245px)!important;min-height:155px!important;
 display:flex!important;flex-direction:column!important;overflow:hidden!important;
}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-media {
 position:relative;flex:1 1 auto;min-height:0;overflow:hidden;
 display:grid;place-items:center;background:#213848;
}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-media img {
 width:100%!important;height:100%!important;min-height:0!important;
 max-width:100%!important;aspect-ratio:auto!important;object-fit:cover!important;
 border-radius:0!important;
}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-media::after {
 content:"";position:absolute;inset:0;pointer-events:none;
 background:linear-gradient(155deg,rgba(255,255,255,.23) 0%,rgba(255,255,255,.10) 23%,transparent 48%,rgba(255,255,255,.07) 100%);
 box-shadow:inset 0 1px 0 rgba(255,255,255,.34);
 opacity:1;transition:opacity .18s ease
}
@media(hover:hover) and (pointer:fine) {
 #ppi-tours-app .ppi-startpage-preview-tile:hover .ppi-startpage-tile-media::after{opacity:0}
}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-copy {
 height:70px!important;min-height:70px!important;max-height:70px!important;
 flex:0 0 70px!important;
 box-sizing:border-box!important;padding:8px 9px!important;
 display:flex!important;flex-direction:column!important;
 align-items:center!important;justify-content:flex-start!important;
 gap:3px!important;overflow:hidden!important;text-align:center!important;
}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-copy strong {
 display:-webkit-box!important;-webkit-box-orient:vertical!important;
 -webkit-line-clamp:2!important;overflow:hidden!important;
 font-size:clamp(12px,1.45vw,16px)!important;font-weight:750!important;
 line-height:1.2!important;padding:0!important;margin:0!important;
 max-width:100%!important;overflow-wrap:anywhere!important;
}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-copy small {
 display:-webkit-box!important;-webkit-box-orient:vertical!important;
 -webkit-line-clamp:2!important;overflow:hidden!important;
 max-width:100%!important;color:#5c6571!important;
 font-size:10px!important;line-height:1.3!important;
 margin:0!important;padding:0!important;overflow-wrap:anywhere!important;
}
@media(prefers-reduced-motion:reduce){
 #ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-media::after{transition:none}
}


/* 0.4.62.119: match the public start page heading, split background
   and readable descriptions in the layout editor. */
#ppi-tours-app .ppi-startpage-preview-header h2{
 -webkit-text-stroke:1.15px var(--ppi-startpage-stroke)!important;
 paint-order:stroke fill;
 text-shadow:0 2px 4px var(--ppi-startpage-shadow),0 5px 12px rgba(0,0,0,.55)!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"] .ppi-startpage-preview-hero{
 background-color:var(--ppi-startpage-header-bg,var(--ppi-startpage-bg,#000000))!important;
}
#ppi-tours-app .ppi-startpage-live-preview[data-layout="split"] .ppi-startpage-preview-grid{
 background-color:transparent!important;
}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-copy {
 height:88px!important;min-height:88px!important;max-height:88px!important;
 flex:0 0 88px!important;padding:8px 9px!important;
}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-copy strong {
 font-size:clamp(14px,1.5vw,18px)!important;
}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-copy small {
 font-size:clamp(13px,1.05vw,15px)!important;
 line-height:1.35!important;
}
@media(max-width:800px){
 #ppi-tours-app .ppi-startpage-preview-grid[data-cols="4"] .ppi-startpage-preview-tile:last-child:nth-child(4n + 3){
  grid-column:auto!important;
 }
}
@media(max-width:420px){
 #ppi-tours-app .ppi-startpage-preview-grid[data-cols] .ppi-startpage-preview-tile{grid-column:auto!important}
}


/* 0.4.62.121: identical dark-at-rest, clear-on-hover effect in editor. */
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-media::after{
 background:linear-gradient(135deg,rgba(8,18,26,.32),rgba(8,18,26,.40))!important;
 box-shadow:inset 0 1px 0 rgba(255,255,255,.15)!important;
 opacity:1!important;transition:opacity .22s ease!important;
}
@media(hover:hover) and (pointer:fine){
 #ppi-tours-app .ppi-startpage-preview-tile:hover .ppi-startpage-tile-media::after{
  opacity:0!important;
 }
}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-copy {
 padding-left:5px!important;padding-right:5px!important;
}
#ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-copy small{
 font-size:clamp(12px,1vw,14px)!important;
}
@media(prefers-reduced-motion:reduce){
 #ppi-tours-app .ppi-startpage-preview-tile .ppi-startpage-tile-media::after {transition:none!important}
}

/* 0.4.62.122: deterministic automatic 1x1/2x1/2x2 mosaic.
    Full cycles leave no grid gaps; the DOM reading order is preserved. */
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="auto"],
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="2"],
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="3"],
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="4"],
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="5"] {
 grid-auto-rows:clamp(155px,21vw,245px)!important;
 grid-auto-flow:row!important;
 align-items:stretch!important;
}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="auto"] > .ppi-startpage-preview-tile:nth-child(n),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="2"] > .ppi-startpage-preview-tile:nth-child(n),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="3"] > .ppi-startpage-preview-tile:nth-child(n),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="4"] > .ppi-startpage-preview-tile:nth-child(n),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="5"] > .ppi-startpage-preview-tile:nth-child(n){
 grid-column:span 1!important;grid-row:span 1!important;
 height:100%!important;min-height:0!important;max-height:none!important;
}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="auto"] > .ppi-startpage-preview-tile:nth-child(7n + 1),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="2"] > .ppi-startpage-preview-tile:nth-child(7n + 1){
 grid-column:span 1!important;grid-row:span 1!important;
}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="auto"]:has(> .ppi-startpage-preview-tile:nth-child(5)) > .ppi-startpage-preview-tile:nth-child(7n + 1),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="2"]:has(> .ppi-startpage-preview-tile:nth-child(5)) > .ppi-startpage-preview-tile:nth-child(7n + 1){
 grid-column:span 2!important;grid-row:span 2!important;
}
#ppi-tours-app[data-cols="3"] .ppi-startpage-preview-grid:has(> .ppi-startpage-preview-tile:nth-child(5)) > .ppi-startpage-preview-tile:nth-child(8n + 1){
 grid-column:span 2!important;grid-row:span 2!important;
}
#ppi-tours-app[data-cols="3"] .ppi-startpage-preview-grid:has(> .ppi-startpage-preview-tile:nth-child(5)) > .ppi-startpage-preview-tile:nth-child(8n + 4){
 grid-column:span 2!important;grid-row:span 1!important;
}
#ppi-tours-app[data-cols="4"] .ppi-startpage-preview-grid:has(> .ppi-startpage-preview-tile:nth-child(5)) > .ppi-startpage-preview-tile:nth-child(12n + 1){
 grid-column:span 2!important;grid-row:span 2!important;
}
#ppi-tours-app[data-cols="4"] .ppi-startpage-preview-grid:has(> .ppi-startpage-preview-tile:nth-child(5)) > .ppi-startpage-preview-tile:nth-child(12n + 6){
 grid-column:span 2!important;grid-row:span 1!important;
}
#ppi-tours-app[data-cols="5"] .ppi-startpage-preview-grid:has(> .ppi-startpage-preview-tile:nth-child(5)) > .ppi-startpage-preview-tile:nth-child(16n + 1){
 grid-column:span 2!important;grid-row:span 2!important;
}
#ppi-tours-app[data-cols="5"] .ppi-startpage-preview-grid:has(> .ppi-startpage-preview-tile:nth-child(5)) > .ppi-startpage-preview-tile:nth-child(16n + 8){
 grid-column:span 2!important;grid-row:span 1!important;
}
@media(max-width:800px){
#ppi-tours-app[data-cols="2"] .ppi-startpage-preview-grid,
#ppi-tours-app[data-cols="3"] .ppi-startpage-preview-grid,
#ppi-tours-app[data-cols="4"] .ppi-startpage-preview-grid,
#ppi-tours-app[data-cols="5"] .ppi-startpage-preview-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important;}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="auto"] > .ppi-startpage-preview-tile:nth-child(n),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="2"] > .ppi-startpage-preview-tile:nth-child(n),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="3"] > .ppi-startpage-preview-tile:nth-child(n),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="4"] > .ppi-startpage-preview-tile:nth-child(n),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="5"] > .ppi-startpage-preview-tile:nth-child(n){grid-column:span 1!important;grid-row:span 1!important;}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="auto"]:has(> .ppi-startpage-preview-tile:nth-child(5)) > .ppi-startpage-preview-tile:nth-child(7n + 1),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="2"]:has(> .ppi-startpage-preview-tile:nth-child(5)) > .ppi-startpage-preview-tile:nth-child(7n + 1),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="3"]:has(> .ppi-startpage-preview-tile:nth-child(5)) > .ppi-startpage-preview-tile:nth-child(7n + 1),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="4"]:has(> .ppi-startpage-preview-tile:nth-child(5)) > .ppi-startpage-preview-tile:nth-child(7n + 1),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="5"]:has(> .ppi-startpage-preview-tile:nth-child(5)) > .ppi-startpage-preview-tile:nth-child(7n + 1){grid-column:span 2!important;grid-row:span 2!important;}
}
@media(max-width:420px){
#ppi-tours-app[data-cols="auto"] .ppi-startpage-preview-grid,
#ppi-tours-app[data-cols="2"] .ppi-startpage-preview-grid,
#ppi-tours-app[data-cols="3"] .ppi-startpage-preview-grid,
#ppi-tours-app[data-cols="4"] .ppi-startpage-preview-grid,
#ppi-tours-app[data-cols="5"] .ppi-startpage-preview-grid{grid-template-columns:minmax(0,1fr)!important;grid-auto-rows:245px!important;}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="auto"] > .ppi-startpage-preview-tile:nth-child(n),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="2"] > .ppi-startpage-preview-tile:nth-child(n),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="3"] > .ppi-startpage-preview-tile:nth-child(n),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="4"] > .ppi-startpage-preview-tile:nth-child(n),
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="5"] > .ppi-startpage-preview-tile:nth-child(n){grid-column:span 1!important;grid-row:span 1!important;}
}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="auto"] > .ppi-startpage-preview-tile:nth-child(n):nth-child(n):nth-child(n){grid-column:span 1!important;grid-row:span 1!important;}
#ppi-tours-app .ppi-startpage-preview-grid[data-cols="auto"]:has(> .ppi-startpage-preview-tile:nth-child(5)) > .ppi-startpage-preview-tile:nth-child(7n + 1):nth-child(n):nth-child(n){grid-column:span 2!important;grid-row:span 2!important;}
@media(max-width:420px){#ppi-tours-app .ppi-startpage-preview-grid[data-cols="auto"] > .ppi-startpage-preview-tile:nth-child(n):nth-child(n):nth-child(n){grid-column:span 1!important;grid-row:span 1!important;}}

</style>
