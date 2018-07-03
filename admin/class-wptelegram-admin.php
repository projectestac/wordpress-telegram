<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://t.me/WPTelegram
 * @since      1.0.0
 *
 * @package    WPTelegram
 * @subpackage WPTelegram/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    WPTelegram
 * @subpackage WPTelegram/admin
 * @author     Manzoor Wani
 */
class WPTelegram_Admin {

	/**
	 * Title of the plugin.
	 *
	 * @since    1.3.7
	 * @access   protected
	 * @var      string    $title    Title of the plugin
	 */
	protected $title;

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    /**
     * WPTelegram_Settings_API
     *
     * @var Object
     */
    private $settings_api;

	/**
	 * Object which handles settings sections, fields and metabox
	 *
	 * @since  	1.0.0
	 * @access 	private
	 * @var WPTelegram_Admin_Settings $settings Admin Settings Object
	 */
	private $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $title, $plugin_name, $version ) {

		$this->title = $title;
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->settings = new WPTelegram_Admin_Settings();
		$this->settings_api = new WPTelegram_Settings_API( $plugin_name );
		
		$this->update_terms_option();
	}

	/**
	 * Update Options after upgrade from version < 1.4.5
	 *
	 * @since    1.5.0
	 */
	private function update_terms_option() {
		$option_name = 'wptelegram_wordpress';
		$option_value = get_option( $option_name );

		if ( ! isset( $option_value['terms'] ) ) {
			return;
		}

		$old_terms = $option_value['terms'];
		$new_terms = array();

		if ( ! preg_match( '/^[0-9]+?-/', $old_terms[0] ) ) {
			return;
		}

		foreach ( $old_terms as $term ) {
			$new_terms[] = preg_replace( '/^([0-9]+?)-/', '$1@', $term );
		}
		$option_value['terms'] = $new_terms;

		update_option( $option_name, $option_value, true );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, WPTELEGRAM_URL . '/admin/css/wptelegram-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, WPTELEGRAM_URL . '/admin/js/wptelegram-admin.js', array( 'jquery' ), $this->version, false );
	}

    /**
     * Add action links to the plugin page
     *
     * @since  1.6.1
     */
    public function plugin_action_links( $links ) {
    	$settings_link = '<a href="' . menu_page_url( $this->plugin_name, false ) . '">' . esc_html( __( 'Settings', 'wptelegram' ) ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
    }

    /**
     * Sets the redirect after activation
     *
     * @since  1.6.3
     */
    public function set_settings_page_redirect() {
    	if ( ! is_network_admin() ) {
    		// set redirect transient
			set_transient( '_wptelegram_settings_page_redirect', 1, 30 );
		}
    }

    /**
     * Do redirect if required
     *
     * @since  1.6.3
     */
    public function settings_page_redirect() {
    	global $wptelegram_options;

    	// check if Bot Token is set
    	$token = $wptelegram_options['telegram']['bot_token'];

    	// redirect transient name
    	$transient = '_wptelegram_settings_page_redirect';

    	// get redirect transient
    	$redirect = get_transient( $transient );

    	// delete redirect transient
		delete_transient( $transient );

		/**
		 * if redirect transient exists
		 * and
		 * if the Bot Token (settings) not set
		 */
		if ( $redirect && ! $token ) {
			// redirect to WP Telegram Settings Page
			wp_redirect( admin_url( 'admin.php?page=' . rawurlencode( $this->plugin_name ) ) );
		}
    }

    /**
     * Initialize the admin area of the plugin
     *
     * @since  1.3.0
     */
    public function admin_init() {
    	
        /**
		 * use admin_init hook to set post types
		 * because add_meta_boxes hook would not be able to
		 * return Custom Post Types
		 */
		$this->settings->set_post_types();
        //set the settings
        $this->settings_api->set_sections( $this->settings->get_settings_sections() );
        $this->settings_api->set_fields( $this->settings->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

	/**
	 * Add an entry in the Admin Menu
	 *
	 * @since  1.0.0
	 */
	public function add_menu_page() {

		add_menu_page(
			__( 'WP Telegram Settings', 'wptelegram' ),
			__( 'WP Telegram', 'wptelegram' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_settings_page' ),
			WPTELEGRAM_URL . '/admin/icons/icon-16x16-white.svg',
			80
		);
		add_submenu_page(
	        'wptelegram',
			__( 'WP Telegram Settings', 'wptelegram' ),
			__( 'Core', 'wptelegram' ),
			'manage_options',
	        'wptelegram'
        );
	}

	/**
	 * Render the menu page for plugin
	 *
	 * @since  1.3.7
	 */
	public function display_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.' ) );
		}
        $this->load_select2();
        $this->load_emojioneArea();
        
		echo '<div class="wrap wptelegram" id="wptelegram-wrap">';

        include_once WPTELEGRAM_DIR . '/admin/partials/wptelegram-admin-header.php';

        add_action( 'wptelegram_before_submit_button', array( $this, 'display_remove_settings' ) );

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();
        echo '</div>';
        // the template containing js
        include_once WPTELEGRAM_DIR . '/admin/partials/wptelegram-admin-display.php';
	}

	/**
	 * Render the remove settings checkbox
	 *
	 * @since  1.4.1
	 */
	public function display_remove_settings() {
		global $wptelegram_options;
		$remove_settings = isset( $wptelegram_options['wordpress']['remove_settings'] ) ? $wptelegram_options['wordpress']['remove_settings'] : 'on';
		$float = is_rtl() ? 'left' : 'right';
		?>
		<div class="wptelegram-before-submit" style="float:<?php echo $float; ?>">
			<input type="hidden" name="wptelegram_wordpress[remove_settings]" value="off">
			<label for="wptelegram_wordpress[remove_settings]"><input type="checkbox" class="checkbox remove_settings" id="wptelegram_wordpress[remove_settings]" name="wptelegram_wordpress[remove_settings]" value="on" <?php checked( $remove_settings, 'on' ); ?>><?php esc_html_e( 'Remove settings on uninstall', 'wptelegram' ); ?></label>
		</div>
		<?php
	}

	/**
	 * Register Metabox
	 *
	 * @since  1.0.0
	 */
	public function register_meta_box()
	{
		global $wptelegram_options;
		$post_edit_switch = $wptelegram_options['wordpress']['post_edit_switch'];
		if ( 'on' != $post_edit_switch ) {
			return;
		}

		$show_switch_to_all = apply_filters( 'wptelegram_show_switch_to_all_authors', false );
		$from_authors = $wptelegram_options['wordpress']['from_authors'][0];

		if ( ! ( $show_switch_to_all || 'all' == $from_authors ) ) {
			$authors = $wptelegram_options['wordpress']['authors'];
			$author_in_list = in_array( get_current_user_id(), $authors );

			if ( 'selected' == $from_authors && ! $author_in_list ) {
				return;
			} elseif ( 'not_selected' == $from_authors && $author_in_list ) {
				return;
			}
		}

        $this->load_emojioneArea();

		$screens = $this->settings->get_post_types( 'metabox' );
	    $screens = (array) apply_filters( 'wptelegram_cpt_meta_box_screens', $screens);

	    /**
	     * The $screen parameter as array to add_meta_box
	     * was added in WordPress 4.4
	     * So, passing as string to support older versions
	     */
	    foreach ( $screens as $screen ) {
	    	add_meta_box(
	            'wptelegram_meta_box',
	            __( 'WP Telegram Options', 'wptelegram' ),
	            array( $this->settings, 'wptelegram_meta_box_cb' ),
	            $screen,
	            'normal',
	            'high'
	        );
	    }
        wp_enqueue_style( $this->plugin_name.'-metabox', WPTELEGRAM_URL . '/admin/css/wptelegram-admin-metabox.css', array(), $this->version, 'all' );
	}

	/**
	 * Load Emoji One Area
	 *
	 * @since  1.3.0
	 */
	private function load_emojioneArea(){
		wp_enqueue_style( $this->plugin_name.'-emojicss', WPTELEGRAM_URL . '/admin/emoji/emojionearea.min.css', array(), $this->version, 'all' );
        wp_enqueue_script( $this->plugin_name.'-emojijs', WPTELEGRAM_URL . '/admin/emoji/emojionearea.min.js', array(), $this->version, 'all' );
	}

	/**
	 * Load Select2
	 *
	 * @since  1.3.8
	 */
	private function load_select2(){
		wp_enqueue_style( $this->plugin_name.'-select2css', WPTELEGRAM_URL . '/admin/select2/select2.min.css', array(), $this->version, 'all' );
        wp_enqueue_script( $this->plugin_name.'-select2js', WPTELEGRAM_URL . '/admin/select2/select2.min.js', array(), $this->version, 'all' );
	}

	/**
	 * Show admin notices on failure
	 *
	 * @since  1.3.0
	 */
	public function admin_notices() {
		if ( ! isset( $_GET['wptelegram'] ) ) {
		 return;
		}
		?>
		<?php
		$code = $_GET['wptelegram'];
		$message = __( 'WP Telegram failed to communicate.', 'wptelegram' );
		if ( $error = get_site_transient( 'wptelegram_http_error' ) ) {
			$message .= ' <b>' . __( 'Reason: ', 'wptelegram' ) . '</b>' . $error->get_error_message();
			delete_transient( 'wptelegram_http_error' );
		}
		?>
		<div class="notice notice-error is-dismissible">
		  <p><b><?php esc_html_e( 'Error: ', 'wptelegram'); _e( $code ); ?>&#33;</b>&nbsp;<?php echo $message; ?></p>
		</div>
		<?php
	}
}
