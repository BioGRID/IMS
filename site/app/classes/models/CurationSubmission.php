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
	private $errors;
	private $hashids;
	private $lookups;
	private $attributeHASH;
	
	public function __construct( ) {
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$this->curationOps = new models\CurationOperations( );
		$this->errors = array( );
		
		$this->blocks = array( );
		$this->hashids = new utilities\Hashes( );
		
		$this->lookups = new models\Lookups( );
		$this->attributeHASH = $this->lookups->buildAttributeTypeHASH( );
		
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
				
				//print_r( $this->curatedData );
				
				// Test curated data against workflow settings to
				// see if data is valid
				if( !$this->validateSubmission( ) ) {
					$status = "ERROR";
				} else {  
					
					// Process each block of stored data and generate a
					// game plan for processing. Game plan will be based
					// on the values and fields stored
					
					// Build Participants
					if( $this->workflowSettings['CONFIG']['participant_method'] == "row" ) {
						
						// Create Participant Pairings
						$participantList = $this->generateParticipantRowPairings( );
							
					}
					
					
					
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
	 
	private function generateParticipantRowPairings( ) {
		
		// Fetch all the sets of participant members
		$participantSets = array( );
		$size = 0;
		foreach( $this->blocks['PARTICIPANT'] as $block ) {
			$participantMembers = $this->curatedData[$block]->getData( "members" );
			
			$setSize = sizeof( $participantMembers['DATA'] );
			if( $setSize > $size ) {
				$size = $setSize;
			}
			
			$participantSets[] = $participantMembers['DATA'];
			
		}
		
		// Fetch all the set of ATTRIBUTE_EACH
		$attributesEach = array( );
		if( isset( $this->blocks['ATTRIBUTE_EACH'] )) {
			foreach( $this->blocks['ATTRIBUTE_EACH'] as $block ) {
				$attributeMembers = $this->curatedData[$block]->getData( "" );
				$attributesEach[] = $attributeMembers['DATA'];
			}
		}
		
		// Build set of participants from ATTRIBUTE_ALL
		$attributesAll = array( );
		if( isset( $this->blocks['ATTRIBUTE_ALL'] )) {
			foreach( $this->blocks['ATTRIBUTE_ALL'] as $block ) {
				$attributeMember = $this->curatedData[$block]->getData( "" );
				foreach( $attributeMember["DATA"] as $entryID => $attributeDetails ) {
					$attributesAll[] = $attributeDetails;
				}
			}
		}
		
		// Step through each array and take either the matching 
		// rows element or the first element in cases where only
		// one entry is provided
		
		$participantList = array( );
		for( $i = 0; $i < $size; $i++ ) {
			
			$currentPair = array( );
			$participantIDs = array( );
			$roleIDs = array( );
			foreach( $participantSets as $participantSet ) {
				if( isset( $participantSet[$i] )) {
					$currentPair[] = $participantSet[$i];
					$participantIDs[] = $participantSet[$i]['participant'];
					$roleIDs[] = $participantSet[$i]['role'];
				} else {
					$currentPair[] = $participantSet[0];
					$participantIDs[] = $participantSet[0]['participant'];
					$roleIDs[] = $participantSet[0]['role'];
				}
			}
			
			// Add Each Attributes
			
			
			// Add All Attributes
			$participantList[] = array( "PARTICIPANTS" => $currentPair, "HASH" => $this->hashids->generateHash( $participantIDs, $roleIDs ) );
			
		}
		
		return $participantList;
		
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
	 
	private function validateSubmission( ) {
		
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