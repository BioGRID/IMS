import sys, string
import Config
import datetime
import re

from classes import Maps, Lookups, DBProcessor

class Chemicals( ) :

	"""Tools for Handling the Migration of Chemical Mappings from IMS 2 to IMS 4"""

	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		self.maps = Maps.Maps( )
		self.lookups = Lookups.Lookups( db, cursor )
		self.dbProcessor = DBProcessor.DBProcessor( db, cursor )
		
		# Build Quick Reference Data Structures
		self.validDatasets = self.lookups.buildValidDatasetSet( )
		self.actionHash = self.lookups.buildChemicalActionHash( )
		self.sourceNameHash = self.lookups.buildSourceNameHash( )
		self.throughputHash = self.lookups.buildThroughputTagHash( )
		
	def migrateChemicalMappings( self ) :
		
		"""
		Copy Operation 
		     -> IMS2: chemical_mappings to 
			 -> IMS4: interactions
		"""
		
		intCount = 0
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".chemical_mappings ORDER BY chemical_mapping_id ASC" )
		for row in self.cursor.fetchall( ) :
			if str(row['publication_id']) in self.validDatasets :
				
				intCount += 1
				self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".interactions VALUES( %s, '-', '-', %s, %s, %s )", ['0', row['publication_id'], "1", "normal"] )
				interactionID = str(self.cursor.lastrowid)
				
				userID = str(row['user_id'])
				addedDate = row['chemical_mapping_addeddate']
				status = row['chemical_mapping_status']
				
				# History
				self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".history VALUES( '0', 'ACTIVATED', %s, %s, 'New Interaction', '1', %s )", [interactionID, userID, addedDate] )
				if status.upper( ) == "INACTIVE" :
					self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".history VALUES( '0', 'DISABLED', %s, %s, 'No Longer Valid in Drugbank', '7', %s )", [interactionID, userID, addedDate] )
				
				# Chemical Action
				actionID = self.actionHash[str(row['chemical_action_id'])]
				chemAttribID = self.dbProcessor.processInteractionAttribute( interactionID, actionID, "32", addedDate, "0", userID, 'active' )
				
				# Source
				sourceID = self.sourceNameHash[str(row['chemical_mapping_source']).lower( )]
				chemAttribID = self.dbProcessor.processInteractionAttribute( interactionID, sourceID, "14", addedDate, "0", userID, 'active' )
				
				# Chemical Participant
				self.dbProcessor.processParticipant( str(row['chemical_id']), interactionID, '1', '4', addedDate )
				
				# Protein Participant
				intParticipantID = self.dbProcessor.processParticipant( str(row['gene_id']), interactionID, '10', '1', addedDate )
				self.dbProcessor.processInteractionParticipantAttribute( intParticipantID, str(row['chemical_mapping_source_id']), '34', addedDate, '0', userID, 'active' )
				
				if (intCount % 10000) == 0 :
					self.db.commit( )
				
		self.db.commit( )