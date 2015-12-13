import sys, string
import Config
import datetime
import re

class DBProcessor( ) :

	"""Tools for Handling Adding of Attributes"""

	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		
	def processInteractionAttribute( self, interactionID, attribVal, attribType, attribDate, parentID, userID, mappingStatus ) :
	
		"""Process adding and mapping of the interaction to its attribute"""
		
		self.cursor.execute( "SELECT attribute_id FROM " + Config.DB_IMS + ".attributes WHERE attribute_value=%s AND attribute_type_id=%s AND attribute_status='active' LIMIT 1", [attribVal.strip( ), attribType] )
		
		row = self.cursor.fetchone( )
		if None == row :
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".attributes VALUES ( '0', %s, %s, %s, 'active' )", [attribVal, attribType, attribDate] )
			attribID = self.cursor.lastrowid
		else :
			attribID = row['attribute_id']
		
		self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".interaction_attributes VALUES( '0', %s, %s, %s, %s, %s, %s )", [interactionID, attribID, parentID, userID, attribDate, mappingStatus] )
		
		return self.cursor.lastrowid
		
	def processParticipant( self, interactorID, interactionID, role, type, dateAdded ) :
	
		"""Check if participant exists already, if not add it, then process mappings"""
	
		self.cursor.execute( "SELECT participant_id FROM " + Config.DB_IMS + ".participants WHERE participant_value=%s AND participant_type_id=%s LIMIT 1", [interactorID, type] )
		row = self.cursor.fetchone( )
		
		participantID = ""
		if None == row :
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".participants VALUES( '0', %s, %s, %s, 'active' )", [interactorID, type, dateAdded] )
			participantID = self.cursor.lastrowid
		else :
			participantID = str(row['participant_id'])
			
		self.processInteractionParticipant( participantID, interactionID, role, dateAdded )
		
	def processInteractionParticipant( self, participantID, interactionID, role, dateAdded ) :
	
		"""Add entries for the interactors to the interaction_participants table"""
	
		self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".interaction_participants VALUES( '0', %s, %s, %s, %s, 'active' )", [interactionID, participantID, role, dateAdded] )