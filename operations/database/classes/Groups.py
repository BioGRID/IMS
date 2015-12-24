import sys, string
import Config
import re
import datetime

from classes import Maps, Lookups, DBProcessor

class Groups( ) :

	"""Tools for Handling the Migration of Project Data from IMS 2 to IMS 4 Groups"""
	
	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		self.lookups = Lookups.Lookups( db, cursor )
		
		# Build Quick Reference Data Structures
		self.validDatasets = self.lookups.buildValidDatasetSet( )
		self.datasetHash = self.lookups.buildPubmedToDatasetHash( )
		
	def migrateGroups( self ) :
		
		"""
		Copy Operation 
		     -> IMS2: projects to 
			 -> IMS4: groups
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".projects" )
		for row in self.cursor.fetchall( ) :
			
			projectStatus = "public"
			if row['project_status'] == "closed" or row['project_status'] == "private" :
				projectStatus = "private"
		
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".groups VALUES( %s, %s, %s, %s, %s, %s )" , [
				row['project_id'], 
				row['project_name'], 
				row['project_description'], 
				row['organism_id'], 
				row['project_timestamp'], 
				projectStatus
			])
		
		self.db.commit( )
		
	def migrageGroupUsers( self ) :
	
		"""
		Copy Operation 
		     -> IMS2: project_users to 
			 -> IMS4: group_users
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".project_users" )
		for row in self.cursor.fetchall( ) :
			
			mappingDate = row['project_users_timestamp']
			if None == mappingDate :
				mappingDate = datetime.datetime.now( )
			
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".group_users VALUES( %s, %s, %s, %s, %s )" , [
				'0',
				row['project_id'], 
				row['user_id'], 
				mappingDate, 
				'active'
			])
		
		self.db.commit( )
		
	def migrateGroupDatasets( self ) :
	
		"""
		Copy Operation 
		     -> IMS2: project_pubmeds to 
			 -> IMS4: group_datasets
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".project_pubmeds" )
		for row in self.cursor.fetchall( ) :
		
			if str(row['pubmed_id']) not in self.datasetHash :
			
				insertData = [row['pubmed_id'], '-','-','-','-','-','-','-','-','0000-00-00','-','-','-','-','-','-','-','-','active',row['project_pubmed_timestamp'],row['project_pubmed_timestamp'], '0']
				
				sqlFormat = ",".join( ['%s'] * len(insertData) )
				self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".pubmed VALUES( %s )" % sqlFormat, insertData )
				
				self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".datasets VALUES( '0', %s, '1', '0', '0', 'public', NOW( ), 'active' )", [row['pubmed_id']] )
				datasetID = self.cursor.lastrowid
				
				self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".dataset_history VALUES( '0','ACTIVATED', %s, '1', 'New Dataset', %s )", [datasetID, row['project_pubmed_timestamp']] )
				
				self.datasetHash[str(row['pubmed_id'])] = str(datasetID)
			
			datasetID = self.datasetHash[str(row['pubmed_id'])]
		
			if datasetID in self.validDatasets :
				self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".group_datasets VALUES( %s, %s, %s, %s, %s )", [
					row['project_pubmed_id'],
					row['project_id'],
					row['pubmed_id'],
					row['project_pubmed_timestamp'],
					row['project_pubmed_status']
				])
				
		self.db.commit( )