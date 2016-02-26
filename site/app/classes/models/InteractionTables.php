<?php

namespace IMS\app\classes\models;

/**
 * Interaction Tables
 * Methods for creating and outputting interaction tables with
 * customized options when fetching interaction data.
 */
 
use IMS\app\classes\models\ElasticSearch;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

class InteractionTables {
	
	private $es;
	private $log;
	
	function __construct( ) {
		$this->es = new ElasticSearch( );
		$this->log = new Logger( 'Load Interactions Log' );
		$this->log->pushHandler( new StreamHandler( __DIR__ . '/../../../www/logs/LoadInteractions.log', Logger::DEBUG ));
	}

	/**
	 * Fetch interaction data for the table with specific parameters passed in
	 * to manipulate exactly what is shown to the user.
	 */
	 
	public function fetchInteractions( $datasetID, $typeID, $status, $searchTerm, $start, $length ) {
		$params = $this->fetchParams( $datasetID, $typeID, $status, $searchTerm, $start, $length );
		return $this->es->search( $params );
	}
	
	/**
	 * Grab columns definitions from the database for the type of
	 * interaction whose data we are going to display
	 */
	 
	public function fetchColumns( $typeID ) {
		
		$columns = array( );
		$columns[0] = array( 
			"title" => "",
			"orderable" => false,
			"sortable" => false,
			"className" => "text-center",
			"type" => "direct",
			"value" => "interaction_id",
			"html" => "<div class='checkbox'><label><input type='checkbox' value='{{VALUE}}'></label></div>"
		);
		
		$columns[1] = array( 
			"title" => "Bait",
			"type" => "participant",
			"value" => "primary_name",
			"query" => array( "participant_role_id" => "2" ),
			"func" => array( "participant-norole" )
		);
		
		$columns[2] = array( 
			"title" => "Prey",
			"type" => "participant",
			"value" => "primary_name",
			"query" => array( "participant_role_id" => "3" ),
			"func" => array( "participant-norole" )
		);
		
		$columns[3] = array( 
			"title" => "Bait Org",
			"type" => "participant",
			"value" => "organism_abbreviation",
			"query" => array( "participant_role_id" => "2" )
		);
		
		$columns[4] = array( 
			"title" => "Prey Org",
			"type" => "participant",
			"value" => "organism_abbreviation",
			"query" => array( "participant_role_id" => "3" )
		);
		
		$columns[5] = array( 
			"title" => "System",
			"type" => "attribute",
			"value" => "attribute_value",
			"query" => array( "attribute_type_id" => "11" )
		);
		
		$columns[6] = array( 
			"title" => "User",
			"type" => "direct",
			"value" => "history_user_name"
		);
		
		$columns[7] = array( 
			"title" => "Date",
			"type" => "direct",
			"value" => "history_date",
			"func" => array( "date" )
		);
		
		$columns[8] = array( 
			"title" => "Attributes",
			"type" => "attribute_icons",
			"value" => "interaction_id",
			"ignore" => array( "attribute_type_id" => "11" ),
			"className" => "text-center"
		);
		
		$columns[9] = array( 
			"title" => "State",
			"type" => "direct",
			"value" => "interaction_state"
		);
		
		return $columns;
		
	}
	
	/**
	 * Format results fetched from Elastic Search to display as a table
	 * with correct column configuration based on type.
	 */
	
	public function formatResults( $response, $columns ) {
		
		$data = array( );
		if( $response['hits']['total'] > 0 ) {
			foreach( $response['hits']['hits'] as $hit ) {
				$data[] = $this->formatRow( $hit, $columns );
			}
		}
		
		return $data;
		
	}
	
	/**
	 * Fetch formatted columns based on the column definition information and the data in a specific
	 * document fetched from ElasticSearch
	 */
	 
