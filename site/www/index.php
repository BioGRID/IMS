<?php

/**
 * Index
 * Manage access to the site. Load files needed to direct traffic to the
 * correct location within the application structure
 */
 
session_start( );
header( "Cache-control: private" );

require_once __DIR__ . '/../app/inc/config.php';
require_once __DIR__ . '/../app/vendor/autoload.php';
require_once __DIR__ . '/../app/lib/Bootstrap.php';

use IMS\app\lib\Loader;
use IMS\app\lib\User;

$loader = new Loader( $_GET );
$controller = $loader->createController( );
$controller->executeAction( );

?>