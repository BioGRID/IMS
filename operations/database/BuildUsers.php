<?php

/*

Build a new user table using as much data as possible from the old IMS 2.0 user table
but with more modern password hashing. Resets all passwords to a default password.

*/

ini_set( "memory_limit", "3000M" );

require_once ( dirname( __FILE__ ) . '/Config.php' );

$db = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_IMS );
$disableUsers = array( 2, 6, 7, 8, 10, 11, 12, 14, 15, 16, 17, 18, 19, 34, 37, 40, 41, 42, 44, 47, 51, 53, 57, 58, 59 );
$disableUsers = array_flip( $disableUsers );

$stmt = $db->prepare( "TRUNCATE TABLE " . DB_IMS . ".users" );
$stmt->execute( );
$stmt->close( );

$stmt = $db->prepare( "SELECT * FROM " . DB_IMS_OLD . ".users ORDER BY user_id ASC" );
$stmt->execute( );
$stmt->bind_result( $userID, $userName, $userPass, $userCookie, $userFirst, $userLast, $userEmail, $accessTimestamp, $userRole, $defaultProject );

$users = array( );
while( $stmt->fetch( ) ) {
	
	$status = "active";
	if( $userCookie == "" && $userID != "1" ) {
		$status = "inactive";
	}
	
	// Disable some users manually
	if( isset( $disableUsers[$userID] ) ) {
		$status = "inactive";
	}
	
	$users[$userID] = array( "ID" => $userID, "USERNAME" => $userName, "FIRSTNAME" => $userFirst, "LASTNAME" => $userLast, "EMAIL" => $userEmail, "TIMESTAMP" => "0000-00-00 00:00:00", "ROLE" => $userRole, "PROJECT" => $defaultProject, "STATUS" => $status, "UUID" => "-", );
}

$stmt->close( );

$stmt = $db->prepare( "INSERT INTO " . DB_IMS . ".users VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '1' )" );

// Set password to a BCRYPT hash based on
// a default password since we are unable to reverse
// engineer to old MD5 hashes.

foreach( $users as $userID => $userInfo ) {
	$hash = password_hash( DEFAULT_PASSWORD, PASSWORD_BCRYPT, array( "cost" => 12 ) );
	$stmt->bind_param( "ssssssssss", $userInfo["ID"], $userInfo["USERNAME"], $hash, $userInfo["FIRSTNAME"], $userInfo["LASTNAME"], $userInfo["EMAIL"], $userInfo["UUID"], $userInfo["TIMESTAMP"], $userInfo["ROLE"], $userInfo["STATUS"] );
	$stmt->execute( );
}

$stmt->close( );

?>