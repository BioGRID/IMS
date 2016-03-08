<?PHP

/**
 * Return interactions from Elastic Search for display in
 * a jquery datatable.
 */

session_start( );
header( "Cache-control: private" );

require_once __DIR__ . '/../../app/lib/Bootstrap.php';

use IMS\app\classes\models\InteractionTables;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

$log = new Logger( 'Load Interactions Log' );
$log->pushHandler( new StreamHandler( __DIR__ . '/../logs/LoadInteractions.log', Logger::DEBUG ));
$log->addInfo( print_r( $_POST, true ) );

$draw = $_POST['draw'];

$datasetID = $_POST['datasetID'];
$typeID = $_POST['type'];
$status = $_POST['status'];
$recordsTotal = $_POST[strtolower($status)];

$searchTerm = "";
if( strlen( $_POST['search']['value'] ) > 0 ) {
	$searchTerm = $_POST['search']['value'];
}

$order = array( );
if( sizeof( $_POST['order'] ) > 0 ) {
	$order = $_POST['order'];
}

$start = $_POST['start'];
$length = $_POST['length'];

/*
$draw = 1;

$datasetID = "101382";
$typeID = "1";
$status = "activated";
$recordsTotal = "1932";

$searchTerm = '"@NOTEX:gln3 gat1 double mutant, GAL-MKS1"';
// if( strlen( $_POST['search']['value'] ) > 0 ) {
	// $searchTerm = $_POST['search']['value'];
// }

$order = array( );
if( sizeof( $_POST['order'] ) > 0 ) {
	$order = $_POST['order'];
}

$start = 0;
$length = 100;
*/

$intTables = new InteractionTables( );

// Fetch correct column structure
$columns = $intTables->fetchColumns( $typeID );

// Send request to Elastic Search and get back hits if they exist
// for the specified query
$response = $intTables->fetchInteractions( $datasetID, $typeID, $status, $searchTerm, $start, $length, $order, $columns );

// Format the results based on the columns configuration
// for output to Jquery Datatables
$data = $intTables->formatResults( $response, $columns, $typeID );

echo json_encode( array( 
	"draw" => $draw, 
	"recordsTotal" => $recordsTotal, 
	"recordsFiltered" => $response['hits']['total'], 
	"data" => $data
));

?>