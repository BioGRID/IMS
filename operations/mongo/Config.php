<?php

	$data = file_get_contents( '../../config/config.json' );
	$data = json_decode( $data, true );
	
	// DATABASE VARS
	define( 'DB_HOST', $data['DB']['DB_HOST'] );
	define( 'DB_USER', $data['DB']['DB_USER'] );
	define( 'DB_PASS', $data['DB']['DB_PASS'] );
	define( 'DB_IMS', $data['DB']['DB_IMS'] );
	define( 'DB_QUICK', $data['DB']['DB_QUICK'] );
	
	// MONGO VARS
	define( 'MONGO_HOST', $data['MONGO']['MONGO_HOST'] );
	define( 'MONGO_PORT', $data['MONGO']['MONGO_PORT'] );
	define( 'MONGO_USER', $data['MONGO']['MONGO_USER'] );
	define( 'MONGO_PASS', $data['MONGO']['MONGO_PASS'] );
	define( 'MONGO_IMS', $data['MONGO']['MONGO_IMS'] );

?>