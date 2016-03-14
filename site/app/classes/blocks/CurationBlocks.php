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
	 
	public function __construct( ) {
		parent::__construct( );
		
		$this->lookups = new models\Lookups( );
		$this->partTypes = $this->lookups->buildParticipantTypesHash( );
		$this->partRoles = $this->lookups->buildParticipantRoleHash( );
		$this->orgNames = $this->lookups->buildOrganismNameHash( );
		$this->idTypes = $this->lookups->buildIDTypeHash( );
	}
	
	/**
	 * Wrap each block inside a curation panel
	 */
	 
	public function generateView( $blocks, $links ) {
		
		$params = array( 
			"CURATION_BLOCKS" => $blocks,
			"SIDEBAR_LINKS" => $links
		);
		
		$view = $this->processView( 'blocks' . DS . 'curation' . DS . 'CurationInterface.tpl', $params, false );
		return $view;
	}
	
	/**
	 * Generate blocks based on the type of interaction
	 * data that is about to be curated
	 */
	 
	public function fetchCurationBlocks( $type ) {
		
		$blocks = array( );
		$links = array( );
		
		switch( strtolower($type) ) {
			
			case "1" : 
				$blocks[] = array( "id" => "participants-1", "title" => "Participant List #1", "content" => $this->fetchParticipantCurationBlock( "participants-1", "2", "1", "1", "1", true ), "errors" => "" );
				
				$links[] = array( "url" => "#participants-1", "class" => "active", "title" => "Participant List #1", "icon" => "" );
				break;
				
			
		}
		
		return $this->generateView( $blocks, $links );
		
	}
	
	/**
	 * Generate a participant block with a set of passed in parameters
	 */
	 
	public function fetchParticipantCurationBlock( $baseName, $roleID, $organismID, $idType, $participantType, $allowAttribs = true ) {
		
		$params = array( 
			"BASE_NAME" => $baseName,
			"ROLES" => $this->partRoles,
			"SELECTED_ROLE" => $roleID,
			"ORGANISMS" => $this->orgNames,
			"ID_TYPES" => $this->idTypes,
			"PARTICIPANT_TYPES" => $this->partTypes,
			"SELECTED_PTYPE" => $participantType,
			"PLACEHOLDER_MSG" => "Enter identifiers, one per line",
			"ALLOW_ATTRIBS" => $allowAttribs
		);
		
		$view = $this->processView( 'blocks' . DS . 'curation' . DS . 'ParticipantCurationBlock.tpl', $params, false );
		return $view;
		
	}
	
}