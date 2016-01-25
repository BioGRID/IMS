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
		//$this->headerParams->set( "CANONICAL", "<link rel='canonical' href='" . SITE_URL . "'>" );
		//$this->headerParams->set( "TITLE", SITE_NAME . " | " . SITE_DESC );
	}
	
	/**
	 * Index
	 * Default layout for the main homepage of the site, called when no other actions
	 * are requested via the URL.
	 */
	
	public function Index( ) {

		parent::renderView( '', '', false );
	
		// $news = new models\NewsParser( );
		// $newsPosts = $news->parse( NEWS_FEED );
	
		// $params = array( "IMG_URL" => IMG_URL,
						 // "VERSION" => "3.1.113",
						 // "PUBLICATIONS" => "42,764",
						 // "RAW" => "742,011",
						 // "LATEST_NEWS" => $newsPosts );
	
		// $this->renderView( "home" . DS . "HomeIndex.tpl", $params, false );
	}

}

?>