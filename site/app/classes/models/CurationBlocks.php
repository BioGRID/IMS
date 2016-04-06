<?php


namespace IMS\app\classes\models;

/**
 * Curation Blocks
 * This set of blocks is for handling output of various layout blocks
 * to build a curation interface via AJAX requests.
 */
 
use IMS\app\lib;
use IMS\app\classes\models;

class CurationBlocks extends lib\Blocks {
	 
	private $lookups;
	private $partTypes;
	private $partRoles;
	private $orgNames;
	private $idTypes;
	private $attributeTypes;
	private $checklistAttributes;
	private $checklistScores;
	private $checklistParticipants;
	private $checklistSubAttributes;
	
	private $blockCount = 1;
	private $participantCount = 1;
	private $lastParticipant = "";
	 
	public function __construct( ) {
		parent::__construct( );
		
		$this->lookups = new models\Lookups( );
		$this->partTypes = $this->lookups->buildParticipantTypesHash( false );
		$this->partRoles = $this->lookups->buildParticipantRoleHash( );
		$this->orgNames = $this->lookups->buildOrganismNameHash( );
		$this->idTypes = $this->lookups->buildIDTypeHash( );
		$this->attributeTypes = $this->lookups->buildAttributeTypeHASH( );
		$this->buildAttributeTypeSelectLists( );
		
		$this->blockCount = 1;
		$this->participantCount = 1;
		
	}
	
	/**
	 * Set block count and participant count
	 */
	 
	public function setCounts( $blockCount, $participantCount ) {
		$this->blockCount = $blockCount;
		$this->participantCount = $participantCount;
	}
	
	/**
	 * Build out the basic interface layout for curation
	 */
	 
	public function generateView( $blocks, $links ) {
		
		$params = array( 
			"SIDEBAR_LINKS" => $links,
			"CHECKLIST_ATTRIBUTES" => $this->checklistAttributes,
			"CHECKLIST_SCORES" => $this->checklistScores,
			"CHECKLIST_PARTICIPANTS" => $this->checklistParticipants,
			"CHECKLIST_BLOCK_COUNT" => $this->blockCount,
			"CHECKLIST_PART_COUNT" => $this->participantCount,
			"LAST_PARTICIPANT" => $this->lastParticipant,
			"CHECKLIST_SUBATTRIBUTES" => $this->checklistSubAttributes,
			"CURATION_CODE" => uniqid( )
		);
		
		$view = $this->processView( 'curation' . DS . 'main' . DS . 'Interface.tpl', $params, false );
		return $view;
	}
	
	/**
	 * Generate checklist based on the type of interaction
	 * data that is about to be curated
	 */
	 
	public function fetchCurationChecklist( $type ) {
		
		$blocks = array( );
		$links = array( );
		
		// Get organism ID from the session for their currently selected group
		// Use it as the default when showing a participant block
		
		$orgID = "559292";
		if( isset( $_SESSION[SESSION_NAME]['GROUP'] ) ) {
			$orgID = $_SESSION[SESSION_NAME]['GROUPS'][$_SESSION[SESSION_NAME]['GROUP']]['ORGANISM_ID'];
		}
		
		switch( strtolower($type) ) {
			
			case "1" : // Protein-Protein Binary Interaction
			
				$links[] = array( "BLOCK" => "participant", "DATA" => array( "role" => "2", "type" => "1", "organism" => $orgID, "required" => 1 ) );
				$links[] = array( "BLOCK" => "participant", "DATA" => array( "role" => "3", "type" => "1", "organism" => $orgID, "required" => 1 ) );
				$links[] = array( "BLOCK" => "attribute", "DATA" => array( "type" => "11", "required" => 1 ) );
				$links[] = array( "BLOCK" => "attribute", "DATA" => array( "type" => "13", "required" => 1 ) );
				$links[] = array( "BLOCK" => "attribute", "DATA" => array( "type" => "22", "required" => 0 ) );
				
				$links = $this->processCurationLinks( $links, 1 );
			
				break;
				
			
		}
		
		return $this->generateView( $blocks, $links );
		
	}
	
	/**
	 * Fetch curation checklist item to append to the current
	 * checklist following existing items
	 */
	 
