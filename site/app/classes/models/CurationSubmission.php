<?php	


namespace IMS\app\classes\models;

/**
 * Curation Submission
 * This set of functions is for handling the entirety of a
 * submitted dataset for validation and submission into the database
 */

use \PDO;
use IMS\app\lib;
use IMS\app\classes\models;
use IMS\app\classes\utilities;

class CurationSubmission {
	
	private $db;
	private $curationOps;
	private $workflowSettings;
	private $curatedData;
	private $blocks;
	private $annotation;
	private $errors;
	private $hashids;
	private $lookups;
	private $attributeHASH;
	private $participantTypes;
	private $participantRoles;
	private $organismHASH;
	private $interactionTypes;
	private $dbErrors;
	
	private $insertStmts;
	private $curationProgress;
	
	public function __construct( ) {
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$this->curationOps = new models\CurationOperations( );
		$this->hashids = new utilities\Hashes( );
		$this->lookups = new models\Lookups( );
		$this->curationProgress = new models\CurationProgress( );
		
		$this->errors = array( );
		$this->blocks = array( );
		$this->annotation = array( );
		
		$this->attributeHASH = $this->lookups->buildAttributeTypeHASH( );
		$this->participantTypes = $this->lookups->buildParticipantTypesHash( true );
		$this->participantRoles = $this->lookups->buildParticipantRoleHash( );
		$this->organismHASH = $this->lookups->buildOrganismHash( );
		$this->interactionTypes = $this->lookups->buildInteractionTypeHash( );
		
		$this->insertStmts = array( 
			"interaction" => $this->db->prepare( "INSERT INTO " . DB_IMS . ".interactions VALUES( '0', ?, ?, ?, ?, ? )" ),
			"history" => $this->db->prepare( "INSERT INTO " . DB_IMS . ".history VALUES( '0', ?, ?, ?, ?, ?, ? )" ),
			"interaction_participants" => $this->db->prepare( "INSERT INTO " . DB_IMS . ".interaction_participants VALUES( '0', ?, ?, ?, ?, ? )" ),
			"interaction_attributes" => $this->db->prepare( "INSERT INTO " . DB_IMS . ".interaction_attributes VALUES( '0', ?, ?, ?, ?, ?, ? )" )
		);
		
		$this->dbErrors = array( );
		
	}
	
	/**
	 * Validate the dataset as a whole and if it validates
	 * process it for inclusion in the database and elastic search
	 */
	 
