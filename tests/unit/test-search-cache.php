<?php
/**
 * Search cache invalidation.
 */

class Test_Search_Cache extends WP_UnitTestCase {

	public function tear_down() {
		remove_all_filters( 'nivo_search_cache_bump_interval' );
		delete_transient( 'nivo_search_cache_bump_lock' );
		parent::tear_down();
	}

	public function test_full_invalidation_always_bumps_the_version() {
		update_option( 'nivo_search_cache_ver', 5 );

		NivoSearch\Nivo_Ajax_Search::invalidate_search_cache();
		NivoSearch\Nivo_Ajax_Search::invalidate_search_cache();

		$this->assertSame( 7, (int) get_option( 'nivo_search_cache_ver' ) );
	}

	public function test_throttled_invalidation_bumps_once_per_interval() {
		update_option( 'nivo_search_cache_ver', 5 );
		delete_transient( 'nivo_search_cache_bump_lock' );

		NivoSearch\Nivo_Ajax_Search::invalidate_search_cache_throttled();
		NivoSearch\Nivo_Ajax_Search::invalidate_search_cache_throttled();
		NivoSearch\Nivo_Ajax_Search::invalidate_search_cache_throttled();

		$this->assertSame( 6, (int) get_option( 'nivo_search_cache_ver' ) );
	}

	public function test_throttle_can_be_disabled() {
		update_option( 'nivo_search_cache_ver', 5 );
		add_filter( 'nivo_search_cache_bump_interval', '__return_zero' );

		NivoSearch\Nivo_Ajax_Search::invalidate_search_cache_throttled();
		NivoSearch\Nivo_Ajax_Search::invalidate_search_cache_throttled();

		$this->assertSame( 7, (int) get_option( 'nivo_search_cache_ver' ) );
	}
}
