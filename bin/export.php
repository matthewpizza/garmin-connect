#!/usr/bin/php

<?php

/**
 * Run the exporter
 */

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Get Options
 */

$shortopts = 'u::';
$shortopts .= 'p::';
$shortopts .= 'o::';

$longopts = array(
	'username::',
	'password::',
	'output_path::',
);

$options = getopt($shortopts, $longopts);

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Set Variables
 */
$variable_to_set = array(
	array(
		'variable' => 'username',
		'values' => array('u', 'username'),
	),
	array(
		'variable' => 'password',
		'values' => array('p', 'password'),
	),
	array(
		'variable' => 'output_path',
		'values' => array('o', 'output_path'),
	),
);

foreach ( $variable_to_set as $option ) {
	foreach ( $option['values'] as $key ) {

		if ( isset($options[$key]) ) {
			$$option['variable'] = $options[$key];
			break;
		}

	}
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Make sure required options are set
 */
foreach (array('username', 'password', 'output_path') as $variable) {
	if ( ! isset($$variable) ) {
		die("Please set the {$variable} argument.\n");
	}
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Require the autoload
 */
$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if ( ! file_exists($autoload) ) {
	die("Please generate the autoload by running `composer install`");
}

require_once $autoload;

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Run the exporter
 */
return new MatthewSpencer\GarminConnect\Export($username, $password, $output_path);