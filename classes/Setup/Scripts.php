<?php
/**
 * Script registrars
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Setup;

use SolidieLib\Colors;
use SolidieLib\Utilities;
use Solidie_Sandbox\Main;
use SolidieLib\Variables;

/**
 * Script class
 */
class Scripts {


	/**
	 * Scripts constructor, register script hooks
	 *
	 * @return void
	 */
	public function __construct() {

		// Load scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'adminScripts' ), 11 );

		// Register script translations
		add_action( 'admin_enqueue_scripts', array( $this, 'scriptTranslation' ), 9 );

		// Load text domain
		add_action( 'init', array( $this, 'loadTextDomain' ) );

		// JS Variables
		add_action( 'admin_enqueue_scripts', array( $this, 'loadVariables' ) );
	}

	/**
	 * Load environment and color variables
	 *
	 * @return void
	 */
	public function loadVariables() {

		if ( ! $this->isSLDS() ) {
			return;
		}

		// Get the default variables
		$variables               = ( new Variables( Main::$configs ) )->get();
		$variables['permalinks'] = array_merge(
			$variables['permalinks'],
			array(
				'settings'  => Utilities::getBackendPermalink( AdminPage::SETTINGS_SLUG ),
				'dashboard' => Utilities::getBackendPermalink( Main::$configs->root_menu_slug ),
			)
		);

		// Load data
		$data    = apply_filters( 'slds_frontend_variables', $variables );
		$pointer = Main::$configs->app_id;

		wp_localize_script( 'slds-translations', $pointer, $data );
	}

	/**
	 * Load scripts for admin dashboard
	 *
	 * @return void
	 */
	public function adminScripts() {
		if ( $this->isSLDS() ) {
			wp_enqueue_script( 'slds-backend', Main::$configs->dist_url . 'admin-dashboard.js', array( 'jquery' ), Main::$configs->version, true );
		}
	}

	/**
	 * Load text domain for translations
	 *
	 * @return void
	 */
	public function loadTextDomain() {
		load_plugin_textdomain( Main::$configs->text_domain, false, Main::$configs->dir . 'languages' );
	}

	/**
	 * Load translations
	 *
	 * @return void
	 */
	public function scriptTranslation() {

		if ( $this->isSLDS() ) {

			$domain = Main::$configs->text_domain;
			$dir    = Main::$configs->dir . 'languages/';

			wp_enqueue_script( 'slds-translations', Main::$configs->dist_url . 'libraries/translation-loader.js', array( 'jquery' ), Main::$configs->version, true );
			wp_set_script_translations( 'slds-translations', $domain, $dir );
		}
	}

	/**
	 * Check if it is slds admin screen
	 *
	 * @return boolean
	 */
	private function isSLDS() {

		static $is = null;

		if ( null === $is ) {
			$is = Utilities::isAdminScreen( Main::$configs->root_menu_slug );
		}

		return $is;
	}
}
