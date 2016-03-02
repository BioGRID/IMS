<?php

/**
 * Column Definitions
 * This class is for manually building out interaction type column
 * definitions to populate the database with
 */

use \PDO;
 
class ColumnDefinitions {

	private $db;

	/**
	 * Establish a database connection
	 */
	
	public function __construct( ) {
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	}
	
	/**
	 * Column definitions for plain protein-protein interactions
	 */
	 
	public function getProteinProteinDefs( ) {
		
		$columns = array( );
		$columns[0] = array( 
			"title" => "",
			"orderable" => false,
			"sortable" => false,
			"className" => "text-center",
			"type" => "direct",
			"value" => "interaction_id",
			"html" => "<div class='checkbox'><label><input type='checkbox' value='{{VALUE}}'></label></div>",
			"search" => ""
		);

		$columns[1] = array( 
			"title" => "Bait",
			"type" => "participant",
			"value" => "primary_name",
			"query" => array( "participant_role_id" => "2" ),
			"func" => array( "participant-norole" ),
			"search" => "#B"
		);

		$columns[2] = array( 
			"title" => "Prey",
			"type" => "participant",
			"value" => "primary_name",
			"query" => array( "participant_role_id" => "3" ),
			"func" => array( "participant-norole" ),
			"search" => "#P"
		);

		$columns[3] = array( 
			"title" => "Bait Org",
			"type" => "participant",
			"value" => "organism_abbreviation",
			"query" => array( "participant_role_id" => "2" ),
			"noattribs" => true,
			"search" => "#BO"
		);

		$columns[4] = array( 
			"title" => "Prey Org",
			"type" => "participant",
			"value" => "organism_abbreviation",
			"query" => array( "participant_role_id" => "3" ),
			"noattribs" => true,
			"search" => "#PO"
		);

		$columns[5] = array( 
			"title" => "System",
			"type" => "attribute",
			"value" => "attribute_value",
			"query" => array( "attribute_type_id" => "11" ),
			"search" => "@ES"
		);

		$columns[6] = array( 
			"title" => "User",
			"type" => "direct",
			"value" => "history_user_name",
			"search" => "#U"
		);

		$columns[7] = array( 
			"title" => "Date",
			"type" => "direct",
			"value" => "history_date",
			"func" => array( "date" ),
			"search" => "#D"
		);

		$columns[8] = array( 
			"title" => "Attributes",
			"type" => "attribute_icons",
			"value" => "interaction_id",
			"ignore" => array( array( "attribute_type_id" => "11" ) ),
			"className" => "text-center",
			"search" => "@ATB"
		);

		$columns[9] = array( 
			"title" => "State",
			"type" => "direct",
			"value" => "interaction_state",
			"search" => "#S"
		);
		
		return $columns;
		
	}
	
	/**
	 * Column definitions for PTM Sites
	 */
	
	public function getPTMDefs( ) {
		
		$columns = array( );
		$columns[0] = array( 
			"title" => "",
			"orderable" => false,
			"sortable" => false,
			"className" => "text-center",
			"type" => "direct",
			"value" => "interaction_id",
			"html" => "<div class='checkbox'><label><input type='checkbox' value='{{VALUE}}'></label></div>",
			"search" => ""
		);

		$columns[1] = array( 
			"title" => "Substrate",
			"type" => "participant",
			"value" => "primary_name",
			"query" => array( "participant_role_id" => "12" ),
			"func" => array( "participant-norole" ),
			"search" => "#SUB"
		);

		$columns[2] = array( 
			"title" => "Enzyme",
			"type" => "participant",
			"value" => "primary_name",
			"query" => array( "participant_role_id" => "11" ),
			"func" => array( "participant-norole" ),
			"search" => "#ENZ"
		);

		$columns[3] = array( 
			"title" => "Organism",
			"type" => "participant",
			"value" => "organism_abbreviation",
			"query" => array( "participant_role_id" => "12" ),
			"noattribs" => true,
			"search" => "#O"
		);
		
		$columns[4] = array( 
			"title" => "PTM Site",
			"type" => "attribute",
			"value" => "attribute_value",
			"query" => array( "attribute_type_id" => "24" ),
			"search" => "@RES-LOC"
		);

		$columns[5] = array( 
			"title" => "PTM Type",
			"type" => "attribute",
			"value" => "attribute_value",
			"query" => array( "attribute_type_id" => "12" ),
			"search" => "@PTM"
		);

		$columns[6] = array( 
			"title" => "User",
			"type" => "direct",
			"value" => "history_user_name",
			"search" => "#U"
		);

		$columns[7] = array( 
			"title" => "Date",
			"type" => "direct",
			"value" => "history_date",
			"func" => array( "date" ),
			"search" => "#D"
		);

		$columns[8] = array( 
			"title" => "Attributes",
			"type" => "attribute_icons",
			"value" => "interaction_id",
			"ignore" => array( array( "attribute_type_id" => "12" ), array( "attribute_type_id" => "24" ) ),
			"className" => "text-center",
			"search" => "#ATB"
		);

		$columns[9] = array( 
			"title" => "State",
			"type" => "direct",
			"value" => "interaction_state",
			"search" => "#S"
		);
		
		return $columns;
		
	}
	
