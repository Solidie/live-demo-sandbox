<?php
/**
 * Script registrars
 *
 * @package solidie
 */

namespace Solidie_Sandbox\Setup;

use Solidie_Sandbox\Main;
use Solidie_Sandbox\Models\Instance;

/**
 * Sandbox class
 */
class Sandbox {

	public function __construct() {
		add_action( 'init', array( $this, 'createSandbox' ) );
	}

	public function createSandbox() {

		$parsed   = parse_url( Main::$configs->current_url );
		$path     = $parsed['path'];
		$path     = trim( $path, '/' );
		$path     = explode( '/', $path );
		$path     = end( $path );

		// Check if if the path is targeted URL to hit
		$instance = new Instance();
		if ( $path !== $instance->getInstancePath() ) {
			return;
		}

		$pointer   = explode( '_', ( $_COOKIE['slds_sandbox_pointer'] ?? '' ) ) ;
		$sand_id   = $pointer[0] ?? null;
		$site_id   = $pointer[1] ?? null;
		$site_path = $pointer[2] ?? null;
		$url       = null;

		if ( $sand_id && $site_id && is_numeric( $sand_id ) && is_numeric( $site_id ) && ! empty( $site_path ) ) {
			// Redirect to the target sandbox site
			$url = $instance->getSandboxURL( $sand_id, $site_id, $site_path );
		} else {
			// Create a sandbox site and then redirect to it
			$data = $instance->createSandboxSite();
			if ( ! $data ) {
				wp_send_json_error( array( 'message' => __( 'Something went wrong! Could not created sandbox.', 'live-demo-sandbox' ) ) );
			}

			$url  = $data['url'];
			setcookie( 
				'slds_sandbox_pointer', 
				"{$data['sandbox_id']}_{$data['site_id']}_{$data['site_path']}",
				time() + (30 * 24 * 60 * 60), // 30 days
				parse_url( get_home_url() )['path'],
			);
		}

		if ( ! empty( $url ) ) {
			wp_safe_redirect( $url );
			exit;
		}
	}
}
