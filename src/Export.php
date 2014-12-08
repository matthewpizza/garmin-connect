<?php

namespace MatthewSpencer\GarminConnect;
use __;

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

	/**
	 * @var string $username
	 * @access public
	 */
	public $username;

	/**
	 * @var object $client MatthewSpencer\GarminConnect\Client
	 * @access private
	 */
	private $client;

	/**
	 * Create export client
	 *
	 * @param string $email
	 * @param string $password
	 * @param string $output_path
	 */
	public function __construct( $email, $password, $output_path ) {

		if ( substr( $output_path, -1 ) === '/' ) {
			$output_path = rtrim( $output_path, '/\\' );
		}

		if ( ! file_exists( $output_path ) ) {

			$created = mkdir( $output_path, 0777, true );

			if ( ! $created ) {
				die( "Cannot create output path: {$output_path}" );
			}

		}

		$connected = Authenticate::new_connection( $email, $password );

		if ( ! $connected ) {
			die( 'Authentication Error' );
		}

		$this->client = Client::instance();
		$this->username = $this->client->username();

		if ( $this->total_activities() === $this->saved_activities( $output_path ) ) {
			return;
		}

		$activities = $this->list_of_activities();
		$downloaded = $this->download_activities( $activities, $output_path );

		if ( $downloaded ) {
			die( 'Done!' );
		}

	}

	/**
	 * Create Array of All Activities
	 *
	 * @uses http://connect.garmin.com/proxy/activitylist-service/activities/
	 * @return array $activities
	 */
	private function list_of_activities() {

		if ( $activities = Cache::get( 'list_of_activities', $this->username ) ) {
			return $activities;
		}

		$total = $this->total_activities();
		$limit = 20;
		$requests = ceil( $total / $limit );
		$activities = array();

		$url = "http://connect.garmin.com/proxy/activitylist-service/activities/{$this->username}";

		for ( $i = 0; $i < $requests; $i++ ) {
			$pagination = $total - ( $limit * ( $i + 1 ) );

			if ( $pagination < 0 ) {
				$limit = $limit + $pagination;
				$pagination = 1;
			}

			$response = $this->client->get( $url, [
				'query' => [
					'start' => $pagination,
					'limit' => $limit,
				],
			] );

			$data = json_decode( (string) $response->getBody(), true );

			foreach ( $data['activityList'] as $activity ) {

				$activities[] = array(
					'id' => $activity['activityId'],
					'start_time' => array(
						'local' => $activity['startTimeLocal'],
						'gmt' => $activity['startTimeGMT'],
					),
					'type' => $activity['activityType']['typeKey'],
					'distance' => $activity['distance'], // distance is in meters
				);

			}

		}

		Cache::set( 'list_of_activities', $activities, $this->username );

		return $activities;

	}

	/**
	 * Get total activity count
	 *
	 * @return integer|boolean
	 */
	private function total_activities() {

		return $this->client->totals( 'activities' );

	}

	/**
	 * Download Activities from List of IDs
	 *
	 * @param array $activites
	 * @param string $path
	 */
	private function _download_activities($activities, $path) {
		$activities = $this->_get_new_activities($activities, $path);
		$count = 0;

		foreach ( $activities as $activity ) {

			if ( $count % 10 === 0 && $count !== 0 ) {
				sleep(1);
			}

			if (
				$this->_download_file($activity['id'], 'gpx', $path) &&
				$this->_download_file($activity['id'], 'tcx', $path)
			) {
				$this->_update_activities_index($activity, $path);
			}

			$count++;
		}

		return true;
	}

	/**
	 * Download File
	 *
	 * @param integer $id
	 * @param string $type gpx or tcx
	 * @param string $path
	 *
	 * @return boolean
	 */
	private function download_file( $id, $type, $path ) {

		$url = "http://connect.garmin.com/proxy/activity-service-1.1/{$type}/activity/{$id}?full=true";
		$filename = "{$path}/{$type}/activity_{$id}.{$type}";
		$directory = "{$path}/{$type}";

		if ( file_exists( $filename ) ) return true;

		if ( ! file_exists( $directory ) ) {

			$created = mkdir( $directory, 0777, true );

			if ( ! $created ) {
				die( "Cannot create: {$directory}" );
			}

		}

		$response = $this->client->get( $url, [
			'save_to' => $filename,
		] );

		return file_exists( $filename );

	}

	/**
	 * Update Activities Index
	 *
	 * @param array $new_activity
	 * @param string $path
	 */
	private function _update_activities_index($new_activity, $path) {
		$index = @file_get_contents($path . 'activities.json');
		$index = json_decode($index, true);

		$new_activity['gpx'] = "gpx/activity_{$new_activity['id']}.gpx";
		$new_activity['tcx'] = "tcx/activity_{$new_activity['id']}.tcx";

		if ( ! empty($index) ) {
			$ok_to_go = true;

			// no duplicates!
			foreach ( $index as $activity ) {
				if ( $activity['id'] === $new_activity['id'] ) {
					$ok_to_go = false;
					break;
				}
			}

			// bail if duplicate
			if ( ! $ok_to_go ) return;
		}
		else {
			$index = array();
		}

		$index[] = $new_activity;

		$index = __::sortBy($index, function($activity) {
			return $activity['id'];
		});

		$index = json_encode($index, JSON_PRETTY_PRINT);
		file_put_contents($path . 'activities.json', $index);
	}

	/**
	 * Get Saved Activities Count
	 *
	 * @param  string $path
	 * @return integer
	 */
	private function saved_activities( $path ) {

		$index = @file_get_contents( "{$path}/activities.json" );
		$index = json_decode( $index, true );

		return count( $index );

	}

	/**
	 * Get New Activities
	 *
	 * @param array $activities
	 * @param string $path
	 * @return array $new
	 */
	private function new_activities( $activities, $path ) {

		$index = @file_get_contents( "{$path}/activities.json" );

		if ( ! $index ) {
			return $activities;
		}

		$saved = json_decode( $index, true );
		$ids = __::pluck( $saved, 'id' );

		$new = __::filter( $activities, function( $item ) use( $ids ) {
			return ! in_array( $item['id'], $ids );
		} );

		return $new;

	}

}