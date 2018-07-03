<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used in the admin area and the handlers
 *
 * @link       https://t.me/WPTelegram
 * @since      1.0.0
 *
 * @package    WPTelegram
 * @subpackage WPTelegram/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WPTelegram
 * @subpackage WPTelegram/includes
 * @author     Manzoor Wani
 */
class WPTelegram {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WPTelegram_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Title of the plugin.
	 *
	 * @since    1.3.7
	 * @access   protected
	 * @var      string    $title    Title of the plugin
	 */
	protected $title;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the handlers.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->title =  __( 'WP Telegram', 'wptelegram' );
		$this->plugin_name = 'wptelegram';
		$this->version = WPTELEGRAM_VER;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_handler_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - WPTelegram_Loader. Orchestrates the hooks of the plugin.
	 * - WPTelegram_i18n. Defines internationalization functionality.
	 * - WPTelegram_Admin. Defines all hooks for the admin area.
	 * - WPTelegram_Post_Handler. Defines all hooks for handling the post transition.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once WPTELEGRAM_DIR . '/includes/class-wptelegram-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once WPTELEGRAM_DIR . '/includes/class-wptelegram-i18n.php';

		/**
		 * The miscellaneous functions
		 * 
		 */
		require_once WPTELEGRAM_DIR . '/includes/wptelegram-functions.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once WPTELEGRAM_DIR . '/admin/class-wptelegram-admin.php';

		/**
		 * The class responsible for loading WPTelegram_Bot_API library
		 */
		require_once WPTELEGRAM_DIR . '/includes/wptelegram-bot-api/class-wptelegram-bot-api-loader.php';

		/**
		 * The library responsible for converting HTML to plain text
		 */
		require_once WPTELEGRAM_DIR . '/includes/html2text/html2text.php';

		/**
		 * The class responsible for rendering all the settings in the admin area
		 */
		require_once WPTELEGRAM_DIR . '/admin/class-wptelegram-settings-api.php';

		/**
		 * The class responsible for rendering all the settings in the admin area
		 */
		require_once WPTELEGRAM_DIR . '/admin/class-wptelegram-admin-settings.php';

		/**
		 * The class responsible for handling the post edit triggers
		 * 
		 */
		require_once WPTELEGRAM_DIR . '/includes/class-wptelegram-post-handler.php';

		/**
		 * The class responsible for handling the wp_mail triggers
		 * 
		 */
		require_once WPTELEGRAM_DIR . '/includes/class-wptelegram-notification-handler.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once WPTELEGRAM_DIR . '/public/class-wptelegram-public.php';

		$this->loader = new WPTelegram_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WPTelegram_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new WPTelegram_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new WPTelegram_Admin( $this->get_plugin_title(), $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wptelegram_activated', $plugin_admin, 'set_settings_page_redirect' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_filter( 'plugin_action_links_' . WPTELEGRAM_BASENAME, $plugin_admin, 'plugin_action_links' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu_page' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'settings_page_redirect' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'admin_init' );
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'register_meta_box');
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'admin_notices' );

	}

	/**
	 * Register all of the hooks related to the changes in post state
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_handler_hooks() {

		$post_handler = new WPTelegram_Post_Handler();
		$this->loader->add_action( 'save_post', $post_handler, 'handle_save_post', 20, 2 );
		$this->loader->add_action( 'future_to_publish', $post_handler, 'handle_future_to_publish', 20, 1 );

		$this->loader->add_action( 'wptelegram_bot_api_before_request', $post_handler, 'proxy_setup', 20, 1 );

		$notif_handler = new WPTelegram_Notification_Handler();
		//$this->loader->add_action( 'phpmailer_init', $notif_handler, 'handle_phpmailer_init' );
		/**
		 * Better to use "wp_mail" filter
		 * instead of "phpmailer_init" action
		 * PHPMailer class has evolved drastically
		 * making the address formats inconsistent
		 */
		$this->loader->add_filter( 'wp_mail', $notif_handler, 'handle_wp_mail', 10, 1 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.6.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new WPTelegram_Public( $this->get_plugin_title(), $this->get_plugin_name(), $this->get_version() );

		global $wptelegram_options;
		$user_notifications = $wptelegram_options['notify']['user_notifications'];
		if ( 'on' == $user_notifications ) {
			$this->loader->add_action( 'show_user_profile', $plugin_public, 'add_user_profile_fields', 10, 1 );
			$this->loader->add_action( 'edit_user_profile', $plugin_public, 'add_user_profile_fields', 10, 1 );

			$this->loader->add_filter( 'user_profile_update_errors', $plugin_public, 'validate_user_profile_fields', 10, 3 );
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The title of the plugin.
	 *
	 * @since     1.3.7
	 * @return    string    The title of the plugin.
	 */
	public function get_plugin_title() {
		return $this->title;
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    WPTelegram_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
