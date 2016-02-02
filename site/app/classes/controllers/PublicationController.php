<?php


namespace IMS\app\classes\controllers;

/**
 * Home Controller
 * This controller handles the processing of the main homepage.
 */
 
use IMS\app\lib;
use IMS\app\classes\utilities;

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
		
		$pubmedParser = new utilities\PubmedParser( );
		$response = $pubmedParser->parse( $pubmedID );
		
		
		$params = array( 
			"TEXT" => print_r( $response, true )
		);
		
		$this->headerParams->set( "CANONICAL", "<link rel='canonical' href='" . WEB_URL . "' />" );
		$this->headerParams->set( "TITLE", WEB_NAME . " | " . WEB_DESC );
		
		$this->renderView( "publication" . DS . "PublicationIndex.tpl", $params, false );
		
	}

}

?>