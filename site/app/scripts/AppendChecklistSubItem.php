<?PHP

/**
 * Return a curation workflow from the database for display in
 * in the curation tools view.
 */

session_start( );
header( "Cache-control: private" );

require_once __DIR__ . '/../../app/lib/Bootstrap.php';

use IMS\app\classes\blocks;

$curationBlocks = new blocks\CurationBlocks( $_POST['blockCount'], 0 );
$checklist = $curationBlocks->fetchCurationChecklistSubItem( $_POST['selected'], $_POST['parent'], $_POST['parentName'], $_POST['subCount'] );
$body = $curationBlocks->fetchCurationPanel( $_POST );

$subCount = intval($_POST['subCount']);

$output = array( "checklist" => $checklist, "body" => $body, "subCount" => ++$subCount );
echo json_encode( $output );

?>