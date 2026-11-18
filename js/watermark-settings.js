(() => {
  'use strict';
  function init() {
    const root = document.getElementById('ppi-watermark-admin');
    if (!root || root.dataset.initialized) return;
    root.dataset.initialized = '1';
    const $ = id => document.getElementById('ppi-' + id);
    const feedback = $('watermark-feedback');
    const form = $('watermark-form');
    const fileInput = $('watermark-file');
    const gallery = $('wm-gallery');
    const save = $('wm-save-options');
    const restoreDefault = $('wm-restore-default');
    const enabled = $('wm-enabled');
    const modeButtons = Array.from(root.querySelectorAll('input[name="ppi-wm-mode"]'));
    const getMode = () => modeButtons.find(button => button.checked)?.value || 'panorama';
    const setMode = value => {
      const requested = value === 'screen' ? 'screen' : 'panorama';
      for (const button of modeButtons) button.checked = button.value === requested;
    };
    const fields = ['yaw','pitchDesktop','pitchMobile','screenX','screenY','sizeDesktop','sizeMobile'];
    let revision = '';
    let active = 'none';
    let busy = false;
    let selectionInProgress = false;
    let folderRules = [];
    const token = () => window.OC?.requestToken || document.head?.dataset.requesttoken || '';

    // Zoom administration is independent of watermark gallery and its state.
    const zoomKeys = ['desktopMin','desktopStart','desktopMax','mobileMin','mobileStart','mobileMax'];
    const zoomField = key => document.getElementById('ppi-zoom-' + key);
    const zoomFeedback = document.getElementById('ppi-zoom-feedback');
    const zoomSave = document.getElementById('ppi-zoom-save');
    const zoomReset = document.getElementById('ppi-zoom-reset');
    const zoomDefaults = {desktopMin:60,desktopStart:110,desktopMax:135,mobileMin:30,mobileStart:90,mobileMax:110};
    function showZoom(message, error = false) {
      if (!zoomFeedback) return;
      zoomFeedback.textContent = message;
      zoomFeedback.style.color = error ? 'var(--color-error,#d33)' : '';
    }
    function readZoomFields() {
      return Object.fromEntries(zoomKeys.map(key => [key, Number(zoomField(key).value)]));
    }
    function validateZoom(values) {
      return ['desktop','mobile'].every(device =>
        values[device+'Min']+5 <= values[device+'Start'] &&
        values[device+'Start']+5 <= values[device+'Max']);
    }
    function updateZoomOutput(key) {
      document.getElementById('ppi-zoom-' + key + '-out').textContent = zoomField(key).value + '°';
    }
    if (zoomSave) {
      zoomKeys.forEach(key => zoomField(key).addEventListener('input', () => {
        updateZoomOutput(key);
        showZoom(validateZoom(readZoomFields()) ? '' : 'Startwert muss mindestens 5° von beiden Grenzen entfernt sein.', true);
      }));
      request(root.dataset.zoomSettingsUrl).then(result => {
        for (const key of zoomKeys) {
          if (Number.isFinite(Number(result.zoom?.[key]))) zoomField(key).value = result.zoom[key];
          updateZoomOutput(key);
        }
      }).catch(error => showZoom('Zoomwerte konnten nicht geladen werden: ' + error.message, true));
      zoomReset?.addEventListener('click', async () => {
        if (!confirm('Zoom-Einstellungen für Desktop und Smartphone auf die Standardwerte zurücksetzen?')) return;
        zoomReset.disabled=true;
        zoomSave.disabled=true;
        showZoom('Standardwerte werden wiederhergestellt …');
        try {
          const body = new FormData(); body.set('zoom', JSON.stringify(zoomDefaults));
          await request(root.dataset.zoomSaveUrl, body);
          for (const key of zoomKeys) {
            zoomField(key).value=zoomDefaults[key];
            updateZoomOutput(key);
          }
          showZoom('Standardwerte gespeichert. Neue Panoramen übernehmen die Einstellung.');
        } catch (error) {showZoom('Fehler: '+error.message,true);}
        finally {zoomReset.disabled=false;zoomSave.disabled=false;}
      });
      zoomSave.addEventListener('click', async () => {
        const values = readZoomFields();
        if (!validateZoom(values)) return showZoom('Bitte die Zoomgrenzen und Startwerte korrigieren.', true);
        zoomSave.disabled = true;
        showZoom('Zoomwerte werden gespeichert …');
        try {
          const body = new FormData(); body.set('zoom', JSON.stringify(values));
          await request(root.dataset.zoomSaveUrl, body);
          showZoom('Zoomwerte gespeichert. Neue Panoramen übernehmen die Einstellung.');
        } catch (error) {showZoom('Fehler: ' + error.message, true);}
        finally {zoomSave.disabled = false;}
      });
    }


    function show(message, isError = false) {
      feedback.textContent = message;
      feedback.style.color = isError ? 'var(--color-error, #d33)' : '';
    }
    async function request(url, body = null) {
      const options = {credentials:'same-origin', cache:'no-store'};
      if (body !== null) {
        options.method = 'POST';
        options.body = body;
        options.headers = {requesttoken:token(),'X-Requested-With':'XMLHttpRequest'};
      }
      const response = await fetch(url, options);
      let value = {};
      try {value = await response.json();} catch {}
      if (!response.ok || value.success !== true) throw new Error(value.message || `HTTP ${response.status}`);
      return value;
    }
    const data = object => {
      const body = new FormData();
      for (const [key, value] of Object.entries(object)) body.set(key, String(value));
      return body;
    };
    function updateMode() {
      $('wm-panorama-settings').style.display = getMode() === 'panorama' ? 'block' : 'none';
      $('wm-screen-settings').style.display = getMode() === 'screen' ? 'block' : 'none';
    }
    function updateOutput(key) {
      const unit = key.startsWith('size') ? ' px' : key.startsWith('screen') ? ' %' : '°';
      $('wm-' + key + '-out').textContent = $('wm-' + key).value + unit;
    }
    for (const key of fields) {
      $('wm-' + key).addEventListener('input', () => updateOutput(key));
    }
    for (const button of modeButtons) button.addEventListener('change', updateMode);
    function renderOptions(options) {
      enabled.checked = Boolean(options.enabled);
      setMode(options.mode);
      for (const key of fields) {
        $('wm-' + key).value = options[key];
        updateOutput(key);
      }
      updateMode();
    }
    function previewUrl(id) {
      return root.dataset.previewBaseUrl + '?id=' + encodeURIComponent(id) + '&v=' + encodeURIComponent(revision);
    }
    function openFolderPicker(imageId) {
      // Modal lives outside the cards so gallery refreshes cannot destroy it.
      root.querySelector('.ppi-folder-picker')?.remove();
      const dialog = document.createElement('dialog');
      dialog.className = 'ppi-folder-picker';
      const heading = document.createElement('h3');
      heading.textContent = 'Nextcloud-Ordner auswählen';
      const hint = document.createElement('p');
      hint.textContent = 'Nur Ordner aus deinem eigenen Dateien-Bereich und von dir erreichbare Freigaben sind auswählbar.';
      const ownerLabel = document.createElement('label');
      ownerLabel.textContent = 'Angemeldetes Konto';
      const owners = document.createElement('select');
      owners.className = 'ppi-picker-owner';
      const ownerName = document.createElement('strong');
      ownerLabel.appendChild(ownerName);
      const path = document.createElement('div');
      path.className = 'ppi-picker-path';
      path.textContent = 'Ordner werden geladen …';
      const items = document.createElement('div');
      items.className = 'ppi-picker-list';
      const message = document.createElement('div');
      message.setAttribute('role','status');
      const actions = document.createElement('div');
      actions.className = 'ppi-picker-actions';
      const back = document.createElement('button');
      back.type = 'button'; back.textContent = 'Zurück';
      const cancel = document.createElement('button');
      cancel.type = 'button'; cancel.textContent = 'Abbrechen';
      const choose = document.createElement('button');
      choose.type = 'button'; choose.className = 'primary';
      choose.textContent = 'Diesen Ordner zuweisen';
      actions.append(back, cancel, choose);
      dialog.append(heading, hint, ownerLabel, path, items, message, actions);
      root.appendChild(dialog);
      const close = () => {dialog.close(); dialog.remove();};
      cancel.addEventListener('click', close);
      dialog.addEventListener('close', () => dialog.remove());
      dialog.showModal();
      let current = null;
      let history = [];
      let generation = 0;
      const load = async (id = 0, pushHistory = false) => {
        const owner = owners.value;
        const serial = ++generation;
        choose.disabled = true;
        items.replaceChildren();
        message.textContent = 'Ordner werden geladen …';
        try {
          const url = root.dataset.browseFoldersUrl + '?owner=' + encodeURIComponent(owner)
            + '&folderId=' + encodeURIComponent(id);
          const response = await request(url);
          if (serial !== generation || !dialog.isConnected) return;
          if (pushHistory && current !== null) history.push(current.folderId);
          current = response;
          back.disabled = history.length === 0;
          path.textContent = `${owner}: ${response.path}`;
          choose.disabled = response.isRoot;
          for (const folder of response.folders) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = '📁  ' + folder.name;
            button.addEventListener('click', () => load(folder.id, true));
            items.appendChild(button);
          }
          message.textContent = response.folders.length
            ? 'Ordner anklicken, um ihn zu öffnen.'
            : 'Keine weiteren Unterordner. Du kannst diesen Ordner zuweisen.';
        } catch (error) {
          if (serial !== generation) return;
          current = null;
          message.textContent = 'Fehler: ' + error.message;
        }
      };
      back.addEventListener('click', () => {
        if (!history.length) return;
        const id = history.pop();
        load(id);
      });
      choose.addEventListener('click', () => {
        if (!current || current.isRoot) return;
        const owner = owners.value;
        const folderPath = current.path;
        run(async () => {
          await request(root.dataset.assignFolderUrl,
            data({imageId, owner, path:folderPath}));
          close();
          await refresh();
          show('Ordner samt Unterordnern zugewiesen.');
        });
      });
      request(root.dataset.folderOwnersUrl).then(result => {
        if (!dialog.isConnected) return;
        const currentUser = result.currentUser;
        const users = result.users || [];
        for (const user of users) {
          const option = document.createElement('option');
          option.value = user.uid;
          option.textContent = user.label === user.uid ? user.uid : `${user.label} (${user.uid})`;
          owners.appendChild(option);
        }
        if (users.some(user => user.uid === currentUser)) owners.value = currentUser;
        ownerName.textContent = users.find(user => user.uid === currentUser)?.label || currentUser;
        if (!owners.value) {
          message.textContent = 'Keine Benutzer gefunden.';
          return;
        }
        load(0);
      }).catch(error => { message.textContent = 'Benutzer konnten nicht geladen werden: ' + error.message; });
    }

    function renderGallery(images) {
      gallery.replaceChildren();
      for (const item of images) {
        const card = document.createElement('div');
        card.className = 'ppi-wm-card';
        Object.assign(card.style, {
          display:'flex',flexDirection:'column',alignItems:'center',gap:'8px',
          border:item.id === active ? '2px solid var(--color-primary-element,#0082c9)' : '1px solid var(--color-border,#ccc)',
          borderRadius:'10px',padding:'10px',width:'160px',maxWidth:'100%',
          background:'var(--color-background-dark,#eee)'
        });
        const image = document.createElement('img');
        if (item.id === 'none') {
          image.alt = 'Keine Grafik';
          image.style.background = 'repeating-linear-gradient(45deg, #25313d, #25313d 9px, #354554 9px, #354554 18px)';
        } else {
          image.src = previewUrl(item.id);
        }
        image.alt = item.label;
        Object.assign(image.style, {width:'132px',height:'83px',objectFit:'contain',background:'#18232d',borderRadius:'5px'});
        const label = document.createElement('div');
        label.textContent = item.label;
        label.title = item.label;
        Object.assign(label.style,{fontSize:'12px',textAlign:'center',width:'100%',overflowWrap:'anywhere'});
        const select = document.createElement('button');
        select.type = 'button';
        select.textContent = item.id === active ? '✓ Aktiv' : 'Auswählen';
        select.disabled = item.id === active; // run() blocks concurrent actions; refresh during busy must not lock all buttons
        select.addEventListener('click', () => run(async () => {
          const response = await request(root.dataset.selectUrl, data({id:item.id}));
          // Respond to the confirmed server selection; do not silently overwrite
          // an error with the old visual selection.
          if (response.active !== item.id) throw new Error('Die Auswahl wurde vom Server nicht übernommen.');
          await refresh();
          if (active !== item.id) throw new Error('Auswahl konnte nicht bestätigt werden.');
          show(item.id === 'none' ? 'Kein Wasserzeichen ausgewählt.' : 'Wasserzeichen erfolgreich ausgewählt.');
        }));
        card.append(image,label,select);
        if (item.id !== 'none') {
          const remove = document.createElement('button');
          remove.type = 'button';
          remove.textContent = item.id === 'default' ? 'Aus Galerie entfernen' : 'Löschen';
          remove.addEventListener('click', () => {
            const question = item.id === 'default'
              ? '360°-Standardgrafik aus der Galerie ausblenden? Sie kann später wiederhergestellt werden.'
              : `Wasserzeichen „${item.label}“ wirklich löschen?`;
            if (!confirm(question)) return;
            run(async () => {
              await request(root.dataset.deleteUrl, data({id:item.id}));
              await refresh();
              show(item.id === 'default' ? 'Standardgrafik aus Galerie entfernt.' : 'Wasserzeichen gelöscht.');
            });
          });
          card.appendChild(remove);
        }
        if (item.id !== 'none') {
          const details = document.createElement('details');
          details.className = 'ppi-rule-details';
          const summary = document.createElement('summary');
          const assigned = folderRules.filter(r => r.imageId === item.id);
          summary.textContent = `Ordnerzuweisungen (${assigned.length})`;
          details.appendChild(summary);
          const list = document.createElement('ul');
          list.className = 'ppi-rule-list';
          if (!assigned.length) {
            const empty = document.createElement('li');
            empty.textContent = 'Noch keine Ordner zugewiesen.';
            list.appendChild(empty);
          }
          for (const rule of assigned) {
            const row = document.createElement('li');
            const path = document.createElement('div');
            path.textContent = `${rule.owner}: ${rule.path}`;
            const removeRule = document.createElement('button');
            removeRule.type = 'button';
            removeRule.textContent = 'Zuordnung entfernen';
            removeRule.addEventListener('click', () => run(async () => {
              await request(root.dataset.unassignFolderUrl, data({folderId:rule.folderId}));
              await refresh();
              show('Ordnerzuweisung entfernt.');
            }));
            row.append(path, removeRule);
            list.appendChild(row);
          }
          details.appendChild(list);
          const pick = document.createElement('button');
          pick.type = 'button';
          pick.textContent = '📁 Ordner auswählen';
          pick.addEventListener('click', () => openFolderPicker(item.id));
          details.appendChild(pick);
          card.appendChild(details);
        }
        gallery.appendChild(card);
      }
    }
    async function refresh() {
      const result = await request(root.dataset.listUrl);
      revision = result.revision;
      folderRules = Array.isArray(result.folderRules) ? result.folderRules : [];
      active = result.active;
      renderOptions(result.options);
      renderGallery(result.images);
      restoreDefault.style.display = result.defaultHidden ? '' : 'none';
      $('watermark-form').querySelector('button').disabled = result.count >= result.maxUploads;
      fileInput.disabled = result.count >= result.maxUploads;
      if (result.count >= result.maxUploads) {
        show('Fünf Wasserzeichen gespeichert. Zum Hochladen zuerst eines löschen.');
      }
    }
    async function run(action) {
      if (busy) return;
      busy = true;
      save.disabled = true;
      restoreDefault.disabled = true;
      show('Bitte warten …');
      try { await action(); }
      catch (error) { show('Fehler: ' + error.message, true); }
      finally {
        busy = false;
        save.disabled = false;
        restoreDefault.disabled = false;
      }
    }
    save.addEventListener('click', () => {
      const options = {enabled: enabled.checked, mode: getMode()};
      for (const key of fields) options[key] = Number($('wm-' + key).value);
      run(async () => {
        await request(root.dataset.optionsUrl, data({options:JSON.stringify(options)}));
        show('Einstellungen gespeichert.');
      });
    });
    form.addEventListener('submit', event => {
      event.preventDefault();
      const file = fileInput.files?.[0];
      if (!file) return;
      if (file.size > 2097152 || !file.name.toLowerCase().endsWith('.png')) {
        show('Bitte PNG-Datei bis 2 MB wählen.', true);return;
      }
      const body = new FormData();
      body.append('watermark', file);
      run(async () => {
        await request(root.dataset.uploadUrl, body);
        form.reset();
        await refresh();
        show('PNG hochgeladen und ausgewählt.');
      });
    });
    restoreDefault.addEventListener('click', () => run(async () => {
      await request(root.dataset.restoreDefaultUrl, new FormData());
      await refresh();
      show('360°-Standardgrafik wieder in der Galerie sichtbar.');
    }));
    refresh().catch(error => show('Einstellungen konnten nicht geladen werden: ' + error.message, true));
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
