<?php
/**
 * App initiator class
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox;

use SolidieLib\_Array;
use SolidieLib\Utilities;
use Solidie_Sandbox\Setup\AdminPage;
use Solidie_Sandbox\Setup\Cron;
use Solidie_Sandbox\Setup\SandboxSetup;
use Solidie_Sandbox\Setup\Scripts;
use SolidieLib\Dispatcher;
use Solidie_Sandbox\Controllers\InstanceController;
use Solidie_Sandbox\Controllers\SandboxController;
use SolidieLib\DB;

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
		self::$configs->app_id = Utilities::getAppId( self::$configs->url );
		self::$configs->sql_path         = self::$configs->dir . 'dist/libraries/db.sql';
		self::$configs->activation_hook  = 'slds_activated';
		self::$configs->db_deployed_hook = 'slds_db_deployed';

		// Register Activation/Deactivation Hook
		register_activation_hook( self::$configs->file, array( $this, 'activate' ) );

		new DB( self::$configs );
		new Scripts();
		new AdminPage();
		new SandboxSetup();
		new Cron();

		new Dispatcher(
			self::$configs->app_id,
			array(
				InstanceController::class,
				SandboxController::class,
			)
		);
	}

	/**
	 * Autload classes
	 *
	 * @param  string $class_name The class name to load file for
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
			include_once $file_name;
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
}
