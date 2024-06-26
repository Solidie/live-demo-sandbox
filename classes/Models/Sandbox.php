<?php
/**
 * Sandbox functionalities
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Models;

/**
 * Sandbox functions
 */
class Sandbox extends Instance {

	public function createSandboxSite() {

		$request = wp_remote_post( 
			$this->multiSiteHomeURL() . 'wp-admin/admin-ajax.php',
			array(
				'body' => array(
					'action' => 'slds_create_multisite',
					'role'   => 'administrator'
				)
			)
		);
		
		$response          = ( ! is_wp_error( $request ) && is_array( $request ) ) ? @json_decode( $request['body'] ?? null ) : null;
		$response          = is_object( $response ) ? $response : new \stdClass();
		$response->success = $response->success ?? false;
		$response->data    = $response->data ?? new \stdClass();
		
		if ( ! $response->success ) {
			return array(
				'success' => false,
				'message' => $response->data->message ?? __( 'Something went wrong! Could not create sandbox.' ),
			);
		}

		$timestamp = gmdate( 'Y-m-d H:i:s' );
		$minutes     = 30;

		// Determine IP address
		$user_ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
		
		global $wpdb;
		$wpdb->insert(
			$wpdb->slds_sandboxes,
			array(
				'site_id'    => $response->data->site_id,
				'site_title' => $response->data->site_title,
				'user_ip'    => $user_ip,
				'site_path'  => $response->data->site_path,
				'created_at' => $timestamp,
				'expires_at' => ( new \DateTime( $timestamp ) )->modify( "+{$minutes} minutes")->format('Y-m-d')
			)
		);
		
		return array(
			'success'    => true,
			'sandbox_id' => $wpdb->insert_id,
			'site_id'    => $response->data->site_id,
			'site_path'  => $response->data->site_path,
			'url'        => $this->getRootUrl() . '/' . $response->data->site_path . '/',
		);
	}

	private static function getRootUrl() {
		$parsed = parse_url( get_home_url() );
		return $parsed['scheme'] . '://' . $parsed['host'];
	}

	/**
	 * Get sansboxes
	 *
	 * @param array $args
	 * @return array
	 */
	public function getSandboxes( $args ) {
		
		global $wpdb;

		$where_clause = '1=1';

		// Sandbox ID filter
		if ( ! empty( $args['sandbox_id'] ) ) {
			$where_clause .= $wpdb->prepare( ' AND sandbox_id=%d', $args['sandbox_id'] );
		}

		// Site ID filter
		if ( ! empty( $args['site_id'] ) ) {
			$where_clause .= $wpdb->prepare( ' AND site_id=%d', $args['site_id'] );
		}

		// Site path filter
		if ( ! empty( $args['site_path'] ) ) {
			$where_clause .= $wpdb->prepare( ' AND site_path=%s', $args['site_path'] );
		}

		$sandboxes = $wpdb->get_results(
			"SELECT * FROM {$wpdb->slds_sandboxes} WHERE {$where_clause}",
			ARRAY_A
		);

		$root = $this->getRootUrl();
		foreach ( $sandboxes as $index => $sandbox ) {
			$sandboxes[ $index ]['dashboard_url'] = $root . '/' . $sandbox['site_path'] . '/wp-admin/';
			$sandboxes[ $index ]['home_url']      = $root . '/' . $sandbox['site_path'] . '/wp-admin/';
		}

		return $sandboxes;
	}

	/**
	 * Get single sandbox
	 *
	 * @param array $args
	 * @return array
	 */
	public function getSandbox( $args ) {
		$sandbox = $this->getSandboxes( $args );
		return $sandbox[0] ?? null;
	}

	public function deleteSandbox( $id ) {
		$sandbox = $this->getSandbox( array( 'sandbox_id' => $id ) );
		if ( empty( $sandbox ) ) {
			return;
		}

		/* wp_remote_post(
			$sandbox['dashboard_url'] . 'admin-ajax.php',
			array(

			)
		) */
	}
}
