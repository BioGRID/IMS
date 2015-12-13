import sys, string
import Config

from classes import Lookups, DBProcessor

class Participants( ) :

	"""Tools for Handling the Migration of Participant Data from IMS 2 to IMS 4"""

	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		self.lookups = Lookups.Lookups( db, cursor )
		self.dbProcessor = DBProcessor.DBProcessor( db, cursor )
		
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
		
			self.dbProcessor.processParticipant( row['interactor_A_id'], row['interaction_id'], '2', '1', dateAdded )
			self.dbProcessor.processParticipant( row['interactor_B_id'], row['interaction_id'], '3', '1', dateAdded )
			
			if (partCount % 10000) == 0 :
				self.db.commit( )
				
		self.db.commit( )