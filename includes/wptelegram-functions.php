<?php
if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}
// Set the global options variable
global $wptelegram_options;
$wptelegram_options = wptelegram_get_all_options();

/**
 * Get all the options into the global variable
 *
 * @since  1.2.0
 *
 * @return array
 */
function wptelegram_get_all_options() {
	$sections = (array) wptelegram_option_sections();
	$defaults = wptelegram_get_default_options();
	$options = array();
	foreach ( $sections as $section ) {
		$args = get_option( 'wptelegram_' . $section );
		$options[ $section ] = wptelegram_parse_args_recursive( $args, $defaults[ $section ] );
	}
	return apply_filters( 'wptelegram_options', $options );
}

/**
 * Get default options to avoid errors after plugin update
 *
 * @since  1.3.8
 *
 * @return array
 */
function wptelegram_get_default_options() {
	$arr = array();
	$defaults = array(
		'telegram'	=> array(
			'bot_token'	=> '',
			'chat_ids'	=> '',
		),
		'wordpress'	=> array(
			'send_when'			=> $arr,
			'which_post_type'	=> $arr,
			'from_terms'		=> 'all',
			'terms'				=> $arr,
			'from_authors'		=> 'all',
			'authors'			=> $arr,
			'post_edit_switch'	=> 'on',
		),
		'message'	=> array(
			'message_template'		=> '',
			'excerpt_source'		=> 'post_content',
			'excerpt_length'		=> 55,
			'parse_mode'			=> 'none',
			'send_featured_image'	=> 'on',
			'image_position'		=> 'before',
			'attach_image'			=> 'off',
			'image_style'			=> 'without_caption',
			'caption_source'		=> 'media_library',
			'misc'					=> array(
				'disable_web_page_preview'	=> false,
				'disable_notification'		=> false,
				'no_message_as_reply'		=> false,
			),
		),
		'notify'	=> array(
			'chat_id'				=> '',
			'watch_emails'			=> '',
			'hashtag'				=> '',
			'user_notifications'	=> 'off',
		),
		'proxy'	=> array(
			'script_url'		=> '',
			'proxy_host'		=> '',
			'proxy_port'		=> '',
			'proxy_type'		=> 'CURLPROXY_HTTP',
			'proxy_username'	=> '',
			'proxy_password'	=> '',
		),
	);
	return apply_filters( 'wptelegram_default_options', $defaults );
}

/**
 * Settings Sections
 *
 * @since  1.3.8
 *
 * @return array
 */
function wptelegram_option_sections( $only_core = false ) {
	$sections = array(
		'telegram',
		'wordpress',
		'message',
		'notify',
		'proxy',
	);
	if ( $only_core ) {
		return $sections;
	}
	return apply_filters( 'wptelegram_option_sections', $sections );
}

/**
 * Sanitize the message_template before being saved to database
 * From _sanitize_text_fields() in wp-includes/formatting.php
 *
 * @param  string $message
 * @since  1.2.0
 * @return string           Sanitized value
 */
function wptelegram_sanitize_message_template( $message, $encoding = 'json' ) {
	$filtered = wp_check_invalid_utf8( $message );
    if ( strpos($filtered, '<') !== false ) {
        $filtered = wp_pre_kses_less_than( $filtered );
        // This will strip extra whitespace for us.
        $filtered = strip_tags( $filtered, "<b><strong><i><em><a><code><pre>");
    }

    $found = false;
    while ( preg_match( '/%[a-f0-9]{2}/i', $filtered, $match ) ) {
        $filtered = str_replace( $match[0], '', $filtered );
        $found = true;
    }

    if ( $found ) {
        // Strip out the whitespace that may now exist after removing the octets.
        $filtered = trim( preg_replace( '/ +/', ' ', $filtered ) );
    }

    $filtered = apply_filters( 'wptelegram_sanitize_message_template', $filtered, $message );

	if ( '' != $filtered && 'json' == $encoding ) {
		// json_encode to avoid errors when saving multibyte emojis into database with no multibyte support
		$filtered = json_encode( $filtered );
	}
    return $filtered;
}

/**
 * Sanitize the Bot Token before being saved to database
 *
 * @param  string $bot_token
 * @since  1.2.0
 * @return string           Sanitized value
 */
