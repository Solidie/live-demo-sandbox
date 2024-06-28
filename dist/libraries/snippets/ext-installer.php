<?php
/*
    Plugin Name: Live Demo Extension Loader
    Description: Multisite theme and plugin installer, and sandbox creator for visitors.
    Author: Solidie
    Author URI: https://solidie.com
    Version: 1.0.0
*/

if (! defined('ABSPATH') ) { exit;
}

$slds_meta_data = '[]';
// dynamics
$slds_meta_data = json_decode( $slds_meta_data, true );

/**
 * This plugin will be copied to multiste setup, and will work there only.
 */

add_action( 'init', '_slds_redirect_home_to_demo' );
add_action('wp_head', '_slds_multisite_scripts_load');
add_action('admin_head', '_slds_multisite_scripts_load');
add_action( 'template_redirect', '_slds_handle_404_sandbox' );

// Call from sandbax instances and host
add_action('wp_ajax_slds_complete_setup', '_slds_complete_setup');
add_action('wp_ajax_nopriv_slds_login_to_admin', '_slds_admin_login');

// Call from Main site
add_action('wp_ajax_nopriv_slds_init_internal_session', 'slds_internal_session');
add_action('wp_ajax_nopriv_slds_internal_request', 'slds_internal_request');

function _slds_redirect_home_to_demo() {
	// Redirect to demo if it is home, setup complete, and non admin
	if ( is_main_site() && get_option('slds_setup_complete') && ! current_user_can( 'manage_options' ) ) {
		global $slds_meta_data; 
		wp_safe_redirect( $slds_meta_data['sandbox_init_url'] );
		exit;
	}
}

function _slds_handle_404_sandbox() {

	if ( ! is_404() ) {
		return;
	}

	$parsed = wp_parse_url( ( is_ssl() ? 'https' : 'http' ) . '://' . sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'] ?? '')) . sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'] ?? '')) );
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

function slds_internal_session()
{
    file_put_contents(sys_get_temp_dir() . '/slds-nonce.tmp', wp_create_nonce('slds_internal_nonce'));
}

function slds_internal_request()
{

    if (! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'slds_internal_nonce') ) {
        wp_send_json_error(array( 'message' => 'Nonce not matchedsd', 'post' => $_POST ));
    }

    $action  = sanitize_text_field($_POST['slds_action'] ?? '');
    $site_id = ( int ) ( $_POST['sandbox_id'] ?? 0 );
    
    switch ( $action ) {

    case 'create_sandbox' : 
        
        $parsed = wp_parse_url(get_home_url());

        // Define the site details
        $domain  = $parsed['host'];
        $path    = trim($parsed['path'], '/') . '/sandbox-' . md5(time() . microtime());
        $title   = 'New Subsite';
        $user_id = 1;
        $meta    = array(
        'public' => 1
        );

        // Check if the path already exists
        if (! domain_exists($domain, $path) ) {

            $new_site_id = wpmu_create_blog($domain, $path, $title, $user_id, $meta);

            if (is_wp_error($new_site_id) ) {
                wp_send_json_error(array( 'message' => $new_site_id->get_error_message() ));
            }

            wp_send_json_success(
                array(
                'site_id'    => $new_site_id,
                'site_path'  => $path,
                'site_title' => $title
                )
            );

        } else {
            wp_send_json_error(array( 'message' => 'The subsite path already exists.' ));
        }

        break;

    case 'delete_sandbox' :

        $success_message = array( 'message' => 'Sandbox deleted successfully' );

        // If site doesn't exist, still send success, because the site maybe deleted from multisite dashboard meanwhile
        if (! get_site($site_id) ) {
            wp_send_json_success($success_message);
        }

        $result = wp_delete_site($site_id);
            
        if (is_wp_error($result) ) {
            wp_send_json_error(array( 'message' => $result->get_error_message() ));
        } else {
            wp_send_json_success($success_message);
        }
        break;
    }
}

function _slds_complete_setup()
{

    if (! current_user_can('manage_options') ) {
        wp_send_json_error(array( 'message' => 'Access denied!' ));
    }

    update_option('slds_setup_complete', true, true);
    wp_send_json_success();
}

function _slds_admin_login()
{

    if (get_option('slds_setup_complete') ) {
        wp_send_json_error(array( 'message' => 'This action is expired as multisite setup completed' ));
    }
    
    wp_set_current_user(1);
    wp_set_auth_cookie(1);
    wp_send_json_success(array( 'message' => 'Login successful' ));
}

function _slds_multisite_scripts_load()
{
    global $slds_meta_data;
    $slds_load_extensions = $slds_meta_data['extensions'];

    $intent          = '';
    $url_after_login = '';
    $redirect_url    = '';

    // Don't load scripts if setup is completed already.
    if (! is_main_site() || get_option('slds_setup_complete') ) {
        return;
    }
    
    if (! is_multisite() ) {

        if (! is_user_logged_in() ) {
            $intent = 'login';
            $url_after_login = admin_url('network.php');

        } else if (is_admin() ) {

            // Get the current screen object
            $screen = get_current_screen();

            // Check if we are on the network.php page
            if ($screen ) {
                if ($screen->id === 'network' && $screen->base === 'network' ) {
                    $intent = 'setup';
                }
            }
        }
    } else if (! is_user_logged_in() ) {
        $intent = 'login';
        $url_after_login = admin_url('plugins.php');

    } else {
        
        $found = false;

        if (is_admin() ) {
            $screen = get_current_screen();
            if ($screen ) {
                if ($screen->id === 'plugins-network' && $screen->base === 'plugins-network' ) {
                    $found = true;
                    $intent = 'extension';
                }
            }
        }

        if (! $found ) {
            $intent = 'redirect';
            $redirect_url = get_home_url() . '/wp-admin/network/plugins.php';
        }
    } 

    ?>
    <script>
        const _slds_net_url                  = '<?php echo esc_url($url_after_login); ?>';
        const _slds_ajax_url                 = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
        const _slds_intent                   = '<?php echo esc_attr($intent); ?>';
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
                    window.location.assign('<?php echo esc_url($redirect_url); ?>');
                    break;
            }
        });
    </script>
    <?php
}
