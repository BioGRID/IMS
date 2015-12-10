import sys, string
import Config

class History( ) :

	"""Tools for Handling the Migration of Interaction History Data from IMS 2 to IMS 4"""

	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		
	def migrateHistory( self ) :
	
		"""
		Copy Operation
			-> IMS2: interaction_history
			-> IMS4: history
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".interaction_history WHERE interaction_id IN ( SELECT interaction_id FROM " + Config.DB_IMS + ".interactions )" )
		rowCount = 0
		for row in self.cursor.fetchall( ) :
		
			rowCount += 1
		
			modificationType = row['modification_type']
			if modificationType == "DEACTIVATED" :
				modificationType = "DISABLED"
		
			self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".history VALUES( %s, %s, %s, %s, %s, %s, %s )" , [
				row['interaction_history_id'], 
				modificationType, 
				row['interaction_id'], 
				row['user_id'], 
				row['interaction_history_comment'],
				'12', 
				row['interaction_history_date']
			])
			
			if (rowCount % 10000) == 0 :
				self.db.commit( )
			
		self.db.commit( )