(function() {
    var data = window.beSchemaAnalyserData || {};
    var strings = data.strings || {};
    var nonce = data.nonce || '';

    function t(key, fallback) {
        return strings[key] || fallback || '';
    }

    document.addEventListener('DOMContentLoaded', function() {
        var tabs = document.querySelectorAll('.be-schema-analyser-tab');
        var panels = document.querySelectorAll('.be-schema-analyser-panel');
        var current = data.defaultTab || 'overview';
        var runBtn = document.getElementById('be-schema-analyser-run');
        var stopBtn = document.getElementById('be-schema-analyser-stop');
        var urlInput = document.getElementById('be-schema-analyser-url');
        var siteSelect = document.getElementById('be-schema-analyser-site');
        var targetRadios = document.querySelectorAll('input[name="be-schema-analyser-target-mode"]');
        var limitInput = document.getElementById('be-schema-analyser-limit');
        var listPagesBtn = document.getElementById('be-schema-analyser-list-pages');
        var siteLimitInput = document.getElementById('be-schema-analyser-site-limit');
        var subpagesSelect = document.getElementById('be-schema-analyser-subpages');
        var envRadios = document.querySelectorAll('input[name="be-schema-analyser-env"]');
        var includePostsToggle = document.getElementById('be-schema-analyser-include-posts');
        var maxPostsInput = document.getElementById('be-schema-analyser-max-posts');
        var statusNode = document.getElementById('be-schema-analyser-status');
        var issuesList = document.getElementById('be-schema-issues-list');
        var sitesList = document.getElementById('be-schema-sites-list');
        var sitesAdd = document.getElementById('be-schema-sites-add');
        var sitesLabel = document.getElementById('be-schema-sites-label');
        var sitesUrl = document.getElementById('be-schema-sites-url');
        var historyList = document.getElementById('be-schema-history-list');
        var historyDelta = document.getElementById('be-schema-history-delta');
        var pauseBtn = document.getElementById('be-schema-analyser-pause');
        var resumeBtn = document.getElementById('be-schema-analyser-resume');
        var exportCsvBtn = document.getElementById('be-schema-analyser-export-csv');
        var exportJsonBtn = document.getElementById('be-schema-analyser-export-json');
        var currentNode = document.getElementById('be-schema-analyser-current');
        var elapsedNode = document.getElementById('be-schema-analyser-elapsed');
        var errorsNode = document.getElementById('be-schema-analyser-errors');

        var sitesStoreKey = 'be-schema-analyser-sites';
        var sites = [];
        var paused = false;
        var crawlTimer = null;
        var lastCrawlData = null;
        var isRunning = false;
        var isListing = false;
        var listReady = false;

        function activate(key) {
            tabs.forEach(function(tab) {
                tab.classList.toggle('active', tab.getAttribute('data-ana-tab') === key);
            });
            panels.forEach(function(panel) {
                panel.classList.toggle('active', panel.id === 'be-schema-analyser-' + key);
            });
        }

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                activate(tab.getAttribute('data-ana-tab'));
            });
        });
        activate(current);

        function renderIssues(dataPayload) {
            if (!issuesList) {
                return;
            }
            issuesList.innerHTML = '';
            if (!dataPayload || !dataPayload.summary || !dataPayload.summary.length) {
                var p = document.createElement('p');
                p.className = 'description';
                p.textContent = t('noIssues', 'No issues detected for this crawl.');
                issuesList.appendChild(p);
                return;
            }
            var table = document.createElement('table');
            table.className = 'be-schema-issues-table';
            var thead = document.createElement('thead');
            thead.innerHTML = '<tr><th>' + t('severity', 'Severity') + '</th><th>' + t('type', 'Type') + '</th><th>' + t('count', 'Count') + '</th><th>' + t('examplePage', 'Example Page') + '</th></tr>';
            table.appendChild(thead);
            var tbody = document.createElement('tbody');
            dataPayload.summary.forEach(function(item) {
                var tr = document.createElement('tr');
                var sev = document.createElement('td');
                var pill = document.createElement('span');
                pill.className = 'be-schema-pill ' + (item.severity || 'info');
                pill.textContent = item.severity || '';
                sev.appendChild(pill);
                var type = document.createElement('td');
                type.textContent = item.type || '';
                var count = document.createElement('td');
                count.textContent = item.count || 0;
                var page = document.createElement('td');
                page.textContent = (item.pages && item.pages.length) ? item.pages[0] : '';
                tr.appendChild(sev);
                tr.appendChild(type);
                tr.appendChild(count);
                tr.appendChild(page);
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            issuesList.appendChild(table);
        }

        function renderPages(pagesData) {
            var pagesNode = document.getElementById('be-schema-pages-list');
            if (!pagesNode) {
                return;
            }
            pagesNode.innerHTML = '';
            if (!pagesData || !Object.keys(pagesData).length) {
                var p = document.createElement('p');
                p.className = 'description';
                p.textContent = t('noPagesYet', 'No pages processed yet.');
                pagesNode.appendChild(p);
                return;
            }
            Object.keys(pagesData).forEach(function(url) {
                var issues = pagesData[url] || [];
                var card = document.createElement('div');
                card.className = 'be-schema-issues-list';
                var title = document.createElement('strong');
                title.textContent = url;
                card.appendChild(title);
                if (!issues.length) {
                    var none = document.createElement('p');
                    none.className = 'description';
                    none.textContent = t('noIssuesPage', 'No issues.');
                    card.appendChild(none);
                } else {
                    var list = document.createElement('ul');
                    issues.forEach(function(issue) {
                        var li = document.createElement('li');
                        li.textContent = '[' + (issue.severity || '').toUpperCase() + '] ' + (issue.type || '') + ': ' + (issue.message || '');
                        list.appendChild(li);
                    });
                    card.appendChild(list);
                }
                pagesNode.appendChild(card);
            });
        }

        function setStatus(text) {
            if (statusNode) {
                statusNode.textContent = text || '';
            }
        }

        function toggleExports(enabled) {
            if (exportCsvBtn) { exportCsvBtn.disabled = !enabled; }
            if (exportJsonBtn) { exportJsonBtn.disabled = !enabled; }
        }

        function formatElapsed(startSeconds) {
            if (!startSeconds) {
                return '';
            }
            var ms = Date.now() - (startSeconds * 1000);
            if (ms < 0) { ms = 0; }
            var totalSeconds = Math.floor(ms / 1000);
            var mins = Math.floor(totalSeconds / 60);
            var secs = totalSeconds % 60;
            var parts = [];
            if (mins > 0) { parts.push(mins + 'm'); }
            parts.push((secs < 10 && mins > 0 ? '0' : '') + secs + 's');
            return parts.join(' ');
        }

        function updateProgress(state, last) {
            if (currentNode) {
                currentNode.textContent = (last && last.url) ? t('currentLabel', 'Current:') + ' ' + last.url : '';
            }
            if (elapsedNode) {
                elapsedNode.textContent = (state && state.start) ? t('elapsedLabel', 'Elapsed:') + ' ' + formatElapsed(state.start) : '';
            }
            if (errorsNode) {
                errorsNode.textContent = (state && typeof state.errors !== 'undefined') ? t('errorsLabel', 'Errors:') + ' ' + state.errors : '';
            }
        }

        function loadHistory() {
            var form = new FormData();
            form.append('action', 'be_schema_analyser_history');
            form.append('nonce', nonce);
            fetch(ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                body: form
            }).then(function(resp){ return resp.json(); }).then(function(payload) {
                if (!payload || !payload.success) {
                    return;
                }
                renderHistory(payload.data.history || []);
            }).catch(function() {});
        }

        function renderHistory(history) {
            if (!historyList) {
                return;
            }
            historyList.innerHTML = '';
            if (!history || !history.length) {
                var p = document.createElement('p');
                p.className = 'description';
                p.textContent = t('noHistory', 'No history yet.');
                historyList.appendChild(p);
                return;
            }
            history.forEach(function(entry) {
                var card = document.createElement('div');
                card.className = 'be-schema-issues-list';
                var heading = document.createElement('strong');
                var date = new Date(entry.timestamp * 1000);
                heading.textContent = date.toLocaleString();
                card.appendChild(heading);
                var info = document.createElement('p');
                info.className = 'description';
                var processedText = t('processed', 'Processed') + ' ' + (entry.stats ? entry.stats.processed : 0);
                if (entry.stats && typeof entry.stats.errors !== 'undefined') {
                    processedText += ' · ' + t('errors', 'Errors') + ' ' + entry.stats.errors;
                }
                info.textContent = processedText;
                card.appendChild(info);
                if (entry.summary && entry.summary.length) {
                    var table = document.createElement('table');
                    table.className = 'be-schema-issues-table';
                    var thead = document.createElement('thead');
                    thead.innerHTML = '<tr><th>' + t('severity', 'Severity') + '</th><th>' + t('type', 'Type') + '</th><th>' + t('count', 'Count') + '</th></tr>';
                    table.appendChild(thead);
                    var tbody = document.createElement('tbody');
                    entry.summary.forEach(function(item) {
                        var tr = document.createElement('tr');
                        var sev = document.createElement('td');
                        var pill = document.createElement('span');
                        pill.className = 'be-schema-pill ' + (item.severity || 'info');
                        pill.textContent = item.severity || '';
                        sev.appendChild(pill);
                        var type = document.createElement('td');
                        type.textContent = item.type || '';
                        var count = document.createElement('td');
                        count.textContent = item.count || 0;
                        tr.appendChild(sev);
                        tr.appendChild(type);
                        tr.appendChild(count);
                        tbody.appendChild(tr);
                    });
                    table.appendChild(tbody);
                    card.appendChild(table);
                }
                historyList.appendChild(card);
            });
            if (historyDelta) {
                if (history.length < 2) {
                    historyDelta.innerHTML = '<p class="description">' + t('runTwo', 'Run two crawls to see deltas.') + '</p>';
                } else {
                    var latest = history[0].summary || [];
                    var prev   = history[1].summary || [];
                    var diff   = {};
                    prev.forEach(function(item) {
                        var key = (item.severity || '') + '|' + (item.type || '');
                        diff[key] = -1 * (item.count || 0);
                    });
                    latest.forEach(function(item) {
                        var key = (item.severity || '') + '|' + (item.type || '');
                        diff[key] = (diff[key] || 0) + (item.count || 0);
                    });
                    var table = document.createElement('table');
                    table.className = 'be-schema-issues-table';
                    var thead = document.createElement('thead');
                    thead.innerHTML = '<tr><th>' + t('severity', 'Severity') + '</th><th>' + t('type', 'Type') + '</th><th>' + t('count', 'Count') + '</th></tr>';
                    table.appendChild(thead);
                    var tbody = document.createElement('tbody');
                    Object.keys(diff).forEach(function(key) {
                        var parts = key.split('|');
                        var delta = diff[key];
                        var tr = document.createElement('tr');
                        var sev = document.createElement('td');
                        var pill = document.createElement('span');
                        pill.className = 'be-schema-pill ' + (parts[0] || 'info');
                        pill.textContent = parts[0] || '';
                        sev.appendChild(pill);
                        var type = document.createElement('td');
                        type.textContent = parts[1] || '';
                        var val = document.createElement('td');
                        val.textContent = delta;
                        tr.appendChild(sev);
                        tr.appendChild(type);
                        tr.appendChild(val);
                        tbody.appendChild(tr);
                    });
                    table.appendChild(tbody);
                    historyDelta.innerHTML = '';
                    historyDelta.appendChild(table);
                }
            }
        }

        function loadSites() {
            try {
                var raw = localStorage.getItem(sitesStoreKey);
                sites = raw ? JSON.parse(raw) : [];
                if (!Array.isArray(sites)) {
                    sites = [];
                }
            } catch (e) {
                sites = [];
            }
        }

        function saveSites() {
            try {
                localStorage.setItem(sitesStoreKey, JSON.stringify(sites));
            } catch (e) {}
        }

        function renderSites() {
            var hasList = !!sitesList;
            var hasSelect = !!siteSelect;
            if (!hasList && !hasSelect) {
                return;
            }
            if (hasList) {
                sitesList.innerHTML = '';
            }
            if (hasSelect) {
                siteSelect.innerHTML = '';
            }
            if (!sites.length) {
                if (hasList) {
                    var li = document.createElement('li');
                    li.textContent = t('noSavedSites', 'No saved websites yet.');
                    sitesList.appendChild(li);
                }
                if (hasSelect) {
                    var optHome = document.createElement('option');
                    optHome.value = data.homeUrl || '';
                    optHome.textContent = data.homeUrl || '';
                    siteSelect.appendChild(optHome);
                }
                return;
            }
            sites.forEach(function(site, idx) {
                if (hasList) {
                    var li = document.createElement('li');
                    li.textContent = site.label + ' — ' + site.url;
                    var btn = document.createElement('button');
                    btn.className = 'button button-secondary';
                    btn.style.marginLeft = '8px';
                    btn.textContent = t('remove', 'Remove');
                    btn.addEventListener('click', function() {
                        sites.splice(idx, 1);
                        saveSites();
                        renderSites();
                    });
                    li.appendChild(btn);
                    sitesList.appendChild(li);
                }

                if (hasSelect) {
                    var opt = document.createElement('option');
                    opt.value = site.url;
                    opt.textContent = site.label + ' (' + site.url + ')';
                    siteSelect.appendChild(opt);
                }
            });
        }

        function currentTargetUrl() {
            var mode = currentTargetMode();
            if (mode === 'manual') {
                return urlInput ? urlInput.value.trim() : '';
            }
            return siteSelect ? siteSelect.value.trim() : '';
        }

        function currentTargetMode() {
            var mode = 'site';
            targetRadios.forEach(function(r) { if (r.checked) { mode = r.value; } });
            return mode;
        }

        function isLocalSelected() {
            var mode = 'local';
            envRadios.forEach(function(radio) {
                if (radio.checked) {
                    mode = radio.value;
                }
            });
            return mode === 'local';
        }

        function normalizeUrl(url) {
            if (!url) {
                return '';
            }
            try {
                var parsed = new URL(url);
                var path = parsed.pathname || '/';
                if (path.length > 1 && path.charAt(path.length - 1) === '/') {
                    path = path.slice(0, -1);
                }
                var host = parsed.host ? parsed.host.toLowerCase() : '';
                return host + path;
            } catch (e) {
                return '';
            }
        }

        function isHomepageUrl(url) {
            if (!url) {
                return false;
            }
            try {
                var parsed = new URL(url);
                var path = parsed.pathname || '/';
                return path === '/' || path === '';
            } catch (e) {
                return false;
            }
        }

        function resetSubpages(disabled) {
            if (!subpagesSelect) {
                return;
            }
            listReady = false;
            subpagesSelect.innerHTML = '';
            var noneOption = document.createElement('option');
            noneOption.value = '';
            noneOption.textContent = t('subpagesNone', 'None');
            subpagesSelect.appendChild(noneOption);
            subpagesSelect.disabled = disabled !== false;
        }

        function populateSubpages(pages) {
            if (!subpagesSelect) {
                return;
            }
            resetSubpages(false);
            if (!pages || !pages.length) {
                return;
            }
            subpagesSelect.innerHTML = '';
            listReady = true;
            var homeEntry = pages.find(function(page) { return page && page.is_home; });
            var remaining = pages.filter(function(page) { return page && !page.is_home; });
            if (homeEntry) {
                var homeOption = document.createElement('option');
                homeOption.value = homeEntry.url || '';
                homeOption.textContent = homeEntry.label || t('subpagesHome', 'Home page');
                subpagesSelect.appendChild(homeOption);
            }
            if (remaining.length) {
                var divider = document.createElement('option');
                divider.textContent = t('subpagesDivider', '────────');
                divider.disabled = true;
                subpagesSelect.appendChild(divider);
                remaining.forEach(function(page) {
                    var opt = document.createElement('option');
                    opt.value = page.url || '';
                    opt.textContent = page.label || page.url || '';
                    subpagesSelect.appendChild(opt);
                });
            }
            subpagesSelect.disabled = false;
        }

        function updateTargetModeControls() {
            var mode = currentTargetMode();
            var allowList = false;
            var allowRun = false;
            if (mode === 'site') {
                allowList = true;
                allowRun = listReady && !isListing;
            } else {
                var manualUrl = urlInput ? urlInput.value.trim() : '';
                if (manualUrl && isHomepageUrl(manualUrl)) {
                    allowList = true;
                    allowRun = listReady && !isListing;
                } else {
                    allowRun = true;
                }
            }
            if (listPagesBtn) {
                listPagesBtn.disabled = !allowList || isListing;
            }
            if (siteLimitInput) {
                siteLimitInput.disabled = !allowList;
            }
                if (limitInput) {
                    limitInput.disabled = !allowRun;
                }
                if (runBtn) {
                    runBtn.disabled = !(allowRun && !isRunning);
                }
            if (!allowList) {
                resetSubpages(true);
            }
        }

        function pollStep() {
            if (paused) {
                return;
            }
            var form = new FormData();
            form.append('action', 'be_schema_analyser_step');
            form.append('nonce', nonce);
            fetch(ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                body: form
            }).then(function(resp){ return resp.json(); }).then(function(payload) {
                if (!payload || !payload.success) {
                    setStatus((payload && payload.data && payload.data.message) ? payload.data.message : t('crawlFailed', 'Crawl failed.'));
                    isRunning = false;
                    updateTargetModeControls();
                    if (stopBtn) { stopBtn.disabled = true; }
                    if (pauseBtn) { pauseBtn.disabled = true; }
                    if (resumeBtn) { resumeBtn.disabled = true; }
                    return;
                }
                if (payload.data) {
                    renderIssues(payload.data);
                    if (payload.data.pages) {
                        renderPages(payload.data.pages);
                    }
                    lastCrawlData = payload.data;
                    toggleExports(true);
                }
                var state = payload.data.state || {};
                var maxVal = (state.max || state.processed || 0);
                var processedVal = state.processed || 0;
                var statusText = t('processed', 'Processed') + ' ' + processedVal;
                if (maxVal) {
                    statusText += ' / ' + maxVal;
                }
                statusText += ' · ' + (state.queued || 0) + ' ' + t('queued', 'queued');
                setStatus(statusText);
                updateProgress(state, payload.data.last);
                var done = payload.data.done;
                if (paused) {
                    crawlTimer = null;
                    return;
                }
                if (done) {
                    isRunning = false;
                    updateTargetModeControls();
                    if (stopBtn) { stopBtn.disabled = true; }
                    if (pauseBtn) { pauseBtn.disabled = true; }
                    if (resumeBtn) { resumeBtn.disabled = true; }
                    crawlTimer = null;
                    loadHistory();
                } else {
                    crawlTimer = setTimeout(pollStep, 600);
                }
            }).catch(function() {
                setStatus(t('crawlFailed', 'Crawl failed.'));
                isRunning = false;
                updateTargetModeControls();
                if (stopBtn) { stopBtn.disabled = true; }
                if (pauseBtn) { pauseBtn.disabled = true; }
                if (resumeBtn) { resumeBtn.disabled = true; }
                crawlTimer = null;
            });
        }

        if (runBtn && urlInput) {
            runBtn.addEventListener('click', function() {
                var url = currentTargetUrl();
                var limit = limitInput ? parseInt(limitInput.value, 10) : 10;
                if (!url) {
                    setStatus(t('enterUrl', 'Enter a URL to analyse.'));
                    return;
                }
                isRunning = true;
                updateTargetModeControls();
                paused = false;
                lastCrawlData = null;
                toggleExports(false);
                if (currentNode) { currentNode.textContent = ''; }
                if (elapsedNode) { elapsedNode.textContent = ''; }
                if (errorsNode) { errorsNode.textContent = ''; }
                setStatus(t('starting', 'Starting…'));
                if (stopBtn) { stopBtn.disabled = false; }
                if (pauseBtn) { pauseBtn.disabled = false; }
                if (resumeBtn) { resumeBtn.disabled = true; }
                var form = new FormData();
                form.append('action', 'be_schema_analyser_start');
                form.append('nonce', nonce);
                form.append('url', url);
                form.append('local', isLocalSelected() ? '1' : '0');
                form.append('max', isNaN(limit) ? 10 : limit);
                fetch(ajaxurl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: form
                }).then(function(resp){ return resp.json(); }).then(function(payload) {
                    if (!payload || !payload.success) {
                        setStatus((payload && payload.data && payload.data.message) ? payload.data.message : t('analysisFailed', 'Analysis failed.'));
                        isRunning = false;
                        updateTargetModeControls();
                        if (stopBtn) { stopBtn.disabled = true; }
                        if (pauseBtn) { pauseBtn.disabled = true; }
                        if (resumeBtn) { resumeBtn.disabled = true; }
                        return;
                    }
                    setStatus(t('crawlStarted', 'Crawl started…'));
                    if (stopBtn) { stopBtn.disabled = false; }
                    if (pauseBtn) { pauseBtn.disabled = false; }
                    if (resumeBtn) { resumeBtn.disabled = true; }
                    pollStep();
                    loadHistory();
                }).catch(function() {
                    isRunning = false;
                    updateTargetModeControls();
                    setStatus(t('analysisFailed', 'Analysis failed.'));
                    if (stopBtn) { stopBtn.disabled = true; }
                    if (pauseBtn) { pauseBtn.disabled = true; }
                    if (resumeBtn) { resumeBtn.disabled = true; }
                });
            });
        }

        if (stopBtn) {
            stopBtn.addEventListener('click', function() {
                var form = new FormData();
                form.append('action', 'be_schema_analyser_stop');
                form.append('nonce', nonce);
                fetch(ajaxurl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: form
                }).finally(function() {
                    if (crawlTimer) {
                        clearTimeout(crawlTimer);
                        crawlTimer = null;
                    }
                    paused = false;
                    setStatus(t('crawlStopped', 'Crawl stopped.'));
                    isRunning = false;
                    updateTargetModeControls();
                    stopBtn.disabled = true;
                    if (pauseBtn) { pauseBtn.disabled = true; }
                    if (resumeBtn) { resumeBtn.disabled = true; }
                    loadHistory();
                });
            });
        }

        if (listPagesBtn) {
            listPagesBtn.addEventListener('click', function() {
                if (listPagesBtn.disabled) {
                    return;
                }
                var url = currentTargetUrl();
                if (!url) {
                    setStatus(t('enterUrl', 'Enter a URL to analyse.'));
                    return;
                }
                if (currentTargetMode() === 'manual' && !isHomepageUrl(url)) {
                    setStatus(t('listFailed', 'Failed to list pages.'));
                    resetSubpages(true);
                    return;
                }
                var max = siteLimitInput ? parseInt(siteLimitInput.value, 10) : 25;
                max = isNaN(max) ? 25 : max;
                isListing = true;
                listReady = false;
                updateTargetModeControls();
                setStatus(t('listingPages', 'Listing sitemap pages…'));
                resetSubpages(true);
                var form = new FormData();
                form.append('action', 'be_schema_analyser_list_pages');
                form.append('nonce', nonce);
                form.append('url', url);
                form.append('local', isLocalSelected() ? '1' : '0');
                form.append('max', max);
                fetch(ajaxurl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: form
                }).then(function(resp){ return resp.json(); }).then(function(payload) {
                    isListing = false;
                    updateTargetModeControls();
                    if (!payload || !payload.success) {
                        setStatus((payload && payload.data && payload.data.message) ? payload.data.message : t('listFailed', 'Failed to list pages.'));
                        resetSubpages(true);
                        listReady = false;
                        updateTargetModeControls();
                        return;
                    }
                    var pages = (payload.data && payload.data.pages) ? payload.data.pages : [];
                    if (!pages.length) {
                        setStatus(t('listNoPages', 'No sitemap pages found.'));
                        resetSubpages(true);
                        listReady = false;
                        updateTargetModeControls();
                        return;
                    }
                    populateSubpages(pages);
                    setStatus(t('listReady', 'Pages loaded.'));
                    updateTargetModeControls();
                }).catch(function() {
                    isListing = false;
                    updateTargetModeControls();
                    setStatus(t('listFailed', 'Failed to list pages.'));
                    resetSubpages(true);
                });
            });
        }

        if (pauseBtn) {
            pauseBtn.addEventListener('click', function() {
                paused = true;
                if (crawlTimer) {
                    clearTimeout(crawlTimer);
                    crawlTimer = null;
                }
                pauseBtn.disabled = true;
                if (resumeBtn) { resumeBtn.disabled = false; }
                setStatus(t('crawlPaused', 'Crawl paused.'));
            });
        }

        if (resumeBtn) {
            resumeBtn.addEventListener('click', function() {
                paused = false;
                resumeBtn.disabled = true;
                if (pauseBtn) { pauseBtn.disabled = false; }
                setStatus(t('resuming', 'Resuming…'));
                pollStep();
            });
        }

        function downloadBlob(filename, content, type) {
            var blob = new Blob([content], { type: type || 'text/plain' });
            var link = document.createElement('a');
            var urlObj = URL.createObjectURL(blob);
            link.href = urlObj;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(urlObj);
        }

        if (exportJsonBtn) {
            exportJsonBtn.addEventListener('click', function() {
                if (!lastCrawlData) {
                    return;
                }
                downloadBlob('beseo-analyser.json', JSON.stringify(lastCrawlData, null, 2), 'application/json');
            });
        }

        if (exportCsvBtn) {
            exportCsvBtn.addEventListener('click', function() {
                if (!lastCrawlData || !lastCrawlData.pages) {
                    return;
                }
                var rows = [['URL','Severity','Type','Message']];
                Object.keys(lastCrawlData.pages).forEach(function(url) {
                    (lastCrawlData.pages[url] || []).forEach(function(issue) {
                        rows.push([
                            url,
                            issue.severity || '',
                            issue.type || '',
                            (issue.message || '').replace(/\s+/g, ' ')
                        ]);
                    });
                });
                var csv = rows.map(function(r){ return r.map(function(col){
                    var val = ('' + col).replace(/\"/g, '\"\"');
                    if (val.search(/[\",\\n]/) >= 0) {
                        val = '\"' + val + '\"';
                    }
                    return val;
                }).join(','); }).join('\n');
                downloadBlob('beseo-analyser.csv', csv, 'text/csv');
            });
        }

        if (sitesAdd && sitesLabel && sitesUrl) {
            sitesAdd.addEventListener('click', function() {
                var label = sitesLabel.value.trim();
                var url = sitesUrl.value.trim();
                if (!label || !url) {
                    setStatus(t('enterLabelUrl', 'Enter a label and URL.'));
                    return;
                }
                if (!/^https?:\/\//i.test(url)) {
                    setStatus(t('useHttp', 'Use http/https URLs only.'));
                    return;
                }
                sites.push({ label: label, url: url });
                saveSites();
                renderSites();
                sitesLabel.value = '';
                sitesUrl.value = '';
                setStatus(t('websiteSaved', 'Website saved.'));
            });
        }

        targetRadios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                var mode = radio.value;
                var useManual = (mode === 'manual');
                if (urlInput) {
                    urlInput.style.display = useManual ? 'inline-block' : 'none';
                    urlInput.disabled = !useManual;
                }
                if (siteSelect) {
                    siteSelect.style.display = useManual ? 'none' : 'inline-block';
                    siteSelect.disabled = useManual;
                }
                if (mode === 'manual' && siteSelect) {
                    siteSelect.value = '';
                }
                resetSubpages(true);
                updateTargetModeControls();
            });
        });

        loadSites();
        renderSites();
        loadHistory();
        updateTargetModeControls();

        if (urlInput) {
            urlInput.addEventListener('input', function() {
                resetSubpages(true);
                updateTargetModeControls();
            });
            urlInput.addEventListener('change', function() {
                resetSubpages(true);
                updateTargetModeControls();
            });
        }
        if (envRadios && envRadios.length) {
            envRadios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    resetSubpages(true);
                    updateTargetModeControls();
                });
            });
        }
        if (siteSelect) {
            siteSelect.addEventListener('change', function() {
                resetSubpages(true);
                updateTargetModeControls();
            });
        }
        if (includePostsToggle && maxPostsInput) {
            includePostsToggle.addEventListener('change', function() {
                maxPostsInput.disabled = !includePostsToggle.checked;
            });
            maxPostsInput.disabled = !includePostsToggle.checked;
        }
    });
})();
