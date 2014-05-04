<?php

namespace Garmin\API;

/**
 * Tools
 */

class Tools {

	/**
	 * curl wrapper
	 *
	 * @param string $url
	 * @param array $options
	 * @param string $method
	 * @return array $return
	 */
	public static function curl($url, $options, $method) {
		$return = array();

		if ( ! function_exists('curl_version') ) {
			die('The PHP CURL extension is not installed.');
		}

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);

		if ( ! isset($options['CURLOPT_HEADER']) ) {
			$options['CURLOPT_HEADER'] = true;
		}

		if ( ! isset($options['CURLOPT_RETURNTRANSFER']) ) {
			$options['CURLOPT_RETURNTRANSFER'] = true;
		}

		foreach ($options as $option => $value) {
			$option = str_replace( 'CURLOPT_', '', strtoupper($option) );
			$value = ( is_array( $value ) ? http_build_query( $value, NULL, '&' ) : $value );

			curl_setopt($ch, constant("CURLOPT_{$option}"), $value);
		}

		$method = strtoupper($method);

		switch ($method) {
			case 'GET':
				curl_setopt($ch, CURLOPT_HTTPGET, true);
				break;
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, true);
				break;
			case 'HEAD':
				curl_setopt($ch, CURLOPT_NOBODY, true);
				break;
			default:
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		}

		$response = curl_exec($ch);
		$headers = curl_getinfo($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		curl_close($ch);
		
		$body = substr($response, $header_size);

		$return['headers'] = $headers;
		$return['data'] = $body;

		return $return;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Set Cache Item
	 *
	 * @param string $key
	 * @param array $data
	 * @param string $group
	 * @param int $duration
	 *
	 * @return bool $cache_set
	 */
	public static function cache_set($key, $data, $group = false, $duration = 3600) {
		$cache_path = self::_cache_path();
		$key = self::_sanitize_key( $key );
		$filename = "{$key}";

		if ( $group ) {
			$group = self::_sanitize_key( $group );
			$filename .= "_{$group}";
		}
		
		$timestamp = time();

		// serialize data with timestamp
		$serialized_data = serialize( array('timestamp' => $timestamp, 'data' => $data) );

		// write cache
		$cache_set = file_put_contents( $cache_path . $filename, $serialized_data );

		// file_put_contents() returns false on failure, number of bytes written on success
		$cache_set = ( $cache_set === false ? $cache_set : true );

		return $cache_set;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Get Cache Item
	 *
	 * @param string $key
	 * @param string $group
	 * @param int $duration
	 * 
	 * @return bool|array
	 */
	public static function cache_get($key, $group = false, $duration = 3600) {
		$cache_path = self::_cache_path();
		$key = self::_sanitize_key( $key );
		$filename = "{$key}";

		if ( $group ) {
			$group = self::_sanitize_key( $group );
			$filename .= "_{$group}";
		}

		// get cache
		$serialized_data = @file_get_contents( $cache_path . $filename );

		// file_get_contents() returns false on failure
		if ( $serialized_data === false || empty($serialized_data) ) return false;

		$cache = unserialize( $serialized_data );
		extract( $cache );

		// return false if cache is expired
		if ( time() > ($timestamp + $duration) ) return false;

		return $data;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Helper Function: Cache Path
	 *
	 * @return string $path
	 */
	private static function _cache_path() {
		$path = dirname(dirname(dirname(__DIR__))) . '/cache/';

		if ( ! file_exists($path) ) {
			mkdir($path);
		}

		return $path;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Helper Function: Sanitize Cache Key
	 * 
	 * Clean up key name
	 * Ex. users/john-doe => users.john-doe
	 *
	 * @param string $key
	 * @return string $key
	 */
	private static function _sanitize_key( $key ) {
		$search = array(
			'/',
			'&',
			'=',
			',',
			'+'
		);
		$replace = array(
			'.',
			'.',
			'_',
			'-',
			'-',
		);

		$key = strtolower( str_replace($search, $replace, $key) );

		return $key;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Sort Multidimensional Array By Value
	 *
	 * @param array $array
	 * @param string $sory_by
	 * @return array $array
	 */
	public static function sort_multidimensional_array_by_value( &$array, $sory_by ) {
		$sort_array = array();
		$return_array = array();

		reset($array);

		foreach ( $array as $key => $value ) {
		    $sort_array[$key] = $value[$sory_by];
		}

		asort($sort_array);

		foreach ( $sort_array as $key => $value ) {
		    $return_array[$key] = $array[$key];
		}
		
		$array = $return_array;

		return $array;

	}

}