<?php
/**
 * Free-tier identifier (GTIN/UPC/EAN/ISBN) search, attribute search and synonyms.
 */

class Test_Codes_Attributes_Synonyms extends WP_UnitTestCase {

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
		update_option( 'nivo_search_enable_synonyms', 1 );
		delete_option( NivoSearch\Synonyms::OPTION_CUSTOM );
		NivoSearch\Synonyms::reset();
	}

	public function tear_down() {
		NivoSearch\Synonyms::reset();
		parent::tear_down();
	}

	private function make_product( $title, $callback = null ) {
		$product = new WC_Product_Simple();
		$product->set_name( $title );
		$product->set_status( 'publish' );
		$product->set_regular_price( '10' );
		if ( $callback ) {
			$callback( $product );
		}
		$id = $product->save();

		( new NivoSearch\Product_Indexer() )->index_product( $id );

		return $id;
	}

	private function titles( $query, $fields = array( 'title', 'sku', 'attributes' ) ) {
		$algorithm = new NivoSearch\Search_Algorithm();
		$result    = $algorithm->search( $query, array( 'limit' => 20, 'search_fields' => $fields ) );

		return wp_list_pluck( $result['products'], 'post_title' );
	}

	public function test_gtin_is_searchable_in_full_and_by_prefix_and_keeps_leading_zeros() {
		$id = $this->make_product( 'Plain Notebook' );
		update_post_meta( $id, '_global_unique_id', '0123456789012' );
		( new NivoSearch\Product_Indexer() )->index_product( $id );

		$this->assertContains( 'Plain Notebook', $this->titles( '0123456789012' ) );
		$this->assertContains( 'Plain Notebook', $this->titles( '012345' ) );
	}

	public function test_other_code_meta_keys_are_indexed() {
		$id = $this->make_product( 'Barcode Lamp' );
		update_post_meta( $id, '_ean', '5901234123457' );
		( new NivoSearch\Product_Indexer() )->index_product( $id );

		$this->assertContains( 'Barcode Lamp', $this->titles( '5901234123457' ) );
	}

	public function test_codes_follow_the_sku_option() {
		$id = $this->make_product( 'Hidden Code Mug' );
		update_post_meta( $id, '_gtin', '9780306406157' );
		( new NivoSearch\Product_Indexer() )->index_product( $id );

		$this->assertSame( array(), $this->titles( '9780306406157', array( 'title' ) ) );
	}

	public function test_custom_attribute_values_are_searchable() {
		$this->make_product(
			'Plain Tee',
			static function ( $product ) {
				$attribute = new WC_Product_Attribute();
				$attribute->set_name( 'Colour' );
				$attribute->set_options( array( 'Burgundy', 'Navy' ) );
				$attribute->set_visible( true );
				$attribute->set_variation( false );
				$product->set_attributes( array( $attribute ) );
			}
		);

		$this->assertContains( 'Plain Tee', $this->titles( 'burgundy' ) );
		$this->assertSame( array(), $this->titles( 'burgundy', array( 'title', 'sku' ) ) );
	}

	public function test_built_in_synonyms_expand_a_search() {
		$this->make_product( 'Corner Sofa' );

		$this->assertContains( 'Corner Sofa', $this->titles( 'couch' ) );
	}

	public function test_synonyms_can_be_switched_off() {
		$this->make_product( 'Corner Sofa' );
		update_option( 'nivo_search_enable_synonyms', 0 );
		NivoSearch\Synonyms::reset();

		$this->assertSame( array(), $this->titles( 'couch' ) );
	}

	public function test_custom_synonym_groups_work_both_ways() {
		$this->make_product( 'Zorplamp Deluxe' );
		NivoSearch\Synonyms::save_custom_groups( array( array( 'zorplamp', 'quuxlight' ) ) );

		$this->assertContains( 'Zorplamp Deluxe', $this->titles( 'quuxlight' ) );
		$this->assertContains( 'Zorplamp Deluxe', $this->titles( 'zorplamp' ) );
	}

	public function test_custom_groups_are_capped_on_the_free_tier() {
		$lines = array();
		for ( $i = 1; $i <= 13; $i++ ) {
			$lines[] = "word{$i}a, word{$i}b";
		}
		$lines[] = 'lonely';
		$lines[] = '';

		$parsed = NivoSearch\Synonyms::parse_text( implode( "\n", $lines ) );

		$this->assertCount( 10, $parsed['groups'] );
		$this->assertSame( 3, $parsed['dropped'] );
	}

	public function test_synonym_cap_can_be_raised_by_a_filter() {
		add_filter(
			'nivo_search_max_custom_synonyms',
			static function () {
				return 50;
			}
		);

		$text = '';
		for ( $i = 1; $i <= 20; $i++ ) {
			$text .= "pa{$i}, pb{$i}\n";
		}

		$this->assertCount( 20, NivoSearch\Synonyms::parse_text( $text )['groups'] );
		remove_all_filters( 'nivo_search_max_custom_synonyms' );
	}

	public function test_multi_word_synonyms_are_ignored() {
		NivoSearch\Synonyms::save_custom_groups( array( array( 'plinthword', 'two word phrase' ) ) );

		// The phrase is not a single word, so the group adds nothing.
		$this->assertSame( array(), NivoSearch\Synonyms::expand( 'plinthword' ) );
		$this->assertSame( array(), NivoSearch\Synonyms::expand( 'phrase' ) );

		foreach ( NivoSearch\Synonyms::expand( 'phone' ) as $alternate ) {
			$this->assertStringNotContainsString( ' ', $alternate );
		}
	}
}
