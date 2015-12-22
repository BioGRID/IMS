
import sys, string
import Config

from classes import Ontologies, Datasets, Interactions, History, Participants, Complexes, Forced, ForcedComplexes, Chemicals, PTM, Groups

class SQL( ) :

	"""Handles the processing and loading of SQL files"""

	def __init__( self, db, cursor, verbose ) :
		self.db = db
		self.cursor = cursor
		self.SQL_DIR = "sql/"
		self.verbose = verbose
		
	def clean_ontologies( self ) :
	
		"""Clean interaction associated tables"""
	
		self.clean( "ontologies" )
		self.clean( "ontology_terms" )
		self.clean( "ontology_relationships" )
		
	def build_ontologies( self ) :
	
		"""Load data into the tables pertaining to ontologies"""
		
		ontologies = Ontologies.Ontologies( self.db, self.cursor )
		
		self.writeHeader( "Building Ontologies" )
		
		self.writeLine( "Migrating Ontologies" )
		ontologies.migrateOntologies( )
		
		self.writeLine( "Migrating Ontology Terms" )
		ontologies.migrateOntologyTerms( )
		
		self.writeLine( "Adding New Ontologies" )
		ontologies.setupNewOntologies( )
		
		self.writeLine( "You will need to run an update of Ontologies separately to populate the relationships table" )
		
	def clean_datasets( self ) :
		
		"""Clean the Dataset Associated Tables"""
		
		self.clean( "datasets" )
		self.clean( "dataset_attributes" )
		self.clean( "dataset_history" )
		self.clean( "dataset_types" )
		self.clean( "pubmed_mappings" )
		self.clean( "pubmed_queries" )
		self.clean( "pubmed" )
		self.clean( "prepub" )
		
		self.writeLine( "Loading Dataset Types" )
		self.processSQL( "dataset_types-data.sql" )
		
	def build_datasets( self ) :
	
		"""Load data into the tables pertaining to Datasets"""
		
		datasets = Datasets.Datasets( self.db, self.cursor )
		
		self.writeHeader( "Building Datasets" )
		
		self.writeLine( "Migrating Pubmed Mappings" )
		datasets.migratePubmedMappings( )
		
		self.writeLine( "Migrating Pubmed Queries" )
		datasets.migratePubmedQueries( )
		
		self.writeLine( "Migrating Pubmed" )
		datasets.migratePubmed( )
		
		self.writeLine( "Migrating Pre-Pub" )
		datasets.migratePrepub( )
		
		self.writeLine( "Migrating History" )
		datasets.migrateHistory( )
		
		self.writeLine( "Publications will have no annotation, you will need to update that manually." )
		
	def clean_interactions( self ) :
		
		"""Clean the Interaction Associated Tables"""
		
		self.clean( "interactions" )
		self.clean( "interaction_attributes" )
		self.clean( "interaction_types" )
		
		self.writeLine( "Loading Interaction Types" )
		self.processSQL( "interaction_types-data.sql" )
		
	def build_interactions( self ) :
	
		"""Load data into the tables pertaining to Interactions"""
		
		self.writeHeader( "Building Interactions" )
		self.writeLine( "Building Quick Lookup Sets" )
		
		interactions = Interactions.Interactions( self.db, self.cursor )
	
		self.writeLine( "Migrating Interactions" )
		interactions.migrateInteractions( )
		
		self.writeLine( "Migrating Qualifications" )
		interactions.migrateQualifications( )
		
		self.writeLine( "Migrating Throughput Tags" )
		interactions.migrateThroughputTags( )
		
		self.writeLine( "Migrating Source Tags" )
		interactions.migrateSourceTags( )
		
		self.writeLine( "Migrating Ontology Terms" )
		interactions.migrateOntologyTerms( )
		
		self.writeLine( "Migrating Quantitative Scores" )
		interactions.migrateQuantitativeScores( )
		
	def clean_attributes( self ) :
	
		"""Clean the Attributes Associated Tables"""
	
		self.clean( "attributes" )
		self.clean( "attribute_types" )
		self.clean( "attribute_type_categories" )
		
		self.writeLine( "Loading Attribute Types" )
		self.processSQL( "attribute_types-data.sql" )
		
		self.writeLine( "Loading Attribute Type Categories" )
		self.processSQL( "attribute_type_categories-data.sql" )
		
	def build_attributes( self ) :
	
		self.writeHeader( "Attributes are loaded via Interaction Build" )
		
	def clean_history( self ) :
	
		"""Clean the history and history_operations tables"""
		
		self.clean( "history" )
		self.clean( "history_operations" )
		
		self.writeLine( "Migrating History Operations" )
		self.processSQL( "history_operations-data.sql" )
		
	def build_history( self ) :
	
		"""Load Interaction History Data"""
	
		self.writeHeader( "Building History" )
		
		history = History.History( self.db, self.cursor )
		
		self.writeLine( "Migrating History" )
		history.migrateHistory( )
		
	def clean_participants( self ) :
	
		"""Clean the participant related tables"""
		
		self.clean( "participants" )
		self.clean( "participant_roles" )
		self.clean( "participant_types" )
		self.clean( "participant_attributes" )
		self.clean( "interaction_participants" )
		self.clean( "interaction_participant_attributes" )
		
		self.writeLine( "Loading Participant Roles" )
		self.processSQL( "participant_roles-data.sql" )
		
		self.writeLine( "Loading Participant Types" )
		self.processSQL( "participant_types-data.sql" )
		
	def build_participants( self ) :
	
		"""Load paticipant data"""
		
		self.writeHeader( "Building Participants" )
		self.writeLine( "Building Quick Lookup Sets" )
		
		participants = Participants.Participants( self.db, self.cursor )
		
		self.writeLine( "Migrating Participants" )
		participants.migrateParticipants( )
		
	def clean_complexes( self ) :
	
		"""Clean the complex specific tables"""
		
		self.writeHeader( "No Complex Specific Tables to Clean" )
		
	def build_complexes( self ) :
	
		"""Load complex data into interactions"""
		
		self.writeHeader( "Building Complexes" )
		self.writeLine( "Building Quick Lookup Sets" )
		
		complexes = Complexes.Complexes( self.db, self.cursor )
		
		self.writeLine( "Migrating Complexes" )
		complexes.migrateComplexes( )
		
		self.writeLine( "Migrating Qualifications" )
		complexes.migrateQualifications( )
		
		self.writeLine( "Migrating Throughput Tags" )
		complexes.migrateThroughputTags( )
		
		self.writeLine( "Migrating Source Tags" )
		complexes.migrateSourceTags( )
		
		self.writeLine( "Migrating Ontology Terms" )
		complexes.migrateOntologyTerms( )
		
		self.writeLine( "Migrating Participants" )
		complexes.migrateParticipants( )
		
		self.writeLine( "Migrating History" )
		complexes.migrateHistory( )
		
	def clean_forced( self ) :
	
		"""Clean the Forced Interaction Specific Tables"""
		
		self.clean( "unknown_participants" )
		
	def build_forced( self ) :
	
		"""Load forced interaction data into interactions"""
		
		self.writeHeader( "Building Forced Interactions" )
		self.writeLine( "Building Quick Lookup Sets" )
		
		forced = Forced.Forced( self.db, self.cursor )
		
		self.writeLine( "Migrating Forced Interactions" )
		forced.migrateForcedInteractions( )
		
		self.writeLine( "Migrating Forced Qualifications" )
		forced.migrateQualifications( )
		
		self.writeLine( "Migrating Forced Throughput Tags" )
		forced.migrateThroughputTags( )
		
		self.writeLine( "Migrating Forced Quantitative Scores" )
		forced.migrateQuantitativeScores( )
		
		self.writeLine( "Migrating Forced Ontology Terms" )
		forced.migrateOntologyTerms( )
		
		self.writeLine( "Migrating Forced Participants" )
		forced.migrateParticipants( )
		
		self.writeHeader( "Building Forced Complexes" )
		self.writeLine( "Building Quick Lookup Sets" )
		
		forced = ForcedComplexes.ForcedComplexes( self.db, self.cursor )
		
		self.writeLine( "Migrating Forced Complexes" )
		forced.migrateForcedComplexes( )
		
		self.writeLine( "Migrating Forced Complex Qualifications" )
		forced.migrateQualifications( )
		
		self.writeLine( "Migrating Forced Complex Throughput Tags" )
		forced.migrateThroughputTags( )
		
		self.writeLine( "Migrating Forced Complex Participants" )
		forced.migrateParticipants( )
		
	def clean_chemicals( self ) :
	
		"""Clean the Chemical Specific Tables"""
		
		self.writeHeader( "No Chemical Specific Tables to Clean" )
		
	def build_chemicals( self ) :
	
		"""Load chemical interaction data into interactions"""
		
		self.writeHeader( "Building Chemical Interactions" )
		self.writeLine( "Building Quick Lookup Sets" )
		
		chemicals = Chemicals.Chemicals( self.db, self.cursor )
		
		self.writeLine( "Migrating Chemical Interactions" )
		chemicals.migrateChemicalMappings( )
		
	def clean_ptms( self ) :
		
		"""Clean the PTM Specific Tables"""
		
		self.writeHeader( "No PTM Specific Tables to Clean" )
		
	def build_ptms( self ) :
	
		"""Load PTM interaction data into interactions"""
		
		self.writeHeader( "Building PTM Interactions" )
		self.writeLine( "Building Quick Lookup Sets" )
		
		ptm = PTM.PTM( self.db, self.cursor )
		
		self.writeLine( "Migrating PTM Interactions" )
		ptm.migratePTMs( )
		
		self.writeLine( "Migrating PTM Orphan Interactions from Relationships" )
		ptm.migratePTMOrphanRelationships( )
		
		self.writeLine( "Migrating PTM Notes" )
		ptm.migratePTMNotes( )
		
	def clean_groups( self ) :
		
		"""Clean the Group Specific Tables"""
		
		self.clean( "groups" )
		self.clean( "group_datasets" )
		self.clean( "group_users" )
		
	def build_groups( self ) :
	
		"""Load GROUP data from PROJECT data"""
		
		self.writeHeader( "Building Groups" )
		self.writeLine( "Building Quick Lookup Sets" )
		
		groups = Groups.Groups( self.db, self.cursor )
		
		self.writeLine( "Migrating Groups" )
		groups.migrateGroups( )
		
		self.writeLine( "Migrating Group Users" )
		groups.migrageGroupUsers( )
		
		self.writeLine( "Migrating Group Datasets" )
		groups.migrateGroupDatasets( )
		
	def clean( self, table ) :
	
		"""Clean a table and reload it from the SQL files"""
		self.writeHeader( "Cleaning Table: " + table )
		self.processSQL( table + "-structure.sql" )
		
	def processSQL( self, file ) :
	
		"""Process an SQL file line by line and execute the statements"""
		
		with open( self.SQL_DIR + file, "r" ) as sqlFile :
			sqlData = sqlFile.read( ).replace( "\n", '' )
			
		queries = sqlData.split( ";" )
		for query in queries :
			query = query.strip( )
			if len(query) > 0 and query[:2] != "--" :
				
				self.writeLine( query )
				
				self.cursor.execute( query )
				self.db.commit( )
				
		if self.verbose :
			print ""
			
	def writeHeader( self, msg ) :
	
		"""Write a formatted header for Verbose Output"""
	
		if self.verbose :
			print "----------------------------------------------------------"
			print msg
			print "----------------------------------------------------------"
			
	def writeLine( self, msg ) :
	
		"""Write a formatted line of data for Verbose Output"""
	
		if self.verbose :
			print "---> " + msg