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

                // Schema graph preview.
                (function () {
                    var previewRoot = document.getElementById('be-schema-tab-preview');
                    if (!previewRoot) {
                        return;
                    }

                    var preview = data.preview || {};
                    var ajaxUrl = preview.ajaxUrl || '';
                    var nonce = preview.nonce || '';
                    var homeUrl = preview.homeUrl || '';

                    var targetInput = document.getElementById('be-schema-preview-target');
                    var runBtn = document.getElementById('be-schema-preview-run');
                    var homeBtn = document.getElementById('be-schema-preview-home');
                    var statusEl = document.getElementById('be-schema-preview-status');
                    var graphEl = document.getElementById('be-schema-preview-graph');
                    var jsonEl = document.getElementById('be-schema-preview-json');
                    var nodeCountEl = document.getElementById('be-schema-preview-node-count');
                    var edgeCountEl = document.getElementById('be-schema-preview-edge-count');

                    function setStatus(message, type) {
                        if (!statusEl) {
                            return;
                        }
                        statusEl.textContent = message || '';
                        statusEl.className = 'be-schema-preview-status';
                        if (message) {
                            statusEl.classList.add('is-active');
                            if (type) {
                                statusEl.classList.add(type);
                            }
                        }
                    }

                    function setCounts(nodes, edges) {
                        if (nodeCountEl) {
                            nodeCountEl.textContent = String(nodes || 0);
                        }
                        if (edgeCountEl) {
                            edgeCountEl.textContent = String(edges || 0);
                        }
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

                    function setJsonOutput(text) {
                        if (!jsonEl) {
                            return;
                        }
                        if (jsonEl.tagName === 'TEXTAREA') {
                            jsonEl.value = text || '';
                            return;
                        }
                        jsonEl.innerHTML = highlightJson(text || '');
                    }

                    function clearPreview() {
                        if (graphEl) {
                            graphEl.innerHTML = '';
                        }
                        setJsonOutput('');
                        setCounts(0, 0);
                    }

                    function truncate(text, max) {
                        var value = (text || '').toString();
                        if (value.length <= max) {
                            return value;
                        }
                        return value.slice(0, Math.max(0, max - 3)) + '...';
                    }

                    function normalizeType(value) {
                        if (Array.isArray(value)) {
                            return value.join(', ');
                        }
                        return value ? value.toString() : 'Thing';
                    }

                    function buildNodes(graph) {
                        var nodes = [];
                        var map = {};
                        if (!Array.isArray(graph)) {
                            return { nodes: nodes, map: map };
                        }

                        graph.forEach(function (node, index) {
                            if (!node || typeof node !== 'object') {
                                return;
                            }
                            var type = node['@type'];
                            if (!type) {
                                return;
                            }
                            var id = node['@id'] || node.url || ('node-' + index);
                            if (map[id]) {
                                return;
                            }
                            var name = node.name || node.headline || node.url || node['@id'] || id;
                            var typeLabel = normalizeType(type);
                            var entry = {
                                id: id,
                                type: typeLabel,
                                name: name ? name.toString() : typeLabel
                            };
                            nodes.push(entry);
                            map[id] = entry;
                        });

                        return { nodes: nodes, map: map };
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

                    function buildEdges(graph, nodesById) {
                        var edges = [];
                        if (!Array.isArray(graph)) {
                            return edges;
                        }
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
                        return edges;
                    }

                    function renderGraph(nodes, edges) {
                        if (!graphEl) {
                            return;
                        }
                        graphEl.innerHTML = '';
                        if (!nodes.length) {
                            return;
                        }

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
                            line.setAttribute('stroke', '#94a3b8');
                            line.setAttribute('stroke-width', '1');
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
                            rect.setAttribute('fill', '#ffffff');
                            rect.setAttribute('stroke', '#ccd0d4');
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

                        graphEl.appendChild(svg);
                    }

                    function handlePreviewResponse(payload) {
                        clearPreview();

                        if (!payload) {
                            setStatus('No preview data returned.', 'is-error');
                            return;
                        }

                        if (payload.message && !payload.graph) {
                            setStatus(payload.message, 'is-warning');
                            return;
                        }

                        var graphPayload = payload.graph || null;
                        var graph = graphPayload && graphPayload['@graph'] ? graphPayload['@graph'] : graphPayload;
                        if (!Array.isArray(graph) || !graph.length) {
                            setStatus(payload.message || 'No graph nodes were returned.', 'is-warning');
                            return;
                        }

                        var parsed = buildNodes(graph);
                        var edges = buildEdges(graph, parsed.map);
                        renderGraph(parsed.nodes, edges);
                        setCounts(parsed.nodes.length, edges.length);

                        setJsonOutput(JSON.stringify(graphPayload, null, 2));

                        var message = 'Preview loaded.';
                        if (payload.target && payload.target.title) {
                            message = 'Preview loaded for ' + payload.target.title + '.';
                        }

                        if (payload.warnings && payload.warnings.length) {
                            message += ' ' + payload.warnings.join(' ');
                            setStatus(message, 'is-warning');
                            return;
                        }

                        setStatus(message);
                    }

                    function postData(params, onSuccess, onError) {
                        if (!ajaxUrl) {
                            setStatus('Missing AJAX URL.', 'is-error');
                            return;
                        }

                        var body = Object.keys(params).map(function (key) {
                            return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
                        }).join('&');

                        if (window.fetch) {
                            fetch(ajaxUrl, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                                },
                                body: body
                            })
                                .then(function (response) {
                                    return response.json();
                                })
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

                    function runPreview(target) {
                        var value = (target || '').toString().trim();
                        if (!value) {
                            setStatus('Enter a URL or post ID to preview.', 'is-error');
                            clearPreview();
                            return;
                        }

                        setStatus('Generating preview...');
                        postData(
                            {
                                action: 'be_schema_preview_graph',
                                nonce: nonce,
                                target: value
                            },
                            function (response) {
                                if (!response || !response.success) {
                                    var msg = (response && response.data && response.data.message) ? response.data.message : 'Preview failed.';
                                    setStatus(msg, 'is-error');
                                    clearPreview();
                                    return;
                                }
                                handlePreviewResponse(response.data);
                            },
                            function () {
                                setStatus('Preview request failed.', 'is-error');
                                clearPreview();
                            }
                        );
                    }

                    if (homeBtn) {
                        homeBtn.addEventListener('click', function (event) {
                            event.preventDefault();
                            if (homeUrl && targetInput) {
                                targetInput.value = homeUrl;
                            }
                            runPreview(targetInput ? targetInput.value : homeUrl);
                        });
                    }

                    if (runBtn) {
                        runBtn.addEventListener('click', function (event) {
                            event.preventDefault();
                            runPreview(targetInput ? targetInput.value : '');
                        });
                    }
                })();
            });
