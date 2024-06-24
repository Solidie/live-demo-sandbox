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

add_action( 'wp_footer', '_slds_multisite_scripts_load' );
add_action( 'admin_footer', '_slds_multisite_scripts_load' );

add_action( 'wp_ajax_slds_login_to_admin', '_slds_admin_login' );
add_action( 'wp_ajax_nopriv_slds_login_to_admin', '_slds_admin_login' );

function _slds_admin_login () {
	if ( get_option( 'slds_multisite_setup_complete' ) ) {
		wp_send_json_error( array( 'message' => 'This action is expired as multisite setup completed' ) );
	}
	wp_set_current_user( 1 );
	wp_set_auth_cookie( 1 );
	wp_send_json_success( array( 'message' => 'Login successful' ) );
}

function _slds_multisite_scripts_load() {
	
	$intent          = '';
	$url_after_login = '';
	$ext_map         = array();
	$redirect_url    = '';

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
	} else {
		
		$found = false;

		if ( is_admin() ) {
			$screen = get_current_screen();
			if ( $screen ) {
				if ( $screen->id === 'plugins-network' && $screen->base === 'plugins-network' ) {
					$found = true;
					$intent = 'extension';
					$ext_map = array(
						array(
							'basename' => 'akismet/akismet.php',
							'network'  => true,
							'type'     => 'plugin'
						)
					);
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
		const _slds_net_url  = '<?php echo $url_after_login; ?>';
		const _slds_ajax_url = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
		const _slds_intent   = '<?php echo $intent; ?>';
		const _slds_exts     = <?php echo wp_json_encode( $ext_map ); ?>;

		window.addEventListener('load', function(){

			switch(_slds_intent) {

				case 'login' :
					const data = new URLSearchParams();
        			data.append('action', 'slds_login_to_admin');

					fetch(_slds_ajax_url, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						},
						body: data
					})
					.then(response => {
						if (!response.ok) {
							throw new Error('Network response was not ok ' + response.statusText);
						}
						return response.json();
					})
					.then(data => {
						if ( ! data?.success ) {
							alert('Multisite admin login failed');
							return;
						}
						window.location.assign(_slds_net_url);
					})
					.catch(error => {
						alert('Multisite admin login error');
					});
					break;
				
				case 'setup' :
					const button = document.getElementById('submit');
					if ( button ) {
						button.scrollIntoView({ behavior: "smooth", block: "center", inline: "center" });
						button.click();
					} else {
						if ( window.parent?.window._slds_deployment_hook ) {
							window.parent.window._slds_deployment_hook(3);
						} else {
							alert('This page is supposed to be loaded in iframe');
						}
					}
					break;

				case 'extension' :
					if ( ! window.parent ) {
						break;
					}

					let found = false;
					for ( let i=0; i<_slds_exts.length; i++ ) {
						const {basename, type, network} = _slds_exts[i];
						const anchor = window.jQuery(`[data-plugin="${basename}"] span.activate a`);
						if ( anchor.length ) {
							found = true;
							window.location.assign(anchor.attr('href'));
						}
					}

					if ( ! found ) {
						window.parent.window._slds_deployment_hook(5);
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
