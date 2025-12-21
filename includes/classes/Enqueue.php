<?php
/**
 * Asset Enqueue Handler
 *
 * @package NivoSearch
 * @since 1.0.0
 */


namespace NivoSearch;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Class
 *
 * Handles all frontend and backend asset loading.
 * Provides hooks for extensibility and customization.
 *
 * @since 1.0.0
 */
class Enqueue {

	/**
	 * Plugin version for cache busting
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $version;

	/**
	 * Plugin URL for asset paths
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $plugin_url;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->version    = NIVO_SEARCH_VERSION;
		$this->plugin_url = NIVO_SEARCH_PLUGIN_URL;

		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Enqueue frontend assets
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		// Only enqueue on pages that need it
		if ( ! $this->should_enqueue_assets() ) {
			return;
		}

		$this->enqueue_scripts();
		$this->enqueue_styles();
		$this->localize_scripts();
	}

	/**
	 * Enqueue admin assets
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		// No admin assets needed for simple settings page
		return;
	}

	/**
	 * Enqueue JavaScript files
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function enqueue_scripts() {
		// Main search script (no jQuery dependency)
		$script_deps    = apply_filters( 'nivo_search_script_dependencies', array() );
		$script_version = apply_filters( 'nivo_search_script_version', $this->version );

		wp_enqueue_script(
			'nivo-search',
			$this->plugin_url . 'assets/js/nivo-search.js',
			$script_deps,
			$script_version,
			true
		);

		// Allow additional scripts
		do_action( 'nivo_search_after_enqueue_scripts' );
	}

	/**
	 * Enqueue CSS files
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function enqueue_styles() {
		$style_deps    = apply_filters( 'nivo_search_style_dependencies', array() );
		$style_version = apply_filters( 'nivo_search_style_version', $this->version );

		wp_enqueue_style(
			'nivo-search',
			$this->plugin_url . 'assets/css/nivo-search.css',
			$style_deps,
			$style_version
		);

		// Add inline styles for customization
		$custom_css = $this->generate_custom_css();
		wp_add_inline_style( 'nivo-search', $custom_css );

		// Allow additional styles
		do_action( 'nivo_search_after_enqueue_styles' );
	}

	/**
	 * Localize scripts with data
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function localize_scripts() {
		$wc_ajax_url = '';
		if ( class_exists( 'WC_AJAX' ) ) {
			$wc_ajax_url = \WC_AJAX::get_endpoint( 'nivo_search' );
		}

		// Get preset settings if on page with preset
		$preset_settings = $this->get_page_preset_settings();

		$localize_data = apply_filters(
			'nivo_search_localize_data',
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'wc_ajax_url' => $wc_ajax_url,
				'nonce'       => wp_create_nonce( 'nivo_search_nonce' ),
				'strings'     => array(
					'no_results' => esc_html__( 'No products found', 'nivo-ajax-search-for-woocommerce' ),
					'loading'    => esc_html__( 'Loading...', 'nivo-ajax-search-for-woocommerce' ),
					'error'      => esc_html__( 'Search error occurred', 'nivo-ajax-search-for-woocommerce' ),
					'view_all'   => esc_html__( 'View All Results', 'nivo-ajax-search-for-woocommerce' ),
				),
				'settings' => ! empty( $preset_settings ) ? $preset_settings : array(
					'min_length'       => (int) get_option( 'nivo_search_min_chars', 3 ),
					'delay'            => 300,
					'max_results'      => (int) get_option( 'nivo_search_limit', 10 ),
					'show_images'      => (int) get_option( 'nivo_search_show_images', 1 ),
					'show_price'       => (int) get_option( 'nivo_search_show_price', 1 ),
					'show_sku'         => (int) get_option( 'nivo_search_show_sku', 0 ),
					'show_description' => (int) get_option( 'nivo_search_show_description', 0 ),
					'show_add_to_cart' => (int) get_option( 'nivo_search_show_add_to_cart', 0 ),
					'border_width' => get_option( 'nivo_search_border_width', 1 ),
					'border_color' => get_option( 'nivo_search_border_color', '' ),
					'border_radius' => get_option( 'nivo_search_border_radius', 5 ),
					'bg_color' => get_option( 'nivo_search_bg_color', '#dfdfdf' ),
					'results_border_width' => get_option( 'nivo_search_results_border_width', 1 ),
					'results_border_color' => get_option( 'nivo_search_results_border_color', '#ddd' ),
					'results_border_radius' => get_option( 'nivo_search_results_border_radius', 4 ),
					'results_bg_color' => get_option( 'nivo_search_results_bg_color', '#ffffff' ),
					'results_padding' => get_option( 'nivo_search_results_padding', 5 ),
				),
			)
		);

		wp_localize_script( 'nivo-search', 'nivo_search', $localize_data );
	}

	/**
	 * Generate custom CSS based on settings
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function generate_custom_css() {
		$css = '';
		
		// Search bar styles.
		$nivo_search_border_width	= get_option( 'nivo_search_border_width', 1 );
		$search_bar_border_color	= get_option( 'nivo_search_border_color', '' );
		$nivo_search_border_radius	= get_option( 'nivo_search_border_radius', 5 );
		$nivo_search_bg_color		= get_option( 'nivo_search_bg_color', '' );
		$nivo_search_bar_height		= get_option( 'nivo_search_bar_height', 50 );

		if (  ! empty( $nivo_search_border_width ) || ! empty( $nivo_search_border_radius ) || ! empty( $nivo_search_bg_color ) || ! empty( $nivo_search_bar_height ) ) {
			$css .= '.nivo-search-wrapper input[type=search].nivo-search-product-search {';

			if ( ! empty( $nivo_search_bar_height ) ) {
				$css .= 'height: ' . esc_attr( $nivo_search_bar_height ) . 'px' . ';';
			}
			
			if ( ! empty( $nivo_search_border_width ) && ! empty( $search_bar_border_color ) ) {
				$css .= 'border: ' . esc_attr( $nivo_search_border_width ) . 'px solid' . $search_bar_border_color . ';';
			}
			
			if ( ! empty( $nivo_search_border_radius ) ) {
				$css .= 'border-radius: ' . esc_attr( $nivo_search_border_radius ) . 'px ;';
			}
			
			if ( ! empty( $nivo_search_bg_color ) ) {
				$css .= 'background-color: ' . esc_attr( $nivo_search_bg_color ) . ';';
			}
			
			$css .= '}';
		}
		
		// Search bar width
		$width = get_option( 'nivo_search_bar_width', 600 );
		$css .= sprintf(
			'.nivo-search-form-wrapper { max-width: %dpx !important; }',
			$width
		);

		//Theme inheritance overrides.
		$custom_text_color  = get_option( 'nivo_search_text_color', '' );
		$custom_hover_color = get_option( 'nivo_search_hover_color', '' );
		$custom_hover_bg    = get_option( 'nivo_search_hover_bg', '' );
		
		if (  ! empty( $custom_text_color ) || ! empty( $custom_hover_color ) || ! empty( $custom_hover_bg ) ) {
			$css .= ':root {';
			
			if ( ! empty( $custom_text_color ) ) {
				$css .= '--nivo-search-text-color: ' . esc_attr( $custom_text_color ) . ';';
			}
			
			if ( ! empty( $custom_hover_color ) ) {
				$css .= '--nivo-search-hover-color: ' . esc_attr( $custom_hover_color ) . ';';
			}
			
			if ( ! empty( $custom_hover_bg ) ) {
				$css .= '--nivo-search-hover-bg: ' . esc_attr( $custom_hover_bg ) . ';';
			}
			
			$css .= '}';
		}
		
		return $css;
	}

	/**
	 * Enqueue block editor assets
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		// Enqueue frontend styles in block editor
		$this->enqueue_styles();
		$this->enqueue_scripts();
		$this->localize_scripts();
	}

	/**
	 * Check if assets should be enqueued
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function should_enqueue_assets() {
		// Always enqueue for now - can be optimized later.
		$should_enqueue = true;

		// Allow filtering.
		return apply_filters( 'nivo_search_should_enqueue_assets', $should_enqueue );
	}

	/**
	 * Get preset settings from page content
	 *
	 * @since 1.1.0
	 * @return array
	 */
	private function get_page_preset_settings() {
		// This is a simplified version - in production you might want to parse the content
		// or use a more sophisticated method to detect presets
		return array();
	}
}