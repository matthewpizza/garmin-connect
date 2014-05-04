<?php

namespace Garmin\API;

/**
 * Authenticate with Garmin Connect
 */

class Authenticate {

	/**
	 * Username
	 * 
	 * @var string $username
	 * @access private
	 */
	private static $username;

	/**
	 * Path to cookie
	 * 
	 * @var string $cookie
	 * @access private
	 */
	private static $cookie;

	/**
	 * Login Parameter
	 *
	 * @var array $params
	 * @access private
	 */
	private static $params = array(
		'service' => 'http://connect.garmin.com/post-auth/login',
		'clientId' => 'GarminConnect',
		'consumeServiceTicket' => 'false',
		// 'webhost' => 'olaxpw-my01.garmin.com',
		// 'source' => 'http://connect.garmin.com/en-US/signin',
		// 'redirectAfterAccountLoginUrl' => 'http://connect.garmin.com/post-auth/login',
		// 'redirectAfterAccountCreationUrl' => 'http://connect.garmin.com/post-auth/login',
		// 'gauthHost' => 'https://sso.garmin.com/sso',
		// 'locale' => 'en',
		// 'id' => 'gauth-widget',
		// 'cssUrl' => 'https://static.garmincdn.com/com.garmin.connect/ui/src-css/gauth-custom.css',
		// 'rememberMeShown' => 'true',
		// 'rememberMeChecked' => 'false',
		// 'createAccountShown' => 'true',
		// 'openCreateAccount' => 'false',
		// 'usernameShown' => 'true',
		// 'displayNameShown' => 'false',
		// 'initialFocus' => 'true',
		// 'embedWidget' => 'false',
	);

	/**
	 * Login URL
	 *
	 * @var string $url
	 * @access private
	 */
	private static $url = 'https://sso.garmin.com/sso/login';

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Setup
	 */
	public static function setup($username) {
		self::$username = $username;
		self::_cookie();
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Make Connection
	 *
	 * @param string $username
	 * @param string $password
	 */
	public static function make_connection($username, $password) {

		self::setup($username);

		if ( self::is_connected($username) ) {
			return true;
		}

		$maybe = self::_connect($username, $password);
		return $maybe;		

	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Test Connection
	 * Checks to see if already logged in
	 *
	 * @param string $username
	 * @return bool $is_connected
	 */
	public static function is_connected($username) {
		self::setup($username);

		$is_connected = false;

		// build curl request
		$options = array(
			'CURLOPT_MAXREDIRS' => 4,
			'CURLOPT_RETURNTRANSFER' => true,
			'CURLOPT_FOLLOWLOCATION' => true,
			'CURLOPT_COOKIEJAR' => self::$cookie,
			'CURLOPT_COOKIEFILE' => self::$cookie,
		);
		$method = 'GET';
		$request = self::$url . '?' . http_build_query(self::$params);

		// get curl response
		$response = Tools::curl($request, $options, $method);

		// is connected?
		// YES: http://connect.garmin.com/dashboard?cid=xxxxxxx
		// NO: https://sso.garmin.com/sso/login?service=http%3A%2F%2Fconnect.garmin.com%2Fpost-auth%2Flogin&clientId=GarminConnect&consumeServiceTicket=false
		if ( strpos($response['headers']['url'], 'connect.garmin.com/dashboard') !== false ) {
			$is_connected = true;
		}

		return $is_connected;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Authenticate with Garmin SSO
	 *
	 * @uses https://sso.garmin.com/sso/js/gauth-widget.js
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	private static function _connect($username, $password) {

		// data to post
		$data = array(
			'username' => $username,
			'password' => $password,
			'_eventId' => 'submit',
			'embed' => 'true',
			// 'displayNameRequired' => 'false',
		);
		$data['lt'] = self::_get_execution_key($response);
		

		$options = array(
			'CURLOPT_MAXREDIRS' => 4,
			'CURLOPT_RETURNTRANSFER' => true,
			'CURLOPT_FOLLOWLOCATION' => true,
			'CURLOPT_COOKIEJAR' => self::$cookie,
			'CURLOPT_COOKIEFILE' => self::$cookie,
			'CURLOPT_POST' => count($data),
			'CURLOPT_POSTFIELDS' => http_build_query($data),
		);
		$method = 'POST';


		$request = self::$url . '?' . http_build_query(self::$params);

		$response = Tools::curl($request, $options, $method);

		if ( strpos($response['headers']['url'], 'connect.garmin.com') !== false ) {
			return true;
		}
		else {
			die('Could not connect.');
		}

	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Get Flow Execution Key
	 * Grabs from HTML comment
	 *
	 * @return string $execution_key
	 */
	private static function _get_execution_key() {
		// build curl request
		$options = array(
			'CURLOPT_MAXREDIRS' => 4,
			'CURLOPT_RETURNTRANSFER' => true,
			'CURLOPT_FOLLOWLOCATION' => true,
			'CURLOPT_COOKIEJAR' => self::$cookie,
			'CURLOPT_COOKIEFILE' => self::$cookie,
		);
		$method = 'GET';
		$request = self::$url . '?' . http_build_query(self::$params);

		// get curl response
		$response = Tools::curl($request, $options, $method);

		// looking for
		// <!-- flowExecutionKey: [xxxxx] -->
		// probably will break :/
		// other option is a hidden input named "lt"
		preg_match('~<!-- flowExecutionKey: \[(.*?)\] -->~', $response['data'], $matches);

		if ( empty($matches) ) {
			die('Failed to find flowExecutionKey');
		}

		$execution_key = $matches[1];

		return $execution_key;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Setup Cookie
	 */
	private static function _cookie() {
		self::$cookie = dirname(dirname(dirname(__DIR__))) . '/cookies/' . self::$username;

		if ( ! file_exists(self::$cookie) ) {
			file_put_contents(self::$cookie, '');
		}
	}

}