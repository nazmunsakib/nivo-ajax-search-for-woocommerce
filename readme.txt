=== NivoSearch – WooCommerce Ajax Product Search ===
Contributors: nazmunsakib
Donate link: https://nazmunsakib.com/donate
Tags: woocommerce search, product search, ajax search, live search, search
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


A powerful WooCommerce Ajax product search plugin. Get instant suggestions, find products by title, SKU, category, description, and more.

== Description ==

**NivoSearch – WooCommerce Ajax Product Search** is a powerful live product search plugin that enhances your WooCommerce store's search functionality. Replace the default WooCommerce search with an advanced AJAX search system that provides instant product search results as customers type.

= Why Choose NivoSearch – WooCommerce Ajax Product Search? =

Transform your WooCommerce search experience with professional live search functionality. This WooCommerce search plugin provides instant product search results, helping customers find products faster and increasing your store's conversion rates.

**[Live Demo](https://nivosearch.com/demo)** | See AJAX Product Search in action!

= Key WooCommerce Search Features =

**Live Product Search:**
* ✅ **Instant AJAX Search** - Real-time product search as customers type
* ✅ **Live Search Results** - Display products instantly without page reload
* ✅ **Search Autocomplete** - Smart search suggestions and autocomplete functionality
* ✅ **Fast Product Search** - Optimized database queries for quick results
* ✅ **Mobile Product Search** - Responsive design for mobile WooCommerce stores

**Advanced Search Functionality:**
* ✅ **Multi-field Product Search** - Search in product titles, descriptions, SKUs, and categories
* ✅ **WooCommerce Category Search** - Separate category search results
* ✅ **Product SKU Search** - Find products by SKU codes
* ✅ **Variable Product Search** - Full support for WooCommerce variations
* ✅ **Search Filter Options** - Filter by stock status and product visibility

**Smart Search Features:**
* ✅ **Search Typo Correction** - Automatically fix common spelling mistakes
* ✅ **Search Suggestions** - Intelligent product suggestions
* ✅ **Search Synonyms** - Expand search with related terms
* ✅ **Relevance Ranking** - Smart product result ordering
* ✅ **Fuzzy Search** - Find products even with partial matches

**WooCommerce Integration:**
* **Native WooCommerce Search** - Deep integration with WooCommerce
* **Product Image Search** - Display product thumbnails in search results
* **Price Display** - Show product prices in search results
* **Stock Status** - Display product availability
* **Add to Cart** - Quick add to cart from search results

= Live Search Customization =

**Search Bar Customization:**
* **Custom Search Styling** - Customize colors, borders, and layout
* **Live Preview** - See changes in real-time
* **Search Placeholder** - Custom placeholder text
* **Search Icon** - Customizable search icons
* **Responsive Design** - Mobile-optimized search interface

**Search Results Customization:**
* **Result Layout** - Customize search result appearance
* **Product Information** - Choose what product data to display
* **Search Result Limit** - Control number of results shown
* **Search Categories** - Enable/disable category search
* **Custom CSS** - Advanced styling options

= WooCommerce Search Performance =

**Optimized Search Performance:**
* **Single Query Search** - Efficient database queries
* **Fast Search Response** - Optimized for speed
* **Search Caching** - Smart caching for better performance
* **Lightweight Code** - Minimal impact on site speed
* **Mobile Optimized** - Fast search on mobile devices



= Developer-Friendly WooCommerce Search =

**Extensive Customization:**
* **15+ Search Hooks** - Extensive filter and action hooks
* **Custom Search Fields** - Add custom product fields to search
* **Search Events** - JavaScript events for custom functionality

**Technical Features:**
* **Modern Architecture** - PSR-4 autoloading and clean code
* **Security Hardened** - Nonce verification and sanitization
* **Translation Ready** - Complete internationalization support
* **WPML Compatible** - Multi-language search support
* **Accessibility** - WCAG compliant search interface

= WooCommerce Search Integration Options =

**Easy Implementation:**
* **Search Shortcode** - `[nivo_search]` shortcode with attributes
* **Gutenberg Search Block** - Drag-and-drop search block
* **PHP Integration** - Programmatic search implementation
* **Theme Integration** - Easy theme integration

**Search Shortcode Attributes:**
* `placeholder` - Custom placeholder text
* `container_class` - Container CSS class
* `input_class` - Input CSS class
* `results_class` - Results CSS class
* `show_icon` - Display search icon
* `style` - Custom inline styles

= WooCommerce Search Use Cases =

**Perfect for:**
* **Online Stores** - Enhance product discovery
* **Large Catalogs** - Help customers find products quickly
* **Mobile Commerce** - Improve mobile shopping experience
* **Multilingual Stores** - Support international customers
* **B2B Stores** - Professional search functionality

**Industries:**
* Fashion and Apparel
* Electronics and Technology
* Home and Garden
* Health and Beauty
* Sports and Outdoors
* Books and Media

= Search Compatibility =

**WordPress Compatibility:**
* WordPress 5.0+ (tested up to 6.8)
* WooCommerce 5.0+ (latest version compatible)
* PHP 7.4+ (optimized for PHP 8.0+)
* MySQL 5.6+ / MariaDB 10.0+

**Theme Compatibility:**
* Works with any WordPress theme
* Popular theme tested (Astra, OceanWP, Storefront)
* Page builder compatible (Elementor, Beaver Builder)
* Custom theme integration support

**Plugin Compatibility:**
* WPML (multilingual support)
* Polylang (translation support)
* WP Rocket (caching compatibility)
* W3 Total Cache (performance optimization)
* Yoast SEO (search optimization)

== Installation ==

= Automatic Installation (Recommended) =

1. **Login to WordPress Admin** - Access your WordPress dashboard
2. **Navigate to Plugins** - Go to Plugins → Add New
3. **Search for Plugin** - Search "AJAX Product Search for WooCommerce" or "NivoSearch"
4. **Install Plugin** - Click "Install Now" then "Activate"
5. **Configure Search** - Go to WooCommerce → NivoSearch
6. **Add Search Form** - Use shortcode `[nivo_search]` or Gutenberg block

= Manual Installation =

1. **Download Plugin** - Download the plugin ZIP file
2. **Upload Plugin** - Go to Plugins → Add New → Upload Plugin
3. **Install ZIP File** - Choose ZIP file and click "Install Now"
4. **Activate Plugin** - Click "Activate Plugin"
5. **Configure Settings** - Navigate to WooCommerce → NivoSearch

= Quick Setup Guide =

**Step 1: Basic Configuration**
* Set search result limit (recommended: 10-15)
* Configure minimum characters (recommended: 2-3)
* Set search delay (recommended: 300ms)

**Step 2: Search Scope**
* Enable product title search (recommended)
* Enable SKU search for product codes
* Enable category search for better navigation
* Configure description search if needed

**Step 3: Styling**
* Customize search bar appearance
* Set colors to match your theme
* Configure search result layout
* Enable live preview for real-time changes

**Step 4: Advanced Features**
* Enable typo correction for better user experience
* Enable synonym support for expanded search
* Test mobile responsiveness

= Implementation Methods =

**Shortcode Implementation:**
```
[nivo_search]
[nivo_search placeholder="Search products..."]
[nivo_search show_icon="true" style="width: 100%;"]
```

**Gutenberg Block:**
1. Add new block in editor
2. Search for "Nivo Search"
3. Configure block settings
4. Customize appearance

**PHP Implementation:**
```php
<?php echo do_shortcode('[nivo_search]'); ?>
```

== Frequently Asked Questions ==

= What is WooCommerce Ajax Product Search? =

NivoSearch – WooCommerce Ajax Product Search is a live product search plugin that replaces the default WooCommerce search with an advanced AJAX-powered search system. It provides instant search results as customers type, improving the shopping experience and helping customers find products faster.

= How does AJAX Product search improve WooCommerce? =

AJAX Product search enhances WooCommerce by providing:
* Instant search results without page reloads
* Better user experience with live search
* Faster product discovery
* Reduced bounce rates
* Increased conversion rates
* Mobile-optimized search functionality

= Does this work with my WooCommerce theme? =

Yes, the AJAX Product search plugin works with any WordPress theme and WooCommerce setup. It inherits your theme's styling and can be fully customized to match your store's design. The plugin has been tested with popular themes like Astra, OceanWP, and Storefront.

= Will AJAX Product search slow down my website? =

No, the plugin is optimized for performance with:
* Lightweight JavaScript (15KB minified)
* Efficient database queries
* Smart caching mechanisms
* Optimized for mobile devices
* Compatible with caching plugins

= Can customers search by product SKU? =

Yes, enable "SKU Search" in the plugin settings to allow customers to find products by entering SKU codes. This is particularly useful for B2B stores and repeat customers who know specific product codes.

= Does it support WooCommerce product variations? =

Yes, the plugin fully supports WooCommerce variable products and searches through all product variations. Customers can find products by searching for variation-specific attributes and details.

= How does the typo correction feature work? =

The typo correction uses advanced algorithms to automatically fix common spelling mistakes. For example:
* "shose" → "shoes"
* "labtop" → "laptop"
* "accesories" → "accessories"
* "jewelery" → "jewelry"

= Can I customize the search results appearance? =

Yes, you can fully customize:
* Search bar colors and styling
* Search result layout and design
* Product information displayed
* Number of results shown
* Mobile responsiveness
* Custom CSS for advanced styling

= Is the plugin translation ready? =

Yes, the plugin is fully translation ready with:
* Complete .pot file for translations
* WPML compatibility for multilingual stores
* RTL language support
* Unicode character support
* Professional translation support

= Does it work with caching plugins? =

Yes, the AJAX Product search (NivoSearch) plugin is compatible with all major caching plugins including:
* WP Rocket
* W3 Total Cache
* WP Super Cache
* LiteSpeed Cache
* Cloudflare


= Is it compatible with multilingual stores? =

Yes, the plugin supports multilingual WooCommerce stores with:
* WPML integration
* Polylang compatibility
* RTL language support
* Translation-ready architecture
* Multi-currency support

== Screenshots ==

1. **Live AJAX Search Results** - Real-time product search with images, prices, and categories
2. **Advanced Admin Settings** - Comprehensive configuration panel with live preview
3. **Mobile Search Interface** - Responsive design optimized for mobile devices
4. **Category Search Results** - Separate category search with product counts
5. **Gutenberg Block Integration** - Easy drag-and-drop search block
6. **Search Scope Configuration** - Control which product fields to search
7. **Styling Customization** - Live preview of search bar and results styling


== Changelog ==

= 1.0.0 – November 2025 =

**Initial Release - Complete AJAX Product Search Solution**

**Core Search Functionality:**
* Real-time AJAX search implementation
* Live product search results
* Multi-field search (title, SKU, description, categories)
* Single optimized database query for performance
* Debounced input with configurable delay
* Smart request cancellation for better performance

**Advanced Search Features:**
* Typo correction with 25+ common spelling fixes
* Search synonym expansion support
* Intelligent relevance-based result ranking
* Fuzzy search with Levenshtein distance algorithm
* Search suggestions and autocomplete

**WooCommerce Integration:**
* Native WooCommerce product search
* Full support for variable products and variations
* Product category search with separate results
* SKU-based product search
* Stock status filtering and control
* Price display in search results

**User Interface & Experience:**
* Modern React-based admin settings panel
* Live preview for all styling customizations
* Responsive design for mobile devices
* Touch-optimized mobile interface
* Accessibility compliant (WCAG 2.1)
* Theme compatibility system

**Customization Options:**
* Comprehensive search bar styling options
* Customizable search result layouts
* Color and border customization
* Typography and spacing controls
* Custom CSS injection support
* Live preview for all changes

**Integration & Implementation:**
* Shortcode support with multiple attributes
* Gutenberg block integration

* PHP function integration


**Developer Features:**
* PSR-4 autoloading architecture
* 15+ hooks and filters for customization
* Custom JavaScript events system

* Clean, documented codebase
* GitHub repository with examples

**Technical Compatibility:**
* WordPress 5.0+ (tested up to 6.8)
* WooCommerce 5.0+ (latest version compatible)
* PHP 7.4+ (optimized for PHP 8.0+)
* MySQL 5.6+ / MariaDB 10.0+
* Major caching plugin compatibility
* Popular theme compatibility

== Upgrade Notice ==

= 1.0.0 =
Initial release of AJAX Product Search for WooCommerce. Install now to enhance your WooCommerce store with professional live product search functionality, improving customer experience and increasing sales conversions.

== Additional Information ==

= About AJAX Product Search for WooCommerce (NivoSearch) =

AJAX Product Search for WooCommerce is developed to provide WooCommerce store owners with a professional-grade search solution that enhances the customer shopping experience. The plugin focuses on performance, usability, and conversion optimization.

= Developer Information =

**Developer:** [Nazmun Sakib](https://nazmunsakib.com)  
**GitHub:** [nazmunsakib](https://github.com/nazmunsakib/)  
**Experience:** WordPress and WooCommerce development specialist
**Focus:** eCommerce solutions and performance optimization

= Useful Resources =

**Documentation & Support:**
* [Docs](https://nivosearch.com/docs)
* [GitHub Repository](https://github.com/nazmunsakib/nivo-ajax-search-for-woocommerce)
* [WordPress Support Forum](https://wordpress.org/support/plugin/nivo-ajax-search-for-woocommerce/)
* [Feature Requests](https://github.com/nazmunsakib/nivo-ajax-search-for-woocommerce/issues)

**Privacy:** zero tracking, zero external calls, 100 % GPL.