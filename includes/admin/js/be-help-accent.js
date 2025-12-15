/**
 * Highlight help text enclosed in {curly braces} with a WordPress cyan accent.
 *
 * Scans help/description elements within BE SEO admin pages and wraps brace
 * segments in a span with the .be-schema-help-accent class.
 */
(function () {
    var selectors = [
        '.beseo-wrap .description',
        '.be-schema-wrap .description',
        '.be-schema-social-wrap .description',
        '.beseo-wrap .be-schema-description',
        '.be-schema-wrap .be-schema-description',
        '.be-schema-social-wrap .be-schema-description'
    ];

    function accentNode(node) {
        if (node.nodeType !== Node.TEXT_NODE) {
            return;
        }
        var text = node.textContent;
        if (text.indexOf('{') === -1 || text.indexOf('}') === -1) {
            return;
        }

        var parts = text.split(/(\{[^}]+\})/);
        if (parts.length <= 1) {
            return;
        }

        var frag = document.createDocumentFragment();
        parts.forEach(function (part) {
            if (!part) {
                return;
            }
            if (part.startsWith('{') && part.endsWith('}')) {
                var span = document.createElement('span');
                span.className = 'be-schema-help-accent';
                span.textContent = part.slice(1, -1);
                frag.appendChild(span);
            } else {
                frag.appendChild(document.createTextNode(part));
            }
        });

        if (frag.childNodes.length) {
            node.parentNode.replaceChild(frag, node);
        }
    }

    function accentElement(el) {
        var walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, null);
        var textNode;
        var toProcess = [];
        while ((textNode = walker.nextNode())) {
            toProcess.push(textNode);
        }
        toProcess.forEach(accentNode);
    }

    function init() {
        selectors.forEach(function (sel) {
            document.querySelectorAll(sel).forEach(accentElement);
        });
    }

    if ('loading' !== document.readyState) {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})();
