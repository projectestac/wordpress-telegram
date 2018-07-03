<?php

/**
 * Displays profile fields
 *
 * Available Variables:
 * 
 * $posts, $post, $wp_did_header, $wp_query, $wp_rewrite,
 * $wpdb, $wp_version, $wp, $id, $comment, $user_ID,
 * $bot_username, $telegram_chat_id
 *
 * @link       https://t.me/WPTelegram
 * @since      1.6.0
 *
 * @package    WPTelegram
 * @subpackage WPTelegram/public/partials
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<h3 id="telegram-info"><?php _e( 'Telegram Info', 'wptelegram' ); ?></h3>
<span class="description"><?php echo sprintf( __( 'You can receive your email notifications on Telegram from %s', 'wptelegram' ), '@' . $bot_username ); ?></span>
<table class="form-table">
	<tr>
		<th>
			<label for="telegram_chat_id"><?php _e( 'Telegram Chat ID', 'wptelegram' ); ?>
		</label></th>
		<td>
			<input type="text" name="telegram_chat_id" id="telegram_chat_id" value="<?php echo esc_attr( $telegram_chat_id ); ?>" class="regular-text" /><br />
			<span class="description"><?php _e( 'Please enter your Telegram Chat ID', 'wptelegram' ); ?></span>
		</td>
	</tr>
	<tr>
		<th></th>
		<td>
			<p><b><?php echo __( 'INSTRUCTIONS!', 'wptelegram' ); ?></b></p>
			<ul style="list-style-type: disc;">
				<li><?php echo sprintf( __( 'Send a message to %s', 'wptelegram' ), '<a href="https://t.me/MyChatInfoBot"  target="_blank">@MyChatInfoBot</a>' );?></li>
				<li><?php esc_html_e( 'It will send you your Telegram chat_id.', 'wptelegram' );?></li>
                <li><?php esc_html_e( 'Enter the received chat_id in the field above', 'wptelegram' );?></li>
				<li><?php echo sprintf( __( 'Also start a conversation with %s to receive notifications', 'wptelegram' ), '<a href="https://t.me/' . $bot_username . '"  target="_blank">@' . $bot_username . '</a>' );?></li>
			</ul>
		</td>
	</tr>
</table>