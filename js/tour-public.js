/* Public 360° tour viewer: only token-scoped image routes, no Nextcloud file-manager context. */
(() => {
  'use strict';
  const root = document.getElementById('ppi-public-tour');
  if (!root) return;
  const byId = id => document.getElementById(id);
  const status = byId('ppi-public-message');
  const surface = byId('ppi-public-sphere');
  const nav = byId('ppi-public-nav');
  const tourWatermark = byId('ppi-public-tour-watermark');
  // Match the existing administrator watermark controls used in the Files viewer.
  // Per-tour selection chooses the image; all positioning remains centrally managed.
  const watermarkDefaults = Object.freeze({mode:'panorama',yaw:0,pitchDesktop:40,pitchMobile:56,
    screenX:88,screenY:85,sizeDesktop:196,sizeMobile:108});
  const watermarkOptions = (()=>{
    try {return {...watermarkDefaults,...JSON.parse(root.dataset.watermarkOptions||'{}')}}
    catch {return {...watermarkDefaults}}
  })();
  let tourWatermarkActive=false;
  let watermarkLoaded=false;
  const radiansPerDegreeWatermark=Math.PI/180;
  const clampWM=(v,min,max)=>Math.max(min,Math.min(max,v));
  function updateTourWatermark(view) {
    if (!tourWatermarkActive || !watermarkLoaded || !view?.ready) {
      tourWatermark.style.opacity='0';
      return;
    }
    const width=Math.max(1,view.width),height=Math.max(1,view.height);
    if (watermarkOptions.mode==='screen') {
      const x=Number(watermarkOptions.screenX),y=Number(watermarkOptions.screenY);
      tourWatermark.style.left=`${clampWM(Number.isFinite(x)?x:88,0,100)}%`;
      tourWatermark.style.top=`${clampWM(Number.isFinite(y)?y:85,0,100)}%`;
      tourWatermark.style.transform='translate(-50%,-50%)';
      tourWatermark.style.opacity='.94';
      return;
    }
    const yaw=Number(view.yaw),pitch=Number(view.pitch),fov=Number(view.fov);
    const heading=Number(watermarkOptions.yaw);
    const altitude=Number(mobile()?watermarkOptions.pitchMobile:watermarkOptions.pitchDesktop);
    const wmYaw=(Number.isFinite(heading)?heading:0)*radiansPerDegreeWatermark;
    const wmPitch=(Number.isFinite(altitude)?altitude:(mobile()?56:40))*radiansPerDegreeWatermark;
    const direction={x:Math.sin(wmYaw)*Math.cos(wmPitch),y:Math.sin(wmPitch),z:-Math.cos(wmYaw)*Math.cos(wmPitch)};
    const forward={x:Math.sin(yaw)*Math.cos(pitch),y:Math.sin(pitch),z:-Math.cos(yaw)*Math.cos(pitch)};
    const right={x:Math.cos(yaw),y:0,z:Math.sin(yaw)};
    const up={x:-right.z*forward.y,y:right.z*forward.x-right.x*forward.z,z:right.x*forward.y};
    const dz=direction.x*forward.x+direction.y*forward.y+direction.z*forward.z;
    if (dz<=0.05) {tourWatermark.style.opacity='0';return;}
    const dx=direction.x*right.x+direction.y*right.y+direction.z*right.z;
    const dy=direction.x*up.x+direction.y*up.y+direction.z*up.z;
    const tan=Math.tan(fov*Math.PI/360);
    const px=(dx/dz)/tan,py=(dy/dz)*(width/height)/tan;
    if (!Number.isFinite(px)||!Number.isFinite(py)||Math.abs(px)>1.18||Math.abs(py)>1.12) {
      tourWatermark.style.opacity='0';return;
    }
    const edgeFactor=Math.max(Math.max(0,Math.abs(px)-.78)/.4,Math.max(0,Math.abs(py)-.72)/.4);
    const scale=Math.max(.82,1-edgeFactor*.16);
    tourWatermark.style.left=`${(px+1)*.5*width}px`;
    tourWatermark.style.top=`${(1-py)*.5*height}px`;
    tourWatermark.style.transform=`translate(-50%,-50%) scale(${scale.toFixed(3)})`;
    tourWatermark.style.opacity=String(Math.max(0,.94*(1-edgeFactor*.3)));
  }
  function applyTourWatermark(display) {
    tourWatermarkActive=display.watermarkEnabled===true && !!root.dataset.watermarkUrl;
    tourWatermark.hidden=!tourWatermarkActive;
    watermarkLoaded=false;
    if (!tourWatermarkActive) {tourWatermark.removeAttribute('src');return;}
    const size=Number(mobile()?watermarkOptions.sizeMobile:watermarkOptions.sizeDesktop);
    const defaultSize=mobile()?108:196;
    tourWatermark.style.width=`${clampWM(Number.isFinite(size)?size:defaultSize,30,500)}px`;
    tourWatermark.style.left='50%';tourWatermark.style.top='50%';
    tourWatermark.style.opacity='0';
    tourWatermark.onload=()=>{watermarkLoaded=true;if(sphere)updateTourWatermark({...sphere.getView(),width:surface.clientWidth,height:surface.clientHeight,ready:true});};
    tourWatermark.onerror=()=>{watermarkLoaded=false;tourWatermark.hidden=true;};
    tourWatermark.src=root.dataset.watermarkUrl;
  }
  const landing=byId('ppi-public-landing');
  const cover=byId('ppi-public-cover');
  const startButton=byId('ppi-public-start');
  const viewer=byId('ppi-public-tour').querySelector('.ppi-public-view');
  let current = null;
  let sphere = null;
  let messageTimer=0;
  let loaderToken=0;
  let gyroHintTimer=0,gyroHintAnimation=null;
  const setMessage=message=>{
    clearTimeout(messageTimer);
    status.textContent=message;
    if(message && /geladen|Zum Platzieren/.test(message))messageTimer=setTimeout(()=>{status.textContent='';},1100);
  };
  const mobile=()=>matchMedia('(pointer:coarse)').matches;
  const shell=byId('ppi-public-gyro');
  const gyroSwitch=byId('ppi-public-gyro-switch');
  let gyroEnabled=false,gyroAnchor=null,gyroLastHeading=null,gyroUnwrap=0,gyroAngle=null;
  let gyroTarget=null,gyroFrame=0,gyroLastTime=0;
  const cap=(v,a,b)=>Math.max(a,Math.min(b,v));
  const angleDiff=v=>Math.atan2(Math.sin(v),Math.cos(v));
  const radiansPerDegree=Math.PI/180;
  function quatMultiply(a,b){return {x:a.w*b.x+a.x*b.w+a.y*b.z-a.z*b.y,
    y:a.w*b.y-a.x*b.z+a.y*b.w+a.z*b.x,z:a.w*b.z+a.x*b.y-a.y*b.x+a.z*b.w,
    w:a.w*b.w-a.x*b.x-a.y*b.y-a.z*b.z};}
  function quatAxis(x,y,z,v){const s=Math.sin(v/2);return {x:x*s,y:y*s,z:z*s,w:Math.cos(v/2)};}
  function orientationVector(alpha,beta,gamma,angle){
    const x=beta*radiansPerDegree,y=alpha*radiansPerDegree,z=-gamma*radiansPerDegree;
    const cx=Math.cos(x/2),sx=Math.sin(x/2),cy=Math.cos(y/2),sy=Math.sin(y/2),cz=Math.cos(z/2),sz=Math.sin(z/2);
    const e={x:sx*cy*cz+cx*sy*sz,y:cx*sy*cz-sx*cy*sz,z:cx*cy*sz-sx*sy*cz,w:cx*cy*cz+sx*sy*sz};
    const q=quatMultiply(quatMultiply(e,quatAxis(1,0,0,-Math.PI/2)),quatAxis(0,0,1,-angle*radiansPerDegree));
    const v={x:0,y:0,z:-1,w:0};
    const c={x:-q.x,y:-q.y,z:-q.z,w:q.w};
    const r=quatMultiply(quatMultiply(q,v),c);return r;
  }
  function onOrientation(event){
    if(!gyroEnabled||!sphere)return;
    if([event.alpha,event.beta,event.gamma].some(v=>v==null))return;
    const dir=orientationVector(event.alpha,event.beta,event.gamma,Number(screen.orientation?.angle??window.orientation??0));
    const horiz=Math.hypot(dir.x,dir.z);
    if(horiz<.17)return;
    const heading=Math.atan2(dir.x,-dir.z),elevation=Math.asin(cap(dir.y,-1,1));
    const angle=Number(screen.orientation?.angle??window.orientation??0);
    if(!gyroAnchor||angle!==gyroAngle){
      const view=sphere.getView();gyroAnchor={heading,elevation,yaw:view.yaw,pitch:view.pitch};
      gyroLastHeading=heading;gyroUnwrap=0;gyroAngle=angle;gyroTarget={yaw:view.yaw,pitch:view.pitch};return;
    }
    const delta=angleDiff(heading-gyroLastHeading);gyroLastHeading=heading;
    if(Math.abs(delta)>.70)return;
    gyroUnwrap+=delta;
    gyroTarget={yaw:gyroAnchor.yaw+gyroUnwrap,pitch:cap(gyroAnchor.pitch+elevation-gyroAnchor.elevation,-1.54,1.54)};
  }
  function gyroTick(time){
    gyroFrame=0;if(!gyroEnabled||!sphere)return;
    const dt=gyroLastTime?Math.min(.08,(time-gyroLastTime)/1000):1/60;gyroLastTime=time;
    if(gyroTarget){const view=sphere.getView(),f=1-Math.exp(-dt/.18);
      sphere.adjustView(angleDiff(gyroTarget.yaw-view.yaw)*f,(gyroTarget.pitch-view.pitch)*f);}
    gyroFrame=requestAnimationFrame(gyroTick);
  }
  function stopGyro(){
    gyroEnabled=false;gyroAnchor=null;gyroTarget=null;gyroLastHeading=null;gyroLastTime=0;
    window.removeEventListener('deviceorientation',onOrientation);
    if(gyroFrame)cancelAnimationFrame(gyroFrame);gyroFrame=0;
    gyroSwitch.textContent='Gyroskop: Aus';
  }
  const motionAvailable=window.isSecureContext && mobile() && typeof DeviceOrientationEvent!=='undefined';
  const gyroGrip=byId('ppi-public-gyro-grip');
  const gyroPanel=shell.querySelector('.ppi-public-gyro-panel');
  function stopGyroHint(){
    clearTimeout(gyroHintTimer);gyroHintTimer=0;
    gyroHintAnimation?.cancel();gyroHintAnimation=null;
  }
  function setGyroPanelOpen(open){
    stopGyroHint();
    shell.dataset.open=String(Boolean(open));
    gyroGrip.setAttribute('aria-expanded',String(Boolean(open)));
    gyroGrip.setAttribute('aria-label',open?'Gyroskop-Steuerung schließen':'Gyroskop-Steuerung öffnen');
  }
  function revealGyroGrip(){
    if(!mobile())return;
    stopGyroHint();
    shell.style.visibility='visible';shell.style.pointerEvents='auto';
    gyroHintTimer=setTimeout(()=>{
      gyroHintTimer=0;
      if(!shell.isConnected || shell.dataset.open==='true' ||
        matchMedia('(prefers-reduced-motion: reduce)').matches || typeof shell.animate!=='function')return;
      gyroHintAnimation=shell.animate([
        {transform:'translateY(-50%) translateX(calc(100% - 14px))',offset:0},
        {transform:'translateY(-50%) translateX(calc(100% - 34px))',offset:.42},
        {transform:'translateY(-50%) translateX(calc(100% - 34px))',offset:.58},
        {transform:'translateY(-50%) translateX(calc(100% - 14px))',offset:1}
      ],{duration:780,iterations:2,easing:'ease-in-out'});
      gyroHintAnimation.addEventListener('finish',()=>{gyroHintAnimation=null;},{once:true});
    },1500);
  }
  if(mobile()){
    shell.hidden=false;
    gyroGrip.addEventListener('click',()=>setGyroPanelOpen(shell.dataset.open!=='true'));
    if(!motionAvailable)gyroSwitch.hidden=true;
    gyroSwitch.addEventListener('click',async event=>{
      event.stopPropagation();
      setGyroPanelOpen(false);
      if(gyroEnabled){stopGyro();return;}
      try{
        if(typeof DeviceOrientationEvent.requestPermission==='function'){
          const permission=await DeviceOrientationEvent.requestPermission();
          if(permission!=='granted')throw new Error('Sensorzugriff nicht erlaubt');
        }
        gyroEnabled=true;gyroAnchor=null;gyroLastTime=0;
        window.addEventListener('deviceorientation',onOrientation);
        gyroSwitch.textContent='Gyroskop: Ein';gyroFrame=requestAnimationFrame(gyroTick);
        setGyroPanelOpen(false);
      }catch(e){stopGyro();setMessage('Gyroskop nicht verfügbar oder nicht erlaubt.');}
    });
  }
  // Same interaction as the Files/Photos panorama viewer: a finger gesture
  // switches sensor mode off before manual dragging starts.
  // Fold the mobile controls away once a finger actually drags the panorama.
  // Do not close for a simple tap, so tapping a hotspot remains predictable.
  const panoramaTouchStarts=new Map();
  surface.addEventListener('pointerdown',event=>{
    if(gyroEnabled && event.pointerType==='touch')stopGyro();
    if(event.pointerType==='touch'){
      panoramaTouchStarts.set(event.pointerId,{x:event.clientX,y:event.clientY});
    }
  },true);
  surface.addEventListener('pointermove',event=>{
    if(event.pointerType!=='touch'||shell.dataset.open!=='true')return;
    const start=panoramaTouchStarts.get(event.pointerId);
    if(!start)return;
    if(Math.hypot(event.clientX-start.x,event.clientY-start.y)>9){
      setGyroPanelOpen(false);
      panoramaTouchStarts.clear();
    }
  },true);
  const forgetPanoramaTouch=event=>panoramaTouchStarts.delete(event.pointerId);
  surface.addEventListener('pointerup',forgetPanoramaTouch,true);
  surface.addEventListener('pointercancel',forgetPanoramaTouch,true);
  const pressed=new Set(),zoomPressed=new Set();
  let movementFrame=0,previousTime=0,vx=0,vy=0,vz=0;
  const tick=time=>{
    movementFrame=0;
    const dt=previousTime?Math.min(.06,(time-previousTime)/1000):1/60;previousTime=time;
    const tx=Number(pressed.has('ArrowRight'))-Number(pressed.has('ArrowLeft'));
    const ty=Number(pressed.has('ArrowUp'))-Number(pressed.has('ArrowDown'));
    const tz=Number(zoomPressed.has('ArrowUp'))-Number(zoomPressed.has('ArrowDown'));
    const smooth=(current,target,seconds)=>current+(target-current)*(1-Math.exp(-dt/seconds));
    vx=smooth(vx,tx,tx?.55:.45);vy=smooth(vy,ty,ty?.55:.45);vz=smooth(vz,tz,tz?.9:.7);
    if(sphere){if(Math.abs(vx)+Math.abs(vy)>.002)sphere.adjustView(vx*.65*dt,vy*.65*dt);
      if(Math.abs(vz)>.002)sphere.zoomBy(-vz*34*dt);}
    if(pressed.size||zoomPressed.size||Math.abs(vx)+Math.abs(vy)+Math.abs(vz)>.003)
      movementFrame=requestAnimationFrame(tick);
    else{vx=vy=vz=0;previousTime=0;}
  };
  const keys=new Set(['ArrowLeft','ArrowRight','ArrowUp','ArrowDown']);
  function onKeyDown(e){
    if(!keys.has(e.key)||e.altKey||e.metaKey||/INPUT|TEXTAREA|SELECT/.test(e.target?.tagName||''))return;
    if(e.ctrlKey && (e.key==='ArrowUp'||e.key==='ArrowDown'))zoomPressed.add(e.key);
    else pressed.add(e.key);
    e.preventDefault();e.stopPropagation();
    if(!movementFrame)movementFrame=requestAnimationFrame(tick);
  }
  function onKeyUp(e){if(!keys.has(e.key))return;pressed.delete(e.key);zoomPressed.delete(e.key);}
  function clearKeys(){pressed.clear();zoomPressed.clear();}
  window.addEventListener('keydown',onKeyDown,true);
  window.addEventListener('keyup',onKeyUp,true);
  window.addEventListener('blur',clearKeys);
  document.addEventListener('visibilitychange',()=>{if(document.hidden)clearKeys();});
  const navToggle=byId('ppi-public-nav-toggle');
  navToggle.addEventListener('click',()=>{toggleNavigation();});
  byId('ppi-public-help').addEventListener('click',()=>{const panel=byId('ppi-public-guide');panel.hidden=!panel.hidden;});
  // Keep the pre-existing gyro side tab as the only control on touch devices.
  const mobileNav=byId('ppi-public-mobile-nav');
  const mobileFullscreen=byId('ppi-public-mobile-fullscreen');
  const setNavigationOpen=open=>{
    const isOpen=Boolean(open);
    nav.hidden=!isOpen;
    navToggle.setAttribute('aria-pressed',String(isOpen));
    mobileNav.setAttribute('aria-pressed',String(isOpen));
    mobileNav.textContent=isOpen?'Menü ausblenden':'Menü anzeigen';
  };
  const toggleNavigation=()=>{
    setNavigationOpen(nav.hidden);
    setGyroPanelOpen(false);
  };
  mobileNav.addEventListener('click',toggleNavigation);
  mobileFullscreen.addEventListener('click',()=>{
    setGyroPanelOpen(false);
    toggleFullscreen();
  });

  if (root.dataset.invalid === '1') {viewer.hidden=false;status.textContent='Dieser Tour-Link ist nicht verfügbar oder wurde deaktiviert.';return;}
  const api = root.dataset.manifestUrl;
  const imageBase = root.dataset.imageUrl;
  const thumbBase = root.dataset.thumbUrl;
  const thumbnailUrl = id => thumbBase.replace(/\/0(?=\?|$)/, `/${encodeURIComponent(id)}`);
  let tour = null;
  let display = {};
  const radians = deg => Number(deg||0)*Math.PI/180;
  const originalImageUrl = id => imageBase.replace(/\/0(?=\?|$)/, `/${encodeURIComponent(id)}`);
  // Device-aware public tour previews. These endpoints retain the existing
  // token/password authorisation. Probe WebGL before requesting any panorama;
  // unknown capabilities fail conservatively, without enlarging decoded images.
  const optimizedImageBase=root.dataset.optimizedImageUrl||'';
  const serverPreviewEnabled=Boolean(optimizedImageBase) &&
    new URLSearchParams(window.location.search).get('ppi_server_preview')!=='0';
  function detectPanoramaCapabilities(){
    let maxTexture=4096;
    try {
      const probe=document.createElement('canvas');
      const gl=probe.getContext('webgl',{alpha:false,antialias:false,failIfMajorPerformanceCaveat:true});
      if(gl){maxTexture=Number(gl.getParameter(gl.MAX_TEXTURE_SIZE))||4096;
        gl.getExtension('WEBGL_lose_context')?.loseContext();}
    } catch (_) { /* Use the conservative profile if probing is unavailable. */ }
    const connection=navigator.connection;
    const memory=Number(navigator.deviceMemory)||0; // Not exposed by Firefox/Safari
    const cores=Number(navigator.hardwareConcurrency)||0;
    const constrained=mobile() || connection?.saveData===true ||
      ['slow-2g','2g','3g'].includes(connection?.effectiveType||'') ||
      (memory>0 && memory<6) || (cores>0 && cores<4);
    return {maxTexture,constrained,memory,cores};
  }
  const panoramaCapabilities=detectPanoramaCapabilities();
  let serverPreviewProfile=serverPreviewEnabled &&
    !panoramaCapabilities.constrained && panoramaCapabilities.maxTexture>=8192
    ? 'desktop':'mobile';
  let slowGpuUploads=0;
  function imageUrl(id, profile=serverPreviewProfile){
    return serverPreviewEnabled
      ? optimizedImageBase.replace(/\/0\/(?:mobile|desktop)(?=\?|$)/,`/${encodeURIComponent(id)}/${profile}`)
      : originalImageUrl(id);
  }
  // The browser's speculative cache and foreground always request the same
  // profile. If the profile changes, stale neighbours are evicted below.
  // Small, token-scoped neighbour cache. Never preload before the visitor
  // explicitly starts a tour or after the page is hidden / the link changes.
  const neighbourCache = new Map();
  // Opt-in diagnostic overlay: enable only with ?ppi_preload_debug=1.
  // No admin permissions, passwords, links or other private tour data displayed.
  // This runs locally in the browser and sends no diagnostics to the server.
  const preloadDebugEnabled = new URLSearchParams(window.location.search).get('ppi_preload_debug') === '1';
  const preloadDebugLog=[];
  let preloadDebugBox=null;
  let preloadDebugText=null;
  function preloadDebug(event, detail='') {
    if(!preloadDebugEnabled)return;
    const stamp=new Date().toLocaleTimeString('de-DE',{hour12:false});
    const line=`${stamp} · ${event}${detail?' · '+detail:''}`;
    preloadDebugLog.push(line);
    if(preloadDebugLog.length>60)preloadDebugLog.shift();
    if(preloadDebugText)preloadDebugText.textContent=preloadDebugLog.slice(-9).join('\n');
    console.info('[Photo Sphere Vorladen]',event,detail);
  }
  if(preloadDebugEnabled){
    preloadDebugBox=document.createElement('aside');
    preloadDebugBox.setAttribute('aria-label','Photo Sphere Vorlade-Diagnose');
    preloadDebugBox.style.cssText='position:fixed;z-index:2147483000;top:max(66px,env(safe-area-inset-top));left:8px;width:min(370px,calc(100vw - 16px));max-height:45dvh;overflow:auto;background:rgba(5,16,26,.93);color:#fff;border:1px solid #60b8ec;border-radius:10px;padding:10px;box-sizing:border-box;font:12px/1.55 system-ui,sans-serif;box-shadow:0 5px 25px #0008;pointer-events:auto;';
    const head=document.createElement('div');head.style.cssText='display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:5px;';
    const title=document.createElement('strong');title.textContent='Photo Sphere · Vorlade-Test';
    const controls=document.createElement('span');controls.style.cssText='display:flex;gap:6px;';
    const copy=document.createElement('button');copy.type='button';copy.textContent='Kopieren';
    const close=document.createElement('button');close.type='button';close.textContent='×';
    for(const btn of [copy,close])btn.style.cssText='font:inherit;color:white;background:#28475b;border:1px solid #7793a5;border-radius:5px;padding:3px 7px;cursor:pointer;';
    copy.addEventListener('click',()=>{const output=preloadDebugLog.join('\n');
      if(navigator.clipboard?.writeText)navigator.clipboard.writeText(output).then(()=>{copy.textContent='Kopiert';}).catch(()=>{console.info(output);copy.textContent='In Konsole';});
      else{console.info(output);copy.textContent='In Konsole';}
    });
    close.addEventListener('click',()=>preloadDebugBox.remove());
    controls.append(copy,close);head.append(title,controls);
    preloadDebugText=document.createElement('pre');preloadDebugText.style.cssText='white-space:pre-wrap;overflow-wrap:anywhere;margin:0;max-height:30dvh;overflow:auto;color:#d7f0ff;font:11px/1.5 ui-monospace,monospace;';
    preloadDebugBox.append(head,preloadDebugText);root.append(preloadDebugBox);
    preloadDebug('Diagnose aktiv',`Nur dieser Browser / diese Seite · Automatische Server-Vorschau ${serverPreviewEnabled?`${serverPreviewProfile} (${serverPreviewProfile==='mobile'?'4096×2048':'8192×4096'}) · WebGL-Limit ${panoramaCapabilities.maxTexture}`:'aus'}`);
  }
  const cacheLimit = mobile() || serverPreviewProfile==='mobile' ? 1 : 2;
  function downgradePanorama(reason){
    if(!serverPreviewEnabled || serverPreviewProfile==='mobile')return false;
    serverPreviewProfile='mobile';
    preloadDebug('Automatisch auf 4096×2048 gewechselt',reason);
    clearTimeout(preloadTimer);
    for(const entry of neighbourCache.values()){
      entry.image.onload=null;entry.image.onerror=null;
      if(entry.status==='loading'){entry.image.src='';entry.settle?.(null);}
      disposePrepared(entry);
    }
    neighbourCache.clear();
    return true;
  }
  function observeGpuUpload(detail){
    const match=String(detail).match(/^(\d+)\s*ms/);
    if(!match || serverPreviewProfile!=='desktop')return;
    const ms=Number(match[1]);
    slowGpuUploads=ms>=650?slowGpuUploads+1:0;
    if(slowGpuUploads>=2)downgradePanorama(`GPU-Upload wiederholt langsam (${ms} ms)`);
  }
  let preloadTimer = 0;
  // Keep only ONE prepared GPU-ready bitmap at a time (about 128 MiB desktop,
  // 32 MiB mobile at the configured texture limits). Other cached neighbours
  // retain their decoded JPEG only. Bitmaps are owned by their cache entries
  // until transferred to the viewer, and closed on eviction.
  function disposePrepared(entry){
    if(entry?.prepared){entry.prepared.close?.();entry.prepared=null;}
  }
  async function prepareCachedBitmap(id,entry){
    if(typeof createImageBitmap!=='function'||!canPreload()||entry.status!=='ready')return;
    // Do not prepare additional bitmaps while another is kept in memory.
    if([...neighbourCache.values()].some(other=>other!==entry && (other.prepared||other.preparing)))return;
    const image=entry.image;
    const limit=Math.min(panoramaCapabilities.maxTexture,serverPreviewProfile==='mobile'?4096:8192);
    const factor=Math.min(1,limit/image.naturalWidth,limit/image.naturalHeight);
    // Avoid allocating a second full-resolution bitmap for small images.
    if(factor>=1)return;
    const width=Math.max(1,Math.floor(image.naturalWidth*factor));
    const height=Math.max(1,Math.floor(image.naturalHeight*factor));
    const start=performance.now();
    entry.preparing=true;
    preloadDebug('Textur im Hintergrund vorbereiten',`${id} · ${width}×${height}`);
    try{
      // Preserve the tested Firefox/WebGL flip correction from version .68.
      const bitmap=await createImageBitmap(image,{imageOrientation:'flipY',resizeWidth:width,resizeHeight:height,resizeQuality:'high'});
      if(neighbourCache.get(id)===entry && entry.status==='ready' && canPreload()){
        entry.prepared=bitmap;
        preloadDebug('Textur vorbereitet',`${id} · ${Math.round(performance.now()-start)} ms · ${width}×${height}`);
      }else bitmap.close?.();
    }catch(error){
      preloadDebug('Textur-Vorbereitung übersprungen',`${id} · ${error?.message||error}`);
    }finally{entry.preparing=false;}
  }
  const canPreload = () => !document.hidden && !navigator.connection?.saveData &&
    !['slow-2g','2g'].includes(navigator.connection?.effectiveType||'');
  function preloadNeighbours(fromId) {
    clearTimeout(preloadTimer);
    if(!tour || !canPreload()){preloadDebug('Vorladen übersprungen','Seite inaktiv oder Datensparen');return;}
    preloadDebug('Nachbarn prüfen',`von ${fromId}`);
    const linked = [...new Set(tour.links.filter(l=>Number(l.from)===Number(fromId))
      .map(l=>Number(l.to)).filter(id=>Number.isFinite(id)&&id!==Number(fromId)&&
        tour.images.some(img=>Number(img.id)===id)))].slice(0,cacheLimit);
    preloadDebug('Ziele gefunden',linked.length?linked.join(', '):'Keine direkten Verbindungen');
    // Do not retain more decoded JPEGs than needed, especially on mobile.
    for(const [id,entry] of neighbourCache){
      if(!linked.includes(id)){
        if(entry.status==='loading'){entry.image.src='';entry.settle?.(null);}
        preloadDebug('Cache aufräumen',String(id));
        entry.image.onload=null;entry.image.onerror=null;disposePrepared(entry);neighbourCache.delete(id);
      }
    }
    // Fetch serially: large 360° JPEGs must not compete for memory/bandwidth.
    const next=(index)=>{
      if(index>=linked.length || current!==Number(fromId) || !canPreload())return;
      const id=linked[index],existing=neighbourCache.get(id);
      if(existing){preloadDebug('Bereits im Cache',`${id} (${existing.status})`);next(index+1);return;}
      const image=new Image();
      const entry={image,status:'loading',profile:serverPreviewProfile};
      // A foreground navigation can subscribe to this exact in-flight image.
      // Promise always settles (including errors/evictions), without an
      // unhandled rejection or a second request for the same JPEG.
      entry.promise=new Promise(resolve=>{entry.settle=resolve;});
      neighbourCache.set(id,entry);
      const started=performance.now();preloadDebug('Vorladen gestartet',`${id} · ${serverPreviewEnabled?`Server-Vorschau (${serverPreviewProfile==='mobile'?'4096×2048':'8192×4096'})`:'Original'}`);
      image.onload=()=>{
        if(neighbourCache.get(id)!==entry)return;
        entry.status=image.naturalWidth>0?'ready':'error';
        preloadDebug(entry.status==='ready'?'Vollständig vorgeladen':'Vorladen fehlgeschlagen',`${id} · ${Math.round(performance.now()-started)} ms · ${image.naturalWidth}×${image.naturalHeight}`);
        if(entry.status==='error')neighbourCache.delete(id);
        if(entry.status==='ready'){
          // A full-resolution 120 MP original causes a visible UI hitch while
          // createImageBitmap resizes it, even though the Promise is async.
          // Never do that expensive work during free panorama navigation.
          // Server-optimized derivatives (normally already 4096/8192 wide)
          // keep the existing fast cache path unchanged.
          const preferred=serverPreviewProfile==='mobile'?4096:8192;
          const needsResize=image.naturalWidth>preferred||image.naturalHeight>preferred;
          if(needsResize){
            preloadDebug('Texturvorbereitung bis zum Wechsel verschoben',
              `${id} · ${image.naturalWidth}×${image.naturalHeight} · Panorama bleibt bedienbar`);
          }else{
            entry.preparePromise=prepareCachedBitmap(id,entry);
          }
        }
        entry.settle?.(entry.status==='ready'?image:null);
        next(index+1);
      };
      image.onerror=()=>{
        preloadDebug('Vorladen fehlgeschlagen',String(id));
        if(neighbourCache.get(id)===entry)neighbourCache.delete(id);
        entry.settle?.(null);
        next(index+1);
      };
      image.src=imageUrl(id,entry.profile);
    };
    next(0);
  }
  // Background preload only after the foreground image is ready and the page
  // has been idle. Serial downloads; at most one on mobile or two on desktop.
  // Never start before the visitor starts the tour, and never on data-saver / 2G.
  function scheduleNeighbourPreload(id){
    clearTimeout(preloadTimer);
    if(!canPreload()){preloadDebug('Vorladen deaktiviert','Datensparen, Verbindung oder Tab inaktiv');return;}
    preloadDebug('Vorladen geplant',`${id} · in ${mobile()?'1,0':'2,2'} s`);
    preloadTimer=setTimeout(()=>{
      preloadTimer=0;
      if(current===Number(id) && !document.hidden)preloadNeighbours(id);
    },mobile()?1000:2200);
  }

  // Overlay mirrors the editor's existing loading animation. The image is
  // loaded by an Image object so the bar is indeterminate (no fake percent).
  function showLoader(id){
    const veil=document.createElement('div');veil.className='ppi-sphere-loading';
    veil.setAttribute('role','status');veil.setAttribute('aria-live','polite');
    const preview=document.createElement('div');preview.className='ppi-sphere-loading-preview';
    // Public, token-scoped route: never request an authenticated core preview.
    preview.style.backgroundImage=`linear-gradient(rgba(0,0,0,.30),rgba(0,0,0,.42)),url("${thumbnailUrl(id)}")`;
    // Match the Files/Photos viewer: a muted blurred backdrop and a second,
    // clearer preview that fills from top to bottom before the soft crossfade.
    // This is a visual readiness effect, not a fabricated download percentage.
    const progressPreview=document.createElement('div');progressPreview.className='ppi-sphere-loading-progress-preview';
    progressPreview.style.backgroundImage=preview.style.backgroundImage;
    const content=document.createElement('div');content.className='ppi-sphere-loading-content';
    const spinner=document.createElement('div');spinner.className='ppi-sphere-loading-spinner';spinner.setAttribute('aria-hidden','true');
    const title=document.createElement('div');title.textContent='Panorama wird geladen …';
    const detail=document.createElement('div');detail.className='ppi-sphere-loading-detail';detail.textContent='360°-Ansicht wird vorbereitet';
    const track=document.createElement('div');track.className='ppi-sphere-loading-track';
    const fill=document.createElement('div');fill.className='ppi-sphere-loading-fill';track.append(fill);
    content.append(spinner,title,detail,track);veil.append(preview,progressPreview,content);surface.append(veil);
    return veil;
  }
  function finishLoader(veil,token){
    if(token!==loaderToken||!veil?.isConnected)return;
    requestAnimationFrame(()=>requestAnimationFrame(()=>{
      if(token!==loaderToken||!veil.isConnected)return;
      // First the soft preview fills top-to-bottom, as in Files/Photos;
      // then crossfade gently to the ready, sharp WebGL panorama.
      const preview=veil.querySelector('.ppi-sphere-loading-progress-preview');
      if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches && preview){
        preview.classList.add('ppi-sphere-loading-preview-complete');
        setTimeout(()=>{
          if(token!==loaderToken||!veil.isConnected)return;
          veil.classList.add('ppi-sphere-loading-done');
        },560);
      }else veil.classList.add('ppi-sphere-loading-done');
      veil.addEventListener('transitionend',event=>{
        if(event.target===veil && event.propertyName==='opacity')veil.remove();
      });
      setTimeout(()=>veil.remove(),1250);
      revealGyroGrip();
    }));
  }
  async function showImage(id) {
    const img = tour.images.find(x => Number(x.id) === Number(id));
    if (!img) return;
    current = Number(id);
    const token=++loaderToken;
    clearTimeout(preloadTimer);
    // Keep the requested image's in-flight preload alive. Cancelling it here
    // used to trigger an expensive second full-resolution network request on
    // mobile. Only unrelated speculative downloads may be cancelled.
    const requestedId=Number(id);
    const stored=neighbourCache.get(requestedId);
    const cached=stored?.profile===serverPreviewProfile?stored:null;
    const pendingPreload=cached?.status==='loading';
    const requestedProfile=serverPreviewProfile;
    const requestedUrl=imageUrl(id,requestedProfile);
    preloadDebug('Panorama angefordert',`${id} · ${cached?.status==='ready'?'CACHE-TREFFER (vorgeladen)':pendingPreload?'LAUFENDES VORLADEN ÜBERNEHMEN':'kein vorgeladenes Bild'}`);
    for(const [cachedId,entry] of neighbourCache){
      if(cachedId===requestedId && entry===cached)continue;
      entry.image.onload=null;entry.image.onerror=null;
      if(entry.status==='loading'){entry.image.src='';entry.settle?.(null);}
      disposePrepared(entry);
      if(entry.status==='ready')entry.image.src='';
      neighbourCache.delete(cachedId);
    }
    stopGyro();stopGyroHint();
    if(mobile()){shell.style.visibility='hidden';shell.style.pointerEvents='none';setGyroPanelOpen(false);}
    surface.hidden=false;
    setMessage('');
    // Keep the overlay mounted while the old WebGL renderer is torn down.
    // Let it visibly paint before potentially heavy texture setup.
    for(const previous of surface.querySelectorAll('.ppi-sphere-loading'))previous.remove();
    const veil=showLoader(id);
    preloadDebug('Ladeanzeige sichtbar',String(id));
    await new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(resolve)));
    if(token!==loaderToken){veil.remove();return;}
    sphere?.destroy();sphere=null;
    try {
      // Zoom settings can be fetched while the already-running image download
      // finishes. The loading veil remains visible throughout this wait.
      const zoomPromise=fetchTourZoomSettings();
      let reusedImage=null;
      if(pendingPreload){
        const waiting=performance.now();
        preloadDebug('Laufenden Download abwarten',String(id));
        reusedImage=await cached.promise;
        if(token!==loaderToken){veil.remove();return;}
        preloadDebug(reusedImage?'Laufender Download übernommen':'Vorladen fehlgeschlagen – direkter Abruf',`${id} · ${Math.round(performance.now()-waiting)} ms gewartet`);
      }else if(cached?.status==='ready' && cached.image.complete && cached.image.naturalWidth>0){
        reusedImage=cached.image;
      }
      // If background texture resizing was started, wait for that same work
      // rather than performing the expensive resize again in the foreground.
      if(reusedImage && cached?.preparePromise){
        preloadDebug('Vorbereitete Textur abwarten',String(id));
        await cached.preparePromise;
      }
      const zoomOptions = await zoomPromise;
      if (token !== loaderToken) {veil.remove();return;}
      const readyImage=reusedImage;
      const readyBitmap=readyImage && cached?.prepared ? cached.prepared : null;
      if(readyBitmap){cached.prepared=null;preloadDebug('Textur-Cache-Treffer',`${id} · vorbereitete Bitmap wird übernommen`);}
      if(readyImage)preloadDebug('Bild übernommen',`${id} · ${pendingPreload?'laufender Download':'vollständig vorgeladen'}`);
      if(cached){
        cached.image.onload=null;cached.image.onerror=null;
        neighbourCache.delete(requestedId);
      }
      sphere = makeTourSphere(surface, requestedUrl,
        () => tour.links.filter(l => Number(l.from) === current),
        target => showImage(target), () => {}, message => {
          if(token!==loaderToken)return;
          if(message.startsWith('360°-Panorama geladen.')){preloadDebug('Panorama angezeigt',`${id} · ${readyImage?(pendingPreload?'aus laufendem Vorladen':'aus vorgeladenem Bild'):'normaler Abruf'}`);setMessage('');finishLoader(veil,token);scheduleNeighbourPreload(Number(id));}
          else if(/fehler|nicht erreichbar|nicht geladen|konnte/i.test(message)){
            preloadDebug('Panorama-Fehler',String(id));veil?.remove();
            if(requestedProfile==='desktop' && downgradePanorama('8192er-Panorama konnte nicht dargestellt werden')){
              queueMicrotask(()=>{if(token===loaderToken)showImage(id);});
            }else setMessage(message);
          }
        },
        // Pins must use Nextcloud's small server-side cached previews, NOT the
        // full-resolution panorama image URL (which could be tens of MB).
        target => thumbnailUrl(target), target => tour.images.find(item=>Number(item.id)===Number(target))?.name || 'Panorama', true, zoomOptions, readyImage, updateTourWatermark, (stage,detail)=>{
          preloadDebug(stage, `${id} · ${detail}`);
          if(stage==='GPU-Upload')observeGpuUpload(detail);
        }, readyBitmap, requestedProfile==='mobile'?4096:8192);
      // The sphere preserves the active loading veil throughout construction.
      // No detach/reattach: that would reset the CSS animation.
      if (current===Number(display.startImageId||tour.images[0]?.id)) sphere.setView(radians(display.startYaw));
    } catch(error) {preloadDebug('Viewer-Fehler',String(error?.message||error));veil.remove();if(token===loaderToken)status.textContent='360°-Ansicht konnte nicht geöffnet werden: '+error.message;}
    for(const button of nav.querySelectorAll('button'))button.setAttribute('aria-current',button.dataset.id===String(id)?'true':'false');
  }
  async function start() {
    try {
      const response=await fetch(api,{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});
      if(!response.ok)throw new Error('Tour ist nicht verfügbar (HTTP '+response.status+').');
      const data=await response.json();if(!data.success||!data.tour)throw new Error('Tour konnte nicht geladen werden.');
      tour=data.tour;display=tour.display||{};byId('ppi-public-title').textContent=tour.name;
      applyTourWatermark(display);
      // Enabled in tour settings means available, not automatically expanded.
      setNavigationOpen(false);
      if(display.showTitle===false)byId('ppi-public-title').parentElement.hidden=true;
      if(display.showNavigation===false){nav.hidden=true;navToggle.hidden=true;mobileNav.hidden=true;}
      root.dataset.hotspotSize=['xsmall','small','medium','large','xlarge'].includes(display.hotspotSize)?display.hotspotSize:'medium';
      surface.style.setProperty('--ppi-svg-pin-color',/^#[0-9a-fA-F]{6}$/.test(display.svgPinColor||'')?display.svgPinColor:'#1687bc');
      surface.dataset.hotspotStyle=display.hotspotStyle==='chat-pin'?'map-pin':(['classic','blue','map-pin','label-pin','outline-pin'].includes(display.hotspotStyle)?display.hotspotStyle:'classic');
      if(mobile())byId('ppi-public-guide').hidden=true;
      byId('ppi-public-fullscreen').hidden=display.showFullscreen===false;
      mobileFullscreen.hidden=display.showFullscreen===false;
      nav.replaceChildren();
      for(const img of tour.images){
        const button=document.createElement('button');button.type='button';button.className='ppi-public-nav-card';button.dataset.id=String(img.id);
        button.title=img.name;button.setAttribute('aria-label','Panorama öffnen: '+img.name);
        const picture=document.createElement('img');picture.src=thumbnailUrl(img.id);picture.alt='';picture.loading='lazy';picture.decoding='async';
        picture.addEventListener('error',()=>{picture.hidden=true;button.classList.add('ppi-public-no-thumb');});
        const label=document.createElement('span');label.textContent=img.name.replace(/\.(jpe?g)$/i,'');
        button.append(picture,label);
        button.addEventListener('click',()=>showImage(img.id));nav.append(button);
      }
      if(!tour.images.length){viewer.hidden=false;status.textContent='In dieser Tour sind aktuell keine Bilder verfügbar.';return;}
      const startId=tour.images.some(img=>Number(img.id)===Number(display.startImageId))?display.startImageId:tour.images[0].id;
      // Keep the tour viewer inactive until the visitor explicitly starts it.
      // The cover image uses the same token-scoped endpoint as tour playback.
      const startImage=tour.images.find(img=>Number(img.id)===Number(startId))||tour.images[0];
      byId('ppi-public-landing-title').textContent=tour.name;
      byId('ppi-public-landing-info').textContent=`${tour.images.length} ${tour.images.length===1?'Panorama':'Panoramen'} entdecken`;
      const intro=byId('ppi-public-landing-description');
      intro.textContent=String(display.introText||'').trim();intro.hidden=!intro.textContent;
      // The landing cover must not download a full-size 360 JPEG in parallel.
      cover.src=thumbnailUrl(startImage.id) + (thumbnailUrl(startImage.id).includes('?') ? '&' : '?') + 'cover=1';
      cover.onerror=()=>{cover.hidden=true;};
      landing.hidden=false;
      viewer.hidden=true;
      status.textContent='';
      startButton.addEventListener('click',()=>{
        if(startButton.disabled)return;
        startButton.disabled=true;
        viewer.hidden=false;
        landing.hidden=true;
        showImage(startId);
        // Correct canvas bounds after transitioning from a hidden container.
        requestAnimationFrame(()=>requestAnimationFrame(()=>sphere?.refreshMarkers()));
      },{once:true});
    }catch(error){status.textContent=error.message;landing.hidden=true;viewer.hidden=false;}
  }
  async function toggleFullscreen(){
    try{if(document.fullscreenElement)await document.exitFullscreen?.();
      else await root.requestFullscreen?.();}
    catch(error){setMessage('Vollbildmodus wurde vom Browser nicht erlaubt.');}
  }
  byId('ppi-public-fullscreen').addEventListener('click',toggleFullscreen);
  // Browsers forbid fullscreen requests without a user gesture; the CSS layout
  // already fills the visual viewport, including mobile screens.
  document.addEventListener('fullscreenchange',()=>{sphere?.refreshMarkers();});
  window.addEventListener('pagehide',()=>{
    stopGyro();stopGyroHint();sphere?.destroy();clearTimeout(messageTimer);clearTimeout(preloadTimer);
    for(const entry of neighbourCache.values())disposePrepared(entry);neighbourCache.clear();
    for(const entry of neighbourCache.values()){
      entry.image.onload=null;entry.image.onerror=null;
      if(entry.status==='loading')entry.image.src='';
    }
    neighbourCache.clear();
    window.removeEventListener('keydown',onKeyDown,true);window.removeEventListener('keyup',onKeyUp,true);
    if(movementFrame)cancelAnimationFrame(movementFrame);
  },{once:true});
  start();
})();
