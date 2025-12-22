/**
 * Nivo AJAX Search for WooCommerce
 * 
 * Professional vanilla JavaScript implementation
 * 
 * @package NivoSearch
 * @since 1.0.0
 */

(function (window, document) {
    'use strict';

    // Configuration
    const config = {
        selectors: {
            input: '.nivo-search-product-search',
            results: '.nivo-search-results',
            container: '.nivo-ajax-search-container'
        },
        classes: {
            loading: 'nivo-search-loading',
            hasResults: 'nivo-search-has-results',
            noResults: 'nivo-search-no-results',
            focused: 'nivo-search-focused'
        },
        settings: {
            minLength: 2,
            delay: 200,
            maxResults: 10
        },
        strings: (window.nivo_search && window.nivo_search.strings) || {}
    };

    const DEFAULT_MIN_LENGTH = 2;
    const DEFAULT_DELAY = 200;

    // State
    let searchTimeout = null;
    let currentRequest = null;

    /**
     * Find closest parent element with selector
     */
    function closest(element, selector) {
        while (element && element !== document) {
            if (element.matches && element.matches(selector)) {
                return element;
            }
            element = element.parentElement;
        }
        return null;
    }

    /**
     * Add class to element
     */
    function addClass(element, className) {
        if (element && element.classList) {
            element.classList.add(className);
        }
    }

    /**
     * Remove class from element
     */
    function removeClass(element, className) {
        if (element && element.classList) {
            element.classList.remove(className);
        }
    }

    /**
     * Trigger custom event
     */
    function triggerEvent(eventName, data = {}) {
        const event = new CustomEvent(`nivo_search:${eventName}`, {
            detail: data,
            bubbles: true
        });
        document.dispatchEvent(event);
    }

    /**
     * Get settings for a specific container
     */
    function getContainerSettings(container) {
        let minLength = DEFAULT_MIN_LENGTH;
        let delay = DEFAULT_DELAY;

        // Check for preset settings
        const presetData = container.getAttribute('data-preset-settings');
        if (presetData) {
            try {
                const presetSettings = JSON.parse(presetData);
                if (presetSettings.min_chars !== undefined && presetSettings.min_chars !== '') {
                    minLength = parseInt(presetSettings.min_chars, 10);
                }
                // Delay might not be in preset settings usually, but if added later:
                if (presetSettings.delay !== undefined && presetSettings.delay !== '') {
                    delay = parseInt(presetSettings.delay, 10);
                }
            } catch (e) {
                // Silently fail and use defaults
            }
        }
        return { minLength, delay };
    }

    /**
     * Handle input events
     */
    function handleInput(event) {
        const input = event.target;
        const query = input.value.trim();
        const container = closest(input, config.selectors.container);
        const results = container ? container.querySelector(config.selectors.results) : null;

        if (!container || !results) return;

        const containerSettings = getContainerSettings(container);

        clearTimeout(searchTimeout);

        if (query.length < containerSettings.minLength) {
            if (currentRequest) {
                currentRequest.abort();
                currentRequest = null;
            }
            clearResults(results, container);
            return;
        }

        searchTimeout = setTimeout(() => {
            if (currentRequest) {
                currentRequest.abort();
            }
            performSearch(query, results, container);
        }, containerSettings.delay);
    }

    /**
     * Handle focus events
     */
    function handleFocus(event) {
        const input = event.target;
        const container = closest(input, config.selectors.container);

        if (container) {
            addClass(container, config.classes.focused);
            triggerEvent('focus', { input, container });

            const query = input.value.trim();
            const results = container.querySelector(config.selectors.results);
            const containerSettings = getContainerSettings(container);

            if (query.length >= containerSettings.minLength && results) {
                if (results.innerHTML.trim() !== '') {
                    // Soft open: Restore view if we have cached results
                    if (results.querySelector('.nivo-search-no-results-message')) {
                        addClass(container, config.classes.noResults);
                    } else {
                        addClass(container, config.classes.hasResults);
                    }
                    // Restore close icon
                    const loaderIcons = container.querySelector('.nivo-search-loader-icons');
                    if (loaderIcons) addClass(loaderIcons, 'nivo-search-close');
                } else {
                    // No cached results, perform new search
                    performSearch(query, results, container);
                }
            }
        }
    }

    /**
     * Handle blur events
     */
    function handleBlur(event) {
        const input = event.target;
        const container = closest(input, config.selectors.container);

        setTimeout(() => {
            if (container) {
                // removeClass(container, config.classes.focused); // Keep focus class if we want, or remove. 
                // Usually blur removes focus style, but let's keep specific logic simple.
                removeClass(container, config.classes.focused);
                triggerEvent('blur', { input, container });
            }
        }, 200);
    }

    /**
     * Perform AJAX search
     */
    function performSearch(query, results, container) {
        addClass(container, config.classes.loading);

        const formData = new FormData();
        formData.append('s', query);

        // Get preset ID from container
        const presetId = container.getAttribute('data-preset-id');
        if (presetId) {
            formData.append('preset_id', presetId);
        }

        // Use WooCommerce AJAX if available
        const useWcAjax = window.nivo_search.wc_ajax_url;
        const ajaxUrl = useWcAjax ? window.nivo_search.wc_ajax_url : window.nivo_search.ajax_url;

        if (!useWcAjax) {
            formData.append('action', 'nivo_search');
            formData.append('nonce', window.nivo_search.nonce);
        }

        triggerEvent('beforeSearch', { query, results, container });

        currentRequest = new XMLHttpRequest();
        currentRequest.open('POST', ajaxUrl);

        currentRequest.onload = function () {
            removeClass(container, config.classes.loading);
            currentRequest = null;

            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        displayResults(response.data, results, container, query);
                    } else {
                        displayError(
                            (response.data && response.data.message) || config.strings.error,
                            results,
                            container
                        );
                    }
                } catch (error) {
                    displayError(config.strings.error, results, container);
                }
            } else {
                displayError(config.strings.error, results, container);
            }
        };

        currentRequest.onerror = function () {
            removeClass(container, config.classes.loading);
            currentRequest = null;
            displayError(config.strings.error, results, container);
        };

        currentRequest.onabort = function () {
            removeClass(container, config.classes.loading);
            currentRequest = null;
        };

        currentRequest.send(formData);
    }

    /**
     * Display search results
     */
    function displayResults(data, results, container, query) {
        // Handle both old format (array) and new format (object with categories/products)
        const categories = data.categories || [];
        const products = data.products || (Array.isArray(data) ? data : []);

        const clearBtn = container.querySelector('.nivo-search-loader-icons');
        if (clearBtn) {
            addClass(clearBtn, 'nivo-search-close'); // Show close icon when results display
        }

        if (categories.length === 0 && products.length === 0) {
            displayNoResults(results, container);
            return;
        }

        const globalSettings = window.nivo_search && window.nivo_search.settings ? window.nivo_search.settings : {};
        // Prioritize settings from response (preset), fallback to global
        const settings = data.settings ? Object.assign({}, globalSettings, data.settings) : globalSettings;

        // Ensure proper type casting for boolean flags if they come as strings
        if (data.settings) {
            ['show_images', 'show_price', 'show_sku', 'show_description'].forEach(key => {
                if (settings[key] !== undefined) {
                    settings[key] = parseInt(settings[key], 10);
                }
            });
        }

        let html = '';

        // Add categories section first
        if (categories.length > 0) {
            html += '<div class="nivo-search-categories-section">';
            html += '<h4 class="nivo-search-section-title">Categories</h4>';
            html += '<ul class="nivo-search-categories-list">';
            categories.forEach(function (category) {
                html += renderCategoryItem(category, query, settings);
            });
            html += '</ul>';
            html += '</div>';
        }

        // Add tags section second
        const tags = data.tags || [];
        if (tags.length > 0) {
            html += '<div class="nivo-search-tags-section">';
            html += '<h4 class="nivo-search-section-title">Tags</h4>';
            html += '<ul class="nivo-search-tags-list">';
            tags.forEach(function (tag) {
                html += renderTagItem(tag, query, settings);
            });
            html += '</ul>';
            html += '</div>';
        }

        // Add products section third
        if (products.length > 0) {
            if (categories.length > 0 || tags.length > 0) {
                html += '<div class="nivo-search-products-section">';
                html += '<h4 class="nivo-search-section-title">Products</h4>';
            } else {
                html += '<div class="nivo-search-products-section">';
            }
            html += '<ul class="nivo-search-results-list">';
            products.forEach(function (product) {
                html += renderProductItem(product, query, settings);
            });
            html += '</ul>';
            html += '</div>';
        }

        results.innerHTML = html;
        addClass(container, config.classes.hasResults);

        triggerEvent('resultsDisplayed', { categories, products, results, container, query });
    }

    /**
     * Escape HTML special characters
     */
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    /**
     * Highlight matching keywords
     */
    function highlightKeywords(text, query) {
        if (!text) return '';
        const escapedText = escapeHtml(text);
        if (!query) return escapedText;

        // Escape query to match against escaped text and avoid regex injection
        const escapedQuery = escapeHtml(query).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${escapedQuery})`, 'gi');
        return escapedText.replace(regex, '<span class="nivo-search-highlight">$1</span>');
    }

    /**
     * Render individual tag item
     */
    function renderTagItem(tag, query, settings) {
        const padding = settings.results_padding || 10;
        const highlightedTitle = highlightKeywords(tag.title, query);

        return `<li class="nivo-search-tag-item" style="padding: ${padding}px;">
                <a href="${escapeHtml(tag.url)}" class="nivo-search-tag-link">
                    <span class="nivo-search-tag-title">${highlightedTitle}</span>
                    <span class="nivo-search-tag-count">(${escapeHtml(tag.count)})</span>
                </a>
            </li>`;
    }

    /**
     * Render individual category item
     */
    function renderCategoryItem(category, query, settings) {
        const padding = settings.results_padding || 10;
        const highlightedTitle = highlightKeywords(category.title, query);

        return `<li class="nivo-search-category-item" style="padding: ${padding}px;">
                <a href="${escapeHtml(category.url)}" class="nivo-search-category-link">
                    <span class="nivo-search-category-title">${highlightedTitle}</span>
                    <span class="nivo-search-category-count">(${escapeHtml(category.count)})</span>
                </a>
            </li>`;
    }

    /**
     * Render individual product item
     */
    function renderProductItem(product, query, settings) {
        const showImages = settings.show_images === 1;
        const showPrice = settings.show_price === 1;
        const showSku = settings.show_sku === 1;
        const showDescription = settings.show_description === 1;
        const padding = settings.results_padding || 10;

        const imageHtml = (showImages && product.image)
            ? `<img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.title)}" class="nivo-search-product-image">`
            : '';

        const highlightedTitle = highlightKeywords(product.title, query);
        const skuHtml = (showSku && product.sku) ? ` <strong>(SKU: ${highlightKeywords(product.sku, query)})</strong>` : '';
        // Price is trusted HTML from WooCommerce
        const priceHtml = (showPrice && product.price) ? `<span class="nivo-search-product-price">${product.price}</span>` : '';

        const titleSkuHtml = `<div class="nivo-search-product-title-row">
            <span class="nivo-search-product-title">${highlightedTitle}${skuHtml}</span>
            ${priceHtml}
        </div>`;

        const descHtml = (showDescription && product.short_description)
            ? `<span class="nivo-search-product-description">${highlightKeywords(product.short_description, query)}</span>`
            : '';

        return `<li class="nivo-search-result-item" style="padding: ${padding}px;">
                <a href="${escapeHtml(product.url)}" class="nivo-search-product-link">
                    ${imageHtml}
                    <div class="nivo-search-product-info">
                        ${titleSkuHtml}
                        ${descHtml}
                    </div>
                </a>
            </li>`;
    }

    /**
     * Display no results message
     */
    function displayNoResults(results, container) {
        results.innerHTML = `<p class="nivo-search-no-results-message">${config.strings.no_results}</p>`;
        addClass(container, config.classes.noResults);
        triggerEvent('noResults', { results, container });
    }

    /**
     * Display error message
     */
    function displayError(message, results, container) {
        results.innerHTML = `<p class="nivo-search-error-message">${message}</p>`;

        triggerEvent('error', { message, results, container });
    }

    /**
     * Clear results (Hard Clear)
     */
    function clearResults(results, container) {
        results.innerHTML = '';
        removeClass(container, config.classes.hasResults);
        removeClass(container, config.classes.noResults);

        // Hide close button when clearing results
        const loaderIcons = container.querySelector('.nivo-search-loader-icons');
        if (loaderIcons) {
            removeClass(loaderIcons, 'nivo-search-close');
        }

        triggerEvent('resultsCleared', { results, container });
    }

    /**
     * Handle clear button click
     */
    function handleClear(event) {
        // The event target might be the icon itself or the container, normalize to the button wrapper if needed
        const clearBtn = event.target;
        const container = closest(clearBtn, config.selectors.container);
        if (!container) return;

        const input = container.querySelector(config.selectors.input);
        const results = container.querySelector(config.selectors.results);

        if (input) {
            input.value = '';
            input.focus();
        }
        if (results) {
            clearResults(results, container);
        }
    }

    /**
     * Toggle clear button visibility
     * @deprecated Icon visibility is now handled by results display state
     */
    function toggleClearButton(input) {
        // Logic moved to displayResults and clearResults
    }

    /**
     * Handle click outside to close results (Soft Close)
     */
    function handleClickOutside(event) {
        // If click is inside any search container, ignore
        if (closest(event.target, config.selectors.container)) {
            return;
        }

        // Close all open search results (Soft Close - maintain HTML)
        const containers = document.querySelectorAll(config.selectors.container);
        containers.forEach(container => {
            const results = container.querySelector(config.selectors.results);
            if (results && (container.classList.contains(config.classes.hasResults) || container.classList.contains(config.classes.noResults))) {

                // Just remove visibility classes, DO NOT clear innerHTML
                removeClass(container, config.classes.hasResults);
                removeClass(container, config.classes.noResults);
                removeClass(container, config.classes.focused);

                // Hide close icon
                const loaderIcons = container.querySelector('.nivo-search-loader-icons');
                if (loaderIcons) {
                    removeClass(loaderIcons, 'nivo-search-close');
                }
            }
        });
    }

    /**
     * Initialize search functionality
     */
    function init() {
        // Event delegation for input events
        document.addEventListener('input', function (event) {
            if (event.target.matches && event.target.matches(config.selectors.input)) {
                handleInput(event);
                // toggleClearButton removed from here
            }
        });

        // Event delegation for focus events
        document.addEventListener('focus', function (event) {
            if (event.target.matches && event.target.matches(config.selectors.input)) {
                handleFocus(event);
            }
        }, true);

        // Event delegation for blur events
        document.addEventListener('blur', function (event) {
            if (event.target.matches && event.target.matches(config.selectors.input)) {
                handleBlur(event);
            }
        }, true);

        // Event delegation for clear button - target the specific class
        document.addEventListener('click', function (event) {
            // Check if clicked element or its parent is the close icon
            if (event.target.matches('.nivo-search-close-icon') || event.target.closest('.nivo-search-close-icon')) {
                handleClear(event);
            }
        });

        // Click outside handler
        document.addEventListener('click', handleClickOutside);

        triggerEvent('init');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose public API
    window.nivoSearchAPI = {
        config: config,
        triggerEvent: triggerEvent
    };

})(window, document);