<?php

/**
 * Datasets
 * This model class is for handling data processing for new
 * and existing datasets stored in the database.
 */

use \PDO;

require_once __DIR__ . '/../../../site/app/classes/models/Lookups.php';
 
class Matrix {

	private $db;
	private $lookups;
	
	private $interactionTypesHash;
	private $userNameHash;
	private $participantTypeHash;
	private $participantRoleHASH;
	private $annotationHASH;
	private $participantTypeMappingHASH;
	private $attributeTypeHASH;
	private $ontologyTermHASH;
	
	private $hosts;
	private $clientBuilder;
	private $client;
	
	private $BULK_BATCH_SIZE = 500;

	/**
	 * Establish a database connection and also build several quick
	 * lookup hashes so we can speed up the process.
	 */
	
	public function __construct( ) {
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$this->lookups = new IMS\app\classes\models\Lookups( );
		
		$this->hosts = array( ES_HOST . ":" . ES_PORT );
		$this->clientBuilder = Elasticsearch\ClientBuilder::create( );
		$this->clientBuilder->setHosts( $this->hosts );
		$this->client = $this->clientBuilder->build( );
	}
	
	/**
	 * Build quick lookups for batch processing
	 */
	 
	private function buildQuickLookups( ) {
		$this->interactionTypesHash = $this->lookups->buildInteractionTypeHash( );
		$this->userNameHash = $this->lookups->buildUserNameHash( );
		$this->participantTypeHash = $this->lookups->buildParticipantTypesHash( );
		$this->participantRoleHash = $this->lookups->buildParticipantRoleHash( );
		$this->annotationHash = $this->lookups->buildAnnotationHash( );
		$this->participantTypeMappingHash = $this->lookups->buildParticipantTypeMappingHash( );
		$this->attributeTypeHash = $this->lookups->buildAttributeTypeHASH( );
		$this->ontologyTermHash = $this->lookups->buildAttributeOntologyTermHASH( );
	}
	
	/**
	 * Build the matrix by fetching interactions by dataset id
	 */
	 
	public function buildMatrixByDataset( $datasetID ) {
		
		$this->buildQuickLookups( );
		
		$stmt = $this->db->prepare( "SELECT * FROM " . DB_IMS . ".interactions WHERE dataset_id=?" );
		$stmt->execute( array( $datasetID ) );
		
		// Build Param Format
		$params = array( 
			"body" => array( )
		);
		
		$documentCount = 0;
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			
			$documentCount++;
			
			$params["body"][] = array( 
				"index" => array( 
					"_index" => ES_INDEX,
					"_type" => ES_TYPE,
					"_id" => $row->interaction_id
				)
			);
			
			// Build Document
			$document = $this->buildMatrixDocument( $row );
			$params["body"][] = $document;

			if( ($documentCount % $this->BULK_BATCH_SIZE) == 0 ) {
				// Insert it into Elastic Search
				$responses = $this->client->bulk( $params );
				$params["body"] = array( );
				print_r( $responses );
			}
			
		}
		
