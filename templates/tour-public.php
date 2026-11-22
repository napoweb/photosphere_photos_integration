<main id="ppi-public-tour" data-manifest-url="<?php p($_['manifestUrl'] ?? ''); ?>"
      data-image-url="<?php p($_['imageUrl'] ?? ''); ?>" data-optimized-image-url="<?php p($_['optimizedImageUrl'] ?? ''); ?>" data-watermark-url="<?php p($_['watermarkUrl'] ?? ''); ?>"
      data-watermark-options="<?php p($_['watermarkOptions'] ?? '{}'); ?>" data-thumb-url="<?php p($_['thumbUrl'] ?? ''); ?>" data-invalid="<?php p(!empty($_['invalid']) ? '1' : '0'); ?>">
  <?php if (!empty($_['invalid'])): ?>
  <section id="ppi-public-unavailable" role="alert" aria-labelledby="ppi-public-unavailable-title">
    <div class="ppi-public-unavailable-card">
      <div class="ppi-public-unavailable-icon" aria-hidden="true">◎</div>
      <span class="ppi-public-unavailable-kicker">360°-TOUR</span>
      <h1 id="ppi-public-unavailable-title">Diese 360°-Tour ist nicht mehr verfügbar</h1>
      <p>Dieser Freigabelink ist ungültig, wurde erneuert oder ist abgelaufen.</p>
      <p>Bitte fordere beim Besitzer der Tour einen neuen Freigabelink an.</p>
    </div>
  </section>
  <?php endif; ?>
  <?php if (!empty($_['passwordRequired'])): ?>
  <section id="ppi-public-unavailable" aria-labelledby="ppi-public-password-title">
    <div class="ppi-public-unavailable-card">
      <div class="ppi-public-unavailable-icon" aria-hidden="true">◎</div>
      <span class="ppi-public-unavailable-kicker">GESCHÜTZTE 360°-TOUR</span>
      <h1 id="ppi-public-password-title">Passwort erforderlich</h1>
      <p>Diese Tour ist mit einem Passwort geschützt.</p>
      <?php if (!empty($_['passwordError'])): ?><p role="alert">Das Passwort ist nicht korrekt. Bitte versuche es erneut.</p><?php endif; ?>
      <form method="post" action="<?php p($_['unlockUrl']); ?>" class="ppi-tour-password-form">
        <label for="ppi-tour-public-password">Freigabepasswort</label>
        <input id="ppi-tour-public-password" name="password" type="password" required autocomplete="current-password" maxlength="128" />
        <button type="submit">Tour entsperren</button>
      </form>
    </div>
  </section>
  <?php endif; ?>
  <section id="ppi-public-landing" aria-label="360°-Tour starten" hidden>
    <img id="ppi-public-cover" alt="Startpanorama dieser 360°-Tour" />
    <div class="ppi-public-landing-shade" aria-hidden="true"></div>
    <div class="ppi-public-landing-content">
      <span class="ppi-public-landing-kicker">INTERAKTIVE 360°-TOUR</span>
      <h1 id="ppi-public-landing-title">360°-Tour</h1>
      <p id="ppi-public-landing-info">Entdecke die Panoramen dieser Tour.</p>
      <div id="ppi-public-landing-description" hidden></div>
      <button id="ppi-public-start" type="button">Tour starten <span aria-hidden="true">→</span></button>
    </div>
  </section>
  <section class="ppi-public-view" aria-label="360°-Tour" hidden>
    <img id="ppi-public-tour-watermark" alt="" aria-hidden="true" hidden />
    <div id="ppi-public-sphere" aria-label="Interaktives 360°-Panorama" tabindex="0" hidden></div>
    <div class="ppi-public-overlay ppi-public-top" id="ppi-public-topbar">
      <div class="ppi-public-tourname"><span class="ppi-tour-icon" aria-hidden="true">◎</span><span id="ppi-public-title">360°-Tour</span></div>
      <div class="ppi-public-toolbar">
        <button type="button" id="ppi-public-nav-toggle" title="Panorama-Übersicht" aria-label="Panorama-Übersicht ein- und ausblenden" aria-pressed="false">▦</button>
        <button type="button" id="ppi-public-help" title="Bedienung" aria-label="Bedienhinweise anzeigen">?</button>
        <button type="button" id="ppi-public-fullscreen" title="Vollbild" aria-label="Vollbild umschalten">⛶</button>
      </div>
    </div>
    <div id="ppi-public-message" role="status">Tour wird geladen …</div>
    <div class="ppi-public-overlay ppi-public-bottom" id="ppi-public-bottom">
      <nav id="ppi-public-nav" aria-label="Panoramen dieser Tour" hidden></nav>
    </div>
    <aside id="ppi-public-guide" hidden>Maus / Finger: Panorama drehen · Pfeiltasten: drehen · Strg + ↑/↓: Zoom · Vollbild: ⛶</aside>
    <div id="ppi-public-gyro" hidden>
      <button id="ppi-public-gyro-grip" type="button" aria-label="Gyroskop-Steuerung öffnen" aria-expanded="false"></button>
      <div class="ppi-public-gyro-panel">
        <strong>Tour-Steuerung</strong>
        <button type="button" id="ppi-public-gyro-switch">Gyroskop: Aus</button>
        <button type="button" id="ppi-public-mobile-nav" aria-pressed="false">Menü anzeigen</button>
        <button type="button" id="ppi-public-mobile-fullscreen">⛶ Vollbild</button>
      </div>
    </div>
  </section>
