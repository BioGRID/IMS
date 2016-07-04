<?php

namespace IMS\app\inc;

/**
 * System Options
 * Below are options that can be customized to change the operation
 * of the system, most simply alter functionality of the site
 * and may eventually be moved into an administration interface
 * instead.
 */

/**
 * SiteOps Variables
 * Fill in the following variables to reflect how you wish the site to
 * operate.
 *
 * - DEFAULT_QUALIFIERS: A list of ontologies that show up as a default list when no specific qualifier ontologies are chosen.
 * - IGNORE_ATTRIBUTES: A list of attribute type ids to ignore when displaying attribute options to add on the curation interface.
 */

$siteOps = array( 
	"DEFAULT_QUALIFIERS" => array( 1,2,3,4,5,6,7,8,9,10,11,13,14,15,16 ),
	"IGNORE_ATTRIBUTES" => array( 31, 35, 36, 32, 23 )
);

?>