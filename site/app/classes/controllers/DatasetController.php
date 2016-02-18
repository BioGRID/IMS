<?php


namespace IMS\app\classes\controllers;

/**
 * Dataset Controller
 * This controller handles the processing of the dataset curation page.
 */
 
use IMS\app\lib;
use IMS\app\classes\models;

class DatasetController extends lib\Controller {
	
	public function __construct( $twig ) {
		parent::__construct( $twig );
		
		$addonJS = array( );
		$addonJS[] = "jquery.webui-popover.min.js";
		$addonJS[] = "jquery.dataTables.js";
		$addonJS[] = "dataTables.bootstrap.js";
		$addonJS[] = "ims-datasets.js";
		
		$addonCSS = array( );
		$addonCSS[] = "jquery.webui-popover.min.css";
		$addonCSS[] = "dataTables.bootstrap.css";
		
		$this->headerParams->set( 'ADDON_CSS', $addonCSS );
		$this->footerParams->set( 'ADDON_JS', $addonJS );
	}
	
	/**
	 * Index
	 * Default layout for the main dataset page, called when no other actions
	 * are requested via the URL.
	 */
	
	public function Index( ) {
		
		lib\Session::canAccess( "observer" );
		
		$datasetID = "";
		if( isset( $_GET['datasetID'] ) ) {
			if( preg_match( '/^[d]?[0-9]+$/iUs', $_GET['datasetID'] ) ) {
				
				$datasets = new models\Datasets( );
				$datasetID = trim($_GET['datasetID']);
				
				// If it starts with a lowercase d, it's a prepub dataset
				// otherwise, it's pubmed
				
				$dataset = null;
				if( $datasetID[0] == "d" ) {
					$datasetID = ltrim( $datasetID, 'd' );
					if( !$dataset = $datasets->fetchDatasetByPrepubID( $datasetID ) ) {
						// Show Invalid PrePub Dataset
						header( "Location: " . WEB_URL . "/Error/Pubmed" );
					}
				} else {
					if( !$dataset = $datasets->fetchDatasetByPubmedID( $datasetID ) ) {
						// Show Pubmed Problems Page, cause Pubmeds should always
						// get entered, unless we can't reach Pubmed successfully
						header( "Location: " . WEB_URL . "/Error/Pubmed" );
					}
				}
				
				$subsections = array( );
				$subsections[] = array( "text" => "Binary Interactions", "type" => "1" );
				$subsections[] = array( "text" => "Complexes", "type" => "2" );
				$subsections[] = array( "text" => "Chemical Interactions", "type" => "3" );
				
				$params = array(
					"TITLE" => $dataset['ANNOTATION']['TITLE'],
					"DATASET_ID" => $dataset['ID'],
					"AUTHOR_LIST" => $dataset['ANNOTATION']['AUTHOR_LIST'],
					"ABSTRACT" => $dataset['ANNOTATION']['ABSTRACT'],
					"AVAILABILITY" => strtoupper($dataset['AVAILABILITY']),
					"AVAILABILITY_LABEL" => $dataset['AVAILABILITY_LABEL'],
					"WEB_URL" => WEB_URL,
					"DATASET_SOURCE_ID" => $dataset['ANNOTATION']['ID'],
					"TYPE_NAME" => $dataset['TYPE_NAME'],
					"STATUS_LABEL" => $dataset['HISTORY_LABEL'],
					"STATUS" => $dataset['HISTORY_CURRENT']['MODIFICATION'],
					"HISTORY_NAME" => $dataset['HISTORY_CURRENT']['USER_NAME'],
					"HISTORY_DATE" => $dataset['HISTORY_CURRENT']['ADDED_DATE'],
					"SUBSECTIONS" => $subsections
				);
				
				$this->headerParams->set( "CANONICAL", "<link rel='canonical' href='" . WEB_URL . "' />" );
				$this->headerParams->set( "TITLE", WEB_NAME . " | " . WEB_DESC );
				
				$this->renderView( "dataset" . DS . "DatasetIndex.tpl", $params, false );
				
			}
		}
	}

}

?>