</main>
<style>
  /* The public experience is deliberately self-contained and viewport-filling. */
  html:has(#ppi-public-tour),body:has(#ppi-public-tour){margin:0!important;overflow:hidden!important}
  #content:has(#ppi-public-tour),#content-vue:has(#ppi-public-tour),#app-content:has(#ppi-public-tour){height:100dvh!important;min-height:0!important;overflow:hidden!important}
  #ppi-public-tour{--ppi-hotspot-scale:1;position:fixed;inset:0;z-index:11000;width:100vw;height:100dvh;max-width:none!important;margin:0!important;padding:0!important;background:#08121b;color:#fff;font:15px system-ui,sans-serif;box-sizing:border-box;overflow:hidden}
  #ppi-public-unavailable{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
    box-sizing:border-box;padding:clamp(20px,5vw,48px);overflow:auto;z-index:20;
    background:radial-gradient(circle at 50% 30%,#17364d 0%,#0b2030 46%,#08121b 100%)}
  #ppi-public-unavailable .ppi-public-unavailable-card{width:min(100%,540px);box-sizing:border-box;
    padding:clamp(25px,5vw,48px);text-align:center;border:1px solid rgba(255,255,255,.2);
    background:rgba(8,20,32,.75);border-radius:20px;box-shadow:0 18px 60px rgba(0,0,0,.25)}
  #ppi-public-unavailable .ppi-public-unavailable-icon{font-size:48px;line-height:1.2;color:#cce6fa;margin-bottom:8px}
  #ppi-public-unavailable .ppi-public-unavailable-kicker{font-size:12px;font-weight:750;letter-spacing:.18em;color:#a7cae1}
  #ppi-public-unavailable h1{font:750 clamp(23px,4.5vw,32px)/1.25 system-ui,sans-serif;
    color:#fff;margin:14px 0 18px}
  #ppi-public-unavailable p{font:400 15px/1.65 system-ui,sans-serif;color:#d5e3ed;margin:0 0 12px}
  #ppi-public-tour:fullscreen{width:100%;height:100%;background:#08121b}
  #ppi-public-tour [hidden]{display:none!important}
  #ppi-public-tour[data-hotspot-size="xsmall"]{--ppi-hotspot-scale:.72}
  #ppi-public-tour[data-hotspot-size="small"]{--ppi-hotspot-scale:.8}
  #ppi-public-tour[data-hotspot-size="large"]{--ppi-hotspot-scale:1.2}
  #ppi-public-tour[data-hotspot-size="xlarge"]{--ppi-hotspot-scale:1.28}
  #ppi-public-tour .ppi-public-view{position:absolute;inset:0;border:0;border-radius:0;padding:0;background:#08121b;overflow:hidden}
  #ppi-public-tour-watermark{position:absolute;z-index:4;pointer-events:none;object-fit:contain;transform:translate(-50%,-50%);max-width:50vw;max-height:40vh;opacity:0;transition:opacity .24s ease,transform .16s ease-out;will-change:transform,opacity,left,top;}
  #ppi-public-sphere{position:absolute;inset:0;width:100%;height:100%;overflow:hidden;touch-action:none;outline:none}
  #ppi-public-sphere canvas{display:block;width:100%;height:100%;touch-action:none}
  #ppi-public-sphere .ppi-hotspot{position:absolute;z-index:2;min-width:46px;min-height:46px;border:2px solid white!important;border-radius:50%;background:#1468a1!important;color:#fff!important;font-size:23px;box-shadow:0 2px 13px #0009;transform:translate(-50%,-50%);cursor:pointer}
  #ppi-public-tour .ppi-public-overlay{position:absolute;z-index:5;pointer-events:none;left:0;right:0;display:flex;gap:10px;padding:16px max(16px,env(safe-area-inset-right)) 16px max(16px,env(safe-area-inset-left));box-sizing:border-box}
  #ppi-public-tour .ppi-public-overlay>*{pointer-events:auto}
  #ppi-public-tour .ppi-public-top{top:0;align-items:flex-start;justify-content:space-between;background:transparent}
  #ppi-public-tour .ppi-public-tourname{display:flex;align-items:center;gap:9px;padding:9px 13px;border:1px solid rgba(255,255,255,.23);background:rgba(7,20,31,.47);color:#fff;border-radius:13px;backdrop-filter:blur(9px);text-shadow:0 1px 4px #0008;font-weight:650;max-width:min(66vw,620px)}
  #ppi-public-tour .ppi-public-tourname span:last-child{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  #ppi-public-tour .ppi-tour-icon{font-size:24px;line-height:16px}
  #ppi-public-tour .ppi-public-toolbar{display:flex;gap:7px;padding:5px;border:1px solid #ffffff3b;background:rgba(7,20,31,.47);border-radius:13px;backdrop-filter:blur(9px)}
  #ppi-public-tour .ppi-public-toolbar button{background:transparent!important;border:0!important;color:#fff!important;width:38px;height:38px;border-radius:9px;font-size:22px;cursor:pointer}
  /* 0.4.62.48: keep every toolbar glyph geometrically centred on hover.
     Nextcloud's generic button styles must not move/resize these buttons. */
  #ppi-public-tour .ppi-public-toolbar button{
    display:flex!important;align-items:center!important;justify-content:center!important;
    padding:0!important;margin:0!important;min-width:0!important;min-height:0!important;
    flex:0 0 38px;box-sizing:border-box!important;line-height:1!important;
    transform:none!important;vertical-align:middle!important;
  }
  #ppi-public-tour .ppi-public-toolbar button:hover,#ppi-public-tour .ppi-public-toolbar button:focus-visible{
    background:#ffffff2c!important;transform:none!important;
  }
  #ppi-public-tour .ppi-public-bottom{bottom:0;justify-content:center;padding-bottom:max(16px,env(safe-area-inset-bottom));background:transparent}
  #ppi-public-nav[hidden]{display:none!important}
  #ppi-public-nav-toggle[hidden],#ppi-public-mobile-nav[hidden]{display:none!important}
  #ppi-public-nav{display:flex;gap:7px;max-width:min(100%,1200px);overflow-x:auto;overscroll-behavior-x:contain;scrollbar-width:thin;padding:7px;border:1px solid #ffffff3b;background:rgba(7,20,31,.48);border-radius:13px;backdrop-filter:blur(9px)}
  #ppi-public-nav button{flex:none;max-width:180px;min-height:36px;padding:7px 12px;border-radius:8px;border:1px solid #ffffff66;background:#101f31b5;color:#fff;cursor:pointer;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  #ppi-public-nav button[aria-current="true"]{border-color:#fff;box-shadow:0 0 0 2px #fff;font-weight:700}
  #ppi-public-nav .ppi-public-nav-card{display:flex;flex-direction:column;align-items:stretch;gap:5px;width:132px;max-width:132px;min-height:0;padding:5px;border-radius:10px;overflow:hidden;white-space:normal;text-align:left;transition:transform .15s ease,border-color .15s ease}
  #ppi-public-nav .ppi-public-nav-card:hover,#ppi-public-nav .ppi-public-nav-card:focus-visible{transform:translateY(-2px);border-color:#fff}
  #ppi-public-nav .ppi-public-nav-card img{display:block;width:100%;height:69px;object-fit:cover;border-radius:6px;background:#2d3d4a}
  #ppi-public-nav .ppi-public-nav-card span{font:500 11px/1.25 system-ui,sans-serif;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
  #ppi-public-nav .ppi-public-nav-card.ppi-public-no-thumb{justify-content:center;min-height:70px}
  #ppi-public-landing-description{font:400 clamp(14px,1.4vw,18px)/1.55 system-ui,sans-serif;white-space:pre-line;max-width:680px;max-height:min(22dvh,180px);overflow-y:auto;margin:8px 0 16px;opacity:.93;text-shadow:0 2px 10px #0008}
  #ppi-public-landing-description[hidden]{display:none!important}
  #ppi-public-landing-description p{font:inherit!important;color:inherit!important;margin:0 0 7px!important}
  #ppi-public-landing-description ul,#ppi-public-landing-description ol{margin:5px 0 10px;padding-left:26px}
  #ppi-public-landing-description li{margin:2px 0}
  #ppi-public-landing-description a{color:#bde3ff;text-decoration:underline;text-underline-offset:2px;overflow-wrap:anywhere}
  #ppi-public-landing-description a:hover,#ppi-public-landing-description a:focus-visible{color:#fff}
  #ppi-public-landing-description svg.ppi-inline-quad{display:inline-block;width:1.65em;height:1em;vertical-align:-.16em;fill:none;stroke:currentColor;stroke-width:3;stroke-linecap:round;stroke-linejoin:round}
  #ppi-public-message{position:absolute;z-index:6;left:50%;top:50%;transform:translate(-50%,-50%);max-width:90vw;pointer-events:none;padding:11px 16px;color:#fff;background:rgba(7,20,31,.7);border-radius:10px;text-align:center;backdrop-filter:blur(8px)}
  #ppi-public-message:empty{display:none!important}
  #ppi-public-guide{position:absolute;z-index:7;right:16px;top:76px;max-width:min(400px,85vw);background:rgba(7,20,31,.78);backdrop-filter:blur(10px);border:1px solid #ffffff56;border-radius:12px;color:#fff;padding:14px;line-height:1.65}
  /* Match the established Files/Photos sliding gyro tab, mobile touch only. */
  #ppi-public-gyro{position:absolute;z-index:8;right:0;top:76%;transform:translateY(-50%) translateX(calc(100% - 14px));
    display:flex;align-items:stretch;gap:0;width:min(184px,92vw);min-height:72px;box-sizing:border-box;overflow:hidden;
    border:1px solid rgba(255,255,255,.88);border-right:0;border-radius:13px 0 0 13px;
    background:rgba(8,18,28,.20);color:#fff;backdrop-filter:blur(2px);box-shadow:0 10px 28px rgba(0,0,0,.14);
    visibility:hidden;pointer-events:none;transition:transform .29s cubic-bezier(.2,.8,.25,1)}
  #ppi-public-gyro[data-open="true"]{transform:translateY(-50%) translateX(0)}
  #ppi-public-gyro-grip{flex:0 0 14px;align-self:stretch;min-height:72px;border:0!important;border-radius:0!important;
    background:transparent!important;color:transparent!important;cursor:pointer;padding:0!important;touch-action:manipulation}
  #ppi-public-gyro .ppi-public-gyro-panel{display:flex;flex:1;min-width:0;flex-direction:column;align-items:flex-start;justify-content:center;
    text-align:left;gap:6px;padding:9px 19px 9px 7px;box-sizing:border-box;opacity:0;visibility:hidden;pointer-events:none;
    transition:opacity .2s ease,visibility .2s ease}
  #ppi-public-gyro[data-open="true"] .ppi-public-gyro-panel{opacity:1;visibility:visible;pointer-events:auto}
  #ppi-public-gyro .ppi-public-gyro-panel strong{display:block;width:100%;box-sizing:border-box;min-width:0;
    font:600 12px/1.35 system-ui,sans-serif;color:#fff;text-shadow:0 1px 3px #0009;
    padding:0 2px 2px;text-align:left;white-space:nowrap}
  #ppi-public-gyro-switch,#ppi-public-mobile-nav,#ppi-public-mobile-fullscreen{border:1px solid rgba(255,255,255,.88)!important;background:rgba(8,18,28,.20)!important;
    border-radius:999px;color:#fff!important;padding:8px 10px;cursor:pointer;font:600 12px system-ui,sans-serif;
    touch-action:manipulation;white-space:nowrap;text-shadow:0 1px 3px #0009;
    width:100%;min-height:35px;box-sizing:border-box;display:flex;align-items:center;justify-content:center;text-align:center}
  /* Mobile side tab: a vertical, equally sized control stack aligned with the heading.
     Scoped to this panel to avoid Nextcloud's global button/layout rules. */
  #ppi-public-gyro .ppi-public-gyro-panel{display:flex!important;flex-direction:column!important;
    align-items:stretch!important;justify-content:flex-start!important;gap:6px!important;
    min-width:0;max-width:100%;}
  #ppi-public-gyro .ppi-public-gyro-panel > button{display:flex!important;flex:0 0 auto!important;
    width:100%!important;max-width:100%!important;min-width:0!important;
    min-height:35px!important;margin:0!important;box-sizing:border-box!important;}
  #ppi-public-gyro .ppi-public-gyro-panel > button[hidden]{display:none!important;}
  @media(hover:hover) and (pointer:fine){#ppi-public-gyro{display:none!important}}
  @media(pointer:coarse){
    #ppi-public-tour .ppi-public-toolbar{display:none!important}
    #ppi-public-guide{display:none!important}
    #ppi-public-gyro .ppi-public-gyro-panel{padding:9px 19px 9px 7px}
  }
  @media(prefers-reduced-motion:reduce){#ppi-public-gyro{transition:none}}
  /* Matching loading veil from the standalone editor, now on public panoramas too. */
  #ppi-public-sphere .ppi-sphere-loading{position:absolute;inset:0;z-index:9;overflow:hidden;background:#121820;
    display:flex;align-items:center;justify-content:center;color:white;font:600 15px/1.3 system-ui,sans-serif;
    opacity:1;transition:opacity .52s ease-out;pointer-events:none}
  #ppi-public-sphere .ppi-sphere-loading-done{opacity:0}
  #ppi-public-sphere .ppi-sphere-loading-done .ppi-sphere-loading-content{opacity:0;transition:opacity .15s ease-out}
  #ppi-public-sphere .ppi-sphere-loading-preview{position:absolute;inset:-24px;background-position:center;background-size:cover;
    filter:blur(17px);transform:scale(1.05);opacity:.65}
  #ppi-public-sphere .ppi-sphere-loading-progress-preview{position:absolute;inset:-24px;background-position:center;background-size:cover;filter:blur(12px);transform:scale(1.05);opacity:.83;clip-path:inset(0 0 100% 0);transition:clip-path .55s cubic-bezier(.35,0,.25,1);pointer-events:none}
