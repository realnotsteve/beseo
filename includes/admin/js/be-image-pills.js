/**
 * Shared image validation + status pill helper.
 *
 * Expects a global window.beSchemaImageConfig with:
 * {
 *   enabled: true/false,
 *   expectedDims: { inputId: { width, height, mime } },
 *   statusMap: { inputId: statusPillId },
 *   textMap: { default: { undefined, verified, resolution } }
 * }
 *
 * Buttons must include:
 *  - .be-schema-image-select[data-target-input][data-target-preview]
 *  - .be-schema-image-clear[data-target-input][data-target-preview]
 */
(function () {
    function setStatus(inputId, statusKey, config) {
        var statusId = (config.statusMap && config.statusMap[inputId]) ? config.statusMap[inputId] : null;
        if (!statusId) {
            return;
        }
        var pill = document.getElementById(statusId);
        if (!pill) {
            return;
        }
        var textMap = (config.textMap && (config.textMap[inputId] || config.textMap.default)) || {
            undefined: 'Undefined',
            verified: 'Verified',
            resolution: 'Resolution'
        };
        pill.classList.remove('verified', 'resolution');
        if (statusKey === 'verified') {
            pill.classList.add('verified');
        } else if (statusKey === 'resolution') {
            pill.classList.add('resolution');
        }
        pill.textContent = textMap[statusKey] || textMap.undefined || '';
    }

    function clearPreview(targetPreviewId) {
        var preview = document.getElementById(targetPreviewId);
        if (preview) {
            preview.innerHTML = '';
        }
    }

    function attachHandlers(config) {
        var enabled = !!config.enabled;
        var expected = config.expectedDims || {};

        var selectButtons = document.querySelectorAll('.be-schema-image-select');
        var clearButtons = document.querySelectorAll('.be-schema-image-clear');

        function openMediaFrame(targetInputId, targetPreviewId) {
            if (!enabled) {
                return;
            }
            if (typeof wp === 'undefined' || !wp.media) {
                return;
            }

            var frame = wp.media({
                title: config.mediaTitle || 'Select Image',
                multiple: false
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                var url = attachment.url || '';

                var input = document.getElementById(targetInputId);
                if (input) {
                    input.value = url;
                }

                var preview = document.getElementById(targetPreviewId);
                if (preview) {
                    preview.innerHTML = url ? '<img src="' + url + '" alt="" />' : '';
                }

                if (!enabled) {
                    return;
                }

                var expect = expected[targetInputId];
                if (!expect) {
                    setStatus(targetInputId, 'verified', config);
                    return;
                }

                var mime = (attachment.mime || '').toLowerCase();
                var subtype = (attachment.subtype || '').toLowerCase();
                var wantMime = (expect.mime || '').toLowerCase();
                var typeOk = !wantMime || mime === wantMime || (!!subtype && wantMime.endsWith(subtype));
                var sizeOk = (!expect.width || !expect.height) ||
                    (attachment.width === expect.width && attachment.height === expect.height);

                if (typeOk && sizeOk) {
                    setStatus(targetInputId, 'verified', config);
                } else {
                    setStatus(targetInputId, 'resolution', config);
                }
            });

            frame.open();
        }

        selectButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                var targetInputId = button.getAttribute('data-target-input');
                var targetPreviewId = button.getAttribute('data-target-preview');
                if (targetInputId && targetPreviewId) {
                    openMediaFrame(targetInputId, targetPreviewId);
                }
            });
        });

        clearButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                var targetInputId = button.getAttribute('data-target-input');
                var targetPreviewId = button.getAttribute('data-target-preview');
                var input = document.getElementById(targetInputId);
                if (input) {
                    input.value = '';
                }
                clearPreview(targetPreviewId);
                setStatus(targetInputId, 'undefined', config);
            });
        });

        // Initialize to undefined on load.
        Object.keys(config.statusMap || {}).forEach(function (inputId) {
            setStatus(inputId, 'undefined', config);
        });
    }

    window.beSchemaInitImages = function () {
        if (!window.beSchemaImageConfig) {
            return;
        }
        attachHandlers(window.beSchemaImageConfig);
    };
})();
