<?php
/**
 * Script registrars
 *
 * @package solidie
 */

namespace Solidie_Sandbox\Setup;

use Solidie_Sandbox\Helpers\Colors;
use Solidie_Sandbox\Helpers\Utilities;
use Solidie_Sandbox\Main;

/**
 * Script class
 */
class Scripts {

	/**
	 * Scripts constructor, register script hooks
	 *
	 * @return void
	 */
	public function __construct() {

		// Load scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'adminScripts' ), 11 );

		// Register script translations
		add_action( 'admin_enqueue_scripts', array( $this, 'scriptTranslation' ), 9 );

		// Load text domain
		add_action( 'init', array( $this, 'loadTextDomain' ) );

		// JS Variables
		add_action( 'admin_head', array( $this, 'loadVariables' ), 1000 );
	}

	/**
	 * Load environment and color variables
	 *
	 * @return void
	 */
	public function loadVariables() {

		// Load dynamic colors
		$dynamic_colors = Colors::getColors();
		$_colors        = '';
		foreach ( $dynamic_colors as $name => $code ) {
			$_colors .= '--solidie-color-' . esc_attr( $name ) . ':' . esc_attr( $code ) . ';';
		}
		?>
			<style>
				:root{
					<?php echo esc_html( $_colors ); ?>
				}
			</style>
		<?php

		$nonce_action = '_solidie_' . str_replace( '-', '_', gmdate( 'Y-m-d' ) );
		$nonce        = wp_create_nonce( $nonce_action );
		$user         = wp_get_current_user();

		// Determine the react react root path
		$parsed    = wp_parse_url( get_home_url() );
		$root_site = 'http' . ( is_ssl() ? 's' : '' ) . '://' . $parsed['host'] . ( ! empty( $parsed['port'] ) ? ':' . $parsed['port'] : '' );
		$home_path = trim( $parsed['path'] ?? '', '/' );
		$page_path = is_singular() ? trim( str_replace( $root_site, '', get_permalink( get_the_ID() ) ), '/' ) : null;

		// Load data
		$data = apply_filters(
			'slds_frontend_variables',
			array(
				'is_admin'         => is_admin(),
				'action_hooks'     => array(),
				'filter_hooks'     => array(),
				'mountpoints'      => ( object ) array(),
				'home_path'        => $home_path,
				'page_path'        => $page_path,
				'app_name'         => Main::$configs->app_id,
				'nonce'            => $nonce,
				'nonce_action'     => $nonce_action,
				'colors'           => $dynamic_colors,
				'opacities'        => Colors::getOpacities(),
				'contrast'         => Colors::CONTRAST_FACTOR,
				'text_domain'      => Main::$configs->text_domain,
				'date_format'      => get_option( 'date_format' ),
				'time_format'      => get_option( 'time_format' ),
				'is_apache'        => is_admin() ? strpos( sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ?? '' ), 'Apache' ) !== false : null,
				'bloginfo'         => array(
					'name' => get_bloginfo( 'name' ),
				),
				'user'             => array(
					'id'           => $user ? $user->ID : 0,
					'first_name'   => $user ? $user->first_name : null,
					'last_name'    => $user ? $user->last_name : null,
					'email'        => $user ? $user->user_email : null,
					'display_name' => $user ? $user->display_name : null,
					'avatar_url'   => $user ? get_avatar_url( $user->ID ) : null,
					'username'     => $user ? $user->user_login : null,
				),
				'settings'         => array(
					
				),
				'permalinks'       => array(
					'home_url'          => get_home_url(),
					'ajaxurl'           => admin_url( 'admin-ajax.php' ),
					'settings'          => Utilities::getBackendPermalink( AdminPage::SETTINGS_SLUG ),
					'dashboard'         => Utilities::getBackendPermalink( Main::$configs->root_menu_slug ),
					'logout'            => htmlspecialchars_decode( wp_logout_url( get_home_url() ) ),
				),
			)
		);

		$pointer = Main::$configs->app_id;

		?>
		<script>
			window.<?php echo esc_html( $pointer ); ?> = <?php echo wp_json_encode( $data ); ?>;
			window.<?php echo esc_html( $pointer ); ?>pro = window.<?php echo esc_html( $pointer ); ?>;
		</script>
		<?php
	}

	/**
	 * Load scripts for admin dashboard
	 *
	 * @return void
	 */
	public function adminScripts() {
		if ( Utilities::isAdminDashboard() ) {
			wp_enqueue_script( 'slds-backend', Main::$configs->dist_url . 'admin-dashboard.js', array( 'jquery' ), Main::$configs->version, true );
		}
	}

	/**
	 * Load text domain for translations
	 *
	 * @return void
	 */
	public function loadTextDomain() {
		load_plugin_textdomain( Main::$configs->text_domain, false, Main::$configs->dir . 'languages' );
	}

	/**
	 * Load translations
	 *
	 * @return void
	 */
	public function scriptTranslation() {

		$domain = Main::$configs->text_domain;
		$dir    = Main::$configs->dir . 'languages/';

		wp_enqueue_script( 'solidie-translations', Main::$configs->dist_url . 'libraries/translation-loader.js', array( 'jquery' ), Main::$configs->version, true );
		wp_set_script_translations( 'solidie-translations', $domain, $dir );
	}
}
