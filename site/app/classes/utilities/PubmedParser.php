<?php

namespace IMS\app\classes\utilities;

/**
 * PubmedParser
 * Parses out a Pubmed Article or Book into an
 * array that can be inserted into the database.
 */
 
use IMS\app\lib;
 
class PubmedParser {
	
	private $baseURL;
	private $params;
	private $pubInfo;
	private $isBook;
	private $pubYear;
	
	public function __construct( ) {
		$this->baseURL = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi";
		$this->params = array( );
		$this->params['db'] = "pubmed";
		$this->params['retMax'] = 1000000;
		$this->params['rettype'] = "text";
		$this->params['retmode'] = "xml";
		$this->params['email'] = ADMIN_EMAIL;
		$this->params['tool'] = "PubmedParser";
		$this->resetDefault( );
		$this->isBook = false;
		$this->citeType = "MedlineCitation";
	}
	
	/**
	 * Set the pubmed info array to a set of default values
	 */
	
	private function resetDefault( ) {
		$this->pubInfo = array( );
		$this->pubInfo['PUBMED_ID'] = "-";
		$this->pubInfo['TITLE'] = "-";
		$this->pubInfo['ABSTRACT'] = "-";
		$this->pubInfo['FULLTEXT'] = "-";
		$this->pubInfo['AUTHOR_SHORT'] = "-";
		$this->pubInfo['VOLUME'] = "-";
		$this->pubInfo['ISSUE'] = "-";
		$this->pubInfo['PUBDATE'] = "0000-00-00";
		$this->pubInfo['JOURNAL'] = "-";
		$this->pubInfo['JOURNAL_SHORT'] = "-";
		$this->pubInfo['PAGINATION'] = "-";
		$this->pubInfo['PMCID'] = "-";
		$this->pubInfo['DOI'] = "-";
		$this->pubInfo['ARTICLE_IDS'] = "-";
		$this->pubInfo['AFFILIATIONS'] = array( );
		$this->pubInfo['STATUS'] = "active";
		$this->pubInfo['MESH'] = array( );
		$this->pubInfo['AUTHORS'] = array( );
	}
	
	/**
	 * Parse out a pubmed ID
	 */
	 
