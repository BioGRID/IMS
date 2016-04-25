<?php

/**
 * Exceute a process in the ScriptProcesses.php file
 * and return the result to handle in JS.
 */

session_start( );
header( "Cache-control: private" );

ini_set( "memory_limit", "2048M" );

require_once __DIR__ . '/../../../app/lib/Bootstrap.php';

use IMS\app\classes\models;

$required = false;
if( $_POST['required'] == "1" ) {
	$required = true;
}

$validate = new models\CurationValidation( $_POST['name'] );

if( isset( $_POST['curationCode'] ) ) {
	$curationCode = $_POST['curationCode'];
	
	if( $_POST['type'] == "participant" ) {

		$results = $validate->validateIdentifiers( $_POST['participants'], $_POST['role'], $_POST['participant_type'], $_POST['organism'], $_POST['id_type'], $curationCode, $_POST['id'], $required );
		
		if( isset( $_POST['allele'] ) && sizeof( $_POST['allele'] ) > 0 ) {
			$results = $validate->validateAlleles( $_POST['allele'], $results['COUNTS']['TOTAL'], $results, $curationCode, $_POST['id'] );
		}

		if( sizeof( $results['ERRORS'] ) > 0 ) {
			$results['ERRORS'] = $validate->processErrors( $results['ERRORS'] );
		} else {
			$results['ERRORS'] = "";
		}
		
		echo json_encode( $results );
		
	}
	
} else {

	$errors = $validate->processErrors( array( $validate->generateError( "NOCODE" )) );
	echo json_encode( array( "STATUS" => "ERROR", "ERRORS" => $errors ) );

}

?>