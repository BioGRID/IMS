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
	 
	public function fetchPopularOntologyTerms( $ontologyID, $allowQualifiers ) {
	
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
				$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyTerms.tpl', array( "TERMS" => $terms, "COUNT" => sizeof( $terms ), "TYPE" => "Popular", "ALLOW_EXPAND" => false, "SHOW_HEADING" => true, "ALLOW_TREE" => true, "NOTFULL" => false, "ALLOW_QUALIFIERS" => $allowQualifiers ), false );
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
	 
	public function fetchSearchOntologyTerms( $ontologyID, $searchTerm, $allowQualifiers ) {
		
		$terms = array( );
		
		// Need to generate ontology id part of the query here 
		// in case ontology ID refers to a group instead of a 
		// single ontology
		
		if( isset( $this->ontologies[$ontologyID] ) ) {
			
			$stmt = $this->db->prepare( "SELECT ontology_term_id, ontology_term_official_id, ontology_term_name, ontology_term_childcount FROM " . DB_IMS . ".ontology_term_search WHERE (MATCH (ontology_term_name, ontology_term_synonyms) AGAINST (? IN BOOLEAN MODE) OR ontology_term_official_id=?) AND ontology_id = ?" );
			$stmt->execute( array( $searchTerm, $searchTerm, $ontologyID ) );
			
			while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
				$terms[strtolower($row->ontology_term_name)] = $row;
			}
			
			$view = "";
			if( sizeof( $terms ) > 0 ) {
				$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyTerms.tpl', array( "TERMS" => $terms, "COUNT" => sizeof( $terms ), "TYPE" => "Matching Searched", "ALLOW_EXPAND" => false, "SHOW_HEADING" => true, "ALLOW_TREE" => true, "NOTFULL" => false, "ALLOW_QUALIFIERS" => $allowQualifiers ), false );
			} else {
				$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyError.tpl', array( "MESSAGE" => "Your search query returned no results. Are you sure you selected the correct ontology to search via the dropdown list?" ), false );
			}
			
			return array( "VIEW" => $view, "COUNT" => sizeof( $terms ) );
			
		}
		
	}
	
	/**
	 * Return a set of annotation for an ontology term id
	 */
	
	private function fetchOntologyTermAnnotation( $ontologyTermID, $incRelations = false ) {
		
		$stmt = $this->db->prepare( "SELECT ontology_term_official_id, ontology_term_name, ontology_term_desc, ontology_term_synonyms FROM " . DB_IMS . ".ontology_terms WHERE ontology_term_id=? LIMIT 1" );
		$stmt->execute( array( $ontologyTermID ) );
		
		$row = $stmt->fetch( PDO::FETCH_ASSOC );
		
		if( $incRelations ) {
			$row['ontology_relations'] = $this->fetchOntologyTermRelationships( $ontologyTermID );
		}
		
		return $row;
		
	}
	
	/**
	 * Return a set of relationships for an ontology term
	 */
	 
	private function fetchOntologyTermRelationships( $ontologyTermID ) {
		
		$stmt = $this->db->prepare( "SELECT o.ontology_parent_id, o.ontology_relationship_type, p.ontology_term_official_id, p.ontology_term_name  FROM " . DB_IMS . ".ontology_relationships o LEFT JOIN " . DB_IMS . ".ontology_terms p ON (o.ontology_parent_id=p.ontology_term_id) WHERE o.ontology_term_id=?" );
		$stmt->execute( array( $ontologyTermID ) );
		
		$relations = array( );
		while( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ) {
			$relations[] = $row;
		}
		
		return $relations;
		
	}
	
	/**
	 * Return a formatted set of annotation information for an
	 * ontology term including all the relationships
	 */
	 
	public function fetchOntologyTermDetails( $ontologyTermID ) {
		
		$annotation = $this->fetchOntologyTermAnnotation( $ontologyTermID, true );
		
		if( $annotation['ontology_term_synonyms'] != "-" ) {
			$synonyms = explode( "|", $annotation['ontology_term_synonyms'] );
			if( sizeof( $synonyms ) > 10 ) {
				$synonyms = array_slice( $synonyms, 0, 10 );
				$annotation['ontology_term_synonyms'] = implode( "|", $synonyms );
			}
		}
		
		$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Ontology_TermPopup.tpl', $annotation, false );
		
		return array( "VIEW" => $view );
	
	}
	
	/**
	 * Generate a tree view for browsing ontology terms based on the 
	 * ontology ID being viewed
	 */
	 
	public function fetchTreeOntologyTerms( $ontologyID, $allowQualifiers ) {
		
		$terms = array( );
		
		// Need to generate ontology id part of the query here 
		// in case ontology ID refers to a group instead of a 
		// single ontology
	
		if( isset( $this->ontologies[$ontologyID] ) ) {
			
			$stmt = $this->db->prepare( "SELECT ontology_term_id, ontology_term_official_id, ontology_term_name, ontology_term_childcount FROM " . DB_IMS . ".ontology_terms WHERE ontology_term_status='active' AND ontology_id=? AND ontology_term_isroot = '1'" );
			$stmt->execute( array( $ontologyID ) );
		
			while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
				$terms[strtolower($row->ontology_term_name)] = $row;
			}
			
			$view = "";
			if( sizeof( $terms ) > 0 ) {
				ksort( $terms );
				$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyTerms.tpl', array( "TERMS" => $terms, "COUNT" => sizeof( $terms ), "TYPE" => "Popular", "ALLOW_EXPAND" => true, "SHOW_HEADING" => false, "ALLOW_TREE" => false, "NOTFULL" => false, "ALLOW_QUALIFIERS" => $allowQualifiers ), false );
			} else {
				$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyError.tpl', array( "MESSAGE" => "Currently no terms exist for this ontology" ), false );
			}
			
			return array( "VIEW" => $view );
	
		}
		
	}
	
	/**
	 * Load in a single ontology term
	 *
	 */
	 
	public function fetchChildOntologyTerms( $ontologyTermID, $allowQualifiers ) {
		
		$terms = array( );
			
		$stmt = $this->db->prepare( "SELECT o.ontology_term_id, p.ontology_term_official_id, p.ontology_term_name, p.ontology_term_childcount, p.ontology_term_status FROM " . DB_IMS . ".ontology_relationships o LEFT JOIN ontology_terms p ON (o.ontology_term_id=p.ontology_term_id) WHERE ontology_relationship_status='active' AND ontology_parent_id=? AND ontology_relationship_type='is_a'" );
		$stmt->execute( array( $ontologyTermID ) );
	
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			if( $row->ontology_term_status == "active" ) {
				$terms[strtolower($row->ontology_term_name)] = $row;
			}
		}
		
		$view = "";
		if( sizeof( $terms ) > 0 ) {
			ksort( $terms );
			$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyTerms.tpl', array( "TERMS" => $terms, "COUNT" => sizeof( $terms ), "TYPE" => "Popular", "ALLOW_EXPAND" => true, "SHOW_HEADING" => false, "ALLOW_TREE" => false, "NOTFULL" => false, "ALLOW_QUALIFIERS" => $allowQualifiers ), false );
		} else {
			$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyError.tpl', array( "MESSAGE" => "No Children Available for this Term" ), false );
		}
		
		return array( "VIEW" => $view );
		
	}
	
	/**
	 * Load a single ontology term, where the fields are passed in
	 * to build it out
	 */
	 
	public function fetchSingleOntologyTerm( $ontologyTermID, $ontologyTermName, $ontologyTermChildCount, $showHeading = false, $allowTree = false, $allowExpand = false,$notFull = false, $expanded = false, $highlightTerm = false, $allowQualifiers, $children = "" ) {
		
		$terms = array(array( "ontology_term_id" => $ontologyTermID, "ontology_term_name" => $ontologyTermName, "ontology_term_childcount" => $ontologyTermChildCount ));
		$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyTerms.tpl', array( "TERMS" => $terms, "COUNT" => sizeof( $terms ), "TYPE" => "Popular", "ALLOW_EXPAND" => $allowExpand, "SHOW_HEADING" => $showHeading, "ALLOW_TREE" => $allowTree, "ONTOLOGY_EXPAND" => $children, "EXPANDED" => $expanded, "NOTFULL" => $notFull, "HIGHLIGHT" => $highlightTerm, "ALLOW_QUALIFIERS" => $allowQualifiers ), false );
		
		return $view;
		
	}
	
	/**
	 * Generate a tree view for browsing ontology terms based on the 
	 * ontology ID being viewed and it's lineage up through multiple
	 * parents.
	 */
	 
	public function fetchLineageOntologyTerms( $ontologyTermID, $allowQualifiers ) {
		
		$terms = array( );
		$stmt = $this->db->prepare( "SELECT ontology_term_path FROM " . DB_IMS . ".ontology_terms WHERE ontology_term_id=? LIMIT 1" );
		$stmt->execute( array( $ontologyTermID ) );
		
		$row = $stmt->fetch( PDO::FETCH_OBJ );
		$view = "";
		if( $row ) {
			$pathSet = json_decode( $row->ontology_term_path, true );
			$completedPaths = array( );
			$lastBranch = "";
			foreach( $pathSet as $path ) {
				$branchCount = 0;
				$lastBranch = array_pop( $path );
				foreach( $path as $branch ) {
					if( $branchCount == 0 ) {
						if( $branch['COUNT'] != 0 ) {
							$children = $this->fetchChildOntologyTerms( $branch['ID'] );
							$view = $children["VIEW"];
						} else {
							$view = "";
						}

						$view = $this->fetchSingleOntologyTerm( $branch['ID'], $branch['NAME'], $branch['COUNT'], false, false, true, false, true, true, $allowQualifiers, $view );
					} else {
						$view = $this->fetchSingleOntologyTerm( $branch['ID'], $branch['NAME'], $branch['COUNT'], false, false, true, true, false, false, $allowQualifiers, $view );
					}
					
					$branchCount++;
				}
				
				$completedPaths[] = $view;
			}
			
			$view = $this->fetchSingleOntologyTerm( $lastBranch['ID'], $lastBranch['NAME'], $lastBranch['COUNT'], false, false, true, true, false, false, $allowQualifiers, implode( "", $completedPaths ) );
			
		}
		
		return array( "VIEW" => $view );
		
	}
	
	/**
	 * Create a formatted selected term id to display in the selected terms column
	 * of the ontology selector interface
	 */
	 
	public function fetchFormattedSelectedTerm( $termID, $termName, $termOfficialID, $selectedTerms ) {
	
		$selectedTerms = array_flip( explode( "|", $selectedTerms ) );
		
		if( isset( $selectedTerms[$termID] ) ) {
			return array( "VIEW" => "" );
		}
		
		$termReq = $this->fetchSelectedTermRequirements( $termID );
	
		$params = array(
			"TERM_ID" => $termID,
			"TERM_NAME" => $termName,
			"TERM_OFFICIAL" => $termOfficialID
		);
		
		$switchID = 0;
		if( $termReq ) {
			$params["QUALIFIER_MSG"] = $termReq["MESSAGE"];
			$switchID = $termReq["SWITCH"];
		}
		
		$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Ontology_TermSelected.tpl', $params, false );
		return array( "VIEW" => $view, "VALUE" => $termID, "SWITCH" => $switchID );
	
	}
	
	/**
	 * Check for a term id, and assign it a specific output based on whether or not
	 * it is a term with further qualification style rules.
	 */
	 
	private function fetchSelectedTermRequirements( $termID ) {
		
		switch( $termID ) {
			
			// Biochemical Activity Experimental System
			// Requires a selection from Post Translational Modifications Ontology
			// as a qualifier
			case "194590" : 
				return array( "MESSAGE" => "This term requires a post translational modification qualifier", "SWITCH" => 21 );
			
		}
		
		return false;
		
	}
	
	/**
	 * Create a formatted qualifier term id to display in the selected terms column
	 * of the ontology selector interface
	 */
	 
	public function fetchFormattedQualifierTerm( $termID, $termName, $termOfficialID ) {
	
	
		$params = array(
			"TERM_ID" => $termID,
			"TERM_NAME" => $termName,
			"TERM_OFFICIAL" => $termOfficialID
		);
		
		$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Ontology_TermQualifier.tpl', $params, false );
		return array( "VIEW" => $view, "VALUE" => $termID );
	
	}
	
}