		if( sizeof( $params["body"] ) > 0 ) {
			$responses = $this->client->bulk( $params );
			$params["body"] = array( );
			print_r( $responses );
		}
	}
	
	/**
	 *  Build the matrix by fetching interactions by interaction_id
	 */
	 
	public function buildMatrixByInteraction( $interactionID ) {
		
		$this->buildQuickLookups( );
		
		$stmt = $this->db->prepare( "SELECT * FROM " . DB_IMS . ".interactions WHERE interaction_id=?" );
		$stmt->execute( array( $interactionID ) );
		
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			// Build Document
			$document = $this->buildMatrixDocument( $row );

			// Build Param Format
			$params = array( 
				"index" => ES_INDEX,
				"type" => ES_TYPE,
				"id" => $row->interaction_id,
				"body" => $document
			);
			
			// Insert it into Elastic Search
			$response = $this->client->index( $params );
				
		}
		
	}
	
	/**
	 * Build a matrix document using the structure required by our search system
	 */
	 
	private function buildMatrixDocument( $interaction ) {
		
		$document = array( );
		$document['interaction_id'] = $interaction->interaction_id;
		$document['dataset_id'] = $interaction->dataset_id;
		$document['interaction_type_id'] = $interaction->interaction_type_id;
		$document['interaction_type_name'] = $this->interactionTypesHash[$interaction->interaction_type_id];
		$document['interaction_state'] = $interaction->interaction_state;
		
		$document += $this->fetchInteractionHistoryDetails( $interaction->interaction_id );
		$document['attributes'] = $this->fetchInteractionAttributes( $interaction->interaction_id );
		$document['participants'] = $this->fetchInteractionParticipants( $interaction->interaction_id );
		
		return $document;
		
	}
	
	/**
	 * Return an array containing interaction history details for mapping to a document
	 */
	 
	private function fetchInteractionHistoryDetails( $interactionID ) {
		
		$stmt = $this->db->prepare( "SELECT modification_type, user_id, history_addeddate FROM " . DB_IMS . ".history WHERE interaction_id=? AND modification_type IN ('ACTIVATED','DISABLED') ORDER BY history_addeddate DESC LIMIT 1" );
		$stmt->execute( array( $interactionID ) );
		
		$historyDetails = array( ); 
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$historyDetails['history_status'] = $row->modification_type;
			$historyDetails['history_user_id'] = $row->user_id;
			$historyDetails['history_user_name'] = $this->userNameHash[$row->user_id];
			$historyDetails['history_date'] = date( 'Y/m/d H:i:s', strtotime( $row->history_addeddate ));
		}
		
		return $historyDetails;
		
	}
	
	/**
	 * Return an array containing interaction attributes
	 */
	
	private function fetchInteractionAttributes( $interactionID ) {
	
		$stmt = $this->db->prepare( "SELECT interaction_attribute_id, attribute_id, interaction_attribute_parent, user_id, interaction_attribute_addeddate FROM " . DB_IMS . ".interaction_attributes WHERE interaction_id=? AND interaction_attribute_status='active' ORDER BY interaction_attribute_addeddate ASC" );
		$stmt->execute( array( $interactionID ) );
		
		$attDetails = array( );		
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$attDetail = array( );
			$attDetail['interaction_attribute_id'] = $row->interaction_attribute_id;
			$attDetail['attribute_id'] = $row->attribute_id;
			$attDetail['attribute_parent_id'] = $row->interaction_attribute_parent;
			$attDetail['attribute_user_id'] = $row->user_id;
			$attDetail['attribute_user_name'] = $this->userNameHash[$row->user_id];
			$attDetail['attribute_addeddate'] = date( 'Y/m/d H:i:s', strtotime( $row->interaction_attribute_addeddate ));
			
			$attDetail += $this->fetchAttribute( $row->attribute_id );
			$attDetails[] = $attDetail;
		}
		
		return $attDetails;
	
	}
	
	/**
	 * Return an array of attribute details
	 */
	
	private function fetchAttribute( $attributeID ) {
	
		$stmt = $this->db->prepare( "SELECT attribute_value, attribute_type_id FROM " . DB_IMS . ".attributes WHERE attribute_id=? LIMIT 1" );
		$stmt->execute( array( $attributeID ) );
		
		$attribute = array( ); 
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$attribute['attribute_value'] = $row->attribute_value;
			$attribute['attribute_type_id'] = $row->attribute_type_id;
			
			$attributeTypeInfo = $this->attributeTypeHash[$row->attribute_type_id];
			$attribute['attribute_type_name'] = $attributeTypeInfo->attribute_type_name;
			$attribute['attribute_type_shortcode'] = $attributeTypeInfo->attribute_type_shortcode;
			$attribute['attribute_type_category_id'] = $attributeTypeInfo->attribute_type_category_id;
			$attribute['attribute_type_category_name'] = $attributeTypeInfo->attribute_type_category_name;
			$attribute['ontology_term_id'] = '0';
			$attribute['ontology_term_official_id'] = '0';
			
			// FOR ONTOLOGY TERMS ONLY
			if( $attribute['attribute_type_category_id'] == "1" ) {
				$ontologyInfo = $this->ontologyTermHash[$row->attribute_value];
				$attribute['attribute_value'] = $ontologyInfo->ontology_term_name;
				$attribute['ontology_term_id'] = $row->attribute_value;
				$attribute['ontology_term_official_id'] = $ontologyInfo->ontology_term_official_id;
			}
			
		}
		
		return $attribute;
	
	}
	
	/**
	 * Return an array containing interaction participants
	 */
	
	private function fetchInteractionParticipants( $interactionID ) {
	
		$stmt = $this->db->prepare( "SELECT interaction_participant_id, participant_id, participant_role_id FROM " . DB_IMS . ".interaction_participants WHERE interaction_id=? AND interaction_participant_status='active'" );
		$stmt->execute( array( $interactionID ) );
		
		$partDetails = array( );		
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$partDetail = array( );
			$partDetail['interaction_participant_id'] = $row->interaction_participant_id;
			$partDetail['participant_id'] = $row->participant_id;
			$partDetail['participant_role_id'] = $row->participant_role_id;
			$partDetail['participant_role_name'] = $this->participantRoleHash[$row->participant_role_id];
			$partDetail['participant_type_id'] = $this->participantTypeMappingHash[$row->participant_id];
			$partDetail['participant_type_name'] = $this->participantTypeHash[$partDetail['participant_type_id']];
			
			$partDetail += $this->fetchParticipantAnnotation( $row->participant_id );
			
			// NEED TO ADD PARTICIPANT ATTRIBUTES HERE AS WELL FOR ALLELES
			$partDetail['attributes'] = $this->fetchInteractionParticipantAttributes( $row->interaction_participant_id );
			
			$partDetails[] = $partDetail;
		}
		
		return $partDetails;
	
	}
	
	/**
	 * Return an array of annotation for a given participant
	 */
	
	private function fetchParticipantAnnotation( $participantID ) {
		return $this->annotationHash[$participantID];
	}
	
	/**
	 * Return an array containing interaction participant attributes
	 */
	
	private function fetchInteractionParticipantAttributes( $interactionParticipantID ) {
	
		$stmt = $this->db->prepare( "SELECT interaction_participant_attribute_id, attribute_id, interaction_participant_attribute_parent, user_id, interaction_participant_attribute_addeddate FROM " . DB_IMS . ".interaction_participant_attributes WHERE interaction_participant_id=? AND interaction_participant_attribute_status='active' ORDER BY interaction_participant_attribute_addeddate ASC" );
		$stmt->execute( array( $interactionParticipantID ) );
		
		$attDetails = array( );		
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$attDetail = array( );
			$attDetail['interaction_participant_attribute_id'] = $row->interaction_participant_attribute_id;
			$attDetail['attribute_id'] = $row->attribute_id;
			$attDetail['attribute_parent_id'] = $row->interaction_participant_attribute_parent;
			$attDetail['attribute_user_id'] = $row->user_id;
			$attDetail['attribute_user_name'] = $this->userNameHash[$row->user_id];
			$attDetail['attribute_addeddate'] = date( 'Y/m/d H:i:s', strtotime( $row->interaction_participant_attribute_addeddate ));
			
			$attDetail += $this->fetchAttribute( $row->attribute_id );
			$attDetails[] = $attDetail;
		}
		
		return $attDetails;
	
	}
	
	/**
	 * Initialize the publications index from scratch
	 */
	 
	public function initialize( ) {
		
		// DELETE EXISTING INDEX

		$params = array( 
			"index" => ES_INDEX
		);

		$response = $this->client->indices( )->delete( $params );
		print_r( $response );
		
		// CREATE NEW INDEX

		$params = array( 
			"index" => ES_INDEX,
			"body" => array( 
				"settings" => array( 
					"number_of_shards" => 1,
					"number_of_replicas" => 0,
					"index" => array(
						"analysis" => array(
							"analyzer" => array( 
								"keyword_analyzer" => array(
									"tokenizer" => "keyword",
									"filter" => "lowercase"
								)
							)
						)
					)
				)
			)
		);
		
		// ADD A MAPPING
		
		$response = $this->client->indices( )->create( $params );
		print_r( $response );
		
		$params = array( 
			"index" => ES_INDEX,
			"type" => ES_TYPE,
			"body" => array( 
				"interaction" => array( 
					"_all" => array( "enabled" => true ),
					"_source" => array( "enabled" => true ),
					"properties" => array( 
						"interaction_id" => array( "type" => "integer" ),
						"dataset_id" => array( "type" => "integer" ),
						"interaction_type_id" => array( "type" => "integer" ),
						"interaction_state" => array( "type" => "string", "analyzer" => "keyword_analyzer" ),
						"history_status" => array( "type" => "string", "analyzer" => "keyword_analyzer" ),
						"history_user_id" => array( "type" => "integer" ),
						"history_user_name" => array( "type" => "string" ),
						"history_date" => array( "type" => "date", "format" => "yyyy/MM/dd HH:mm:ss" ),
						"attributes" => array( "type" => "nested", "properties" => array(  
							"interaction_attribute_id" => array( "type" => "integer" ),
							"attribute_id" => array( "type" => "integer" ),
							"attribute_parent_id" => array( "type" => "integer" ),
							"attribute_user_id" => array( "type" => "integer" ),
							"attribute_user_name" => array( "type" => "string" ),
							"attribute_addeddate" => array( "type" => "date", "format" => "yyyy/MM/dd HH:mm:ss" ),
							"attribute_value" => array( "type" => "string", "analyzer" => "keyword_analyzer", "fields" => array( 
								"full" => array( "type" => "string" )
							)),
							"attribute_type_id" => array( "type" => "integer" ),
							"attribute_type_name" => array( "type" => "string", "analyzer" => "keyword_analyzer" ),
							"attribute_type_shortcode" => array( "type" => "string", "analyzer" => "keyword_analyzer" ),
							"attribute_type_category_id" => array( "type" => "integer" ),
							"attribute_type_category_name" => array( "type" => "string", "analyzer" => "keyword_analyzer" ),
							"ontology_term_id" => array( "type" => "integer" ),
							"ontology_term_official_id" => array( "type" => "string", "analyzer" => "keyword_analyzer" )
						)),
						"participants" => array( "type" => "nested", "properties" => array( 
							"interaction_participant_id" => array( "type" => "integer" ),
							"participant_id" => array( "type" => "integer" ),
							"participant_role_id" => array( "type" => "integer" ),
							"participant_role_name" => array( "type" => "string", "analyzer" => "keyword_analyzer" ),
							"participant_type_id" => array( "type" => "integer" ),
							"participant_type_name" => array( "type" => "string", "analyzer" => "keyword_analyzer" ),
							"primary_name" => array( "type" => "string", "analyzer" => "keyword_analyzer", "fields" => array( 
								"full" => array( "type" => "string" )
							)),
							"systematic_name" => array( "type" => "string", "analyzer" => "keyword_analyzer", "fields" => array( 
								"full" => array( "type" => "string" )
							)),
							"aliases" => array( "type" => "string", "analyzer" => "keyword_analyzer", "fields" => array( 
								"full" => array( "type" => "string" )
							)),
							"organism_id" => array( "type" => "integer" ),
							"organism_official_name" => array( "type" => "string", "analyzer" => "keyword_analyzer" ),
							"organism_abbreviation" => array( "type" => "string", "analyzer" => "keyword_analyzer" ),
							"organism_strain" => array( "type" => "string", "analyzer" => "keyword_analyzer" ),
							"attributes" => array( "type" => "nested", "properties" => array( 
								"interaction_attribute_id" => array( "type" => "integer" ),
								"attribute_id" => array( "type" => "integer" ),
								"attribute_parent_id" => array( "type" => "integer" ),
								"attribute_user_id" => array( "type" => "integer" ),
								"attribute_user_name" => array( "type" => "string", "analyzer" => "keyword_analyzer" ),
								"attribute_addeddate" => array( "type" => "date", "format" => "yyyy/MM/dd HH:mm:ss" ),
								"attribute_value" => array( "type" => "string", "analyzer" => "keyword_analyzer", "fields" => array( 
									"full" => array( "type" => "string" )
								)),
								"attribute_type_id" => array( "type" => "integer" ),
								"attribute_type_name" => array( "type" => "string", "analyzer" => "keyword_analyzer" ),
								"attribute_type_shortcode" => array( "type" => "string", "analyzer" => "keyword_analyzer" ),
								"attribute_type_category_id" => array( "type" => "integer" ),
								"attribute_type_category_name" => array( "type" => "string", "analyzer" => "keyword_analyzer" ),
								"ontology_term_id" => array( "type" => "integer" ),
								"ontology_term_official_id" => array( "type" => "string", "analyzer" => "keyword_analyzer" )
							))
						))
					)
				)
			)
		);
		
		$response = $this->client->indices( )->putMapping( $params );
		print_r( $response );
		
	}
	
	/**
	 * Submit a search to elastic search and return the response
	 */
	 
	public function search( $params ) {
		return $this->client->search( $params );
	}
	
}

?>