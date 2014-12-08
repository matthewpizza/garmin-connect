<?php

namespace MatthewSpencer\GarminConnect;

/**
 * Cache
 */

class Cache {

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
	public static function set( $key, $data, $group = false, $duration = 3600 ) {
		$cache_path = self::cache_path();
		$key = self::sanitize_key( $key );
		$filename = "{$key}";

		if ( $group ) {
			$group = self::sanitize_key( $group );
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

	/**
	 * Get Cache Item
	 *
	 * @param string $key
	 * @param string $group
	 * @param int $duration
	 *
	 * @return bool|array
	 */
	public static function get( $key, $group = false, $duration = 3600 ) {
		$cache_path = self::cache_path();
		$key = self::sanitize_key( $key );
		$filename = "{$key}";

		if ( $group ) {
			$group = self::sanitize_key( $group );
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

	/**
	 * Helper Function: Cache Path
	 *
	 * @return string $path
	 */
	private static function cache_path() {
		$path = dirname(__DIR__) . '/cache/';

		if ( ! file_exists($path) ) {
			mkdir($path);
		}

		return $path;
	}

	/**
	 * Helper Function: Sanitize Cache Key
	 *
	 * Clean up key name
	 * Ex. users/john-doe => users.john-doe
	 *
	 * @param string $key
	 * @return string $key
	 */
	private static function sanitize_key( $key ) {
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

}