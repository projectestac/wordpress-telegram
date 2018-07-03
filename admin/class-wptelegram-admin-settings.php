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
 * The admin-specific settings of the plugin.
 *
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WPTelegram
 * @subpackage WPTelegram/admin
 * @author     Manzoor Wani
 */
class WPTelegram_Admin_Settings {

	/**
	 * Inbuilt and Registered Post Types
	 *
	 * @since  	1.2.0
	 * @access 	private
	 * @var array $post_types Post Types
	 */
	private $post_types;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

	}

    /**
     * get all the settings sections
     *
     * @since    1.2.0
     * @return array settings fields
     */
    public function get_settings_sections() {
        $sections = array(
            array(
                'id'       => 'wptelegram_telegram',
                'title'    => __( 'Telegram Settings', 'wptelegram' ),
                'callback' => array( $this, 'wptelegram_telegram_cb' ),
                'icon_src' => WPTELEGRAM_URL . '/admin/icons/telegram.svg',
            ),
            array(
                'id'       => 'wptelegram_wordpress',
                'title'    => __( 'WordPress Settings', 'wptelegram' ),
                'desc'     => __( 'In this section you can teach WordPress how and when to do the job', 'wptelegram' ),
                'icon_src' => WPTELEGRAM_URL . '/admin/icons/wordpress.svg',
            ),
            array(
                'id'       => 'wptelegram_message',
                'title'    => __( 'Message Settings', 'wptelegram' ),
                'desc'     => __( 'In this section you can change the way messages are sent to Telegram', 'wptelegram' ),
                'icon_src' => WPTELEGRAM_URL . '/admin/icons/message.svg',
            ),
            array(
                'id'       => 'wptelegram_notify',
                'title'    => __( 'Notification Settings', 'wptelegram' ),
                'callback' => array( $this, 'wptelegram_notify_cb' ),
                'icon_src' => plugins_url( 'icons/notify.svg' , __FILE__ ),
            ),
        );

        if ( (bool) apply_filters( 'wptelegram_bot_api_use_proxy', false ) ) {
            $sections[] = array(
                'id'    => 'wptelegram_proxy',
                'desc'  => __( 'Disclaimer! Use the proxy at your own risk!', 'wptelegram' ),
                'title' => __( 'Proxy Settings', 'wptelegram' ),
            );
        }
        return apply_filters('wptelegram_admin_settings_sections_array', $sections);
    }

    /**
     * get all the settings fields
	 *
	 * @since    1.2.0
     * @return array settings fields
     */
    public function get_settings_fields() {
        $settings_fields = array(
            'wptelegram_telegram' => array(
                array(
                    'name'              => 'bot_token',
                    'label'             => __( 'Bot Token', 'wptelegram' ),
                    'desc'              => __( 'Please read the instructions above', 'wptelegram' ),
                    'placeholder'       => __( 'e.g.', 'wptelegram' ) . ' 123456789:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                    'type'              => 'text',
                    'sanitize_callback' => 'wptelegram_sanitize_bot_token',
                    'events'            => array(
                        'onblur' => 'validateToken("telegram")',
                    ),
                    'button'            => array(
                        'name'  => __( "Test Token", 'wptelegram' ),
                        'id'    => 'checkbot',
                        'class' => 'button-secondary',
                        'events'=> array(
                            'onclick' => 'getMe("telegram")',
                        ),
                    ),
                ),
                array(
                    'name'        => 'bot_html',
                    'desc'        => $this->bot_token_html_cb( 'telegram' ),
                    'type'        => 'html'
                ),
                array(
                    'name'              => 'chat_ids',
                    'label'             => __( 'Channel Username(s) or Chat ID(s)', 'wptelegram' ),
                    'desc'              => __( 'If more than one, separate them by comma', 'wptelegram' ),
                    'placeholder'       => __( 'e.g.', 'wptelegram' ) . ' @WPTelegram,-12345678998765',
                    'type'              => 'text',
                    'sanitize_callback' => 'wptelegram_sanitize_chat_ids',
                    'events'            => array(
                        'onblur' => 'getChatInfo("telegram")',
                    ),
                    'button'            => array(
                        'name'  => __('Send Test', 'wptelegram'),
                        'id'    => 'wptelegram-chat-ids-btn',
                        'class' => 'button-secondary',
                        'events'=> array(
                            'onclick' => 'sendMessage("telegram")',
                        ),
                    ),
                ),
                array(
                    'name'        => 'chat_html',
                    'desc'        => $this->chat_ids_html_cb('telegram'),
                    'type'        => 'html'
                ),
            ),
            'wptelegram_wordpress' => array(
                array(
                    'name'     => 'send_when',
                    'label'    => __( 'When to send?', 'wptelegram' ),
                    'desc'     => '',
                    'type'     => 'multicheck',
                    'as_array' => true,
                    'default'  => 'send_new',
                    'options'  => array(
                        'send_new'     => __( 'When publishing a new post', 'wptelegram' ),
                        'send_updated' => __( 'Updating an existing post', 'wptelegram' ),
                    ),
                    'sanitize_callback' => 'wptelegram_sanitize_array',
                ),
                array(
                    'name'     => 'which_post_type',
                    'label'    => __( 'Which post type(s) to send?', 'wptelegram' ),
                    'desc'     => '',
                    'type'     => 'multicheck',
                    'as_array' => true,
                    'default'  => array( 'post' ),
                    'options'  => $this->get_post_types(),
                    'sanitize_callback' => 'wptelegram_sanitize_array',
                ),
                array(
                    'name'    => 'from_terms',
                    'label'   => __( 'Categories/Terms', 'wptelegram' ),
                    'type'    => 'select',
                    'class'   => 'no-fancy',
                    'default' => 'all',
                    'sanitize_callback' => 'wptelegram_sanitize_array',
                    'options' => array(
                        'all'           => __( 'Post from all Categories/Terms', 'wptelegram' ),
                        'selected'      => __( 'Post only from selected ones', 'wptelegram' ),
                        'not_selected'  => __( 'Do not post from selected ones', 'wptelegram' ),
                    ),
                ),
                array(
                    'name'      => 'terms',
                    'label'     => '',
                    'desc'      => __( 'The rule will apply to the selected categories/terms and their children', 'wptelegram' ),
                    'type'      => 'select',
                    'multiple'  => true,
                    'grouped'   => true,
                    'sanitize_callback' => 'wptelegram_sanitize_array',
                    'options'   => self::get_all_terms(),
                ),
                array(
                    'name'    => 'from_authors',
                    'label'   => __( 'Authors', 'wptelegram' ),
                    'type'    => 'select',
                    'class'   => 'no-fancy',
                    'default' => 'all',
                    'sanitize_callback' => 'wptelegram_sanitize_array',
                    'options' => array(
                        'all'           => __( 'Post from all Authors', 'wptelegram' ),
                        'selected'      => __( 'Post only from selected ones', 'wptelegram' ),
                        'not_selected'  => __( 'Do not post from selected ones', 'wptelegram' ),
                    ),
                ),
                array(
                    'name'      => 'authors',
                    'label'     => '',
                    'type'      => 'select',
                    'multiple'  => true,
                    'desc'      => '<b>' . __( 'Note:', 'wptelegram' ) . ' </b>' . __( 'The authors not chosen to post from, will not see the ON/OFF switch on the post edit screen', 'wptelegram' ),
                    'sanitize_callback' => 'wptelegram_sanitize_array',
                    'options'   => self::get_all_authors(),
                ),
                array(
                    'name'     => 'post_edit_switch',
                    'label'    => __( 'When editing a post', 'wptelegram' ),
                    'desc'     => __( 'You can use this switch to override the above settings for a particular post', 'wptelegram' ),
                    'desc_tip' => __( 'Show an ON/OFF switch on the post edit screen', 'wptelegram' ),
                    'default'  => 'on',
                    'type'     => 'checkbox',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
            'wptelegram_message' => array(
                array(
                    'name'              => 'message_template',
                    'label'             => __( 'Message Template', 'wptelegram' ),
                    'desc'              => __( 'Structure of the message to be sent', 'wptelegram' ),
                    'placeholder'       => __( 'e.g.', 'wptelegram' ) . "\n{title}\n{full_url}",
                    'type'              => 'textarea',
                    'default'           => json_encode( "{title}\n{full_url}" ),
                    'sanitize_callback' => 'wptelegram_sanitize_message_template',
                    'json_encoded'      => true,
                    'emoji_container'   => true
                ),
                array(
                    'name'        => 'html',
                    'desc'        => $this->message_template_desc_cb(),
                    'type'        => 'html'
                ),
                array(
                    'name'    => 'excerpt_source',
                    'label'   => __( 'Excerpt Source', 'wptelegram' ),
                    'desc'    => '',
                    'type'    => 'radio',
                    'default' => 'post_content',
                    'sanitize_callback' => 'sanitize_text_field',
                    'options' => array(
                        'post_content'  => __( 'Post Content', 'wptelegram' ),
                        'before_more'  => __( 'Post Content before Read More tag', 'wptelegram' ),
                        'post_excerpt'  => __( 'Post Excerpt', 'wptelegram' ),
                    ),
                ),
                array(
                    'name'              => 'excerpt_length',
                    'label'             => __( 'Excerpt Length', 'wptelegram' ),
                    'desc'              => __( 'Number of words for the excerpt. Won\'t be used when Caption Source is "Post Content before Read More tag"', 'wptelegram' ),
                    'placeholder'       => '55',
                    'min'               => 1,
                    'max'               => 300,
                    'step'              => '1',
                    'type'              => 'number',
                    'default'           => 55,
                    'sanitize_callback' => 'wptelegram_sanitize_excerpt_length'
                ),
                array(
                    'name'    => 'parse_mode',
                    'label'   => __( 'Parse Mode', 'wptelegram' ),
                    'desc'    => '<a href="'. esc_url( 'https://core.telegram.org/bots/api/#formatting-options' ) . '" target="_blank">' . __( 'Learn more', 'wptelegram' ) . '</a>',
                    'type'    => 'radio',
                    'default' => 'none',
                    'sanitize_callback' => 'sanitize_text_field',
                    'options' => array(
                        'none'      => __( 'None', 'wptelegram' ),
                        'Markdown'  => __( 'Markdown style', 'wptelegram' ),
                        'HTML'      => __( 'HTML style', 'wptelegram' ),
                    ),
                ),
                array(
                    'name'     => 'send_featured_image',
                    'label'    => __( 'Featured Image', 'wptelegram' ),
                    'desc_tip' => __( 'Send Featured Image (if exists)', 'wptelegram' ),
                    'default'  => 'on',
                    'type'     => 'checkbox',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                array(
                    'name'    => 'image_position',
                    'label'   => __( 'Image Position', 'wptelegram' ),
                    'type'    => 'radio',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'  => 'before',
                    'options' => array(
                        'before'    => __( 'Before the Text', 'wptelegram' ),
                        'after' => __( 'After the Text', 'wptelegram' ),
                    ),
                ),
                array(
                    'name'     => 'attach_image',
                    'label'    => '',
                    'desc_tip' => __( 'Attach the image below the text', 'wptelegram' ),
                    'type'     => 'checkbox',
                    'sanitize_callback' => 'sanitize_text_field',
                    'desc'    => __( 'Useful if you want everything in one message.', 'wptelegram' ) . '<br><b>' . __( 'Note:', 'wptelegram' ) . ' </b>' . __( 'If enabled:', 'wptelegram' ) . '<ol style="list-style-type:disc;"><li><span id="wptg-attach-caption" class="wptg-attach">' . __( 'Caption cannot be used', 'wptelegram' ) . '</span></li><li><span id="wptg-attach-parse" class="wptg-attach">' . __( 'Parse Mode should NOT be', 'wptelegram' ) . ' "' . __( 'None', 'wptelegram' ) . '".</span></li><li><span id="wptg-attach-preview" class="wptg-attach">"' . __( 'Disable Web Page Preview', 'wptelegram' ) . '" ' . __( 'should not be checked', 'wptelegram' ) . '</span></li></ol>',
                ),
                array(
                    'name'    => 'image_style',
                    'label'   => __( 'How to send the image?', 'wptelegram' ),
                    'type'    => 'radio',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'  => 'without_caption',
                    'options' => array(
                        'with_caption'    => __( 'With Caption', 'wptelegram' ),
                        'without_caption' => __( 'Without Caption', 'wptelegram' ),
                    ),
                ),
                array(
                    'name'    => 'caption_source',
                    'label'   => __( 'Caption Source', 'wptelegram' ),
                    'type'    => 'radio',
                    'sanitize_callback' => 'sanitize_text_field',
                    'desc'    => __( 'Telegram limits photo caption to 200 characters.', 'wptelegram' ) . '<br>' . __( 'If the Media Library caption is not used and the Message Template exceeds the limit, the remaining part of the Template will be sent separately after the photo without breaking the words.', 'wptelegram' ) .'<br><b>'. __( 'Note:', 'wptelegram' ) . ' </b>' . __( 'If "Image Position" is set to "After the Text", then "Only Media Library" caption can be used.', 'wptelegram' ),
                    'default' => 'media_library',
                    'options' => array(
                        'media_library'    => __( 'Only Media Library', 'wptelegram' ),
                        'either'           => __( 'Media Library or Message Template (former preferred if not empty)', 'wptelegram' ),
                        'message_template' => __( 'Only Message Template', 'wptelegram' ),
                    ),
                ),
                array(
                    'name'    => 'misc',
                    'label'   => __( 'Miscellaneous', 'wptelegram' ),
                    'desc'    => '',
                    'type'    => 'multicheck',
                    'options' => array(
                        'disable_web_page_preview' => __( 'Disable Web Page Preview', 'wptelegram' ) . ' (' . __( 'of the link in text', 'wptelegram' ) . ')',
                        'disable_notification'     => __( 'Disable Notifications', 'wptelegram' ),
                        'no_message_as_reply'     => __( 'Do not send the second message as a reply to the first', 'wptelegram' ),
                    ),
                    'sanitize_callback' => 'wptelegram_sanitize_array',
                ),
            ),
            'wptelegram_notify' => array(
                array(
                    'name'              => 'chat_ids',
                    'label'             => __( 'Chat ID', 'wptelegram' ) . ' (chat_id)',
                    'desc'              => __( 'If more than one, separate them by comma. Read the instructions above', 'wptelegram' ),
                    'placeholder'       => __( 'e.g.', 'wptelegram' ) . ' 12345678',
                    'type'              => 'text',
                    'sanitize_callback' => 'wptelegram_sanitize_chat_ids',
                    'events'            => array(
                        'onblur' => 'getChatInfo("notify")',
                    ),
                    'button'            => array(
                        'name'  => __( 'Send Test', 'wptelegram' ),
                        'id'    => 'wptelegram-chat-ids-btn',
                        'class' => 'button-secondary',
                        'events'=> array(
                            'onclick' => 'sendMessage("notify")',
                        ),
                    ),
                ),
                array(
                    'name'        => 'chat_html',
                    'desc'        => $this->chat_ids_html_cb('notify'),
                    'type'        => 'html'
                ),
                array(
                    'name'              => 'watch_emails',
                    'label'             => __( 'Email(s) to watch', 'wptelegram' ),
                    'desc'              => __( 'If more than one, separate them by comma', 'wptelegram' ) . '<br><b>' . __( 'Note:', 'wptelegram' ) . '&nbsp;</b>' . __( 'WP Telegram will watch the Email Notifications sent from this site, if the email is sent to one of the above email ids, it will be sent to you on Telegram by your bot. It does not matter whether the email was successfully sent or not.', 'wptelegram' ) . '<br>' . __( 'If you want to receive notification for every sent email, write', 'wptelegram' ) . ' <code>all</code><br>' . __( 'Usually it should be the admin email', 'wptelegram' ) . ' <code>' . get_option( 'admin_email' ) . '</code>',
                    'type'              => 'text',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => get_option( 'admin_email' ),
                ),
                array(
                    'name'              => 'hashtag',
                    'label'             => __( 'Hashtag (optional)', 'wptelegram' ),
                    'desc'              => __( 'Add a hashtag at the end of the message.', 'wptelegram' ) . '<br>' . __( 'Must begin with', 'wptelegram' ) . '  <code>#</code>',
                    'type'              => 'text',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                array(
                    'name'     => 'user_notifications',
                    'label'    => __( 'Notifications to Users', 'wptelegram' ),
                    'desc'     => sprintf( __( 'User can enter the Telegram information on %sprofile%s page', 'wptelegram' ), '<a href="' . esc_url( get_edit_profile_url( get_current_user_id() ) . '#telegram-info' ) . '">', '</a>' ),
                    'desc_tip' => __( 'Allow users to receive their email notifications on Telegram', 'wptelegram' ),
                    'default'  => 'off',
                    'type'     => 'checkbox',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        );

        if ( (bool) apply_filters( 'wptelegram_bot_api_use_proxy', false ) ) {
            
            $settings_fields['wptelegram_proxy'] = array(
                array(
                    'name'              => 'script_url',
                    'label'             => __( 'Google Script URL', 'wptelegram' ),
                    'desc'              => sprintf( __( 'You can bypass the ban on Telegram by making using of Google Scripts. The requests will be sent via your Google Script. See this tutorial', 'wptelegram' ), '<b>192.168.84.101</b>' ),
                    'type'              => 'text',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                array(
                    'name'        => 'html',
                    'desc'        => '-----' . __( 'OR', 'wptelegram' ) . '-----' ,
                    'type'        => 'html'
                ),
                array(
                    'name'              => 'proxy_host',
                    'label'             => __( 'Proxy Host', 'wptelegram' ),
                    'desc'              => sprintf( __( 'Host IP or domian name like %s', 'wptelegram' ), '<b>192.168.84.101</b>' ),
                    'type'              => 'text',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                array(
                    'name'              => 'proxy_port',
                    'label'             => __( 'Proxy Port', 'wptelegram' ),
                    'desc'              => sprintf( __( 'Target Port like %s', 'wptelegram' ), '<b>8080</b>' ),
                    'type'              => 'text',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                array(
                    'name'    => 'proxy_type',
                    'label'   => __( 'Proxy Type', 'wptelegram' ),
                    'type'    => 'radio',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'       => 'CURLPROXY_HTTP',
                    'options'   => array(
                        'CURLPROXY_HTTP'            => 'HTTP',
                        'CURLPROXY_SOCKS4'          => 'SOCKS4',
                        'CURLPROXY_SOCKS4A'         => 'SOCKS4A',
                        'CURLPROXY_SOCKS5'          => 'SOCKS5',
                        'CURLPROXY_SOCKS5_HOSTNAME' => 'SOCKS5_HOSTNAME',
                    ),
                ),
                array(
                    'name'              => 'proxy_username',
                    'label'             => __( 'Username', 'wptelegram' ),
                    'desc'              => __( 'Leave empty if not required', 'wptelegram' ),
                    'type'              => 'text',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                array(
                    'name'              => 'proxy_password',
                    'label'             => __( 'Password', 'wptelegram' ),
                    'desc'              => __( 'Leave empty if not required', 'wptelegram' ),
                    'type'              => 'text',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            );
        }
        return apply_filters('wptelegram_admin_settings_fields_array', $settings_fields);
    }

	/**
	 * Render the text for the telegram section
	 *
	 * @since  1.0.0
	 */
	public function wptelegram_telegram_cb() {
		?>
		<div class="inside">
		<p><?php esc_html_e( 'In this section you can change the settings related to Telegram.', 'wptelegram' );?>&nbsp;<?php esc_html_e( 'To send messages to a Telegram Channel, group, supergroup or a private chat, follow the steps below.', 'wptelegram' ); ?></p>
		 <p style="color:#f10e0e;text-align:center;"><b><?php echo __( 'ATTENTION!','wptelegram'); ?></b></p>
		 <ol style="list-style-type: decimal;">
		 	<li><?php esc_html_e( 'Create a Channel/group/supergroup', 'wptelegram' );?></a>&nbsp;(<?php esc_html_e( 'If you haven\'t', 'wptelegram' );?>)</li>
		 	<li><?php echo sprintf( __( 'Create a Bot by sending %s command to %s', 'wptelegram' ), '<code>/newbot</code>', '<a href="https://t.me/BotFather"  target="_blank">@BotFather</a>' );
            ?></li>
		 	<li><?php echo sprintf( __( 'After completing the steps %s will provide you the Bot Token.', 'wptelegram' ), '@BotFather' );?></li>
		 	<li><?php esc_html_e( 'Copy the token and paste into the Bot Token field below.', 'wptelegram' );?>&nbsp;<?php esc_html_e( 'For ease, use', 'wptelegram' );?>&nbsp;<a href="<?php echo esc_url( 'https://web.telegram.org' ); ?>" target="_blank">Telegram Web</a></li>
		 	<li><?php echo __( 'Add the Bot as Administrator to your Channel/Group', 'wptelegram' );?></li>
		 	<li><?php esc_html_e( 'Enter the Channel Username(s) or Chat ID(s) separated by comma in the field below', 'wptelegram' );?>
		 		<ol>
		 			<li><?php echo sprintf( __( 'Username must start with %s', 'wptelegram' ), '<code>@</code>' );?></li>
		 			<li><?php esc_html_e( 'You can also use the Chat ID of a private chat with the Bot, see Notification Settings', 'wptelegram' );?></li>
		 		</ol>
		 	</li>
		 	<li><?php echo sprintf( __( 'Hit %s below', 'wptelegram' ), '<b>' . __( 'Save Changes' ) . '</b>' );?></li>
		 	<li><?php esc_html_e( 'That\'s it. Happy WPTelegram :)', 'wptelegram' );?></li>
		 </ol>
		 </div>
		<?php
	}

	/**
	 * get bot_token HTML
	 *
	 * @since  1.2.0
	 * @return string
	 */
	private function bot_token_html_cb( $section ) {
		$html = '<p style="margin-top:-30px;"><span id="wptelegram-'.$section.'-test" class="wptelegram-'.$section.'-desc description hidden">' . esc_html__( 'Test result:', 'wptelegram' ) . '&nbsp;<b><span style="color:#bb0f3b;" id="bot-info"></span></b></span>
		<span id="wptelegram-'.$section.'-token-err" class="hidden"  style="color:#f10e0e;font-weight:bold;">&nbsp;' . esc_html__('Invalid Bot Token', 'wptelegram' ) . '</span></p>';
		return $html;
	}

	/**
	 * get chat_ids HTML
	 *
	 * @since  1.2.0
	 * @return string
	 */
	private function chat_ids_html_cb( $section ) {
		$html = '<p style="margin-top:-30px;"><span id="wptelegram-'.$section.'-mem-count" class="hidden">' . esc_html__( "Members Count:", "wptelegram" ) . '</span></p>
		<ol id="wptelegram-'.$section.'-chat-list">
		</ol>
		<table id="wptelegram-'.$section.'-chat-table" class="hidden">
			<tbody>
				<tr>
					<th>' . esc_html__( "Chat_id", "wptelegram" ) . '</th>
					<th>' . esc_html__( "Name/Title", "wptelegram" ) . '</th>
					<th>' . esc_html__( "Chat Type", "wptelegram" ) . '</th>
					<th>' . esc_html__( "Test Status", "wptelegram" ) . '</th>
				</tr>
			</tbody>
		</table>';
		return $html;
	}

    /**
     * Render the text for the notify section
     *
     * @since  1.4.0
     */
    public function wptelegram_notify_cb() {
        ?>
        <div class="inside">
        <p><?php esc_html_e( 'In this section you can set/change the settings related to Notifications sent to Telegram', 'wptelegram' );?></p>
         <p style="color:#f10e0e;text-align:center;"><b><?php echo __( 'INSTRUCTIONS!', 'wptelegram' ); ?></b></p>
        <p><b><?php esc_html_e( 'IMPORTANT! First complete at least first 4 steps in Telegram Settings section, otherwise notifications won\'t be sent', 'wptelegram' );?></b></p>
         <ul style="list-style-type: disc;margin-left: 20px;">
             <li><?php echo __( 'Every Telegram user or group has a unique ID called', 'wptelegram' );?> <i>chat_id</i>.</li>
             <li><?php echo __( 'It is different from a username and is visible only to bots.', 'wptelegram' );?></li>
             <li><?php echo __( 'This chat_id is used by the bots to send messages to a user or group.', 'wptelegram' );?></li>
             <li><?php echo __( 'In order to receive notifications through your bot, you need to find your', 'wptelegram' );?> <i>chat_id</i></li>
             <li><?php echo __( 'Follow these steps:', 'wptelegram' );?>
                 <ol style="list-style-type: decimal;">
                    <li><?php echo sprintf( __( 'Send a message to %s', 'wptelegram' ), '<a href="https://t.me/MyChatInfoBot" target="_blank">@MyChatInfoBot</a>' );?></li>
                    <li><?php esc_html_e( 'It will send you your Telegram chat_id.', 'wptelegram' );?></li>
                    <li><?php echo sprintf( esc_html__( 'If you want to receive notifications into a group, then add %s to the group and it will send you the group ID.', 'wptelegram' ), '@MyChatInfoBot' );?>
                        <ul style="list-style-type: disc;margin-left: 20px;"">
                            <li><?php esc_html_e( 'Do not forget to add your own bot to the group.', 'wptelegram' );?></li>
                        </ul>
                    <li><?php esc_html_e( 'Enter the received chat_id in the field below', 'wptelegram' );?></li>
                    <li><?php esc_html_e( 'Start the conversation with your bot, bots cannot initialize a conversation.', 'wptelegram' );?></li>
                 </ol>
             </li>
         </ul>
         </div>
        <?php
    }

    /**
     * get all terms of the registered taxonomies
     *
     * @since  1.3.8
     * @return array
     */
    public static function get_all_terms() {
        $all_terms = array();
        $args = array(
          'public'   => true,
          'hierarchical' => true,
        ); 
        $taxonomies = get_taxonomies( $args, 'objects' );
        foreach ( $taxonomies as $taxonomy ) {
            $singular_name = isset( $taxonomy->labels->singular_name ) ? $taxonomy->labels->singular_name : '';
            $optgroup = $singular_name . ' (' . $taxonomy->name . ')';
            $terms = get_terms( $taxonomy->name, array( 'hide_empty' => 0, 'orderby' => 'term_group' ) );
            $terms_count = count( $terms );

            foreach ( $terms as $term ) {
                $term_name = $term->name;

                if ( $term->parent ) {
                    $parent_id = $term->parent;
                    $has_parent = true;

                    // avoid infinite loop with "ghost" categories
                    $found = false;
                    $i = 0;

                    while ( $has_parent && ( $i < $terms_count || $found ) ) {

                        // Reset each time
                        $found = false;
                        $i = 0;

                        foreach ( $terms as $parent_term ) {

                            $i++;

                            if ( $parent_term->term_id == $parent_id ) {
                                $term_name = $parent_term->name . ' &rarr; ' . $term_name;
                                $found = true;

                                if ( $parent_term->parent ) {
                                    $parent_id = $parent_term->parent;
                                }
                                else {
                                    $has_parent = false;
                                }
                                break;
                            }
                        }
                    }
                }
                /**
                 * add taxonomy->name to the value
                 * to later use it as second argument to has_term(),
                 * get_term() in older WordPress versions
                 * has $taxonomy as required argument
                 */
                $all_terms[ $optgroup ][ $term->term_id.'@'.$taxonomy->name ] = $term_name;
            }
        }
        return $all_terms;
    }

    /**
     * get all post authors
     *
     * @since  1.3.8
     * @return array
     */
    public static function get_all_authors() {
        $all_authors = array();
        $args = array(
            'orderby'   => 'name',
            'who'       => 'authors',
         ); 
        
        $authors = get_users( $args );
        
        foreach ( $authors as $author ) {
            $all_authors[ $author->ID ] = get_the_author_meta( 'display_name', $author->ID );
        }
        return $all_authors;
    }

    /**
     * get message template HTML
     *
     * @since  1.2.0
     * @return string
     */
    private function message_template_desc_cb() {
		$html = '<p style="margin-top:-15px;">' . esc_html__( 'You can use any text, emojis or these macros in any order:', 'wptelegram' ) . '&nbsp;<b><i>(' . esc_html__( 'Click to insert', 'wptelegram' ) . ')</i></b>' . $this->get_macros() . '</p>
			<p>
				<span><strong>' . esc_html__( 'Note:', 'wptelegram' ) .'</strong></span>
				<ol>
                    <li>' . sprintf( esc_html__( 'Replace %s in %s by the name of the taxonomy from which you want to get the terms attached to the post.', 'wptelegram' ), '<code>taxonomy</code>', '<code>{[taxonomy]}</code>' ) . '&nbsp;' . sprintf( esc_html__( 'For example %s', 'wptelegram' ), '<code>{[genre]}</code>' ) . '</li>
                    <li>' . sprintf( esc_html__( 'Replace %s in %s by the name of the Custom Field, the value of which you want to add to the template.', 'wptelegram' ), '<code>custom_field</code>', '<code>{[[custom_field]]}</code>' ) . '&nbsp;' . sprintf( esc_html__( 'For example %s', 'wptelegram' ), '<code>{[rtl_title]}</code>' ) . '</li>
				</ol>
			</p>';
			return $html;
	}

    /**
     * get macros
     *
     * @since  1.3.0
     */
    private function get_macros() {
        $macros = array(
            '{ID}',
            '{title}',
            '{post_date}',
            '{post_date_gmt}',
            '{author}',
            '{excerpt}',
            '{content}',
            '{short_url}',
            '{full_url}',
            '{tags}',
            '{categories}',
            '{[taxonomy]}',
            '{[[custom_field]]}',
            );

        /**
         * If you add your own macros using this filter
         * You should use "wptelegram_macro_values" filter
         * to replace the macro with the corresponding values
         * See prepare_message() method in
         * wptelegram/includes/class-wptelegram-post-handler.php
         *
         */
        $macros = (array) apply_filters( 'wptelegram_settings_macros', $macros );

        $html = '';
        foreach ( $macros as $macro ) {
            $html .= '<button type="button" class="wptelegram-tag"><code>' . esc_html__( $macro ) . '</code></button>';
        }
        return $html;
    }

    /**
     * Set $this->post_types
     *
     * @since  1.2.0
     */
    public function set_post_types() {
		$this->post_types = get_post_types( array( 'public' => true ), 'objects' );
	}

	/**
	 * get registered post types
	 *
	 * @param  string $for the page or section to get_post_types for
	 *
	 * @since  1.2.0
	 * @return array
	 */
	public function get_post_types( $for = 'options' ) {
		$arr = array();
		if ( 'metabox' == $for ) {
			foreach ( $this->post_types  as $post_type ) {
                if ( 'attachment' != $post_type->name ){
                    $arr[] = $post_type->name;
                }
			}
		} else{
			foreach ( $this->post_types  as $post_type ) {
                if ( 'attachment' != $post_type->name ){
        			$arr[ $post_type->name ] = isset( $post_type->labels->singular_name ) ? $post_type->labels->singular_name . ' (' . $post_type->name . ')' : $post_type->name;
                }
			}
		}
		return $arr;
	}

	/**
	 * Render the meta box
	 *
	 * @since  1.0.0
	 */
	public function wptelegram_meta_box_cb() {
		global $post;
        global $wptelegram_options;
        
		wp_nonce_field( 'save_scheduled_post_meta', 'wptelegram_meta_box_nonce' );

		$chat_ids = $wptelegram_options['telegram']['chat_ids'];

	    $message_template = $wptelegram_options['message']['message_template'];

		if ( '' != $message_template ) {
			$message_template = json_decode( $message_template );
		}
		$message_template = apply_filters( 'wptelegram_message_template', $message_template, $post );
		?>
		<input type="checkbox" name="<?php echo esc_attr( 'wptelegram_override_switch'); ?>" id="<?php echo esc_attr( 'wptelegram_override_switch' ); ?>" value="on">
		<label for="<?php echo esc_attr( 'wptelegram_override_switch' ); ?>" id="<?php echo esc_attr( 'wptelegram_override_switch-label' ); ?>"></label><label for="<?php echo esc_attr( 'wptelegram_override_switch' ); ?>" id="switch-label">&nbsp;<?php esc_html_e( 'Override default settings', 'wptelegram' ); ?></label>
		<table class="form-table" id="wptelegram-meta-table">
			<tbody>
				<tr><th scope="row"><?php esc_html_e( 'Message', 'wptelegram' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="<?php echo esc_attr( 'wptelegram_send_message' ); ?>" id="<?php echo esc_attr( 'wptelegram_send_message' ); ?>" value="yes" checked><?php esc_html_e( 'Send', 'wptelegram' ); ?>
							</label>
							<br>
							<label>
								<input type="radio" name="<?php echo esc_attr( 'wptelegram_send_message' ); ?>" value="no"><?php esc_html_e( 'Do not Send', 'wptelegram' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr id="send_to"><th scope="row"><?php esc_html_e( 'Send to', 'wptelegram' ); ?></th>
					<td>
					<?php
					if ( $chat_ids ) : ?>
						<fieldset>
							<label>
								<input type="checkbox" id="<?php echo esc_attr( 'wptelegram_send_to_all' ); ?>" name="<?php echo esc_attr( 'wptelegram_send_to[all]' ); ?>" value="1" checked><?php esc_html_e( 'All Channels/Chats', 'wptelegram' ); ?>
							</label>
							<?php
							$chat_ids = explode( ',', $chat_ids );
							foreach ( $chat_ids as $chat_id ) :
								if ( '' == $chat_id ):
									continue;
								endif; ?>
								<br>
								<label class="<?php echo esc_attr( 'wptelegram_send_to' ); ?>" >
									<input type="checkbox" name="<?php echo esc_attr( 'wptelegram_send_to[]' ); ?>" value="<?php echo $chat_id; ?>"><?php echo $chat_id; ?>
								</label>
							<?php endforeach; ?>
						</fieldset>
					<?php else : ?>
						<span><?php esc_html_e( 'No Channels/Chat IDs found', 'wptelegram' ); ?></span>
					<?php endif; ?>
					</td>
				</tr>
                <tr><th scope="row" class="hide-if-no-js"><?php esc_html_e( 'Send file(s)', 'wptelegram' ); ?></th>
                    <td>
                        <fieldset>
                            <div class="wptelegram-files-container"><table></table></div>

                            <p class="hide-if-no-js">
                                <a class="wptelegram-file-upload" 
                                   href="<?php echo esc_url( get_upload_iframe_src() ) ?>" type="button">
                                    <?php _e('Add') ?></a>&nbsp;&nbsp;
                                <a class="wptelegram-file-remove-all hidden" 
                                  href="#"><?php _e('Remove All') ?></a>
                            </p>
                        </fieldset>
                    </td>
                </tr>
				<tr id="message_template"><th scope="row"><?php esc_html_e( 'Template', 'wptelegram' ); ?></th>
					<td>
                        <div id="<?php echo esc_attr( 'wptelegram_message_template-container' ); ?>"></div>
						<textarea id="<?php echo esc_attr( 'wptelegram_message_template' ); ?>" name="<?php echo esc_attr( 'wptelegram_message_template' ); ?>" dir="auto"><?php echo esc_textarea( $message_template ); ?></textarea>
						<br><br>
						<?php
						echo call_user_func( array( $this, 'message_template_desc_cb' ) );
						?>
					</td>
				</tr>
			</tbody>
		</table>
	    <?php
	}

}
