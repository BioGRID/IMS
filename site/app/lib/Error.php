<?php

namespace IMS\app\lib;

/**
 * Error
 * This class contains methods for dealing with errors within the
 * site.
 */
 
class Error {
	
	public static function processError( $errorType, $urlValues ) {
	
		switch( $errorType ) {
		
			case "invalidAction" :
				die( "INVALID ACTION => " . print_r( $urlValues, true ) );
				break;
			
			case "invalidController" :
				die( "INVALID CONTROLLER => " . print_r( $urlValues, true ) );
				break;
				
			case "invalidResultID" :
				die( "INVALID RESULT ID => " . print_r( $urlValues, true ) );
				break;
				
			default :
				die( "ERROR" );
				break;
		
		}
	
	}
	
}

?>