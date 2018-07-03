<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://t.me/WPTelegram
 * @since      1.6.0
 *
 * @package    WPTelegram
 * @subpackage WPTelegram/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WPTelegram
 * @subpackage WPTelegram/public
 * @author     Manzoor Wani
 */
class WPTelegram_Public {

	/**
	 * Title of the plugin.
	 *
	 * @since    1.6.0
	 * @access   protected
	 * @var      string    $title    Title of the plugin
	 */
	protected $title;

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.6.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.6.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since	1.6.0
	 * @param	string    $plugin_name       The name of the plugin.
	 * @param	string    $version    The version of this plugin.
	 */
	public function __construct( $title, $plugin_name, $version ) {

		$this->title = $title;
		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Render the profile fields
	 *
	 * @since	1.6.0
	 * @param	WP_User	$user	The WordPress user
	 */
	public function add_user_profile_fields( $user ) {
		global $wptelegram_options;
		$bot_token = $wptelegram_options['telegram']['bot_token'];
		if ( '' == $bot_token ) {
			return;
		}
		$bot_username = get_site_transient( 'wptelegram_bot_username' );
		// if somehow the bot_username is not in transient
		if ( ! $bot_username ) {
			$tg_api = new WPTelegram_Bot_API( $bot_token );
			$res = $tg_api->getMe();

			if ( ! is_wp_error( $res ) && 200 == $res->get_response_code() ) {
				$bot_info = $res->get_result();
				$bot_username = $bot_info['username'];
				set_site_transient( 'wptelegram_bot_username', $bot_username );
			}
		}

		set_query_var( 'telegram_chat_id', $user->telegram_chat_id );
		set_query_var( 'bot_username', $bot_username );

		if ( $template = locate_template( 'wptelegram-profile-fields.php' ) ) {
			/**
		     * locate_template() returns path to file.
		     * if either the child theme or the parent theme have overridden the template.
		     */

			if ( $this->is_valid_template( $template ) ) {
			    load_template( $template );
			}
		} else {
		    /*
		     * If neither the child nor parent theme have overridden the template,
		     * we load the template from the 'partials' sub-directory of the directory this file is in.
		     */
		    load_template( WPTELEGRAM_DIR . '/public/partials/wptelegram-profile-fields.php' );
		}
	}

	/**
	 * Check whether the template path is valid
	 *
	 * @since	1.6.0
	 * @param	string	$template	The template path
	 * @return	bool
	 */
	private function is_valid_template( $template ) {
		/**
		 * Only allow templates that are in the active theme directory,
		 * parent theme directory, or the /wp-includes/theme-compat/ directory
		 * (prevent directory traversal attacks)
		 */
		$valid_paths = array_map( 'realpath',
			array(
				STYLESHEETPATH,
				TEMPLATEPATH,
				ABSPATH . WPINC . '/theme-compat/',
			)
		);

		$path = realpath( $template );

		foreach ( $valid_paths as $valid_path ) {
			if ( preg_match( '/\A' . preg_quote( $valid_path ) . '/', $path ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Render the profile fields
	 *
	 * @since	1.6.0
	 * @param	WP_Error	$errors	WP_Error object (passed by reference)
	 * @param	bool		$update	Whether this is a user update.
	 * @param	stdClass	$user	User object (passed by reference).
	 */
	public function validate_user_profile_fields( &$errors, $update = null, &$user = null ) {
		if ( isset( $_POST['telegram_chat_id'] ) ) {
			$chat_id = sanitize_text_field( $_POST['telegram_chat_id'] );
			$pattern = apply_filters( 'wptelegram_user_telegram_chat_id_regex', '/\A[0-9]+\Z/', $user );
			if ( $chat_id && ! preg_match( $pattern, $chat_id ) ) {
		        $errors->add( 'invalid_chat_id', __( 'Error: ', 'wptelegram' ) . __( 'Please Enter a valid Chat ID', 'wptelegram' ) );
		    } elseif ( current_user_can( 'edit_user', $user->ID ) ) {
		    	update_user_meta( $user->ID, 'telegram_chat_id', $chat_id );
		    }
		}
	}

}
