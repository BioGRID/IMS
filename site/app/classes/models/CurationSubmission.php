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
			}
		}
		
		return json_encode( array( "STATUS" => $status, "ERRORS" => $this->curationOps->processErrors( $errors ) ) );
		
	}
	
}