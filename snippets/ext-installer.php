<?php

if ( ! defined( 'ABSPATH' ) ) { exit;
}

const SLDS_EXT_INSTALLER_VERSION = '1.0.1';

$slds_meta_data = '[]';
// dynamics
$slds_meta_data                   = json_decode( $slds_meta_data, true );
$slds_meta_data['auto_user_info'] = 'slds_demo_user_auto_created';

/**
 * This plugin will be copied to multiste setup, and will work there only.
 */

add_action( 'init', 'slds_redirect_home_to_demo' );
add_action( 'init', 'slds_active_state_logger' );
add_action( 'wp_enqueue_scripts', 'slds_multisite_scripts_load' );
add_action( 'admin_enqueue_scripts', 'slds_multisite_scripts_load' );
add_action( 'template_redirect', 'slds_handle_404_sandbox' );

// Call from sandbax instances and host
add_action( 'wp_ajax_slds_complete_setup', 'slds_complete_setup' );
add_action( 'wp_ajax_nopriv_slds_login_to_admin', 'slds_admin_login' );

// Call from Main site
add_action( 'wp_ajax_nopriv_slds_init_internal_session', 'slds_internal_session' );
add_action( 'wp_ajax_nopriv_slds_internal_request', 'slds_internal_request' );

/**
 * Get DB connection to the control panel website
 *
 * @return object
 */
function slds_control_panel_db() {

	global $slds_meta_data;
	$configs = $slds_meta_data['control_panel_db'];

	return new \wpdb(
		$configs['user'],
		$configs['pass'],
		$configs['name'],
		$configs['host']
	);
}

/**
 * Get configs aray from control panel site
 *
 * @param string $key To get specific value from congis
 * @param mixed  $def Default value to return for singular value
 *
 * @return mixed
 */
function slds_control_panel_get_configs( $key = null, $def = null ) {

	global $slds_meta_data;

	$control_panel = $slds_meta_data['control_panel_db'];

	$wpdb    = slds_control_panel_db();
	$configs = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT option_value FROM {$control_panel['tables']['options']} WHERE option_name=%s",
			$control_panel['configs_option_name']
		)
	);

	$configs = maybe_unserialize( $configs );
	$configs = is_array( $configs ) ? $configs : array();
	$configs = $configs[ $slds_meta_data['host_id'] ] ?? array();

	return $key ? ( $configs[ $key ] ?? $def ) : $configs;
}


/**
 * Get how many minutes can a sandbox allowed to remain inactive.
 *
 * @return int Total minutes
 */
function slds_get_sandbox_inactivity_minutes() {

	$settings = slds_control_panel_get_configs( 'settings', array() );
	$time     = (int) $settings['inactivity_time_allowed'] ?? 1;
	$period   = $settings['inactivity_period_allowed'] ?? 'hour';
	$minutes  = (int) ( 'hour' === $period ? $time * 60 : $time );

	return $minutes > 0 ? $minutes : 60;
}

/**
 * Multisite home is not accessible for visitors, sandbox will be created for theme instantly and will be redirected to.
 *
 * @return void
 */
function slds_redirect_home_to_demo() {
	// Redirect to demo if it is home, setup complete, and non admin
	if ( ! is_admin()
		&& 'GET' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) )
		&& is_main_site()
		&& get_option( 'slds_setup_complete' )
		&& ! current_user_can( 'manage_options' )
	) {
		global $slds_meta_data;
		wp_safe_redirect( $slds_meta_data['sandbox_init_url'] );
		exit;
	}
}

/**
 * Store site active state
 *
 * @return void
 */
function slds_active_state_logger() {

	if ( is_main_site() ) {
		return;
	}

	$site_id      = get_current_blog_id();
	$current_time = gmdate( 'Y-m-d H:i:s' );
	$inactivity   = sprintf( '+%s minutes', slds_get_sandbox_inactivity_minutes() );
	$expires_at   = gmdate( 'Y-m-d H:i:s', strtotime( $inactivity, strtotime( $current_time ) ) );

	$wpdb = slds_control_panel_db();
	global $slds_meta_data;

	// Update expires time in the control panel main site
	$wpdb->update(
		$slds_meta_data['control_panel_db']['tables']['sandboxes'],
		array(
			'expires_at' => $expires_at,
			'last_hit'   => $current_time,
		),
		array( 'site_id' => $site_id )
	);

	if (
		! is_user_logged_in() &&
		! is_admin() &&
		'GET' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) )
	) {

		$auto_user = get_option( $slds_meta_data['auto_user_info'] );
		$auto_user = is_array( $auto_user ) ? $auto_user : array();

		if ( ! empty( $auto_user['user_id'] ) &&
			( $auto_user['auto_login'] ?? false ) &&
			! ( $auto_user['logged_in'] ?? false )
		) {

			$auto_user['logged_in'] = true;
			update_option( $slds_meta_data['auto_user_info'], $auto_user, true );

			wp_set_current_user( $auto_user['user_id'] );
			wp_set_auth_cookie( $auto_user['user_id'] );
			wp_safe_redirect( get_home_url() );
			exit;
		}
	}
}

