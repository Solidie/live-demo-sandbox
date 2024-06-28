<?php
/**
 * The utilities functionalities
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Helpers;

use Solidie_Sandbox\Main;

/**
 * The class
 */
class Utilities {

	/**
	 * Check if the page is a Crew Dashboard
	 *
	 * @param  string $sub_page Optional sub page name to match too
	 * @return boolean
	 */
	public static function isAdminDashboard( $sub_page = null ) {
		$is_dashboard = is_admin() && get_admin_page_parent() === Main::$configs->root_menu_slug;

		if ( $is_dashboard && null !== $sub_page ) {

			// Accessing $_GET['page'] directly will most likely show nonce error in wpcs check.
			// However checking nonce is pointless since visitor can visit dashboard pages from bookmark or direct link.

			$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$pages        = ! is_array( $sub_page ) ? array( $sub_page ) : $sub_page;
			$is_dashboard = in_array( $current_page, $pages, true );
		}

		return $is_dashboard;
	}

	/**
	 * Get unique ID to point solid app in any setup
	 *
	 * @param string $url The URL to this plugin to get unique pointer for it
	 * @return string
	 */
	public static function getPluginId( $url ) {
		$pattern = '/\/([^\/]+)\/wp-content\/(plugins|themes)\/([^\/]+)\/.*/';
		preg_match( $pattern, $url, $matches );

		$parsed_string = strtolower( "CrewMat_{$matches[1]}_{$matches[3]}" );
		$app_id        = preg_replace( '/[^a-zA-Z0-9_]/', '', $parsed_string );

		return $app_id;
	}

	/**
	 * Generate admin page urls
	 *
	 * @param  string $page The page to get backend dashboard link to
	 * @return string
	 */
	public static function getBackendPermalink( string $page ) {
		return add_query_arg(
			array(
				'page' => $page,
			),
			admin_url( 'admin.php' )
		);
	}
}
