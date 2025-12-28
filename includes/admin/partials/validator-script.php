<script>
var validatorPages = <?php echo wp_json_encode( $validator_page_data ); ?>;
            var validatorPosts = <?php echo wp_json_encode( $validator_post_data ); ?>;
            var validatorAjax = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
            var validatorNonce = '<?php echo wp_create_nonce( 'be_schema_validator' ); ?>';
            var validatorListPagesNonce = '<?php echo wp_create_nonce( 'be_schema_analyser' ); ?>';
            var validatorHomeUrl = '<?php echo esc_js( home_url( '/' ) ); ?>';

            var validatorStorageKey = 'be-schema-validator-state';
            var lastData = null;
            var sitesStoreKey = 'be-schema-analyser-sites';
            var sites = [];
            var listReady = false;
            var isListing = false;

            document.addEventListener('DOMContentLoaded', function () {
                var selector = window.beSchemaSelector || {};
                var tabs = document.querySelectorAll('.nav-tab-wrapper a[data-tools-tab]');
                var panels = document.querySelectorAll('.be-schema-tools-panel');
                var defaultTab = 'validator';
                var hasValidatorTab = false;
                tabs.forEach(function (tab) {
                    if (tab.getAttribute('data-tools-tab') === 'validator') {
                        hasValidatorTab = true;
                    }
                });

                var validatorMode = document.querySelectorAll('input[name="be_schema_validator_mode"]');
                if (!validatorMode.length) {
                    validatorMode = document.querySelectorAll('input[name="be-schema-preview-target-mode"]');
                }
                var validatorType = document.querySelectorAll('input[name="be_schema_validator_type"]');
                var siteSelect = document.getElementById('be-schema-validator-site') || document.getElementById('be-schema-preview-site');
                var validatorManual = document.getElementById('be-schema-validator-manual') || document.getElementById('be-schema-preview-target');
                var includePosts = document.getElementById('be-schema-validator-include-posts') || document.getElementById('be-schema-preview-include-posts');
                var maxPostsInput = document.getElementById('be-schema-validator-max-posts') || document.getElementById('be-schema-preview-max-posts');
                var listPagesBtn = document.getElementById('be-schema-validator-list-pages') || document.getElementById('be-schema-preview-list-pages');
                var subpagesSelect = document.getElementById('be-schema-validator-subpages') || document.getElementById('be-schema-preview-subpages');
                var siteLimitInput = document.getElementById('be-schema-validator-site-limit') || document.getElementById('be-schema-preview-site-limit');
                var envInputs = document.querySelectorAll('input[name="be-schema-validator-env"]');
                if (!envInputs.length) {
                    envInputs = document.querySelectorAll('input[name="be-schema-preview-env"]');
                }
                var selectorStatus = document.getElementById('be-schema-validator-selector-status') || document.getElementById('be-schema-preview-target-status');
                var ogCheckbox = document.getElementById('be-schema-validator-og');
                var twitterCheckbox = document.getElementById('be-schema-validator-twitter');
                var cropsCheckbox = document.getElementById('be-schema-validator-crops');
                var serviceSelect = document.getElementById('be-schema-validator-service');
                var copyCheckbox = document.getElementById('be-schema-validator-copy');
                var openNewCheckbox = document.getElementById('be-schema-validator-open-new');
                var validateBtn = document.getElementById('be-schema-validator-run');
                var reRunBtn = document.getElementById('be-schema-validator-rerun');
                var copySummaryBtn = document.getElementById('be-schema-validator-copy-summary');
                var toggleTwitter = document.getElementById('be-schema-toggle-twitter');
                var toggleOg = document.getElementById('be-schema-toggle-og');
                var validatorNote = document.getElementById('be-schema-validator-note');
                var warningList = document.getElementById('be-schema-warning-list');
                var sourceMap = document.getElementById('be-schema-source-map');
                var contextUrl = document.getElementById('be-schema-context-url');
                var contextPlatforms = document.getElementById('be-schema-context-platforms');
                var contextTime = document.getElementById('be-schema-context-time');
                var contextResult = document.getElementById('be-schema-context-result');
                var miniBadges = document.getElementById('be-schema-mini-badges');
                var fetchLog = {
                    container: document.getElementById('be-schema-fetch-log'),
                    pageStatus: document.getElementById('be-schema-log-page-status'),
                    pageTime: document.getElementById('be-schema-log-page-time'),
                    redirects: document.getElementById('be-schema-log-redirects'),
                    imageStatus: document.getElementById('be-schema-log-image-status'),
                    imageTime: document.getElementById('be-schema-log-image-time'),
                    imageType: document.getElementById('be-schema-log-image-type'),
                    imageSize: document.getElementById('be-schema-log-image-size')
                };

                var previewTwitter = {
                    wrap: document.getElementById('be-schema-preview-twitter'),
                    img: document.getElementById('be-schema-preview-twitter-img'),
                    title: document.getElementById('be-schema-preview-twitter-title'),
                        desc: document.getElementById('be-schema-preview-twitter-desc'),
                        domain: document.getElementById('be-schema-preview-twitter-domain'),
                        card: document.getElementById('be-schema-preview-twitter-card')
                    };
                var previewOg = {
                    wrap: document.getElementById('be-schema-preview-og'),
                    img: document.getElementById('be-schema-preview-og-img'),
                    title: document.getElementById('be-schema-preview-og-title'),
                    desc: document.getElementById('be-schema-preview-og-desc'),
                    domain: document.getElementById('be-schema-preview-og-domain')
                };

                function activateTab(key) {
                    tabs.forEach(function(tab) {
                        if (tab.getAttribute('data-tools-tab') === key) {
                            tab.classList.add('nav-tab-active');
                        } else {
                            tab.classList.remove('nav-tab-active');
                        }
                    });
                    panels.forEach(function(panel) {
                        if (panel.id === 'be-schema-tools-' + key) {
                            panel.classList.add('active');
                        } else {
                            panel.classList.remove('active');
                        }
                    });
                }

                if (hasValidatorTab) {
                    tabs.forEach(function(tab) {
                        tab.addEventListener('click', function(event) {
                            event.preventDefault();
                            activateTab(tab.getAttribute('data-tools-tab'));
                        });
                    });

                    activateTab(defaultTab || 'dashboard');
                }

                    function getRadioValue(inputs) {
                        var selected = '';
                        inputs.forEach(function (input) {
                            if (input.checked) {
                                selected = input.value;
                            }
                        });
                        return selected;
                    }

                    function currentMode() {
                        return getRadioValue(validatorMode) || 'site';
                    }

                function currentValidationType() {
                    var type = 'native';
                    validatorType.forEach(function (radio) {
                        if (radio.checked) {
                            type = radio.value;
                            }
                        });
                    return type;
                }

                function persistState() {
                    var state = {
                        mode: currentMode(),
                        type: currentValidationType(),
                        env: getRadioValue(envInputs),
                        includePosts: includePosts ? includePosts.checked : false,
                        maxPosts: maxPostsInput ? maxPostsInput.value : '',
                        og: ogCheckbox ? ogCheckbox.checked : false,
                        twitter: twitterCheckbox ? twitterCheckbox.checked : false,
                        crops: cropsCheckbox ? cropsCheckbox.checked : false,
                        copy: copyCheckbox ? copyCheckbox.checked : false,
                        openNew: openNewCheckbox ? openNewCheckbox.checked : false,
                        url: currentUrl(),
                        siteValue: siteSelect ? siteSelect.value : '',
                        manualValue: validatorManual ? validatorManual.value : '',
                        subpageValue: subpagesSelect ? subpagesSelect.value : '',
                        siteLimit: siteLimitInput ? siteLimitInput.value : '',
                        showTwitter: toggleTwitter ? toggleTwitter.checked : true,
                        showOg: toggleOg ? toggleOg.checked : true,
                        service: serviceSelect ? serviceSelect.value : ''
                    };
                    try {
                        localStorage.setItem(validatorStorageKey, JSON.stringify(state));
                    } catch (e) {
                        // ignore
                    }
                }

                function restoreState() {
                    try {
                        var raw = localStorage.getItem(validatorStorageKey);
                        if (!raw) { return; }
                        var state = JSON.parse(raw);
                        if (state.mode && validatorMode) {
                            var modeValue = state.mode === 'dropdown' ? 'site' : state.mode;
                            validatorMode.forEach(function(r){ r.checked = (r.value === modeValue); });
                        }
                        if (state.type && validatorType) {
                            validatorType.forEach(function(r){ r.checked = (r.value === state.type); });
                        }
                        if (state.env && envInputs) {
                            envInputs.forEach(function(r){ r.checked = (r.value === state.env); });
                        }
                        if (includePosts && typeof state.includePosts !== 'undefined') {
                            includePosts.checked = state.includePosts;
                        }
                        if (maxPostsInput && typeof state.maxPosts !== 'undefined') {
                            maxPostsInput.value = state.maxPosts;
                        }
                        if (ogCheckbox && typeof state.og !== 'undefined') { ogCheckbox.checked = state.og; }
                        if (twitterCheckbox && typeof state.twitter !== 'undefined') { twitterCheckbox.checked = state.twitter; }
                        if (cropsCheckbox && typeof state.crops !== 'undefined') { cropsCheckbox.checked = state.crops; }
                        if (copyCheckbox && typeof state.copy !== 'undefined') { copyCheckbox.checked = state.copy; }
                        if (openNewCheckbox && typeof state.openNew !== 'undefined') { openNewCheckbox.checked = state.openNew; }
                        if (toggleTwitter && typeof state.showTwitter !== 'undefined') { toggleTwitter.checked = state.showTwitter; }
                        if (toggleOg && typeof state.showOg !== 'undefined') { toggleOg.checked = state.showOg; }
                        if (serviceSelect && typeof state.service !== 'undefined') { serviceSelect.value = state.service; }
                        if (siteSelect && state.siteValue) { siteSelect.value = state.siteValue; }
                        if (validatorManual && typeof state.manualValue !== 'undefined') { validatorManual.value = state.manualValue; }
                        if (subpagesSelect && typeof state.subpageValue !== 'undefined') { subpagesSelect.value = state.subpageValue; }
                        if (siteLimitInput && typeof state.siteLimit !== 'undefined') { siteLimitInput.value = state.siteLimit; }
                    } catch (e) {
                        // ignore
                    }
                }

                function isValidHttpUrl(value) {
                    if (!value) {
                        return false;
                    }
                    try {
                        var parsed = new URL(value);
                        return parsed.protocol === 'http:' || parsed.protocol === 'https:';
                    } catch (e) {
                        return false;
                    }
                }

                function currentUrl() {
                    return getTargetValue();
                }

                function isLocalSelected() {
                    return getRadioValue(envInputs) === 'local';
                }

                function setSelectorStatus(message) {
                    if (!selectorStatus) {
                        return;
                    }
                    selectorStatus.textContent = message || '';
                }

                function resetSubpages(disabled) {
                    listReady = false;
                    if (selector.resetSubpages) {
                        selector.resetSubpages(subpagesSelect, '<?php echo esc_js( __( 'None', 'beseo' ) ); ?>', disabled);
                    }
                }

                function populateSubpages(pages) {
                    if (!selector.populateSubpages) {
                        return;
                    }
                    listReady = selector.populateSubpages(subpagesSelect, pages, {
                        noneLabel: '<?php echo esc_js( __( 'None', 'beseo' ) ); ?>',
                        homeLabel: '<?php echo esc_js( __( 'Home page', 'beseo' ) ); ?>',
                        dividerLabel: '────────'
                    });
                }

                function isHomepageUrl(url) {
                    return selector.isHomepageUrl ? selector.isHomepageUrl(url) : false;
                }

                function updateTargetModeControls() {
                    var mode = currentMode();
                    var allowList = false;
                    if (mode === 'site') {
                        allowList = !!(siteSelect && siteSelect.value);
                    } else {
                        var manualUrl = validatorManual ? validatorManual.value.trim() : '';
                        if (manualUrl && isHomepageUrl(manualUrl)) {
                            allowList = true;
                        }
                    }
                    if (listPagesBtn) {
                        listPagesBtn.disabled = !allowList || isListing;
                    }
                    if (siteLimitInput) {
                        siteLimitInput.disabled = !allowList;
                    }
                    if (!allowList) {
                        resetSubpages(true);
                    }
                }

                function currentTargetUrl() {
                    var mode = currentMode();
                    if (mode === 'manual') {
                        return validatorManual ? validatorManual.value.trim() : '';
                    }
                    return siteSelect ? siteSelect.value.trim() : '';
                }

                function currentSelectedTarget() {
                    var subpage = subpagesSelect && !subpagesSelect.disabled ? (subpagesSelect.value || '').trim() : '';
                    if (subpage) {
                        return subpage;
                    }
                    return currentTargetUrl();
                }

                function mapToLocalTarget(value) {
                    if (!value || !validatorHomeUrl) {
                        return value;
                    }
                    try {
                        var parsed = new URL(value);
                        var local = new URL(validatorHomeUrl);
                        if (parsed.host === local.host) {
                            return value;
                        }
                        return local.origin + (parsed.pathname || '/') + (parsed.search || '') + (parsed.hash || '');
                    } catch (err) {
                        return value;
                    }
                }

                function getTargetValue() {
                    var value = currentSelectedTarget();
                    if (isLocalSelected()) {
                        value = mapToLocalTarget(value);
                    }
                    return value;
                }

                function applyTargetMode(mode) {
                    var useManual = mode === 'manual';
                    validatorMode.forEach(function (input) {
                        input.checked = input.value === mode;
                    });
                    if (validatorManual) {
                        validatorManual.style.display = useManual ? 'inline-block' : 'none';
                        validatorManual.disabled = !useManual;
                    }
                    if (siteSelect) {
                        siteSelect.style.display = useManual ? 'none' : 'inline-block';
                        siteSelect.disabled = useManual;
                    }
                    if (includePosts) {
                        includePosts.disabled = useManual;
                        if (useManual) {
                            includePosts.checked = false;
                        }
                    }
                    if (maxPostsInput) {
                        maxPostsInput.disabled = useManual || !includePosts || !includePosts.checked;
                    }
                }

                function loadSites() {
                    if (selector.loadSites) {
                        sites = selector.loadSites(sitesStoreKey);
                        return;
                    }
                    sites = [];
                }

                function renderSites() {
                    if (selector.renderSitesSelect) {
                        selector.renderSitesSelect(siteSelect, sites, validatorHomeUrl);
                    }
                }

                function syncValidatorMode() {
                    var mode = currentMode();
                    applyTargetMode(mode);
                    updateTargetModeControls();
                    persistState();
                    updateButtonState();
                }

                function syncValidationType() {
                    var type = currentValidationType();
                    var isNative = type === 'native';
                    if (ogCheckbox) { ogCheckbox.disabled = !isNative; }
                    if (twitterCheckbox) { twitterCheckbox.disabled = !isNative; }
                    if (cropsCheckbox) { cropsCheckbox.disabled = !isNative; }
                    if (serviceSelect) { serviceSelect.disabled = isNative; }
                    if (copyCheckbox) { copyCheckbox.disabled = isNative; }
                    if (openNewCheckbox) { openNewCheckbox.disabled = isNative; }
                    persistState();
                    updateButtonState();
                }

                function updateButtonState() {
                    var url = currentUrl();
                    var type = currentValidationType();
                    var platformsOn = (ogCheckbox && ogCheckbox.checked) || (twitterCheckbox && twitterCheckbox.checked);
                    var validUrl = isValidHttpUrl(url);
                    var externalReady = (serviceSelect && serviceSelect.value);
                    if (validateBtn) {
                        if (type === 'native') {
                            validateBtn.disabled = !(platformsOn && validUrl);
                        } else {
                            validateBtn.disabled = !(externalReady && validUrl);
                        }
                    }
                    if (reRunBtn) {
                        reRunBtn.disabled = validateBtn ? validateBtn.disabled : true;
                    }
                    if (contextResult) {
                        var guidance = '—';
                        if (type === 'native' && !platformsOn && validUrl) {
                            guidance = '<?php echo esc_js( __( 'Turn on at least one platform to validate.', 'beseo' ) ); ?>';
                        } else if (type === 'external' && !externalReady && validUrl) {
                            guidance = '<?php echo esc_js( __( 'Choose an external service to continue.', 'beseo' ) ); ?>';
                        } else if (validUrl) {
                            guidance = '<?php echo esc_js( __( 'Ready', 'beseo' ) ); ?>';
                        }
                        contextResult.textContent = guidance;
                        contextResult.dataset.state = 'guidance';
                    }
                    persistState();
                }

                function toggleCrops() {
                    var on = cropsCheckbox && cropsCheckbox.checked;
                    document.querySelectorAll('.be-schema-preview-img').forEach(function (el) {
                        el.classList.toggle('crops-on', on);
                    });
                }

                function applyPreviewToggles() {
                    if (previewTwitter.wrap && toggleTwitter) {
                        previewTwitter.wrap.style.display = toggleTwitter.checked ? 'block' : 'none';
                    }
                    if (previewOg.wrap && toggleOg) {
                        previewOg.wrap.style.display = toggleOg.checked ? 'block' : 'none';
                    }
                }

                function renderSourceRow(field, value, source, confidence) {
                    if (!sourceMap) {
                        return;
                    }
                    var row = sourceMap.querySelector('tr[data-field="' + field + '"]');
                    if (!row) {
                        return;
                    }
                    var valCell = row.querySelector('.value');
                    var sourceCell = row.querySelector('.source');
                    if (valCell) {
                        valCell.innerHTML = '';
                        var container = document.createElement('div');
                        container.className = 'be-schema-source-value';
                        var valSpan = document.createElement('span');
                        valSpan.className = 'truncate';
                        valSpan.textContent = value || '—';
                        container.appendChild(valSpan);
                        if (value && field === 'image') {
                            var copyBtn = document.createElement('button');
                            copyBtn.type = 'button';
                            copyBtn.className = 'be-schema-copy-btn';
                            copyBtn.textContent = '<?php echo esc_js( __( 'Copy URL', 'beseo' ) ); ?>';
                            copyBtn.addEventListener('click', function() {
                                navigator.clipboard.writeText(value);
                            });
                            container.appendChild(copyBtn);
                        }
                        if (!value) {
                            var missing = document.createElement('span');
                            missing.style.color = '#a00';
                            missing.textContent = '<?php echo esc_js( __( 'Missing', 'beseo' ) ); ?>';
                            container.appendChild(missing);
                        }
                        valCell.appendChild(container);
                    }
                    if (sourceCell) {
                        sourceCell.innerHTML = '';
                        var dot = document.createElement('span');
                        dot.className = 'be-schema-dot ' + (confidence || 'red');
                        sourceCell.appendChild(dot);
                        var text = document.createElement('span');
                        text.textContent = source || (value ? '<?php echo esc_js( __( 'fallback', 'beseo' ) ); ?>' : '<?php echo esc_js( __( 'missing', 'beseo' ) ); ?>');
                        sourceCell.appendChild(text);
                    }
                }

                function renderSourceMap(resolved) {
                    if (!resolved) {
                        return;
                    }
                    renderSourceRow('title', resolved.title && resolved.title.value, resolved.title && resolved.title.source, resolved.title && resolved.title.confidence);
                    renderSourceRow('description', resolved.description && resolved.description.value, resolved.description && resolved.description.source, resolved.description && resolved.description.confidence);
                    renderSourceRow('image', resolved.image && resolved.image.value, resolved.image && resolved.image.source, resolved.image && resolved.image.confidence);
                    renderSourceRow('card', resolved.card && resolved.card.value, resolved.card && resolved.card.source, resolved.card && resolved.card.confidence);
                    renderSourceRow('domain', resolved.domain && resolved.domain.value, resolved.domain && resolved.domain.source, resolved.domain && resolved.domain.confidence);
                }

                function applyPreview(preview, data, enabled) {
                    if (!preview.wrap) {
                        return;
                    }
                    if (!enabled || !data) {
                        preview.wrap.style.display = 'none';
                        return;
                    }
                    preview.wrap.style.display = 'block';
                    if (preview.title) {
                        preview.title.textContent = (data.title && data.title.value) || '—';
                    }
                    if (preview.desc) {
                        preview.desc.textContent = (data.description && data.description.value) || '—';
                    }
                    if (preview.domain) {
                        var domainText = (data.domain && data.domain.value) || '';
                        preview.domain.innerHTML = '';
                        if (domainText) {
                            var badge = document.createElement('span');
                            badge.className = 'be-schema-preview-domain-badge';
                            badge.textContent = domainText;
                            preview.domain.appendChild(badge);
                        }
                        if (data.image && data.image.ratioWarning) {
                            var cropFlag = document.createElement('span');
                            cropFlag.className = 'be-schema-preview-crop-flag';
                            cropFlag.textContent = '<?php echo esc_js( __( 'May crop', 'beseo' ) ); ?>';
                            preview.domain.appendChild(cropFlag);
                        }
                    }
                    if (preview.card && data.card) {
                        preview.card.textContent = data.card.value || '';
                    }
                    if (preview.img) {
                        var imgUrl = data.image && data.image.value ? data.image.value : '';
                        preview.img.style.backgroundImage = imgUrl ? 'url(' + imgUrl + ')' : '';
                        preview.img.classList.toggle('crops-on', cropsCheckbox && cropsCheckbox.checked);
                    }
                }

                function renderWarnings(warnings, data) {
                    if (!warningList) {
                        return;
                    }
                    warnings = warnings || [];
                    if (data && data.fetch && typeof data.fetch.redirects !== 'undefined' && data.fetch.redirects > 1) {
                        var redirWarn = {
                            status: 'warn',
                            message: '<?php echo esc_js( __( 'Page had multiple redirects; some platforms may stop after one.', 'beseo' ) ); ?>',
                            platforms: ['Open Graph', 'X']
                        };
                        warnings.push(redirWarn);
                    }
                    if (miniBadges) {
                        miniBadges.innerHTML = '';
                        warnings.forEach(function (item) {
                            if (item.status && item.status === 'error') {
                                var badge = document.createElement('span');
                                badge.className = 'be-schema-mini-badge';
                                badge.textContent = item.message || '';
                                miniBadges.appendChild(badge);
                            }
                        });
                    }
                    warningList.innerHTML = '';
                    if (!warnings || !warnings.length) {
                        var liEmpty = document.createElement('li');
                        liEmpty.className = 'be-schema-warning-empty';
                        liEmpty.textContent = '<?php echo esc_js( __( 'No issues detected.', 'beseo' ) ); ?>';
                        warningList.appendChild(liEmpty);
                        return;
                    }
                    var icons = {
                        ok: '✅',
                        warn: '⚠️',
                        error: '❌'
                    };
                    warnings.forEach(function (item) {
                        var li = document.createElement('li');
                        var status = document.createElement('span');
                        status.className = 'status';
                        status.textContent = icons[item.status] || '⚠️';
                        li.appendChild(status);
                        var msg = document.createElement('span');
                        msg.textContent = item.message || '';
                        li.appendChild(msg);
                        if (item.platforms && item.platforms.length) {
                            item.platforms.forEach(function (platform) {
                                var badge = document.createElement('span');
                                badge.className = 'platform';
                                badge.textContent = platform;
                                li.appendChild(badge);
                            });
                        }
                        warningList.appendChild(li);
                    });
                }

                function renderFetchLog(data) {
                    if (!fetchLog || !fetchLog.container) {
                        return;
                    }
                    if (!data || !data.fetch) {
                        fetchLog.container.style.display = 'none';
                        return;
                    }
                    fetchLog.container.style.display = 'block';
                    fetchLog.pageStatus.textContent = (data.fetch.status || '') + (data.fetch.message ? (' ' + data.fetch.message) : '');
                    fetchLog.pageTime.textContent = typeof data.fetch.duration_ms !== 'undefined' ? data.fetch.duration_ms + ' ms' : '—';
                    fetchLog.redirects.textContent = typeof data.fetch.redirects !== 'undefined' ? data.fetch.redirects : '—';
                    fetchLog.imageStatus.textContent = data.image ? (data.image.httpStatus || '') : '—';
                    fetchLog.imageTime.textContent = data.image && typeof data.image.duration_ms !== 'undefined' ? data.image.duration_ms + ' ms' : '—';
                    fetchLog.imageType.textContent = data.image ? (data.image.contentType || '') : '—';
                    fetchLog.imageSize.textContent = (data.image && data.image.width && data.image.height)
                        ? (data.image.width + '×' + data.image.height)
                        : '—';
                }

                function renderResponse(data) {
                    if (!data) {
                        return;
                    }
                    window.__beSchemaLastData = data;
                    lastData = data;
                    if (contextUrl) {
                        contextUrl.textContent = data.fetch && data.fetch.final_url ? data.fetch.final_url : currentUrl();
                    }
                    if (contextPlatforms) {
                        var labels = [];
                        if (data.platforms) {
                            if (data.platforms.og) { labels.push('<?php echo esc_js( __( 'OG', 'beseo' ) ); ?>'); }
                            if (data.platforms.twitter) { labels.push('<?php echo esc_js( __( 'Twitter', 'beseo' ) ); ?>'); }
                        }
                        contextPlatforms.textContent = labels.length ? labels.join(', ') : '—';
                    }
                    if (contextTime) {
                        contextTime.textContent = new Date().toLocaleTimeString();
                    }
                    if (contextResult) {
                        contextResult.textContent = data.fetch && data.fetch.status && data.fetch.status >= 200 && data.fetch.status < 300
                            ? '<?php echo esc_js( __( 'Validated', 'beseo' ) ); ?>'
                            : '<?php echo esc_js( __( 'Validation failed', 'beseo' ) ); ?>';
                        contextResult.dataset.state = 'result';
                    }
                    if (miniBadges) {
                        miniBadges.innerHTML = '';
                        var badgeItems = [];
                        if (data.fetch) {
                            badgeItems.push('HTTP ' + (data.fetch.status || '?'));
                            if (typeof data.fetch.duration_ms !== 'undefined') {
                                badgeItems.push((data.fetch.duration_ms || 0) + ' ms');
                            }
                            if (typeof data.fetch.redirects !== 'undefined') {
                                badgeItems.push('redir ' + data.fetch.redirects);
                            }
                        }
                        if (data.image) {
                            if (data.image.contentType) { badgeItems.push(data.image.contentType); }
                            if (data.image.width && data.image.height) { badgeItems.push(data.image.width + '×' + data.image.height); }
                            if (typeof data.image.duration_ms !== 'undefined') { badgeItems.push('img ' + data.image.duration_ms + ' ms'); }
                        }
                        badgeItems.forEach(function(label) {
                            var badge = document.createElement('span');
                            badge.className = 'be-schema-mini-badge';
                            badge.textContent = label;
                            miniBadges.appendChild(badge);
                        });
                    }
                    if (reRunBtn) {
                        reRunBtn.disabled = false;
                    }
                    if (copySummaryBtn) {
                        copySummaryBtn.disabled = false;
                    }
                    var resolved = data.resolved ? data.resolved.primary : null;
                    renderSourceMap(resolved);
                    applyPreview(previewTwitter, data.resolved ? data.resolved.twitter : null, (twitterCheckbox && twitterCheckbox.checked) && (toggleTwitter ? toggleTwitter.checked : true));
                    applyPreview(previewOg, data.resolved ? data.resolved.og : null, (ogCheckbox && ogCheckbox.checked) && (toggleOg ? toggleOg.checked : true));
                    renderWarnings(data.warnings, data);
                    renderFetchLog(data);
                    lastData = data;

                    if (validatorNote) {
                        var fetchNote = '';
                        if (data.fetch) {
                            fetchNote = 'HTTP ' + (data.fetch.status || '?');
                            if (typeof data.fetch.redirects !== 'undefined') {
                                fetchNote += ' · redirects: ' + data.fetch.redirects;
                            }
                            if (data.fetch.final_url) {
                                fetchNote += ' · ' + data.fetch.final_url;
                            }
                            if (data.fetch.host) {
                                fetchNote += ' · host: ' + data.fetch.host;
                            }
                        }
                        validatorNote.textContent = fetchNote;
                    }
                }

                function runValidation() {
                    var url = currentUrl();
                    var type = currentValidationType();
                    persistState();
                    if (!isValidHttpUrl(url) || !validateBtn) {
                        updateButtonState();
                        return;
                    }
                    if (type === 'external') {
                        var svc = serviceSelect ? serviceSelect.options[serviceSelect.selectedIndex] : null;
                        var svcUrl = svc && svc.getAttribute('data-url');
                        if (svc && svc.disabled) {
                            if (validatorNote) {
                                validatorNote.textContent = '<?php echo esc_js( __( 'Select an external service to continue.', 'beseo' ) ); ?>';
                            }
                            if (contextResult) {
                                contextResult.textContent = '<?php echo esc_js( __( 'Validation failed', 'beseo' ) ); ?>';
                            }
                            return;
                        }
                        if (copyCheckbox && copyCheckbox.checked && navigator.clipboard) {
                            navigator.clipboard.writeText(url);
                        }
                        if (svcUrl) {
                            var target = (openNewCheckbox && openNewCheckbox.checked) ? '_blank' : '_self';
                            window.open(svcUrl, target, 'noopener,noreferrer');
                            if (validatorNote) {
                                validatorNote.textContent = '<?php echo esc_js( __( 'Opening external validator…', 'beseo' ) ); ?>';
                            }
                            if (contextResult) {
                                contextResult.textContent = '<?php echo esc_js( __( 'External validator opened', 'beseo' ) ); ?>';
                                contextResult.dataset.state = 'result';
                            }
                        } else if (validatorNote) {
                            validatorNote.textContent = '<?php echo esc_js( __( 'Select an external service to continue.', 'beseo' ) ); ?>';
                        }
                        return;
                    }
                    validateBtn.disabled = true;
                    var originalLabel = validateBtn.textContent;
                    validateBtn.textContent = '<?php echo esc_js( __( 'Validating…', 'beseo' ) ); ?>';
                    if (reRunBtn) {
                        reRunBtn.disabled = true;
                    }
                    if (copySummaryBtn) {
                        copySummaryBtn.disabled = true;
                    }
                    if (contextResult) {
                        contextResult.textContent = '<?php echo esc_js( __( 'Validating…', 'beseo' ) ); ?>';
                        contextResult.dataset.state = 'result';
                    }
                    if (validatorNote) {
                        validatorNote.textContent = '<?php echo esc_js( __( 'Running validation…', 'beseo' ) ); ?>';
                    }

                    var form = new FormData();
                    form.append('action', 'be_schema_validator_run');
                    form.append('nonce', validatorNonce);
                    form.append('url', url);
                    form.append('enableOg', ogCheckbox && ogCheckbox.checked ? '1' : '');
                    form.append('enableTwitter', twitterCheckbox && twitterCheckbox.checked ? '1' : '');

                    var endpoint = validatorAjax || (window.ajaxurl || '');
                    fetch(endpoint, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: form
                    }).then(function (response) {
                        return response.json();
                    }).then(function (payload) {
                        validateBtn.disabled = false;
                        validateBtn.textContent = originalLabel;
                        if (!payload || !payload.success) {
                            if (validatorNote) {
                                var errorMsg = (payload && payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js( __( 'Validation failed.', 'beseo' ) ); ?>';
                                validatorNote.textContent = errorMsg;
                            }
                            if (contextResult) {
                                contextResult.textContent = '<?php echo esc_js( __( 'Validation failed', 'beseo' ) ); ?>';
                                contextResult.dataset.state = 'result';
                            }
                            if (reRunBtn) {
                                reRunBtn.disabled = validateBtn.disabled;
                            }
                            return;
                        }
                        renderResponse(payload.data);
                    }).catch(function () {
                        validateBtn.disabled = false;
                        validateBtn.textContent = originalLabel;
                        if (reRunBtn) {
                            reRunBtn.disabled = validateBtn.disabled;
                        }
                        if (validatorNote) {
                            validatorNote.textContent = '<?php echo esc_js( __( 'Validation failed.', 'beseo' ) ); ?>';
                        }
                        if (contextResult) {
                            contextResult.textContent = '<?php echo esc_js( __( 'Validation failed', 'beseo' ) ); ?>';
                            contextResult.dataset.state = 'result';
                        }
                        if (copySummaryBtn) {
                            copySummaryBtn.disabled = false;
                        }
                    });
                }

                validatorMode.forEach(function (radio) {
                    radio.addEventListener('change', syncValidatorMode);
                });
                validatorType.forEach(function (radio) {
                    radio.addEventListener('change', syncValidationType);
                });
                if (siteSelect) {
                    siteSelect.addEventListener('change', function() {
                        resetSubpages(true);
                        updateTargetModeControls();
                        persistState();
                        updateButtonState();
                    });
                }
                if (validatorManual) {
                    validatorManual.addEventListener('input', function() {
                        resetSubpages(true);
                        updateTargetModeControls();
                        persistState();
                        updateButtonState();
                    });
                }
                if (subpagesSelect) {
                    subpagesSelect.addEventListener('change', function() {
                        persistState();
                        updateButtonState();
                    });
                }
                if (envInputs.length) {
                    envInputs.forEach(function (input) {
                        input.addEventListener('change', function() {
                            resetSubpages(true);
                            updateTargetModeControls();
                            persistState();
                            updateButtonState();
                        });
                    });
                }
                if (includePosts) {
                    includePosts.addEventListener('change', function() {
                        if (maxPostsInput) {
                            maxPostsInput.disabled = !includePosts.checked;
                        }
                        persistState();
                    });
                }
                if (maxPostsInput) {
                    maxPostsInput.disabled = !includePosts || !includePosts.checked;
                    maxPostsInput.addEventListener('change', persistState);
                }
                if (siteLimitInput) {
                    siteLimitInput.addEventListener('change', persistState);
                }
                if (listPagesBtn) {
                    listPagesBtn.addEventListener('click', function(event) {
                        event.preventDefault();
                        if (listPagesBtn.disabled) {
                            return;
                        }
                        if (!validatorListPagesNonce) {
                            setSelectorStatus('<?php echo esc_js( __( 'List Pages is not configured.', 'beseo' ) ); ?>');
                            return;
                        }
                        var url = currentTargetUrl();
                        if (!url) {
                            setSelectorStatus('<?php echo esc_js( __( 'Select a target URL first.', 'beseo' ) ); ?>');
                            return;
                        }
                        if (currentMode() === 'manual' && !isHomepageUrl(url)) {
                            setSelectorStatus('<?php echo esc_js( __( 'List Pages requires a homepage URL.', 'beseo' ) ); ?>');
                            resetSubpages(true);
                            return;
                        }
                        var max = siteLimitInput ? parseInt(siteLimitInput.value, 10) : 25;
                        max = isNaN(max) ? 25 : max;
                        isListing = true;
                        listReady = false;
                        updateTargetModeControls();
                        setSelectorStatus('<?php echo esc_js( __( 'Listing sitemap pages…', 'beseo' ) ); ?>');
                        resetSubpages(true);
                        var requestListPages = selector.requestListPages;
                        if (!requestListPages) {
                            isListing = false;
                            updateTargetModeControls();
                            setSelectorStatus('<?php echo esc_js( __( 'Failed to list pages.', 'beseo' ) ); ?>');
                            resetSubpages(true);
                            return;
                        }
                        requestListPages({
                            ajaxUrl: validatorAjax,
                            nonce: validatorListPagesNonce,
                            url: url,
                            local: isLocalSelected(),
                            max: max
                        }).then(function(payload) {
                            isListing = false;
                            updateTargetModeControls();
                            if (!payload || !payload.success) {
                                var msg = (payload && payload.data && payload.data.message) ? payload.data.message : '<?php echo esc_js( __( 'Failed to list pages.', 'beseo' ) ); ?>';
                                setSelectorStatus(msg);
                                resetSubpages(true);
                                listReady = false;
                                return;
                            }
                            var pages = payload.data && payload.data.pages ? payload.data.pages : [];
                            if (!pages.length) {
                                setSelectorStatus('<?php echo esc_js( __( 'No sitemap pages found.', 'beseo' ) ); ?>');
                                resetSubpages(true);
                                listReady = false;
                                return;
                            }
                            populateSubpages(pages);
                            setSelectorStatus('<?php echo esc_js( __( 'Pages loaded.', 'beseo' ) ); ?>');
                            persistState();
                            updateButtonState();
                        }).catch(function() {
                            isListing = false;
                            updateTargetModeControls();
                            setSelectorStatus('<?php echo esc_js( __( 'Failed to list pages.', 'beseo' ) ); ?>');
                            resetSubpages(true);
                        });
                    });
                }
                if (ogCheckbox) {
                    ogCheckbox.addEventListener('change', function() {
                        updateButtonState();
                        persistState();
                    });
                }
                if (twitterCheckbox) {
                    twitterCheckbox.addEventListener('change', function() {
                        updateButtonState();
                        persistState();
                    });
                }
                if (cropsCheckbox) {
                    cropsCheckbox.addEventListener('change', function() {
                        toggleCrops();
                        persistState();
                    });
                }
                if (validateBtn) {
                    validateBtn.addEventListener('click', runValidation);
                }
                if (reRunBtn) {
                    reRunBtn.addEventListener('click', runValidation);
                }
                if (copyCheckbox) {
                    copyCheckbox.addEventListener('change', persistState);
                }
                if (openNewCheckbox) {
                    openNewCheckbox.addEventListener('change', persistState);
                }
                if (serviceSelect) {
                    serviceSelect.addEventListener('change', function() {
                        updateButtonState();
                        persistState();
                    });
                }
                if (toggleTwitter) {
                    toggleTwitter.addEventListener('change', function() {
                        applyPreviewToggles();
                        persistState();
                    });
                }
                if (toggleOg) {
                    toggleOg.addEventListener('change', function() {
                        applyPreviewToggles();
                        persistState();
                    });
                }
                if (copySummaryBtn) {
                    copySummaryBtn.addEventListener('click', function() {
                        var summary = [];
                        summary.push('Result: ' + (contextResult ? contextResult.textContent : ''));
                        summary.push('URL: ' + (contextUrl ? contextUrl.textContent : ''));
                        summary.push('Platforms: ' + (contextPlatforms ? contextPlatforms.textContent : ''));
                        summary.push('Last run: ' + (contextTime ? contextTime.textContent : ''));
                        var resolvedTwitter = (lastData && lastData.resolved) ? lastData.resolved.twitter : null;
                        var resolvedOg = (lastData && lastData.resolved) ? lastData.resolved.og : null;
                        if (resolvedTwitter) {
                            summary.push('Twitter title: ' + (resolvedTwitter.title && resolvedTwitter.title.value ? resolvedTwitter.title.value : ''));
                            summary.push('Twitter desc: ' + (resolvedTwitter.description && resolvedTwitter.description.value ? resolvedTwitter.description.value : ''));
                            summary.push('Twitter image: ' + (resolvedTwitter.image && resolvedTwitter.image.value ? resolvedTwitter.image.value : ''));
                        }
                        if (resolvedOg) {
                            summary.push('OG title: ' + (resolvedOg.title && resolvedOg.title.value ? resolvedOg.title.value : ''));
                            summary.push('OG desc: ' + (resolvedOg.description && resolvedOg.description.value ? resolvedOg.description.value : ''));
                            summary.push('OG image: ' + (resolvedOg.image && resolvedOg.image.value ? resolvedOg.image.value : ''));
                        }
                        if (lastData && lastData.warnings) {
                            summary.push('Warnings: ' + lastData.warnings.map(function(w){ return w.status.toUpperCase() + ': ' + w.message; }).join(' | '));
                        }
                        navigator.clipboard.writeText(summary.join('\n')).catch(function() {
                            if (validatorNote) {
                                validatorNote.textContent = '<?php echo esc_js( __( 'Copy failed. Please try again.', 'beseo' ) ); ?>';
                            }
                        });
                    });
                }

                loadSites();
                renderSites();
                restoreState();
                applyTargetMode(currentMode());
                updateTargetModeControls();
                syncValidatorMode();
                syncValidationType();
                updateButtonState();
                toggleCrops();
                applyPreviewToggles();
            });
    </script>
