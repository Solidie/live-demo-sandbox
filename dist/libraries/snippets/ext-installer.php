<?php
	/*
	Plugin Name: Live Demo Extension Loader
	Description: Multisite theme and plugin installer, and sandbox creator for visitors.
	Author: Solidie
	Author URI: https://solidie.com
	Version: 1.0.0
*/

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * This plugin will be copied to multiste setup, and will work there only.
 */

add_action( 'wp_head', '_slds_multisite_scripts_load' );
add_action( 'admin_head', '_slds_multisite_scripts_load' );

add_action( 'wp_ajax_slds_complete_setup', '_slds_complete_setup' );
add_action( 'wp_ajax_slds_login_to_admin', '_slds_admin_login' );
add_action( 'wp_ajax_nopriv_slds_login_to_admin', '_slds_admin_login' );
add_action( 'wp_ajax_nopriv_slds_create_multisite', '_slds_create_multisite' );

function _slds_create_multisite() {
	
	$parsed = parse_url( get_home_url() );

	// Define the site details
	$domain  = $parsed['host'];
	$path    = 'sandbox-' . md5( time() . microtime() );
	$title   = 'New Subsite';
	$user_id = 1;
	$meta    = array(
		'public' => 1
	);

	// Check if the path already exists
	if ( domain_exists( $domain, $path ) === false) {

		$new_site_id = wpmu_create_blog( $domain, $path, $title, $user_id, $meta );

		if ( is_wp_error( $new_site_id ) ) {
			wp_send_json_error( array( 'message' => $new_site_id->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'site_id'    => $new_site_id,
				'site_path'  => $path,
				'site_title' => $title
			)
		);

	} else {
		wp_send_json_error( 'The subsite path already exists.', 'live-demo-sandbox' );
	}
}

function _slds_complete_setup() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Access denied!', 'live-demo-sandbox' ) ) );
	}

	update_option( 'slds_setup_complete', true, true );
	wp_send_json_success();
}

function _slds_admin_login () {

	if ( get_option( 'slds_setup_complete' ) ) {
		wp_send_json_error( array( 'message' => 'This action is expired as multisite setup completed' ) );
	}
	
	wp_set_current_user( 1 );
	wp_set_auth_cookie( 1 );
	wp_send_json_success( array( 'message' => 'Login successful' ) );
}

function _slds_multisite_scripts_load() {
	
	$slds_load_extensions = '[]';
	// dynamics

	$intent          = '';
	$url_after_login = '';
	$redirect_url    = '';
	$setup_complete  = get_option( 'slds_setup_complete' ) ;

	if ( ! is_multisite() ) {

		if ( ! is_user_logged_in() ) {
			$intent = 'login';
			$url_after_login = admin_url( 'network.php' );

		} else if ( is_admin() ) {

			// Get the current screen object
			$screen = get_current_screen();

			// Check if we are on the network.php page
			if ( $screen ) {
				if ( $screen->id === 'network' && $screen->base === 'network' ) {
					$intent = 'setup';
				}
			}
		}
	} else if ( ! is_user_logged_in() ) {
		$intent = 'login';
		$url_after_login = admin_url( 'plugins.php' );

	} else if ( ! $setup_complete ) {
		
		$found = false;

		if ( is_admin() ) {
			$screen = get_current_screen();
			if ( $screen ) {
				if ( $screen->id === 'plugins-network' && $screen->base === 'plugins-network' ) {
					$found = true;
					$intent = 'extension';
				}
			}
		}

		if ( ! $found ) {
			$intent = 'redirect';
			$redirect_url = get_home_url() . '/wp-admin/network/plugins.php';
		}
	} 

	?>
	<script>
		const _slds_net_url                  = '<?php echo $url_after_login; ?>';
		const _slds_ajax_url                 = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
		const _slds_intent                   = '<?php echo $intent; ?>';
		const _slds_exts                     = <?php echo $slds_load_extensions; ?>;
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
						button.scrollIntoView({ behavior: "smooth", block: "center", inline: "center" });
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
					window.location.assign('<?php echo $redirect_url; ?>');
					break;
			}
		});
	</script>
	<?php
}