/**
 * Handle 404 when sandbox is deleted.
 *
 * @return void
 */
function slds_handle_404_sandbox() {

	if ( ! is_404() ) {
		return;
	}

	$parsed = wp_parse_url( ( is_ssl() ? 'https' : 'http' ) . '://' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) );
	$path   = $parsed['path'];
	$path   = trim( $path, '/' );
	$path   = explode( '/', $path );
	$path   = end( $path );

	if ( ! empty( $path ) && strpos( $path, 'sandbox-' ) === 0 ) {
		global $slds_meta_data;
		wp_safe_redirect( $slds_meta_data['sandbox_init_url'], 301 );
		exit;
	}
}

/**
 * Create nonce for internal call
 *
 * @return void
 */
function slds_internal_session() {

	if ( ! is_main_site() ) {
		return;
	}

	file_put_contents( sys_get_temp_dir() . '/slds-nonce.tmp', wp_create_nonce( 'slds_internal_nonce' ) );
	wp_send_json_success();
}

/**
 * Activate plugin by directory name
 *
 * @param string $plugin_directory The plugin dir string
 * @return void
 */
function slds_activate_plugin_by_directory( $plugin_directory ) {

	// Path to the plugin directory
	$plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_directory;

	// Make sure the directory exists
	if ( ! is_dir( $plugin_dir ) ) {
		return;
	}

	// Scan the plugin directory for files
	$plugin_files = scandir( $plugin_dir );
	if ( false === $plugin_files ) {
		return;
	}

	// Iterate over the files to find the main plugin file
	$plugin_file = '';
	foreach ( $plugin_files as $file ) {
		if ( substr( $file, -4 ) === '.php' ) {
			$file_path = $plugin_dir . '/' . $file;
			$file_data = get_file_data( $file_path, array( 'Plugin Name' => 'Plugin Name' ), 'plugin' );
			if ( ! empty( $file_data['Plugin Name'] ) ) {
				$plugin_file = $plugin_directory . '/' . $file;
				break;
			}
		}
	}

	// If a main plugin file was found, activate the plugin
	if ( $plugin_file && ! is_plugin_active( $plugin_file ) ) {
		activate_plugin( $plugin_file );
	}
}

/**
 * Handle internal request
 *
 * @return void
 */
function slds_internal_request() {

	if ( ! is_main_site() ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'slds_internal_nonce' ) ) {
		wp_send_json_error(
			array(
				'message' => 'Nonce not matched',
			)
		);
	}

	$action = sanitize_text_field( wp_unslash( $_POST['slds_action'] ?? '' ) );

	switch ( $action ) {

		case 'create_sandbox':
			global $slds_meta_data;

			$options = slds_control_panel_get_configs( 'settings', array() );
			$parsed  = wp_parse_url( get_home_url() );

			// Define the site details
			$domain  = $parsed['host'];
			$unique  = md5( time() . microtime() );
			$path    = trim( $parsed['path'], '/' ) . '/sandbox-' . $unique;
			$title   = $options['sandbox_site_title'] ?? 'Sandbox Site';
			$user_id = 1;
			$meta    = array(
				'public' => 1,
			);

			// Check if the path already exists
			if ( ! domain_exists( $domain, $path ) ) {

				// Create subsite
				$new_site_id = wpmu_create_blog( $domain, $path, $title, $user_id, $meta );
				if ( is_wp_error( $new_site_id ) ) {
					wp_send_json_error( array( 'message' => $new_site_id->get_error_message() ) );
				}

				// Switch to the new site to store user info
				switch_to_blog( $new_site_id );

				// Activate theme and plugins which are not network wide
				foreach ( $slds_meta_data['extensions'] as $ext ) {

					// Activate theme
					if ( 'theme' === $ext['type'] ) {
						$current_theme = wp_get_theme();
						if ( $current_theme->get_stylesheet() !== $ext['dir_name'] ) {
							switch_theme( $ext['dir_name'] );
						}
					} elseif ( 'plugin' === $ext['type'] && false === ( $ext['network'] ?? true ) ) {
						slds_activate_plugin_by_directory( $ext['dir_name'] );
					}
				}

				// Create a user if role is defined
				if ( ! empty( $options['new_user_role'] ) ) {

					$string_source = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$%&*?';

					// Create a new user
					$user_id = wp_insert_user(
						array(
							'user_login' => $unique,
							'user_pass'  => substr( str_shuffle( str_repeat( $string_source, ceil( 16 / strlen( $string_source ) ) ) ), 1, 16 ),
							'user_email' => $unique . '@example.com',
							'first_name' => 'John',
							'last_name'  => 'Doe',
							'role'       => $options['new_user_role'],
						)
					);

					// Save auto login flags in option
					if ( ! empty( $user_id ) && ! is_wp_error( $user_id ) ) {
						update_option(
							$slds_meta_data['auto_user_info'],
							array(
								'user_id'    => $user_id,
								'logged_in'  => false,
								'auto_login' => $options['auto_login_new_user'],
							),
							true
						);
					}
				}

				do_action( 'slds_sandbox_created', $new_site_id );

				// Get back to current site
				restore_current_blog();

				// Send response back to control panel site
				wp_send_json_success(
					array(
						'site_id'    => $new_site_id,
						'site_path'  => $path,
						'site_title' => $title,
					)
				);

			} else {
				wp_send_json_error( array( 'message' => 'The subsite path already exists.' ) );
			}

			break;

		case 'delete_sandbox':
			$site_ids = wp_unslash( $_POST['site_ids'] ?? '' );
			$site_ids = is_array( $site_ids ) ? array_map( 'sanitize_text_field', $site_ids ) : array();

			foreach ( $site_ids as $id ) {

				$site_id = (int) $id;

				if ( $site_id ) {
					wp_delete_site( $site_id );
				}
			}

			wp_send_json_success( array( 'message' => 'Sandbox deleted successfully' ) );

			break;
	}
}