	public function processCurationSubmission( $options ) {
		
		$status = "SUCCESS";
		$this->curationProgress->init( $options['curationCode'] );
		
		$this->db->beginTransaction( );
		
		if( isset( $options['validationStatus'] ) ) {
			
			$this->curationProgress->changeProgress( "validateblocks", $options['curationCode'] );
			
			if( $options['validationStatus'] == "false" ) {
				$status = "ERROR";
				$this->errors[] = $this->curationOps->generateError( "INVALID_BLOCKS", array( "invalidBlocks" => json_decode( $_POST['invalidBlocks'] ) ) );
				$this->db->rollBack( );
			} else {
				
				$this->curationProgress->changeProgress( "annotate", $options['curationCode'] );
				
				// All blocks are Valid
				// Fetch curation block details
				$this->workflowSettings = $this->curationOps->fetchCurationWorkflowSettings( $options['curationType'] );
				
				// Fetch curation details from database
				$this->curatedData = $this->fetchCurationSubmissionEntries( $options['curationCode'] );
				
				// Fetch annotation lookups for participants
				foreach( $this->blocks['PARTICIPANT'] as $participantBlock ) {
					$this->annotation[$participantBlock] = $this->curatedData[$participantBlock]->getData( "annotation" );
				}
				
				// Determine the row count if we are doing a row
				// based input by determining the largest participant
				// dataset size
				$rowCount = 1;
				if( $this->workflowSettings['CONFIG']['participant_method'] == "row" ) {
					foreach( $this->curatedData as $curatedData ) {
						if( strtoupper($curatedData->getType( )) == "PARTICIPANT" ) {
							$dataSize = $curatedData->getDataSize( "members" );
							if( $dataSize > $rowCount ) {
								$rowCount = $dataSize;
							}
						}
					}
				}
				
				$this->curationProgress->changeProgress( "validatesubmission", $options['curationCode'] );
				
				// Test curated data against workflow settings to
				// see if data is valid
				if( !$this->validateSubmission( $rowCount ) ) {
					$status = "ERROR";
				} else {  
				
					$this->curationProgress->changeProgress( "build", $options['curationCode'] );
					
					// Process each block of stored data and generate a
					// game plan for processing. Game plan will be based
					// on the values and fields stored
					
					// Get a base interaction representation
					$baseInt = $this->generateInteractionStructure( $options );
					
					// Get a set of all Participant Blocks and their Members
					$participantSets = array( );
					$size = 0;
					foreach( $this->blocks['PARTICIPANT'] as $block ) {
						$participantMembers = $this->curatedData[$block]->getData( "members" );
						
						$setSize = sizeof( $participantMembers['DATA'] );
						if( $setSize > $size ) {
							$size = $setSize;
						}
						
						$participantSets[$block] = $participantMembers['DATA'];
						
					}
					
					// Build Sets of Attributes Applied to EACH Row
					$attributesEach = array( );
					if( isset( $this->blocks['ATTRIBUTE_EACH'] )) {
						foreach( $this->blocks['ATTRIBUTE_EACH'] as $block ) {
							$attributeMembers = $this->curatedData[$block]->getData( "" );
							$attributesEach[] = $attributeMembers;
						}
					}
		
					// Build set of attributes applied to ALL rows
					$attributesAll = array( );
					if( isset( $this->blocks['ATTRIBUTE_ALL'] )) {
						foreach( $this->blocks['ATTRIBUTE_ALL'] as $block ) {
							$attributeMember = $this->curatedData[$block]->getData( "" );
							foreach( $attributeMember["DATA"] as $entryID => $attributeDetails ) {
								$attributesAll[] = $attributeDetails;
							}
						}
					}
					
					$this->curationProgress->changeProgress( "insert", $options['curationCode'] );
					
					// Process Interactions
					if( $this->workflowSettings['CONFIG']['participant_method'] == "row" ) {
						
						// Create Participant Pairings and Process to Database
						$stats = $this->processRows( $participantSets, $attributesEach, $attributesAll, $size, $baseInt );
						if($stats['ERROR'] > 0 ) {
							$status = "ERROR";
							$this->errors[] = $this->curationOps->generateError( "DATABASE_INSERT", array( "dbErrors" => $this->dbErrors ) );
						}
							
					}
					
					//if( $status == "SUCCESS" ) {
					//print "COMMITTING";
					//$this->db->commit( );
					//} else {
						//print "ROLLING BACK!";
						$this->db->rollBack( );
					//}
				
					$this->curationProgress->changeProgress( "complete", $options['curationCode'] );
				
				}
				
				
				
			}
		}
		
		return json_encode( array( "STATUS" => $status, "ERRORS" => $this->curationOps->processErrors( $this->errors ) ) );
		
	}
	
	/**
	 * Generate a list of participant pairings based on the number
	 * of pariticpants provided and the fact that we want row based
	 * pairings
	 */
	 
