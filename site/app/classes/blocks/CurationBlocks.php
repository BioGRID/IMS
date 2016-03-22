<?php


namespace IMS\app\classes\blocks;

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
	private $fullAttributes;
	
	private $blockCount = 1;
	private $participantCount = 1;
	 
	public function __construct( $blockCount = 1, $participantCount = 1 ) {
		parent::__construct( );
		
		$this->lookups = new models\Lookups( );
		$this->partTypes = $this->lookups->buildParticipantTypesHash( );
		$this->partRoles = $this->lookups->buildParticipantRoleHash( );
		$this->orgNames = $this->lookups->buildOrganismNameHash( );
		$this->idTypes = $this->lookups->buildIDTypeHash( );
		$this->attributeTypes = $this->lookups->buildAttributeTypeHASH( );
		$this->buildAttributeTypeSelectLists( );
		
		$this->blockCount = $blockCount;
		$this->participantCount = $participantCount;
		
	}
	
	/**
	 * Wrap each block inside a curation panel
	 */
	 
	public function generateView( $blocks, $links ) {
		
		$params = array( 
			"SIDEBAR_LINKS" => $links,
			"ATTRIBUTES" => $this->fullAttributes,
			"CHECKLIST_BLOCK_COUNT" => $this->blockCount,
			"CHECKLIST_PART_COUNT" => $this->participantCount
		);
		
		$view = $this->processView( 'blocks' . DS . 'curation' . DS . 'CurationInterface.tpl', $params, false );
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
			
				$links[] = array( "BLOCK" => "participant", "DATA" => array( "role" => "2", "type" => "1", "organism" => $orgID ));
				$links[] = array( "BLOCK" => "participant", "DATA" => array( "role" => "3", "type" => "1", "organism" => $orgID ));
				$links[] = array( "BLOCK" => "attribute", "DATA" => array( "type" => "11" ));
				$links[] = array( "BLOCK" => "attribute", "DATA" => array( "type" => "13" ));
				$links[] = array( "BLOCK" => "attribute", "DATA" => array( "type" => "22" ));
				
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
				$link['DATA'] = array( "role" => "1", "type" => "1", "organism" => $orgID );
				break;
				
			case "score" :
				$link['BLOCK'] = "score";
				$link['DATA'] = array( "type" => "16" );
				break;
				
			default :
				$link['BLOCK'] = "attribute";
				$link['DATA'] = array( "type" => $itemID );
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
	 * Generate Curation Block based on passed in options
	 */
	 
	public function fetchCurationBlock( $options ) {
	
		$view = "";
	
		switch( strtolower($options['block']) ) {
			
			case "participant" :
				$view = $this->fetchParticipantCurationBlock( $options['blockid'], $options );
				break;
				
			case "attribute" :
				$view = $this->fetchAttributeCurationBlock( $options['blockid'], $options );
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
			"TITLE" => $options['blockName'], 
			"CONTENT" => $view, 
			"ERRORS" => array( )
		);
		
		$curationBlock = $this->processView( 'blocks' . DS . 'curation' . DS . 'CurationBlock.tpl', $params, false );
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
				$link['TITLE'] = "Participant #" . $this->participantCount;
				
				$participantStatus = $this->processView( 'blocks' . DS . 'curation' . DS . 'ParticipantStatusMenuItem.tpl', array( ), false );
				
				$link['SUBMENU'] = array( array( 'class' => 'participantStatus', 'value' => $participantStatus ) );
				
				$this->participantCount++;
			} else if( $link['BLOCK'] == "attribute" ) {
				$attributeInfo = $this->attributeTypes[$link['DATA']['type']];
				$link['TITLE'] = $attributeInfo->attribute_type_name;
			}
			
			
			$this->blockCount++;
			$updatedLinks[] = $this->processView( 'blocks' . DS . 'curation' . DS . 'ChecklistItem.tpl', $link, false );
		}
		
		return $updatedLinks;
		
	}
	
	/**
	 * Process an ontology attribute block
	 */
	 
	private function fetchAttributeCurationBlock( $id, $options ) {
		
		$attributeID = $options['type'];
		$attributeInfo = $this->attributeTypes[$attributeID];
		$view = "";
		
		if( $attributeInfo->attribute_type_category_id == "1" ) { // Ontology Attributes
			// Get Ontology View
		} else if( $attributeInfo->attribute_type_category_id == "3" ) { // Note Attributes
			// Get Note View
			$params = array( 
				"BASE_NAME" => $id,
				"PLACEHOLDER_MSG" => "Enter Notes Here, Each distinct note on a New Line"
			);
			
			$view = $this->processView( 'blocks' . DS . 'curation' . DS . 'CurationBlock_Note.tpl', $params, false );
			
		} else if( $attributeInfo->attribute_type_category_id == "2" ) { // Quantitiative Score
			// Get Quantitiative Score View
		}
		
		return $view;
		
	}
	
	/**
	 * Generate a participant block with a set of passed in parameters
	 */
	 
	private function fetchParticipantCurationBlock( $id, $options ) {
		
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
		
		$view = $this->processView( 'blocks' . DS . 'curation' . DS . 'CurationBlock_Participant.tpl', $params, false );
		return $view;
		
	}
	
	/**
	 * Generate a participant block addon
	 */
	 
	public function fetchParticipantCurationBlockAddon( $baseName, $attributeType, $count ) {
		
		$placeholder = "Enter " . strtolower( $attributeType) . " values, one per line";
		
		$params = array( 
			"BASE_NAME" => $baseName,
			"PLACEHOLDER_MSG" => $placeholder,
			"ATTRIBUTE_TYPE" => strtolower( $attributeType ),
			"COUNT" => $count
		);
		
		$view = $this->processView( 'blocks' . DS . 'curation' . DS . 'AddonParticipantAttribute.tpl', $params, false );
		return $view;
		
	}
	
	/**
	 * Build Attribute Select Lists
	 */
	 
	private function buildAttributeTypeSelectLists( ) {
		
		$this->fullAttributes = array( );
		
		foreach( $this->attributeTypes as $attributeID => $attributeInfo ) {
			$catID = $attributeInfo->attribute_type_category_id;
			if( $catID == "1" ) {
				if( $attributeInfo->attribute_type_id != "31" && $attributeInfo->attribute_type_id != "35" ) {
					$this->fullAttributes[$attributeInfo->attribute_type_id] = $attributeInfo->attribute_type_name;
				} else {
					$this->subAttributes[$attributeInfo->attribute_type_id] = $attributeInfo->attribute_type_name;
				}
			}
		}
		
		$this->fullAttributes['22'] = "Note";
		$this->fullAttributes['score'] = "Quantitative Score";
		$this->fullAttributes['participant'] = "Participant";
		
		asort( $this->fullAttributes );
		
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