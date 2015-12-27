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
				attributeList.append( self.combineNumbers( attRow['attribute_id'], attRow['interaction_attribute_parent'] ))
				
			attributeList.sort( )
			
			self.cursor.execute( "UPDATE " + Config.DB_IMS + ".interactions SET attribute_hash = %s WHERE interaction_id=%s", [self.hashids.encode( *attributeList ), row['interaction_id']] )
			
			if (intCount % 10000) == 0 :
				self.db.commit( )
			
		self.db.commit( )
		
	def combineNumbers( self, num1, num2 ) :
	
		"""Combine two numbers into a single number, not by addition"""
		
		return int(str(num1) + str(num2))