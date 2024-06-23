<?php
/**
 * Instance controller
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Controllers;

use Solidie_Sandbox\Models\Instance;

/**
 * Content manager class
 */
class InstanceController {

	const PREREQUISITES = array(
		'initBaseInstance' => array(

		),
	);
	
	/**
	 * Provide content list for various area like dashboard, gallery and so on.
	 *
	 * @param array $filters Content filter arguments
	 * @param bool  $is_contributor_inventory Whether it is frontend contributor dashboard
	 * @param bool  $is_gallery Whether loaded in gallery
	 * @return void
	 */
	public static function initBaseInstance() {

		$created = Instance::createMultiSite( true );

		if ( $created === true ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( array( 'message' => $created ) );
		}
	}
}
