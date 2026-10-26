=== NivoSearch – Ajax Search for WooCommerce ===
Contributors: nazmunsakib
Donate link: https://nivosearch.com
Tags: woocommerce, woocommerce search, product search, instant search, ajax search
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce Ajax Search with live product suggestions, typo correction, SKU matching, and add to cart from results.

== Description ==

[NivoSearch](https://nivosearch.com/) is a free WooCommerce Ajax Search plugin for WooCommerce stores. It displays matching product suggestions as shoppers type, without a full page reload. Depending on preset settings, it can search product names, descriptions, SKUs, categories, and tags. Built-in typo correction, fuzzy matching, and optional cart controls give shoppers more ways to find and act on products.

[Live demo](https://nivosearch.com/live-demo/) | [Features](https://nivosearch.com/features/) | [Documentation](https://nivosearch.com/documentation/) | [WordPress.org support](https://wordpress.org/support/plugin/nivo-ajax-search-for-woocommerce/)

= Why choose NivoSearch? =

NivoSearch is a WooCommerce search plugin that adds live product suggestions, typo correction, SKU matching, and optional cart controls. Store owners can configure what each search bar finds and which product details it displays.

NivoSearch is the free WordPress.org plugin, with up to 10 custom typo correction rules. Project documentation also refers to a separate Pro tier; it is not part of this plugin download.

= Features =

* **Live product search:** Matching products appear as shoppers type, without a full page reload. Results are ordered by relevance, with product title and SKU matches contributing to ranking.
* **Product and taxonomy search:** Choose whether to search product titles, full and short descriptions, and SKUs. When SKU search is enabled, variation SKU matches return the parent product. Category and tag matches can appear in their own result sections when enabled.
* **Typo correction and fuzzy search:** A built-in dictionary contains more than 300 common corrections. An indexed fuzzy search fallback can suggest a close match when a search returns no products. A clickable “Did you mean?” suggestion is optional.
* **Product result details:** Choose whether results show images, prices, SKUs, short descriptions, stock status, category labels, and highlighted search terms.
* **Product filters:** Exclude out-of-stock products or selected product IDs from search results.
* **Add to cart from results:** Optionally show an add-to-cart control and quantity selector for eligible products. Variable products link to their product page so shoppers can choose options.
* **Unlimited search presets:** Configure separate search scopes, display settings, colors, dimensions, result counts, and search delays for different search bars.
* **Shortcode and block placement:** Add a preset with the `[nivo_search id="123"]` shortcode or the NivoSearch block in the block editor.
* **Optional theme replacement:** Replace search forms rendered through supported WordPress and WooCommerce hooks or the core Search block. Custom theme markup may not use these pathways; use the shortcode or block if replacement does not apply.
* **Mobile search and history:** Use a full-screen overlay on narrow screens. Keep up to eight recent queries in the visitor's browser, show them when the search input is focused and empty, and remove individual or all entries.
* **Multilingual and accented search:** Scope product and taxonomy results to the current WPML or Polylang language and match accented or unaccented words.
* **Search results page:** When enabled, send submitted WordPress searches to the WooCommerce shop page; if that page is unavailable, show product-only search results.
* **View all results:** Optionally show a link from the suggestions panel to the full product results page.
* **Analytics events:** Send search events to an existing GA4 or Google Tag Manager integration. The plugin does not load their scripts; see Privacy and data for what is sent and the default setting.
* **Search index and cache:** Maintain a local product word index for fuzzy matching and cache results for five minutes. The index can be rebuilt from settings.

= Who is NivoSearch for? =

NivoSearch is for WooCommerce store owners who want customers to find products through live search without custom development. It can be useful for stores where shoppers:

* Browse catalogs with product variations and need to search by SKU.
* Make spelling mistakes or use accented and unaccented forms of product names.
* Shop on mobile devices or need search results in more than one language.
* Need separate search bars with different fields, displays, or styling.

= Search index and performance =

The regular product search uses WordPress queries. A local product word index supports the fuzzy search fallback and can be rebuilt from NivoSearch settings. Product saves update the index; use the rebuild control after imports if indexed terms are missing. Results are cached for five minutes and the cache is invalidated when products or presets change. Actual response times depend on the store's hosting, database, catalog, and configuration; no fixed response-time or catalog-size guarantee is made.

= Compared with the standard WooCommerce search =

A standard search form generally submits the query and loads a results page. NivoSearch adds a live suggestions panel so shoppers can inspect matching products while staying on the current page. Its optional typo handling, SKU search, product details, and cart controls are designed for product discovery; the search scope and displayed details are configurable per preset.

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

Frontend events are dispatched on `document` and use the `nivo_search:` prefix: `nivo_search:init`, `nivo_search:beforeSearch`, `nivo_search:resultsDisplayed`, `nivo_search:noResults`, `nivo_search:error`, `nivo_search:resultsCleared`, `nivo_search:focus`, `nivo_search:blur`, `nivo_search:didYouMeanClicked`, `nivo_search:addedToCart`, `nivo_search:mobileOverlayOpened`, and `nivo_search:mobileOverlayClosed`.

= Privacy and data =

Search queries are sent to the store's own WordPress site to retrieve results. Recent searches are stored in the visitor's browser using localStorage, with up to eight entries. When the typo correction system changes a query, NivoSearch records the original and corrected terms in a local correction analytics table.

The Google Analytics tracking setting is enabled by default. If an existing Google Analytics 4 `gtag` function or Google Tag Manager `dataLayer` is present, NivoSearch sends a search event containing the query and result count. NivoSearch does not load those analytics scripts or check consent itself. Disable the setting in NivoSearch settings, or coordinate it with your site's consent tool, as required by your privacy and consent policy.

When the plugin is deactivated or deleted, presets and settings are preserved by default. Transient caches are removed on deletion. Enable “Delete all data on uninstall” in NivoSearch settings before deleting the plugin if you want it to remove its presets, settings, and custom tables as well.

= Developer =

NivoSearch is developed and maintained by [Nazmun Sakib](https://nazmunsakib.com). See the [GitHub repository](https://github.com/nazmunsakib/nivo-ajax-search-for-woocommerce) for source code and the [documentation](https://nivosearch.com/documentation/) for setup guides.

== Installation ==

1. In your WordPress dashboard, go to **Plugins > Add New**.
2. Search for **NivoSearch** and select **Install Now**.
3. Select **Activate** after installation completes.
4. Go to **NivoSearch > Search Presets** to configure a preset.
5. Add the search bar using its shortcode or the NivoSearch block. To replace supported theme search forms instead, go to **NivoSearch > Settings > Search Integration** and enable **Replace theme search form**.

== Frequently Asked Questions ==

= What is WooCommerce Ajax Search? =

WooCommerce Ajax Search retrieves product matches in a background request while the customer types. Unlike submitting a standard search form, it can show suggestions without loading a new page. NivoSearch provides this live search interface for WooCommerce products.

= Is NivoSearch a WooCommerce Search plugin? =

Yes. NivoSearch is a WooCommerce Search plugin that adds a live product suggestions panel, configurable product search, typo correction, SKU matching, and optional cart controls to a WooCommerce store.

= How is NivoSearch different from the standard WooCommerce search? =

The standard search form typically submits the query and loads a results page. NivoSearch can show product suggestions in a dropdown while the customer stays on the current page. It also offers configurable SKU and variation SKU matching, typo correction, product details, and add-to-cart controls.

= Can shoppers search by product SKU or variation SKU? =

Yes. Enable SKU search in a preset to match parent product SKUs and variation SKUs. A variation SKU match returns the parent product in the results.

= Can customers add products to the cart from search results? =

An add-to-cart control can be enabled in a preset for eligible products. Variable products link to their product page so customers can select the required variation. The quantity selector is optional.

= What happens if a shopper misspells a search? =

When typo tolerance is enabled, NivoSearch first checks the query against its built-in correction dictionary and custom rules. When fuzzy search is enabled, it can use the local product word index to find close terms after a search returns no products. A clickable “Did you mean?” suggestion can be shown when that setting is enabled. The fuzzy fallback requires the search index to be built.

= Does NivoSearch work on mobile devices and block themes? =

The search interface is responsive and can use a full-screen overlay on narrow screens. NivoSearch can also replace the core Search block when the optional theme replacement setting is enabled. Theme replacement depends on how a theme renders its search form; use the NivoSearch block or shortcode if a custom form is not intercepted.

= Does NivoSearch support WPML and Polylang? =

Yes. When WPML or Polylang is active, NivoSearch passes the current language to product and taxonomy searches. Compatibility depends on the installed versions of WordPress, WooCommerce, and the multilingual plugin.

= How do I add or configure a search bar? =

Create or edit a preset under **NivoSearch > Search Presets**. Choose the product fields to search and the details to display, then publish it. Place the generated shortcode on a page or post, or add a NivoSearch block. To replace supported theme search forms, enable that option under **NivoSearch > Settings > Search Integration**.

= What analytics events does NivoSearch support? =

When Google Analytics tracking is enabled and an analytics integration is already present, NivoSearch sends a GA4 `search` event through `gtag` and pushes a `nivo_search` event with the query and result count to Google Tag Manager's `dataLayer`. It also dispatches a `nivoSearch` browser event for custom integrations. NivoSearch does not install or configure analytics scripts for you.

= What are the performance considerations? =

Search speed depends on your hosting, database, product catalog, and active configuration. Standard product searches use WordPress queries; the local index is for fuzzy matching, not a replacement search engine. Results are cached for five minutes. Rebuild the index from **NivoSearch > Settings > Search Accuracy** if fuzzy matching does not include recently imported products.

= What information does NivoSearch store or transmit? =

NivoSearch sends each search query to your own WordPress site to return matching products. It stores up to eight recent queries in the visitor's browser and logs original and corrected terms locally when a typo correction is applied. If Google Analytics tracking is enabled and the relevant analytics script is present, the query and result count are also sent through that integration. The plugin does not load third-party analytics scripts.

= What happens to settings when NivoSearch is uninstalled? =

Deactivating the plugin does not delete its presets or settings. On deletion, transient caches are removed, while presets, settings, and custom tables are preserved by default. Enable **Delete all data on uninstall** in NivoSearch settings before deleting the plugin to request full data removal.

= Which WordPress, WooCommerce, and PHP versions are declared as supported? =

The readme metadata requires WordPress 5.0 or later, lists testing through WordPress 7.1, and requires PHP 7.4 or later. The plugin header declares WooCommerce 5.0 or later, testing through WooCommerce 8.5, and compatibility with High-Performance Order Storage (HPOS). These are the project's compatibility declarations, not a guarantee for every theme, extension, or newer release.

== Screenshots ==

1. Live product suggestions with product details and an add-to-cart control.
2. A “Did you mean?” correction suggestion in the results panel.
3. Search preset list with a shortcode for each preset.
4. Preset search, display, and style settings.
5. Search Accuracy controls and the product index rebuild option.
6. Search Integration options for theme replacement, results pages, and analytics.

== Changelog ==

= 2.0.6 =
* IMPROVED: Updated plugin documentation and metadata with verified feature, compatibility, privacy, and setup information.

= 2.0.5 =
* FIX: Search results cache is now scoped per language and per accuracy setting — multilingual sites and typo/fuzzy toggle changes no longer serve stale results
* FIX: Typo-correction analytics are now counted on cached searches too, not only the first uncached search
* FIX: Removed a PHP 8.2 deprecation warning caused by an undeclared property
* FIX: Plugin uninstall now fully clears search result transients and search index options
* PERFORMANCE: Product term caching enabled — removes per-result category queries from result rendering
* PERFORMANCE: Add-to-cart nonce generated once per response instead of once per product
* PERFORMANCE: Fuzzy search now batch-checks publish status in a single query instead of one per product
* IMPROVED: SEO and GEO metadata updated around the "WooCommerce Ajax Search" keyword
* CLEANUP: Removed an unused legacy admin script
* CLEANUP: Result list styling hardened against theme conflicts

= 2.0.3 =
* NEW: Default WooCommerce search form is now replaced by NivoSearch automatically when theme integration is enabled
* NEW: Native WordPress search form and WooCommerce product search form both route through NivoSearch
* FIX: Resolved a search caching issue that could return stale results after product updates
* IMPROVED: Plugin metadata updated for better SEO and WordPress.org discoverability
* IMPROVED: Plugin name and short descriptions refined for clarity

= 2.0.2 =
* NEW: Fuzzy search engine: finds close product name matches when no exact results exist
* NEW: Search index built and maintained automatically, with one-click rebuild in Settings
* NEW: "Did you mean?" suggestion with a clickable link to re-run the corrected search
* NEW: Variation SKU search: searching a variation product code returns the correct parent product
* NEW: Typo Rules admin page: add up to 10 custom correction rules, import and export as CSV
* NEW: Typo correction engine with 300+ built-in e-commerce misspelling corrections
* NEW: Accented character support: "cafe" finds "Café" and vice versa
* NEW: WPML and Polylang multilingual support: language scoped automatically on every search
* NEW: Block and FSE theme support: replaces the Gutenberg core search block automatically
* NEW: Preset selector for theme form replacement: choose which preset replaces your theme's search box
* NEW: Search history panel: last 8 searches shown when the search bar receives focus
* NEW: Mobile search overlay: full-screen search opens automatically on small screens
* NEW: Google Analytics 4, Universal Analytics, and Google Tag Manager: automatic search event on each search
* NEW: WooCommerce products shown on the native WordPress search results page
* NEW: Optional redirect to the WooCommerce shop page when the customer presses Enter
* IMPROVED: Fuzzy search and typo tolerance are now independent toggles in Settings
* IMPROVED: All matching products loaded in a single database batch call, with no per-product queries
* IMPROVED: SKU relevance ranking uses a single bulk database query instead of one per product
* IMPROVED: Product loading switched to direct `wc_get_product()`: finds all products regardless of how they were created
* IMPROVED: Index rebuild now also invalidates the search transient cache immediately
* IMPROVED: `exclude_out_of_stock` filter now correctly includes products with no stock meta row
* SECURITY: Nonce verified on all request paths including wc-ajax
* SECURITY: Preset settings sanitized before including in the JSON response
* SECURITY: All database queries use prepared statements throughout

= 2.0.0 =
* NEW: Add-to-cart button in search results with instant cart update and no page reload
* NEW: Quantity selector in search results, configurable per preset
* NEW: Variable product support: link to product page for option selection
* NEW: Stock status badge in results: In Stock, Out of Stock, On Backorder
* NEW: Category labels on each product result card
* NEW: Short description shown beneath the product name in results
* NEW: Sale price shown automatically when a sale is active
* NEW: SKU displayed inline next to the product name
* NEW: 5-minute result cache, cleared automatically when products or presets change
* NEW: "View All Results" link pinned to the bottom of the results panel
* NEW: Database migration system for safe plugin updates
* NEW: Preset data preserved by default on plugin deletion
* NEW: "Delete all data on uninstall" opt-in setting
* NEW: Unlimited search presets
* NEW: Gutenberg block with preset selector
* FIX: Default preset not created reliably on fresh install
* FIX: Deactivation now correctly flushes rewrite rules
* FIX: Custom CSS class attributes on the shortcode now applied correctly to the HTML output

= 1.2.0 =
* FIX: Activation race condition, default preset now reliably created on fresh install
* FIX: Deactivation hook now correctly flushes rewrite rules
* FIX: Shortcode `container_class`, `input_class`, and `results_class` attributes now applied to rendered HTML
* FIX: Duplicate clear button, suppressed browser-native X icon on type="search" inputs
* NEW: Database migration system for safe, zero-downtime plugin updates
* NEW: Safe uninstall, preset data is preserved by default when the plugin is deleted
* NEW: “Data & Privacy” settings card with opt-in data deletion toggle
* IMPROVED: aria-label on search input and aria-live on results panel for screen readers
* IMPROVED: Submit button restored for keyboard navigation and accessibility users
* IMPROVED: Default preset now pre-populated with all current settings keys on fresh install

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.0.6 =
Documentation and metadata update. Search functionality is unchanged.

= 2.0.5 =
Maintenance release with performance improvements and multilingual search cache fixes. Recommended for all users.

= 2.0.3 =
Maintenance release. Improves theme search form integration and fixes a search caching edge case. Recommended for all users.

= 2.0.2 =
Major update. After upgrading, go to NivoSearch > Settings > Search Accuracy and click Rebuild Index to update the search index with your latest product catalog.
