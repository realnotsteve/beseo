<?php
/**
 * Playfair capture script (shared).
 */
?>
<script>
    (function() {
        var messages = {
            selectTarget: '<?php echo esc_js( __( 'Select a target in the selector above.', 'beseo' ) ); ?>',
            running: '<?php echo esc_js( __( 'Running capture…', 'beseo' ) ); ?>',
            complete: '<?php echo esc_js( __( 'Capture complete.', 'beseo' ) ); ?>',
            completeFallback: '<?php echo esc_js( __( 'Capture complete (remote failed, used local).', 'beseo' ) ); ?>',
            failed: '<?php echo esc_js( __( 'Capture failed.', 'beseo' ) ); ?>',
            requestFailed: '<?php echo esc_js( __( 'Capture request failed.', 'beseo' ) ); ?>',
            targetLabel: '<?php echo esc_js( __( 'Selected target:', 'beseo' ) ); ?>'
        };

        function toArray(list) {
            return Array.prototype.slice.call(list || []);
        }

        function getRadioValue(inputs) {
            var value = '';
            toArray(inputs).forEach(function (input) {
                if (input.checked) {
                    value = input.value;
                }
            });
            return value;
        }

        function escapeHtml(text) {
            return (text || '').toString().replace(/[&<>"']/g, function (char) {
                switch (char) {
                    case '&':
                        return '&amp;';
                    case '<':
                        return '&lt;';
                    case '>':
                        return '&gt;';
                    case '"':
                        return '&quot;';
                    case "'":
                        return '&#39;';
                    default:
                        return char;
                }
            });
        }

        function setPre(el, data) {
            if (!el) {
                return;
            }
            if (!data || (Array.isArray(data) && !data.length)) {
                el.textContent = '';
                return;
            }
            if (typeof data === 'string') {
                el.textContent = data;
                return;
            }
            el.textContent = JSON.stringify(data, null, 2);
        }

        function countList(list) {
            return Array.isArray(list) ? list.length : 0;
        }

        function shouldForceHttp(hostname) {
            if (!hostname) {
                return false;
            }
            if (hostname === 'localhost' || hostname.slice(-6) === '.local') {
                return true;
            }
            if (!/^\d+\.\d+\.\d+\.\d+$/.test(hostname)) {
                return false;
            }
            var parts = hostname.split('.').map(function (part) { return parseInt(part, 10); });
            if (parts[0] === 10 || parts[0] === 127) {
                return true;
            }
            if (parts[0] === 192 && parts[1] === 168) {
                return true;
            }
            if (parts[0] === 172 && parts[1] >= 16 && parts[1] <= 31) {
                return true;
            }
            return false;
        }

        function mapToLocalTarget(value, homeUrl) {
            if (!value || !homeUrl) {
                return value;
            }
            if (/^\d+$/.test(value)) {
                return value;
            }
            try {
                var parsed = new URL(value);
                var local = new URL(homeUrl);
                var localOrigin = local.origin;
                if (local.protocol === 'https:' && shouldForceHttp(local.hostname)) {
                    localOrigin = 'http://' + local.host;
                }
                if (parsed.host === local.host) {
                    return localOrigin + (parsed.pathname || '/') + (parsed.search || '') + (parsed.hash || '');
                }
                return localOrigin + (parsed.pathname || '/') + (parsed.search || '') + (parsed.hash || '');
            } catch (err) {
                return value;
            }
        }

        function resolveSchemaTarget(homeUrl) {
            var envInputs = document.querySelectorAll('input[name="be-schema-preview-env"]');
            var targetModeInputs = document.querySelectorAll('input[name="be-schema-preview-target-mode"]');
            var manualInput = document.getElementById('be-schema-preview-target');
            var siteSelect = document.getElementById('be-schema-preview-site');
            var subpagesSelect = document.getElementById('be-schema-preview-subpages');
            var targetMode = getRadioValue(targetModeInputs) || 'site';
            var target = '';
            if (subpagesSelect && !subpagesSelect.disabled && subpagesSelect.value) {
                target = subpagesSelect.value.trim();
            } else if (targetMode === 'manual') {
                target = manualInput ? manualInput.value.trim() : '';
            } else {
                target = siteSelect ? siteSelect.value.trim() : '';
            }
            var env = getRadioValue(envInputs) || 'remote';
            if (env === 'local') {
                target = mapToLocalTarget(target, homeUrl);
            }
            return {
                target: target,
                mode: env === 'local' ? 'local' : 'remote',
                local: env === 'local'
            };
        }

        function resolveValidatorTarget(homeUrl) {
            var envInputs = document.querySelectorAll('input[name="be-schema-validator-env"]');
            var targetModeInputs = document.querySelectorAll('input[name="be_schema_validator_mode"]');
            var manualInput = document.getElementById('be-schema-validator-manual');
            var siteSelect = document.getElementById('be-schema-validator-site');
            var subpagesSelect = document.getElementById('be-schema-validator-subpages');
            var targetMode = getRadioValue(targetModeInputs) || 'site';
            var target = '';
            if (subpagesSelect && !subpagesSelect.disabled && subpagesSelect.value) {
                target = subpagesSelect.value.trim();
            } else if (targetMode === 'manual') {
                target = manualInput ? manualInput.value.trim() : '';
            } else {
                target = siteSelect ? siteSelect.value.trim() : '';
            }
            var env = getRadioValue(envInputs) || 'remote';
            if (env === 'local') {
                target = mapToLocalTarget(target, homeUrl);
            }
            return {
                target: target,
                mode: env === 'local' ? 'local' : 'remote',
                local: env === 'local'
            };
        }

        function extractMetaKey(raw) {
            if (!raw) {
                return '';
            }
            var value = raw.toString();
            var match = value.match(/\\b(?:property|name)\\s*=\\s*["']([^"']+)["']/i);
            if (match && match[1]) {
                return match[1].toLowerCase();
            }
            return value.toLowerCase();
        }

        function getEntryKey(entry) {
            if (!entry) {
                return '';
            }
            if (typeof entry === 'string') {
                return extractMetaKey(entry);
            }
            if (typeof entry === 'object') {
                var key = entry.key || entry.property || entry.name || entry.meta || '';
                if (key) {
                    return String(key).toLowerCase();
                }
                if (entry.raw) {
                    return extractMetaKey(entry.raw);
                }
            }
            return '';
        }

        function splitOpengraph(entries) {
            var buckets = { og: [], twitter: [], other: [] };
            if (!Array.isArray(entries)) {
                return buckets;
            }
            entries.forEach(function (entry) {
                var key = getEntryKey(entry);
                if (key.indexOf('twitter:') === 0) {
                    buckets.twitter.push(entry);
                } else if (key.indexOf('og:') === 0 || key.indexOf('article:') === 0 || key.indexOf('fb:') === 0) {
                    buckets.og.push(entry);
                } else {
                    buckets.other.push(entry);
                }
            });
            return buckets;
        }

        function initCapture(container) {
            var context = container.getAttribute('data-playfair-context') || 'schema';
            var selector = container.getAttribute('data-playfair-selector') || context;
            var ajaxUrl = container.getAttribute('data-playfair-ajax') || (window.ajaxurl || '');
            var nonce = container.getAttribute('data-playfair-nonce') || '';
            var homeUrl = container.getAttribute('data-playfair-home') || '';

            var runBtn = container.querySelector('[data-playfair-role="run"]');
            var statusEl = container.querySelector('[data-playfair-role="status"]');
            var metaEl = container.querySelector('[data-playfair-role="meta"]');
            var targetEl = container.querySelector('[data-playfair-role="target"]');
            var resultsEl = container.querySelector('[data-playfair-role="results"]');
            var htmlWrap = container.querySelector('[data-playfair-role="html"]');
            var logsWrap = container.querySelector('[data-playfair-role="logs"]');

            var profileSelect = container.querySelector('[data-playfair-role="profile"]');
            var waitInput = container.querySelector('[data-playfair-role="wait"]');
            var localeInput = container.querySelector('[data-playfair-role="locale"]');
            var timezoneInput = container.querySelector('[data-playfair-role="timezone"]');
            var includeHtmlInput = container.querySelector('[data-playfair-role="include-html"]');
            var includeLogsInput = container.querySelector('[data-playfair-role="include-logs"]');

            var schemaDom = container.querySelector('[data-playfair-role="schema-dom"]');
            var schemaServer = container.querySelector('[data-playfair-role="schema-server"]');
            var ogDom = container.querySelector('[data-playfair-role="og-dom"]');
            var ogServer = container.querySelector('[data-playfair-role="og-server"]');
            var twitterDom = container.querySelector('[data-playfair-role="twitter-dom"]');
            var twitterServer = container.querySelector('[data-playfair-role="twitter-server"]');
            var htmlDom = container.querySelector('[data-playfair-role="html-dom"]');
            var htmlServer = container.querySelector('[data-playfair-role="html-server"]');
            var logsConsole = container.querySelector('[data-playfair-role="logs-console"]');
            var logsPageErrors = container.querySelector('[data-playfair-role="logs-pageerrors"]');
            var logsRequestFailed = container.querySelector('[data-playfair-role="logs-requestfailed"]');

            if (!runBtn) {
                return;
            }

            function setStatus(message, type) {
                if (!statusEl) {
                    return;
                }
                statusEl.textContent = message || '';
                statusEl.className = 'be-schema-playfair-status';
                if (message) {
                    statusEl.classList.add('is-active');
                    if (type) {
                        statusEl.classList.add(type);
                    }
                }
            }

            function clearResults() {
                if (resultsEl) {
                    resultsEl.style.display = 'none';
                }
                if (metaEl) {
                    metaEl.innerHTML = '';
                }
                if (htmlWrap) {
                    htmlWrap.style.display = 'none';
                }
                setPre(schemaDom, '');
                setPre(schemaServer, '');
                setPre(ogDom, '');
                setPre(ogServer, '');
                setPre(twitterDom, '');
                setPre(twitterServer, '');
                setPre(htmlDom, '');
                setPre(htmlServer, '');
                setPre(logsConsole, '');
                setPre(logsPageErrors, '');
                setPre(logsRequestFailed, '');
            }

            function renderMeta(meta, payload, counts) {
                if (!metaEl) {
                    return;
                }
                var lines = [];
                if (meta && meta.target) {
                    lines.push('Target: ' + meta.target);
                }
                if (meta && meta.mode) {
                    lines.push('Mode: ' + meta.mode);
                }
                if (meta && meta.endpoint) {
                    lines.push('Endpoint: ' + meta.endpoint);
                }
                if (meta && meta.profile) {
                    lines.push('Profile: ' + meta.profile);
                }
                if (meta && typeof meta.wait_ms !== 'undefined') {
                    lines.push('Wait: ' + meta.wait_ms + 'ms');
                }
                if (meta && meta.fallback) {
                    lines.push('Fallback: remote failed' + (meta.fallback_error ? ' (' + meta.fallback_error + ')' : ''));
                }
                if (counts) {
                    if (typeof counts.schemaDom !== 'undefined') {
                        lines.push('Schema DOM: ' + counts.schemaDom);
                        lines.push('Schema Server: ' + counts.schemaServer);
                    }
                    if (typeof counts.ogDom !== 'undefined') {
                        lines.push('Open Graph DOM: ' + counts.ogDom);
                        lines.push('Open Graph Server: ' + counts.ogServer);
                    }
                    if (typeof counts.twitterDom !== 'undefined') {
                        lines.push('Twitter DOM: ' + counts.twitterDom);
                        lines.push('Twitter Server: ' + counts.twitterServer);
                    }
                }
                metaEl.innerHTML = lines.map(function (line) {
                    return '<div>' + escapeHtml(line) + '</div>';
                }).join('');
            }

            function renderResult(payload) {
                if (resultsEl) {
                    resultsEl.style.display = 'block';
                }

                if (context === 'schema') {
                    var schemaDomList = payload.schema ? payload.schema.dom : [];
                    var schemaServerList = payload.schema ? payload.schema.server : [];
                    setPre(schemaDom, schemaDomList);
                    setPre(schemaServer, schemaServerList);
                    renderMeta(payload.meta || {}, payload, {
                        schemaDom: countList(schemaDomList),
                        schemaServer: countList(schemaServerList)
                    });
                } else {
                    var domEntries = payload.opengraph ? payload.opengraph.dom : [];
                    var serverEntries = payload.opengraph ? payload.opengraph.server : [];
                    var domSplit = splitOpengraph(domEntries);
                    var serverSplit = splitOpengraph(serverEntries);
                    setPre(ogDom, domSplit.og);
                    setPre(ogServer, serverSplit.og);
                    setPre(twitterDom, domSplit.twitter);
                    setPre(twitterServer, serverSplit.twitter);
                    renderMeta(payload.meta || {}, payload, {
                        ogDom: countList(domSplit.og),
                        ogServer: countList(serverSplit.og),
                        twitterDom: countList(domSplit.twitter),
                        twitterServer: countList(serverSplit.twitter)
                    });
                }

                if (payload.logs) {
                    setPre(logsConsole, payload.logs.console || null);
                    setPre(logsPageErrors, payload.logs.pageErrors || null);
                    setPre(logsRequestFailed, payload.logs.requestFailed || null);
                    if (logsWrap) {
                        logsWrap.style.display = 'grid';
                    }
                } else if (logsWrap) {
                    logsWrap.style.display = 'none';
                }

                if (payload.html && (payload.html.dom || payload.html.server)) {
                    if (htmlWrap) {
                        htmlWrap.style.display = 'grid';
                    }
                    setPre(htmlDom, payload.html.dom || '');
                    setPre(htmlServer, payload.html.server || '');
                } else if (htmlWrap) {
                    htmlWrap.style.display = 'none';
                }
            }

            function resolveTarget() {
                if (selector === 'validator') {
                    return resolveValidatorTarget(homeUrl);
                }
                return resolveSchemaTarget(homeUrl);
            }

            function updateTargetDisplay() {
                if (!targetEl) {
                    return;
                }
                var resolved = resolveTarget();
                var text = resolved.target ? (messages.targetLabel + ' ' + resolved.target) : messages.selectTarget;
                targetEl.textContent = text;
            }

            function buildCapturePayload() {
                var resolved = resolveTarget();
                return {
                    resolved: resolved,
                    payload: {
                        action: 'be_schema_playfair_capture',
                        nonce: nonce,
                        url: resolved.target,
                        mode: resolved.mode,
                        profile: profileSelect ? profileSelect.value : '',
                        wait_ms: waitInput ? waitInput.value : '',
                        include_html: includeHtmlInput && includeHtmlInput.checked ? '1' : '0',
                        include_logs: includeLogsInput ? (includeLogsInput.checked ? '1' : '0') : '0',
                        locale: localeInput ? localeInput.value : '',
                        timezone_id: timezoneInput ? timezoneInput.value : ''
                    }
                };
            }

            function postAction(payload, onSuccess, onError) {
                var body = Object.keys(payload).map(function (key) {
                    return encodeURIComponent(key) + '=' + encodeURIComponent(payload[key]);
                }).join('&');

                if (window.fetch) {
                    fetch(ajaxUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                        body: body
                    })
                        .then(function (response) { return response.json(); })
                        .then(onSuccess)
                        .catch(onError);
                    return;
                }

                var xhr = new XMLHttpRequest();
                xhr.open('POST', ajaxUrl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                xhr.onreadystatechange = function () {
                    if (xhr.readyState !== 4) {
                        return;
                    }
                    try {
                        var json = JSON.parse(xhr.responseText);
                        onSuccess(json);
                    } catch (err) {
                        onError(err);
                    }
                };
                xhr.send(body);
            }

            function applyQueryArgs(payload, resolved) {
                if (context !== 'schema' || !resolved.local) {
                    return payload;
                }
                var addBeseoInput = document.getElementById('be-schema-preview-add-beseo');
                var queryArgs = {
                    beseo_preview: 1
                };
                if (addBeseoInput && addBeseoInput.checked) {
                    queryArgs.beseo_marker = 1;
                } else {
                    queryArgs.beseo_add = 0;
                }
                payload.query_args = JSON.stringify(queryArgs);
                return payload;
            }

            runBtn.addEventListener('click', function (event) {
                event.preventDefault();
                if (!ajaxUrl || !nonce) {
                    setStatus(messages.failed, 'is-error');
                    return;
                }

                var capture = buildCapturePayload();
                if (!capture.resolved.target) {
                    setStatus(messages.selectTarget, 'is-error');
                    clearResults();
                    return;
                }

                setStatus(messages.running);
                clearResults();

                var payload = applyQueryArgs(capture.payload, capture.resolved);
                postAction(
                    payload,
                    function (response) {
                        if (!response || !response.success) {
                            var msg = response && response.data && response.data.message ? response.data.message : messages.failed;
                            setStatus(msg, 'is-error');
                            return;
                        }
                        renderResult(response.data);
                        var captureMsg = messages.complete;
                        var captureType = '';
                        if (response.data && response.data.meta && response.data.meta.fallback) {
                            captureMsg = messages.completeFallback;
                            captureType = 'is-warning';
                        }
                        setStatus(captureMsg, captureType);
                    },
                    function () {
                        setStatus(messages.requestFailed, 'is-error');
                    }
                );
            });

            updateTargetDisplay();

            var schemaSelectors = [
                '#be-schema-preview-target',
                '#be-schema-preview-site',
                '#be-schema-preview-subpages',
                'input[name="be-schema-preview-env"]',
                'input[name="be-schema-preview-target-mode"]'
            ];
            var validatorSelectors = [
                '#be-schema-validator-manual',
                '#be-schema-validator-site',
                '#be-schema-validator-subpages',
                'input[name="be-schema-validator-env"]',
                'input[name="be_schema_validator_mode"]'
            ];
            var watchList = selector === 'validator' ? validatorSelectors : schemaSelectors;
            watchList.forEach(function (query) {
                toArray(document.querySelectorAll(query)).forEach(function (el) {
                    el.addEventListener('change', updateTargetDisplay);
                    if (el.tagName === 'INPUT') {
                        el.addEventListener('input', updateTargetDisplay);
                    }
                });
            });
        }

        function initAll() {
            var containers = document.querySelectorAll('.be-schema-playfair-capture');
            toArray(containers).forEach(initCapture);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAll);
        } else {
            initAll();
        }
    })();
</script>
