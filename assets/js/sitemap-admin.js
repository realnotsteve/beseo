(function() {
    var data = window.beSchemaSitemapData || {};
    var tabLinks = document.querySelectorAll('.beseo-sitemap-tabs a[data-sitemap-tab]');
    var tabPanels = document.querySelectorAll('.beseo-sitemap-panel');
    var selectEl = document.getElementById('be-schema-sitemap-iframe-select');
    var previewCodeEl = document.getElementById('be-schema-sitemap-preview-code');
    var xmlCodeEl = document.getElementById('be-schema-sitemap-xml-code');
    var radios = document.getElementsByName('be_schema_sitemap_preview_mode');
    var htmlUrl = data.htmlUrl || '';
    var htmlSource = data.htmlSource || '';
    var ajaxUrl = data.ajaxUrl || window.ajaxurl || '';
    var formEl = document.getElementById('be-schema-sitemap-form');
    var trigger = document.getElementById('be-schema-sitemap-generate-btn');
    var hasFlash = !!data.hasFlash;
    var inlineErr = document.getElementById('be-schema-sitemap-inline-error');
    var generating = false;

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function(match) {
            switch (match) {
                case '&':
                    return '&amp;';
                case '<':
                    return '&lt;';
                case '>':
                    return '&gt;';
                case '"':
                    return '&quot;';
                default:
                    return '&#039;';
            }
        });
    }

    function formatMarkup(markup) {
        if (!markup) {
            return '';
        }
        var text = String(markup).replace(/\r\n/g, '\n').replace(/\r/g, '\n').trim();
        if (!text) {
            return '';
        }
        var tokens = text.split(/(<[^>]+>)/g).filter(function(token) {
            return token !== '';
        });
        var lines = [];
        var indent = 0;
        for (var i = 0; i < tokens.length; i++) {
            var token = tokens[i];
            if (token.trim() === '') {
                continue;
            }
            var isTag = token.charAt(0) === '<' && token.charAt(token.length - 1) === '>';
            var isClosing = /^<\//.test(token);
            var isSelfClosing = /\/>$/.test(token) || /^<\?/.test(token) || /^<!/.test(token);
            if (isClosing) {
                indent = Math.max(indent - 1, 0);
            }
            var line = token;
            if (!isTag) {
                line = token.trim();
                if (!line) {
                    continue;
                }
            }
            lines.push(new Array(indent + 1).join('  ') + line);
            if (isTag && !isClosing && !isSelfClosing) {
                indent++;
            }
        }
        return lines.join('\n');
    }

    function highlightTag(token) {
        if (/^<!--/.test(token) || /^<\?/.test(token) || /^<!/.test(token)) {
            return '<span class="beseo-code-comment">' + escapeHtml(token) + '</span>';
        }
        var isClosing = /^<\//.test(token);
        var content = token.slice(1, -1);
        if (isClosing) {
            content = content.slice(1);
        }
        if (/\/\s*$/.test(content)) {
            content = content.replace(/\/\s*$/, '');
        }
        var trimmed = content.trim();
        var spaceIndex = trimmed.search(/\s/);
        var tagName = spaceIndex === -1 ? trimmed : trimmed.slice(0, spaceIndex);
        var attrs = spaceIndex === -1 ? '' : trimmed.slice(spaceIndex);
        var attrOutput = '';
        var lastIndex = 0;
        var attrRe = /([^\s=]+)(\s*=\s*)(\"[^\"]*\"|'[^']*'|[^\s\"'>]+)/g;
        var match;
        while ((match = attrRe.exec(attrs))) {
            attrOutput += escapeHtml(attrs.slice(lastIndex, match.index));
            attrOutput += '<span class="beseo-code-attr">' + escapeHtml(match[1]) + '</span>';
            attrOutput += '<span class="beseo-code-punct">' + escapeHtml(match[2]) + '</span>';
            attrOutput += '<span class="beseo-code-value">' + escapeHtml(match[3]) + '</span>';
            lastIndex = attrRe.lastIndex;
        }
        attrOutput += escapeHtml(attrs.slice(lastIndex));
        var openPunct = '<span class="beseo-code-punct">&lt;' + (isClosing ? '/' : '') + '</span>';
        var closePunct = '<span class="beseo-code-punct">' + (token.slice(-2) === '/>' ? '/&gt;' : '&gt;') + '</span>';
        return openPunct + '<span class="beseo-code-tag">' + escapeHtml(tagName) + '</span>' + attrOutput + closePunct;
    }

    function highlightMarkup(markup) {
        if (!markup) {
            return '';
        }
        var tokens = String(markup).split(/(<[^>]+>)/g);
        var output = '';
        for (var i = 0; i < tokens.length; i++) {
            var token = tokens[i];
            if (!token) {
                continue;
            }
            if (token.charAt(0) === '<' && token.charAt(token.length - 1) === '>') {
                output += highlightTag(token);
            } else {
                output += escapeHtml(token);
            }
        }
        return output;
    }

    function applyHighlight(codeEl, raw, type) {
        if (!codeEl) {
            return;
        }
        var source = raw || '';
        codeEl.setAttribute('data-raw', source);
        if (type) {
            codeEl.setAttribute('data-code-type', type);
        }
        if (!source) {
            codeEl.innerHTML = '';
            return;
        }
        var formatted = formatMarkup(source);
        if (!formatted) {
            formatted = source;
        }
        codeEl.innerHTML = highlightMarkup(formatted);
    }

    function initCodeBlock(codeEl) {
        if (!codeEl) {
            return '';
        }
        var raw = codeEl.textContent || '';
        applyHighlight(codeEl, raw, codeEl.getAttribute('data-code-type'));
        return raw;
    }

    var previewRaw = initCodeBlock(previewCodeEl);
    var xmlRaw = initCodeBlock(xmlCodeEl);
    var xmlSource = xmlRaw;
    if (!htmlSource && previewCodeEl && previewCodeEl.getAttribute('data-code-type') === 'html') {
        htmlSource = previewRaw;
    }
    if (!xmlSource && previewCodeEl && previewCodeEl.getAttribute('data-code-type') === 'xml') {
        xmlSource = previewRaw;
    }
    var previewCache = {};
    var xmlSourceUrl = selectEl ? selectEl.value : '';
    if (xmlSource && xmlSourceUrl) {
        previewCache[xmlSourceUrl] = xmlSource;
    }

    function currentMode() {
        var mode = 'xml';
        if (!radios || !radios.length) {
            return mode;
        }
        for (var i = 0; i < radios.length; i++) {
            if (radios[i].checked) {
                mode = radios[i].value;
                break;
            }
        }
        return mode;
    }

    function setPreviewSource(source, type) {
        if (!previewCodeEl) {
            return;
        }
        applyHighlight(previewCodeEl, source, type);
    }

    function fetchSource(url, callback) {
        if (!url || !window.fetch) {
            callback(new Error('missing'));
            return;
        }
        fetch(url, { credentials: 'same-origin' }).then(function(resp) {
            if (!resp.ok) {
                throw new Error('HTTP ' + resp.status);
            }
            return resp.text();
        }).then(function(text) {
            callback(null, text);
        }).catch(function() {
            callback(new Error('fetch failed'));
        });
    }

    function loadPreviewFromUrl(url, type) {
        if (!url) {
            return;
        }
        if (previewCache[url]) {
            setPreviewSource(previewCache[url], type);
            return;
        }
        fetchSource(url, function(err, text) {
            if (err) {
                return;
            }
            previewCache[url] = text;
            setPreviewSource(text, type);
        });
    }

    function syncPreviewToMode(mode) {
        if (!previewCodeEl) {
            return;
        }
        if (mode === 'html') {
            if (htmlSource) {
                setPreviewSource(htmlSource, 'html');
            } else if (htmlUrl) {
                loadPreviewFromUrl(htmlUrl, 'html');
            }
            return;
        }
        if (selectEl && selectEl.value) {
            if (previewCache[selectEl.value]) {
                setPreviewSource(previewCache[selectEl.value], 'xml');
                return;
            }
            loadPreviewFromUrl(selectEl.value, 'xml');
            return;
        }
        if (xmlSource) {
            setPreviewSource(xmlSource, 'xml');
        }
    }

    function activateTab(key) {
        for (var i = 0; i < tabLinks.length; i++) {
            tabLinks[i].classList.toggle('nav-tab-active', tabLinks[i].getAttribute('data-sitemap-tab') === key);
        }
        for (var j = 0; j < tabPanels.length; j++) {
            tabPanels[j].classList.toggle('active', tabPanels[j].getAttribute('data-sitemap-panel') === key);
        }
    }

    function keyFromHash() {
        if (!window.location.hash) {
            return '';
        }
        var hash = window.location.hash.substring(1);
        var panel = document.getElementById(hash);
        if (panel && panel.getAttribute('data-sitemap-panel')) {
            return panel.getAttribute('data-sitemap-panel');
        }
        return '';
    }

    if (tabLinks.length && tabPanels.length) {
        for (var t = 0; t < tabLinks.length; t++) {
            tabLinks[t].addEventListener('click', function(event) {
                event.preventDefault();
                var key = this.getAttribute('data-sitemap-tab');
                activateTab(key);
                var panel = document.querySelector('.beseo-sitemap-panel[data-sitemap-panel="' + key + '"]');
                if (panel && panel.id) {
                    if (window.history && window.history.replaceState) {
                        window.history.replaceState({}, document.title, '#' + panel.id);
                    } else {
                        window.location.hash = panel.id;
                    }
                }
            });
        }
        var initialKey = keyFromHash();
        if (!initialKey && tabLinks[0]) {
            initialKey = tabLinks[0].getAttribute('data-sitemap-tab');
        }
        if (initialKey) {
            activateTab(initialKey);
        }
    }

    if (selectEl) {
        selectEl.addEventListener('change', function() {
            xmlSourceUrl = selectEl.value;
            if (currentMode() === 'xml') {
                loadPreviewFromUrl(selectEl.value, 'xml');
            }
        });
    }
    if (radios && radios.length) {
        for (var i = 0; i < radios.length; i++) {
            radios[i].addEventListener('change', function() {
                syncPreviewToMode(this.value);
            });
        }
    }

    function showError(msg) {
        if (inlineErr && inlineErr.querySelector('p')) {
            inlineErr.style.display = 'block';
            inlineErr.querySelector('p').textContent = msg;
        } else {
            alert(msg);
        }
    }

    function clearError() {
        if (inlineErr && inlineErr.querySelector('p')) {
            inlineErr.style.display = 'none';
            inlineErr.querySelector('p').textContent = '';
        }
    }

    function generateSitemap(e) {
        if (e) {
            e.preventDefault();
        }
        if (generating) {
            return;
        }
        if (!formEl || !window.fetch || !window.FormData) {
            return;
        }
        generating = true;
        if (trigger) {
            trigger.setAttribute('aria-busy', 'true');
            trigger.disabled = true;
        }
        clearError();

        var fd = new FormData(formEl);
        fd.append('action', 'be_schema_generate_sitemap');
        var noticeContainer = document.querySelector('.beseo-sitemap-flash');
        if (noticeContainer) {
            noticeContainer.remove();
        }
        fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: fd
        }).then(function(resp) {
            return resp.json();
        }).then(function(resp) {
            if (!resp || !resp.success || !resp.data || !resp.data.redirect) {
                var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Sitemap generation failed. Please try again.';
                showError(msg);
                return;
            }
            window.location = resp.data.redirect;
        }).catch(function() {
            showError('Sitemap generation failed. Please try again.');
        }).finally(function() {
            generating = false;
            if (trigger) {
                trigger.removeAttribute('aria-busy');
                trigger.disabled = false;
            }
        });
    }

    if (formEl && window.fetch && window.FormData) {
        formEl.addEventListener('submit', function(e) {
            generateSitemap(e);
        });
    }
    if (trigger && window.fetch && window.FormData) {
        trigger.addEventListener('click', generateSitemap);
    }

    if (hasFlash && window.history && window.history.replaceState) {
        var url = new URL(window.location.href);
        url.searchParams.delete('beseo_sitemap_flash');
        window.history.replaceState({}, document.title, url.toString());
    }
})();
