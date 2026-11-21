<?php
/**
 * Background, resumable rebuild of the product search index.
 *
 * Rebuilds in short batches (Action Scheduler when WooCommerce provides it,
 * WP-Cron otherwise) so large catalogs never hit a PHP time limit, can resume
 * after an interruption, and report progress. Search keeps using the standard
 * path until the rebuild finishes, because the index version option is cleared
 * when a rebuild starts and only set again when it completes.
 *
 * @package NivoSearch
 * @since   2.0.8
 */

namespace NivoSearch;

defined( 'ABSPATH' ) || exit;

class Index_Rebuilder {

	/**
	 * Hook that runs one batch.
	 */
	const HOOK = 'nivo_search_index_batch';

	/**
	 * Option holding the job state.
	 */
	const OPTION = 'nivo_search_index_job';

	/**
	 * Products fetched per chunk.
	 */
	const CHUNK = 200;

	/**
	 * Seconds one scheduled run may work before handing over to the next.
	 */
	const TIME_BUDGET = 20;

	/**
	 * Catalogs up to this size are rebuilt immediately by the admin button.
	 */
	const SYNC_LIMIT = 2000;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( self::HOOK, array( __CLASS__, 'run_batch' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_autostart' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_notice' ) );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'nivo-search index', 'NivoSearch\\Index_CLI' );
		}
	}

	/**
	 * Current job state with defaults filled in.
	 *
	 * @return array{status:string,last_id:int,processed:int,total:int,started:int,updated:int,error:string,auto_started:int}
	 */
	public static function get_state() {
		$state = get_option( self::OPTION, array() );

		return wp_parse_args(
			is_array( $state ) ? $state : array(),
			array(
				'status'       => 'idle', // idle | running | done | failed.
				'last_id'      => 0,
				'processed'    => 0,
				'total'        => 0,
				'started'      => 0,
				'updated'      => 0,
				'error'        => '',
				'auto_started' => 0,
			)
		);
	}

	/**
	 * Persist job state.
	 *
	 * @param array $state State to store.
	 * @return void
	 */
	private static function save_state( $state ) {
		$state['updated'] = time();
		update_option( self::OPTION, $state, false );
	}

	/**
	 * Number of published products.
	 *
	 * @return int
	 */
	private static function count_products() {
		return (int) wp_count_posts( 'product' )->publish;
	}

	/**
	 * Start (or restart) a rebuild.
	 *
	 * Clears the index and the index version, so search falls back to the standard
	 * path until the job completes.
	 *
	 * @param bool $schedule Whether to schedule the first batch (false for WP-CLI, which runs it inline).
	 * @return array Job state.
	 */
	public static function start( $schedule = true ) {
		global $wpdb;

		Product_Indexer::maybe_create_table();

		delete_option( 'nivo_search_index_version' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.NotPrepared -- internal table name.
		$wpdb->query( 'TRUNCATE TABLE ' . Product_Indexer::get_table_name() );
		Product_Indexer::ensure_cover_index();

		$state = array_merge(
			self::get_state(),
			array(
				'status'    => 'running',
				'last_id'   => 0,
				'processed' => 0,
				'total'     => self::count_products(),
				'started'   => time(),
				'error'     => '',
			)
		);
		self::save_state( $state );

		if ( $schedule ) {
			self::schedule_next();
		}

		return $state;
	}

	/**
	 * Queue the next batch. Action Scheduler ships with WooCommerce; WP-Cron is the fallback.
	 *
	 * @return void
	 */
	public static function schedule_next() {
		/**
		 * Filters whether the rebuild is queued through Action Scheduler (when available)
		 * instead of WP-Cron. Return false to force WP-Cron.
		 *
		 * @param bool $use Default true.
		 */
		$use_action_scheduler = (bool) apply_filters( 'nivo_search_use_action_scheduler', true );

		if ( $use_action_scheduler && function_exists( 'as_enqueue_async_action' ) && function_exists( 'as_get_scheduled_actions' ) ) {
			// Look only for PENDING actions: as_has_scheduled_action() also counts the
			// in-progress action that is calling us, which would stop the job chaining itself.
			$pending = as_get_scheduled_actions(
				array(
					'hook'     => self::HOOK,
					'status'   => \ActionScheduler_Store::STATUS_PENDING,
					'per_page' => 1,
				),
				'ids'
			);

			if ( empty( $pending ) ) {
				as_enqueue_async_action( self::HOOK, array(), 'nivo-search' );
			}
			return;
		}

		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_single_event( time() + 5, self::HOOK );
		}
	}

	/**
	 * Process batches until the time budget is used up, then queue the next run.
	 *
	 * @param bool $schedule Whether to queue a follow-up run (false when driven inline by WP-CLI).
	 * @param int  $budget   Seconds to work; 0 means no limit.
	 * @return array Job state.
	 */
	public static function run_batch( $schedule = true, $budget = self::TIME_BUDGET ) {
		global $wpdb;

		// Boolean true/false is passed by the CLI; the scheduler passes nothing.
		$schedule = (bool) $schedule;

		$state = self::get_state();
		if ( 'running' !== $state['status'] ) {
			return $state;
		}

		// One runner at a time.
		if ( get_transient( 'nivo_search_index_lock' ) ) {
			return $state;
		}
		set_transient( 'nivo_search_index_lock', 1, MINUTE_IN_SECONDS );

		$started = microtime( true );
		$indexer = new Product_Indexer();

		try {
			do {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$ids = array_map(
					'intval',
					$wpdb->get_col(
						$wpdb->prepare(
							"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish' AND ID > %d ORDER BY ID ASC LIMIT %d",
							$state['last_id'],
							self::CHUNK
						)
					)
				);

				if ( empty( $ids ) ) {
					self::finish( $state );
					delete_transient( 'nivo_search_index_lock' );

					return self::get_state();
				}

				_prime_post_caches( $ids, true, true );

				foreach ( $ids as $id ) {
					$indexer->index_product( $id );
				}

				$state['last_id']    = end( $ids );
				$state['processed'] += count( $ids );
				self::save_state( $state );

				set_transient( 'nivo_search_index_lock', 1, MINUTE_IN_SECONDS );
			} while ( 0 === (int) $budget || ( microtime( true ) - $started ) < $budget );
		} catch ( \Throwable $e ) {
			$state['status'] = 'failed';
			$state['error']  = $e->getMessage();
			self::save_state( $state );
			delete_transient( 'nivo_search_index_lock' );

			return $state;
		}

		delete_transient( 'nivo_search_index_lock' );

		if ( $schedule ) {
			self::schedule_next();
		}

		return $state;
	}

	/**
	 * Mark the rebuild complete and switch the index-first search on.
	 *
	 * @param array $state Job state.
	 * @return void
	 */
	private static function finish( $state ) {
		update_option( 'nivo_search_index_last_rebuilt', time() );
		update_option( 'nivo_search_index_count', (int) $state['processed'] );
		update_option( 'nivo_search_index_has_content', apply_filters( 'nivo_search_index_content', true ) ? 1 : 0 );
		update_option( 'nivo_search_index_version', Product_Indexer::INDEX_VERSION );

		$state['status'] = 'done';
		self::save_state( $state );

		Nivo_Ajax_Search::invalidate_search_cache();
	}

	/**
	 * Start a rebuild automatically once per index version when the index is out of date.
	 *
	 * Runs in wp-admin for administrators only. Opt out with the option
	 * `nivo_search_auto_reindex` or the filter of the same name.
	 *
	 * @return void
	 */
	public static function maybe_autostart() {
		if ( ! current_user_can( 'manage_options' ) || ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$state = self::get_state();

		// Recover a job whose scheduled run went missing.
		if ( 'running' === $state['status'] ) {
			if ( time() - (int) $state['updated'] > 5 * MINUTE_IN_SECONDS ) {
				self::schedule_next();
			}
			return;
		}

		if ( (int) get_option( 'nivo_search_index_version', 0 ) === Product_Indexer::INDEX_VERSION ) {
			return;
		}

		if ( ! apply_filters( 'nivo_search_auto_reindex', (bool) get_option( 'nivo_search_auto_reindex', 1 ) ) ) {
			return;
		}

		// Only try once per index version automatically; a failed job needs a manual restart.
		if ( (int) $state['auto_started'] === Product_Indexer::INDEX_VERSION ) {
			return;
		}

		if ( self::count_products() < 1 ) {
			return;
		}

		$state['auto_started'] = Product_Indexer::INDEX_VERSION;
		self::save_state( $state );
		self::start();
	}

	/**
	 * Show progress on NivoSearch admin screens while a rebuild runs.
	 *
	 * @return void
	 */
	public static function render_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || false === strpos( (string) $screen->id, 'nivo' ) ) {
			return;
		}

		$state = self::get_state();

		if ( 'running' === $state['status'] ) {
			$percent = $state['total'] > 0 ? min( 100, (int) round( $state['processed'] / $state['total'] * 100 ) ) : 0;
			printf(
				'<div class="notice notice-info"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: products indexed, 2: total products, 3: percent */
						__( 'NivoSearch is building its search index in the background: %1$d of %2$d products (%3$d%%). Search keeps working and switches to the faster index when it finishes.', 'nivo-ajax-search-for-woocommerce' ),
						(int) $state['processed'],
						(int) $state['total'],
						$percent
					)
				)
			);
		} elseif ( 'failed' === $state['status'] ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %s: error message */
						__( 'NivoSearch could not finish building its search index: %s. Search is using the standard method. Use "Rebuild index" to try again.', 'nivo-ajax-search-for-woocommerce' ),
						$state['error']
					)
				)
			);
		}
	}
}
