/* Photo Sphere – Standalone Panorama integration v0.4.30 (Nextcloud 35 / Viewer 8). */
(() => {
  'use strict';
  if (window.__photospherePhotosIntegrationLoaded) return;
  window.__photospherePhotosIntegrationLoaded = true;

  const FRAME_ID = 'ppi-photo-frame';
  const CLOSE_ID = 'ppi-close-360';
  const BADGE_ID = 'ppi-360-badge';
  const BADGE_WRAP_ID = 'ppi-360-badge-wrap';
  const BADGE_HINT_ID = 'ppi-360-hint';
  const ERROR_ID = 'ppi-status';
  const APP_ROUTE = /(?:\/index\.php)?\/apps\/(?:photos|files)(?:\/|$)/;
  const xmpCache = new Map();
  let requestedUrl = null;
  let requestSerial = 0;
  let currentPanorama = null;
  let opening = false;
  // Keyboard steering: sustained movement with all four arrow keys.
  const heldArrows = new Set();
  let steeringFrame = 0;
  let lastSteeringTime = 0;
  let velocityX = 0;
  let velocityY = 0;
  const STEER_ACCEL_SECONDS = 0.55;
  const STEER_DECEL_SECONDS = 0.45;
  let warnedMissingViewer = false;
  const ARROWS = new Set(['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown']);
  const TURN_SPEED = 0.65; // radians per second (about 37 degrees/second)
  // Hold Ctrl + ArrowUp/Down to zoom without interrupting ordinary arrow steering.
  const heldZoomKeys = new Set();
  let zoomFrame = 0;
  let lastZoomTime = 0;
  // Physical FOV degrees per second, independent of configured zoom range.
  const ZOOM_DEGREES_PER_SECOND = 34;
  const ZOOM_ACCEL_SECONDS = 0.90;
  const ZOOM_DECEL_SECONDS = 0.70;
  let zoomVelocity = 0;

  const ZOOM_DEFAULTS = {
    desktopMin: 60, desktopStart: 110, desktopMax: 135,
    mobileMin: 30, mobileStart: 75, mobileMax: 110
  };
  async function fetchZoomSettings() {
    try {
      const url = window.OC?.generateUrl?.('/apps/photosphere_photos_integration/zoom/settings');
      if (!url) return ZOOM_DEFAULTS;
      const response = await fetch(url, {credentials:'same-origin', cache:'no-store'});
      if (!response.ok) return ZOOM_DEFAULTS;
      const result = await response.json();
      return result?.success && result.zoom ? {...ZOOM_DEFAULTS, ...result.zoom} : ZOOM_DEFAULTS;
    } catch (error) {
      console.warn('[Photo Sphere] Zoom-Konfiguration nicht erreichbar:', error);
      return ZOOM_DEFAULTS;
    }
  }

  const WATERMARK_DEFAULTS = {
    enabled: false, active: 'none', mode: 'panorama', yaw: 0,
    pitchDesktop: 40, pitchMobile: 56,
    screenX: 88, screenY: 85,
    sizeDesktop: 196, sizeMobile: 108
  };
  const WATERMARK_OPACITY = 0.94;
  function photoShareToken() {
    const match = location.pathname.match(/(?:\/index\.php)?\/s\/([^/]+)/);
    return match ? decodeURIComponent(match[1]) : '';
  }
  async function fetchWatermarkSettings(photo = null) {
    try {
      const url = window.OC?.generateUrl?.('/apps/photosphere_photos_integration/watermark/settings');
      if (!url) return WATERMARK_DEFAULTS;
      const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
      if (!response.ok) return WATERMARK_DEFAULTS;
      const result = await response.json();
      if (!result?.success || !result.options) return WATERMARK_DEFAULTS;
      const options = { ...WATERMARK_DEFAULTS, ...result.options, active: result.active || 'none' };
      const fileId = Number(photo?.fileid);
      if (Number.isSafeInteger(fileId) && fileId > 0) {
        try {
          const token = photoShareToken();
          const base = window.OC.generateUrl('/apps/photosphere_photos_integration/watermark/resolve');
          const params = new URLSearchParams({fileId:String(fileId), shareToken:token});
          const response = await fetch(base + '?' + params.toString(), {credentials:'same-origin',cache:'no-store'});
          if (response.ok) {
            const matched = await response.json();
            if (matched?.success && typeof matched.active === 'string') {
              options.active = matched.active;
              const imageBase = window.OC.generateUrl('/apps/photosphere_photos_integration/watermark/context-image');
              options.imageUrl = imageBase + '?' + params.toString();
            }
          }
        } catch (error) { console.warn('[Photosphere Photos] Ordnerregel nicht verfügbar:', error); }
      }
      return options;
    } catch (error) {
      console.warn('[Photosphere Photos] Wasserzeichen-Einstellungen nicht verfügbar:', error);
      return WATERMARK_DEFAULTS;
    }
  }


  function inPublicFileShare() {
    return /(?:\/index\.php)?\/s\/[^/]+(?:\/|$)/.test(location.pathname);
  }
  function inPhotos() { return APP_ROUTE.test(location.pathname) || inPublicFileShare(); }
  function inFiles() { return /(?:\/index\.php)?\/apps\/files(?:\/|$)/.test(location.pathname); }
  function inPublicPhotos() {
    return /(?:\/index\.php)?\/apps\/photos\/public(?:\/|$)/.test(location.pathname);
  }
  function eligibleSource(source) {
    if (typeof source !== 'string') return false;
    try {
      const url = new URL(source, location.origin);
      const path = url.pathname;
      const authorizedSource = inPublicPhotos()
        ? path.includes('/remote.php/dav/') && path.includes('/photospublic/')
        : inPublicFileShare()
          ? path.includes('/public.php/dav/files/')
            || path.includes('/public.php/webdav/')
            || path.includes('/remote.php/dav/public-files/')
            || /(?:\/index\.php)?\/s\/[^/]+\/(?:download|preview)(?:\/|$)/.test(path)
          : path.includes('/remote.php/dav/files/');
      // For public share download/preview URLs the filename can live in a
      // query string, while the MIME type is checked separately in getPhoto().
      return url.origin === location.origin && authorizedSource
        && (inPublicFileShare() || /\.jpe?g$/i.test(path));
    } catch { return false; }
  }
  function viewModel() {
    const roots = [document.querySelector('#viewer'), document.querySelector('#viewer .viewer__file--active')];
    for (const root of roots) {
      let vm = root?.__vue__;
      const seen = new Set();
      while (vm && !seen.has(vm)) {
        if (vm.currentFile && ((inPublicPhotos() || inPublicFileShare())
          || (Array.isArray(vm.fileList) && typeof vm.currentIndex === 'number'))) return vm;
        seen.add(vm);
        vm = vm.$parent;
      }
    }
    return null;
  }
  function getPhoto() {
    if (!inPhotos()) return null;
    const vm = viewModel();
    if (!vm) return null;
    const file = vm.currentFile;
    if (!file || file.mime !== 'image/jpeg' || !eligibleSource(file.source)) return null;
    return {
      source: file.source,
      davPath: file.davPath,
      filename: file.filename || file.displayname || file.basename || new URL(file.source).pathname.split('/').pop(),
      fileid: file.fileid,
    };
  }

function assetUrl(fileName) {
  try {
    if (window.OC?.filePath) {
      return window.OC.filePath('photosphere_photos_integration', 'img', fileName);
    }
  } catch {}
  return `/custom_apps/photosphere_photos_integration/img/${fileName}`;
}

  function status(message) {
    let el = document.getElementById(ERROR_ID);
    if (!el) {
      el = document.createElement('div');
      el.id = ERROR_ID;
      Object.assign(el.style, {
        position: 'fixed', right: '18px', bottom: '78px', zIndex: '2147483645',
        background: '#242424', color: '#fff', padding: '10px 14px', borderRadius: '8px',
        maxWidth: 'min(460px,90vw)', font: '14px system-ui, sans-serif'
      });
      document.body.appendChild(el);
    }
    el.textContent = message;
    setTimeout(() => el.remove(), 5500);
  }
  function makeOverlayButton(id, label, small = false) {
    const button = document.createElement('button');
    button.type = 'button';
    button.id = id;
    button.textContent = label;
    Object.assign(button.style, {
      position: 'fixed',
      zIndex: '2147483645',
      background: 'rgba(8, 18, 28, .20)',
      color: 'rgba(255,255,255,.94)',
      border: '1px solid rgba(255,255,255,.88)',
      borderRadius: '999px',
      padding: small ? '9px 16px' : '16px 28px',
      cursor: 'pointer',
      font: small ? '700 14px system-ui, sans-serif' : '700 46px/1 system-ui, sans-serif',
      letterSpacing: '.04em',
      boxShadow: '0 10px 28px rgba(0,0,0,.14)',
      backdropFilter: 'blur(2px)',
      WebkitBackdropFilter: 'blur(2px)',
      transition: 'background .15s ease, box-shadow .12s ease, opacity .15s ease, transform .17s ease-out',
      transform: 'scale(1)',
      transformOrigin: 'center center',
      userSelect: 'none'
    });
    // Hover has no transform: the centered button must NEVER jump away from
    // translate(-50%, -50%). A shadow-based press effect works for both buttons.
    button.addEventListener('mouseenter', () => {
      button.style.transform = 'scale(1.045)';
      button.style.background = 'rgba(8, 18, 28, .28)';
      button.style.boxShadow = '0 7px 18px rgba(0,0,0,.20)';
    });
    button.addEventListener('mouseleave', () => {
      button.style.transform = 'scale(1)';
      button.style.background = 'rgba(8, 18, 28, .20)';
      button.style.boxShadow = '0 10px 28px rgba(0,0,0,.14)';
    });
    button.addEventListener('pointerdown', () => {
      button.style.transform = 'scale(.985)';
      button.style.background = 'rgba(8, 18, 28, .39)';
      button.style.boxShadow = 'inset 0 2px 7px rgba(0,0,0,.32)';
    });
    const resetPress = () => {
      button.style.transform = button.matches(':hover') ? 'scale(1.045)' : 'scale(1)';
      button.style.background = button.matches(':hover') ? 'rgba(8, 18, 28, .28)' : 'rgba(8, 18, 28, .20)';
      button.style.boxShadow = button.matches(':hover') ? '0 7px 18px rgba(0,0,0,.20)' : '0 10px 28px rgba(0,0,0,.14)';
    };
    button.addEventListener('pointerup', resetPress);
    button.addEventListener('pointercancel', resetPress);
    return button;
  }
  function removeBadge() { document.getElementById(BADGE_WRAP_ID)?.remove(); document.getElementById(BADGE_ID)?.remove(); }

  // Mobile Nextcloud Viewer may consume touch/pointer events on the document
  // before a regular click is dispatched to the 360-degree launcher. Intercept
  // just the centre launcher's own hit rectangle during the CAPTURE phase.
  // Crucially this never covers the Nextcloud image navigation arrows at the
  // left and right edges, nor the rest of the photograph.
  let mobileLaunchPointer = null;
  function isMobileLauncher() {
    return Boolean(window.matchMedia?.('(max-width: 700px)').matches
      && (window.matchMedia?.('(pointer: coarse)').matches || navigator.maxTouchPoints > 0));
  }
  function isWithinMobileLauncher(x, y) {
    const wrap = document.getElementById(BADGE_WRAP_ID);
    if (!wrap || !isMobileLauncher() || !wrap.isConnected || document.getElementById(FRAME_ID)) return false;
    const rect = wrap.getBoundingClientRect();
    return x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;
  }
  window.addEventListener('pointerdown', event => {
    mobileLaunchPointer = null;
    if (event.pointerType === 'mouse' || !event.isPrimary
      || !isWithinMobileLauncher(event.clientX, event.clientY)) return;
    mobileLaunchPointer = {id: event.pointerId, x: event.clientX, y: event.clientY};
    // No preventDefault: keep click and accessibility semantics as fallback.
    event.stopImmediatePropagation();
  }, true);
  window.addEventListener('pointerup', event => {
    const start = mobileLaunchPointer;
    mobileLaunchPointer = null;
    if (!start || event.pointerId !== start.id
      || Math.hypot(event.clientX-start.x, event.clientY-start.y) > 18
      || !isWithinMobileLauncher(event.clientX, event.clientY)) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    openPanorama();
  }, true);
  window.addEventListener('pointercancel', () => { mobileLaunchPointer = null; }, true);


  function getActiveVisualRect() {
    const visual = document.querySelector('#viewer .viewer__file--active img')
      || document.querySelector('#viewer .viewer__file--active canvas')
      || document.querySelector('#viewer .viewer__file--active');
    return visual?.getBoundingClientRect() || null;
  }


function ensureOpenBadge() {
  // Only on narrow touch devices, the full launcher (symbol, instructions and gap) is interactive.
  const isSmallTouch = isMobileLauncher();
  let wrap = document.getElementById(BADGE_WRAP_ID);
  let badge = document.getElementById(BADGE_ID);
  if (!wrap) {
    wrap = document.createElement(isSmallTouch ? 'button' : 'div');
    wrap.id = BADGE_WRAP_ID;
    Object.assign(wrap.style, {
      position: 'fixed',
      zIndex: '2147483645',
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      gap: isSmallTouch ? '3px' : '10px',
      // Never put an interactive box across Nextcloud's left/right image arrows.
      // Only the symbol, the hint and their narrow central bridge receive taps.
      pointerEvents: isSmallTouch ? 'auto' : 'none',
      cursor: isSmallTouch ? 'pointer' : 'default',
      padding: '0',
      border: 'none',
      appearance: 'none',
      WebkitAppearance: 'none',
      background: 'transparent',
      color: 'inherit',
      font: 'inherit',
      touchAction: isSmallTouch ? 'manipulation' : 'auto',
      userSelect: 'none'
    });
    // Real semantic button on mobile: all three centred visible parts share
    // one hit target; no fullscreen overlay and no side-arrow interception.
    if (isSmallTouch) {
      wrap.type = 'button';
      wrap.setAttribute('aria-label', '360°-Panorama öffnen');
      wrap.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        openPanorama();
      });
    }

    badge = document.createElement(isSmallTouch ? 'span' : 'button');
    if (!isSmallTouch) badge.type = 'button';
    badge.id = BADGE_ID;
    badge.title = 'In 360°-Ansicht öffnen';
    badge.setAttribute('aria-label', 'In 360°-Ansicht öffnen');
    Object.assign(badge.style, {
      appearance: 'none',
      WebkitAppearance: 'none',
      background: 'transparent',
      border: 'none',
      padding: '0',
      margin: '0',
      cursor: 'pointer',
      pointerEvents: isSmallTouch ? 'none' : 'auto',
      display: 'block',
      lineHeight: '0',
      transition: 'filter .15s ease, opacity .15s ease',
      filter: 'drop-shadow(0 10px 22px rgba(0,0,0,.26))',
      opacity: '.97'
    });
    const image = document.createElement('img');
    image.src = assetUrl('360-open-button.png');
    image.alt = '360° öffnen';
    image.draggable = false;
    Object.assign(image.style, {
      display: 'block',
      width: 'min(280px, 44vw)',
      // On narrow touch screens keep the open graphic unobtrusive.
      ...(window.matchMedia?.('(pointer: coarse) and (max-width: 700px)').matches
        ? {width:'min(145px, 35.65vw)'} : {}),
      maxWidth: '310px',
      height: 'auto',
      pointerEvents: 'none',
      transform: 'scale(1)',
      transformOrigin: 'center center',
      transition: 'transform .17s ease-out'
    });
    badge.appendChild(image);
    if (isSmallTouch) {
      // Recenter around the actual image height once the PNG has loaded.
      image.addEventListener('load', () => ensureOpenBadge(), {once: true});
    }
    badge.addEventListener('mouseenter', () => {
      image.style.transform = 'scale(1.045)';
      badge.style.filter = 'drop-shadow(0 12px 24px rgba(0,0,0,.32))';
      badge.style.opacity = '1';
    });
    badge.addEventListener('mouseleave', () => {
      image.style.transform = 'scale(1)';
      badge.style.filter = 'drop-shadow(0 10px 22px rgba(0,0,0,.26))';
      badge.style.opacity = '.97';
    });
    const setPressed = down => {
      image.style.transform = down ? 'scale(.985)' : (badge.matches(':hover') ? 'scale(1.045)' : 'scale(1)');
      badge.style.filter = down
        ? 'drop-shadow(0 6px 14px rgba(0,0,0,.32))'
        : (badge.matches(':hover')
          ? 'drop-shadow(0 12px 24px rgba(0,0,0,.32))'
          : 'drop-shadow(0 10px 22px rgba(0,0,0,.26))');
      badge.style.opacity = down ? '.92' : (badge.matches(':hover') ? '1' : '.97');
    };
    badge.addEventListener('pointerdown', () => setPressed(true));
    badge.addEventListener('pointerup', () => setPressed(false));
    badge.addEventListener('pointercancel', () => setPressed(false));
    if (!isSmallTouch) badge.addEventListener('click', event => {
      event.stopPropagation();
      openPanorama();
    });

    const hint = document.createElement('div');
    hint.id = BADGE_HINT_ID;
    // Each arrow gets its own thin, bright outline for better contrast.
    const arrow = label => `<span style="display:inline-flex;align-items:center;justify-content:center;min-width:${isSmallTouch ? 16 : 19}px;height:${isSmallTouch ? 17 : 20}px;box-sizing:border-box;border:1.5px solid rgba(255,255,255,.98);border-radius:5px;padding:0 3px;margin:0 1px;font-weight:800;color:#fff;">${label}</span>`;
    hint.innerHTML = '<div>Steuerung: ' + ['←', '↑', '↓', '→'].map(arrow).join('') + ' bewegen</div>'
      + '<div><strong>Strg + </strong>' + arrow('↑') + '/' + arrow('↓')
      + ' zoomen &nbsp;•&nbsp; <strong>Enter</strong> öffnen</div>';
    Object.assign(hint.style, {
      pointerEvents: 'none',
      cursor: isSmallTouch ? 'pointer' : 'default',
      touchAction: isSmallTouch ? 'manipulation' : 'auto',
      color: 'rgba(255,255,255,.98)',
      background: 'rgba(8, 18, 28, .34)',
      border: '1.5px solid rgba(255,255,255,.94)',
      borderRadius: '999px',
      padding: isSmallTouch ? '6px 9px' : '8px 14px',
      font: isSmallTouch ? '600 10px/1.4 system-ui, sans-serif' : '600 12px/1.5 system-ui, sans-serif',
      textAlign: 'center',
      boxShadow: '0 10px 22px rgba(0,0,0,.22), 0 0 0 1px rgba(255,255,255,.12)',
      textShadow: '0 1px 2px rgba(0,0,0,.24)',
      backdropFilter: 'blur(2px)',
      WebkitBackdropFilter: 'blur(2px)',
      maxWidth: isSmallTouch ? 'min(96vw, 340px)' : 'min(90vw, 390px)',
      whiteSpace: 'nowrap'
    });

    // On mobile the 360-degree graphic is the only visible launch control.
    // Keep the existing semantic button and its capture-phase touch handling.
    // Desktop still displays the full keyboard and mouse help below the icon.
    if (isSmallTouch) {
      wrap.append(badge);
    } else {
      wrap.append(badge, hint);
    }
    document.body.appendChild(wrap);
  }
  const rect = getActiveVisualRect();
  const centerX = rect && rect.width > 0 ? rect.left + rect.width / 2 : window.innerWidth / 2;
  const centerY = rect && rect.height > 0 ? rect.top + rect.height / 2 : window.innerHeight / 2;
  wrap.style.left = `${Math.round(centerX)}px`;
  if (isSmallTouch) {
    // Mobile launcher contains only the icon: center its actual rendered
    // height on the active Nextcloud image, not the viewport or a hint group.
    const badgeHeight = badge?.getBoundingClientRect().height || 0;
    wrap.style.top = `${Math.round(centerY - badgeHeight / 2)}px`;
    wrap.style.transform = 'translateX(-50%)';
  } else {
    wrap.style.top = `${Math.round(centerY)}px`;
    wrap.style.transform = 'translate(-50%, -50%)';
  }
}



  // Integrierter 360°-Betrachter, ohne Abhängigkeit von files_photospheres.
  // Ein flächendeckendes WebGL-Fragmentshader-Rendering bildet eine equirektanguläre
  // JPEG-Textur auf eine virtuelle Kugel ab. Nextcloud-Gastberechtigungen bleiben
  // unverändert: Das JPEG wird nur über seine bereits autorisierte URL geladen.
  let currentViewer = null;
  function createPanoramaViewer(container, url, xmp, watermarkOptions = WATERMARK_DEFAULTS, zoomOptions = ZOOM_DEFAULTS) {
    const canvas = document.createElement('canvas');
    canvas.setAttribute('aria-label', 'Interaktives 360°-Panorama');
    Object.assign(canvas.style, {
      width: '100%', height: '100%', display: 'block', cursor: 'grab',
      touchAction: 'none', background: '#000'
    });
    // First completed 3D image begins gently blurred and becomes sharp
    // when the loading veil fades away (instead of an abrupt hard cut).
    canvas.style.filter = 'blur(15px)';
    canvas.style.transform = 'scale(1.035)';
    canvas.style.transformOrigin = 'center center';
    canvas.style.transition = 'filter .72s ease-out, transform .72s ease-out';
    container.appendChild(canvas);
    const watermarkEnabled = watermarkOptions.enabled !== false && watermarkOptions.active !== 'none';
    const watermarkMode = watermarkOptions.mode === 'screen' ? 'screen' : 'panorama';
    const mobileWatermark = window.matchMedia?.('(pointer: coarse) and (max-width: 700px)').matches || false;
    const watermark = document.createElement('img');
    if (watermarkEnabled) {
      watermark.src = watermarkOptions.imageUrl || window.OC?.generateUrl?.('/apps/photosphere_photos_integration/watermark')
        || assetUrl('watermark-napohd.png');
    }
    watermark.alt = 'NapoHD Wasserzeichen';
    watermark.draggable = false;
    const configuredSize = mobileWatermark ? Number(watermarkOptions.sizeMobile) : Number(watermarkOptions.sizeDesktop);
    const displaySize = Number.isFinite(configuredSize) ? Math.max(30, Math.min(500, configuredSize)) : (mobileWatermark ? 108 : 196);
    Object.assign(watermark.style, {
      position: 'absolute',
      left: '50%',
      top: '18%',
      width: `${displaySize}px`,
      maxWidth: '85vw',
      height: 'auto',
      transform: 'translate(-50%, -50%)',
      transformOrigin: 'center center',
      opacity: '0',
      pointerEvents: 'none',
      userSelect: 'none',
      zIndex: '1',
      filter: 'drop-shadow(0 10px 18px rgba(0,0,0,.24))',
      transition: 'opacity .26s ease, transform .16s ease-out',
      willChange: 'transform, opacity, left, top'
    });
    if (watermarkEnabled) container.appendChild(watermark);
    const gl = canvas.getContext('webgl', { alpha: false, antialias: true, preserveDrawingBuffer: false });
    if (!gl) throw new Error('WebGL ist im Browser nicht verfügbar.');
    const vs = `attribute vec2 point; varying vec2 pixel;
      void main(){pixel=point;gl_Position=vec4(point,0.,1.);}`;
    const fs = `precision highp float;
      varying vec2 pixel;
      uniform sampler2D panorama;
      uniform float yaw, pitch, halfFov, aspect;
      uniform vec4 crop; // x = links, y = oben, z = Breite, w = Höhe (jeweils in Vollbildanteilen)
      const float PI=3.14159265358979323846264;
      void main(){
        vec3 forward=vec3(sin(yaw)*cos(pitch),sin(pitch),-cos(yaw)*cos(pitch));
        vec3 right=vec3(cos(yaw),0.,sin(yaw));
        vec3 up=normalize(cross(right,forward));
        vec3 ray=normalize(forward + right*pixel.x*tan(halfFov)
           + up*pixel.y*tan(halfFov)/aspect);
        float longitude=atan(ray.x,-ray.z);
        float latitude=asin(clamp(ray.y,-1.,1.));
        vec2 spherical=vec2(fract(longitude/(2.*PI)+.5),.5+latitude/PI);
        // crop.y is measured from the upper left; texture v increases upwards.
        vec2 uv=vec2((spherical.x-crop.x)/crop.z,
                     (spherical.y-(1.-crop.y-crop.w))/crop.w);
        if(uv.x<0.||uv.x>1.||uv.y<0.||uv.y>1.) {
          gl_FragColor=vec4(0.,0.,0.,1.);
        } else {
          gl_FragColor=texture2D(panorama,uv);
        }
      }`;
    function shader(type, code) {
      const obj = gl.createShader(type);
      gl.shaderSource(obj, code);
      gl.compileShader(obj);
      if (!gl.getShaderParameter(obj, gl.COMPILE_STATUS)) {
        const message = gl.getShaderInfoLog(obj);
        gl.deleteShader(obj);
        throw new Error('WebGL-Shaderfehler: ' + message);
      }
      return obj;
    }
    const program = gl.createProgram();
    const vshader = shader(gl.VERTEX_SHADER, vs);
    const fshader = shader(gl.FRAGMENT_SHADER, fs);
    gl.attachShader(program, vshader);
    gl.attachShader(program, fshader);
    gl.linkProgram(program);
    if (!gl.getProgramParameter(program, gl.LINK_STATUS))
      throw new Error('WebGL-Programm konnte nicht erstellt werden.');
    gl.useProgram(program);
    const vertices = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, vertices);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1,-1,1,-1,-1,1,1,1]), gl.STATIC_DRAW);
    const aPoint = gl.getAttribLocation(program, 'point');
    gl.enableVertexAttribArray(aPoint);
    gl.vertexAttribPointer(aPoint, 2, gl.FLOAT, false, 0, 0);
    const u = name => gl.getUniformLocation(program, name);
    const uniforms = {yaw:u('yaw'),pitch:u('pitch'),fov:u('halfFov'),
      aspect:u('aspect'),crop:u('crop'),panorama:u('panorama')};
    gl.uniform1i(uniforms.panorama, 0);
    const texture = gl.createTexture();
    gl.bindTexture(gl.TEXTURE_2D, texture);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
    gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL, true);
    // Wider default view and much wider zoom-out range than 0.4.0.
    const mobileTouch = window.matchMedia?.('(pointer: coarse)').matches || false;
    const prefix = mobileTouch ? 'mobile' : 'desktop';
    const MIN_FOV = Number(zoomOptions[prefix+'Min']) || 30;
    const MAX_FOV = Number(zoomOptions[prefix+'Max']) || (mobileTouch ? 110 : 135);
    let yaw=0, pitch=0, hfov=Number(zoomOptions[prefix+'Start']) || (mobileTouch ? 75 : 110), dead=false, imageReady=false;
    const limit=(n,min,max)=>Math.max(min,Math.min(max,n));
    let firstFrameSent=false;
    let raf=0;
    let resized=false;
    const resizeObserver = typeof ResizeObserver === 'function'
      ? new ResizeObserver(() => requestDraw()) : null;
    resizeObserver?.observe(container);
    function updateWatermark() {
      if (!watermarkEnabled || !watermark.isConnected) return;
      if (!imageReady) {
        watermark.style.opacity = '0';
        return;
      }
      if (watermarkMode === 'screen') {
        const screenX = Math.max(0, Math.min(100, Number(watermarkOptions.screenX) || 0));
        const screenY = Math.max(0, Math.min(100, Number(watermarkOptions.screenY) || 0));
        watermark.style.left = `${screenX}%`;
        watermark.style.top = `${screenY}%`;
        watermark.style.transform = 'translate(-50%, -50%)';
        watermark.style.opacity = `${WATERMARK_OPACITY}`;
        return;
      }
      const w = Math.max(1, canvas.clientWidth);
      const h = Math.max(1, canvas.clientHeight);
      const aspect = w / h;
      const halfFov = hfov * Math.PI / 360;
      const forward = { x: Math.sin(yaw) * Math.cos(pitch), y: Math.sin(pitch), z: -Math.cos(yaw) * Math.cos(pitch) };
      const right = { x: Math.cos(yaw), y: 0, z: Math.sin(yaw) };
      const up = {
        x: right.y * forward.z - right.z * forward.y,
        y: right.z * forward.x - right.x * forward.z,
        z: right.x * forward.y - right.y * forward.x
      };
      const upLen = Math.hypot(up.x, up.y, up.z) || 1;
      up.x /= upLen; up.y /= upLen; up.z /= upLen;
      const yawSetting = Number(watermarkOptions.yaw);
      const pitchSetting = Number(mobileTouch ? watermarkOptions.pitchMobile : watermarkOptions.pitchDesktop);
      const watermarkYaw = (Number.isFinite(yawSetting) ? yawSetting : 0) * Math.PI / 180;
      const watermarkPitch = (Number.isFinite(pitchSetting) ? pitchSetting : (mobileTouch ? 56 : 40)) * Math.PI / 180;
      const dir = {
        x: Math.sin(watermarkYaw) * Math.cos(watermarkPitch),
        y: Math.sin(watermarkPitch),
        z: -Math.cos(watermarkYaw) * Math.cos(watermarkPitch)
      };
      const dz = dir.x * forward.x + dir.y * forward.y + dir.z * forward.z;
      if (!(dz > 0.05)) {
        watermark.style.opacity = '0';
        return;
      }
      const dx = dir.x * right.x + dir.y * right.y + dir.z * right.z;
      const dy = dir.x * up.x + dir.y * up.y + dir.z * up.z;
      const px = (dx / dz) / Math.tan(halfFov);
      const py = (dy / dz) * aspect / Math.tan(halfFov);
      if (!Number.isFinite(px) || !Number.isFinite(py) || Math.abs(px) > 1.18 || Math.abs(py) > 1.12) {
        watermark.style.opacity = '0';
        return;
      }
      const screenX = (px + 1) * 0.5 * w;
      const screenY = (1 - py) * 0.5 * h;
      const nearEdgeX = Math.max(0, Math.abs(px) - 0.78) / 0.4;
      const nearEdgeY = Math.max(0, Math.abs(py) - 0.72) / 0.4;
      const edgeFactor = Math.max(nearEdgeX, nearEdgeY);
      const scale = Math.max(0.82, 1 - edgeFactor * 0.16);
      watermark.style.left = `${screenX}px`;
      watermark.style.top = `${screenY}px`;
      watermark.style.transform = `translate(-50%, -50%) scale(${scale.toFixed(3)})`;
      watermark.style.opacity = `${Math.max(0, WATERMARK_OPACITY * (1 - edgeFactor * 0.3)).toFixed(3)}`;
    }

    function draw() {
      raf=0;
      if (dead || !imageReady || !canvas.isConnected) return;
      const scale=Math.min(window.devicePixelRatio || 1,2);
      const w=Math.max(1,Math.floor(canvas.clientWidth*scale));
      const h=Math.max(1,Math.floor(canvas.clientHeight*scale));
      if (w!==canvas.width || h!==canvas.height) {
        canvas.width=w;canvas.height=h;gl.viewport(0,0,w,h);
      }
      const aspect=w/h;
      gl.uniform1f(uniforms.yaw,yaw);
      gl.uniform1f(uniforms.pitch,pitch);
      gl.uniform1f(uniforms.fov,hfov*Math.PI/360);
      gl.uniform1f(uniforms.aspect,aspect);
      gl.uniform4fv(uniforms.crop,crop);
      gl.drawArrays(gl.TRIANGLE_STRIP,0,4);
      updateWatermark();
      if (!firstFrameSent) {
        firstFrameSent = true;
        container.dispatchEvent(new Event('ppi-image-ready'));
      }
    }
    function requestDraw(){if(!raf&&!dead)raf=requestAnimationFrame(draw);}
    function cropValue() {
      const cfg=xmp?.croppingConfig || xmp?.panoData || {};
      const value=(...keys)=>{
        for(const key of keys){const num=Number(cfg[key]);if(Number.isFinite(num)&&num>0)return num;}
        return null;
      };
      const fullW=value('fullWidth','fullPanoWidthPixels','FullPanoWidthPixels');
      const fullH=value('fullHeight','fullPanoHeightPixels','FullPanoHeightPixels');
      const cropW=value('croppedWidth','croppedAreaImageWidthPixels','CroppedAreaImageWidthPixels');
      const cropH=value('croppedHeight','croppedAreaImageHeightPixels','CroppedAreaImageHeightPixels');
      const left=Number(cfg.croppedX ?? cfg.croppedAreaLeftPixels ?? cfg.CroppedAreaLeftPixels ?? 0);
      const top=Number(cfg.croppedY ?? cfg.croppedAreaTopPixels ?? cfg.CroppedAreaTopPixels ?? 0);
      if(fullW&&fullH&&cropW&&cropH&&cropW<=fullW&&cropH<=fullH&&left>=0&&top>=0)
        return new Float32Array([left/fullW,top/fullH,cropW/fullW,cropH/fullH]);
      return new Float32Array([0,0,1,1]);
    }
    // Optional motion mode: sensor orientation is converted to camera-space
    // orientation, including device landscape rotation. No raw alpha Euler
    // subtraction: that jumps when the phone tilts or crosses the 0/360 seam.
    const motionAvailable = mobileTouch && window.isSecureContext && 'DeviceOrientationEvent' in window;
    let motionEnabled=false, motionAnchor=null, motionFrame=0, motionLast=0;
    let motionTargetYaw=0, motionTargetPitch=0;
    let sensorScreenAngle=null, sensorLastHeading=null;
    // A single sliding control: its border and background belong to the
    // outer shell. The integrated grip has no separate frame or decoration.
    // Hide the entire control until the panorama's loading overlay and blur
    // transition have completed.
    let gyroOpen=false;
    let gyroHintTimer=0;
    let gyroHintAnimation=null;
    const gyroShell=document.createElement('div');
    const gyroTab=document.createElement('button');
    const gyroPanel=document.createElement('div');
    const gyroSwitch=document.createElement('button');
    if (motionAvailable) {
      const CLOSED_OFFSET='translateY(-50%) translateX(calc(100% - 14px))';
      const OPEN_OFFSET='translateY(-50%) translateX(0)';
      Object.assign(gyroShell.style,{
        position:'absolute',right:'0',top:'76%',zIndex:'4',
        display:'flex',alignItems:'stretch',gap:'0',width:'min(198px, 92vw)',
        boxSizing:'border-box',overflow:'hidden',
        background:'rgba(8, 18, 28, .20)',color:'#fff',
        border:'1px solid rgba(255,255,255,.88)',borderRight:'none',
        borderRadius:'13px 0 0 13px',
        backdropFilter:'blur(2px)',WebkitBackdropFilter:'blur(2px)',
        boxShadow:'0 10px 28px rgba(0,0,0,.14)',
        transform:CLOSED_OFFSET,
        transition:'transform .29s cubic-bezier(.2,.8,.25,1)',
        visibility:'hidden',pointerEvents:'none'
      });
      gyroPanel.setAttribute('aria-label','Gyroskop-Steuerung');
      Object.assign(gyroPanel.style,{
        flex:'1 1 auto',minWidth:'0',padding:'10px 25px 10px 5px',
        boxSizing:'border-box',display:'flex',flexDirection:'column',
        alignItems:'center',justifyContent:'center',textAlign:'center',
        opacity:'0',visibility:'hidden',pointerEvents:'none',
        transition:'opacity .2s ease, visibility .2s ease'
      });
      const heading=document.createElement('div');
      heading.textContent='Bewegungssteuerung';
      Object.assign(heading.style,{
        font:'600 12px system-ui,sans-serif',marginBottom:'7px',
        textShadow:'0 1px 3px rgba(0,0,0,.6)'
      });
      gyroSwitch.type='button';
      gyroSwitch.textContent='Gyroskop: Aus';
      Object.assign(gyroSwitch.style,{
        background:'rgba(8, 18, 28, .20)',color:'#fff',
        border:'1px solid rgba(255,255,255,.88)',borderRadius:'999px',
        padding:'8px 10px',font:'600 12px system-ui,sans-serif',
        cursor:'pointer',touchAction:'manipulation',whiteSpace:'nowrap',maxWidth:'100%',
        textShadow:'0 1px 3px rgba(0,0,0,.6)'
      });
      gyroPanel.append(heading,gyroSwitch);
      gyroTab.type='button';
      gyroTab.textContent='';
      gyroTab.title='Gyroskop-Steuerung aufklappen';
      gyroTab.setAttribute('aria-label','Gyroskop-Steuerung öffnen');
      gyroTab.setAttribute('aria-expanded','false');
      Object.assign(gyroTab.style,{
        flex:'0 0 14px',alignSelf:'stretch',minHeight:'72px',
        background:'transparent',border:'0',borderRadius:'0',padding:'0',
        cursor:'pointer',touchAction:'manipulation',
        WebkitTapHighlightColor:'transparent'
      });
      function stopGyroHint() {
        if (gyroHintTimer) clearTimeout(gyroHintTimer);
        gyroHintTimer=0;
        if (gyroHintAnimation) { gyroHintAnimation.cancel(); gyroHintAnimation=null; }
      }
      function setGyroPanelOpen(open) {
        if (dead || !gyroShell.isConnected) return;
        stopGyroHint();
        gyroOpen=Boolean(open);
        gyroShell.style.transform=gyroOpen ? OPEN_OFFSET : CLOSED_OFFSET;
        gyroPanel.style.opacity=gyroOpen ? '1' : '0';
        gyroPanel.style.visibility=gyroOpen ? 'visible' : 'hidden';
        gyroPanel.style.pointerEvents=gyroOpen ? 'auto' : 'none';
        gyroTab.setAttribute('aria-expanded',String(gyroOpen));
        gyroTab.setAttribute('aria-label',gyroOpen
          ? 'Gyroskop-Steuerung schließen' : 'Gyroskop-Steuerung öffnen');
      }
      // The entire sliding surface acts as the open/close touch target.
      // Only the dedicated gyro switch has its own independent action.
      gyroShell.addEventListener('click', event => {
        if (event.target === gyroSwitch || gyroSwitch.contains(event.target)) return;
        setGyroPanelOpen(!gyroOpen);
      });
      gyroSwitch.addEventListener('click', event => {
        // Do not let the click reach the shell and immediately reopen it.
        event.stopPropagation();
        setGyroPanelOpen(false);
      });
      // The panel and its grip share one parent border/background. The grip
      // is the LEFT edge of the sliding surface so it remains visible and clickable
      // while the rest of the shell is translated off the right screen edge.
      gyroShell.append(gyroTab,gyroPanel);
      container.appendChild(gyroShell);
      container.addEventListener('ppi-image-ready', () => {
        // The first GPU frame still has a loading veil (650 ms) and a canvas
        // blur transition (720 ms). Only reveal the grip after both finish.
        gyroHintTimer=setTimeout(() => {
          gyroHintTimer=0;
          if (dead || !gyroShell.isConnected) return;
          gyroShell.style.visibility='visible';
          gyroShell.style.pointerEvents='auto';
          if (gyroOpen || window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
              || typeof gyroShell.animate!=='function') return;
          gyroHintAnimation=gyroShell.animate([
            {transform:CLOSED_OFFSET,offset:0},
            {transform:'translateY(-50%) translateX(calc(100% - 34px))',offset:.42},
            {transform:'translateY(-50%) translateX(calc(100% - 34px))',offset:.58},
            {transform:CLOSED_OFFSET,offset:1}
          ],{duration:780,iterations:2,easing:'ease-in-out'});
          gyroHintAnimation.addEventListener('finish',()=>{
            gyroHintAnimation=null;
          },{once:true});
        },1500);
      },{once:true});
    }
    function shortestAngle(value){return Math.atan2(Math.sin(value),Math.cos(value));}
    function quatMultiply(a,b){return {
      x:a.w*b.x+a.x*b.w+a.y*b.z-a.z*b.y,
      y:a.w*b.y-a.x*b.z+a.y*b.w+a.z*b.x,
      z:a.w*b.z+a.x*b.y-a.y*b.x+a.z*b.w,
      w:a.w*b.w-a.x*b.x-a.y*b.y-a.z*b.z
    };}
    function quatAxis(x,y,z,angle){
      const t=angle/2;return {x:x*Math.sin(t),y:y*Math.sin(t),z:z*Math.sin(t),w:Math.cos(t)};
    }
    function vectorByQuat(q,v){
      const t=quatMultiply(quatMultiply(q,{...v,w:0}),{x:-q.x,y:-q.y,z:-q.z,w:q.w});
      return {x:t.x,y:t.y,z:t.z};
    }
    function orientationDirection(a,b,g,angle) {
      const rad=Math.PI/180;
      const alpha=a*rad, beta=b*rad, gamma=g*rad;
      // DeviceOrientationControls' intrinsic Z-X'-Y'' order represented by
      // YXZ Euler (beta,alpha,-gamma), then phone-camera and screen rotations.
      const cx=Math.cos(beta/2),sx=Math.sin(beta/2);
      const cy=Math.cos(alpha/2),sy=Math.sin(alpha/2);
      const cz=Math.cos(-gamma/2),sz=Math.sin(-gamma/2);
      const euler={
        x:sx*cy*cz+cx*sy*sz,
        y:cx*sy*cz-sx*cy*sz,
        z:cx*cy*sz-sx*sy*cz,
        w:cx*cy*cz+sx*sy*sz
      };
      const phoneCamera=quatAxis(1,0,0,-Math.PI/2);
      const screenRotation=quatAxis(0,0,1,-angle*rad);
      const q=quatMultiply(quatMultiply(euler,phoneCamera),screenRotation);
      return vectorByQuat(q,{x:0,y:0,z:-1});
    }
    function orientationHandler(event) {
      if (!motionEnabled || dead) return;
      if (event.alpha==null || event.beta==null || event.gamma==null) return;
      const a=Number(event.alpha), b=Number(event.beta), g=Number(event.gamma);
      if (![a,b,g].every(Number.isFinite)) return;
      const angle=Number(screen.orientation?.angle ?? window.orientation ?? 0);
      if (!Number.isFinite(angle)) return;
      const dir=orientationDirection(a,b,g,angle);
      const horizontal=Math.hypot(dir.x,dir.z);
      // When pointed almost at the zenith, heading is mathematically unstable.
      // Hold previous heading to avoid a sudden 180-degree rotation.
      const heading=horizontal>.17?Math.atan2(dir.x,-dir.z):sensorLastHeading;
      if (heading===null) return;
      const elevation=Math.asin(limit(dir.y,-1,1));
      if (!motionAnchor || angle!==sensorScreenAngle) {
        motionAnchor={heading,initialHeading:heading,elevation,yaw,pitch};
        sensorScreenAngle=angle;
        sensorLastHeading=heading;
        motionTargetYaw=yaw;motionTargetPitch=pitch;
        return;
      }
      // Maintain continuous heading across the 360-degree wrap. The horizontal
      // motion should follow the device turn (reversed from 0.4.20).
      const delta=shortestAngle(heading-sensorLastHeading);
      sensorLastHeading=heading;
      if (Number.isFinite(delta) && Math.abs(delta)<.70) {
        motionAnchor.heading += delta;
      } else if (Math.abs(delta)>=.70) {
        // Ignore occasional browser sensor spikes rather than jumping.
        return;
      }
      motionTargetYaw=motionAnchor.yaw+(motionAnchor.heading-motionAnchor.initialHeading);
      motionTargetPitch=limit(motionAnchor.pitch+(elevation-motionAnchor.elevation),
        -Math.PI/2+.02,Math.PI/2-.02);
    }
    function motionTick(now) {
      motionFrame=0;
      if (!motionEnabled || dead || !canvas.isConnected) return;
      const dt=motionLast?Math.min((now-motionLast)/1000,.08):1/60;
      motionLast=now;
      // Compromise between the responsive 0.4.24 (0.16 s) and the very
      // damped 0.4.25 (0.22 s). Sensor noise smaller than ~0.06 degrees
      // is ignored, avoiding tiny back-and-forth corrections on mobile.
      const factor=1-Math.exp(-dt/.18);
      const deadZone=0.001;
      const yawError=shortestAngle(motionTargetYaw-yaw);
      const pitchError=motionTargetPitch-pitch;
      if (Math.abs(yawError)>deadZone) yaw+=(yawError-Math.sign(yawError)*deadZone)*factor;
      if (Math.abs(pitchError)>deadZone) {
        pitch=limit(pitch+(pitchError-Math.sign(pitchError)*deadZone)*factor,
          -Math.PI/2+.02,Math.PI/2-.02);
      }
      requestDraw();
      motionFrame=requestAnimationFrame(motionTick);
    }
    function stopMotion() {
      motionEnabled=false;motionAnchor=null;motionLast=0;
      sensorLastHeading=null;sensorScreenAngle=null;
      window.removeEventListener('deviceorientation',orientationHandler);
      if (motionFrame) cancelAnimationFrame(motionFrame);
      motionFrame=0;
      if (gyroSwitch.isConnected) gyroSwitch.textContent='Gyroskop: Aus';
    }
    if (motionAvailable) gyroSwitch.addEventListener('click',async () => {
      if (motionEnabled) {stopMotion();return;}
      try {
        if (typeof DeviceOrientationEvent.requestPermission === 'function') {
          const permission=await DeviceOrientationEvent.requestPermission();
          if (permission!=='granted') throw new Error('Sensorzugriff nicht erlaubt');
        }
        motionAnchor=null;sensorLastHeading=null;sensorScreenAngle=null;
        motionTargetYaw=yaw;motionTargetPitch=pitch;
        motionEnabled=true;
        gyroSwitch.textContent='Gyroskop: Ein';
        window.addEventListener('deviceorientation',orientationHandler);
        motionFrame=requestAnimationFrame(motionTick);
      } catch(error) {
        stopMotion();gyroSwitch.textContent='Gyroskop nicht verfügbar';
        console.warn('[Photo Sphere] Gyroskop:',error);
      }
    });
    const crop=cropValue();
    // Touch swipes move the view directly with the finger rather than following
    // it via an easing target. This avoids perceptible lag on phones.
    // Pinch zoom retains an anchor distance and FOV to avoid incremental jitter.
    const pointers = new Map();
    let lastPinchDistance = null;
    let pinchAnchorDistance = null;
    let pinchAnchorFov = null;
    let singlePointer = null;
    let touchYawTarget = yaw, touchPitchTarget = pitch;
    let touchFovTarget = hfov;
    function pinchDistance() {
      const values = Array.from(pointers.values());
      if (values.length < 2) return null;
      return Math.hypot(values[0].x - values[1].x, values[0].y - values[1].y);
    }
    function setFov(value) {
      if (dead || !Number.isFinite(value)) return;
      hfov = limit(value, MIN_FOV, MAX_FOV);
      touchFovTarget = hfov;
      requestDraw();
    }
    const onDown = e => {
      if (motionEnabled && e.pointerType === 'touch') stopMotion();
      if (e.pointerType === 'mouse' && e.button !== 0) return;
      const touch = e.pointerType === 'touch';
      pointers.set(e.pointerId, {x:e.clientX, y:e.clientY, touch});
      try { canvas.setPointerCapture(e.pointerId); } catch {}
      if (pointers.size === 1) {
        singlePointer = {id:e.pointerId, x:e.clientX, y:e.clientY};
        touchYawTarget = yaw;
        touchPitchTarget = pitch;
        touchFovTarget = hfov;
        lastPinchDistance = null;
        pinchAnchorDistance = null;
        canvas.style.cursor = 'grabbing';
      } else {
        singlePointer = null;
        pinchAnchorDistance = pinchDistance();
        lastPinchDistance = pinchAnchorDistance;
        pinchAnchorFov = touchFovTarget;
      }
      e.preventDefault();
    };
    const onMove = e => {
      if (!pointers.has(e.pointerId)) return;
      const point = pointers.get(e.pointerId);
      pointers.set(e.pointerId, {x:e.clientX, y:e.clientY, touch:point.touch});
      if (pointers.size >= 2) {
        const distance = pinchDistance();
        if (distance && pinchAnchorDistance > 1 && distance > 1) {
          const requested = 2 * Math.atan(Math.tan(pinchAnchorFov * Math.PI / 360)
            * pinchAnchorDistance / distance) * 180 / Math.PI;
          // Track pinch fingers immediately, on the same pointer event.
          setFov(requested);
        }
        lastPinchDistance = distance;
      } else if (singlePointer && e.pointerId === singlePointer.id) {
        const dx=e.clientX-singlePointer.x, dy=e.clientY-singlePointer.y;
        singlePointer.x=e.clientX; singlePointer.y=e.clientY;
        const touch = point.touch;
        // Use the actual viewport width for direct finger-to-panorama motion.
        // The former height-based denominator reduced the movement too much
        // on portrait smartphones, making the image lag behind the finger.
        const scale = 1;
        const width = Math.max(1, canvas.clientWidth);
        const horizontal = dx * (hfov*Math.PI/180) / width * scale;
        const vertical = dy * (hfov*Math.PI/180) / width * scale;
        // Mouse and touch now update yaw/pitch directly; keyboard still uses
        // its own smooth acceleration and braking.
        yaw -= horizontal;
        pitch = limit(pitch+vertical,-Math.PI/2+.02,Math.PI/2-.02);
        touchYawTarget=yaw;
        touchPitchTarget=pitch;
        requestDraw();
      }
      e.preventDefault();
    };
    const onUp = e => {
      pointers.delete(e.pointerId);
      lastPinchDistance=null;
      pinchAnchorDistance=null;
      pinchAnchorFov=null;
      if (pointers.size===1) {
        const [id, p] = pointers.entries().next().value;
        singlePointer={id, x:p.x, y:p.y};
      } else singlePointer=null;
      canvas.style.cursor=pointers.size ? 'grabbing' : 'grab';
    };
    const onWheel = e => {e.preventDefault();setFov(hfov+Math.sign(e.deltaY)*4);};
    canvas.addEventListener('pointerdown',onDown);
    canvas.addEventListener('pointermove',onMove);
    canvas.addEventListener('pointerup',onUp);
    canvas.addEventListener('pointercancel',onUp);
    canvas.addEventListener('lostpointercapture',onUp);
    canvas.addEventListener('wheel',onWheel,{passive:false});
    canvas.addEventListener('webglcontextlost', event => {
      event.preventDefault();
      if (!dead) container.dispatchEvent(new CustomEvent('ppi-image-error', {
        detail: 'Die 3D-Grafikverbindung wurde unterbrochen.'
      }));
    });
    const image = new Image();
    // Download the JPEG as a stream so the progress reflects real received bytes,
    // rather than an animation that starts after the GPU render is already ready.
    const downloadController = new AbortController();
    let imageBlobUrl = null;
    const reportProgress = (phase, loaded = 0, total = null) => {
      if (!dead && container.isConnected) {
        container.dispatchEvent(new CustomEvent('ppi-image-progress', {
          detail: {
            phase, loaded, total,
            percent: total && total > 0 ? Math.min(100, Math.round(100 * loaded / total)) : null
          }
        }));
      }
    };
    async function loadPanoramaImage() {
      try {
        reportProgress('download');
        const response = await fetch(url, {
          method: 'GET', credentials: 'same-origin', signal: downloadController.signal
        });
        if (!response.ok) throw new Error(`JPEG HTTP ${response.status}`);
        const lengthHeader = Number(response.headers.get('content-length'));
        const total = Number.isSafeInteger(lengthHeader) && lengthHeader > 0 ? lengthHeader : null;
        // Some Nextcloud backends omit Content-Length. Then the progress is
        // indeterminate: never pretend that an arbitrary percentage is real.
        const reader = response.body?.getReader();
        let blob;
        if (reader) {
          const chunks = [];
          let loaded = 0;
          while (true) {
            const {value, done} = await reader.read();
            if (done) break;
            if (!value?.byteLength) continue;
            chunks.push(value);
            loaded += value.byteLength;
            reportProgress('download', loaded, total);
          }
          blob = new Blob(chunks, {type:'image/jpeg'});
          reportProgress('decode', loaded, total);
        } else {
          blob = await response.blob();
          reportProgress('decode', blob.size, total);
        }
        if (dead || downloadController.signal.aborted) return;
        // Decode the exact bytes already downloaded. No second network GET.
        imageBlobUrl = URL.createObjectURL(blob);
        image.src = imageBlobUrl;
      } catch (error) {
        if (dead || downloadController.signal.aborted) return;
        // Compatibility fallback for unusual servers denying a streaming fetch.
        // Do not fabricate byte percentages in this mode.
        console.warn('[Photosphere Photos] Fortschrittsdownload nicht verfügbar, direkter Bildabruf:', error);
        reportProgress('fallback');
        image.src = url;
      }
    }
    image.onload=()=>{
      if(dead)return;
      try {
        reportProgress('render');
        let source=image;
        const max=gl.getParameter(gl.MAX_TEXTURE_SIZE);
        if(image.naturalWidth>max || image.naturalHeight>max){
          // Large DJI panos: scale to the actual GPU texture limit, not a fixed size.
          const factor=Math.min(max/image.naturalWidth,max/image.naturalHeight);
          const resizedImage=document.createElement('canvas');
          resizedImage.width=Math.max(1,Math.floor(image.naturalWidth*factor));
          resizedImage.height=Math.max(1,Math.floor(image.naturalHeight*factor));
          const ctx=resizedImage.getContext('2d');
          if(!ctx)throw new Error('Bildkonvertierung nicht verfügbar');
          ctx.drawImage(image,0,0,resizedImage.width,resizedImage.height);
          source=resizedImage;
        }
        gl.activeTexture(gl.TEXTURE0);
        gl.bindTexture(gl.TEXTURE_2D,texture);
        gl.texImage2D(gl.TEXTURE_2D,0,gl.RGBA,gl.RGBA,gl.UNSIGNED_BYTE,source);
        const glError=gl.getError();
        if(glError!==gl.NO_ERROR)throw new Error('GPU-Bildfehler '+glError);
        imageReady=true;requestDraw();
      }catch(error){ container.dispatchEvent(new CustomEvent('ppi-image-error',{detail:error.message})); }
    };
    image.onerror=()=>{
      if(!dead)container.dispatchEvent(new CustomEvent('ppi-image-error',{
        detail:'Bild konnte über die bestehende Nextcloud-Freigabe nicht geladen werden.'}));
    };
    // Attach events before starting the download; the loading UI is already mounted.
    void loadPanoramaImage();
    return {
      getPosition(){return {yaw,pitch};},
      rotate(position){
        if(dead)return;
        const y=Number(position.yaw ?? position.longitude);
        const p=Number(position.pitch ?? position.latitude);
        if(Number.isFinite(y))yaw=y;
        if(Number.isFinite(p))pitch=limit(p,-Math.PI/2+.02,Math.PI/2-.02);
        touchYawTarget=yaw; touchPitchTarget=pitch;
        requestDraw();
      },
      getFov(){return hfov;},
      zoomByDegrees(delta){setFov(hfov+delta);},
      getZoomLevel(){return (MAX_FOV-hfov)/(MAX_FOV-MIN_FOV)*100;},
      zoom(level){setFov(MAX_FOV-limit(level,0,100)/100*(MAX_FOV-MIN_FOV));},
      zoomIn(){setFov(hfov-8);},
      zoomOut(){setFov(hfov+8);},
      destroy(){
        dead=true;stopMotion();if (gyroHintTimer) clearTimeout(gyroHintTimer);if (gyroHintAnimation) gyroHintAnimation.cancel();gyroShell.remove();if(raf)cancelAnimationFrame(raf);
        downloadController.abort();
        resizeObserver?.disconnect();
        image.onload=null;image.onerror=null;image.src='';
        if (imageBlobUrl) { URL.revokeObjectURL(imageBlobUrl); imageBlobUrl = null; }
        canvas.removeEventListener('pointerdown',onDown);
        canvas.removeEventListener('pointermove',onMove);
        canvas.removeEventListener('pointerup',onUp);
        canvas.removeEventListener('pointercancel',onUp);
        canvas.removeEventListener('lostpointercapture',onUp);
        canvas.removeEventListener('wheel',onWheel);
        gl.deleteTexture(texture);gl.deleteBuffer(vertices);
        gl.deleteProgram(program);gl.deleteShader(vshader);gl.deleteShader(fshader);
        watermark.remove();
      }
    };
  }

  function sphereViewer() { return currentViewer; }

  function stopSteering() {
    heldArrows.clear();
    if (steeringFrame) cancelAnimationFrame(steeringFrame);
    steeringFrame = 0;
    lastSteeringTime = 0;
    velocityX = 0;
    velocityY = 0;
  }
  // Smoothly move velocity towards the requested direction.  Starting from
  // rest takes ~0.4 s, while releasing the keys takes ~0.3 s to settle.
  // Velocities are dimensionless; TURN_SPEED remains unchanged at full speed.
  function approachVelocity(current, target, seconds) {
    const duration = Math.abs(target) > Math.abs(current)
      ? STEER_ACCEL_SECONDS : STEER_DECEL_SECONDS;
    const maxChange = seconds / duration;
    if (current < target) return Math.min(target, current + maxChange);
    if (current > target) return Math.max(target, current - maxChange);
    return current;
  }
  function steeringTick(now) {
    steeringFrame = 0;
    if (!document.getElementById(FRAME_ID)) {
      stopSteering();
      return;
    }
    // Clamp elapsed time after a background-tab pause; never jump the view.
    const seconds = lastSteeringTime ? Math.min(Math.max((now - lastSteeringTime) / 1000, 0), .06) : 1 / 60;
    lastSteeringTime = now;
    let targetX = Number(heldArrows.has('ArrowRight')) - Number(heldArrows.has('ArrowLeft'));
    let targetY = Number(heldArrows.has('ArrowUp')) - Number(heldArrows.has('ArrowDown'));
    if (targetX && targetY) { targetX *= Math.SQRT1_2; targetY *= Math.SQRT1_2; }
    velocityX = approachVelocity(velocityX, targetX, seconds);
    velocityY = approachVelocity(velocityY, targetY, seconds);
    const moving = Math.abs(velocityX) > 1e-6 || Math.abs(velocityY) > 1e-6;
    if (!heldArrows.size && !moving) {
      stopSteering();
      return;
    }
    const viewer = sphereViewer();
    if (viewer) {
      warnedMissingViewer = false;
      if (moving) {
        try {
          const pos = viewer.getPosition();
          const yaw = Number.isFinite(pos.yaw) ? pos.yaw : pos.longitude;
          const pitch = Number.isFinite(pos.pitch) ? pos.pitch : pos.latitude;
          if (Number.isFinite(yaw) && Number.isFinite(pitch)) {
            if (Number.isFinite(pos.yaw) && Number.isFinite(pos.pitch)) {
              viewer.rotate({ yaw: yaw + velocityX * TURN_SPEED * seconds,
                pitch: pitch + velocityY * TURN_SPEED * seconds });
            } else {
              viewer.rotate({ longitude: yaw + velocityX * TURN_SPEED * seconds,
                latitude: pitch + velocityY * TURN_SPEED * seconds });
            }
          }
        } catch (error) {
          if (!warnedMissingViewer) {
            console.warn('[Photosphere Photos] Pfeilsteuerung konnte die Blickrichtung nicht ändern:', error);
            warnedMissingViewer = true;
          }
        }
      }
    } else if (!warnedMissingViewer) {
      console.warn('[Photosphere Photos] Photo Sphere Viewer Instanz nicht gefunden – Pfeiltastensteuerung möglicherweise erst nach dem Laden verfügbar.');
      warnedMissingViewer = true;
    }
    steeringFrame = requestAnimationFrame(steeringTick);
  }
  function handleSteeringKeyDown(event) {
    if (event.key === 'Control') { stopSteering(); return; }
    if (!ARROWS.has(event.key) || !document.getElementById(FRAME_ID)
      || event.ctrlKey || event.altKey || event.metaKey || event.shiftKey
      || isTextOrMenuTarget(event.target)) return;
    // Keep Nextcloud's image-navigation shortcut from stealing arrow presses.
    event.preventDefault();
    event.stopImmediatePropagation();
    heldArrows.add(event.key);
    if (!steeringFrame) steeringFrame = requestAnimationFrame(steeringTick);
  }
  function handleSteeringKeyUp(event) {
    if (!ARROWS.has(event.key) || !document.getElementById(FRAME_ID)
      || !heldArrows.has(event.key)) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    heldArrows.delete(event.key);
    // Keep animating during deceleration until both velocities reach zero.
    if (!steeringFrame) steeringFrame = requestAnimationFrame(steeringTick);
  }
  function stopZoom() {
    heldZoomKeys.clear();
    if (zoomFrame) cancelAnimationFrame(zoomFrame);
    zoomFrame = 0;
    lastZoomTime = 0;
    zoomVelocity = 0;
  }
  function zoomTick(now) {
    zoomFrame = 0;
    if (!document.getElementById(FRAME_ID)) { stopZoom(); return; }
    const dt = lastZoomTime ? Math.min(Math.max((now-lastZoomTime)/1000,0),.06) : 1/60;
    lastZoomTime = now;
    const target = Number(heldZoomKeys.has('ArrowUp')) - Number(heldZoomKeys.has('ArrowDown'));
    // Stronger ease-in/ease-out with a cubic smoothstep velocity envelope.
    const duration=Math.abs(target)>Math.abs(zoomVelocity)?ZOOM_ACCEL_SECONDS:ZOOM_DECEL_SECONDS;
    const linearStep=dt/duration;
    if (zoomVelocity < target) zoomVelocity=Math.min(target,zoomVelocity+linearStep);
    else if (zoomVelocity > target) zoomVelocity=Math.max(target,zoomVelocity-linearStep);
    if (!heldZoomKeys.size && Math.abs(zoomVelocity)<1e-5) {
      stopZoom();return;
    }
    const viewer=sphereViewer();
    if (viewer && typeof viewer.zoomByDegrees==='function') {
      try {
        // Smoothstep reduces initial motion and gives a pronounced ramp-up.
        const amplitude=Math.abs(zoomVelocity);
        const eased=amplitude*amplitude*(3-2*amplitude);
        const degrees=-Math.sign(zoomVelocity)*eased*ZOOM_DEGREES_PER_SECOND*dt;
        viewer.zoomByDegrees(degrees);
      } catch(error) {
        console.warn('[Photo Sphere] Sanfter Zoom fehlgeschlagen:',error);
        stopZoom();return;
      }
    }
    zoomFrame = requestAnimationFrame(zoomTick);
  }
  function handleZoomKeyDown(event) {
    if (event.key === 'Control') return;
    if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') return;
    if (!document.getElementById(FRAME_ID) || !event.ctrlKey || event.altKey
      || event.metaKey || event.shiftKey || isTextOrMenuTarget(event.target)) return;
    event.preventDefault(); event.stopImmediatePropagation();
    stopSteering();
    heldZoomKeys.add(event.key);
    if (!zoomFrame) zoomFrame = requestAnimationFrame(zoomTick);
  }
  function handleZoomKeyUp(event) {
    if (!document.getElementById(FRAME_ID)) return;
    if (event.key === 'Control') {
      heldZoomKeys.clear();
      if (zoomVelocity && !zoomFrame) zoomFrame = requestAnimationFrame(zoomTick);
      return;
    }
    if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') return;
    if (!heldZoomKeys.has(event.key)) return;
    event.preventDefault(); event.stopImmediatePropagation();
    heldZoomKeys.delete(event.key);
    if (!zoomFrame) zoomFrame = requestAnimationFrame(zoomTick);
  }
  function attachZoomListeners(target) {
    target.addEventListener('keydown', handleZoomKeyDown, true);
    target.addEventListener('keyup', handleZoomKeyUp, true);
    target.addEventListener('blur', stopZoom, true);
  }
  attachZoomListeners(window);

  function attachSteeringListeners(target) {
    // Capture before Nextcloud Viewer keyboard handling.
    target.addEventListener('keydown', handleSteeringKeyDown, true);
    target.addEventListener('keyup', handleSteeringKeyUp, true);
    target.addEventListener('blur', stopSteering, true);
  }
  attachSteeringListeners(window);
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) { stopSteering(); stopZoom(); }
  });

  function closePanorama() {
    stopZoom();
    stopSteering();
    currentViewer?.destroy();
    currentViewer = null;
    document.getElementById(FRAME_ID)?.remove();
    document.getElementById(CLOSE_ID)?.remove();
    document.body.classList.remove('ppi-panorama-open');
    currentPanorama = null;
    opening = false;
    syncUi();
  }
  // Enter works inside the Nextcloud Viewer modal and our panorama overlay.
  // Never treat the modal/dialog itself as an interactive control: otherwise
  // every Enter key inside the viewer was excluded in v0.3.5.
  function isTextOrMenuTarget(target) {
    const element = target?.nodeType === 1 ? target : target?.parentElement;
    return Boolean(element?.closest(
      'input, textarea, select, a, summary, [contenteditable=""], '
      + '[contenteditable="true"], [role="menu"], [role="menuitem"], '
      + '[role="textbox"], [role="combobox"], [role="listbox"], '
      + '[role="option"], [aria-haspopup="true"]'
    ));
  }
  function handleKeyboard(event) {
    const frameOpen = Boolean(document.getElementById(FRAME_ID));
    if (event.key === 'Escape' && frameOpen) {
      // Preserve existing Escape behavior.
      event.preventDefault();
      event.stopPropagation();
      closePanorama();
      return;
    }
    if (event.key !== 'Enter' || event.repeat || event.isComposing
      || event.ctrlKey || event.altKey || event.metaKey || event.shiftKey
      || isTextOrMenuTarget(event.target)) return;

    // Enter is a panorama shortcut only if the active viewer has already
    // been identified as a 360° image (or an overlay is currently open).
    if (frameOpen) {
      event.preventDefault();
      event.stopImmediatePropagation();
      closePanorama();
    } else if (document.getElementById(BADGE_ID) && getPhoto()) {
      event.preventDefault();
      event.stopImmediatePropagation();
      openPanorama();
    }
  }
  // Capture before Nextcloud's own global keyboard handlers, which may
  // consume Enter while the Vue viewer modal is active.
  window.addEventListener('keydown', handleKeyboard, true);

  async function readXmp(url) {
    const body = '<?xml version="1.0" encoding="utf-8"?>'
      + '<d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns"><d:prop>'
      + '<nc:files-photospheres-xmp-metadata/></d:prop></d:propfind>';
    const token = window.OC?.requestToken
      || document.querySelector('head[data-requesttoken]')?.getAttribute('data-requesttoken')
      || document.querySelector('meta[name="requesttoken"]')?.getAttribute('content');
    if (!token) throw new Error('Nextcloud-Anfragetoken fehlt');
    const response = await fetch(url, {
      method: 'PROPFIND', credentials: 'same-origin',
      headers: {
        Depth: '0',
        'Content-Type': 'application/xml; charset=utf-8',
        requesttoken: token,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body,
    });
    if (response.status !== 207) throw new Error(`WebDAV HTTP ${response.status}`);
    const doc = new DOMParser().parseFromString(await response.text(), 'application/xml');
    if (doc.querySelector('parsererror')) throw new Error('Ungueltige WebDAV-Antwort');
    const propstats = Array.from(doc.getElementsByTagName('*')).filter(el => el.localName === 'propstat');
    for (const propstat of propstats) {
      const statusEl = Array.from(propstat.children).find(el => el.localName === 'status');
      if (!statusEl || !/\s200\s/.test(statusEl.textContent || '')) continue;
      const prop = Array.from(propstat.getElementsByTagName('*')).find(
        el => el.localName === 'files-photospheres-xmp-metadata' && el.namespaceURI === 'http://nextcloud.org/ns'
      );
      if (!prop || !prop.textContent?.trim()) continue;
      try { return JSON.parse(prop.textContent.trim()); }
      catch (e) { console.warn('[Photosphere Photos] XMP-JSON unlesbar', e); }
    }
    return null;
  }
  // In public Photos albums, the WebDAV XMP property is 404 (the public
  // photospublic node is not an OCA\DAV\Connector\Sabre\File). Read only the
  // already-publicly-accessible JPEG header via HTTP Range instead.
  // A JPEG APP1 segment is at most 65535 bytes and is normally near the start.
  function parsePublicJpegXmp(bytes) {
    if (bytes.length < 4 || bytes[0] !== 0xff || bytes[1] !== 0xd8) return null;
    const decoder = new TextDecoder('utf-8');
    const namespace = 'http://ns.google.com/photos/1.0/panorama/';
    let offset = 2;
    while (offset + 4 <= bytes.length) {
      if (bytes[offset++] !== 0xff) break;
      let marker = bytes[offset++];
      while (marker === 0xff && offset < bytes.length) marker = bytes[offset++];
      if (marker === 0xda || marker === 0xd9) break; // SOS / EOI
      if ((marker >= 0xd0 && marker <= 0xd7) || marker === 0x01) continue;
      if (offset + 2 > bytes.length) break;
      const length = (bytes[offset] << 8) | bytes[offset + 1];
      if (length < 2 || offset + length > bytes.length) break;
      if (marker === 0xe1) {
        const start = offset + 2;
        const end = offset + length;
        const prefix = 'http://ns.adobe.com/xap/1.0/\0';
        // Only inspect standard Adobe XMP packets, not arbitrary JPEG data.
        if (end - start > prefix.length
          && prefix.split('').every((c, i) => bytes[start + i] === c.charCodeAt(0))) {
          const xmlText = decoder.decode(bytes.subarray(start + prefix.length, end))
            .replace(/\0+$/, '').trim();
          const xml = new DOMParser().parseFromString(xmlText, 'application/xml');
          if (xml.querySelector('parsererror')) continue;
          let usePanorama = null;
          let projection = null;
          const nodes = Array.from(xml.getElementsByTagName('*'));
          for (const node of nodes) {
            if (usePanorama === null) {
              const attr = node.getAttributeNS(namespace, 'UsePanoramaViewer');
              if (attr !== null) usePanorama = attr;
            }
            if (projection === null) {
              const attr = node.getAttributeNS(namespace, 'ProjectionType');
              if (attr !== null) projection = attr;
            }
            if (node.namespaceURI === namespace && node.localName === 'UsePanoramaViewer')
              usePanorama = node.textContent.trim();
            if (node.namespaceURI === namespace && node.localName === 'ProjectionType')
              projection = node.textContent.trim();
          }
          const explicitFlag = usePanorama === null ? null : /^(true|1)$/i.test(usePanorama);
          if (explicitFlag !== null) return { usePanoramaViewer: explicitFlag };
          if (projection && projection.toLowerCase() === 'equirectangular')
            return { usePanoramaViewer: true };
        }
      }
      offset += length;
    }
    return null;
  }
  async function readPublicJpegXmp(url) {
    // Protect guest users from an accidental full download of very large panoramas.
    const response = await fetch(url, {
      method: 'GET', credentials: 'same-origin',
      headers: { Range: 'bytes=0-1048575' }
    });
    if (response.status !== 206) {
      await response.body?.cancel();
      throw new Error(`Öffentlicher JPEG-Bereich nicht verfügbar (HTTP ${response.status})`);
    }
    if (!(response.headers.get('Content-Type') || '').toLowerCase().includes('image/jpeg')) {
      await response.body?.cancel();
      throw new Error('Öffentliche Datei ist kein JPEG');
    }
    const bytes = new Uint8Array(await response.arrayBuffer());
    return parsePublicJpegXmp(bytes);
  }
  function isPanorama(xmp) {
    return xmp && (xmp.usePanoramaViewer === true || xmp.usePanoramaViewer === 1);
  }
  function syncUi() {
    const photo = getPhoto();
    const url = photo?.source ?? null;
    if (url !== requestedUrl) {
      requestedUrl = url;
      ++requestSerial;
      removeBadge();
    }
    if (!url || document.getElementById(FRAME_ID) || opening) {
      removeBadge();
      return;
    }
    if (!xmpCache.has(url)) {
      xmpCache.set(url, { state: 'pending' });
      const serial = requestSerial;
      ((inPublicPhotos() || inPublicFileShare()) ? readPublicJpegXmp(url) : readXmp((photo?.davPath && eligibleSource(photo.davPath)) ? photo.davPath : url).catch(() => null).then(xmp => xmp || readPublicJpegXmp(url))).then(xmp => {
        xmpCache.set(url, { state: 'ready', xmp });
        if (requestedUrl === url && serial === requestSerial) syncUi();
      }).catch(error => {
        console.warn('[Photosphere Photos] Panorama-Erkennung fehlgeschlagen:', error);
        xmpCache.set(url, { state: 'error' });
        if (requestedUrl === url && serial === requestSerial) syncUi();
      });
      return;
    }
    const entry = xmpCache.get(url);
    if (entry.state !== 'ready' || !isPanorama(entry.xmp)) {
      removeBadge();
      return;
    }
    ensureOpenBadge();
  }
  async function openPanorama() {
    // A touch may produce both pointerup and a synthetic click; start once only.
    if (opening || currentPanorama || document.getElementById(FRAME_ID)) return;
    const photo = getPhoto();
    if (!photo) return;
    const entry = xmpCache.get(photo.source);
    if (!entry || entry.state !== 'ready' || !isPanorama(entry.xmp)) return;
    const xmp = entry.xmp;
    opening = true;
    removeBadge();
    currentPanorama = photo.source;
    try {
      const [watermarkOptions, zoomOptions] = await Promise.all([fetchWatermarkSettings(photo), fetchZoomSettings()]);
      if (currentPanorama !== photo.source) return;
      const overlay = document.createElement('div');
      overlay.id = FRAME_ID;
      overlay.setAttribute('role', 'region');
      overlay.setAttribute('aria-label', '360°-Panorama');
      Object.assign(overlay.style, {
        position: 'fixed', inset: '0', width: '100vw', height: '100vh',
        border: 'none', zIndex: '2147483646', background: '#000'
      });
      const close = makeOverlayButton(CLOSE_ID, '360° schließen', true);
      close.title = '360°-Ansicht schließen';
      close.setAttribute('aria-label', '360°-Ansicht schließen');
      close.addEventListener('click', closePanorama);
      close.style.zIndex = '2147483647';
      close.style.top = '18px';
      close.style.left = 'auto';
      close.style.right = '18px';
      // The loading veil is visible WHILE the JPEG is downloaded, not after.
      // Its top-down reveal and percentage are driven by actual received bytes.
      const loading = document.createElement('div');
      loading.setAttribute('role', 'status');
      loading.setAttribute('aria-live', 'polite');
      Object.assign(loading.style, {
        position:'absolute', inset:'0', zIndex:'2', overflow:'hidden',
        background:'#121820', display:'flex', alignItems:'center',
        justifyContent:'center', flexDirection:'column', gap:'12px',
        color:'#fff', font:'600 15px/1.3 system-ui,sans-serif',
        transition:'opacity .62s ease-out'
      });
      const activePreview = document.querySelector('#viewer .viewer__file--active img');
      // Do not fetch the full JPEG again merely to obtain a loading background.
      const previewUrl = activePreview?.currentSrc || activePreview?.src || null;
      const previewLayer = document.createElement('div');
      const previewBackground = previewUrl
        ? `linear-gradient(rgba(0,0,0,.30),rgba(0,0,0,.42)),url(${JSON.stringify(previewUrl)})`
        : 'linear-gradient(145deg,#142637,#385164 55%,#182735)';
      Object.assign(previewLayer.style, {
        position:'absolute', inset:'-24px', backgroundImage:previewBackground,
        backgroundPosition:'center', backgroundSize:'cover',
        filter:'blur(20px)', transform:'scale(1.04)', pointerEvents:'none',
        opacity:'.32'
      });
      // This second preview layer is unveiled top-to-bottom as *download bytes*
      // arrive. It stays blurred until the final 3D canvas becomes sharp.
      const progressPreview = document.createElement('div');
      Object.assign(progressPreview.style, {
        position:'absolute', inset:'-24px', backgroundImage:previewBackground,
        backgroundPosition:'center', backgroundSize:'cover',
        filter:'blur(13px)', transform:'scale(1.04)', pointerEvents:'none',
        clipPath:'inset(100% 0 0 0)', WebkitClipPath:'inset(100% 0 0 0)',
        transition:'clip-path .14s linear, -webkit-clip-path .14s linear'
      });
      const loadingContent = document.createElement('div');
      Object.assign(loadingContent.style, {
        position:'relative', zIndex:'1', padding:'20px 24px',
        width:'min(330px,86vw)', boxSizing:'border-box', borderRadius:'16px',
        background:'rgba(8,16,25,.46)', border:'1px solid rgba(255,255,255,.20)',
        backdropFilter:'blur(8px)', WebkitBackdropFilter:'blur(8px)',
        boxShadow:'0 15px 36px rgba(0,0,0,.18)', textAlign:'center'
      });
      const spinner = document.createElement('div');
      Object.assign(spinner.style, {
        width:'36px', height:'36px', border:'3px solid rgba(255,255,255,.26)',
        borderTopColor:'#fff', borderRadius:'50%', margin:'0 auto 15px',
        animation:'ppi-panorama-spin .9s linear infinite'
      });
      const loaderText = document.createElement('div');
      loaderText.textContent = 'Panorama wird heruntergeladen …';
      const progressDetail = document.createElement('div');
      progressDetail.textContent = 'Ladefortschritt wird ermittelt';
      Object.assign(progressDetail.style, {
        font:'500 12px/1.4 system-ui,sans-serif', marginTop:'6px',
        opacity:'.85', fontVariantNumeric:'tabular-nums'
      });
      const progressTrack = document.createElement('div');
      Object.assign(progressTrack.style, {
        width:'100%', height:'7px', borderRadius:'999px', overflow:'hidden',
        background:'rgba(255,255,255,.24)', marginTop:'16px'
      });
      const progressFill = document.createElement('div');
      Object.assign(progressFill.style, {
        width:'32%', height:'100%', borderRadius:'inherit', background:'#fff',
        transform:'translateX(-120%)',
        animation:'ppi-panorama-indeterminate 1.5s ease-in-out infinite'
      });
      progressTrack.appendChild(progressFill);
      const animationStyle = document.createElement('style');
      animationStyle.textContent = '@keyframes ppi-panorama-spin{to{transform:rotate(360deg)}}'
        + '@keyframes ppi-panorama-indeterminate{0%{transform:translateX(-120%)}'
        + '50%{transform:translateX(120%)}100%{transform:translateX(340%)}}';
      loadingContent.append(spinner, loaderText, progressDetail, progressTrack);
      loading.append(previewLayer, progressPreview, loadingContent, animationStyle);
      overlay.appendChild(loading);
      overlay.addEventListener('ppi-image-progress', event => {
        if (!loading.isConnected) return;
        const {phase, loaded, total, percent} = event.detail || {};
        if (phase === 'download' && percent !== null && Number.isFinite(percent)) {
          const p = Math.max(0, Math.min(100, percent));
          progressFill.style.animation = 'none';
          progressFill.style.transform = 'none';
          progressFill.style.width = `${p}%`;
          progressDetail.textContent = `${p}% · ${Math.round(loaded / 1048576 * 10) / 10} MB`
            + (total ? ` / ${Math.round(total / 1048576 * 10) / 10} MB` : '');
          const remainder = 100 - p;
          progressPreview.style.clipPath = `inset(0 0 ${remainder}% 0)`;
          progressPreview.style.WebkitClipPath = `inset(0 0 ${remainder}% 0)`;
        } else if (phase === 'download') {
          progressDetail.textContent = 'Dateigröße nicht verfügbar · lade Daten …';
        } else if (phase === 'decode') {
          loaderText.textContent = 'Panorama wird vorbereitet …';
          progressDetail.textContent = 'Download abgeschlossen';
          progressFill.style.animation = 'none';
          progressFill.style.transform = 'none';
          progressFill.style.width = '100%';
          progressPreview.style.clipPath = 'inset(0 0 0 0)';
          progressPreview.style.WebkitClipPath = 'inset(0 0 0 0)';
        } else if (phase === 'render') {
          loaderText.textContent = '360°-Ansicht wird aufgebaut …';
          progressDetail.textContent = 'Bild wird dargestellt';
        } else if (phase === 'fallback') {
          loaderText.textContent = 'Panorama wird geladen …';
          progressDetail.textContent = 'Fortschritt nicht verfügbar';
        }
      });
      overlay.addEventListener('ppi-image-ready', () => {
        // As soon as the first GPU frame is ready, crossfade the veil into
        // the still-soft panorama, then remove blur. No artificial loading sweep.
        if (!loading.isConnected || !overlay.isConnected || currentPanorama !== photo.source) return;
        const canvas = overlay.querySelector('canvas');
        if (!canvas) return;
        loading.style.pointerEvents = 'none';
        loading.style.opacity = '0';
        setTimeout(() => {
          if (!canvas.isConnected || currentPanorama !== photo.source) return;
          loading.remove();
          requestAnimationFrame(() => {
            canvas.style.filter = 'blur(0px)';
            canvas.style.transform = 'scale(1)';
          });
        }, 650);
      }, {once:true});
      overlay.addEventListener('ppi-image-error', event => {
        console.error('[Photosphere Photos] Panoramabild:', event.detail);
        closePanorama();
        status('Panoramabild konnte nicht geladen werden: ' + event.detail);
      }, { once: true });
      document.body.append(overlay, close);
      currentViewer = createPanoramaViewer(overlay, photo.source, xmp, watermarkOptions, zoomOptions);

      if (currentPanorama !== photo.source) closePanorama();
    } catch (error) {
      console.error('[Photosphere Photos]', error);
      closePanorama();
      status('Fehler beim Öffnen der 360°-Ansicht.');
    } finally {
      opening = false;
    }
  }

  // Faster panorama detection: react to actual viewer changes immediately.
  // Retain the existing 450 ms polling as a fallback for Vue-only state changes.
  let syncScheduled = false;
  function scheduleSync() {
    if (syncScheduled) return;
    syncScheduled = true;
    requestAnimationFrame(() => {
      syncScheduled = false;
      syncUi();
    });
  }
  function installFastDetection() {
    syncUi();
    let observedViewer = null;
    let viewerObserver = null;
    function attachViewerObserver() {
      const root = document.getElementById('viewer');
      if (root === observedViewer) return;
      viewerObserver?.disconnect();
      observedViewer = root;
      if (!root) return;
      viewerObserver = new MutationObserver(scheduleSync);
      viewerObserver.observe(root, {
        subtree: true,
        childList: true,
        attributes: true,
        attributeFilter: ['src', 'class', 'style']
      });
      scheduleSync();
    }
    attachViewerObserver();
    // Nextcloud creates/removes the viewer dynamically. Observe only direct
    // body children to avoid rescanning the whole page for irrelevant changes.
    const bodyObserver = new MutationObserver(() => {
      attachViewerObserver();
      scheduleSync();
    });
    bodyObserver.observe(document.body, { childList: true });
    window.addEventListener('popstate', scheduleSync);
    window.addEventListener('hashchange', scheduleSync);
    setInterval(() => {
      attachViewerObserver();
      syncUi();
    }, 450);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', installFastDetection, { once: true });
  } else {
    installFastDetection();
  }
})();