	private function formatRow( $hit, $columns ) {
		
		$row = array( );
		foreach( $columns as $columnIndex => $columnInfo ) {
			
			$colVal = "";
			if( $columnInfo['type'] == "direct" ) {
				$colVal = $hit['_source'][$columnInfo['value']];
				$colVal = $this->formatCol( $colVal, $columnInfo );
			} else if( $columnInfo['type'] == "participant" ) {
				$colVal = $this->fetchParticipants( $hit['_source']['participants'], $columnInfo );
				$colVal = implode( ", ", $colVal );
			} else if( $columnInfo['type'] == "attribute" ) {
				$colVal = $this->fetchAttributes( $hit['_source']['attributes'], $columnInfo );
				$colVal = implode( ", ", $colVal );
			} else if( $columnInfo['type'] == "attribute_icons" ) {
				$colVal = $this->fetchAttributeIcons( $hit['_source']['attributes'], $columnInfo );
				$colVal = implode( " ", $colVal );
			}
			
			if($hit['_source']['interaction_state'] == "error" ) {
				$row['DT_RowClass'] = "warning";
			}
			
			$row[] = $colVal;
			
		}
		
		return $row;
		
	}
	
	/**
	 * Step through a set of participants and find the ones we are looking for to
	 * display for a particular column.
	 */
	 
	private function fetchParticipants( $participants, $columnInfo ) {
		
		$participantList = array( );
		foreach( $participants as $participant ) {
			
			if( $this->matchesQueries( $participant, $columnInfo['query'] )) {
				$participantList[] = $this->formatCol( $participant[$columnInfo['value']], $columnInfo, $participant );
			}
			
		}
		
		return $participantList;
		
	}
	
	/**
	 * Step through each attribute and find the ones we are looking for to display
	 * for this particular column.
	 */
	 
	private function fetchAttributes( $attributes, $columnInfo ) {
	
		$attributeList = array( );
		foreach( $attributes as $attribute ) {
			
			if( $this->matchesQueries( $attribute, $columnInfo['query'] )) {
				$attributeList[] = $this->formatCol( $attribute[$columnInfo['value']], $columnInfo, $attribute );
			}
			
		}
		
		return $attributeList;
	
	}
	
	/**
	 * Step through each attribute and build out icons based on what we want to
	 * display for each type.
	 */
	 
	private function fetchAttributeIcons( $attributes, $columnInfo ) {
		
		$attributeList = array( );
		$attributeChildren = array( );
		foreach( $attributes as $attribute ) {
			
			if( !$this->matchesQueries( $attribute, $columnInfo['ignore'] ) ) {
				if( !isset( $attributeList[$attribute['attribute_type_category_id']] )) {
					$attributeList[$attribute['attribute_type_category_id']] = array( );
				}
				
				if( $attribute['attribute_parent_id'] == 0 ) {
					$attributeList[$attribute['attribute_type_category_id']][$attribute['attribute_id']] = $attribute;
				} else {
					
					if( !isset( $attributeChildren[$attribute['attribute_parent_id']] )) {
						$attributeChildren[$attribute['attribute_parent_id']] = array( );
					} 
					
					$attributeChildren[$attribute['attribute_parent_id']][] = $attribute;
					
				}
			}
		}
		
		ksort( $attributeList );
		$attributeIcons = array( );
		foreach( $attributeList as $attributeTypeCategoryID => $attributes ) {
			$content = array( );
			foreach( $attributes as $attribute ) {
			
				$children = array( );
				if( isset( $attributeChildren[$attribute['interaction_attribute_id']] )) {
					$children = $attributeChildren[$attribute['interaction_attribute_id']];
				}

				$content[] = $this->fetchAttributeContent( $attribute, $children );
				
			}
			
			$content = "<span class='attributeContent'><ul><li>" . implode( "</li><li>", $content ) . "</li></ul></span>";
			$icon = $this->fetchAttributeIcon( $attributeTypeCategoryID );
			
			$attributeIcons[] = "<span class='attribute'>" . $icon . $content . "</span>";
			
		}
		
		return $attributeIcons;
	}
	
	/**
	 * Fetch all subattributes attached to an attribute or participant and attach them all
	 * to a single icon.
	 */
	 
