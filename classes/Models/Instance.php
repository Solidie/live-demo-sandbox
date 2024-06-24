<?php
/**
 * Instance functionalities
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Models;

use Solidie_Sandbox\Main;

/**
 * Instance functions
 */
class Instance {

	private static $base_path = 'demo-parent';
	private static $conf_place = '// multisite_configs';

	public static function getBaseDir() {
		return ABSPATH . self::$base_path;
	}

	public static function multiSiteHomeURL( $path = '' ) {
		$path = $path ? trailingslashit( trim( '/', $path ) ) : '';
		return get_home_url() . '/' . self::$base_path . '/wordpress/' . $path;
	}
	
	public static function createMultiSite( $override = false ) {

        $db_name     = DB_NAME;
        $db_user     = DB_USER;
        $db_password = DB_PASSWORD;
		$db_host     = DB_HOST;
		$tbl_prefix  = Main::$configs->db_prefix;

        // Create subsite directory
        $subsite_path = self::getBaseDir();
		$exists       = file_exists( $subsite_path );

		if ( $exists ) {
			if ( ! $override ) {
				return array(
					'success' => false,
					'message' => __( 'Base instance directory exists already', 'live-demo-sandbox' )
				);
			}

			self::deleteMultiSite();
		}

		set_time_limit(60);

		wp_mkdir_p( $subsite_path );
			
        // Download WordPress
        $download_url = 'https://wordpress.org/latest.zip';
        $zip_file = __DIR__ . '/wordpress-latest.zip'; // $subsite_path . '/latest.zip';
        // copy( $download_url, $zip_file );

        // Unzip the file using WordPress function
		WP_Filesystem();
        $result = unzip_file( $zip_file, $subsite_path, null, array( 'method' => 'direct' ) );
        if ( is_wp_error( $result ) ) {
            return array(
				'success' => false,
				'message' => $result->get_error_message()
			);
        }
		
        // unlink( $zip_file );
        // rename( $subsite_path . '/wordpress', $subsite_path . '/site' );

        // Create wp-config.php
        $config_sample = file_get_contents( $subsite_path . '/wordpress/wp-config-sample.php' );
		$config_path   = $subsite_path . '/wordpress/wp-config.php';
		$prefix_line   = '$table_prefix = \''. $tbl_prefix .'\';';
		
        $config = str_replace(
            array( "database_name_here", "username_here", "password_here", "localhost", '$table_prefix = \'wp_\';' ),
            array( $db_name, $db_user, $db_password, $db_host, $prefix_line . PHP_EOL . "define( 'WP_ALLOW_MULTISITE', true );" . PHP_EOL . self::$conf_place ),
            $config_sample
        );
		if ( strpos( $config, $prefix_line ) === false ) {
			return array(
				'success' => false,
				'message' => __( 'Prefix variable not found in the config file', 'live-demo-sandbox' )
			);
		}

        file_put_contents( $config_path, $config );

        // Create the database and install WordPress
        // require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $db = new \wpdb( $db_user, $db_password, $db_name, $db_host );
        $db->query( "CREATE DATABASE IF NOT EXISTS $db_name" );

		// Finalize setup
		$pass = 'k';
		$payload = array(
			'weblog_title'    => __( 'Demo Multisite' ),
			'user_name'       => 'k',
			'admin_password'  => $pass,
			'admin_password2' => $pass,
			'pw_weak'         => 'on',
			'admin_email'     => 'jayedulk33@gmail.com',
			'blog_public'     => 0,
			'Submit'          => 'Install WordPress'
		);
		wp_remote_post( self::multiSiteHomeURL() . 'wp-admin/install.php?step=2', array( 'body' => $payload ) );

		// Install MU plugin that deploy custom theme and plugins
		$mu_dir = $subsite_path . '/wordpress/wp-content/mu-plugins';
		wp_mkdir_p( $mu_dir );
		copy( Main::$configs->dir . '/dist/libraries/snippets/ext-installer.php', $mu_dir . '/sandbox-extension-installer.php' );

        return array(
			'success' => true,
			'iframe_url' => self::multiSiteHomeURL()
		);
	}

	public static function deleteMultiSite() {
		
		// Delete all files
		FileManager::deleteDirectory( self::getBaseDir() );

		// Delete all db tables
	}

	public static function createSandbox() {
		
	}

	public static function deployNetworkConfigs() {

        $subsite_path = self::getBaseDir();
		$config_path  = $subsite_path . '/wordpress/wp-config.php';

		// Add multisite config in php file
		$multi_site = file_get_contents( Main::$configs->dir . 'dist/libraries/snippets/wp-config.php' );
        $config     = file_get_contents( $config_path );
		file_put_contents( $config_path, str_replace( self::$conf_place, $multi_site, $config ) );

		// Add htaccess for multisite
		$htaccess = file_get_contents( Main::$configs->dir . 'dist/libraries/snippets/.htaccess' );
        file_put_contents( $subsite_path . '/wordpress/.htaccess', $htaccess ); 
	}
}
