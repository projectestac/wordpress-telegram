=== WP Telegram ===
Contributors: manzoorwanijk
Donate link: https://paypal.me/manzoorwanijk
Tags: telegram, notifications, posts, channel, group
Requires at least: 3.6
Tested up to: 4.9.6
Stable tag: 1.8.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Get notifications and send posts automatically to Telegram when published or updated, whether to a Telegram Channel, Group, Supergroup or private chat, with full control...

== Description ==
Get notifications and send posts automatically to Telegram when published or updated, whether to a Telegram Channel, Group, Supergroup or private chat, with full control...

Whenever a post is published or updated, you can easily use this plugin to send it to your Telegram chat, be it a Channel, Group, Supergroup or a private chat and as many as you want :)
It will not only help you make this process automatic, rather it will also give a lot of control on how you want the automation to be :)

Running a WooCommerce store, user registration portal or anything you want to get notifications of? With this plugin, get all the notifications to your Telegram.

**Some Features**

== Excellent LIVE Support on Telegram ==

*	Supports all types of chats - Channels, groups, supergroups and even private chats :)
*	Supports multiple chats
*	Message Template composer with Emojis
*	Can send featured image along with the text
*	You can choose to send only Featured Image
*	WordPress Multisite support
*	WooCommerce support
*	Supports Custom Post Types
*	Direct Support for Custom Fields
*	Supports Custom Taxonomies
*	Select the post types to be sent
*	Choose when to send (New and/or updated)
*	Filter posts by authors, categories or custom taxonomy terms
*	Provides an override switch in post edit screen
*	Supports scheduled (future) posts
*	Get notifications from your website
*	Allow users to receive email notifications on Telegram

**Get in touch**

