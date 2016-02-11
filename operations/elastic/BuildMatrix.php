<?php

/**
 * Build out the matrix collection in ElasticSearch
 * containing all the interactions in quick searchable/sortable combination
 */
 
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/../../site/app/classes/models/Lookups.php';

$lookups = new IMS\app\classes\models\Lookups( );
$interactionTypeHash = $lookups->buildInteractionTypeHash( );
print_r( $interactionTypeHash );



?>