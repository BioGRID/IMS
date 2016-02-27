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
			"html" => "<div class='checkbox'><label><input type='checkbox' value='{{VALUE}}'></label></div>"
		);

		$columns[1] = array( 
			"title" => "Bait",
			"type" => "participant",
			"value" => "primary_name",
			"query" => array( "participant_role_id" => "2" ),
			"func" => array( "participant-norole" )
		);

		$columns[2] = array( 
			"title" => "Prey",
			"type" => "participant",
			"value" => "primary_name",
			"query" => array( "participant_role_id" => "3" ),
			"func" => array( "participant-norole" )
		);

		$columns[3] = array( 
			"title" => "Bait Org",
			"type" => "participant",
			"value" => "organism_abbreviation",
			"query" => array( "participant_role_id" => "2" ),
			"noattribs" => true
		);

		$columns[4] = array( 
			"title" => "Prey Org",
			"type" => "participant",
			"value" => "organism_abbreviation",
			"query" => array( "participant_role_id" => "3" ),
			"noattribs" => true
		);

		$columns[5] = array( 
			"title" => "System",
			"type" => "attribute",
			"value" => "attribute_value",
			"query" => array( "attribute_type_id" => "11" )
		);

		$columns[6] = array( 
			"title" => "User",
			"type" => "direct",
			"value" => "history_user_name"
		);

		$columns[7] = array( 
			"title" => "Date",
			"type" => "direct",
			"value" => "history_date",
			"func" => array( "date" )
		);

		$columns[8] = array( 
			"title" => "Attributes",
			"type" => "attribute_icons",
			"value" => "interaction_id",
			"ignore" => array( array( "attribute_type_id" => "11" ) ),
			"className" => "text-center"
		);

		$columns[9] = array( 
			"title" => "State",
			"type" => "direct",
			"value" => "interaction_state"
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
			"html" => "<div class='checkbox'><label><input type='checkbox' value='{{VALUE}}'></label></div>"
		);

		$columns[1] = array( 
			"title" => "Substrate",
			"type" => "participant",
			"value" => "primary_name",
			"query" => array( "participant_role_id" => "12" ),
			"func" => array( "participant-norole" )
		);

		$columns[2] = array( 
			"title" => "Enzyme",
			"type" => "participant",
			"value" => "primary_name",
			"query" => array( "participant_role_id" => "11" ),
			"func" => array( "participant-norole" )
		);

		$columns[3] = array( 
			"title" => "Organism",
			"type" => "participant",
			"value" => "organism_abbreviation",
			"query" => array( "participant_role_id" => "12" ),
			"noattribs" => true
		);
		
		$columns[4] = array( 
			"title" => "PTM Site",
			"type" => "attribute",
			"value" => "attribute_value",
			"query" => array( "attribute_type_id" => "24" )
		);

		$columns[5] = array( 
			"title" => "PTM Type",
			"type" => "attribute",
			"value" => "attribute_value",
			"query" => array( "attribute_type_id" => "12" )
		);

		$columns[6] = array( 
			"title" => "User",
			"type" => "direct",
			"value" => "history_user_name"
		);

		$columns[7] = array( 
			"title" => "Date",
			"type" => "direct",
			"value" => "history_date",
			"func" => array( "date" )
		);

		$columns[8] = array( 
			"title" => "Attributes",
			"type" => "attribute_icons",
			"value" => "interaction_id",
			"ignore" => array( array( "attribute_type_id" => "12" ), array( "attribute_type_id" => "24" ) ),
			"className" => "text-center"
		);

		$columns[9] = array( 
			"title" => "State",
			"type" => "direct",
			"value" => "interaction_state"
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
			"html" => "<div class='checkbox'><label><input type='checkbox' value='{{VALUE}}'></label></div>"
		);

		$columns[1] = array( 
			"title" => "Participants",
			"type" => "participant",
			"value" => "primary_name",
			"query" => array( ),
			"func" => array( "participant-norole" )
		);

		$columns[2] = array( 
			"title" => "Organisms",
			"type" => "participant",
			"value" => "organism_abbreviation",
			"query" => array( ),
			"list" => "unique",
			"noattribs" => true
		);

		$columns[3] = array( 
			"title" => "System",
			"type" => "attribute",
			"value" => "attribute_value",
			"query" => array( "attribute_type_id" => "11" )
		);

		$columns[4] = array( 
			"title" => "User",
			"type" => "direct",
			"value" => "history_user_name"
		);

		$columns[5] = array( 
			"title" => "Date",
			"type" => "direct",
			"value" => "history_date",
			"func" => array( "date" )
		);

		$columns[6] = array( 
			"title" => "Attributes",
			"type" => "attribute_icons",
			"value" => "interaction_id",
			"ignore" => array( array( "attribute_type_id" => "11" ) ),
			"className" => "text-center"
		);

		$columns[7] = array( 
			"title" => "State",
			"type" => "direct",
			"value" => "interaction_state"
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
			"html" => "<div class='checkbox'><label><input type='checkbox' value='{{VALUE}}'></label></div>"
		);

		$columns[1] = array( 
			"title" => "Chemical Target",
			"type" => "participant",
			"value" => "primary_name",
			"query" => array( "participant_role_id" => "10" ),
			"func" => array( "participant-norole" )
		);

		$columns[2] = array( 
			"title" => "Chemical",
			"type" => "participant",
			"value" => "primary_name",
			"query" => array( "participant_type_id" => "4" ),
			"func" => array( "participant-norole" )
		);

		$columns[3] = array( 
			"title" => "Organism",
			"type" => "participant",
			"value" => "organism_abbreviation",
			"query" => array( "participant_role_id" => "10" ),
			"noattribs" => true
		);
		
		$columns[4] = array( 
			"title" => "Chemical Action",
			"type" => "attribute",
			"value" => "attribute_value",
			"query" => array( "attribute_type_id" => "32" )
		);

		$columns[5] = array( 
			"title" => "User",
			"type" => "direct",
			"value" => "history_user_name"
		);

		$columns[6] = array( 
			"title" => "Date",
			"type" => "direct",
			"value" => "history_date",
			"func" => array( "date" )
		);

		$columns[7] = array( 
			"title" => "Attributes",
			"type" => "attribute_icons",
			"value" => "interaction_id",
			"ignore" => array( array( "attribute_type_id" => "32" ) ),
			"className" => "text-center"
		);

		$columns[8] = array( 
			"title" => "State",
			"type" => "direct",
			"value" => "interaction_state"
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