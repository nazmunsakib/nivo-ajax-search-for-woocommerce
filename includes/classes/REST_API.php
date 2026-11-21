<?php
/**
 * REST API: product search and preset settings.
 *
 * Namespace: nivo-search/v1
 *   GET /search?q=...&preset_id=...   Public, rate limited. Same payload as the AJAX search.
 *   GET /presets                       Administrators: list presets.
 *   GET /presets/<id>                  Public: sanitised display settings of one preset.
 *
 * @package NivoSearch
 * @since   2.0.8
 */

namespace NivoSearch;

defined( 'ABSPATH' ) || exit;

class REST_API {

	const NAMESPACE_NAME = 'nivo-search/v1';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE_NAME,
			'/search',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q'         => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function ( $value ) {
							return is_string( $value ) && '' !== trim( $value ) && strlen( $value ) <= 200;
						},
					),
					'preset_id' => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_NAME,
			'/presets',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_presets' ),
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			self::NAMESPACE_NAME,
			'/presets/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_preset' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * GET /search.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function search( $request ) {
		if ( ! Rate_Limiter::allow() ) {
			$error = new \WP_Error(
				'nivo_search_rate_limited',
				__( 'Too many searches. Please wait a moment and try again.', 'nivo-ajax-search-for-woocommerce' ),
				array( 'status' => 429 )
			);

			return $error;
		}

		$data = Nivo_Ajax_Search::get_instance()->build_search_response( (string) $request['q'], (int) $request['preset_id'] );

		if ( is_wp_error( $data ) ) {
			$data->add_data( array( 'status' => 400 ) );

			return $data;
		}

		// Internal cache bookkeeping is not part of the public payload.
		unset( $data['_corrected_from'], $data['_corrected_to'] );

		$response = rest_ensure_response( $data );
		$response->header( 'Cache-Control', 'public, max-age=60' );

		return $response;
	}

	/**
	 * GET /presets.
	 *
	 * @return \WP_REST_Response
	 */
	public function list_presets() {
		$posts = get_posts(
			array(
				'post_type'      => 'nivo_search_preset',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$items = array();
		foreach ( $posts as $post ) {
			$items[] = array(
				'id'    => (int) $post->ID,
				'title' => get_the_title( $post ),
			);
		}

		return rest_ensure_response( $items );
	}

	/**
	 * GET /presets/<id>.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_preset( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );

		if ( ! $post || 'nivo_search_preset' !== $post->post_type || 'publish' !== $post->post_status ) {
			return new \WP_Error( 'nivo_search_preset_not_found', __( 'Preset not found.', 'nivo-ajax-search-for-woocommerce' ), array( 'status' => 404 ) );
		}

		$settings = array_merge(
			(array) get_post_meta( $id, '_nivo_search_generale', true ),
			(array) get_post_meta( $id, '_nivo_search_query', true ),
			(array) get_post_meta( $id, '_nivo_search_display', true ),
			(array) get_post_meta( $id, '_nivo_search_style', true )
		);
		$settings = wp_parse_args( $settings, Helper::get_default_settings() );

		return rest_ensure_response(
			array(
				'id'       => $id,
				'title'    => get_the_title( $post ),
				'settings' => Nivo_Ajax_Search::get_instance()->sanitize_settings_for_response( $settings ),
			)
		);
	}
}
