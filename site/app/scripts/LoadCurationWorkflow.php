<?PHP

/**
 * Return a curation workflow from the database for display in
 * in the curation tools view.
 */

session_start( );
header( "Cache-control: private" );

require_once __DIR__ . '/../../app/lib/Bootstrap.php';

use IMS\app\classes\blocks;

// $blocks = array( );
// $blocks[] = array( "id" => "panel1", "title" => "panel 1", "content" => "panel 1", "errors" => "" );
// $blocks[] = array( "id" => "panel2", "title" => "panel 2", "content" => "panel 2", "errors" => "" );
// $blocks[] = array( "id" => "panel3", "title" => "panel 3", "content" => "panel 3", "errors" => "" );
// $blocks[] = array( "id" => "panel4", "title" => "panel 4", "content" => "panel 4", "errors" => "" );
// $blocks[] = array( "id" => "panel1", "title" => "panel 1", "content" => "panel 1", "errors" => "" );
// $blocks[] = array( "id" => "panel2", "title" => "panel 2", "content" => "panel 2", "errors" => "" );
// $blocks[] = array( "id" => "panel3", "title" => "panel 3", "content" => "panel 3", "errors" => "" );
// $blocks[] = array( "id" => "panel4", "title" => "panel 4", "content" => "panel 4", "errors" => "" );
// $blocks[] = array( "id" => "panel1", "title" => "panel 1", "content" => "panel 1", "errors" => "" );
// $blocks[] = array( "id" => "panel2", "title" => "panel 2", "content" => "panel 2", "errors" => "" );
// $blocks[] = array( "id" => "panel3", "title" => "panel 3", "content" => "panel 3", "errors" => "" );
// $blocks[] = array( "id" => "panel4", "title" => "panel 4", "content" => "panel 4", "errors" => "" );

// $links = array( );
// $links[] = array( "url" => "#panel1", "class" => "active", "title" => "Link 1", "icon" => "" );
// $links[] = array( "url" => "#panel2", "class" => "list-group-item-info", "title" => "Link 2", "icon" => "" );
// $links[] = array( "url" => "#panel3", "class" => "list-group-item-success", "title" => "Link 3", "icon" => "check" );
// $links[] = array( "url" => "#panel4", "class" => "list-group-item-danger", "title" => "Link 4", "icon" => "close" );
// $links[] = array( "url" => "#panel4", "class" => "list-group-item-info", "title" => "Link 5", "icon" => "" );

$curationBlocks = new blocks\CurationBlocks( );
echo $curationBlocks->fetchCurationBlocks( "1" );
// $view = $curationBlocks->generateView( $blocks, $links );
// echo $view;

?>