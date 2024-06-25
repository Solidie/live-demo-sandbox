<?php
/**
 * Instance functionalities
 *
 * @package live-demo-sandbox
 */

namespace Solidie_Sandbox\Models;

use Solidie_Sandbox\Helpers\_Array;
use Solidie_Sandbox\Main;

/**
 * Instance functions
 */
class Instance {

	const OPTION_KEY = 'slds_multisite_configs';
	const CONF_PLACE = '// multisite_configs';

	private $configs;
	private $db;

	public function __construct( $configs = null ) {

		$this->configs = $configs ? $configs :  $this->getConfigs();
		
        $db_name     = $this->configs['db_name'];
        $db_user     = $this->configs['db_user'];
        $db_password = $this->configs['db_password'];
		$db_host     = $this->configs['db_host'];
        $this->db    = new \wpdb( $db_user, $db_password, $db_name, $db_host );
	}

	private function getBaseDir() {
		return empty( $this->configs['directory_name'] ) ? null : ABSPATH . $this->configs['directory_name'];
	}

	public function multiSiteHomeURL() {
		return ! empty( $this->configs['directory_name'] ) ? get_home_url() . '/' .  $this->configs['directory_name'] . '/wordpress/' : null;
	}

	public static function getSourcePath() {
		return wp_upload_dir()['basedir'] . '/slds-wordpress-latest.zip';
	}

	/**
	 * Create multisite using configs
	 *
	 * @param array $configs
	 * @return array
	 */
	public function createMultiSite() {

		$site_configs = $this->configs;

        $db_name     = $site_configs['db_name'];
        $db_user     = $site_configs['db_user'];
        $db_password = $site_configs['db_password'];
		$db_host     = $site_configs['db_host'];
		$tbl_prefix  = $site_configs['table_prefix'];

        // Create subsite directory
        $subsite_path = $this->getBaseDir();
		$exists       = file_exists( $subsite_path );

		if ( $exists ) {
			if ( ! ( $site_configs['override'] ?? false ) ) {
				return array(
					'success'   => false,
					'duplicate' => true,
					'message'   => __( 'The directory exists already', 'live-demo-sandbox' )
				);
			}

			$this->deleteMultiSite();
		}

        // Download WordPress
        $zip_file =  self::getSourcePath();
		if ( ! file_exists( $zip_file ) ) {
			return array(
				'success' => false,
				'message' => __( 'WordPress source file not found!', 'live-demo-sandbox' ),
			);
		}

		if ( ! empty( $this->db->last_error ) ) {
			return array(
				'success' => false,
				'message' => __( 'Could not connect to database', 'live-demo-sandbox' ),
			);
		}

        // Unzip the file using WordPress function
		set_time_limit(160);
		wp_mkdir_p( $subsite_path );
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
            array( $db_name, $db_user, $db_password, $db_host, $prefix_line . PHP_EOL . "define( 'WP_ALLOW_MULTISITE', true );" . PHP_EOL .  self::CONF_PLACE ),
            $config_sample
        );
		if ( strpos( $config, $prefix_line ) === false ) {
			return array(
				'success' => false,
				'message' => __( 'Prefix variable not found in the config file', 'live-demo-sandbox' )
			);
		}

        file_put_contents( $config_path, $config );

		// Finalize setup
		$payload = array(
			'weblog_title'    => $site_configs['site_title'],
			'admin_email'     => $site_configs['admin_email'],
			'user_name'       => $site_configs['admin_username'],
			'admin_password'  => $site_configs['admin_password'],
			'admin_password2' => $site_configs['admin_password'],
			'pw_weak'         => 'on',
			'blog_public'     => 0,
			'Submit'          => 'Install WordPress'
		);
		wp_remote_post(  $this->multiSiteHomeURL() . 'wp-admin/install.php?step=2', array( 'body' => $payload ) );

		// Install necessary plugins into plugins directory
		$this->installExtensions( $site_configs );

		// Save the configs into database
		$this->updateConfigs();

