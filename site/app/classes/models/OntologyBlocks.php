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
	
	private $POPULAR_LIMIT = 60;
	 
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
			$stmt = $this->db->prepare( "SELECT ontology_term_id, ontology_term_official_id, ontology_term_name, ontology_term_childcount FROM " . DB_IMS . ".ontology_terms WHERE ontology_term_status='active' AND ontology_id=? AND ontology_term_count != '0' ORDER BY ontology_term_count DESC LIMIT " . $this->POPULAR_LIMIT );
			$stmt->execute( array( $ontologyID ) );
		
			while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
				$terms[strtolower($row->ontology_term_name)] = $row;
			}
			
			$view = "";
			if( sizeof( $terms ) > 0 ) {
				ksort( $terms );
				$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyTerms.tpl', array( "TERMS" => $terms, "COUNT" => sizeof( $terms ), "TYPE" => "Popular" ), false );
			} else {
				$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyError.tpl', array( "MESSAGE" => "Currently no terms have been used from this ontology previously..." ), false );
			}
			
			return array( "VIEW" => $view, "COUNT" => sizeof( $terms ) );
	
		}
		
	}
	
	/**
	 * Return an array of terms that are found using
	 * a search within an ontology or group of ontologies being searched
	 */
	 
	public function fetchSearchOntologyTerms( $ontologyID, $searchTerm ) {
		
		$terms = array( );
		
		// Need to generate ontology id part of the query here 
		// in case ontology ID refers to a group instead of a 
		// single ontology
		
		if( isset( $this->ontologies[$ontologyID] ) ) {
			
			$stmt = $this->db->prepare( "SELECT ontology_term_id, ontology_term_official_id, ontology_term_name, ontology_term_childcount FROM " . DB_IMS . ".ontology_term_search WHERE (MATCH (ontology_term_name) AGAINST (? IN BOOLEAN MODE) OR ontology_term_official_id=?) AND ontology_id = ?" );
			$stmt->execute( array( $searchTerm, $searchTerm, $ontologyID ) );
			
			while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
				$terms[strtolower($row->ontology_term_name)] = $row;
			}
			
			$view = "";
			if( sizeof( $terms ) > 0 ) {
				$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyTerms.tpl', array( "TERMS" => $terms, "COUNT" => sizeof( $terms ), "TYPE" => "Matching Searched" ), false );
			} else {
				$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyError.tpl', array( "MESSAGE" => "Your search query returned no results. Are you sure you selected the correct ontology to search via the dropdown list?" ), false );
			}
			
			return array( "VIEW" => $view, "COUNT" => sizeof( $terms ) );
			
		}
		
	}
	
}