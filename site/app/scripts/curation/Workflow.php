<?PHP

/**
 * Exceute a process in the ScriptProcesses.php file
 * and return the result to handle in JS.
 */

session_start( );
header( "Cache-control: private" );

require_once __DIR__ . '/../../../app/lib/Bootstrap.php';

use IMS\app\classes\blocks;

if( isset( $_POST['script'] ) ) {
	
	$curationBlocks = new blocks\CurationBlocks( );
	
	switch( $_POST['script'] ) {
	
		// Append a main checklist item to the curation checklist
		// on the right hand side of the curation workflow
		case 'appendChecklistItem' :
			$curationBlocks->setCounts( $_POST['blockCount'], $_POST['partCount'] );
			
			$view = $curationBlocks->fetchCurationChecklistItem( $_POST['selected'] );
			$output = array( "view" => $view, "blockCount" => $curationBlocks->getBlockCount( ), "partCount" => $curationBlocks->getParticipantCount( ), "show" => $curationBlocks->getBlockCount( )-1 );
			
			echo json_encode( $output );
			break;
		
		// Append a sub item to a main checklist item in the curation
		// checklist on the right hand side
		case 'appendChecklistSubItem' :
			$curationBlocks->setCounts( $_POST['blockCount'], 0 );
			
			$checklist = $curationBlocks->fetchCurationChecklistSubItem( $_POST['selected'], $_POST['parent'], $_POST['parentName'], $_POST['subCount'] );
			$body = $curationBlocks->fetchCurationPanel( $_POST );

			$subCount = intval($_POST['subCount']);

			$output = array( "checklist" => $checklist, "body" => $body, "subCount" => ++$subCount );
			echo json_encode( $output );
			break;
		
		// Generate a curation block and return the HTML to display
		case 'loadCurationBlock' :
			echo $curationBlocks->fetchCurationBlock( $_POST );
			break;
			
		// Load a curation checklist based on the type of interaction
		// requested for curation
		case 'loadCurationChecklist' :
			$curationBlocks->setCounts( 1, 1 );
			echo $curationBlocks->fetchCurationChecklist( $_POST['type'] );
			break;
			
	}
		
}
 
?>