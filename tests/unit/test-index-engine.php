<?php
/**
 * Index-first search engine: prefix and multi-word matching, SKU and description
 * lookup, visibility rules, and fallback to the WP_Query path when the index is not ready.
 */

class Test_Index_Engine extends WP_UnitTestCase {

	public static function wpSetUpBeforeClass() {
		NivoSearch\Product_Indexer::maybe_create_table();
	}

	public function set_up() {
		parent::set_up();
		update_option( 'nivo_search_index_version', NivoSearch\Product_Indexer::INDEX_VERSION );
		update_option( 'nivo_search_index_count', 1 );
		update_option( 'nivo_search_index_has_content', 1 );
		update_option( 'nivo_search_enable_fuzzy_search', 0 );
		update_option( 'nivo_search_enable_typo_tolerance', 0 );
	}

	private function make_product( $title, $extra = array() ) {
		$product = new WC_Product_Simple();
		$product->set_name( $title );
		$product->set_status( 'publish' );
		$product->set_regular_price( '10' );
		if ( isset( $extra['sku'] ) ) {
			$product->set_sku( $extra['sku'] );
		}
		if ( isset( $extra['description'] ) ) {
			$product->set_description( $extra['description'] );
		}
		if ( isset( $extra['visibility'] ) ) {
			$product->set_catalog_visibility( $extra['visibility'] );
		}
		$id = $product->save();

		( new NivoSearch\Product_Indexer() )->index_product( $id );

		return $id;
	}

	private function titles( $query ) {
		$algorithm = new NivoSearch\Search_Algorithm();
		$result    = $algorithm->search( $query, array( 'limit' => 20 ) );

		return array_map(
			static function ( $post ) {
				return $post->post_title;
			},
			$result['products']
		);
	}

	public function test_index_path_is_active_only_after_rebuild() {
		$args = array(
			'post_types'    => array( 'product' ),
			'search_fields' => array( 'title', 'sku' ),
		);

		$this->assertTrue( NivoSearch\Index_Query::can_handle( $args ) );

		delete_option( 'nivo_search_index_version' );
		$this->assertFalse( NivoSearch\Index_Query::can_handle( $args ) );
	}

	public function test_prefix_and_multiword_matching() {
		$this->make_product( 'Premium Red Hoodie' );
		$this->make_product( 'Blue Hoodie' );

		$this->assertSame( array( 'Premium Red Hoodie' ), $this->titles( 'prem hood' ) );
		$this->assertCount( 2, $this->titles( 'hoodie' ) );
	}

	public function test_sku_lookup_by_full_sku_and_prefix() {
		$this->make_product( 'Plain Shirt', array( 'sku' => 'ABC-123-XL' ) );

		$this->assertContains( 'Plain Shirt', $this->titles( 'ABC-123-XL' ) );
		$this->assertContains( 'Plain Shirt', $this->titles( 'abc-123' ) );
	}

	public function test_description_words_are_searchable() {
		$this->make_product( 'Plain Mug', array( 'description' => 'A ceramic mug with zebrastripe pattern.' ) );

		$this->assertContains( 'Plain Mug', $this->titles( 'zebrastripe' ) );
	}

	public function test_hidden_products_are_excluded_on_the_index_path() {
		$this->make_product( 'Quokkaxyz Visible' );
		$this->make_product( 'Quokkaxyz Hidden', array( 'visibility' => 'hidden' ) );

		$titles = $this->titles( 'quokkaxyz' );

		$this->assertContains( 'Quokkaxyz Visible', $titles );
		$this->assertNotContains( 'Quokkaxyz Hidden', $titles );
	}

	public function test_excluded_products_are_hidden_on_the_index_path() {
		$keep    = $this->make_product( 'Exclusionz Keep' );
		$exclude = $this->make_product( 'Exclusionz Drop' );

		$algorithm = new NivoSearch\Search_Algorithm();
		$result    = $algorithm->search( 'exclusionz', array( 'limit' => 20, 'excluded_products' => array( $exclude ) ) );
		$titles    = wp_list_pluck( $result['products'], 'post_title' );

		$this->assertContains( 'Exclusionz Keep', $titles );
		$this->assertNotContains( 'Exclusionz Drop', $titles );
		$this->assertGreaterThan( 0, $keep );
	}

	public function test_mid_word_matches_only_work_on_the_legacy_path() {
		$this->make_product( 'Premium Hoodie' );

		// Index path matches word prefixes, not the middle of words.
		$this->assertSame( array(), $this->titles( 'oodie' ) );

		// Without a rebuilt index the WP_Query substring path is used.
		delete_option( 'nivo_search_index_version' );
		$this->assertContains( 'Premium Hoodie', $this->titles( 'oodie' ) );
	}
}
