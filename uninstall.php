<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * 
 *
 * @link       https://twitter.com/manzoorwanijk
 * @since      1.0.0
 *
 * @package    WPTelegram
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
require_once plugin_dir_path( __FILE__ ) . 'includes/wptelegram-functions.php';

wptelegram_handle_uninstall();