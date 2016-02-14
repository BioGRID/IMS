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
	private $historyOperationsHash;
	private $participantTypeHash;
	private $participantRoleHASH;
	private $annotationHASH;
	private $participantTypeMappingHASH;
	private $attributeTypeHASH;
	private $ontologyTermHASH;
	
	private $hosts;
	private $clientBuilder;
	private $client;

	/**
	 * Establish a database connection and also build several quick
	 * lookup hashes so we can speed up the process.
	 */
	
	public function __construct( ) {
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$this->lookups = new IMS\app\classes\models\Lookups( );
		$this->interactionTypesHash = $this->lookups->buildInteractionTypeHash( );
		$this->userNameHash = $this->lookups->buildUserNameHash( );
		// $this->historyOperationsHash = $this->lookups->buildHistoryOperationsHash( );
		// $this->participantTypeHash = $this->lookups->buildParticipantTypesHash( );
		// $this->participantRoleHASH = $this->lookups->buildParticipantRoleHash( );
		// $this->annotationHASH = $this->lookups->buildAnnotationHash( );
		// $this->participantTypeMappingHASH = $this->lookups->buildParticipantTypeMappingHash( );
		// $this->attributeTypeHASH = $this->lookups->buildAttributeTypeHASH( );
		// $this->ontologyTermHASH = $this->lookups->buildAttributeOntologyTermHASH( );
		
		$this->hosts = array( ES_HOST . ":" . ES_PORT );
		$this->clientBuilder = Elasticsearch\ClientBuilder::create( );
		$this->clientBuilder->setHosts( $this->hosts );
		$this->client = $this->clientBuilder->build( );
	}
	
	/**
	 * Build the matrix by fetching interactions by dataset id
	 */
	 
	public function buildMatrixByDataset( $datasetID ) {
		
		$stmt = $this->db->prepare( "SELECT * FROM " . DB_IMS . ".interactions WHERE dataset_id=?" );
		$stmt->execute( array( $datasetID ) );
		
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			// Build Document
			$document = $this->buildMatrixDocument( $row );
			// Insert it into Elastic Search
		}
		
	}
	
	/**
	 *  Build the matrix by fetching interactions by interaction_id
	 */
	 
	public function buildMatrixByInteraction( $interactionID ) {
		
		$stmt = $this->db->prepare( "SELECT * FROM " . DB_IMS . ".interactions WHERE interaction_id=?" );
		$stmt->execute( array( $interactionID ) );
		
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			// Build Document
			$document = $this->buildMatrixDocument( $row );
			print_r( $document );
			// Insert it into Elastic Search
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
		
		$document = $document + $this->fetchInteractionHistoryDetails( $interaction->interaction_id );
		
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
			$historyDetails['history_date'] = $row->history_addeddate;
		}
		
		return $historyDetails;
		
	}
	
}

?>