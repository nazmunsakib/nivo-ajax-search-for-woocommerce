/**
 * NivoSearch accessibility layer (WAI-ARIA combobox pattern).
 *
 * Works on top of nivo-search.js without touching how results are rendered:
 *  - the input becomes a combobox that controls the results listbox;
 *  - every result row becomes an option with an id;
 *  - ArrowUp/ArrowDown/Home/End move the active option (aria-activedescendant),
 *    Enter opens it, Escape closes the list;
 *  - a separate polite status region announces how many results are available.
 */
(function (window, document) {
    'use strict';

    var INPUT = '.nivo-search-product-search';
    var CONTAINER = '.nivo-ajax-search-container';
    var RESULTS = '.nivo-search-results';
    var OPTION = '.nivo-search-result-item, .nivo-search-category-item, .nivo-search-tag-item';
    var ACTIVE = 'nivo-search-option-active';

    var uid = 0;

    function strings() {
        return (window.nivo_search && window.nivo_search.strings) || {};
    }

    function parts(input) {
        var container = input.closest(CONTAINER);
        var results = container ? container.querySelector(RESULTS) : null;
        return { container: container, results: results };
    }

    function ensureIds(container, results) {
        if (!container.nivoA11yId) {
            container.nivoA11yId = 'nivo-search-' + (++uid);
        }
        if (!results.id) {
            results.id = container.nivoA11yId + '-listbox';
        }
        return container.nivoA11yId;
    }

    function statusRegion(container) {
        var region = container.querySelector('.nivo-search-status');
        if (!region) {
            region = document.createElement('div');
            region.className = 'nivo-search-status screen-reader-text';
            region.setAttribute('role', 'status');
            region.setAttribute('aria-live', 'polite');
            container.appendChild(region);
        }
        return region;
    }

    function options(results) {
        return Array.prototype.slice.call(results.querySelectorAll(OPTION));
    }

    function isOpen(results) {
        return options(results).length > 0 && results.offsetParent !== null &&
            !results.classList.contains('nivo-search-no-results');
    }

    function setActive(input, results, option) {
        options(results).forEach(function (el) {
            el.classList.remove(ACTIVE);
            el.setAttribute('aria-selected', 'false');
        });

        if (!option) {
            input.removeAttribute('aria-activedescendant');
            return;
        }

        option.classList.add(ACTIVE);
        option.setAttribute('aria-selected', 'true');
        input.setAttribute('aria-activedescendant', option.id);

        if (option.scrollIntoView) {
            option.scrollIntoView({ block: 'nearest' });
        }
    }

    /**
     * Apply ARIA state after results change.
     */
    function sync(container) {
        var input = container.querySelector(INPUT);
        var results = container.querySelector(RESULTS);
        if (!input || !results) {
            return;
        }

        var base = ensureIds(container, results);
        var opts = options(results);

        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-haspopup', 'listbox');
        input.setAttribute('aria-controls', results.id);

        // The results region already carries aria-live in older markup; a listbox
        // must not announce its whole content, the status region does that.
        results.removeAttribute('aria-live');
        results.removeAttribute('aria-atomic');
        results.setAttribute('role', 'listbox');

        opts.forEach(function (el, i) {
            if (!el.id) {
                el.id = base + '-option-' + i;
            }
            el.setAttribute('role', 'option');
            if (!el.hasAttribute('aria-selected')) {
                el.setAttribute('aria-selected', 'false');
            }
        });

        var open = isOpen(results);
        input.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (!open) {
            input.removeAttribute('aria-activedescendant');
        }

        var message = '';
        var s = strings();
        if (open) {
            message = opts.length === 1
                ? (s.a11y_one_result || '1 result available. Use up and down arrow keys to review.')
                : (s.a11y_many_results || '%d results available. Use up and down arrow keys to review.').replace('%d', opts.length);
        } else if (results.classList.contains('nivo-search-no-results')) {
            message = s.a11y_no_results || 'No results found.';
        }

        var region = statusRegion(container);
        if (region.textContent !== message) {
            region.textContent = message;
        }
    }

    function primaryLink(option) {
        return option.querySelector('.nivo-search-product-title-link') || option.querySelector('a[href]');
    }

    function onKeydown(event) {
        var input = event.target;
        if (!input.matches || !input.matches(INPUT)) {
            return;
        }

        var p = parts(input);
        if (!p.container || !p.results) {
            return;
        }

        var key = event.key;
        var opts = options(p.results);
        var current = opts.findIndex(function (el) { return el.classList.contains(ACTIVE); });

        if (key === 'Escape') {
            if (isOpen(p.results)) {
                setActive(input, p.results, null);
                input.setAttribute('aria-expanded', 'false');
                p.results.style.display = 'none';
                event.preventDefault();
            }
            return;
        }

        if (!opts.length) {
            return;
        }

        if (key === 'ArrowDown' || key === 'ArrowUp' || key === 'Home' || key === 'End') {
            // Re-open a list hidden with Escape.
            if (p.results.style.display === 'none') {
                p.results.style.display = '';
            }

            var next;
            if (key === 'ArrowDown') {
                next = current < 0 ? 0 : (current + 1) % opts.length;
            } else if (key === 'ArrowUp') {
                next = current <= 0 ? opts.length - 1 : current - 1;
            } else if (key === 'Home') {
                next = 0;
            } else {
                next = opts.length - 1;
            }

            // Home/End keep caret behaviour in the text field unless an option is active.
            if ((key === 'Home' || key === 'End') && current < 0) {
                return;
            }

            event.preventDefault();
            setActive(input, p.results, opts[next]);
            return;
        }

        if (key === 'Enter' && current >= 0) {
            var link = primaryLink(opts[current]);
            if (link) {
                event.preventDefault();
                link.click();
            }
        }
    }

    function observe(container) {
        if (container.nivoA11yObserved) {
            return;
        }
        container.nivoA11yObserved = true;

        var results = container.querySelector(RESULTS);
        if (!results) {
            return;
        }

        var queued = false;
        var schedule = function () {
            if (queued) {
                return;
            }
            queued = true;
            window.requestAnimationFrame(function () {
                queued = false;
                sync(container);
            });
        };

        new MutationObserver(schedule).observe(results, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'style']
        });

        sync(container);
    }

    function init() {
        document.querySelectorAll(CONTAINER).forEach(observe);
        document.addEventListener('keydown', onKeydown, true);

        // Containers added later (page builders, mobile overlay).
        document.addEventListener('focusin', function (event) {
            var container = event.target.closest && event.target.closest(CONTAINER);
            if (container) {
                observe(container);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
