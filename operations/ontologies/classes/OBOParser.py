import sys, string
import Config
import urllib, urllib2
import re

class OBOParser( ) :

	"""Tools for parsing of OBO formatted files"""

	def __init__( self ) :
		self.oboData = { }
		self.parsingTerm = False
		self.currentTerm = { }
		self.quoteRE = re.compile( "\"(.*)\"\s?(.*)" )
		
	def parse( self, oboFile ) :
	
		"""Read the contents of an OBO into a structured array"""
		
		for line in urllib2.urlopen( oboFile ) :
			line = line.strip( )
			
			if len(line) <= 0 :
				continue
			
			if "[term]" == line.lower( ) :
				
				# Add term to oboData and reset
				# current Term
				if len(self.currentTerm) > 0 :
					self.commitCurrentTerm( )
					
				self.parsingTerm = True
				continue
				
			elif "[" == line[:1] and "]" == line[-1:] :
				
				# Add term to oboData and reset
				# current Term
				if len(self.currentTerm) > 0 :
					self.commitCurrentTerm( )
					
				self.parsingTerm = False
				
			# If anything other than a Term
			# we are not interested
			if not self.parsingTerm :
				continue
				
			self.processTag( line )
			
		if len(self.currentTerm) > 0 :
			self.commitCurrentTerm( )
			
		return self.oboData
			
	def commitCurrentTerm( self ) :
		
		"""Take data currently stored in self.currentTerm and commit it to the oboData"""
		
		# Get the ID
		if "id" in self.currentTerm :
			termID = self.currentTerm['id'][0]
				
			if termID not in self.oboData :
				self.oboData[termID] = self.currentTerm.copy( )
		
		self.currentTerm = { }
		
	def processTag( self, line ) :
		
		"""Convert tag into two parts and store inside the current term dict"""
		
		# Split up the line into components
		tag, term, comment = self.splitLine( line )
		
		if tag.lower( ) not in self.currentTerm :
			self.currentTerm[tag.lower( )] = []
			
		self.currentTerm[tag.lower( )].append( term )
		
	def splitLine( self, line ) :
	
		"""Split a line into components such as comment, term, tag, and garbage"""
		
		line = line.strip( )
		colonLoc = line.find( ":" )
		
		tag = line[:colonLoc].strip( )
		term = line[colonLoc+1:].strip( )
		
		comment = ""
		splitTerm = term.split( "!" )
		if len(splitTerm) > 1 :
			term = splitTerm[0].strip( )
			comment = splitTerm[1].strip( )
			
		matches = self.quoteRE.match( term )
		if matches : 
			term = matches.group(1).strip( )
			
		if "relationship" == tag.lower( ) :
			spaceLoc = term.rfind( " " )
			term = term[:spaceLoc] + "|" + term[spaceLoc+1:]
			
		return tag.strip( ).lower( ), term.strip( ), comment.strip( )