import sys, string
import Config

class Parents( ) :

	"""Tools for calculating the parents for a term"""

	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		
	def fetchParentPath( self, termID, path, pathSet, allBranches = True ) :
	
		"""Recursively fetch parents until a full path to root has been retrieved"""
		
		path = [termID] + path
		basePath = list(path)
		
		if allBranches :
			self.cursor.execute( "SELECT ontology_parent_id FROM " + Config.DB_IMS + ".ontology_relationships WHERE ontology_term_id=%s AND ontology_relationship_type='is_a' AND ontology_relationship_status='active'", [termID] )
		else :
			self.cursor.execute( "SELECT ontology_parent_id FROM " + Config.DB_IMS + ".ontology_relationships WHERE ontology_term_id=%s AND ontology_relationship_type='is_a' AND ontology_relationship_status='active' LIMIT 1", [termID] )
		
		if self.cursor.rowcount != 0 :
			for parent in self.cursor.fetchall( ) :
				path = self.fetchParentPath( parent['ontology_parent_id'], list(basePath), pathSet, False )
		else :
			pathSet.append( list(path) )
			
		return path