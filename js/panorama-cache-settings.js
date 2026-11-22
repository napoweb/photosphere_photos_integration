(function () {
    'use strict';
    function init() {
        const panel = document.getElementById('ppi-panorama-cache-panel');
        if (!panel || panel.dataset.initialized === '1') return;
        panel.dataset.initialized = '1';
        const toggle = document.getElementById('ppi-panorama-cache-enabled');
        const workers = document.getElementById('ppi-panorama-cache-workers');
        const button = document.getElementById('ppi-panorama-cache-save');
        const feedback = document.getElementById('ppi-panorama-cache-feedback');
        const request = async (url, body) => {
            const headers = {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'};
            if (body !== undefined) {
                headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
                headers['requesttoken'] = window.OC?.requestToken
                    || document.querySelector('head[data-requesttoken]')?.getAttribute('data-requesttoken')
                    || document.querySelector('meta[name="requesttoken"]')?.getAttribute('content')
                    || '';
            }
            const response = await fetch(url, {
                method: body === undefined ? 'GET' : 'POST',
                credentials: 'same-origin', headers,
                ...(body === undefined ? {} : {body: new URLSearchParams(body).toString()}),
            });
            let data;
            try { data = await response.json(); } catch (_) { throw new Error('Ungültige Serverantwort (HTTP ' + response.status + ')'); }
            if (!response.ok) throw new Error((data && data.message) || 'HTTP ' + response.status);
            if (typeof data?.enabled !== 'boolean' || !Number.isInteger(Number(data.workers))) {
                throw new Error('Unerwartete Einstellungsdaten');
            }
            return data;
        };
        // Never trap the control in a permanently disabled state when GET fails.
        // Saving still requires an authenticated admin request with CSRF token.
        request(panel.dataset.statusUrl).then(state => {
            toggle.checked = state.enabled;
            workers.value = String(state.workers);
            feedback.textContent = '';
        }).catch(error => {
            feedback.textContent = 'Aktuelle Einstellungen konnten nicht geladen werden: ' + error.message
                + '. Bitte die Verbindung prüfen; Speichern bleibt möglich.';
        });
        const statsButton = document.getElementById('ppi-panorama-cache-stats-refresh');
        const statsFeedback = document.getElementById('ppi-panorama-cache-stats-feedback');
        const statsContent = document.getElementById('ppi-panorama-cache-stats-content');
        const statsSummary = document.getElementById('ppi-panorama-cache-stats-summary');
        const statsRows = document.getElementById('ppi-panorama-cache-stats-rows');
        const statsFilter = document.getElementById('ppi-panorama-cache-stats-filter');
        const statsLimit = document.getElementById('ppi-panorama-cache-stats-limit');
        const statsPager = document.getElementById('ppi-cache-stats-pager');
        const statsPrev = document.getElementById('ppi-cache-stats-prev');
        const statsNext = document.getElementById('ppi-cache-stats-next');
        const statsPages = document.getElementById('ppi-cache-stats-pages');
        const statsRange = document.getElementById('ppi-cache-stats-pager-range');
        const PAGE_SIZE = 10;
        let statsPage = 0;
        // Resize columns independently by dragging header separators. Only widths
        // change: we never alter or reload the statistics data.
        const initializeResizableTable = () => {
            const table = document.getElementById('ppi-cache-stats-table');
            if (!table || table.dataset.resizable === '1') return;
            const columns = [...table.querySelectorAll('colgroup col')];
            const headers = [...table.querySelectorAll('thead th')];
            if (columns.length !== headers.length) return;
            table.dataset.resizable = '1';
            const widths = columns.map(col => parseFloat(col.style.width) || 120);
            const minimums = [70, 150, 150, 100, 75, 70, 105, 135];
            const renderWidths = () => {
                columns.forEach((col, index) => { col.style.width = widths[index] + 'px'; });
                const total = widths.reduce((sum, width) => sum + width, 0);
                table.style.width = total + 'px';
                table.style.minWidth = total + 'px';
            };
            renderWidths();
            const resize = (index, next) => {
                widths[index] = Math.max(minimums[index] || 80, Math.min(950, Math.round(next)));
                renderWidths();
            };
            headers.forEach((th, index) => {
                const handle = document.createElement('span');
                handle.className = 'ppi-cache-resize-handle';
                handle.setAttribute('role', 'separator');
                handle.setAttribute('tabindex', '0');
                handle.setAttribute('aria-label', 'Spaltenbreite für ' + th.textContent.trim() + ' anpassen');
                handle.setAttribute('aria-orientation', 'vertical');
                handle.setAttribute('aria-valuemin', String(minimums[index] || 80));
                handle.setAttribute('aria-valuemax', '950');
                const announce = () => handle.setAttribute('aria-valuenow', String(widths[index]));
                announce();
                handle.addEventListener('pointerdown', event => {
                    if (event.button !== 0) return;
                    event.preventDefault();
                    const pointerX = event.clientX;
                    const initial = widths[index];
                    handle.setPointerCapture(event.pointerId);
                    const onMove = move => {
                        if (move.pointerId !== event.pointerId) return;
                        resize(index, initial + move.clientX - pointerX);
                        announce();
                    };
                    const onDone = () => {
                        handle.removeEventListener('pointermove', onMove);
                        handle.removeEventListener('pointerup', onDone);
                        handle.removeEventListener('pointercancel', onDone);
                    };
                    handle.addEventListener('pointermove', onMove);
                    handle.addEventListener('pointerup', onDone);
                    handle.addEventListener('pointercancel', onDone);
                });
                handle.addEventListener('keydown', event => {
                    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
                    event.preventDefault();
                    resize(index, widths[index] + (event.key === 'ArrowRight' ? 20 : -20));
                    announce();
                });
                th.appendChild(handle);
            });
        };
        initializeResizableTable();
        const fmt = bytes => new Intl.NumberFormat('de-DE', {maximumFractionDigits: 2}).format(bytes / 1048576) + ' MiB';
        const date = seconds => seconds ? new Date(seconds * 1000).toLocaleString('de-DE') : '–';
        const cell = (tr, value, kind = '') => {
            const td = document.createElement('td');
            if (kind === 'filename' || kind === 'tourname') {
                const span = document.createElement('span');
                span.className = kind === 'filename' ? 'ppi-cache-filename' : 'ppi-cache-tourname';
                span.textContent = String(value);
                span.title = String(value);
                td.appendChild(span);
            } else {
                td.textContent = String(value);
            }
            tr.appendChild(td);
        };
        let latestStats = null;
        const renderStats = () => {
            if (!latestStats) return;
            const data = latestStats;
            statsSummary.replaceChildren();
            const metrics = [
                ['Cache gesamt', fmt(data.totalBytes)],
                ['Dateien aktuell in Touren', data.usedPanoramas + ' Panoramen · ' + fmt(data.usedBytes)],
                ['Nicht mehr verwendet', data.unusedPanoramas + ' Panoramen · ' + fmt(data.unusedBytes)],
                ['Vorgemerkt', data.markedPanoramas + ' Panoramen · ' + fmt(data.markedBytes)],
                ['Tour-Dateireferenzen insgesamt', data.tourReferences],
                ['Frühester Ablauf', date(data.earliestExpiry)],
            ];
            for (const [label, value] of metrics) {
                const box = document.createElement('div');
                box.style.cssText = 'border:1px solid var(--color-border, #ddd);border-radius:8px;padding:10px;min-width:145px;flex:1 1 145px;';
                const heading = document.createElement('strong');
                heading.textContent = label;
                const number = document.createElement('div');
                number.textContent = String(value);
                number.style.marginTop = '5px';
                box.append(heading, number);
                statsSummary.appendChild(box);
            }
            const filter = statsFilter.value;
            const rows = data.entries.filter(row => filter === 'all' ||
                (filter === 'used' && row.used) ||
                (filter === 'unused' && !row.used) ||
                (filter === 'marked' && !row.used && row.markedSince !== null));
            // Keep the summary global, but only render 10 matching rows at a time.
            const pageCount = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
            statsPage = Math.min(statsPage, pageCount - 1);
            const pageStart = statsPage * PAGE_SIZE;
            const pageRows = rows.slice(pageStart, pageStart + PAGE_SIZE);
            statsRows.replaceChildren();
            if (!rows.length) {
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.colSpan = 8;
                td.textContent = 'Keine Dateien für diesen Filter.';
                tr.appendChild(td);
                statsRows.appendChild(tr);
            }
            for (const row of pageRows) {
                const tr = document.createElement('tr');
                const visibility = row.visibility || 'unknown';
                cell(tr, visibility === 'own' ? row.id : '–');
                cell(tr, visibility === 'own' ? (row.name || 'Dateiname nicht hinterlegt')
                    : visibility === 'other' ? '🔒 Datei eines anderen Benutzers'
                    : '🔒 Besitzer nicht ermittelbar', 'filename');
                cell(tr, visibility === 'own' && Array.isArray(row.tourNames) && row.tourNames.length
                    ? row.tourNames.join(', ')
                    : visibility === 'other' ? '🔒 Andere Tour (geschützt)'
                    : row.used ? 'Tourname nicht hinterlegt' : '–', 'tourname');
                cell(tr, row.used ? 'Verwendet' : row.markedSince !== null ? 'Vorgemerkt' : 'Nicht verwendet');
                cell(tr, row.desktop ? 'Ja' : 'Nein');
                cell(tr, row.mobile ? 'Ja' : 'Nein');
                cell(tr, fmt(row.bytes));
                cell(tr, date(row.eligibleAfter));
                statsRows.appendChild(tr);
            }
            if (statsPager && statsPrev && statsNext && statsPages && statsRange) {
                statsPager.hidden = pageCount <= 1;
                statsPrev.disabled = statsPage === 0;
                statsNext.disabled = statsPage >= pageCount - 1;
                statsRange.textContent = rows.length
                    ? 'Einträge ' + (pageStart + 1) + '–' + (pageStart + pageRows.length) + ' von ' + rows.length
                    : 'Keine passenden Einträge';
                statsPages.replaceChildren();
                // Show at most five page tabs for large caches; arrows reach every page.
                const firstPage = Math.max(0, Math.min(statsPage - 2, pageCount - 5));
                for (let page = firstPage; page < Math.min(firstPage + 5, pageCount); page++) {
                    const tab = document.createElement('button');
                    tab.type = 'button';
                    tab.textContent = String(page + 1);
                    tab.setAttribute('aria-label', 'Seite ' + (page + 1) + ' von ' + pageCount);
                    if (page === statsPage) tab.setAttribute('aria-current', 'page');
                    tab.addEventListener('click', () => {
                        statsPage = page;
                        renderStats();
                    });
                    statsPages.appendChild(tab);
                }
            }
            statsLimit.textContent = data.cachedPanoramas > data.entries.length
                ? 'Zur Schonung des Servers werden maximal ' + data.shownLimit + ' Einträge angezeigt. Die Summen umfassen alle erkannten Cache-Dateien.'
                : 'Cache-Dateien: ' + data.cachedFiles + ' · Cache-Panoramen: ' + data.cachedPanoramas + '. Die Anzeige ist rein lesend.';
        };
        const loadStats = async () => {
            statsButton.disabled = true;
            statsFeedback.textContent = 'Cache-Statistik wird ermittelt …';
            try {
                const response = await fetch(panel.dataset.statisticsUrl, {
                    credentials: 'same-origin', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data?.message || 'HTTP ' + response.status);
                if (!Array.isArray(data.entries) || typeof data.usedPanoramas !== 'number') {
                    throw new Error('Unerwartete Statistikdaten');
                }
                latestStats = data;
                statsContent.hidden = false;
                statsFeedback.textContent = 'Statistik erfolgreich aktualisiert.';
                renderStats();
            } catch (error) {
                statsFeedback.textContent = 'Statistik nicht verfügbar: ' + error.message;
            } finally {
                statsButton.disabled = false;
            }
        };
        statsButton?.addEventListener('click', loadStats);
        statsFilter?.addEventListener('change', () => {
            statsPage = 0; // Every filter starts at its first set of ten entries.
            renderStats();
        });
        statsPrev?.addEventListener('click', () => {
            if (statsPage <= 0) return;
            statsPage--;
            renderStats();
        });
        statsNext?.addEventListener('click', () => {
            statsPage++;
            renderStats(); // Clamps at the last available page.
        });
        if (statsButton && panel.dataset.statisticsUrl) loadStats();
        button.addEventListener('click', async () => {
            button.disabled = true;
            feedback.textContent = 'Speichere …';
            try {
                const state = await request(panel.dataset.saveUrl, {
                    enabled: toggle.checked ? 'yes' : 'no',
                    workers: workers.value,
                });
                toggle.checked = state.enabled;
                workers.value = String(state.workers);
                feedback.textContent = (state.enabled ? 'Hintergrundoptimierung ist aktiviert.'
                    : 'Hintergrundoptimierung ist pausiert; bestehende Bilder bleiben verfügbar.')
                    + ' Parallele Berechnungen: ' + state.workers + '.';
            } catch (error) {
                feedback.textContent = 'Speichern fehlgeschlagen: ' + error.message;
            } finally {
                button.disabled = false;
            }
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