	private function processRows( $participantSets, $attributesEach, $attributesAll, $participantSize, $baseInt ) {
		
		// Step through each array and take either the matching 
		// rows element or the first element in cases where only
		// one entry is provided
		
		$this->dbErrors = array( );
		$stats = array( "INSERTED" => 0, "DUPLICATE" => 0, "ERROR" => 0 );
		for( $i = 0; $i < $participantSize; $i++ ) {
			
			$interaction = $baseInt;
			
			$participants = array( );
			$participantIDs = array( );
			$participantRoles = array( );
			foreach( $participantSets as $block => $participantSet ) {
				
				$currentParticipant = "";

				if( isset( $participantSet[$i] )) {
					$currentParticipant = $participantSet[$i];
					$participantIDs[] = $participantSet[$i]["participant"];
					$participantRoles[] = $participantSet[$i]["role"];
				} else {
					$currentParticipant = $participantSet[0];
					$participantIDs[] = $participantSet[0]["participant"];
					$participantRoles[] = $participantSet[0]["role"];
				}
				
				$processedParticipant = $this->generateParticipantStructure( $currentParticipant, $block );
				
				// If one participant is unknown it is an erroroneous 
				// interaction that is being added
				if( strtoupper($currentParticipant['status']) == "UNKNOWN" ) {
					$interaction['interaction_state'] = "error";
				}
				
				$participants[] = $processedParticipant;
				
			}
			
			$participantHashID = $this->hashids->generateHash( $participantIDs, $participantRoles );
			
			$interaction["participant_hash"] = $participantHashID;
			$interaction['participants'] = $participants;
			
			$attributeIDs = array( );
			$attributeParentIDs = array( );
			
			// Add EACH Attributes
			foreach( $attributesEach as $attributeSet ) {
				$interaction['attributes'][] = $attributeSet['DATA'][$i];
				$attributeIDs[] = $attributeSet['DATA'][$i]["attribute_id"];
				$attributeParentIDs[] = 0;
				foreach( $attributeSet['DATA'][$i]['attributes'] as $subAttribute ) {
					$attributeIDs[] = $subAttribute["attribute_id"];
					$attributeParentIDs[] = $attributeSet['DATA'][$i]["attribute_id"];
				}
			}
			
			// Add ALL Attributes
			foreach( $attributesAll as $attribute ) {
				$interaction['attributes'][] = $attribute;
				$attributeIDs[] = $attribute["attribute_id"];
				$attributeParentIDs[] = 0;
				foreach( $attribute['attributes'] as $subAttribute ) {
					$attributeIDs[] = $subAttribute["attribute_id"];
					$attributeParentIDs[] = $attribute["attribute_id"];
				}
			}
			
			$attributeHashID = $this->hashids->generateHash( $attributeIDs, $attributeParentIDs );
			$interaction["attribute_hash"] = $attributeHashID;
			
			// Insert into Database
			$insertResults = $this->submitInteraction( $interaction );
			$interaction = $insertResults['INTERACTION'];
			
			if( $insertResults['STATUS'] ) {
				$stats['INSERTED']++;
			} else {
				$stats['ERROR']++;
			}
			
			//print_r( $interaction );
			
		}
		
		return $stats;
		
	}
	
	/**
	 * Process Interaction into the Database
	 */
	 
	private function submitInteraction( $interaction ) {
		
		$currentDate = date( 'Y/m/d H:i:s', strtotime( "now" ));
		$interaction['history_date'] = $currentDate;
	
		$insertComplete = true;
		if( !$interactionID = $this->insertInteractionToDB( $interaction ) ) {
			$insertComplete = false;
		} else {
			
			$interaction['interaction_id'] = $interactionID;
			
			if( !$this->insertHistoryToDB( "ACTIVATED", $interactionID, $interaction['history_user_id'], "New " . $interaction['interaction_type_name'] . " Interaction", "1", $interaction['history_date'] )) {
				$insertComplete = false;
			}
			
			if( !$interaction['participants'] = $this->insertInteractionParticipantsToDB( $interaction['participants'], $interactionID, $currentDate, "active" )) {
				$insertComplete = false;
			}
			
			if( !$interaction['attributes'] = $this->insertInteractionAttributesToDB( $interaction['attributes'], $interactionID, $currentDate, "active" )) {
				$insertComplete = false;
			}
		
		}
		
		return array( "STATUS" => $insertComplete, "INTERACTION" => $interaction );
		
	}
	
	/**
	 * Insert Interaction Participants to the interaction_participants table
	 */
	 
