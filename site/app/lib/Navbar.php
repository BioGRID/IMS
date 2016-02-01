<?php

namespace IMS\app\lib;

/**
 * NAVBAR
 * This class contains a static array that can be customized to modify
 * the site Navbar.
 *
 * - Status can be "public", "observer", "curator", "poweruser", "admin"
 * - public - Seen by all, even non-logged in users
 * - observer - Lowest Class, sees Observer and Public links
 * - curator - General Class, sees Curator, Observer, and Public Links
 * - poweruser - Slightly Elevated User, sees PowerUser, Curator, Observer, and Public Links
 * - admin - Highest User Class, sees All Links
 */
 
class Navbar {

	public static $leftNav;
	public static $rightNav;
	
	public static function init( ) {
			
		self::$leftNav = array( );
		self::$rightNav = array( );
		
		// LEFT SIDE OF NAVBAR
		self::$leftNav['Home'] = array( "URL" => WEB_URL, "TITLE" => 'Return to Homepage', "STATUS" => 'public' );
		self::$leftNav['Admin'] = array( "URL" => "#", "TITLE" => 'Administration Utilities', "STATUS" => 'poweruser', "DROPDOWN" => array( ) );
		self::$leftNav['Admin']['DROPDOWN']['Users'] = array( "URL" => WEB_URL, "TITLE" => 'Manage Users and Permissions', "STATUS" => 'poweruser' );
		
		// RIGHT SIDE OF NAVBAR
		
		self::$rightNav['Logout'] = array( "URL" => WEB_URL . "/Home/Logout", "TITLE" => 'Logout from Site', "STATUS" => 'observer' );
	
	}
	
}