/**
 * Auto-prefix Twitter handle inputs with '@' for site/creator fields.
 */
(function () {
    function ensurePrefix(input) {
        if (!input) {
            return;
        }
        var val = input.value || '';
        // If empty, seed with '@'. If present, normalize to single leading @.
        if (val === '') {
            input.value = '@';
            return;
        }
        input.value = '@' + val.replace(/^@+/, '');
    }

    function init() {
        var site = document.getElementById('be_schema_twitter_site');
        var creator = document.getElementById('be_schema_twitter_creator');

        [site, creator].forEach(function (input) {
            if (!input) {
                return;
            }
            input.addEventListener('blur', function () {
                ensurePrefix(input);
            });
            input.addEventListener('change', function () {
                ensurePrefix(input);
            });
            // Initial pass if prefilled.
            ensurePrefix(input);
        });
    }

    if (document.readyState !== 'loading') {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})();
