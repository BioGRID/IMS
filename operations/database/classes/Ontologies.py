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
				row['phenotype_child_count'],
				row['phenotype_parents'],
				0
			])
			
		self.cursor.execute( "UPDATE " + Config.DB_IMS + ".ontology_terms SET ontology_term_isroot='1' WHERE ontology_term_id IN ( SELECT ontology_rootid FROM " + Config.DB_IMS + ".ontologies )" )
			
		