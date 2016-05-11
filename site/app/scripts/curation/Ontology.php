<?PHP

/**
 * Exceute an ontology process
 * and return the result to handle in JS.
 */

session_start( );
header( "Cache-control: private" );

require_once __DIR__ . '/../../../app/lib/Bootstrap.php';

use IMS\app\classes\models;

if( isset( $_POST['script'] ) ) {
	
	$ontologyBlocks = new models\OntologyBlocks( );
	
	switch( $_POST['script'] ) {
			
		// Load a list of popular ontology terms based on the ontology group selected
		case 'loadPopularOntologyTerms' :
			$output = $ontologyBlocks->fetchPopularOntologyTerms( $_POST['ontology_id'] );
			echo json_encode( $output );
			break;
			
	}
		
}
 
?>


		