	private function insertInteractionParticipantsToDB( $participants, $interactionID, $date, $status ) {
		
		try {
			
			$participantList = array( );
			foreach( $participants as $participant ) {
				
				$this->insertStmts['interaction_participants']->execute( array( $interactionID, $participant['participant_id'], $participant['participant_role_id'], $date, $status ));
				
				$interactionParticipantID = $this->db->lastInsertId( );
				$participant['interaction_participant_id'] = $interactionParticipantID;
				$participantList[] = $participant;
				
			}
			
			if( sizeof( $participantList ) > 0 ) {
				return $participantList;
			}
			
		} catch( PDOException $e ) {
			$this->dbErrors[] = 'DB ERROR: ' . $e->getMessage( );
		}
		
		return false;
		
	}
	
	/**
	 * Insert Interaction Attributes to the interaction_attributes table
	 */
	 
	private function insertInteractionAttributesToDB( $attributes, $interactionID, $date, $status ) {
		
		try {
			
			$attributeList = array( );
			foreach( $attributes as $attribute ) {
				
				$this->insertStmts['interaction_attributes']->execute( array( $interactionID, $attribute['attribute_id'], "0", $attribute['attribute_user_id'], $date, $status ));
				
				$interactionAttributeID = $this->db->lastInsertId( );
				$attribute['interaction_attribute_id'] = $interactionAttributeID;
				$attribute['attribute_addeddate'] = $date;
				
				// Process Child Attributes by Inserting into the Database
				// and also splitting them out in the elastic search record
				// so they can be indexed correctly for searching
				
				$childAttributes = array( );
				foreach( $attribute['attributes'] as $childAttribute ) {
					
					$this->insertStmts['interaction_attributes']->execute( array( $interactionID, $childAttribute['attribute_id'], $interactionAttributeID, $childAttribute['attribute_user_id'], $date, $status ));
				
					$interactionChildAttributeID = $this->db->lastInsertId( );
					$childAttribute['interaction_attribute_id'] = $interactionChildAttributeID;
					$childAttribute['interaction_attribute_parent_id'] = $interactionAttributeID;
					$childAttribute['attribute_addeddate'] = $date;
					$childAttributes[] = $childAttribute;
				}
				
				// Add newly Annotated Child Attributes in place of
				// originals
				$attribute['attributes'] = $childAttributes;
				
				// Add the Parent Attribute to the master List
				$attributeList[] = $attribute;
				
				// Add Child Attributes as separate attributes
				$attributeList = array_merge( $attributeList, $childAttributes );
				
			}
			
			if( sizeof( $attributeList ) > 0 ) {
				return $attributeList;
			}
			
		} catch( PDOException $e ) {
			$this->dbErrors[] = 'DB ERROR: ' . $e->getMessage( );
		}
		
		return false;
		
	}
	
	/**
	 * Insert an interaction into the database
	 */
	 
	private function insertInteractionToDB( $interaction ) {
		
		try {
			
			$this->insertStmts['interaction']->execute( array( $interaction['participant_hash'], $interaction['attribute_hash'], $interaction['dataset_id'], $interaction['interaction_type_id'], $interaction['interaction_state'] ));
			
			return $this->db->lastInsertId( );
			
		} catch( PDOException $e ) {
			$this->dbErrors[] = 'DB ERROR: ' . $e->getMessage( );
		}
		
		return false;
		
	}
	
	/**
	 * Insert an entry into the History table
	 */
	 
	private function insertHistoryToDB( $modType, $interactionID, $userID, $comment, $operationID, $date ) {
	
		try {
			
			$this->insertStmts['history']->execute( array( $modType, $interactionID, $userID, $comment, $operationID, $date ));
			
			return true;
			
		} catch( PDOException $e ) {
			$this->dbErrors[] = 'DB ERROR: ' . $e->getMessage( );
		}
		
		return false;
	
	}
	
	/**
	 * Create the base details about an interaction
	 */
	 
