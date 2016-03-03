<?php

namespace IMS\app\classes\models;

/**
 * Interaction Tables
 * Methods for creating and outputting interaction tables with
 * customized options when fetching interaction data.
 */

use \PDO;
use IMS\app\classes\models\ElasticSearch;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

class InteractionTables {
	
	private $es;
	private $log;
	private $db;
	
	function __construct( ) {
		$this->es = new ElasticSearch( );
		$this->db = new PDO( DB_CONNECT, DB_USER, DB_PASS );
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$this->log = new Logger( 'Load Interactions Log' );
		$this->log->pushHandler( new StreamHandler( __DIR__ . '/../../../www/logs/LoadInteractions.log', Logger::DEBUG ));
	}

	/**
	 * Fetch interaction data for the table with specific parameters passed in
	 * to manipulate exactly what is shown to the user.
	 */
	 
	public function fetchInteractions( $datasetID, $typeID, $status, $searchTerm, $start, $length, $order, $columns ) {
		$params = $this->fetchParams( $datasetID, $typeID, $status, $searchTerm, $start, $length, $order, $columns );
		return $this->es->search( $params );
	}
	
	/**
	 * Grab columns definitions from the database for the type of
	 * interaction whose data we are going to display
	 */
	 
	public function fetchColumns( $typeID ) {
		
		$stmt = $this->db->prepare( "SELECT interaction_type_columns FROM " . DB_IMS . ".interaction_types WHERE interaction_type_id=?" );
		$stmt->execute( array( $typeID ) );
		
		$columns = array( );
		if( $row = $stmt->fetch( PDO::FETCH_OBJ ) ) {
			$columns = json_decode( $row->interaction_type_columns, true );
		} 
		
		return $columns;
		
	}
	
	/**
	 * Format results fetched from Elastic Search to display as a table
	 * with correct column configuration based on type.
	 */
	
	public function formatResults( $response, $columns, $typeID ) {
		
		$data = array( );
		if( $response['hits']['total'] > 0 ) {
			foreach( $response['hits']['hits'] as $hit ) {
				$data[] = $this->formatRow( $hit, $columns, $typeID );
			}
		}
		
		return $data;
		
	}
	
	/**
	 * Fetch formatted columns based on the column definition information and the data in a specific
	 * document fetched from ElasticSearch
	 */
	 
