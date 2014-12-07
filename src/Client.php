<?php

namespace MatthewSpencer\GarminConnect;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Cookie\CookieJar;

/**
 * Garmin Connect Client
 */

class Client {

	/**
	* @var $instance MatthewSpencer\GarminConnect\Client
	* @access public
	*/
	public static $instance = null;

	/**
	 * @var $guzzle GuzzleHttp\Client
	 * @access public
	 */
	public $guzzle = null;

	/**
	 * @var $guzzle GuzzleHttp\Cookie\CookieJar
	 * @access public
	 */
	public $jar = null;

	/**
	 * Public Constructor
	 */
	public function __construct() {

		$this->guzzle = new Guzzle();
		$this->jar = new CookieJar();

	}

	/**
	* Get Instance of This Class
	*
	* @return MatthewSpencer\GarminConnect\Client
	*/
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

	/**
	 * Get
	 *
	 * @param string $url
	 * @param array $params
	 * @return object GuzzleHttp $response
	 */
	public function get( $url, $params = [] ) {

		$params = array_merge( $params, [ 'cookies' => $this->jar ] );

		$response = $this->guzzle->get( $url, $params );

		return $response;

	}

	/**
	 * Post
	 *
	 * @param string $url
	 * @param array $params
	 * @return object GuzzleHttp $response
	 */
	public function post( $url, $params = [] ) {

		$params = array_merge( $params, [ 'cookies' => $this->jar ] );

		$response = $this->guzzle->post( $url, $params );

		return $response;

	}

	/**
	 * Username for currently logged in user
	 *
	 * @uses http://connect.garmin.com/user/username
	 * @return string|bool
	 */
	public function username() {

		$response = $this->get( 'https://connect.garmin.com/user/username' );

		if ( ! $body = json_decode( (string) $response->getBody(), true ) ) {
			return false;
		}

		return $body['username'];

	}

}