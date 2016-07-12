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

class CurationSubmission {
	
	private $db;
	private $curationOps;
	private $workflowSettings;
	private $curatedData;
	private $errors;
	
	public function __construct( ) {
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$this->curationOps = new models\CurationOperations( );
		$this->errors = array( );
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
				
				// Test curated data against workflow settings to
				// see if data is valid
				if( !$this->validateSubmission( ) ) {
					$status = "ERROR";
				} else {
					
					// Process each block of stored data and generate a
					// game plan for processing. Game plan will be based
					// on the values and fields stored
					
					$status = "SUCCESS";
					
				}
				
			}
		}
		
		return json_encode( array( "STATUS" => $status, "ERRORS" => $this->curationOps->processErrors( $this->errors ) ) );
		
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
			
				$testSize = sizeof( $this->curatedData[$testBlock]["participant"]["members"]->curation_data );
				$compareSize = sizeof( $this->curatedData[$compareBlock]["participant"]["members"]->curation_data );
			
				if( $testSize == 1 || $compareSize == 1 ) {
					
					return true;
					
				} else {
					
					if( $testSize == $compareSize ) {
						return true;
					}
					
				}
				
				$this->errors[] = $this->curationOps->generateError( "SINGLE_EQUAL", array( "testBlockName" => $this->curatedData[$testBlock]["participant"]["members"]->curation_name, "compareBlockName" => $this->curatedData[$compareBlock]["participant"]["members"]->curation_name, "testBlockSize" => $testSize, "compareBlockSize" => $compareSize ) );
				
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
			
			if( !isset( $results[$row->curation_block] ) ) {
				$results[$row->curation_block] = array( );
			}
			
			if( !isset( $results[$row->curation_block][$row->curation_type] ) ) {
				$results[$row->curation_block][$row->curation_type] = array( );
			}
			
			if( !isset( $results[$row->curation_block][$row->curation_type][$row->curation_subtype] ) ) {
				$results[$row->curation_block][$row->curation_type][$row->curation_subtype] = array( );
			}
			
			$results[$row->curation_block][$row->curation_type][$row->curation_subtype] = $row;
			$results[$row->curation_block][$row->curation_type][$row->curation_subtype]->curation_data = json_decode( $row->curation_data );
			
		}
		
		return $results;
		
	}
	
}