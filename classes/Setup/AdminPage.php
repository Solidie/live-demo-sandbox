<?php
/**
 * Admin page setup
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Setup;

use Solidie_Sandbox\Main;
use Solidie_Sandbox\Models\Instance;
use Solidie_Sandbox\Models\Sandbox;

/**
 * Admin page setup handlers
 */
class AdminPage {


	const SETTINGS_SLUG = 'live-demo-settings';

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
			esc_html__( 'Live Demo Sandbox', 'live-demo-sandbox' ),
			esc_html__( 'Sandbox', 'live-demo-sandbox' ),
			$role,
			Main::$configs->root_menu_slug,
			array( $this, 'homePage' ),
			Main::$configs->dist_url . 'libraries/logo.svg'
		);

		// Register dashboard home
		add_submenu_page(
			Main::$configs->root_menu_slug,
			esc_html__( 'Multisites', 'live-demo-sandbox' ),
			esc_html__( 'Multisites', 'live-demo-sandbox' ),
			$role,
			Main::$configs->root_menu_slug,
			array( $this, 'homePage' )
		);
	}

	/**
	 * Main page content
	 *
	 * @return void
	 */
	public function homePage() {

		$instance = new Instance( 'new' );
		$hosts    = (object) $instance->getConfigs( null, null, true );
		$configs  = (object) $instance->getDefaultHostConfigs();

		$meta_data = array(
			'is_apache' => strpos( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ?? '' ) ), 'Apache' ) !== false,
		);

		echo '<div 
			id="Solidie_Sandbox_Backend_Dashboard"
			data-configs="' . esc_attr( wp_json_encode( $configs ) ) . '"
			data-hosts="' . esc_attr( wp_json_encode( $hosts ) ) . '"
			data-meta_data="' . esc_attr( wp_json_encode( $meta_data ) ) . '"
		></div>';
	}
}
