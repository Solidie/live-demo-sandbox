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
class Request {

	private $action;

	public function __construct( string $action ) {
		$this->action = $action;
	}

	public function post( $data ) {
		// $request = wp_remote_post(  );
	}
}
