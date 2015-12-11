import sys, string
import Config
import datetime
import re

class Interactions( ) :

	"""Tools for Handling the Migration of Interaction Data from IMS 2 to IMS 4"""

	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		self.dateFormat = "%Y-%m-%d %H:%M:%S"
		self.quoteWrap = re.compile( '^[\'\"](.*)[\"\']$' )
		
		# Build Quick Reference Data Structures
		self.validDatasets = self.buildValidDatasetSet( )
		self.ignoreInteractionSet = self.buildIgnoreInteractionSet( )
		self.expSysHash = self.buildExpSystemHash( )
		self.modHash = self.buildModificationHash( )
		self.throughputHash = self.buildThroughputTagHash( )
		self.sourceHash = self.buildSourceTagHash( )
		self.activatedHash = self.buildInteractionActivationHash( )
		
	def buildValidDatasetSet( self ) :
		
		"""Build a set of Publication IDs to Ignore"""
		
		datasetSet = set( )
		self.cursor.execute( "SELECT dataset_id FROM " + Config.DB_IMS + ".datasets" )
		
		for row in self.cursor.fetchall( ) :
			datasetSet.add( str(row['dataset_id']) )
			
		return datasetSet
		
	def buildIgnoreInteractionSet( self ) :
	
		"""Build a set of interaction ids to Ignore"""
		
		intSet = set( )
		self.cursor.execute( "SELECT interaction_id FROM " + Config.DB_IMS_OLD + ".interaction_tag_mappings WHERE tag_id IN ('5','7') AND interaction_tag_mapping_status='active'" )
		
		for row in self.cursor.fetchall( ) :
			intSet.add( str(row['interaction_id']) )
			
		return intSet
		
	def buildExpSystemHash( self ) :
	
		"""Build a mapping HASH from old Experimental Systems ID to New Ontology ID"""
		
		# Need to fetch the ontology by name rather than by ID because
		# the ID may not be the same by the time we do the final
		# migration of the databases.
		
		ontologyTerms = { }
		self.cursor.execute( "SELECT ontology_id FROM " + Config.DB_IMS + ".ontologies WHERE ontology_name = 'BioGRID Experimental System Ontology' LIMIT 1" )
		row = self.cursor.fetchone( ) 
		
		if None != row :
			ontologyID = row['ontology_id']
			
			self.cursor.execute( "SELECT ontology_term_id, ontology_term_name FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_id=%s", [ontologyID] )
			for row in self.cursor.fetchall( ) :
				ontologyTerms[row['ontology_term_name'].lower( )] = str(row['ontology_term_id'])
		
		expSysHash = { }
		self.cursor.execute( "SELECT experimental_system_id, experimental_system_name FROM " + Config.DB_IMS_OLD + ".experimental_systems" )
		
		for row in self.cursor.fetchall( ) :
			if row['experimental_system_name'].lower( ) in ontologyTerms :
				ontologyID = ontologyTerms[row['experimental_system_name'].lower( )]
				expSysHash[str(row['experimental_system_id'])] = ontologyID
				
		return expSysHash
		
	def buildModificationHash( self ) :
	
		"""Build a mapping HASH from old Modification ID to New Ontology ID"""
		
		# Need to fetch the ontology by name rather than by ID because
		# the ID may not be the same by the time we do the final
		# migration of the databases.
		
		ontologyTerms = { }
		self.cursor.execute( "SELECT ontology_id FROM " + Config.DB_IMS + ".ontologies WHERE ontology_name = 'BioGRID Post-Translational Modification Ontology' LIMIT 1" )
		row = self.cursor.fetchone( ) 
		
		if None != row :
			ontologyID = row['ontology_id']
			
			self.cursor.execute( "SELECT ontology_term_id, ontology_term_name FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_id=%s", [ontologyID] )
			for row in self.cursor.fetchall( ) :
				ontologyTerms[row['ontology_term_name'].lower( )] = str(row['ontology_term_id'])
		
		modHash = { }
		self.cursor.execute( "SELECT modification_id, modification_name FROM " + Config.DB_IMS_OLD + ".modification" )
		
		for row in self.cursor.fetchall( ) :
			if row['modification_name'].lower( ) in ontologyTerms :
				ontologyID = ontologyTerms[row['modification_name'].lower( )]
				modHash[str(row['modification_id'])] = ontologyID
			else :
				print row['modification_name']
				
		return modHash
		
	def buildInteractionActivationHash( self ) :
	
		"""Build a mapping HASH of interaction IDs to details of activation"""
		
		activatedHash = { }
		self.cursor.execute( "SELECT interaction_id, interaction_history_date, user_id FROM " + Config.DB_IMS_OLD + ".interaction_history WHERE modification_type='ACTIVATED'" )
		for row in self.cursor.fetchall( ) :
			activatedHash[str(row['interaction_id'])] = { "USER_ID" : str(row['user_id']), "DATE" : row['interaction_history_date'].strftime(self.dateFormat) }
			
		return activatedHash
		
	def buildThroughputTagHash( self ) :
	
		"""Build a mapping HASH from old Throughput Tag IDs to New Ontology ID"""
		
		# Need to fetch the ontology by name rather than by ID because
		# the ID may not be the same by the time we do the final
		# migration of the databases.
		
		ontologyTerms = { }
		self.cursor.execute( "SELECT ontology_id FROM " + Config.DB_IMS + ".ontologies WHERE ontology_name = 'BioGRID Throughput Ontology' LIMIT 1" )
		row = self.cursor.fetchone( ) 
		
		if None != row :
			ontologyID = row['ontology_id']
			
			self.cursor.execute( "SELECT ontology_term_id, ontology_term_name FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_id=%s", [ontologyID] )
			for row in self.cursor.fetchall( ) :
				ontologyTerms[row['ontology_term_name'].lower( )] = str(row['ontology_term_id'])
		
		tagHash = { }
		self.cursor.execute( "SELECT tag_id, tag_name FROM " + Config.DB_IMS_OLD + ".tags WHERE tag_category_id='1' AND tag_status='active'" )
		
		for row in self.cursor.fetchall( ) :
			if row['tag_name'].lower( ) in ontologyTerms :
				ontologyID = ontologyTerms[row['tag_name'].lower( )]
				tagHash[str(row['tag_id'])] = ontologyID
				
		return tagHash
		
	def buildSourceTagHash( self ) :
	
		"""Build a mapping HASH from old Source Tag IDs to New Ontology ID"""
		
		# Need to fetch the ontology by name rather than by ID because
		# the ID may not be the same by the time we do the final
		# migration of the databases.
		
		ontologyTerms = { }
		self.cursor.execute( "SELECT ontology_id FROM " + Config.DB_IMS + ".ontologies WHERE ontology_name = 'BioGRID Sources Ontology' LIMIT 1" )
		row = self.cursor.fetchone( ) 
		
		if None != row :
			ontologyID = row['ontology_id']
			
			self.cursor.execute( "SELECT ontology_term_id, ontology_term_name FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_id=%s", [ontologyID] )
			for row in self.cursor.fetchall( ) :
				ontologyTerms[row['ontology_term_name'].lower( )] = str(row['ontology_term_id'])
		
		tagHash = { }
		self.cursor.execute( "SELECT tag_id, tag_name FROM " + Config.DB_IMS_OLD + ".tags WHERE tag_category_id='2' AND tag_status='active'" )
		
		for row in self.cursor.fetchall( ) :
			if row['tag_name'].lower( ) in ontologyTerms :
				ontologyID = ontologyTerms[row['tag_name'].lower( )]
				tagHash[str(row['tag_id'])] = ontologyID
				
		return tagHash
		
	def migrateInteractions( self ) :
		
		"""
		Copy Operation 
		     -> IMS2: interactions to 
			 -> IMS4: interactions
		"""
		
		intCount = 0
		self.cursor.execute( "SELECT interaction_id, publication_id, experimental_system_id, modification_id FROM " + Config.DB_IMS_OLD + ".interactions ORDER BY interaction_id" )
		for row in self.cursor.fetchall( ) :
			if str(row['interaction_id']) not in self.ignoreInteractionSet and str(row['publication_id']) in self.validDatasets :
				intCount += 1
				self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".interactions VALUES( %s, %s, %s, %s )", [row['interaction_id'], row['publication_id'], "1", "normal"] )
				
				activationInfo = self.activatedHash[str(row['interaction_id'])]
				attribDate = activationInfo["DATE"]
				attribUserID = activationInfo["USER_ID"]
				
				# Remap Experimental System ID into Attributes Entry
				expSystemID = self.expSysHash[str(row['experimental_system_id'])]
				self.processInteractionAttribute( row['interaction_id'], expSystemID, "11", attribDate, attribUserID, 'active' )
				
				# Remap Modification ID into Attributes Entry
				# Modifications are only applicable when BioChemical Activity (9)
				# is the Experimental System 
				modificationID = self.modHash[str(row['modification_id'])]
				if str(row['experimental_system_id']) == '9' :
					self.processInteractionAttribute( row['interaction_id'], modificationID, "12", attribDate, attribUserID, 'active' )
				
				if (intCount % 10000) == 0 :
					self.db.commit( )
				
		self.db.commit( )
		
	def processInteractionAttribute( self, interactionID, attribVal, attribType, attribDate, userID, mappingStatus ) :
	
		"""Process adding and mapping of the interaction to its attribute"""
		
		self.cursor.execute( "SELECT attribute_id FROM " + Config.DB_IMS + ".attributes WHERE attribute_value=%s AND attribute_type_id=%s AND attribute_status='active' LIMIT 1", [attribVal.strip( ), attribType] )
		
		row = self.cursor.fetchone( )
		if None == row :
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".attributes VALUES ( '0', %s, %s, %s, 'active' )", [attribVal, attribType, attribDate] )
			attribID = self.cursor.lastrowid
		else :
			attribID = row['attribute_id']
		
		self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".interaction_attributes VALUES( '0', %s, %s, '0', %s, %s, %s )", [interactionID, attribID, userID, attribDate, mappingStatus] )
		
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
			
			matchSet = self.quoteWrap.match( qualification )
			if matchSet :
				qualification = matchSet.group(1)
				
			if len(qualification) > 0 :
				self.processInteractionAttribute( row['interaction_id'], qualification, "22", attribDate, row['user_id'], row['interaction_qualification_status'] )
				
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
				self.processInteractionAttribute( row['interaction_id'], throughputTermID, "13", row['interaction_tag_mapping_timestamp'], attribUserID, row['interaction_tag_mapping_status'] )
				
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
				self.processInteractionAttribute( row['interaction_id'], sourceTermID, "14", row['interaction_tag_mapping_timestamp'], attribUserID, row['interaction_tag_mapping_status'] )
				
				if (tagCount % 10000) == 0 :
					self.db.commit( )
				
		self.db.commit( )