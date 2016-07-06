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
	
	public function __construct( ) {
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$this->curationOps = new models\CurationOperations( );
		
	}
	
	/**
	 * Validate the dataset as a whole and if it validates
	 * process it for inclusion in the database and elastic search
	 */
	 
	public function processCurationSubmission( $options ) {
		
		$status = "SUCCESS";
		$errors = array( );
		
		if( isset( $options['validationStatus'] ) ) {
			if( $options['validationStatus'] == "false" ) {
				$status = "ERROR";
				$errors[] = $this->curationOps->generateError( "INVALID_BLOCKS", array( "invalidBlocks" => json_decode( $_POST['invalidBlocks'] ) ) );
			} else {
				
				// All blocks are Valid
				// Fetch curation block details
				$this->curationOps->fetchCurationWorkflowSettings( $options['curationType'] );
				
				// Fetch curation details from database
				$curatedData = $this->fetchCurationSubmissionEntries( $options['curationCode'] );
				
				// Check to see if any of the blocks have validation requirements
				
				
			}
		}
		
		return json_encode( array( "STATUS" => $status, "ERRORS" => $this->curationOps->processErrors( $errors ) ) );
		
	}
	
	/**
	 * Fetch curation submission content for processing to 
	 * the other parts of the database
	 */
	 
		/**
	 * Fetch an existing curation entry out of the database
	 * if it exists, otherwise an empty array
	 */
	 
	private function fetchCurationSubmissionEntries( $code ) {
		
		$stmt = $this->db->prepare( "SELECT * FROM " . DB_IMS . ".curation WHERE curation_code=?" );
		$stmt->execute( array( $code ) );
		
		$results = array( );
		while( $row = $stmt->fetchAll( PDO::FETCH_ASSOC ) ) {
			$results[$row["curation_block"] . "|" . $row["curation_subtype"]] = $row;
			$results[$row["curation_block"] . "|" . $row["curation_subtype"]]["curation_data"] = json_decode( $row["curation_data"] );
		}
		
		return $results;
		
	}
	
}