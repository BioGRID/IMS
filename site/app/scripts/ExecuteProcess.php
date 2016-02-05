<?PHP

/**
 * Exceute a process in the ScriptProcesses.php file
 * and return the result to handle in JS.
 */

session_start( );
header( "Cache-control: private" );

require_once __DIR__ . '/../../app/lib/Bootstrap.php';

use IMS\app\classes\utilities\ScriptProcesses;

$sp = new ScriptProcesses( );

if( isset( $_POST['script'] ) ) {
	switch( $_POST['script'] ) {
		
		case 'switchGroup' :
			$groupInfo = $sp->switchGroup( $_POST['id'] );
			echo json_encode( $groupInfo );
			break;
			
		case 'switchAvailability' :
			$availText = $sp->switchAvailability( $_POST['id'], $_POST['value'] );
			echo $availText;
			break;
			
	}
}
 
?>