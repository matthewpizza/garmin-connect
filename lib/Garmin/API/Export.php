<?php

namespace Garmin\API;

/**
 * Garmin Connect Export
 * Download Activities as GPX and TCX
 *
 * @see http://sergeykrasnov.ru/subsites/dev/garmin-connect-statisics/
 * @see https://github.com/cpfair/tapiriik/blob/master/tapiriik/services/GarminConnect/garminconnect.py
 * @see https://forums.garmin.com/showthread.php?72150-connect-garmin-com-signin-question&p=264580#post264580
 * @see https://forums.garmin.com/showthread.php?72150-connect-garmin-com-signin-question&p=275935#post275935
 * @see http://www.ciscomonkey.net/gc-to-dm-export/
 */

class Export {

	public $username;
	public $cookie;

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Create export client
	 *
	 * @param string $username
	 * @param string $password
	 * @param string $output_path
	 */
	public function __construct($username, $password, $output_path) {

		if (substr($output_path, -1) !== '/') {
			$output_path = "{$output_path}/";
		}

		if ( ! file_exists($output_path) ) {
			die('Output path does not exist');
		}

		$this->username = $username;
		$this->cookie = dirname(dirname(dirname(__DIR__))) . '/cookies/' . $username;

		$connected = Authenticate::make_connection($username, $password);

		if ( ! $connected ) {
			die('Authentication Error');
		}

	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Create Array of All Activities
	 *
	 * @uses http://connect.garmin.com/proxy/activitylist-service/activities/
	 * @return array $activities
	 */
	private function _get_list_of_activities() {
		if ( $activities = Tools::cache_get('list_of_activities', $this->username) ) {
			return $activities;
		}

		// get total
		$total_activities = $this->_get_total_activity_count();

		$limit = 100;
		$how_many_loops = ceil($total_activities/$limit);
		$activities = array();

		$url = "http://connect.garmin.com/proxy/activitylist-service/activities/{$this->username}";
		
		for ( $i = 0; $i < $how_many_loops; $i++) {
			$pagination = $total_activities - ($limit * ($i + 1));

			if ( $pagination < 0 ) {
				$limit = $limit + $pagination;
				$pagination = 1;
			}

			$params = http_build_query(array(
				'start' => $pagination,
				'limit' => $limit,
			));

			$response = Tools::curl(
				"{$url}?{$params}",
				array(
					'CURLOPT_MAXREDIRS' => 4,
					'CURLOPT_RETURNTRANSFER' => true,
					'CURLOPT_FOLLOWLOCATION' => true,
					'CURLOPT_COOKIEJAR' => $this->cookie,
					'CURLOPT_COOKIEFILE' => $this->cookie,
				),
				'GET'
			);

			$data = json_decode($response['data'], true);

			foreach ( $data['activityList'] as $activity ) {
				$activities[] = array(
					'id' => $activity['activityId'],
					'start_time' => array(
						'local' => $activity['startTimeLocal'],
						'gmt' => $activity['startTimeGMT'],
					),
					'type' => $activity['activityType']['typeKey'],

					// distance is in meters
					'distance' => $activity['distance'],
			}

		}

		Tools::cache_set('list_of_activities', $activities, $this->username);
		
		return $activities;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Get total activity count
	 *
	 * @uses http://connect.garmin.com/proxy/userstats-service/
	 * @return int
	 */
	private function _get_total_activity_count() {
		if ( $total_activities = Tools::cache_get('total_activities', $this->username) ) {
			return $total_activities;
		}

		$response = Tools::curl(
			"http://connect.garmin.com/proxy/userstats-service/statistics/{$this->username}",
			array(
				'CURLOPT_MAXREDIRS' => 4,
				'CURLOPT_RETURNTRANSFER' => true,
				'CURLOPT_FOLLOWLOCATION' => true,
				'CURLOPT_COOKIEJAR' => $this->cookie,
				'CURLOPT_COOKIEFILE' => $this->cookie,
			),
			'GET'
		);

		$data = json_decode($response['data'], true);
		$total_activities = (int) $data['userMetrics'][0]['totalActivities'];
		Tools::cache_set('total_activities', $total_activities, $this->username);

		return $total_activities;
	}

}