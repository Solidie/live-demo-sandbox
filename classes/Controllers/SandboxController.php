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
		'getSandboxes' => array(
			'role' => 'administrator',
		),
		'deleteSandbox' => array(
			'role' => 'administrator',
		),
	);

	public static function getSandboxes() {
		
		$sandboxes = ( new Sandbox() )->getSandboxes();

		wp_send_json_success( array( 'sandboxes' => $sandboxes ) );
	}

	public static function deleteSandbox( int $sandbox_id ) {
		// new Sandbox()->de
	}
}
