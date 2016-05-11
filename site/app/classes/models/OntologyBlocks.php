<?php


namespace IMS\app\classes\models;

/**
 * Ontology Blocks
 * This set of blocks is for handling output of various layout blocks
 * to build a curation interface via AJAX requests.
 */
 
use IMS\app\lib;
use IMS\app\classes\models;

class OntologyBlocks extends lib\Blocks {
	
	private $db;
	private $ontologies;
	private $lookups;
	 
	public function __construct( ) {
		parent::__construct( );
		
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$this->lookups = new models\Lookups( );
		$this->ontologies = $this->lookups->buildOntologyIDHash( true );
		
	}
	
	public function fetchPopularOntologyTerms( $ontologyID ) {
	
		$terms = array( );
	
		if( isset( $this->ontologies[$ontologyID] ) ) {
			
		}
	
	}
	
}