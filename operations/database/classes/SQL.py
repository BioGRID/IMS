
import sys, string
import Config

from classes import Ontologies

class SQL( ) :

	"""Handles the processing and loading of SQL files"""

	def __init__( self, db, cursor, verbose ) :
		self.db = db
		self.cursor = cursor
		self.SQL_DIR = "sql/"
		self.verbose = verbose
		
		self.ontologies = Ontologies.Ontologies( db, cursor )
				
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
		
	def build_ontologies( self ) :
	
		"""Load data into the tables pertaining to ontologies"""
		
		self.writeHeader( "Building Ontologies" )
		
		self.writeLine( "Migrating Ontologies" )
		self.ontologies.migrateOntologies( )
		
		self.writeLine( "Migrating Ontology Terms" )
		self.ontologies.migrateOntologyTerms( )
		
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