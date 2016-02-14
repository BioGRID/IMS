<?php

namespace IMS\app\classes\models;

/**
 * Lookups
 * This class is for creating quick lookup hashes
 * that can be used to speed up various operations
 * and limit SQL connections required.
 */

use \PDO;
 
class Lookups {

	private $db;

	public function __construct( ) {
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	}
	
	/**
	 * Build a hash that maps user ids to a firstname/lastname combo
	 * so we can quickly get readable user names without extra DB lookups
	 */
	 
	public function buildUserNameHash( ) {
		
		$mappingHash = array( );
		$stmt = $this->db->prepare( "SELECT user_id, user_firstname, user_lastname FROM " . DB_IMS . ".users" );
		$stmt->execute( );
		
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$mappingHash[$row->user_id] = $row->user_firstname . " " . $row->user_lastname;
		}

		return $mappingHash;
		
	}
	
	/**
	 * Build a quick lookup hash of interaction types for rapid mapping
	 * when fetching datasets in bulk
	 */
	 
	public function buildInteractionTypeHash( ) {
		
		$mappingHash = array( );
		$stmt = $this->db->prepare( "SELECT interaction_type_id, interaction_type_name FROM " . DB_IMS . ".interaction_types" );
		$stmt->execute( );
		
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$mappingHash[$row->interaction_type_id] = $row->interaction_type_name;
		}

		return $mappingHash;
		
	}
	
	/**
	 * Build a quick lookup hash of history operations for rapid mapping
	 * when fetching datasets in bulk
	 */
	 
	public function buildHistoryOperationsHash( ) {
		
		$mappingHash = array( );
		$stmt = $this->db->prepare( "SELECT history_operation_id, history_operation_name FROM " . DB_IMS . ".history_operations" );
		$stmt->execute( );
		
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$mappingHash[$row->history_operation_id] = $row->history_operation_name;
		}

		return $mappingHash;
		
	}
	
	/**
	 * Build a quick lookup hash of participant types for rapid mapping
	 * when fetching datasets in bulk
	 */
	 
	public function buildParticipantTypesHash( ) {
		
		$mappingHash = array( );
		$stmt = $this->db->prepare( "SELECT participant_type_id, participant_type_name FROM " . DB_IMS . ".participant_types" );
		$stmt->execute( );
		
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$mappingHash[$row->participant_type_id] = $row->participant_type_name;
		}

		return $mappingHash;
		
	}
	
	/**
	 * Build a quick lookup hash of participant roles for rapid mapping
	 * when fetching datasets in bulk
	 */
	 
	public function buildParticipantRoleHash( ) {
		
		$mappingHash = array( );
		$stmt = $this->db->prepare( "SELECT participant_role_id, participant_role_name FROM " . DB_IMS . ".participant_roles" );
		$stmt->execute( );
		
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$mappingHash[$row->participant_role_id] = $row->participant_role_name;
		}

		return $mappingHash;
		
	}
	
	/**
	 * Build a quick lookup hash of participant ids to types for rapid mapping
	 * when fetching datasets in bulk
	 */
	 
	public function buildParticipantTypeMappingHash( ) {
		
		$mappingHash = array( );
		$stmt = $this->db->prepare( "SELECT participant_id, participant_type_id FROM " . DB_IMS . ".participants" );
		$stmt->execute( );
		
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$mappingHash[$row->participant_id] = $row->participant_type_id;
		}

		return $mappingHash;
		
	}
	
	/**
	 * Build a quick lookup hash of attribute types for rapid mapping
	 * when fetching datasets in bulk
	 */
	 
	public function buildAttributeTypeHASH( ) {
		
		$mappingHash = array( );
		$stmt = $this->db->prepare( "SELECT o.attribute_type_id, o.attribute_type_name, o.attribute_type_shortcode, o.attribute_type_category_id, p.attribute_type_category_name FROM " . DB_IMS . ".attribute_types o LEFT JOIN " . DB_IMS . ".attribute_type_categories p ON (o.attribute_type_category_id=p.attribute_type_category_id)" );
		$stmt->execute( );
		
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$mappingHash[$row->attribute_type_id] = $row;
		}

		return $mappingHash;
		
	}
	
	/**
	 * Build a quick lookup hash of ontology term details for rapid mapping
	 * when fetching datasets in bulk
	 */
	 
	public function buildAttributeOntologyTermHASH( ) {
		
		$mappingHash = array( );
		$stmt = $this->db->prepare( "SELECT attribute_value FROM " . DB_IMS . ".attributes WHERE attribute_type_id IN ( SELECT attribute_type_id FROM " . DB_IMS . ".attribute_types WHERE attribute_type_category_id='1' ) GROUP BY attribute_value" );
		$stmt->execute( );
		
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$mappingHash[$row->attribute_value] = $this->fetchOntologyTermInfo( $row->attribute_value );
		}

		return $mappingHash;
		
	}
	
	/**
	 * Grab information on a single ontology term by
	 * its ontology id
	 */
	 
	public function fetchOntologyTermInfo( $ontologyTermID ) {
		
		$stmt = $this->db->prepare( "SELECT o.ontology_term_id, o.ontology_term_official_id, o.ontology_term_name, o.ontology_id, p.ontology_name FROM " . DB_IMS .".ontology_terms o LEFT JOIN " . DB_IMS . ".ontologies p ON (o.ontology_id=p.ontology_id) WHERE o.ontology_term_id=? LIMIT 1" );
		$stmt->execute( array( $ontologyTermID ) );
		
		if( $stmt->rowCount( ) > 0 ) {
			$row = $stmt->fetch( PDO::FETCH_OBJ );
			return $row;
		}
		
		return array( );
		
	}
	
	/**
	 * Build a quick lookup of annotation for participants
	 */
	 
	public function buildAnnotationHash( ) {
		
		$mappingHash = array( );
		$stmt = $this->db->prepare( "SELECT participant_id, participant_value, participant_type_id FROM " . DB_IMS . ".participants" );
		$stmt->execute( );
		
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			
			switch( $row->participant_type_id ) {
				
				case "1" :
					$annotation = $this->fetchGeneAnnotation( $row->participant_value );
					$mappingHash[$row->participant_id] = $annotation;
					break;
					
				case "2" :
					$annotation = $this->fetchUniprotAnnotation( $row->participant_value );
					$mappingHash[$row->participant_id] = $annotation;
					break;
					
				case "3" :
					$annotation = $this->fetchRefseqAnnotation( $row->participant_value );
					$mappingHash[$row->participant_id] = $annotation;
					break;
					
				case "4" :
					$annotation = $this->fetchChemicalAnnotation( $row->participant_value );
					$mappingHash[$row->participant_id] = $annotation;
					break;
					
				case "5" :
					$annotation = $this->fetchUnknownAnnotation( $row->participant_value );
					$mappingHash[$row->participant_id] = $annotation;
					break;
				
			}
		}
				
		return $mappingHash;
	
	}
				
	/**
	 * Fetches Gene Annotation from the Quick Lookup Table
	 */
				
	public function fetchGeneAnnotation( $geneID ) {
	
		$stmt = $this->db->prepare( "SELECT official_symbol, aliases, systematic_name, organism_id, organism_official_name, organism_abbreviation, organism_strain FROM " . DB_QUICK . ".quick_annotation WHERE gene_id=? LIMIT 1" );
		$stmt->execute( array( $geneID ) );
		
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
		
			if( $row->aliases != "-" ) {
				$row->aliases = explode( "|", $row->aliases );
			} else {
				$row->aliases = array( );
			}
				
			return $row;
		}
		
		return false;
		
	}
	
	/**
	 * Fetches Uniprot Sequence Annotation from the Quick Lookup Table
	 */
		
	public function fetchUniprotAnnotation( $uniprotID ) {
		
		$stmt = $this->db->prepare( "SELECT uniprot_identifier_value, uniprot_aliases, uniprot_name, uniprot_source, organism_id, organism_official_name, organism_abbreviation, organism_strain FROM " . DB_QUICK . ".quick_uniprot WHERE uniprot_id=? LIMIT 1" );
		$stmt->execute( array( $uniprotID ) );
		
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
		
			if( $row->uniprot_aliases != "-" ) {
				$row->uniprot_aliases = explode( "|", $row->uniprot_aliases );
			} else {
				$row->uniprot_aliases = array( );
			}
				
			return $row;
			
		}
			
		return false;
		
	}
	
	/**
	 * Fetches REFSEQ Sequence Annotation from the Quick Lookup Table
	 */
		
	public function fetchRefseqAnnotation( $refseqID ) {
		
		$stmt = $this->db->prepare( "SELECT refseq_accession, refseq_gi, refseq_aliases, refseq_uniprot_aliases, organism_id, organism_official_name, organism_abbreviation, organism_strain, gene_id FROM " . DB_QUICK . ".quick_refseq WHERE refseq_id=? LIMIT 1" );
		$stmt->execute( array( $refseqID ) );
		
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
		
			if( $row->refseq_aliases != "-" ) {
				$row->refseq_aliases = explode( "|", $row->refseq_aliases );
			} else {
				$row->refseq_aliases = array( );
			}
				
			if( $row->refseq_uniprot_aliases != "-" ) {
				$row->refseq_uniprot_aliases = explode( "|", $row->refseq_uniprot_aliases );
			} else {
				$row->refseq_uniprot_aliases = array( );
			}
				
			return $row;
			
		}
			
		return false;
		
	}
	
	/**
	 * Fetches Chemical Annotation from the Quick Lookup Table
	 */
		
	public function fetchChemicalAnnotation( $chemicalID ) {
		
		$stmt = $this->db->prepare( "SELECT chemical_name, chemical_synonyms, chemical_brands, chemical_formula, chemical_type, chemical_source FROM " . DB_QUICK . ".quick_chemicals WHERE chemical_id=? LIMIT 1" );
		$stmt->execute( array( $chemicalID ) );
		
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			
			if( $row->chemical_synonyms != "-" ) {
				$row->chemical_synonyms = explode( "|", $row->chemical_synonyms );
			} else {
				$row->chemical_synonyms = array( );
			}
				
			if( $row->chemical_brands != "-" ) {
				$row->chemical_brands = explode( "|", $row->chemical_brands );
			} else {
				$row->chemical_brands = array( );
			}
				
			return $row;
		
		}
			
		return false;
		
	}
	
	/**
	 * Fetches Unknown Annotation from the Lookup Table
	 */
		
	public function fetchUnknownAnnotation( $unknownID ) {
		
		$stmt = $this->db->prepare( "SELECT unknown_participant_value, organism_id FROM " . DB_IMS . ".unknown_participants WHERE unknown_participant_id=? LIMIT 1" );
		$stmt->execute( array( $unknownID ) );
		
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$organismInfo = $this->fetchOrganismInfoFromOrganismID( $row->organism_id );
			$row->organism_official_name = $organismInfo->organism_official_name;
			$row->organism_abbreviation = $organismInfo->organism_abbreviation;
			$row->organism_strain = $organismInfo->organism_strain;
			
			return $row;
		}
		
		return false;
		
	}
	
	/**
	 * Fetches Unknown Annotation from the Lookup Table
	 */
		
	public function fetchOrganismInfoFromOrganismID( $organismID ) {
		
		$stmt = $this->db->prepare( "SELECT organism_official_name, organism_abbreviation, organism_strain FROM " . DB_QUICK . ".quick_organisms WHERE organism_id=? LIMIT 1" );
		$stmt->execute( array( $organismID ) );
		
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			return $row;
		} 
		
		
		return false;
	
	}
	
}

?>