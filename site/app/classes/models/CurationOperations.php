<?php


namespace IMS\app\classes\models;

/**
 * Curation Operations
 * A set of functions for common curation operational
 * functionality often shared amongst other scripts
 */

use \PDO;
use IMS\app\lib;
use IMS\app\classes\models;

class CurationOperations {
	
	private $db;
	 
	public function __construct( ) {
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$loader = new \Twig_Loader_Filesystem( TEMPLATE_PATH );
		$this->twig = new \Twig_Environment( $loader );
	}
	
	/**
	 * Process an attribute and either return the existing
	 * attribute id, or add it and return the newly created attribute
	 * id
	 */
	 
	public function processAttribute( $termID, $attributeTypeID ) {
		
		$stmt = $this->db->prepare( "SELECT attribute_id FROM " . DB_IMS . ".attributes WHERE attribute_value=? AND attribute_type_id=? AND attribute_status='active'  LIMIT 1" );
		$stmt->execute( array( $termID, $attributeTypeID ) );
		
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			return $row->attribute_id;
		} 
		
		$stmt = $this->db->prepare( "INSERT INTO " . DB_IMS . ".attributes VALUES ( '0',?,?,NOW( ),'active' )" );
		$stmt->execute( array( $termID, $attributeTypeID ) );
		
		$attributeID = $this->db->lastInsertId( );
		
