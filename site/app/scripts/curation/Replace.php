<?php

/**
 * Exceute a process in the ScriptProcesses.php file
 * and return the result to handle in JS.
 */

session_start( );
header( "Cache-control: private" );

require_once __DIR__ . '/../../../app/lib/Bootstrap.php';

use IMS\app\classes\models;

$validate = new models\CurationValidation( "" );
$replacedParticipants = $validate->replaceParticipantLines( $_POST['data'], $_POST['lines'], $_POST['identifier'] );
echo json_encode( array( "REPLACEMENT" => $replacedParticipants, "MESSAGE" => "Successfully Replaced Ambiguous Lines" ) );

?>