<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// keep admin notices above the header
echo '<h2></h2>';
?>
<div class="wptelegram-header-wrapper">
    <table>
        <tr>
            <td>
                <div class="wptelegram-logo">
                    <img src="<?php echo esc_url( $plugin_url . '/admin/icons/icon-100x100.svg' ); ?>" alt="<?php esc_attr_e('WP Telegram', $text_domain); ?>" />
                </div>
            </td>
            <td>
                <div class="wptelegram-title"><h1><?php echo esc_html( sprintf( '%1$s %2$s', $title, $version ) ); ?></h1></div>
            </td>
            <?php
            // ************ XTEC - Hide help info - 2018.07.26 @nacho
            if ( is_xtec_super_admin() ) {
            ?>
                <td>
                    <div class="">
                        <p><?php echo esc_html__( 'Need help?', 'wptelegram' ) . '<br>' . esc_html__( 'Shout in', 'wptelegram' ); ?>
                            👇</p>
                        <a href="https://t.me/WPTelegramChat" class="telegram-follow-button btn" target="_blank">
                            <img src="<?php echo esc_url( WPTELEGRAM_URL . '/admin/icons/tg-icon.svg' ); ?>"
                                 alt="WPTelegramChat"/>&nbsp;@WPTelegramChat</a>
                    </div>
                </td>
            <?php
            }
            ?>

            <?php
            // ************ XTEC - Hide social networks - 2018.07.26 @nacho
            if ( is_xtec_super_admin() ) {
            ?>
                <td>
                    <div class="wptelegram-socials">
                        <p><?php esc_html_e( 'Get connected', 'wptelegram' ) ?></p>
                        <div class="wptelegram-social-bttns">
                            <ul style="list-style-type: none">
                                <li>
                                   <div class="fb-like" data-href="https://www.facebook.com/WPTelegram" data-layout="button_count" data-action="like" data-size="small" data-show-faces="false" data-share="false"></div>
                                </li>
                                <li>
                                    <a href="https://twitter.com/WPTelegram" class="twitter-follow-button btn" data-show-count="false" data-lang="en"><?php esc_html_e( 'Follow', 'wptelegram' );?>&nbsp;@WPTelegram</a>
                                </li>
                                <li>
                                    <a href="https://t.me/WPTelegram" class="telegram-follow-button btn" target="_blank">
                                    <img src="<?php echo esc_url( WPTELEGRAM_URL . '/admin/icons/tg-icon.svg' ); ?>" alt="<?php esc_attr_e( 'WP Telegram', 'wptelegram' ); ?>" />&nbsp;<?php esc_html_e( 'Join', 'wptelegram' );?>&nbsp;@WPTelegram</a>
                                </li>
                            </ul>
                            <div id="fb-root"></div>
                            <script>(function(d, s, id) {
                              var js, fjs = d.getElementsByTagName(s)[0];
                              if (d.getElementById(id)) return;
                              js = d.createElement(s); js.id = id;
                              js.src = "//connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v2.9";
                              fjs.parentNode.insertBefore(js, fjs);
                            }(document, 'script', 'facebook-jssdk'));</script>
                            <script>window.twttr = (function(d, s, id) {
                              var js, fjs = d.getElementsByTagName(s)[0],
                                t = window.twttr || {};
                              if (d.getElementById(id)) return t;
                              js = d.createElement(s);
                              js.id = id;
                              js.src = "https://platform.twitter.com/widgets.js";
                              fjs.parentNode.insertBefore(js, fjs);

                              t._e = [];
                              t.ready = function(f) {
                                t._e.push(f);
                              };

                              return t;
                            }(document, "script", "twitter-wjs"));</script>

                        </div>
                    </div>
                </td>
            <?php
            }
            ?>
        </tr>
    </table>
</div>