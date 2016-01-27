<?php


namespace IMS\app\classes\controllers;

/**
 * Home Controller
 * This controller handles the processing of the main homepage.
 */
 
use IMS\app\lib\Controller;
use IMS\app\lib\User;

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
		
		if( isset( $_SESSION[SESSION_NAME] ) ) {
			$this->Member( );
		} else {
			$this->Login( );
		}
		
	}
	
	/**
	 * Member
	 * Default Layout for a Logged in Member of the Site
	 */
	 
	 public function Member( ) {
		
		$params = array( 
			"WEB_NAME" => WEB_NAME,
			"WEB_NAME_ABBR" => WEB_NAME_ABBR,
			"WEB_DESC" => WEB_DESC,
			"VERSION" => VERSION
		);
		$this->renderView( "home" . DS . "HomeIndex.tpl", $params, false );
	}
	
    /**
	 * Login
	 * Layout for the Login page for the site, called when a user
	 * does not have adequate permissions to view the standard news page.
	 */
	 
	 public function Login( ) {
		 
		$params = array(
			"WEB_NAME_ABBR" => WEB_NAME_ABBR,
			"SHOW_ERROR" => "hidden",
			"WEB_URL" => WEB_URL
		);
		
		// Check to see if User is attempting to
		// Login to the site
		
		if( isset( $_POST['username'] ) ) {
			print_r( $_POST );
			
			$user = new User( );
			if( $user->validateByLogin( $_POST['username'], $_POST['password'], $_POST['remember'] ) ) {
				print "VALIDATED";
			} else {
				print "UNVALIDATED";
			}
			
			$params["USERNAME"] = $_POST['username'];
		}

		$this->renderView( "home" . DS . "HomeLogin.tpl", $params, false );
	 }

}

?>