	private function generateInteractionStructure( $options ) {
		
		$interaction = array( 
			"interaction_id" => 0,
			"dataset_id" => $options['datasetID'],
			"interaction_type_id" => $options['curationType'],
			"interaction_type_name" => $this->interactionTypes[$options['curationType']],
			"interaction_state" => "normal",
			"history_status" => "ACTIVATED",
			"history_user_id" => $_SESSION['IMS_USER']['ID'],
			"history_user_name" => $_SESSION['IMS_USER']['FIRSTNAME'] . " " . $_SESSION['IMS_USER']['LASTNAME'],
			"history_date" => "",
			"attribute_hash" => "",
			"participant_hash" => "",
			"attributes" => array( ),
			"participants" => array( )
		);
		
		return $interaction;
	}
	
	/**
	 * Properly format a participant so it can be inserted into
	 * elastic search
	 */
	 
	private function generateParticipantStructure( $participant, $block ) {
		
		$formattedParticipant = array( );
		$formattedParticipant['interaction_participant_id'] = "0";
		$formattedParticipant['participant_id'] = $participant['participant'];
		$formattedParticipant['participant_role_id'] = $participant['role'];
		$formattedParticipant['participant_role_name'] = $this->participantRoles[$participant['role']];
		$formattedParticipant['participant_type_id'] = $participant['type'];
		$formattedParticipant['participant_type_name'] = $this->participantTypes[$participant['type']];
		
		// ANNOTATION HERE
		
		if( isset( $this->annotation[$block]['DATA'][$participant['id']] )) {
			$participantAnnotation = $this->annotation[$block]['DATA'][$participant['id']];
			$formattedParticipant['primary_name'] = $participantAnnotation['primary_name'];
			$formattedParticipant['systematic_name'] = $participantAnnotation['systematic_name'];
			$formattedParticipant['aliases'] = $participantAnnotation['aliases'];
			$formattedParticipant['organism_id'] = $participantAnnotation['organism_id'];
			$formattedParticipant['organism_official_name'] = $participantAnnotation['organism_official_name'];
			$formattedParticipant['organism_abbreviation'] = $participantAnnotation['organism_abbreviation'];
			$formattedParticipant['organism_strain'] = $participantAnnotation['organism_strain'];
		} else {
			$formattedParticipant['primary_name'] = $participant['identifier'];
			$formattedParticipant['systematic_name'] = "-";
			$formattedParticipant['aliases'] = array( $participant['identifier'] );
			
			$orgInfo = $this->organismHASH[$participant['taxa']];
			$formattedParticipant['organism_id'] = $orgInfo['organism_id'];
			$formattedParticipant['organism_official_name'] = $orgInfo['organism_official_name'];
			$formattedParticipant['organism_abbreviation'] = $orgInfo['organism_abbreviation'];
			$formattedParticipant['organism_strain'] = $orgInfo['organism_strain'];
		}
	
		$formattedParticipant['attributes'] = array( );
		
		return $formattedParticipant;
		
	}
	
	/**
	 * Generate a list of attributes and assign them to the 
	 * participants in the participant list
	 */
	 
	private function matchAttributesToParticipants( $participantList, $attributeSet ) {
		
	}
	
	/** 
	 * Test for validation criteria in workflow settings
	 * to see if data passes the test
	 */
	 
	private function validateSubmission( $rowCount ) {
		
		$isValid = true;
		
		// Check to see if any of the blocks have validation requirements
		foreach( $this->workflowSettings['CHECKLIST'] as $id => $blockSettings ) {
			if( isset( $blockSettings['VALIDATE'] ) ) {
				$validateTarget = "block-" . $blockSettings['VALIDATE']['block'];
				$validationTest = $blockSettings['VALIDATE']['type'];
				if( !$this->runValidationTest( "block-" . $id, $validateTarget, $validationTest ) ) {
					$isValid = false;
				}
			}
		}
		
		// Check to see if the fields that need equal number of entries as 
		// participants have the correct number of entries
		if( $this->workflowSettings['CONFIG']['participant_method'] == "row" ) {
			foreach( $this->curatedData as $curatedData ) {
				if( strtoupper($curatedData->getType( )) == "ATTRIBUTE" ) {
					$attributeInfo = $this->attributeHASH[$curatedData->getTypeID( "" )];
					
					// If we have a quantitative score block
					if( $attributeInfo->attribute_type_category_id == "2" ) {
						$dataSize = $curatedData->getDataSize( "" );
						if( $dataSize != $rowCount ) {
							$this->errors[] = $this->curationOps->generateError( "QUANT_COUNT", array( "quantName" => $curatedData->getName( "" ), "participantSize" => $rowCount, "quantSize" => $dataSize ) );
							$isValid = false;
						}
					}
				}
			}
		}
		
		return $isValid;
		
	}
	