	private function fetchCombinedAttributeIcon( $attributes ) {
		
		$attributeList = array( );
		foreach( $attributes as $attribute ) {
			$attributeList[] = $this->fetchAttributeContent( $attribute, array( ) );
		}
		
		$content = "<span class='attributeContent'><ul><li>" . implode( "</li><li>", $attributeList ) . "</li></ul></span>";
		$icon = $this->fetchAttributeIcon( "0" );
		
		return " <span class='attribute'>" . $icon . $content . "</span>";
		
	}
	
	/** 
	 * Fetch an attributes content based on the attribute category type ID and the data
	 * for the attribute that was passed in
	 */
	 
	private function fetchAttributeContent( $attribute, $children ) {
		
		$term = "<strong>" . $attribute['attribute_type_name'] . "</strong>: ";
		$term .= $attribute['attribute_value'];
		
		// Ontology Term
		if( $attribute['attribute_type_category_id'] == "1" ) { 
			$term .= " (" . $attribute['ontology_term_official_id'] . ")";
		}
		
		$childrenSet = array( );
		foreach( $children as $child ) {
			$childrenSet[] = $child['attribute_value'];
		}
		
		if( sizeof( $childrenSet ) > 0 ) {
			$term .= " [" . implode( " | ", $childrenSet ) . "]";
		}
		
		$term .= " - <span class='attributeUser'>" . $attribute['attribute_user_name'] . " (" . date( 'Y-m-d', strtotime( $attribute['attribute_addeddate'] )) . ")</span>";
		
		return $term;
		
	}
	
	/**
	 * Fetch an attribute icon based on the attribute category type ID that was passed
	 * in to function
	 */
	 
	private function fetchAttributeIcon( $attributeTypeCategoryID ) {
		
		$icon = "flask";
		$title = "Attribute";
		
		switch( $attributeTypeCategoryID ) {
			
			case "1" : // Ontology Term
				$icon = "tags";
				$title = "Ontology Terms";
				break;
				
			case "2" : // Quantitative Score
				$icon = "bar-chart-o";
				$title = "Quantitative Scores";
				break;
				
			case "3" : // Note
				$icon = "edit";
				$title = "Notes";
				break;
				
			case "4" : // PTM Detail
				$icon = "key";
				$title = "PTM Detail";
				break;
				
			case "5" : // Participant Tag
				$icon = "star";
				$title = "Participant Tag";
				break;
				
			case "6" : // File
				$icon = "file-text-o";
				$title = "Upload File";
				break;
				
			case "7" : // External Database ID
				$icon = "external-link";
				$title = "External Link";
				break;
				
			case "0" : // Combined Icon
				$icon = "sitemap";
				$title = "Attributes";
				break;
			
		}
		
		return "<i data-title='" . $title . "' class='attributeIcon fa fa-lg fa-" . $icon . "'></i>";
		
	}
	
	/**
	 * Test to see if the nested value in the elastic search document
	 * matches the query parameters
	 */
	 
	private function matchesQueries( $nestedDoc, $queries ) {
		
		foreach( $queries as $queryIndex => $queryVal ) {
			if( isset( $nestedDoc[$queryIndex] ) && $nestedDoc[$queryIndex] == $queryVal ) {
				continue;
			} else {
				return False;
			}
		}
		
		return True;
		
	}
	
	/**
	 * Format a row based on what info is passed in, and the parameters specified
	 * as to how it should be generated.
	 */
	 
	private function formatCol( $value, $columnInfo, $data = array( ) ) {
		
		// If the func option is set, we are running the value through a 
		// specified function or set of functions
		
		if( isset( $columnInfo['func'] )) {
			foreach( $columnInfo['func'] as $func ) {
				$value = $this->processFunc( $value, $func, $data );
			}
		}
		
		// If the html option is set, we are placing the value
		// inside a pre-configured HTML markup, where {{VALUE}}
		// should be replaced with the value passed in.
		
		if( isset( $columnInfo['html'] )) {
			$value = str_replace( "{{VALUE}}", $value, $columnInfo['html'] );
		} else if( isset( $data['attributes'] ) && sizeof( $data['attributes'] ) > 0 ) {
			$value = $value . $this->fetchCombinedAttributeIcon( $data['attributes'] );
		}
		
		return $value;
		
	}
	
	/** 
	 * Process a value through a standardized function call
	 * to pre-process the results
	 */
	 
