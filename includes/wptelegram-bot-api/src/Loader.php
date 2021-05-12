<?php
/**
 * The loader.
 *
 * The class which loads the latest version of the library.
 *
 * @link       https://manzoorwani.dev
 * @since      1.2.2
 *
 * @package    WPTelegram\BotAPI
 * @subpackage WPTelegram\BotAPI
 */

namespace WPTelegram\BotAPI;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( __NAMESPACE__ . '\Loader_1_2_4', false ) ) {
	/**
	 * Handles checking for and loading the newest version of WPTelegram\BotAPI
	 *
	 * Inspired from CMB2 loading technique
	 * to ensure that only the latest version is loaded
	 *
	 * @see https://github.com/CMB2/CMB2/blob/v2.3.0/init.php
	 *
	 * @since  1.0.1
	 *
	 * @category  WordPress_Plugin Addon
	 * @package   WPTelegram\BotAPI
	 * @author    WPTelegram team
	 * @license   GPL-2.0+
	 * @link      https://t.me/WPTelegram
	 */
	class Loader_1_2_4 {

		/**
		 * Current version number
		 *
		 * @var   string
		 * @since 1.0.1
		 */
		const VERSION = '1.2.4';

		/**
		 * Current version hook priority.
		 * Will decrement with each release
		 *
		 * @var   int
		 * @since 1.0.1
		 */
		const PRIORITY = 9983;

		/**
		 * Single instance of the Loader_1_2_4 object
		 *
		 * @var Loader_1_2_4
		 */
		public static $single_instance = null;

		/**
		 * Creates/returns the single instance Loader_1_2_4 object
		 *
		 * @since  1.0.1
		 * @return Loader_1_2_4 Single instance object
		 */
		public static function initiate() {
			if ( null === self::$single_instance ) {
				self::$single_instance = new self();
			}
			return self::$single_instance;
		}

		/**
		 * Starts the version checking process.
		 * Creates WPTelegram\BotAPI_LOADED definition for early detection by other scripts
		 *
		 * Hooks WPTelegram\BotAPI inclusion to the after_setup_theme hook on a high priority which decrements
		 * (increasing the priority) with each version release.
		 *
		 * @since 1.0.1
		 */
		private function __construct() {
			/**
			 * A constant you can use to check if WPTelegram\BotAPI is loaded
			 * for your plugins/themes with WPTelegram\BotAPI dependency
			 */
			if ( ! defined( 'WPTELEGRAM_BOT_API_LOADED' ) ) {
				define( 'WPTELEGRAM_BOT_API_LOADED', self::PRIORITY );
			}

			/**
			 * Use after_setup_theme hook instead of init
			 * to make the API library available during init
			 */
			add_action( 'after_setup_theme', [ $this, 'init_wptelegram_bot_api' ], self::PRIORITY );
		}

		/**
		 * A final check if WPTelegram\BotAPI exists before kicking off our WPTelegram\BotAPI loading.
		 * WPTELEGRAM_BOT_API_VERSION constant is set at this point.
		 *
		 * @since  1.0.1
		 */
		public function init_wptelegram_bot_api() {
			if ( class_exists( BotAPI\API::class, false ) ) {
				return;
			}

			if ( ! defined( 'WPTELEGRAM_BOT_API_VERSION' ) ) {
				define( 'WPTELEGRAM_BOT_API_VERSION', self::VERSION );
			}

			if ( ! defined( 'WPTELEGRAM_BOT_API_DIR' ) ) {
				define( 'WPTELEGRAM_BOT_API_DIR', dirname( __FILE__ ) );
			}

			// Now kick off the class autoloader.
			spl_autoload_register( [ __CLASS__, 'wptelegram_bot_api_autoload_classes' ] );

			add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
		}

		/**
		 * Autoloads files with WPTelegram\BotAPI classes when needed
		 *
		 * @since  1.0.1
		 * @param  string $class_name Name of the class being requested.
		 */
		public static function wptelegram_bot_api_autoload_classes( $class_name ) {
			$namespace = 'WPTelegram\BotAPI';

			if ( 0 !== strpos( $class_name, $namespace ) ) {
				return;
			}

			$class_name = str_replace( $namespace, '', $class_name );
			$class_name = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name );

			$path = WPTELEGRAM_BOT_API_DIR . $class_name . '.php';

			include_once $path;
		}

		/**
		 * Register WP REST API routes.
		 *
		 * @since 1.2.2
		 */
		public function register_rest_routes() {
			$controller = new restApi\RESTAPIController();
			$controller->register_routes();
		}
	}
	Loader_1_2_4::initiate();
}