	public function parse( $pubmedID ) {
		
		try {
			
			$this->params['id'] = $pubmedID;
			foreach( $this->params as $key => $val ) {
				$paramSet[] = $key . "=" . $val;
			}
			
			// Suppress warnings here, it will return false on problems
			// and we are equipped to handle returns of false in the 
			// application.
			
			if( @$pubmedDetails = file_get_contents( $this->baseURL . "?" . implode( "&", $paramSet )) ) {
				
				// Modify heading so we can include UTF-8 encoding
				$pubmedDetails = str_replace( '<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>', $pubmedDetails );
				
				// Suppress warnings here, if it's invalid xml we simply want
				// to return false, no need to output errors or warnings
				if( $parsedXML = @simplexml_load_string( $pubmedDetails ) ) {
					
					// Check if it's an article or 
					// a book, parsing is different depending on which
					$article = $parsedXML->PubmedArticle;
					if( $article == null || $article == "" ) {
						$article = $parsedXML->PubmedBookArticle;
						if( $article == null || $article == "" ) {
							// Neither book nor article
							// so we return false
							return false;
						}
						
						$this->isBook = true;
						$this->citeType = "BookDocument";
					}
					
					$this->parsePubmedID( $article );
					$this->parseTitle( $article );
					$this->parseAbstract( $article );
					$this->parseVolume( $article );
					$this->parseIssue( $article );
					$this->parseJournal( $article );
					$this->parsePagination( $article );
					$this->parseArticleIDs( $article );
					$this->parsePubDate( $article );
					$this->parseAuthors( $article );
					$this->parseMeshTerms( $article );
					$this->parseCommentCorrectionsList( $article );
					return $this->pubInfo;
					
				} 
			}
			
			return false;
		
		} catch( \Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Grab the Pubmed ID from the XML
	 */
	
	private function parsePubmedID( $article ) {
		
		$pubmedID = "-";
		$type = $this->citeType;
		$pubmedID = $article->$type->PMID;
		
		if( $pubmedID != null ) {
			$this->pubInfo['PUBMED_ID'] = trim($pubmedID);
		}
		
	}
	
	/**
	 * Grab the title from the XML
	 */
	 
	private function parseTitle( $article ) {
	 
		$type = $this->citeType;
		$entity = null;
		
		if( $this->isBook ) {
			$entity = $article->$type->ArticleTitle;
		} else {
			$entity = $article->$type->Article->ArticleTitle;
		}
		
		if( $entity != null && strlen(trim($entity)) > 0 ) {
			$this->pubInfo['TITLE'] = trim( $entity );
		}

	}
	
	/**
	 * Grab the abstracts if available. Needs to be done
	 * as an array, because some abstracts are broken into
	 * different components. Example: 18829411
	 */
	 
	private function parseAbstract( $article ) {
		
		$abstracts = array( );
		
		$type = $this->citeType;
		
		if( $this->isBook ) {
			$entity = $article->$type->Abstract->AbstractText;
		} else {
			$entity = $article->$type->Article->Abstract->AbstractText;
		}
		
		if( $entity != null && strlen($entity) > 0 ) {
			foreach( $entity as $abstract ) {
				
				$attributes = $abstract->attributes( );
				$label = "";
				if( $attributes->Label != null ) {
					$label = trim($attributes->Label) . ": ";
				}
				
				if( strlen(trim($abstract)) > 0 ) {
					$abstracts[] = $label . trim($abstract);
				}
				
				if( sizeof( $abstracts ) > 0 ) {
					$this->pubInfo['ABSTRACT'] = implode( "|", $abstracts );
				}
			}
		}
		
	}
	
	/**
	 * Parse out the article volume details, may not be numeric
	 */
	 
	private function parseVolume( $article ) {
		
		if( !$this->isBook ) {
			$type = $this->citeType;
			$entity = $article->$type->Article->Journal->JournalIssue->Volume;
			if( $entity != null && strlen($entity) > 0 ) {
				$this->pubInfo['VOLUME'] = trim( $entity );
			}
		}
		
	}
	
	/**
	 * Parse out the article issue details, may not be numeric
	 */
	 
	private function parseIssue( $article ) {
		
		if( !$this->isBook ) {
			$type = $this->citeType;
			$entity = $article->$type->Article->Journal->JournalIssue->Issue;
			if( $entity != null && strlen($entity) > 0 ) {
				$this->pubInfo['ISSUE'] = trim( $entity );
			}
		}
		
	}
	
	/**
	 * Parse out the article pagination details, may not be numeric
	 * Could exist but be empty: Example: PUBMED 19229185
	 */
	 
	private function parsePagination( $article ) {
		
		if( !$this->isBook ) {
			$type = $this->citeType;
			$entity = $article->$type->Article->Pagination->MedlinePgn;
			if( $entity != null && strlen($entity) > 0 ) {
				$pgnTxt = trim( $entity );
				if( strlen($pgnTxt) > 0 ) {
					$this->pubInfo['PAGINATION'] = trim( $entity );
				} 
			}
		}
		
	}
	
	/**
	 * Parse out all article ids and specifically isolate DOI and PMC ids
	 */
	 
	private function parseArticleIDs( $article ) {
		
		$entity = null;
		if( $this->isBook ) {
			$entity = $article->PubmedBookData->ArticleIdList->ArticleId;
		} else {
			$entity = $article->PubmedData->ArticleIdList->ArticleId;
		}
		
		$articleIDs = array( );
		if( $entity != null && strlen($entity) > 0 ) {
			foreach( $entity as $articleID ) {
				
				$attributes = $articleID->attributes( );
				if( $attributes->IdType != null ) {
					$idType = strtoupper($attributes->IdType);
					
					if( $idType == "PMC" ) {
						$this->pubInfo['PMCID'] = trim($articleID);
					} else if( $idType == "DOI" ) {
						$this->pubInfo['DOI'] = trim($articleID);
					}
					
					if( $idType != "PUBMED" ) {
						if( !isset( $articleIDs[$idType] ) ) {
							$articleIDs[$idType] = array( );
						}
						
						$articleIDs[$idType][] = trim($articleID);
					}	
				}
			}
		}
		
		if( sizeof($articleIDs) > 0 ) {
			$this->pubInfo['ARTICLE_IDS'] = json_encode( $articleIDs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES );
		}
		
	}
	
	/**
	 * Parse out Publication date and convert it to a 
	 * MySQL friendly date format.
	 */
	 
	private function parsePubDate( $article ) {
		
		$articleDate = array( );
		$type = $this->citeType;
		
		$entity = null;
		if( $this->isBook ) {
			$entity = $article->$type->ContributionDate->Day;
		} else {
			$entity = $article->$type->Article->Journal->JournalIssue->PubDate->Day;
		}
		
		if( $entity != null && strlen(trim($entity)) > 0 ) {
			$articleDate[] = trim($entity);
		} else {
			$articleDate[] = "1";
		}
		
		$entity = null;
		if( $this->isBook ) {
			$entity = $article->$type->ContributionDate->Month;
		} else {
			$entity = $article->$type->Article->Journal->JournalIssue->PubDate->Month;
		}
		
		if( $entity != null && strlen(trim($entity)) > 0 ) {
			$month = $this->monthSwap( trim($entity) );
			$articleDate[] = $month;
		} else {
			$articleDate[] = "Jan";
		}
		
		$entity = null;
		if( $this->isBook ) {
			$entity = $article->$type->ContributionDate->Year;
		} else {
			$entity = $article->$type->Article->Journal->JournalIssue->PubDate->Year;
		}
		
		if( $entity != null && strlen(trim($entity)) > 0 ) {
			$articleDate[] = trim($entity);
			$this->pubYear = trim($entity);
		} else {
			$articleDate[] = "1970";
			$this->pubYear = "1970";
		}
		
		$articleDate = implode( " ", $articleDate );
		$timestamp = strtotime( $articleDate );
		$this->pubInfo['PUBDATE'] = date( 'Y-m-d', $timestamp );
		
	}
	
	/**
	 * Convert from numeric month value to a 3 letter string version
	 */
	 
	private function monthSwap( $month ) {

		if( strlen($month) == 1 ) {
			$month = "0" . $month;
		}
		
		switch( $month ) {
			
			case "01" : return "Jan";
			case "02" : return "Feb";
			case "03" : return "Mar";
			case "04" : return "Apr";
			case "05" : return "May";
			case "06" : return "Jun";
			case "07" : return "Jul";
			case "08" : return "Aug";
			case "09" : return "Sep";
			case "10" : return "Oct";
			case "11" : return "Nov";
			case "12" : return "Dec";
			default: return $month;
		
		}
		
	}
	
	/**
	 * Process authors and build out several additional variables
	 * based on the data parsed.
	 */
	 
	private function parseAuthors( $article ) {
		
		$type = $this->citeType;
		
		$entity = null;
		if( $this->isBook ) {
			$entity = $article->$type->AuthorList->Author;
		} else {
			$entity = $article->$type->Article->AuthorList->Author;
		}
		
		if( $entity != null ) {
			foreach( $entity as $author ) {
				
				// Skip authors with authorValid = "N" because
				// this means that there are a correction and this
				// authors name was replaced by another
				
				$authorValid = "Y";
				
				$attributes = $author->attributes( );
				if( $attributes->ValidYN != null ) {
					$authorValid = strtoupper(trim($attributes->ValidYN));
				}
				
				if( "Y" == $authorValid ) {
				
					$authorRecord = array( "FIRSTNAME" => "-", "LASTNAME" => "-", "INITIALS" => "-", "AFFILIATION" => "-" );
					
					$firstname = $author->ForeName;
					if( $firstname != null && strlen($firstname) > 0 ) {
						$authorRecord['FIRSTNAME'] = trim($firstname);
					}
					
					$lastname = $author->LastName;
					if( $lastname != null && strlen($lastname) > 0 ) {
						$authorRecord['LASTNAME'] = trim($lastname);
					}
					
					$initials = $author->Initials;
					if( $initials != null ) {
						if( strlen($initials) > 0 ) {
							$authorRecord['INITIALS'] = trim($initials);
						} else {
							$initials = $author->Suffix;
							if( $initials != null && strlen($initials) > 0 ) {
								$authorRecord['INITIALS'] = trim($initials);
							}
						}
					}
					
					if( $authorRecord['INITIALS'] == "-" || $authorRecord['INITIALS'] == "" ) {
						$authorRecord['INITIALS'] = "UU";
					}
					
					// Build universal list of affiliations as well as attach affiliation
					// to the author entry
					
					$authorAffiliation = $author->AffiliationInfo->Affiliation;
					if( $authorAffiliation != null ) {
						$authorRecord['AFFILIATION'] = trim($authorAffiliation);
						$this->pubInfo['AFFILIATIONS'][] = trim($authorAffiliation);
					}
					
					// Skip Collectives
					if( $authorRecord['LASTNAME'] != "-" ) {
						$this->pubInfo['AUTHORS'][] = $authorRecord;
					}
					
				}
			}
		}
				
		if( sizeof( $this->pubInfo['AUTHORS'] ) > 0 ) {
			reset( $this->pubInfo['AUTHORS'] );
			$firstAuthor = current($this->pubInfo['AUTHORS']);
			$this->pubInfo['AUTHOR_SHORT'] = $firstAuthor['LASTNAME'] . " " . $firstAuthor['INITIALS'] . " (" . $this->pubYear . ")";
		} else {
			$this->pubInfo['AUTHOR_SHORT'] = "Unknown Authors (" . $this->pubYear . ")";
		}
		
	}
	
	/**
	 * Parse out the Article Journal Details
	 * Some Journal fields are not numeric. Example: PUBMED 18475251
	 */
	 
	private function parseJournal( $article ) {
	 
		$journal = "-";
		$type = $this->citeType;
	
		$entity = null;
		if( $this->isBook ) {
			$entity = $article->$type->Book->BookTitle;
		} else {
			$entity = $article->$type->Article->Journal->Title;
		}
		
		if( $entity != null && strlen($entity) > 0 ) {
			$this->pubInfo['JOURNAL'] = trim( $entity );
			if( $this->isBook ) {
				$this->pubInfo['JOURNAL_SHORT'] = $this->pubInfo['JOURNAL'];
			}
		}
		
		if( !$this->isBook ) {
			$entity = $article->$type->Article->Journal->ISOAbbreviation;
			if( $entity != null ) {
				$this->pubInfo['JOURNAL_SHORT'] = trim( $entity );
			}
		}
		

	}
	
	/** 
	 * Parse out Mesh Terms and Mesh Term Qualifiers
	 */
	 
	private function parseMeshTerms( $article ) {
		
		if( !$this->isBook ) {
		
			$type = $this->citeType;
			$entity = $article->$type->MeshHeadingList->MeshHeading;
			
			if( $entity != null ) {
				foreach( $entity as $meshtag ) {
					
					$attributes = $meshtag->attributes( );
					$meshRecord = array( "DESCRIPTOR" => "-", "ID" => "-", "MAJOR" => "N", "QUALIFIERS" => array( ) );

					$meshDescriptor = $meshtag->DescriptorName;
					if( $meshDescriptor != null ) {
						$meshRecord['DESCRIPTOR'] = trim($meshDescriptor);
						
						if( $attributes->UI != null ) {
							$meshRecord['ID'] = trim($attributes->UI);
						}
						
						if( $attributes->MajorTopicYN != null ) {
							$meshRecord['MAJOR'] = trim($attributes->MajorTopicYN);
						}
						
						foreach( $meshtag->QualifierName as $qualifier ) {
							
							if( $qualifier != null ) {
								
								$attributes = $qualifier->attributes( );
								$meshQualifier = array( "NAME" => "-", "ID" => "-", "MAJOR" => "-" );
								
								$meshQualifier['NAME'] = trim($qualifier);
						
								if( $attributes->UI != null ) {
									$meshQualifier['ID'] = trim($attributes->UI);
								}
								
								if( $attributes->MajorTopicYN != null ) {
									$meshQualifier['MAJOR'] = trim($attributes->MajorTopicYN);
								}
								
								$meshRecord['QUALIFIERS'][] = $meshQualifier;
								
							}
							
						}
						
						$this->pubInfo['MESH'][] = $meshRecord;
						
					}
				}
			}
		}
	}
	
	/**
	 * Parse out whether a paper has corrections or is retracted
	 */
	
	private function parseCommentCorrectionsList( $article ) {
		
		if( !$this->isBook ) {
			
			$type = $this->citeType;
			$entity = $article->$type->CommentsCorrectionsList->CommentsCorrections;
			
			if( $entity != null ) {
				foreach( $entity as $correction ) {
					$attributes = $correction->attributes( );
					$refType = $attributes->RefType;
					
					if( $refType != null ) {
						$refType = strtoupper($refType);
						
						if( "RETRACTIONIN" == $refType ) {
							$this->pubInfo['STATUS'] = "retracted";
						}
						
						if( "PARTIALRETRACTIONIN" == $refType && $this->pubInfo['STATUS'] != "retracted" ) {
							$this->pubInfo['STATUS'] = "partialretracted";
						}
						
						if( "ERRATUMIN" == $refType && $this->pubInfo['STATUS'] != "retracted" && $this->pubInfo['STATUS'] != "partialretracted" ) {
							$this->pubInfo['STATUS'] = "erratum";
						}
					}
				}
			}
			
		}
		
	}

}

?>