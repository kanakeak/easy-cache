<?php
/**
 * Plugin Name: Easy WP Cache
 * Plugin URI: https://wpexplored.com
 * Description: A simple caching plugin that just works without giving you headache about setting complex things.
 * Author: Eyasir Arafat
 * Version: 1.1.0
 * Text Domain: easy-cache
 * Domain Path: /languages
 * Author URI: https://wpexplored.com
 *
 * @package  easy-cache
 */

defined( 'ABSPATH' ) || exit;

define( 'WE_VERSION', '1.1.0' );

require_once dirname( __FILE__ ) . '/inc/functions.php';
require_once dirname( __FILE__ ) . '/inc/class-we-settings.php';
require_once dirname( __FILE__ ) . '/inc/class-we-config.php';
require_once dirname( __FILE__ ) . '/inc/class-we-advanced-cache.php';
require_once dirname( __FILE__ ) . '/inc/class-we-object-cache.php';
require_once dirname( __FILE__ ) . '/inc/class-we-cron.php';

WE_Settings::factory();
WE_Advanced_Cache::factory();
WE_Object_Cache::factory();
WE_Cron::factory();


/**
 * Load text domain
 *
 * @since 1.0
 */
function we_load_textdomain() {

	load_plugin_textdomain( 'easy-cache', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'we_load_textdomain' );


/**
 * Add settings link to plugin actions
 *
 * @param  array  $plugin_actions Each action is HTML.
 * @param  string $plugin_file Path to plugin file.
 * @since  1.0
 * @return array
 */
function we_filter_plugin_action_links( $plugin_actions, $plugin_file ) {

	$new_actions = array();

	if ( basename( dirname( __FILE__ ) ) . '/easy-cache.php' === $plugin_file ) {
		/* translators: Param 1 is link to settings page. */
		$new_actions['we_settings'] = sprintf( __( '<a href="%s">Settings</a>', 'easy-cache' ), esc_url( admin_url( 'options-general.php?page=easy-cache' ) ) );
	}

	return array_merge( $new_actions, $plugin_actions );
}
add_filter( 'plugin_action_links', 'we_filter_plugin_action_links', 10, 2 );

/**
 * Clean up necessary files
 *
 * @since 1.0
 */
function we_clean_up() {

	WP_Filesystem();

	WE_Advanced_Cache::factory()->clean_up();
	WE_Advanced_Cache::factory()->toggle_caching( false );
	WE_Object_Cache::factory()->clean_up();
	WE_Config::factory()->clean_up();
}
register_deactivation_hook( __FILE__, 'we_clean_up' );


