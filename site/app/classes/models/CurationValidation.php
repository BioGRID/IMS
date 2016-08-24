<?php	


namespace IMS\app\classes\models;

/**
 * Curation Validation
 * This set of functions is for handling input from various curation blocks
 * and submission to the database of validated data.
 */

use \PDO;
use IMS\app\lib;
use IMS\app\classes\models;

class CurationValidation {
	
	private $db;
	private $blockName;
	private $lookups;
	private $attributeTypeInfo;
	
	private $curationOps;
	
	public function __construct( $blockName ) {
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$loader = new \Twig_Loader_Filesystem( TEMPLATE_PATH );
		$this->twig = new \Twig_Environment( $loader );
		
		$this->blockName = $blockName;
		$this->lookups = new models\Lookups( );
		$this->attributeTypeInfo = $this->lookups->buildAttributeTypeHASH( );
		
		$this->curationOps = new models\CurationOperations( );

	}
	
	/**
	 * Take an attribute and attribute category and validate it accordingly
	 */
	 
	public function validateAttribute( $options, $block, $curationCode, $isRequired = false ) {
	
		switch( $options['category'] ) {
			
			case "1" : // Ontology Terms
				$ontologyTerms = array( );
				if( isset( $options['ontologyTerms'] ) ) {
					$ontologyTerms = $options['ontologyTerms'];
				}
				
				$results = $this->validateOntologyTerms( $ontologyTerms, $options['attribute'], $block, $curationCode, $isRequired );
				return $results;
			
			case "3" : // NOTE
				$results = $this->validateInteractionNotes( $options['data'], $block, $curationCode, $isRequired );
				return $results;
				
			case "2" : // Quantitative Score
				$results = $this->validateQuantitiativeScores( $options['data'], $options['attribute'], $block, $curationCode, $isRequired );
				return $results;
			
		}
	
	}
	
	/**
	 * Get ontology term details by ontology id
	 */
	 
	private function fetchOntologyTermDetails( $ontologyTermID ) {
		
		$stmt = $this->db->prepare( "SELECT ontology_term_id, ontology_term_official_id, ontology_term_name FROM " . DB_IMS . ".ontology_terms WHERE ontology_term_id=?  LIMIT 1" );
		
		$stmt->execute( array( $ontologyTermID ) );
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			return $row;
		} 
		
