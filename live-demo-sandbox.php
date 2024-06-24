<?php
/**
 * Plugin Name: Live Demo Sandbox
 * Plugin URI: https://wordpress.org/plugins/live-demo-sandbox/
 * Description: Live demo sandbox instance creator
 * Version: 1.0.0
 * Author: Solidie
 * Author URI: https://solidie.com
 * Requires at least: 5.3
 * Tested up to: 6.5.4
 * Requires PHP: 7.4
 * License: GPLv3
 * License URI: https://opensource.org/licenses/GPL-3.0
 * Text Domain: live-demo-sandbox
 *
 * @package live-demo-sandbox
 */

if ( ! defined( 'ABSPATH' ) ) { exit;
}

// Load autoloader
require_once __DIR__ . '/classes/Main.php';

( new Solidie_Sandbox\Main() )->init(
	(object) array(
		'file'           => __FILE__,
		'mode'           => 'development',
		'root_menu_slug' => 'live-demo-sandbox',
		'db_prefix'      => 'slds_demo_',
		'current_url'    => ( is_ssl() ? 'https' : 'http' ) . '://' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ),
	)
);
