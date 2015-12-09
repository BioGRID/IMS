<?php

	$data = file_get_contents( '../../config/config.json' );
	$data = json_decode( $data, true );
	
	// DATABASE VARS
	define( 'DB_HOST', $data['DB']['DB_HOST'] );
	define( 'DB_USER', $data['DB']['DB_USER'] );
	define( 'DB_PASS', $data['DB']['DB_PASS'] );
	define( 'DB_IMS', $data['DB']['DB_IMS'] );
	
	$data = file_get_contents( 'config/config.json' );
	$data = json_decode( $data, true );
	
	define( 'DB_IMS_OLD', $data['DB']['DB_IMS_OLD'] );
	define( 'DB_IMS_TRANSITION', $data['DB']['DB_IMS_TRANSITION'] );
	define( 'DEFAULT_PASSWORD', $data['USERS']['DEFAULT_PASSWORD'] );

?>