*	Website [wptelegram.com](https://wptelegram.com)
*	Telegram [@WPTelegram](https://t.me/WPTelegram)
*	Facebook [@WPTelegram](https://fb.com/WPTelegram)
*	Twitter [@WPTelegram](https://twitter.com/WPTelegram)

**Join the Chat**

We have a public group on Telegram to provide help setting up the plugin, discuss issues, features, translations etc. Join [@WPTelegramChat](https://t.me/WPTelegramChat)
For rules, see the pinned message. No spam please.

**Translations**

Many thanks to the translators for the great job!

* [mohammadhero](https://profiles.wordpress.org/mohammadhero/) and [Aydin Mirzaie](http://mirzaie-aydin.com) (Persian)
* [Mirko Genovese](http://www.mirkogenovese.it) (Italian)
* [Mohamad Bush](https://profiles.wordpress.org/Mohamadbush) and Mohammad Taher (Arabic)
* [Muffin](https://t.me/Muffin) (German)
* [HellFive Osborn](https://t.me/HellFiveOsborn) (Portuguese Brazilian)
* [Oxford](http://radiowolf.ru) (Russian)
* [jdellund](https://profiles.wordpress.org/jdellund) (Catalan)

* Note: You can also contribute in translating this plugin into your local language. Join the Chat (above)


== Installation ==


1. Upload the `wptelegram` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the Plugins menu in WordPress. After activation, you should see the menu of this plugin the the admin
3. Configure the plugin.

**Enjoy!**

== Frequently Asked Questions ==

= How to create a Telegram Bot =

[How do I create a bot?](https://core.telegram.org/bots/faq#how-do-i-create-a-bot).


== Screenshots ==

1. Telegram Settings.
2. WordPress Settings
3. Message Settings
4. Message Settings (Cont...)
5. Message Settings (Cont...)
6. Notification Settings
7. User Profile page
8. Post Edit Screen

== Changelog ==

= 1.8.0 =
* Added support for sending files along with the post.
* A few more hooks and filters
* Minor fixes

= 1.7.9 =
* Fixed the issue with sending test messages

= 1.7.8 =
* Added the support for bypassing blockage using Google App Script.
* Fixed the issue with double quotes in message template
* Minor fixes

= 1.7.7 =
* Fixed the issue with saving the settings with proxy

= 1.7.6 =
* Added support for many proxy types

= 1.7.5 =
* Added the hidden support for proxy
* Added hooks to bot API for modifying curl handle

= 1.7.4 =
* Added the latest update for Bot API Library
* Increased the default request timeout
* Added few more hooks for bot API request params

= 1.7.3 =
* Fixed the syntax error in previous update

= 1.7.2 =
* Some more control for user permissions
* Fixed the issue of bot token loss when saving the settings

= 1.7.1 =
* Added new filters for controlling the sent message

= 1.7.0 =
* Revamped Telegram Bot API Library to make it more portable
* Changed a few hooks to avoid confusion
* Added Catalan translation. Thanks to jdellund
* Minor fixes

= 1.6.5 =
* Enabled `parse_mode` for in image caption

= 1.6.4 =
* Added few more hooks for more control and customizations

= 1.6.3 =
* Added Russian translation. Thanks to Oxford
* Updated EmojiOne Area library to v3.2.6 to enable emoji search
* Updated Select2 library to v4.0.5

= 1.6.2 =
* Added method for creating API log
* Added method to modify curl handle for file uploads
* More filters to control the process
* Bug fixes

= 1.6.1 =
* Fixed the Fatal Error caused by WP_Error when saving the settings
* Added Portuguese Brazilian translation. Thanks to HellFive Osborn
* Fixed the issue caused by unending Markdown which stopped notifications

= 1.6.0 =
* Total revamp of the notification sending mechanism
* Allow users to receive email notifications on Telegram
* Added compatibility with every plugin that uses `wp_mail()` to send emails
* Fixed bugs in notification processing

= 1.5.7 =
* Fixed the issue of posts not being sent when published by cron
* Fixed the hyperlink issue in content URLs after the previous update
* Added more filters to control the way post_content and post_excerpt are processed

= 1.5.6 =
* Added German translation. Thanks to [Muffin](https://t.me/Muffin)
* Fixed post_date format and localization issue.
* Fixed shortcode issue in post_content
* Improved processing of post_content and post_excerpt
* Added option to choose the way consecutive messages are sent
* Fixed caption issue when sending image after the text
* Improved plugin strings for easy translations
* Bug fixes and performance improvements

= 1.5.4 =
* Added Italian translation. Thanks to [Mirko Genovese](http://www.mirkogenovese.it)
* Added Arabic translation. Thanks to @Mohamadbush and Mohammad Taher
* Fixed the HTML parsing issue when using Content before Read More tag as Excerpt Source
* Added hooks before and after sending the message
* Added `{post_date}` and `{post_date_gmt}` macros to be used in Message Template

= 1.5.3 =
* Added Persian translation. Thanks to [mohammadhero](https://profiles.wordpress.org/mohammadhero/)

= 1.5.2 =
* Added hooks and filters for post title, author, excerpt, featured_image etc.
* Final support for the search plugin

= 1.5.1 =
* Fixed the warning for undefined index when not using categories/terms restriction

= 1.5.0 =
* Added support for Read More tag to be used in Excerpt Source
* Improved Telegram API as a Library for developers to use
* Many upgrades to provide basis for future plugin(s)
* Minor fixes

= 1.4.3 =
* Fixed the bug with scheduled posts when using override switch

= 1.4.2 =
* Fixed the unwanted warning about invalid bot token

= 1.4.1 =
* Fixed warnings when settings not saved
* Added language pack for translations
* Minor fixes

= 1.4 =
* Introducing Website notifications to Telegram
* Dropped support for WordPress 3.5 and older

= 1.3.8 =
* Filter posts by author
* Filter posts by categories or terms of custom taxonomies
* You can now explicitly set Excerpt Source
* Performance improvements

= 1.3.7 =
* Delayed `save_post` hook execution to fix the issue with some custom fields
* Added filters to give you more control over macros and their values
* Added separate filters for modifying the values of individual custom fields and taxonomies
* Minor fixes

= 1.3.6 =
* Now Featured Image can be sent after the text
* Image and text can be send in a single message

= 1.3.5 =
* Now Featured Image can be sent with Caption
* Caption source can explicitly be chosen
* Added support for sending only Featured Image
* Minor fixes

= 1.3.4 =
* Fixed the text issue with scheduled posts

= 1.3.3 =
* Optimized Settings tabs for small screens
* Added tab icons to fit on small screens
* Minor fixes

= 1.3.2 =
* Fixed message template issue in post edit screen

= 1.3.0 =
* Total revamp of the settings page
* Added tabbed interface to reduce scrolling
* Added a beautiful template editor with emojis :)
* Added direct support for Custom Post Type selection
* Added the option to choose Channel/chat at the post edit screen
* Preserve override option for Scheduled (future) Posts
* Bug fixes for older WordPress versions

= 1.2.0 =
* Added support for PHP 5.2
* Minor bug fixes

= 1.1.0 =
* Added direct support for Custom Fields
* Added support for including {taxonomy} in template
* Fixed HTML issue with {content}

= 1.0.9 =
* Fixed HTML Parse Mode issue
* Fixed URL issue in Markdown style

= 1.0.8 =
* Added support for scheduled posts
* Fixed HTML Entities issue in the text

= 1.0.6 =
* Fixed excerpt length bug

= 1.0.5 =
* Minor fixes

= 1.0.4 =
* Updated README

= 1.0.3 =
* Minor fixes

= 1.0.2 =
* Changed the override option to make it more versatile
* Bug fixes

= 1.0.0 =
* Initial Release.

== Upgrade Notice ==

= 1.3.0 =
* Goto WP Telegram Settings and just save the settings (Recommended)
