<?php

namespace IMS\app\classes\models;

/**
 * Datasets
 * This model class is for handling data processing for new
 * and existing datasets stored in the database.
 */

use \PDO;
use IMS\app\classes\utilities\PubmedParser;
 
class Datasets {

	private $db;
	private $datasetTypes;

	public function __construct( ) {
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		// Setup Quick Lookup Hashes
		$this->datasetTypes = $this->buildDatasetTypeHash( );
	}
	
	/**
	 * Build a quick lookup hash of dataset types for rapid mapping
	 * when fetching datasets
	 */
	 
	private function buildDatasetTypeHash( ) {
	
		$stmt = $this->db->prepare( "SELECT dataset_type_id, dataset_type_name FROM " . DB_IMS . ".dataset_types" );
		$stmt->execute( );
		
		$datasetTypes = array( );
		while( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$datasetTypes[$row->dataset_type_id] = $row->dataset_type_name;
		}
		
		return $datasetTypes;
	
	}
	
	/**
	 * Grab a dataset by pubmed id and if it doesn't exist
	 * add it to the database
	 */
	
	public function fetchDatasetByPubmedID( $pubmedID ) {
	
		$stmt = $this->db->prepare( "SELECT dataset_id FROM " . DB_IMS . ".datasets WHERE dataset_type_id = '1' AND dataset_source_id=? LIMIT 1" );
		$stmt->execute( array( $pubmedID ) );
		
		if( $stmt->rowCount( ) > 0 ) {
			$row = $stmt->fetch( PDO::FETCH_OBJ );
			return $this->fetchDataset( $row->dataset_id );
		} else {
			
			// Insert the Pubmed with Annotation (if possible)
			$pubmedParser = new PubmedParser( );
			if( $pubmedData = $pubmedParser->parse( $pubmedID ) ) {
				// Create a dataset to represent it
				if( $pubmedID = $this->addPubmed( $pubmedData ) ) {
					if( $datasetID = $this->addDataset( $pubmedID, "1", "public" ) ) {
						return $this->fetchDataset( $datasetID );
					}
				}
			}
			
		}
		
		return false;
		
	}
	
	/**
	 * Grab a prepub dataset by prepub dataset id, if it doesn't exist
	 * just return false because prepub datasets must be entered manually
	 * via an admin form.
	 */
	 
	public function fetchDatasetByPrepubID( $prepubID ) {
	
		$stmt = $this->db->prepare( "SELECT dataset_id FROM " . DB_IMS . ".datasets WHERE dataset_type_id = '2' AND dataset_source_id=? LIMIT 1" );
		$stmt->execute( array( $prepubID ) );
		
		if( $stmt->rowCount( ) > 0 ) {
			$row = $stmt->fetch( PDO::FETCH_OBJ );
			return $this->fetchDataset( $row->dataset_id );
		} 
		
		return false;
		
	}
	
	/** 
	 * Add a new pubmed to the database or return the pubmed ID
	 * of an existing one.
	 */
	
	private function addPubmed( $pubmedData ) {
		
		// Check to see if pubmed already exists in the database
		$stmt = $this->db->prepare( "SELECT pubmed_id FROM " . DB_IMS . ".pubmed WHERE pubmed_id=? LIMIT 1" );
		$stmt->execute( array( $pubmedData['PUBMED_ID'] ) );
		
		// If it does, return it
		if( $stmt->rowCount( ) > 0 ) {
			return $pubmedData['PUBMED_ID'];
		}
		
		// If it doesn't, insert it as a new record
		$this->db->beginTransaction( );
		
		try {
			
			$stmt = $this->db->prepare( "INSERT INTO " . DB_IMS . ".pubmed (pubmed_id, pubmed_title, pubmed_abstract, pubmed_fulltext, pubmed_author_short, pubmed_author_list, pubmed_author_full, pubmed_volume, pubmed_issue, pubmed_date, pubmed_journal, pubmed_journal_short, pubmed_pagination, pubmed_affiliations, pubmed_meshterms, pubmed_pmcid, pubmed_doi, pubmed_article_ids, pubmed_status, pubmed_addeddate, pubmed_lastupdated, pubmed_isannotated) VALUES ( :PUBMED_ID, :TITLE, :ABSTRACT, '-', :AUTHOR_SHORT, :AUTHORS_LIST, :AUTHORS, :VOLUME, :ISSUE, :PUBDATE, :JOURNAL, :JOURNAL_SHORT, :PAGINATION, :AFFILIATIONS, :MESH, :PMCID, :DOI, :ARTICLE_IDS, :STATUS, NOW( ), NOW( ), '1' )" );
			
			$stmt->execute( array(  
				":PUBMED_ID" => $pubmedData['PUBMED_ID'],
				":TITLE" => $pubmedData['TITLE'],
				":ABSTRACT" => $pubmedData['ABSTRACT'],
				":AUTHOR_SHORT" => $pubmedData['AUTHOR_SHORT'],
				":AUTHORS_LIST" => $pubmedData['AUTHORS_LIST'],
				":AUTHORS" => $pubmedData['AUTHORS'],
				":VOLUME" => $pubmedData['VOLUME'],
				":ISSUE" => $pubmedData['ISSUE'],
				":PUBDATE" => $pubmedData['PUBDATE'],
				":JOURNAL" => $pubmedData['JOURNAL'],
				":JOURNAL_SHORT" => $pubmedData['JOURNAL_SHORT'],
				":PAGINATION" => $pubmedData['PAGINATION'],
				":AFFILIATIONS" => $pubmedData['AFFILIATIONS'],
				":MESH" => $pubmedData['MESH'],
				":PMCID" => $pubmedData['PMCID'],
				":DOI" => $pubmedData['DOI'],
				":ARTICLE_IDS" => $pubmedData['ARTICLE_IDS'],
				":STATUS" => $pubmedData['STATUS']
			));
			
			$this->db->commit( );
			
		} catch( PDOException $e ) {
			$this->db->rollback( );
			return false;
		}
		
		return $pubmedData['PUBMED_ID'];
		
	}
	
