<?php
/**
 * Search must never return hidden, shop-only or password-protected products.
 */

class Test_Search_Visibility extends WP_UnitTestCase {

	/**
	 * Create a published simple product.
	 *
	 * @param string $title      Product title.
	 * @param string $visibility WooCommerce catalog visibility.
	 * @param string $password   Optional post password.
	 * @return int Product ID.
	 */
	private function make_product( $title, $visibility = 'visible', $password = '' ) {
		$product = new WC_Product_Simple();
		$product->set_name( $title );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( $visibility );
		$product->set_regular_price( '10' );
		$id = $product->save();

		if ( '' !== $password ) {
			wp_update_post(
				array(
					'ID'            => $id,
					'post_password' => $password,
				)
			);
		}

		return $id;
	}

	/**
	 * Run a search and return the matching product titles.
	 *
	 * @param string $query Search term.
	 * @return string[]
	 */
	private function search_titles( $query ) {
		$algorithm = new NivoSearch\Search_Algorithm();
		$result    = $algorithm->search( $query, array( 'limit' => 20 ) );

		return array_map(
			static function ( $post ) {
				return $post->post_title;
			},
			$result['products']
		);
	}

	public function test_hidden_shop_only_and_password_products_are_excluded() {
		$this->make_product( 'Quokkaxyz Visible', 'visible' );
		$this->make_product( 'Quokkaxyz Hidden', 'hidden' );
		$this->make_product( 'Quokkaxyz ShopOnly', 'catalog' );
		$this->make_product( 'Quokkaxyz Locked', 'visible', 'secret' );

		$titles = $this->search_titles( 'quokkaxyz' );

		$this->assertContains( 'Quokkaxyz Visible', $titles );
		$this->assertNotContains( 'Quokkaxyz Hidden', $titles );
		$this->assertNotContains( 'Quokkaxyz ShopOnly', $titles );
		$this->assertNotContains( 'Quokkaxyz Locked', $titles );
	}

	public function test_search_only_products_are_included() {
		$this->make_product( 'Quokkaxyz SearchOnly', 'search' );

		$this->assertContains( 'Quokkaxyz SearchOnly', $this->search_titles( 'quokkaxyz' ) );
	}
}
