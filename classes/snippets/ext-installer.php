<?php
/**
 * Plugin Name: Live Demo Extension Loader
 * Description: Multisite theme and plugin installer, and sandbox creator for visitors.
 * Author: Solidie
 * Author URI: https://solidie.com
 * Version: 1.0.0
 *
 * @package live-demo-sandbox
 */

if ( ! defined( 'ABSPATH' ) ) { exit;
}

$slds_meta_data = '[]';
// dynamics
$slds_meta_data = json_decode( $slds_meta_data, true );

/**
 * This plugin will be copied to multiste setup, and will work there only.
 */

add_action( 'init', '_slds_redirect_home_to_demo' );
add_action( 'init', '_slds_active_state_logger' );
add_action( 'wp_head', '_slds_multisite_scripts_load' );
add_action( 'admin_head', '_slds_multisite_scripts_load' );
add_action( 'template_redirect', '_slds_handle_404_sandbox' );

// Call from sandbax instances and host
add_action( 'wp_ajax_slds_complete_setup', '_slds_complete_setup' );
add_action( 'wp_ajax_nopriv_slds_login_to_admin', '_slds_admin_login' );

// Call from Main site
add_action( 'wp_ajax_nopriv_slds_init_internal_session', 'slds_internal_session' );
add_action( 'wp_ajax_nopriv_slds_internal_request', 'slds_internal_request' );

/**
 * Get DB connection to the control panel website
 *
 * @return object
 */
function _slds_control_panel_db() {

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
function _slds_control_panel_get_configs( $key = null, $def = null ) {

	global $slds_meta_data;

	$control_panel = $slds_meta_data['control_panel_db'];

	$wpdb    = _slds_control_panel_db();
	$configs = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT option_value FROM {$control_panel['tables']['options']} WHERE option_name=%s",
			$control_panel['configs_option_name']
		)
	);

	$configs = maybe_unserialize( $configs );
	$configs = is_array( $configs ) ? $configs : array();

	return $key ? ( $configs[ $key ] ?? $def ) : $configs;
}


/**
 * Get how many minutes can a sandbox allowed to remain inactive. 
 *
 * @return int Total minutes
 */
function _slds_get_sandbox_inactivity_minutes() {
	
	$settings = _slds_control_panel_get_configs( 'settings', array() );
	$time     = ( int ) $settings['inactivity_time_allowed'] ?? 1;
	$period   = $settings['inactivity_period_allowed'] ?? 'hour';
	$minutes  = ( int ) ( $period === 'hour' ? $time*60 : $time );

	return $minutes > 0 ? $minutes : 40;
}

/**
 * Multisite home is not accessible for visitors, sandbox will be created for theme instantly and will be redirected to.
 *
 * @return void
 */
