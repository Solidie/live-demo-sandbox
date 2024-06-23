<?php
/**
 * Admin page setup
 *
 * @package solidie
 */

namespace Solidie_Sandbox\Setup;

use Solidie_Sandbox\Main;

/**
 * Admin page setup handlers
 */
class AdminPage {

	const SETTINGS_SLUG = 'solidie-settings';

	/**
	 * Admin page setup hooks register
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'registerMenu' ) );
	}

	/**
	 * Register admin menu pages
	 *
	 * @return void
	 */
	public function registerMenu() {

		$role = 'manage_options';

		// Main page
		add_menu_page(
			esc_html__( 'Live Demo Sandbox', 'solidie' ),
			esc_html__( 'Sandbox', 'solidie' ),
			$role,
			Main::$configs->root_menu_slug,
			array( $this, 'homePage' )
		);

		// Register solidie dashboard home
		add_submenu_page(
			Main::$configs->root_menu_slug,
			esc_html__( 'Home', 'solidie' ),
			esc_html__( 'Home', 'solidie' ),
			$role,
			Main::$configs->root_menu_slug,
			array( $this, 'homePage' )
		);

		// General settings page
		add_submenu_page(
			Main::$configs->root_menu_slug,
			esc_html__( 'Settings', 'solidie' ),
			esc_html__( 'Settings', 'solidie' ),
			$role,
			self::SETTINGS_SLUG,
			array( $this, 'settingsPage' )
		);
	}

	/**
	 * Main page content
	 *
	 * @return void
	 */
	public function homePage() {
		echo '<div id="Solidie_Sandbox_Backend_Dashboard"></div>';
	}

	/**
	 * Geenral settings page contents
	 *
	 * @return void
	 */
	public function settingsPage() {
		echo '<div 
				id="Solidie_Sandbox_Settings" 
				data-settings="' . esc_attr( wp_json_encode( array() ) ) . '"></div>';
	}
}
