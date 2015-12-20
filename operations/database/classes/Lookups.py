import sys, string
import Config
import datetime

class Lookups( ) :

	"""Functions for building quick lookup data-structures to save on Database query times"""

	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		self.dateFormat = "%Y-%m-%d %H:%M:%S"
		
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
		
	def buildOntologyTermIDSet( self ) :
	
		"""Build a set of ontology term ids"""
		
		termIDSet = set( )
		self.cursor.execute( "SELECT ontology_term_id FROM " + Config.DB_IMS + ".ontology_terms" )
		
		for row in self.cursor.fetchall( ) :
			termIDSet.add( str(row['ontology_term_id']) )
			
		return termIDSet
		
	def buildPhenotypeTypeHash( self ) :
	
		"""Build a mapping HASH from old Phenotype Type IDs to New Ontology ID"""
		
		# Need to fetch the ontology by name rather than by ID because
		# the ID may not be the same by the time we do the final
		# migration of the databases.
		
		ontologyTerms = { }
		self.cursor.execute( "SELECT ontology_id FROM " + Config.DB_IMS + ".ontologies WHERE ontology_name = 'BioGRID Phenotype Types Ontology' LIMIT 1" )
		row = self.cursor.fetchone( ) 
		
		if None != row :
			ontologyID = row['ontology_id']
			
			self.cursor.execute( "SELECT ontology_term_id, ontology_term_name FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_id=%s", [ontologyID] )
			for row in self.cursor.fetchall( ) :
				ontologyTerms[row['ontology_term_name'].lower( )] = str(row['ontology_term_id'])
		
		typeHash = { }
		self.cursor.execute( "SELECT phenotype_type_id, phenotype_type_name FROM " + Config.DB_IMS_OLD + ".phenotypes_types" )
		
		for row in self.cursor.fetchall( ) :
			if row['phenotype_type_name'].lower( ) in ontologyTerms :
				ontologyID = ontologyTerms[row['phenotype_type_name'].lower( )]
				typeHash[str(row['phenotype_type_id'])] = ontologyID
				
		return typeHash
		
	def buildComplexActivationHash( self ) :
	
		"""Build a mapping HASH of Complex IDs to details of activation"""
		
		activatedHash = { }
		self.cursor.execute( "SELECT complex_id, complex_history_date, user_id FROM " + Config.DB_IMS_OLD + ".complex_history WHERE modification_type='ACTIVATED'" )
		for row in self.cursor.fetchall( ) :
			activatedHash[str(row['complex_id'])] = { "USER_ID" : str(row['user_id']), "DATE" : row['complex_history_date'].strftime(self.dateFormat) }
			
		return activatedHash
		
	def buildIgnoreForcedInteractionSet( self ) :
	
		"""Build a set of forced interaction ids to Ignore"""
		
		intSet = set( )
		self.cursor.execute( "SELECT interaction_forced_id FROM " + Config.DB_IMS_OLD + ".interaction_forced_attributes WHERE interaction_forced_attribute_value IN ('5','7') AND forced_attribute_type_id='4'" )
		
		for row in self.cursor.fetchall( ) :
			intSet.add( str(row['interaction_forced_id']) )
			
		return intSet
		
	def buildChemicalActionHash( self ) :
	
		"""Build a mapping HASH from old Chemical Action IDs to New Ontology ID"""
		
		# Need to fetch the ontology by name rather than by ID because
		# the ID may not be the same by the time we do the final
		# migration of the databases.
		
		ontologyTerms = { }
		self.cursor.execute( "SELECT ontology_id FROM " + Config.DB_IMS + ".ontologies WHERE ontology_name = 'BioGRID Chemical Actions Ontology' LIMIT 1" )
		row = self.cursor.fetchone( ) 
		
		if None != row :
			ontologyID = row['ontology_id']
			
			self.cursor.execute( "SELECT ontology_term_id, ontology_term_name FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_id=%s", [ontologyID] )
			for row in self.cursor.fetchall( ) :
				ontologyTerms[row['ontology_term_name'].lower( )] = str(row['ontology_term_id'])
		
		chemHash = { }
		self.cursor.execute( "SELECT chemical_action_id, chemical_action_name FROM " + Config.DB_IMS_OLD + ".chemical_actions WHERE chemical_action_status='active'" )
		
		for row in self.cursor.fetchall( ) :
			if row['chemical_action_name'].lower( ) in ontologyTerms :
				ontologyID = ontologyTerms[row['chemical_action_name'].lower( )]
				chemHash[str(row['chemical_action_id'])] = ontologyID
				
		return chemHash
		
	def buildSourceNameHash( self ) :
	
		"""Build a mapping HASH from source names to Source Ontology IDs"""
		
		# Need to fetch the ontology by name rather than by ID because
		# the ID may not be the same by the time we do the final
		# migration of the databases.
		
		nameHash = { }
		self.cursor.execute( "SELECT ontology_id FROM " + Config.DB_IMS + ".ontologies WHERE ontology_name = 'BioGRID Sources Ontology' LIMIT 1" )
		row = self.cursor.fetchone( )
		
		if None != row :
			ontologyID = row['ontology_id']
			
			self.cursor.execute( "SELECT ontology_term_id, ontology_term_name FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_id=%s", [ontologyID] )
			for row in self.cursor.fetchall( ) :
				nameHash[row['ontology_term_name'].lower( )] = str(row['ontology_term_id'])
				
		return nameHash
		
	def buildPTMIdentityHash( self ) :
	
		"""Build a mapping HASH from PTM identity types to Ontology IDs"""
		
		# Need to fetch the ontology by name rather than by ID because
		# the ID may not be the same by the time we do the final
		# migration of the databases.
		
		ontologyTerms = { }
		self.cursor.execute( "SELECT ontology_id FROM " + Config.DB_IMS + ".ontologies WHERE ontology_name = 'BioGRID Post-Translational Modification Identities Ontology' LIMIT 1" )
		row = self.cursor.fetchone( )
		
		if None != row :
			ontologyID = row['ontology_id']
			
			self.cursor.execute( "SELECT ontology_term_id, ontology_term_name FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_id=%s", [ontologyID] )
			for row in self.cursor.fetchall( ) :
				ontologyTerms[row['ontology_term_name'].lower( )] = str(row['ontology_term_id'])
				
		return ontologyTerms