import sys, string
import Config
import datetime
import re

from classes import Maps, Lookups, DBProcessor

class Forced( ) :

	"""Tools for Handling the Migration of Forced Interactions from IMS 2 to IMS 4"""

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
		self.ignoreInteractionSet = self.lookups.buildIgnoreForcedInteractionSet( )
		self.expSysHash = self.lookups.buildExpSystemHash( )
		self.modHash = self.lookups.buildModificationHash( )
		self.throughputHash = self.lookups.buildThroughputTagHash( )
		self.sourceHash = self.lookups.buildSourceTagHash( )
		self.ontologyTermIDSet = self.lookups.buildOntologyTermIDSet( )
		self.phenotypeTypeHash = self.lookups.buildPhenotypeTypeHash( )
		
		# A set of newly mapped 
		self.forced2interaction = { }
		self.interaction2activation = { }
		
	def migrateForcedInteractions( self ) :
		
		"""
		Copy Operation 
		     -> IMS2: interaction_forced_additions to 
			 -> IMS4: interactions
		"""
		
		intCount = 0
		self.cursor.execute( "SELECT interaction_forced_id, publication_id, interaction_forced_timestamp, user_id, experimental_system_id, modification_id FROM " + Config.DB_IMS_OLD + ".interaction_forced_additions ORDER BY interaction_forced_id" )
		for row in self.cursor.fetchall( ) :
			if str(row['publication_id']) in self.validDatasets :
				if str(row['interaction_forced_id']) not in self.ignoreInteractionSet :
			
					intCount += 1
					self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".interactions VALUES( %s, '-','-',%s, %s, %s )", ['0', row['publication_id'], "1", "error"] )
					interactionID = str(self.cursor.lastrowid)
					
					self.forced2interaction[str(row['interaction_forced_id'])] = interactionID
					self.interaction2activation[interactionID] = { "USER_ID" : row['user_id'], "DATE" : row['interaction_forced_timestamp'] }
					
					# Remap Experimental System ID into Attributes Entry
					expSystemID = self.expSysHash[str(row['experimental_system_id'])]
					complexAttribID = self.dbProcessor.processInteractionAttribute( interactionID, expSystemID, "11", row['interaction_forced_timestamp'], "0", row['user_id'], 'active' )
					
					# Remap Modification ID into Attributes Entry
					# Modifications are only applicable when BioChemical Activity (9)
					# is the Experimental System 
					modificationID = self.modHash[str(row['modification_id'])]
					if str(row['experimental_system_id']) == '9' :
						interactionAttribID = self.dbProcessor.processInteractionAttribute( interactionID, modificationID, "12", row['interaction_forced_timestamp'], "0", row['user_id'], 'active' )
						
					# Insert a record into the History Table cause previously
					# no history was recorded
					self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".history VALUES( '0', 'ACTIVATED', %s, %s, %s, '1', %s )", [interactionID, row['user_id'], 'New Interaction', row['interaction_forced_timestamp']] )
					
					if (intCount % 10000) == 0 :
						self.db.commit( )
				
		self.db.commit( )
		
	def migrateQualifications( self ) :
	
		"""
		Copy Operation
			-> IMS2: interaction_forced_attributes
			-> IMS4: attributes and interaction_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".interaction_forced_attributes WHERE forced_attribute_type_id='2'" )
		
		qualCount = 0
		for row in self.cursor.fetchall( ) :
			
			if str(row['interaction_forced_id']) in self.forced2interaction :
			
				interactionID = self.forced2interaction[str(row['interaction_forced_id'])]
				
				activationInfo = self.interaction2activation[interactionID]
				attribDate = row['interaction_forced_attribute_timestamp']
				attribUserID = activationInfo["USER_ID"]
			
				qualCount += 1
				
				qualification = row['interaction_forced_attribute_value'].strip( "\\" ).decode( 'string_escape' ).strip( )
				qualification = (c for c in qualification if 0 < ord(c) < 127)
				qualification = ''.join(qualification)
				qualification = qualification.strip( )
				
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
			-> IMS2: interaction_forced_attributes
			-> IMS4: attribues and interaction_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".interaction_forced_attributes WHERE forced_attribute_type_id='4'" )
		
		tagCount = 0
		for row in self.cursor.fetchall( ) :
		
			if str(row['interaction_forced_id']) in self.forced2interaction :
			
				interactionID = self.forced2interaction[str(row['interaction_forced_id'])]
		
				activationInfo = self.interaction2activation[interactionID]
				attribDate = row['interaction_forced_attribute_timestamp']
				attribUserID = activationInfo["USER_ID"]
				
				if str(row['interaction_forced_attribute_value']) in self.throughputHash :
				
					tagCount += 1
					
					# Remap Tag ID into Attributes Entry
					throughputTermID = self.throughputHash[str(row['interaction_forced_attribute_value'])]
					interactionAttribID = self.dbProcessor.processInteractionAttribute( interactionID, throughputTermID, "13", attribDate, "0", attribUserID, 'active' )
					
					if (tagCount % 10000) == 0 :
						self.db.commit( )
				
		self.db.commit( )
		
	def migrateQuantitativeScores( self ) :
	
		"""
		Copy Operation
			-> IMS2: interaction_forced_attributes
			-> IMS4: attribues and interaction_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".interaction_forced_attributes WHERE forced_attribute_type_id='3' OR forced_attribute_type_id='2'" )
		
		quantCount = 0
		quantCheck = { }
		for row in self.cursor.fetchall( ) :
		
			if str(row['interaction_forced_id']) in self.forced2interaction :
			
				quantCount += 1
			
				interactionID = self.forced2interaction[str(row['interaction_forced_id'])]
		
				activationInfo = self.interaction2activation[interactionID]
				attribDate = row['interaction_forced_attribute_timestamp']
				attribUserID = activationInfo["USER_ID"]
				
				quantValue = row['interaction_forced_attribute_value'].strip( )

				# Skip if doesn't match our regular expression
				matchSet = self.quantTest.match( quantValue )
				if not matchSet :
					continue
				
				# Skip if it doesn't have a type
				quantSplit = quantValue.split( "|" )
				if len(quantSplit) < 2 :
					continue
					
				# Skip if unrecognized type
				quantTypeConverted = self.maps.convertQuantType( quantSplit[1] )
				if quantTypeConverted == "" :
					continue
					
				# Skip if not a valid float value
				if not self.isFloat( quantSplit[0] ) :
					continue
					
				if interactionID not in quantCheck :
					quantCheck[interactionID] = set( )
					
				# Skip if we already have a quant score of this type for this interaction
				if quantTypeConverted not in quantCheck[interactionID] :
					quantCheck[interactionID].add( quantTypeConverted )
				
					interactionAttribID = self.dbProcessor.processInteractionAttribute( interactionID, quantSplit[0], quantTypeConverted, attribDate, "0", attribUserID, 'active' )
				
					if (quantCount % 10000) == 0 :
						self.db.commit( )
				
		self.db.commit( )
		
	def isFloat( self, number ) :
		
		try :
			float( number )
			return True
		except ValueError :
			return False

	def migrateOntologyTerms( self ) :
	
		"""
		Copy Operation
			-> IMS2: interaction_forced_attributes
			-> IMS4: attributes, interaction_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".interaction_forced_attributes WHERE forced_attribute_type_id='3' OR forced_attribute_type_id='2'" )
		
		phenoCount = 0
		for row in self.cursor.fetchall( ) :
		
			if str(row['interaction_forced_id']) in self.forced2interaction :
			
				interactionID = self.forced2interaction[str(row['interaction_forced_id'])]
		
				activationInfo = self.interaction2activation[interactionID]
				attribDate = row['interaction_forced_attribute_timestamp']
				attribUserID = activationInfo["USER_ID"]
				
				phenotypeInfo = str(row['interaction_forced_attribute_value'])
				
				# If doesn't match the syntax for a Phenotype 
				matchWithStructure = self.phenoTest.match( phenotypeInfo )
				matchWithNone = self.phenoTest2.match( phenotypeInfo )
				if not matchWithStructure and not matchWithNone :
					continue
					
				# If doesn't have a clean split
				phenoSplit = phenotypeInfo.split( "|" )
				if len(phenoSplit) < 2 :
					continue
					
				phenotypeInfo = phenoSplit[0]
				phenotypeTypeID = phenoSplit[1]
				
				phenoSplit = phenotypeInfo.split( "^^^" )
				phenotypeID = phenoSplit[0]
				
				qualifiers = []
				if len(phenoSplit) > 1 :
					qualifiers = phenoSplit[1].split( "$$$" )
				
				if phenotypeID in self.ontologyTermIDSet :
					phenoCount += 1
					interactionAttribID = self.dbProcessor.processInteractionAttribute( interactionID, phenotypeID, "1", attribDate, "0", attribUserID, "active" )
					
					for qualifier in qualifiers :
						if qualifier.upper( ) != "NONE" :
							# Add Phenotype Qualifiers if Found
							self.processPhenotypeQualifiers( interactionAttribID, qualifier, attribUserID, attribDate, interactionID )
					
					# Add Phenotype Type if Necessary
					self.processPhenotypeTypes( interactionAttribID, phenotypeTypeID, attribUserID, attribDate, interactionID, "active" )
					
					if (phenoCount % 10000) == 0 :
						self.db.commit( )
				
		self.db.commit( )
		
	def processPhenotypeQualifiers( self, attribID, qualifierID, userID, dateAdded, interactionID ) :
	
		"""Check to see if the qualifierID is valid copy those over as well if they are"""
			
		if qualifierID in self.ontologyTermIDSet :
			interactionAttribID = self.dbProcessor.processInteractionAttribute( interactionID, qualifierID, "31", dateAdded, attribID, userID, "active" )
		else :
			print "FOUND MISSING QUALIFIER ID: " + str(qualifierID)
				
		self.db.commit( )
		
	def processPhenotypeTypes( self, attribID, phenotypeTypeID, userID, addedDate, interactionID, status ) :
	
		"""Check to see if the phenotypeTypeID is useful and copy those over as mappings if they are"""
		
		phenotypeTypeID = phenotypeTypeID.strip( )
		if phenotypeTypeID != "4" and phenotypeTypeID != "" :
			phenotypeTypeOntologyID = self.phenotypeTypeHash[phenotypeTypeID]
			interactionAttribID = self.dbProcessor.processInteractionAttribute( interactionID, phenotypeTypeOntologyID, "23", addedDate, attribID, userID, status )
				
			self.db.commit( )
			
	def migrateParticipants( self ) :
	
		"""
		Copy Operation
			-> IMS2: interaction_forced_additions
			-> IMS4: participants, interaction_participants
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".interaction_forced_additions ORDER BY interaction_forced_id ASC" )
		
		partCount = 0
		for row in self.cursor.fetchall( ) :
		
			if str(row['interaction_forced_id']) in self.forced2interaction :
			
				interactionID = self.forced2interaction[str(row['interaction_forced_id'])]
		
				partCount += 1
				
				activationInfo = self.interaction2activation[interactionID]
				attribDate = row['interaction_forced_timestamp']
				attribUserID = activationInfo["USER_ID"]
		
				# INTERACTOR A
				interactorA = str(row['interactor_A_name']).strip( )
				interactorAStatus = row['interactor_A_forced_status']
				interactorAOrg = str(row['interactor_A_organism_id'])
				interactorAOrg = self.maps.convertForcedOrganismID( interactorAOrg )
				interactorAType = str(row['interactor_A_type_id'])
				
				if interactorAType == "2" :
					interactorAType = "4"
				
				interactorAID = interactorA
				participantTypeID = "1"
				if interactorAStatus.upper( ) == "UNKNOWN" :
					interactorAID = self.dbProcessor.processUnknownParticipant( interactorA, interactorAType, interactorAOrg, attribDate )
					participantTypeID = "5"
				
				self.dbProcessor.processParticipant( interactorAID, interactionID, '2', participantTypeID, attribDate )
				
				# INTERACTOR B
				interactorB = str(row['interactor_B_name']).strip( )
				interactorBStatus = row['interactor_B_forced_status']
				interactorBOrg = str(row['interactor_B_organism_id'])
				interactorBOrg = self.maps.convertForcedOrganismID( interactorBOrg )
				interactorBType = str(row['interactor_B_type_id'])
				
				if interactorAType == "2" :
					interactorAType = "4"
				
				interactorBID = interactorB
				participantTypeID = "1"
				if interactorBStatus.upper( ) == "UNKNOWN" :
					interactorBID = self.dbProcessor.processUnknownParticipant( interactorB, interactorBType, interactorBOrg, attribDate )
					participantTypeID = "5"
				
				self.dbProcessor.processParticipant( interactorBID, interactionID, '3', participantTypeID, attribDate )
		
				if (partCount % 10000) == 0 :
					self.db.commit( )
				
		self.db.commit( )