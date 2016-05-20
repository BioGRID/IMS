import sys, string
import Config

class Ontologies( ) :

	"""Tools for Handling the Migration of Ontology Data from IMS 2 to IMS 4"""

	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		
	def migrateOntologies( self ) :
		
		"""
		Copy Operation 
		     -> IMS2: phenotype_ontologies to 
			 -> IMS4: ontologies
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".phenotypes_ontologies" )
		for row in self.cursor.fetchall( ) :
			sqlFormat = ",".join( ['%s'] * len(row) )
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".ontologies VALUES( %s )" % sqlFormat, [
				row['phenotype_ontology_id'], 
				row['phenotype_ontology_name'], 
				row['phenotype_ontology_url'], 
				str(row['phenotype_ontology_rootid']), 
				row['phenotype_ontology_addeddate'], 
				row['phenotype_ontology_lastparsed'], 
				row['phenotype_ontology_status']
			])
			
	def migrateOntologyTerms( self ) :
		
		"""
		Copy Operation 
		     -> IMS2: phenotypes to 
			 -> IMS4: ontology_terms
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".phenotypes WHERE phenotype_ontology_id != '0'" )
		for row in self.cursor.fetchall( ) :
			sqlFormat = ",".join( ['%s'] * (len(row) + 1) )
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".ontology_terms VALUES( %s )" % sqlFormat, [
				row['phenotype_id'], 
				row['phenotype_official_id'], 
				row['phenotype_name'], 
				row['phenotype_desc'], 
				row['phenotype_synonyms'], 
				row['phenotype_replacement'], 
				row['phenotype_subsets'], 
				row['phenotype_preferred_name'], 
				row['phenotype_ontology_id'], 
				row['phenotype_addeddate'],
				row['phenotype_status'],
				0,
				"-",
				"-",
				0,
				0
			])
			
		self.cursor.execute( "UPDATE " + Config.DB_IMS + ".ontology_terms SET ontology_term_isroot='1' WHERE ontology_term_id IN ( SELECT ontology_rootid FROM " + Config.DB_IMS + ".ontologies )" )
		
	def copyOntologyTermsToSearch( self ) :
	
		"""
		Copy Operation
			-> IMS4: ontology_terms
			-> IMS4: ontology_term_search
		"""
		
		self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".ontology_term_search SELECT ontology_term_id, ontology_term_official_id, ontology_term_name, ontology_term_synonyms, ontology_id, ontology_term_childcount FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_term_status='active' AND ontology_term_isroot='0'" )
		
	def setupNewOntologies( self ) :
		
		"""Add new ontologies to the the end of the ontologies table"""
		
		self.addOntology( 
			"BioGRID Experimental System Ontology", 
			"https://raw.githubusercontent.com/BioGRID/BioGRID-Ontologies/master/BioGRIDExperimentalSystems.obo", 
			"BIOGRID:0000030", 
			"BioGRID Experimental System Ontology", 
			"BioGRID Experimental Systems used to classify interactions" 
		)
		
		self.addOntology( 
			"BioGRID Chemical Actions Ontology", 
			"https://raw.githubusercontent.com/BioGRID/BioGRID-Ontologies/master/BioGRIDChemicalActions.obo", 
			"BIOGRID:0000031", 
			"BioGRID Chemical Actions Ontology", 
			"BioGRID Terms relating to effects and modes of employment for drugs and chemicals" 
		)
		
		self.addOntology( 
			"BioGRID Participant Tag Ontology", 
			"https://raw.githubusercontent.com/BioGRID/BioGRID-Ontologies/master/BioGRIDParticipantTags.obo", 
			"BIOGRID:0000032", 
			"BioGRID Participant Tag Ontology", 
			"BioGRID tags denoting additional annotation specifically attributed to a single participant" 
		)
		
		self.addOntology( 
			"BioGRID Phenotype Types Ontology", 
			"https://raw.githubusercontent.com/BioGRID/BioGRID-Ontologies/master/BioGRIDPhenotypeTypes.obo", 
			"BIOGRID:0000033", 
			"BioGRID Phenotype Types Ontology", 
			"BioGRID terms for qualifiying phenotypes" 
		)
		
		self.addOntology( 
			"BioGRID Post-Translational Modification Ontology", 
			"https://raw.githubusercontent.com/BioGRID/BioGRID-Ontologies/master/BioGRIDPostTranslationalModifications.obo", 
			"BIOGRID:0000034", 
			"BioGRID Post-Translational Modification Ontology", 
			"BioGRID post-translational modification classification terms" 
		)
		
		self.addOntology( 
			"BioGRID Sources Ontology", 
			"https://raw.githubusercontent.com/BioGRID/BioGRID-Ontologies/master/BioGRIDSources.obo", 
			"BIOGRID:0000035", 
			"BioGRID Sources Ontology", 
			"BioGRID database/website source references for curated data" 
		)
		
		self.addOntology( 
			"BioGRID Throughput Ontology", 
			"https://raw.githubusercontent.com/BioGRID/BioGRID-Ontologies/master/BioGRIDThroughput.obo", 
			"BIOGRID:0000036", 
			"BioGRID Throughput Ontology", 
			"BioGRID terms for classifying the throughput methodology of a dataset" 
		)
		
		self.addOntology( 
			"BioGRID Post-Translational Modification Identities Ontology", 
			"https://raw.githubusercontent.com/starkfree/BioGRID-Ontologies/master/BioGRIDPostTranslationalModificationIdentities.obo", 
			"BIOGRID:0000037", 
			"BioGRID Post-Translational Modification Identities Ontology", 
			"BioGRID terms for classifying the identity of PTM relationships" 
		)
		
	def addOntology( self, ontologyName, ontologyURL, termID, termName, termDef ) :
	
		"""Input both a new Ontology and a ROOT ID reference and establish linkage between the two within the database"""
	
		# Add Ontology
		self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".ontologies VALUES( '0', %s, %s, %s, NOW( ), '0000-00-00 00:00:00', 'active' )", [ontologyName, ontologyURL, "0"] )
		ontologyID = self.cursor.lastrowid
		
		# Add Root Ontology Term
		self.cursor.execute( "INSERT INTO ontology_terms VALUES( '0', %s, %s, %s, '-', '-', '-', '-', %s, NOW( ), 'active', '0', '-', '1' )", [termID, termName, termDef, ontologyID] )
		rootTermID = self.cursor.lastrowid
		
		# Map new root to ontology
		self.cursor.execute( "UPDATE ontologies SET ontology_rootid=%s WHERE ontology_id=%s", [rootTermID, ontologyID] )
		self.db.commit( )
		
		