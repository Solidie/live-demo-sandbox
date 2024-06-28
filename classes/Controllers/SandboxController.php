<?php
/**
 * Sandbox controller
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Controllers;

use Solidie_Sandbox\Models\Sandbox;

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
	);

	/**
	 * Get sandbox list in browser
	 *
	 * @return void
	 */
	public static function getSandboxes() {

		$sandboxes = ( new Sandbox() )->getSandboxes();

		wp_send_json_success( array( 'sandboxes' => $sandboxes ) );
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
}
