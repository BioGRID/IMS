<?php

//namespace IMS\www;

/**
 * Index
 * Manage access to the site. Load files needed to direct traffic to the
 * correct location within the application structure
 */

require_once __DIR__ . '/../app/inc/config.php';
require_once __DIR__ . '/../app/vendor/autoload.php';
require_once __DIR__ . '/../app/lib/Bootstrap.php';

print_r( $_GET );

use IMS\app\lib\Loader;

$loader = new Loader( $_GET );
$controller = $loader->createController( );
//$controller->executeAction( );

// $loader = new Twig_Loader_Filesystem( TEMPLATE_PATH );
// $twig = new Twig_Environment( $loader );

// $controller = new controllers\HomeController( $twig );
// $controller->Index( );

?>