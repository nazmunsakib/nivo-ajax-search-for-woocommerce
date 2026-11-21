<?php
/**
 * Merchandising rules for specific search queries.
 *
 * A rule matches a query (exactly, as a prefix, or as a substring) and can:
 *   - pin products to the top, in the order given;
 *   - bury products at the bottom of the results;
 *   - redirect a submitted search to a page (same-site URLs by default).
 *
 * @package NivoSearch
 * @since   2.0.8
 */

namespace NivoSearch;

defined( 'ABSPATH' ) || exit;

class Merchandising {

	const OPTION    = 'nivo_search_merch_rules';
	const MAX_RULES = 200;

	/**
	 * Front-end hooks.
	 *
	 * @return void
	 */
	public static function init() {
		// Priority 1 on `wp`: before the results-page redirect to the shop (priority 10).
		add_action( 'wp', array( __CLASS__, 'maybe_redirect' ), 1 );
	}

	/**
	 * Normalise a query for matching: lowercase, trimmed, single spaces.
	 *
	 * @param string $query Query.
	 * @return string
	 */
	public static function normalize( $query ) {
		return strtolower( trim( preg_replace( '/\s+/u', ' ', (string) $query ) ) );
	}

	/**
	 * All rules.
	 *
	 * @return array[]
	 */
	public static function get_rules() {
		$rules = get_option( self::OPTION, array() );

		return is_array( $rules ) ? array_values( array_filter( $rules, 'is_array' ) ) : array();
	}

	/**
	 * Validate and normalise one rule. Returns null when it has no query or no action.
	 *
	 * @param array $input Raw rule.
	 * @return array|null
	 */
	public static function sanitize_rule( $input ) {
		$query = self::normalize( isset( $input['query'] ) ? sanitize_text_field( (string) $input['query'] ) : '' );
		if ( '' === $query || strlen( $query ) > 200 ) {
			return null;
		}

		$match = isset( $input['match'] ) && in_array( $input['match'], array( 'exact', 'starts', 'contains' ), true ) ? $input['match'] : 'exact';

		$pin      = self::sanitize_ids( isset( $input['pin'] ) ? $input['pin'] : array() );
		$bury     = array_values( array_diff( self::sanitize_ids( isset( $input['bury'] ) ? $input['bury'] : array() ), $pin ) );
		$redirect = '';

		if ( ! empty( $input['redirect'] ) ) {
			$url = esc_url_raw( trim( (string) $input['redirect'] ), array( 'http', 'https' ) );
			// Same-site URLs (or hosts allowed via `allowed_redirect_hosts`) only.
			$redirect = $url ? wp_validate_redirect( $url, '' ) : '';
		}

		if ( empty( $pin ) && empty( $bury ) && '' === $redirect ) {
			return null;
		}

		return array(
			'id'       => isset( $input['id'] ) && '' !== $input['id'] ? sanitize_key( $input['id'] ) : strtolower( wp_generate_password( 8, false, false ) ), // Lowercase: ids pass through sanitize_key().
			'query'    => $query,
			'match'    => $match,
			'pin'      => $pin,
			'bury'     => $bury,
			'redirect' => $redirect,
			'enabled'  => ! isset( $input['enabled'] ) || ! empty( $input['enabled'] ),
		);
	}

