<?php
/**
 * Sandbox controller
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Controllers;

use Solidie_Sandbox\Models\Instance;
use Solidie_Sandbox\Models\Sandbox;
use Solidie_Sandbox\Setup\Cron;

/**
 * Content manager class
 */
class SandboxController {


	const PREREQUISITES = array(
		'getSandboxes'        => array(
			'role' => 'administrator',
		),
		'deleteSandbox'       => array(
			'role' => 'administrator',
		),
		'saveSandboxSettings' => array(
			'role' => 'administrator',
		),
	);

	/**
	 * Get sandbox list in browser
	 *
	 * @param string $host_id Multsite host
	 * @param int    $page Page number for pagination
	 *
	 * @return void
	 */
	public static function getSandboxes( string $host_id, int $page = 1 ) {

		do_action( Cron::HOOK_NAME );

		$args     = array( 'page' => $page );
		$instance = new Sandbox( $host_id );

		wp_send_json_success(
			array(
				'sandboxes'    => $instance->getSandboxes( $args ),
				'segmentation' => $instance->getSandboxes( $args, true ),
			)
		);
	}

	/**
	 * Delete sandbox by id
	 *
	 * @param string  $host_id Multsite host
	 * @param integer $sandbox_id The sandbox ID to delete
	 *
	 * @return void
	 */
	public static function deleteSandbox( string $host_id, int $sandbox_id ) {

		$deleted = ( new Sandbox( $host_id ) )->deleteSandbox( $sandbox_id );

		if ( true === $deleted ) {
			wp_send_json_success( array( 'message' => __( 'Sandbox deleted successfully', 'live-demo-sandbox' ) ) );

		} else {
			wp_send_json_error(
				array(
					'message' => is_string( $deleted ) ? $deleted : __( 'Something went wrong! Could not delete sandbox', 'live-demo-sandbox' ),
				)
			);
		}
	}

	/**
	 * Save sandbox settings
	 *
	 * @param array  $settings Settings array
	 * @param string $host_id Host ID
	 *
	 * @return void
	 */
	public static function saveSandboxSettings( array $settings, string $host_id ) {
		( new Instance( $host_id ) )->updateSettings( $settings );
		wp_send_json_success();
	}
}
