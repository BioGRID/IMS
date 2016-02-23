<?php

namespace IMS\app\classes\models;

/**
 * Elastic Search
 * Interface methods for connecting and both inputting and fetching
 * elastic search records.
 */

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
	 *
	 */
	 
	public function get( $params ) {
		return $this->client->get( $params );
	}
	
}

?>