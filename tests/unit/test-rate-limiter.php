<?php
/**
 * Rate limiter allows requests up to the limit, then blocks.
 */

class Test_Rate_Limiter extends WP_UnitTestCase {

	public function tear_down() {
		remove_all_filters( 'nivo_search_rate_limit' );
		parent::tear_down();
	}

	public function test_blocks_after_limit() {
		add_filter(
			'nivo_search_rate_limit',
			static function () {
				return array( 3, 60 );
			}
		);

		$client = 'test-client-' . wp_generate_uuid4();

		$this->assertTrue( NivoSearch\Rate_Limiter::allow( $client ) );
		$this->assertTrue( NivoSearch\Rate_Limiter::allow( $client ) );
		$this->assertTrue( NivoSearch\Rate_Limiter::allow( $client ) );
		$this->assertFalse( NivoSearch\Rate_Limiter::allow( $client ) );
	}

	public function test_separate_clients_have_separate_limits() {
		add_filter(
			'nivo_search_rate_limit',
			static function () {
				return array( 1, 60 );
			}
		);

		$this->assertTrue( NivoSearch\Rate_Limiter::allow( 'client-a-' . wp_generate_uuid4() ) );
		$this->assertTrue( NivoSearch\Rate_Limiter::allow( 'client-b-' . wp_generate_uuid4() ) );
	}

	public function test_zero_limit_disables_throttling() {
		add_filter(
			'nivo_search_rate_limit',
			static function () {
				return array( 0, 60 );
			}
		);

		$client = 'test-client-' . wp_generate_uuid4();

		for ( $i = 0; $i < 20; $i++ ) {
			$this->assertTrue( NivoSearch\Rate_Limiter::allow( $client ) );
		}
	}
}
