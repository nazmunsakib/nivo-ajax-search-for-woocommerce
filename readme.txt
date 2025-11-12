=== Live AJAX Search for WooCommerce - NivoSearch ===
Contributors: nazmunsakib
Donate link: https://nazmunsakib.com/donate
Tags: ajax search, live search, WooCommerce search, product search, instant search
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Live AJAX product search for WooCommerce with typo correction and category search functionality.

== Description ==

**Live AJAX Search for WooCommerce** provides real-time product search functionality for WooCommerce stores. The plugin includes AI-powered features, modern admin interface, and comprehensive customization options.

= Key Features =

**Search Functionality:**
* Real-time AJAX search as you type
* Search in product titles, SKUs, descriptions, and categories
* Typo correction with common spelling fixes
* Synonym support for related terms
* Relevance-based result ranking

**User Interface:**
* React-based admin settings panel
* Live preview for styling changes
* Responsive design for mobile devices
* Customizable colors, borders, and layout
* Theme compatibility

**Search Scope Options:**
* Product titles (enabled by default)
* Product SKUs (enabled by default)
* Product descriptions (optional)
* Short descriptions (optional)
* Product categories (optional)
* Out of stock product control (optional)

**Display Options:**
* Product thumbnails in results
* Price information display
* SKU codes in results
* Short descriptions
* Stock status filtering

**Technical Features:**
* Single optimized database query
* Debounced input handling
* Request cancellation for performance
* 15+ developer hooks and filters
* Translation ready with .pot file

= Integration Options =

* Shortcode: `[nivo_search]`
* Gutenberg block: "Nivo Search"
* Widget support for sidebars
* PHP function integration

= AI Features =

The plugin includes optional AI-powered enhancements:

* **Typo Correction**: Automatically corrects common spelling mistakes
* **Synonym Support**: Expands search terms with related words
* **Smart Ranking**: Prioritizes results by relevance

These features can be enabled or disabled in the settings.

= Developer Friendly =

* PSR-4 autoloading architecture
* Extensive hook system with 15+ filters and actions
* Custom JavaScript events
* Clean, documented code
* GitHub repository available

= Multilingual Support =

* Translation ready with a complete .pot file
* WPML compatibility
* RTL language support
* Unicode character support

== Installation ==

= Automatic Installation =

1. Go to **Plugins <span aria-hidden="true" class="wp-exclude-emoji"><span aria-hidden="true" class="wp-exclude-emoji"><span aria-hidden="true" class="wp-exclude-emoji">→</span></span></span> Add New** in WordPress admin
2. Search for **"Live AJAX Search for WooCommerce"**
3. Click **Install Now** then **Activate**
4. Configure settings at **WooCommerce <span aria-hidden="true" class="wp-exclude-emoji"><span aria-hidden="true" class="wp-exclude-emoji"><span aria-hidden="true" class="wp-exclude-emoji">→</span></span></span> NivoSearch**
5. Add search form using shortcode `[nivo_search]`

= Manual Installation =

1. Download the plugin ZIP file
2. Go to **Plugins <span aria-hidden="true" class="wp-exclude-emoji"><span aria-hidden="true" class="wp-exclude-emoji"><span aria-hidden="true" class="wp-exclude-emoji">→</span></span></span> Add New <span aria-hidden="true" class="wp-exclude-emoji"><span aria-hidden="true" class="wp-exclude-emoji"><span aria-hidden="true" class="wp-exclude-emoji">→</span></span></span> Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**
5. Configure at **WooCommerce <span aria-hidden="true" class="wp-exclude-emoji"><span aria-hidden="true" class="wp-exclude-emoji"><span aria-hidden="true" class="wp-exclude-emoji">→</span></span></span> NivoSearch**

= Configuration =

After activation, configure the plugin:

1. **General Settings**: Enable AJAX search, set result limits and search delay
2. **Search Scope**: Choose which fields to search (title, SKU, description, categories)
3. **Search Bar Styling**: Customize appearance with live preview
4. **Search Results**: Configure result display options
5. **AI Features**: Enable typo correction and synonym support

= Usage =

