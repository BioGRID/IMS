
import sys, string
import Config

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
		
		if self.verbose :
			print "----------------------------------------------------------"
			print "Building Interactions"
			print "----------------------------------------------------------"
			
		self.processSQL( "interaction_types-data.sql" )
		
		
	def clean( self, table ) :
	
		"""Clean a table and reload it from the SQL files"""
		
		if self.verbose :
			print "----------------------------------------------------------"
			print "Cleaning Table: " + table
			print "----------------------------------------------------------"
		
		# self.cursor.execute( "DROP TABLE IF EXISTS " + Config.DB_IMS + "." + table )
		self.processSQL( table + "-structure.sql" )
		
	def processSQL( self, file ) :
	
		"""Process an SQL file line by line and execute the statements"""
		
		with open( self.SQL_DIR + file, "r" ) as sqlFile :
			sqlData = sqlFile.read( ).replace( "\n", '' )
			
		queries = sqlData.split( ";" )
		for query in queries :
			query = query.strip( )
			if len(query) > 0 and query[:2] != "--" :
				
				if self.verbose :
					print "---> " + query
				
				self.cursor.execute( query )
				self.db.commit( )
				
		if self.verbose :
			print ""