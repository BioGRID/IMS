import sys, string
import Config

class OntologyHash( ) :

	"""Tools for Generating quick lookup Hashes for Ontologies"""

	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		
	def buildTermHash( self, ontologyID ) :
	
		"""Build a quick lookup hash of terms within an ontology"""
		
		termHash = { }
		
		if ontologyID == "" :
			self.cursor.execute( "SELECT ontology_term_id, ontology_term_official_id FROM " + Config.DB_IMS + ".ontology_terms" )
		else :
			self.cursor.execute( "SELECT ontology_term_id, ontology_term_official_id FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_id=%s", [ontologyID] )

		for row in self.cursor.fetchall( ) :
			termHash[row['ontology_term_official_id'].upper( )] = str(row['ontology_term_id'])
			
		return termHash
		
	def buildRelationshipHash( self, ontologyID ) :
	
		"""Build a quick lookup hash of terms within an ontology"""
		
		termRelHash = { }
		self.cursor.execute( "SELECT ontology_term_id, ontology_parent_id, ontology_relationship_type, ontology_relationship_id FROM " + Config.DB_IMS + ".ontology_relationships WHERE ontology_term_id IN ( SELECT ontology_term_id FROM ontology_terms WHERE ontology_id=%s )", [ontologyID] )
		
		for row in self.cursor.fetchall( ) :
			relationship = str(row['ontology_term_id']) + "|" + str(row['ontology_parent_id']) + "|" + str(row['ontology_relationship_type'])
			termRelHash[relationship.upper( )] = str(row['ontology_relationship_id'])
			
		return termRelHash
		
	def buildTermDetailHash( self, ontologyID ) :
	
		"""Build a quick lookup hash of terms within an ontology"""
		
		termHash = { }
		
		if ontologyID == "" :
			self.cursor.execute( "SELECT ontology_term_id, ontology_term_official_id, ontology_term_name, ontology_term_childcount FROM " + Config.DB_IMS + ".ontology_terms" )
		else :
			self.cursor.execute( "SELECT ontology_term_id, ontology_term_official_id, ontology_term_name, ontology_term_childcount FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_id=%s", [ontologyID] )

		for row in self.cursor.fetchall( ) :
			termHash[str(row['ontology_term_id'])] = { "ID" : str(row['ontology_term_id']), "NAME" : row['ontology_term_name'], "OFFICIAL_ID" : row['ontology_term_official_id'], "COUNT" : str(row['ontology_term_childcount']) }
			
		return termHash