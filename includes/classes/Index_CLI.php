<?php
/**
 * WP-CLI commands for the NivoSearch product index.
 *
 * @package NivoSearch
 * @since   2.0.8
 */

namespace NivoSearch;

defined( 'ABSPATH' ) || exit;

/**
 * Manage the NivoSearch product index.
 */
class Index_CLI {

	/**
	 * Rebuild the product search index, showing progress.
	 *
	 * Runs inline (no time limit) instead of through the background scheduler.
	 *
	 * ## EXAMPLES
	 *
	 *     wp nivo-search index rebuild
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function rebuild( $args, $assoc_args ) {
		$state = Index_Rebuilder::start( false );
		$total = (int) $state['total'];

		$progress = \WP_CLI\Utils\make_progress_bar( 'Indexing products', max( 1, $total ) );
		$done     = 0;

		do {
			$state = Index_Rebuilder::run_batch( false, 0 );
			$now   = (int) $state['processed'];
			$progress->tick( max( 0, $now - $done ) );
			$done = $now;
		} while ( 'running' === $state['status'] );

		$progress->finish();

		if ( 'done' === $state['status'] ) {
			\WP_CLI::success( sprintf( 'Indexed %d products.', (int) $state['processed'] ) );
			return;
		}

		\WP_CLI::error( 'Index rebuild failed: ' . $state['error'] );
	}

	/**
	 * Show the state of the product search index.
	 *
	 * ## EXAMPLES
	 *
	 *     wp nivo-search index status
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function status( $args, $assoc_args ) {
		$state = Index_Rebuilder::get_state();
		$stats = Product_Indexer::get_stats();

		\WP_CLI::log( sprintf( 'Job: %s (%d of %d products)', $state['status'], $state['processed'], $state['total'] ) );
		\WP_CLI::log( sprintf( 'Index: %d of %d products indexed', $stats['indexed'], $stats['total'] ) );
		\WP_CLI::log( sprintf( 'Index-first search: %s', (int) get_option( 'nivo_search_index_version', 0 ) === Product_Indexer::INDEX_VERSION ? 'active' : 'inactive (standard search in use)' ) );

		if ( '' !== $state['error'] ) {
			\WP_CLI::warning( $state['error'] );
		}
	}
}
