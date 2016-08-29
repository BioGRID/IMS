<?PHP

/**
 * Exceute a process in the Progress.php file
 * and return the result to handle in JS.
 */

header( "Cache-control: private" );

require_once __DIR__ . '/../../../app/lib/Bootstrap.php';

use IMS\app\classes\models;

if( isset( $_POST['script'] ) ) {
	
	switch( $_POST['script'] ) {
			
		// Fetch Database Submission Progress
		case 'updateSubmissionProgress' :
			$curationOps = new models\CurationOperations( );
			echo json_encode($curationOps->fetchCurationSubmissionProgress( $_POST ));
			break;
			
	}
		
}
 
?>