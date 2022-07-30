<?php
/**
 * Plugin Name: WPSync Webspark
 * Description: Sync WooCommerce Products with Webspark API
 * Version: 0.30.07.2022
 * Author: Maxim Olefirenko
 * Text Domain: wpsync-webspark
 * GitHub Plugin URI: https://github.com/molefirenko/wpsync-webspark
 *
 * @package wpsync-webspark
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPSYNC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

const WEBSPARK_PRODUCTS_URL = 'https://wp.webspark.dev/wp-api/products';
const WEBSPARK_PRODUCTS_QUEUE = 35;

require_once WPSYNC_PLUGIN_PATH . 'includes/class-webspark-sync.php';
require_once WPSYNC_PLUGIN_PATH . 'includes/class-webspark-admin-page.php';

// Action on plugin activation.
register_activation_hook( __FILE__, 'wpsync_activation_action' );
if ( ! function_exists( 'wpsync_activation_action' ) ) {
	/**
	 * Activation action
	 *
	 * @return void
	 */
	function wpsync_activation_action() {
		Webspark_Sync::enable_cron_action();
	}
}

// Action on plugin deactivation.
register_deactivation_hook( __FILE__, 'wpsync_deactivation_action' );
if ( ! function_exists( 'wpsync_deactivation_action' ) ) {
	/**
	 * Deactivation action
	 *
	 * @return void
	 */
	function wpsync_deactivation_action() {
		Webspark_Sync::disable_cron_action();
		delete_option( 'wpsync_products_queue' );
	}
}

// Register menu.
add_action( 'admin_menu', array( 'Webspark_Admin_Page', 'create_settings_page' ) );

// Admin init action.
add_action( 'admin_init', 'wpsync_admin_init_action' );
if ( ! function_exists( 'wpsync_admin_init_action' ) ) {
	/**
	 * Admin init actions
	 *
	 * @return void
	 */
	function wpsync_admin_init_action() {
		Webspark_Admin_Page::register_settings();
	}
}

// Cron actions.
add_action( 'wpsync_cron_action', array( 'Webspark_Sync', 'sync_action' ) );
add_action( 'wpsync_create_products_queue', array( 'Webspark_Sync', 'create_products' ) );
