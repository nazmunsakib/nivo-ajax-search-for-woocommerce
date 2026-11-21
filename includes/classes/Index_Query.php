<?php
/**
 * Index-first product lookup.
 *
 * Finds candidate product IDs from the wp_nivo_search_index table using indexed
 * word-prefix lookups instead of scanning post titles, content and postmeta with
 * LIKE '%term%'. Every query word must match (any order). Candidates are scored
 * by field weight; Search_Algorithm then loads the products, applies visibility,
 * stock and exclusion rules, and ranks them.
 *
 * The engine only runs when the index has been rebuilt for the current index
 * version and covers the fields the preset searches. Otherwise Search_Algorithm
 * keeps using its WP_Query path, so nothing changes until the index is built.
 *
 * @package NivoSearch
 * @since   2.0.8
 */

namespace NivoSearch;

defined( 'ABSPATH' ) || exit;

class Index_Query {

	/**
	 * Maximum query words joined in one lookup.
	 */
	const MAX_TOKENS = 6;

	/**
	 * Map of search field name => index field label.
	 *
	 * @return array<string,string>
	 */
	private static function field_map() {
		return array(
			'title'   => 'title',
			'sku'     => 'sku',
			'content' => 'content',
			'excerpt' => 'content',
		);
	}

	/**
	 * Whether the index-first path may be used for this search.
	 *
	 * @param array $args Search arguments (search_fields, post_types).
	 * @return bool
	 */
	public static function can_handle( $args ) {
		if ( ! apply_filters( 'nivo_search_use_index_engine', (bool) get_option( 'nivo_search_use_index', 1 ), $args ) ) {
			return false;
		}

		if ( (int) get_option( 'nivo_search_index_version', 0 ) !== Product_Indexer::INDEX_VERSION ) {
			return false;
		}

		if ( (int) get_option( 'nivo_search_index_count', 0 ) < 1 ) {
			return false;
		}

		$post_types = isset( $args['post_types'] ) ? (array) $args['post_types'] : array( 'product' );
		if ( array( 'product' ) !== array_values( $post_types ) ) {
			return false;
		}

		$fields = isset( $args['search_fields'] ) ? (array) $args['search_fields'] : array();
		$map    = self::field_map();
		$has_content = (bool) get_option( 'nivo_search_index_has_content', 1 );

		foreach ( $fields as $field ) {
			if ( ! isset( $map[ $field ] ) ) {
				return false;
			}
			if ( 'content' === $map[ $field ] && ! $has_content ) {
				return false;
			}
		}

		return ! empty( $fields );
	}

	/**
	 * Find candidate product IDs for a query, best first.
	 *
	 * @param string   $query         Search query (diacritics already normalised or not; tokenised here).
	 * @param string[] $search_fields Fields to search (title, sku, content, excerpt).
	 * @param int      $pool          Maximum candidates to return.
	 * @return int[]
	 */
	public static function find_ids( $query, $search_fields, $pool ) {
		global $wpdb;

		$table  = Product_Indexer::get_table_name();
		$tokens = array_slice( array_values( array_unique( Product_Indexer::tokenize( $query ) ) ), 0, self::MAX_TOKENS );
		$pool   = max( 1, (int) $pool );

		$map    = self::field_map();
		$labels = array();
		foreach ( $search_fields as $field ) {
			if ( isset( $map[ $field ] ) ) {
				$labels[ $map[ $field ] ] = true;
			}
		}
		$labels = array_keys( $labels );

		if ( empty( $labels ) ) {
			return array();
		}

		$ids = array();

		// Whole-query SKU lookup ("TSHIRT-RED-XL"): the indexer stores the full SKU as one token.
		$full = strtolower( trim( (string) $query ) );
		if ( in_array( 'sku', $search_fields, true ) && strlen( $full ) >= 3 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sku_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT product_id FROM {$table} WHERE field = 'sku' AND token LIKE %s LIMIT %d",
					$wpdb->esc_like( $full ) . '%',
					$pool
				)
			);
			$ids = array_map( 'intval', $sku_ids );
		}

		if ( ! empty( $tokens ) ) {
			// Drive the lookup from the longest (usually rarest) word and check the
			// others with EXISTS probes on the product_id index. Joining one derived
			// table per word would materialise every row of a very common word
			// (e.g. "sku", which every product has) before intersecting.
			usort(
				$tokens,
				static function ( $a, $b ) {
					return strlen( $b ) - strlen( $a );
				}
			);

			$field_ph = implode( ',', array_fill( 0, count( $labels ), '%s' ) );
			$driver   = array_shift( $tokens );

			$where  = "t0.token LIKE %s AND t0.field IN ({$field_ph})";
			$params = array_merge( array( $wpdb->esc_like( $driver ) . '%' ), $labels );

			foreach ( $tokens as $i => $token ) {
				$n       = $i + 1;
				$where  .= " AND EXISTS (SELECT 1 FROM {$table} t{$n} WHERE t{$n}.product_id = t0.product_id AND t{$n}.token LIKE %s AND t{$n}.field IN ({$field_ph}))";
				$params  = array_merge( $params, array( $wpdb->esc_like( $token ) . '%' ), $labels );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared -- table name is internal; values are prepared.
			$rows = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT t0.product_id FROM {$table} t0 WHERE {$where} GROUP BY t0.product_id ORDER BY MAX(t0.weight * 2 + (t0.token = %s)) DESC, t0.product_id ASC LIMIT %d",
					array_merge( $params, array( $driver, $pool ) )
				)
			);

			$ids = array_merge( $ids, array_map( 'intval', $rows ) );
		}

		return array_slice( array_values( array_unique( $ids ) ), 0, $pool );
	}
}
