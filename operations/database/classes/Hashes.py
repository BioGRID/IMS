import sys, string
import Config
import Database

from classes import Hashids

class Hashes( ) :

	"""Tools for Handling the Creation of Hashes in IMS 4"""
	
	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		self.hashids = Hashids.Hashids( )
		
	def createParticipantHashes( self ) :
	
		"""Create Hashes for Participants and Participant Roles"""
		
		intCount = 0
		
		self.cursor.execute( "SELECT interaction_id FROM " + Config.DB_IMS + ".interactions" )
		for row in self.cursor.fetchall( ) :
		
			intCount += 1
			
			self.cursor.execute( "SELECT participant_id, participant_role_id FROM " + Config.DB_IMS + ".interaction_participants WHERE interaction_id=%s AND interaction_participant_status='active'", [row['interaction_id']] )
			
			participantList = []
			for partRow in self.cursor.fetchall( ) :
				participantList.append( self.combineNumbers( partRow['participant_id'], partRow['participant_role_id'] ))
				
			participantList.sort( )
			
			self.cursor.execute( "UPDATE " + Config.DB_IMS + ".interactions SET participant_hash = %s WHERE interaction_id=%s", [self.hashids.encode( *participantList ), row['interaction_id']] )
			
			if (intCount % 10000) == 0 :
				self.db.commit( )
			
		self.db.commit( )
		
	def createAttributeHashes( self ) :
	
		"""Create Hashes for Attributes and Attribute Parents"""
		
		intCount = 0
		
		self.cursor.execute( "SELECT interaction_id FROM " + Config.DB_IMS + ".interactions" )
		for row in self.cursor.fetchall( ) :
		
			intCount += 1
			
			self.cursor.execute( "SELECT attribute_id, interaction_attribute_parent FROM " + Config.DB_IMS + ".interaction_attributes WHERE interaction_id=%s AND interaction_attribute_status='active'", [row['interaction_id']] )
			
			attributeList = []
			for attRow in self.cursor.fetchall( ) :
			
				parentAttID = attRow['interaction_attribute_parent']
				if parentAttID != 0 :
					self.cursor.execute( "SELECT attribute_id FROM " + Config.DB_IMS + ".interaction_attributes WHERE interaction_attribute_id=%s AND interaction_attribute_status='active'", [parentAttID] )
					
					parentRow = self.cursor.fetchone( )
					parentAttID = parentRow['attribute_id']
			
				attributeList.append( self.combineNumbers( attRow['attribute_id'], parentAttID ))
				
			attributeList.sort( )
			
			self.cursor.execute( "UPDATE " + Config.DB_IMS + ".interactions SET attribute_hash = %s WHERE interaction_id=%s", [self.hashids.encode( *attributeList ), row['interaction_id']] )
			
			if (intCount % 10000) == 0 :
				self.db.commit( )
			
		self.db.commit( )
		
	def createParticipantHashByInteractionID( self, interactionID ) :
	
		"""Create Single Interaction Participant and Participant Roles Hash"""
		
		intCount = 0
		
		self.cursor.execute( "SELECT interaction_id FROM " + Config.DB_IMS + ".interactions WHERE interaction_id=%s", [interactionID] )
		row = self.cursor.fetchone( )
		
		self.cursor.execute( "SELECT participant_id, participant_role_id FROM " + Config.DB_IMS + ".interaction_participants WHERE interaction_id=%s AND interaction_participant_status='active'", [row['interaction_id']] )
		
		participantList = []
		for partRow in self.cursor.fetchall( ) :
			participantList.append( self.combineNumbers( partRow['participant_id'], partRow['participant_role_id'] ))
		
		participantList.sort( )
		print participantList
		
		return self.hashids.encode( *participantList )
		
	def combineNumbers( self, num1, num2 ) :
	
		"""Combine two numbers into a single number with triple zero padding in the middle, not by addition"""
		
		return int(str(num1) + "000" + str(num2))