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
		
	def buildPubmedToDatasetHash( self ) :
	
		"""Build a mapping HASH from pubmed_ids to dataset_ids"""
		
		mappingHash = { }
		self.cursor.execute( "SELECT dataset_id, dataset_source_id FROM " + Config.DB_IMS + ".datasets WHERE dataset_type_id='1'" )
		
		for row in self.cursor.fetchall( ) :
			mappingHash[str(row['dataset_source_id'])] = str(row['dataset_id'])
			
		return mappingHash
		
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
		
	def buildGenetagIDHash( self ) :
	
		"""Build a mapping HASH from old Genetag IDs to New Ontology ID"""
		
		# Need to fetch the ontology by name rather than by ID because
		# the ID may not be the same by the time we do the final
		# migration of the databases.
		
		ontologyTerms = { }
		self.cursor.execute( "SELECT ontology_id FROM " + Config.DB_IMS + ".ontologies WHERE ontology_name = 'BioGRID Participant Tag Ontology' LIMIT 1" )
		row = self.cursor.fetchone( ) 
		
		if None != row :
			ontologyID = row['ontology_id']
			
			self.cursor.execute( "SELECT ontology_term_id, ontology_term_name FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_id=%s", [ontologyID] )
			for row in self.cursor.fetchall( ) :
				ontologyTerms[row['ontology_term_name'].lower( )] = str(row['ontology_term_id'])
		
		typeHash = { }
		self.cursor.execute( "SELECT genetag_id, genetag_name FROM " + Config.DB_IMS_OLD + ".genetags" )
		
		for row in self.cursor.fetchall( ) :
			if row['genetag_name'].lower( ) in ontologyTerms :
				ontologyID = ontologyTerms[row['genetag_name'].lower( )]
				typeHash[str(row['genetag_id'])] = ontologyID
				
		return typeHash
		
	def buildOldPublicationIDtoPubmedID( self ) :
	
		"""Build a Mapping Hash from Old Publication ID to Pubmed ID"""
		
		mappingHash = { }
		self.cursor.execute( "SELECT publication_id, publication_pubmed_id FROM " + Config.DB_IMS_OLD + ".publications" )
		for row in self.cursor.fetchall( ) :
			mappingHash[str(row['publication_pubmed_id'])] = str(row['publication_id'])
			
		return mappingHash
		
	def buildIgnoreGroupPubmedSet( self ) :
	
		"""Build a set of pubmed ids to ignore while adding extra pubmeds via group migration"""
		
		ignorePubmeds = set( )
		self.cursor.execute( "SELECT pubmed_id FROM " + Config.DB_IMS_TRANSITION + ".ignore_group_pubmed" )
		for row in self.cursor.fetchall( ) :
			ignorePubmeds.add( str(row['pubmed_id']) )
			
		return ignorePubmeds
		
	def buildInteractionTypeHash( self ) :
	
		"""Build a lookup of interaction type names from interaction type ids"""
		
		mappingHash = { }
		self.cursor.execute( "SELECT interaction_type_id, interaction_type_name FROM " + Config.DB_IMS + ".interaction_types" )
		for row in self.cursor.fetchall( ) :
			mappingHash[str(row['interaction_type_id'])] = row['interaction_type_name']
			
		return mappingHash
		
	def buildHistoryOperationsHash( self ) :
	
		"""Build a lookup of history operation names from history operation ids"""
		
		mappingHash = { }
		self.cursor.execute( "SELECT history_operation_id, history_operation_name FROM " + Config.DB_IMS + ".history_operations" )
		for row in self.cursor.fetchall( ) :
			mappingHash[str(row['history_operation_id'])] = row['history_operation_name']
			
		return mappingHash
		
	def buildParticipantTypesHash( self ) :
	
		"""Build a lookup of participant_types"""
		
		mappingHash = { }
		self.cursor.execute( "SELECT participant_type_id, participant_type_name FROM " + Config.DB_IMS + ".participant_types" )
		for row in self.cursor.fetchall( ) :
			mappingHash[str(row['participant_type_id'])] = row['participant_type_name']
			
		return mappingHash
		
	def buildParticipantRoleHash( self ) :
	
		"""Build a Lookup of Participant Roles"""
		
		mappingHash = { }
		self.cursor.execute( "SELECT participant_role_id, participant_role_name FROM " + Config.DB_IMS + ".participant_roles" )
		for row in self.cursor.fetchall( ) :
			mappingHash[str(row['participant_role_id'])] = row['participant_role_name']
			
		return mappingHash
		
	def buildParticipantTypeMappingHash( self ) :
		
		"""Build a lookup of participant ids to their types"""
		
		mappingHash = { }
		self.cursor.execute( "SELECT participant_id, participant_type_id FROM " + Config.DB_IMS + ".participants" )
		for row in self.cursor.fetchall( ) :
			mappingHash[str(row['participant_id'])] = str(row['participant_type_id'])
			
		return mappingHash
		
	def buildAnnotationHash( self ) :
	
		"""Build a quick lookup of annotation for participants"""
		
		mappingHash = { }
		self.cursor.execute( "SELECT participant_id, participant_value, participant_type_id FROM " + Config.DB_IMS + ".participants" )
		
		for row in self.cursor.fetchall( ) :
			
			# GENES 
			if str(row['participant_type_id']) == "1" :
				geneAnnotation = self.fetchGeneAnnotation( str(row['participant_value']), True )
				mappingHash[str(row['participant_id'])] = geneAnnotation
			
			# UNIPROT
			elif str(row['participant_type_id']) == "2" :
				uniprotAnnotation = self.fetchUniprotAnnotation( str(row['participant_value']) )
				mappingHash[str(row['participant_id'])] = uniprotAnnotation
			
			# REFSEQ
			elif str(row['participant_type_id']) == "3" :
				refseqAnnotation = self.fetchRefseqAnnotation( str(row['participant_value']) )
				mappingHash[str(row['participant_id'])] = refseqAnnotation
			
			# CHEMICAL
			elif str(row['participant_type_id']) == "4" :
				chemicalAnnotation = self.fetchChemicalAnnotation( str(row['participant_value']) )
				mappingHash[str(row['participant_id'])] = chemicalAnnotation
				
			# UNKNOWN PARTICIPANT
			elif str(row['participant_type_id']) == "5" :
				unknownAnnotation = self.fetchUnknownAnnotation( str(row['participant_value']) )
				mappingHash[str(row['participant_id'])] = unknownAnnotation
				
		return mappingHash
				
	def fetchGeneAnnotation( self, geneID, withOrganism ) :
	
		"""Fetches Gene Annotation from the Quick Lookup Table"""
	
		if withOrganism :
			self.cursor.execute( "SELECT official_symbol, aliases, systematic_name, organism_id, organism_abbreviation, organism_strain FROM " + Config.DB_QUICK + ".quick_annotation WHERE gene_id=%s LIMIT 1", [geneID] )
		else :
			self.cursor.execute( "SELECT official_symbol, aliases, systematic_name FROM " + Config.DB_QUICK + ".quick_annotation WHERE gene_id=%s LIMIT 1", [geneID] )
		
		geneAnnotation = self.cursor.fetchone( )
		if None != geneAnnotation :
		
			if geneAnnotation['aliases'] != "-" :
				geneAnnotation['aliases'] = geneAnnotation['aliases'].split( "|" )
			else :
				geneAnnotation['aliases'] = []
				
			return geneAnnotation
		
		return False
		
	def fetchUniprotAnnotation( self, uniprotID ) :
	
		"""Fetches Uniprot Sequence Annotation from the Quick Lookup Table"""
		
		self.cursor.execute( "SELECT uniprot_identifier_value, uniprot_aliases, uniprot_name, uniprot_source, organism_id, organism_abbreviation, organism_strain FROM " + Config.DB_QUICK + ".quick_uniprot WHERE uniprot_id=%s LIMIT 1", [uniprotID] )
		
		uniprotAnnotation = self.cursor.fetchone( )
		if None != uniprotAnnotation :
		
			if uniprotAnnotation['uniprot_aliases'] != "-" :
				uniprotAnnotation['uniprot_aliases'] = uniprotAnnotation['uniprot_aliases'].split( "|" )
			else :
				uniprotAnnotation['uniprot_aliases'] = []
				
			return uniprotAnnotation
			
		return False
		
	def fetchRefseqAnnotation( self, refseqID ) :
	
		"""Fetches REFSEQ Sequence Annotation from the Quick Lookup Table"""
		
		self.cursor.execute( "SELECT refseq_accession, refseq_gi, refseq_aliases, refseq_uniprot_aliases, organism_id, organism_abbreviation, organism_strain, gene_id FROM " + Config.DB_QUICK + ".quick_refseq WHERE refseq_id=%s LIMIT 1", [refseqID] )
		
		refseqAnnotation = self.cursor.fetchone( )
		if None != refseqAnnotation :
		
			if refseqAnnotation['refseq_aliases'] != "-" :
				refseqAnnotation['refseq_aliases'] = refseqAnnotation['refseq_aliases'].split( "|" )
			else :
				refseqAnnotation['refseq_aliases'] = []
				
			if refseqAnnotation['refseq_uniprot_aliases'] != "-" :
				refseqAnnotation['refseq_uniprot_aliases'] = refseqAnnotation['refseq_uniprot_aliases'].split( "|" )
			else :
				refseqAnnotation['refseq_uniprot_aliases'] = []
				
			refseqAnnotation['gene_annotation'] = { }
			if refseqAnnotation['gene_id'] != 0 :
				geneAnnotation = self.fetchGeneAnnotation( str(refseqAnnotation['gene_id']), False )
				refseqAnnotation['gene_annotation'] = geneAnnotation
				
			return refseqAnnotation
			
		return False
		
	def fetchChemicalAnnotation( self, chemicalID ) :
	
		"""Fetches Chemical Annotation from the Quick Lookup Table"""
		
		self.cursor.execute( "SELECT chemical_name, chemical_synonyms, chemical_brands, chemical_formula, chemical_type, chemical_source FROM " + Config.DB_QUICK + ".quick_chemicals WHERE chemical_id=%s LIMIT 1", [chemicalID] )
		
		chemAnnotation = self.cursor.fetchone( )
		if None != chemAnnotation :
		
			if chemAnnotation['chemical_synonyms'] != "-" :
				chemAnnotation['chemical_synonyms'] = chemAnnotation['chemical_synonyms'].split( "|" )
			else :
				chemAnnotation['chemical_synonyms'] = []
				
			if chemAnnotation['chemical_brands'] != "-" :
				chemAnnotation['chemical_brands'] = chemAnnotation['chemical_brands'].split( "|" )
			else :
				chemAnnotation['chemical_brands'] = []
				
			return chemAnnotation
			
		return False
		
	def fetchUnknownAnnotation( self, unknownID ) :
	
		"""Fetches Unknown Annotation from the Lookup Table"""
		
		self.cursor.execute( "SELECT unknown_participant_value, organism_id FROM " + Config.DB_IMS + ".unknown_participants WHERE unknown_participant_id=%s LIMIT 1", [unknownID] )
		unknownAnnotation = self.cursor.fetchone( )	
		
		if None != unknownAnnotation :
			unknownAnnotation['organism_abbreviation'], unknownAnnotation['organism_strain'] = self.fetchOrganismInfoFromOrganismID( unknownAnnotation['organism_id'] )
			return unknownAnnotation
		
		return False
		
	def fetchOrganismInfoFromOrganismID( self, organismID ) :
	
		"""Fetches organism information from the quick lookup table"""
		
		self.cursor.execute( "SELECT organism_abbreviation, organism_strain FROM " + Config.DB_QUICK + ".quick_organisms WHERE organism_id=%s LIMIT 1", [organismID] )
		orgInfo = self.cursor.fetchone( )
		return orgInfo['organism_abbreviation'], orgInfo['organism_strain']
		
	def buildAttributeTypeHASH( self ) :
	
		"""Build a lookup of attribute_types"""
		
		mappingHash = { }
		self.cursor.execute( "SELECT o.attribute_type_id, o.attribute_type_name, o.attribute_type_shortcode, o.attribute_type_category_id, p.attribute_type_category_name FROM " + Config.DB_IMS + ".attribute_types o LEFT JOIN " + Config.DB_IMS + ".attribute_type_categories p ON (o.attribute_type_category_id=p.attribute_type_category_id)" )
		for row in self.cursor.fetchall( ) :
			mappingHash[str(row['attribute_type_id'])] = row
			
		return mappingHash
		
	def buildAttributeOntologyTermHASH( self ) :
		
		"""Build a lookup of currently mapped ontology term details"""
		
		mappingHash = { }
		self.cursor.execute( "SELECT attribute_value FROM " + Config.DB_IMS + ".attributes WHERE attribute_type_id IN ( SELECT attribute_type_id FROM " + Config.DB_IMS + ".attribute_types WHERE attribute_type_category_id='1' ) GROUP BY attribute_value" )
		
		for row in self.cursor.fetchall( ) :
			mappingHash[str(row['attribute_value'])] = self.fetchOntologyTermInfo( str(row['attribute_value']) )
			
		return mappingHash
		
	def fetchOntologyTermInfo( self, ontologyTermID ) :
	
		"""Fetch details about an ontology term"""
		
		self.cursor.execute( "SELECT o.ontology_term_id, o.ontology_term_official_id, o.ontology_term_name, o.ontology_id, p.ontology_name FROM " + Config.DB_IMS + ".ontology_terms o LEFT JOIN " + Config.DB_IMS + ".ontologies p ON (o.ontology_id=p.ontology_id) WHERE o.ontology_term_id=%s LIMIT 1", [ontologyTermID] )
		
		row = self.cursor.fetchone( )
		return row