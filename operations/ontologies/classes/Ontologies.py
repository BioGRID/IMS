import sys, string
import Config

from classes import OntologyHash

class Ontologies( ) :

	"""Tools for Processing Ontology Terms in to the Database"""

	def __init__( self, db, cursor, ontologyID ) :
		self.db = db
		self.cursor = cursor
		self.ontologyHash = OntologyHash.OntologyHash( db, cursor )
		self.termHash = self.ontologyHash.buildTermHash( ontologyID )
		self.termRelHash = self.ontologyHash.buildRelationshipHash( ontologyID )
		
	def processTerm( self, termOfficialID, termDetails, rootID, ontologyID ) :
	
		"""Process the deta stored for a term and either update or insert as required"""
	
		self.cursor.execute( "SELECT ontology_term_id FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_term_official_id=%s LIMIT 1", [termOfficialID] )
		row = self.cursor.fetchone( )
			
		termID = "0"
		if None != row :
			termID = str(row['ontology_term_id'])
			
		termName = "-"
		if "name" in termDetails :
			termName = termDetails["name"][0]
		
		termDesc = "-"
		if "def" in termDetails :
			termDesc = "|".join( termDetails["def"] )
			
		termSynonyms = "-"
		if "synonym" in termDetails :
			termSynonyms = "|".join( termDetails["synonym"] )
		
		termReplacement = "-"
		if "replaced_by" in termDetails :
			termReplacement = "|".join( termDetails["replaced_by"] )
		
		termSubsets = "-"
		if "subset" in termDetails :
			termSubsets = "|".join( termDetails["subset"] )
		
		termStatus = "active"
		if "is_obsolete" in termDetails :
			if "true" == termDetails["is_obsolete"][0] :
				termStatus = "inactive"
		
		termChildCount = "0"
		termParent = "-"
		
		if termID != "0" :
			self.cursor.execute( "UPDATE " + Config.DB_IMS + ".ontology_terms SET ontology_term_name=%s, ontology_term_desc=%s, ontology_term_synonyms=%s, ontology_term_replacement=%s, ontology_term_subsets=%s, ontology_term_status=%s, ontology_term_childcount=%s, ontology_term_parent=%s WHERE ontology_term_id=%s", [termName, termDesc, termSynonyms, termReplacement, termSubsets, termStatus, termChildCount, termParent, termID]  )
		else :
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".ontology_terms VALUES( %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW( ), %s, %s, %s, %s )", [termID, termOfficialID, termName, termDesc, termSynonyms, termReplacement, termSubsets, "-", ontologyID, termStatus, termChildCount, termParent, '0'] )
			termID = self.cursor.lastrowid
			self.termHash[termOfficialID.upper( )] = termID
			
		#if termStatus != "inactive" :
			#self.processRelationships( termID, termDetails, rootID, ontologyID )
		
	def processRelationships( self, termOfficialID, termDetails, rootID, ontologyID ) :
	
		"""Step through both is_a and other relationships and update/insert relationships"""
		
		termID = self.termHash[termOfficialID.upper( )]
	
		self.cursor.execute( "UPDATE " + Config.DB_IMS + ".ontology_relationships SET ontology_relationship_status='inactive' WHERE ontology_term_id=%s", [termID] )
		self.db.commit( )
		
		if "is_a" not in termDetails :
			self.processRelationship( termID, rootID, "is_a" )
		else :
			for relationship in termDetails["is_a"] :
				# Clean up improperly formatted is_a relationships
				# by cutting out anything past a space
				relationship = relationship.split( " " )
				relationship = relationship[0].strip( )
				if relationship.upper( ) in self.termHash :
					self.processRelationship( termID, self.termHash[relationship.upper( )], "is_a" )
					
		if "relationship" in termDetails :	
			for relationship in termDetails["relationship"] :
				splitRel = relationship.split( "|" )
				if len(splitRel) > 1 and splitRel[1].upper( ) in self.termHash :
					self.processRelationship( termID, self.termHash[splitRel[1].upper( )], splitRel[0].lower( ) )
	
	def processRelationship( self, termID, parentID, type ) :
		
		"""Process a single relationship record"""
		
		relationship = str(termID) + "|" + str(parentID) + "|" + str(type)
		
		if relationship.upper( ) not in self.termRelHash :
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".ontology_relationships VALUES( '0', %s, %s, %s, NOW( ), 'active' )", [termID, parentID, type] )
			relID = self.cursor.lastrowid
			self.termRelHash[relationship.upper( )] = str(relID)
		else :
			ontologyRelationshipID = self.termRelHash[relationship.upper( )]
			self.cursor.execute( "UPDATE " + Config.DB_IMS + ".ontology_relationships SET ontology_relationship_status='active' WHERE ontology_relationship_id=%s", [ontologyRelationshipID] )