        return array(
			'success' => true,
			'iframe_url' =>  $this->multiSiteHomeURL()
		);
	}

	private function installExtensions( $site_configs ) {

		$content_dir = $this->getBaseDir() . '/wordpress/wp-content';

		// Install custom theme and plugins
		$extensions = _Array::getArray( $site_configs['plugins'] ?? null ); 
		$extensions = array_column( $extensions, 'file_id' ); 
		$extensions = array_map( 'intval', array_filter( $extensions, 'is_numeric' ) );
		$extensions = array_map( function( $file_id ) {
			return array( 
				'file_id' => $file_id, 
				'type'    => 'plugin',
				'network' => true,
			); 
		}, $extensions );

		// Add theme to the 
		if ( is_array( $site_configs['theme'] ?? null ) && isset( $site_configs['theme']['file_id'] ) ) {
			$extensions[] = array(
				'file_id' => $site_configs['theme']['file_id'],
				'type'    => 'theme',
				'network' => true,
			);
		}

		foreach ( $extensions as $index => $extension ) {
			$file_path = get_attached_file( $extension['file_id'] );
			$extensions[ $index ]['dir_name'] = FileManager::getOnlyFolderNameInZip( $file_path );
			$target_dir = $extension['type'] == 'plugin' ? 'plugins' : 'themes';
			unzip_file( $file_path, $content_dir . '/' . $target_dir, null, array( 'method' => 'direct' ) );
		}

		// Install MU plugin that activates custom theme and plugins
		$mu_dir = $content_dir . '/mu-plugins';
		wp_mkdir_p( $mu_dir );
		
		$ext_codes = file_get_contents( Main::$configs->dir . '/dist/libraries/snippets/ext-installer.php' );
		$ext_codes = str_replace( '// dynamics', '$slds_load_extensions = \'' . wp_json_encode( $extensions ) . '\';', $ext_codes );
		file_put_contents( $mu_dir . '/sandbox-extension-installer.php', $ext_codes );
	}

	public function deleteMultiSite() {
		
		// Delete all files
		FileManager::deleteDirectory(  $this->getBaseDir() );

		// Delete all db tables
		$tables = $this->db->get_col(
			$this->db->prepare(
				"SHOW TABLES LIKE %s",
				$this->db->esc_like( $this->configs['table_prefix'] ) . '%'
			)
		);

		if ( ! empty( $tables ) && is_array( $tables ) ) {
			foreach ($tables as $table) {
				$this->db->query( "DROP TABLE IF EXISTS {$table}" );
			}
		}

		delete_option( self::OPTION_KEY );
	}

	private function updateConfigs( $configs = array() ) {

		$options = array_merge( $this->configs, $configs );

		if ( isset( $options['plugins'] ) ) {
			unset( $options['plugins'] );
		}

		if ( isset( $options['theme'] ) ) {
			unset( $options['theme'] );
		}

		update_option( self::OPTION_KEY, $options );
	}

	/**
	 * Get the multisite config from option
	 *
	 * @return array
	 */
	public static function getConfigs() {

		$defaults = array(
        	'db_name'      => DB_NAME,
        	'db_user'      => DB_USER,
        	'db_password'  => DB_PASSWORD,
			'db_host'      => DB_HOST,
			'table_prefix' => Main::$configs->db_prefix . 'sandbox_instance_'
		);

		$option = _Array::getArray( get_option(  self::OPTION_KEY ) );

		return array_merge( $defaults, $option );
	}

	public function deployNetworkConfigs() {

        $subsite_path =  $this->getBaseDir();
		$config_path  = $subsite_path ? $subsite_path . '/wordpress/wp-config.php' : null;

		if ( ! $config_path || ! file_exists( $config_path ) ) {
			return;
		}

		$home_url    = $this->multiSiteHomeURL();
		$parsed      = parse_url( $home_url );
		$domain_name = $parsed['host'];
		$site_path   = $parsed['path'];

		// Add dynamics to multi site configs
		$multi_site = file_get_contents( Main::$configs->dir . 'dist/libraries/snippets/wp-config.php' );
		$multi_site = str_replace( '__site_path__', $site_path, $multi_site );
		$multi_site = str_replace( '__domain_name__', $domain_name, $multi_site );
		
		// Get current wp-config and add the multisite configs
        $config = file_get_contents( $config_path );
		file_put_contents( $config_path, str_replace( self::CONF_PLACE, $multi_site, $config ) );

		// Add htaccess for multisite
		$htaccess = file_get_contents( Main::$configs->dir . 'dist/libraries/snippets/.htaccess' );
		$htaccess = str_replace( '__site_path__', $site_path, $htaccess );
        file_put_contents( $subsite_path . '/wordpress/.htaccess', $htaccess ); 
	}

	public function markMultiSiteCompleted() {
		$this->updateConfigs(
			array( 
				'setup_complete' => true,
				'created_at'     => strtotime( gmdate( 'Y-m-d H:i:s' ) )
			) 
		);
	}
}
