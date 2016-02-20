<?php

namespace IMS\app\classes\utilities;

/**
 * Grab datatable structures based on the type of data we are
 * attempting to display to the user
 */
 
class Datatables {
	
	function __construct( ) {
		
	}
	
	/**
	 * Fetch the formatted datatable structure based on the type of data
	 * we intend to show to the user.
	 */
	
	public function fetchTableStructure( $typeID ) {
		
		switch( $typeID ) {
			
			case "1" :
				$columns = array( 
					array( "title" => "", "data" => "CHECK", "orderable" => false, "sortable" => false, "className" => "text-center" ),
					array( "title" => "Bait", "data" => "BAIT" ),
					array( "title" => "Prey", "data" => "HIT" ),
					array( "title" => "Bait Org", "data" => "ORG_BAIT" ),
					array( "title" => "Prey Org", "data" => "ORG_HIT" ),
					array( "title" => "System", "data" => "SYSTEM" ),
					array( "title" => "User", "data" => "USER" ),
					array( "title" => "Date", "data" => "DATE" ),
					array( "title" => "Attributes", "data" => "ATTRIBUTES" ),
					array( "title" => "State", "data" => "STATE" )
				);
				
				return json_encode( $columns );
			
		}
		
	}
	
}