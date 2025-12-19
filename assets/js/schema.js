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
            });
