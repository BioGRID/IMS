# Tools for Processing Pubmed Results to the Database

import sys, string
import Config
import Database
import json

class DatabaseProcessor( ) :

	"""Take Pubmed Data Parsed Out and Dump it Correctly to Database"""

	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		
	def processPubmed( self, data ) :
	
		"""Update the Record in Pubmed with latest data parsed from the API"""
		
		authorList, authorFull = self.processAuthors( data['AUTHORS'] )
		affiliations = self.processAffiliations( data['AFFILIATIONS'] )
		meshTerms = self.processMeshTerms( data['MESH'] )
	
		self.cursor.execute( "UPDATE " + Config.DB_IMS + ".pubmed SET " +
			"pubmed_title=%s, pubmed_abstract=%s, pubmed_author_short=%s, pubmed_author_list=%s, pubmed_author_full=%s," +
			"pubmed_volume=%s, pubmed_issue=%s, pubmed_date=%s, pubmed_journal=%s, pubmed_journal_short=%s, pubmed_pagination=%s," +
			"pubmed_affiliations=%s, pubmed_meshterms=%s, pubmed_pmcid=%s, pubmed_addeddate=NOW( ), pubmed_lastupdated=NOW( )" +
			"WHERE pubmed_id=%s", [data['TITLE'], data['ABSTRACT'], data['AUTHOR_SHORT'], authorList, authorFull, data['VOLUME'], data['ISSUE'],
			data['PUBDATE'], data['JOURNAL'], data['JOURNAL_SHORT'], data['PAGINATION'], affiliations, meshTerms, data['PMCID'], data['PUBMED_ID']] )
			
	def processMeshTerms( self, meshTerms ) :
	
		"""Convert Mesh Terms into the Datbaase Format Required"""
		
		return json.dumps( meshTerms, ensure_ascii=False ).encode( 'utf8' )
		
	def processAuthors( self, authorSet ) :
	
		"""Build nice list of Authors with Initials and json the full set with affiliations"""
	
		authorList = []
		for author in authorSet :
			authorName = author['LASTNAME']
			
			if author['INITIALS'] != "-" :
				authorName = authorName + " " + author['INITIALS']
				
			authorList.append( authorName )
			
		return ", ".join( authorList ), json.dumps( authorSet, ensure_ascii=False ).encode( 'utf8' )
		
	def processAffiliations( self, affiliations ) :
		
		"""Get a final list of affiliations and convert to json"""
		
		return json.dumps( self.buildOrderedAffiliationSet( affiliations ), ensure_ascii=False ).encode( 'utf8' )
	
	def buildOrderedAffiliationSet( self, affiliations ) :
	
		"""Build a unique set of affiliations but maintain the original ordering"""
		
		seen = set( )
		seen_add = seen.add
		return [x for x in affiliations if not (x.upper( ) in seen or seen_add(x.upper( )))]