**Shortcode**: `[nivo_search]`
**Gutenberg Block**: Search for "Nivo Search" in block editor
**PHP Code**: `<?php echo do_shortcode('[nivo_search]'); ?>`
**Widget**: Add to any widget area

== Frequently Asked Questions ==

= Does this work with my theme? =

Yes, the plugin works with any WordPress theme. It inherits your theme's styling and can be customized to match your design.

= Will it affect my website's performance? =

The plugin is optimized for performance with lightweight JavaScript (15KB), single database queries, and smart request handling.

= Does it work on mobile devices? =

Yes, the plugin includes responsive design and touch-optimized interface for mobile devices.

= Can I search by product SKU? =

Yes, enable "Search in SKU" in the Search Scope settings to allow customers to find products by SKU code.

= Does it support WooCommerce variations? =

Yes, the plugin fully supports variable products and searches through all variations.

= How does typo correction work? =

The plugin uses the Levenshtein distance algorithm to find matches even with spelling mistakes. For example: "shose" matches "shoes", "labtop" matches "laptop".

= Can I customize the appearance? =

Yes, you can customize colors, borders, spacing, and layout through the settings panel with live preview.

= Is it translation ready? =

Yes, the plugin includes a complete .pot file for translations and is compatible with WPML for multilingual sites.

= Does it work with caching plugins? =

Yes, the plugin is compatible with major caching plugins including WP Rocket, W3 Total Cache, and WP Super Cache.

= How do I get support? =

Support is available through:
– [WordPress Support Forum](https://wordpress.org/support/plugin/nivo-ajax-search-for-woocommerce/)
– [GitHub Issues](https://github.com/nazmunsakib/nivo-ajax-search-for-woocommerce/issues)

== Screenshots ==

1. Live search results showing products with images and prices
2. React-based admin settings interface with live preview
3. Mobile responsive search interface
4. Category search results displayed separately
5. Gutenberg block integration
6. Search scope configuration options
7. AI features settings panel
8. Live styling preview in admin

== Changelog ==

= 1.0.0 – January 2025 =

Initial release with the following features:

**Core Functionality:**
* Real-time AJAX search implementation
* Multi-field search (title, SKU, description, categories)
* Single optimized database query
* Debounced input with configurable delay
* Request cancellation for performance

**AI Features:**
* Typo correction with 25+ common fixes
* Synonym expansion support
* Relevance-based result ranking
* Fuzzy search with Levenshtein distance algorithm

**User Interface:**
* React-based admin settings panel
* Live preview for styling options
* Responsive design for all devices
* Theme compatibility system
* Customizable colors and layout

**Search Options:**
* Product title search (default: enabled)
* Product SKU search (default: enabled)
* Product description search (optional)
* Short description search (optional)
* Category search (optional)
* Out of stock control (optional)

**Integration:**
* Shortcode support with attributes
* Gutenberg block integration
* Widget area compatibility
* PHP function integration

**Developer Features:**
* PSR-4 autoloading architecture
* 15+ hooks and filters
* Custom JavaScript events
* Translation ready (.pot file)
* Security hardened with nonce verification

**Technical:**
* WordPress 5.0+ compatibility (tested up to 6.8)
* WooCommerce 5.0+ compatibility
* PHP 7.4+ requirement
* WPML multilingual support
* Caching plugin compatibility

== Upgrade Notice ==

= 1.0.0 =
Initial release of Live AJAX Search for WooCommerce. Install to add real-time product search functionality to your WooCommerce store.

== Additional Info ==

**About the Developer**

Created by [Nazmun Sakib](https://nazmunsakib.com), a WordPress developer with experience in eCommerce solutions.

**Useful Links**

* [GitHub Repository](https://github.com/nazmunsakib/nivo-ajax-search-for-woocommerce)
* [Support Forum](https://wordpress.org/support/plugin/nivo-ajax-search-for-woocommerce/)

**Privacy**

* No external API calls or data collection
* All searches processed locally on your server
* GDPR compliant by design
* Security hardened with nonce verification

**Contributing**

Feature requests and bug reports welcome:
* [GitHub Issues](https://github.com/nazmunsakib/nivo-ajax-search-for-woocommerce/issues)
* [WordPress Support Forum](https://wordpress.org/support/plugin/nivo-ajax-search-for-woocommerce/)