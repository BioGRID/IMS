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
$matrix->buildMatrixByInteraction( "521082" );

// $hosts = array( ES_HOST . ":" . ES_PORT );

// $clientBuilder = Elasticsearch\ClientBuilder::create( );
// $clientBuilder->setHosts( $hosts );
// $client = $clientBuilder->build( );

// DELETE EXISTING INDEX

// $params = array( 
	// "index" => ES_INDEX
// );

// $response = $client->indices( )->delete( $params );
// print_r( $response );

// CREATE NEW INDEX

// $params = array( 
	// "index" => ES_INDEX,
	// "body" => array( 
		// "settings" => array( 
			// "number_of_shards" => 1,
			// "number_of_replicas" => 0
		// )
	// )
// );

// $response = $client->indices( )->create( $params );
// print_r( $response );

// INPUT TEST DOCUMENTS

// $testDocuments = array( );
// $testDocument = array( 
	// "index" => ES_INDEX,
	// "type" => ES_TYPE,
	// "body" => array( 
		// "interaction_id" => 520140,
		// "dataset_id" => 115451,
		// "interaction_type" => 1,
		// "interaction_state" => "normal",
		// "interaction_status" => "ACTIVATED",
		// "user_id" => 5,
		// "user_name" => "Rose Oughtred",
		// "history_date" => "2011-04-07 10:45:10"
		// "attributes" => array( );
		// "participants" => array( );
	// )
// );

// $testDocument['body']['attributes'][] = array( 
	// "attribute_id" => 6970,
	// "attribute_type_name" => "Note",
	// "attribute_type_id" => 22,
	// "attribute_type_category_id" => 3,
	// "attribute_type_category_name" => "Note",
	// "attribute_value" => "High Throughput: Proteome microarrays were used to identify proteins that interact with protein kinases."
// );

// $testDocument['body']['attributes'][] = array( 
	// "attribute_id" => 9,
	// "attribute_type_name" => "Experimental System",
	// "attribute_type_id" => 11,
	// "attribute_type_category_id" => 1,
	// "attribute_type_category_name" => "Ontology Term",
	// "attribute_value" => "Reconstituted Complex",
	// "attribute_ontology_term_id" => "193471",
	// "attribute_ontology_term_official_id" => "BIOGRID_SYSTEM:0000021"
// );
		

// {"attributes": {"note": {}, "ontology term": {"experimental system": [{"user_id": "5", "attribute_type_shortcode": "ES", "attribute_type_category_id": 1, "attribute_children": [], "attribute_type_name": "Experimental System", "attribute_type_id": 11, "attribute_id": "9", "attribute_value": "194571", "attribute_annotation": {"ontology_term_id": 194571, "ontology_term_official_id": "BIOGRID_SYSTEM:0000021", "ontology_term_name": "Reconstituted Complex", "ontology_name": "BioGRID Experimental System Ontology", "ontology_id": 17}, "attribute_type_category_name": "Ontology Term"}], "throughput tag": [{"user_id": "5", "attribute_type_shortcode": "TT", "attribute_type_category_id": 1, "attribute_children": [], "attribute_type_name": "Throughput Tag", "attribute_type_id": 13, "attribute_id": "21714", "attribute_value": "194716", "attribute_annotation": {"ontology_term_id": 194716, "ontology_term_official_id": "BIOGRID_TP:0000002", "ontology_term_name": "High Throughput", "ontology_name": "BioGRID Throughput Ontology", "ontology_id": 23}, "attribute_type_category_name": "Ontology Term"}]}}, "participants": [{"participant_type_name": "Gene", "participant_role_id": "2", "participant_role_name": "Bait", "participant_type_id": "1", "participant_id": "19387", "participant_annotation": {"organism_strain": "S288c", "organism_id": 559292, "systematic_name": "YBR059C", "official_symbol": "AKL1", "organism_abbreviation": "S. cerevisiae", "aliases": ["serine/threonine protein kinase AKL1", "S000007479"]}}, {"participant_type_name": "Gene", "participant_role_id": "3", "participant_role_name": "Prey", "participant_type_id": "1", "participant_id": "20878", "participant_annotation": {"organism_strain": "S288c", "organism_id": 559292, "systematic_name": "YER087W", "official_symbol": "AIM10", "organism_abbreviation": "S. cerevisiae", "aliases": ["putative proline--tRNA ligase AIM10"]}}], "interaction_id": "520140"}

// $testDocument = array( 
	// "index" => ES_INDEX,
	// "type" => ES_TYPE,
	// "id" => 1,
	// "body" => array( 
		// "attributes" => array( 
			// "experimental_system" => array( array( 
				// "name" => "two-hybrid",
				// "id" => 1,
				// "ontology_id" => "biogrid_system:0000001"
			// ))
		// )
	// )
// );

// $response = $client->index( $testDocument );
// print_r( $response );

// $testDocument = array( 
	// "index" => ES_INDEX,
	// "type" => ES_TYPE,
	// "id" => 2,
	// "body" => array( 
		// "attributes" => array( 
			// "experimental_system" => array( array(
				// "name" => "affinity capture-ms",
				// "id" => 2,
				// "ontology_id" => "biogrid_system:0000002"
			// ))
		// )
	// )
// );

// $response = $client->index( $testDocument );
// print_r( $response );

// $testDocument = array( 
	// "index" => ES_INDEX,
	// "type" => ES_TYPE,
	// "id" => 3,
	// "body" => array( 
		// "attributes" => array( 
			// "experimental_system" => array( array(
				// "name" => "affinity capture-ms",
				// "id" => 2,
				// "ontology_id" => "biogrid_system:0000002"
			// ), array( 
				// "name" => "two-hybrid",
				// "id" => 1,
				// "ontology_id" => "biogrid_system:0000001"
			// ))
		// )
	// )
// );

// $response = $client->index( $testDocument );
// print_r( $response );

// $params = array( 
	// "index" => ES_INDEX,
	// "type" => ES_TYPE,
	// "body" => array( 
		// "query" => array( 
			// "filtered" => array( 
				// "query" => array( "match_all" => array( ) ),
				// "filter" => array( "term" => array( "attributes.experimental_system.name" => 'two-hybrid' ) )
			// )
		// )
	// )
// );

// $params = array( 
	// "index" => ES_INDEX,
	// "type" => ES_TYPE,
	// "body" => array( 
		// "query" => array( 
			// "multi_match" => array( 
				// "query" => "hybrid",
				// "fields" => array( "attributes.experimental_system.ontology_id", "attributes.experimental_system.name" )
			// )
		// )
	// )
// );

// $response = $client->search( $params );
// print_r( $response );



?>