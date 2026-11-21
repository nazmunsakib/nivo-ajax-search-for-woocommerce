<?php
/**
 * Admin tabs: Ranking (weights and relevance signals) and Merchandising (query rules).
 *
 * @package NivoSearch
 * @since   2.0.8
 */

namespace NivoSearch;

defined( 'ABSPATH' ) || exit;

class Ranking_Admin {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'nivo_render_ranking_tab', array( $this, 'render_ranking_tab' ) );
		add_action( 'nivo_render_merch_tab', array( $this, 'render_merch_tab' ) );
		add_action( 'nivo_render_synonyms_tab', array( $this, 'render_synonyms_tab' ) );
		add_action( 'admin_init', array( $this, 'handle_post' ) );
	}

	/**
	 * Tab URL.
	 *
	 * @param string $tab  Tab slug.
	 * @param array  $args Extra query args.
	 * @return string
	 */
	private function tab_url( $tab, $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => 'nivo-search', 'tab' => $tab ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Handle form posts from both tabs.
	 *
	 * @return void
	 */
	public function handle_post() {
		if ( ! isset( $_POST['nivo_ranking_action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'nivo_ranking_admin', '_nivo_ranking_nonce' );

		$action = sanitize_key( wp_unslash( $_POST['nivo_ranking_action'] ) );

		if ( 'save_ranking' === $action ) {
			$raw = isset( $_POST['nivo_ranking'] ) && is_array( $_POST['nivo_ranking'] ) ? wp_unslash( $_POST['nivo_ranking'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- every value is validated by Ranking_Settings::sanitize().
			update_option( Ranking_Settings::OPTION, Ranking_Settings::sanitize( $raw ), false );
			Nivo_Ajax_Search::invalidate_search_cache();

			wp_safe_redirect( $this->tab_url( 'ranking', array( 'saved' => 1 ) ) );
			exit;
		}

		if ( 'save_synonyms' === $action ) {
			update_option( Synonyms::OPTION_ENABLED, isset( $_POST['nivo_synonyms_enabled'] ) ? 1 : 0 );

			$parsed = Synonyms::parse_text( isset( $_POST['nivo_synonyms_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['nivo_synonyms_text'] ) ) : '' );
			Synonyms::save_custom_groups( $parsed['groups'] );

			wp_safe_redirect( $this->tab_url( 'synonyms', array( 'saved' => 1, 'dropped' => $parsed['dropped'] ) ) );
			exit;
		}

		if ( 'save_rule' === $action ) {
			$raw = array(
				'id'       => isset( $_POST['rule_id'] ) ? sanitize_key( wp_unslash( $_POST['rule_id'] ) ) : '',
				'query'    => isset( $_POST['rule_query'] ) ? sanitize_text_field( wp_unslash( $_POST['rule_query'] ) ) : '',
				'match'    => isset( $_POST['rule_match'] ) ? sanitize_key( wp_unslash( $_POST['rule_match'] ) ) : 'exact',
				'pin'      => isset( $_POST['rule_pin'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['rule_pin'] ) ) : array(), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- cast with absint.
				'bury'     => isset( $_POST['rule_bury'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['rule_bury'] ) ) : array(), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- cast with absint.
				'redirect' => isset( $_POST['rule_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['rule_redirect'] ) ) : '',
				'enabled'  => isset( $_POST['rule_enabled'] ) ? 1 : 0,
			);

			$result = Merchandising::save_rule( $raw );

			wp_safe_redirect( $this->tab_url( 'merchandising', array( 'rule' => $result ? 'saved' : 'invalid' ) ) );
			exit;
		}

		if ( 'delete_rule' === $action && isset( $_POST['rule_id'] ) ) {
			Merchandising::delete_rule( sanitize_key( wp_unslash( $_POST['rule_id'] ) ) );

			wp_safe_redirect( $this->tab_url( 'merchandising', array( 'rule' => 'deleted' ) ) );
			exit;
		}
	}

	/**
	 * Ranking tab.
	 *
	 * @return void
	 */
	public function render_ranking_tab() {
		$settings = Ranking_Settings::get();
		$fields   = array(
			'text'    => array(
				'title'  => __( 'Text match weights', 'nivo-ajax-search-for-woocommerce' ),
				'help'   => __( 'Points a product earns for how well its title or SKU matches what the shopper typed. Higher means ranked higher.', 'nivo-ajax-search-for-woocommerce' ),
				'fields' => array(
					'weight_title_exact'    => __( 'Title is exactly the search', 'nivo-ajax-search-for-woocommerce' ),
					'weight_title_starts'   => __( 'Title starts with the search', 'nivo-ajax-search-for-woocommerce' ),
					'weight_title_contains' => __( 'Title contains the search', 'nivo-ajax-search-for-woocommerce' ),
					'weight_sku'            => __( 'SKU matches the search', 'nivo-ajax-search-for-woocommerce' ),
				),
			),
			'signals' => array(
				'title'  => __( 'Relevance signals', 'nivo-ajax-search-for-woocommerce' ),
				'help'   => __( 'Extra points added on top of the text match, so popular, well-rated, new or in-stock products rise. Each value is the most points that signal can add; 0 turns it off.', 'nivo-ajax-search-for-woocommerce' ),
				'fields' => array(
					'boost_popularity' => __( 'Popularity (best sellers)', 'nivo-ajax-search-for-woocommerce' ),
					'boost_rating'     => __( 'Customer rating', 'nivo-ajax-search-for-woocommerce' ),
					'boost_recency'    => __( 'Newness (over the last year)', 'nivo-ajax-search-for-woocommerce' ),
					'boost_instock'    => __( 'In stock', 'nivo-ajax-search-for-woocommerce' ),
				),
			),
		);
		$defs     = Ranking_Settings::definitions();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$saved = isset( $_GET['saved'] );
		?>
		<div class="nivo-card nivo-accuracy-card">
			<h2><?php esc_html_e( 'Ranking', 'nivo-ajax-search-for-woocommerce' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Control how results are ordered. Signals work on the best text matches, so a product still has to match what the shopper typed.', 'nivo-ajax-search-for-woocommerce' ); ?></p>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success inline"><p><?php esc_html_e( 'Ranking settings saved.', 'nivo-ajax-search-for-woocommerce' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( $this->tab_url( 'ranking' ) ); ?>">
				<?php wp_nonce_field( 'nivo_ranking_admin', '_nivo_ranking_nonce' ); ?>
				<input type="hidden" name="nivo_ranking_action" value="save_ranking">

				<?php foreach ( $fields as $group ) : ?>
					<h3><?php echo esc_html( $group['title'] ); ?></h3>
					<p class="description"><?php echo esc_html( $group['help'] ); ?></p>
					<table class="form-table" role="presentation">
						<?php foreach ( $group['fields'] as $key => $label ) : ?>
							<tr>
								<th scope="row"><label for="nivo-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
								<td>
									<input type="number" id="nivo-<?php echo esc_attr( $key ); ?>" name="nivo_ranking[<?php echo esc_attr( $key ); ?>]"
										value="<?php echo esc_attr( $settings[ $key ] ); ?>" min="<?php echo esc_attr( $defs[ $key ][1] ); ?>" max="<?php echo esc_attr( $defs[ $key ][2] ); ?>" class="small-text">
									<span class="description">
										<?php
										/* translators: %d: default value */
										echo esc_html( sprintf( __( 'Default: %d', 'nivo-ajax-search-for-woocommerce' ), $defs[ $key ][0] ) );
										?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				<?php endforeach; ?>

				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save ranking', 'nivo-ajax-search-for-woocommerce' ); ?></button></p>
			</form>
		</div>
		<?php
	}

	/**
	 * Synonyms tab.
	 *
	 * @return void
	 */
	public function render_synonyms_tab() {
		$groups = Synonyms::get_custom_groups();
		$max    = Synonyms::max_custom_groups();
		$text   = implode( "\n", array_map( static function ( $group ) {
			return implode( ', ', $group );
		}, $groups ) );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$saved   = isset( $_GET['saved'] );
		$dropped = isset( $_GET['dropped'] ) ? absint( $_GET['dropped'] ) : 0;
		// phpcs:enable
		?>
		<div class="nivo-card nivo-accuracy-card">
			<h2><?php esc_html_e( 'Synonyms', 'nivo-ajax-search-for-woocommerce' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Teach the search that different words mean the same thing, so "sofa" also finds "couch". NivoSearch already knows a long list of common ones; add your own for your products.', 'nivo-ajax-search-for-woocommerce' ); ?></p>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success inline"><p><?php esc_html_e( 'Synonyms saved.', 'nivo-ajax-search-for-woocommerce' ); ?></p></div>
			<?php endif; ?>
			<?php if ( $dropped > 0 ) : ?>
				<div class="notice notice-warning inline"><p>
					<?php
					/* translators: 1: groups not saved, 2: allowed groups */
					echo esc_html( sprintf( _n( '%1$d group was not saved: the free version allows %2$d custom groups.', '%1$d groups were not saved: the free version allows %2$d custom groups.', $dropped, 'nivo-ajax-search-for-woocommerce' ), $dropped, $max ) );
					?>
				</p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( $this->tab_url( 'synonyms' ) ); ?>">
				<?php wp_nonce_field( 'nivo_ranking_admin', '_nivo_ranking_nonce' ); ?>
				<input type="hidden" name="nivo_ranking_action" value="save_synonyms">

				<p>
					<label>
						<input type="checkbox" name="nivo_synonyms_enabled" value="1" <?php checked( Synonyms::is_enabled() ); ?>>
						<?php esc_html_e( 'Use synonyms in search', 'nivo-ajax-search-for-woocommerce' ); ?>
					</label>
				</p>

				<h3><?php esc_html_e( 'Your own synonyms', 'nivo-ajax-search-for-woocommerce' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'One group per line, words separated by commas. Single words work best.', 'nivo-ajax-search-for-woocommerce' ); ?>
					<?php
					/* translators: 1: groups used, 2: groups allowed */
					echo esc_html( sprintf( __( 'Using %1$d of %2$d groups.', 'nivo-ajax-search-for-woocommerce' ), count( $groups ), $max ) );
					?>
				</p>
				<textarea name="nivo_synonyms_text" rows="8" class="large-text code" placeholder="sofa, couch, settee&#10;trainers, sneakers, running shoes"><?php echo esc_textarea( $text ); ?></textarea>

				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save synonyms', 'nivo-ajax-search-for-woocommerce' ); ?></button></p>
			</form>
		</div>
		<?php
	}

	/**
	 * Names of products for a list of IDs.
	 *
	 * @param int[] $ids Product IDs.
	 * @return string
	 */
	private function product_names( $ids ) {
		$names = array();
		foreach ( (array) $ids as $id ) {
			$title   = get_the_title( (int) $id );
			$names[] = '' !== $title ? $title : '#' . (int) $id . ' ' . __( '(missing)', 'nivo-ajax-search-for-woocommerce' );
		}

		return implode( ', ', $names );
	}

	/**
	 * Product search select (WooCommerce's enhanced select).
	 *
	 * @param string $name     Field name.
	 * @param int[]  $selected Selected product IDs.
	 * @param string $label    Accessible label.
	 * @return void
	 */
	private function product_select( $name, $selected, $label ) {
		?>
		<select class="wc-product-search" multiple="multiple" style="width: 100%;" name="<?php echo esc_attr( $name ); ?>[]"
			data-placeholder="<?php esc_attr_e( 'Search for a product…', 'nivo-ajax-search-for-woocommerce' ); ?>"
			data-action="woocommerce_json_search_products_and_variations" data-exclude_type="variation"
			aria-label="<?php echo esc_attr( $label ); ?>">
			<?php foreach ( (array) $selected as $id ) : ?>
				<?php $product = wc_get_product( (int) $id ); ?>
				<?php if ( $product ) : ?>
					<option value="<?php echo esc_attr( $id ); ?>" selected="selected"><?php echo esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ); ?></option>
				<?php endif; ?>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Merchandising tab.
	 *
	 * @return void
	 */
	public function render_merch_tab() {
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'woocommerce_admin_styles' );

		$rules = Merchandising::get_rules();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$notice  = isset( $_GET['rule'] ) ? sanitize_key( wp_unslash( $_GET['rule'] ) ) : '';
		$edit_id = isset( $_GET['edit'] ) ? sanitize_key( wp_unslash( $_GET['edit'] ) ) : '';
		// phpcs:enable

		$editing = array(
			'id'       => '',
			'query'    => '',
			'match'    => 'exact',
			'pin'      => array(),
			'bury'     => array(),
			'redirect' => '',
			'enabled'  => true,
		);
		foreach ( $rules as $rule ) {
			if ( '' !== $edit_id && isset( $rule['id'] ) && $rule['id'] === $edit_id ) {
				$editing = array_merge( $editing, $rule );
			}
		}

		$match_labels = array(
			'exact'    => __( 'Exactly', 'nivo-ajax-search-for-woocommerce' ),
			'starts'   => __( 'Starts with', 'nivo-ajax-search-for-woocommerce' ),
			'contains' => __( 'Contains', 'nivo-ajax-search-for-woocommerce' ),
		);
		?>
		<div class="nivo-card nivo-accuracy-card">
			<h2><?php esc_html_e( 'Merchandising rules', 'nivo-ajax-search-for-woocommerce' ); ?></h2>
			<p class="description"><?php esc_html_e( 'For specific searches, pin products to the top, push products to the bottom, or send shoppers to a page. The first matching rule wins. Hidden and out-of-stock rules of your search settings still apply to pinned products.', 'nivo-ajax-search-for-woocommerce' ); ?></p>

			<?php if ( 'saved' === $notice ) : ?>
				<div class="notice notice-success inline"><p><?php esc_html_e( 'Rule saved.', 'nivo-ajax-search-for-woocommerce' ); ?></p></div>
			<?php elseif ( 'deleted' === $notice ) : ?>
				<div class="notice notice-success inline"><p><?php esc_html_e( 'Rule deleted.', 'nivo-ajax-search-for-woocommerce' ); ?></p></div>
			<?php elseif ( 'invalid' === $notice ) : ?>
				<div class="notice notice-error inline"><p><?php esc_html_e( 'The rule was not saved. It needs a search phrase and at least one action: pin, bury or redirect (same-site URL).', 'nivo-ajax-search-for-woocommerce' ); ?></p></div>
			<?php endif; ?>

			<?php if ( empty( $rules ) ) : ?>
				<p><em><?php esc_html_e( 'No rules yet. Add one below.', 'nivo-ajax-search-for-woocommerce' ); ?></em></p>
			<?php else : ?>
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'When the search…', 'nivo-ajax-search-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Pinned to top', 'nivo-ajax-search-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Pushed to bottom', 'nivo-ajax-search-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Redirect', 'nivo-ajax-search-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Status', 'nivo-ajax-search-for-woocommerce' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rules as $rule ) : ?>
							<tr>
								<td><strong><?php echo esc_html( isset( $match_labels[ $rule['match'] ] ) ? $match_labels[ $rule['match'] ] : '' ); ?></strong> “<?php echo esc_html( $rule['query'] ); ?>”</td>
								<td><?php echo esc_html( $this->product_names( $rule['pin'] ) ); ?></td>
								<td><?php echo esc_html( $this->product_names( $rule['bury'] ) ); ?></td>
								<td><?php echo ! empty( $rule['redirect'] ) ? esc_html( $rule['redirect'] ) : '—'; ?></td>
								<td><?php echo ! empty( $rule['enabled'] ) ? esc_html__( 'On', 'nivo-ajax-search-for-woocommerce' ) : esc_html__( 'Off', 'nivo-ajax-search-for-woocommerce' ); ?></td>
								<td>
									<a href="<?php echo esc_url( $this->tab_url( 'merchandising', array( 'edit' => $rule['id'] ) ) ); ?>"><?php esc_html_e( 'Edit', 'nivo-ajax-search-for-woocommerce' ); ?></a>
									<form method="post" action="<?php echo esc_url( $this->tab_url( 'merchandising' ) ); ?>" style="display:inline;">
										<?php wp_nonce_field( 'nivo_ranking_admin', '_nivo_ranking_nonce' ); ?>
										<input type="hidden" name="nivo_ranking_action" value="delete_rule">
										<input type="hidden" name="rule_id" value="<?php echo esc_attr( $rule['id'] ); ?>">
										<button type="submit" class="button-link button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Delete this rule?', 'nivo-ajax-search-for-woocommerce' ) ); ?>');"><?php esc_html_e( 'Delete', 'nivo-ajax-search-for-woocommerce' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h3 style="margin-top:24px;">
				<?php echo '' !== $editing['id'] ? esc_html__( 'Edit rule', 'nivo-ajax-search-for-woocommerce' ) : esc_html__( 'Add a rule', 'nivo-ajax-search-for-woocommerce' ); ?>
			</h3>
			<form method="post" action="<?php echo esc_url( $this->tab_url( 'merchandising' ) ); ?>">
				<?php wp_nonce_field( 'nivo_ranking_admin', '_nivo_ranking_nonce' ); ?>
				<input type="hidden" name="nivo_ranking_action" value="save_rule">
				<input type="hidden" name="rule_id" value="<?php echo esc_attr( $editing['id'] ); ?>">

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="nivo-rule-query"><?php esc_html_e( 'When the search…', 'nivo-ajax-search-for-woocommerce' ); ?></label></th>
						<td>
							<select name="rule_match" aria-label="<?php esc_attr_e( 'Match type', 'nivo-ajax-search-for-woocommerce' ); ?>">
								<?php foreach ( $match_labels as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $editing['match'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<input type="text" id="nivo-rule-query" name="rule_query" value="<?php echo esc_attr( $editing['query'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. gift', 'nivo-ajax-search-for-woocommerce' ); ?>" required>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Pin to top', 'nivo-ajax-search-for-woocommerce' ); ?></th>
						<td>
							<?php $this->product_select( 'rule_pin', $editing['pin'], __( 'Products to pin to the top', 'nivo-ajax-search-for-woocommerce' ) ); ?>
							<p class="description"><?php esc_html_e( 'Shown first, in the order you pick them, even if they do not match the words.', 'nivo-ajax-search-for-woocommerce' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Push to bottom', 'nivo-ajax-search-for-woocommerce' ); ?></th>
						<td><?php $this->product_select( 'rule_bury', $editing['bury'], __( 'Products to push to the bottom', 'nivo-ajax-search-for-woocommerce' ) ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="nivo-rule-redirect"><?php esc_html_e( 'Redirect to', 'nivo-ajax-search-for-woocommerce' ); ?></label></th>
						<td>
							<input type="url" id="nivo-rule-redirect" name="rule_redirect" value="<?php echo esc_attr( $editing['redirect'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( home_url( '/returns-policy/' ) ); ?>">
							<p class="description"><?php esc_html_e( 'Optional. When a shopper submits this search (presses Enter), they go to this page instead. Same-site URLs only.', 'nivo-ajax-search-for-woocommerce' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Enabled', 'nivo-ajax-search-for-woocommerce' ); ?></th>
						<td><label><input type="checkbox" name="rule_enabled" value="1" <?php checked( ! empty( $editing['enabled'] ) ); ?>> <?php esc_html_e( 'Rule is active', 'nivo-ajax-search-for-woocommerce' ); ?></label></td>
					</tr>
				</table>

				<p>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save rule', 'nivo-ajax-search-for-woocommerce' ); ?></button>
					<?php if ( '' !== $editing['id'] ) : ?>
						<a class="button" href="<?php echo esc_url( $this->tab_url( 'merchandising' ) ); ?>"><?php esc_html_e( 'Cancel', 'nivo-ajax-search-for-woocommerce' ); ?></a>
					<?php endif; ?>
				</p>
			</form>
		</div>
		<?php
	}
}