/**
 * Mark multisite as completed
 *
 * @return void
 */
function slds_complete_setup() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Access denied!' ) );
	}

	update_option( 'slds_setup_complete', true, true );
	wp_send_json_success();
}

/**
 * Dynamically login to admin during setting up multisite
 *
 * @return void
 */
function slds_admin_login() {
	if ( get_option( 'slds_setup_complete' ) ) {
		wp_send_json_error( array( 'message' => 'This action is expired as multisite setup completed' ) );
	}

	wp_set_current_user( 1 );
	wp_set_auth_cookie( 1 );
	wp_send_json_success( array( 'message' => 'Login successful' ) );
}

/**
 * Load necessary script to handle multsite setup
 *
 * @return void
 */
function slds_multisite_scripts_load() {
	global $slds_meta_data;
	$slds_load_extensions = $slds_meta_data['extensions'];

	$intent          = '';
	$url_after_login = '';
	$redirect_url    = '';

	// Don't load scripts if setup is completed already.
	if ( ! is_main_site() || get_option( 'slds_setup_complete' ) ) {
		return;
	}

	// If not multisite, then it is just a normal WP setup
	if ( ! is_multisite() ) {

		// So first of all login, and then go to network page to set up multisite
		if ( ! is_user_logged_in() ) {
			$intent          = 'login';
			$url_after_login = admin_url( 'network.php' );

		} elseif ( is_admin() ) {
			// Go to network setup page
			$screen = get_current_screen();
			if ( $screen ) {
				if ( 'network' === $screen->id && 'network' === $screen->base ) {
					$intent = 'setup';
				}
			}
		}
	} elseif ( ! is_user_logged_in() ) {
		// If it is multisite but not logged in, it means setup complete.
		// Now login to multsite admin using ajax and load the plugins page to activate network wide plugins.
		$intent          = 'login';
		$url_after_login = admin_url( 'plugins.php' );

	} else {

		// If it is multisite and admin is logged in, then go to either plugins page or themes to enable network wide.
		// This condition will be true multiple times per plugin/theme.
		$found = false;

		// If it is admin dashboard, then check if it is plugins or themes page. If none, then redirect to plugins forcefully.
		if ( is_admin() ) {
			$screen = get_current_screen();
			if ( $screen ) {
				if ( 'plugins-network' === $screen->id && 'plugins-network' === $screen->base ) {
					$found  = true;
					$intent = 'plugins';
				}

				if ( 'themes-network' === $screen->id && 'themes-network' === $screen->base ) {
					$found  = true;
					$intent = 'themes';
				}
			}
		}

		// It means neither plugins not theme page. So forcefully load plugins page.
		// If there is no plugin to activate network wide, it will load themes page subsequently.
		if ( ! $found ) {
			$intent       = 'redirect';
			$redirect_url = get_home_url() . '/wp-admin/network/plugins.php';
		}
	}

	$variables = array(
		'url_after_login' => $url_after_login,
		'themes_url'      => get_home_url() . '/wp-admin/network/themes.php',
		'ajax_url'        => admin_url( 'admin-ajax.php' ),
		'intent'          => $intent,
		'extensions'      => $slds_load_extensions,
		'redirect_to'     => $redirect_url,
	);

	wp_enqueue_script( 'slds-installer-script', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'ext-installer.js', array( 'jquery' ), SLDS_EXT_INSTALLER_VERSION, false );
	wp_localize_script( 'slds-installer-script', 'slds_variables', $variables );
}
