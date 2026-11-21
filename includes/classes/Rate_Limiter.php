<?php
/**
 * Fixed-window rate limiter for the public search endpoint.
 *
 * Uses the object cache when a persistent one is available (atomic increments,
 * no database writes) and falls back to transients otherwise.
 *
 * @package NivoSearch
 * @since   2.0.8
 */

namespace NivoSearch;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rate_Limiter {

	const CACHE_GROUP = 'nivo_search_rl';

	/**
	 * Limit and window, filterable. A limit of 0 disables rate limiting.
	 *
	 * @return array{0:int,1:int} [ max requests, window in seconds ].
	 */
	public static function get_limits() {
		/**
		 * Filters the search rate limit.
		 *
		 * @param array $limits [ max requests per window, window length in seconds ].
		 */
		$limits = apply_filters( 'nivo_search_rate_limit', array( 120, MINUTE_IN_SECONDS ) );

		$max    = isset( $limits[0] ) ? max( 0, (int) $limits[0] ) : 0;
		$window = isset( $limits[1] ) ? max( 1, (int) $limits[1] ) : MINUTE_IN_SECONDS;

		return array( $max, $window );
	}

	/**
	 * Client identifier. Uses REMOTE_ADDR only, because forwarded headers can be
	 * spoofed. Sites behind a proxy or CDN can supply the real IP via the filter.
	 *
	 * @return string
	 */
	public static function get_client_id() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		/**
		 * Filters the client IP used for rate limiting.
		 *
		 * @param string $ip Client IP address.
		 */
		return (string) apply_filters( 'nivo_search_rate_limit_ip', $ip );
	}

	/**
	 * Count this request and report whether it is allowed.
	 *
	 * @param string|null $client_id Optional client identifier (defaults to the visitor IP).
	 * @return bool True when the request is within the limit.
	 */
	public static function allow( $client_id = null ) {
		list( $max, $window ) = self::get_limits();

		if ( 0 === $max ) {
			return true;
		}

		if ( null === $client_id ) {
			$client_id = self::get_client_id();
		}

		$key   = md5( $client_id ) . '_' . (int) floor( time() / $window );
		$count = self::increment( $key, $window );

		return $count <= $max;
	}

	/**
	 * Seconds until the current window ends.
	 *
	 * @return int
	 */
	public static function retry_after() {
		list( , $window ) = self::get_limits();

		return max( 1, $window - ( time() % $window ) );
	}

	/**
	 * Increment the counter for a key and return the new value.
	 *
	 * @param string $key    Counter key (already includes the window).
	 * @param int    $window Window length in seconds.
	 * @return int
	 */
	private static function increment( $key, $window ) {
		if ( wp_using_ext_object_cache() ) {
			wp_cache_add( $key, 0, self::CACHE_GROUP, $window );
			$count = wp_cache_incr( $key, 1, self::CACHE_GROUP );

			return false === $count ? 1 : (int) $count;
		}

		$transient = 'nivo_rl_' . $key;
		$count     = (int) get_transient( $transient ) + 1;
		set_transient( $transient, $count, $window );

		return $count;
	}
}
