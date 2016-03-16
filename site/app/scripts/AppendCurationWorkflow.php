<?PHP

/**
 * Return a curation workflow from the database for display in
 * in the curation tools view.
 */

session_start( );
header( "Cache-control: private" );

require_once __DIR__ . '/../../app/lib/Bootstrap.php';

use IMS\app\classes\blocks;

echo "<div class='col-lg-3 col-md-3 col-sm-4 col-xs-4'><textarea class='form-control' placeholder='ADDON' name='{{BASE_NAME}}-participants' id='{{BASE_NAME}}-participants' rows='10'></textarea></div>";

?>