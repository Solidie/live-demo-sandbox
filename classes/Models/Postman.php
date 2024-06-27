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

	private $action;
	private $sandbox;

	public function __construct( string $action, int $sandbox = null ) {
		$this->action   = $action;
		$this->sandbox = $sandbox;
	}

	public function request( $data = array() ) {
		
		$home_url = $this->sandbox ? Sandbox::getRootUrl() : ( new Instance() )->multiSiteHomeURL();
		$ajax_url = $home_url . 'wp-admin/admin-ajax.php';

		$request = wp_remote_post(
			$ajax_url,
			array(
				'body' => array_merge(
					$data, 
					array( 
						'action'      => 'slds_internal_request',
						'slds_action' => $this->action 
					)
				)
			)
		);

		$response          = ( ! is_wp_error( $request ) && is_array( $request ) ) ? @json_decode( $request['body'] ?? null ) : null;
		$response          = is_object( $response ) ? $response : new \stdClass();
		$response->success = $response->success ?? false;
		$response->data    = $response->data ?? new \stdClass();
		
		return $response;
	}
}
