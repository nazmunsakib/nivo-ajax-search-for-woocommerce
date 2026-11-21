<?php
/**
 * PHPUnit bootstrap: loads the WordPress test suite, WooCommerce and this plugin.
 *
 * Needs WP_TESTS_DIR (see bin/install-wp-tests.sh) and WooCommerce installed in
 * the test WordPress plugins directory.
 */

$nivo_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : '/tmp/wordpress-tests-lib';

if ( ! file_exists( $nivo_tests_dir . '/includes/functions.php' ) ) {
	echo "WordPress test suite not found in {$nivo_tests_dir}. Run bin/install-wp-tests.sh first.\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );
}

require_once $nivo_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	function () {
		$wc = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
		if ( file_exists( $wc ) ) {
			require $wc;
		}
		require dirname( __DIR__ ) . '/nivo-ajax-search-for-woocommerce.php';
	}
);

// Create WooCommerce tables for the test database.
tests_add_filter(
	'setup_theme',
	function () {
		if ( class_exists( 'WC_Install' ) ) {
			WC_Install::install();
		}
	}
);

require $nivo_tests_dir . '/includes/bootstrap.php';
