(function () {
    'use strict';
    function init() {
        const panel = document.getElementById('ppi-tour-navigation-panel');
        if (!panel || panel.dataset.initialized === '1') return;
        panel.dataset.initialized = '1';
        const toggle = document.getElementById('ppi-tour-navigation-enabled');
        const save = document.getElementById('ppi-tour-navigation-save');
        const feedback = document.getElementById('ppi-tour-navigation-feedback');
        const token = () => window.OC?.requestToken
            || document.querySelector('head[data-requesttoken]')?.getAttribute('data-requesttoken')
            || document.querySelector('meta[name="requesttoken"]')?.getAttribute('content') || '';
        const request = async (url, data) => {
            const headers = {'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest'};
            if (data !== undefined) {
                headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
                headers.requesttoken = token();
            }
            const response = await fetch(url, {
                method: data === undefined ? 'GET' : 'POST',
                credentials: 'same-origin', headers,
                ...(data === undefined ? {} : {body: new URLSearchParams(data).toString()}),
            });
            let payload;
            try { payload = await response.json(); }
            catch (_) { throw new Error('Ungültige Serverantwort (HTTP ' + response.status + ')'); }
            if (!response.ok) throw new Error(payload?.message || 'HTTP ' + response.status);
            if (typeof payload?.enabled !== 'boolean') throw new Error('Unerwartete Serverantwort');
            return payload;
        };
        request(panel.dataset.statusUrl).then(data => {
            toggle.checked = data.enabled;
            feedback.textContent = '';
        }).catch(err => {
            feedback.textContent = 'Aktueller Menüstatus konnte nicht geladen werden: ' + err.message;
        });
        save.addEventListener('click', async () => {
            save.disabled = true;
            feedback.textContent = 'Einstellung wird gespeichert …';
            try {
                const state = await request(panel.dataset.saveUrl, {enabled: toggle.checked ? 'yes' : 'no'});
                toggle.checked = state.enabled;
                feedback.textContent = 'Gespeichert. Das Nextcloud-App-Menü nach dem Neuladen aktualisieren.';
            } catch (err) {
                feedback.textContent = 'Speichern fehlgeschlagen: ' + err.message;
            } finally { save.disabled = false; }
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
