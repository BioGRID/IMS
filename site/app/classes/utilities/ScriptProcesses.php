<?php

namespace IMS\app\classes\utilities;

/**
 * Script Processes
 * This is a simple class containing common processes that need
 * to be executed from ajax based scripts. 
 */
 
use IMS\app\lib;
 
class ScriptProcesses {
	
	private $db;
	
	public function __construct( ) {
		//$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		//$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
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