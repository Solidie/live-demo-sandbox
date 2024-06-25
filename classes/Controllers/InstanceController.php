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
		'downloadWP' => array(
			'role' => 'administrator',
		),
		'initBaseInstance' => array(
			'role' => 'administrator',
		),
		'deployNetworkConfigs' => array(
			'role' => 'administrator',
		),
		'multisiteSetupComplete' => array(
			'role' => 'administrator',
		),
		'deleteEntireHost' => array(
			'role' => 'administrator'
		)
	);

	/**
	 * Download WP
	 *
	 * @return void
	 */
	public static function downloadWP() {

		set_time_limit(60);
		
		$source_path = Instance::getSourcePath();
		$url         = 'https://wordpress.org/latest.zip';
		$success     = file_exists( $source_path ) || copy( $url, $source_path );

		if ( $success ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( array( 'message' => __( 'WordPress download error', 'live-demo-sandbox' ) ) );
		}
	}
	
	/**
	 * Provide content list for various area like dashboard, gallery and so on.
	 *
	 * @param array $filters Content filter arguments
	 * @param bool  $is_contributor_inventory Whether it is frontend contributor dashboard
	 * @param bool  $is_gallery Whether loaded in gallery
	 * @return void
	 */
	public static function initBaseInstance( array $configs ) {

		$ret = (new Instance( $configs ))->createMultiSite();

		if ( ! ( $ret['success'] ?? false ) ) {
			wp_send_json_error( 
				array( 
					'message'   => $ret['message'] ?? __( 'Something went wrong!', 'live-demo-sandbox' ),
					'duplicate' => $ret['duplicate'] ?? false,
				) 
			);
		} else {
			wp_send_json_success( array( 'iframe_url' => $ret['iframe_url'] ) );
		}
	}

	public static function deployNetworkConfigs() {
		$instance = new Instance();
		$instance->deployNetworkConfigs();
		wp_send_json_success( array( 'iframe_url' => $instance->multiSiteHomeURL() ) );
	}

	public static function multisiteSetupComplete() {
		( new Instance() )->markMultiSiteCompleted();
		wp_send_json_success();
	}

	public static function deleteEntireHost() {
		( new Instance() )->deleteMultiSite();
		wp_send_json_success();
	}
}