function _slds_redirect_home_to_demo() {
	// Redirect to demo if it is home, setup complete, and non admin
	if ( 
		! is_admin()
		&& 'GET' == sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) 
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
function _slds_active_state_logger() {

	if ( is_main_site() ) {
		return;
	}

	$site_id      = get_current_blog_id();
	$current_time = gmdate( 'Y-m-d H:i:s' );
	$inactivity   = sprintf( '+%s minutes', _slds_get_sandbox_inactivity_minutes() );
	$expires_at   = gmdate( 'Y-m-d H:i:s', strtotime( $inactivity , strtotime( $current_time ) ) );

	$wpdb = _slds_control_panel_db();
	global $slds_meta_data;
	
	// Update expires time in the control panel main site
	$wpdb->update(
		$slds_meta_data['control_panel_db']['tables']['sandboxes'],
		array( 'expires_at' => $expires_at ),
		array( 'site_id' => $site_id )
	);
}

/**
 * Handle 404 when sandbox is deleted.
 *
 * @return void
 */
function _slds_handle_404_sandbox() {

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
			$parsed = wp_parse_url( get_home_url() );

			// Define the site details
			$domain  = $parsed['host'];
			$path    = trim( $parsed['path'], '/' ) . '/sandbox-' . md5( time() . microtime() );
			$title   = 'New Subsite';
			$user_id = 1;
			$meta    = array(
				'public' => 1,
			);

			// Check if the path already exists
			if ( ! domain_exists( $domain, $path ) ) {

				$new_site_id = wpmu_create_blog( $domain, $path, $title, $user_id, $meta );

				if ( is_wp_error( $new_site_id ) ) {
					wp_send_json_error( array( 'message' => $new_site_id->get_error_message() ) );
				}

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

			$site_ids = $_POST['site_ids'] ?? '';
			$site_ids = is_array( $site_ids ) ? array_map( 'intval', $site_ids ) : array();

			foreach ( $site_ids as $id ) {
				wp_delete_site( $id );
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
function _slds_complete_setup() {
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
function _slds_admin_login() {
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
function _slds_multisite_scripts_load() {
	global $slds_meta_data;
	$slds_load_extensions = $slds_meta_data['extensions'];

	$intent          = '';
	$url_after_login = '';
	$redirect_url    = '';

	// Don't load scripts if setup is completed already.
	if ( ! is_main_site() || get_option( 'slds_setup_complete' ) ) {
		return;
	}

	if ( ! is_multisite() ) {

		if ( ! is_user_logged_in() ) {
			$intent          = 'login';
			$url_after_login = admin_url( 'network.php' );

		} elseif ( is_admin() ) {

			// Get the current screen object
			$screen = get_current_screen();

			// Check if we are on the network.php page
			if ( $screen ) {
				if ( 'network' === $screen->id && 'network' === $screen->base ) {
					$intent = 'setup';
				}
			}
		}
	} elseif ( ! is_user_logged_in() ) {
		$intent          = 'login';
		$url_after_login = admin_url( 'plugins.php' );

	} else {

		$found = false;

		if ( is_admin() ) {
			$screen = get_current_screen();
			if ( $screen ) {
				if ( 'plugins-network' === $screen->id && 'plugins-network' === $screen->base ) {
					$found  = true;
					$intent = 'extension';
				}
			}
		}

		if ( ! $found ) {
			$intent       = 'redirect';
			$redirect_url = get_home_url() . '/wp-admin/network/plugins.php';
		}
	}

	?>
	<script>
		const _slds_net_url                  = '<?php echo esc_url( $url_after_login ); ?>';
		const _slds_ajax_url                 = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
		const _slds_intent                   = '<?php echo esc_attr( $intent ); ?>';
		const _slds_exts                     = <?php echo wp_json_encode( $slds_load_extensions ); ?>;
		const {_slds_deployment_hook=()=>{}} = window.parent;

		function slds_fetch_request(action, data, callback) {
			
			data = {...data, action};
			const payload = new URLSearchParams();
			for ( k in data) {
				payload.append(k, data[k]);
			}
			
			fetch(_slds_ajax_url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				},
				body: payload
			})
			.then(response => {
				if (!response.ok) {
					throw new Error('Network response was not ok ' + response.statusText);
				}
				return response.json();
			})
			.then(data => {
				callback(data);
			})
			.catch(error => {
				alert('Request error');
			});
		}

		window.addEventListener('load', function(){

			switch(_slds_intent) {

				case 'login' :
					const data = new URLSearchParams();
					data.append('action', 'slds_login_to_admin');

					slds_fetch_request('slds_login_to_admin', {}, resp=>{
						if ( ! resp?.success ) {
							alert('Multisite admin login failed');
							return;
						}
						window.location.assign(_slds_net_url);
					});
					
					break;
				
				case 'setup' :
					const button = document.getElementById('submit');
					if ( button ) {
						button.click();
					} else {
						_slds_deployment_hook(3);
					}
					break;

				case 'extension' :
					let found = false;
					for ( let i=0; i<_slds_exts.length; i++ ) {
						const {dir_name, type, network=false} = _slds_exts[i];
						const anchor = window.jQuery(`[data-plugin^="${dir_name}/"] span.activate a`);
						if ( network && anchor.length ) {
							found = true;
							window.location.assign(anchor.attr('href'));
						}
					}

					if ( ! found ) {
						slds_fetch_request('slds_complete_setup', {}, resp=>{
							if ( ! resp?.success ) {
								alert('Could not mark as setup complete');
								return;
							}

							_slds_deployment_hook(5);
						});
					}
					break;

				case 'redirect' :
					window.location.assign('<?php echo esc_url( $redirect_url ); ?>');
					break;
			}
		});
	</script>
	<?php
}
