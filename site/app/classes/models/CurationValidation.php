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
	 * Take a set of alleles and validate them compared to a set of identifiers
	 */
	 
	public function validateAlleles( $alleles, $participantCount, &$results, $block, $curationCode ) {
		
		$messages = array( );
		$alleleSet = array( );
		
		foreach( $alleles as $alleleBoxNumber => $alleleList ) {
			$alleleList = trim( $alleleList );
			$alleleList = explode( PHP_EOL, $alleleList );
			
			if( sizeof( $alleleList ) != $participantCount ) {
				$messages[] = $this->generateError( "ALLELE_MISMATCH", array( "alleleBoxNumber" => $alleleBoxNumber ) );
			}
			
			// Remove Left Over NewLines
			$alleleSet[] = array_map( 'trim', $alleleList );
			
		}
		
		if( sizeof( $messages ) > 0 ) {
			$results["STATUS"] = "ERROR";
			$results["ERRORS"] = array_merge( $results['ERRORS'], $messages );
		} 
		
		// INSERT/UPDATE IT IN THE DATABASE
		$this->updateCurationEntries( $curationCode, $results["STATUS"], $block, $alleleSet, "allele", 0 );
		
		return $results;
		
	}
	
	/**
	 * Take a string of passed in identifiers and various search parameters, and
	 * attempt to map each one to a database identifier based on the string text
	 */
	
	public function validateIdentifiers( $identifiers, $role, $type, $taxa, $idType, $curationCode, $block, $isRequired = false ) {
		
		$messages = array( );
		$identifiers = trim( $identifiers );
		
		// Get already stored mapping info
		// to help save time on lookups
		
		$mapping = array( );
		$annotationSet = $this->fetchCurationEntry( $curationCode, $block, "participant_annotation", 0 );
		$termMap = $this->fetchCurationEntry( $curationCode, $block, "participant_terms", 0 );
		
		$counts = array( "VALID" => 0, "AMBIGUOUS" => 0, "UNKNOWN" => 0, "TOTAL" => 0 );
		
		if( $isRequired && strlen( $identifiers ) <= 0 ) {
			$status = "ERROR";
			$messages[] = $this->generateError( "REQUIRED" );
		} else if( !$isRequired && strlen( $identifiers ) <= 0 ) {
			$status = "VALID";
		} else {
			
			$identifiers = explode( PHP_EOL, $identifiers );
			$uniqueIdentifiers = array_unique( $identifiers );
			
			$toAnnotate = array( );
			foreach( $uniqueIdentifiers as $identifier ) {
				
				$identifier = strtoupper( trim( filter_var( $identifier, FILTER_SANITIZE_STRING )));
				$splitIdentifier = explode( "|", $identifier );
				
				if( sizeof( $splitIdentifier ) > 1 ) {
					$identifier = $splitIdentifier[1];
				}
				
				if( !isset( $termMap[$identifier] )) {
					$matchList = $this->fetchMatchingIdentifiers( $identifier, $type, $taxa, $idType );
					$termMap[$identifier] = $matchList;
					
					foreach( $matchList as $matchID => $matchInfo ) {
						if( !isset( $annotationSet[$matchID] ) ) {
							$annotationSet[$matchID] = array( );
							$toAnnotate[] = $matchID;
						}
					}
					
				}
				
				// If we passed in a BioGRID ID alternative, we need to make sure it's in the list of options
				// for that identifier in the termMap and also make sure we have annotation for it in the
				// annotation set.
				
				if( sizeof( $splitIdentifier ) > 1 ) {
					if( !isset( $termMap[$identifier][$splitIdentifier[0]] ) ) {
						$termMap[$identifier][$splitIdentifier[0]] = $splitIdentifier[0];
						if( !isset( $annotationSet[$splitIdentifier[0]] ) ) {
							$annotationSet[$splitIdentifier[0]] = array( );
							$toAnnotate[] = $splitIdentifier[0];
						}
					}
				}
				
			}
			
			$toAnnotate = array_chunk( $toAnnotate, 1000 );
			foreach( $toAnnotate as $idChunk ) {
				$this->fetchMatchingAnnotation( $idChunk, $type, $annotationSet );
			}
			
			$lineCount = 1;
			$errorList = array( );
			$warningList = array( );
			foreach( $identifiers as $identifier ) {
				$identifier = strtoupper( trim( filter_var( $identifier, FILTER_SANITIZE_STRING )));
				
				// If we have an identifier with a | in it
				// then it's a BIOGRID ID | STRING ID type of
				// identifier
				
				$splitIdentifier = explode( "|", $identifier );
				if( sizeof( $splitIdentifier ) > 1 ) {
					$identifier = $splitIdentifier[1];
				} 
				
				$termIDs = $termMap[$identifier];
				
				// If we specified a specific GENE ID to use, then here
				// we convert the annotation from a pack of ids to a 
				// specific set of annotation
				
				if( sizeof( $splitIdentifier ) > 1 && isset( $termIDs[$splitIdentifier[0]] ) ) {
					$termIDs = array( $splitIdentifier[0] );
				}
				
				if( sizeof( $termIDs ) <= 0 ) {
					
					// UNKNOWN
					if( !isset( $warningList[$identifier] ) ) {
						$warningList[$identifier] = array( );
					}
					
					$warningList[$identifier][] = $lineCount;
					$mapping[] = array( "id" => "", "key" => $identifier, "status" => "UNKNOWN", "annotation" => array( ) );
					$counts["UNKNOWN"]++;
					
				} else if( sizeof( $termIDs ) > 1 ) {
					
					// AMBIGUOUS
					if( !isset( $errorList[$identifier] ) ) {
						$errorList[$identifier] = array( );
					}
					
					$errorList[$identifier][] = $lineCount;
					$mapping[] = array( "id" => "", "key" => $identifier, "status" => "AMBIGUOUS", "annotation" => array( ) );
					$counts["AMBIGUOUS"]++;
					
				} else {
					// VALID MAPPING
					$termID = current( $termIDs );
					$mapping[] = array( "id" => $termID, "key" => $identifier, "status" => "VALID", "annotation" => $annotationSet[$termID] );
					$counts["VALID"]++;
				}
				
				$counts["TOTAL"]++;
				$lineCount++;
			}
			
			foreach( $errorList as $identifier => $lines ) {
				$termSet = $termMap[$identifier];
				$options = array( );
				foreach( $termSet as $termID => $termDetails ) {
					$options[$termID] = $annotationSet[$termID];
				}
				$messages[] = $this->generateError( "AMBIGUOUS", array( "identifier" => $identifier, "lines" => $lines, "options" => $options ) );
			}
			
			foreach( $warningList as $identifier => $lines ) {
				$messages[] = $this->generateError( "UNKNOWN", array( "identifier" => $identifier, "lines" => $lines ) );
			}
			
			if( sizeof( $errorList ) > 0 ) {
				$status = "ERROR";
			} else if( sizeof( $warningList ) > 0 ) {
				$status = "WARNING";
			} else {
				$status = "VALID";
			}
			
		}
		
		// Update Curation Database Entries
		$this->updateCurationEntries( $curationCode, $status, $block, $mapping, "participant", 0 );
		$this->updateCurationEntries( $curationCode, "NEW", $block, $annotationSet, "participant_annotation", 0 );
		$this->updateCurationEntries( $curationCode, "NEW", $block, $termMap, "participant_terms", 0 );
		
		return array( "STATUS" => $status, "ERRORS" => $messages, "COUNTS" => $counts );
		
	}
	
	/**
	 * Add entries to the curation table based on passed in parameters
	 * and the format of the table
	 */
	 
	private function updateCurationEntries( $code, $status, $block, $data, $type, $index ) {
		
		$stmt = $this->db->prepare( "SELECT curation_id FROM " . DB_IMS . ".curation WHERE curation_code=? AND curation_block=? AND curation_type=? AND curation_index=? LIMIT 1" );
		
		$stmt->execute( array( $code, $block, $type, $index ) );
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			// PERFORM UPDATE INSTEAD OF INSERT
			$stmt = $this->db->prepare( "UPDATE " . DB_IMS . ".curation SET curation_status=?, curation_data=? WHERE curation_id=?" );
			$stmt->execute( array( $status, json_encode( $data ), $row->curation_id ) );
		} else {
			// PERFORM INSERT
			$stmt = $this->db->prepare( "INSERT INTO " . DB_IMS . ".curation VALUES ( '0',?,?,?,?,?,?,NOW( ) )" );
			$stmt->execute( array( $code, $status, $block, json_encode( $data ), $type, $index ) );
		}
		
	}
	
	/**
	 * Fetch an existing curation entry out of the database
	 * if it exists, otherwise an empty array
	 */
	 
	private function fetchCurationEntry( $code, $block, $type, $index ) {
		
		$stmt = $this->db->prepare( "SELECT curation_data FROM " . DB_IMS . ".curation WHERE curation_code=? AND curation_block=? AND curation_type=? AND curation_index=? LIMIT 1" );
		
		$stmt->execute( array( $code, $block, $type, $index ) );
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			return json_decode( $row->curation_data, true );
		} 
		
		return array( );
		
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
					$geneIDs[$row->gene_id] = $row->gene_id;
				}
				
				if( sizeof( $geneIDs ) <= 0 ) {
					return array( );
				}
				
				return $geneIDs;//$this->fetchMatchingAnnotation( $geneIDs, $type );
			
		}
		
	}
	
	/**
	 * Fetch matching annotation by the gene ids that are passed in
	 */
	 
	private function fetchMatchingAnnotation( $geneIDs, $type, &$annotationSet ) {
		
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
					
					$annotationSet[$annotation['gene_id']] = $annotation;
				}
				
				break;
			
		}
		
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
	 * Process messages to create errors
	 */
	 
	public function processErrors( $messages ) {
		$errors = $this->twig->render( 'curation' . DS . 'error' . DS . 'CurationError.tpl', array( "ERRORS" => $messages ) );
		return $errors;
	}
	
	/**
	 * Generate text for validation error messages
	 * based on the type of error
	 */
	 
	public function generateError( $errorType, $details = array( ) ) {
	
		switch( strtoupper( $errorType ) ) {
			
			case "REQUIRED" :
				return array( "class" => "danger", "message" => $this->blockName . " is a required field. It will not validate while no data has been entered in the provided fields." );
				
			case "AMBIGUOUS" :
			
				$message = "The identifier <strong>" . $details['identifier'] . "</strong> is AMBIGUOUS on lines <strong>" . implode( ", ", $details['lines'] ) . "</strong>. <br />Ambiguities are: ";
				
				foreach( $details['options'] as $geneID => $annotation ) {
					$options[] = "<a href='http://thebiogrid.org/" . $annotation['gene_id'] . "' target='_blank'>" . $annotation['primary_name'] . "</a> (<a class='lineReplace' data-lines='" . implode( "|", $details['lines'] ) . "' data-value='BG_" . $annotation['gene_id'] . "'>" . "<i class='fa fa-lg fa-exchange'></i>" . "</a>)</a>";
				}
				
				$message .= implode( ", ", $options ) . "<div class='text-success statusMsg'></div>";
				return array( "class" => "danger", "message" => $message );
				
			case "UNKNOWN" :
			
				$message = "The identifier <strong>" . $details['identifier'] . "</strong> is UNKNOWN on lines <strong>" . implode( ", ", $details['lines'] ) . "</strong>. If you believe it to be valid, you can add it as an unknown participant. Alternatively, you can correct it, by entering an alternative identifier below. To enter a BioGRID ID, preface term with BG_ (example: BG_123456). Otherwise, any other term will be assumed to be the same ID Type as the others listed above...";
				
				$message .= "<div class='clearfix'><div class='input-group col-lg-6 col-md-6 col-sm-12 col-xs-12 marginTopSm'><input type='text' class=' form-control unknownReplaceField' placeholder='Enter Replacement Term' value='' /><span class='input-group-btn'><button data-lines='" . implode( "|", $details['lines'] ) . "' class='btn btn-success unknownReplaceSubmit' type='button'>Replace</button></span></div></div>";
				
				$message .= "<div class='text-success statusMsg'></div>";
				
				return array( "class" => "warning", "message" => $message );
				
			case "ALLELE_MISMATCH" :
				
				$message = "The number of alleles in <strong>Allele Box #" . $details['alleleBoxNumber'] . "</strong> does not match the number of participants specified. You must enter an allele for every participant or use a hyphen (-) if wanting no allele. Please correct this and try validation again.";
				
				return array( "class" => "danger", "message" => $message );
				
			case "NOCODE" :
				return array( "class" => "danger", "message" => "No curation code was passed to the validation script. Please try again!" );

			default:
				return array( "class" => "danger", "message" => "An unknown error has occurred." );
			
		}
	
	}
	
	/**
	 * Step through array row by row, and replace specific lines
	 * with a newly formatted entry
	 */
	 
	public function replaceParticipantLines( $participants, $lines, $value ) {
		
		$participants = explode( PHP_EOL, $participants );
		$lines = explode( "|", $lines );
		
		foreach( $lines as $line ) {
			$participant = $participants[$line-1];
			$participant = explode( "|", trim($participant) );
			
			$participantText = $participant[0];
			if( sizeof( $participant ) > 1 ) {
				$participantText = $participant[1];
			}
			
			$replaceVal = $value;
			if( strtoupper( substr( $replaceVal, 0, 3 ) ) === "BG_" ) {
				$replaceVal = substr( $replaceVal, 3 ) . "|" . $participantText;
			} 
			
			$participants[$line-1] = $replaceVal;
			
		}
		
		return implode( PHP_EOL, $participants );
	}
	
}