(function (window) {
    'use strict';

    var selector = window.beSchemaSelector || {};

    selector.isHomepageUrl = function (url) {
        if (!url) {
            return false;
        }
        try {
            var parsed = new URL(url);
            var path = parsed.pathname || '/';
            return path === '/' || path === '';
        } catch (err) {
            return false;
        }
    };

    selector.loadSites = function (storeKey) {
        if (!window.localStorage || !storeKey) {
            return [];
        }
        try {
            var raw = window.localStorage.getItem(storeKey);
            var sites = raw ? JSON.parse(raw) : [];
            return Array.isArray(sites) ? sites : [];
        } catch (err) {
            return [];
        }
    };

    selector.renderSitesSelect = function (selectEl, sites, homeUrl) {
        if (!selectEl) {
            return;
        }
        selectEl.innerHTML = '';
        if (!sites || !sites.length) {
            if (homeUrl) {
                var optHome = document.createElement('option');
                optHome.value = homeUrl;
                optHome.textContent = homeUrl;
                selectEl.appendChild(optHome);
            }
            return;
        }
        sites.forEach(function (site) {
            if (!site || !site.url) {
                return;
            }
            var opt = document.createElement('option');
            opt.value = site.url;
            opt.textContent = site.label ? (site.label + ' (' + site.url + ')') : site.url;
            selectEl.appendChild(opt);
        });
    };

    selector.resetSubpages = function (selectEl, noneLabel, disabled) {
        if (!selectEl) {
            return false;
        }
        var label = noneLabel || 'None';
        selectEl.innerHTML = '';
        var noneOption = document.createElement('option');
        noneOption.value = '';
        noneOption.textContent = label;
        selectEl.appendChild(noneOption);
        selectEl.disabled = disabled !== false;
        return false;
    };

    selector.populateSubpages = function (selectEl, pages, labels) {
        if (!selectEl) {
            return false;
        }
        var options = labels || {};
        selector.resetSubpages(selectEl, options.noneLabel || 'None', false);
        if (!pages || !pages.length) {
            return false;
        }
        selectEl.innerHTML = '';
        var homeEntry = pages.find(function (page) { return page && page.is_home; });
        var remaining = pages.filter(function (page) { return page && !page.is_home; });
        if (homeEntry) {
            var homeOption = document.createElement('option');
            homeOption.value = homeEntry.url || '';
            homeOption.textContent = homeEntry.label || options.homeLabel || 'Home page';
            selectEl.appendChild(homeOption);
        }
        if (remaining.length) {
            var divider = document.createElement('option');
            divider.textContent = options.dividerLabel || '--------';
            divider.disabled = true;
            selectEl.appendChild(divider);
            remaining.forEach(function (page) {
                var opt = document.createElement('option');
                opt.value = page.url || '';
                opt.textContent = page.label || page.url || '';
                selectEl.appendChild(opt);
            });
        }
        selectEl.disabled = false;
        return true;
    };

    selector.requestListPages = function (options) {
        var settings = options || {};
        var endpoint = settings.ajaxUrl || window.ajaxurl || '';
        if (!endpoint) {
            return Promise.reject(new Error('Missing AJAX URL.'));
        }
        var action = settings.action || 'be_schema_analyser_list_pages';
        var nonce = settings.nonce || '';
        var url = settings.url || '';
        var max = typeof settings.max !== 'undefined' ? settings.max : 25;
        if (isNaN(max)) {
            max = 25;
        }
        var localFlag = settings.local ? '1' : '';
        if (window.fetch && window.FormData) {
            var form = new FormData();
            form.append('action', action);
            form.append('nonce', nonce);
            form.append('url', url);
            form.append('local', localFlag);
            form.append('max', max);
            return fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                body: form
            }).then(function (response) {
                return response.json();
            });
        }

        return new Promise(function (resolve, reject) {
            var body = [
                'action=' + encodeURIComponent(action),
                'nonce=' + encodeURIComponent(nonce),
                'url=' + encodeURIComponent(url),
                'local=' + encodeURIComponent(localFlag),
                'max=' + encodeURIComponent(max)
            ].join('&');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', endpoint, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) {
                    return;
                }
                try {
                    resolve(JSON.parse(xhr.responseText));
                } catch (err) {
                    reject(err);
                }
            };
            xhr.onerror = function () {
                reject(new Error('Request failed.'));
            };
            xhr.send(body);
        });
    };

    window.beSchemaSelector = selector;
})(window);
