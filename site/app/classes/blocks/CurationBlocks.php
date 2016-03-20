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
	 
	public function __construct( ) {
		parent::__construct( );
		
		$this->lookups = new models\Lookups( );
		$this->partTypes = $this->lookups->buildParticipantTypesHash( );
		$this->partRoles = $this->lookups->buildParticipantRoleHash( );
		$this->orgNames = $this->lookups->buildOrganismNameHash( );
		$this->idTypes = $this->lookups->buildIDTypeHash( );
		$this->attributeTypes = $this->lookups->buildAttributeTypeHASH( );
	}
	
	/**
	 * Wrap each block inside a curation panel
	 */
	 
	public function generateView( $blocks, $links ) {
		
		$params = array( 
			"SIDEBAR_LINKS" => $links
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
		
		switch( strtolower($type) ) {
			
			case "1" : 
			
				$links[] = array( "block" => "participant", "data" => array( "role" => "2", "type" => "1", "organism" => "559292" ));
				$links[] = array( "block" => "participant", "data" => array( "role" => "3", "type" => "1", "organism" => "559292" ));
				$links[] = array( "block" => "attribute", "data" => array( "type" => "11" ));
				$links[] = array( "block" => "attribute", "data" => array( "type" => "13" ));
				$links[] = array( "block" => "attribute", "data" => array( "type" => "22" ));
				
				$links = $this->processCurationLinks( $links );
				// $blocks[] = $this->fetchParticipantCurationBlock( $links[0]['id'], $links[0]['data'], $links[0]['title'], array( ) );
			
				break;
				
			
		}
		
		return $this->generateView( $blocks, $links );
		
	}
	
	/**
	 * Process through the set of links and create code specific additions 
	 * for dealing with each type
	 */
	 
	private function processCurationLinks( $links ) {
		
		$updatedLinks = array( );
		
		$linkCount = 1;
		$participantCount = 1;
		
		foreach( $links as $link ) {
			
			$link['id'] = "block-" . $linkCount;
			
			if( $link['block'] == "participant" ) {
				$link['title'] = "Participant #" . $participantCount;
				
				$participantStatus = $this->processView( 'blocks' . DS . 'curation' . DS . 'ParticipantStatusMenuItem.tpl', array( ), false );
				
				$link['submenu'] = array( array( 'class' => 'participantStatus', 'value' => $participantStatus ) );
				
				$participantCount++;
			} else if( $link['block'] == "attribute" ) {
				$attributeInfo = $this->attributeTypes[$link['data']['type']];
				$link['title'] = $attributeInfo->attribute_type_name;
			}
			
			
			$linkCount++;
			$updatedLinks[] = $link;
		}
		
		return $updatedLinks;
		
	}
	
	/**
	 * Generate a participant block with a set of passed in parameters
	 */
	 
	public function fetchParticipantCurationBlock( $id, $options, $title, $errors = array( ) ) {
		
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
		
		$view = $this->processView( 'blocks' . DS . 'curation' . DS . 'ParticipantCurationBlock.tpl', $params, false );
		
		return array( 
			"id" => $id, 
			"title" => $title, 
			"content" => $view, 
			"errors" => implode( "\n", $errors ) 
		);
		
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
	
}