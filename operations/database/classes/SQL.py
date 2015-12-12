
import sys, string
import Config

from classes import Ontologies, Datasets, Interactions, History

class SQL( ) :

	"""Handles the processing and loading of SQL files"""

	def __init__( self, db, cursor, verbose ) :
		self.db = db
		self.cursor = cursor
		self.SQL_DIR = "sql/"
		self.verbose = verbose
				
	def clean_interactions( self ) :
	
		"""Clean interaction associated tables"""
	
		self.clean( "interactions" )
		self.clean( "interaction_types" )
		
	def build_interactions( self ) :
				
		"""Load data into the tables based on established criteria in the IMS class"""
		self.writeHeader( "Building Interactions" )
		self.processSQL( "interaction_types-data.sql" )
		
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
		
	def build_datasets( self ) :
	
		"""Load data into the tables pertaining to Datasets"""
		
		datasets = Datasets.Datasets( self.db, self.cursor )
		
		self.writeHeader( "Building Datasets" )
		
		self.writeLine( "Loading Dataset Types" )
		self.processSQL( "dataset_types-data.sql" )
		
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
		
	def build_interactions( self ) :
	
		"""Load data into the tables pertaining to Interactions"""
		
		self.writeHeader( "Building Interactions" )
		self.writeLine( "Building Quick Lookup Sets" )
		
		interactions = Interactions.Interactions( self.db, self.cursor )
		
		self.writeLine( "Loading Interaction Types" )
		self.processSQL( "interaction_types-data.sql" )
		
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
		
	def build_attributes( self ) :
	
		self.writeHeader( "Resetting Attributes" )
		
		self.writeLine( "Loading Attribute Types" )
		self.processSQL( "attribute_types-data.sql" )
		
		self.writeLine( "Loading Attribute Type Categories" )
		self.processSQL( "attribute_type_categories-data.sql" )
		
	def clean_history( self ) :
	
		"""Clean the history and history_operations tables"""
		
		self.clean( "history" )
		self.clean( "history_operations" )
		
	def build_history( self ) :
	
		"""Load Interaction History Data"""
	
		self.writeHeader( "Building History" )
		
		history = History.History( self.db, self.cursor )
		
		self.writeLine( "Migrating History Operations" )
		self.processSQL( "history_operations-data.sql" )
		
		self.writeLine( "Migrating History" )
		history.migrateHistory( )
		
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