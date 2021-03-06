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
	private $defaultQualifierOntologies;
	
	private $POPULAR_LIMIT = 60;
	 
	public function __construct( ) {
		parent::__construct( );
		
		global $siteOps;
		
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$this->lookups = new models\Lookups( );
		$this->ontologies = $this->lookups->buildOntologyIDHash( true );
		$this->defaultQualifierOntologies = $siteOps["DEFAULT_QUALIFIERS"];
		
	}
	
	/**
	 * Return an array of terms that are commonly used
	 * within an ontology or group of ontologies being searched
	 */
	 
	public function fetchPopularOntologyTerms( $ontologyID, $allowQualifiers, $allowTerms ) {
	
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
				$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyTerms.tpl', array( "TERMS" => $terms, "COUNT" => sizeof( $terms ), "TYPE" => "Popular", "ALLOW_EXPAND" => false, "SHOW_HEADING" => true, "ALLOW_TREE" => true, "NOTFULL" => false, "ALLOW_QUALIFIERS" => $allowQualifiers, "ALLOW_TERMS" => $allowTerms ), false );
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
	 
	public function fetchSearchOntologyTerms( $ontologyID, $searchTerm, $allowQualifiers, $allowTerms ) {
		
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
				$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyTerms.tpl', array( "TERMS" => $terms, "COUNT" => sizeof( $terms ), "TYPE" => "Matching Searched", "ALLOW_EXPAND" => false, "SHOW_HEADING" => true, "ALLOW_TREE" => true, "NOTFULL" => false, "ALLOW_QUALIFIERS" => $allowQualifiers, "ALLOW_TERMS" => $allowTerms ), false );
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
	 
	public function fetchTreeOntologyTerms( $ontologyID, $allowQualifiers, $allowTerms ) {
		
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
				$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyTerms.tpl', array( "TERMS" => $terms, "COUNT" => sizeof( $terms ), "TYPE" => "Popular", "ALLOW_EXPAND" => true, "SHOW_HEADING" => false, "ALLOW_TREE" => false, "NOTFULL" => false, "ALLOW_QUALIFIERS" => $allowQualifiers, "ALLOW_TERMS" => $allowTerms ), false );
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
	 
	public function fetchChildOntologyTerms( $ontologyTermID, $allowQualifiers, $allowTerms ) {
		
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
			$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyTerms.tpl', array( "TERMS" => $terms, "COUNT" => sizeof( $terms ), "TYPE" => "Popular", "ALLOW_EXPAND" => true, "SHOW_HEADING" => false, "ALLOW_TREE" => false, "NOTFULL" => false, "ALLOW_QUALIFIERS" => $allowQualifiers, "ALLOW_TERMS" => $allowTerms ), false );
		} else {
			$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyError.tpl', array( "MESSAGE" => "No Children Available for this Term" ), false );
		}
		
		return array( "VIEW" => $view );
		
	}
	
	/**
	 * Load a single ontology term, where the fields are passed in
	 * to build it out
	 */
	 
	public function fetchSingleOntologyTerm( $ontologyTermID, $ontologyTermName, $ontologyTermChildCount, $showHeading = false, $allowTree = false, $allowExpand = false,$notFull = false, $expanded = false, $highlightTerm = false, $allowQualifiers, $allowTerms, $children = "" ) {
		
		$terms = array(array( "ontology_term_id" => $ontologyTermID, "ontology_term_name" => $ontologyTermName, "ontology_term_childcount" => $ontologyTermChildCount ));
		$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_OntologyTerms.tpl', array( "TERMS" => $terms, "COUNT" => sizeof( $terms ), "TYPE" => "Popular", "ALLOW_EXPAND" => $allowExpand, "SHOW_HEADING" => $showHeading, "ALLOW_TREE" => $allowTree, "ONTOLOGY_EXPAND" => $children, "EXPANDED" => $expanded, "NOTFULL" => $notFull, "HIGHLIGHT" => $highlightTerm, "ALLOW_QUALIFIERS" => $allowQualifiers, "ALLOW_TERMS" => $allowTerms ), false );
		
		return $view;
		
	}
	
	/**
	 * Generate a tree view for browsing ontology terms based on the 
	 * ontology ID being viewed and it's lineage up through multiple
	 * parents.
	 */
	 
	public function fetchLineageOntologyTerms( $ontologyTermID, $allowQualifiers, $allowTerms ) {
		
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
			
			$view = $this->fetchSingleOntologyTerm( $lastBranch['ID'], $lastBranch['NAME'], $lastBranch['COUNT'], false, false, true, true, false, false, $allowQualifiers, $allowTerms, implode( "", $completedPaths ) );
			
		}
		
		return array( "VIEW" => $view );
		
	}
	
	/**
	 * Create a formatted selected term id to display in the selected terms column
	 * of the ontology selector interface
	 */
	 
	public function fetchFormattedSelectedTerm( $termID, $termName, $termOfficialID, $selectedTerms, $attributeTypeID ) {
	
		$selectedTerms = array_flip( explode( "|", $selectedTerms ) );
		
		if( isset( $selectedTerms[$termID] ) ) {
			return array( "VIEW" => "", "VALUE" => $termID, "SWITCH" => 0 );
		}
		
		$termReq = $this->fetchSelectedTermRequirements( $termID, $attributeTypeID );
	
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
	 
	private function fetchSelectedTermRequirements( $termID, $attributeTypeID ) {
			
		// Biochemical Activity Experimental System
		// Requires a selection from Post Translational Modifications Ontology
		// as a qualifier
		if( $termID == "194590" && $attributeTypeID == "11" ) {	 
			return array( "MESSAGE" => "This term requires a post translational modification qualifier", "SWITCH" => "21|0|1" );
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
	
	/**
	 * Get option parameters for ontology forms based on passed in details
	 */
	 
	public function fetchOntologyOptions( $attributeTypeID, $organismList = array( ) ) {
		
		if( sizeof( $organismList ) <= 0 ) {
			$organismList[] = "-";
		}
		
		$stmt = $this->db->prepare( "SELECT ontology_id, attribute_type_ontology_option, attribute_type_ontology_selected, attribute_type_ontology_organism FROM " . DB_IMS . ".attribute_type_ontologies WHERE attribute_type_ontology_status='active'AND attribute_type_id=?" );
		$stmt->execute( array( $attributeTypeID ) );
	
		$terms = array( );
		$qualifiers = array( );
		$hasDefaultQualifiers = false;
		$ontologyIDs = array( );
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			
			if( $row->ontology_id != "0" ) {
			
				$ontologyName = $this->ontologies[$row->ontology_id];
			
				$ontologyOption = $this->fetchOntologyOption( $row->ontology_id, $row->attribute_type_ontology_option );
				if( $row->attribute_type_ontology_option == "qualifier" ) {
					$qualifiers[$ontologyName] = $ontologyOption;
				} else {
					$terms[$ontologyName] = $ontologyOption;
					$ontologyIDs[] = $row->ontology_id;
				}
			
				// Check all the options for Selected Ontology for the one
				// that matches the list of organism options in the organismList
				$selectedOntology = 1;
				$organisms = explode( "|", $row->attribute_type_ontology_organism );
				if( $row->attribute_type_ontology_selected == 1 ) {
					$intersect = array_intersect( $organismList, $organisms );
					if( sizeof( $intersect ) > 0 ) {
						$selectedOntology = $row->ontology_id;
					}
				}
				
			} else {
				$hasDefaultQualifiers = true;
			}
		}
		
		if( $hasDefaultQualifiers ) {
			$ontologies = $this->fetchDefaultQualifierOntologies( $ontologyIDs );
			
			foreach( $ontologies as $ontologyID ) {
				$ontologyName = $this->ontologies[$ontologyID];
			
				$ontologyOption = $this->fetchOntologyOption( $ontologyID, "qualifier" );
				$qualifiers[$ontologyName] = $ontologyOption;
			}
		
		}
		
		ksort( $terms );
		ksort( $qualifiers );
		
		$ontologyAttributes = $this->fetchOntologyAttributes( $attributeTypeID );
		
		return array( "SINGLE_SELECT" => $ontologyAttributes["SINGLE_SELECT"], "SELECTED_ONTOLOGY" => $selectedOntology, "TERMS" => $terms, "QUALIFIERS" => $qualifiers, "SINGLE_QUAL" => $ontologyAttributes["SINGLE_QUAL"], "ALLOW_QUAL" => $ontologyAttributes["ALLOW_QUAL"] );
		
	}
	
	/**
	 * Return a list of default ontologies with already used ones removed
	 */
	 
	private function fetchDefaultQualifierOntologies( $ontologyIDs ) {
		
		$ontologies = array_diff( $this->defaultQualifierOntologies, $ontologyIDs );
		return $ontologies;
		
	}
	
	/**
	 * Create the correct dropdown list entry for an ontology based on the passed in 
	 * parameters
	 */
	 
	private function fetchOntologyOption( $ontologyID, $ontologyOption ) {
		
		// Ontologies have the ID format ontology_id|allow_terms|allow_qualifiers
		if( $ontologyOption == "both" ) {
			return $ontologyID . "|1|1";
		} else if( $ontologyOption == "term" ) {
			return $ontologyID . "|1|0";
		} 
		
		return $ontologyID . "|0|1";
		
	}
	
	/**
	 * Return whether or not an ontology allows multiple terms to be selected or
	 * only a single term that is overridden, whether it allows for qualifiers, and
	 * whether or not it allows for qualifiers on non-specific terms
	 */
	 
	private function fetchOntologyAttributes( $attributeTypeID ) {
		
		// Make some ontologies only single selectable
		// where a pick of a second term overrides the first one
		
		// Default Values
		$singleSelect = 0;
		$singleQual = 0;
		$allowQual = 1;
		
		switch( $attributeTypeID ) {
				
			case "11" :
				$singleSelect = 1;
				$singleQual = 1;
				$allowQual = 0;
				break; 
				
			case "12" :
				$singleSelect = 1;
				$singleQual = 0;
				$allowQual = 0;
				break;
			
			case "13" :
				$singleSelect = 1;
				$singleQual = 0;
				$allowQual = 0;
				break;
			
			case "32" :
				$singleSelect = 1;
				$singleQual = 1;
				$allowQual = 0;
				break;
				
		}
		
		return array( "SINGLE_SELECT" => $singleSelect, "SINGLE_QUAL" => $singleQual, "ALLOW_QUAL" => $allowQual );
	}
	
}