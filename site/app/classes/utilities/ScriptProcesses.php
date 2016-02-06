<?php

namespace IMS\app\classes\utilities;

/**
 * Script Processes
 * This is a simple class containing common processes that need
 * to be executed from ajax based scripts. 
 */
 
use IMS\app\lib;
use IMS\app\classes\models;
 
class ScriptProcesses {
	
	private $db;
	
	public function __construct( ) {
		
	}
	
	/**
	 * Switch availability of a dataset
	 */
	 
	public function switchAvailability( $datasetID, $option ) {
		
		$datasets = new models\Datasets( );
		$datasets->changeAvailability( $datasetID, $option );
		
		$availabilityLabel = $datasets->fetchAvailabilityLabel( $option );
		return "<span class='label label-" . $availabilityLabel . "'><span class='datasetDetailText'>" . strtoupper($option) . "</span></span>";
	}
	
	/**
	 * Switch to a new group
	 */
	
	public function switchGroup( $groupID ) {
		if( lib\Session::updateGroup( $groupID ) ) {
			return $_SESSION[SESSION_NAME]["GROUPS"][$groupID];
		} 
		
		return array( );
	}

}

?>