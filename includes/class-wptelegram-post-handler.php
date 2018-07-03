<?php

/**
 * Post Handling functionality of the plugin.
 *
 * @link       https://t.me/WPTelegram
 * @since      1.0.0
 *
 * @package    WPTelegram
 * @subpackage WPTelegram/includes
 */

/**
 * The Post Handling functionality of the plugin.
 *
 * @package    WPTelegram
 * @subpackage WPTelegram/includes
 * @author     Manzoor Wani <@manzoorwanijk>
 */
class WPTelegram_Post_Handler {

	/**
	 * Settings/Options
	 *
	 * @since  	1.3.8
	 * @access 	private
	 * @var  	string 		$options 	Plugin Options
	 */
	private $options;

	/**
	 * Meta box override switch
	 *
	 * @since  	1.2.0
	 * @access 	private
	 * @var  	string 		$override_switch 
	 */
	private $override_switch = 'off';

	/**
	 * The Chat IDs
	 *
	 * @since  	1.0.0
	 * @access 	private
	 * @var  	array 		$chat_ids 	Array of chat IDs
	 */
	private $chat_ids;

	/**
	 * The Telegram API
	 *
	 * @since  	1.0.0
	 * @access 	private
	 * @var WPTelegram_Bot_API $tg_api Telegram API Object
	 */
	private $tg_api;

    /**
     * WP_Error
     *
     * @var WP_Error
     */
    protected $WP_Error;

    /**
     * The post to be handled
     *
     * @var	WP_Post	$post	Post object.
     */
    protected $post;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wptelegram_options;
		
