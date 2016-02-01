<?php


namespace IMS\app\classes\controllers;

/**
 * Home Controller
 * This controller handles the processing of the main homepage.
 */
 
use IMS\app\lib;

class PublicationController extends lib\Controller {
	
	public function __construct( $twig ) {
		parent::__construct( $twig );
	}
	
	/**
	 * Index
	 * Default layout for the main homepage of the site, called when no other actions
	 * are requested via the URL.
	 */
	
	public function Index( ) {
		
		lib\Session::canAccess( "observer" );
		
		$pubmedID = "";
		if( isset( $_GET['pubmed'] ) ) {
			$pubmedID = filter_var( $_GET['pubmed'], FILTER_SANITIZE_NUMBER_INT );
		}
		
		$params = array( 
			"TEXT" => $pubmedID
		);
		
		$this->headerParams->set( "CANONICAL", "<link rel='canonical' href='" . WEB_URL . "' />" );
		$this->headerParams->set( "TITLE", WEB_NAME . " | " . WEB_DESC );
		
		$this->renderView( "publication" . DS . "PublicationIndex.tpl", $params, false );
		
	}

}

?>