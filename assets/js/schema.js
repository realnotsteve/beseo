document.addEventListener('DOMContentLoaded', function () {
    var data = window.beSchemaSchemaData || {};
    var labels = data.labels || {};
    var imageValidationEnabled = !!data.imageValidationEnabled;

    var labelUndefined = labels.undefined || 'Undefined';
    var labelVerified = labels.verified || 'Verified';
    var labelResolution = labels.resolution || 'Resolution';
    var labelSelectImage = labels.selectImage || 'Select Image';
    var labelPublisherNone = labels.publisherNone || 'Publisher Type: None';
    var labelPublisherDedicated = labels.publisherDedicated || 'Publisher Type: Dedicated';
    var labelPublisherReference = labels.publisherReference || 'Publisher Type: Reference';
                // Top-level tabs.
                var tabLinks = document.querySelectorAll('.be-schema-tab-link');
                var tabPanels = document.querySelectorAll('.be-schema-tab-panel');

                function activateSchemaTab(tabKey) {
                    tabLinks.forEach(function (link) {
                        if (link.getAttribute('data-schema-tab') === tabKey) {
                            link.classList.add('be-schema-tab-active');
                        } else {
                            link.classList.remove('be-schema-tab-active');
                        }
                    });

                    tabPanels.forEach(function (panel) {
                        if (panel.id === 'be-schema-tab-' + tabKey) {
                            panel.classList.add('be-schema-tab-panel-active');
                        } else {
                            panel.classList.remove('be-schema-tab-panel-active');
                        }
                    });
                }

                // Overview vertical tabs.
                var overviewLinks = document.querySelectorAll('.be-schema-overview-tab-link');
                var overviewPanels = document.querySelectorAll('.be-schema-overview-panel');

                function activateOverviewTab(tabKey) {
                    overviewLinks.forEach(function (link) {
                        if (link.getAttribute('data-overview-tab') === tabKey) {
                            link.classList.add('be-schema-overview-tab-active');
                        } else {
                            link.classList.remove('be-schema-overview-tab-active');
                        }
                    });

                    overviewPanels.forEach(function (panel) {
                        if (panel.id === 'be-schema-overview-' + tabKey) {
                            panel.classList.add('be-schema-overview-panel-active');
                        } else {
                            panel.classList.remove('be-schema-overview-panel-active');
                        }
                    });
                }

                overviewLinks.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        var tabKey = link.getAttribute('data-overview-tab');
                        activateOverviewTab(tabKey);
                    });
                });

                tabLinks.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        var tabKey = link.getAttribute('data-schema-tab');
                        activateSchemaTab(tabKey);
                    });
                });

                // Vertical website tabs.
                var websiteLinks = document.querySelectorAll('.be-schema-website-tab-link');
                var websitePanels = document.querySelectorAll('.be-schema-website-panel');

                function activateWebsiteTab(tabKey) {
                    websiteLinks.forEach(function (link) {
                        if (link.getAttribute('data-website-tab') === tabKey) {
                            link.classList.add('be-schema-website-tab-active');
                        } else {
                            link.classList.remove('be-schema-website-tab-active');
                        }
                    });

                    websitePanels.forEach(function (panel) {
                        if (panel.id === 'be-schema-website-' + tabKey) {
                            panel.classList.add('be-schema-website-panel-active');
                        } else {
                            panel.classList.remove('be-schema-website-panel-active');
                        }
                    });
                }

                function getFirstEnabledWebsiteTab() {
                    var first = null;
                    websiteLinks.forEach(function (link) {
                        if (first) {
                            return;
                        }
                        if (! link.classList.contains('be-schema-website-tab-disabled')) {
                            first = link.getAttribute('data-website-tab');
                        }
                    });
                    return first;
                }

                function setWebsiteTabDisabled(tabKey, disabled) {
                    websiteLinks.forEach(function (link) {
                        if (link.getAttribute('data-website-tab') !== tabKey) {
                            return;
                        }
                        if (disabled) {
                            link.classList.add('be-schema-website-tab-disabled');
                            link.setAttribute('aria-disabled', 'true');
                            if (link.classList.contains('be-schema-website-tab-active')) {
                                var fallback = getFirstEnabledWebsiteTab();
                                if (fallback && fallback !== tabKey) {
                                    activateWebsiteTab(fallback);
                                }
                            }
                        } else {
                            link.classList.remove('be-schema-website-tab-disabled');
                            link.removeAttribute('aria-disabled');
                        }
                    });
                }

                websiteLinks.forEach(function (link) {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        var tabKey = link.getAttribute('data-website-tab');
                        activateWebsiteTab(tabKey);
                    });
                });

                // Health panel quick links to Website subtabs.
                var healthLinks = document.querySelectorAll('.be-schema-health-link');
                function openWebsiteTab(target) {
                    activateSchemaTab('website');
                    if (target) {
                        activateWebsiteTab(target);
                    }
                }
                healthLinks.forEach(function (link) {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        var target = link.getAttribute('data-website-tab');
                        openWebsiteTab(target);
                    });
                });

                // Conditional blocks (Person / Organisation / Publisher).
                var toggles = document.querySelectorAll('.be-schema-toggle-block');

                function updateConditionalBlock(toggle) {
                    var targetIds = toggle.getAttribute('data-target-block');
                    if (! targetIds) {
                        return;
                    }
                    targetIds.split(/\s+/).forEach(function (targetId) {
                        if (! targetId) {
                            return;
                        }
                        var block = document.getElementById(targetId);
                        if (! block) {
                            return;
                        }
                        if (toggle.checked) {
                            block.classList.remove('is-disabled');
                        } else {
                            block.classList.add('is-disabled');
                        }
                    });
                }

                toggles.forEach(function (toggle) {
                    updateConditionalBlock(toggle);
                    toggle.addEventListener('change', function () {
                        updateConditionalBlock(toggle);
                    });
                });

                var repeatableAdders = {};

                function repeatableHasValue(name) {
                    var inputs = document.querySelectorAll('input[name="' + name + '"]');
                    var has = false;
                    inputs.forEach(function (input) {
                        if (input.value && input.value.trim().length > 0) {
                            has = true;
                        }
                    });
                    return has;
                }

                function initRepeatableField(container) {
                    var prop = container.getAttribute('data-repeatable-prop');
                    var name = container.getAttribute('data-repeatable-name');
                    var itemsWrap = container.querySelector('.be-schema-repeatable-items');
                    var addBtn = container.querySelector('.be-schema-repeatable-add');

                    function attachRemove(btn) {
                        btn.addEventListener('click', function (event) {
                            event.preventDefault();
                            var item = btn.closest('.be-schema-repeatable-item');
                            if (item && itemsWrap) {
                                item.remove();
                                if (! itemsWrap.children.length) {
                                    addItem('');
                                }
                            }
                        });
                    }

                    function addItem(value) {
                        if (! itemsWrap || ! name) {
                            return null;
                        }
                        var row = document.createElement('div');
                        row.className = 'be-schema-repeatable-item';

                        var input = document.createElement('input');
                        input.type = 'text';
                        input.name = name;
                        input.className = 'regular-text';
                        input.value = value || '';
                        row.appendChild(input);

                        var removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'button be-schema-repeatable-remove';
                        removeBtn.textContent = '−';
                        row.appendChild(removeBtn);

                        itemsWrap.appendChild(row);
                        attachRemove(removeBtn);
                        return input;
                    }

                    container.querySelectorAll('.be-schema-repeatable-remove').forEach(function (btn) {
                        attachRemove(btn);
                    });

                    if (addBtn) {
                        addBtn.addEventListener('click', function (event) {
                            event.preventDefault();
                            var input = addItem('');
                            if (input) {
                                input.focus();
                            }
                        });
                    }

                    if (prop) {
                        repeatableAdders[prop] = function () {
                            var input = addItem('');
                            if (input) {
                                input.focus();
                            }
                        };
                    }

                    if (itemsWrap && ! itemsWrap.children.length) {
                        addItem('');
                    }
                }

                document.querySelectorAll('.be-schema-repeatable').forEach(function (container) {
                    initRepeatableField(container);
                });

                // Media pickers.
                var selectButtons = document.querySelectorAll('.be-schema-image-select');
                var clearButtons = document.querySelectorAll('.be-schema-image-clear');

                var expectedImageDims = {
                    'be_schema_website_image_16_9': { width: 1920, height: 1080, label: '16:9 (1920x1080)' },
                    'be_schema_website_image_4_3': { width: 1600, height: 1200, label: '4:3 (1600x1200)' },
                    'be_schema_website_image_1_1': { width: 1200, height: 1200, label: '1:1 (1200x1200)' },
                    'be_schema_website_image_3_4': { width: 1200, height: 1600, label: '3:4 (1200x1600)' },
                    'be_schema_website_image_9_16': { width: 1080, height: 1920, label: '9:16 (1080x1920)' },
                    'be_schema_org_logo_image_16_9': { width: 1920, height: 1080, label: '16:9 (1920x1080)' },
                    'be_schema_org_logo_image_4_3': { width: 1600, height: 1200, label: '4:3 (1600x1200)' },
                    'be_schema_org_logo_image_1_1': { width: 1200, height: 1200, label: '1:1 (1200x1200)' },
                    'be_schema_org_logo_image_3_4': { width: 1200, height: 1600, label: '3:4 (1200x1600)' },
                    'be_schema_org_logo_image_9_16': { width: 1080, height: 1920, label: '9:16 (1080x1920)' },
                    'be_schema_person_image_16_9': { width: 1920, height: 1080, label: '16:9 (1920x1080)' },
                    'be_schema_person_image_4_3': { width: 1600, height: 1200, label: '4:3 (1600x1200)' },
                    'be_schema_person_image_1_1': { width: 1200, height: 1200, label: '1:1 (1200x1200)' },
                    'be_schema_person_image_3_4': { width: 1200, height: 1600, label: '3:4 (1200x1600)' },
                    'be_schema_person_image_9_16': { width: 1080, height: 1920, label: '9:16 (1080x1920)' },
                    'be_schema_publisher_image_16_9': { width: 1920, height: 1080, label: '16:9 (1920x1080)' },
                    'be_schema_publisher_image_4_3': { width: 1600, height: 1200, label: '4:3 (1600x1200)' },
                    'be_schema_publisher_image_1_1': { width: 1200, height: 1200, label: '1:1 (1200x1200)' },
                    'be_schema_publisher_image_3_4': { width: 1200, height: 1600, label: '3:4 (1200x1600)' },
                    'be_schema_publisher_image_9_16': { width: 1080, height: 1920, label: '9:16 (1080x1920)' }
                };

                var imageStatusMap = {
                    'be_schema_website_image_16_9': 'be_schema_website_image_16_9_status',
                    'be_schema_website_image_4_3': 'be_schema_website_image_4_3_status',
                    'be_schema_website_image_1_1': 'be_schema_website_image_1_1_status',
                    'be_schema_website_image_3_4': 'be_schema_website_image_3_4_status',
                    'be_schema_website_image_9_16': 'be_schema_website_image_9_16_status',
                    'be_schema_org_logo_image_16_9': 'be_schema_org_logo_image_16_9_status',
                    'be_schema_org_logo_image_4_3': 'be_schema_org_logo_image_4_3_status',
                    'be_schema_org_logo_image_1_1': 'be_schema_org_logo_image_1_1_status',
                    'be_schema_org_logo_image_3_4': 'be_schema_org_logo_image_3_4_status',
                    'be_schema_org_logo_image_9_16': 'be_schema_org_logo_image_9_16_status',
                    'be_schema_person_image_16_9': 'be_schema_person_image_16_9_status',
                    'be_schema_person_image_4_3': 'be_schema_person_image_4_3_status',
                    'be_schema_person_image_1_1': 'be_schema_person_image_1_1_status',
                    'be_schema_person_image_3_4': 'be_schema_person_image_3_4_status',
                    'be_schema_person_image_9_16': 'be_schema_person_image_9_16_status',
                    'be_schema_publisher_image_16_9': 'be_schema_publisher_image_16_9_status',
                    'be_schema_publisher_image_4_3': 'be_schema_publisher_image_4_3_status',
                    'be_schema_publisher_image_1_1': 'be_schema_publisher_image_1_1_status',
                    'be_schema_publisher_image_3_4': 'be_schema_publisher_image_3_4_status',
                    'be_schema_publisher_image_9_16': 'be_schema_publisher_image_9_16_status'
                };

                var statusDisplayMap = {
                    default: {
                        undefined: labelUndefined,
                        verified: labelVerified,
                        resolution: labelResolution
                    }
                };

                function setImageStatus(inputId, statusKey) {
                    var statusId = imageStatusMap[inputId];
                    if (! statusId) {
                        return;
                    }
                    var pill = document.getElementById(statusId);
                    if (! pill) {
                        return;
                    }
                    var textMap = statusDisplayMap[inputId] || statusDisplayMap.default;
                    pill.classList.remove('verified', 'resolution');
                    if (statusKey === 'verified') {
                        pill.classList.add('verified');
                    } else if (statusKey === 'resolution') {
                        pill.classList.add('resolution');
                    }
                    pill.textContent = textMap[statusKey] || textMap.undefined;
                }

                function openMediaFrame(targetInputId, targetPreviewId) {
                    if (typeof wp === 'undefined' || ! wp.media) {
                        return;
                    }

                    var frame = wp.media({
                        title: labelSelectImage,
                        multiple: false
                    });

                    frame.on('select', function () {
                        var attachment = frame.state().get('selection').first().toJSON();
                        var url = attachment.url || '';

                        var input = document.getElementById(targetInputId);
                        var preview = document.getElementById(targetPreviewId);
                        var expected = expectedImageDims[targetInputId];

                        if (input) {
                            input.value = url;
                        }

                        if (preview) {
                            if (url) {
                                preview.innerHTML = '<img src="' + url + '" alt="" />';
                            } else {
                                preview.innerHTML = '';
                            }
                        }

                        if (! imageValidationEnabled) {
                            setImageStatus(targetInputId, 'undefined');
                            return;
                        }

                        if (expected && attachment.width && attachment.height) {
                            var isCorrectSize = attachment.width === expected.width && attachment.height === expected.height;
                            var mime = (attachment.mime || '').toLowerCase();
                            var subtype = (attachment.subtype || '').toLowerCase();
                            var expectedMime = (expected.mime || '').toLowerCase();
                            var isCorrectType = true;
                            if (expectedMime) {
                                isCorrectType = (mime === expectedMime) || (!! subtype && expectedMime.endsWith(subtype));
                            }
                            if (isCorrectSize && isCorrectType) {
                                setImageStatus(targetInputId, 'verified');
                            } else {
                                setImageStatus(targetInputId, 'resolution');
                            }
                        } else if (expected && expected.mime) {
                            var mimeOnly = (attachment.mime || '').toLowerCase();
                            var subtypeOnly = (attachment.subtype || '').toLowerCase();
                            var expectedMimeOnly = (expected.mime || '').toLowerCase();
                            var typeMatchesOnly = (mimeOnly === expectedMimeOnly) || (!! subtypeOnly && expectedMimeOnly.endsWith(subtypeOnly));
                            setImageStatus(targetInputId, typeMatchesOnly ? 'verified' : 'resolution');
                        } else {
                            setImageStatus(targetInputId, 'verified');
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
                        var preview = document.getElementById(targetPreviewId);

                        if (input) {
                            input.value = '';
                        }
                        if (preview) {
                            preview.innerHTML = '';
                        }

                        setImageStatus(targetInputId, 'undefined');
                    });
                });

                // Image enable/disable toggles (Global tab).
                var imageEnableToggles = document.querySelectorAll('.be-schema-image-enable');

                function toggleImageField(toggle) {
                    var targetInputId = toggle.getAttribute('data-target-input');
                    var targetSelectId = toggle.getAttribute('data-target-select');
                    var targetClearId = toggle.getAttribute('data-target-clear');

                    var input = document.getElementById(targetInputId);
                    var selectBtn = document.getElementById(targetSelectId);
                    var clearBtn = document.getElementById(targetClearId);

                    var enabled = toggle.checked;

                    if (input) {
                        input.disabled = ! enabled;
                    }
                    if (selectBtn) {
                        selectBtn.disabled = ! enabled;
                    }
                    if (clearBtn) {
                        clearBtn.disabled = ! enabled;
                    }
                }

                imageEnableToggles.forEach(function (toggle) {
                    toggle.addEventListener('change', function () {
                        toggleImageField(toggle);
                    });
                    toggleImageField(toggle);
                });

                // Initialize image status pills to Undefined.
                Object.keys(imageStatusMap).forEach(function (inputId) {
                    setImageStatus(inputId, 'undefined');
                });

                // Optional dropdowns now use the shared helper.
                if (window.beSchemaInitAllOptionalGroups) {
                    window.beSchemaInitAllOptionalGroups();
                }

                // Publisher dedicated optional: enable only when publisher + dedicated are on.
                (function () {
                    var publisherToggle = document.querySelector('input[name="be_schema_publisher_enabled"]');
                    var dedicatedToggle = document.querySelector('input[name="be_schema_publisher_dedicated_enabled"]');
                    var controls = document.querySelector('.be-schema-optional-controls[data-optional-scope="publisher-dedicated"]');
                    var select = document.getElementById('be-schema-publisher-dedicated-optional');
                    var add = document.querySelector('[data-optional-add="publisher-dedicated"]');
                    var fields = document.getElementById('be-schema-publisher-dedicated-optional-fields');
                    var imageControls = document.querySelector('.be-schema-optional-controls[data-optional-scope="publisher-images"]');
                    var imageSelect = document.getElementById('be-schema-publisher-dedicated-images-optional');
                    var imageAdd = document.querySelector('[data-optional-add="publisher-images"]');
                    var imageFields = document.getElementById('be-schema-publisher-dedicated-images-optional-fields');
                    var typePill = document.getElementById('be-schema-publisher-type-pill');

                    function updateTypePill() {
                        if (! typePill) {
                            return;
                        }
                        var publisherOn = !! (publisherToggle && publisherToggle.checked);
                        var dedicatedOn = !! (publisherOn && dedicatedToggle && dedicatedToggle.checked);

                        var label = '';
                        if (! publisherOn) {
                            label = labelPublisherNone;
                            typePill.classList.add('off');
                            typePill.classList.remove('neutral');
                        } else if (dedicatedOn) {
                            label = labelPublisherDedicated;
                            typePill.classList.remove('off');
                            typePill.classList.remove('neutral');
                        } else {
                            label = labelPublisherReference;
                            typePill.classList.remove('off');
                            typePill.classList.remove('neutral');
                        }

                        typePill.textContent = label;
                    }

                    function setDedicatedOptionalEnabled() {
                        syncDedicatedToggle();

                        var enabled = !! (publisherToggle && publisherToggle.checked && dedicatedToggle && dedicatedToggle.checked);

                        if (controls) {
                            controls.classList.toggle('is-disabled', ! enabled);
                        }

                        if (select) {
                            select.disabled = ! enabled;
                            if (enabled) {
                                select.dispatchEvent(new Event('change'));
                            }
                        }

                        if (add) {
                            add.disabled = ! enabled;
                            if (! enabled) {
                                add.classList.add('disabled');
                            } else {
                                add.classList.remove('disabled');
                            }
                        }

                        if (fields) {
                            fields.querySelectorAll('.be-schema-optional-remove, .be-schema-image-select, .be-schema-image-clear').forEach(function (btn) {
                                btn.disabled = ! enabled;
                            });
                            fields.querySelectorAll('input[type="text"], textarea').forEach(function (input) {
                                input.readOnly = ! enabled;
                            });
                        }

                        if (imageControls) {
                            imageControls.classList.toggle('is-disabled', ! enabled);
                        }

                        if (imageSelect) {
                            imageSelect.disabled = ! enabled;
                            if (enabled) {
                                imageSelect.dispatchEvent(new Event('change'));
                            }
                        }

                        if (imageAdd) {
                            imageAdd.disabled = ! enabled;
                            if (! enabled) {
                                imageAdd.classList.add('disabled');
                            } else {
                                imageAdd.classList.remove('disabled');
                            }
                        }

                        if (imageFields) {
                            imageFields.querySelectorAll('.be-schema-optional-remove, .be-schema-image-select, .be-schema-image-clear').forEach(function (btn) {
                                btn.disabled = ! enabled;
                            });
                            imageFields.querySelectorAll('input[type="text"], textarea').forEach(function (input) {
                                input.readOnly = ! enabled;
                            });
                        }

                        updateTypePill();
                    }

                    function syncDedicatedToggle() {
                        if (! dedicatedToggle) {
                            return;
                        }
                        var publisherOn = !! (publisherToggle && publisherToggle.checked);
                        dedicatedToggle.disabled = ! publisherOn;
                        if (! publisherOn) {
                            dedicatedToggle.checked = false;
                        }
                    }

                    if (publisherToggle) {
                        publisherToggle.addEventListener('change', setDedicatedOptionalEnabled);
                    }
                    if (dedicatedToggle) {
                        dedicatedToggle.addEventListener('change', setDedicatedOptionalEnabled);
                    }

                    setDedicatedOptionalEnabled();
                })();

                // Identity option enable/disable.
                var identityCheckboxes = document.querySelectorAll('.be-schema-identity-checkbox');

                function updateIdentityOption(checkbox) {
                    var radioId = checkbox.getAttribute('data-target-radio');
                    var radio = document.getElementById(radioId);
                    if (! radio) {
                        return;
                    }

                    if (checkbox.checked) {
                        radio.disabled = false;
                        return;
                    }

                    var wasChecked = radio.checked;
                    radio.disabled = true;
                    radio.checked = false;

                    if (wasChecked) {
                        var fallback = document.querySelector('.be-schema-identity-radio:not(:disabled)');
                        if (fallback) {
                            fallback.checked = true;
                        }
                    }
                }

                identityCheckboxes.forEach(function (checkbox) {
                    checkbox.addEventListener('change', function () {
                        updateIdentityOption(checkbox);
                    });
                    updateIdentityOption(checkbox);
                });

                function updateIdentityTabLink(checkbox) {
                    var tabKey = checkbox.getAttribute('data-target-tab');
                    if (! tabKey || tabKey === 'publisher') {
                        return;
                    }
                    setWebsiteTabDisabled(tabKey, ! checkbox.checked);
                }

                identityCheckboxes.forEach(function (checkbox) {
                    checkbox.addEventListener('change', function () {
                        updateIdentityTabLink(checkbox);
                    });
                    updateIdentityTabLink(checkbox);
                });

                // Identity radio font weight: highlight only the checked one.
                var identityRadios = document.querySelectorAll('.be-schema-identity-radio');

                function refreshIdentityRadios() {
                    identityRadios.forEach(function (radio) {
                        var label = radio.closest('label');
                        if (! label) {
                            return;
                        }
                        if (radio.checked) {
                            label.classList.add('be-identity-radio-active');
                        } else {
                            label.classList.remove('be-identity-radio-active');
                        }
                    });
                }

                identityRadios.forEach(function (radio) {
                    radio.addEventListener('change', refreshIdentityRadios);
                });
                refreshIdentityRadios();

                // Keep publisher identity availability in sync with the main publisher enable toggle.
                (function () {
                    var publisherToggle = document.querySelector('input[name="be_schema_publisher_enabled"]');
                    var publisherIdentityCheckbox = document.getElementById('be_schema_identity_publisher_checkbox');
                    var publisherIdentityRadio = document.getElementById('be_schema_identity_publisher_radio');

                    function syncPublisherIdentityAvailability() {
                        if (! publisherIdentityCheckbox || ! publisherIdentityRadio) {
                            return;
                        }
                        var enabled = !! (publisherToggle && publisherToggle.checked);
                        publisherIdentityCheckbox.disabled = ! enabled;
                        publisherIdentityRadio.disabled = ! enabled;

                        if (! enabled) {
                            publisherIdentityCheckbox.checked = false;
                            var wasChecked = publisherIdentityRadio.checked;
                            publisherIdentityRadio.checked = false;
                            var fallback = document.querySelector('.be-schema-identity-radio:not(:disabled)');
                            if (wasChecked && fallback) {
                                fallback.checked = true;
                            }
                        }

                        updateIdentityOption(publisherIdentityCheckbox);
                        updateIdentityTabLink(publisherIdentityCheckbox);
                        refreshIdentityRadios();
                    }

                    if (publisherToggle) {
                        publisherToggle.addEventListener('change', syncPublisherIdentityAvailability);
                    }
                    syncPublisherIdentityAvailability();
                })();

                // Person name fallback placeholder behavior.
                (function () {
                    var inputs = document.querySelectorAll('.be-schema-person-name');
                    if (!inputs.length) {
                        return;
                    }

                    inputs.forEach(function (input) {
                        var fallback = input.getAttribute('data-fallback') || '';
                        if (!fallback) {
                            return;
                        }

                        function applyPlaceholder() {
                            input.setAttribute('placeholder', fallback);
                        }

                        function clearPlaceholder() {
                            input.setAttribute('placeholder', '');
                        }

                        function syncPlaceholder() {
                            if (input.value.trim()) {
                                clearPlaceholder();
                            } else {
                                applyPlaceholder();
                            }
                        }

                        input.addEventListener('focus', clearPlaceholder);
                        input.addEventListener('blur', syncPlaceholder);
                        syncPlaceholder();
                    });
                })();

                // Schema preview visualizer (Playfair).
                (function () {
                    var previewRoot = document.getElementById('be-schema-tab-preview');
                    if (!previewRoot) {
                        return;
                    }

                    var preview = data.preview || {};
                    var ajaxUrl = preview.ajaxUrl || '';
                    var playfairNonce = preview.playfairNonce || '';
                    var playfairAction = preview.playfairAction || 'be_schema_playfair_capture';
                    var homeUrl = preview.homeUrl || '';
                    var marker = preview.marker || 'beseo-generated';

                    var targetInput = document.getElementById('be-schema-preview-target');
                    var targetHelp = document.getElementById('be-schema-preview-target-help');
                    var homeBtn = document.getElementById('be-schema-preview-home');
                    var createBtn = document.getElementById('be-schema-preview-create');
                    var refreshAllBtn = document.getElementById('be-schema-preview-refresh-all');
                    var clearCacheBtn = document.getElementById('be-schema-preview-clear-cache');
                    var columnsWrap = document.getElementById('be-schema-preview-columns');
                    var diffBody = document.getElementById('be-schema-preview-diff-body');

                    var locationInputs = previewRoot.querySelectorAll('input[name="be_schema_preview_location"]');
                    var captureInputs = previewRoot.querySelectorAll('input[name="be_schema_preview_capture"]');
                    var viewInputs = previewRoot.querySelectorAll('input[name="be_schema_preview_view"]');
                    var colourSelect = document.getElementById('be-schema-preview-colour');
                    var addBeseoInput = document.getElementById('be-schema-preview-add-beseo');

                    var locationHelp = document.getElementById('be-schema-preview-location-help');
                    var colourHelp = document.getElementById('be-schema-preview-colour-help');
                    var beseoHelp = document.getElementById('be-schema-preview-beseo-help');

                    var STORAGE_KEY = 'beSchemaPreviewV2';
                    var runtime = {
                        loading: {},
                        compareSelection: [],
                        diffState: null
                    };
                    var state = loadState();

                    function loadState() {
                        var fallback = { columns: [], cache: {}, lastTarget: '' };
                        if (!window.localStorage) {
                            return fallback;
                        }
                        try {
                            var raw = window.localStorage.getItem(STORAGE_KEY);
                            if (!raw) {
                                return fallback;
                            }
                            var parsed = JSON.parse(raw);
                            if (!parsed || typeof parsed !== 'object') {
                                return fallback;
                            }
                            if (!Array.isArray(parsed.columns)) {
                                parsed.columns = [];
                            }
                            if (!parsed.cache || typeof parsed.cache !== 'object') {
                                parsed.cache = {};
                            }
                            if (!parsed.lastTarget) {
                                parsed.lastTarget = '';
                            }
                            return parsed;
                        } catch (err) {
                            return fallback;
                        }
                    }

                    function saveState() {
                        if (!window.localStorage) {
                            return;
                        }
                        try {
                            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
                        } catch (err) {
                            // Ignore storage errors.
                        }
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

                    function setRadioValue(inputs, value) {
                        inputs.forEach(function (input) {
                            input.checked = input.value === value;
                        });
                    }

                    function getCriteria() {
                        return {
                            location: getRadioValue(locationInputs) || 'internal',
                            capture: getRadioValue(captureInputs) || 'server',
                            view: getRadioValue(viewInputs) || 'graph',
                            colour: colourSelect ? colourSelect.value : 'keyword',
                            addBeseo: !!(addBeseoInput && addBeseoInput.checked)
                        };
                    }

                    function getControlAvailability(criteria) {
                        var availability = {
                            addBeseo: { enabled: true, reason: '' },
                            colour: {
                                source: { enabled: true, reason: '' }
                            }
                        };

                        if (criteria.location === 'external') {
                            availability.addBeseo.enabled = false;
                            availability.addBeseo.reason = 'External renders cannot inject BE SEO.';
                        }

                        if (criteria.location === 'external' || !criteria.addBeseo) {
                            availability.colour.source.enabled = false;
                            availability.colour.source.reason = criteria.location === 'external'
                                ? 'Source colouring requires BE SEO injection (internal renders only).'
                                : 'Source colouring requires BE SEO injection.';
                        }

                        return availability;
                    }

                    function setHelpText(el, text) {
                        if (!el) {
                            return;
                        }
                        el.textContent = text || '';
                    }

                    function applyAvailability() {
                        var criteria = getCriteria();
                        var availability = getControlAvailability(criteria);

                        if (addBeseoInput) {
                            addBeseoInput.disabled = !availability.addBeseo.enabled;
                            if (!availability.addBeseo.enabled && addBeseoInput.checked) {
                                addBeseoInput.checked = false;
                                criteria.addBeseo = false;
                            }
                            setHelpText(beseoHelp, availability.addBeseo.enabled ? '' : availability.addBeseo.reason);
                        }

                        if (colourSelect) {
                            var sourceOption = colourSelect.querySelector('option[value="source"]');
                            if (sourceOption) {
                                sourceOption.disabled = !availability.colour.source.enabled;
                                if (sourceOption.disabled && colourSelect.value === 'source') {
                                    colourSelect.value = 'keyword';
                                    criteria.colour = 'keyword';
                                }
                            }
                            setHelpText(colourHelp, availability.colour.source.enabled ? '' : availability.colour.source.reason);
                        }

                        return criteria;
                    }

                    function getTargetValue() {
                        var value = targetInput ? targetInput.value.trim() : '';
                        if (targetHelp) {
                            targetHelp.textContent = value ? '' : 'Enter a URL or post ID.';
                        }
                        return value;
                    }

                    function setTargetValue(value) {
                        if (!targetInput) {
                            return;
                        }
                        targetInput.value = value || '';
                        if (targetHelp) {
                            targetHelp.textContent = '';
                        }
                    }

                    function formatTime(iso) {
                        if (!iso) {
                            return '';
                        }
                        var date = new Date(iso);
                        if (isNaN(date.getTime())) {
                            return '';
                        }
                        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    }

                    function buildCaptureKey(target, criteria) {
                        return [
                            target || '',
                            criteria.location,
                            criteria.capture,
                            criteria.addBeseo ? '1' : '0'
                        ].join('|');
                    }

                    function ensureCriteriaFromInputs() {
                        return applyAvailability();
                    }

                    function createColumn() {
                        var criteria = ensureCriteriaFromInputs();
                        var target = getTargetValue();
                        if (!target) {
                            return;
                        }

                        state.lastTarget = target;
                        saveState();

                        var now = new Date().toISOString();
                        var column = {
                            id: 'col-' + Date.now() + '-' + Math.floor(Math.random() * 10000),
                            target: target,
                            criteria: criteria,
                            captureKey: buildCaptureKey(target, criteria),
                            createdAt: now,
                            lastCapturedAt: null,
                            lastError: null,
                            collapsed: false
                        };

                        state.columns.push(column);
                        saveState();
                        renderAll();
                        ensureCapture(column, false);
                    }

                    function ensureCapture(column, force) {
                        var cached = state.cache[column.captureKey];
                        if (!force && cached && cached.payload) {
                            column.lastCapturedAt = cached.capturedAt;
                            column.lastError = cached.error || null;
                            saveState();
                            renderAll();
                            return;
                        }
                        runCapture(column);
                    }

                    function runCapture(column) {
                        if (!ajaxUrl || !playfairNonce) {
                            column.lastError = { message: 'Playfair is not configured.' };
                            saveState();
                            renderAll();
                            return;
                        }

                        runtime.loading[column.id] = true;
                        column.lastError = null;
                        saveState();
                        renderAll();

                        var queryArgs = {};
                        if (column.criteria.location === 'internal') {
                            queryArgs.beseo_preview = 1;
                            if (column.criteria.addBeseo) {
                                queryArgs.beseo_marker = 1;
                            } else {
                                queryArgs.beseo_add = 0;
                            }
                        }

                        var payload = {
                            action: playfairAction,
                            nonce: playfairNonce,
                            url: column.target,
                            mode: column.criteria.location === 'internal' ? 'local' : 'remote',
                            include_logs: 1,
                            include_html: 0
                        };

                        if (Object.keys(queryArgs).length) {
                            payload.query_args = JSON.stringify(queryArgs);
                        }

                        var body = Object.keys(payload).map(function (key) {
                            return encodeURIComponent(key) + '=' + encodeURIComponent(payload[key]);
                        }).join('&');

                        var controller = window.AbortController ? new AbortController() : null;
                        var timeoutId = null;
                        if (controller) {
                            timeoutId = window.setTimeout(function () {
                                controller.abort();
                            }, 300000);
                        }

                        function finalize() {
                            if (timeoutId) {
                                window.clearTimeout(timeoutId);
                            }
                            runtime.loading[column.id] = false;
                        }

                        function handleError(message, details) {
                            finalize();
                            column.lastError = {
                                message: message || 'Capture failed.',
                                details: details || null
                            };
                            saveState();
                            renderAll();
                        }

                        if (window.fetch) {
                            fetch(ajaxUrl, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                                },
                                body: body,
                                signal: controller ? controller.signal : undefined
                            })
                                .then(function (response) {
                                    return response.json();
                                })
                                .then(function (response) {
                                    finalize();
                                    if (!response || !response.success) {
                                        var msg = response && response.data && response.data.message ? response.data.message : 'Capture failed.';
                                        handleError(msg, response && response.data ? response.data : null);
                                        return;
                                    }
                                    var now = new Date().toISOString();
                                    state.cache[column.captureKey] = {
                                        payload: response.data,
                                        capturedAt: now
                                    };
                                    column.lastCapturedAt = now;
                                    column.lastError = null;
                                    saveState();
                                    renderAll();
                                })
                                .catch(function (err) {
                                    if (err && err.name === 'AbortError') {
                                        handleError('Capture timed out. Try reducing waitMs or target size.');
                                        return;
                                    }
                                    handleError('Capture request failed.', err ? { error: String(err) } : null);
                                });
                            return;
                        }

                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', ajaxUrl, true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                        xhr.onreadystatechange = function () {
                            if (xhr.readyState !== 4) {
                                return;
                            }
                            finalize();
                            try {
                                var json = JSON.parse(xhr.responseText);
                                if (!json || !json.success) {
                                    var msg = json && json.data && json.data.message ? json.data.message : 'Capture failed.';
                                    handleError(msg, json && json.data ? json.data : null);
                                    return;
                                }
                                var now = new Date().toISOString();
                                state.cache[column.captureKey] = {
                                    payload: json.data,
                                    capturedAt: now
                                };
                                column.lastCapturedAt = now;
                                column.lastError = null;
                                saveState();
                                renderAll();
                            } catch (err) {
                                handleError('Capture response failed.', err ? { error: String(err) } : null);
                            }
                        };
                        xhr.send(body);
                    }

                    function escapeHtml(text) {
                        return (text || '').replace(/[&<>"']/g, function (char) {
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

                    function highlightJson(text) {
                        var source = (text || '').toString();
                        var regex = /("(\\u[a-fA-F0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d+)?(?:[eE][+\-]?\d+)?)/g;
                        var result = '';
                        var lastIndex = 0;
                        var match;
                        while ((match = regex.exec(source)) !== null) {
                            var token = match[0];
                            var index = match.index;
                            result += escapeHtml(source.slice(lastIndex, index));
                            var cls = 'json-number';
                            if (token.charAt(0) === '"') {
                                if (/:$/.test(token)) {
                                    cls = 'json-key';
                                } else {
                                    cls = 'json-string';
                                }
                            } else if (token === 'true' || token === 'false') {
                                cls = 'json-boolean';
                            } else if (token === 'null') {
                                cls = 'json-null';
                            }
                            result += '<span class="' + cls + '">' + escapeHtml(token) + '</span>';
                            lastIndex = index + token.length;
                        }
                        result += escapeHtml(source.slice(lastIndex));
                        return result;
                    }

                    function stableStringify(value) {
                        if (value === null || typeof value !== 'object') {
                            return JSON.stringify(value);
                        }
                        if (Array.isArray(value)) {
                            return '[' + value.map(stableStringify).join(',') + ']';
                        }
                        var keys = Object.keys(value).sort();
                        var props = keys.map(function (key) {
                            return JSON.stringify(key) + ':' + stableStringify(value[key]);
                        });
                        return '{' + props.join(',') + '}';
                    }

                    function safeParseJson(raw) {
                        if (!raw) {
                            return null;
                        }
                        try {
                            return JSON.parse(raw);
                        } catch (err) {
                            return null;
                        }
                    }

                    function normalizeType(value) {
                        if (Array.isArray(value)) {
                            return value.join(', ');
                        }
                        return value ? value.toString() : 'Thing';
                    }

                    function truncate(text, max) {
                        var value = (text || '').toString();
                        if (value.length <= max) {
                            return value;
                        }
                        return value.slice(0, Math.max(0, max - 3)) + '...';
                    }

                    function hashString(value) {
                        var hash = 0;
                        var str = value || '';
                        for (var i = 0; i < str.length; i++) {
                            hash = ((hash << 5) - hash) + str.charCodeAt(i);
                            hash |= 0;
                        }
                        return Math.abs(hash);
                    }

                    function getKeywordColor(keyword) {
                        var palette = ['#2563eb', '#16a34a', '#ea580c', '#7c3aed', '#0d9488', '#dc2626', '#9333ea', '#0891b2'];
                        var index = hashString(keyword) % palette.length;
                        return palette[index];
                    }

                    function getSourceColor(source) {
                        if (source === 'beseo') {
                            return '#16a34a';
                        }
                        if (source === 'unknown') {
                            return '#f59e0b';
                        }
                        return '#94a3b8';
                    }

                    function nodeHasMarker(node) {
                        if (!node || typeof node !== 'object') {
                            return false;
                        }
                        if (!node.identifier) {
                            return false;
                        }
                        if (Array.isArray(node.identifier)) {
                            return node.identifier.indexOf(marker) !== -1;
                        }
                        return node.identifier === marker;
                    }

                    function extractNodesFromParsed(parsed) {
                        var nodes = [];
                        if (!parsed || typeof parsed !== 'object') {
                            return nodes;
                        }
                        if (Array.isArray(parsed)) {
                            parsed.forEach(function (item) {
                                nodes = nodes.concat(extractNodesFromParsed(item));
                            });
                            return nodes;
                        }
                        if (Array.isArray(parsed['@graph'])) {
                            parsed['@graph'].forEach(function (item) {
                                if (item && typeof item === 'object') {
                                    nodes.push(item);
                                }
                            });
                            return nodes;
                        }
                        if (parsed['@type'] || parsed['@id']) {
                            nodes.push(parsed);
                        }
                        return nodes;
                    }

                    function normalizeSchemaEntries(entries) {
                        if (!Array.isArray(entries)) {
                            return [];
                        }
                        return entries.map(function (entry, index) {
                            var parsed = entry && entry.parsed ? entry.parsed : safeParseJson(entry ? entry.raw : '');
                            var raw = entry && entry.raw ? entry.raw : (parsed ? JSON.stringify(parsed, null, 2) : '');
                            return {
                                index: index,
                                raw: raw || '',
                                parsed: parsed
                            };
                        });
                    }

                    function buildGraphData(entries, colourMode, diffHighlight) {
                        var graph = [];
                        entries.forEach(function (entry) {
                            if (!entry.parsed) {
                                return;
                            }
                            graph = graph.concat(extractNodesFromParsed(entry.parsed));
                        });

                        var nodes = [];
                        var nodesById = {};
                        graph.forEach(function (node, index) {
                            if (!node || typeof node !== 'object') {
                                return;
                            }
                            var type = node['@type'];
                            if (!type) {
                                return;
                            }
                            var id = node['@id'] || node.url || ('node-' + index);
                            if (nodesById[id]) {
                                return;
                            }
                            var name = node.name || node.headline || node.url || node['@id'] || id;
                            var typeLabel = normalizeType(type);
                            var source = nodeHasMarker(node) ? 'beseo' : 'unknown';
                            var key = node['@id'] || (typeLabel + '|' + (name || '') + '|' + (node.url || ''));
                            var color = colourMode === 'source' ? getSourceColor(source) : getKeywordColor(typeLabel);
                            var entryNode = {
                                id: id,
                                key: key,
                                type: typeLabel,
                                name: name ? name.toString() : typeLabel,
                                source: source,
                                color: color
                            };

                            if (diffHighlight && diffHighlight.keys && diffHighlight.keys[entryNode.key]) {
                                entryNode.diffStatus = diffHighlight.keys[entryNode.key];
                            }

                            nodes.push(entryNode);
                            nodesById[id] = entryNode;
                        });

                        var edges = [];
                        graph.forEach(function (node, index) {
                            if (!node || typeof node !== 'object') {
                                return;
                            }
                            var fromId = node['@id'] || node.url || ('node-' + index);
                            if (!nodesById[fromId]) {
                                return;
                            }
                            Object.keys(node).forEach(function (key) {
                                if (key === '@id' || key === '@type' || key === '@context') {
                                    return;
                                }
                                collectEdges(node[key], key, fromId, edges, nodesById);
                            });
                        });

                        return {
                            nodes: nodes,
                            edges: edges
                        };
                    }

                    function collectEdges(value, key, fromId, edges, nodesById) {
                        if (!value) {
                            return;
                        }
                        if (Array.isArray(value)) {
                            value.forEach(function (item) {
                                collectEdges(item, key, fromId, edges, nodesById);
                            });
                            return;
                        }
                        if (typeof value === 'object') {
                            if (value['@id']) {
                                if (nodesById[value['@id']]) {
                                    edges.push({
                                        from: fromId,
                                        to: value['@id'],
                                        label: key
                                    });
                                }
                            } else {
                                Object.keys(value).forEach(function (subKey) {
                                    collectEdges(value[subKey], key, fromId, edges, nodesById);
                                });
                            }
                        }
                    }

                    function renderGraph(container, graphData) {
                        if (!container) {
                            return;
                        }
                        container.innerHTML = '';
                        if (!graphData || !graphData.nodes.length) {
                            container.innerHTML = '<p class="be-schema-preview-empty">No graph nodes found.</p>';
                            return;
                        }

                        var nodes = graphData.nodes;
                        var edges = graphData.edges;
                        var nodeWidth = 180;
                        var nodeHeight = 60;
                        var colGap = 40;
                        var rowGap = 40;
                        var padding = 20;
                        var cols = Math.min(4, Math.max(1, Math.ceil(Math.sqrt(nodes.length))));
                        var rows = Math.ceil(nodes.length / cols);
                        var width = padding * 2 + cols * nodeWidth + (cols - 1) * colGap;
                        var height = padding * 2 + rows * nodeHeight + (rows - 1) * rowGap;

                        var ns = 'http://www.w3.org/2000/svg';
                        var svg = document.createElementNS(ns, 'svg');
                        svg.setAttribute('width', width);
                        svg.setAttribute('height', height);
                        svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);

                        var nodesById = {};
                        nodes.forEach(function (node, index) {
                            var col = index % cols;
                            var row = Math.floor(index / cols);
                            node.x = padding + col * (nodeWidth + colGap);
                            node.y = padding + row * (nodeHeight + rowGap);
                            nodesById[node.id] = node;
                        });

                        edges.forEach(function (edge) {
                            var from = nodesById[edge.from];
                            var to = nodesById[edge.to];
                            if (!from || !to) {
                                return;
                            }
                            var line = document.createElementNS(ns, 'line');
                            line.setAttribute('x1', from.x + nodeWidth / 2);
                            line.setAttribute('y1', from.y + nodeHeight / 2);
                            line.setAttribute('x2', to.x + nodeWidth / 2);
                            line.setAttribute('y2', to.y + nodeHeight / 2);
                            var edgeStatus = from.diffStatus || to.diffStatus;
                            var edgeStroke = '#94a3b8';
                            if (edgeStatus === 'added') {
                                edgeStroke = '#16a34a';
                            } else if (edgeStatus === 'removed') {
                                edgeStroke = '#dc2626';
                            } else if (edgeStatus === 'changed') {
                                edgeStroke = '#f59e0b';
                            }
                            line.setAttribute('stroke', edgeStroke);
                            line.setAttribute('stroke-width', edgeStatus ? '1.5' : '1');
                            line.setAttribute('stroke-linecap', 'round');
                            svg.appendChild(line);
                        });

                        nodes.forEach(function (node) {
                            var group = document.createElementNS(ns, 'g');
                            var rect = document.createElementNS(ns, 'rect');
                            rect.setAttribute('x', node.x);
                            rect.setAttribute('y', node.y);
                            rect.setAttribute('width', nodeWidth);
                            rect.setAttribute('height', nodeHeight);
                            rect.setAttribute('rx', '4');
                            rect.setAttribute('ry', '4');

                            var stroke = node.color || '#ccd0d4';
                            var fill = '#ffffff';
                            if (node.diffStatus === 'added') {
                                stroke = '#16a34a';
                                fill = '#ecfdf3';
                            } else if (node.diffStatus === 'removed') {
                                stroke = '#dc2626';
                                fill = '#fef2f2';
                            } else if (node.diffStatus === 'changed') {
                                stroke = '#f59e0b';
                                fill = '#fff7ed';
                            }

                            rect.setAttribute('fill', fill);
                            rect.setAttribute('stroke', stroke);
                            rect.setAttribute('stroke-width', '1');
                            group.appendChild(rect);

                            var typeText = document.createElementNS(ns, 'text');
                            typeText.setAttribute('x', node.x + 8);
                            typeText.setAttribute('y', node.y + 20);
                            typeText.setAttribute('font-size', '11');
                            typeText.setAttribute('font-weight', '600');
                            typeText.setAttribute('fill', '#1d2327');
                            typeText.textContent = truncate(node.type, 24);
                            group.appendChild(typeText);

                            var nameText = document.createElementNS(ns, 'text');
                            nameText.setAttribute('x', node.x + 8);
                            nameText.setAttribute('y', node.y + 38);
                            nameText.setAttribute('font-size', '10');
                            nameText.setAttribute('fill', '#3c434a');
                            nameText.textContent = truncate(node.name, 30);
                            group.appendChild(nameText);

                            svg.appendChild(group);
                        });

                        container.appendChild(svg);
                    }

                    function buildColumnSummary(entries, payload) {
                        var nodeCount = 0;
                        entries.forEach(function (entry) {
                            if (!entry.parsed) {
                                return;
                            }
                            nodeCount += extractNodesFromParsed(entry.parsed).length;
                        });
                        var errorCount = 0;
                        if (payload && payload.logs) {
                            var logs = payload.logs;
                            errorCount += (logs.pageErrors || []).length;
                            errorCount += (logs.requestFailed || []).length;
                        }
                        return {
                            blocks: entries.length,
                            nodes: nodeCount,
                            errors: errorCount
                        };
                    }

                    function getSchemaEntries(payload, captureMode) {
                        if (!payload || !payload.schema) {
                            return [];
                        }
                        var entries = payload.schema[captureMode] || [];
                        return Array.isArray(entries) ? normalizeSchemaEntries(entries) : [];
                    }

                    function formatCriteriaSummary(criteria) {
                        var bits = [];
                        bits.push(criteria.location === 'internal' ? 'Internal' : 'External');
                        bits.push(criteria.capture === 'dom' ? 'DOM' : 'Server');
                        bits.push(criteria.view === 'json' ? 'JSON-LD' : 'Graph');
                        bits.push('Colour: ' + (criteria.colour === 'source' ? 'Source' : 'Keyword'));
                        bits.push('BE SEO: ' + (criteria.addBeseo ? 'On' : 'Off'));
                        return bits.join(' | ');
                    }

                    function normalizeColumnCriteria(criteria) {
                        var normalized = {
                            location: 'internal',
                            capture: 'server',
                            view: 'graph',
                            colour: 'keyword',
                            addBeseo: true
                        };
                        if (criteria && typeof criteria === 'object') {
                            if (criteria.location) {
                                normalized.location = criteria.location;
                            }
                            if (criteria.capture) {
                                normalized.capture = criteria.capture;
                            }
                            if (criteria.view) {
                                normalized.view = criteria.view;
                            }
                            if (criteria.colour) {
                                normalized.colour = criteria.colour;
                            }
                            if (typeof criteria.addBeseo !== 'undefined') {
                                normalized.addBeseo = !!criteria.addBeseo;
                            }
                        }
                        return normalized;
                    }

                    function formatColumnSource(source) {
                        if (source === 'beseo') {
                            return 'BE SEO';
                        }
                        if (source === 'unknown') {
                            return 'Unknown';
                        }
                        return 'Other';
                    }

                    function getBlockInfo(entry) {
                        var nodes = entry.parsed ? extractNodesFromParsed(entry.parsed) : [];
                        var typeLabel = 'Thing';
                        if (nodes.length) {
                            var rawType = nodes[0]['@type'];
                            if (rawType) {
                                typeLabel = normalizeType(rawType);
                            }
                        }
                        var source = nodes.some(nodeHasMarker) ? 'beseo' : 'unknown';
                        return {
                            type: typeLabel,
                            source: source
                        };
                    }

                    function renderJsonBlocks(container, entries, colourMode) {
                        container.innerHTML = '';
                        if (!entries.length) {
                            container.innerHTML = '<p class="be-schema-preview-empty">No JSON-LD blocks found.</p>';
                            return;
                        }

                        var blocksWrap = document.createElement('div');
                        blocksWrap.className = 'be-schema-preview-json-blocks';

                        entries.forEach(function (entry, index) {
                            var info = getBlockInfo(entry);
                            var colour = colourMode === 'source' ? getSourceColor(info.source) : getKeywordColor(info.type);

                            var block = document.createElement('div');
                            block.className = 'be-schema-preview-json-block';
                            block.style.setProperty('--be-schema-block-color', colour);

                            var header = document.createElement('div');
                            header.className = 'be-schema-preview-json-block-header';

                            var tag = document.createElement('span');
                            tag.className = 'be-schema-preview-json-block-tag';
                            tag.textContent = 'JSON-LD #' + (index + 1) + ' • ' + info.type;

                            var source = document.createElement('span');
                            source.textContent = 'Source: ' + formatColumnSource(info.source);

                            header.appendChild(tag);
                            header.appendChild(source);

                            var pre = document.createElement('pre');
                            pre.className = 'be-schema-json-code';
                            pre.innerHTML = highlightJson(entry.raw || '');

                            block.appendChild(header);
                            block.appendChild(pre);
                            blocksWrap.appendChild(block);
                        });

                        container.appendChild(blocksWrap);
                    }

                    function buildNodeIndex(entries) {
                        var index = {};
                        entries.forEach(function (entry) {
                            if (!entry.parsed) {
                                return;
                            }
                            var nodes = extractNodesFromParsed(entry.parsed);
                            nodes.forEach(function (node) {
                                if (!node || typeof node !== 'object') {
                                    return;
                                }
                                var typeLabel = normalizeType(node['@type'] || 'Thing');
                                var name = node.name || node.headline || node.url || '';
                                var key = node['@id'] || (typeLabel + '|' + name + '|' + (node.url || ''));
                                if (!index[key]) {
                                    index[key] = stableStringify(node);
                                }
                            });
                        });
                        return index;
                    }

                    function buildBlockSet(entries) {
                        var set = {};
                        entries.forEach(function (entry) {
                            var parsed = entry.parsed || safeParseJson(entry.raw);
                            if (!parsed) {
                                return;
                            }
                            var fingerprint = stableStringify(parsed);
                            set[fingerprint] = true;
                        });
                        return set;
                    }

                    function computeDiffState() {
                        if (runtime.compareSelection.length < 2) {
                            return null;
                        }
                        var leftId = runtime.compareSelection[0];
                        var rightId = runtime.compareSelection[1];
                        var leftCol = state.columns.find(function (col) { return col.id === leftId; });
                        var rightCol = state.columns.find(function (col) { return col.id === rightId; });
                        if (!leftCol || !rightCol) {
                            return null;
                        }

                        var leftPayload = state.cache[leftCol.captureKey] ? state.cache[leftCol.captureKey].payload : null;
                        var rightPayload = state.cache[rightCol.captureKey] ? state.cache[rightCol.captureKey].payload : null;

                        if (!leftPayload || !rightPayload) {
                            return {
                                leftId: leftId,
                                rightId: rightId,
                                error: 'Capture data missing for one or both columns.'
                            };
                        }

                        var leftEntries = getSchemaEntries(leftPayload, leftCol.criteria.capture);
                        var rightEntries = getSchemaEntries(rightPayload, rightCol.criteria.capture);

                        var leftBlocks = buildBlockSet(leftEntries);
                        var rightBlocks = buildBlockSet(rightEntries);

                        var addedBlocks = [];
                        var removedBlocks = [];
                        Object.keys(rightBlocks).forEach(function (fingerprint) {
                            if (!leftBlocks[fingerprint]) {
                                addedBlocks.push(fingerprint);
                            }
                        });
                        Object.keys(leftBlocks).forEach(function (fingerprint) {
                            if (!rightBlocks[fingerprint]) {
                                removedBlocks.push(fingerprint);
                            }
                        });

                        var leftIndex = buildNodeIndex(leftEntries);
                        var rightIndex = buildNodeIndex(rightEntries);
                        var addedNodes = [];
                        var removedNodes = [];
                        var changedNodes = [];

                        Object.keys(rightIndex).forEach(function (key) {
                            if (!leftIndex[key]) {
                                addedNodes.push(key);
                            } else if (leftIndex[key] !== rightIndex[key]) {
                                changedNodes.push(key);
                            }
                        });
                        Object.keys(leftIndex).forEach(function (key) {
                            if (!rightIndex[key]) {
                                removedNodes.push(key);
                            }
                        });

                        var highlightKeysLeft = {};
                        removedNodes.forEach(function (key) {
                            highlightKeysLeft[key] = 'removed';
                        });
                        changedNodes.forEach(function (key) {
                            highlightKeysLeft[key] = 'changed';
                        });

                        var highlightKeysRight = {};
                        addedNodes.forEach(function (key) {
                            highlightKeysRight[key] = 'added';
                        });
                        changedNodes.forEach(function (key) {
                            highlightKeysRight[key] = 'changed';
                        });

                        return {
                            leftId: leftId,
                            rightId: rightId,
                            blocks: {
                                added: addedBlocks,
                                removed: removedBlocks
                            },
                            nodes: {
                                added: addedNodes,
                                removed: removedNodes,
                                changed: changedNodes
                            },
                            highlights: {
                                left: highlightKeysLeft,
                                right: highlightKeysRight
                            }
                        };
                    }

                    function renderDiff(diffState) {
                        if (!diffBody) {
                            return;
                        }
                        diffBody.innerHTML = '';

                        if (!diffState) {
                            diffBody.innerHTML = '<p class="be-schema-preview-empty">Select two columns to compare.</p>';
                            return;
                        }

                        if (diffState.error) {
                            diffBody.innerHTML = '<p class="be-schema-preview-empty">' + escapeHtml(diffState.error) + '</p>';
                            return;
                        }

                        var summary = document.createElement('div');
                        summary.className = 'be-schema-preview-diff-summary';
                        summary.textContent = 'Blocks +' + diffState.blocks.added.length + ' / -' + diffState.blocks.removed.length +
                            ' · Nodes +' + diffState.nodes.added.length + ' / -' + diffState.nodes.removed.length + ' / Δ' + diffState.nodes.changed.length;
                        diffBody.appendChild(summary);

                        var grid = document.createElement('div');
                        grid.className = 'be-schema-preview-diff-grid';

                        function buildPanel(title, items) {
                            var panel = document.createElement('div');
                            panel.className = 'be-schema-preview-diff-panel';
                            var heading = document.createElement('h5');
                            heading.textContent = title + ' (' + items.length + ')';
                            panel.appendChild(heading);
                            if (!items.length) {
                                var empty = document.createElement('p');
                                empty.className = 'be-schema-preview-empty';
                                empty.textContent = 'None';
                                panel.appendChild(empty);
                                return panel;
                            }
                            var list = document.createElement('ul');
                            list.className = 'be-schema-preview-diff-list';
                            items.slice(0, 12).forEach(function (item) {
                                var li = document.createElement('li');
                                li.textContent = item;
                                list.appendChild(li);
                            });
                            if (items.length > 12) {
                                var more = document.createElement('li');
                                more.textContent = '… +' + (items.length - 12) + ' more';
                                list.appendChild(more);
                            }
                            panel.appendChild(list);
                            return panel;
                        }

                        grid.appendChild(buildPanel('Added blocks', diffState.blocks.added));
                        grid.appendChild(buildPanel('Removed blocks', diffState.blocks.removed));
                        grid.appendChild(buildPanel('Added nodes', diffState.nodes.added));
                        grid.appendChild(buildPanel('Removed nodes', diffState.nodes.removed));
                        grid.appendChild(buildPanel('Changed nodes', diffState.nodes.changed));
                        diffBody.appendChild(grid);
                    }

                    function renderColumns(diffState) {
                        if (!columnsWrap) {
                            return;
                        }
                        columnsWrap.innerHTML = '';

                        if (!state.columns.length) {
                            columnsWrap.innerHTML = '<p class="be-schema-preview-empty">No output columns yet. Use Render Criteria to create one.</p>';
                            return;
                        }

                        state.columns.forEach(function (column, index) {
                            column.criteria = normalizeColumnCriteria(column.criteria);
                            var payload = state.cache[column.captureKey] ? state.cache[column.captureKey].payload : null;
                            var entries = payload ? getSchemaEntries(payload, column.criteria.capture) : [];
                            var summary = buildColumnSummary(entries, payload);
                            var isLoading = !!runtime.loading[column.id];
                            var columnEl = document.createElement('div');
                            columnEl.className = 'be-schema-preview-column' + (column.collapsed ? ' is-collapsed' : '');
                            columnEl.setAttribute('data-column-id', column.id);

                            var header = document.createElement('div');
                            header.className = 'be-schema-preview-column-header';

                            var heading = document.createElement('div');
                            heading.className = 'be-schema-preview-column-heading';

                            var title = document.createElement('div');
                            title.className = 'be-schema-preview-column-title';
                            title.textContent = formatCriteriaSummary(column.criteria);

                            var meta = document.createElement('div');
                            meta.className = 'be-schema-preview-column-meta';
                            meta.textContent = column.lastCapturedAt ? ('Captured ' + formatTime(column.lastCapturedAt)) : 'Not captured yet';

                            var summaryEl = document.createElement('div');
                            summaryEl.className = 'be-schema-preview-column-summary';
                            summaryEl.textContent = 'JSON-LD blocks: ' + summary.blocks + ' | Nodes: ' + summary.nodes + ' | Errors: ' + summary.errors;

                            heading.appendChild(title);
                            heading.appendChild(meta);
                            heading.appendChild(summaryEl);

                            var controls = document.createElement('div');
                            controls.className = 'be-schema-preview-column-controls';

                            var collapseBtn = document.createElement('button');
                            collapseBtn.type = 'button';
                            collapseBtn.className = 'button-link be-schema-preview-collapse';
                            collapseBtn.setAttribute('aria-expanded', column.collapsed ? 'false' : 'true');
                            collapseBtn.setAttribute('aria-label', column.collapsed ? 'Expand' : 'Collapse');
                            collapseBtn.innerHTML = '<span class="screen-reader-text">' + (column.collapsed ? 'Expand' : 'Collapse') + '</span>';
                            collapseBtn.addEventListener('click', function () {
                                column.collapsed = !column.collapsed;
                                saveState();
                                renderAll();
                            });

                            var refreshBtn = document.createElement('button');
                            refreshBtn.type = 'button';
                            refreshBtn.className = 'button';
                            refreshBtn.textContent = 'Refresh';
                            refreshBtn.addEventListener('click', function () {
                                ensureCapture(column, true);
                            });

                            var moveLeftBtn = document.createElement('button');
                            moveLeftBtn.type = 'button';
                            moveLeftBtn.className = 'button';
                            moveLeftBtn.textContent = '←';
                            moveLeftBtn.disabled = index === 0;
                            moveLeftBtn.addEventListener('click', function () {
                                moveColumn(column.id, -1);
                            });

                            var moveRightBtn = document.createElement('button');
                            moveRightBtn.type = 'button';
                            moveRightBtn.className = 'button';
                            moveRightBtn.textContent = '→';
                            moveRightBtn.disabled = index === state.columns.length - 1;
                            moveRightBtn.addEventListener('click', function () {
                                moveColumn(column.id, 1);
                            });

                            var deleteBtn = document.createElement('button');
                            deleteBtn.type = 'button';
                            deleteBtn.className = 'button';
                            deleteBtn.textContent = '−';
                            deleteBtn.addEventListener('click', function () {
                                deleteColumn(column.id);
                            });

                            var compareLabel = document.createElement('label');
                            compareLabel.className = 'be-schema-preview-compare';
                            var compareInput = document.createElement('input');
                            compareInput.type = 'checkbox';
                            compareInput.checked = runtime.compareSelection.indexOf(column.id) !== -1;
                            compareInput.addEventListener('change', function () {
                                toggleCompare(column.id, compareInput.checked);
                            });
                            compareLabel.appendChild(compareInput);
                            compareLabel.appendChild(document.createTextNode('Compare'));

                            controls.appendChild(collapseBtn);
                            controls.appendChild(refreshBtn);
                            controls.appendChild(moveLeftBtn);
                            controls.appendChild(moveRightBtn);
                            controls.appendChild(deleteBtn);
                            controls.appendChild(compareLabel);

                            header.appendChild(heading);
                            header.appendChild(controls);

                            var body = document.createElement('div');
                            body.className = 'be-schema-preview-column-body';

                            if (isLoading) {
                                var loading = document.createElement('div');
                                loading.className = 'be-schema-preview-status is-active';
                                loading.textContent = 'Capturing schema via Playfair...';
                                body.appendChild(loading);
                            } else if (column.lastError) {
                                var error = document.createElement('div');
                                error.className = 'be-schema-preview-status is-active is-error';
                                error.textContent = column.lastError.message || 'Capture failed.';
                                body.appendChild(error);

                                var retry = document.createElement('button');
                                retry.type = 'button';
                                retry.className = 'button';
                                retry.textContent = 'Retry';
                                retry.addEventListener('click', function () {
                                    ensureCapture(column, true);
                                });
                                body.appendChild(retry);

                                if (column.lastError.details) {
                                    var details = document.createElement('details');
                                    var summaryTag = document.createElement('summary');
                                    summaryTag.textContent = 'Show details';
                                    var pre = document.createElement('pre');
                                    pre.className = 'be-schema-preview-json';
                                    pre.textContent = JSON.stringify(column.lastError.details, null, 2);
                                    details.appendChild(summaryTag);
                                    details.appendChild(pre);
                                    body.appendChild(details);
                                }
                            } else if (payload) {
                                var metaBlock = document.createElement('div');
                                metaBlock.className = 'be-schema-preview-meta';
                                metaBlock.textContent = 'Target: ' + (payload.meta && payload.meta.target ? payload.meta.target : column.target);
                                body.appendChild(metaBlock);

                                if (column.criteria.view === 'graph') {
                                    var graphWrap = document.createElement('div');
                                    graphWrap.className = 'be-schema-preview-graph';
                                    body.appendChild(graphWrap);

                                    var diffHighlight = null;
                                    if (diffState) {
                                        if (diffState.leftId === column.id) {
                                            diffHighlight = { keys: diffState.highlights.left };
                                        } else if (diffState.rightId === column.id) {
                                            diffHighlight = { keys: diffState.highlights.right };
                                        }
                                    }

                                    var graphData = buildGraphData(entries, column.criteria.colour, diffHighlight);
                                    renderGraph(graphWrap, graphData);
                                } else {
                                    var jsonWrap = document.createElement('div');
                                    renderJsonBlocks(jsonWrap, entries, column.criteria.colour);
                                    body.appendChild(jsonWrap);
                                }

                                if (payload.logs && (payload.logs.pageErrors || payload.logs.requestFailed)) {
                                    var logsDetails = document.createElement('details');
                                    var logsSummary = document.createElement('summary');
                                    logsSummary.textContent = 'Logs';
                                    logsDetails.appendChild(logsSummary);
                                    var logsPre = document.createElement('pre');
                                    logsPre.className = 'be-schema-preview-json';
                                    logsPre.textContent = JSON.stringify(payload.logs, null, 2);
                                    logsDetails.appendChild(logsPre);
                                    body.appendChild(logsDetails);
                                }
                            } else {
                                var empty = document.createElement('p');
                                empty.className = 'be-schema-preview-empty';
                                empty.textContent = 'No capture data yet.';
                                body.appendChild(empty);
                            }

                            columnEl.appendChild(header);
                            columnEl.appendChild(body);
                            columnsWrap.appendChild(columnEl);
                        });
                    }

                    function toggleCompare(columnId, enabled) {
                        var idx = runtime.compareSelection.indexOf(columnId);
                        if (enabled && idx === -1) {
                            runtime.compareSelection.push(columnId);
                            if (runtime.compareSelection.length > 2) {
                                runtime.compareSelection.shift();
                            }
                        } else if (!enabled && idx !== -1) {
                            runtime.compareSelection.splice(idx, 1);
                        }
                        renderAll();
                    }

                    function deleteColumn(columnId) {
                        state.columns = state.columns.filter(function (col) { return col.id !== columnId; });
                        runtime.compareSelection = runtime.compareSelection.filter(function (id) { return id !== columnId; });
                        saveState();
                        renderAll();
                    }

                    function moveColumn(columnId, direction) {
                        var index = state.columns.findIndex(function (col) { return col.id === columnId; });
                        if (index === -1) {
                            return;
                        }
                        var newIndex = index + direction;
                        if (newIndex < 0 || newIndex >= state.columns.length) {
                            return;
                        }
                        var temp = state.columns[index];
                        state.columns[index] = state.columns[newIndex];
                        state.columns[newIndex] = temp;
                        saveState();
                        renderAll();
                    }

                    function refreshAll() {
                        state.columns.forEach(function (column) {
                            ensureCapture(column, true);
                        });
                    }

                    function clearCache() {
                        state.cache = {};
                        state.columns.forEach(function (column) {
                            column.lastCapturedAt = null;
                            column.lastError = null;
                        });
                        saveState();
                        renderAll();
                    }

                    function renderAll() {
                        runtime.diffState = computeDiffState();
                        renderColumns(runtime.diffState);
                        renderDiff(runtime.diffState);
                    }

                    function initEventListeners() {
                        locationInputs.forEach(function (input) {
                            input.addEventListener('change', applyAvailability);
                        });
                        captureInputs.forEach(function (input) {
                            input.addEventListener('change', applyAvailability);
                        });
                        viewInputs.forEach(function (input) {
                            input.addEventListener('change', applyAvailability);
                        });
                        if (colourSelect) {
                            colourSelect.addEventListener('change', applyAvailability);
                        }
                        if (addBeseoInput) {
                            addBeseoInput.addEventListener('change', applyAvailability);
                        }

                        if (homeBtn) {
                            homeBtn.addEventListener('click', function (event) {
                                event.preventDefault();
                                if (homeUrl) {
                                    setTargetValue(homeUrl);
                                }
                            });
                        }

                        if (createBtn) {
                            createBtn.addEventListener('click', function (event) {
                                event.preventDefault();
                                createColumn();
                            });
                        }

                        if (refreshAllBtn) {
                            refreshAllBtn.addEventListener('click', function (event) {
                                event.preventDefault();
                                refreshAll();
                            });
                        }

                        if (clearCacheBtn) {
                            clearCacheBtn.addEventListener('click', function (event) {
                                event.preventDefault();
                                clearCache();
                            });
                        }
                    }

                    if (targetInput && state.lastTarget) {
                        targetInput.value = state.lastTarget;
                    }

                    applyAvailability();
                    initEventListeners();
                    renderAll();
                })();
            });