	public function fetchCurationChecklistItem( $itemID ) {
		
		$link = array( );
		
		$orgID = "559292";
		if( isset( $_SESSION[SESSION_NAME]['GROUP'] ) ) {
			$orgID = $_SESSION[SESSION_NAME]['GROUPS'][$_SESSION[SESSION_NAME]['GROUP']]['ORGANISM_ID'];
		}
		
		switch( $itemID ) {
			
			case "participant" :
				$link['BLOCK'] = "participant";
				$link['DATA'] = array( "role" => "1", "type" => "1", "organism" => $orgID, "required" => 0 );
				break;
				
			default :
				$link['BLOCK'] = "attribute";
				$link['DATA'] = array( "type" => $itemID, "required" => 0 );
				break;
		}
		
		if( sizeof( $link ) > 0 ) {
			$links = array( $link );
			$links = $this->processCurationLinks( $links );
			return $links[0];
		} 
		
		return "";
		
	}
	
	/** 
	 * Fetch curation checklist sub item to append to the submenu
	 * of the current checklist item
	 */
	 
	public function fetchCurationChecklistSubItem( $itemID, $parentID, $parentName, $count ) {
		
		$attributeInfo = $this->attributeTypes[$itemID];
		
		$link = array( 
			"PARENT_NAME" => $parentName,
			"SUBITEM_NAME" => $attributeInfo->attribute_type_name . " #" . $count,
			"PARENT_ID" => $parentID
		);
		
		return $this->processView( 'curation' . DS . 'checklist' . DS . 'SubItem.tpl', $link, false );
		
	}
	
	/**
	 * Generate Curation Block based on passed in options
	 */
	 
	public function fetchCurationBlock( $options ) {
	
		$view = "";
		$type = "";
	
		switch( strtolower($options['block']) ) {
			
			case "participant" :
				$view = $this->fetchParticipantCurationForm( $options['blockid'], $options );
				break;
				
			case "attribute" :
				$view = $this->fetchAttributeCurationForm( $options['blockid'], $options, false );
				break;
				
			default:
				$block = "<div style='width:100%; background-color: #FFFFEF;' id='" . $options['blockid'] . "' class='curationBlock'>";
				$block .= "<strong>" . $options['blockid'] . "</strong>";
				$block .= print_r( $options, true );
				$block .= "</div>";
				$view = $block;
				break;
				
		}
		
		$params = array( 
			"ID" => $options['blockid'], 
			"TITLE" => trim($options['blockName']), 
			"CONTENT" => $view, 
			"ERRORS" => "",
			"REQUIRED" => $options['required'],
			"SUBPANEL" => false,
			"TYPE" => $options['block']
		);
		
		$curationBlock = $this->processView( 'curation' . DS . 'blocks' . DS . 'Block.tpl', $params, false );
		return $curationBlock;
	
	}
	
	/**
	 * Generate Curation Panel based on passed in options
	 */
	 
	public function fetchCurationPanel( $options ) {
	
		$view = "";
		$title = "";
		
		$options['type'] = $options['selected'];
	
		if( $options['selected'] == "36" ) {
			$title = $options['parentName'] . " - Allele #" . $options['subCount'];
		} else if( $options['selected'] == "22" ) {
			$title = $options['parentName'] . " - Note #" . $options['subCount'];
		}
		
		$view = $this->fetchAttributeCurationForm( $options['parent'], $options, true );
		
		$params = array( 
			"ID" => $options['parent'], 
			"TITLE" => $title, 
			"CONTENT" => $view, 
			"ERRORS" => "",
			"SUBPANEL" => true
		);
		
		$curationBlock = $this->processView( 'curation' . DS . 'blocks' . DS . 'Panel.tpl', $params, false );
		return $curationBlock;
	
	}
	
	/**
	 * Process through the set of links and create code specific additions 
	 * for dealing with each type
	 */
	 
	private function processCurationLinks( $links ) {
		
		$updatedLinks = array( );
		
		foreach( $links as $link ) {
			
			$link['ID'] = "block-" . $this->blockCount;
			
			if( $link['BLOCK'] == "participant" ) {
				
				$link['TITLE'] = "Participants #" . $this->participantCount;
				
				$participantStatus = $this->processView( 'curation' . DS . 'checklist' . DS . 'ParticipantStatus.tpl', array( ), false );
				
				$link['SUBMENU'] = array( array( 'class' => 'participantStatus', 'value' => $participantStatus ) );
				$link['IS_PARTICIPANT'] = true;
				
				$this->lastParticipant = $link['ID'];
				$this->participantCount++;
				
			} else if( $link['BLOCK'] == "attribute" ) {
				$attributeInfo = $this->attributeTypes[$link['DATA']['type']];
				$link['TITLE'] = $attributeInfo->attribute_type_name;
			}
			
			
			$this->blockCount++;
			$updatedLinks[] = $this->processView( 'curation' . DS . 'checklist' . DS . 'ListItem.tpl', $link, false );
		}
		
		return $updatedLinks;
		
	}
	
