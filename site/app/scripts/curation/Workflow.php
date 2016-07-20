<?PHP

/**
 * Exceute a process in the ScriptProcesses.php file
 * and return the result to handle in JS.
 */

session_start( );
header( "Cache-control: private" );

ini_set( "memory_limit", "3096M" );

require_once __DIR__ . '/../../../app/lib/Bootstrap.php';

use IMS\app\classes\models;

if( isset( $_POST['script'] ) ) {
	
	switch( $_POST['script'] ) {
	
		// Append a main checklist item to the curation checklist
		// on the right hand side of the curation workflow
		case 'appendChecklistItem' :
			$curationBlocks = new models\CurationBlocks( );
			$curationBlocks->setCounts( $_POST['blockCount'], $_POST['partCount'] );
			
			$view = $curationBlocks->fetchCurationChecklistItem( $_POST['selected'] );
			$output = array( "view" => $view, "blockCount" => $curationBlocks->getBlockCount( ), "partCount" => $curationBlocks->getParticipantCount( ), "show" => $curationBlocks->getBlockCount( )-1 );
			
			echo json_encode( $output );
			break;
		
		// Append a sub item to a main checklist item in the curation
		// checklist on the right hand side
		case 'appendChecklistSubItem' :
			$curationBlocks = new models\CurationBlocks( );
			$curationBlocks->setCounts( $_POST['blockCount'], 0 );
			
			$checklist = $curationBlocks->fetchCurationChecklistSubItem( $_POST['selected'], $_POST['parent'], $_POST['parentName'], $_POST['subCount'] );
			$body = $curationBlocks->fetchCurationPanel( $_POST );

			$subCount = intval($_POST['subCount']);

			$output = array( "checklist" => $checklist, "body" => $body, "subCount" => ++$subCount );
			echo json_encode( $output );
			break;
		
		// Generate a curation block and return the HTML to display
		case 'loadCurationBlock' :
			$curationBlocks = new models\CurationBlocks( );
			echo $curationBlocks->fetchCurationBlock( $_POST );
			break;
			
		// Load a curation checklist based on the type of interaction
		// requested for curation
		case 'loadCurationChecklist' :
			$curationBlocks = new models\CurationBlocks( );
			$curationBlocks->setCounts( 1, 1 );
			echo $curationBlocks->fetchCurationChecklist( $_POST['type'] );
			break;
			
		// Submit a Validated Dataset for Validation across entire set
		// and for submission into the database if successful
		case 'submitCuratedDataset' :
			$curationSubmit = new models\CurationSubmission( );
			echo $curationSubmit->processCurationSubmission( $_POST );
			break;
			
		// Fetch a formatted notification
		case 'loadWorkflowNotification' :
			$curationOps = new models\CurationOperations( );
			echo $curationOps->fetchCurationNotification( $_POST );
			break;
			
	}
		
}
 
?>