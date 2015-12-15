import sys, string
import Config
import datetime
import re

from classes import Maps, Lookups, DBProcessor

class ForcedComplexes( ) :

	"""Tools for Handling the Migration of Forced Complexes from IMS 2 to IMS 4"""

	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		self.dateFormat = "%Y-%m-%d %H:%M:%S"
		self.quoteWrap = re.compile( '^[\'\"](.*)[\"\']$' )
		self.maps = Maps.Maps( )
		self.lookups = Lookups.Lookups( db, cursor )
		self.dbProcessor = DBProcessor.DBProcessor( db, cursor )
		
		# Regular expressions to test if a qualifier is 
		# actually a qualifier, cause it appears scores and 
		# quantification are being stuffed in the qualification fields
		self.ptmTest1 = re.compile( '\^\^\^' )
		self.ptmTest2 = re.compile( '\$\$\$' )
		self.quantTest = re.compile( '^[-0-9Ee.]+[|]?[0-9]?$' )
		self.phenoTest = re.compile( '^[0-9\^\$]+[|]+[0-9]+$' )
		self.phenoTest2 = re.compile( '^[0-9]+[\^NONE|]+[0-9]+$' )
		
		# Build Quick Reference Data Structures
		self.validDatasets = self.lookups.buildValidDatasetSet( )
		self.expSysHash = self.lookups.buildExpSystemHash( )
		self.modHash = self.lookups.buildModificationHash( )
		self.throughputHash = self.lookups.buildThroughputTagHash( )
		self.sourceHash = self.lookups.buildSourceTagHash( )
		self.ontologyTermIDSet = self.lookups.buildOntologyTermIDSet( )
		self.phenotypeTypeHash = self.lookups.buildPhenotypeTypeHash( )
		
		# A set of newly mapped 
		self.forced2interaction = { }
		self.interaction2activation = { }
		
	def migrateForcedComplexes( self ) :
		
		"""
		Copy Operation 
		     -> IMS2: complex_forced_additions to 
			 -> IMS4: interactions
		"""
		
		intCount = 0
		self.cursor.execute( "SELECT complex_forced_id, publication_id, complex_forced_timestamp, user_id, experimental_system_id, modification_id FROM " + Config.DB_IMS_OLD + ".complex_forced_additions ORDER BY complex_forced_id" )
		for row in self.cursor.fetchall( ) :
			if str(row['publication_id']) in self.validDatasets :
			
					intCount += 1
					self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".interactions VALUES( %s, %s, %s, %s )", ['0', row['publication_id'], "2", "error"] )
					interactionID = str(self.cursor.lastrowid)
					
					self.forced2interaction[str(row['complex_forced_id'])] = interactionID
					self.interaction2activation[interactionID] = { "USER_ID" : row['user_id'], "DATE" : row['complex_forced_timestamp'] }
					
					# Remap Experimental System ID into Attributes Entry
					expSystemID = self.expSysHash[str(row['experimental_system_id'])]
					complexAttribID = self.dbProcessor.processInteractionAttribute( interactionID, expSystemID, "11", row['complex_forced_timestamp'], "0", row['user_id'], 'active' )
					
					# Remap Modification ID into Attributes Entry
					# Modifications are only applicable when BioChemical Activity (9)
					# is the Experimental System 
					modificationID = self.modHash[str(row['modification_id'])]
					if str(row['experimental_system_id']) == '9' :
						interactionAttribID = self.dbProcessor.processInteractionAttribute( interactionID, modificationID, "12", row['complex_forced_timestamp'], "0", row['user_id'], 'active' )
						
					# Insert a record into the History Table cause previously
					# no history was recorded
					self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".history VALUES( '0', 'ACTIVATED', %s, %s, %s, '1', %s )", [interactionID, row['user_id'], 'New Complex', row['complex_forced_timestamp']] )
					
					if (intCount % 10000) == 0 :
						self.db.commit( )
				
		self.db.commit( )
		
	def migrateQualifications( self ) :
	
		"""
		Copy Operation
			-> IMS2: complex_forced_attributes
			-> IMS4: attributes and interaction_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".complex_forced_attributes WHERE forced_attribute_type_id='2'" )
		
		qualCount = 0
		for row in self.cursor.fetchall( ) :
			
			if str(row['complex_forced_id']) in self.forced2interaction :
			
				interactionID = self.forced2interaction[str(row['complex_forced_id'])]
				
				activationInfo = self.interaction2activation[interactionID]
				attribDate = row['complex_forced_attribute_timestamp']
				attribUserID = activationInfo["USER_ID"]
			
				qualCount += 1
				
				qualification = row['complex_forced_attribute_value'].strip( "\\" ).decode( 'string_escape' ).strip( )
				
				qualificationSplit = qualification.split( "|" )
				if len(qualificationSplit) > 1 :
					attribUserID = qualificationSplit[1].strip( )
					qualification = qualificationSplit[0].strip( )
				
				matchSet = self.quoteWrap.match( qualification )
				if matchSet :
					qualification = matchSet.group(1)
					
				# Check to see if the qualifications entered are invalid
				# and shouldn't be there
				
				matchSet = self.ptmTest1.search( qualification )
				if matchSet :
					continue
					
				matchSet = self.ptmTest2.search( qualification )
				if matchSet :
					continue
					
				matchSet = self.quantTest.search( qualification )
				if matchSet :
					continue
					
				if len(qualification) > 0 :
					interactionAttribID = self.dbProcessor.processInteractionAttribute( interactionID, qualification, "22", attribDate, "0", attribUserID, 'active' )
					
				if (qualCount % 10000) == 0 :
					self.db.commit( )
				
		self.db.commit( )
				
	def migrateThroughputTags( self ) :
	
		"""
		Copy Operation
			-> IMS2: complex_forced_attributes
			-> IMS4: attribues and interaction_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".complex_forced_attributes WHERE forced_attribute_type_id='4'" )
		
		tagCount = 0
		for row in self.cursor.fetchall( ) :
		
			if str(row['complex_forced_id']) in self.forced2interaction :
			
				interactionID = self.forced2interaction[str(row['complex_forced_id'])]
		
				activationInfo = self.interaction2activation[interactionID]
				attribDate = row['complex_forced_attribute_timestamp']
				attribUserID = activationInfo["USER_ID"]
				
				if str(row['complex_forced_attribute_value']) in self.throughputHash :
				
					tagCount += 1
					
					# Remap Tag ID into Attributes Entry
					throughputTermID = self.throughputHash[str(row['complex_forced_attribute_value'])]
					interactionAttribID = self.dbProcessor.processInteractionAttribute( interactionID, throughputTermID, "13", attribDate, "0", attribUserID, 'active' )
					
					if (tagCount % 10000) == 0 :
						self.db.commit( )
				
		self.db.commit( )
			
	def migrateParticipants( self ) :
	
		"""
		Copy Operation
			-> IMS2: complex_forced_additions
			-> IMS4: participants, interaction_participants
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".complex_forced_additions ORDER BY complex_forced_id ASC" )
		
		partCount = 0
		for row in self.cursor.fetchall( ) :
		
			if str(row['complex_forced_id']) in self.forced2interaction :
			
				interactionID = self.forced2interaction[str(row['complex_forced_id'])]
		
				partCount += 1
				
				activationInfo = self.interaction2activation[interactionID]
				attribDate = row['complex_forced_timestamp']
				attribUserID = activationInfo["USER_ID"]
				
				cOrg = str(row['complex_organism_id'])
				cOrg = self.maps.convertForcedOrganismID( cOrg )
				
				validParticipants = row['complex_participants_success'].strip( )
				if len(validParticipants) > 0 :
					partSplit = validParticipants.split( "|" )
					for participant in partSplit :
						participant = participant.strip( )
						self.dbProcessor.processParticipant( participant, interactionID, '1', '1', attribDate )
						
				invalidParticipants = row['complex_participants_errors'].strip( )
				if len(invalidParticipants) > 0 :
					partSplit = invalidParticipants.split( "|" )
					for participant in partSplit :
						participant = participant.strip( )
						interactorAID = self.dbProcessor.processUnknownParticipant( participant, '1', cOrg, attribDate )
						self.dbProcessor.processParticipant( interactorAID, interactionID, '1', '5', attribDate )
		
				if (partCount % 10000) == 0 :
					self.db.commit( )
				
		self.db.commit( )