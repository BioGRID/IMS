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
			
		// Load a list of ontology terms that match a passed in full text search query
		case 'loadSearchOntologyTerms' :
			$output = $ontologyBlocks->fetchSearchOntologyTerms( $_POST['ontology_id'], $_POST['search'] );
			echo json_encode( $output );
			break;
			
		// Load term details into a popup
		case 'fetchOntologyTermDetails' :
			$output = $ontologyBlocks->fetchOntologyTermDetails( $_POST['ontology_term_id'] );
			echo json_encode( $output );
			break;
			
		// Load a list of ontology terms to start a browseable tree view
		case 'loadTreeOntologyTerms' :
			$output = $ontologyBlocks->fetchTreeOntologyTerms( $_POST['ontology_id'] );
			echo json_encode( $output );
			break;
		
		// Load a list of ontology terms to expand children of a browseable tree
		case 'loadTreeOntologyChildren' :
			$output = $ontologyBlocks->fetchChildOntologyTerms( $_POST['ontology_term_id'] );
			echo json_encode( $output );
			break;
			
		// Load a list of ontology terms showing all lineage paths for a given term
		case 'loadLineageOntologyTerms' :
			$output = $ontologyBlocks->fetchLineageOntologyTerms( $_POST['ontology_term_id'] );
			echo json_encode( $output );
			break;
	}
		
}
 
?>


		