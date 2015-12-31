import sys, string
import Config
import datetime
import re

from classes import Maps, Lookups, DBProcessor

class Interactions( ) :

	"""Tools for Handling the Migration of Interaction Data from IMS 2 to IMS 4"""

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
		self.ignoreInteractionSet = self.lookups.buildIgnoreInteractionSet( )
		self.expSysHash = self.lookups.buildExpSystemHash( )
		self.modHash = self.lookups.buildModificationHash( )
		self.throughputHash = self.lookups.buildThroughputTagHash( )
		self.sourceHash = self.lookups.buildSourceTagHash( )
		self.activatedHash = self.lookups.buildInteractionActivationHash( )
		self.ontologyTermIDSet = self.lookups.buildOntologyTermIDSet( )
		self.phenotypeTypeHash = self.lookups.buildPhenotypeTypeHash( )
		
	def migrateInteractions( self ) :
		
		"""
		Copy Operation 
		     -> IMS2: interactions to 
			 -> IMS4: interactions
		"""
		
		intCount = 0
		self.cursor.execute( "SELECT interaction_id, publication_id, experimental_system_id, modification_id FROM " + Config.DB_IMS_OLD + ".interactions ORDER BY interaction_id ASC" )
		for row in self.cursor.fetchall( ) :
			if str(row['interaction_id']) not in self.ignoreInteractionSet and str(row['publication_id']) in self.validDatasets :
				intCount += 1
				self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".interactions VALUES( %s, '-', '-', %s, %s, %s )", [row['interaction_id'], row['publication_id'], "1", "normal"] )
				
				activationInfo = self.activatedHash[str(row['interaction_id'])]
				attribDate = activationInfo["DATE"]
				attribUserID = activationInfo["USER_ID"]
				
				# Remap Experimental System ID into Attributes Entry
				expSystemID = self.expSysHash[str(row['experimental_system_id'])]
				interactionAttribID = self.dbProcessor.processInteractionAttribute( row['interaction_id'], expSystemID, "11", attribDate, "0", attribUserID, 'active' )
				
				# Remap Modification ID into Attributes Entry
				# Modifications are only applicable when BioChemical Activity (9)
				# is the Experimental System 
				modificationID = self.modHash[str(row['modification_id'])]
				if str(row['experimental_system_id']) == '9' :
					interactionAttribID = self.dbProcessor.processInteractionAttribute( row['interaction_id'], modificationID, "12", attribDate, "0", attribUserID, 'active' )
				
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
			-> IMS2: interaction_qualifications
			-> IMS4: attributes and interaction_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".interaction_qualifications WHERE interaction_id IN ( SELECT interaction_id FROM " + Config.DB_IMS + ".interactions )" )
		
		qualCount = 0
		for row in self.cursor.fetchall( ) :
			
			qualCount += 1
			
			activationInfo = self.activatedHash[str(row['interaction_id'])]
			attribDate = activationInfo["DATE"]
			attribUserID = activationInfo["USER_ID"]
			
			qualification = row['interaction_qualification'].strip( "\\" ).decode( 'string_escape' ).strip( )
			qualification = (c for c in qualification if 0 < ord(c) < 127)
			qualification = ''.join(qualification)
			qualification = qualification.strip( )
			
			matchSet = self.quoteWrap.match( qualification )
			if matchSet :
				qualification = matchSet.group(1)
				
			if len(qualification) > 0 :
				interactionAttribID = self.dbProcessor.processInteractionAttribute( row['interaction_id'], qualification, "22", attribDate, "0", row['user_id'], row['interaction_qualification_status'] )
				
			if (qualCount % 10000) == 0 :
				self.db.commit( )
				
		self.db.commit( )
				
	def migrateThroughputTags( self ) :
	
		"""
		Copy Operation
			-> IMS2: interaction_tag_mappings
			-> IMS4: attribues and interaction_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".interaction_tag_mappings WHERE interaction_id IN ( SELECT interaction_id FROM " + Config.DB_IMS + ".interactions )" )
		
		tagCount = 0
		for row in self.cursor.fetchall( ) :
		
			activationInfo = self.activatedHash[str(row['interaction_id'])]
			attribDate = activationInfo["DATE"]
			attribUserID = activationInfo["USER_ID"]
			
			if str(row['tag_id']) in self.throughputHash :
			
				tagCount += 1
				
				# Remap Tag ID into Attributes Entry
				throughputTermID = self.throughputHash[str(row['tag_id'])]
				interactionAttribID = self.dbProcessor.processInteractionAttribute( row['interaction_id'], throughputTermID, "13", row['interaction_tag_mapping_timestamp'], "0", attribUserID, row['interaction_tag_mapping_status'] )
				
				if (tagCount % 10000) == 0 :
					self.db.commit( )
				
		self.db.commit( )
		
	def migrateSourceTags( self ) :
	
		"""
		Copy Operation
			-> IMS2: interaction_tag_mappings
			-> IMS4: attribues and interaction_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".interaction_tag_mappings WHERE interaction_id IN ( SELECT interaction_id FROM " + Config.DB_IMS + ".interactions )" )
		
		tagCount = 0
		for row in self.cursor.fetchall( ) :
		
			activationInfo = self.activatedHash[str(row['interaction_id'])]
			attribDate = activationInfo["DATE"]
			attribUserID = activationInfo["USER_ID"]
			
			if str(row['tag_id']) in self.sourceHash :
			
				tagCount += 1
				
				# Remap Tag ID into Attributes Entry
				sourceTermID = self.sourceHash[str(row['tag_id'])]
				interactionAttribID = self.dbProcessor.processInteractionAttribute( row['interaction_id'], sourceTermID, "14", row['interaction_tag_mapping_timestamp'], "0", attribUserID, row['interaction_tag_mapping_status'] )
				
				if (tagCount % 10000) == 0 :
					self.db.commit( )
				
		self.db.commit( )
		
	def migrateQuantitativeScores( self ) :
	
		"""
		Copy Operation
			-> IMS2: interaction_quantitation, interaction_quantitation_type
			-> IMS4: attributes, interaction_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".interaction_quantitation WHERE interaction_id IN ( SELECT interaction_id FROM " + Config.DB_IMS + ".interactions )" )
		
		quantCount = 0
		quantCheck = { }
		for row in self.cursor.fetchall( ) :
			
			activationInfo = self.activatedHash[str(row['interaction_id'])]
			attribDate = activationInfo["DATE"]
			attribUserID = activationInfo["USER_ID"]
			
			quantTypeConverted = self.maps.convertQuantType( row['interaction_quantitation_type_id'] )
			quantValue = str(row['interaction_quantitation_value']).strip( )
			
			if "" != quantTypeConverted :
			
				if str(row['interaction_id']) not in quantCheck :
					quantCheck[str(row['interaction_id'])] = set( )
					
				if str(row['interaction_quantitation_type_id']) not in quantCheck[str(row['interaction_id'])] :
				
					quantCheck[str(row['interaction_id'])].add( str(row['interaction_quantitation_type_id']) )
				
					quantCount += 1	
					interactionAttribID = self.dbProcessor.processInteractionAttribute( row['interaction_id'], quantValue, quantTypeConverted, attribDate, "0", attribUserID, 'active' )
					
					if (quantCount % 10000) == 0 :
						self.db.commit( )
				
		self.db.commit( )

	def migrateOntologyTerms( self ) :
	
		"""
		Copy Operation
			-> IMS2: interaction_phenotypes, interaction_phenotypes_qualifiers
			-> IMS4: attributes, interaction_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".interaction_phenotypes WHERE interaction_id IN ( SELECT interaction_id FROM " + Config.DB_IMS + ".interactions )" )
		
		phenoCount = 0
		for row in self.cursor.fetchall( ) :
			
			activationInfo = self.activatedHash[str(row['interaction_id'])]
			attribDate = activationInfo["DATE"]
			attribUserID = activationInfo["USER_ID"]
			
			flagConverted = self.maps.convertPhenotypeFlag( row['flag'] )
			interactionPhenotypeID = str(row['interaction_phenotype_id'])
			phenotypeID = str(row['phenotype_id'])
			phenotypeTypeID = str(row['phenotype_type_id'])
			phenotypeStatus = row['interaction_phenotype_status']
			
			if "" != flagConverted :
			
				if phenotypeID in self.ontologyTermIDSet :
					phenoCount += 1
					interactionAttribID = self.dbProcessor.processInteractionAttribute( row['interaction_id'], phenotypeID, flagConverted, attribDate, "0", attribUserID, phenotypeStatus )
					
					# Add Phenotype Qualifiers if Found
					self.processPhenotypeQualifiers( interactionAttribID, interactionPhenotypeID, attribUserID, row['interaction_id'] )
					
					# Add Phenotype Type if Necessary
					self.processPhenotypeTypes( interactionAttribID, phenotypeTypeID, attribUserID, attribDate, row['interaction_id'], phenotypeStatus )
					
					if (phenoCount % 10000) == 0 :
						self.db.commit( )
						
				else :
					if phenotypeStatus == "active" :
						print "FOUND MISSING ONTOLOGY ID: " + phenotypeID
				
		self.db.commit( )
		
	def processPhenotypeQualifiers( self, attribID, interactionPhenotypeID, userID, interactionID ) :
	
		"""Check to see if the interactionPhenotypeID had qualifiers and copy those over as well if it did"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".interaction_phenotypes_qualifiers WHERE interaction_phenotype_id=%s", [interactionPhenotypeID] )
		for row in self.cursor.fetchall( ) :
			
			if str(row['phenotype_id']) in self.ontologyTermIDSet :
				interactionAttribID = self.dbProcessor.processInteractionAttribute( interactionID, str(row['phenotype_id']), "31", row['interaction_phenotypes_qualifier_addeddate'], attribID, userID, row['interaction_phenotypes_qualifier_status'] )
			else :
				if row['interaction_phenotypes_qualifier_status'] == "active" :
					print "FOUND MISSING QUALIFIER ID: " + str(row['phenotype_id'])
				
		self.db.commit( )
		
	def processPhenotypeTypes( self, attribID, phenotypeTypeID, userID, addedDate, interactionID, status ) :
	
		"""Check to see if the phenotypeTypeID is useful and copy those over as mappings if they are"""
		
		phenotypeTypeID = phenotypeTypeID.strip( )
		if phenotypeTypeID != "4" and phenotypeTypeID != "" :
			phenotypeTypeOntologyID = self.phenotypeTypeHash[phenotypeTypeID]
			interactionAttribID = self.dbProcessor.processInteractionAttribute( interactionID, phenotypeTypeOntologyID, "23", addedDate, attribID, userID, status )
				
			self.db.commit( )