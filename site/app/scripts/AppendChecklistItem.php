<?PHP

/**
 * Return a curation workflow from the database for display in
 * in the curation tools view.
 */

session_start( );
header( "Cache-control: private" );

require_once __DIR__ . '/../../app/lib/Bootstrap.php';

use IMS\app\classes\blocks;

$curationBlocks = new blocks\CurationBlocks( $_POST['blockCount'], $_POST['partCount'] );
$view = $curationBlocks->fetchCurationChecklistItem( $_POST['selected'] );

$output = array( "view" => $view, "blockCount" => $curationBlocks->getBlockCount( ), "partCount" => $curationBlocks->getParticipantCount( ), "show" => $curationBlocks->getBlockCount( )-1 );
echo json_encode( $output );

?>