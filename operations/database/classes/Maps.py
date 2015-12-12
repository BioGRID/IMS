import sys, string
import Config

class Maps( ) :

	"""Functions for manual conversion from one value to another"""

	def convertQuantType( self, quantTypeID ) :
	
		quantTypeID = str(quantTypeID).lower( )
		
		quantTypes = {
			"1" : "15", # SGA Score
			"2" : "16", # Confidence Score
			"3" : "16", # Confidence Score
			"4" : "17", # P-Value
			"5"	: "18", # SAINT Score
			"6" : "19", # S-Value
			"7" : "20", # Denoised Score
			"8" : "21"  # compPASS Score
		}
		
		if quantTypeID in quantTypes :
			return quantTypes[quantTypeID]
			
		return ""