<?php

/**
 * Exceute a process in the ScriptProcesses.php file
 * and return the result to handle in JS.
 */

session_start( );
header( "Cache-control: private" );

require_once __DIR__ . '/../../../app/lib/Bootstrap.php';

use IMS\app\classes\models;
$validate = new models\CurationValidation( $_POST['name'] );
$results = $validate->validateIdentifiers( $_POST['participants'], $_POST['role'], $_POST['participant_type'], $_POST['organism'], $_POST['id_type'], true );

echo json_encode( $results );

?>