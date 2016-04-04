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
		
		$this->blockName = $blockName;
	}
	
	/**
	 * Take a string of passed in identifiers and various search parameters, and
	 * attempt to map each one to a database identifier based on the string text
	 */
	
	public function validateIdentifiers( $identifiers, $role, $type, $taxa, $idType, $isRequired = false ) {
		
		$messages = array( );
		$identifiers = trim( $identifiers );
		
		if( $isRequired && strlen( $identifiers ) <= 0 ) {
			$status = "ERROR";
			$messages[] = $this->generateError( "REQUIRED" );
		} else if( !$isRequired && strlen( $identifiers ) <= 0 ) {
			$status = "VALID";
		} else {
			$identifiers = explode( PHP_EOL, $identifiers );
			$uniqueIdentifiers = array_unique( $identifiers );
			
			foreach( $uniqueIdentifiers as $identifier ) {
				$identifier = trim( filter_var( $str, FILTER_SANITIZE_STRING ));
				$annotationInfo = $this->fetchMatchingAnnotation( $identifier, $type, $taxa, $idType );
			}
		
		return array( "STATUS" => $status, "MESSAGES" => $messages );
		
	}
	
	/**
	 * Fetch matching identifier passed in based on the
	 * additional parameters also passed
	 */
	 
	private function fetchMatchingIdentifiers( $identifier, $type, $taxa, $idType ) {
		
		$type = strtolower( $type );
		
		if( $type == "gene" ) {
			
			$idTypeQuery = $this->fetchIDTypeQuery( $idType );
			
			$stmt = $this->db->prepare( "SELECT gene_id FROM " . DB_QUICK . ".quick_identifiers WHERE quick_identifier_value=? AND organism_id=? " . $idTypeQuery . " GROUP BY gene_id" );
			$stmt->execute( array( $identifier, $taxa ) );
			
			$datasetTypes = array( );
			while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
				$datasetTypes[$row->dataset_type_id] = $row->dataset_type_name;
			}
			
		}
		
		return $datasetTypes;
		
	}
	
	/**
	 * Fetch matching annotation by the gene ids that are passed in
	 */
	 
	private function fetchMatchingAnnotation( $geneIDs, $type ) {
		
		$matchingAnnotation = array( );
		$type = strtolower( $type );
		
		if( $type == "gene" ) {
			
			$params = str_repeat( "?", sizeof( $geneIDs ) );
			$stmt = $this->db->prepare( "SELECT gene_id, systematic_name, official_symbol, aliases, organism_id, organism_official_name, organism_abbreviation, organism_strain FROM " . DB_QUICK . ".quick_annotation WHERE gene_id IN ( " . $params . " )" );
			$stmt->execute( $geneIDs );

			while( $row = stmt->fetch( PDO::FETCH_OBJ ) ) {
				
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
	 
	private function generateError( $errorType ) {
	
		switch( strtoupper( $errorType ) ) {
			
			case "REQUIRED" :
				return $this->blockName . " is a required field. It will not validate while no data has been entered in the provided fields.";

			default:
				return "An unknown error has occurred.";
			
		}
	
	}
	
}