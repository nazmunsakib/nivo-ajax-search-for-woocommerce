<?php
/**
 * REST API: search and presets.
 */

class Test_REST_API extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );

		update_option( 'nivo_search_enable_fuzzy_search', 0 );
		update_option( 'nivo_search_enable_typo_tolerance', 0 );
	}

	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;
		remove_all_filters( 'nivo_search_rate_limit' );
		parent::tear_down();
	}

	private function make_product( $title ) {
		$product = new WC_Product_Simple();
		$product->set_name( $title );
		$product->set_status( 'publish' );
		$product->set_regular_price( '10' );

		return $product->save();
	}

	public function test_search_returns_products() {
		$this->make_product( 'Restquokka Lamp' );

		$request  = new WP_REST_Request( 'GET', '/nivo-search/v1/search' );
		$request->set_param( 'q', 'restquokka' );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'Restquokka Lamp', $data['products'][0]['title'] );
	}

	public function test_search_requires_a_query() {
		$response = rest_do_request( new WP_REST_Request( 'GET', '/nivo-search/v1/search' ) );

		$this->assertSame( 400, $response->get_status() );
	}

	public function test_search_is_rate_limited() {
		add_filter(
			'nivo_search_rate_limit',
			static function () {
				return array( 1, 60 );
			}
		);
		$client = 'rest-test-' . wp_generate_uuid4();
		add_filter(
			'nivo_search_rate_limit_ip',
			static function () use ( $client ) {
				return $client;
			}
		);

		$request = new WP_REST_Request( 'GET', '/nivo-search/v1/search' );
		$request->set_param( 'q', 'anything' );

		$this->assertNotSame( 429, rest_do_request( $request )->get_status() );
		$this->assertSame( 429, rest_do_request( $request )->get_status() );
	}

	public function test_preset_list_requires_admin() {
		wp_set_current_user( 0 );
		$this->assertSame( 401, rest_do_request( new WP_REST_Request( 'GET', '/nivo-search/v1/presets' ) )->get_status() );

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertSame( 200, rest_do_request( new WP_REST_Request( 'GET', '/nivo-search/v1/presets' ) )->get_status() );
	}

	public function test_preset_detail_returns_sanitised_settings() {
		$preset_id = $this->factory()->post->create(
			array(
				'post_type'   => 'nivo_search_preset',
				'post_status' => 'publish',
				'post_title'  => 'REST Preset',
			)
		);

		$response = rest_do_request( new WP_REST_Request( 'GET', '/nivo-search/v1/presets/' . $preset_id ) );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'REST Preset', $data['title'] );
		$this->assertIsArray( $data['settings'] );
	}

	public function test_unknown_preset_is_404() {
		$this->assertSame( 404, rest_do_request( new WP_REST_Request( 'GET', '/nivo-search/v1/presets/999999' ) )->get_status() );
	}
}
