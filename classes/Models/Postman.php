<?php
/**
 * Request functionalities
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Models;

/**
 * Request functions
 */
class Postman {

	/**
	 * The ajax action
	 *
	 * @var string
	 */
	private $action;

	/**
	 * The ajax url to the instance multisite
	 *
	 * @var string
	 */
	private $ajax_url;

	/**
	 * Post constructor
	 *
	 * @param string $action The ajax action
	 * @param string $home_url Home URL of multisite
	 */
	public function __construct( string $action, string $home_url ) {

		// Set ajax action
		$this->action = $action;

		// Set ajax url
		$this->ajax_url = $home_url . 'wp-admin/admin-ajax.php';
	}

	/**
	 * Send request to the instance
	 *
	 * @param array $data The post data array
	 * @return object
	 */
	public function request( $data = array() ) {

		// Session initiation request
		wp_remote_post( $this->ajax_url, array( 'body' => array( 'action' => 'slds_init_internal_session' ) ) );

		// Retrieve generated nonce
		$nonce_path = sys_get_temp_dir() . '/slds-nonce.tmp';
		$nonce      = file_exists( $nonce_path ) ? file_get_contents( $nonce_path ) : null;

		if ( empty( $nonce ) ) {
			return (object) array(
				'success' => false,
				'data'    => (object) array(
					'message' => 'Nonce was not generated',
				),
			);
		}

		// Send actual request
		$request = wp_remote_post(
			$this->ajax_url,
			array(
				'body' => array_merge(
					$data,
					array(
						'nonce'       => $nonce,
						'action'      => 'slds_internal_request',
						'slds_action' => $this->action,
					)
				),
			)
		);

		$response          = ( ! is_wp_error( $request ) && is_array( $request ) ) ? @json_decode( $request['body'] ?? null ) : null;
		$response          = is_object( $response ) ? $response : new \stdClass();
		$response->success = $response->success ?? false;
		$response->data    = $response->data ?? new \stdClass();

		return $response;
	}
}
