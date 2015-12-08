import sys, string
import Config

class Children( ) :

	"""Tools for calculating the number of children for each term"""

	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		
	def fetchChildren( self, parentID ) :
	
		"""Recursively fetch children until all have been retrieved"""
		childSet = []
		self.cursor.execute( "SELECT ontology_term_id FROM " + Config.DB_IMS + ".ontology_relationships WHERE ontology_parent_id=%s ANd ontology_relationship_type='is_a' AND ontology_relationship_status='active'", [parentID] )
		
		for child in self.cursor.fetchall( ) :
			childSet.append( child['ontology_term_id'] )
			childSet = childSet + self.fetchChildren( child['ontology_term_id'] )
			
		return childSet