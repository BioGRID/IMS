<?php	


namespace IMS\app\classes\models;

/**
 * Curation Progress
 * Tools for handling curation submission progress updates
 */

use \PDO;
use IMS\app\lib;
use IMS\app\classes\models;

class CurationProgress {
	
	private $db;
	private $dbStmts;
	
	public function __construct( ) {
		
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$this->dbStmts = array( 
			"exists" => $this->db->prepare( "SELECT curation_code FROM " . DB_IMS . ".curation_progress WHERE curation_code=? LIMIT 1" ),
			"insert" => $this->db->prepare( "INSERT INTO " . DB_IMS . ".curation_progress VALUES( ?, ?, NOW( ), NOW( ) )" ),
			"update" => $this->db->prepare( "UPDATE " . DB_IMS . ".curation_progress SET curation_status=?, curation_last_update=NOW( ) WHERE curation_code=?" )
		);
		
	}
	
	/**
	 * Initialize the curation progress procedures
	 */
	
	public function init( $curationCode ) {
		
		$this->dbStmts['exists']->execute( array( $curationCode ));
		
		if( $row = $this->dbStmts['exists']->fetch( PDO::FETCH_OBJ ) ) {
			$this->updateProgress( "init", $curationCode );
		} else {
			$this->insertProgress( "init", $curationCode );
		}
		
	}
	
	/**
	 * Change curation status to something new and return all missing results
	 */
	
	public function changeProgress( $curationStatus, $curationCode ) {
		$this->updateProgress( $curationStatus, $curationCode );
	}
	
	/**
	 * Update the curation progress to the progress type
	 */
	
	private function updateProgress( $curationStatus, $curationCode ) {
		$this->dbStmts['update']->execute( array( $curationStatus, $curationCode ));
	}
	
		
	/**
	 * Insert the curation progress to the progress type
	 */
	
	private function insertProgress( $curationStatus, $curationCode ) {
		$this->dbStmts['insert']->execute( array( $curationCode, $curationStatus ));
	}
	
}