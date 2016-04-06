<?php	


namespace IMS\app\classes\models;

/**
 * Curation Validation
 * This set of functions is for handling input from various curation blocks
 * and submission to the database of validated data.
 */

use \PDO;
use IMS\app\lib;
use IMS\app\classes\models;

class CurationValidation {
	
	private $db;
	private $blockName;
	
	public function __construct( $blockName ) {
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$loader = new \Twig_Loader_Filesystem( TEMPLATE_PATH );
		$this->twig = new \Twig_Environment( $loader );
		
		$this->blockName = $blockName;
	}
	
	/**
	 * Take a string of passed in identifiers and various search parameters, and
	 * attempt to map each one to a database identifier based on the string text
	 */
	
	public function validateIdentifiers( $identifiers, $role, $type, $taxa, $idType, $isRequired = false ) {
		
		$messages = array( );
		$identifiers = trim( $identifiers );
		$mapping = array( );
		
		if( $isRequired && strlen( $identifiers ) <= 0 ) {
			$status = "ERROR";
			$messages[] = $this->generateError( "REQUIRED" );
		} else if( !$isRequired && strlen( $identifiers ) <= 0 ) {
			$status = "VALID";
		} else {
			
			$identifiers = explode( PHP_EOL, $identifiers );
			$uniqueIdentifiers = array_unique( $identifiers );
			
			$annotationSet = array( );
			foreach( $uniqueIdentifiers as $identifier ) {
				$identifier = strtoupper( trim( filter_var( $identifier, FILTER_SANITIZE_STRING )));
				
				if( !isset( $annotationSet[$identifier] )) {
					$annotationInfo = $this->fetchMatchingIdentifiers( $identifier, $type, $taxa, $idType );
					$annotationSet[$identifier] = $annotationInfo;
				}
				
			}
			
			$lineCount = 1;
			$errorList = array( );
			$warningList = array( );
			foreach( $identifiers as $identifier ) {
				$identifier = strtoupper( trim( filter_var( $identifier, FILTER_SANITIZE_STRING )));
				
				$annotation = $annotationSet[$identifier];
				
				if( !isset( $mapping ) ) {
					$mapping[$identifier] = "UNKNOWN";
				}
				
				if( sizeof( $annotation ) <= 0 ) {
					
					// UNKNOWN
					if( !isset( $warningList[$identifier] ) ) {
						$warningList[$identifier] = array( );
					}
					
					$warningList[$identifier][] = $lineCount;
					
				} else if( sizeof( $annotation ) > 1 ) {
					
					// AMBIGUOUS
					if( !isset( $errorList[$identifier] ) ) {
						$errorList[$identifier] = array( );
					}
					
					$errorList[$identifier][] = $lineCount;
					$mapping[$identifier] = "AMBIGUOUS";
					
				} else {
					// VALID MAPPING
					$annotationDetails = current( $annotation );
					$mapping[$identifier] = $annotationDetails['gene_id'];
					
				}
				
				$lineCount++;
			}
			
			foreach( $warningList as $identifier => $lines ) {
				$messages[] = $this->generateError( "UNKNOWN", array( "identifier" => $identifier, "lines" => $lines ) );
			}
			
			foreach( $errorList as $identifier => $lines ) {
				$messages[] = $this->generateError( "AMBIGUOUS", array( "identifier" => $identifier, "lines" => $lines, "options" => $annotationSet[$identifier] ) );
			}
			
			if( sizeof( $errorList ) > 0 ) {
				$status = "ERROR";
			} else if( sizeof( $warningList ) > 0 ) {
				$status = "WARNING";
			} else {
				$status = "VALID";
			}
			
		}
		
		$errors = $this->twig->render( 'curation' . DS . 'error' . DS . 'CurationError.tpl', array( "ERRORS" => $messages ) );
		return array( "STATUS" => $status, "ERRORS" => $errors );
		
	}
	
	/**
	 * Fetch matching identifier passed in based on the
	 * additional parameters also passed
	 */
	 
