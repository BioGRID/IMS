<?php

/**
 * Build out the matrix collection in ElasticSearch
 * containing all the interactions in quick searchable/sortable combination
 */
 
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/classes/Matrix.php';

//115451
$matrix = new Matrix( );
$matrix->initialize( );
$matrix->buildMatrixByAll( );
//$matrix->buildMatrixByDataset( "115451" );
//$matrix->buildMatrixByInteraction( "1434467" );

// $params = array( 
	// "index" => ES_INDEX,
	// "type" => ES_TYPE,
	// "body" => array( 
		// "query" => array(
			// "nested" => array(
				// "path" => "attributes",
				// "query" => array(
					// "match" => array( "attributes.attribute_value" => "inhibitor" )
				// )
			// )
		// )
	// )
// );

// $params = array( 
	// "index" => ES_INDEX,
	// "type" => ES_TYPE,
	// "body" => array( 
		// "query" => array(
			// "nested" => array(
				// "path" => "participants",
				// "query" => array(
					// "bool" => array( 
						// "must" => array( 
							// array( "match" => array( "participants.primary_name" => "F2" ) ),
							// array( "match" => array( "participants.organism_id" => "9606" ) )
						// )
					// )
				// ),
				// "inner_hits" => array( )
			// )
		// )
	// )
// );

// $params = array(
	// "index" => ES_INDEX,
	// "type" => ES_TYPE,
	// "body" => array(
		// "query" => array( 
			// "term" => array( 
				// "dataset_id" => 115351
			// )
		// )
	// )
// );

// $response = $matrix->search( $params );
// print_r( $response );		
				

?>