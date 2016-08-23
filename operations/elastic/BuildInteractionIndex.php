<?php

/**
 * Build out the matrix collection in ElasticSearch
 * containing all the interactions in quick searchable/sortable combination
 */
 
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/classes/ElasticSearch.php';

//115451
$es = new ElasticSearch( );
//$es->initializeInteractionsIndex( );
$es->buildInteractionIndexByAll( );

?>