	/** 
	 * Add a dataset mapping a source id and availability
	 * as well as updating the history.
	 */
	 
	private function addDataset( $sourceID, $sourceType, $availability ) {
		
		// Check if a dataset already exists for this source id and type
		$stmt = $this->db->prepare( "SELECT dataset_id FROM " . DB_IMS . ".datasets WHERE dataset_source_id=? AND dataset_type_id=?" );
		$stmt->execute( array( $sourceID, $sourceType ) );
		
		// Dataset already exists, return it
		if( $stmt->rowCount( ) > 0 ) {
			$row = $stmt->fetch( PDO::FETCH_OBJ );
			return $row->dataset_id;
		}
		
		// If it doesn't, insert it as a new one
		$this->db->beginTransaction( );
		
		try {
			
			$stmt = $this->db->prepare( "INSERT INTO " . DB_IMS . ".datasets (dataset_id, dataset_source_id, dataset_type_id, dataset_source_id_replacement, dataset_type_id_replacement, dataset_availability, dataset_addeddate, dataset_status) VALUES ( '0', ?, ?, '0', '0', ?, NOW( ), 'active' )" );
			$stmt->execute( array( $sourceID, $sourceType, $availability ) );
			
			$datasetID = $this->db->lastInsertId( );
			
			$this->addHistory( $datasetID, "ACTIVATED", "New Dataset" );
			
			$this->db->commit( );
		
			return $datasetID;
		
		} catch( PDOException $e ) {
			$this->db->rollback( );
			return false;
		}
		
	}
	
	/**
	 * Add an entry to the dataset_history table with specific parameters
	 * passed in such as modification_type and comment
	 */
	 
	private function addHistory( $datasetID, $modType, $comment = "-" ) {
		$stmt = $this->db->prepare( "INSERT INTO " . DB_IMS . ".dataset_history (dataset_history_id, modification_type, dataset_id, user_id, dataset_history_comment, dataset_history_addeddate) VALUES ( '0', ?, ?, ?, ?, NOW( ) )" );
		$stmt->execute( array( $modType, $datasetID, $_SESSION[SESSION_NAME]['ID'], $comment ) );
	}
	
	/**
	 * Fetch a dataset by the dataset id
	 * return an array of data formatted for view display
	 */
	 
	private function fetchDataset( $datasetID ) {
		
		$stmt = $this->db->prepare( "SELECT * FROM " . DB_IMS . ".datasets WHERE dataset_id=? LIMIT 1" );
		$stmt->execute( array( $datasetID ) );
		
		if( $stmt->rowCount( ) > 0 ) {
		
			$row = $stmt->fetch( PDO::FETCH_OBJ );
			
			$dataset = array( 
				"ID" => $row->dataset_id, 
				"SOURCE_ID" => $row->dataset_source_id,
				"TYPE" => $row->dataset_type_id,
				"TYPE_NAME" => "-",
				"REPLACEMENT_ID" => $row->dataset_source_id_replacement,
				"REPLACEMENT_TYPE_ID" => $row->dataset_type_id_replacement,
				"REPLACEMENT_TYPE_NAME" => "-",
				"AVAILABILITY" => $row->dataset_availability,
				"AVAILABILITY_LABEL" => $this->fetchAvailabilityLabel( $row->dataset_availability ),
				"STATUS" => $row->dataset_status,
				"ANNOTATION" => array( )
			);
			
			if( isset( $this->datasetTypes[$row->dataset_type_id] ) ) {
				$dataset["TYPE_NAME"] = $this->datasetTypes[$row->dataset_type_id];
			}
			
			if( isset( $this->datasetTypes[$row->dataset_type_id_replacement] ) ) {
				$dataset["REPLACEMENT_TYPE_NAME"] = $this->datasetTypes[$row->dataset_type_id_replacement];
			}
			
			if( $dataset['TYPE'] == "1" ) {
				// PUBMED
				$dataset["ANNOTATION"] = $this->fetchPubmedAnnotation( $dataset["SOURCE_ID"] );
			} else if( $dataset['TYPE'] == "2" ) {
				// PREPUB
				$dataset["ANNOTATION"] = $this->fetchPrepubAnnotation( $dataset["SOURCE_ID"] );
			}
			
			return $dataset;
			
		} 
		
		return false;
		
	}
	
