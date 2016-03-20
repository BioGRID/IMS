<?PHP

/**
 * Return a curation workflow from the database for display in
 * in the curation tools view.
 */

session_start( );
header( "Cache-control: private" );

require_once __DIR__ . '/../../app/lib/Bootstrap.php';

use IMS\app\classes\blocks;

// $curationBlocks = new blocks\CurationBlocks( );
// echo $curationBlocks->fetchCurationBlocks( "1" );

echo "<div style='width:100%; background-color: #FFFFEF;' id='" . $_POST['blockid'] . "' class='curationBlock'>";
	echo "<strong>" . $_POST['blockid'] . "</strong>";
	print_r( $_POST );
echo "</div>";

?>