	private function formatRow( $hit, $columns, $typeID ) {
		
		$row = array( );
		foreach( $columns as $columnIndex => $columnInfo ) {
			
			$colVal = "";
			if( $columnInfo['type'] == "direct" ) {
				$colVal = $hit['_source'][$columnInfo['value']];
				$colVal = $this->formatCol( $colVal, $columnInfo, array( ), $typeID );
			} else if( $columnInfo['type'] == "participant" ) {
				$colVal = $this->fetchParticipants( $hit['_source']['participants'], $columnInfo, $typeID );
				$colVal = implode( ", ", $colVal );
			} else if( $columnInfo['type'] == "attribute" ) {
				$colVal = $this->fetchAttributes( $hit['_source']['attributes'], $columnInfo, $typeID );
				$colVal = implode( ", ", $colVal );
			} else if( $columnInfo['type'] == "attribute_icons" ) {
				$colVal = $this->fetchAttributeIcons( $hit['_source']['attributes'], $columnInfo, $typeID );
				if( sizeof( $colVal ) > 0 ) {
					$colVal = implode( " ", $colVal );
				} else {
					$colVal = "-";
				}
			}
			
			if($hit['_source']['history_status'] == 'DISABLED') {
				$row['DT_RowClass'] = "disabled";
			} else if($hit['_source']['interaction_state'] == "error" ) {
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
	 
	private function fetchParticipants( $participants, $columnInfo, $typeID ) {
		
		$participantList = array( );
		foreach( $participants as $participant ) {
			
			if( $this->matchesQueries( $participant, $columnInfo['query'] )) {
				$participantList[] = $this->formatCol( $participant[$columnInfo['value']], $columnInfo, $participant, $typeID );
			}
			
		}
		
		if( isset( $columnInfo['list'] ) && $columnInfo['list'] == "unique" ) {
			$participantList = array_unique( $participantList );
		}
		
		return $participantList;
		
	}
	
	/**
	 * Step through each attribute and find the ones we are looking for to display
	 * for this particular column.
	 */
	 
	private function fetchAttributes( $attributes, $columnInfo, $typeID ) {
	
		$attributeList = array( );
		foreach( $attributes as $attribute ) {
			
			if( $this->matchesQueries( $attribute, $columnInfo['query'] )) {
				$attributeList[] = $this->formatCol( $attribute[$columnInfo['value']], $columnInfo, $attribute, $typeID );
			}
			
		}
		
		return $attributeList;
	
	}
	
	/**
	 * Step through each attribute and build out icons based on what we want to
	 * display for each type.
	 */
	 
	private function fetchAttributeIcons( $attributes, $columnInfo, $typeID ) {
		
		$attributeList = array( );
		$attributeChildren = array( );
		
		foreach( $attributes as $attribute ) {
			
			if( !$this->matchesIgnores( $attribute, $columnInfo['ignore'] ) ) {
				if( !isset( $attributeList[$attribute['attribute_type_category_id']]) && $attribute['attribute_parent_id'] == 0 ) {
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

				$content[] = $this->fetchAttributeContent( $attribute, $children, $typeID );
				
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
	 
	private function fetchCombinedAttributeIcon( $attributes, $typeID ) {
		
		$attributeList = array( );
		foreach( $attributes as $attribute ) {
			$attributeList[] = $this->fetchAttributeContent( $attribute, array( ), $typeID );
		}
		
		$content = "<span class='attributeContent'><ul><li>" . implode( "</li><li>", $attributeList ) . "</li></ul></span>";
		$icon = $this->fetchAttributeIcon( "0" );
		
		return " <span class='attribute'>" . $icon . $content . "</span>";
		
	}
	
	/** 
	 * Fetch an attributes content based on the attribute category type ID and the data
	 * for the attribute that was passed in
	 */
	 
	private function fetchAttributeContent( $attribute, $children, $typeID ) {
		
		$term = "<strong>" . $attribute['attribute_type_name'] . " [<a data-type='" . $typeID . "' class='dataTable-attribute-searchTag'>@" . $attribute['attribute_type_shortcode'] . "</a>]</strong>: ";
		
		// Ontology Term
		if( $attribute['attribute_type_category_id'] == "1" ) { 
			$term .= $attribute['attribute_value'] . " (<span class='dataTable-attribute-value'>" . $attribute['ontology_term_official_id'] . "</span>)";
		} else {
			$term .= "<span class='dataTable-attribute-value'>" . $attribute['attribute_value'] . "</span>";
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
		$size = "lg";
		
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
				$icon = "info-circle";
				$title = "Attributes";
				$size = "sm";
				break;
			
		}
		
		return "<i data-title='" . $title . "' class='attributeIcon fa fa-" . $size . " fa-" . $icon . "'></i>";
		
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
	 * Test to see if the nested value in the elastic search document
	 * matches a set of ignore parameters
	 */
	 
	private function matchesIgnores( $nestedDoc, $ignores ) {
	
		foreach( $ignores as $ignore ) {
			foreach( $ignore as $ignoreIndex => $ignoreVal ) {
				if( isset( $nestedDoc[$ignoreIndex] ) && $nestedDoc[$ignoreIndex] == $ignoreVal ) {
					return True;
				}
			}
		}
		
		return False;
	
	}
	
	/**
	 * Format a row based on what info is passed in, and the parameters specified
	 * as to how it should be generated.
	 */
	 
	private function formatCol( $value, $columnInfo, $data = array( ), $typeID ) {
		
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
		} else if( isset( $data['attributes'] ) && sizeof( $data['attributes'] ) > 0 && !isset( $columnInfo['noattribs'] ) ) {
			$value = $value . $this->fetchCombinedAttributeIcon( $data['attributes'], $typeID );
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
				
				return '<span class="participantWrap"><a data-title="' . $value . '" class="participantPopover"><strong>' . $value . '</strong></a><span class="participantContent"><ul><li>' . implode( "</li><li>", $participantContent ). '</li></ul></span></span>';
				
		}
		
		return $value;
	}

	/**
	 * Fetch the column headers to be displayed based on the column structure for this 
	 * interaction type
	 */
	 
	public function fetchColumnHeaderDefinitions( $columns, $type ) {
		
		$columnSet = array( );
		foreach( $columns as $columnIndex => $columnInfo ) {
			
			$column = array( );
			$column["title"] = $columnInfo["title"];
			$column["data"] = $columnIndex;
			
			if(isset( $columnInfo['search'] ) && strlen($columnInfo['search']) > 0) {
				$column["title"] .= " [<a data-type='" . $type . "' class='dataTable-searchTag'>" . $columnInfo["search"] . "</a>]";
			}
			
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
	 
	private function fetchParams( $datasetID, $typeID, $status, $searchTerm, $start, $length, $order, $columns ) {
		
		$params = array(
			"index" => "interactions",
			"type" => "interaction",
			"body" => array(
				"from" => $start,
				"size" => $length
			)
		);
		
		$params["body"]["query"] = $this->fetchGlobalQueryParams( $datasetID, $typeID, $status, $searchTerm, $columns );
		$params["body"]["sort"] = $this->fetchSortParams( $order, $columns );
		
		return $params;
	
	}
	
	/**
	 * Fetch elastic search formatted query params based on the type of search being performed
	 * and the input parameters specificing what kind of searching is to be applied.
	 */
	 
	private function fetchGlobalQueryParams( $datasetID, $typeID, $status, $searchTerm = "", $columns ) {
		
		// All queries are restricted to a specific 
		// dataset (publication) a specific type (like Binary/Complex/etc.)
		// and a specific status (activated/deactivated)
		
		$queryparams = array( "filtered" => array( "filter" => array( "bool" => array( "must" => array( ) ) ) ) );
		$querySet = array( );
		$querySet[] = array( "term" => array( "dataset_id" => $datasetID ));
		$querySet[] = array( "term" => array( "interaction_type_id" => $typeID ));
		$querySet[] = array( "term" => array( "history_status" => $status ));
		
		if( strlen( $searchTerm ) > 0 ) {
			$searchTerms = $this->tokenizeSearchTerm( $searchTerm );
			if( sizeof( $searchTerms ) > 0 ) {
				$searchQueries = $this->generateSearchQueries( $searchTerms, $columns );
				if( sizeof( $searchQueries ) > 0 ) {
					foreach( $searchQueries as $query ) {
						$querySet[] = $query;
					}
				}
			}
			//$queryParams["bool"]["must"][] = array( "match" => array( "_all" => $searchTerm ) );
			// $queryParams["bool"]["must"][] = array( 
				// "nested" => array( 
					// "path" => "participants",
					// "query" => array( 
						// "bool" => array( 
							// "must" => array( 
								// array( "match" => array( "participants.participant_role_id" => "2" )),
								// array( "match" => array( "participants.primary_name" => "FRK1" ))
							// )
						// )
					// )
				// )
			// );
			
			// $queryParams["bool"]["must"][] = array( 
				// "nested" => array( 
					// "path" => "attributes",
					// "query" => array( 
						// "bool" => array( 
							// "must" => array( 
								// array( "match" => array( "attributes.attribute_value" => $searchTerm ))
							// )
						// )
					// )
				// )
			// );
		}
		
		$queryParams["filtered"]["filter"]["bool"]["must"] = $querySet;
		print_r( $queryParams );
		return $queryParams;
		
	}
	
	/**
	 * Tokenize the search term passed in and return for use in creating search params
	 */
	 
	public function tokenizeSearchTerm( $searchTerm ) {
		
		$searchTerms = array( );
		$searchTerm = trim( $searchTerm );
		$tokenizedTerms = str_getcsv( $searchTerm, " ", '"' );
		
		foreach( $tokenizedTerms as $term ) {
			
			$searchTag = "_all";
			$searchTerm = $term;
			if( preg_match( '/^([#@][A-Z-]+):(.*)$/iUs', $term, $matches )) {
				$searchTag = $matches[1];
				$searchTerm = $matches[2];
			}
			
			if( !isset( $searchTerms[$searchTag] )) {
				$searchTerms[$searchTag] = array( );
			}
			
			$searchTerms[$searchTag][] = $searchTerm;
			
		}
		
		return $searchTerms;
		
	}
	
	/**
	 * Convert a set of tokenized search terms into the
	 * queries to be sent in our request to elastic search
	 */
	 
	private function generateSearchQueries( $searchTerms, $columns ) {
		
		$searchQueries = array( );
		foreach( $searchTerms as $searchTag => $termSet ) {
			$searchQuery = $this->generateSearchQuery( $searchTag, $termSet, $columns );
			if( sizeof( $searchQuery ) > 0 ) {
				$searchQueries[] = $searchQuery;
			}
		}
		
		return $searchQueries;
		
	}
	
	/**
	 * Create a search query for elastic search based on the
	 * information passed in
	 */
	 
	private function generateSearchQuery( $tag, $terms, $columns ) {
		
		$tag = trim(strtoupper($tag));
		$tagType = $tag[0];
		
		$searchQuery = array( );
		switch( $tagType ) {
			
			case "@" :
			case "#" :
				$searchQuery = $this->processHashSearchTag( $tag, $terms, $columns );
				break;
			
			default :
				$searchQuery = array( "match" => array( "_all" => implode( " ", $terms ) ) );
				break;
				
		}
		
		return $searchQuery;
		
	}
	
	/**
	 * Process search tags that begin with a # which comprises both 
	 * participants and direct search terms.
	 */
	 
	private function processHashSearchTag( $tag, $terms, $columns ) {
		
		if( sizeof( $terms ) <= 0 ) {
			return array( );
		}
		
		$column = array( );
		foreach( $columns as $columnIndex => $columnInfo ) {
			if( strtoupper($columnInfo['search']) == $tag ) {
				$column = $columnInfo;
				break;
			}
		}
		
		$searchQuery = array( );
		if( $column['type'] == "direct" ) {
			
			// Direct query is just a single term mapping entry
			$searchQuery = array( "term" => array( $column['value'] => strtolower(implode( ' ', $terms )) ) );
			
		} else if( $column['type'] == "participant" ) {
			
			// A participant query requires MUST matching entries for
			// limiting which nested entries we look at, and then SHOULD
			// matching entries for the actual keywords to search.
			$searchQuery = array( 
				"nested" => array( 
					"path" => "participants",
					"query" => array( 
						"bool" => array( 
							"must" => array( )
						)
					)
				)
			);
			
			$querySet = array( );
			
			// Must matching entries are based on the criteria set in the columns index
			foreach( $column['query'] as $queryIndex => $queryVal ) {
				$querySet[] = array( "term" => array( "participants." . $queryIndex => $queryVal ));
			}

			// Should matching entries are based on the keyword terms that were passed in
			if( sizeof( $terms ) > 0 ) {
				
				$queryList = array( );
				foreach( $terms as $term ) {
					$queryList[] = array( "term" => array( "participants." . $column['value'] => strtolower($term) ));
				}
				
			
				if( sizeof( $queryList ) > 1 ) {
					$querySet[] = array( "bool" => array( "should" => $queryList ) );
				} else {
					$querySet[] = $queryList;
				}
			
			}
			
			if( sizeof( $querySet ) > 0 ) {
				$searchQuery["nested"]["query"]["bool"]["must"] = $querySet;
			} else {
				$searchQuery = array( );
			}
		}
		
		
		return $searchQuery;
		
	}
	 
	/** 
	 * Fetch elastic search formatted sort query based on the type of search being performed
	 * and the input parameters specificing what kind of sorting needs to be applied.
	 */
	 
	private function fetchSortParams( $order, $columns ) {
		
		// Default sorting is to sort the interactions from newest
		// to oldest based on the history_date document entry
		
		// "participants.primary_name" => array( 
					// "order" => "desc",
					// "nested_path" => "participants",
					// "nested_filter" => array( 
						// "term" => array( "participants.participant_role_id" => 3 )
					// )
				// )
		
		$sortParams = array( );
		
		if( sizeof( $order ) > 0 ) {
			
			$columnInfo = $columns[$order[0]['column']];
			$direction = $order[0]['dir'];
			
			if( $columnInfo['type'] == "attribute" || $columnInfo['type'] == "participant" ) {
				
				$path = $columnInfo['type'] . "s";
				$sortTerm = $path . "." . $columnInfo['value'];
				
				$filters = array( );
				foreach( $columnInfo['query'] as $queryIndex => $queryVal ) {
					$filters[] = array( "term" => array( $path . "." . $queryIndex => $queryVal ));
				}
		
				$sortParams = array( 
					$sortTerm => array( 
						"order" => $direction,
						"nested_path" => $path,
						"nested_filter" => $filters
					)
				);
			
			} else {
				
				$sortParams = array( 
					$columnInfo['value'] => array( "order" => $direction )
				);
			}
			
		} 
		
		$this->log->addInfo( print_r( $sortParams, true ) );			
		return $sortParams;
	}
	
}