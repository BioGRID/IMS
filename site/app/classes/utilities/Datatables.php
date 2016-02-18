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
		
		$tableHeader = "<table id='dataTable-" . $typeID . "' class='table table-striped table-bordered'>";
		$tableFooter = "<tbody></tbody></table>";
		
		switch( $typeID ) {
			
			case "1" :
				return $tableHeader . "<thead><th>Bait</th><th>Prey</th><th>Bait Org</th><th>Prey Org</th><th>System</th><th>User</th><th>Date</th><th>Other</th><th>Options</th></thead>" . $tableFooter;
			
		}
		
	}
	
}