		$this->options = $wptelegram_options;
	}

	/**
	 * Handle Save Post
	 *
	 * @since 1.0.0
	 *
	 * @param $post_id  int
	 * @param $post     WP_Post
	 * @param $update   bool
	 */
	public function handle_save_post( $post_id, $post ) {

		do_action( 'wptelegram_post_init', $post );

		$this->post = $post;
		$post_edit_switch = $this->options['wordpress']['post_edit_switch'];

		/**
		 * Assuming that the post is coming from
		 * the default WordPress editing screen
		 * If it is, then we will verify the nonces
		 * Otherwise, no need
		 * This filter can be used to tell WP Telegram
		 * whether to verify nonces or not
		 */

		$classic_screen = (bool) apply_filters( 'wptelegram_classic_post_edit_screen', true, $post );

		if ( 'on' == $post_edit_switch && $classic_screen ) {
			// Check for nonce
		    if ( ! isset( $_POST['wptelegram_meta_box_nonce'] ) ) {
		    	return;
		    }
		    // Verify nonce
		    if ( ! wp_verify_nonce( $_POST['wptelegram_meta_box_nonce'], 'save_scheduled_post_meta' ) ) {
		    	return;
		    }
		}
		// Return if it's an AUTOSAVE
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
			return;
		}
		// Return if it's a post revision
	    if ( wp_is_post_revision( $post_id ) ){
	    	return;
	    }
	    $is_cron = ( defined( 'DOING_CRON' ) && DOING_CRON );
	    if ( ! $is_cron ) {
	    	// allow custom code to control authentication
	    	$user_has_permission = apply_filters( 'wptelegram_current_user_has_permission', false, $this->post );

			if ( 'page' == $post->post_type && ! ( current_user_can( 'edit_page', $post_id ) || $user_has_permission ) ){ // Return if user has not permissions to edit pages
				return;
			} elseif ( ! ( current_user_can( 'edit_post', $post_id ) || $user_has_permission ) ) { // Return if user has not permissions to edit posts
				return;
			}
	    }
		
		// If new status is not 'publish' or 'future'
		if ( 'publish' != $post->post_status && 'future' != $post->post_status ) {
			return;
		}

		if ( isset( $_POST['wptelegram_override_switch'] ) ) {
			$this->override_switch = sanitize_text_field( $_POST['wptelegram_override_switch'] );
		}
		$send_message = 'yes';
		if ( isset( $_POST['wptelegram_send_message'] ) ) {
			$send_message = sanitize_text_field( $_POST['wptelegram_send_message'] );
		}

		$this->chat_ids = $this->get_chat_ids( $post );

		if ( ! $this->options['telegram']['bot_token'] || ! $this->chat_ids ) {
			return;
		}

		$send_featured_image = $this->options['message']['send_featured_image'];
		$has_image = ( has_post_thumbnail( $post_id ) || apply_filters( 'wptelegram_post_featured_image_url', '', $post ) );
		$template = $this->get_message_template( $post );
		if ( ! $template && ( 'on' != $send_featured_image || ! $has_image ) ) {
			return;
		}
		if ( 'on' == $this->override_switch && 'no' == $send_message ) {
			return;
		}

		// If new status is future
		if ( 'future' == $post->post_status ) {
			$this->save_scheduled_post_meta( $post_id, $template, $send_message );
			return;
		}
		if ( ! apply_filters( 'wptelegram_filter_post', $post ) ) {
	    	return;
	    }
	    if ( ! $this->should_post_be_sent( $post, $send_message ) ) {
			return;
		}

		$this->prepare_message( $post, $post_id, $template );

		do_action( 'wptelegram_post_finish', $post );
	}

	/**
	 * Save Post meta of scheduled posts
	 *
	 * @since 1.3.0
	 *
	 * @param $post_id		int
	 * @param $template		string
	 * @param $send_message	string
	 *
	 */
	private function save_scheduled_post_meta( $post_id, $template, $send_message ) {

		$send_when = $this->options['wordpress']['send_when'];
		if ( ! in_array( 'send_new', $send_when ) && 'on' != $this->override_switch ){
			$send_message = 'no';
		}
		if ( ! add_post_meta( $post_id, 'wptelegram_send_message', $send_message, true ) ) { 
		   update_post_meta ( $post_id, 'wptelegram_send_message', $send_message );
		}
		// json_encode to avoid errors when saving multibyte emojis into database with no multibyte support
		$template = json_encode( $template );

		// escape slashes for storing the in database
		// wp_slash was introduced in WordPress 3.6
		if ( function_exists( 'wp_slash' ) ) {
			$template = wp_slash( $template );
		} else {
			$template = str_replace( '\\', '\\\\', $template );
		}
		if ( ! add_post_meta( $post_id, 'wptelegram_message_template', $template, true ) ) { 
		   update_post_meta ( $post_id, 'wptelegram_message_template', $template );
		}

		$chat_ids = implode( ',', $this->chat_ids );
		if ( ! add_post_meta( $post_id, 'wptelegram_chat_ids', $chat_ids, true ) ) { 
		   update_post_meta ( $post_id, 'wptelegram_chat_ids', $chat_ids );
		}
	}

	/**
	 * Handle Scheduled Post
	 *
	 * @since 1.0.8
	 *
	 * @param $post     WP_Post
	 */
	public function handle_future_to_publish( $post ) {
		$this->post = $post;
		$send_message = get_post_meta( $post->ID, 'wptelegram_send_message', true );
		if ( 'yes' != $send_message ) {
			$send_message = false;
		}

		$template = $this->get_message_template( $post, 'postmeta' );
		$send_featured_image = $this->options['message']['send_featured_image'];
		
		if ( ! $template && ( 'on' != $send_featured_image || ! has_post_thumbnail( $post_id )) ) {
			return;
		}
		if ( ! $template ) {
			$send_message = false;
		}
		$this->chat_ids = $this->get_chat_ids( $post, 'postmeta' );
		if ( ! $this->chat_ids ) {
			$send_message = false;
		}
		$this->delete_post_meta( $post->ID );
		if ( ! $send_message ) {
			return;
		}
		$this->prepare_message( $post, $post->ID, $template );
	}

	/**
	 * Decide whether the settings
	 * permit the post to be sent or not
	 *
	 * @since  1.0.2
	 *
	 * @param $post			WP_Post
	 * @param $send_message	string
	 *
	 * @return bool
	 */
	private function should_post_be_sent( $post, $send_message ) {
		if ( 'on' == $this->override_switch && 'yes' == $send_message ) {
			return true;
		}

		$post_types = $this->options['wordpress']['which_post_type'];
		$post_types = (array) apply_filters( 'wptelegram_send_post_types', $post_types );
		if ( ! in_array( $post->post_type, $post_types ) ) {
			return false;
		}

		$send_when = $this->options['wordpress']['send_when'];
		
		if ( ! in_array( 'send_new', $send_when ) && $post->post_date_gmt == $post->post_modified_gmt ){
			return false;
		}

		if ( ! in_array( 'send_updated', $send_when ) && $post->post_date_gmt != $post->post_modified_gmt ) {
			return false;
		}

		$from_terms = $this->options['wordpress']['from_terms'][0];

		if ( 'all' != $from_terms ) {
			$terms = (array) $this->options['wordpress']['terms'];
			$has_term = $this->has_term_or_its_descendants( $terms, $post );
			if ( 'selected' == $from_terms && ! $has_term ) {
				return false;
			} elseif ( 'not_selected' == $from_terms && $has_term ) {
				return false;
			}
		}

		$from_authors = $this->options['wordpress']['from_authors'][0];

		if ( 'all' != $from_authors ) {
			$authors = $this->options['wordpress']['authors'];
			$has_author = in_array( $post->post_author, $authors );

			if ( 'selected' == $from_authors && ! $has_author ) {
				return false;
			} elseif ( 'not_selected' == $from_authors && $has_author ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check if the post has any of given terms or their descendants
	 *
	 * @param int|array $terms The target terms. Integer ID or array of integer IDs
	 * @param int|object $_post The post. Omit to test the current post in the Loop or main query
	 * @return bool True if at least 1 of the post's terms is a descendant of any of the target terms
	 */
    private function has_term_or_its_descendants( $terms, $_post = null ) {
        foreach ( (array) $terms as $term ) {
        	list( $term_id, $taxonomy ) = explode( '@', $term );

        	if ( has_term( $term_id, $taxonomy, $_post ) ) {
	    		return true;
	    	}
            
            $descendants = get_term_children( (int) $term_id, $taxonomy );
            
            if ( $descendants && has_term( $descendants, $taxonomy, $_post ) ){
                return true;
            }
        }
        return false;
    }

	/**
	 * Delete stored post meta
	 *
	 * @since 1.3.5
	 *
	 * @param $post_id    int
	 *
	 */
	private function delete_post_meta( $post_id ) {
		$meta_keys = array(
			'send_message',
			'message_template',
			'chat_ids',
			);
		foreach ( $meta_keys as $key ) {
			delete_post_meta( $post_id, 'wptelegram_' . $key );
		}
	}

	/**
	 * Get the chat IDs
	 *
	 * @since 1.3.0
	 *
	 * @param $post    WP_Post
	 * @param $from    string 'options', 'postmeta' or metabox
	 *
	 * @return array
	 */
	private function get_chat_ids( $post, $from = 'options' ) {

		$chat_ids = $this->options['telegram']['chat_ids'];
		$chat_ids = explode( ',', $chat_ids );

		$chat_ids = apply_filters( 'wptelegram_post_to_chat_ids', $chat_ids, $post );
		// Check the chat_ids from meta box
		if ( 'on' == $this->override_switch && ! isset( $_POST['wptelegram_send_to'] ) ) {
			return false;
		} elseif ( isset( $_POST['wptelegram_send_to'] ) ){
			if ( ! isset( $_POST['wptelegram_send_to']['all'] ) ) {
				$meta_chat_ids = array_map( 'sanitize_text_field', $_POST['wptelegram_send_to'] );
				$chat_ids = array_intersect( $chat_ids, $meta_chat_ids );
			}
		} elseif ( 'postmeta' == $from ) {
			$chat_ids = get_post_meta( $post->ID, 'wptelegram_chat_ids', true );
			$chat_ids = explode( ',', $chat_ids );
		}
		return $chat_ids;
	}

	/**
	 * Get the message template for the post
	 *
	 * @since 1.3.0
	 *
	 * @param $post    WP_Post
	 * @param $from    string 'options', 'postmeta' or metabox
	 *
	 * @return string
	 */
	private function get_message_template( $post, $from = 'options' ) {
		$is_template = isset( $_POST['wptelegram_message_template'] );
		$json = true;
		if ( $is_template && 'on' == $this->override_switch ) {
			$template = wptelegram_sanitize_message_template( stripslashes( $_POST['wptelegram_message_template'] ), 'noJSON' );
			$json = false;
		} elseif ( 'postmeta' == $from ) {
			$template = get_post_meta( $post->ID, 'wptelegram_message_template', true );
		} else {
			$template = $this->options['message']['message_template'];
		}
		if ( $json ){
			$template = ( $template )? json_decode( $template ) : '';
			$template = apply_filters( 'wptelegram_message_template', $template, $post);
		}
		return $template;
	}

	/**
	 * Prepare Message
	 *
	 * @since 1.0.0
	 *
	 * @param $post     WP_Post
	 * @param $post_id  int
	 * @param $template string
	 */
	private function prepare_message( $post, $post_id, $template ) {

		$excerpt_source = $this->options['message']['excerpt_source'];
		$excerpt_length = $this->options['message']['excerpt_length'];
		$parse_mode = $this->options['message']['parse_mode'];

		switch ( $parse_mode ) {
			case 'Markdown':
			$parse_mode = 'Markdown';
				break;
			case 'HTML':
			$parse_mode = 'HTML';
				break;
			case 'none':
			$parse_mode = null;
				break;
			default:
			$parse_mode = null;
				break;
		}

		if( 'post' == $post->post_type || 'page' == $post->post_type ) {
			$tags_arr = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
			$cats_arr = wp_get_post_categories( $post_id, array( 'fields' => 'names' ) );	
		} elseif( 'product' == $post->post_type ){
			$pf = new WC_Product_Factory();
			$product = $pf->get_product( $post_id );
			$tags_arr = explode( ', ' , $product->get_tags() );
			$tags_arr = array_map( 'strip_tags', $tags_arr );

			$cats_arr = explode( ', ' , $product->get_categories() );
			$cats_arr = array_map( 'strip_tags', $cats_arr );
		} else {
			$tags_arr = apply_filters( 'wptelegram_cpt_tags', array(), $post );
			if ( ! is_array( $tags_arr ) ) {
				$tags_arr = array();
			}
			$cats_arr = apply_filters( 'wptelegram_cpt_cats', array(), $post );
			if ( ! is_array( $cats_arr ) ) {
				$cats_arr = array();
			}
		}

		/* Post Title */
		$title = get_the_title( $post );
		$title = apply_filters( 'wptelegram_post_title', $title, $post );

		/* The post's local publication time */
		$date = get_the_date( '', $post_id );
		$post_date = apply_filters( 'wptelegram_post_date', $date, $post );

		/* The post's GMT publication time */
		$post_date_gmt = date_i18n( get_option( 'date_format' ), strtotime( $post->post_date_gmt ) );
		$post_date_gmt = apply_filters( 'wptelegram_post_date_gmt', $post_date_gmt, $post );
		
		/* Post Author */
		$author = get_the_author_meta( 'display_name', $post->post_author );
		$author = apply_filters( 'wptelegram_post_author', $author, $post );

		/* Post Excerpt */
		if ( 'before_more' == $excerpt_source ) {
			$parts = get_extended ( get_post_field( 'post_content', $post ) );
			$excerpt = wp_trim_words( $parts['main'], $excerpt_length, '...' );
		} else {
			/*
			 * change
			 * post_content to the_content
			 * post_excerpt to the_excerpt
			 */
			$filter_tag = str_replace( 'post', 'the', $excerpt_source );
			$excerpt = get_post_field( $excerpt_source, $post );
			// if the_content or the_excerpt filter should be applied
			if ( apply_filters( "wptelegram_apply_{$filter_tag}_filter", true, $post ) ) {
				$excerpt = apply_filters( $filter_tag, $excerpt );
			}

			$excerpt = wp_trim_words( $excerpt, $excerpt_length, '...' );
		}
		$excerpt = apply_filters( 'wptelegram_post_excerpt', $excerpt, $post );

		/* Post Content */
		$content = get_post_field( 'post_content', $post );
		// if the_content filter should be applied
		if ( apply_filters( 'wptelegram_apply_the_content_filter', true, $post ) ) {
			$content = apply_filters( 'the_content', $content );
		}
		$content = trim( strip_tags( html_entity_decode( $content ), '<b><strong><em><i><a><pre><code>' ) );

		$content = apply_filters( 'wptelegram_post_content', $content, $post );
		// if shortcodes should be stripped
		if ( apply_filters( 'wptelegram_strip_shortcodes', true, $post ) ) {
			$content = trim( strip_shortcodes( $content ) );
		}
		
		/* Post Tags */
		$tags = ( ! empty( $tags_arr ) && '' != $tags_arr[0] ) ? '#' . implode( ' #', $tags_arr ) : '';
		$tags = apply_filters( 'wptelegram_post_tags', $tags, $post );

		/* Post Categories */
		$cats = ( ! empty( $cats_arr ) && '' != $cats_arr[0] ) ? implode( '|', $cats_arr ) : '';
		$categories = apply_filters( 'wptelegram_post_categories', $cats, $post );

		$macro_values = array(
			'{ID}'				=>	$post_id,
			'{title}'			=>	$title,
			'{post_date}'		=>	$post_date,
			'{post_date_gmt}'	=>	$post_date_gmt,
			'{author}'			=>	$author,
			'{excerpt}'			=>	$excerpt,
			'{content}'			=>	$content,
			'{short_url}'		=>	wp_get_shortlink( $post_id ),
			'{full_url}'		=>	get_permalink( $post_id ),
			'{tags}'			=>	$tags,
			'{categories}'		=>	$categories,
		);

		/**
         * Use this filter to replace your own macros
         * with the corresponding values
         */
		$macro_values = (array) apply_filters( 'wptelegram_macro_values', $macro_values, $post );

		$markdown_search = array( '_', '*', '[' );
		$markdown_replace = array( '\_', '\*', '\[' );

		$text = $template;

		foreach ( $macro_values as $macro => $macro_value ) {
			if( 'Markdown' == $parse_mode ){
				$macro_value = str_replace( $markdown_search, $markdown_replace, $macro_value );
			}
			$text = str_replace( $macro, $macro_value, $text );
		}

		// replace taxonomy with its terms from the post
		if ( preg_match_all( '/(?<=\{\[)[a-z_]+?(?=\]\})/iu', $text, $matches ) ) {
			foreach ( $matches[0] as $taxonomy ) {
				$replace = '';
				if ( taxonomy_exists( $taxonomy ) ) {
					$terms = get_the_terms( $post->ID, $taxonomy );
					if ( ! empty( $terms ) ) {
						$names = array();
						foreach ( $terms as $term ) {
							$name = $term->name;
							if ( 'Markdown' == $parse_mode ) {
								$name = str_replace( $markdown_search, $markdown_replace, $name );
							}
							$names[] = $name;
						}
						if ( is_taxonomy_hierarchical( $taxonomy ) ) {
							$replace = implode( ' | ', $names );
						} else {
							$replace = '#'.implode( ' #', $names );
						}
					}
				}
				$replace = apply_filters( 'wptelegram_replace_macro_taxonomy', $replace, $taxonomy, $post );

				$text = str_replace( '{[' . $taxonomy . ']}', $replace, $text );
			}
		}

		// replace custom fields with their values
		if ( preg_match_all( '/(?<=\{\[\[).+?(?=\]\]\})/u', $text, $matches ) ) {
			foreach ( $matches[0] as $meta_key ) {
				$meta_value = (string) get_post_meta( $post_id, $meta_key, true );

				$meta_value = apply_filters( 'wptelegram_replace_macro_custom_field', $meta_value, $meta_key, $post );
				
				if ( 'Markdown' == $parse_mode ) {
					$meta_value = str_replace( $markdown_search, $markdown_replace, $meta_value );
				}
				$text = str_replace( '{[[' . $meta_key . ']]}', $meta_value, $text );
			}
		}
		$text = html_entity_decode( $text );
		if ( 'Markdown' != $parse_mode ) {
			$text = stripslashes( $text );
		}

		$text = $this->filter_text( $text, $parse_mode );

		$featured_image = false;
		$caption = '';

		// pass by reference
		$this->set_featured_image_location( $featured_image, $caption );

		$this->prepare_post_data( $text, $parse_mode, $featured_image, $caption );
	}

	/**
	 * Set Featured Image location and caption
	 *
	 * @since 1.6.2
	 *
	 * @param	string	$location
	 * @param 	string	$caption
     *
	 */
	private function set_featured_image_location( &$location, &$caption ){
		$send_image = $this->options['message']['send_featured_image'];
		if ( 'on' == $send_image && has_post_thumbnail( $this->post->ID ) ) {
			// post thumbnail ID
			$thumbnail_id = get_post_thumbnail_id( $this->post->ID );
			/**
			 * Pass false to upload the file
			 * instead of sending as URL
			 */
			if ( apply_filters( 'wptelegram_send_image_by_url', true, $this->post ) ) {
				// featured image url
				$location = wp_get_attachment_url( $thumbnail_id );
				$location = apply_filters( 'wptelegram_post_featured_image_url', $location, $this->post );
			} else {
				$location = get_attached_file( $thumbnail_id );
				$location = apply_filters( 'wptelegram_post_featured_image_path', $location, $this->post );
				// modify curl for file upload
				add_filter( 'wptelegram_modify_curl_handle', '__return_true' );
			}
			// image caption from Media Library
			$caption = get_post_field( 'post_excerpt', $thumbnail_id );
		}
	}

	/**
	 * Prepare POST data
	 *
	 * @since 1.0.0
	 *
	 * @param $text 	          string
	 * @param $parse_mode 		  string
	 * @param $featured_image string
	 *
	 */
	private function prepare_post_data( $text, $parse_mode, $featured_image, $caption ){

		$misc_opts = $this->options['message']['misc'];
		$disable_web_page_preview = (bool) $misc_opts['disable_web_page_preview'];
		$disable_notification = (bool) $misc_opts['disable_notification'];
		$no_message_as_reply = (bool) $misc_opts['no_message_as_reply'];

		$method_params = array(
			'sendPhoto'	  => compact(
					'parse_mode',
					'disable_notification'
				),
			'sendMessage' => compact(
					'parse_mode',
					'disable_notification',
					'disable_web_page_preview'
				),
		);
		if ( ! $no_message_as_reply ) {
			$method_params['sendPhoto']['reply_to_message_id'] = NULL;
			$method_params['sendMessage']['reply_to_message_id'] = NULL;
		}

		if ( $featured_image ) {
			$image_position = $this->options['message']['image_position'];
			$image_style = $this->options['message']['image_style'];
			$caption_source = $this->options['message']['caption_source'];
			if ( 'before' == $image_position ) {
				if ( 'with_caption' == $image_style ) {
					// pass $caption and $text by reference
					$this->filter_caption_text( $caption, $text, $caption_source );
					$caption = apply_filters( 'wptelegram_post_image_caption', $caption, $this->post );
				} else {
					$caption = '';
				}
			} else {
				$attach_image = $this->options['message']['attach_image'];
				if ( 'on' == $attach_image && null != $parse_mode ) {
					$text = $this->add_hidden_image_url( $text, $featured_image, $parse_mode );
					unset( $method_params['sendPhoto'] );
				} elseif ( 'with_caption' == $image_style && 'message_template' != $caption_source ) {
					// pass $caption and $text by reference
					$this->filter_caption_text( $caption, $text, 'media_library' );
				} else {
					$caption = '';
				}
				$method_params = array_reverse( $method_params );
			}
			if ( isset( $method_params['sendPhoto'] ) ) {
				$method_params['sendPhoto']['photo'] = $featured_image;
				$method_params['sendPhoto']['caption'] = $caption;
			}
		} else {
			unset( $method_params['sendPhoto'] );
		}
		if ( ! $text ) {
			unset( $method_params['sendMessage'] );
		} else {
			$method_params['sendMessage']['text'] = $text;
		}

		// check for send files
		if ( 'on' == $this->override_switch && isset( $_POST['wptelegram_send_files'] ) ) {

			add_action( 'wptelegram_after_post_sent', array( $this, 'send_files' ), 10, 4 );
		}

		$method_params = apply_filters( 'wptelegram_post_method_params', $method_params, $this->post );

		$this->send( $method_params );
	}

	/**
	 * Send the message
	 *
	 * @since 1.3.6
	 *
	 * @param 	array	$method_params
     *
	 */
	private function send( $method_params ){
		// Remove query variable, if present
		remove_query_arg( 'wptelegram' );
		
		$this->tg_api = new WPTelegram_Bot_API( $this->options['telegram']['bot_token'] );

		$responses = array();
		
		/**
		 * add $responses to the params
		 * to avoid confusion in params between 
		 * before_post_sent and after_post_sent
		 */
		do_action( 'wptelegram_before_post_sent', $this->post, $responses, $this->tg_api, $this->chat_ids );

		// if modify curl for WP Telegram
		if ( (bool) apply_filters( 'wptelegram_modify_curl_handle', false ) ) {
            // modify curl
            add_action( 'http_api_curl', array( $this, 'modify_http_api_curl' ), 10, 3 );
        }

		foreach ( $this->chat_ids as $chat_id ) {
			$res = false;
			foreach ( (array) $method_params as $method => $params ) {
				$params['chat_id'] = $chat_id;
				if ( ! is_wp_error( $res ) && $res && 200 == $res->get_response_code() && array_key_exists( 'reply_to_message_id', $params ) ) {

					$result = $res->get_result();
					$params['reply_to_message_id'] = $result ? $result['message_id'] : NULL;
				}
				/**
			     * Filters the params for the Telegram API method
			     *
			     * It can be used to modify the behavior
			     * in a number of ways
			     * You can use it to change the text based on the channel/chat
			     *
			     * @since 1.6.4
			     *
			     * @param array		$params		The parameters for the method
			     * @param string	$method		The method name
			     * @param WP_Post	$post		The post being handled
			     */
				$params = apply_filters( 'wptelegram_api_method_params', $params, $method, $this->post );

				$res = call_user_func( array( $this->tg_api, $method ), $params );
				$responses[] = $res;
				do_action( 'wptelegram_post_response', $res, $this->post );
			}
			if ( is_wp_error( $res ) ) {
		        $this->handle_wp_error( $res );
			}
		}

		// remove cURL modification
        remove_action( 'http_api_curl', array( $this, 'modify_http_api_curl' ), 10, 3 );

		do_action( 'wptelegram_after_post_sent', $this->post, $responses, $this->tg_api, $this->chat_ids );
	}

	/**
	 * Add the file methods to handle the custom files
	 *
	 * @since 1.8.0
	 *
	 * @param 	array	$method_params
     *
	 */
	public function send_files( $post, $responses, $tg_api, $chat_ids ) {

		$files = $_POST['wptelegram_send_files'];

		$types = array( 'photo', 'audio', 'video', 'document' );

		$send_files = array();

		$file_by_url = apply_filters( 'wptelegram_send_file_by_url', true );

		if ( ! empty( $files ) ) {
			foreach ( $files as $id => $file ) {
				$type = in_array( $file['type'], $types ) ? $file['type'] : 'document';
				$send_files[] = array(
					'send' . ucfirst( $type ) => array(
						$type => $file_by_url ? $file['url'] : get_attached_file( $id ),
					),
				);
			}
		}

		$send_files = (array) apply_filters( 'wptelegram_post_send_files', $send_files, $post, $chat_ids );

		$chat_ids = (array) apply_filters( 'wptelegram_send_files_chat_ids', $chat_ids, $send_files, $post );

		if ( ! empty( $send_files ) && ! empty( $chat_ids ) ) {
			// if modify curl for WP Telegram
			if ( ! $file_by_url ) {
	            // modify curl
	            add_action( 'http_api_curl', array( $this, 'modify_http_api_curl' ), 10, 3 );
	        }

			foreach ( $chat_ids as $chat_id ) {

				foreach ( $send_files as $send_file ) {

					$params = reset( $send_file );
					$method = key( $send_file );

					$params['chat_id'] = $chat_id;
					
					/**
				     * Filters the params for the Telegram API method
				     *
				     * It can be used to modify the behavior
				     * in a number of ways
				     * You can use it to change the caption based on the channel/chat
				     *
				     * @since 1.8.0
				     *
				     * @param array		$params		The parameters for the method
				     * @param string	$method		The method name
				     * @param WP_Post	$post		The post being handled
				     */
					$params = apply_filters( 'wptelegram_send_file_params', $params, $method, $post );

					$res = call_user_func( array( $tg_api, $method ), $params );
				}
			}
		}
	}

	/**
	 * setup the proxy
	 *
	 * @since  1.7.8
	 */
	public function proxy_setup( $request ) {

		// if proxy is enabled for the Whole API
		if ( (bool) apply_filters( 'wptelegram_bot_api_use_proxy', false ) ) {

			$script_url = $this->options['proxy']['script_url'];
			// give priority to Google Script
			if ( ! empty( $script_url ) ) {

				// setup Google Script args
				add_filter( 'wptelegram_bot_api_remote_post_args', array( $this, 'google_script_request_args' ), 20, 2 );
				// set URL
				add_filter( 'wptelegram_bot_api_request_url', array( $this, 'google_script_request_url' ), 20, 1 );

			} else {

				add_filter( 'wptelegram_bot_api_curl_proxy', array( $this, 'configure_curl_proxy' ), 10, 1 );
				// set curl modification to true
				add_filter( 'wptelegram_bot_api_modify_curl_handle', '__return_true' );
			}
		}
	}

	/**
	 * Set Google Script args
	 *
	 * @since  1.7.8
	 */
	public function google_script_request_args( $args, $request ) {

		$args['body'] = array(
			'bot_token'	=> $request->get_bot_token(),
			'method'	=> $request->get_endpoint(),
			'args'		=> json_encode( $args['body'] ),
		);
		$args['method'] = 'GET';

		return $args;
	}

	/**
	 * Set Google Script URL
	 *
	 * @since  1.7.8
	 */
	public function google_script_request_url( $url ) {

		return $this->options['proxy']['script_url'];
	}

	/**
	 * Configure the proxy
	 *
	 * @since  1.7.6
	 */
	public function configure_curl_proxy( $proxy = array() ) {

		// if nothing set
		if ( empty( $proxy ) ) {
			$defaults = array(
				'host'		=> '',
				'port'		=> '',
				'type'		=> '',
				'username'	=> '',
				'password'	=> '',
			);
			$proxy = wp_parse_args( $proxy, $defaults );

			// get the values from settings/defaults
			foreach ( $proxy as $key => $value ) {
				$proxy[ $key ] = $this->options['proxy']['proxy_' . $key ];
			}
		}
		return $proxy;
	}

    /**
     * Modify cURL handle
     * The method is not used by default
     * but can be used to modify
     * the behavior of cURL requests
     *
     * @since 1.0.0
     *
     * @param resource $handle  The cURL handle (passed by reference).
     * @param array    $r       The HTTP request arguments.
     * @param string   $url     The request URL.
     *
     * @return string
     */
    public function modify_http_api_curl( &$handle, $r, $url ) {
        $to_telegram = ( 0 === strpos( $url, 'https://api.telegram.org/bot' ) );
        $by_wptelegram = ( isset( $r['headers']['wptelegram_bot'] ) && $r['headers']['wptelegram_bot'] );
        // if the request is sent to Telegram by WP Telegram
        if ( $to_telegram && $by_wptelegram ) {
        	
        	// for backward compatibility
        	$image_by_url = apply_filters( 'wptelegram_send_image_by_url', true );
        	
        	$file_by_url = apply_filters( 'wptelegram_send_file_by_url', true );
            /**
             * Modify for files
             */
            if ( ! ( $image_by_url && $file_by_url ) ) {

            	$types = array( 'photo', 'audio', 'video', 'document' );

            	foreach ( $types as $type ) {
            		if ( isset( $r['body'][ $type ] ) && file_exists( $r['body'][ $type ] ) ) {
	                    // PHP >= 5.5
	                    if ( function_exists( 'curl_file_create' ) ) {
	                        $r['body'][ $type ] = curl_file_create( $r['body'][ $type ] );
	                    } else {
	                        // Create a string with file data
	                        $r['body'][ $type ] = '@' . $r['body'][ $type ] . ';type=' . mime_content_type( $r['body'][ $type ] ) . ';filename=' . basename( $r['body'][ $type ] );
	                    }
	                    curl_setopt( $handle, CURLOPT_POSTFIELDS, $r['body'] );
	                }
            	}
            }
        }
    }

    /**
     * Handle WP_Error of wp_remote_post()
     *
     * @since  1.5.0
     */
    private function handle_wp_error( $WP_Error ) {
    	set_site_transient( 'wptelegram_http_error', $WP_Error, 45 );
        $this->WP_Error = $WP_Error;
        add_filter( 'redirect_post_location', array( $this, 'add_admin_notice_query_var' ), 99 );
    }

    /**
     * Add query variable upon error
     *
     * @since  1.5.0
     */
    public function add_admin_notice_query_var( $location ) {
        remove_filter( 'redirect_post_location', array( $this, __FUNCTION__ ), 99 );
        return add_query_arg( array( 'wptelegram' => $this->WP_Error->get_error_code() ), $location );
    }

	/**
	 * Add hidden URL at the beginning of the text
	 *
	 * @since 1.3.6
	 *
	 * @param 	string	$text
	 * @param 	string	$image_url
	 * @param 	string 	$parse_mode
     *
     * @return string
	 */
	private function add_hidden_image_url( $text, $image_url, $parse_mode ){
		if ( 'HTML' == $parse_mode ) {
			// Add Zero Width Non Joiner &#8204; as the anchor text
			$string = '<a href="' . $image_url . '">&#8204;</a>';
		} else {
			// Add hidden Zero Width Non Joiner between "[" and "]"
			$string = '[‌](' . $image_url . ')';
		}
		return $string . $text;
	}

	/**
	 * Get caption and Text
	 *
	 * @since 1.3.5
	 *
	 * @param	string	$caption
	 * @param 	string	$text
	 * @param 	string	$caption_source
     *
     * @return array
	 */
	private function filter_caption_text( &$caption, &$text, $caption_source ){
		if ( ! $caption && 'media_library' == $caption_source ) {
			return;
		}
		$with_text = $text ? true : false;
		if ( ! $caption || 'message_template' == $caption_source ) {
			$caption = $text;
			$text = false;
		}
		// use Media Library caption
		$caption_len = mb_strlen( $caption, 'UTF-8' );
		if ( $caption_len > 200 ) {
			$len = '199';
			$more = '…';
		} else {
			$len = '200';
			$more = '';
		}
		// use regex instead of mb_substr to preserve words
		$pattern = '/.{1,' . $len . '}(?=\s|$)/us';
		preg_match( $pattern, $caption, $cap_match );
		
		$start = mb_strlen( $cap_match[0], 'UTF-8' );
		if ( false == $text && $with_text ) {
			$text = mb_substr( $caption, $start, $caption_len, 'UTF-8' );
		}
		$caption = $cap_match[0] . $more;
	}

	/**
	 * Filter Text
	 *
	 * @since 1.1.0
	 *
	 * @param $text 	  string
	 * @param $parse_mode string
     *
     * @return string
	 */
	private function filter_text( $text, $parse_mode ){
		if ( 'HTML' == $parse_mode ) {
			// remove unnecessary tags
			$text = strip_tags( $text, '<b><strong><em><i><a><pre><code>' );

			// remove <em> if <a> is nested in it
			$pattern = '#(<em>)((.+)?<a\s+(?:[^>]*?\s+)?href=["\']?([^\'"]*)["\']?.*?>(.*?)<\/a>(.+)?)(<\/em>)#iu';
			$text = preg_replace( $pattern, '$2', $text);

			// remove <strong> if <a> is nested in it
			$pattern = '#(<strong>)((.+)?<a\s+(?:[^>]*?\s+)?href=["\']?([^\'"]*)["\']?.*?>(.*?)<\/a>(.+)?)(<\/strong>)#iu';
			$text = preg_replace( $pattern, '$2', $text );

			// remove <b> if <a> is nested in it
			$pattern = '#(<b>)((.+)?<a\s+(?:[^>]*?\s+)?href=["\']?([^\'"]*)["\']?.*?>(.*?)<\/a>(.+)?)(<\/b>)#iu';
			$text = preg_replace( $pattern, '$2', $text );

			// remove <i> if <a> is nested in it
			$pattern = '#(<i>)((.+)?<a\s+(?:[^>]*?\s+)?href=["\']?([^\'"]*)["\']?.*?>(.*?)<\/a>(.+)?)(<\/i>)#iu';
			$text = preg_replace( $pattern, '$2', $text );

			$text = $this->handle_html_chars( $text );
		} else {
			$text = strip_tags( $text );
			if ( 'Markdown' == $parse_mode ) {
				$text = preg_replace_callback( '/\*(.+?)\*/su', 'wptelegram_replace_nested_markdown', $text );
			}
		}
		return $text;
	}

    /**
     * Convert the character into html code
	 *
	 * @since 1.2.0
     *
     * @param $match array
     *
     * @return string
     */
    private function get_htmlentities( $match ) {
    	return htmlentities( $match[0] );
    }

    /**
     * Replace HTML special characters with their codes
	 *
	 * @since 1.0.0
	 *
     * @param $text string
     *
     * @return string
     */
    private function handle_html_chars( $text ) {
        $pattern = '#(?:<\/?)(?:(?:a(?:[^<>]+?)?>)|(?:b>)|(?:strong>)|(?:i>)|(?:em>)|(?:pre>)|(?:code>))(*SKIP)(*FAIL)|[<>&]+#iu';
        
        $filtered = preg_replace_callback( $pattern, array( $this, 'get_htmlentities' ), $text );

        return $filtered;
    }
}