	private function fetchMatchingIdentifiers( $identifier, $type, $taxa, $idType ) {
		
		switch( $type ) {
			
			case "1" : // Gene
				$idTypeQuery = $this->fetchIDTypeQuery( $idType );
				
				$stmt = $this->db->prepare( "SELECT gene_id FROM " . DB_QUICK . ".quick_identifiers WHERE quick_identifier_value=? AND organism_id=? " . $idTypeQuery . " GROUP BY gene_id" );
				
				$stmt->execute( array( $identifier, $taxa ) );
				
				$geneIDs = array( );
				while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
					$geneIDs[] = $row->gene_id;
				}
				
				if( sizeof( $geneIDs ) <= 0 ) {
					return array( );
				}
				
				return $this->fetchMatchingAnnotation( $geneIDs, $type );
			
		}
		
	}
	
	/**
	 * Fetch matching annotation by the gene ids that are passed in
	 */
	 
	private function fetchMatchingAnnotation( $geneIDs, $type ) {
		
		$matchingAnnotation = array( );
		
		switch( $type ) {
			
			case "1" : // Gene
			
				$params = implode( ",", array_fill( 0, sizeof( $geneIDs ), "?" ));
				$stmt = $this->db->prepare( "SELECT gene_id, systematic_name, official_symbol, aliases, organism_id, organism_official_name, organism_abbreviation, organism_strain FROM " . DB_QUICK . ".quick_annotation WHERE gene_id IN ( " . $params . " )" );
				$stmt->execute( $geneIDs );

				while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
					
					$annotation = array( );
					$annotation['gene_id'] = $row->gene_id;
					
					if( $row->aliases != "-" ) {
						$annotation['aliases'] = explode( "|", $row->aliases );
					}
					
					$annotation['primary_name'] = $row->official_symbol;
					$annotation['aliases'][] = $annotation['primary_name'];
					
					if( $row->systematic_name != "-" ) {
						$annotation['systematic_name'] = $row->systematic_name;
						$annotation['aliases'][] = $row->systematic_name;
					}
					
					$annotation['organism_id'] = $row->organism_id;
					$annotation['organism_official_name'] = $row->organism_official_name;
					$annotation['organism_abbreviation'] = $row->organism_abbreviation;
					
					if( $row->organism_strain != "-" ) {
						$annotation['organism_abbreviation'] .= " (" . $row->organism_strain . ")";
						$annotation['organism_strain'] = $row->organism_strain;
					}
					
					$matchingAnnotation[$row->gene_id] = $annotation;
				}
				
				break;
			
		}
		
		return $matchingAnnotation;
		
	}
	
	/**
	 * Fetch the id type specific portion of the lookup query
	 * based on the idType passed in
	 */
	 
	private function fetchIDTypeQuery( $idType ) {
		
		switch( strtoupper( $idType ) ) {
			
			case "ALL": return "";
			case "NAMES": return " AND quick_identifier_type IN ( 'SYSTEMATIC NAME', 'ORDERED LOCUS', 'OFFICIAL SYMBOL', 'SYNONYM' )";
			case "OFFICIAL":  return " AND quick_identifier_type IN ( 'OFFICIAL SYMBOL' )";
			case "SYNONYM": return " AND quick_identifier_type IN ( 'SYSTEMATIC NAME', 'SYNONYM', 'ORDERED LOCUS' )";
			case "ENSEMBL": return " AND quick_identifier_type IN ( 'ENSEMBL RNA', 'ENSEMBL PROTEIN', 'ENSEMBL GENE', 'ENSEMBL' )";
			case "UNIPROTKB": return " AND quick_identifier_type IN ( 'SWISS-PROT', 'UNIPROT', 'TREMBL', 'UNIPROT-ACCESSION' )";
			case "REFSEQ": return " AND quick_identifier_type IN ( 'REFSEQ-PROTEIN-ACCESSION', 'REFSEQ-PROTEIN-GI', 'REFSEQ-PROTEIN-ACCESSION-VERSIONED' )";
			case "WORMBASE": return " AND quick_identifier_type IN ( 'WORMBASE','WORMBASE-OLD' )";
			default: return " AND quick_identifier_type IN ( '" . strtoupper( $idType ) . "' )";
			
		}
		
	}
	
	/**
	 * Generate text for validation error messages
	 * based on the type of error
	 */
	 
	private function generateError( $errorType, $details = array( ) ) {
	
		switch( strtoupper( $errorType ) ) {
			
			case "REQUIRED" :
				return array( "class" => "danger", "message" => $this->blockName . " is a required field. It will not validate while no data has been entered in the provided fields." );
				
			case "AMBIGUOUS" :
				return array( "class" => "danger", "message" => "The identifier " . $details['identifier'] . " is AMBIGUOUS on lines " . implode( ", ", $details['lines'] ) . ". Options available are: A,B,C" );
				
			case "UNKNOWN" :
				return array( "class" => "warning", "message" => "The identifier " . $details['identifier'] . " is UNKNOWN on lines " . implode( ", ", $details['lines'] ) . ". If you believe it to not be a mistake, you can leave it and it will be added as an unknown participant. Alternatively, if you have a correction, enter it here to replace all occurrences above: " );

			default:
				return "An unknown error has occurred.";
			
		}
	
	}
	
}