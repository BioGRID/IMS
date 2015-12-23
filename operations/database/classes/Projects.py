import sys, string
import Config
import re
import datetime

from classes import Maps, Lookups, DBProcessor

class Projects( ) :

	"""Tools for Handling the Migration of Iplex Projects and Genetags from IMS 2 to IMS 4 Projects and Participant Attributes"""
	
	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		self.lookups = Lookups.Lookups( db, cursor )
		self.maps = Maps.Maps( )
		self.dbProcessor = DBProcessor.DBProcessor( db, cursor )
		
		# Build Quick Reference Data Structures
		self.genetagHash = self.lookups.buildGenetagIDHash( )
		
	def migrateProjects( self ) :
		
		"""
		Copy Operation 
		     -> IMS2: iplex_projects to 
			 -> IMS4: projects
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".iplex_projects" )
		for row in self.cursor.fetchall( ) :
		
			attributeID = self.maps.convertGenetagTypeIDFromProject( row['genetag_type_id'] )
		
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".projects VALUES( %s, %s, %s, %s, %s, %s, %s, %s )" , [
				row['iplex_project_id'], 
				row['iplex_project_name'], 
				row['iplex_project_fullname'],
				row['iplex_project_description'],
				attributeID,
				'private',
				row['iplex_project_addeddate'], 
				row['iplex_project_status']
			])
		
		self.db.commit( )
		
	def migrateProjectColumns( self ) :
	
		"""
		Copy Operation 
		     -> IMS2: iplex_columns to 
			 -> IMS4: project_columns
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".iplex_columns" )
		for row in self.cursor.fetchall( ) :
		
			attributeID = self.maps.convertGenetagTypeIDFromProject( row['genetag_type_id'] )
		
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".project_columns VALUES( %s, %s, %s, %s, %s, %s, %s )" , [
				row['iplex_column_id'], 
				row['iplex_column_title'], 
				attributeID,
				row['genetag_mapping_rank'],
				row['iplex_column_addeddate'], 
				row['iplex_column_status'],
				row['iplex_project_id']
			])
		
		self.db.commit( )
		
	def migrateProjectAttributes( self ) :
	
		"""
		Copy Operation
			-> IMS2: genetags/genetag_mappings to
			-> IMS4: participant_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".genetag_mappings WHERE genetag_evidence_type_id IN ( SELECT participant_attribute_evidence_id FROM " + Config.DB_IMS + ".participant_attribute_evidence )" )
		
		for row in self.cursor.fetchall( ) :
		
			attributeVal = self.genetagHash[str(row['genetag_id'])]
			participant = str(row['gene_id'])
			evidenceValue = str(row['genetag_evidence_value'])
			evidenceText = str(row['genetag_evidence_value_text'])
			evidenceMethod = str(row['genetag_mapping_method'])
			evidenceID = str(row['genetag_evidence_type_id'])
			mappingDate = row['genetag_mapping_addeddate']
			mappingStatus = row['genetag_mapping_status']
			
			participantID = self.dbProcessor.addParticipant( participant, "1", mappingDate )
			
			self.dbProcessor.processParticipantAttribute( participantID, attributeVal, "35", mappingDate, "0", "1", mappingStatus, evidenceValue, evidenceText, evidenceMethod, evidenceID )
			
		self.db.commit( )