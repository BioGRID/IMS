import sys, string
import Config
import datetime
import re

from classes import Maps, Lookups, DBProcessor

class Complexes( ) :

	"""Tools for Handling the Migration of Complex Data from IMS 2 to IMS 4"""

	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		self.dateFormat = "%Y-%m-%d %H:%M:%S"
		self.quoteWrap = re.compile( '^[\'\"](.*)[\"\']$' )
		self.maps = Maps.Maps( )
		self.lookups = Lookups.Lookups( db, cursor )
		self.dbProcessor = DBProcessor.DBProcessor( db, cursor )
		
		# Build Quick Reference Data Structures
		self.validDatasets = self.lookups.buildValidDatasetSet( )
		self.expSysHash = self.lookups.buildExpSystemHash( )
		self.modHash = self.lookups.buildModificationHash( )
		self.throughputHash = self.lookups.buildThroughputTagHash( )
		self.sourceHash = self.lookups.buildSourceTagHash( )
		self.activatedHash = self.lookups.buildComplexActivationHash( )
		self.ontologyTermIDSet = self.lookups.buildOntologyTermIDSet( )
		self.phenotypeTypeHash = self.lookups.buildPhenotypeTypeHash( )
		
		# A set of newly mapped 
		self.complex2interaction = { }
		
	def migrateComplexes( self ) :
		
		"""
		Copy Operation 
		     -> IMS2: complexes to 
			 -> IMS4: interactions
		"""
		
		intCount = 0
		self.cursor.execute( "SELECT complex_id, publication_id, experimental_system_id, modification_id FROM " + Config.DB_IMS_OLD + ".complexes ORDER BY complex_id" )
		for row in self.cursor.fetchall( ) :
			if str(row['publication_id']) in self.validDatasets :
			
				intCount += 1
				self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".interactions VALUES( %s, %s, %s, %s )", ['0', row['publication_id'], "2", "normal"] )
				interactionID = str(self.cursor.lastrowid)
				
				self.complex2interaction[str(row['complex_id'])] = interactionID
				
				activationInfo = self.activatedHash[str(row['complex_id'])]
				attribDate = activationInfo["DATE"]
				attribUserID = activationInfo["USER_ID"]
				
				# Remap Experimental System ID into Attributes Entry
				expSystemID = self.expSysHash[str(row['experimental_system_id'])]
				complexAttribID = self.dbProcessor.processInteractionAttribute( interactionID, expSystemID, "11", attribDate, "0", attribUserID, 'active' )
				
				# Remap Modification ID into Attributes Entry
				# Modifications are only applicable when BioChemical Activity (9)
				# is the Experimental System 
				modificationID = self.modHash[str(row['modification_id'])]
				if str(row['experimental_system_id']) == '9' :
					interactionAttribID = self.dbProcessor.processInteractionAttribute( interactionID, modificationID, "12", attribDate, "0", attribUserID, 'active' )
				
				if (intCount % 10000) == 0 :
					self.db.commit( )
				
		self.db.commit( )
		
	def migrateHistory( self ) :
	
		"""
		Copy Operation
			-> IMS2: interaction_history
			-> IMS4: history
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".interaction_history WHERE interaction_id IN ( SELECT interaction_id FROM " + Config.DB_IMS + ".interactions )" )
		for row in self.cursor.fetchall( ) :
		
			modificationType = row['modification_type']
			if modificationType == "DEACTIVATED" :
				modificationType = "DISABLED"
		
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".history VALUES( %s, %s, %s, %s, %s, %s, %s )" , [
				row['interaction_history_id'], 
				modificationType, 
				row['interaction_id'], 
				row['user_id'], 
				row['interaction_history_comment'],
				'12', 
				row['interaction_history_date']
			])
			
		self.db.commit( )
		
	def migrateQualifications( self ) :
	
		"""
		Copy Operation
			-> IMS2: complex_qualifications
			-> IMS4: attributes and interaction_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".complex_qualifications" )
		
		qualCount = 0
		for row in self.cursor.fetchall( ) :
			
			if str(row['complex_id']) in self.complex2interaction :
			
				interactionID = self.complex2interaction[str(row['complex_id'])]
			
				qualCount += 1
				
				activationInfo = self.activatedHash[str(row['complex_id'])]
				attribDate = activationInfo["DATE"]
				attribUserID = activationInfo["USER_ID"]
				
				qualification = row['complex_qualification'].strip( "\\" ).decode( 'string_escape' ).strip( )
				
				matchSet = self.quoteWrap.match( qualification )
				if matchSet :
					qualification = matchSet.group(1)
					
				if len(qualification) > 0 :
					interactionAttribID = self.dbProcessor.processInteractionAttribute( interactionID, qualification, "22", attribDate, "0", row['user_id'], row['complex_qualification_status'] )
					
				if (qualCount % 10000) == 0 :
					self.db.commit( )
				
		self.db.commit( )
				
	def migrateThroughputTags( self ) :
	
		"""
		Copy Operation
			-> IMS2: complex_tag_mappings
			-> IMS4: attribues and interaction_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".complex_tag_mappings" )
		
		tagCount = 0
		for row in self.cursor.fetchall( ) :
		
			if str(row['complex_id']) in self.complex2interaction :
			
				interactionID = self.complex2interaction[str(row['complex_id'])]
		
				activationInfo = self.activatedHash[str(row['complex_id'])]
				attribDate = activationInfo["DATE"]
				attribUserID = activationInfo["USER_ID"]
				
				if str(row['tag_id']) in self.throughputHash :
				
					tagCount += 1
					
					# Remap Tag ID into Attributes Entry
					throughputTermID = self.throughputHash[str(row['tag_id'])]
					interactionAttribID = self.dbProcessor.processInteractionAttribute( interactionID, throughputTermID, "13", row['complex_tag_mapping_timestamp'], "0", attribUserID, row['complex_tag_mapping_status'] )
					
					if (tagCount % 10000) == 0 :
						self.db.commit( )
				
		self.db.commit( )
		
	def migrateSourceTags( self ) :
	
		"""
		Copy Operation
			-> IMS2: complex_tag_mappings
			-> IMS4: attribues and interaction_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".complex_tag_mappings" )
		
		tagCount = 0
		for row in self.cursor.fetchall( ) :
		
			if str(row['complex_id']) in self.complex2interaction :
			
				interactionID = self.complex2interaction[str(row['complex_id'])]
		
				activationInfo = self.activatedHash[str(row['complex_id'])]
				attribDate = activationInfo["DATE"]
				attribUserID = activationInfo["USER_ID"]
				
				if str(row['tag_id']) in self.sourceHash :
				
					tagCount += 1
					
					# Remap Tag ID into Attributes Entry
					sourceTermID = self.sourceHash[str(row['tag_id'])]
					interactionAttribID = self.processInteractionAttribute( interactionID, sourceTermID, "14", row['complex_tag_mapping_timestamp'], "0", attribUserID, row['complex_tag_mapping_status'] )
					
					if (tagCount % 10000) == 0 :
						self.db.commit( )
				
		self.db.commit( )

	def migrateOntologyTerms( self ) :
	
		"""
		Copy Operation
			-> IMS2: complex_phenotypes, complex_phenotypes_qualifiers
			-> IMS4: attributes, interaction_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".complex_phenotypes" )
		
		phenoCount = 0
		for row in self.cursor.fetchall( ) :
		
			if str(row['complex_id']) in self.complex2interaction :
			
				interactionID = self.complex2interaction[str(row['complex_id'])]
			
				activationInfo = self.activatedHash[str(row['complex_id'])]
				attribDate = activationInfo["DATE"]
				attribUserID = activationInfo["USER_ID"]
				
				flagConverted = self.maps.convertPhenotypeFlag( row['flag'] )
				complexPhenotypeID = str(row['complex_phenotype_id'])
				phenotypeID = str(row['phenotype_id'])
				phenotypeTypeID = str(row['phenotype_type_id'])
				phenotypeStatus = row['complex_phenotype_status']
				
				if "" != flagConverted :
				
					if phenotypeID in self.ontologyTermIDSet :
						phenoCount += 1
						interactionAttribID = self.dbProcessor.processInteractionAttribute( interactionID, phenotypeID, flagConverted, attribDate, "0", attribUserID, phenotypeStatus )
						
						# Add Phenotype Qualifiers if Found
						self.processPhenotypeQualifiers( interactionAttribID, complexPhenotypeID, attribUserID, interactionID )
						
						# Add Phenotype Type if Necessary
						self.processPhenotypeTypes( interactionAttribID, phenotypeTypeID, attribUserID, attribDate, interactionID, phenotypeStatus )
						
						if (phenoCount % 10000) == 0 :
							self.db.commit( )
							
					else :
						if phenotypeStatus == "active" :
							print "FOUND MISSING ONTOLOGY ID: " + phenotypeID
				
		self.db.commit( )
		
	def processPhenotypeQualifiers( self, attribID, complexPhenotypeID, userID, interactionID ) :
	
		"""Check to see if the complexPhenotypeID had qualifiers and copy those over as well if it did"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".complex_phenotypes_qualifiers WHERE complex_phenotype_id=%s", [complexPhenotypeID] )
		for row in self.cursor.fetchall( ) :
			
			if str(row['phenotype_id']) in self.ontologyTermIDSet :
				interactionAttribID = self.dbProcessor.processInteractionAttribute( interactionID, str(row['phenotype_id']), "31", row['complex_phenotypes_qualifier_addeddate'], attribID, userID, row['complex_phenotypes_qualifier_status'] )
			else :
				if row['complex_phenotypes_qualifier_status'] == "active" :
					print "FOUND MISSING QUALIFIER ID: " + str(row['phenotype_id'])
				
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
			-> IMS2: complexes
			-> IMS4: participants, interaction_participants
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".complexes ORDER BY complex_id ASC" )
		
		partCount = 0
		for row in self.cursor.fetchall( ) :
		
			if str(row['complex_id']) in self.complex2interaction :
			
				interactionID = self.complex2interaction[str(row['complex_id'])]
		
				partCount += 1
				
				activationInfo = self.activatedHash[str(row['complex_id'])]
				dateAdded = activationInfo["DATE"]
				
				interactors = row['complex_participants'].split( "|" )
				
				for interactor in interactors :
					interactor = interactor.strip( )
					self.dbProcessor.processParticipant( interactor, interactionID, '1', '1', dateAdded )
				
				if (partCount % 10000) == 0 :
					self.db.commit( )
				
		self.db.commit( )
		
	def migrateHistory( self ) :
	
		"""
		Copy Operation
			-> IMS2: complex_history
			-> IMS4: history
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".complex_history" )
		rowCount = 0
		for row in self.cursor.fetchall( ) :
		
			if str(row['complex_id']) in self.complex2interaction :
			
				interactionID = self.complex2interaction[str(row['complex_id'])]
		
				rowCount += 1
			
				modificationType = row['modification_type']
				if modificationType == "DEACTIVATED" :
					modificationType = "DISABLED"
			
				self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".history VALUES( %s, %s, %s, %s, %s, %s, %s )" , [
					'0', 
					modificationType, 
					interactionID, 
					row['user_id'], 
					row['complex_history_comment'],
					'12', 
					row['complex_history_date']
				])
			
				if (rowCount % 10000) == 0 :
					self.db.commit( )
			
		self.db.commit( )