	/**
	 * Build an associative array of all the annotation details
	 * for a publication.
	 */
	 
	private function fetchPubmedAnnotation( $pubmedID ) {
		
		$stmt = $this->db->prepare( "SELECT * FROM " . DB_IMS . ".pubmed WHERE pubmed_id=? LIMIT 1" );
		$stmt->execute( array( $pubmedID ) );
		
		if( $stmt->rowCount( ) <= 0 ) {
			return array( );
		}
		
		$row = $stmt->fetch( PDO::FETCH_OBJ );
		
		$annotation = array( 
			"ID" => $row->pubmed_id,
			"TITLE" => $row->pubmed_title,
			"ABSTRACT" => $row->pubmed_abstract,
			"FULLTEXT" => $row->pubmed_fulltext,
			"AUTHOR_SHORT" => $row->pubmed_author_short,
			"AUTHOR_LIST" => $row->pubmed_author_list,
			"AUTHOR_FULL" => json_decode( $row->pubmed_author_full, true ),
			"VOLUME" => $row->pubmed_volume,
			"ISSUE" => $row->pubmed_issue,
			"PUBDATE" => $row->pubmed_date,
			"JOURNAL" => $row->pubmed_journal,
			"JOURNAL_SHORT" => $row->pubmed_journal_short,
			"PAGINATION" => $row->pubmed_pagination,
			"AFFILIATIONS" => json_decode( $row->pubmed_affiliations, true ),
			"MESH" => json_decode( $row->pubmed_meshterms, true ),
			"PMCID" => $row->pubmed_pmcid,
			"DOI" => $row->pubmed_doi,
			"ARTICLE_IDS" => json_decode( $row->pubmed_article_ids, true ),
			"STATUS" => $row->pubmed_status,
			"ADDEDDATE" => $row->pubmed_addeddate,
			"LASTUPDATED" => $row->pubmed_lastupdated,
			"IS_ANNOTATED" => $row->pubmed_isannotated
		);
		
		return $annotation;
		
	}
	
	/**
	 * Build an associative array of all the annotation details
	 * for a pre-publication dataset.
	 */
	 
	private function fetchPrepubAnnotation( $prepubID ) {
		
		$stmt = $this->db->prepare( "SELECT * FROM " . DB_IMS . ".prepub WHERE prepub_id=? LIMIT 1" );
		$stmt->execute( array( $prepubID ) );
		
		if( $stmt->rowCount( ) <= 0 ) {
			return array( );
		}
		
		$row = $stmt->fetch( PDO::FETCH_OBJ );
		
		$annotation = array( 
			"ID" => $row->prepub_id,
			"TITLE" => $row->prepub_title,
			"ABSTRACT" => $row->prepub_abstract,
			"AUTHOR_SHORT" => $row->prepub_author_short,
			"AUTHOR_LIST" => $row->prepub_author_list,
			"AUTHOR_FULL" => json_decode( $row->prepub_author_full, true ),
			"PUBDATE" => $row->prepub_date,
			"AFFILIATIONS" => json_decode( $row->prepub_affiliations, true ),
			"URL" => $row->prepub_url,
			"PUBMED_ID" => $row->prepub_pubmed_id,
			"STATUS" => $row->prepub_status,
			"ADDEDDATE" => $row->prepub_addeddate,
			"LASTUPDATED" => $row->prepub_lastupdated
		);
		
		return $annotation;
		
	}
	
	/**
	 * Fetch Availability Label based on availability setting
	 */
	 
	public function fetchAvailabilityLabel( $availability ) {
		$availabilityLabel = "success";
		if( $availability == "private" ) {
			$availabilityLabel = "danger";
		} else if( $availability == "website-only" ) {
			$availabilityLabel = "warning";
		}
		
		return $availabilityLabel;
	}
	
	/**
	 * Change availability of a dataset to a new value
	 */
	 
	public function changeAvailability( $datasetID, $availability ) {
	
		$stmt = $this->db->prepare( "UPDATE " . DB_IMS . ".datasets SET dataset_availability=? WHERE dataset_id=?" );
		$stmt->execute( array( $availability, $datasetID ) );
		
		$this->addHistory( $datasetID, 'UPDATED', "Changed Availability Setting to " . $availability );
	
	}
	
}

?>