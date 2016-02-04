<?php


namespace IMS\app\classes\controllers;

/**
 * Error Controller
 * This controller handles the processing of error messages.
 */
 
use IMS\app\lib;
use IMS\app\classes\models;

class ErrorController extends lib\Controller {
	
	public function __construct( $twig ) {
		parent::__construct( $twig );
	}
	
	/**
	 * Index
	 * Default layout for when no specific error is passed, simple returns a 404 error
	 * page with generic description.
	 */
	
	public function Index( ) {
		
		header( "HTTP/1.0 404 Not Found" );
				
		$params = array( 
			"IMG_URL" => IMG_URL,
			"WEB_URL" => WEB_URL
		);
		
		$this->headerParams->set( "CANONICAL", "<link rel='canonical' href='" . WEB_URL . "/Error/' />" );
		$this->headerParams->set( "TITLE", "ERROR 404 Page Not Found | " . WEB_NAME );
		
		$this->renderView( "error" . DS . "Error404.tpl", $params, false );
		
	}
	
	/**
	 * Permission Denied
	 * A static view presented when a user does not have permission to
	 * view a certain page
	 */
	
	public function PermissionDenied( ) {
		
		header( "HTTP/1.0 403 Forbidden" );
				
		$params = array( 
			"IMG_URL" => IMG_URL,
			"WEB_URL" => WEB_URL
		);
		
		$this->headerParams->set( "CANONICAL", "<link rel='canonical' href='" . WEB_URL . "/Error/' />" );
		$this->headerParams->set( "TITLE", "ERROR 403 Forbidden | " . WEB_NAME );
		
		$this->renderView( "error" . DS . "Error403.tpl", $params, false );
		
	}
	
	/**
	 * Pubmed Connection Error
	 * A static error when we attempt to process data from pubmed
	 * but can't for one reason or another.
	 */
	
	public function Pubmed( ) {
				
		$params = array( 
			"IMG_URL" => IMG_URL,
			"WEB_URL" => WEB_URL
		);
		
		$this->headerParams->set( "CANONICAL", "<link rel='canonical' href='" . WEB_URL . "/Error/' />" );
		$this->headerParams->set( "TITLE", "Pubmed ERROR | " . WEB_NAME );
		
		$this->renderView( "error" . DS . "ErrorPubmed.tpl", $params, false );
		
	}
	
/**
	 * Prepub Doesn't Exist
	 * A static error when we attempt to access a prepub dataset
	 * that does not exist
	 */
	
	public function Prepub( ) {
				
		$params = array( 
			"IMG_URL" => IMG_URL,
			"WEB_URL" => WEB_URL
		);
		
		$this->headerParams->set( "CANONICAL", "<link rel='canonical' href='" . WEB_URL . "/Error/' />" );
		$this->headerParams->set( "TITLE", "Prepub Dataset ERROR | " . WEB_NAME );
		
		$this->renderView( "error" . DS . "ErrorPrepub.tpl", $params, false );
		
	}

}

?>