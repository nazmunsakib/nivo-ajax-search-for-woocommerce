<?php
/**
 * Search bar styles and result layouts: whitelisting, saving, and front-end output.
 */

class Test_Layouts extends WP_UnitTestCase {

	private function make_preset( $general = array() ) {
		$id = $this->factory()->post->create(
			array(
				'post_type'   => 'nivo_search_preset',
				'post_status' => 'publish',
				'post_title'  => 'Layout preset',
			)
		);
		update_post_meta( $id, '_nivo_search_generale', $general );

		return $id;
	}

	public function test_layout_values_are_whitelisted() {
		$this->assertSame( 'pill', NivoSearch\Helper::sanitize_bar_layout( 'pill' ) );
		$this->assertSame( 'pill', NivoSearch\Helper::sanitize_bar_layout( ' PILL ' ) );
		$this->assertSame( 'standard', NivoSearch\Helper::sanitize_bar_layout( 1 ) ); // Legacy numeric value.
		$this->assertSame( 'standard', NivoSearch\Helper::sanitize_bar_layout( 'evil"><script>' ) );
		$this->assertSame( 'standard', NivoSearch\Helper::sanitize_bar_layout( array( 'pill' ) ) );

		$this->assertSame( 'grid', NivoSearch\Helper::sanitize_results_layout( 'grid' ) );
		$this->assertSame( 'list', NivoSearch\Helper::sanitize_results_layout( 'carousel' ) );
		$this->assertSame( 'list', NivoSearch\Helper::sanitize_results_layout( null ) );
	}

	public function test_every_layout_has_a_label() {
		foreach ( array( 'standard', 'icon-left', 'pill', 'button', 'minimal' ) as $slug ) {
			$this->assertArrayHasKey( $slug, NivoSearch\Helper::get_bar_layouts() );
		}
		foreach ( array( 'list', 'grid', 'compact' ) as $slug ) {
			$this->assertArrayHasKey( $slug, NivoSearch\Helper::get_results_layouts() );
		}
	}

	public function test_shortcode_uses_the_layouts_saved_on_the_preset() {
		$id = $this->make_preset( array( 'search_bar_layout' => 'pill', 'results_layout' => 'grid' ) );

		$html = do_shortcode( '[nivo_search id="' . $id . '"]' );

		$this->assertStringContainsString( 'nivo-search-bar-pill', $html );
		$this->assertStringContainsString( 'nivo-results-layout-grid', $html );
	}

	public function test_shortcode_attributes_override_the_preset() {
		$id = $this->make_preset( array( 'search_bar_layout' => 'pill', 'results_layout' => 'grid' ) );

		$html = do_shortcode( '[nivo_search id="' . $id . '" search_bar_layout="minimal" results_layout="compact"]' );

		$this->assertStringContainsString( 'nivo-search-bar-minimal', $html );
		$this->assertStringContainsString( 'nivo-results-layout-compact', $html );
		$this->assertStringNotContainsString( 'nivo-search-bar-pill', $html );
	}

	public function test_a_malicious_layout_value_cannot_inject_markup() {
		$id   = $this->make_preset();
		$html = do_shortcode( '[nivo_search id="' . $id . '" search_bar_layout="x&quot; onmouseover=&quot;alert(1)" results_layout="y&quot;&gt;&lt;script&gt;"]' );

		$this->assertStringContainsString( 'nivo-search-bar-standard', $html );
		$this->assertStringContainsString( 'nivo-results-layout-list', $html );
		$this->assertStringNotContainsString( 'onmouseover', $html );
		$this->assertStringNotContainsString( '<script>', $html );
	}

	public function test_default_layouts_when_nothing_is_saved() {
		$html = do_shortcode( '[nivo_search id="' . $this->make_preset() . '"]' );

		$this->assertStringContainsString( 'nivo-search-bar-standard', $html );
		$this->assertStringContainsString( 'nivo-results-layout-list', $html );
	}

	public function test_saving_the_editor_stores_only_valid_layouts() {
		$id = $this->make_preset();
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );

		$_POST['nivo_preset_nonce'] = wp_create_nonce( 'nivo_preset_meta' );
		$_POST['nivo_settings']     = array(
			'search_bar_layout' => 'button',
			'results_layout'    => 'not-a-layout',
		);

		( new NivoSearch\Search_Preset_CPT() )->save_preset_meta( $id, get_post( $id ) );
		unset( $_POST['nivo_preset_nonce'], $_POST['nivo_settings'] );

		$general = get_post_meta( $id, '_nivo_search_generale', true );
		$this->assertSame( 'button', $general['search_bar_layout'] );
		$this->assertSame( 'list', $general['results_layout'] );
	}
}
