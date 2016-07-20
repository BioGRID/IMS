<?php	


namespace IMS\app\classes\models;

/**
 * Curation Data
 * This set of functions is for handling the curation data
 * that will be retrieved before submission
 */

use \PDO;
use IMS\app\lib;
use IMS\app\classes\models;

class CurationData {
	
	private $blockID;
	private $type;
	private $data;
	
	public function __construct( $blockID ) {
		$this->blockID = $blockID;
		$this->type = "";
		$this->data = array( );
	}
	
	/**
	 * Set the type of curation data
	 */
	 
	public function setType( $type ) {
		$this->type = $type;
	}
	
	/**
	 * Get the curation type
	 */
	 
	public function getType( $type ) {
		return $this->type;
	}
	
	/**
	 * Set a curation subtype
	 */
	 
	public function addData( $subtype, $attributeTypeID, $data, $name, $required ) {
		
		if( $subtype == "" ) {
			$subtype = "attribute";
		}
		
		if( !isset( $this->data[strtoupper($subtype)] )) {
			$this->data[strtoupper($subtype)] = array( );
		}
		
		$this->data[strtoupper($subtype)][$attributeTypeID] = array( "ID" => $attributeTypeID, "DATA" => json_decode( $data ), "NAME" => $name, "REQUIRED" => $required );
		
	}
	
	/**
	 * Get data by passed in criteria
	 */
	 
	public function getData( $subtype, $attributeTypeID ) {
		
		if( $subtype == "" ) {
			$subtype = "attribute";
		}
		
		return $this->data[strtoupper($subtype)][$attributeTypeID];
	}
	
	
}