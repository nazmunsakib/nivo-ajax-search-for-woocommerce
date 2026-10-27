=== NivoSearch – Ajax Search for WooCommerce ===

Contributors: nazmunsakib
Donate link: https://nivosearch.com
Tags: woocommerce, woocommerce search, product search, instant search, ajax search
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce Ajax Search with live product suggestions, typo correction, SKU matching, and optional add-to-cart controls.

== Description ==

NivoSearch is a WooCommerce Ajax Search plugin that helps customers find products as they type, without reloading the page. This WooCommerce Search Plugin provides live product suggestions, typo correction, fuzzy matching, SKU and variation SKU search, and optional add-to-cart controls.

Store owners can configure search presets to control which product fields are searched, which details appear in results, and how each search bar looks and behaves. NivoSearch supports product titles, descriptions, SKUs, categories, and tags, depending on the selected settings.

Built for WooCommerce stores of different sizes, NivoSearch combines configurable product search, mobile-friendly layouts, multilingual support, and product discovery tools in one plugin. Its search features help shoppers find relevant products even when they misspell a search term or know a product by its SKU.

[Live demo](https://nivosearch.com/live-demo/) | [Features](https://nivosearch.com/features/) | [Documentation](https://nivosearch.com/documentation/) | [WordPress.org support](https://wordpress.org/support/plugin/nivo-ajax-search-for-woocommerce/)

= Why choose NivoSearch? =

NivoSearch improves WooCommerce product discovery by showing relevant product suggestions while customers type. Instead of requiring shoppers to submit a search and wait for a separate results page, it lets them inspect matching products directly in a live search panel.

Key benefits include configurable product search, SKU and variation SKU matching, built-in typo corrections, fuzzy search fallback, and optional add-to-cart controls. Store owners can customize search behavior and presentation for different areas of their store using separate search presets.

The free WordPress.org version supports up to 10 custom typo correction rules. Project documentation refers to a separate Pro tier, which is not included in this plugin download.

= Features =

* **Live WooCommerce Ajax Search:** Display matching products as customers type, without a full page reload. Results are ordered by relevance, with product title and SKU matches contributing to ranking.
* **Configurable product search:** Choose whether to search product titles, full and short descriptions, and SKUs. Category and tag matches can appear in separate result sections when enabled.
* **SKU and variation SKU search:** Help shoppers find products using product codes. A variation SKU match returns the parent product in the search results.
* **Typo correction and fuzzy search:** Use a built-in dictionary containing more than 300 common corrections. When a search returns no products, the indexed fuzzy search fallback can suggest close matches. An optional clickable "Did you mean?" suggestion is also available.
* **Custom typo correction rules:** Add up to 10 custom correction rules in the free version to address store-specific product names and common spelling mistakes.
* **Customizable product details:** Choose whether search results display product images, prices, SKUs, short descriptions, stock status, category labels, and highlighted search terms.
* **Product availability filters:** Exclude out-of-stock products or selected product IDs from search results.
* **Add to cart from search results:** Optionally display an add-to-cart control and quantity selector for eligible products. Variable products link to their product pages so shoppers can choose the required options.
* **Unlimited search presets:** Create separate search configurations with different search scopes, display settings, colors, dimensions, result counts, and search delays.
* **Shortcode and block support:** Add a search bar using the `[nivo_search id="123"]` shortcode or the NivoSearch block in the WordPress block editor.
* **Optional theme search replacement:** Replace search forms rendered through supported WordPress and WooCommerce hooks or the core Search block. Custom theme markup may require the shortcode or block instead.
* **Mobile search overlay:** Use a full-screen search interface on narrow screens for a mobile-friendly product discovery experience.
* **Recent search history:** Store up to eight recent queries in the visitor's browser, display them when the search input is focused and empty, and allow individual entries or the entire history to be removed.
* **Multilingual and accented search:** Scope product and taxonomy results to the current WPML or Polylang language and match accented or unaccented words.
* **WooCommerce search results page:** When enabled, route submitted WordPress searches to the WooCommerce shop page. If that page is unavailable, show product-only search results.
* **View all results:** Optionally display a link from the live search panel to the full product results page.
* **GA4 and Google Tag Manager events:** Send search events to an existing GA4 or Google Tag Manager integration. NivoSearch does not load the analytics scripts. See the Privacy and data section for details.
* **Search index and caching:** Maintain a local product word index for fuzzy matching and cache search results for five minutes. Rebuild the index from the plugin settings when necessary.

= Who is NivoSearch for? =

NivoSearch is designed for WooCommerce store owners who want to improve product discovery with live search without developing a custom search interface.

This WooCommerce Ajax Search solution can be useful for stores where customers:

* Need to find products quickly without leaving the current page.
* Search for products using SKUs or variation SKUs.
* Frequently misspell product names or search with accented and unaccented words.
* Want to inspect product images, prices, stock status, or other details before opening a product page.
* Prefer to add eligible products to their cart directly from search results.
* Shop on mobile devices and need a responsive search interface.
* Browse multilingual catalogs using WPML or Polylang.
* Need different search configurations for different pages or sections of a store.

= Search index and performance =

NivoSearch uses standard WordPress queries for regular product searches. A local product word index supports the fuzzy search fallback and can be rebuilt from the plugin settings.

Product saves update the index. After importing products, rebuild the index if indexed terms are missing. Search results are cached for five minutes, and the cache is invalidated when products or presets change.

Actual search response times depend on the store's hosting environment, database performance, product catalog, and configuration. No fixed response-time or catalog-size guarantee is made.

= WooCommerce Ajax Search compared with standard search =

The standard WooCommerce search form generally submits a query and loads a separate results page. A WooCommerce Ajax Search interface can display matching product suggestions while customers remain on the current page.

NivoSearch adds configurable live product suggestions, SKU and variation SKU matching, typo correction, fuzzy search fallback, product details, and optional add-to-cart controls. Store owners can choose which product fields are searched and which details appear in the results.

These features give shoppers another way to discover products without replacing the underlying WooCommerce product catalog or requiring a separate search service.

= Developer hooks and events =

The following PHP filters are available in the current plugin:

* `nivo_search_args`: Change search arguments before the product query.
* `nivo_search_results`: Change the complete results array before the response is sent.
* `nivo_search_result_item`: Change an individual product result.
* `nivo_search_shortcode_html`: Change the rendered shortcode HTML.
* `nivo_search_localize_data`: Change data passed to the frontend script.
* `nivo_search_should_enqueue_assets`: Prevent frontend assets from being enqueued.
* `nivo_search_typo_corrections`: Change the built-in and custom correction map.
* `nivo_search_corrected_term`: Override a correction for a query.
* `nivo_search_max_custom_rules`: Change the default limit of 10 custom correction rules.
* `nivo_search_language_query_args`: Change language arguments for multilingual queries.
* `nivo_search_results_redirect_url`: Change or cancel the search results redirect.
* `nivo_search_results_post_types`: Change post types in the search results page fallback.
* `nivo_search_index_tokens`: Change tokens stored for an indexed product.
* `nivo_search_script_dependencies` and `nivo_search_style_dependencies`: Change frontend asset dependencies.
* `nivo_search_script_version` and `nivo_search_style_version`: Change frontend asset versions.

Available PHP actions:

* `nivo_search_plugin_loaded`: Fires when the main plugin controller is initialized.
* `nivo_search_components_loaded`: Fires after plugin components are initialized.
* `nivo_search_after_enqueue_scripts` and `nivo_search_after_enqueue_styles`: Fire after frontend assets are enqueued.
* `nivo_search_migrations_complete`: Fires after database migrations run.
* `nivo_search_results_query`: Fires after the search results page fallback query is configured.

Frontend events are dispatched on `document` and use the `nivo_search:` prefix:

* `nivo_search:init`
* `nivo_search:beforeSearch`
* `nivo_search:resultsDisplayed`
* `nivo_search:noResults`
* `nivo_search:error`
* `nivo_search:resultsCleared`
* `nivo_search:focus`
* `nivo_search:blur`
* `nivo_search:didYouMeanClicked`
* `nivo_search:addedToCart`
* `nivo_search:mobileOverlayOpened`
* `nivo_search:mobileOverlayClosed`

= Privacy and data =

Search queries are sent to the store's own WordPress site to retrieve results. Recent searches are stored in the visitor's browser using localStorage, with up to eight entries. When the typo correction system changes a query, NivoSearch records the original and corrected terms in a local correction analytics table.

The Google Analytics tracking setting is enabled by default. If an existing Google Analytics 4 `gtag` function or Google Tag Manager `dataLayer` is present, NivoSearch sends a search event containing the query and result count. NivoSearch does not load those analytics scripts or check consent itself.

Store owners should disable the tracking setting or coordinate it with their site's consent tool as required by their privacy and consent policy.

When the plugin is deactivated or deleted, presets and settings are preserved by default. Transient caches are removed on deletion. Enable "Delete all data on uninstall" in NivoSearch settings before deleting the plugin if you want it to remove its presets, settings, and custom tables as well.

= Developer =

NivoSearch is developed and maintained by [Nazmun Sakib](https://nazmunsakib.com).

Visit the [NivoSearch website](https://nivosearch.com/) for product information, the [GitHub repository](https://github.com/nazmunsakib/nivo-ajax-search-for-woocommerce) for source code, and the [documentation](https://nivosearch.com/documentation/) for setup guides.

== Installation ==

1. In your WordPress dashboard, go to **Plugins > Add New**.
2. Search for **NivoSearch** and select **Install Now**.
3. Select **Activate** after installation completes.
4. Go to **NivoSearch > Search Presets** to configure a preset.
5. Add the search bar using its shortcode or the NivoSearch block. To replace supported theme search forms instead, go to **NivoSearch > Settings > Search Integration** and enable **Replace theme search form**.

== Frequently Asked Questions ==

= What is WooCommerce Ajax Search? =

WooCommerce Ajax Search retrieves product matches in a background request while customers type. Unlike a standard search form that submits a query and loads a new page, Ajax search can display matching product suggestions without reloading the current page.

NivoSearch provides this live product search interface for WooCommerce stores, with configurable search fields, typo correction, SKU matching, and optional cart controls.

= Is NivoSearch a WooCommerce Search Plugin? =

Yes. NivoSearch is a WooCommerce Search Plugin that adds live product suggestions, configurable product search, typo correction, SKU and variation SKU matching, and optional add-to-cart controls to a WooCommerce store.

= How is NivoSearch different from standard WooCommerce search? =

Standard WooCommerce search typically submits a query and loads a results page. NivoSearch can display product suggestions in a live panel while customers stay on the current page. It also provides configurable search fields, SKU matching, typo correction, product details, and optional add-to-cart controls.

= Can shoppers search by product SKU or variation SKU? =

Yes. Enable SKU search in a preset to match parent product SKUs and variation SKUs. A variation SKU match returns the parent product in the search results.

= Can customers add products to the cart from search results? =

Yes, for eligible products when the add-to-cart control is enabled in a preset. The quantity selector is optional. Variable products link to their product page so customers can choose the required variation.

= How does NivoSearch handle misspelled product searches? =

When typo tolerance is enabled, NivoSearch checks the query against its built-in correction dictionary and custom correction rules. When fuzzy search is enabled, it can use the local product word index to find close terms after a search returns no products. A clickable "Did you mean?" suggestion can be displayed when that setting is enabled. The fuzzy search fallback requires the search index to be built.

= Can I customize which products and details appear in search results? =

Yes. Each search preset can be configured to search selected product fields and display details such as product images, prices, SKUs, short descriptions, stock status, category labels, and highlighted search terms. Available options depend on the plugin settings.

= Does NivoSearch work on mobile devices and block themes? =

The search interface is responsive and can use a full-screen overlay on narrow screens. NivoSearch can also replace the core WordPress Search block when the optional theme replacement setting is enabled. Theme replacement depends on how the theme renders its search form. If a custom form is not intercepted, use the NivoSearch block or shortcode.

= Does NivoSearch support WPML and Polylang? =

Yes. When WPML or Polylang is active, NivoSearch passes the current language to product and taxonomy searches. Compatibility depends on the installed versions of WordPress, WooCommerce, and the multilingual plugin.

= How do I add or configure a search bar? =

Create or edit a preset under **NivoSearch > Search Presets**. Choose the product fields to search and the details to display, then publish the preset. Place the generated shortcode on a page or post, or add a NivoSearch block. To replace supported theme search forms, enable the relevant option under **NivoSearch > Settings > Search Integration**.

= What analytics events does NivoSearch support? =

When Google Analytics tracking is enabled and an analytics integration is already present, NivoSearch sends a GA4 `search` event through `gtag` and pushes a `nivo_search` event with the query and result count to Google Tag Manager's `dataLayer`. It also dispatches a `nivoSearch` browser event for custom integrations. NivoSearch does not install or configure analytics scripts for you.

= What are the performance considerations? =

Search speed depends on hosting, database performance, product catalog size, and active configuration. Standard product searches use WordPress queries, while the local product word index supports fuzzy search fallback. Results are cached for five minutes. Rebuild the index from **NivoSearch > Settings > Search Accuracy** if fuzzy matching does not include recently imported products.

= What information does NivoSearch store or transmit? =

NivoSearch sends search queries to your own WordPress site to return matching products. It stores up to eight recent queries in the visitor's browser and logs original and corrected terms locally when a typo correction is applied. If Google Analytics tracking is enabled and the relevant analytics integration is present, the query and result count are also sent through that integration. The plugin does not load third-party analytics scripts.

= What happens to settings when NivoSearch is uninstalled? =

Deactivating the plugin does not delete its presets or settings. On deletion, transient caches are removed, while presets, settings, and custom tables are preserved by default. Enable **Delete all data on uninstall** in NivoSearch settings before deleting the plugin to request full data removal.

= Which WordPress, WooCommerce, and PHP versions are declared as supported? =

The readme metadata requires WordPress 5.0 or later, lists testing through WordPress 7.1, and requires PHP 7.4 or later. The plugin header declares WooCommerce 5.0 or later, testing through WooCommerce 8.5, and compatibility with High-Performance Order Storage (HPOS). These are the project's compatibility declarations, not a guarantee for every theme, extension, or newer release.

== Screenshots ==

1. Live product suggestions with product details and an add-to-cart control.
2. A "Did you mean?" correction suggestion in the results panel.
3. Search preset list with a shortcode for each preset.
4. Preset search, display, and style settings.
5. Search Accuracy controls and the product index rebuild option.
6. Search Integration options for theme replacement, results pages, and analytics.

== Changelog ==

= 2.0.7 =
* IMPROVED: Expanded WooCommerce Ajax Search feature descriptions, store use cases, comparison guidance, and FAQs.

= 2.0.6 =
* IMPROVED: Updated plugin documentation and metadata with verified feature, compatibility, privacy, and setup information.

= 2.0.5 =
* FIX: Search results cache is now scoped per language and per accuracy setting — multilingual sites and typo/fuzzy toggle changes no longer serve stale results.
* FIX: Typo-correction analytics are now counted on cached searches too, not only the first uncached search.
* FIX: Removed a PHP 8.2 deprecation warning caused by an undeclared property.
* FIX: Plugin uninstall now fully clears search result transients and search index options.
* PERFORMANCE: Product term caching enabled to remove per-result category queries from result rendering.
* PERFORMANCE: Add-to-cart nonce generated once per response instead of once per product.
* PERFORMANCE: Fuzzy search now batch-checks publish status in a single query instead of one per product.
* IMPROVED: SEO and GEO metadata updated around the "WooCommerce Ajax Search" keyword.
* CLEANUP: Removed an unused legacy admin script.
* CLEANUP: Result list styling hardened against theme conflicts.

= 2.0.3 =
* NEW: Default WooCommerce search form is now replaced by NivoSearch automatically when theme integration is enabled.
* NEW: Native WordPress search form and WooCommerce product search form both route through NivoSearch.
* FIX: Resolved a search caching issue that could return stale results after product updates.
* IMPROVED: Plugin metadata updated for better SEO and WordPress.org discoverability.
* IMPROVED: Plugin name and short descriptions refined for clarity.

= 2.0.2 =
* NEW: Fuzzy search engine finds close product name matches when no exact results exist.
* NEW: Search index built and maintained automatically, with one-click rebuild in Settings.
* NEW: "Did you mean?" suggestion with a clickable link to rerun the corrected search.
* NEW: Variation SKU search returns the correct parent product when searching a variation product code.
* NEW: Typo Rules admin page with up to 10 custom correction rules and CSV import and export.
* NEW: Typo correction engine with more than 300 built-in e-commerce misspelling corrections.
* NEW: Accented character support allows "cafe" to find "Café" and vice versa.
* NEW: WPML and Polylang multilingual support, with language scoping on every search.
* NEW: Block and FSE theme support for replacing the Gutenberg core Search block.
* NEW: Preset selector for theme form replacement.
* NEW: Search history panel showing the last eight searches when the search bar receives focus.
* NEW: Full-screen mobile search overlay on small screens.
* NEW: Google Analytics 4, Universal Analytics, and Google Tag Manager search-event integration.
* NEW: WooCommerce products shown on the native WordPress search results page.
* NEW: Optional redirect to the WooCommerce shop page when customers press Enter.
* IMPROVED: Fuzzy search and typo tolerance are independent settings.
* IMPROVED: Matching products are loaded in a single database batch call, avoiding per-product queries.
* IMPROVED: SKU relevance ranking uses a single bulk database query instead of one per product.
* IMPROVED: Product loading switched to direct `wc_get_product()` to find products regardless of how they were created.
* IMPROVED: Index rebuild immediately invalidates the search transient cache.
* FIX: `exclude_out_of_stock` correctly includes products with no stock metadata row.
* SECURITY: Nonce verified on all request paths, including wc-ajax.
* SECURITY: Preset settings sanitized before inclusion in the JSON response.
* SECURITY: Database queries use prepared statements throughout.

= 2.0.0 =
* NEW: Add-to-cart button in search results with instant cart update and no page reload.
* NEW: Optional quantity selector in search results.
* NEW: Variable product support, linking to product pages for option selection.
* NEW: Stock status badges for In Stock, Out of Stock, and On Backorder.
* NEW: Category labels on each product result card.
* NEW: Short descriptions displayed beneath product names.
* NEW: Sale prices displayed automatically when a sale is active.
* NEW: SKU displayed inline next to the product name.
* NEW: Five-minute result cache, cleared automatically when products or presets change.
* NEW: "View All Results" link at the bottom of the results panel.
* NEW: Database migration system for safe plugin updates.
* NEW: Preset data preserved by default on plugin deletion.
* NEW: "Delete all data on uninstall" opt-in setting.
* NEW: Unlimited search presets.
* NEW: Gutenberg block with preset selector.
* FIX: Default preset creation on fresh installation.
* FIX: Deactivation now correctly flushes rewrite rules.
* FIX: Custom CSS classes on the shortcode are applied correctly to the rendered HTML.

= 1.2.0 =
* FIX: Activation race condition resolved so the default preset is reliably created on fresh installation.
* FIX: Deactivation hook now correctly flushes rewrite rules.
* FIX: Shortcode `container_class`, `input_class`, and `results_class` attributes are applied to rendered HTML.
* FIX: Duplicate clear button removed and browser-native X icon suppressed on type="search" inputs.
* NEW: Database migration system for safe plugin updates.
* NEW: Safe uninstall with preset data preserved by default when the plugin is deleted.
* NEW: "Data & Privacy" settings card with opt-in data deletion.
* IMPROVED: Added `aria-label` to the search input and `aria-live` to the results panel for screen readers.
* IMPROVED: Submit button restored for keyboard navigation and accessibility.
* IMPROVED: Default preset now includes all current settings keys on fresh installation.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 2.0.7 =
Documentation update. Search functionality is unchanged.

= 2.0.6 =
Documentation and metadata update. Search functionality is unchanged.

= 2.0.5 =
Maintenance release with performance improvements and multilingual search cache fixes. Recommended for all users.

= 2.0.3 =
Maintenance release. Improves theme search form integration and fixes a search caching edge case. Recommended for all users.

= 2.0.2 =
Major update. After upgrading, go to NivoSearch > Settings > Search Accuracy and click Rebuild Index to update the search index with your latest product catalog.
