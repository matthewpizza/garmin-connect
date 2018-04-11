#!/usr/bin/php
<?php

// Get Options
$shortopts = 'e::';
$shortopts .= 'p::';
$shortopts .= 'o::';

$longopts = array( 'email::', 'password::', 'output_path::' );

$options = getopt( $shortopts, $longopts );

$email = $options['e'] ?? $options['email'] ?? '';
$password = $options['p'] ?? $options['password'] ?? '';
$output_path = $options['o'] ?? $options['output_path'] ?? '';

// Make sure required options are set
foreach ( array( 'email', 'password', 'output_path' ) as $variable ) {
	if ( isset( $$variable ) ) {
		continue;
	}
	die( "Please set the {$variable} argument.\n" );
}

// Require the autoload
$autoload = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! file_exists( $autoload ) ) {
	die( "Please generate the autoload by running `composer install`\n" );
}

require_once $autoload;

// Run the exporter
return new MatthewSpencer\GarminConnect\Export( $email, $password, $output_path );
