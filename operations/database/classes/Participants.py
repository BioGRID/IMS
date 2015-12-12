import sys, string
import Config

from classes import Lookups

class Participants( ) :

	"""Tools for Handling the Migration of Participant Data from IMS 2 to IMS 4"""

	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		self.lookups = Lookups.Lookups( db, cursor )
		
		# Quick Lookup Data Structures
		self.activatedHash = self.lookups.buildInteractionActivationHash( )
		
	def migrateParticipants( self ) :
	
		"""
		Copy Operation
			-> IMS2: interactions
			-> IMS4: participants, interaction_participants
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".interactions WHERE interaction_id IN ( SELECT interaction_id FROM " + Config.DB_IMS + ".interactions ORDER BY interaction_id ASC )" )
		
		partCount = 0
		for row in self.cursor.fetchall( ) :
		
			partCount += 1
			
			activationInfo = self.activatedHash[str(row['interaction_id'])]
			dateAdded = activationInfo["DATE"]
		
			self.processParticipant( row['interactor_A_id'], row['interaction_id'], '2', '1', dateAdded )
			self.processParticipant( row['interactor_B_id'], row['interaction_id'], '3', '1', dateAdded )
			
			if (partCount % 10000) == 0 :
				self.db.commit( )
				
		self.db.commit( )
		
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