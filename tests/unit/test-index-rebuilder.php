<?php
/**
 * Background index rebuild: batching, completion, and automatic start rules.
 *
 * start() truncates the index table (a DDL statement that commits the test
 * transaction), so it is only exercised before any products are created.
 */

class Test_Index_Rebuilder extends WP_UnitTestCase {

	public static function wpSetUpBeforeClass() {
		NivoSearch\Product_Indexer::maybe_create_table();
	}

	private function make_product( $title ) {
		$product = new WC_Product_Simple();
		$product->set_name( $title );
		$product->set_status( 'publish' );
		$product->set_regular_price( '10' );

		return $product->save();
	}

	public function test_start_clears_index_version_and_marks_job_running() {
		update_option( 'nivo_search_index_version', NivoSearch\Product_Indexer::INDEX_VERSION );

		$state = NivoSearch\Index_Rebuilder::start( false );

		$this->assertSame( 'running', $state['status'] );
		$this->assertFalse( get_option( 'nivo_search_index_version' ) );

		// An empty catalog completes in one run.
		$state = NivoSearch\Index_Rebuilder::run_batch( false, 0 );
		$this->assertSame( 'done', $state['status'] );
		$this->assertSame( NivoSearch\Product_Indexer::INDEX_VERSION, (int) get_option( 'nivo_search_index_version' ) );
	}

	public function test_run_batch_indexes_products_and_switches_the_index_on() {
		$this->make_product( 'Rebuild Alpha' );
		$this->make_product( 'Rebuild Beta' );
		$this->make_product( 'Rebuild Gamma' );

		delete_option( 'nivo_search_index_version' );
		update_option(
			NivoSearch\Index_Rebuilder::OPTION,
			array(
				'status'    => 'running',
				'last_id'   => 0,
				'processed' => 0,
				'total'     => 3,
			)
		);

		$state = NivoSearch\Index_Rebuilder::run_batch( false, 0 );

		$this->assertSame( 'done', $state['status'] );
		$this->assertGreaterThanOrEqual( 3, $state['processed'] );
		$this->assertSame( NivoSearch\Product_Indexer::INDEX_VERSION, (int) get_option( 'nivo_search_index_version' ) );

		$stats = NivoSearch\Product_Indexer::get_stats();
		$this->assertGreaterThanOrEqual( 3, $stats['indexed'] );
	}

	public function test_run_batch_does_nothing_when_no_job_is_running() {
		update_option( NivoSearch\Index_Rebuilder::OPTION, array( 'status' => 'idle' ) );
		update_option( 'nivo_search_index_version', 0 );

		$state = NivoSearch\Index_Rebuilder::run_batch( false, 0 );

		$this->assertSame( 'idle', $state['status'] );
		$this->assertSame( 0, (int) get_option( 'nivo_search_index_version' ) );
	}

	public function test_autostart_is_skipped_when_the_index_is_current() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		update_option( 'nivo_search_index_version', NivoSearch\Product_Indexer::INDEX_VERSION );
		delete_option( NivoSearch\Index_Rebuilder::OPTION );

		NivoSearch\Index_Rebuilder::maybe_autostart();

		$this->assertSame( 'idle', NivoSearch\Index_Rebuilder::get_state()['status'] );
	}

	public function test_autostart_is_skipped_for_non_admins() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'subscriber' ) ) );
		delete_option( 'nivo_search_index_version' );
		delete_option( NivoSearch\Index_Rebuilder::OPTION );

		NivoSearch\Index_Rebuilder::maybe_autostart();

		$this->assertSame( 'idle', NivoSearch\Index_Rebuilder::get_state()['status'] );
	}

	public function test_autostart_can_be_disabled() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		delete_option( 'nivo_search_index_version' );
		delete_option( NivoSearch\Index_Rebuilder::OPTION );
		add_filter( 'nivo_search_auto_reindex', '__return_false' );

		NivoSearch\Index_Rebuilder::maybe_autostart();

		remove_filter( 'nivo_search_auto_reindex', '__return_false' );
		$this->assertSame( 'idle', NivoSearch\Index_Rebuilder::get_state()['status'] );
	}
}
