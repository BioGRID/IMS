<?php

/*

Build a new user table using as much data as possible from the old IMS 2.0 user table
but with more modern password hashing. Resets all passwords to a default password.

*/

ini_set( "memory_limit", "3000M" );

require_once ( dirname( __FILE__ ) . '/Config.php' );
require_once ( dirname( __FILE__ ) . '/classes/ColumnDefinitions.php' );

$colDefs = new ColumnDefinitions( );

// Protein Protein Columns
$columns = $colDefs->getProteinProteinDefs( );
$colDefs->insert( $columns, "1" );

// PTMS
$columns = $colDefs->getPTMDefs( );
$colDefs->insert( $columns, "3" );

// Complex Columns
$columns = $colDefs->getComplexDefs( );
$colDefs->insert( $columns, "2" );

// Chemical Columns
$columns = $colDefs->getChemDefs( );
$colDefs->insert( $columns, "4" );

?>