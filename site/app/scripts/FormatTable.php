<?PHP

/**
 * Grab a formatted table based on the type of data we are about to
 * show using jquery datatables.
 */

session_start( );
header( "Cache-control: private" );

require_once __DIR__ . '/../../app/lib/Bootstrap.php';

use IMS\app\classes\models\InteractionTables;

$intTables = new InteractionTables( );
$columns = $intTables->fetchColumns( $_POST['type'] );
$columns = $intTables->fetchColumnHeaderDefinitions( $columns, $_POST['type'] );
echo json_encode( $columns );
 
?>