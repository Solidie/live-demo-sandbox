<?php
/**
 * Cron deleter setup
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Setup;

use Solidie_Sandbox\Models\Sandbox;

/**
 * Admin page setup handlers
 */
class Cron {

	const TIMER_NAME = 'slds_every_fifteen_minutes';
	const HOOK_NAME  = 'slds_clear_expired_sandbox_sites';

	/**
	 * Cron register
	 */
	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'customInterval' ) );
		add_action( 'init', array( $this, 'siteDeletion' ) );
		add_action( self::HOOK_NAME, array( $this, 'clearSites' ) );
	}

	/**
	 * Define custom cron interval to delete sub sites
	 *
	 * @param array $schedules
	 * @return array
	 */
	public function customInterval( $schedules ) {

		$schedules[ self::TIMER_NAME ] = array(
			'interval' => 900,
			'display'  => __( 'Every 15 Minutes', 'live-demo-sandbox' ),
		);

		return $schedules;
	}

	/**
	 * Add scheduler to call the clearer hook.
	 *
	 * @return void
	 */
	public function siteDeletion() {
		if ( ! wp_next_scheduled( self::HOOK_NAME ) ) {
			wp_schedule_event( time(), self::TIMER_NAME, self::HOOK_NAME );
		}
	}

	/**
	 * Delete expired sites
	 *
	 * @return void
	 */
	public function clearSites() {

		$timestamp = gmdate( 'Y-m-d H:i:s' );
		
		global $wpdb;
		$sandbox_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT
					sandbox_id
				FROM
					{$wpdb->slds_sandboxes}
				WHERE
					expires_at<%s",
				$timestamp
			)
		);

		if ( ! empty( $sandbox_ids ) ) {

			$instance    = ( new Sandbox() );
			$sandbox_ids = array_map( 'intval', $sandbox_ids );

			$instance->deleteSandbox( $sandbox_ids );
		}
	}
}
