<?php

/**
 * Exceute a process in the ScriptProcesses.php file
 * and return the result to handle in JS.
 */

session_start( );
header( "Cache-control: private" );

ini_set( "memory_limit", "3096M" );

require_once __DIR__ . '/../../../app/lib/Bootstrap.php';

use IMS\app\classes\models;

$required = false;
if( $_POST['required'] == "1" ) {
	$required = true;
}

$validate = new models\CurationValidation( $_POST['name'] );
$curationOps = new models\CurationOperations( );

if( isset( $_POST['curationCode'] ) ) {
	$curationCode = $_POST['curationCode'];
	
	$results = array( );
	if( $_POST['type'] == "participant" ) {

		$results = $validate->validateIdentifiers( $_POST['participants'], $_POST['role'], $_POST['participant_type'], $_POST['organism'], $_POST['id_type'], $_POST['id'], $curationCode, $required );
		
		if( isset( $_POST['allele'] ) && sizeof( $_POST['allele'] ) > 0 ) {
			$results = $validate->validateAlleles( $_POST['allele'], $results['COUNTS']['TOTAL'], $results, $_POST['id'], $curationCode );
		}
		
	} else if( $_POST['type'] == "attribute" ) {
		$results = $validate->validateAttribute( $_POST, $_POST['id'], $curationCode, $required );
	}
	
	if( sizeof( $results['ERRORS'] ) > 0 ) {
		$results['ERRORS'] = $curationOps->processErrors( $results['ERRORS'] );
	} else {
		$results['ERRORS'] = "";
	}
	
	echo json_encode( $results );
	
} else {

	$errors = $curationOps->processErrors( array( $curationOps->generateError( "NOCODE" )) );
	echo json_encode( array( "STATUS" => "ERROR", "ERRORS" => $errors ) );

}

?>