	/**
	 * Process an ontology attribute form
	 */
	 
	private function fetchAttributeCurationForm( $id, $options, $isPanel = false ) {
		
		$attributeID = $options['type'];
		$attributeInfo = $this->attributeTypes[$attributeID];
		$view = "";
		
		if( $attributeInfo->attribute_type_category_id == "1" && $attributeID != "36" ) { // Ontology Attributes
			// Get Ontology View
			
		} else if( $attributeID == "36" ) { // Allele List View
		
			// Get Allele Form View
			$params = array( 
				"BASE_NAME" => $id,
				"PLACEHOLDER_MSG" => "Enter alleles here, One per Line to match with " . $options['parentName'] . " in list above",
				"ID" => $options['subCount']
			);
			
			$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_Allele.tpl', $params, false );
			
			
		} else if( $attributeInfo->attribute_type_category_id == "3" ) { // Note Attributes
			// Get Note View
			
			$msg = "Enter Notes Here, Each distinct note on a New Line";
			if( $isPanel ) {
				$msg = "Enter notes here! You must have one note for each participant listed in the " . $options['parentName'] . " list above. Enter hyphen '-' if no note is present for a given participant.";
			}
			
			$params = array( 
				"BASE_NAME" => $id,
				"PLACEHOLDER_MSG" => $msg
			);
			
			$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_Note.tpl', $params, false );
			
		} else if( $attributeInfo->attribute_type_category_id == "2" ) { // Quantitiative Score
			// Get Quantitiative Score View
		}
		
		return $view;
		
	}
	
	/**
	 * Generate a participant form with a set of passed in parameters
	 */
	 
	private function fetchParticipantCurationForm( $id, $options ) {
		
		$roleID = "";
		if( isset( $options['role'] ) ) {
			$roleID = $options['role'];
		}
		
		$participantType = "";
		if( isset( $options['type'] ) ) {
			$participantType = $options['type'];
		}
		
		$organism = "";
		if( isset( $options['organism'] ) ) {
			$organism = $options['organism'];
		}
		
		$idType = "";
		if( isset( $options['idtype'] ) ) {
			$idType = $options['idtype'];
		}
		
		$params = array( 
			"BASE_NAME" => $id,
			"ROLES" => $this->partRoles,
			"SELECTED_ROLE" => $roleID,
			"ORGANISMS" => $this->orgNames,
			"ID_TYPES" => $this->idTypes,
			"PARTICIPANT_TYPES" => $this->partTypes,
			"SELECTED_PTYPE" => $participantType,
			"SELECTED_TYPE" => $idType,
			"SELECTED_ORG" => $organism,
			"PLACEHOLDER_MSG" => "Enter identifiers, one per line"
		);
		
		$view = $this->processView( 'curation' . DS . 'blocks' . DS . 'Form_Participant.tpl', $params, false );
		return $view;
		
	}
	
	/**
	 * Build Attribute Select Lists
	 */
	 
	private function buildAttributeTypeSelectLists( ) {
		
		$this->checklistAttributes = array( );
		$this->checklistParticipants = array( );
		$this->checklistScores = array( );
		
		foreach( $this->attributeTypes as $attributeID => $attributeInfo ) {
			$catID = $attributeInfo->attribute_type_category_id;
			if( $catID == "1" ) {
				if( $attributeInfo->attribute_type_id != "31" && $attributeInfo->attribute_type_id != "35" && $attributeInfo->attribute_type_id != '36' ) {
					$this->checklistAttributes[$attributeInfo->attribute_type_id] = $attributeInfo->attribute_type_name;
				} 
			} else if( $catID == "2" ) {
				$this->checklistScores[$attributeInfo->attribute_type_id] = $attributeInfo->attribute_type_name;
			}
		}
		
		$this->checklistAttributes['22'] = "Note";
		$this->checklistParticipants['participant'] = "Participant";
		
		asort( $this->checklistAttributes );
		asort( $this->checklistScores );
		
		$this->checklistSubAttributes['36'] = "Alleles";
		$this->checklistSubAttributes['22'] = "Note";
		
	}
	
	/** 
	 * Get Block Count
	 */
	 
	public function getBlockCount( ) {
		return $this->blockCount;
	}
	
	/** 
	 * Get Participant Count
	 */
	 
	public function getParticipantCount( ) {
		return $this->participantCount;
	}
	
}