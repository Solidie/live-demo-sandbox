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
		'downloadWP'             => array(
			'role' => 'administrator',
		),
		'initBaseInstance'       => array(
			'role' => 'administrator',
		),
		'deployNetworkConfigs'   => array(
			'role' => 'administrator',
		),
		'multisiteSetupComplete' => array(
			'role' => 'administrator',
		),
		'deleteEntireHost'       => array(
			'role' => 'administrator',
		),
		'getHosts'               => array(
			'role' => 'administrator',
		),
	);

	/**
	 * Download WP
	 *
	 * @return void
	 */
	public static function downloadWP() {

		set_time_limit( 60 );

		$source_path = Instance::getSourcePath();
		$url         = 'https://wordpress.org/latest.zip';
		$success     = file_exists( $source_path ) || copy( $url, $source_path );

		if ( $success ) {
			wp_send_json_success();
		} else {

			// Delete incomplete downloaded file
			if ( file_exists( $source_path ) ) {
				wp_delete_file( $source_path );
			}

			wp_send_json_error( array( 'message' => __( 'WordPress download error', 'live-demo-sandbox' ) ) );
		}
	}

	/**
	 * Provide content list for various area like dashboard, gallery and so on.
	 *
	 * @param array  $configs Multsite init configs
	 * @param string $host_id Sandbox host ID
	 *
	 * @return void
	 */
	public static function initBaseInstance( array $configs, string $host_id ) {

		$ret = ( new Instance( $host_id, $configs ) )->createMultiSite();

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

	/**
	 * Deploy multsite constants
	 *
	 * @param string $host_id Multisite host ID
	 *
	 * @return void
	 */
	public static function deployNetworkConfigs( string $host_id ) {
		$instance = new Instance( $host_id );
		$instance->deployNetworkConfigs();
		wp_send_json_success( array( 'iframe_url' => $instance->multiSiteHomeURL() ) );
	}

	/**
	 * Mark multisite as completed setup
	 *
	 * @param string $host_id Multisite host ID
	 *
	 * @return void
	 */
	public static function multisiteSetupComplete( string $host_id ) {
		( new Instance( $host_id ) )->markMultiSiteCompleted();
		wp_send_json_success();
	}

	/**
	 * Delete multsite entirely
	 *
	 * @param string $host_id Multsite host ID
	 *
	 * @return void
	 */
	public static function deleteEntireHost( string $host_id ) {
		( new Instance( $host_id ) )->deleteMultiSite();
		wp_send_json_success();
	}

	/**
	 * Get created hosts array
	 *
	 * @return void
	 */
	public static function getHosts() {

		$instance = new Instance( '' );

		wp_send_json_success(
			array(
				'hosts' => (object) $instance->getConfigs( null, null, true ),
			)
		);
	}
}
