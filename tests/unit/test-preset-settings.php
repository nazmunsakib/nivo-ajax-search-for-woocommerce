<?php
/**
 * Preset settings: server-side limits, the 2.0.8 migration, fuzzy fallback scope, duplicate link.
 */

class Test_Preset_Settings extends WP_UnitTestCase {

	public static function wpSetUpBeforeClass() {
		NivoSearch\Product_Indexer::maybe_create_table();
	}

	private function make_preset() {
		return $this->factory()->post->create(
			array(
				'post_type'   => 'nivo_search_preset',
				'post_status' => 'publish',
				'post_title'  => 'Test preset',
			)
		);
	}

	/**
	 * Submit the editor form for a preset, as an administrator.
	 */
	private function submit_form( $preset_id, $fields ) {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );

		$_POST['nivo_preset_nonce'] = wp_create_nonce( 'nivo_preset_meta' );
		$_POST['nivo_settings']     = $fields;

		$cpt = new NivoSearch\Search_Preset_CPT();
		$cpt->save_preset_meta( $preset_id, get_post( $preset_id ) );

		unset( $_POST['nivo_preset_nonce'], $_POST['nivo_settings'] );
	}

	public function test_clamp_keeps_numbers_in_range() {
		$this->assertSame( 50, NivoSearch\Search_Preset_CPT::clamp( '5000', 1, 50, 10 ) );
		$this->assertSame( 1, NivoSearch\Search_Preset_CPT::clamp( '-4', 1, 50, 10 ) );
		$this->assertSame( 10, NivoSearch\Search_Preset_CPT::clamp( 'abc', 1, 50, 10 ) );
		$this->assertSame( 0, NivoSearch\Search_Preset_CPT::clamp( '0', 0, 2000, 300 ) );
		$this->assertSame( 25, NivoSearch\Search_Preset_CPT::clamp( '25', 1, 50, 10 ) );
	}

	public function test_saving_the_editor_clamps_out_of_range_values() {
		$id = $this->make_preset();

		$this->submit_form(
			$id,
			array(
				'limit'         => '9999',
				'min_chars'     => '0',
				'delay'         => '999999',
				'placeholder'   => '',
				'bar_width'     => '5',
				'bar_height'    => '5000',
				'results_width' => '99999',
			)
		);

		$general = get_post_meta( $id, '_nivo_search_generale', true );
		$style   = get_post_meta( $id, '_nivo_search_style', true );

		$this->assertSame( 50, $general['limit'] );
		$this->assertSame( 1, $general['min_chars'] );
		$this->assertSame( 2000, $general['delay'] );
		$this->assertNotSame( '', $general['placeholder'] );
		$this->assertSame( 150, $style['bar_width'] );
		$this->assertSame( 100, $style['bar_height'] );
		$this->assertSame( 1400, $style['results_width'] );
	}

	public function test_migration_enables_attributes_and_removes_dead_keys() {
		$id = $this->make_preset();
		update_post_meta(
			$id,
			'_nivo_search_query',
			array(
				'search_in_title'      => 1,
				'search_in_attributes' => 0,
				'search_in_gtin'       => 0,
				'enable_synonyms'      => 0,
			)
		);

		NivoSearch\Migrator::migrate_to_2_0_8();
		$query = get_post_meta( $id, '_nivo_search_query', true );

		$this->assertSame( 1, $query['search_in_attributes'] );
		$this->assertArrayNotHasKey( 'search_in_gtin', $query );
		$this->assertArrayNotHasKey( 'enable_synonyms', $query );
		$this->assertSame( 1, $query['search_in_title'] );

		// Running it again changes nothing.
		NivoSearch\Migrator::migrate_to_2_0_8();
		$this->assertSame( $query, get_post_meta( $id, '_nivo_search_query', true ) );
	}

	public function test_fuzzy_fallback_respects_the_search_scope() {
		update_option( 'nivo_search_index_version', NivoSearch\Product_Indexer::INDEX_VERSION );
		update_option( 'nivo_search_index_count', 1 );
		update_option( 'nivo_search_index_has_content', 1 );
		update_option( 'nivo_search_enable_fuzzy_search', 1 );
		update_option( 'nivo_search_enable_typo_tolerance', 0 );

		$product = new WC_Product_Simple();
		$product->set_name( 'Plain Mug' );
		$product->set_status( 'publish' );
		$product->set_regular_price( '10' );
		$product->set_description( 'A ceramic mug with a zebrastripe pattern.' );
		$id = $product->save();
		( new NivoSearch\Product_Indexer() )->index_product( $id );

		$algorithm = new NivoSearch\Search_Algorithm();

		// Misspelled description word, description search ON: the fuzzy fallback finds it.
		$on = $algorithm->search( 'zebrastripee', array( 'limit' => 10, 'search_fields' => array( 'title', 'content' ) ) );
		$this->assertContains( 'Plain Mug', wp_list_pluck( $on['products'], 'post_title' ) );

		// Same query, description search OFF: the fuzzy fallback must not bypass the setting.
		$off = $algorithm->search( 'zebrastripee', array( 'limit' => 10, 'search_fields' => array( 'title' ) ) );
		$this->assertNotContains( 'Plain Mug', wp_list_pluck( $off['products'], 'post_title' ) );
	}

	public function test_duplicate_link_is_only_added_for_presets() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		$cpt = new NivoSearch\Search_Preset_CPT();

		$preset = get_post( $this->make_preset() );
		$page   = get_post( $this->factory()->post->create( array( 'post_type' => 'page' ) ) );

		$this->assertArrayHasKey( 'nivo_duplicate', $cpt->add_duplicate_row_action( array(), $preset ) );
		$this->assertArrayNotHasKey( 'nivo_duplicate', $cpt->add_duplicate_row_action( array(), $page ) );
	}
}