	private function processFunc( $value, $func, $data ) {
		
		switch( strtolower($func) ) {
			
			case "date" :
				return date( 'Y-m-d', strtotime( $value ));
				
			case "participant-norole" :
				
				$participantContent = array( );
				if( sizeof( $data['aliases'] ) > 0 ) {
					$participantContent[] = "<strong>Aliases:</strong> " . implode( ", ", $data['aliases'] );
				}
				
				$participantContent[] = "<strong>Type:</strong> " . $data['participant_type_name'];
				$participantContent[] = "<strong>Role:</strong> " . $data['participant_role_name'];
				
				if( $data['organism_id'] != "" ) {
					$participantContent[] = "<strong>Organism:</strong> " . $data['organism_official_name'];
					if( $data['organism_strain'] != "" ) {
						$participantContent[] = "<strong>Organism Strain:</strong> " . $data['organism_strain'];
					}
				}
				
				return '<div class="participantWrap"><a data-title="' . $value . '" class="participantPopover"><strong>' . $value . '</strong></a><span class="participantContent"><ul><li>' . implode( "</li><li>", $participantContent ). '</li></ul></span></div>';
				
		}
		
		return $value;
	}

	/**
	 * Fetch the column headers to be displayed based on the column structure for this 
	 * interaction type
	 */
	 
	public function fetchColumnHeaderDefinitions( $columns ) {
		
		$columnSet = array( );
		foreach( $columns as $columnIndex => $columnInfo ) {
			
			$column = array( );
			$column["title"] = $columnInfo["title"];
			$column["data"] = $columnIndex;
			
			if(isset( $column['orderable'] )) {
				$column["orderable"] = $columnInfo["orderable"];
			}
			
			if(isset( $columnInfo["sortable"] )) {
				$column["sortable"] = $columnInfo["sortable"];
			}
			
			if(isset( $columnInfo["className"] )) {
				$column["className"] = $columnInfo["className"];
			}
			
			$columnSet[] = $column;

		}
		
		return $columnSet;
		
	}
	
	/**
	 * Fetch elastic search formatted search query based on the input parameters
	 * passed in to this function.
	 */
	 
	private function fetchParams( $datasetID, $typeID, $status, $searchTerm, $start, $length ) {
		
		$params = array(
			"index" => "interactions",
			"type" => "interaction",
			"body" => array(
				"from" => $start,
				"size" => $length
			)
		);
		
		$params["body"]["query"] = $this->fetchGlobalQueryParams( $datasetID, $typeID, $status, $searchTerm );
		$params["body"]["sort"] = $this->fetchSortParams( );
		
		return $params;
	
	}
	
	/**
	 * Fetch elastic search formatted query params based on the type of search being performed
	 * and the input parameters specificing what kind of searching is to be applied.
	 */
	 
	private function fetchGlobalQueryParams( $datasetID, $typeID, $status, $searchTerm = "" ) {
		
		// All queries are restricted to a specific 
		// dataset (publication) a specific type (like Binary/Complex/etc.)
		// and a specific status (activated/deactivated)
		
		$queryparams = array( "bool" => array( "must" => array( ) ) );
		$queryParams["bool"]["must"][] = array( "match" => array( "dataset_id" => $datasetID ));
		$queryParams["bool"]["must"][] = array( "match" => array( "interaction_type_id" => $typeID ));
		$queryParams["bool"]["must"][] = array( "match" => array( "history_status" => $status ));
		
		if( strlen( $searchTerm ) > 0 ) {
			$queryParams["bool"]["must"][] = array( "match" => array( "_all" => $searchTerm ) );
		}
		
		return $queryParams;
		
	}
	
	/** 
	 * Fetch elastic search formatted sort query based on the type of search being performed
	 * and the input parameters specificing what kind of sorting needs to be applied.
	 */
	 
	private function fetchSortParams( ) {
		
		// Default sorting is to sort the interactions from newest
		// to oldest based on the history_date document entry
		
		$sortParams = array( 
			"history_date" => array( "order" => "desc" )
		);
		
		return $sortParams;
	}
	
}