import sys, string
import Config
import re
import datetime

class Datasets( ) :

	"""Tools for Handling the Migration of Dataset Data from IMS 2 to IMS 4"""

	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		self.validatePub = re.compile( '^([0-9]{1,8})$' )
		
	def migratePubmedMappings( self ) :
		
		"""
		Copy Operation 
		     -> IMS2: pubmed_mappings to 
			 -> IMS4: pubmed_mappings
		"""
		
		self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".pubmed_mappings ( SELECT * FROM " + Config.DB_IMS_OLD + ".pubmed_mappings )" )
		self.db.commit( )
		
	def migratePubmedQueries( self ) :
		
		"""
		Copy Operation 
		     -> IMS2: pubmed_queries to 
			 -> IMS4: pubmed_queries
		"""
		
		self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".pubmed_queries ( SELECT * FROM " + Config.DB_IMS_OLD + ".pubmed_queries )" )
		self.db.commit( )
		
	def migratePubmed( self ) :
	
		"""
		Copy Operation 
		     -> IMS2: publications to 
			 -> IMS4: pubmed
		"""
		
		dateFormat = "%Y-%m-%d %H:%M:%S"
		
		self.cursor.execute( "TRUNCATE TABLE " + Config.DB_IMS_TRANSITION + ".invalid_publications" )
		self.cursor.execute( "SELECT publication_id, publication_pubmed_id, publication_status, publication_modified FROM " + Config.DB_IMS_OLD + ".publications WHERE publication_pubmed_id NOT LIKE '99990000%'" )
		for row in self.cursor.fetchall( ) :
		
			skipPub = False
			if not self.validatePubmed( row['publication_pubmed_id'] ) :
				if not self.hasData( row['publication_id'] ) :
					skipPub = True
				else :
					print "--------- > One with Data: " + str(row['publication_id'])
					self.cursor.execute( "INSERT INTO " + Config.DB_IMS_TRANSITION + ".invalid_publications VALUES( %s )", [row['publication_id']] )
					skipPub = True
					
			elif self.hasDuplicate( row['publication_pubmed_id'] ) :
				if not self.hasData( row['publication_id'] ) :
					skipPub = True
				else :
					print "--------- > Duplicate with Data: " + str(row['publication_id'])
					self.cursor.execute( "INSERT INTO " + Config.DB_IMS_TRANSITION + ".invalid_publications VALUES( %s )", [row['publication_id']] )
					skipPub = True
		
			if not skipPub :
				insertData = [row['publication_pubmed_id'].strip( )]
				insertData = insertData + (["-"] * 7)
				insertData.append( "0000-00-00" )
				insertData = insertData + (["-"] * 6)
				insertData = insertData + [row['publication_status'], row['publication_modified'].strftime(dateFormat), row['publication_modified'].strftime(dateFormat), '0'] 
				#print insertData
				
				sqlFormat = ",".join( ['%s'] * len(insertData) )
				self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".pubmed VALUES( %s )" % sqlFormat, insertData )
				
				self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".datasets VALUES( %s, %s, '1', '0', '0', 'public', %s, 'active' )", [row['publication_id'], row['publication_pubmed_id'].strip( ), row['publication_modified'].strftime(dateFormat)] )
				
		self.db.commit( )
		
	def migratePrepub( self ) :
	
		"""
		Copy Operation 
		     -> IMS2: publications to 
			 -> IMS4: prepub
		"""
		
		dateFormat = "%Y-%m-%d %H:%M:%S"
		
		self.cursor.execute( "SELECT publication_id, publication_article_title, publication_abstract, publication_author, publication_author_full, publication_date, publication_affiliation, publication_status, publication_modified FROM " + Config.DB_IMS_OLD + ".publications WHERE publication_pubmed_id LIKE '99990000%'" )
		for row in self.cursor.fetchall( ) :
			
			skipPub = False
			if not self.hasData( row['publication_id'] ) :
				skipPub = True
			
			if not skipPub :
			
				insertData = ['0', row['publication_article_title'], row['publication_abstract'], row['publication_author'], row['publication_author_full'], row['publication_date'], row['publication_affiliation'], "-", "-1", row['publication_status'], row['publication_modified'].strftime(dateFormat), row['publication_modified'].strftime(dateFormat)]
				
				sqlFormat = ",".join( ['%s'] * len(insertData) )
				self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".prepub VALUES( %s )" % sqlFormat, insertData )
				prepubID = self.cursor.lastrowid
				
				self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".datasets VALUES( %s, %s, '2', '0', '0', 'website-only', %s, 'active' )", [row['publication_id'], prepubID, row['publication_modified'].strftime(dateFormat)] )
				
		self.db.commit( )
		
	def migrateHistory( self ) :
	
		"""
		Copy Operation
			-> IMS2: progress
			-> IMS4: dataset_history
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".progress WHERE publication_id IN ( SELECT dataset_id FROM " + Config.DB_IMS + ".datasets )" )
		for row in self.cursor.fetchall( ) :
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".dataset_history VALUES( %s, %s, %s, %s, %s, %s )" , [
				'0', 
				row['progress_type'], 
				row['publication_id'], 
				row['user_id'], 
				'-', 
				row['progress_timestamp']
			])
		
	def validatePubmed( self, pubmedID ) :
		
		"""Validate that the pubmed ID is properly formatted"""
		if self.validatePub.match( pubmedID.strip( ) ) :
			return True
			
		return False
		
	def hasDuplicate( self, pubmedID ) :
	
		"""Check to see if a publication has a duplicate entry already in place"""
		
		self.cursor.execute( "SELECT pubmed_id FROM " + Config.DB_IMS + ".pubmed WHERE pubmed_id=%s LIMIT 1", [str(pubmedID)] )
		row = self.cursor.fetchone( )
		
		if None == row :
			return False
			
		return True
		
	def hasData( self, publicationID ) :
	
		"""Test to see if a paper has interaction data associated with it, to determine if we can ignore it"""
		
		recordCount = 0
		recordCount += self.getMatchCount( publicationID, "complexes" )
		recordCount += self.getMatchCount( publicationID, "interactions" )
		recordCount += self.getMatchCount( publicationID, "chemical_mappings" )
		recordCount += self.getMatchCount( publicationID, "interaction_forced_additions" )
		recordCount += self.getMatchCount( publicationID, "ptms" )
		
		if recordCount > 0 :
			return True
			
		return False
			
	def getMatchCount( self, publicationID, table ) :
	
		"""Get the number of rows in a specific table where publicationID matches"""
		
		self.cursor.execute( "SELECT count(*) as intCount FROM " + Config.DB_IMS_OLD + "." + table + " WHERE publication_id=%s", [publicationID] )
		row = self.cursor.fetchone( )
		return row['intCount']