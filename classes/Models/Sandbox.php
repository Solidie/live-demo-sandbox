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

	/**
	 * Create sandbox/subsite
	 *
	 * @return array
	 */
	public function createSandboxSite() {

		$response = ( new Postman( 'create_sandbox' ) )->request( array( 'role' => 'administrator' ) );

		if ( ! $response->success ) {
			return array(
				'success' => false,
				'message' => $response->data->message ?? __( 'Something went wrong! Could not create sandbox.' ),
			);
		}

		$timestamp = gmdate( 'Y-m-d H:i:s' );
		$minutes   = 30;

		// Determine IP address
		$user_ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '' ) );

		global $wpdb;
		$wpdb->insert(
			$wpdb->slds_sandboxes,
			array(
				'site_id'    => $response->data->site_id,
				'site_title' => $response->data->site_title,
				'user_ip'    => ! empty( $user_ip ) ? $user_ip : null,
				'site_path'  => $response->data->site_path,
				'created_at' => $timestamp,
				'expires_at' => ( new \DateTime( $timestamp ) )->modify( "+{$minutes} minutes" )->format( 'Y-m-d' ),
			)
		);

		return array(
			'success'    => true,
			'sandbox_id' => $wpdb->insert_id,
			'site_id'    => $response->data->site_id,
			'site_path'  => $response->data->site_path,
			'url'        => $this->getAbsoluteRootURL() . $response->data->site_path . '/',
		);
	}

	/**
	 * Get root URL regardless of sub site
	 *
	 * @return string
	 */
	public static function getAbsoluteRootURL() {
		$parsed = wp_parse_url( get_home_url() );
		return $parsed['scheme'] . '://' . $parsed['host'] . '/';
	}

	/**
	 * Get sansboxes
	 *
	 * @param  array $args Sandbox query filter array
	 * @return array
	 */
	public function getSandboxes( $args = array() ) {

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

		$root = $this->getAbsoluteRootURL();
		foreach ( $sandboxes as $index => $sandbox ) {
			$sandboxes[ $index ]['dashboard_url'] = $root . $sandbox['site_path'] . '/wp-admin/';
			$sandboxes[ $index ]['home_url']      = $root . $sandbox['site_path'] . '/wp-admin/';
		}

		return $sandboxes;
	}

	/**
	 * Get single sandbox
	 *
	 * @param  array $args Filter array
	 * @return array
	 */
	public function getSandbox( $args ) {
		$sandbox = $this->getSandboxes( $args );
		return $sandbox[0] ?? null;
	}

	/**
	 * Delete sandbox
	 *
	 * @param int $sandbox_id The ID of sandbox to delete
	 * @return bool|string
	 */
	public function deleteSandbox( $sandbox_id ) {

		$sandbox = $this->getSandbox( array( 'sandbox_id' => $sandbox_id ) );
		if ( empty( $sandbox ) ) {
			// Maybe deleted from another tab
			return true;
		}

		$response = ( new Postman( 'delete_sandbox' ) )->request( array( 'site_id' => $sandbox['site_id'] ) );

		if ( $response->success ) {

			global $wpdb;

			$wpdb->delete(
				$wpdb->slds_sandboxes,
				array( 'sandbox_id' => $sandbox_id )
			);

			return true;
		}

		return $response->data->message ?? __( 'Could not delete sandbox', 'live-demo-instance' );
	}

	/**
	 * Get the multsite root directory name only, not path
	 *
	 * @return string
	 */
	public static function getSandboxInitPath() {
		return apply_filters( 'slds_get_instance_path', 'live-demo-sandbox-instance' );
	}

	/**
	 * Get the multisite root dir url
	 *
	 * @return string
	 */
	public static function getSandboxInitURL() {
		return get_home_url() . '/' . self::getSandboxInitPath() . '/';
	}
}
