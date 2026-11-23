/* Photo Sphere Tour Editor – tour picker default-status build 0.4.62.96. */
(() => {
  'use strict';
  const root = document.getElementById('ppi-tours-app');
  if (!root) return;
  // Diagnostic marker: verifies that the browser loaded the matching script.
  root.dataset.tourEditorBuild = '0.4.62.96-tagsearch';
  const byId = id => document.getElementById(id);
  const api = root.dataset.apiUrl;
  const filesApi = root.dataset.filesUrl;
  const imageApi = root.dataset.imageUrl;
  const publicRoute = root.dataset.publicRoute || '';
  let tourWatermarks = [];
  async function loadTourWatermarks() {
    try {
      const result = await request(root.dataset.watermarkLibraryUrl);
      tourWatermarks = Array.isArray(result.images) ? result.images : [];
    } catch (error) { tourWatermarks = []; }
  } // populated by public sharing API response

  let tours = [], active = null, browsing = {id: 0, path: '/', parentId: 0};
  const selectedFiles = new Map();
  const previewUrl = (id, x = 320, y = 180) => {
    const route = window.OC?.generateUrl ? window.OC.generateUrl('/core/preview') : '/index.php/core/preview';
    return `${route}?fileId=${encodeURIComponent(id)}&x=${x}&y=${y}&a=true&forceIcon=false`;
  };
  function imageThumb(id, width=320, height=180) {
    const img = node('img'); img.loading = 'eager'; img.decoding = 'async';
    img.src = previewUrl(id,width,height);
    img.alt = 'Panorama-Vorschaubild'; img.className='ppi-pano-thumb';
    img.addEventListener('error', () => {img.style.display = 'none'; img.parentElement?.classList.add('ppi-no-thumb');});
    return img;
  }
  // Tour-specific, authenticated 360° editor. Never registered in Nextcloud Files/Photos.
  const previewDialog = byId('ppi-tour-preview');
  document.body.appendChild(previewDialog);
  let previewImageId = null, sphere = null, placeMode = false, pendingPoint = null;
  const sphereRoot = byId('ppi-preview-sphere');
  // Reuse the completed Files/Photos loading veil design in the tour editor.
  // The standalone tour sphere uses Image(), so download bytes are not observable:
  // show an indeterminate progress bar, never invented percentages.
  function showSphereLoader(id) {
    const veil = node('div', undefined, 'ppi-sphere-loading');
    veil.setAttribute('role', 'status');
    veil.setAttribute('aria-live', 'polite');
    const preview = node('div', undefined, 'ppi-sphere-loading-preview');
    preview.style.backgroundImage = `linear-gradient(rgba(0,0,0,.30),rgba(0,0,0,.42)),url("${previewUrl(id,960,540)}")`;
    const progressPreview = node('div', undefined, 'ppi-sphere-loading-progress-preview');
    progressPreview.style.backgroundImage = preview.style.backgroundImage;
    const content = node('div', undefined, 'ppi-sphere-loading-content');
    const spinner = node('div', undefined, 'ppi-sphere-loading-spinner');
    spinner.setAttribute('aria-hidden', 'true');
    const label = node('div', 'Panorama wird geladen …');
    const detail = node('div', '360°-Ansicht wird vorbereitet', 'ppi-sphere-loading-detail');
    const track = node('div', undefined, 'ppi-sphere-loading-track');
    track.append(node('div', undefined, 'ppi-sphere-loading-fill'));
    content.append(spinner, label, detail, track);
    veil.append(preview, progressPreview, content);
    sphereRoot.append(veil);
    return veil;
  }
  function finishSphereLoader(veil) {
    if (!veil?.isConnected) return;
    // Wait for the first WebGL frame before fading the overlay away.
    requestAnimationFrame(() => requestAnimationFrame(() => {
      if (!veil.isConnected) return;
      const preview = veil.querySelector('.ppi-sphere-loading-progress-preview');
      if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && preview) {
        preview.classList.add('ppi-sphere-loading-preview-complete');
        setTimeout(() => {
          if (veil.isConnected) veil.classList.add('ppi-sphere-loading-done');
        }, 560);
      } else veil.classList.add('ppi-sphere-loading-done');
      veil.addEventListener('transitionend', event => {
        if (event.target === veil && event.propertyName === 'opacity') veil.remove();
      });
      setTimeout(() => veil.remove(), 1250);
    }));
  }
  function clearPlacement() {
    placeMode = false; pendingPoint = null;
    byId('ppi-preview-placement').hidden = true;
    byId('ppi-preview-place').textContent = '+ Wechselpunkt im Bild setzen';
    sphere?.setPending(null);
    sphere?.setPlacementMode(false);
  }
  function closePreview() {
    hideTargetHoverPreview();
    clearPlacement(); sphere?.destroy(); sphere = null;
    previewDialog.hidden = true; previewDialog.style.display = 'none';
    byId('ppi-preview-image').removeAttribute('src'); previewImageId = null;
    sphereRoot.hidden = true; sphereRoot.style.display = 'none';
  }
  async function openSphereImage(id) {
    clearPlacement();
    sphere?.destroy(); sphere = null;
    previewImageId = id;
    previewDialog.querySelector('.ppi-preview-window')?.scrollTo({top:0});
    const img = active?.images?.find(x => Number(x.id) === id);
    if (!img) return;
    byId('ppi-preview-title').textContent = img.name;
    const status = byId('ppi-preview-status');
    status.textContent = '360°-Panorama wird geladen …';
    byId('ppi-preview-image').style.display = 'none';
    sphereRoot.hidden = false; sphereRoot.style.display = 'block';
    sphereRoot.style.setProperty('--ppi-svg-pin-color',/^#[0-9a-fA-F]{6}$/.test(active?.display?.svgPinColor||'')?active.display.svgPinColor:'#1687bc');
    sphereRoot.dataset.hotspotStyle=active?.display?.hotspotStyle==='chat-pin'?'map-pin':(['classic','blue','map-pin','label-pin','outline-pin'].includes(active?.display?.hotspotStyle)?active.display.hotspotStyle:'classic');
    const url = `${imageApi}?fileId=${encodeURIComponent(id)}`;
    // Preserve the loading overlay while WebGL is reconstructed. Allow it
    // to paint before potentially expensive image / texture work begins.
    sphereRoot.querySelectorAll('.ppi-sphere-loading').forEach(el=>el.remove());
    const loadingVeil = showSphereLoader(id);
    await new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(resolve)));
    if(previewImageId!==id||previewDialog.hidden){loadingVeil.remove();return;}
    try {
      const zoomOptions = await fetchTourZoomSettings();
      if (previewImageId !== id || previewDialog.hidden) {loadingVeil.remove();return;}
      sphere = makeTourSphere(sphereRoot, url, () => active.links.filter(l=>Number(l.from)===id),
        targetId => openSphereImage(targetId), point => {
          if (!placeMode) return;
          pendingPoint = point;
          sphere?.setPending(point);
          byId('ppi-preview-coordinates').textContent = `Horizontal ${Math.round(point.yaw*180/Math.PI)}° · Vertikal ${Math.round(point.pitch*180/Math.PI)}°`;
          byId('ppi-preview-placement').hidden = false;
        }, message => {
          if (previewImageId !== id || previewDialog.hidden) return;
          status.textContent = message;
          if (message.startsWith('360°-Panorama geladen.')) finishSphereLoader(loadingVeil);
          else if (/fehler|nicht erreichbar|nicht geladen|konnte/i.test(message)) loadingVeil?.remove();
        }, targetId => previewUrl(targetId, 280, 165),
        targetId => active.images.find(item => Number(item.id) === Number(targetId))?.name || 'Panorama', false, zoomOptions);
      // makeTourSphere keeps the loader attached; do not restart its animation.
    } catch (error) { status.textContent = '3D-Betrachter: ' + error.message; loadingVeil.remove(); }
    updateAvailableTargets();
  }
  // Test-only hover preview for the list of available panorama destinations.
  // Reuses Nextcloud's authenticated /core/preview endpoint, never the source JPEG.
  const targetHoverPreview = document.createElement('div');
  targetHoverPreview.className = 'ppi-target-hover-preview';
  targetHoverPreview.hidden = true;
  targetHoverPreview.setAttribute('aria-hidden', 'true');
  const targetHoverImage = document.createElement('img');
  targetHoverImage.alt = '';
  targetHoverImage.decoding = 'async';
  targetHoverPreview.append(targetHoverImage);
  document.body.append(targetHoverPreview);
  let hoverTargetCard = null;
  const hideTargetHoverPreview = () => {
    hoverTargetCard = null;
    targetHoverPreview.hidden = true;
    targetHoverImage.removeAttribute('src');
  };
  function positionTargetHoverPreview(card) {
    if (!card || targetHoverPreview.hidden || previewDialog.hidden) return;
    const rect = card.getBoundingClientRect();
    const margin = 12;
    const width = Math.min(320, window.innerWidth - 2 * margin);
    const height = width * 9 / 16;
    const left = Math.max(margin, Math.min(window.innerWidth - width - margin,
      rect.left + rect.width / 2 - width / 2));
    const above = rect.top - height - margin;
    const top = above >= margin ? above :
      Math.max(margin, Math.min(window.innerHeight - height - margin, rect.bottom + margin));
    targetHoverPreview.style.width = width + 'px';
    targetHoverPreview.style.left = left + 'px';
    targetHoverPreview.style.top = top + 'px';
  }
  function showTargetHoverPreview(card) {
    if (hoverTargetCard === card) return;
    const id = Number(card.dataset.imageId);
    if (!Number.isSafeInteger(id) || id <= 0 || previewDialog.hidden) return;
    hoverTargetCard = card;
    targetHoverImage.src = previewUrl(id, 640, 360);
    targetHoverPreview.hidden = false;
    positionTargetHoverPreview(card);
  }
  const targetGallery = byId('ppi-preview-target-gallery');
  targetGallery.addEventListener('pointerover', event => {
    if (event.pointerType !== 'mouse' && event.pointerType !== 'pen') return;
    const card = event.target.closest('.ppi-target-card');
    if (card && targetGallery.contains(card)) showTargetHoverPreview(card);
  });
  targetGallery.addEventListener('pointerout', event => {
    if (!hoverTargetCard || hoverTargetCard.contains(event.relatedTarget)) return;
    hideTargetHoverPreview();
  });
  targetGallery.addEventListener('scroll', hideTargetHoverPreview, {passive:true});
  previewDialog.querySelector('.ppi-preview-window')?.addEventListener('scroll', hideTargetHoverPreview, {passive:true});
  window.addEventListener('resize', hideTargetHoverPreview, {passive:true});
  // Show only destinations that are not already connected FROM the panorama being edited.
  // All links in active.links include unsaved draft changes, so the gallery updates instantly.
  function updateAvailableTargets() {
    const target = byId('ppi-preview-link-target');
    const gallery = byId('ppi-preview-target-gallery');
    if (!target || !gallery || !active || !previewImageId) return;
    const from = Number(previewImageId);
    const previousSelection = target.value;
    const connected = new Set(active.links
      .filter(link => Number(link.from) === from)
      .map(link => Number(link.to)));
    const available = active.images.filter(item =>
      Number(item.id) !== from && !connected.has(Number(item.id)));
    target.replaceChildren();
    hideTargetHoverPreview();
    gallery.replaceChildren();
    for (const item of available) {
      const opt = node('option', item.name); opt.value = String(item.id); target.append(opt);
      const card = node('button', undefined, 'ppi-target-card');
      card.type = 'button'; card.dataset.imageId = String(item.id);
      card.title = 'Als Ziel auswählen: ' + item.name;
      const thumb = imageThumb(item.id, 260, 145);
      thumb.loading = 'eager'; thumb.alt = 'Vorschau: ' + item.name;
      card.append(thumb, node('span', item.name));
      card.addEventListener('click', () => {
        target.value = String(item.id);
        updateTargetSelection();
        if (pendingPoint && placeMode) {
          byId('ppi-preview-link-add').click();
        } else if (!placeMode) {
          byId('ppi-preview-status').textContent = 'Zum Verknüpfen zuerst „Wechselpunkt im Bild setzen“ wählen.';
        }
      });
      gallery.append(card);
    }
    if (!available.length) {
      gallery.append(node('p', 'Alle anderen Panoramen sind bereits verbunden. Zum erneuten Verknüpfen zuerst eine Verbindung entfernen.', 'ppi-preview-no-targets'));
    }
    // Keep the selected destination whenever it is still available.
    if (available.some(item => String(item.id) === previousSelection)) target.value = previousSelection;
    const addButton = byId('ppi-preview-link-add');
    if (addButton) addButton.disabled = available.length === 0;
    updateTargetSelection();
  }
  function updateTargetSelection() {
    const targetId = byId('ppi-preview-link-target').value;
    byId('ppi-preview-target-gallery').querySelectorAll('.ppi-target-card').forEach(card => {
      card.setAttribute('aria-pressed', String(card.dataset.imageId === targetId));
    });
  }
  byId('ppi-preview-link-target').addEventListener('change', updateTargetSelection);
  function previewImage(image) {
    const id = Number(image.id);
    if (!Number.isInteger(id) || id <= 0) return;
    previewImageId = id;
    byId('ppi-preview-title').textContent = image.name;
    const included = Boolean(active?.images?.some(item => Number(item.id) === id));
    const controls = byId('ppi-preview-link-controls');
    controls.hidden = !included || active.images.length < 2;
    previewDialog.hidden = false; previewDialog.style.display = 'flex';
    previewDialog.querySelector('.ppi-preview-scroll')?.scrollTo({top:0});
    byId('ppi-preview-image').style.display = included ? 'none' : 'block';
    sphereRoot.hidden = !included; sphereRoot.style.display = included ? 'block' : 'none';
    if (included) {
      openSphereImage(id);
    } else {
      byId('ppi-preview-status').textContent = 'Panorama-Vorschau';
      const photo = byId('ppi-preview-image');
      photo.onerror = () => {byId('ppi-preview-status').textContent = 'Vorschau konnte nicht geladen werden.';};
      photo.src = previewUrl(id, 1280, 720);
    }
    byId('ppi-preview-close').focus({preventScroll: true});
  }
  byId('ppi-preview-close').addEventListener('click', closePreview);
  previewDialog.addEventListener('click', event => {if(event.target===previewDialog)closePreview();});
  // Wheel scrolling always reaches the editor controls, even above WebGL.
  // Ctrl + wheel is reserved for panorama zoom. Only this modal is affected.
  previewDialog.addEventListener('wheel', event => {
    if (event.ctrlKey || previewDialog.hidden) return;
    // Let native scrolling work inside the target gallery and settings.
    if (event.target.closest?.('.ppi-preview-target-gallery')) return;
    // The WebGL canvas owns pointer gestures, but not ordinary wheel scrolling.
    // Forward wheel events originating on the panorama to the dialog scroller.
    if (!event.target.closest?.('.ppi-preview-sphere')) return;
    const scroll=previewDialog.querySelector('.ppi-preview-window');
    if (!scroll) return;
    event.preventDefault();
    const multiplier=event.deltaMode===1 ? 18 : event.deltaMode===2 ? scroll.clientHeight : 1;
    scroll.scrollTop += event.deltaY*multiplier;
  }, {capture:true,passive:false});

  window.addEventListener('keydown', event => {
    if (event.key==='Escape' && !previewDialog.hidden) {closePreview();event.stopImmediatePropagation();}
  }, true);
  byId('ppi-preview-place').addEventListener('click',()=>{
    if (!sphere) return;
    placeMode = true; pendingPoint = null;
    sphere.setPlacementMode(true);
    sphere.setPending(null);
    byId('ppi-preview-placement').hidden = false;
    byId('ppi-preview-coordinates').textContent = 'Position anklicken und anschließend das Ziel-Vorschaubild auswählen.';
    byId('ppi-preview-status').textContent = 'Jetzt auf die gewünschte Stelle im Panorama klicken oder tippen.';
    byId('ppi-preview-place').textContent = 'Position im Bild anklicken …';
  });
  byId('ppi-preview-place-cancel').addEventListener('click', clearPlacement);
  byId('ppi-preview-link-add').addEventListener('click', () => {
    const from=previewImageId, to=Number(byId('ppi-preview-link-target').value);
    if(!active || !from || !to || from===to || !pendingPoint) return;
    if (active.links.some(l=>Number(l.from)===from && Number(l.to)===to)) {
      byId('ppi-preview-status').textContent='Zu diesem Ziel gibt es bereits eine Verbindung. Bitte zuerst entfernen.';
      return;
    }
    active.links.push({from,to,yaw:pendingPoint.yaw,pitch:pendingPoint.pitch});
    clearPlacement(); sphere?.refreshMarkers();render();
    notify('Wechselpunkt gesetzt. Bitte „Änderungen speichern“ drücken.');
    byId('ppi-preview-status').textContent='Wechselpunkt gespeichert (Entwurf).';
  });
  function updateSelection() {
    const count=selectedFiles.size;
    byId('ppi-picker-selection-count').textContent=`${count} ausgewählt`;
    byId('ppi-picker-add-selected').disabled = count === 0;
    byId('ppi-picker-add-selected').textContent = count ? `${count} Panorama${count===1?'':'en'} hinzufügen` : 'Panoramen hinzufügen';
  }
  function addSelected() {
    if (!active) return;
    let added=0;
    for (const item of selectedFiles.values()) {
      if (active.images.some(x=>Number(x.id)===Number(item.id))) continue;
      if (active.images.length >= 50) {notify('Maximal 50 Bilder pro Tour. Weitere Bilder wurden nicht hinzugefügt.',true);break;}
      active.images.push({id:Number(item.id),name:item.name,path:item.path});added++;
    }
    selectedFiles.clear();updateSelection();++browseSerial;
    show('editor');render();
    notify(`${added} Panorama${added===1?'':'en'} hinzugefügt. Zum Übernehmen bitte „Änderungen speichern“ drücken.`);
  }
  byId('ppi-picker-add-selected').addEventListener('click',addSelected);

  const clone = obj => JSON.parse(JSON.stringify(obj));
  function node(tag, label, className) {
    const el = document.createElement(tag);
    if (label !== undefined) el.textContent = label;
    if (className) el.className = className;
    return el;
  }
  function notify(message, error = false) {
    const el = byId('ppi-tour-message');
    el.hidden = false;
    el.textContent = message;
    el.classList.toggle('ppi-notice-error', error);
    el.classList.toggle('ppi-notice-success', !error);
  }
  async function request(url, method = 'GET', data = null) {
    const headers = {'Accept':'application/json'};
    const token = window.OC?.requestToken || document.querySelector('head[data-requesttoken]')?.dataset.requesttoken;
    if (token) headers.requesttoken = token;
    if (data !== null) headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
    const response = await fetch(url, {method, credentials:'same-origin', headers,
      body: data === null ? undefined : new URLSearchParams(data).toString()});
    let result;
    try { result = await response.json(); } catch { throw new Error(`Unerwartete Serverantwort (HTTP ${response.status})`); }
    if (!response.ok || !result.success) throw new Error(result.message || `HTTP ${response.status}`);
    return result;
  }
  function tourPublicUrl(tour) {
    if (!tour?.publicToken) return '';
    // Native Nextcloud route; OC.generateUrl includes subdirectory/index.php when needed.
    const path = `/apps/photosphere_photos_integration/tour/${encodeURIComponent(tour.publicToken)}`;
    const url = window.OC?.generateUrl ? window.OC.generateUrl(path) : `/index.php${path}`;
    return new URL(url, location.origin).href;
  }
  async function quickShare(tour, enabled) {
    if (enabled && !tour.images?.length) {notify('Bitte zuerst ein Panorama zur Tour hinzufügen.',true);return;}
    if (!confirm(enabled ? 'Öffentlichen Link erstellen? Jeder mit diesem Link kann alle Panoramen dieser Tour ansehen.'
      : 'Öffentliche Freigabe deaktivieren? Die Tour und ihre Bilder bleiben erhalten.')) return;
    try {
      await request(`${api}/${encodeURIComponent(tour.id)}/share`, 'POST', {enabled:enabled?'1':'0'});
      await refresh();
      notify(enabled?'Freigabe erstellt.':'Freigabe deaktiviert. Die Tour bleibt erhalten.');
    } catch(e) {notify('Freigabe fehlgeschlagen: '+e.message,true);}
  }
  async function copyPublicUrl(tour) {
    try {await navigator.clipboard.writeText(tourPublicUrl(tour)); notify('Freigabelink kopiert.');}
    catch {window.prompt('Freigabelink kopieren:', tourPublicUrl(tour));}
  }
  function tourAction(label, handler, primary=false) {
    const b=node('button',label);b.type='button';if(primary)b.classList.add('primary');
    b.addEventListener('click',handler);return b;
  }
  async function refresh() {
    const data = await request(api);
    tours = data.tours;
    const container = byId('ppi-tour-list');container.replaceChildren();
    // The overview adapts to the number of tours without changing their stored order.
    container.classList.toggle('ppi-tours-single', tours.length === 1);
    container.classList.toggle('ppi-tours-double', tours.length === 2);
    container.classList.toggle('ppi-tours-featured', tours.length >= 3);
    if (!tours.length) container.append(node('p','Noch keine Touren erstellt.'));
    for (const tour of tours) {
      const tile=node('article',undefined,'ppi-overview-tile');
      if (tour.images?.length) {
        const preview=imageThumb(tour.images[0].id,640,300);
        preview.className='ppi-overview-thumb';preview.alt='Tour-Vorschaubild: '+tour.name;
        tile.append(preview);
      } else tile.append(node('div','🌐','ppi-overview-placeholder'));
      const body=node('div',undefined,'ppi-overview-body');
      body.append(node('h3',tour.name,'ppi-overview-name'));
      body.append(node('div',`${(tour.publicToken||tour.shareLinks?.length)?'🌐 Öffentlich':'🔒 Privat'} · ${tour.images?.length||0} Panoramen · ${tour.links?.length||0} Verbindungen`,'ppi-overview-status'));
      const actions=node('div',undefined,'ppi-overview-actions');
      actions.append(tourAction('Bearbeiten',()=>openTour(tour,'edit'),true));
      actions.append(tourAction('Vorschau',()=>{
        if (tour.publicToken||tour.shareLinks?.length) window.open(tourPublicUrl({publicToken:tour.publicToken||tour.shareLinks[0].token}),'_blank','noopener,noreferrer');
        else {openTour(tour,'edit');notify('Private Tour: Öffne ein Panorama über die Vorschau-Kacheln.');}
      }));
      if (tour.publicToken) {
        actions.append(tourAction('Link kopieren',()=>copyPublicUrl(tour)));
        actions.append(tourAction('Hauptlink deaktivieren',()=>quickShare(tour,false)));
      } else actions.append(tourAction('Freigeben',()=>quickShare(tour,true)));
      actions.append(tourAction('Freigabe verwalten',()=>openTour(tour,'share')));
      body.append(actions);tile.append(body);container.append(tile);
    }
  }
  function show(view) {
    for (const [name, id] of [['overview', 'ppi-tour-overview'], ['editor', 'ppi-tour-editor'], ['picker', 'ppi-tour-picker']]) {
      const element = byId(id);
      // Some Nextcloud themes override the browser's [hidden] rendering.
      // Explicitly toggle display as well to keep only one section visible.
      element.hidden = name !== view;
      element.style.display = name === view ? 'block' : 'none';
    }
    byId('ppi-tour-create').hidden = view !== 'overview';
    if (view === 'picker') {
      const dialog = byId('ppi-tour-picker');
      // The Add button may be below the fold. Reveal the picker immediately.
      requestAnimationFrame(() => {
        if (!dialog.hidden) {
          dialog.scrollIntoView({block: 'start', behavior: 'instant'});
          // Do not focus the picker wrapper: this can steal focus from the search input.
        }
      });
    }
  }
  function setSetupStep(step) {
    const share=step==='share',design=step==='design';
    byId('ppi-step-share').hidden=!share;
    byId('ppi-step-design').hidden=!design;
    byId('ppi-step-edit').hidden=share||design;
    byId('ppi-tour-save').hidden=share||design;
    byId('ppi-design-save').hidden=!design;
    if(design)renderDesign();
    // The editor graph cannot be measured while its tab is hidden.
    // Draw it only after its container becomes visible again.
    if(!share && !design) requestAnimationFrame(() => {
      if(active && !byId('ppi-step-edit').hidden) renderGraph();
    });
    for(const tab of root.querySelectorAll('.ppi-setup-tab')) {
      if (tab.dataset.step===step) tab.setAttribute('aria-current','step');
      else tab.removeAttribute('aria-current');
    }
    if(share) updateShareUi();
  }
  const sizeSteps=['xsmall','small','medium','large','xlarge'];
  const sizeNames=['Extra klein','Klein','Mittel','Groß','Extra groß'];
  function refreshDesignPreviews(){
    if(!active)return;
    const id=Number(byId('ppi-design-start').value);
    const img=byId('ppi-design-start-preview');
    if(id && active.images.some(p=>Number(p.id)===id)){
      img.hidden=false;img.src=previewUrl(id,460,260);
    }else{img.removeAttribute('src');img.hidden=true;}
    const blueImg=byId('ppi-design-style-preview');
    if(!blueImg.getAttribute('src'))blueImg.src=window.OC?.filePath ? window.OC.filePath('photosphere_photos_integration','img','hotspot-guide-blue.png') : '/custom_apps/photosphere_photos_integration/img/hotspot-guide-blue.png';
    const style=byId('ppi-design-style').value;
    const blue=style==='blue', classic=style==='classic';
    blueImg.hidden=!blue;
    blueImg.style.display=blue?'block':'none';
    const classicPreview=byId('ppi-design-classic-preview');
    classicPreview.hidden=!classic;
    classicPreview.style.display=classic?'block':'none';
    const pinPreview=byId('ppi-design-pin-preview');
    const isPin=style==='map-pin'||style==='label-pin'||style==='outline-pin';
    const pinColor=byId('ppi-design-pin-color').value;
    // The color card remains visible in the top row; only SVG pin styles use its value.
    root.style.setProperty('--ppi-svg-pin-color',pinColor);
    byId('ppi-design-pin-color-hex').textContent=pinColor.toUpperCase();
    byId('ppi-design-color-chip').style.backgroundColor=pinColor;
    pinPreview.hidden=!isPin;
    pinPreview.style.display=isPin?'block':'none';
    if(isPin){
      const file=style==='label-pin'?'hotspot-label-pin.svg':style==='outline-pin'?'hotspot-outline-pin.svg':'hotspot-map-pin.svg';
      const path=window.OC?.filePath ? window.OC.filePath('photosphere_photos_integration','img',file) : '/custom_apps/photosphere_photos_integration/img/'+file;
      if(pinPreview.dataset.maskUrl!==path){pinPreview.dataset.maskUrl=path;pinPreview.style.webkitMaskImage=`url("${path}")`;pinPreview.style.maskImage=`url("${path}")`;}
    }
    const n=Math.max(0,Math.min(4,Number(byId('ppi-design-size').value)||0));
    byId('ppi-design-size-label').textContent=sizeNames[n];
    root.querySelector('.ppi-design-hotspot-preview')?.style.setProperty('--ppi-mini-scale',String([.72,.8,1,1.2,1.28][n]));
  }
  const designDefault = {watermarkEnabled:false,watermarkId:'none',startImageId:0,startYaw:0,showTitle:true,showNavigation:true,showFullscreen:true,hotspotSize:'medium',hotspotStyle:'classic',introText:'',socialPreviewMode:'cover',svgPinColor:'#1687bc'};
  function currentDesign(){return {...designDefault,...(active?.display||{})};}
  function updateSocialPreviewStatus(){
    const indicator=byId('ppi-design-social-status');
    if(!indicator)return;
    const mode=byId('ppi-design-social').value;
    if(mode==='off'){
      indicator.textContent='Keine zusätzlichen Linkvorschau-Daten aktiviert.';
    }else if(active?.sharePasswordProtected){
      indicator.textContent='Passwortschutz aktiv: neutrales 360°-Motiv und allgemeiner Beschreibungstext.';
    }else if(mode==='neutral'){
      indicator.textContent='Neutrales 360°-Motiv; Tourtitel und Tourbeschreibung bleiben sichtbar.';
    }else{
      indicator.textContent='Ohne Passwort: Startpanorama, Tourname und Beschreibung werden automatisch verwendet.';
    }
  }
  function renderDesign(){
    const d=currentDesign();const select=byId('ppi-design-start');select.replaceChildren();
    for(const img of active.images){const opt=node('option',img.name);opt.value=String(img.id);select.append(opt);}
    select.value=String(active.images.some(img=>Number(img.id)===Number(d.startImageId))?d.startImageId:(active.images[0]?.id||''));
    byId('ppi-tour-name').value=String(active.name||'');
    byId('ppi-design-yaw').value=String(d.startYaw);byId('ppi-design-yaw-value').textContent=d.startYaw+'°';
    byId('ppi-design-intro').value=String(d.introText||'');
    byId('ppi-design-social').value=['cover','neutral','off'].includes(d.socialPreviewMode)?d.socialPreviewMode:'cover';
    byId('ppi-design-size').value=String(Math.max(0,sizeSteps.indexOf(d.hotspotSize)));
    byId('ppi-design-pin-color').value=/^#[0-9a-fA-F]{6}$/.test(d.svgPinColor||'')?d.svgPinColor:'#1687bc';
    byId('ppi-design-style').value=d.hotspotStyle==='chat-pin'?'map-pin':(['classic','blue','map-pin','label-pin','outline-pin'].includes(d.hotspotStyle)?d.hotspotStyle:'classic');
    byId('ppi-design-title').checked=Boolean(d.showTitle);
    byId('ppi-design-nav').checked=Boolean(d.showNavigation);
    byId('ppi-design-fullscreen').checked=Boolean(d.showFullscreen);
    const wm = byId('ppi-design-watermark-id'); wm.replaceChildren();
    wm.append(new Option('Wasserzeichen auswählen','none'));
    for (const entry of tourWatermarks) wm.append(new Option(entry.label,entry.id));
    const exists = tourWatermarks.some(item=>item.id===d.watermarkId);
    if (d.watermarkId && d.watermarkId!=='none' && !exists) wm.append(new Option('Bisheriges Wasserzeichen – nicht mehr freigegeben',d.watermarkId));
    wm.value=d.watermarkId||'none';
    byId('ppi-design-watermark-enabled').checked=Boolean(d.watermarkEnabled);
    byId('ppi-design-watermark-choice').hidden=!d.watermarkEnabled;
    byId('ppi-design-watermark-info').textContent=tourWatermarks.length?'Nur vom Administrator freigegebene Wasserzeichen sind auswählbar.':'Aktuell wurden keine Wasserzeichen für Touren freigegeben.';
    refreshDesignPreviews();
    refreshWatermarkThumbnail();
    updateSocialPreviewStatus();
  }
  function refreshWatermarkThumbnail(){
    const enabled=byId('ppi-design-watermark-enabled').checked;
    const id=byId('ppi-design-watermark-id').value;
    const approved=enabled && tourWatermarks.some(entry=>entry.id===id);
    const img=byId('ppi-design-watermark-preview');
    const placeholder=byId('ppi-design-watermark-placeholder');
    img.hidden=!approved;
    placeholder.hidden=approved;
    if(approved){
      const endpoint=root.dataset.watermarkThumbnailUrl;
      const url=endpoint + (endpoint.includes('?')?'&':'?')+'id='+encodeURIComponent(id);
      if(img.getAttribute('src')!==url)img.src=url;
    } else {img.removeAttribute('src');}
    placeholder.textContent=!enabled?'Deaktiviert':id==='none'?'Kein Wasserzeichen':'Nicht freigegeben';
  }
  byId('ppi-design-watermark-enabled').addEventListener('change',()=>{
    byId('ppi-design-watermark-choice').hidden=!byId('ppi-design-watermark-enabled').checked;
    refreshWatermarkThumbnail();
  });
  byId('ppi-design-watermark-id').addEventListener('change',refreshWatermarkThumbnail);
  byId('ppi-design-social').addEventListener('change',updateSocialPreviewStatus);
  byId('ppi-design-pin-color').addEventListener('input',refreshDesignPreviews);
  root.querySelectorAll('[data-pin-color]').forEach(button=>button.addEventListener('click',()=>{byId('ppi-design-pin-color').value=button.dataset.pinColor;refreshDesignPreviews();}));
  ['ppi-design-start','ppi-design-style','ppi-design-size'].forEach(id=>byId(id).addEventListener(id==='ppi-design-size'?'input':'change',refreshDesignPreviews));
  byId('ppi-design-yaw').addEventListener('input',()=>{byId('ppi-design-yaw-value').textContent=byId('ppi-design-yaw').value+'°';});
  // Lightweight description formatting: store only text, not user HTML.
  const descriptionField=byId('ppi-design-intro');
  const insertDescriptionText=(replacement)=>{
    const start=descriptionField.selectionStart,end=descriptionField.selectionEnd;
    if(descriptionField.value.length-(end-start)+replacement.length>descriptionField.maxLength){
      notify('Beschreibung: maximal 300 Zeichen.',true);return false;
    }
    descriptionField.setRangeText(replacement,start,end,'end');
    descriptionField.focus({preventScroll:true});
    descriptionField.dispatchEvent(new Event('input',{bubbles:true}));
    return true;
  };
  root.querySelectorAll('[data-intro-insert]').forEach(button=>button.addEventListener('click',()=>{
    const start=descriptionField.selectionStart,end=descriptionField.selectionEnd;
    const selected=descriptionField.value.slice(start,end);
    const action=button.dataset.introInsert;
    let insertion='';
    if(action==='bullet'||action==='number'){
      const lines=(selected||'Listenpunkt').split('\n');
      insertion=lines.map((line,i)=>(action==='bullet'?'- ':(i+1)+'. ')+line).join('\n');
      if(start>0&&descriptionField.value[start-1]!=='\n')insertion='\n'+insertion;
    }else if(action==='link'){
      const address=window.prompt('HTTPS- oder HTTP-Link eingeben:','https://');
      if(address===null)return;
      let url;
      try{url=new URL(address);if(!['http:','https:'].includes(url.protocol))throw Error('scheme');}
      catch(_e){notify('Bitte eine gültige HTTPS- oder HTTP-Adresse eingeben.',true);return;}
      insertion='['+(selected||'Linktext').replace(/[\[\]\r\n]/g,'')+']('+url.href+')';
    }
    if(insertion)insertDescriptionText(insertion);
  }));
  // Markdown list editing in the plain-text intro textarea.
  // Keep the stored description as text; the public renderer handles safe markup.
  descriptionField.addEventListener('keydown',event=>{
    if(event.defaultPrevented||event.isComposing||event.keyCode===229||
       (event.key!=='Enter'&&event.key!=='Backspace')||
       event.ctrlKey||event.metaKey||event.altKey||
       (event.key==='Enter'&&event.shiftKey))return;
    const start=descriptionField.selectionStart;
    if(start!==descriptionField.selectionEnd)return;
    const value=descriptionField.value;
    const lineStart=value.lastIndexOf('\n',start-1)+1;
    const lineEnd=value.indexOf('\n',start);
    const end=lineEnd<0?value.length:lineEnd;
    const before=value.slice(lineStart,start);
    const after=value.slice(start,end);
    const match=/^([ \t]*)([-*]|(\d+)[.)])([ \t]+)(.*)$/.exec(value.slice(lineStart,end));
    if(!match)return;
    const prefix=match[1]+match[2]+match[4];
    const empty=match[5].trim()==='';
    if(event.key==='Backspace'){
      // Empty list marker -> plain paragraph, preserving the line.
      if(empty&&start===lineStart+prefix.length){
        event.preventDefault();
        descriptionField.setRangeText('',lineStart,lineStart+prefix.length,'start');
        descriptionField.dispatchEvent(new Event('input',{bubbles:true}));
      }
      return;
    }
    if(start<lineStart+prefix.length)return;
    if(empty){
      // Enter on an empty list item leaves the list instead of adding another marker.
      event.preventDefault();
      descriptionField.setRangeText('',lineStart,lineStart+prefix.length,'start');
      descriptionField.dispatchEvent(new Event('input',{bubbles:true}));
      return;
    }
    const nextMarker=match[3]!==undefined
      ?String(Number(match[3])+1)+match[2].slice(-1)
      :match[2];
    const insertion='\n'+match[1]+nextMarker+match[4];
    if(value.length+insertion.length>descriptionField.maxLength){
      event.preventDefault();
      notify('Beschreibung: maximal 300 Zeichen.',true);
      return;
    }
    event.preventDefault();
    descriptionField.setRangeText(insertion,start,start,'end');
    descriptionField.dispatchEvent(new Event('input',{bubbles:true}));
  });
  const iconToggle=byId('ppi-intro-icons-toggle'),iconPanel=byId('ppi-intro-icons');
  iconToggle.addEventListener('click',()=>{
    iconPanel.hidden=!iconPanel.hidden;
    iconToggle.setAttribute('aria-expanded',String(!iconPanel.hidden));
  });
  root.querySelectorAll('[data-intro-icon]').forEach(button=>button.addEventListener('click',()=>{
    if(insertDescriptionText(button.dataset.introIcon)){
      iconPanel.hidden=true;iconToggle.setAttribute('aria-expanded','false');
    }
  }));

  byId('ppi-design-save').addEventListener('click',async()=>{
    if(!active)return;
    try{
      if(descriptionField.value.trim().length>300){notify('Beschreibung: maximal 300 Zeichen. Bitte vorhandenen Text kürzen.',true);descriptionField.focus();return;}
      if(!byId('ppi-tour-name').value.trim()){notify('Bitte einen Namen für die Tour eingeben.',true);byId('ppi-tour-name').focus();return;}
      const display={startImageId:Number(byId('ppi-design-start').value)||0,startYaw:Number(byId('ppi-design-yaw').value),
        introText:byId('ppi-design-intro').value.trim(),socialPreviewMode:byId('ppi-design-social').value,hotspotSize:sizeSteps[Number(byId('ppi-design-size').value)]||'medium',hotspotStyle:byId('ppi-design-style').value,svgPinColor:byId('ppi-design-pin-color').value,showTitle:byId('ppi-design-title').checked,
        showNavigation:byId('ppi-design-nav').checked,showFullscreen:byId('ppi-design-fullscreen').checked,
        watermarkEnabled:byId('ppi-design-watermark-enabled').checked,watermarkId:byId('ppi-design-watermark-id').value};
      const result=await request(`${api}/${encodeURIComponent(active.id)}`,'POST',{
        name:byId('ppi-tour-name').value,images:JSON.stringify(active.images),links:JSON.stringify(active.links),display:JSON.stringify(display)});
      active=clone(result.tour);await refresh();render();notify('Darstellung erfolgreich gespeichert.');
    }catch(e){notify('Darstellung konnte nicht gespeichert werden: '+e.message,true);}
  });
  root.querySelectorAll('.ppi-setup-tab:not(:disabled)').forEach(tab=>tab.addEventListener('click',()=>setSetupStep(tab.dataset.step)));
  async function openTour(tour,step='edit') { active=clone(tour); await loadTourWatermarks(); show('editor'); render(); setSetupStep(step); }
  function renderExtraShareLinks() {
    const list=byId('ppi-extra-list');
    list.replaceChildren();
    const links=Array.isArray(active?.shareLinks)?active.shareLinks:[];
    if(!links.length){list.append(node('p','Noch keine zusätzlichen Freigabelinks.'));return;}
    for(const link of links){
      const card=node('article',undefined,'ppi-extra-entry');
      const header=node('strong',link.name);
      const state=node('span',link.expiresOn&&link.expiresOn<new Date().toISOString().slice(0,10)?' · Abgelaufen':(link.disabled?' · Deaktiviert':(link.passwordProtected?' · Mit Passwort':' · Ohne Passwort')));
      header.append(state);card.append(header);
      const url=tourPublicUrl({publicToken:link.token});
      const linkRow=node('div',undefined,'ppi-extra-link');
      const urlInput=node('input');urlInput.type='text';urlInput.readOnly=true;urlInput.value=url;urlInput.setAttribute('aria-label','Freigabelink '+link.name);
      const copy=tourAction('Kopieren',async()=>{try{await navigator.clipboard.writeText(url);notify('Freigabelink kopiert.');}catch{urlInput.select();notify('Link markieren und kopieren.');}});
      const open=tourAction('Öffnen',()=>window.open(url,'_blank','noopener,noreferrer'));
      linkRow.append(urlInput,copy,open);card.append(linkRow);
      const fields=node('div',undefined,'ppi-extra-fields');
      const nameLabel=node('label','Bezeichnung');const name=node('input');name.value=link.name;name.maxLength=60;nameLabel.append(name);
      const dateLabel=node('label','Ablaufdatum');const date=node('input');date.type='date';date.value=link.expiresOn||'';dateLabel.append(date);
      const save=tourAction('Änderungen speichern',()=>extraAction(link.id,'settings',{name:name.value,expiresOn:date.value}));
      fields.append(nameLabel,dateLabel,save);card.append(fields);
      const passwordFields=node('div',undefined,'ppi-extra-fields');
      const passLabel=node('label','Eigenes Passwort');const pass=node('input');pass.type='password';pass.maxLength=128;pass.autocomplete='new-password';pass.placeholder='Neues Passwort';passLabel.append(pass);
      passwordFields.append(passLabel,tourAction(link.passwordProtected?'Passwort ändern':'Passwort setzen',async()=>{
        if(!pass.value){notify('Bitte Passwort eingeben.',true);return;}
        await extraAction(link.id,'password',{password:pass.value});pass.value='';
      }));
      if(link.passwordProtected){passwordFields.append(tourAction('Passwort entfernen',()=>{
        if(confirm('Passwortschutz für diesen Link entfernen?'))return extraAction(link.id,'removePassword');
      }));}
      card.append(passwordFields);
      card.append(tourAction(link.disabled?'Link aktivieren':'Link deaktivieren',()=>{
        if(link.disabled || confirm('Diesen Link vorübergehend deaktivieren?'))return extraAction(link.id,link.disabled?'enable':'disable');
      }));
      card.append(tourAction('Diesen Link widerrufen',()=>{
        if(confirm('Diesen Freigabelink endgültig widerrufen? Andere Links bleiben gültig.'))return extraAction(link.id,'revoke');
      }));
      list.append(card);
    }
  }
  async function extraAction(linkId,action,fields={}){
    try{
      const response=await request(`${api}/${encodeURIComponent(active.id)}/share/links/${encodeURIComponent(linkId)}`,'POST',{action,...fields});
      active=clone(response.tour);updateShareUi();await refresh();notify(action==='revoke'?'Freigabelink widerrufen.':'Freigabelink aktualisiert.');
    }catch(e){notify('Freigabelink konnte nicht geändert werden: '+e.message,true);}
  }
  byId('ppi-extra-create').addEventListener('click',async()=>{
    if(!active?.images?.length){notify('Bitte zuerst mindestens ein Panorama speichern.',true);return;}
    const name=byId('ppi-extra-name').value.trim();
    const expiresOn=byId('ppi-extra-expiry').value;
    const password=byId('ppi-extra-password').value;
    if(!name){notify('Bitte einen Namen für den zusätzlichen Link eingeben.',true);return;}
    if(!confirm('Neuen öffentlichen Link anlegen? Jeder mit diesem Link kann die gespeicherten Panoramen der Tour ansehen.'))return;
    try{
      const response=await request(`${api}/${encodeURIComponent(active.id)}/share/links`,'POST',{name,expiresOn,password});
      active=clone(response.tour);
      byId('ppi-extra-name').value='';byId('ppi-extra-expiry').value='';byId('ppi-extra-password').value='';
      updateShareUi();await refresh();notify('Zusätzlicher Freigabelink erstellt.');
    }catch(e){notify('Freigabelink konnte nicht erstellt werden: '+e.message,true);}
  });
  function updateShareUi() {
    renderExtraShareLinks();
    updateSocialPreviewStatus();
    const token = active?.publicToken || null;
    byId('ppi-share-state').textContent = token ? (active?.shareExpiresOn && active.shareExpiresOn < new Date().toISOString().slice(0,10) ? 'Abgelaufen' : 'Freigegeben') : 'Nicht freigegeben';
    byId('ppi-share-enable').hidden = Boolean(token);
    byId('ppi-share-disable').hidden = !token;
    byId('ppi-share-link-wrap').hidden = !token;
    byId('ppi-share-expiry-save').hidden = !token;
    const protectedShare = Boolean(active?.sharePasswordProtected);
    byId('ppi-share-password-state').textContent = protectedShare ? 'Passwortschutz aktiv' : 'Kein Passwort eingerichtet';
    byId('ppi-share-password-save').disabled = !token;
    byId('ppi-share-password-remove').hidden = !token || !protectedShare;
    byId('ppi-share-custom-save').disabled = !token;
    byId('ppi-share-custom-token').disabled = !token;
    byId('ppi-share-custom-token').value = token || '';
    byId('ppi-share-expiry').value = active?.shareExpiresOn || '';
    byId('ppi-share-expiry').min = `${new Date().getFullYear()}-${String(new Date().getMonth()+1).padStart(2,'0')}-${String(new Date().getDate()).padStart(2,'0')}`;
    if (token) {
      byId('ppi-share-link').value = tourPublicUrl({publicToken:token});
    } else byId('ppi-share-link').value = '';
  }
  byId('ppi-share-enable').addEventListener('click', async () => {
    if (!active || !active.images.length) {notify('Bitte zuerst Panoramen hinzufügen und die Tour speichern.',true);return;}
    if (!confirm('Ein öffentlicher Link erlaubt jedem mit diesem Link Zugriff auf alle Bilder dieser Tour. Fortfahren?')) return;
    try {
      // Save current editor state first; only validated images can be published.
      const saved = await request(`${api}/${encodeURIComponent(active.id)}`,'POST',{
        name:byId('ppi-tour-name').value,images:JSON.stringify(active.images),links:JSON.stringify(active.links)});
      active=clone(saved.tour);
      const data=await request(`${api}/${encodeURIComponent(active.id)}/share`,'POST',
        {enabled:'1',expiresOn:byId('ppi-share-expiry').value});
      active=clone(data.tour);updateShareUi();await refresh();
      if (data.url) byId('ppi-share-link').value=data.url;
      notify('Öffentlicher Testlink erstellt. Jeder mit dem Link kann die Bilder dieser Tour ansehen.');
    } catch(e){notify('Freigabe fehlgeschlagen: '+e.message,true);}
  });
  byId('ppi-share-disable').addEventListener('click', async () => {
    if (!active || !confirm('Öffentlichen Link wirklich deaktivieren?'))return;
    try {
      const data=await request(`${api}/${encodeURIComponent(active.id)}/share`,'POST',{enabled:'0'});
      active=clone(data.tour);updateShareUi();await refresh();notify('Freigabe deaktiviert. Der bisherige Link ist ungültig.');
    }catch(e){notify('Freigabe konnte nicht deaktiviert werden: '+e.message,true);}
  });
  byId('ppi-share-expiry-save').addEventListener('click',async()=>{
    if(!active?.publicToken)return;
    try{
      const data=await request(`${api}/${encodeURIComponent(active.id)}/share`,'POST',
        {enabled:'1',expiresOn:byId('ppi-share-expiry').value});
      active=clone(data.tour);updateShareUi();await refresh();notify('Ablaufdatum gespeichert.');
    }catch(e){notify('Ablaufdatum konnte nicht gespeichert werden: '+e.message,true);}
  });
  byId('ppi-share-rotate').addEventListener('click',async()=>{
    if(!active?.publicToken || !confirm('Token wirklich erneuern? Der bisherige Link und alle alten Bild-URLs werden sofort ungültig.'))return;
    try{
      const data=await request(`${api}/${encodeURIComponent(active.id)}/share`,'POST',
        {enabled:'1',rotate:'1',expiresOn:byId('ppi-share-expiry').value});
      active=clone(data.tour);updateShareUi();await refresh();notify('Neuer Link erstellt. Alter Token widerrufen.');
    }catch(e){notify('Token konnte nicht erneuert werden: '+e.message,true);}
  });
  byId('ppi-share-custom-save').addEventListener('click',async()=>{
    if(!active?.publicToken)return;
    const wanted=byId('ppi-share-custom-token').value.trim().toLowerCase();
    if(!/^[a-z0-9][a-z0-9-]{4,62}[a-z0-9]$/.test(wanted)){
      notify('Token: 6 bis 64 Zeichen, nur Kleinbuchstaben, Zahlen und Bindestriche.',true);return;
    }
    if(wanted===active.publicToken){notify('Dieser Token ist bereits aktiv.');return;}
    if(!confirm('Wunsch-Token übernehmen? Der bisherige Link wird sofort ungültig. Leicht zu erratende Tokens sollten mit Passwort geschützt werden.'))return;
    try{
      const data=await request(`${api}/${encodeURIComponent(active.id)}/share`,'POST',
        {enabled:'1',customToken:wanted,expiresOn:byId('ppi-share-expiry').value});
      active=clone(data.tour);updateShareUi();await refresh();notify('Wunsch-Token übernommen. Der alte Link ist ungültig.');
    }catch(e){notify('Wunsch-Token konnte nicht gespeichert werden: '+e.message,true);}
  });
  byId('ppi-share-password-save').addEventListener('click',async()=>{
    if(!active?.publicToken)return;
    const field=byId('ppi-share-password');
    if(!field.value || field.value.length>128){notify('Bitte ein Passwort mit höchstens 128 Zeichen eingeben.',true);return;}
    try{
      const data=await request(`${api}/${encodeURIComponent(active.id)}/share/password`,'POST',
        {action:'set',password:field.value});
      field.value='';active=clone(data.tour);updateShareUi();await refresh();
      notify('Passwortschutz gespeichert. Bereits entsperrte Besucher müssen sich neu anmelden.');
    }catch(e){notify('Passwort konnte nicht gespeichert werden: '+e.message,true);}
  });
  byId('ppi-share-password-remove').addEventListener('click',async()=>{
    if(!active?.publicToken || !confirm('Passwortschutz wirklich entfernen? Jeder mit dem Link kann die Tour dann ohne Passwort öffnen.'))return;
    try{
      const data=await request(`${api}/${encodeURIComponent(active.id)}/share/password`,'POST',{action:'remove'});
      byId('ppi-share-password').value='';active=clone(data.tour);updateShareUi();await refresh();
      notify('Passwortschutz entfernt. Der Link ist jetzt ohne Passwort erreichbar.');
    }catch(e){notify('Passwortschutz konnte nicht entfernt werden: '+e.message,true);}
  });
  byId('ppi-share-copy').addEventListener('click',async()=>{
    const url=byId('ppi-share-link').value;
    try { await navigator.clipboard.writeText(url);notify('Tour-Link kopiert.'); }
    catch {byId('ppi-share-link').select();notify('Link markieren und mit Strg+C kopieren.');}
  });
  byId('ppi-share-open').addEventListener('click',()=>{
    const url=byId('ppi-share-link').value;if(url)window.open(url,'_blank','noopener,noreferrer');
  });

  function selectOptions(select, images) {
    select.replaceChildren();
    for (const image of images) {const option=node('option',image.name);option.value=String(image.id);select.append(option);}
  }
  // On narrow displays use a readable touch list instead of a horizontally scrolling SVG.
  // Both renderers use the same active.images and active.links data model.
  function renderMobileGraph(container){
    if(!active.images.length){const empty=node('p','Noch keine Panoramen: Tippe auf „Panorama hinzufügen“.');empty.style.padding='14px';container.append(empty);return;}
    const list=node('div',undefined,'ppi-mobile-graph');
    const names=new Map(active.images.map(i=>[Number(i.id),i.name]));
    active.images.forEach(img=>{
      const card=node('div',undefined,'ppi-mobile-pano');
      const photo=node('button',undefined,'ppi-mobile-pano-photo');photo.type='button';photo.title=`Panorama öffnen: ${img.name}`;
      photo.append(imageThumb(img.id,420,240));photo.addEventListener('click',()=>previewImage(img));
      const title=node('div',img.name,'ppi-mobile-pano-title');
      const linksBox=node('div',undefined,'ppi-mobile-pano-links');
      const outgoing=active.links.filter(l=>Number(l.from)===Number(img.id));
      if(!outgoing.length)linksBox.append(node('span','Noch keine Verbindungen von diesem Panorama.'));
      for(const link of outgoing){
        const line=node('div',undefined,'ppi-mobile-link');
        line.append(node('span','→ '+(names.get(Number(link.to))||'Panorama')));
        const del=node('button','×');del.type='button';del.title='Verbindung entfernen';
        del.setAttribute('aria-label',`Verbindung von ${img.name} zu ${names.get(Number(link.to))||'Panorama'} entfernen`);
        del.addEventListener('click',()=>{
          if(!confirm('Diese Verbindung wirklich entfernen?'))return;
          active.links=active.links.filter(item=>item!==link);
          render();notify('Verbindung entfernt (Entwurf). Bitte Änderungen speichern.');
        });
        line.append(del);linksBox.append(line);
      }
      const actions=node('div',undefined,'ppi-mobile-pano-actions');
      const open=node('button','360° öffnen');open.type='button';open.addEventListener('click',()=>previewImage(img));
      const remove=node('button','Entfernen');remove.type='button';remove.addEventListener('click',()=>{
        if(!confirm(`„${img.name}“ aus dieser Tour entfernen? Zugehörige Verbindungen werden ebenfalls gelöscht. Die Originaldatei bleibt erhalten.`))return;
        active.images=active.images.filter(x=>Number(x.id)!==Number(img.id));
        active.links=active.links.filter(x=>Number(x.from)!==Number(img.id)&&Number(x.to)!==Number(img.id));
        render();notify('Panorama entfernt (Entwurf). Bitte Änderungen speichern.');
      });
      actions.append(open,remove);card.append(photo,title,linksBox,actions);list.append(card);
    });
    container.append(list);
  }
  // 0.4.44: Tour overview consists of interactive preview cards and editable arrows.
  function renderGraph() {
    const container=byId('ppi-tour-graph'); container.replaceChildren();
    if(window.matchMedia('(max-width:700px)').matches){renderMobileGraph(container);return;}
    if (!active.images.length) {
      const empty=node('p','Noch keine Panoramen: Klicke auf „Panorama hinzufügen“.');
      empty.style.padding='32px'; container.append(empty); return;
    }
    const count=active.images.length;
    // Keep cards, connecting paths and delete buttons in one shared pixel coordinate system.
    const w=Math.max(540,Math.round(container.clientWidth-16));
    const cols=count<=4?2:Math.min(3,Math.ceil(Math.sqrt(count)));
    const rows=Math.ceil(count/cols);
    const h=count===3?420:count<=2?250:rows*210+35;
    const board=node('div',undefined,'ppi-tour-board');
    board.style.width=w+'px';board.style.height=h+'px';
    const ns='http://www.w3.org/2000/svg';
    const el=(tag,attrs={})=>{
      const obj=document.createElementNS(ns,tag);
      for(const [key,value] of Object.entries(attrs))obj.setAttribute(key,String(value));
      return obj;
    };
    const svg=el('svg',{viewBox:`0 0 ${w} ${h}`,width:w,height:h,'aria-hidden':'true',class:'ppi-tour-arrows'});
    const defs=el('defs');
    const marker=el('marker',{id:'ppi-graph-arrow-046',viewBox:'0 0 16 16',refX:13,refY:8,markerWidth:13,markerHeight:13,orient:'auto',markerUnits:'userSpaceOnUse'});
    marker.append(el('path',{d:'M 1 1 L 14 8 L 1 15 Z',fill:'#147eae'}));defs.append(marker);svg.append(defs);
    const positions=new Map();
    active.images.forEach((img,i)=>{
      let x,y;
      if(count===1){x=w/2;y=120;}
      else if(count===2){x=w*.25+(i*.5*w);y=120;}
      else if(count===3){x=i===0?w*.23:i===1?w*.77:w*.5;y=i===2?315:115;}
      else {const column=i%cols,row=Math.floor(i/cols);x=110+(w-220)*(column/(cols-1));y=112+row*210;}
      positions.set(String(img.id),{x,y});
    });
    // Keep arrows equally spaced from all four edges of the 205px by roughly 151px cards.
    // The offset is measured from the actual card edge, not from its centre.
    const cardBoundary=(p,ux,uy)=>{
      const factor=Math.min(115/Math.max(Math.abs(ux),.0001),88/Math.max(Math.abs(uy),.0001));
      return {x:p.x+ux*factor,y:p.y+uy*factor};
    };
    const removeLink=(link)=>{
      if(!confirm('Diese Verbindung wirklich entfernen?'))return;
      active.links=active.links.filter(x=>x!==link);render();
      notify('Verbindung entfernt (Entwurf). Bitte Änderungen speichern.');
    };
    const edgeButtons=[];
    // Keep a hovered delete control alive briefly while the pointer crosses a card.
    // Otherwise the underlying card makes the button vanish before it can be clicked.
    let edgeLeaveTimer=null;
    const activateEdge=selected=>{
      if(edgeLeaveTimer!==null){clearTimeout(edgeLeaveTimer);edgeLeaveTimer=null;}
      edgeButtons.forEach(({button,path})=>{
        const shown=button===selected;
        button.classList.toggle('ppi-edge-active',shown);
        path.classList.toggle('ppi-edge-active',shown);
      });
    };
    const scheduleEdgeLeave=()=>{
      if(edgeLeaveTimer!==null)clearTimeout(edgeLeaveTimer);
      edgeLeaveTimer=setTimeout(()=>{
        edgeLeaveTimer=null;
        if(!edgeButtons.some(({button})=>button.matches(':hover')||button===document.activeElement))activateEdge(null);
      },650);
    };
    // 0.4.55: Route each direction along its own curved lane. In contrast to
    // sorting endpoints independently by other-card position, paired links
    // receive matching offsets at both ends: no X-shaped crossings.
    const pairKeys=new Map();
    active.links.forEach((link,index)=>{
      const a=positions.get(String(link.from)),b=positions.get(String(link.to));
      if(!a||!b||String(link.from)===String(link.to))return;
      const key=String(link.from)+'>'+String(link.to);
      if(!pairKeys.has(key))pairKeys.set(key,[]);
      pairKeys.get(key).push(index);
    });
    const anchorOnCard=(p,other,normal,offset)=>{
      const dx=other.x-p.x,dy=other.y-p.y;
      const length=Math.hypot(dx,dy)||1;
      const ux=dx/length,uy=dy/length;
      const base=cardBoundary(p,ux,uy);
      // Slide along the card edge, not off it.
      if(Math.abs(ux)*88>=Math.abs(uy)*115){
        return {x:base.x,y:Math.max(p.y-61,Math.min(p.y+61,base.y+normal.y*offset))};
      }
      return {x:Math.max(p.x-89,Math.min(p.x+89,base.x+normal.x*offset)),y:base.y};
    };
    // 0.4.62.12: reserve separate attachment slots on each side of a card.
    // This is calculated for ALL endpoints before drawing the first route;
    // link order alone can no longer cause several arrowheads to coincide.
    // A direction key based on the unordered image pair ensures that opposite
    // directions use matching slot order at both ends (no accidental X).
    const attachmentGroups=new Map();
    const attachmentSlots=new Map();
    const sideFor=(p,other)=>{
      const dx=other.x-p.x,dy=other.y-p.y;
      return Math.abs(dx)*81>=Math.abs(dy)*109
        ? (dx>=0?'right':'left') : (dy>=0?'bottom':'top');
    };
    active.links.forEach((link,index)=>{
      const from=positions.get(String(link.from)),to=positions.get(String(link.to));
      if(!from||!to||String(link.from)===String(link.to))return;
      for(const [role,p,other,id] of [
        ['from',from,to,String(link.from)],['to',to,from,String(link.to)]
      ]){
        const side=sideFor(p,other),key=id+':'+side;
        const unorderedPair=[String(link.from),String(link.to)].sort().join('~');
        const forward=String(link.from)<String(link.to)?0:1;
        if(!attachmentGroups.has(key))attachmentGroups.set(key,[]);
        attachmentGroups.get(key).push({index,role,p,other,side,unorderedPair,forward});
      }
    });
    for(const group of attachmentGroups.values()){
      if(group.length<2)continue;
      const vertical=group[0].side==='left'||group[0].side==='right';
      const halfSpan=vertical?61:89;
      group.sort((a,b)=>{
        const coordinate=(vertical? a.other.y-b.other.y : a.other.x-b.other.x);
        if(Math.abs(coordinate)>1)return coordinate;
        if(a.unorderedPair!==b.unorderedPair)return a.unorderedPair<b.unorderedPair?-1:1;
        return a.forward-b.forward||a.index-b.index;
      });
      // A comfortable minimum separation, but fit all endpoints inside the
      // available straight portion of the card edge for dense tours.
      const gap=Math.min(23,(halfSpan*2)/(group.length-1));
      group.forEach((item,i)=>{
        const delta=(i-(group.length-1)/2)*gap;
        const {p,side}=item;
        const point=side==='left'?{x:p.x-115,y:p.y+delta}
          :side==='right'?{x:p.x+115,y:p.y+delta}
          :side==='top'?{x:p.x+delta,y:p.y-88}
          :{x:p.x+delta,y:p.y+88};
        attachmentSlots.set(item.index+':'+item.role,point);
      });
    }
    // 0.4.62: obstacle-aware link routing. All measurements use graph pixels;
    // no tour data or saved hotspot coordinates are changed.
    const rectangles=[...positions].map(([id,p])=>({id,x1:p.x-113,y1:p.y-85,x2:p.x+113,y2:p.y+85}));
    const inside=(p,r)=>p.x>r.x1&&p.x<r.x2&&p.y>r.y1&&p.y<r.y2;
    const segmentCrossesRect=(a,b,r)=>{
      // Liang-Barsky clipping, with open edges treated as blocked.
      let lo=0,hi=1;
      const dx=b.x-a.x,dy=b.y-a.y;
      for(const [p,q] of [[-dx,a.x-r.x1],[dx,r.x2-a.x],[-dy,a.y-r.y1],[dy,r.y2-a.y]]){
        if(Math.abs(p)<1e-9){if(q<=0)return false;continue;}
        const t=q/p;
        if(p<0)lo=Math.max(lo,t);else hi=Math.min(hi,t);
        if(lo>hi)return false;
      }
      return hi>Math.max(lo,0)&&lo<1;
    };
    const segHits=(a,b,obstacles)=>obstacles.some(r=>segmentCrossesRect(a,b,r));
    const qPoint=(a,c,b,t)=>({x:(1-t)*(1-t)*a.x+2*(1-t)*t*c.x+t*t*b.x,y:(1-t)*(1-t)*a.y+2*(1-t)*t*c.y+t*t*b.y});
    const curvePoints=(a,c,b)=>Array.from({length:25},(_,i)=>qPoint(a,c,b,i/24));
    const getSegments=points=>points.slice(1).map((p,i)=>[points[i],p]);
    const orient=(a,b,c)=>(b.x-a.x)*(c.y-a.y)-(b.y-a.y)*(c.x-a.x);
    const segmentsIntersect=(a,b,c,d)=>{
      const o1=orient(a,b,c),o2=orient(a,b,d),o3=orient(c,d,a),o4=orient(c,d,b);
      return o1*o2<-.0001&&o3*o4<-.0001;
    };
    const plotted=[];
    // Penalize adjacent routes that run almost on top of each other. Ignore
    // the first/last short stretch: lines must still meet their own cards.
    const pointSegDistance=(p,a,b)=>{
      const dx=b.x-a.x,dy=b.y-a.y,den=dx*dx+dy*dy;
      const t=den?Math.max(0,Math.min(1,((p.x-a.x)*dx+(p.y-a.y)*dy)/den)):0;
      return Math.hypot(p.x-(a.x+t*dx),p.y-(a.y+t*dy));
    };
    const closePenalty=points=>{
      if(!plotted.length || points.length<3)return 0;
      let cost=0;
      const segs=getSegments(points);
      for(let i=0;i<segs.length;i++){
        if(i===0 || i===segs.length-1)continue;
        const mid={x:(segs[i][0].x+segs[i][1].x)/2,y:(segs[i][0].y+segs[i][1].y)/2};
        let nearest=Infinity;
        for(const previous of plotted)for(const [a,b] of previous){
          nearest=Math.min(nearest,pointSegDistance(mid,a,b));
        }
        if(nearest<16)cost+=(16-nearest)*(16-nearest)*.045;
      }
      return cost;
    };
    const crossingPenalty=points=>{
      let intersections=0;
      for(const [a,b] of getSegments(points))for(const prev of plotted)for(const [c,d] of prev){
        if(segmentsIntersect(a,b,c,d))intersections++;
      }
      return intersections;
    };
    const unobstructed=(points,obstacles)=>getSegments(points).every(([a,b])=>!segHits(a,b,obstacles));
    // When the short curved path is blocked, route around buffered card corners.
    // A visibility graph avoids routing through other panorama cards.
    const detour=(start,end,obstacles)=>{
      const nodes=[start,end];
      for(const r of obstacles){
        for(const pad of [10,28])for(const x of [r.x1-pad,r.x2+pad])for(const y of [r.y1-pad,r.y2+pad]){
          if(x>=4&&x<=w-4&&y>=4&&y<=h-4)nodes.push({x,y});
        }
      }
      const size=nodes.length,dist=Array(size).fill(Infinity),done=Array(size).fill(false),prev=Array(size).fill(-1);
      dist[0]=0;
      for(let step=0;step<size;step++){
        let u=-1;
        for(let i=0;i<size;i++)if(!done[i]&&(u<0||dist[i]<dist[u]))u=i;
        if(u<0||!Number.isFinite(dist[u])||u===1)break;
        done[u]=true;
        for(let v=0;v<size;v++){
          if(u===v||done[v]||segHits(nodes[u],nodes[v],obstacles))continue;
          const length=Math.hypot(nodes[u].x-nodes[v].x,nodes[u].y-nodes[v].y);
          const candidatePoints=Array.from({length:9},(_,i)=>({x:nodes[u].x+(nodes[v].x-nodes[u].x)*i/8,y:nodes[u].y+(nodes[v].y-nodes[u].y)*i/8}));
          const penalty=crossingPenalty(candidatePoints)*95+closePenalty(candidatePoints)*1.6;
          const candidate=dist[u]+length+penalty+((v===1)?0:8);
          if(candidate<dist[v]){dist[v]=candidate;prev[v]=u;}
        }
      }
      if(!Number.isFinite(dist[1]))return [start,end];
      const route=[];
      for(let i=1;i>=0;i=prev[i]){route.push(nodes[i]);if(i===0)break;}
      return route.reverse();
    };
    // 0.4.62.1: round obstacle-detour corners with variable-radius quadratic
    // curves. Sample the complete replacement curve and shorten a corner if
    // the smoothing would cut into another panorama card. Leave the final
    // approach to arrowheads straight so their direction stays precise.
    const pathForPoints=(points,obstacles)=>{
      if(points.length<3)return `M ${points[0].x} ${points[0].y} L ${points[1].x} ${points[1].y}`;
      let path=`M ${points[0].x} ${points[0].y}`;
      for(let i=1;i<points.length-1;i++){
        const prev=points[i-1],p=points[i],next=points[i+1];
        const lenIn=Math.hypot(p.x-prev.x,p.y-prev.y);
        const lenOut=Math.hypot(next.x-p.x,next.y-p.y);
        if(lenIn<.001||lenOut<.001){path+=` L ${p.x} ${p.y}`;continue;}
        const vi={x:(p.x-prev.x)/lenIn,y:(p.y-prev.y)/lenIn};
        const vo={x:(next.x-p.x)/lenOut,y:(next.y-p.y)/lenOut};
        // Do not smooth nearly collinear waypoints or U-turns.
        const dot=vi.x*vo.x+vi.y*vo.y;
        if(dot>.995||dot<-.95){path+=` L ${p.x} ${p.y}`;continue;}
        const maxRadius=Math.min(48,lenIn*.43,lenOut*.43,
          i===1?lenIn*.38:48,
          i===points.length-2?Math.max(0,lenOut-18):48);
        let radius=maxRadius,entry,leave,ok=false;
        while(radius>=1.5){
          entry={x:p.x-vi.x*radius,y:p.y-vi.y*radius};
          leave={x:p.x+vo.x*radius,y:p.y+vo.y*radius};
          const rounded=Array.from({length:17},(_,j)=>qPoint(entry,p,leave,j/16));
          if(unobstructed(rounded,obstacles)&&!segHits(prev,entry,obstacles)&&!segHits(leave,next,obstacles)){
            ok=true;break;
          }
          radius*=.6;
        }
        if(ok)path+=` L ${entry.x.toFixed(2)} ${entry.y.toFixed(2)} Q ${p.x.toFixed(2)} ${p.y.toFixed(2)} ${leave.x.toFixed(2)} ${leave.y.toFixed(2)}`;
        else path+=` L ${p.x} ${p.y}`;
      }
      const end=points[points.length-1];
      return path+` L ${end.x} ${end.y}`;
    };
    active.links.forEach((link,index)=>{
      const a=positions.get(String(link.from)),b=positions.get(String(link.to));
      if(!a||!b)return;
      const dx=b.x-a.x,dy=b.y-a.y,d=Math.hypot(dx,dy);if(d<1)return;
      const ux=dx/d,uy=dy/d,nx=-uy,ny=ux;
      const n={x:nx,y:ny};
      const directionKey=String(link.from)+'>'+String(link.to);
      const sequence=pairKeys.get(directionKey)||[index];
      const lane=sequence.indexOf(index);
      const offset=18+lane*11;
      const aEdge=attachmentSlots.get(index+':from')||anchorOnCard(a,b,n,offset);
      const bEdge=attachmentSlots.get(index+':to')||anchorOnCard(b,a,n,offset);
      const ax=aEdge.x,ay=aEdge.y,bx=bEdge.x,by=bEdge.y;
      const startPoint={x:ax,y:ay},endPoint={x:bx,y:by};
      const obstacles=rectangles.filter(r=>r.id!==String(link.from)&&r.id!==String(link.to));
      // Prefer near-straight strokes between neighbours. Reverse links naturally
      // curve to opposite sides because their direction normal is reversed.
      let best=null;
      const baseBend=Math.min(36,Math.max(7,d*.065))+lane*9;
      for(const bend of [baseBend,baseBend*.45,0,-baseBend*.45,-baseBend,baseBend*1.6,-baseBend*1.6]){
        const control={x:(ax+bx)/2+nx*bend,y:(ay+by)/2+ny*bend};
        const pts=curvePoints(startPoint,control,endPoint);
        if(!unobstructed(pts,obstacles))continue;
        const score=crossingPenalty(pts)*150+closePenalty(pts)*2.4+Math.abs(bend-baseBend)*.45;
        if(!best||score<best.score)best={score,control,pts};
      }
      let dPath;
      if(best){
        dPath=`M ${ax} ${ay} Q ${best.control.x} ${best.control.y} ${bx} ${by}`;

      }else{
        const route=detour(startPoint,endPoint,obstacles);
        dPath=pathForPoints(route,obstacles);

      }
      const curve=el('path',{d:dPath,fill:'none',stroke:'#147eae','stroke-width':3.5,'marker-end':'url(#ppi-graph-arrow-046)',class:'ppi-tour-edge-visible'});
      svg.append(curve);
      // Score later edges against the real smooth path, not the original
      // straight detour waypoints (these can differ substantially).
      const total=curve.getTotalLength();
      const samples=Array.from({length:Math.max(25,Math.ceil(total/13))},(_,i)=>{
        const pt=curve.getPointAtLength(total*i/(Math.max(25,Math.ceil(total/13))-1));
        return {x:pt.x,y:pt.y};
      });
      plotted.push(getSegments(samples));
      curve.dataset.from=String(link.from);
      curve.dataset.to=String(link.to);
      // Wide invisible hit stroke: only the hovered connection reveals its delete control.
      const hit=el('path',{d:dPath,fill:'none',stroke:'transparent','stroke-width':19,'pointer-events':'stroke'});
      hit.style.cursor='pointer';svg.append(hit);
      hit.dataset.from=String(link.from);hit.dataset.to=String(link.to);
      // Centre on the actual SVG path length, not at t=0.5 of the curve.
      const center=curve.getPointAtLength(curve.getTotalLength()/2);
      const midx=center.x,midy=center.y;
      const source=active.images.find(img=>Number(img.id)===Number(link.from))?.name||'Panorama';
      const target=active.images.find(img=>Number(img.id)===Number(link.to))?.name||'Panorama';
      const del=node('button',undefined,'ppi-tour-edge-delete');del.type='button';del.append(node('span','×','ppi-delete-glyph'));
      del.style.left=midx+'px';del.style.top=midy+'px';
      del.title=`Verbindung löschen: ${source} → ${target}`;
      del.setAttribute('aria-label',del.title);
      del.addEventListener('click',event=>{event.stopPropagation();removeLink(link);});
      del.addEventListener('mouseenter',()=>activateEdge(del));
      del.addEventListener('mouseleave',scheduleEdgeLeave);
      del.addEventListener('focus',()=>activateEdge(del));
      del.addEventListener('blur',()=>activateEdge(null));
      hit.addEventListener('mouseenter',()=>activateEdge(del));
      hit.addEventListener('mouseleave',scheduleEdgeLeave);
      hit.addEventListener('click',()=>activateEdge(del));
      edgeButtons.push({button:del,path:curve});board.append(del);
    });
    board.prepend(svg);
    const focusConnections=id=>{
      const focused=id!==null;
      svg.classList.toggle('ppi-arrows-card-focused',focused);
      for(const path of svg.querySelectorAll('.ppi-tour-edge-visible')){
        path.classList.toggle('ppi-edge-related',focused&&(path.dataset.from===id||path.dataset.to===id));
      }
    };
    active.images.forEach(img=>{
      const p=positions.get(String(img.id));
      const card=node('div',undefined,'ppi-tour-graph-card');
      const imageId=String(img.id);
      card.addEventListener('mouseenter',()=>focusConnections(imageId));
      card.addEventListener('mouseleave',()=>focusConnections(null));
      card.addEventListener('focusin',()=>focusConnections(imageId));
      card.addEventListener('focusout',()=>{
        requestAnimationFrame(()=>{if(!card.contains(document.activeElement))focusConnections(null);});
      });
      card.style.left=p.x+'px';card.style.top=p.y+'px';
      const picture=node('button',undefined,'ppi-tour-graph-thumb');
      picture.type='button';
      picture.title=`Panorama öffnen: ${img.name}`;
      picture.setAttribute('aria-label',picture.title);
      picture.append(imageThumb(img.id,420,240),node('span','🌐','ppi-thumb-fallback'));
      picture.addEventListener('click',()=>previewImage(img));
      const name=node('div',img.name,'ppi-tour-graph-name');name.title=img.path||img.name;
      const remove=node('button',undefined,'ppi-tour-card-delete');remove.type='button';remove.append(node('span','×','ppi-delete-glyph'));remove.title=`Panorama aus Tour entfernen: ${img.name}`;
      remove.setAttribute('aria-label',remove.title);
      remove.addEventListener('click',event=>{
        event.stopPropagation();
        if(!confirm(`„${img.name}“ aus dieser Tour entfernen? Zugehörige Verbindungen werden ebenfalls gelöscht. Die Originaldatei bleibt erhalten.`))return;
        active.images=active.images.filter(x=>Number(x.id)!==Number(img.id));
        active.links=active.links.filter(x=>Number(x.from)!==Number(img.id)&&Number(x.to)!==Number(img.id));
        render();notify('Panorama aus Tour entfernt (Entwurf). Bitte Änderungen speichern.');
      });
      card.append(picture,name,remove);board.append(card);
    });
    container.append(board);
  }
  function render() {
    if(!active)return;
    // Keep the open panorama's available destinations synchronized after add/delete.
    if (!previewDialog.hidden && previewImageId && active.images.some(item => Number(item.id) === Number(previewImageId))) {
      updateAvailableTargets();
    }
    byId('ppi-tour-name').value=active.name;
    updateShareUi();
    selectOptions(byId('ppi-tour-from'),active.images);
    selectOptions(byId('ppi-tour-to'),active.images);
    // Saving display settings calls render() while this tab is hidden.
    // A hidden graph has clientWidth=0 and would overlap all panorama cards.
    if (!byId('ppi-step-edit').hidden) renderGraph();
  }

  let browseSerial = 0;
  let pickerFolderId = 0; // Target folder, including when its request is still loading.
  let pickerData = null;
  let pickerDebounce = null;
  let searchEditing = false;
  const pickerValue = id => byId(id).value;
  function renderPickerItems(updateFolders=true) {
    if (!pickerData) return;
    const folderBox=byId('ppi-picker-folders'), imageBox=byId('ppi-picker-images');
    const folders=pickerData.items.filter(item=>item.type==='folder');
    const tourIds=new Set((active?.images||[]).map(item=>Number(item.id)));
    const status=pickerValue('ppi-picker-status');
    const images=pickerData.items.filter(item=>item.type==='image' &&
      (status==='all'||(status==='used')===tourIds.has(Number(item.id))));
    // On live search, update only #ppi-picker-images. Never reconstruct folder navigation.
    if (updateFolders) {
      folderBox.replaceChildren();
      if(!folders.length)folderBox.append(node('p','Keine Unterordner.'));
      for(const item of folders){
        const button=node('button',undefined,'ppi-folder-tile');button.type='button';
        button.append(node('span','📁','ppi-folder-icon'),node('span',item.name));
        button.addEventListener('click',()=>browse(item.id));folderBox.append(button);
      }
    }
    imageBox.replaceChildren();
    if(!images.length)imageBox.append(node('p','Keine passenden JPEG-Bilder gefunden.'));
    for(const item of images){
      const card=node('label',undefined,'ppi-pano-card');
      const checkbox=node('input');checkbox.type='checkbox';checkbox.checked=selectedFiles.has(Number(item.id));
      checkbox.setAttribute('aria-label','Panorama '+item.name+' auswählen');
      checkbox.addEventListener('change',()=>{
        if(checkbox.checked)selectedFiles.set(Number(item.id),item);
        else selectedFiles.delete(Number(item.id));
        card.classList.toggle('ppi-selected',checkbox.checked);updateSelection();
      });
      card.classList.toggle('ppi-selected',checkbox.checked);
      const thumb=node('div',undefined,'ppi-picker-thumb');
      thumb.append(imageThumb(item.id,320,180),node('span','🌐','ppi-thumb-fallback'));
      const caption=node('span',item.name,'ppi-picker-filename');
      if(item.path && byId('ppi-picker-recursive').checked)caption.title=item.path;
      const quickView=node('button','Vorschau öffnen','ppi-picker-view');
      quickView.type='button';
      quickView.addEventListener('click',event=>{event.preventDefault();event.stopPropagation();previewImage(item);});
      card.append(checkbox,thumb,caption,quickView);imageBox.append(card);
    }
    byId('ppi-picker-results').textContent=images.length+' Treffer'+(pickerData.panoramaOnly?' · 360°-Filter aktiv · '+Number(pickerData.nonPanoramasExcluded||0)+' normale JPEGs ausgeblendet':'')+(pickerData.truncated?' (begrenzt)':'');
    const warning=byId('ppi-picker-limit-warning');
    warning.hidden=!pickerData.truncated;
    warning.textContent=pickerData.truncated
      ? 'Sehr viele Dateien gefunden. Die Ergebnisliste ist begrenzt; bitte Suche oder Ordner eingrenzen.'
      : '';
    updateSelection();
  }
  // The server applies the filename/date/size search before limiting results;
  // browsing into other folders keeps selected images from previous folders.
  async function browse(folderId=0, resetSelection=false, resultsOnly=false) {
    const serial=++browseSerial;
    pickerFolderId=Number(folderId)||0;
    if(resetSelection){selectedFiles.clear();updateSelection();}
    // Only focus/scroll the picker when opening it, never on live filter refresh.
    if (byId('ppi-tour-picker').hidden) show('picker');
    const folderBox=byId('ppi-picker-folders'), imageBox=byId('ppi-picker-images');
    // Keep existing folders, previews and search controls stable while loading.
    // This also avoids scroll/layout jumps when the user executes a search.
    const currentSearch = byId('ppi-picker-search');
    const restoreSearchFocus = searchEditing || document.activeElement === currentSearch;
    const caretStart = currentSearch.selectionStart;
    const caretEnd = currentSearch.selectionEnd;
    const retainSearchFocus = () => {
      if (!restoreSearchFocus || !currentSearch.isConnected || byId('ppi-tour-picker').hidden) return;
      // Restore focus even if a framework/background update has stolen it.
      if (document.activeElement !== currentSearch) currentSearch.focus({preventScroll:true});
      try { currentSearch.setSelectionRange(caretStart, caretEnd); } catch (_e) {}
    };
    if (!resultsOnly && !pickerData) {
      folderBox.replaceChildren(node('p','Ordner werden geladen …'));
      imageBox.replaceChildren();
    }
    byId('ppi-picker-results').setAttribute('aria-busy','true');
    const params=new URLSearchParams({
      folderId:String(folderId),
      q:pickerValue('ppi-picker-search').trim(),
      searchMode:pickerValue('ppi-picker-search-mode'),
      sort:pickerValue('ppi-picker-sort'),
      date:pickerValue('ppi-picker-date'),
      size:pickerValue('ppi-picker-size'),
      recursive:byId('ppi-picker-recursive').checked?'1':'0',
      panoramaOnly:byId('ppi-picker-panorama-only').checked?'1':'0'
    });
    try {
      const data=await request(filesApi+(filesApi.includes('?')?'&':'?')+params.toString());
      if(serial!==browseSerial)return;
      // A stale PHP controller can silently ignore the search-mode parameter.
      // Never display misleading filename hits as tag-only results.
      if (data.searchMode !== params.get('searchMode')) {
        throw new Error('Suchmodus wurde vom Server nicht bestätigt. Bitte den aktualisierten PHP-Controller der Test-App installieren.');
      }
      browsing={id:Number(data.folderId),path:data.path,parentId:Number(data.parentId||0)};
      pickerFolderId=browsing.id;
      pickerData=data;
      if (!resultsOnly) {
        byId('ppi-picker-path').textContent=data.path;
        byId('ppi-picker-up').disabled=Boolean(data.isRoot);
      }
      renderPickerItems(!resultsOnly);
      byId('ppi-picker-results').removeAttribute('aria-busy');
      retainSearchFocus();
      requestAnimationFrame(retainSearchFocus);
    }catch(e){
      if(serial!==browseSerial)return;
      byId('ppi-picker-results').removeAttribute('aria-busy');
      if (!resultsOnly && !pickerData) folderBox.replaceChildren(node('p','Ordner konnten nicht geladen werden: '+e.message));
      retainSearchFocus();
      requestAnimationFrame(retainSearchFocus);
      notify('Panorama-Auswahl: '+e.message,true);
    }
  }
  function reapplyPicker() {
    if(!byId('ppi-tour-picker').hidden)browse(pickerFolderId,false,true);
  }
  // Debounced live search. Only the image-results container is rerendered.
  // Invalidate any previous in-flight result as soon as the text changes.
  const searchInput=byId('ppi-picker-search');
  const searchMode=byId('ppi-picker-search-mode');
  const searchModeLabel=byId('ppi-picker-search-mode-label');
  const syncSearchModeLabel=()=>{
    searchModeLabel.textContent=searchMode.options[searchMode.selectedIndex]?.textContent || 'Suchbereich';
  };
  searchMode.addEventListener('change',()=>{
    syncSearchModeLabel();
    ++browseSerial;
    clearTimeout(pickerDebounce);
    reapplyPicker();
  });
  syncSearchModeLabel();
  // Genuine user interaction elsewhere ends the typing session.
  root.addEventListener('pointerdown', event => {
    if (!searchInput.contains(event.target)) searchEditing = false;
  }, true);
  document.addEventListener('pointerdown', event => {
    if (!searchInput.contains(event.target)) searchEditing = false;
  }, true);
  searchInput.addEventListener('focus',()=>{searchEditing=true;});
  // Some Nextcloud themes/browser integrations blur inputs when the results settle.
  // Keep a real typing session focused unless the user intentionally moves elsewhere.
  searchInput.addEventListener('blur',()=>{
    if (!searchEditing) return;
    requestAnimationFrame(()=>{
      const current=document.activeElement;
      if (searchEditing && searchInput.isConnected && !byId('ppi-tour-picker').hidden
          && (!current || current === document.body || current === document.documentElement)) {
        searchInput.focus({preventScroll:true});
      }
    });
  });
  searchInput.addEventListener('input',()=>{
    searchEditing = true;
    ++browseSerial;
    clearTimeout(pickerDebounce);
    pickerDebounce=setTimeout(reapplyPicker,350);
  });
  searchInput.addEventListener('keydown',event=>{
    if (event.key === 'Tab') searchEditing = false;
    if(event.key==='Enter' && !event.isComposing){
      event.preventDefault();
      clearTimeout(pickerDebounce);
      reapplyPicker();
    }
  });
  for(const id of ['ppi-picker-sort','ppi-picker-date','ppi-picker-size','ppi-picker-recursive']){
    byId(id).addEventListener('change',()=>{
      ++browseSerial;
      clearTimeout(pickerDebounce);
      reapplyPicker();
    });
  }
  byId('ppi-picker-status').addEventListener('change',renderPickerItems);
  // This preference belongs to the Nextcloud user, not a browser-specific localStorage key.
  byId('ppi-picker-panorama-only').addEventListener('change',async()=>{
    const checkbox=byId('ppi-picker-panorama-only');
    const newValue=checkbox.checked, oldValue=!newValue;
    checkbox.disabled=true;
    // Refresh immediately, regardless of how long saving the preference takes.
    // The browse request carries the new filter state explicitly.
    clearTimeout(pickerDebounce);
    reapplyPicker();
    try {
      await request(root.dataset.pickerPreferencesUrl,'POST',{panoramaOnly:newValue?'1':'0'});
    } catch(e) {
      checkbox.checked=oldValue;
      reapplyPicker();
      notify('360°-Filter konnte nicht gespeichert werden: '+e.message,true);
    } finally {checkbox.disabled=false;}
  });
  byId('ppi-picker-reset-filters').addEventListener('click',()=>{
    clearTimeout(pickerDebounce);
    for(const id of ['ppi-picker-search','ppi-picker-search-mode','ppi-picker-sort','ppi-picker-status','ppi-picker-date','ppi-picker-size']){
      byId(id).selectedIndex!==undefined && byId(id).tagName==='SELECT'
        ? byId(id).selectedIndex=0 : byId(id).value='';
    }
    byId('ppi-picker-recursive').checked=false;
    syncSearchModeLabel();
    ++browseSerial;
    reapplyPicker();
  });

  // Independent private Startseitenverwaltung (phase 1).
  const startPagesApi = root.dataset.startPagesUrl;
  let startPages = [], activeStartPage = null, managementMode = 'tours';
  let savedStartPageSnapshot = '';
  const startPageFields = ['ppi-start-page-name','ppi-start-page-headline','ppi-start-page-description',
    'ppi-start-page-layout','ppi-start-page-heading-color','ppi-start-page-description-color','ppi-start-page-background-color','ppi-start-page-tile-columns'];
  let startPageBackgroundFileId = 0, startPageBgFolderParentId = 0;
  let startPageBgFolderId = 0, startPageBgRequestSerial = 0;
  function tourShortDescription(tour) {
    const raw = String(tour.display?.introText || '');
    return raw.replace(/\[([^\]]+)\]\(https?:\/\/[^\s)]+\)/g, '$1')
      .replace(/(?:^|\n)\s*(?:[-*]|\d+[.)])\s+/gm, ' ')
      .replace(/\s+/g, ' ').trim().slice(0, 140);
  }
  function startPageFormValues() {
    return {
      name:byId('ppi-start-page-name').value,
      headline:byId('ppi-start-page-headline').value,
      description:byId('ppi-start-page-description').value,
      layout:byId('ppi-start-page-layout').value,
      headingColor:byId('ppi-start-page-heading-color').value,
      descriptionColor:byId('ppi-start-page-description-color').value,
      backgroundColor:byId('ppi-start-page-background-color').value,
      backgroundFileId:startPageBackgroundFileId,
      tileColumns:byId('ppi-start-page-tile-columns').value,
      tourIds:[...byId('ppi-start-page-tours').querySelectorAll('input:checked')].map(input => input.value)
    };
  }
  function updateStartPagePreview() {
    if (!activeStartPage) return;
    const values = startPageFormValues();
    const preview = byId('ppi-startpage-preview');
    preview.dataset.layout = values.layout;
    preview.style.setProperty('--ppi-startpage-bg', values.backgroundColor);
    preview.style.setProperty('--ppi-startpage-heading', values.headingColor);
    preview.style.setProperty('--ppi-startpage-description', values.descriptionColor);
    preview.style.setProperty('--ppi-startpage-image', values.backgroundFileId ? 'url("' + previewUrl(values.backgroundFileId, 1920, 1080) + '")' : 'none');
    byId('ppi-startpage-preview-title').textContent = values.headline || values.name || 'Meine 360°-Startseite';
    byId('ppi-startpage-preview-description').textContent = values.description;
    const grid = byId('ppi-startpage-preview-grid');
    grid.replaceChildren();
    grid.dataset.cols = values.tileColumns;
    for (const id of values.tourIds) {
      const tour = tours.find(item => item.id === id);
      if (!tour) continue;
      const tile = node('div', undefined, 'ppi-startpage-preview-tile');
      if (tour.images?.length) {
        const image = imageThumb(tour.images[0].id, 520, 293);
        image.alt = 'Vorschau: ' + tour.name;
        image.loading = 'lazy';
        tile.append(image);
      } else tile.append(node('div', '🌐', 'ppi-overview-placeholder'));
      const copy = node('div',undefined,'ppi-startpage-tile-copy');
      copy.append(node('strong', tour.name));
      const description = tourShortDescription(tour);
      copy.append(node('small', description || 'Keine Beschreibung hinterlegt'));
      tile.append(copy);
      grid.append(tile);
    }
    if (!grid.children.length) grid.append(node('p','Wähle unter „Touren wählen“ mindestens eine Tour aus.','ppi-startpage-preview-empty'));
    byId('ppi-startpage-dirty').textContent =
      JSON.stringify(values) === savedStartPageSnapshot ? 'Gespeicherter Stand' : '● Ungespeicherte Änderungen';
  }
  const layoutPresetButtons = [...byId('ppi-start-page-tile-presets').querySelectorAll('[data-columns]')];
  function chooseTileLayout(value) {
    if (!['auto','2','3','4','5'].includes(value)) return;
    byId('ppi-start-page-tile-columns').value = value;
    for (const button of layoutPresetButtons) {
      button.setAttribute('aria-pressed', String(button.dataset.columns === value));
    }
    updateStartPagePreview();
  }
  for (const button of layoutPresetButtons) {
    button.addEventListener('click', () => chooseTileLayout(button.dataset.columns));
  }
  function showStartPageTab(tab) {
    const tabs = ['start','layout','tours','share'];
    if (!tabs.includes(tab)) return;
    byId('ppi-start-page-editor').querySelectorAll('[data-startpage-tab]').forEach(button => {
      if (button.dataset.startpageTab === tab) button.setAttribute('aria-current','step');
      else button.removeAttribute('aria-current');
    });
    byId('ppi-start-page-editor').querySelectorAll('[data-startpage-panel]').forEach(section => {
      section.hidden = section.dataset.startpagePanel !== tab;
    });
  }
  byId('ppi-start-page-editor').querySelectorAll('[data-startpage-tab]').forEach(button => {
    button.addEventListener('click', () => showStartPageTab(button.dataset.startpageTab));
  });
  startPageFields.forEach(id => byId(id).addEventListener('input', updateStartPagePreview));
  byId('ppi-start-page-tours').addEventListener('change', updateStartPagePreview);
  async function browseStartPageBackground(folderId = 0) {
    const serial = ++startPageBgRequestSerial;
    const results = byId('ppi-start-page-bg-files');
    results.replaceChildren(node('p','Bilder werden geladen …'));
    const params = new URLSearchParams({folderId:String(folderId),panoramaOnly:'0'});
    try {
      const result = await request(filesApi + '?' + params);
      if (serial !== startPageBgRequestSerial) return;
      startPageBgFolderId = result.isRoot ? 0 : Number(result.folderId);
      startPageBgFolderParentId = Number(result.parentId) || 0;
      byId('ppi-start-page-bg-path').textContent = result.path || '/';
      byId('ppi-start-page-bg-up').disabled = !!result.isRoot;
      results.replaceChildren();
      const items = (result.items || []).filter(item => item.type === 'folder' || item.type === 'image').slice(0,160);
      for (const item of items) {
        const button = node('button',undefined);
        button.type = 'button';
        if (item.type === 'folder') {
          button.append(node('span','📁 ' + item.name));
          button.addEventListener('click',()=>browseStartPageBackground(item.id));
        } else {
          const image = imageThumb(item.id,260,146);
          image.loading = 'lazy';
          button.append(image,node('span',item.name));
          button.addEventListener('click',()=>{
            startPageBackgroundFileId = Number(item.id);
            byId('ppi-start-page-bg-selected').textContent = 'Ausgewählt: ' + item.name;
            byId('ppi-start-page-bg-browser').hidden = true;
            updateStartPagePreview();
          });
        }
        results.append(button);
      }
      if (!items.length) results.append(node('p','Keine JPEG-Bilder in diesem Ordner.'));
      if (result.truncated) results.append(node('p','Ergebnis begrenzt. Bitte einen Unterordner öffnen.'));
    } catch (error) {
      if (serial === startPageBgRequestSerial) results.replaceChildren(node('p','Bilder nicht verfügbar: '+error.message));
    }
  }
  byId('ppi-start-page-bg-browse').addEventListener('click',()=>{
    byId('ppi-start-page-bg-browser').hidden = false;
    browseStartPageBackground(startPageBgFolderId);
  });
  byId('ppi-start-page-bg-up').addEventListener('click',()=>browseStartPageBackground(startPageBgFolderParentId));
  byId('ppi-start-page-bg-remove').addEventListener('click',()=>{
    startPageBackgroundFileId = 0;
    byId('ppi-start-page-bg-selected').textContent = 'Kein Bild ausgewählt';
    byId('ppi-start-page-bg-browser').hidden = true;
    updateStartPagePreview();
  });
  function showStartPageEditor(page) {
    activeStartPage = clone(page);
    byId('ppi-start-page-name').value = page.name || '';
    byId('ppi-start-page-headline').value = page.headline || '';
    byId('ppi-start-page-description').value = page.description || '';
    byId('ppi-start-page-layout').value = page.layout || 'hero';
    byId('ppi-start-page-heading-color').value = /^#[a-f\d]{6}$/i.test(page.headingColor || '') ? page.headingColor : '#ffffff';
    byId('ppi-start-page-description-color').value = /^#[a-f\d]{6}$/i.test(page.descriptionColor || '') ? page.descriptionColor : '#ffffff';
    byId('ppi-start-page-background-color').value = /^#[a-f\d]{6}$/i.test(page.backgroundColor || '') ? page.backgroundColor : '#000000';
    const savedColumns = ['auto','2','3','4','5'].includes(page.tileColumns) ? page.tileColumns : 'auto';
    byId('ppi-start-page-tile-columns').value = savedColumns;
    for (const button of layoutPresetButtons) button.setAttribute('aria-pressed', String(button.dataset.columns === savedColumns));
    startPageBackgroundFileId = Number(page.backgroundFileId) || 0;
    byId('ppi-start-page-bg-selected').textContent = startPageBackgroundFileId ? 'Bild aus Nextcloud ausgewählt (Datei-ID ' + startPageBackgroundFileId + ')' : 'Kein Bild ausgewählt';
    byId('ppi-start-page-bg-browser').hidden = true;
    startPageBgFolderId = 0;
    const choices = byId('ppi-start-page-tours');
    choices.replaceChildren();
    for (const tour of tours) {
      const row = node('label', undefined, 'ppi-startpage-tour-option');
      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.value = tour.id;
      checkbox.checked = (page.tourIds || []).includes(tour.id);
      if (tour.images?.length) {
        const picture = imageThumb(tour.images[0].id,320,180);
        picture.loading = 'lazy';
        picture.alt = 'Vorschau: '+tour.name;
        row.append(picture);
      } else row.append(node('span','🌐','ppi-overview-placeholder'));
      const info = node('span',undefined,'ppi-startpage-tour-info');
      info.append(node('strong',tour.name));
      info.append(node('small',tourShortDescription(tour) || 'Keine Beschreibung hinterlegt'));
      row.append(info,checkbox);
      choices.append(row);
    }
    byId('ppi-start-pages-overview').hidden = true;
    byId('ppi-start-pages-overview').style.display = 'none';
    byId('ppi-start-page-editor').hidden = false;
    byId('ppi-start-page-editor').style.display = 'block';
    byId('ppi-start-page-create').hidden = true;
    byId('ppi-overview-switch').hidden = true;
    savedStartPageSnapshot = JSON.stringify(startPageFormValues());
    showStartPageTab('start');
    updateStartPagePreview();
  }
  async function refreshStartPages() {
    const result = await request(startPagesApi);
    startPages = Array.isArray(result.pages) ? result.pages : [];
    const container = byId('ppi-start-pages-list');
    container.replaceChildren();
    container.classList.toggle('ppi-startpages-single', startPages.length === 1);
    container.classList.toggle('ppi-startpages-double', startPages.length === 2);
    container.classList.toggle('ppi-startpages-multi', startPages.length >= 3);
    if (!startPages.length) container.append(node('p', 'Noch keine Startseiten erstellt.'));
    for (const page of startPages) {
      const tile = node('article', undefined, 'ppi-overview-tile');
      const cover = node('div',undefined,'ppi-startpage-overview-cover');
      if (Number(page.backgroundFileId) > 0) {
        const background = previewUrl(Number(page.backgroundFileId), 920, 520);
        cover.style.backgroundImage = 'linear-gradient(#0005,#0005),url("' + background + '")';
      } else {
        cover.style.backgroundImage = 'linear-gradient(140deg,#101820,#30465b)';
      }
      cover.append(node('div', '▦', 'ppi-overview-placeholder'));
      tile.append(cover);
      const body = node('div', undefined, 'ppi-overview-body');
      body.append(node('h3', page.name, 'ppi-overview-name'));
      body.append(node('div', '🔒 Privater Entwurf · ' + (page.tourIds || []).length + ' Touren', 'ppi-overview-status'));
      const actions = node('div', undefined, 'ppi-overview-actions');
      actions.append(tourAction('Bearbeiten', () => showStartPageEditor(page), true));
      actions.append(tourAction('Startseite duplizieren', async () => {
        try {
          await request(startPagesApi + '/' + encodeURIComponent(page.id) + '/duplicate', 'POST', {});
          await refreshStartPages();
          notify('Startseite als privater Entwurf dupliziert.');
        } catch (e) { notify(e.message, true); }
      }));
      body.append(actions);
      tile.append(body);
      container.append(tile);
    }
  }
  async function setManagementMode(mode) {
    if (mode !== 'tours' && mode !== 'startpages') return;
    managementMode = mode;
    const pages = mode === 'startpages';
    byId('ppi-section-icon').textContent = pages ? '▦' : '🌐';
    byId('ppi-section-heading').textContent = pages ? 'Meine 360°-Startseiten' : 'Meine 360°-Touren';
    byId('ppi-section-subtitle').textContent = pages ?
      'Startseiten mit mehreren Panorama-Touren gestalten' : 'Touren aus deinen Nextcloud-Panoramen zusammenstellen';
    byId('ppi-mode-tours').setAttribute('aria-pressed', String(!pages));
    byId('ppi-mode-startpages').setAttribute('aria-pressed', String(pages));
    byId('ppi-tour-overview').hidden = pages;
    byId('ppi-tour-overview').style.display = pages ? 'none' : 'block';
    byId('ppi-start-pages-overview').hidden = !pages;
    byId('ppi-start-pages-overview').style.display = pages ? 'block' : 'none';
    byId('ppi-start-page-editor').hidden = true;
    byId('ppi-start-page-editor').style.display = 'none';
    byId('ppi-tour-create').hidden = pages;
    byId('ppi-start-page-create').hidden = !pages;
    byId('ppi-overview-switch').hidden = false;
    if (pages) {
      try { await refreshStartPages(); }
      catch (e) { notify('Startseiten konnten nicht geladen werden: ' + e.message, true); }
    } else {
      try { await refresh(); }
      catch (e) { notify('Touren konnten nicht aktualisiert werden: ' + e.message, true); }
    }
  }
  byId('ppi-mode-tours').addEventListener('click', () => setManagementMode('tours'));
  byId('ppi-mode-startpages').addEventListener('click', () => setManagementMode('startpages'));
  byId('ppi-start-page-create').addEventListener('click', async () => {
    const name = prompt('Wie soll die neue Startseite heißen?');
    if (name === null) return;
    try {
      const result = await request(startPagesApi, 'POST', {name});
      await refreshStartPages();
      showStartPageEditor(result.page);
      notify('Private Startseite erstellt.');
    } catch (e) { notify(e.message, true); }
  });
  byId('ppi-start-page-back').addEventListener('click', () => setManagementMode('startpages'));
  byId('ppi-start-page-save').addEventListener('click', async () => {
    if (!activeStartPage) return;
    const values = startPageFormValues();
    try {
      const result = await request(startPagesApi + '/' + encodeURIComponent(activeStartPage.id), 'POST', {
        ...values,
        tourIds:JSON.stringify(values.tourIds)
      });
      activeStartPage = clone(result.page);
      savedStartPageSnapshot = JSON.stringify(startPageFormValues());
      updateStartPagePreview();
      await refreshStartPages();
      notify('Startseite gespeichert. Übersicht und Vorschau sind aktualisiert.');
    } catch (e) { notify(e.message, true); }
  });
  byId('ppi-start-page-delete').addEventListener('click', async () => {
    if (!activeStartPage || !confirm('Diese Startseite endgültig löschen? Touren bleiben erhalten.')) return;
    try {
      await request(startPagesApi + '/' + encodeURIComponent(activeStartPage.id), 'DELETE');
      activeStartPage = null;
      await setManagementMode('startpages');
      notify('Startseite gelöscht. Touren bleiben erhalten.');
    } catch (e) { notify(e.message, true); }
  });

  byId('ppi-tour-create').addEventListener('click',async()=>{
    const name=prompt('Wie soll die neue Tour heißen?');if(name===null)return;
    try{const data=await request(api,'POST',{name});await refresh();openTour(data.tour);notify('Tour erstellt. Jetzt Panoramen hinzufügen.');}catch(e){notify(e.message,true);}
  });
  byId('ppi-tour-back').addEventListener('click',()=>{active=null;show('overview');refresh().catch(e=>notify(e.message,true));});
  byId('ppi-tour-add').addEventListener('click',event=>{
    event.preventDefault();
    // Always start a new picker session with panoramas not yet in this tour.
    // Keep any user-selected tour status while navigating within the open picker.
    byId('ppi-picker-status').value='unused';
    browse(0, true);
  });
  byId('ppi-picker-close').addEventListener('click',()=>{++browseSerial;show('editor');});
  byId('ppi-picker-up').addEventListener('click',()=>browse(browsing?.parentId || 0));
  byId('ppi-tour-link').addEventListener('click',()=>{
    const from=Number(byId('ppi-tour-from').value),to=Number(byId('ppi-tour-to').value);
    if(!from||!to||from===to){notify('Bitte zwei unterschiedliche Panoramen wählen.',true);return;}
    if(active.links.some(x=>x.from===from&&x.to===to)){notify('Diese Verbindung existiert bereits.',true);return;}
    active.links.push({from,to,yaw:0,pitch:-0.10});render();notify('Verbindung hinzugefügt. Bitte Änderungen speichern.');
  });
  byId('ppi-tour-save').addEventListener('click',async()=>{
    try { const result=await request(`${api}/${encodeURIComponent(active.id)}`,'POST',{
      name:byId('ppi-tour-name').value,images:JSON.stringify(active.images),links:JSON.stringify(active.links)});
      active=clone(result.tour);await refresh();render();notify('Tour erfolgreich gespeichert.');
    }catch(e){notify(e.message,true);}
  });
  byId('ppi-tour-delete').addEventListener('click',async()=>{
    if(!confirm('Diese Tour wirklich löschen? Die Originalbilder bleiben erhalten.'))return;
    try{await request(`${api}/${encodeURIComponent(active.id)}`,'DELETE');active=null;await refresh();show('overview');notify('Tour gelöscht.');}
    catch(e){notify(e.message,true);}
  });
  let mobileGraphState=window.matchMedia('(max-width:700px)').matches;
  window.addEventListener('resize',()=>{
    const next=window.matchMedia('(max-width:700px)').matches;
    if(next!==mobileGraphState){mobileGraphState=next;if(active&&!byId('ppi-step-edit').hidden)renderGraph();}
  });
  show('overview');
  refresh().catch(e=>notify('Touren konnten nicht geladen werden: '+e.message,true));
  setManagementMode('tours');
})();
