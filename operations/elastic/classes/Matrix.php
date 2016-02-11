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
		$this->historyOperationsHash = $this->lookups->buildHistoryOperationsHash( );
		$this->participantTypeHash = $this->lookups->buildParticipantTypesHash( );
		$this->participantRoleHASH = $this->lookups->buildParticipantRoleHash( );
		$this->annotationHASH = $this->lookups->buildAnnotationHash( );
		$this->participantTypeMappingHASH = $this->lookups->buildParticipantTypeMappingHash( );
		$this->attributeTypeHASH = $this->lookups->buildAttributeTypeHASH( );
		$this->ontologyTermHASH = $this->lookups->buildAttributeOntologyTermHASH( );
		
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
			// Build Record
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
			// Build Record
			// Insert it into Elastic Search
		}
		
	}
	
}

?>