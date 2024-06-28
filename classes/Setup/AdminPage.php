<?php
/**
 * Admin page setup
 *
 * @package solidie
 */

namespace Solidie_Sandbox\Setup;

use Solidie_Sandbox\Main;
use Solidie_Sandbox\Models\Instance;
use Solidie_Sandbox\Models\Sandbox;

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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueMediaPicker' ) );
	}

	/**
	 * Enqueue media picker in this plugins page
	 *
	 * @return void
	 */
	public function enqueueMediaPicker() {
		if ( is_admin() && ( sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) ) ) === Main::$configs->root_menu_slug ) {
			wp_enqueue_media();
			wp_enqueue_script( 'jquery' );
		}
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

		$instance                 = new Instance();
		$configs                  = $instance->getConfigs();
		$configs['dashboard_url'] = $instance->multiSiteHomeURL() . 'wp-admin/';
		$configs['sandbox_url']   = Sandbox::getSandboxInitURL();
		$configs['is_apache']     = strpos( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ?? '' ) ), 'Apache' ) !== false;

		echo '<div 
			id="Solidie_Sandbox_Backend_Dashboard"
			data-configs="' . esc_attr( wp_json_encode( (object) $configs ) ) . '"
		></div>';
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