function wptelegram_sanitize_bot_token( $bot_token ) {

    // fetch old value
    global $wptelegram_options;
    $old_value = $wptelegram_options['telegram']['bot_token'];

	$san_bot_token = sanitize_text_field( $bot_token );

	$tg_api = new WPTelegram_Bot_API( $san_bot_token );
	
	$res = $tg_api->getMe();

	if ( get_site_transient( 'wptelegram_bot_username' ) ) {
		// delete the bot username from transient
		delete_site_transient( 'wptelegram_bot_username' );
	}

	if ( preg_match( '/\A\d{9}:[\w-]{35}\Z/', $san_bot_token ) && ! is_wp_error( $res ) && 200 == $res->get_response_code() ) {
		// save the username in transient
		$bot_info = $res->get_result();
		$bot_username = $bot_info['username'];
		set_site_transient( 'wptelegram_bot_username', $bot_username );
		
        return $san_bot_token;
    }
    else{
    	$type = 'error';
        if ( is_wp_error( $res ) ) {
        	$message = $res->get_error_code() . '&nbsp;' . $res->get_error_message();
        } elseif ( ! $res->is_valid_json() ) {
        	$message = sprintf( __( 'Your host seems to block requests to %s', 'wptelegram' ), 'api.telegram.org' );
        } else {
	        $message = __( 'Invalid Bot Token', 'wptelegram' );
        }
	    add_settings_error(
	        'wptelegram',
	        esc_attr( 'settings_updated' ),
	        $message,
	        $type
	    );
    }
    return $old_value;
}

/**
 * Sanitize the Bot Token before being saved to database
 *
 * @param  string $chat_ids
 * @since  1.2.0
 * @return string           Sanitized value
 */
function wptelegram_sanitize_chat_ids( $chat_ids ) {
	$filtered = sanitize_text_field( $chat_ids );
	$filtered = trim( $filtered, " \t\n\r\0\x0B," );
	return str_replace( ' ', '', $filtered );
}

/**
 * Sanitize the input arrays
 *
 * @param  array $option
 * @since  1.2.0
 * @return array           with Sanitized keys and values
 */
function wptelegram_sanitize_array( $option ) {
	$filtered = array();
	foreach ( (array) $option as $key => $value ) {
		$filtered[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
	}
	return $filtered;
}

/**
 * Extends function wp_parse_args for recursive array or object
 *
 * Inspired from Drupal NestedArray::mergeDeepArray
 * @see https://api.drupal.org/api/drupal/core!lib!Drupal!Component!Utility!NestedArray.php/function/NestedArray%3A%3AmergeDeepArray/8
 *
 * @since 1.3.8
 *
 * @param	mixed	$args
 * @param	mixed	$default
 * @param	bool	$preserve_integer_keys
 *
 * @return mixed
 */
function wptelegram_parse_args_recursive( $args, $default, $preserve_integer_keys = false ) {
    if ( ! ( is_array( $default ) || is_object( $default ) ) ){
        return wp_parse_args( $args, $default );
    }

    $is_object = ( is_object( $args ) || is_object( $default ) );
    $output    = array();

    foreach ( array( $default, $args ) as $elements ) {
        foreach ( (array) $elements as $key => $element ) {
            if ( is_integer( $key ) && ! $preserve_integer_keys ) {
                $output[] = $element;
            } elseif (
                isset( $output[ $key ] ) &&
                ( is_array( $output[ $key ] ) || is_object( $output[ $key ] ) ) &&
                ( is_array( $element ) || is_object( $element ) )
            ) {
                $output[ $key ] = wptelegram_parse_args_recursive( $element, $output[ $key ], $preserve_integer_keys );
            } else {
                $output[ $key ] = $element;
            }
        }
    }
    return $is_object ? (object) $output: $output;
}

/**
 * Replace nested "[" and "_" between two "*"
 *
 * @since 1.3.8
 *
 * @param $match array
 *
 * @return string
 */
function wptelegram_replace_nested_markdown( $match ){
	return str_replace( array( '\\[', '\\_' ), array( '[', '_' ), $match[0] );
}

/**
 * Called when plugin in uninstalled
 *
 * @since  1.4.1
 *
 */
function wptelegram_handle_uninstall() {

	$options = get_option( 'wptelegram_wordpress' ) ;
	if ( isset( $options['remove_settings'] ) && 'off' == $options['remove_settings'] ) {
		return;
	}
	$sections = wptelegram_option_sections( true );
	foreach ( $sections as $section ) {
		delete_option( 'wptelegram_' . $section );
	}
}

/**
 * Sanitize excerpt length
 *
 * @since  1.4.1
 *
 */
function wptelegram_sanitize_excerpt_length( $value ) {
    return filter_var(
	    $value,
	    FILTER_VALIDATE_INT, 
	    array(
	        'options' => array(
	            'min_range'	=> 1, 
	            'max_range'	=> 300,
	            'default'	=> 55,
	        )
	    )
	);
}