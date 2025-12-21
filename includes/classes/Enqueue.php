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
				)
			)
		);

		wp_localize_script( 'nivo-search', 'nivo_search', $localize_data );
	}

	/**
	 * Check if assets should be enqueued
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function should_enqueue_assets() {
		return apply_filters( 'nivo_search_should_enqueue_assets', true );
	}
}
