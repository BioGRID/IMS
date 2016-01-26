<?php


namespace IMS\app\classes\controllers;

/**
 * Home Controller
 * This controller handles the processing of the main homepage.
 */
 
use IMS\app\lib\Controller;

class HomeController extends Controller {
	
	public function __construct( $twig ) {
		parent::__construct( $twig );
		$this->headerParams->set( "CANONICAL", "<link rel='canonical' href='" . WEB_URL . "' />" );
		$this->headerParams->set( "TITLE", WEB_NAME . " | " . WEB_DESC );
	}
	
	/**
	 * Index
	 * Default layout for the main homepage of the site, called when no other actions
	 * are requested via the URL.
	 */
	
	public function Index( ) {
		$params = array( 
			"WEB_NAME" => WEB_NAME,
			"WEB_NAME_ABBR" => WEB_NAME_ABBR,
			"WEB_DESC" => WEB_DESC,
			"VERSION" => VERSION
		);
		$this->renderView( "home" . DS . "HomeIndex.tpl", $params, false );
	}

}

?>