<?php

/**
 * Fired during plugin activation
 *
 * @link       https://twitter.com/manzoorwanijk
 * @since      1.0.0
 *
 * @package    WPTelegram
 * @subpackage WPTelegram/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WPTelegram
 * @subpackage WPTelegram/includes
 * @author     Manzoor Wani
 */
class WPTelegram_Activator {

	/**
	 * Enables hooks for the activation process.
	 *
	 * @since	1.0.0
	 * @param	bool	$network_wide	Whether the plugin is enabled the for all sites in the network or just the current site
	 */
	public static function activate( $network_wide = false ) {
		do_action( 'wptelegram_activated', $network_wide );
	}

}
