<?php
/**
 * Multi-word queries match every word in any order, and ranking considers all matches.
 */

class Test_Search_Multiword extends WP_UnitTestCase {

	private function make_product( $title ) {
		$product = new WC_Product_Simple();
		$product->set_name( $title );
		$product->set_status( 'publish' );
		$product->set_regular_price( '10' );
		return $product->save();
	}

	private function search_titles( $query, $limit = 20 ) {
		$algorithm = new NivoSearch\Search_Algorithm();
		$result    = $algorithm->search( $query, array( 'limit' => $limit ) );

		return array_map(
			static function ( $post ) {
				return $post->post_title;
			},
			$result['products']
		);
	}

	public function test_words_match_in_any_order_and_position() {
		$this->make_product( 'Premium Red Hoodie' );
		$this->make_product( 'Hoodie Red Basic' );
		$this->make_product( 'Blue Hoodie' );

		$titles = $this->search_titles( 'premium hoodie' );
		$this->assertSame( array( 'Premium Red Hoodie' ), $titles );

		$titles = $this->search_titles( 'hoodie red' );
		$this->assertContains( 'Premium Red Hoodie', $titles );
		$this->assertContains( 'Hoodie Red Basic', $titles );
		$this->assertNotContains( 'Blue Hoodie', $titles );
	}

	public function test_every_word_must_match() {
		// Disable the approximate fallbacks so only the strict primary search is tested.
		update_option( 'nivo_search_enable_fuzzy_search', 0 );
		update_option( 'nivo_search_enable_typo_tolerance', 0 );

		$this->make_product( 'Quokka Lamp' );

		$this->assertCount( 0, $this->search_titles( 'quokka sofa' ) );
	}

	public function test_exact_phrase_ranks_above_scattered_words() {
		$this->make_product( 'Red Big Hoodie Zed' );
		$this->make_product( 'Red Hoodie' );

		$titles = $this->search_titles( 'red hoodie' );
		$this->assertSame( 'Red Hoodie', $titles[0] );
	}

	public function test_best_match_is_found_even_when_many_products_match() {
		for ( $i = 1; $i <= 80; $i++ ) {
			$this->make_product( 'Zorpquux Filler Item ' . $i );
		}
		$this->make_product( 'Zorpquux' );

		$titles = $this->search_titles( 'zorpquux', 5 );

		$this->assertCount( 5, $titles );
		$this->assertSame( 'Zorpquux', $titles[0] );
	}

	public function test_tokenize_query() {
		$this->assertSame( array( 'red', 'hoodie' ), NivoSearch\Search_Algorithm::tokenize_query( '  red   hoodie ' ) );
		$this->assertSame( array(), NivoSearch\Search_Algorithm::tokenize_query( '   ' ) );
		$this->assertCount( 8, NivoSearch\Search_Algorithm::tokenize_query( 'a b c d e f g h i j' ) );
	}
}
