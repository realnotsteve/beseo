/**
 * Generic optional-field dropdown/add/remove helper.
 *
 * Expects markup:
 *  - Controls wrapper: .be-schema-optional-controls with data-optional-scope="<scope>"
 *      - data-optional-hidden="input_id" (CSV of active props) optional
 *      - data-optional-singleton="prop_a,prop_b" optional
 *      - child <select> with option values matching data-optional-prop values
 *      - child .be-schema-optional-add button
 *  - Fields wrapper: id="be-schema-<scope>-optional-fields"
 *      - children with data-optional-prop="<prop>"
 *      - each removable via .be-schema-optional-remove[data-optional-remove="<prop>"]
 */
(function () {
    function toList(value) {
        return (value || '')
            .split(',')
            .map(function (v) { return v.trim(); })
            .filter(Boolean);
    }

    function getAllProps(fieldsEl) {
        return Array.prototype.map.call(fieldsEl.querySelectorAll('[data-optional-prop]'), function (field) {
            return field.getAttribute('data-optional-prop');
        }).filter(Boolean);
    }

    function getActiveProps(fieldsEl) {
        return Array.prototype.map.call(fieldsEl.querySelectorAll('[data-optional-prop]'), function (field) {
            if (field.classList.contains('is-hidden')) {
                return null;
            }
            return field.getAttribute('data-optional-prop');
        }).filter(Boolean);
    }

    function clearField(fieldEl) {
        fieldEl.querySelectorAll('input[type="text"], textarea').forEach(function (input) {
            input.value = '';
        });
        fieldEl.querySelectorAll('.be-schema-image-preview').forEach(function (preview) {
            preview.innerHTML = '';
        });
    }

    function fieldHasValue(fieldEl) {
        var hasInput = Array.prototype.some.call(fieldEl.querySelectorAll('input[type="text"], textarea'), function (input) {
            return input.value && input.value.trim().length > 0;
        });
        if (hasInput) {
            return true;
        }
        return Array.prototype.some.call(fieldEl.querySelectorAll('.be-schema-image-preview img'), function (img) {
            return !!img.getAttribute('src');
        });
    }

    function initOptionalGroup(controlsEl) {
        if (!controlsEl) {
            return;
        }

        var scope = controlsEl.getAttribute('data-optional-scope') || '';
        if (!scope) {
            return;
        }

        var fieldsEl = document.getElementById('be-schema-' + scope + '-optional-fields');
        if (!fieldsEl) {
            return;
        }

        var selectEl = controlsEl.querySelector('select');
        var addBtn = controlsEl.querySelector('.be-schema-optional-add');
        var hiddenId = controlsEl.getAttribute('data-optional-hidden') || '';
        var hiddenEl = hiddenId ? document.getElementById(hiddenId) : null;
        var singletonProps = toList(controlsEl.getAttribute('data-optional-singleton'));
        var allProps = getAllProps(fieldsEl);

        function syncHidden() {
            if (!hiddenEl) {
                return;
            }
            hiddenEl.value = getActiveProps(fieldsEl).join(',');
        }

        function syncOptionDisable() {
            if (!selectEl) {
                return;
            }
            var active = getActiveProps(fieldsEl);
            var singletonSet = singletonProps.reduce(function (acc, prop) {
                acc[prop] = true;
                return acc;
            }, {});

            Array.prototype.forEach.call(selectEl.options, function (opt) {
                if (!opt.value) {
                    opt.disabled = false;
                    return;
                }
                if (singletonSet[opt.value] && active.indexOf(opt.value) !== -1) {
                    opt.disabled = true;
                } else {
                    opt.disabled = false;
                }
            });
        }

        function showProp(prop) {
            var field = fieldsEl.querySelector('[data-optional-prop="' + prop + '"]');
            if (!field) {
                return;
            }
            field.classList.remove('is-hidden');
            syncHidden();
            syncOptionDisable();
        }

        function hideProp(prop) {
            var field = fieldsEl.querySelector('[data-optional-prop="' + prop + '"]');
            if (!field) {
                return;
            }
            clearField(field);
            field.classList.add('is-hidden');
            syncHidden();
            syncOptionDisable();
        }

        if (addBtn && selectEl) {
            addBtn.addEventListener('click', function (event) {
                event.preventDefault();
                var val = selectEl.value;
                if (!val) {
                    return;
                }
                if (getActiveProps(fieldsEl).indexOf(val) !== -1) {
                    return;
                }
                showProp(val);
                selectEl.value = '';
                syncOptionDisable();
            });
        }

        fieldsEl.querySelectorAll('.be-schema-optional-remove').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                var prop = btn.getAttribute('data-optional-remove');
                if (prop) {
                    hideProp(prop);
                }
            });
        });

        // Initial state from hidden CSV or existing values.
        var initial = hiddenEl && hiddenEl.value ? toList(hiddenEl.value) : [];
        if (!initial.length) {
            allProps.forEach(function (prop) {
                var field = fieldsEl.querySelector('[data-optional-prop="' + prop + '"]');
                if (field && fieldHasValue(field)) {
                    initial.push(prop);
                }
            });
        }
        initial.forEach(showProp);

        syncHidden();
        syncOptionDisable();
    }

    window.beSchemaInitOptionalGroup = initOptionalGroup;
    window.beSchemaInitAllOptionalGroups = function () {
        document.querySelectorAll('.be-schema-optional-controls').forEach(initOptionalGroup);
    };
})();