		return $attributeID;
		
	}
	
	/**
	 * Fetch latest status and if none exists, simply return "init"
	 */
	 
	public function fetchCurationSubmissionProgress( $options ) {
		
		$curationCode = $options['curationCode'];
		
		$stmt = $this->db->prepare( "SELECT curation_status FROM " . DB_IMS . ".curation_progress WHERE curation_code=? LIMIT 1" );
		$stmt->execute( array( $curationCode ) );
		
		$status = "init";
		if( $row = $stmt->fetch( PDO::FETCH_OBJ )) {
			$status = $row->curation_status;
		}
		
		return array( "PROGRESS" => $status );
	}
	
	/**
	 * Fetch a configuration for a curation workflow based on the type
	 * of curation about to be performed.
	 */
	 
	public function fetchCurationWorkflowSettings( $curationType ) {
		
		$curationSettings = array( "CONFIG" => array( ), "CHECKLIST" => array( ) );
		
		// Get organism ID from the session for their currently selected group
		// Use it as the default when showing a participant block
		
		$orgID = "559292";
		if( isset( $_SESSION[SESSION_NAME]['GROUP'] ) ) {
			$orgID = $_SESSION[SESSION_NAME]['GROUPS'][$_SESSION[SESSION_NAME]['GROUP']]['ORGANISM_ID'];
		}
		
		// VALIDATE OPTIONS
		// Type: single_equal: must have only a single entry or the same number of entries as the block specified in the "block" parameter
		
		// THIS NEEDS TO BE CHANGED TO A DATABASE CALL LATER AND STORED IN A TABLE
		// KEEPING IT AS A MANUALLY CREATED ARRAY FOR NOW TO AVOID HAVING TO WRITE ADMIN
		// TOOL AND TO MAKE IT EASY TO TWEAK UNTIL WE HAVE A FORMAT THAT WORKS
		
		switch( strtolower($curationType) ) {
			
			case "1" : // Protein-Protein Binary Interaction
			
				// Config
				$curationSettings['CONFIG']['participant_method'] = 'row'; 
				
				// Checklist Items
				$curationSettings['CHECKLIST'][0] = array( "BLOCK" => "participant", "DATA" => array( "role" => "2", "type" => "1", "organism" => $orgID, "required" => 1 ), "VALIDATE" => array( "type" => "single_equal", "block" => 1 ), "METHOD" => "row" );
				$curationSettings['CHECKLIST'][1] = array( "BLOCK" => "participant", "DATA" => array( "role" => "3", "type" => "1", "organism" => $orgID, "required" => 1 ), "VALIDATE" => array( "type" => "single_equal", "block" => 0 ) );
				$curationSettings['CHECKLIST'][2] = array( "BLOCK" => "attribute", "DATA" => array( "type" => "11", "required" => 1 ) );
				$curationSettings['CHECKLIST'][3] = array( "BLOCK" => "attribute", "DATA" => array( "type" => "13", "required" => 1 ) );
				$curationSettings['CHECKLIST'][4] = array( "BLOCK" => "attribute", "DATA" => array( "type" => "22", "required" => 0 ) );
				break;
				
			
		}
		
		return $curationSettings;
		
	}
	
	/**
	 * Process a curation workflow notification
	 */
	 
	public function fetchCurationNotification( $options ) {
		
		$notification = "";
		
		if( isset( $options['notificationType'] )) {
			
			$params = array( );
			switch( strtoupper( $options['notificationType'] )) {
		
				case "ERROR" :
				
					$params = array(
						"NOTIFICATION" => "Your submission failed due to one or more errors...",
						"NOTIFICATION_TYPE" => "text-danger",
						"VIEW_LINK" => "<i class='fa fa-arrow-left'></i> View Errors"
					);
					
					break;
					
				case "SUBMIT" :
					
					$params = array( 
						"NOTIFICATION" => "Processing Submission <i class='fa fa-refresh fa-spin fa-lg'></i>",
						"NOTIFICATION_TYPE" => "",
						"VIEW_LINK" => "<i class='fa fa-arrow-left'></i> View Results"
					);
					
					break;
					
				case "SUCCESS" :
					
					$params = array( 
						"NOTIFICATION" => "Your submission was completed successfully!",
						"NOTIFICATION_TYPE" => "text-success",
						"VIEW_LINK" => "<i class='fa fa-arrow-left'></i> View Results"
					);
					
					break;
		
			}
			
			return $this->twig->render( 'curation' . DS . 'checklist' . DS . 'Notification.tpl', $params ); 
		
		}
		
		return "";
		
	}
	
	/**
	 * Process messages to create errors
	 */
	 
	public function processErrors( $messages ) {
		$errors = $this->twig->render( 'curation' . DS . 'error' . DS . 'CurationError.tpl', array( "ERRORS" => $messages ) ); 
		return $errors;
	}
	
	/**
	 * Generate text for validation error messages
	 * based on the type of error
	 */
	 
	public function generateError( $errorType, $details = array( ) ) {
	
		switch( strtoupper( $errorType ) ) {
			
			case "REQUIRED" :
				return array( "class" => "danger", "message" => $details['blockName'] . " is a required field. It will not validate while no data has been entered in the provided fields." );
				
			case "NON_NUMERIC" :
				
				$message = "The score value <strong>" . $details['score'] . "</strong> is NON NUMERIC on line <strong>" . implode( ", ", $details['lines'] ) . "</strong>. Please use a numerical value or use a hyphen (-) for no score on this line.";
				
				return array( "class" => "danger", "message" => $message );
				
			case "AMBIGUOUS" :
			
				$message = "The identifier <strong>" . $details['identifier'] . "</strong> is AMBIGUOUS on lines <strong>" . implode( ", ", $details['lines'] ) . "</strong>. <br />Ambiguities are: ";
				
				foreach( $details['options'] as $geneID => $annotation ) {
					$options[] = "<a href='http://thebiogrid.org/" . $annotation['gene_id'] . "' target='_blank'>" . $annotation['primary_name'] . "</a> (<a class='lineReplace' data-lines='" . implode( "|", $details['lines'] ) . "' data-value='BG_" . $annotation['gene_id'] . "'>" . "<i class='fa fa-lg fa-exchange'></i>" . "</a>)</a>";
				}
				
				$message .= implode( ", ", $options ) . "<div class='text-success statusMsg'></div>";
				return array( "class" => "danger", "message" => $message );
				
			case "UNKNOWN" :
			
				$message = "The identifier <strong>" . $details['identifier'] . "</strong> is UNKNOWN on lines <strong>" . implode( ", ", $details['lines'] ) . "</strong>. If you believe it to be valid, you can add it as an unknown participant. Alternatively, you can correct it, by entering an alternative identifier below. To enter a BioGRID ID, preface term with BG_ (example: BG_123456). Otherwise, any other term will be assumed to be the same ID Type as the others listed above...";
				
				$message .= "<div class='clearfix'><div class='input-group col-lg-6 col-md-6 col-sm-12 col-xs-12 marginTopSm'><input type='text' class=' form-control unknownReplaceField' placeholder='Enter Replacement Term' value='' /><span class='input-group-btn'><button data-lines='" . implode( "|", $details['lines'] ) . "' class='btn btn-success unknownReplaceSubmit' type='button'>Replace</button></span></div></div>";
				
				$message .= "<div class='text-success statusMsg'></div>";
				
				return array( "class" => "warning", "message" => $message );
				
			case "ALLELE_MISMATCH" :
				
				$message = "The number of alleles in <strong>Allele Box #" . $details['alleleBoxNumber'] . "</strong> does not match the number of participants specified. You must enter an allele for every participant or use a hyphen (-) if wanting no allele. Please correct this and try validation again.";
				
				return array( "class" => "danger", "message" => $message );
				
			case "NOCODE" :
				return array( "class" => "danger", "message" => "No curation code was passed to the validation script. Please try again!" );
				
			case "BLANK" :
				return array( "class" => "warning", "message" => $details['blockName'] . " is currently blank but is not a required field. If you do not wish to use it, you can remove it by clicking the remove button, or simply ignore it, and your submission will still succeed, with this block ignored." );
				
			case "INVALID_ONTOLOGY_TERM" :
				return array( "class" => "danger", "message" => "One of the ontology terms you selected is not a valid ontology term id, try re-searching for your ontology term mappings and adding them again!" );
				
			case "BIOCHEMICAL_ACTIVITY_WRONG_QUALIFIER" :
				return array( "class" => "danger", "message" => "Your selected ontology term: " . $details['term'] . " must have a qualifier appended from the BioGRID Post-Translational modification ontology, but you selected a qualifier from a different ontology. Please correct this mistake and re-attempt validation." );
				
			case "BIOCHEMICAL_ACTIVITY_NO_QUALIFIER" :
				return array( "class" => "danger", "message" => "Your selected ontology term: " . $details['term'] . " must have a qualifier appended from the BioGRID Post-Translational modification ontology, but you selected none or too many. Please correct this mistake and re-attempt validation." );
				
			case "INVALID_BLOCKS" :
				return array( "class" => "danger", "message" => "Your data was not submitted because one or more items in the checklist to the right are still invalid. The following blocks: <strong>" . implode( ", ", $details['invalidBlocks'] ) . "</strong> still have errors that need to be fixed before submitting. You can find the errors that still exist by clicking on a checklist item marked with a red X to the right, and scrolling to the bottom..." );
				
			case "SINGLE_EQUAL" :
				return array( "class" => "danger", "message" => "Your data was not submitted because <strong>" . $details['testBlockName'] . "</strong> or <strong>" . $details['compareBlockName'] . "</strong> must contain only a single entry (which will be repeated automatically) or <strong>" . $details['testBlockName'] . "</strong> must contain the exact same number of entries as <strong>" . $details['compareBlockName'] . "</strong>. Currently, <strong>" . $details['testBlockName'] . "</strong> contains <strong> " . $details['testBlockSize'] . "</strong> entries and <strong>" . $details['compareBlockName'] . "</strong> contains <strong> " . $details['compareBlockSize'] . "</strong> entries" );
				
			case "QUANT_COUNT" :
				return array( "class" => "danger", "message" => "Your data was not submitted because <strong>" . $details['quantName'] . "</strong> must contain the same number of entries as the number of participants. Currently, <strong>" . $details['quantName'] . "</strong> contains <strong> " . $details['quantSize'] . "</strong> entries but you are entering <strong> " . $details['participantSize'] . "</strong> participants." );
				
			case "DATABASE_INSERT" :
				return array( "class" => "danger", "message" => "Your data was not submitted because there was an issue inserting it into the database. If this problem persists, please contact the site administrators and paste in the following details:<br />" . implode( "<br />", $details['dbErrors'] ) );

			default:
				return array( "class" => "danger", "message" => "An unknown error has occurred." );
			
		}
	
	}
	
}