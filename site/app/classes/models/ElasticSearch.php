<?php

namespace IMS\app\classes\models;

/**
 * Elastic Search
 * Interface methods for connecting and both inputting and fetching
 * elastic search records.
 */
 
use \PDO;

class ElasticSearch {
	
	private $hosts;
	private $clientBuilder;
	private $client;
	
	function __construct( ) {
		$this->hosts = array( ES_HOST . ":" . ES_PORT );
		$this->clientBuilder = \Elasticsearch\ClientBuilder::create( );
		$this->clientBuilder->setHosts( $this->hosts );
		$this->client = $this->clientBuilder->build( );
	}
	
	/**
	 * Submit a search to elastic search and return the response
	 */
	 
	public function search( $params ) {
		return $this->client->search( $params );
	}
	
	/**
	 * Submit a get request to elastic search and return a response
	 */
	 
	public function get( $params ) {
		return $this->client->get( $params );
	}
	
	/** 
	 * Submit a search to elastic search and return a count response
	 */
	 
	public function getCount( $params ) {
		return $this->client->count( $params );
	}
	
	/**
	 * Index a new document into elastic search
	 */
	 
	public function index( $params ) {
		return $this->client->index( $params );
	}
	
	/**
	 * Update a document into elastic search
	 */
	 
	public function update( $params ) {
		return $this->client->update( $params );
	}
	
	/**
	 * Build a dataset document using the structure required by our search system
	 */
	 
	public function buildDatasetDocument( $datasetID, $intCount, $datasetStats ) {
		
		$ES_INDEX = "datasets";
		$ES_TYPE = "dataset";
		
		$params = array( 
			"index" => $ES_INDEX,
			"type" => $ES_TYPE,
			"id" => $datasetID
		);
			
		$document['dataset_id'] = $datasetID;
		$document['dataset_size'] = $intCount;
		$document['interactions'] = $this->buildFullInteractionStats( $datasetID, $datasetStats );
		
		$params["body"] = $document;
		
		return $params;
		
	}
	
	/**
	 * Fetch the total number of interactions, regardless of status for a total dataset size
	 */
	 
	private function buildFullInteractionStats( $datasetID, $datasetStats ) {
		
		$ES_INDEX = "interactions";
		$ES_TYPE = "interaction";
		
		$interactionStats = array( );
		
		foreach( $datasetStats as $stats ) {
			
			$params = array(
				"index" => $ES_INDEX,
				"type" => $ES_TYPE,
				"body" => array(
					"query" => array( 
						"bool" => array( 
							"must" => array( 
								array( "match" => array( "dataset_id" => $datasetID )),
								array( "match" => array( "interaction_type_id" => $stats['interaction_type_id'] )),
								array( "match" => array( "history_status" => "activated" ))
							)
						)
					)
				)
			);
			
			$response = $this->getCount( $params );
			$stats['activated_count'] = $response['count'];
			
			$params = array(
				"index" => $ES_INDEX,
				"type" => $ES_TYPE,
				"body" => array(
					"query" => array( 
						"bool" => array( 
							"must" => array( 
								array( "match" => array( "dataset_id" => $datasetID )),
								array( "match" => array( "interaction_type_id" => $stats['interaction_type_id'] )),
								array( "match" => array( "history_status" => "disabled" ))
							)
						)
					)
				)
			);
			
			$response = $this->getCount( $params );
			$stats['disabled_count'] = $response['count'];
			
			$interactionStats[] = $stats;
			
		}
		
		return $interactionStats;
		
	}
	
}

?>