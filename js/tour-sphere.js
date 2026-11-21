/* Standalone 360° tour editing view: it is never activated in Files/Photos. */
const TOUR_ZOOM_DEFAULTS = Object.freeze({
  desktopMin: 60, desktopStart: 110, desktopMax: 135,
  mobileMin: 30, mobileStart: 75, mobileMax: 110
});
let tourZoomSettingsRequest = null;
function fetchTourZoomSettings() {
  if (!tourZoomSettingsRequest) {
    tourZoomSettingsRequest = (async () => {
      try {
        const route = window.OC?.generateUrl?.('/apps/photosphere_photos_integration/zoom/settings')
          || '/index.php/apps/photosphere_photos_integration/zoom/settings';
        const response = await fetch(route, {credentials:'same-origin', cache:'no-store'});
        if (!response.ok) throw new Error('HTTP '+response.status);
        const result = await response.json();
        if (result?.success && result.zoom) return {...TOUR_ZOOM_DEFAULTS, ...result.zoom};
        throw new Error('Ungültige Zoom-Konfiguration');
      } catch (error) {
        console.warn('[Photo Sphere] Tour-Zoom-Konfiguration nicht erreichbar:', error);
        return {...TOUR_ZOOM_DEFAULTS};
      }
    })();
  }
  return tourZoomSettingsRequest;
}
function makeTourSphere(root, url, getLinks, navigate, onPoint, report, getThumbUrl = () => "", getTargetName = () => "Panorama", directWheelZoom = false, zoomOptions = TOUR_ZOOM_DEFAULTS, preloadedImage = null, onViewChange = null, onTexturePhase = null, preparedBitmap = null, maxPanoramaTexture = null) {
  // Preserve the active loading overlay when replacing the WebGL canvas.
  // Removing/reinserting it interrupted the spinner during each transition.
  const activeLoadingVeils=[...root.querySelectorAll(':scope > .ppi-sphere-loading')];
  root.replaceChildren(...activeLoadingVeils);
  const canvas = document.createElement('canvas');
  canvas.setAttribute('aria-label','360°-Panorama drehen, klicken zum Setzen eines Wechselpunkts');
  canvas.style.cssText='display:block;width:100%;height:100%;cursor:grab;touch-action:none';
  root.prepend(canvas);
  const gl = canvas.getContext('webgl', {alpha:false,antialias:false});
  if (!gl) throw new Error('WebGL nicht verfügbar');
  const vert=`attribute vec2 point; varying vec2 pixel;
    void main(){pixel=point;gl_Position=vec4(point,0.,1.);}`;
  const frag=`precision highp float; varying vec2 pixel;
    uniform sampler2D panorama; uniform float yaw,pitch,halfFov,aspect;
    const float PI=3.14159265358979323846264;
    void main(){
      vec3 forward=vec3(sin(yaw)*cos(pitch),sin(pitch),-cos(yaw)*cos(pitch));
      vec3 right=vec3(cos(yaw),0.,sin(yaw));
      vec3 up=normalize(cross(right,forward));
      vec3 ray=normalize(forward+right*pixel.x*tan(halfFov)+up*pixel.y*tan(halfFov)/aspect);
      float longitude=atan(ray.x,-ray.z);
      float latitude=asin(clamp(ray.y,-1.,1.));
      vec2 uv=vec2(fract(longitude/(2.*PI)+.5),.5+latitude/PI);
      gl_FragColor=texture2D(panorama,uv);
    }`;
  const shader=(type,source)=>{
    const sh=gl.createShader(type);gl.shaderSource(sh,source);gl.compileShader(sh);
    if(!gl.getShaderParameter(sh,gl.COMPILE_STATUS))throw new Error(gl.getShaderInfoLog(sh)||'Shader-Fehler');
    return sh;
  };
  const vs=shader(gl.VERTEX_SHADER,vert), fs=shader(gl.FRAGMENT_SHADER,frag);
  const program=gl.createProgram();gl.attachShader(program,vs);gl.attachShader(program,fs);gl.linkProgram(program);
  if(!gl.getProgramParameter(program,gl.LINK_STATUS))throw new Error('3D-Programm konnte nicht geladen werden');
  gl.useProgram(program);
  const buffer=gl.createBuffer();gl.bindBuffer(gl.ARRAY_BUFFER,buffer);
  gl.bufferData(gl.ARRAY_BUFFER,new Float32Array([-1,-1,1,-1,-1,1,1,1]),gl.STATIC_DRAW);
  const point=gl.getAttribLocation(program,'point');gl.enableVertexAttribArray(point);
  gl.vertexAttribPointer(point,2,gl.FLOAT,false,0,0);
  const u=n=>gl.getUniformLocation(program,n);
  const un={yaw:u('yaw'),pitch:u('pitch'),fov:u('halfFov'),aspect:u('aspect')};
  gl.uniform1i(u('panorama'),0);
  const tex=gl.createTexture();gl.bindTexture(gl.TEXTURE_2D,tex);
  gl.texParameteri(gl.TEXTURE_2D,gl.TEXTURE_WRAP_S,gl.CLAMP_TO_EDGE);
  gl.texParameteri(gl.TEXTURE_2D,gl.TEXTURE_WRAP_T,gl.CLAMP_TO_EDGE);
  gl.texParameteri(gl.TEXTURE_2D,gl.TEXTURE_MIN_FILTER,gl.LINEAR);
  gl.texParameteri(gl.TEXTURE_2D,gl.TEXTURE_MAG_FILTER,gl.LINEAR);
  gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL,true);
  const cap=(x,a,b)=>Math.max(a,Math.min(b,x));
  const mobileTouch = window.matchMedia?.('(pointer: coarse)').matches || false;
  const zoomPrefix = mobileTouch ? 'mobile' : 'desktop';
  const zoomNumber = (suffix, fallback) => {
    const value = Number(zoomOptions?.[zoomPrefix+suffix]);
    return Number.isFinite(value) && value > 0 ? value : fallback;
  };
  const MIN_FOV = zoomNumber('Min', mobileTouch ? 30 : 60);
  const MAX_FOV = zoomNumber('Max', mobileTouch ? 110 : 135);
  const START_FOV = cap(zoomNumber('Start', mobileTouch ? 75 : 110), MIN_FOV, MAX_FOV);
  let yaw=0,pitch=0,fov=START_FOV,ready=false,dead=false,raf=0,pending=null;
  const pointers=new Map();let prev=null, pinchStart=null, pinchFov=null, pointerDown=null;
  const markers=new Map();
  // Editor placement is independent of public hotspot visibility / fading.
  const editorMode=root.id==='ppi-preview-sphere';
  let placementActive=false;
  // A double hop is a one-shot attention cue only for the four SVG pin designs.
  // Animate the pin glyph (not the hotspot button or thumbnail), preserving
  // its screen coordinates, opacity fading and click/touch handling.
  const pinDesigns=new Set(['map-pin','label-pin','outline-pin']);
  const reducedMotion=window.matchMedia?.('(prefers-reduced-motion: reduce)').matches || false;
  function maybeHopPin(button, pinFigure, style, loc){
    if(!pinDesigns.has(style)||!pinFigure||reducedMotion){
      delete button._ppiCenterSide;
      return;
    }
    if(!loc || loc.opacity<0.65){
      delete button._ppiCenterSide;
      return;
    }
    const centre=canvas.clientWidth/2;
    const delta=parseFloat(loc.left)-centre;
    const old=button._ppiCenterSide;
    // Register a meaningful approach from either side; when crossing zero,
    // play twice. Starting at the centre does not trigger an animation.
    if(delta<-7) button._ppiCenterSide=-1;
    else if(delta>7) button._ppiCenterSide=1;
    if(old && ((old===-1 && delta>=0)||(old===1 && delta<=0))){
      button._ppiCenterSide=delta<0?-1:1;
      const now=performance.now();
      if(now-(button._ppiLastHop||0)>1800 && typeof pinFigure.animate==='function'){
        button._ppiLastHop=now;
        // "translate" is independent of the existing translateX(-50%) CSS
        // transform and therefore does not move the hotspot's hit area.
        // Animate the glyph and its blurred silhouette in lockstep.
        // Only the two visual layers move, never the click target/thumbnail.
        const shadow=button.querySelector('.ppi-hotspot-pin-shadow');
        const hopFrames=[
          {translate:'0 0',offset:0},
          {translate:'0 -13px',offset:.17},
          {translate:'0 0',offset:.36},
          {translate:'0 -9px',offset:.55},
          {translate:'0 0',offset:.78},
          {translate:'0 0',offset:1}
        ];
        const insetPhoto=button.querySelector('.ppi-hotspot-pin-mini');
        for(const layer of [pinFigure,shadow,insetPhoto]){
          if(!layer || typeof layer.animate!=='function')continue;
          layer.getAnimations().forEach(animation=>animation.cancel());
          layer.animate(hopFrames,{duration:920,easing:'ease-in-out',iterations:1});
        }
      }
    }
  }
  const touchPreviewMode=mobileTouch || window.matchMedia('(hover:none)').matches;
  let openedPreview=null;
  function closePreview(){
    if(openedPreview){openedPreview.classList.remove('ppi-hotspot-preview-open');openedPreview.setAttribute('aria-expanded','false');openedPreview=null;}
  }
  const aspect=()=>Math.max(1,canvas.clientWidth)/Math.max(1,canvas.clientHeight);
  const forward=()=>[Math.sin(yaw)*Math.cos(pitch),Math.sin(pitch),-Math.cos(yaw)*Math.cos(pitch)];
  const right=()=>[Math.cos(yaw),0,Math.sin(yaw)];
  const up=()=>{const r=right(), f=forward();return [r[1]*f[2]-r[2]*f[1],r[2]*f[0]-r[0]*f[2],r[0]*f[1]-r[1]*f[0]];};
  const dot=(a,b)=>a[0]*b[0]+a[1]*b[1]+a[2]*b[2];
  function toScreen(pos) {
    const v=[Math.sin(pos.yaw)*Math.cos(pos.pitch),Math.sin(pos.pitch),-Math.cos(pos.yaw)*Math.cos(pos.pitch)];
    const dz=dot(v,forward());if(dz<=0.03)return null;
    const half=Math.tan(fov*Math.PI/360);
    const x=dot(v,right())/dz/half;
    const y=dot(v,up())/dz*aspect()/half;
    // The tour-wide hotspots are deliberately restricted to the horizontal
    // centre of the screen: 50% on desktop, 55% on touch/mobile devices.
    // Keep the true projected coordinates (never clamp or move hotspots).
    const width=canvas.clientWidth, height=canvas.clientHeight;
    const left=(x+1)*width/2, top=(1-y)*height/2;
    const mobile=mobileTouch || window.matchMedia('(max-width:650px)').matches;
    // Public viewer permits per-tour sizing; the editor uses the original scale.
    // Keep frustum fading consistent with the CSS transform rather than CSS zoom.
    const publicTour=root.closest('#ppi-public-tour');
    const size=publicTour?.dataset.hotspotSize;
    const sizeScale=({xsmall:.72,small:.8,medium:1,large:1.2,xlarge:1.28})[size]||1;
    const scale=(mobile?0.8:1)*sizeScale;
    const halfWidth=48*scale;
    const topExtent=116*.70*scale, bottomExtent=116*.30*scale;
    // The inner band is 50% / 55% of the width; markers fade over 8% of
    // the viewport immediately inside the band's edges (smoothstep).
    // Outside the band they are hidden and cannot receive input.
    const centreWidth=width*(mobile?0.55:0.50);
    const centreLeft=(width-centreWidth)/2;
    const centreRight=centreLeft+centreWidth;
    if(!Number.isFinite(left)||!Number.isFinite(top)||
       top<=-topExtent||top>=height+bottomExtent)return null;
    // Editing must allow a hotspot at any horizontal point in the panorama,
    // even where the public viewer intentionally fades markers away. The
    // projected marker can be partly clipped at the viewport edge; its saved
    // yaw/pitch are never clamped or modified.
    if(editorMode){
      if(left < -halfWidth || left > width+halfWidth)return null;
      // Keep editor markers selectable at the edges without disabling the
      // original smooth centre fade while panning. The public tour still
      // completely hides markers outside its 50% / 55% centre area.
      // During point placement, show every marker at full opacity so the
      // target position is easy to inspect even at the viewport edge.
      if(placementActive)return {left:left+'px',top:top+'px',opacity:1};
      const smoothEditor=t=>{t=cap(t,0,1);return t*t*(3-2*t);};
      const edgeLeft=smoothEditor((left+halfWidth)/(centreLeft+halfWidth));
      const edgeRight=smoothEditor((width+halfWidth-left)/(width+halfWidth-centreRight));
      const edgeTop=smoothEditor((top+topExtent)/(2*topExtent));
      const edgeBottom=smoothEditor((height+bottomExtent-top)/(2*bottomExtent));
      const opacity=0.26+0.74*Math.min(edgeLeft,edgeRight,edgeTop,edgeBottom);
      return {left:left+'px',top:top+'px',opacity};
    }
    if(left<=centreLeft||left>=centreRight)return null;
    const smooth=t=>{t=cap(t,0,1);return t*t*(3-2*t);};
    const fadeBand=Math.min(width*0.08,centreWidth*0.25);
    const fadeLeft=smooth((left-centreLeft)/fadeBand);
    const fadeRight=smooth((centreRight-left)/fadeBand);
    const fadeTop=smooth((top+topExtent)/(2*topExtent));
    const fadeBottom=smooth((height+bottomExtent-top)/(2*bottomExtent));
    const opacity=Math.min(fadeLeft,fadeRight,fadeTop,fadeBottom);
    return {left:left+'px',top:top+'px',opacity};
  }
  function fromClick(x,y) {
    const rect=canvas.getBoundingClientRect();
    const nx=((x-rect.left)/rect.width)*2-1;
    const ny=1-((y-rect.top)/rect.height)*2;
    const scale=Math.tan(fov*Math.PI/360), a=aspect();
    const f=forward(),r=right(),v=up();
    const ray=[f[0]+nx*scale*r[0]+ny*scale/a*v[0],f[1]+nx*scale*r[1]+ny*scale/a*v[1],f[2]+nx*scale*r[2]+ny*scale/a*v[2]];
    const mag=Math.hypot(...ray);ray[0]/=mag;ray[1]/=mag;ray[2]/=mag;
    return {yaw:Math.atan2(ray[0],-ray[2]),pitch:Math.asin(cap(ray[1],-1,1))};
  }
  function drawMarkers() {
    const links=getLinks(); const keys=new Set();
    links.forEach((link,i)=>{
      const key=`${link.from}:${link.to}`;keys.add(key);
      let button=markers.get(key);
      if(!button){
        button=document.createElement('button');button.type='button';button.className='ppi-hotspot ppi-hotspot-person';
        // A lightweight hand-drawn guide holding the actual destination preview.
        // The entire button is keyboard- and touch-accessible.
        const photo=document.createElement('span');photo.className='ppi-hotspot-photo';
        const thumbnail=document.createElement('img');thumbnail.className='ppi-hotspot-thumb';thumbnail.alt='';thumbnail.loading='lazy';
        photo.append(thumbnail);
        const figure=document.createElementNS('http://www.w3.org/2000/svg','svg');
        figure.setAttribute('viewBox','0 0 90 66');figure.setAttribute('aria-hidden','true');
        figure.classList.add('ppi-hotspot-figure');
        const body=document.createElementNS('http://www.w3.org/2000/svg','path');
        body.setAttribute('d','M 13 8 Q 20 28 33 31 L 40 39 M 77 8 Q 68 27 56 31 L 50 39 M 40 39 Q 32 49 31 66 M 50 39 Q 59 49 60 66');
        body.setAttribute('fill','none');body.setAttribute('stroke','currentColor');body.setAttribute('stroke-width','4');body.setAttribute('stroke-linecap','round');body.setAttribute('stroke-dasharray','6 4');
        const head=document.createElementNS('http://www.w3.org/2000/svg','ellipse');
        head.setAttribute('cx','45');head.setAttribute('cy','24');head.setAttribute('rx','10');head.setAttribute('ry','13');
        head.setAttribute('fill','none');head.setAttribute('stroke','currentColor');head.setAttribute('stroke-width','4');head.setAttribute('stroke-dasharray','6 3');
        figure.append(body,head);button.append(photo,figure);
        const blue=document.createElement('img');
        blue.className='ppi-hotspot-blue-figure';
        blue.src=(window.OC?.filePath ? window.OC.filePath('photosphere_photos_integration','img','hotspot-guide-blue.png') : '/custom_apps/photosphere_photos_integration/img/hotspot-guide-blue.png');
        blue.alt='';blue.setAttribute('aria-hidden','true');
        button.append(blue);
        const pinShadow=document.createElement('span');
        pinShadow.className='ppi-hotspot-pin-shadow';pinShadow.setAttribute('aria-hidden','true');
        const pin=document.createElement('span');
        pin.className='ppi-hotspot-pin-figure';pin.setAttribute('aria-hidden','true');
        // Small destination image inside the pin's upper opening. It shares
        // the double-hop animation with the icon and its blurred silhouette.
        const pinMini=document.createElement('span');
        pinMini.className='ppi-hotspot-pin-mini';pinMini.setAttribute('aria-hidden','true');
        const miniImage=document.createElement('img');miniImage.alt='';miniImage.loading='eager';miniImage.decoding='async';
        pinMini.append(miniImage);
        button.append(pinShadow,pin,pinMini);
        button.setAttribute('aria-expanded','false');

        // Mouse/trackpad: show the zoomed preview on hover, then navigate
        // with a single click. Touch: first tap expands, second tap navigates.
        // Do not alter the classic stick figure or blue guide behaviour.
        button.addEventListener('pointerenter',event=>{
          if(event.pointerType!=='mouse' || !pinDesigns.has(root.dataset.hotspotStyle||'classic'))return;
          closePreview();
          openedPreview=button;
          button.classList.add('ppi-hotspot-preview-open');
          button.setAttribute('aria-expanded','true');
        });
        button.addEventListener('pointerleave',event=>{
          if(event.pointerType==='mouse' && openedPreview===button)closePreview();
        });
        button.addEventListener('click',event=>{
          event.stopPropagation();
          const isSvg=pinDesigns.has(root.dataset.hotspotStyle||'classic');
          if(isSvg){
            // Desktop mouse hover already opens it. A mouse click navigates.
            // Touch/keyboard first opens, the next activation navigates.
            const mouseClick=event.detail>0 && !touchPreviewMode && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
            if(!mouseClick && openedPreview!==button){
              closePreview();
              openedPreview=button;
              button.classList.add('ppi-hotspot-preview-open');
              button.setAttribute('aria-expanded','true');
              return;
            }
            closePreview();
          }else if(touchPreviewMode){
            if(openedPreview!==button){
              closePreview();
              openedPreview=button;
              button.classList.add('ppi-hotspot-preview-open');
              button.setAttribute('aria-expanded','true');
              return;
            }
            closePreview();
          }
          navigate(Number(button.dataset.targetId));
        });
        root.append(button);markers.set(key,button);
      }
      const targetId=Number(link.to);
      const hotspotStyle=root.dataset.hotspotStyle||'classic';
      button.classList.toggle('ppi-hotspot-blue', hotspotStyle==='blue');
      button.classList.toggle('ppi-hotspot-map-pin', hotspotStyle==='map-pin');
      button.classList.remove('ppi-hotspot-chat-pin');
      button.classList.toggle('ppi-hotspot-label-pin', hotspotStyle==='label-pin');
      button.classList.toggle('ppi-hotspot-outline-pin', hotspotStyle==='outline-pin');
      // Keep inactive guide artwork OUT of the rendered DOM, rather than
      // relying on CSS/hidden attributes. Nextcloud's editor styles can
      // override SVG display and leave the classical guide's lower arcs
      // visible underneath image-filled pin designs. Preserve nodes so that
      // switching back to the classic or blue design works without reload.
      const isSvgPin=hotspotStyle==='map-pin'||hotspotStyle==='label-pin'||hotspotStyle==='outline-pin';
      if(!button._ppiClassicFigure)button._ppiClassicFigure=button.querySelector('.ppi-hotspot-figure');
      if(!button._ppiBlueFigure)button._ppiBlueFigure=button.querySelector('.ppi-hotspot-blue-figure');
      const classicFigure=button._ppiClassicFigure;
      if(classicFigure){
        if(hotspotStyle==='classic'){
          if(classicFigure.parentNode!==button)button.append(classicFigure);
          classicFigure.hidden=false;
        }else{
          classicFigure.remove();
        }
      }
      const blueFigure=button._ppiBlueFigure;
      if(blueFigure){
        if(hotspotStyle==='blue'){
          if(blueFigure.parentNode!==button)button.append(blueFigure);
          blueFigure.hidden=false;
        }else{
          blueFigure.remove();
        }
      }
      const pinFigure=button.querySelector('.ppi-hotspot-pin-figure');
      if(pinFigure&&(hotspotStyle==='map-pin'||hotspotStyle==='label-pin'||hotspotStyle==='outline-pin')){
        const file=hotspotStyle==='label-pin'?'hotspot-label-pin.svg':hotspotStyle==='outline-pin'?'hotspot-outline-pin.svg':'hotspot-map-pin.svg';
        const path=window.OC?.filePath ? window.OC.filePath('photosphere_photos_integration','img',file) : '/custom_apps/photosphere_photos_integration/img/'+file;
        if(pinFigure.dataset.maskUrl!==path){
          pinFigure.dataset.maskUrl=path;
          pinFigure.style.webkitMaskImage=`url("${path}")`;
          pinFigure.style.maskImage=`url("${path}")`;
          const shadow=button.querySelector('.ppi-hotspot-pin-shadow');
          if(shadow){
            // Keep the mask on the shadow's child: blur the entire silhouette
            // *after* masking, avoiding a hard clipped outline.
            shadow.style.setProperty('--ppi-shadow-mask',`url("${path}")`);
          }
        }
        // Verified in Firefox: an SVG mask with `auto` sizing and `repeat`
        // duplicates its upper arc below the pin and offsets the thumbnail.
        // Apply the exact browser-tested settings on the rendered figure for
        // all SVG designs, in editor and public viewer, regardless of theme CSS.
        for(const prop of ['mask-repeat','-webkit-mask-repeat']){
          pinFigure.style.setProperty(prop,'no-repeat','important');
        }
        for(const prop of ['mask-size','-webkit-mask-size']){
          pinFigure.style.setProperty(prop,'100% 100%','important');
        }
        for(const prop of ['mask-position','-webkit-mask-position']){
          pinFigure.style.setProperty(prop,'center','important');
        }
      }
      button.dataset.targetId=String(targetId);
      const targetName=(getTargetName(targetId) || 'Panorama').replace(/\.(?:jpe?g|png|webp|heic|heif|avif)$/i, '');
      button.title='Weiter zu: '+targetName;
      button.setAttribute('aria-label','Zum Panorama wechseln: '+targetName);
      const thumbnail=button.querySelector('.ppi-hotspot-thumb');
      const thumbUrl=getThumbUrl(targetId);
      if(thumbnail && thumbUrl && thumbnail.getAttribute('src')!==thumbUrl)thumbnail.src=thumbUrl;
      const miniImage=button.querySelector('.ppi-hotspot-pin-mini img');
      if(miniImage && miniImage.getAttribute('src')!==thumbUrl){
        if(thumbUrl)miniImage.src=thumbUrl;
        else miniImage.removeAttribute('src');
      }
      const loc=toScreen({yaw:Number(link.yaw??0),pitch:Number(link.pitch??-0.10)});
      // CSS uses display:flex!important for guide figures. Inline display:none
      // cannot override that declaration; the hidden attribute can.
      button.hidden=!loc;
      if((!loc || loc.opacity<0.15) && openedPreview===button)closePreview();
      if(loc){button.style.left=loc.left;button.style.top=loc.top;button.style.opacity=String(loc.opacity);button.style.pointerEvents=(placementActive||loc.opacity<0.15)?'none':'auto';}
      maybeHopPin(button,pinFigure,hotspotStyle,loc);
    });
    for(const [key,button] of markers){if(!keys.has(key)){if(openedPreview===button)closePreview();button.remove();markers.delete(key);}}
    const existing=root.querySelector('[data-pending="true"]');
    if(pending){
      let button=existing;
      if(!button){button=document.createElement('button');button.className='ppi-hotspot';button.type='button';button.dataset.pending='true';button.textContent='＋';button.disabled=true;root.append(button);}
      const pos=toScreen(pending);button.hidden=!pos;
      if(pos){button.style.left=pos.left;button.style.top=pos.top;button.style.opacity=String(pos.opacity);}
    }else if(existing){existing.remove();}
  }
  function draw(){
    raf=0;if(dead||!ready||!canvas.isConnected)return;
    const scale=Math.min(window.devicePixelRatio||1,2);
    const w=Math.max(1,Math.floor(canvas.clientWidth*scale));
    const h=Math.max(1,Math.floor(canvas.clientHeight*scale));
    if(w!==canvas.width||h!==canvas.height){canvas.width=w;canvas.height=h;gl.viewport(0,0,w,h);}
    gl.uniform1f(un.yaw,yaw);gl.uniform1f(un.pitch,pitch);gl.uniform1f(un.fov,fov*Math.PI/360);
    gl.uniform1f(un.aspect,w/h);gl.drawArrays(gl.TRIANGLE_STRIP,0,4);drawMarkers();
    if (typeof onViewChange === 'function') onViewChange({yaw,pitch,fov,ready, width:canvas.clientWidth,height:canvas.clientHeight});
  }
  const redraw=()=>{if(!dead&&!raf)raf=requestAnimationFrame(draw);};
  const onDown=e=>{
    if(openedPreview)closePreview();
    if(e.pointerType==='mouse'&&e.button!==0)return;
    pointers.set(e.pointerId,{x:e.clientX,y:e.clientY});
    try{canvas.setPointerCapture(e.pointerId);}catch{}
    if(pointers.size===1){prev={x:e.clientX,y:e.clientY};pointerDown={x:e.clientX,y:e.clientY};pinchStart=null;}
    if(pointers.size===2){const p=[...pointers.values()];pinchStart=Math.hypot(p[0].x-p[1].x,p[0].y-p[1].y);pinchFov=fov;prev=null;pointerDown=null;}
    e.preventDefault();
  };
  const onMove=e=>{
    if(!pointers.has(e.pointerId))return;
    pointers.set(e.pointerId,{x:e.clientX,y:e.clientY});
    if(pointers.size>=2){
      const p=[...pointers.values()],d=Math.hypot(p[0].x-p[1].x,p[0].y-p[1].y);
      if(d>1&&pinchStart>1)fov=cap(2*Math.atan(Math.tan(pinchFov*Math.PI/360)*pinchStart/d)*180/Math.PI,MIN_FOV,MAX_FOV);
    }else if(prev){
      const dx=e.clientX-prev.x,dy=e.clientY-prev.y;
      yaw-=dx*fov*Math.PI/180/Math.max(1,canvas.clientWidth);
      pitch=cap(pitch+dy*fov*Math.PI/180/Math.max(1,canvas.clientWidth),-1.54,1.54);
      prev={x:e.clientX,y:e.clientY};
    }
    redraw();e.preventDefault();
  };
  const onUp=e=>{
    const down=pointerDown;
    pointers.delete(e.pointerId);
    if(pointers.size===1){const p=[...pointers.values()][0];prev={x:p.x,y:p.y};pointerDown=null;}
    else if(!pointers.size){prev=null;pointerDown=null;}
    if(down&&pointers.size===0&&Math.hypot(e.clientX-down.x,e.clientY-down.y)<8){onPoint(fromClick(e.clientX,e.clientY));}
  };
  const onWheel=e=>{if(!directWheelZoom&&!e.ctrlKey)return;e.preventDefault();if(!e.deltaY)return;fov=cap(fov+Math.sign(e.deltaY)*4,MIN_FOV,MAX_FOV);redraw();};
  canvas.addEventListener('pointerdown',onDown);canvas.addEventListener('pointermove',onMove);
  canvas.addEventListener('pointerup',onUp);canvas.addEventListener('pointercancel',onUp);
  canvas.addEventListener('wheel',onWheel,{passive:false});
  const observer=typeof ResizeObserver==='function'?new ResizeObserver(redraw):null;observer?.observe(root);
  const image=preloadedImage || new Image();
  // The full 120 MP JPEG is valuable in the download cache, but uploading its
  // decoded RGBA data to WebGL can take seconds and hundreds of MiB. Resize the
  // GPU texture to a bounded size asynchronously when supported. The original
  // image is kept untouched and remains reusable by the tour's preload cache.
  image.onload=async()=>{
    if(dead)return;
    const started=performance.now();
    let bitmap=null;
    try{
      const max=gl.getParameter(gl.MAX_TEXTURE_SIZE);
      const mobileGpu=window.matchMedia?.('(pointer: coarse)').matches || false;
      const preferred=maxPanoramaTexture===4096||maxPanoramaTexture===8192?maxPanoramaTexture:(mobileGpu?4096:8192);
      const limit=Math.min(max,preferred);
      const factor=Math.min(1,limit/image.naturalWidth,limit/image.naturalHeight);
      const targetW=Math.max(1,Math.floor(image.naturalWidth*factor));
      const targetH=Math.max(1,Math.floor(image.naturalHeight*factor));
      onTexturePhase?.('Textur vorbereiten',`${image.naturalWidth}×${image.naturalHeight} → ${targetW}×${targetH}`);
      let source=image;
      if(preparedBitmap && preparedBitmap.width===targetW && preparedBitmap.height===targetH){
        bitmap=preparedBitmap;
        preparedBitmap=null;
        source=bitmap;
        onTexturePhase?.('Textur-Cache-Treffer','Verkleinerte Bitmap bereits im Hintergrund vorbereitet');
      }
      if(factor<1 && source===image){
        // Async image bitmap resizing avoids a large full-resolution canvas
        // allocation on browsers that support resize options (including Firefox).
        if(typeof createImageBitmap==='function'){
          try{
            // Unlike HTMLImageElement/canvas uploads, WebGL ignores
            // UNPACK_FLIP_Y_WEBGL for ImageBitmap uploads. Flip the bitmap
            // during creation to preserve the panorama's correct orientation.
            bitmap=await createImageBitmap(image,{imageOrientation:'flipY',resizeWidth:targetW,resizeHeight:targetH,resizeQuality:'high'});
            source=bitmap;
          }catch(e){
            console.warn('[Photo Sphere] Bitmap-Resize fehlgeschlagen, Canvas-Fallback:',e);
          }
        }
        if(source===image){
          const reduced=document.createElement('canvas');reduced.width=targetW;reduced.height=targetH;
          const ctx=reduced.getContext('2d');if(!ctx)throw new Error('Bild konnte nicht skaliert werden');
          ctx.drawImage(image,0,0,targetW,targetH);source=reduced;
        }
      }
      if(dead){bitmap?.close?.();preparedBitmap?.close?.();preparedBitmap=null;return;}
      onTexturePhase?.('Bildverarbeitung',`${Math.round(performance.now()-started)} ms`);
      const uploaded=performance.now();
      gl.bindTexture(gl.TEXTURE_2D,tex);
      // ImageBitmap already contains the vertical flip above; other image
      // sources still rely on WebGL's unpack-time vertical flip.
      gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL,source===bitmap?false:true);
      gl.texImage2D(gl.TEXTURE_2D,0,gl.RGBA,gl.RGBA,gl.UNSIGNED_BYTE,source);
      if(gl.getError()!==gl.NO_ERROR)throw new Error('Grafiktreiber konnte die JPEG-Textur nicht laden');
      onTexturePhase?.('GPU-Upload',`${Math.round(performance.now()-uploaded)} ms`);
      ready=true;report('360°-Panorama geladen. Zum Platzieren „Wechselpunkt setzen“ auswählen.');redraw();
    }catch(e){if(!dead)report('3D-Ladefehler: '+e.message);}
    finally{bitmap?.close?.();preparedBitmap?.close?.();preparedBitmap=null;}
  };
  image.onerror=()=>{if(!dead)report('Panorama nicht erreichbar. Bitte Dateizugriff und Nextcloud-Protokoll prüfen.');};
  // A completed neighbour preload can be uploaded to WebGL immediately.
  // Still create the texture here: preloaded images do not keep GPU resources alive.
  if(preloadedImage && image.complete && image.naturalWidth>0) queueMicrotask(()=>image.onload?.());
  else if(!preloadedImage) image.src=url;
  else if(image.complete) queueMicrotask(()=>image.onerror?.());
  return {
    getView(){return {yaw,pitch,fov};},
    adjustView(deltaYaw=0,deltaPitch=0){yaw+=deltaYaw;pitch=cap(pitch+deltaPitch,-1.54,1.54);redraw();},
    zoomBy(delta){fov=cap(fov+delta,MIN_FOV,MAX_FOV);redraw();},
    setPending(point){pending=point;redraw();},
    setPlacementMode(enabled){
      placementActive=editorMode && !!enabled;
      // No existing marker may swallow a canvas click while selecting a
      // new point. Restore normal navigation immediately after placement.
      for(const button of markers.values())button.style.pointerEvents=placementActive?'none':'auto';
      redraw();
    },
    setView(nextYaw,nextPitch=0){yaw=Number(nextYaw)||0;pitch=cap(Number(nextPitch)||0,-1.5,1.5);redraw();},
    refreshMarkers(){redraw();},
    destroy(){dead=true;preparedBitmap?.close?.();preparedBitmap=null;if(raf)cancelAnimationFrame(raf);observer?.disconnect();image.onload=null;image.onerror=null;if(!preloadedImage)image.src='';
      canvas.removeEventListener('pointerdown',onDown);canvas.removeEventListener('pointermove',onMove);
      canvas.removeEventListener('pointerup',onUp);canvas.removeEventListener('pointercancel',onUp);canvas.removeEventListener('wheel',onWheel);
      gl.deleteTexture(tex);gl.deleteBuffer(buffer);gl.deleteProgram(program);gl.deleteShader(vs);gl.deleteShader(fs);
      // Keep a newer panorama's loading overlay when an older sphere dies.
      root.replaceChildren(...root.querySelectorAll(':scope > .ppi-sphere-loading'));
    }
  };
}