		return false;
		
	}
	

	
	/** 
	 * Get an ontology term annotated with relevant details
	 * to prepare it for easy insertion into the database
	 */
	 
	private function processOntologyTerm( $termID, $termOfficialID, $termName, $attributeTypeID ) {
		
		$ontologyTerm = array( );
		
		$ontologyTerm["interaction_attribute_id"] = "0";
		$ontologyTerm["interaction_attribute_parent_id"] = "0";
		
		// Check to see if the term is an attribute, if not, add it
		$attributeID = $this->curationOps->processAttribute( $termID, $attributeTypeID );
		$ontologyTerm["attribute_id"] = $attributeID;
		$ontologyTerm["attribute_value"] = $termName;
		$ontologyTerm["attribute_type_id"] = $attributeTypeID;
	
		// Get details about attribute type from attributeTypeInfo lookup
		$attributeTypeInfo = $this->attributeTypeInfo[$attributeTypeID];
		$ontologyTerm["attribute_type_name"] = $attributeTypeInfo->attribute_type_name;
		$ontologyTerm["attribute_type_shortcode"] = $attributeTypeInfo->attribute_type_shortcode;
		$ontologyTerm["attribute_type_category_id"] = $attributeTypeInfo->attribute_type_category_id;
		$ontologyTerm["attribute_type_category_name"] = $attributeTypeInfo->attribute_type_category_name;
		
		// Add User Info
		$ontologyTerm["attribute_user_id"] = $_SESSION['IMS_USER']['ID'];
		$ontologyTerm["attribute_user_name"] = $_SESSION['IMS_USER']['FIRSTNAME'] . " " . $_SESSION['IMS_USER']['LASTNAME'];
		$ontologyTerm["attribute_addeddate"] = date( 'Y/m/d H:i:s', strtotime( "now" ));
		
		$ontologyTerm["ontology_term_id"] = $termID;
		$ontologyTerm["ontology_term_official_id"] = $termOfficialID;
		
		$ontologyTerm["attributes"] = array( );
		
		return $ontologyTerm;
		
	}
	
	/** 
	 * Test a term ID and attribute Type ID combo to see if there are 
	 * any special circumstances in which extra scrutiny must be applied
	 */
	 
	private function validateTermsWithQualifierRequirements( $termID, $termName, $qualifiers, $attributeTypeID ) {
			
		// Biochemical Activity Experimental System
		// Requires a single selection from Post Translational Modifications Ontology
		// as a qualifier
		
		if( $termID == "194590" && $attributeTypeID == "11" ) {	
			if( sizeof( $qualifiers ) <= 0 || sizeof( $qualifiers ) > 1 ) {
				return array( "STATUS" => false, "MESSAGE" => $this->curationOps->generateError( "BIOCHEMICAL_ACTIVITY_NO_QUALIFIER", array( "term" => $termName ) ) );
			} else {
				
				// Verify that the qualifier is from the PTM Ontology
				$stmt = $this->db->prepare( "SELECT ontology_term_id FROM " . DB_IMS . ".ontology_terms WHERE ontology_term_id=? AND ontology_id='21' LIMIT 1" );
				$stmt->execute( array( $qualifiers[0] ) );
				
				if( !$row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
					return array( "STATUS" => false, "MESSAGE" => $this->curationOps->generateError( "BIOCHEMICAL_ACTIVITY_WRONG_QUALIFIER", array( "term" => $termName ) ) );
				} 
		
			}
		}
		
		return array( "STATUS" => true );
		
	}
	
	/**
	 * Take a set of ontology terms and ensure they are valid
	 * and that they are not missing any required info
	 */
	 
	private function validateOntologyTerms( $ontologyTerms, $attributeTypeID, $block, $curationCode, $isRequired = false ) {
		
		$messages = array( );
		$status = "VALID";
		$ontologyTermSet = array( );
		
		if( sizeof( $ontologyTerms ) <= 0 ) {
			
			if( $isRequired ) {
				$messages[] = $this->curationOps->generateError( "REQUIRED", array( "blockName" => $this->blockName ) );
				$status = "ERROR";
			} else {
				$messages[] = $this->curationOps->generateError( "BLANK", array( "blockName" => $this->blockName ) );
				$status = "WARNING";
			}
			
		} else {
		
			$ontologyTerms = json_decode( $ontologyTerms, true );
			
			foreach( $ontologyTerms as $termID => $qualifiers ) {
				
				// Convert IDs to Actual Terms
				$termDetails = $this->fetchOntologyTermDetails( $termID );
				if( !$termDetails ) {
					$status = "ERROR";
					$messages[] = $this->curationOps->generateError( "INVALID_ONTOLOGY_TERM" );
				} else {
					
					// Check validity of selected terms based on which attribute ID is being validated
					// Example: Biochemical Activity must have qualifier
					$validateTerms = $this->validateTermsWithQualifierRequirements( $termID, $termDetails->ontology_term_name, $qualifiers, $attributeTypeID );
					if( !$validateTerms["STATUS"] ) {
						
						$status = "ERROR";
						$messages[] = $validateTerms["MESSAGE"];
						
					} else {
						
						// Process ontology Term
						$ontologyTerm = $this->processOntologyTerm( $termDetails->ontology_term_id, $termDetails->ontology_term_official_id, $termDetails->ontology_term_name, $attributeTypeID );
					
						// Process through the list of qualifiers
						foreach( $qualifiers as $qualifier ) {
							$qualDetails = $this->fetchOntologyTermDetails( $qualifier );
							$qualifierTerm = $this->processOntologyTerm( $qualDetails->ontology_term_id, $qualDetails->ontology_term_official_id, $qualDetails->ontology_term_name, "31");
							
							$ontologyTerm["attributes"][] = $qualifierTerm;
						}
					
						// Add completed terms and their qualifiers to the ontologyTermSet
						$ontologyTermSet[$termID] = $ontologyTerm;
						
					}
					
				}
				
			}	
			
		}
		
		// INSERT/UPDATE IT IN THE DATABASE
		$this->updateCurationEntries( $curationCode, $status, $block, $ontologyTermSet, "attribute", "-", $attributeTypeID, $isRequired );
		
		return array( "STATUS" => $status, "ERRORS" => $messages );
		
	}
	
	/**
	 * Take a set of quantitative scores and ensure they are numerical
	 * values with a simple validation process
	 */
	 
	private function validateQuantitiativeScores( $scores, $attributeTypeID, $block, $curationCode, $isRequired = false ) {
		
		$messages = array( );
		$scoreSet = array( );
		
		$status = "VALID";
		
		$scores = trim( $scores );
		if( strlen( $scores ) <= 0 && $isRequired ) {
			$messages[] = $this->curationOps->generateError( "REQUIRED", array( "blockName" => $this->blockName ));
		} else if( strlen( $scores ) <= 0 ) {
			// DO NOTHING, CAUSE IT"S EMPTY AND NOT REQUIRED
		} else {
		
			$scoreList = explode( PHP_EOL, $scores );
			$lineCount = 1;
			
			foreach( $scoreList as $score ) {
				$score = trim( str_replace( array( ",", " " ), "", $score ));
				
				if( !is_numeric( $score ) && $score != "-" ) {
					$messages[] = $this->curationOps->generateError( "NON_NUMERIC", array( "score" => $score, "lines" => array( $lineCount ) ) );
				}
					
				$scoreSet[] = $this->processScore( $score, $attributeTypeID );
				$lineCount++;

			}
			
			$status = "VALID";
			if( sizeof( $messages ) > 0 ) {
				$status = "ERROR";
			} else {
				if( $isRequired ) {
					if( sizeof( $scoreSet ) <= 0 ) {
						array_unshift( $messages, $this->curationOps->generateError( "REQUIRED", array( "blockName" => $this->blockName ) ) );
						$status = "ERROR";
					}
				} else {
					if( sizeof( $scoreSet ) <= 0 ) {
						$messages[] = $this->curationOps->generateError( "BLANK", array( "blockName" => $this->blockName ) );
						$status = "WARNING";
					} 
				}
			}
			
		}
	
		// INSERT/UPDATE IT IN THE DATABASE
		$this->updateCurationEntries( $curationCode, $status, $block, $scoreSet, "attribute", "-", $attributeTypeID, $isRequired );
		
		return array( "STATUS" => $status, "ERRORS" => $messages );
		
	}
	
	/**
	 * Process a quantitiative score 
	 */
	 
	private function processScore( $score, $attributeTypeID ) {
		
		$formattedScore = array( );
		$formattedScore["interaction_attribute_id"] = "0";
		$formattedScore["interaction_attribute_parent_id"] = "0";
		
		// Add basic score info
		$formattedScore["attribute_id"] = 0;
		$formattedScore["attribute_value"] = $score;
		$formattedScore["attribute_type_id"] = $attributeTypeID;
	
		// Get details about attribute type from attributeTypeInfo lookup
		$attributeTypeInfo = $this->attributeTypeInfo[$attributeTypeID];
		$formattedScore["attribute_type_name"] = $attributeTypeInfo->attribute_type_name;
		$formattedScore["attribute_type_shortcode"] = $attributeTypeInfo->attribute_type_shortcode;
		$formattedScore["attribute_type_category_id"] = $attributeTypeInfo->attribute_type_category_id;
		$formattedScore["attribute_type_category_name"] = $attributeTypeInfo->attribute_type_category_name;
		
		// Add User Info
		$formattedScore["attribute_user_id"] = $_SESSION['IMS_USER']['ID'];
		$formattedScore["attribute_user_name"] = $_SESSION['IMS_USER']['FIRSTNAME'] . " " . $_SESSION['IMS_USER']['LASTNAME'];
		$formattedScore["attribute_addeddate"] = date( 'Y/m/d H:i:s', strtotime( "now" ));
		
		$formattedScore["ontology_term_id"] = "0";
		$formattedScore["ontology_term_official_id"] = "0";
		
		$formattedScore["attributes"] = array( );
		
		return $formattedScore;
		
	}
	
	/**
	 * Take a set of notes and sanitize and validate them
	 */
	 
	private function validateInteractionNotes( $notes, $block, $curationCode, $isRequired = false ) {
		
		$messages = array( );
		$noteSet = array( );
		
		$notesList = explode( PHP_EOL, trim($notes) );
		foreach( $notesList as $note ) {
			$note = $this->cleanText( $note );
			if( strlen( $note ) > 0 ) {
				$attributeID = $this->curationOps->processAttribute( $note, "22" );
				$noteSet[] = $this->processNote( $note ); 
			}
		}
		
		$status = "VALID";
		if( $isRequired ) {
			if( sizeof( $noteSet ) <= 0 ) {
				$messages[] = $this->curationOps->generateError( "REQUIRED", array( "blockName" => $this->blockName ) );
				$status = "ERROR";
			}
		} else {
			if( sizeof( $noteSet ) <= 0 ) {
				$messages[] = $this->curationOps->generateError( "BLANK", array( "blockName" => $this->blockName ) );
				$status = "WARNING";
			}
		}
		
		// INSERT/UPDATE IT IN THE DATABASE
		$this->updateCurationEntries( $curationCode, $status, $block, $noteSet, "attribute", "-", "22", $isRequired );
		
		return array( "STATUS" => $status, "ERRORS" => $messages );
		
	}
	
	/**
	 * Process each note into a formatted record
	 */
	 
	private function processNote( $note ) {
		
		$formattedNote = array( );
		$formattedNote["interaction_attribute_id"] = "0";
		$formattedNote["interaction_attribute_parent_id"] = "0";
	
		// Check to see if the note is an attribute, if not add it
		$attributeID = $this->curationOps->processAttribute( $note, "22" );
		$formattedNote["attribute_id"] = $attributeID;
		$formattedNote["attribute_value"] = $note;
		$formattedNote["attribute_type_id"] = "22";
	
		// Get details about attribute type from attributeTypeInfo lookup
		$attributeTypeInfo = $this->attributeTypeInfo["22"];
		$formattedNote["attribute_type_name"] = $attributeTypeInfo->attribute_type_name;
		$formattedNote["attribute_type_shortcode"] = $attributeTypeInfo->attribute_type_shortcode;
		$formattedNote["attribute_type_category_id"] = $attributeTypeInfo->attribute_type_category_id;
		$formattedNote["attribute_type_category_name"] = $attributeTypeInfo->attribute_type_category_name;
		
		// Add User Info
		$formattedNote["attribute_user_id"] = $_SESSION['IMS_USER']['ID'];
		$formattedNote["attribute_user_name"] = $_SESSION['IMS_USER']['FIRSTNAME'] . " " . $_SESSION['IMS_USER']['LASTNAME'];
		$formattedNote["attribute_addeddate"] = date( 'Y/m/d H:i:s', strtotime( "now" ));
		
		$formattedNote["ontology_term_id"] = "0";
		$formattedNote["ontology_term_official_id"] = "0";
		
		$formattedNote["attributes"] = array( );
		
		return $formattedNote;
		
	}
	
	/**
	 * Take a set of alleles and validate them compared to a set of identifiers
	 */
	 
	public function validateAlleles( $alleles, $participantCount, &$results, $block, $curationCode ) {
		
		$messages = array( );
		$alleleSet = array( );
		
		foreach( $alleles as $alleleBoxNumber => $alleleList ) {
			$alleleList = trim( $alleleList );
			$alleleList = explode( PHP_EOL, $alleleList );
			
			if( sizeof( $alleleList ) != $participantCount ) {
				$messages[] = $this->curationOps->generateError( "ALLELE_MISMATCH", array( "alleleBoxNumber" => $alleleBoxNumber ) );
			}
			
			// Remove Left Over NewLines
			$alleleSet[] = array_map( 'trim', $alleleList );
			
		}
		
		if( sizeof( $messages ) > 0 ) {
			$results["STATUS"] = "ERROR";
			$results["ERRORS"] = array_merge( $results['ERRORS'], $messages );
		} 
		
		// INSERT/UPDATE IT IN THE DATABASE
		$this->updateCurationEntries( $curationCode, $results["STATUS"], $block, $alleleSet, "participant", "attribute", "36", false );
		
		return $results;
		
	}
	
	/**
	 * Take a string of passed in identifiers and various search parameters, and
	 * attempt to map each one to a database identifier based on the string text
	 */
	
	public function validateIdentifiers( $identifiers, $role, $type, $taxa, $idType, $block, $curationCode, $isRequired = false ) {
		
		$messages = array( );
		$identifiers = trim( $identifiers );
		
		// Get already stored mapping info
		// to help save time on lookups
		
		$mapping = array( );
		$annotationSet = $this->fetchCurationEntry( $curationCode, $block, "participant", "annotation" );
		$termMap = $this->fetchCurationEntry( $curationCode, $block, "participant", "terms" );
		
		$counts = array( "VALID" => 0, "AMBIGUOUS" => 0, "UNKNOWN" => 0, "TOTAL" => 0 );
		
		if( $isRequired && strlen( $identifiers ) <= 0 ) {
			$status = "ERROR";
			$messages[] = $this->curationOps->generateError( "REQUIRED", array( "blockName" => $this->blockName ) );
		} else if( !$isRequired && strlen( $identifiers ) <= 0 ) {
			$status = "VALID";
		} else {
			
			$identifiers = explode( PHP_EOL, $identifiers );
			$uniqueIdentifiers = array_unique( $identifiers );
			
			$toAnnotate = array( );
			foreach( $uniqueIdentifiers as $identifier ) {
				
				$identifier = strtoupper( $this->cleanText( $identifier ) );
				$splitIdentifier = explode( "|", $identifier );
				
				if( sizeof( $splitIdentifier ) > 1 ) {
					$identifier = $splitIdentifier[1];
				}
				
				$index = $identifier . "|" . $taxa;
				
				if( !isset( $termMap[$index] )) {
					$matchList = $this->fetchMatchingIdentifiers( $identifier, $type, $taxa, $idType );
					$termMap[$index] = $matchList;
					
					foreach( $matchList as $matchID => $matchInfo ) {
						if( !isset( $annotationSet[$matchID] ) ) {
							$annotationSet[$matchID] = array( );
							$toAnnotate[] = $matchID;
						}
					}
					
				}
				
				// If we passed in a BioGRID ID alternative, we need to make sure it's in the list of options
				// for that identifier in the termMap and also make sure we have annotation for it in the
				// annotation set.
				
				if( sizeof( $splitIdentifier ) > 1 ) {
					if( !isset( $termMap[$index][$splitIdentifier[0]] ) ) {
						$termMap[$index][$splitIdentifier[0]] = $splitIdentifier[0];
						if( !isset( $annotationSet[$splitIdentifier[0]] ) ) {
							$annotationSet[$splitIdentifier[0]] = array( );
							$toAnnotate[] = $splitIdentifier[0];
						}
					}
				}
				
			}
			
			$toAnnotate = array_chunk( $toAnnotate, 1000 );
			foreach( $toAnnotate as $idChunk ) {
				$this->fetchMatchingAnnotation( $idChunk, $type, $annotationSet );
			}
			
			$unknownParticipantHASH = array( );
			
			$lineCount = 1;
			$errorList = array( );
			$warningList = array( );
			foreach( $identifiers as $identifier ) {
				$identifier = strtoupper( $this->cleanText( $identifier ) );
				
				// If we have an identifier with a | in it
				// then it's a BIOGRID ID | STRING ID type of
				// identifier
				
				$splitIdentifier = explode( "|", $identifier );
				if( sizeof( $splitIdentifier ) > 1 ) {
					$identifier = $splitIdentifier[1];
				} 
				
				$index = $identifier . "|" . $taxa;
				$termIDs = $termMap[$index];
				
				// If we specified a specific GENE ID to use, then here
				// we convert the annotation from a pack of ids to a 
				// specific set of annotation
				
				if( sizeof( $splitIdentifier ) > 1 && isset( $termIDs[$splitIdentifier[0]] ) ) {
					$termIDs = array( $splitIdentifier[0] );
				}
				
				if( sizeof( $termIDs ) <= 0 ) {
					
					// UNKNOWN
					if( !isset( $warningList[$identifier] ) ) {
						$warningList[$identifier] = array( );
					}
					
					$hashIndex = strtoupper($identifier . "|" . $type . "|" . $taxa);
					if( !isset( $unknownParticipantHASH[$hashIndex] )) {
						$unknownParticipantHASH[$hashIndex] = $this->processUnknownParticipant( $identifier, $type, $taxa );
					}
					
					$participantID = $unknownParticipantHASH[$hashIndex];
					
					$warningList[$identifier][] = $lineCount;
					$mapping[] = array( "id" => "", "key" => $identifier . "|" . $taxa, "identifier" => $identifier, "status" => "UNKNOWN", "role" => $role, "type" => "5", "participant" => $participantID, "taxa" => $taxa );
					$counts["UNKNOWN"]++;
					
				} else if( sizeof( $termIDs ) > 1 ) {
					
					// AMBIGUOUS
					if( !isset( $errorList[$identifier] ) ) {
						$errorList[$identifier] = array( );
					}
					
					$errorList[$identifier][] = $lineCount;
					$mapping[] = array( "id" => "", "key" => $identifier . "|" . $taxa, "identifier" => $identifier, "status" => "AMBIGUOUS", "role" => $role, "type" => $type, "taxa" => $taxa );
					$counts["AMBIGUOUS"]++;
					
				} else {
					// VALID MAPPING
					$termID = current( $termIDs );
					$termAnn = $annotationSet[$termID];
					$mapping[] = array( "id" => $termID, "key" => $identifier . "|" . $taxa, "identifier" => $identifier, "status" => "VALID", "role" => $role, "type" => $type, "participant" => $termAnn['participant_id'], "taxa" => $taxa );
					$counts["VALID"]++;
				}
				
				$counts["TOTAL"]++;
				$lineCount++;
			}
			
			foreach( $errorList as $identifier => $lines ) {
				$index = $identifier . "|" . $taxa;
				$termSet = $termMap[$index];
				$options = array( );
				foreach( $termSet as $termID => $termDetails ) {
					$options[$termID] = $annotationSet[$termID];
				}
				$messages[] = $this->curationOps->generateError( "AMBIGUOUS", array( "identifier" => $identifier, "lines" => $lines, "options" => $options ) );
			}
			
			foreach( $warningList as $identifier => $lines ) {
				$messages[] = $this->curationOps->generateError( "UNKNOWN", array( "identifier" => $identifier, "lines" => $lines ) );
			}
			
			if( sizeof( $errorList ) > 0 ) {
				$status = "ERROR";
			} else if( sizeof( $warningList ) > 0 ) {
				$status = "WARNING";
			} else {
				$status = "VALID";
			}
			
		}
		
		// Update Curation Database Entries
		$this->updateCurationEntries( $curationCode, $status, $block, $mapping, "participant", "members", "0", $isRequired );
		$this->updateCurationEntries( $curationCode, "NEW", $block, $annotationSet, "participant", "annotation", "0", $isRequired );
		$this->updateCurationEntries( $curationCode, "NEW", $block, $termMap, "participant", "terms", "0", $isRequired );
		
		return array( "STATUS" => $status, "ERRORS" => $messages, "COUNTS" => $counts );
		
	}
	
	/**
	 * Add entries to the curation table based on passed in parameters
	 * and the format of the table
	 */
	 
	private function updateCurationEntries( $code, $status, $block, $data, $type, $subType, $attributeTypeID, $isRequired ) {
		
		$required = 0;
		if( $isRequired ) {
			$required = 1;
		}
		
		$stmt = $this->db->prepare( "SELECT curation_id FROM " . DB_IMS . ".curation WHERE curation_code=? AND curation_block=? AND curation_type=? AND curation_subtype=? AND attribute_type_id=? LIMIT 1" );
		
		$stmt->execute( array( $code, $block, $type, $subType, $attributeTypeID ) );
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			// PERFORM UPDATE INSTEAD OF INSERT
			$stmt = $this->db->prepare( "UPDATE " . DB_IMS . ".curation SET curation_status=?, curation_data=? WHERE curation_id=?" );
			$stmt->execute( array( $status, json_encode( $data ), $row->curation_id ) );
		} else {
			// PERFORM INSERT
			$stmt = $this->db->prepare( "INSERT INTO " . DB_IMS . ".curation VALUES ( '0',?,?,?,?,?,?,?,?,?,NOW( ) )" );
			$stmt->execute( array( $code, $status, $block, json_encode( $data ), $type, $subType, $attributeTypeID, $this->blockName, $required ) );
		}
		
	}
	
	/**
	 * Fetch an existing curation entry out of the database
	 * if it exists, otherwise an empty array
	 */
	 
	private function fetchCurationEntry( $code, $block, $type, $subType ) {
		
		$stmt = $this->db->prepare( "SELECT curation_data FROM " . DB_IMS . ".curation WHERE curation_code=? AND curation_block=? AND curation_type=? AND curation_subtype=? LIMIT 1" );
		
		$stmt->execute( array( $code, $block, $type, $subType ) );
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			return json_decode( $row->curation_data, true );
		} 
		
		return array( );
		
	}
	
	/**
	 * Fetch matching identifier passed in based on the
	 * additional parameters also passed
	 */
	 
	private function fetchMatchingIdentifiers( $identifier, $type, $taxa, $idType ) {
		
		switch( $type ) {
			
			case "1" : // Gene
				$idTypeQuery = $this->fetchIDTypeQuery( $idType );
				
				$stmt = $this->db->prepare( "SELECT gene_id FROM " . DB_QUICK . ".quick_identifiers WHERE quick_identifier_value=? AND organism_id=? " . $idTypeQuery . " GROUP BY gene_id" );
				
				$stmt->execute( array( $identifier, $taxa ) );
				
				$geneIDs = array( );
				while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
					$geneIDs[$row->gene_id] = $row->gene_id;
				}
				
				if( sizeof( $geneIDs ) <= 0 ) {
					return array( );
				}
				
				return $geneIDs;//$this->fetchMatchingAnnotation( $geneIDs, $type );
			
		}
		
	}
	
	/**
	 * Fetch matching annotation by the values and type that are passed in
	 */
	 
	private function fetchMatchingAnnotation( $values, $type, &$annotationSet ) {
		
		$matchingAnnotation = array( );
		$participantHASH = $this->fetchParticipantIDHash( $values, $type );
		
		switch( $type ) {
			
			case "1" : // Gene
			
				$params = implode( ",", array_fill( 0, sizeof( $values ), "?" ));
				$stmt = $this->db->prepare( "SELECT gene_id, systematic_name, official_symbol, aliases, organism_id, organism_official_name, organism_abbreviation, organism_strain FROM " . DB_QUICK . ".quick_annotation WHERE gene_id IN ( " . $params . " )" );
				$stmt->execute( $values );

				while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
					
					$annotation = array( );
					$annotation['gene_id'] = $row->gene_id;
					
					if( $row->aliases != "-" ) {
						$annotation['aliases'] = explode( "|", $row->aliases );
					}
					
					$annotation['primary_name'] = $row->official_symbol;
					$annotation['aliases'][] = $annotation['primary_name'];
					$annotation['systematic_name'] = $row->systematic_name;
					
					if( $row->systematic_name != "-" ) {
						$annotation['systematic_name'] = $row->systematic_name;
						$annotation['aliases'][] = $row->systematic_name;
					}
					
					$annotation['organism_id'] = $row->organism_id;
					$annotation['organism_official_name'] = $row->organism_official_name;
					$annotation['organism_abbreviation'] = $row->organism_abbreviation;
					
					if( $row->organism_strain != "-" ) {
						$annotation['organism_abbreviation'] .= " (" . $row->organism_strain . ")";	
					}
					
					$annotation['organism_strain'] = $row->organism_strain;
					
					// Get a participant ID out of the database
					// and store it for quicker processing later on
					if( isset( $participantHASH[$row->gene_id] )) {
						$annotation['participant_id'] = $participantHASH[$row->gene_id];
					} else {
						$participantID = $this->fetchParticipantID( $row->gene_id, $type );
						$annotation['participant_id'] = $participantID;
					}
					
					$annotationSet[$annotation['gene_id']] = $annotation;
				}
				
				break;
			
		}
		
	}
	
	/**
	 * Fetch a hash table of existing participants so we can quickly
	 * determine which ones require addition
	 */
	 
	private function fetchParticipantIDHash( $values, $type ) {
		
		$params = implode( ",", array_fill( 0, sizeof( $values ), "?" ));
		$stmt = $this->db->prepare( "SELECT participant_id, participant_value FROM " . DB_IMS . ".participants WHERE participant_value IN ( " . $params . " ) AND participant_type_id=? AND participant_status='active'" );
		$values[] = $type;
		$stmt->execute( $values );
		
		$mappingHASH = array( );
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$mappingHASH[$row->participant_value] = $row->participant_id;
		}
		
		return $mappingHASH;
		
	}
	
	/**
	 * Determine if a participant exists for this combination of value and type
	 * and if not, add it. Return the id of the participant.
	 */
	 
	private function fetchParticipantID( $value, $type ) {
		
		$stmt = $this->db->prepare( "SELECT participant_id FROM " . DB_IMS . ".participants WHERE participant_value=? AND participant_type_id=? AND participant_status='active' LIMIT 1" );
		$stmt->execute( array( $value, $type ) );
		
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			return $row->participant_id;
		}
		
		$stmt = $this->db->prepare( "INSERT INTO " . DB_IMS . ".participants VALUES ( '0',?,?,NOW( ),'active' )" );
		$stmt->execute( array( $value, $type ) );
		
		return $this->db->lastInsertId( );
		
	}
	
	/**
	 * Fetch the participant ID of an unknown participant
	 */
	 
	private function processUnknownParticipant( $value, $type, $orgID ) {
		
		$unknownParticipantID = $this->fetchUnknownParticipantID( $value, $type, $orgID );
		$participantID = $this->fetchParticipantID( $unknownParticipantID, "5" ); // 5 is Unknown Participant
		return $participantID;
		
	}
	
	/**
	 * Determine if an  unknown_participant exists for this combination of value, organism, and type
	 * and if not, add it. Return the id of the unknown_participant.
	 */
	 
	private function fetchUnknownParticipantID( $value, $type, $orgID ) {
		
		$stmt = $this->db->prepare( "SELECT unknown_participant_id FROM " . DB_IMS . ".unknown_participants WHERE unknown_participant_value=? AND participant_type_id=? AND unknown_participant_status='active' AND organism_id=? LIMIT 1" );
		$stmt->execute( array( $value, $type, $orgID ) );
		
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			return $row->unknown_participant_id;
		}
		
		$stmt = $this->db->prepare( "INSERT INTO " . DB_IMS . ".unknown_participants VALUES ( '0',?,?,?,'0',NOW( ),'active' )" );
		$stmt->execute( array( $value, $type, $orgID ) );
		
		return $this->db->lastInsertId( );
		
	}
	
	/**
	 * Fetch the id type specific portion of the lookup query
	 * based on the idType passed in
	 */
	 
	private function fetchIDTypeQuery( $idType ) {
		
		switch( strtoupper( $idType ) ) {
			
			case "ALL": return "";
			case "NAMES": return " AND quick_identifier_type IN ( 'SYSTEMATIC NAME', 'ORDERED LOCUS', 'OFFICIAL SYMBOL', 'SYNONYM' )";
			case "OFFICIAL":  return " AND quick_identifier_type IN ( 'OFFICIAL SYMBOL' )";
			case "SYNONYM": return " AND quick_identifier_type IN ( 'SYSTEMATIC NAME', 'SYNONYM', 'ORDERED LOCUS' )";
			case "ENSEMBL": return " AND quick_identifier_type IN ( 'ENSEMBL RNA', 'ENSEMBL PROTEIN', 'ENSEMBL GENE', 'ENSEMBL' )";
			case "UNIPROTKB": return " AND quick_identifier_type IN ( 'SWISS-PROT', 'UNIPROT', 'TREMBL', 'UNIPROT-ACCESSION' )";
			case "REFSEQ": return " AND quick_identifier_type IN ( 'REFSEQ-PROTEIN-ACCESSION', 'REFSEQ-PROTEIN-GI', 'REFSEQ-PROTEIN-ACCESSION-VERSIONED' )";
			case "WORMBASE": return " AND quick_identifier_type IN ( 'WORMBASE','WORMBASE-OLD' )";
			default: return " AND quick_identifier_type IN ( '" . strtoupper( $idType ) . "' )";
			
		}
		
	}
	
	/**
	 * Step through array row by row, and replace specific lines
	 * with a newly formatted entry
	 */
	 
	public function replaceParticipantLines( $participants, $lines, $value ) {
		
		$participants = explode( PHP_EOL, $participants );
		$lines = explode( "|", $lines );
		
		foreach( $lines as $line ) {
			$participant = $participants[$line-1];
			$participant = explode( "|", trim($participant) );
			
			$participantText = $participant[0];
			if( sizeof( $participant ) > 1 ) {
				$participantText = $participant[1];
			}
			
			$replaceVal = $value;
			if( strtoupper( substr( $replaceVal, 0, 3 ) ) === "BG_" ) {
				$replaceVal = substr( $replaceVal, 3 ) . "|" . $participantText;
			} 
			
			$participants[$line-1] = $replaceVal;
			
		}
		
		return implode( PHP_EOL, $participants );
	}
	
	/**
	 * Clean text to remove non-ascii characters
	 * and return the rest unmodified
	 */
	 
	public function cleanText( $str ) {
		
		//$str = preg_replace( '/[^(\x20-\x7F)]*/', '', $str );
		$str = filter_var( $str, FILTER_DEFAULT, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW );
		$str = trim( strip_tags( $str ) );
		return $str;
		
	}
	
}