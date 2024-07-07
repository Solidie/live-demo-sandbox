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
class SandboxSetup {

	/**
	 * Setup constructor to register hooks
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'createSandbox' ) );
	}
	
	/**
	 * Check if it is browser request
	 *
	 * @return boolean
	 */
	private function isBrowserRequest() {

		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {

			$userAgent = $_SERVER['HTTP_USER_AGENT'];
			
			// List of common browsers
			$browsers = apply_filters(
				'slds_browser_identifier_flags',
				array( 'Mozilla', 'Chrome', 'Safari', 'Opera', 'MSIE', 'Edge', 'Trident' )
			);
			
			// List of common crawlers/bots
			$crawlers = apply_filters(
				'slds_crawler_identifier_flags',
				array(
					'Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot', 'Baiduspider', 'YandexBot', 'Sogou', 'Exabot',
					'facebot', 'ia_archiver', 'MJ12bot', 'AhrefsBot', 'SEMrushBot', 'DotBot', 'MegaIndex', 'YandexImages',
					'Google Web Preview', 'Twitterbot', 'Pinterest', 'LinkedInBot'
				)
			);

			$isBrowser = false;
			$isCrawler = false;

			// Check if the user agent is a known browser
			foreach ( $browsers as $browser ) {
				if ( stripos( $userAgent, $browser ) !== false) {
					$isBrowser = true;
					break;
				}
			}

			// Check if the user agent is a known crawler/bot
			foreach ( $crawlers as $crawler ) {
				if ( stripos( $userAgent, $crawler ) !== false) {
					$isCrawler = true;
					break;
				}
			}

			// Consider it a browser request if it is a known browser and not a known crawler
			return $isBrowser && !$isCrawler;
		}

		return false;
	}


	/**
	 * Create sandbox if it the URL
	 *
	 * @return void
	 */
	public function createSandbox() {

		if ( is_admin() || 'GET' !== sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || ! $this->isBrowserRequest() ) {
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
				$error_messages = $data['message'];
				include Main::$configs->dir . 'templates/error.php';
				exit;
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
