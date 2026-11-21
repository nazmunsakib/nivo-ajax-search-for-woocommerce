<?php
/**
 * Synonyms: built-in dictionary plus up to 10 custom groups on the free tier.
 *
 * "sofa, couch, settee" means a search for any of them also finds the others.
 * Only single-word synonyms are expanded (the search is word-based); multi-word
 * entries in the built-in dictionary are skipped.
 *
 * @package NivoSearch
 * @since   2.0.8
 */

namespace NivoSearch;

defined( 'ABSPATH' ) || exit;

class Synonyms {

	const OPTION_CUSTOM  = 'nivo_search_custom_synonyms';
	const OPTION_ENABLED = 'nivo_search_enable_synonyms';
	const FREE_LIMIT     = 10;
	const MAX_ALTERNATES = 6;

	/**
	 * Lookup map word => alternative words, built once per request.
	 *
	 * @var array<string,string[]>|null
	 */
	private static $map = null;

	/**
	 * Whether synonym expansion is on.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return (bool) apply_filters( 'nivo_search_enable_synonyms', (bool) get_option( self::OPTION_ENABLED, 1 ) );
	}

	/**
	 * Maximum custom groups (free tier cap; a Pro add-on can raise it).
	 *
	 * @return int
	 */
	public static function max_custom_groups() {
		return max( 0, (int) apply_filters( 'nivo_search_max_custom_synonyms', self::FREE_LIMIT ) );
	}

	/**
	 * Reset the per-request lookup (after saving, and in tests).
	 *
	 * @return void
	 */
	public static function reset() {
		self::$map = null;
	}

	/**
	 * Normalise one term to a single lowercase, accent-free word, or '' when it is a phrase.
	 *
	 * @param string $term Term.
	 * @return string
	 */
	private static function word( $term ) {
		$tokens = Product_Indexer::tokenize( (string) $term );

		return 1 === count( $tokens ) ? $tokens[0] : '';
	}

	/**
	 * Custom groups as arrays of terms.
	 *
	 * @return array[]
	 */
	public static function get_custom_groups() {
		$groups = get_option( self::OPTION_CUSTOM, array() );

		return is_array( $groups ) ? array_values( array_filter( $groups, 'is_array' ) ) : array();
	}

	/**
	 * Parse the admin textarea: one group per line, terms separated by commas.
	 *
	 * @param string $text Raw text.
	 * @return array{groups:array[],dropped:int} Parsed groups (within the cap) and how many were dropped.
	 */
	public static function parse_text( $text ) {
		$groups = array();

		foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
			$terms = array_values(
				array_unique(
					array_filter(
						array_map(
							static function ( $term ) {
								return sanitize_text_field( trim( $term ) );
							},
							explode( ',', $line )
						),
						'strlen'
					)
				)
			);

			if ( count( $terms ) >= 2 ) {
				$groups[] = array_slice( $terms, 0, 20 );
			}
		}

		$max     = self::max_custom_groups();
		$dropped = max( 0, count( $groups ) - $max );

		return array(
			'groups'  => array_slice( $groups, 0, $max ),
			'dropped' => $dropped,
		);
	}

	/**
	 * Store custom groups.
	 *
	 * @param array[] $groups Groups of terms.
	 * @return void
	 */
	public static function save_custom_groups( $groups ) {
		update_option( self::OPTION_CUSTOM, array_values( $groups ), false );
		self::reset();
		Nivo_Ajax_Search::invalidate_search_cache();
	}

	/**
	 * Build the word => alternatives lookup from the dictionary and custom groups.
	 *
	 * @return array<string,string[]>
	 */
	private static function build_map() {
		$map = array();

		$add = static function ( $a, $b ) use ( &$map ) {
			if ( '' === $a || '' === $b || $a === $b ) {
				return;
			}
			if ( ! isset( $map[ $a ] ) ) {
				$map[ $a ] = array();
			}
			$map[ $a ][ $b ] = true;
		};

		$built_in_file = dirname( __DIR__ ) . '/data/synonyms.php';
		$built_in      = file_exists( $built_in_file ) ? include $built_in_file : array();
		$built_in      = apply_filters( 'nivo_search_synonyms', is_array( $built_in ) ? $built_in : array() );

		foreach ( (array) $built_in as $key => $alts ) {
			$k = self::word( $key );
			foreach ( (array) $alts as $alt ) {
				$add( $k, self::word( $alt ) );
			}
		}

		foreach ( self::get_custom_groups() as $group ) {
			$words = array_filter( array_map( array( __CLASS__, 'word' ), $group ) );
			foreach ( $words as $a ) {
				foreach ( $words as $b ) {
					$add( $a, $b );
				}
			}
		}

		foreach ( $map as $word => $alts ) {
			$map[ $word ] = array_keys( $alts );
		}

		return $map;
	}

	/**
	 * Alternative single words for a query word (not including the word itself).
	 *
	 * @param string $word Query word (already tokenised).
	 * @return string[]
	 */
	public static function expand( $word ) {
		if ( ! self::is_enabled() ) {
			return array();
		}

		if ( null === self::$map ) {
			self::$map = self::build_map();
		}

		$word = strtolower( (string) $word );

		return isset( self::$map[ $word ] ) ? array_slice( self::$map[ $word ], 0, self::MAX_ALTERNATES ) : array();
	}

	/**
	 * Short fingerprint of the active synonyms, used in the search cache key.
	 *
	 * @return string
	 */
	public static function fingerprint() {
		return md5( (string) (int) self::is_enabled() . wp_json_encode( self::get_custom_groups() ) );
	}
}
