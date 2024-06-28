<?php
/**
 * Script registrars
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Setup;

use Solidie_Sandbox\Main;
use Solidie_Sandbox\Models\Sandbox as ModelsSandbox;

/**
 * Sandbox class
 */
class Sandbox {

	/**
	 * Setup constructor to register hooks
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'createSandbox' ) );
	}

	/**
	 * Create sandbox if it the URL
	 *
	 * @return void
	 */
	public function createSandbox() {

		if ( is_admin() || 'GET' !== sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
			return;
		}

		$parsed = wp_parse_url( Main::$configs->current_url );
		$path   = $parsed['path'];
		$path   = trim( $path, '/' );
		$path   = explode( '/', $path );
		$path   = end( $path );

		// Check if if the path is targeted URL to hit
		if ( ModelsSandbox::getSandboxInitPath() !== $path ) {
			return;
		}

		// Sandbox instance
		$instance = new ModelsSandbox();

		// Sandbox info
		$pointer    = explode( '_', ( sanitize_text_field( wp_unslash( $_COOKIE['slds_sandbox_pointer'] ?? '' ) ) ) );
		$sandbox_id = $pointer[0] ?? null;
		$site_id    = $pointer[1] ?? null;
		$site_path  = $pointer[2] ?? null;
		$url        = null;

		if ( $sandbox_id && $site_id && is_numeric( $sandbox_id ) && is_numeric( $site_id ) && ! empty( $site_path ) ) {
			// Redirect to the target sandbox site
			$sandbox = $instance->getSandbox( compact( 'sandbox_id', 'site_id', 'site_path' ) );
			$url     = $sandbox ? $sandbox['home_url'] : null;
		}

		if ( empty( $url ) ) {
			// Create a sandbox site and then redirect to it
			$data = $instance->createSandboxSite();
			if ( ! $data['success'] ) {
				wp_send_json_error(
					array(
						'message' => $data['message'] ?? __( 'Could not created sandbox.', 'live-demo-sandbox' ),
					)
				);
			}

			$url = $data['url'];
			setcookie(
				'slds_sandbox_pointer',
				"{$data['sandbox_id']}_{$data['site_id']}_{$data['site_path']}",
				time() + ( 6 * 60 * 60 ), // 6 hours
				wp_parse_url( get_home_url() )['path'],
			);
		}

		if ( ! empty( $url ) ) {
			wp_safe_redirect( $url );
			exit;
		}
	}
}
