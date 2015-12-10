import sys, string
import Config
import datetime

class Interactions( ) :

	"""Tools for Handling the Migration of Interaction Data from IMS 2 to IMS 4"""

	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		self.dateFormat = "%Y-%m-%d %H:%M:%S"
		
		# Build Quick Reference Data Structures
		self.validDatasets = self.buildValidDatasetSet( )
		self.ignoreInteractionSet = self.buildIgnoreInteractionSet( )
		self.expSysHash = self.buildExpSystemHash( )
		self.modHash = self.buildModificationHash( )
		self.activatedHash = self.buildInteractionActivationDateHash( )
		
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
		
	def buildInteractionActivationDateHash( self ) :
	
		"""Build a mapping HASH of interaction IDs to the date of activation"""
		
		activatedHash = { }
		self.cursor.execute( "SELECT interaction_id, interaction_history_date FROM " + Config.DB_IMS_OLD + ".interaction_history WHERE modification_type='ACTIVATED'" )
		for row in self.cursor.fetchall( ) :
			activatedHash[str(row['interaction_id'])] = row['interaction_history_date'].strftime(self.dateFormat)
			
		return activatedHash
		
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
				
				attribDate = self.activatedHash[str(row['interaction_id'])]
				
				# Remap Experimental System ID into Attributes Entry
				expSystemID = self.expSysHash[str(row['experimental_system_id'])]
				self.processInteractionAttribute( row['interaction_id'], expSystemID, "11", attribDate )
				
				# Remap Modification ID into Attributes Entry
				# Modifications are only applicable when BioChemical Activity (9)
				# is the Experimental System 
				modificationID = self.modHash[str(row['modification_id'])]
				if str(row['experimental_system_id']) == '9' :
					self.processInteractionAttribute( row['interaction_id'], modificationID, "12", attribDate )
				
				if (intCount % 10000) == 0 :
					self.db.commit( )
				
		self.db.commit( )
		
	def processInteractionAttribute( self, interactionID, attribVal, attribType, attribDate ) :
	
		"""Process adding and mapping of the interaction to its attribute"""
		
		self.cursor.execute( "SELECT attribute_id FROM " + Config.DB_IMS + ".attributes WHERE attribute_value=%s AND attribute_type_id=%s AND attribute_status='active' LIMIT 1", [attribVal.strip( ), attribType] )
		
		row = self.cursor.fetchone( )
		if None == row :
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".attributes VALUES ( '0', %s, %s, %s, 'active' )", [attribVal, attribType, attribDate] )
			attribID = self.cursor.lastrowid
		else :
			attribID = row['attribute_id']
		
		self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".interaction_attributes VALUES( '0', %s, %s, '0', %s, 'active' )", [interactionID, attribID, attribDate] )
		
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