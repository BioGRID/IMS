import sys, string
import Config
import datetime
import re
import json

from classes import Lookups

class Matrix( ) :

	"""Tools for Handling the Creation of the Matrix Table"""
	
	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		self.lookups = Lookups.Lookups( db, cursor )
		
		# Quick Lookup Hashes
		self.interactionTypesHASH = self.lookups.buildInteractionTypeHash( )
		self.historyOperationsHASH = self.lookups.buildHistoryOperationsHash( )
		self.participantTypeHASH = self.lookups.buildParticipantTypesHash( )
		self.participantRoleHASH = self.lookups.buildParticipantRoleHash( )
		self.annotationHASH = self.lookups.buildAnnotationHash( )
		self.participantTypeMappingHASH = self.lookups.buildParticipantTypeMappingHash( )
		self.attributeTypeHASH = self.lookups.buildAttributeTypeHASH( )
		self.ontologyTermHASH = self.lookups.buildAttributeOntologyTermHASH( )
		
		# Attribute Value Lookups
		self.attributeValues = { }
		
	def buildMatrix( self ) :
		
		"""Build out the Matrix Table One Interaction at a TIme"""
		
		self.cursor.execute( "SELECT interaction_id, dataset_id, interaction_type_id, interaction_state FROM " + Config.DB_IMS + ".interactions ORDER BY interaction_id ASC" )
		
		intCount = 0
		for row in self.cursor.fetchall( ) :
		
			intCount += 1
		
			typeName = self.interactionTypesHASH[str(row['interaction_type_id'])]
			
			interaction = [str(row['interaction_id'])]
			interaction.append( self.buildInteractionDetails( str(row['interaction_id']) ) )
			interaction.extend( [str(row['dataset_id']), str(row['interaction_type_id']), typeName, row['interaction_state']] )
			
			historyInfo = self.fetchLatestHistory( row['interaction_id'] )
			historyOperation = self.historyOperationsHASH[str(historyInfo['history_operation_id'])]
			interaction.extend( [historyInfo['modification_type'], str(historyInfo['history_operation_id']), historyOperation, historyInfo['history_comment']] )
			
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".matrix VALUES( %s, %s, %s, %s, %s, %s, %s, %s, %s, %s )", interaction )
			
			if (intCount % 10000) == 0 :
				self.db.commit( )
			
		self.db.commit( )
		
	def buildInteractionDetails( self, interactionID ) :
	
		"""Build a JSON array representing the details of an interaction for quick display purposes"""
		
		interactionDetails = { "interaction_id" : interactionID }
		interactionDetails["participants"] = self.buildParticipantDetails( interactionID )
		interactionDetails["attributes"] = self.buildAttributeDetails( interactionID )
		
		return json.dumps( interactionDetails, ensure_ascii=False ).encode( 'utf8' )
		
	def buildParticipantDetails( self, interactionID ) :
	
		"""Build a list of participant details for an interaction""" 
		
		participants = []
		self.cursor.execute( "SELECT participant_id, participant_role_id FROM " + Config.DB_IMS + ".interaction_participants WHERE interaction_participant_status='active' AND interaction_id=%s", [interactionID] )
		
		for row in self.cursor.fetchall( ) :
			participant = { "participant_id" : str(row['participant_id']) }
			participant["participant_role_id"] = str(row['participant_role_id'])
			participant["participant_role_name"] = self.participantRoleHASH[participant["participant_role_id"]]
			participant["participant_type_id"] = self.participantTypeMappingHASH[participant["participant_id"]]
			participant["participant_type_name"] = self.participantTypeHASH[participant["participant_type_id"]]
			participant["participant_annotation"] = self.annotationHASH[participant["participant_id"]]
			participants.append( participant )
			
		return participants
		
	def buildAttributeDetails( self, interactionID ) :
	
		"""Build a set of attributes for an interaction"""
		
		attributes = { }
		self.cursor.execute( "SELECT interaction_attribute_id, attribute_id, user_id FROM " + Config.DB_IMS + ".interaction_attributes WHERE interaction_id=%s AND interaction_attribute_parent='0' AND interaction_attribute_status='active'", [interactionID] )
		
		for row in self.cursor.fetchall( ) :
		
			attribute = self.buildAttribute( str(row['attribute_id']), str(row['user_id']), str(row['interaction_attribute_id']) )
			
			# NESTED STRUCTURE OF CATEGORIES -> TYPES -> ATTRIBUTES -> ATTRIBUTE_CHILDREN
			
			if attribute["attribute_type_category_name"].lower( ) not in attributes :
				attributes[attribute["attribute_type_category_name"].lower( )] = { }
				
			if attribute["attribute_type_name"].lower( ) not in attributes[attribute["attribute_type_category_name"].lower( )] :
				attributes[attribute["attribute_type_category_name"].lower( )][attribute["attribute_type_name"].lower( )] = []
			
			attributes[attribute["attribute_type_category_name"].lower( )][attribute["attribute_type_name"].lower( )].append( attribute )
			
		return attributes
		
	def buildAttribute( self, attributeID, userID, interactionAttributeID ) :
	
		"""Build out the details on a single attribute and its children"""
		
		attribute = { "attribute_id" : attributeID, "user_id" : userID }
		
		attributeDetails = self.fetchAttributeValue( str(attributeID) )
		
		attributeInfo = self.attributeTypeHASH[str(attributeDetails["attribute_type_id"])]
		attribute["attribute_type_id"] = attributeInfo["attribute_type_id"]
		attribute["attribute_type_name"] = attributeInfo["attribute_type_name"]
		attribute["attribute_type_shortcode"] = attributeInfo["attribute_type_shortcode"]
		attribute["attribute_type_category_id"] = attributeInfo["attribute_type_category_id"]
		attribute["attribute_type_category_name"] = attributeInfo["attribute_type_category_name"]
		
		# ATTRIBUTE VALUE
		attribute["attribute_value"] = attributeDetails["attribute_value"]
		
		# ONTOLOGY TERM ATTRIBUTES
		attribute["attribute_annotation"] = { }
		if attribute["attribute_type_category_id"] == 1 :
			attribute["attribute_annotation"] = self.ontologyTermHASH[attributeDetails["attribute_value"]]
		
		# CHILDREN
		self.cursor.execute( "SELECT interaction_attribute_id, attribute_id, user_id FROM " + Config.DB_IMS + ".interaction_attributes WHERE interaction_attribute_parent=%s AND interaction_attribute_status='active'", [interactionAttributeID] )
		
		attribute["attribute_children"] = []
		for row in self.cursor.fetchall( ) :
			attribute["attribute_children"].append( self.buildAttribute( str(row['attribute_id']), str(row['user_id']), str(row['interaction_attribute_id']) ))
			
		return attribute
			
	def fetchAttributeValue( self, attributeID ) :
	
		"""Fetch attribute value from HASH or look up in database"""
	
		if str(attributeID) not in self.attributeValues :
			self.cursor.execute( "SELECT attribute_value, attribute_type_id FROM " + Config.DB_IMS + ".attributes WHERE attribute_id=%s LIMIT 1", [attributeID] )
			row = self.cursor.fetchone( )
			self.attributeValues[str(attributeID)] = row
		
		return self.attributeValues[str(attributeID)]
		
	def fetchLatestHistory( self, interactionID ) :
	
		"""Grab latest history details from the history table"""
		
		self.cursor.execute( "SELECT modification_type, history_comment, history_operation_id FROM " + Config.DB_IMS + ".history WHERE interaction_id=%s ORDER BY history_addeddate DESC LIMIT 1", [interactionID] )
		
		row = self.cursor.fetchone( )
		return row