#ppi-public-sphere .ppi-sphere-loading-preview-complete{clip-path:inset(0 0 0 0)}
#ppi-public-sphere .ppi-sphere-loading-content{position:relative;z-index:1;padding:20px 24px;width:min(330px,86%);
    box-sizing:border-box;border-radius:16px;background:rgba(8,16,25,.55);border:1px solid rgba(255,255,255,.2);
    backdrop-filter:blur(8px);text-align:center}
  #ppi-public-sphere .ppi-sphere-loading-spinner{width:36px;height:36px;border:3px solid rgba(255,255,255,.26);
    border-top-color:#fff;border-radius:50%;margin:0 auto 15px;animation:ppi-public-spin .9s linear infinite}
  #ppi-public-sphere .ppi-sphere-loading-detail{font:500 12px/1.4 system-ui,sans-serif;margin-top:6px;opacity:.85}
  #ppi-public-sphere .ppi-sphere-loading-track{width:100%;height:7px;border-radius:999px;overflow:hidden;
    background:rgba(255,255,255,.24);margin-top:16px}
  #ppi-public-sphere .ppi-sphere-loading-fill{width:32%;height:100%;border-radius:inherit;background:#fff;
    animation:ppi-public-loading 1.5s ease-in-out infinite}
  @keyframes ppi-public-spin{to{transform:rotate(360deg)}}
  @keyframes ppi-public-loading{0%{transform:translateX(-110%)}50%{transform:translateX(110%)}100%{transform:translateX(320%)}}
  @media(prefers-reduced-motion:reduce){#ppi-public-sphere .ppi-sphere-loading-spinner,#ppi-public-sphere .ppi-sphere-loading-fill{animation:none}#ppi-public-sphere .ppi-sphere-loading{transition:none}}
  @media(max-width:650px){
    #ppi-public-tour .ppi-public-overlay{padding:9px}
    #ppi-public-tour .ppi-public-tourname{max-width:52vw;padding:9px;font-size:13px}
    #ppi-public-tour .ppi-public-toolbar{gap:2px}
    #ppi-public-tour .ppi-public-toolbar button{width:33px;height:34px;flex-basis:33px;font-size:19px}
    #ppi-public-tour .ppi-public-bottom{padding-bottom:max(9px,env(safe-area-inset-bottom))}
    #ppi-public-nav{max-width:100%;}
    #ppi-public-nav .ppi-public-nav-card{width:102px;max-width:102px;padding:5px}
    #ppi-public-nav .ppi-public-nav-card img{height:55px}
  }
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
/* Public view: match projected anchor and CSS size on both edges. */
#ppi-public-sphere .ppi-hotspot-person{transform-origin:50% 70%!important;transform:translate(-50%,-70%) scale(var(--ppi-hotspot-scale,1))!important;}
@media(max-width:650px){#ppi-public-sphere .ppi-hotspot-person{transform:translate(-50%,-70%) scale(calc(var(--ppi-hotspot-scale,1) * .8))!important;}}

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


  /* 0.4.59: first-view public landing, before the immersive viewer. */
  #ppi-public-landing{position:absolute;inset:0;z-index:12;display:flex;align-items:end;justify-content:flex-start;overflow:hidden;background:#0e1924}
  #ppi-public-cover{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center;filter:brightness(.82)}
  #ppi-public-landing .ppi-public-landing-shade{position:absolute;inset:0;background:linear-gradient(0deg,rgba(3,11,20,.9),rgba(3,11,20,.45) 43%,rgba(3,11,20,.03) 85%)}
  #ppi-public-landing .ppi-public-landing-content{position:relative;z-index:1;width:min(760px,calc(100% - 48px));margin:0 max(24px,env(safe-area-inset-left)) max(64px,env(safe-area-inset-bottom));color:#fff;text-shadow:0 1px 9px #0008}
  #ppi-public-landing .ppi-public-landing-kicker{font-weight:750;letter-spacing:.15em;font-size:12px;color:#e0efff}
  #ppi-public-landing h1{font:750 clamp(30px,5.5vw,64px)/1.12 system-ui,sans-serif;margin:12px 0 12px;color:#fff;overflow-wrap:anywhere}
  #ppi-public-landing p{font:400 clamp(14px,2vw,18px)/1.5 system-ui,sans-serif;margin:0 0 25px;color:#f0f4f8}
  #ppi-public-start{display:inline-flex;gap:18px;align-items:center;justify-content:center;padding:14px 25px;border:1px solid #ffffff80;border-radius:12px;background:#fff!important;color:#102132!important;font-weight:750;font-size:16px;cursor:pointer;box-shadow:0 8px 28px #0005;min-height:50px}
  #ppi-public-start:hover,#ppi-public-start:focus-visible{background:#e9f4ff!important;transform:translateY(-1px);outline-offset:3px}
  #ppi-public-start:disabled{opacity:.6;cursor:wait}
  @media(max-width:650px){#ppi-public-landing .ppi-public-landing-content{width:calc(100% - 36px);margin:0 18px max(42px,env(safe-area-inset-bottom))}#ppi-public-start{width:100%}}
  @media(prefers-reduced-motion:reduce){#ppi-public-start:hover{transform:none}}
  .ppi-tour-password-form{display:flex;flex-direction:column;align-items:stretch;gap:12px;margin:22px auto 0;width:min(360px,100%);text-align:left}
  .ppi-tour-password-form label{font-weight:650;color:#fff}
  .ppi-tour-password-form input{width:100%;min-height:46px;border:1px solid #8297ab;border-radius:9px;padding:11px 13px;background:#f7fbff;color:#122334;font:inherit}
  .ppi-tour-password-form button{min-height:46px;border-radius:9px;border:1px solid #b1d1e6;background:#d3efff;color:#102336;font-weight:700;cursor:pointer}

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


/* 0.4.62.29: Preserve SVG transparency instead of drawing a blue button or cream box. */
#ppi-public-sphere .ppi-hotspot.ppi-hotspot-map-pin,
#ppi-public-sphere .ppi-hotspot.ppi-hotspot-chat-pin,
#ppi-public-sphere .ppi-hotspot.ppi-hotspot-label-pin,
#ppi-public-sphere .ppi-hotspot.ppi-hotspot-outline-pin{
  background:transparent!important;border:0!important;border-radius:0!important;box-shadow:none!important;
}
#ppi-public-sphere .ppi-hotspot-map-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-chat-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-label-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-outline-pin .ppi-hotspot-pin-figure{
  background:transparent!important;border:0!important;border-radius:0!important;padding:0!important;
}

/* 0.4.62.30: recolorable SVG silhouettes only, preserving transparent cutouts. */
#ppi-public-sphere .ppi-hotspot-map-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-chat-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-label-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-outline-pin .ppi-hotspot-pin-figure{background:var(--ppi-svg-pin-color,#1687bc)!important;padding:0!important;border:0!important;border-radius:0!important;-webkit-mask-position:center;-webkit-mask-size:contain;-webkit-mask-repeat:no-repeat;mask-position:center;mask-size:contain;mask-repeat:no-repeat}

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
#ppi-public-sphere .ppi-hotspot-map-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-chat-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-label-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-outline-pin .ppi-hotspot-pin-figure{
  filter:drop-shadow(0 3px 3px rgba(0,0,0,.48))!important;
}

/* 0.4.62.40: SVG pin shadow (correct pseudo-element selectors). Blur the outer unmasked shadow layer,
   while its pseudo-element carries the SVG silhouette. This produces a soft
   edge; JS translates this layer in sync with the double-hop pin animation. */
#ppi-public-sphere .ppi-hotspot-pin-shadow{display:none!important;}
#ppi-public-sphere .ppi-hotspot-map-pin .ppi-hotspot-pin-shadow,
#ppi-public-sphere .ppi-hotspot-chat-pin .ppi-hotspot-pin-shadow,
#ppi-public-sphere .ppi-hotspot-label-pin .ppi-hotspot-pin-shadow,
#ppi-public-sphere .ppi-hotspot-outline-pin .ppi-hotspot-pin-shadow{
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
#ppi-public-sphere .ppi-hotspot-map-pin .ppi-hotspot-pin-shadow::before,
#ppi-public-sphere .ppi-hotspot-chat-pin .ppi-hotspot-pin-shadow::before,
#ppi-public-sphere .ppi-hotspot-label-pin .ppi-hotspot-pin-shadow::before,
#ppi-public-sphere .ppi-hotspot-outline-pin .ppi-hotspot-pin-shadow::before{
  content:"";position:absolute;inset:0;
  background:rgba(0,0,0,.8);
  -webkit-mask-image:var(--ppi-shadow-mask,none);
  mask-image:var(--ppi-shadow-mask,none);
  -webkit-mask-position:center;-webkit-mask-size:contain;-webkit-mask-repeat:no-repeat;
  mask-position:center;mask-size:contain;mask-repeat:no-repeat;
}
#ppi-public-sphere .ppi-hotspot-map-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-chat-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-label-pin .ppi-hotspot-pin-figure,
#ppi-public-sphere .ppi-hotspot-outline-pin .ppi-hotspot-pin-figure{
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
</style>
