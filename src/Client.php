<?php

namespace MatthewSpencer\GarminConnect;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ClientException;

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

		if ( ! ini_get( 'date.timezone' ) ) {
		    date_default_timezone_set( 'UTC' );
		}

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

		try {
			$response = $this->guzzle->get( $url, $params );
		} catch (ClientException $e) {
			return false;
		}

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
	 * @uses https://connect.garmin.com/user/username
	 * @return string $username
	 */
	public function username() {

		if ( $username = Cache::get( 'username' ) ) {
			return $username;
		}

		$response = $this->get( 'https://connect.garmin.com/user/username' );

		if ( ! $body = json_decode( (string) $response->getBody(), true ) ) {
			return '';
		}

		$username = $body['username'];

		if ( ! empty( $username ) ) {
			Cache::set( 'username', $username );
		}

		return $username;

	}

	/**
	 * Totals
	 *
	 * @return mixed
	 */
	public function totals( $key = false ) {

		$username = $this->username();

		if ( ! $totals = Cache::get( 'totals', $username ) ) {

			$response = $this->get( "https://connect.garmin.com/proxy/userstats-service/statistics/{$username}" );

			if ( ! $body = json_decode( (string) $response->getBody(), true ) ) {
				return false;
			}

			$totals = [
				'activities' => (int) $body['userMetrics'][0]['totalActivities'],
				'distance' => (float) $body['userMetrics'][0]['totalDistance'],
				'duration' => (float) $body['userMetrics'][0]['totalDuration'],
				'calories' => (float) $body['userMetrics'][0]['totalCalories'],
				'elevationgain' => (float) $body['userMetrics'][0]['totalElevationGain'],
			];

			Cache::set( 'totals', $totals, $username );

		}

		if ( $key !== false ) {

			return isset( $totals[ $key ] ) ? $totals[ $key ] : false;

		}

		return $totals;

	}

}