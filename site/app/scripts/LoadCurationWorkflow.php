<?PHP

/**
 * Return a curation workflow from the database for display in
 * in the curation tools view.
 */

session_start( );
header( "Cache-control: private" );

require_once __DIR__ . '/../../app/lib/Bootstrap.php';

print_r( $_POST );

?>