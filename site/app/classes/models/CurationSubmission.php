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
	
	public function __construct( ) {
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$this->curationOps = new models\CurationOperations( );
		$this->hashids = new utilities\Hashes( );
		$this->lookups = new models\Lookups( );
		
		$this->errors = array( );
		$this->blocks = array( );
		$this->annotation = array( );
		
		$this->attributeHASH = $this->lookups->buildAttributeTypeHASH( );
		$this->participantTypes = $this->lookups->buildParticipantTypesHash( true );
		$this->participantRoles = $this->lookups->buildParticipantRoleHash( );
		$this->organismHASH = $this->lookups->buildOrganismHash( );
		
	}
	
	/**
	 * Validate the dataset as a whole and if it validates
	 * process it for inclusion in the database and elastic search
	 */
	 
	public function processCurationSubmission( $options ) {
		
		$status = "SUCCESS";
		
		if( isset( $options['validationStatus'] ) ) {
			if( $options['validationStatus'] == "false" ) {
				$status = "ERROR";
				$this->errors[] = $this->curationOps->generateError( "INVALID_BLOCKS", array( "invalidBlocks" => json_decode( $_POST['invalidBlocks'] ) ) );
			} else {
				
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
				
				//print_r( $this->curatedData );
				
				// Test curated data against workflow settings to
				// see if data is valid
				if( !$this->validateSubmission( $rowCount ) ) {
					$status = "ERROR";
				} else {  
					
					// Process each block of stored data and generate a
					// game plan for processing. Game plan will be based
					// on the values and fields stored
					
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
					
					// Build Sets of Attributes Applied to Each Row
					$attributesEach = array( );
					if( isset( $this->blocks['ATTRIBUTE_EACH'] )) {
						foreach( $this->blocks['ATTRIBUTE_EACH'] as $block ) {
							$attributeMembers = $this->curatedData[$block]->getData( "" );
							$attributesEach[] = $attributeMembers;
						}
					}
		
					// Build set of attributes applied to all rows
					$attributesAll = array( );
					if( isset( $this->blocks['ATTRIBUTE_ALL'] )) {
						foreach( $this->blocks['ATTRIBUTE_ALL'] as $block ) {
							$attributeMember = $this->curatedData[$block]->getData( "" );
							foreach( $attributeMember["DATA"] as $entryID => $attributeDetails ) {
								$attributesAll[] = $attributeDetails;
							}
						}
					}
					
					// Process Interactions
					if( $this->workflowSettings['CONFIG']['participant_method'] == "row" ) {
						
						// Create Participant Pairings
						$participantList = $this->processRows( $participantSets, $attributesEach, $attributesAll, $size );
							
					}
					
					//print_r( $participantList );
					
					
					// Check for Duplicates
					
					// Insert into the Database
					
					
					
					// Insert into Elastic Search
					
					
					
					// $participantBlocks = array( );
					// Build Interactions
					// foreach( $curatedData as $blockID => $blockTypes ) {
						// foreach( $blockTypes as $blockType => $blockSubType ) {
							// if( $blockType == "participant" ) {
								// $participantBlocks[] = $blockID;
							// }
						// }
					// }
					
					// $participantBlocks = array_unique( $participantBlocks );
					
					$status = "SUCCESS";
					
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
	 
	private function processRows( $participantSets, $attributesEach, $attributesAll, $participantSize ) {
		
		// Step through each array and take either the matching 
		// rows element or the first element in cases where only
		// one entry is provided
		
		print_r( $participantSets );
		
		$participantList = array( );
		for( $i = 0; $i < $participantSize; $i++ ) {
			
			$currentParticipants = array( );
			foreach( $participantSets as $block => $participantSet ) {
				if( isset( $participantSet[$i] )) {
					$currentParticipants[] = $this->processParticipant( $participantSet[$i], $block );
				} else {
					$currentParticipants[] = $this->processParticipant( $participantSet[0], $block );
				}
			}
			
			print_r( $currentParticipants );
			
			// Add EACH Attributes
			
			
			// Add ALL Attributes
			
			// Add All Attributes
			//$participantList[] = array( "PARTICIPANTS" => $currentPair, "HASH" => $this->hashids->generateHash( $participantIDs, $roleIDs ) );
			
		}
		
		return $participantList;
		
	}
	
	/**
	 * Properly format a participant so it can be inserted into
	 * elastic search
	 */
	 
	private function processParticipant( $participant, $block ) {
		
		$formattedParticipant = array( );
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