import sys, string
import Config
import datetime
import re

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
		
	def buildMatrix( self ) :
		
		"""Build out the Matrix Table One Interaction at a TIme"""
		
		self.cursor.execute( "SELECT interaction_id, dataset_id, interaction_type_id, interaction_state FROM " + Config.DB_IMS + ".interactions ORDER BY interaction_id ASC" )
		
		intCount = 0
		for row in self.cursor.fetchall( ) :
		
			intCount += 1
		
			typeName = self.interactionTypesHASH[str(row['interaction_type_id'])]
			
			interaction = [str(row['interaction_id'])]
			interaction.append( "-" )
			interaction.extend( [str(row['dataset_id']), str(row['interaction_type_id']), typeName, row['interaction_state']] )
			
			historyInfo = self.fetchLatestHistory( row['interaction_id'] )
			historyOperation = self.historyOperationsHASH[str(historyInfo['history_operation_id'])]
			interaction.extend( [historyInfo['modification_type'], str(historyInfo['history_operation_id']), historyOperation, historyInfo['history_comment']] )
			
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".matrix VALUES( %s, %s, %s, %s, %s, %s, %s, %s, %s, %s )", interaction )
			
			if (intCount % 10000) == 0 :
				self.db.commit( )
			
		self.db.commit( )
		
	def fetchLatestHistory( self, interactionID ) :
	
		"""Grab latest history details from the history table"""
		
		self.cursor.execute( "SELECT modification_type, history_comment, history_operation_id FROM " + Config.DB_IMS + ".history WHERE interaction_id=%s ORDER BY history_addeddate DESC LIMIT 1", [interactionID] )
		
		row = self.cursor.fetchone( )
		return row