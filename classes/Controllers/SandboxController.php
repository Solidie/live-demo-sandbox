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
		'getSandboxes'  => array(
			'role' => 'administrator',
		),
		'deleteSandbox' => array(
			'role' => 'administrator',
		),
		'saveSandboxSettings' => array(
			'role' => 'administrator',
		),
	);

	/**
	 * Get sandbox list in browser
	 *
	 * @return void
	 */
	public static function getSandboxes( int $page = 1 ) {

		do_action( Cron::HOOK_NAME );

		$args      = array( 'page' => $page );
		$instance  = new Sandbox();
		
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
	 * @param integer $sandbox_id The sandbox ID to delete
	 * @return void
	 */
	public static function deleteSandbox( int $sandbox_id ) {

		$deleted = ( new Sandbox() )->deleteSandbox( $sandbox_id );

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
	 * @param array $settings
	 * @return void
	 */
	public static function saveSandboxSettings( array $settings ) {
		( new Instance() )->updateSettings( $settings );
		wp_send_json_success();
	}
}
