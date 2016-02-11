<?php

	$data = file_get_contents( '../../config/config.json' );
	$data = json_decode( $data, true );
	
	// DATABASE VARS
	define( 'DB_HOST', $data['DB']['DB_HOST'] );
	define( 'DB_PORT', $data['DB']['DB_PORT'] );
	define( 'DB_USER', $data['DB']['DB_USER'] );
	define( 'DB_PASS', $data['DB']['DB_PASS'] );
	define( 'DB_IMS', $data['DB']['DB_IMS'] );
	define( 'DB_QUICK', $data['DB']['DB_QUICK'] );
	define( "DB_CONNECT", 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_IMS . ';charset=utf8' );
	
	$data = file_get_contents( 'config/config.json' );
	$data = json_decode( $data, true );
	
	define( 'ES_HOST', $data['ELASTICSEARCH']['ES_HOST'] );
	define( 'ES_PORT', $data['ELASTICSEARCH']['ES_PORT'] );
	define( 'ES_INDEX', $data['ELASTICSEARCH']['ES_INDEX'] );
	define( 'ES_TYPE', $data['ELASTICSEARCH']['ES_TYPE'] );

?>