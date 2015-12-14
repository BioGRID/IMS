import sys, string
import Config

class Maps( ) :

	"""Functions for manual conversion from one value to another"""

	def convertQuantType( self, quantTypeID ) :
	
		"""Convert the Quantitative Score Types in IMS 2 to Attribute Type ID in IMS 4"""
	
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
		
	def convertPhenotypeFlag( self, flag ) :
	
		"""Convert the Phenotype Flag in IMS 2 to Attribute Type ID in IMS 4"""
		
		flag = flag.strip( ).upper( )
		
		if len(flag) == 0 :
			flag = "P"
		
		flags = { 
			"P"  : "1",   # Phenotype
			"CC" : "7",   # Cellular Location
			"CH" : "5",   # Chemical
			"CL" : "2",   # Cell Line
			"CT" : "6",   # Cell Type
			"DS" : "8",   # Disease Association
			"DV" : "9",   # Development
			"EV" : "10",  # Environmental
			"PW" : "3",   # Pathway
			"TS" : "4",   # Tissue Specificity
		}
		
		if flag in flags :
			return flags[flag]
			
		print "Couldn't Map: " + str(flag)
		return ""
		
	def convertForcedOrganismID( self, organismID ) :
	
		"""Convert the Forced Organism ID into a Correct One Using Up to Date Annotation"""
	
		organismID = str(organismID).lower( )
		
		orgIDs = {
			"4896" 		: "284812", # Schizosaccharomyces pombe
			"5141" 		: "367110", # Neurospora crassa
			"5270" 		: "237631", # Ustilago maydis
			"5664" 		: "347515", # Leishmania major
			"435895"	: "37296", 	# Human Herpes 8
			"57667" 	: "11723", 	# SIV
			"0" 		: "9606" 	# Map to Human 
		}
		
		if organismID in orgIDs :
			return orgIDs[organismID]
			
		return organismID