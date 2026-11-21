<?php
/**
 * Ranking settings: text-match weights and relevance signals.
 *
 * Boosts are maximum points added to a product's relevance score, scaled
 * 0 to 1 per product within the candidate pool:
 *   popularity  = total_sales / highest total_sales in the pool
 *   rating      = average rating / 5
 *   recency     = newer products score higher, linearly over one year
 *   in stock    = flat bonus for products that are in stock
 *
 * All boosts default to 0, so existing sites rank exactly as before until an
 * administrator turns them on.
 *
 * @package NivoSearch
 * @since   2.0.8
 */

namespace NivoSearch;

defined( 'ABSPATH' ) || exit;

class Ranking_Settings {

	const OPTION = 'nivo_search_ranking';

	/**
	 * Default values and their allowed range [ min, max ].
	 *
	 * @return array<string,array{0:int,1:int,2:int}> key => [ default, min, max ].
	 */
	public static function definitions() {
		return array(
			'weight_title_exact'    => array( 100, 0, 500 ),
			'weight_title_starts'   => array( 50, 0, 500 ),
			'weight_title_contains' => array( 20, 0, 500 ),
			'weight_sku'            => array( 80, 0, 500 ),
			'boost_popularity'      => array( 0, 0, 200 ),
			'boost_rating'          => array( 0, 0, 200 ),
			'boost_recency'         => array( 0, 0, 200 ),
			'boost_instock'         => array( 0, 0, 200 ),
		);
	}

	/**
	 * Current settings, validated.
	 *
	 * @return array<string,int>
	 */
	public static function get() {
		$stored = get_option( self::OPTION, array() );

		return self::sanitize( is_array( $stored ) ? $stored : array() );
	}

	/**
	 * Clamp every value to its allowed range, filling defaults.
	 *
	 * @param array $input Raw values.
	 * @return array<string,int>
	 */
	public static function sanitize( $input ) {
		$clean = array();

		foreach ( self::definitions() as $key => $def ) {
			$value         = isset( $input[ $key ] ) && is_numeric( $input[ $key ] ) ? (int) $input[ $key ] : $def[0];
			$clean[ $key ] = max( $def[1], min( $def[2], $value ) );
		}

		return $clean;
	}

	/**
	 * Whether any relevance signal is switched on.
	 *
	 * @param array $settings Settings from get().
	 * @return bool
	 */
	public static function has_signals( $settings ) {
		return $settings['boost_popularity'] > 0 || $settings['boost_rating'] > 0 || $settings['boost_recency'] > 0 || $settings['boost_instock'] > 0;
	}
}
