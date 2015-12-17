import sys, string
import Config
import datetime
import re

class PTM( ) :

	"""Tools for Handling the Migration of PTM Data from IMS 2 to IMS 4"""
	
	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor