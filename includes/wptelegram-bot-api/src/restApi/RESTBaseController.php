<?php
/**
 * WP REST API functionality of the plugin.
 *
 * @link       https://manzoorwani.dev
 * @since      1.2.2
 *
 * @package    WPTelegram\BotAPI
 * @subpackage WPTelegram\BotAPI\restApi
 */

namespace WPTelegram\BotAPI\restApi;

/**
 * Base class for all the endpoints.
 *
 * @since 1.2.2
 *
 * @package    WPTelegram\BotAPI
 * @subpackage WPTelegram\BotAPI\restApi
 * @author     Manzoor Wani <@manzoorwanijk>
 */
abstract class RESTBaseController extends \WP_REST_Controller {

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 * @since 1.2.2
	 */
	const REST_NAMESPACE = 'wptelegram-bot/v1';

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	const REST_BASE = '';
}
