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
				die( "Cannot create output path: {$output_path}\n" );
			}

		}

		$connected = Authenticate::new_connection( $email, $password );

		if ( ! $connected ) {
			die( "Authentication Error\n" );
		}

		$this->client = Client::instance();
		$this->username = $this->client->username();

		$activities = $this->list_of_activities();
		$downloaded = $this->download_activities( $activities, $output_path );

		if ( $downloaded ) {
			die( "Done!\n" );
		}

	}

	/**
	 * Create Array of All Activities
	 *
	 * @uses https://connect.garmin.com/proxy/activitylist-service/activities/
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

		$url = "https://connect.garmin.com/proxy/activitylist-service/activities/{$this->username}";

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
	private function download_activities( $activities, $path ) {

		$activities = $this->new_activities( $activities, $path );
		$count = 0;

		foreach ( $activities as $activity ) {

			if ( $count % 10 === 0 && $count !== 0 ) {
				sleep(0.5); // for rate limiting
			}

			$gpx = $this->download_file( $activity['id'], 'gpx', $path );
			$tcx = $this->download_file( $activity['id'], 'tcx', $path );

			if ( $gpx && $tcx ) {
				$this->update_activities( $activity, $path );
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

		$url = "https://connect.garmin.com/modern/proxy/download-service/export/{$type}/activity/{$id}";
		$filename = "{$path}/{$type}/activity_{$id}.{$type}";
		$directory = "{$path}/{$type}";

		if ( file_exists( $filename ) ) return true;

		if ( ! file_exists( $directory ) ) {

			$created = mkdir( $directory, 0777, true );

			if ( ! $created ) {
				die( "Cannot create: {$directory}\n" );
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
	 * @param array $activity
	 * @param string $path
	 * @return boolean
	 */
	private function update_activities( $activity, $path ) {

		$index = @file_get_contents( "{$path}/activities.json" );

		$data = json_decode( $index, true );
		$data = ! empty( $data ) ? $data : array();

		$exists = __::any( $data, function( $item ) {
			return $item['id'] === $activity['id'];
		} );

		if ( $exists ) return true;

		$activity['gpx'] = "gpx/activity_{$activity['id']}.gpx";
		$activity['tcx'] = "tcx/activity_{$activity['id']}.tcx";

		$data[] = $activity;

		$data = __::sortBy( $data, function( $item ) {
			return $item['id'];
		} );

		$data = json_encode( $data, JSON_PRETTY_PRINT );
		$bytes = file_put_contents( "{$path}/activities.json", $data );

		return $bytes === false ? false : true;

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
