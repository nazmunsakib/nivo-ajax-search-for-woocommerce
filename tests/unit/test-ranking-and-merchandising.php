<?php
/**
 * Configurable weights, relevance signals and merchandising rules.
 */

class Test_Ranking_And_Merchandising extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		update_option( 'nivo_search_enable_fuzzy_search', 0 );
		update_option( 'nivo_search_enable_typo_tolerance', 0 );
		delete_option( NivoSearch\Merchandising::OPTION );
	}

	private function make_product( $title, $meta = array() ) {
		$product = new WC_Product_Simple();
		$product->set_name( $title );
		$product->set_status( 'publish' );
		$product->set_regular_price( '10' );
		$id = $product->save();

		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}

		return $id;
	}

	private function search( $query, $args = array() ) {
		$algorithm = new NivoSearch\Search_Algorithm();

		return $algorithm->search( $query, array_merge( array( 'limit' => 20 ), $args ) );
	}

	private function titles( $result ) {
		return wp_list_pluck( $result['products'], 'post_title' );
	}

	public function test_ranking_settings_are_clamped_and_default_to_off() {
		$settings = NivoSearch\Ranking_Settings::get();
		$this->assertSame( 0, $settings['boost_popularity'] );
		$this->assertSame( 100, $settings['weight_title_exact'] );

		$clean = NivoSearch\Ranking_Settings::sanitize( array( 'boost_popularity' => 9999, 'weight_sku' => -5, 'boost_rating' => 'abc' ) );
		$this->assertSame( 200, $clean['boost_popularity'] );
		$this->assertSame( 0, $clean['weight_sku'] );
		$this->assertSame( 0, $clean['boost_rating'] );
	}

	public function test_title_weights_are_configurable() {
		$this->make_product( 'Hoodie Zed' );   // Title starts with the query.
		$this->make_product( 'Zed Hoodie' );   // Title contains the query.

		$this->assertSame( 'Hoodie Zed', $this->titles( $this->search( 'hoodie' ) )[0] );

		$swapped = $this->search( 'hoodie', array( 'weight_title_starts' => 0, 'weight_title_contains' => 90 ) );
		$this->assertSame( 'Zed Hoodie', $this->titles( $swapped )[0] );
	}

	public function test_popularity_boost_reorders_results() {
		$this->make_product( 'Hoodie A', array( 'total_sales' => 0 ) );
		$this->make_product( 'Hoodie Alphabet', array( 'total_sales' => 100 ) );

		// Shorter title wins on text relevance alone.
		$this->assertSame( 'Hoodie A', $this->titles( $this->search( 'hoodie' ) )[0] );

		$boosted = $this->search( 'hoodie', array( 'boost_popularity' => 50 ) );
		$this->assertSame( 'Hoodie Alphabet', $this->titles( $boosted )[0] );
	}

	public function test_rating_and_stock_boosts() {
		$this->make_product( 'Mug A', array( '_wc_average_rating' => '1.0', '_stock_status' => 'instock' ) );
		$this->make_product( 'Mug Alphabet', array( '_wc_average_rating' => '5.0', '_stock_status' => 'instock' ) );

		$boosted = $this->search( 'mug', array( 'boost_rating' => 40 ) );
		$this->assertSame( 'Mug Alphabet', $this->titles( $boosted )[0] );
	}

	public function test_rule_matching_modes() {
		NivoSearch\Merchandising::save_rule( array( 'query' => 'sale', 'match' => 'exact', 'pin' => '1' ) );
		$this->assertNotNull( NivoSearch\Merchandising::find_rule( '  SALE ' ) );
		$this->assertNull( NivoSearch\Merchandising::find_rule( 'sale shoes' ) );

		NivoSearch\Merchandising::save_rule( array( 'query' => 'gift', 'match' => 'starts', 'pin' => '1' ) );
		$this->assertNotNull( NivoSearch\Merchandising::find_rule( 'gift cards' ) );
		$this->assertNull( NivoSearch\Merchandising::find_rule( 'a gift' ) );

		NivoSearch\Merchandising::save_rule( array( 'query' => 'blue', 'match' => 'contains', 'pin' => '1' ) );
		$this->assertNotNull( NivoSearch\Merchandising::find_rule( 'navy blue shirt' ) );
	}

	public function test_invalid_and_disabled_rules() {
		$this->assertFalse( NivoSearch\Merchandising::save_rule( array( 'query' => '', 'pin' => '1' ) ) );
		$this->assertFalse( NivoSearch\Merchandising::save_rule( array( 'query' => 'nothing to do' ) ) );

		$rule = NivoSearch\Merchandising::save_rule( array( 'query' => 'off', 'pin' => '5', 'enabled' => 0 ) );
		$this->assertFalse( $rule['enabled'] );
		$this->assertNull( NivoSearch\Merchandising::find_rule( 'off' ) );

		$rule = NivoSearch\Merchandising::save_rule( array( 'query' => 'bad url', 'redirect' => 'javascript:alert(1)', 'pin' => '2' ) );
		$this->assertSame( '', $rule['redirect'] );

		// Off-site redirects are dropped; with no other action the rule is rejected.
		$this->assertFalse( NivoSearch\Merchandising::save_rule( array( 'query' => 'external', 'redirect' => 'https://offsite.invalid/page' ) ) );
		$rule = NivoSearch\Merchandising::save_rule( array( 'query' => 'external two', 'redirect' => 'https://offsite.invalid/page', 'pin' => '2' ) );
		$this->assertSame( '', $rule['redirect'] );
	}

	public function test_pinned_products_come_first_and_buried_last() {
		$a = $this->make_product( 'Merchzz Alpha' );
		$b = $this->make_product( 'Merchzz Beta' );
		$c = $this->make_product( 'Merchzz Gamma Edition' );
		$unrelated = $this->make_product( 'Completely Different Thing' );

		NivoSearch\Merchandising::save_rule(
			array(
				'query' => 'merchzz',
				'pin'   => array( $unrelated, $c ),
				'bury'  => array( $a ),
			)
		);

		$titles = $this->titles( $this->search( 'merchzz' ) );

		$this->assertSame( 'Completely Different Thing', $titles[0] );
		$this->assertSame( 'Merchzz Gamma Edition', $titles[1] );
		$this->assertSame( 'Merchzz Alpha', end( $titles ) );
		$this->assertContains( 'Merchzz Beta', $titles );
	}

	public function test_pinned_hidden_product_is_not_shown() {
		$visible = $this->make_product( 'Pinvis Item' );
		$hidden  = $this->make_product( 'Pinhidden Item' );
		$product = wc_get_product( $hidden );
		$product->set_catalog_visibility( 'hidden' );
		$product->save();

		NivoSearch\Merchandising::save_rule( array( 'query' => 'pinvis', 'pin' => array( $hidden, $visible ) ) );

		$titles = $this->titles( $this->search( 'pinvis' ) );
		$this->assertSame( array( 'Pinvis Item' ), $titles );
	}

	public function test_redirect_is_reported_in_results() {
		NivoSearch\Merchandising::save_rule( array( 'query' => 'returns', 'redirect' => home_url( '/returns-policy/' ) ) );

		$result = $this->search( 'returns' );

		$this->assertSame( home_url( '/returns-policy/' ), $result['redirect_url'] );
	}

	public function test_delete_rule() {
		$rule = NivoSearch\Merchandising::save_rule( array( 'query' => 'temp', 'pin' => '3' ) );
		$this->assertCount( 1, NivoSearch\Merchandising::get_rules() );

		NivoSearch\Merchandising::delete_rule( $rule['id'] );
		$this->assertCount( 0, NivoSearch\Merchandising::get_rules() );
	}
}