	/**
	 * Column definitions for plain complex interactions
	 */
	 
	public function getComplexDefs( ) {
		
		$columns = array( );
		$columns[0] = array( 
			"title" => "",
			"orderable" => false,
			"sortable" => false,
			"className" => "text-center",
			"type" => "direct",
			"value" => "interaction_id",
			"html" => "<div class='checkbox'><label><input type='checkbox' value='{{VALUE}}'></label></div>",
			"search" => ""
		);

		$columns[1] = array( 
			"title" => "Participants",
			"type" => "participant",
			"value" => "primary_name",
			"query" => array( ),
			"func" => array( "participant-norole" ),
			"search" => "#PS"
		);

		$columns[2] = array( 
			"title" => "Organisms",
			"type" => "participant",
			"value" => "organism_abbreviation",
			"query" => array( ),
			"list" => "unique",
			"noattribs" => true,
			"search" => "#O"
		);

		$columns[3] = array( 
			"title" => "System",
			"type" => "attribute",
			"value" => "attribute_value",
			"query" => array( "attribute_type_id" => "11" ),
			"search" => "@ES"
		);

		$columns[4] = array( 
			"title" => "User",
			"type" => "direct",
			"value" => "history_user_name",
			"search" => "#U"
		);

		$columns[5] = array( 
			"title" => "Date",
			"type" => "direct",
			"value" => "history_date",
			"func" => array( "date" ),
			"search" => "#D"
		);

		$columns[6] = array( 
			"title" => "Attributes",
			"type" => "attribute_icons",
			"value" => "interaction_id",
			"ignore" => array( array( "attribute_type_id" => "11" ) ),
			"className" => "text-center",
			"search" => "@ATB"
		);

		$columns[7] = array( 
			"title" => "State",
			"type" => "direct",
			"value" => "interaction_state",
			"search" => "#S"
		);
		
		return $columns;
		
	}
	
	/**
	 * Column definitions for Chemical-Protein Interactions
	 */
	
	public function getChemDefs( ) {
		
		$columns = array( );
		$columns[0] = array( 
			"title" => "",
			"orderable" => false,
			"sortable" => false,
			"className" => "text-center",
			"type" => "direct",
			"value" => "interaction_id",
			"html" => "<div class='checkbox'><label><input type='checkbox' value='{{VALUE}}'></label></div>",
			"search" => ""
		);

		$columns[1] = array( 
			"title" => "Chemical Target",
			"type" => "participant",
			"value" => "primary_name",
			"query" => array( "participant_role_id" => "10" ),
			"func" => array( "participant-norole" ),
			"search" => "#CT"
		);

		$columns[2] = array( 
			"title" => "Chemical",
			"type" => "participant",
			"value" => "primary_name",
			"query" => array( "participant_type_id" => "4" ),
			"func" => array( "participant-norole" ),
			"search" => "#CH"
		);

		$columns[3] = array( 
			"title" => "Organism",
			"type" => "participant",
			"value" => "organism_abbreviation",
			"query" => array( "participant_role_id" => "10" ),
			"noattribs" => true,
			"search" => "#O"
		);
		
		$columns[4] = array( 
			"title" => "Chemical Action",
			"type" => "attribute",
			"value" => "attribute_value",
			"query" => array( "attribute_type_id" => "32" ),
			"search" => "@ACTN"
		);

		$columns[5] = array( 
			"title" => "User",
			"type" => "direct",
			"value" => "history_user_name",
			"search" => "#U"
		);

		$columns[6] = array( 
			"title" => "Date",
			"type" => "direct",
			"value" => "history_date",
			"func" => array( "date" ),
			"search" => "#D"
		);

		$columns[7] = array( 
			"title" => "Attributes",
			"type" => "attribute_icons",
			"value" => "interaction_id",
			"ignore" => array( array( "attribute_type_id" => "32" ) ),
			"className" => "text-center",
			"search" => "#ATB"
		);

		$columns[8] = array( 
			"title" => "State",
			"type" => "direct",
			"value" => "interaction_state",
			"search" => "#S"
		);
		
		return $columns;
		
	}
	
	/**
	 * Insert column definitions into the database
	 */
	 
	public function insert( $columnDefs, $interactionTypeID ) {
		$stmt = $this->db->prepare( "UPDATE " . DB_IMS . ".interaction_types SET interaction_type_columns=? WHERE interaction_type_id=?" );
		$stmt->execute( array( json_encode($columnDefs), $interactionTypeID) );
	}
	
}