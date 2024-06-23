<?php
/**
 * Instance functionalities
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Models;

/**
 * Instance functions
 */
class Instance {

	public static function getBaseDir() {
		return 'demo-parent';
	}

	public static function getHomeURL() {
		return get_home_url() . '/' . self::getBaseDir();
	}
	
	public static function createMultiSite( $override = false ) {

       /*  $db_name     = DB_NAME;
        $db_user     = DB_USER;
        $db_password = DB_PASSWORD;
		$db_host     = DB_HOST;
		$tbl_prefix  = 'slds_demo_';

        // Create subsite directory
        $subsite_path = ABSPATH . self::getBaseDir();
		$exists       = file_exists( $subsite_path );

		if ( $exists ) {
			if ( ! $override ) {
				return __( 'Base instance directory exists already', 'live-demo-sandbox' );
			}
		}

		wp_mkdir_p( $subsite_path );
			
        // Download WordPress
        $download_url = 'https://wordpress.org/latest.zip';
        $zip_file = __DIR__ . '/wordpress-latest.zip'; // $subsite_path . '/latest.zip';
        // copy( $download_url, $zip_file );

        // Unzip the file using WordPress function
		WP_Filesystem();
        $result = unzip_file( $zip_file, $subsite_path, null, array( 'method' => 'direct' ) );
        if ( is_wp_error( $result ) ) {
            return $result->get_error_message(); // __( 'Failed to unzip the WordPress package.', 'live-demo-sandbox' );
        }
		
        // unlink( $zip_file );
        rename( $subsite_path . '/wordpress', $subsite_path . '/site' );

        // Create wp-config.php
        $config_sample = file_get_contents( $subsite_path . '/site/wp-config-sample.php' );
		$prefix_line   = '$table_prefix = \''. $tbl_prefix .'\';';
        $config        = str_replace(
            array( "database_name_here", "username_here", "password_here", "localhost", '$table_prefix = \'wp_\';' ),
            array( $db_name, $db_user, $db_password, $db_host, $prefix_line ),
            $config_sample
        );
		if ( strpos( $config, $prefix_line ) === false ) {
			return __( 'Prefix variable not found in the config file', 'live-demo-sandbox' );
		}

        file_put_contents( $subsite_path . '/site/wp-config.php', $config );

        // Create the database and install WordPress
        // require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $db = new \wpdb( $db_user, $db_password, $db_name, $db_host );
        $db->query( "CREATE DATABASE IF NOT EXISTS $db_name" ); */

		// Finalize setup
		$pass = 'OxzCzuVt6)Txu#Yasw';
		$payload = array(
			'weblog_title'    => __( 'Demo Multisite' ),
			'user_name'       => 'demo',
			'admin_password'  => $pass,
			'admin_password2' => $pass,
			'admin_email'     => 'jayedulk33@gmail.com',
			'blog_public'     => 0,
			'Submit'          => 'Install WordPress'
		);
		
		$resp = wp_remote_post( self::getHomeURL() . '/site/wp-admin/install.php?step=2', array( 'body' => $payload ) );

		error_log( var_export( $resp, true ) );

        return true;
	}

	public static function deleteMultiSite() {
		
		// Delete all files
		FileManager::deleteDirectory( self::getBaseDir() );

		// Delete all db tables
	}

	public static function createSandbox() {
		
	}
}
