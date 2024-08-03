<?php
/**
 * Sandbox functionalities
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Models;

use SolidieLib\_Array;
use SolidieLib\_String;

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

		// Check if targeted host is valid
		if ( ! $this->isHostValid() ) {
			return array(
				'success' => false,
				'message' => array(
					__( 'Invalid Host', 'live-demo-sandbox' ),
					__( 'The targeted sandbox host was not found', 'live-demo-sandbox' ),
					__( 'Please checkout the origin page of live demo link', 'live-demo-sandbox' ),
				),
			);
		}

		// Check limit
		$settings = $this->getConfigs( 'settings', array() );
		$limit    = $settings['concurrency_limit'] ?? 100;
		$created  = $this->getSandboxes( array(), true )['total_count'];
		if ( $created >= $limit ) {
			return array(
				'success' => false,
				'message' => array(
					__( 'Sandbox Limit Reached', 'live-demo-sandbox' ),
					__( 'We\'re sorry, but the sandbox limit has been reached.', 'live-demo-sandbox' ),
					__( 'Please try again later. Some instances might be deleted in the meantime.', 'live-demo-sandbox' ),
				),
			);
		}

		// Create internal request to sandbox host to create one
		$response = ( new Postman( 'create_sandbox', $this->multiSiteHomeURL() ) )->request( array( 'role' => 'administrator' ) );
		if ( ! $response->success ) {
			return array(
				'success' => false,
				'message' => array(
					__( 'Something went wrong!', 'live-demo-sandbox' ),
					! empty( $response->data->message ) ? $response->data->message : __( 'Could not create sandbox.', 'live-demo-sandbox' ),
					__( 'Please try again later.', 'live-demo-sandbox' ),
				),
			);
		}

		// Determine IP address
		$user_ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '' ) );

		// Determine expiry
		$current_time       = gmdate( 'Y-m-d H:i:s' );
		$allowed_inactivity = sprintf( '+%s minutes', $this->getSandboxInactivityMinutes() );
		$expires_at         = gmdate( 'Y-m-d H:i:s', strtotime( $allowed_inactivity, strtotime( $current_time ) ) );

		global $wpdb;
		$wpdb->insert(
			$wpdb->slds_sandboxes,
			array(
				'site_id'    => $response->data->site_id,
				'host_id'    => $this->host_id,
				'site_title' => $response->data->site_title,
				'user_ip'    => ! empty( $user_ip ) ? $user_ip : null,
				'site_path'  => $response->data->site_path,
				'created_at' => $current_time,
				'expires_at' => $expires_at,
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
	 * @param array $args Sandbox query filter array
	 * @param bool  $segmentation Whether to get pagination or not
	 *
	 * @return array
	 */
	public function getSandboxes( $args = array(), $segmentation = false ) {

		global $wpdb;

		$page         = max( absint( (int) ( $args['page'] ?? 1 ) ), 1 );
		$limit        = 30;
		$offset       = ( $page - 1 ) * $limit;
		$limit_clause = $wpdb->prepare( ' LIMIT %d OFFSET %d', $limit, $offset );
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

		if ( $segmentation ) {

			$total_count = (int) $wpdb->get_var(
				"SELECT COUNT(sandbox_id) FROM {$wpdb->slds_sandboxes} WHERE {$where_clause}"
			);

			$page_count = ceil( $total_count / $limit );

			return array(
				'total_count' => $total_count,
				'page_count'  => $page_count,
				'page'        => $page,
				'limit'       => $limit,
			);
		}

		$sandboxes = $wpdb->get_results(
			"SELECT 
				*, 
				UNIX_TIMESTAMP(created_at) AS created_unix,
				UNIX_TIMESTAMP(expires_at) AS expires_unix,
				UNIX_TIMESTAMP(last_hit) AS last_hit_unix 
			FROM 
				{$wpdb->slds_sandboxes} 
			WHERE {$where_clause} {$limit_clause}",
			ARRAY_A
		);

		$root = $this->getAbsoluteRootURL();
		foreach ( $sandboxes as $index => $sandbox ) {
			$sandboxes[ $index ]['dashboard_url'] = $root . $sandbox['site_path'] . '/wp-admin/';
			$sandboxes[ $index ]['home_url']      = $root . $sandbox['site_path'];
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
	 * @param int|array $sandbox_id The ID or array of IDs of sandbox to delete
	 * @return bool|string
	 */
	public function deleteSandbox( $sandbox_id ) {

		// Prepare sandbox IDs placeholder for SQL
		$sandbox_ids = _Array::getArray( $sandbox_id, true, 0 );
		$ids_places  = _String::getPlaceHolders( $sandbox_ids );

		global $wpdb;
		$site_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT site_id FROM {$wpdb->slds_sandboxes} WHERE sandbox_id IN({$ids_places})",
				...$sandbox_ids
			)
		);

		if ( empty( $site_ids ) ) {
			return true;
		}

		$response = ( new Postman( 'delete_sandbox', $this->multiSiteHomeURL() ) )->request(
			array(
				'site_ids' => array_map( 'intval', $site_ids ),
			)
		);

		if ( $response->success ) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->slds_sandboxes} WHERE sandbox_id IN($ids_places)",
					$sandbox_ids
				)
			);
			return true;
		}

		return $response->data->message ?? __( 'Could not delete sandbox', 'live-demo-sandbox' );
	}

	/**
	 * Get the multsite root directory name only, not path
	 *
	 * @return string
	 */
	public static function getSandboxInitPath() {
		return apply_filters( 'slds_get_instance_path', 'live-demo-sandbox' );
	}

	/**
	 * Get how many minutes can a sandbox allowed to remain inactive.
	 *
	 * @return int Total minutes
	 */
	public function getSandboxInactivityMinutes() {

		$settings = $this->getConfigs( 'settings', array() );
		$time     = (int) $settings['inactivity_time_allowed'] ?? 1;
		$period   = $settings['inactivity_period_allowed'] ?? 'hour';
		$minutes  = (int) ( 'hour' === $period ? $time * 60 : $time );

		return $minutes > 0 ? $minutes : 60;
	}
}