	/**
	 * Accept an array or a comma/space separated string of IDs.
	 *
	 * @param array|string $value IDs.
	 * @return int[]
	 */
	private static function sanitize_ids( $value ) {
		if ( is_string( $value ) ) {
			$value = preg_split( '/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY );
		}

		return array_values( array_unique( array_filter( array_map( 'absint', (array) $value ) ) ) );
	}

	/**
	 * Add a rule (or replace the one with the same id). Returns false when invalid or at the limit.
	 *
	 * @param array $input Raw rule.
	 * @return array|false The stored rule.
	 */
	public static function save_rule( $input ) {
		$rule = self::sanitize_rule( $input );
		if ( null === $rule ) {
			return false;
		}

		$rules = self::get_rules();
		$found = false;

		foreach ( $rules as $i => $existing ) {
			if ( isset( $existing['id'] ) && $existing['id'] === $rule['id'] ) {
				$rules[ $i ] = $rule;
				$found       = true;
			}
		}

		if ( ! $found ) {
			if ( count( $rules ) >= self::MAX_RULES ) {
				return false;
			}
			$rules[] = $rule;
		}

		update_option( self::OPTION, $rules, false );
		Nivo_Ajax_Search::invalidate_search_cache();

		return $rule;
	}

	/**
	 * Delete a rule by id.
	 *
	 * @param string $id Rule id.
	 * @return void
	 */
	public static function delete_rule( $id ) {
		$id    = sanitize_key( $id );
		$rules = array_values(
			array_filter(
				self::get_rules(),
				static function ( $rule ) use ( $id ) {
					return ! isset( $rule['id'] ) || $rule['id'] !== $id;
				}
			)
		);

		update_option( self::OPTION, $rules, false );
		Nivo_Ajax_Search::invalidate_search_cache();
	}

	/**
	 * First enabled rule matching the query.
	 *
	 * @param string $query Search query as typed.
	 * @return array|null
	 */
	public static function find_rule( $query ) {
		$q = self::normalize( $query );
		if ( '' === $q ) {
			return null;
		}

		foreach ( self::get_rules() as $rule ) {
			if ( empty( $rule['enabled'] ) || empty( $rule['query'] ) ) {
				continue;
			}

			$hit = false;
			switch ( isset( $rule['match'] ) ? $rule['match'] : 'exact' ) {
				case 'starts':
					$hit = 0 === strpos( $q, $rule['query'] );
					break;
				case 'contains':
					$hit = false !== strpos( $q, $rule['query'] );
					break;
				default:
					$hit = $q === $rule['query'];
			}

			if ( $hit ) {
				return $rule;
			}
		}

		return null;
	}

	/**
	 * Reorder results: pinned first (in the configured order), buried last.
	 *
	 * @param \WP_Post[] $products Ranked products.
	 * @param array      $rule     Matching rule.
	 * @param \WP_Post[] $pinned   Pinned products already loaded (visibility rules applied), keyed or not.
	 * @param int        $limit    Maximum results.
	 * @return \WP_Post[]
	 */
	public static function apply( $products, $rule, $pinned, $limit ) {
		$by_id = array();
		foreach ( $products as $product ) {
			$by_id[ (int) $product->ID ] = $product;
		}

		$pinned_by_id = array();
		foreach ( $pinned as $product ) {
			$pinned_by_id[ (int) $product->ID ] = $product;
		}

		$head = array();
		foreach ( (array) $rule['pin'] as $id ) {
			$id = (int) $id;
			if ( isset( $pinned_by_id[ $id ] ) ) {
				$head[ $id ] = $pinned_by_id[ $id ];
			} elseif ( isset( $by_id[ $id ] ) ) {
				$head[ $id ] = $by_id[ $id ];
			}
		}

		$bury = array_flip( array_map( 'intval', (array) $rule['bury'] ) );
		$mid  = array();
		$tail = array();

		foreach ( $by_id as $id => $product ) {
			if ( isset( $head[ $id ] ) ) {
				continue;
			}
			if ( isset( $bury[ $id ] ) ) {
				$tail[] = $product;
			} else {
				$mid[] = $product;
			}
		}

		$merged = array_merge( array_values( $head ), $mid, $tail );

		return $limit > 0 ? array_slice( $merged, 0, $limit ) : $merged;
	}

	/**
	 * Redirect a submitted search to the rule's URL.
	 *
	 * Same-site URLs only unless the host is allowed with the `allowed_redirect_hosts` filter.
	 *
	 * @return void
	 */
	public static function maybe_redirect() {
		if ( ! is_search() || is_admin() ) {
			return;
		}

		$rule = self::find_rule( get_search_query( false ) );
		if ( ! $rule || empty( $rule['redirect'] ) ) {
			return;
		}

		wp_safe_redirect( $rule['redirect'], 302 );
		exit;
	}
}
