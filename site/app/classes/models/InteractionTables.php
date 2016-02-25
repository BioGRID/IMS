<?php

namespace IMS\app\classes\models;

/**
 * Interaction Tables
 * Methods for creating and outputting interaction tables with
 * customized options when fetching interaction data.
 */
 
use IMS\app\classes\models\ElasticSearch;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

class InteractionTables {
	
	private $es;
	private $log;
	
	function __construct( ) {
		$this->es = new ElasticSearch( );
		$this->log = new Logger( 'Load Interactions Log' );
		$this->log->pushHandler( new StreamHandler( __DIR__ . '/../../../www/logs/LoadInteractions.log', Logger::DEBUG ));
	}

	/**
	 * Fetch interaction data for the table with specific parameters passed in
	 * to manipulate exactly what is shown to the user.
	 */
	 
	public function fetchInteractions( $datasetID, $typeID, $status, $searchTerm, $start, $length ) {
		$params = $this->fetchParams( $datasetID, $typeID, $status, $searchTerm, $start, $length );
		return $this->es->search( $params );
	}
	
	/**
	 * Grab columns definitions from the database for the type of
	 * interaction whose data we are going to display
	 */
	 
	public function fetchColumns( $typeID ) {
		
		$columns = array( );
		$columns[0] = array( 
			"title" => "",
			"orderable" => false,
			"sortable" => false,
			"className" => "text-center"
		);
		
		$columns[1] = array( "title" => "Bait" );
		$columns[2] = array( "title" => "Prey" );
		$columns[3] = array( "title" => "Bait Org" );
		$columns[4] = array( "title" => "Prey Org" );
		$columns[5] = array( "title" => "System" );
		$columns[6] = array( "title" => "User" );
		$columns[7] = array( "title" => "Date" );
		$columns[8] = array( "title" => "Attributes" );
		$columns[9] = array( "title" => "State" );
		
		return $columns;
		
	}

	/**
	 * Fetch the column headers to be displayed based on the column structure for this 
	 * interaction type
	 */
	 
	public function fetchColumnHeaderDefinitions( $columns ) {
		
		$columns = array( );
		foreach( $columns as $columnIndex => $columnInfo ) {
			
			$column = array( );
			$column["title"] = $columnInfo["title"];
			$column["data"] = "COLUMN_" . $columnIndex;
			
			if(isset( $column['orderable'] )) {
				$column["orderable"] = $columnInfo["orderable"];
			}
			
			if(isset( $columnInfo["sortable"] )) {
				$column["sortable"] = $columnInfo["sortable"];
			}
			
			if(isset( $columnInfo["className"] )) {
				$column["className"] = $columnInfo["className"];
			}
			
			$columns[] = $column;

		}
		
	}
	
	/**
	 * Fetch elastic search formatted search query based on the input parameters
	 * passed in to this function.
	 */
	 
	private function fetchParams( $datasetID, $typeID, $status, $searchTerm, $start, $length ) {
		
		$params = array(
			"index" => "interactions",
			"type" => "interaction",
			"body" => array(
				"from" => $start,
				"size" => $length
			)
		);
		
		$params["body"]["query"] = $this->fetchGlobalQueryParams( $datasetID, $typeID, $status, $searchTerm );
		$params["body"]["sort"] = $this->fetchSortParams( );
		
		return $params;
	
	}
	
	/**
	 * Fetch elastic search formatted query params based on the type of search being performed
	 * and the input parameters specificing what kind of searching is to be applied.
	 */
	 
	private function fetchGlobalQueryParams( $datasetID, $typeID, $status, $searchTerm = "" ) {
		
		// All queries are restricted to a specific 
		// dataset (publication) a specific type (like Binary/Complex/etc.)
		// and a specific status (activated/deactivated)
		
		$queryparams = array( "bool" => array( "must" => array( ) ) );
		$queryParams["bool"]["must"][] = array( "match" => array( "dataset_id" => $datasetID ));
		$queryParams["bool"]["must"][] = array( "match" => array( "interaction_type_id" => $typeID ));
		$queryParams["bool"]["must"][] = array( "match" => array( "history_status" => $status ));
		
		if( strlen( $searchTerm ) > 0 ) {
			$queryParams["bool"]["must"][] = array( "match" => array( "_all" => $searchTerm ) );
		}
		
		return $queryParams;
		
	}
	
	/** 
	 * Fetch elastic search formatted sort query based on the type of search being performed
	 * and the input parameters specificing what kind of sorting needs to be applied.
	 */
	 
	private function fetchSortParams( ) {
		
		// Default sorting is to sort the interactions from newest
		// to oldest based on the history_date document entry
		
		$sortParams = array( 
			"history_date" => array( "order" => "desc" )
		);
		
		return $sortParams;
	}
	
}