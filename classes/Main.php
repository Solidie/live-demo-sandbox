<?php
/**
 * App initiator class
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox;

use Solidie_Sandbox\Helpers\_Array;
use Solidie_Sandbox\Helpers\Utilities;
use Solidie_Sandbox\Setup\AdminPage;
use Solidie_Sandbox\Setup\Database;
use Solidie_Sandbox\Setup\Dispatcher;
use Solidie_Sandbox\Setup\Scripts;

/**
 * Main class to initiate app
 */
class Main {
	/**
	 * Configs array
	 *
	 * @var object
	 */
	public static $configs;

	/**
	 * Initialize Plugin
	 *
	 * @param object $configs Plugin configs for start up
	 *
	 * @return void
	 */
	public function init( object $configs ) {

		// Store configs in runtime static property
		self::$configs           = $configs;
		self::$configs->dir      = dirname( $configs->file ) . '/';
		self::$configs->basename = plugin_basename( $configs->file );

		// Loading Autoloader
		spl_autoload_register( array( $this, 'loader' ) );

		// Retrieve plugin info from index
		$manifest      = _Array::getManifestArray( $configs->file, ARRAY_A );
		self::$configs = (object) array_merge( $manifest, (array) self::$configs );

		// Prepare the unique app name
		self::$configs->app_id = Utilities::getPluginId( self::$configs->url );

		// Register Activation/Deactivation Hook
		register_activation_hook( self::$configs->file, array( $this, 'activate' ) );
		register_deactivation_hook( self::$configs->file, array( $this, 'deactivate' ) );

		new Database();
		new Dispatcher();
		new Scripts();
		new AdminPage();
		
		do_action( 'slds_loaded' );
	}

	/**
	 * Autload classes
	 *
	 * @param string $class_name The class name to load file for
	 * @return void
	 */
	public function loader( $class_name ) {
		if ( class_exists( $class_name ) ) {
			return;
		}

		$class_name = preg_replace(
			array( '/([a-z])([A-Z])/', '/\\\/' ),
			array( '$1$2', DIRECTORY_SEPARATOR ),
			$class_name
		);

		$class_name = str_replace( 'Solidie_Sandbox' . DIRECTORY_SEPARATOR, 'classes' . DIRECTORY_SEPARATOR, $class_name );
		$file_name  = self::$configs->dir . $class_name . '.php';

		if ( file_exists( $file_name ) ) {
			require_once $file_name;
		}
	}

	/**
	 * Execute activation hook
	 *
	 * @return void
	 */
	public static function activate() {
		do_action( 'slds_activated' );
	}

	/**
	 * Execute deactivation hook
	 *
	 * @return void
	 */
	public static function deactivate() {
		do_action( 'slds_deactivated' );
	}
}
