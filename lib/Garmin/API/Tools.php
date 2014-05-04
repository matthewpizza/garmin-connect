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

		$options['CURLOPT_HEADER'] = true;
		$options['CURLOPT_RETURNTRANSFER'] = true;

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

}