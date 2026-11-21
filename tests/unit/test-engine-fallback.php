<?php
/**
 * The core-search fallback used when the NivoSearch engine fails.
 */

class Test_Engine_Fallback extends WP_UnitTestCase {

	private function make_product( $title, $visibility = 'visible' ) {
		$product = new WC_Product_Simple();
		$product->set_name( $title );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( $visibility );
		$product->set_regular_price( '10' );

		return $product->save();
	}

	private function fallback( $query ) {
		$controller = NivoSearch\Nivo_Ajax_Search::get_instance();
		$method     = new ReflectionMethod( $controller, 'fallback_search' );
		$method->setAccessible( true );

		return $method->invoke( $controller, $query, array( 'limit' => 10 ) );
	}

	public function test_fallback_returns_matching_visible_products() {
		$this->make_product( 'Fallbackzed Lamp' );
		$this->make_product( 'Fallbackzed Hidden', 'hidden' );

		$result = $this->fallback( 'fallbackzed' );
		$titles = wp_list_pluck( $result['products'], 'post_title' );

		$this->assertTrue( $result['engine_fallback'] );
		$this->assertContains( 'Fallbackzed Lamp', $titles );
		$this->assertNotContains( 'Fallbackzed Hidden', $titles );
	}
}
