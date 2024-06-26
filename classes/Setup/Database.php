<?php
/**
 * Database importer for Sandbox
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Setup;

use Solidie_Sandbox\Main;
use Solidie_Sandbox\Models\DB;

/**
 * The database manager class
 */
class Database {

	const DB_VERSION_KEY = 'slds_db_version';

	/**
	 * Constructor that registeres hook to deploy database on plugin activation
	 *
	 * @return void
	 */
	public function __construct() {
		$this->prepareTableNames();
		add_action( 'slds_activated', array( $this, 'importDB' ) );
		add_action( 'admin_init', array( $this, 'importDBOnUpdate' ), 0 );
	}

	/**
	 * Trigger import db function on plugin update
	 *
	 * @return void
	 */
	public function importDBOnUpdate() {

		$last_version = get_option( self::DB_VERSION_KEY );
		
		if ( empty( $last_version ) || version_compare( $last_version, Main::$configs->version, '<' ) ) {
			$this->importDB();
		}
	}

	/**
	 * Import database
	 *
	 * @return void
	 */
	public function importDB() {
		
		$sql_path = Main::$configs->dir . 'dist/libraries/db.sql';
		DB::import( file_get_contents( $sql_path ) );
		update_option( self::DB_VERSION_KEY, Main::$configs->version, true );

		do_action( 'slds_db_deployed' );
	}

	/**
	 * Add table names into wpdb object
	 *
	 * @return void
	 */
	private function prepareTableNames() {
		global $wpdb;

		// WP and Plugin prefix
		$prefix = $wpdb->prefix . Main::$configs->db_prefix;
		$wpdb->slds_sandboxes = $prefix . 'sandboxes';
	}
}