	/**
	 * Test an individual validation query for success
	 * or failure
	 */
	 
	private function runValidationTest( $testBlock, $compareBlock, $validationTest ) {
		
		switch( strtoupper( $validationTest ) ) {
			
			// Test that the $testBlock has either 1 entry only, or the same
			// number of entries as $compareBlock does. Only works on participant
			// style entries. Also check if the other block has 1 entry only.
			
			case "SINGLE_EQUAL" :
			
				$testData = $this->curatedData[$testBlock]->getData( "members", 0 );
				$compareData = $this->curatedData[$compareBlock]->getData( "members", 0 );
			
				$testSize = sizeof( $testData['DATA'] );
				$compareSize = sizeof( $compareData['DATA'] );
			
				if( $testSize == 1 || $compareSize == 1 ) {
					return true;
				} else {
					if( $testSize == $compareSize ) {
						return true;
					}
				}
				
				$this->errors[] = $this->curationOps->generateError( "SINGLE_EQUAL", array( "testBlockName" => $testData['NAME'], "compareBlockName" => $compareData['NAME'], "testBlockSize" => $testSize, "compareBlockSize" => $compareSize ) );
				
				break;
				
		}
		
		return false;
		
	}
	 
	/**
	 * Fetch an existing curation entry out of the database
	 * if it exists, otherwise an empty array
	 */
	 
	private function fetchCurationSubmissionEntries( $code ) {
		
		$stmt = $this->db->prepare( "SELECT * FROM " . DB_IMS . ".curation WHERE curation_code=?" );
		$stmt->execute( array( $code ) );
		
		$results = array( );
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			
			//new models\CurationOperations( );
			if( !isset( $results[$row->curation_block] ) ) {
				$results[$row->curation_block] = new models\CurationData( $row->curation_block );
				$results[$row->curation_block]->setType( $row->curation_type );
			}
			
			$results[$row->curation_block]->addData( $row->curation_subtype, $row->attribute_type_id, $row->curation_data, $row->curation_name, $row->curation_required );
			
			
			$curationType = strtoupper($row->curation_type);
			if( $curationType == "ATTRIBUTE" ) {
				if( $row->curation_status == "WARNING" ) {
					// Skip because Warning Attribute just means
					// that it was empty
					continue;
				} else {

					// Get details about the attribute
					$attributeInfo = $this->attributeHASH[$row->attribute_type_id];
					
					// If it's a type that has a unique value for each participant set
					// like quantitative scores, place it in ATTRIBUTE_EACH
					if( $attributeInfo->attribute_type_category_id == "2" ) {
						$curationType = "ATTRIBUTE_EACH";
					} else {
						// Otherwise, place it in a list where
						// we apply the attribute to all participants
						$curationType = "ATTRIBUTE_ALL";
					}
					
				}
			} 
			
			if( !isset( $this->blocks[$curationType] )) {
				$this->blocks[$curationType] = array( );
			}
			
			$this->blocks[$curationType][] = $row->curation_block;
			
		}
		
		$this->blocks['PARTICIPANT'] = array_unique( $this->blocks['PARTICIPANT'] );
		
		if( isset( $this->blocks['ATTRIBUTE_EACH'] ) ) {
			$this->blocks['ATTRIBUTE_EACH'] = array_unique( $this->blocks['ATTRIBUTE_EACH'] );
		}
		
		if( isset( $this->blocks['ATTRIBUTE_ALL'] ) ) {
			$this->blocks['ATTRIBUTE_ALL'] = array_unique( $this->blocks['ATTRIBUTE_ALL'] );
		}
		
		return $results;
		
	}
	
}