<?php

namespace MatthewSpencer\GarminConnect;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;

/**
 * Authenticate with Garmin Connect
 */

class Authenticate {

	/**
	 * GuzzleHttp\Client
	 *
	 * @var $client
	 * @access public
	 */
	public $client;

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

	/**
	 * Make Connection
	 *
	 * @param string $username
	 * @param string $password
	 */
	public static function make_connection($username, $password) {

		self::$username = $username;
		$this->client = new Client();

		if ( self::is_connected($username) ) {
			return true;
		}

		$maybe = self::_connect($username, $password);
		return $maybe;

	}

	/**
	 * Test Connection
	 * Checks to see if already logged in
	 *
	 * @param string $username
	 * @return bool $is_connected
	 */
	public static function is_connected($username) {

		$response = $this->client->get( self::$url, [
		    'query' => self::$params
		] );

		// is connected?
		// YES: http://connect.garmin.com/dashboard?cid=xxxxxxx
		// NO: https://sso.garmin.com/sso/login?service=http%3A%2F%2Fconnect.garmin.com%2Fpost-auth%2Flogin&clientId=GarminConnect&consumeServiceTicket=false
		if ( strpos($response->getEffectiveUrl(), 'sso.garmin.com/sso/login') === false ) {
			return true;
		}

		return false;

	}

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
		$data['lt'] = self::flow_execution_key($response);

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

	/**
	 * Get Flow Execution Key
	 * Grabs from HTML comment
	 *
	 * @return string|bool $execution_key
	 */
	private static function flow_execution_key() {

		$response = $this->client->get( self::$url, [
		    'query' => self::$params
		] );

		$crawler = new Crawler( (string) $response->getBody() );

		// looking for
		// <!-- flowExecutionKey: [xxxx] -->
		// or
		// <input name="lt" value="xxxx" type="hidden">
		try {
			$execution_key = $crawler->filter('input[name=lt]')->attr('value');
		} catch ( InvalidArgumentException $e ) {
			$execution_key = false;
		}

		return $execution_key;

	}

}