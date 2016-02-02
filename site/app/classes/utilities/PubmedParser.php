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
	}
	
	/**
	 * Parse out a pubmed ID
	 */
	 
	public function parse( $pubmedID ) {
		
		$this->params['id'] = $pubmedID;
		$paramSet = array( );
		foreach( $this->params as $key => $val ) {
			$paramSet[] = $key . "=" . $val;
		}
		
		if( $pubmedDetails = file_get_contents( $this->baseURL . "?" . implode( "&", $paramSet )) ) {
			
			// Modify heading so we can include UTF-8 encoding
			$pubmedDetails = str_replace( '<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>', $pubmedDetails );
			
			if( $parsedXML = simplexml_load_string( $pubmedDetails ) ) {
				$article = $parsedXML->PubmedArticle;
				if( $article == null ) {
					$article = $parsedXML->PubmedBookArticle;
					if( $article == null ) {
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
				$this->parseJournal( $article );
				return $this->pubInfo;
				
			}
		}
		
		return false;
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
	 
		$title = "-";
		$type = $this->citeType;
		$entity = $article->$type->Article->ArticleTitle;
		
		if( $this->isBook ) {
			$entity = $article->$type->ArticleTitle;
		}
		
		if( $entity != null ) {
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
	
	/**
	 * Parse out the article volume details, may not be numeric
	 */
	 
	private function parseVolume( $article ) {
		
		if( !$this->isBook ) {
			$type = $this->citeType;
			$entity = $article->$type->Article->Journal->JournalIssue->Volume;
			if( $entity != null ) {
				$this->pubInfo['VOLUME'] = trim( $entity );
			}
		}
		
	}
	
	/**
	 * Parse out the Article Journal Details
	 * Some Journal fields are not numeric. Example: PUBMED 18475251
	 */
	 
	private function parseJournal( $article ) {
	 
		$journal = "-";
		$type = $this->citeType;
		$entity = $article->$type->Article->Journal->Title;
		
		if( $this->isBook ) {
			$entity = $article->$type->Book->BookTitle;
		}
		
		if( $entity != null ) {
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

}

?>