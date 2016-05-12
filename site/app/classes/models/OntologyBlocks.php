<?php


namespace IMS\app\classes\models;

/**
 * Ontology Blocks
 * This set of blocks is for handling output of various layout blocks
 * to build a curation interface via AJAX requests.
 */
 
use IMS\app\lib;
use IMS\app\classes\models;
use \PDO;

class OntologyBlocks extends lib\Blocks {
	
	private $db;
	private $ontologies;
	private $lookups;
	
	private $POPULAR_LIMIT = 20;
	 
	public function __construct( ) {
		parent::__construct( );
		
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$this->lookups = new models\Lookups( );
		$this->ontologies = $this->lookups->buildOntologyIDHash( true );
		
	}
	
	/**
	 * Return an array of terms that are commonly used
	 * within an ontology or group of ontologies being searched
	 */
	 
	public function fetchPopularOntologyTerms( $ontologyID ) {
	
		$terms = array( );
		
		// Need to generate ontology id part of the query here 
		// in case ontology ID refers to a group instead of a 
		// single ontology
	
		if( isset( $this->ontologies[$ontologyID] ) ) {
			$stmt = $this->db->prepare( "SELECT ontology_term_id, ontology_term_official_id, ontology_term_name FROM " . DB_IMS . ".ontology_terms WHERE ontology_term_status='active' AND ontology_id=? LIMIT " . $this->POPULAR_LIMIT );
			$stmt->execute( array( $ontologyID ) );
		
			while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
				$terms[strtolower($row->ontology_term_name)] = $row;
			}
			
			$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyPopular.tpl', array( "TERMS" => $terms ), false );
			
			return $view;
	
		}
		
	}
	
}