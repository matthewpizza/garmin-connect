<?php

namespace MatthewSpencer\GarminConnect;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Authenticate with Garmin Connect
 */

class Authenticate {

	/**
	 * @var $client MatthewSpencer\GarminConnect\Client
	 * @access public
	 */
	public static $client = null;

	/**
	 * @var string $email
	 * @access private
	 */
	private static $email;

	/**
	 * @var string $password
	 * @access private
	 */
	private static $password;

	/**
	 * @var array $params
	 * @access public
	 */
	public static $params = array(
		'service' => 'http://connect.garmin.com/post-auth/login',
		'clientId' => 'GarminConnect',
		'consumeServiceTicket' => 'false',
	);

	/**
	 * @var string $login_url
	 * @access public
	 */
	public static $login_url = 'https://sso.garmin.com/sso/login';

	/**
	 * @var string $dashboard_url
	 * @access public
	 */
	public static $dashboard_url = 'http://connect.garmin.com/post-auth/login';

	/**
	 * Make New Connection
	 *
	 * @param  string $email
	 * @param  string $password
	 * @return boolean $connected
	 */
	public static function new_connection($email, $password) {

		self::client();
		self::$email = $email;
		self::$password = $password;

		$connected = self::connect();
		return $connected;

	}

	/**
	 * Authenticate with Garmin SSO
	 *
	 * @return boolean
	 */
	private static function connect() {

		if ( ! $ticket = self::ticket( $data ) ) {
			die('Cannot find ticket value. Please check connection details.');
		}

		$response = self::$client->post( self::$dashboard_url, [
			'query' => [
				'ticket' => $ticket,
			],
			'allow_redirects' => false,
		] );

		if ( $response->getStatusCode() !== 302 ) {
			die('Expected 302, saw ' . $response->getStatusCode());
		}

		$response = self::$client->get( $response->getHeader('Location') );

		if ( $response->getStatusCode() !== 200 ) return false;
		if ( strpos( (string) $response->getEffectiveUrl(), '://connect.garmin.com/' ) === false ) return false;

		return true;

	}

	/**
	 * Get Flow Execution Key
	 * Grabs from HTML comment
	 *
	 * @return string|bool $execution_key
	 */
	private static function flow_execution_key() {

		$response = self::$client->get( self::$login_url, [
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

	/**
	 * Get Ticket Value
	 *
	 * @return string
	 */
	private static function ticket() {

		$data = [
			'username' => self::$email,
			'password' => self::$password,
			'_eventId' => 'submit',
			'embed' => 'true',
			'displayNameRequired' => 'false',
			'lt' => self::flow_execution_key(),
		];

		$response = self::$client->post( self::$login_url, [
			'query' => self::$params,
			'body' => $data,
			'allow_redirects' => false,
		] );

		// looking for
		// var response_url = 'http://connect.garmin.com/post-auth/login?ticket=xx-xxxxxxxx-xxxxxxxxxxxxxxxxxxxx-xxx';
		preg_match( "/ticket=([^']+)'/", (string) $response->getBody(), $matches );

		if ( ! isset( $matches[1] ) ) {
			return false;
		}

		return $matches[1];

	}

	/**
	 * Client
	 */
	private static function client() {

		if ( is_null( self::$client ) ) {
			self::$client = Client::instance();
		}

	}

}