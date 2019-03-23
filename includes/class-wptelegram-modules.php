<?php

/**
 * Loads and includes all the active modules
 *
 * @link	   https://t.me/manzoorwanijk
 * @since	  1.0.0
 *
 * @package	WPTelegram
 * @subpackage WPTelegram/includes
 */

/**
 * Loads and includes all the active modules
 *
 * @package	WPTelegram
 * @subpackage WPTelegram/includes
 * @author	 Manzoor Wani <@manzoorwanijk>
 */
class WPTelegram_Modules {

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->load_dependencies();

	}

	/**
	 * Load the required dependencies
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for defining all the common basics of modules
		 */
		require_once WPTELEGRAM_MODULES_DIR . '/class-wptelegram-module.php';

		/**
		 * The class responsible for defining the basics
		 */
		require_once WPTELEGRAM_MODULES_DIR . '/class-wptelegram-module-base.php';
	}

	/**
	 * Retrieve all modules
	 *
	 * @since	1.0.0
	 */
	public static function get_all_modules() {

		return array(
			'p2tg'		=> __( 'Post to Telegram', 'wptelegram' ),
			'notify'	=> __( 'Private Notifications', 'wptelegram' ),
			'proxy'		=> __( 'Proxy', 'wptelegram' ),
		);
	}

	/**
	 * Load the active modules
	 *
	 * @since	1.0.0
	 * @access   private
	 */
	public function load() {
		
		$all_modules	= self::get_all_modules();
		$active_modules	= WPTG()->helpers->get_active_modules();

		if ( empty( $active_modules ) ) {
			return;
		}

		foreach ( $active_modules as $_module ) {

			$module = str_replace( '_', '-', $_module );

			$path = WPTELEGRAM_MODULES_DIR . '/' . $module;

			$file = $path . '/class-wptelegram-' . $module . '.php';

			if ( file_exists( $file ) ) {
				/**
				 * The class responsible for loading the module
				 */
				require_once $file;
				
				$module = WPTG()->utils->ucwords( $_module, '_' );

				$class = "WPTelegram_{$module}";

				if ( class_exists( $class ) ) {

					$obj = new $class( $_module, $path, $all_modules[ $_module ] );

					$obj->run();

					define( strtoupper( $class ) . '_LOADED', true );
				}
			}
		}
	}
}
