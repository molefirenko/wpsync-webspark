<?php
/**
 * Admin page
 *
 * @package wpsync-webspark
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Webspark_Admin_Page' ) ) {
	/**
	 * Class Admin Page
	 */
	class Webspark_Admin_Page {
		/**
		 * Admin menu page
		 *
		 * @return void
		 */
		public static function create_settings_page() {
			add_menu_page( 'WPSync Webspark', 'WPSync', 'manage_options', 'wpsync_settings', array( __CLASS__, 'render_settings_page' ) );
		}

		/**
		 * Render Settings Page
		 *
		 * @return void
		 */
		public static function render_settings_page() {
			include WPSYNC_PLUGIN_PATH . 'templates/settings-page.php';
		}

		/**
		 * Register Settings data
		 *
		 * @return void
		 */
		public static function register_settings() {
			register_setting( 'wpsync_settings', 'wpsync_enable_sync' );
			register_setting( 'wpsync_settings', 'wpsync_api_key' );
		}
	}
}
