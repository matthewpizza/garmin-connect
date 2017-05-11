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
		'clientId' => 'GarminConnect',
		'consumeServiceTicket' => 'false',
		'gauthHost' => 'https://sso.garmin.com/sso',
		'service' => 'https://connect.garmin.com/modern/',
		'webhost' => '',
	);

	/**
	 * @var string $dashboard_url
	 * @access public
	 */
	public static $dashboard_url = 'https://connect.garmin.com/modern/';

	/**
	 * @var string $login_url
	 * @access public
	 */
	public static $login_url = 'https://sso.garmin.com/sso/login';

	/**
	 * @var string $session_url
	 * @access public
	 */
	public static $session_url = 'https://connect.garmin.com/legacy/session';

	/**
	 * @var string $hostname_url
	 * @access public
	 */
	public static $hostname_url = 'https://connect.garmin.com/modern/auth/hostname';

	/**
	 * Make New Connection
	 *
	 * @param  string $email
	 * @param  string $password
	 * @return boolean $connected
	 */
	public static function new_connection($email, $password) {

		if ( self::is_connected() ) return true;

		self::client();
		self::$email = $email;
		self::$password = $password;

		$connected = self::connect();
		return $connected;

	}

	/**
	 * Is Connected?
	 *
	 * @return boolean
	 */
	public static function is_connected() {

		self::client();

		$username = self::$client->username();

		return $username !== '';

	}

	/**
	 * Authenticate with Garmin SSO
	 *
	 * @return boolean
	 */
	private static function connect() {

		if ( ! $ticket = self::ticket() ) {
			die( "Cannot find ticket value. Please check connection details.\n" );
		}

		$response = self::$client->post( self::$dashboard_url, [
			'query' => [
				'ticket' => $ticket,
			],
			'allow_redirects' => false,
		] );

		if ( $response->getStatusCode() !== 302 ) {
			die( 'Expected 302, saw ' . $response->getStatusCode() . "\n" );
		}

		$response = self::$client->get( $response->getHeader('Location') );

		if ( $response->getStatusCode() !== 200 ) return false;
		if ( strpos( (string) $response->getEffectiveUrl(), '://connect.garmin.com/' ) === false ) return false;

		// Last bit of magic for authentication.
		$response = self::$client->get( self::$session_url );

		return $response->getStatusCode() === 200;

	}

	/**
	 * Get webhost from JSON.
	 *
	 * @return string|bool
	 */
	private static function webhost() {

		$response = self::$client->get( self::$hostname_url );

		$data = json_decode((string) $response->getBody(), true);

		return $data['host'] ?: false;

	}

	/**
	 * Get Ticket Value
	 *
	 * @return string
	 */
	private static function ticket() {
		if ( ! $webhost = self::webhost() ) {
			die( "Cannot find webhost value. Please check connection details.\n" );
		}

		$data = [
			'username' => self::$email,
			'password' => self::$password,
			'_eventId' => 'submit',
			'embed' => 'true',
			'displayNameRequired' => 'false',
		];

		$query = self::$params;
		$query['webhost'] = $webhost;

		$response = self::$client->post( self::$login_url, [
			'query' => $query,
			'body' => $data,
			'allow_redirects' => false,
		] );

		// looking for
		// var response_url = 'https://connect.garmin.com/post-auth/login?ticket=xx-xxxxxxxx-xxxxxxxxxxxxxxxxxxxx-xxx';
		preg_match( '/ticket=([^"]+)/', (string) $response->getBody(), $matches );

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
