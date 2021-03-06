# Tools for Processing Pubmed Articles from XML

import sys, string
import Config
import time
import xml.etree.ElementTree as ET
import json

from datetime import datetime
from classes import TextProcessor

class PubmedArticle( ) :

	"""Parse out the details for a Pubmed Article"""
	
	def __init__( self ) :
		self.textProcessor = TextProcessor.TextProcessor( )
		self.pubYearVal = ""
		self.reset( )
		
	def reset( self ) :
	
		"""Reset base storage to clean default data"""
	
		self.pubInfo = { }
		self.pubInfo['PUBMED_ID'] = "-"
		self.pubInfo['TITLE'] = "-"
		self.pubInfo['ABSTRACT'] = "-"
		self.pubInfo['FULLTEXT'] = "-"
		self.pubInfo['AUTHOR_SHORT'] = "-"
		self.pubInfo['VOLUME'] = "-"
		self.pubInfo['ISSUE'] = "-"
		self.pubInfo['PUBDATE'] = "0000-00-00"
		self.pubInfo['JOURNAL'] = "-"
		self.pubInfo['JOURNAL_SHORT'] = "-"
		self.pubInfo['PAGINATION'] = "-"
		self.pubInfo['PMCID'] = "-"
		self.pubInfo['DOI'] = "-"
		self.pubInfo['ARTICLE_IDS'] = "-"
		self.pubInfo['AFFILIATIONS'] = []
		self.pubInfo['STATUS'] = "active"
		
	def parse( self, article ) :
		
		"""Parse out the pieces we need from the Article"""
		
		self.reset( )
		
		self.parsePubmedID( article )
		self.parseTitle( article )
		self.parseAbstract( article )
		self.parseVolume( article )
		self.parseIssue( article )
		self.parseJournal( article )
		self.parsePagination( article )
		self.parseArticleIDs( article )
		self.parsePubDate( article )
		self.parseAuthors( article )
		self.parseMeshTerms( article )
		self.parseCommentCorrectionsList( article )
		
		return self.pubInfo
		
	def parsePubmedID( self, article ) :
		
		"""Grab the Pubmed ID from the XML"""
		
		pubmedID = article.find( 'MedlineCitation/PMID' )
		if None != pubmedID :
			self.pubInfo['PUBMED_ID'] = pubmedID.text.strip( )
			
	def parseTitle( self, article ) :
		
		"""Grab the Title from the XML"""
		
		articleTitle = article.find( 'MedlineCitation/Article/ArticleTitle' )
		if None != articleTitle :
			self.pubInfo['TITLE'] = articleTitle.text.strip( )
			
	def parseAbstract( self, article ) :
	
		"""Grab the Abstract if Available"""
				
		# NEED TO DO ABSTRACTS AS AN ARRAY BECAUSE SOME HAVE MULTIPLES BROKEN DOWN
		# EXAMPLE: PUBMED 18829411
				
		abstracts = []
		for abstract in article.findall( 'MedlineCitation/Article/Abstract/AbstractText' ) :
			
			label = ""
			if "Label" in abstract.attrib :
				label = abstract.attrib["Label"] + ": "
			
			abstractText = abstract.text
			if None != abstractText :
				abstracts.append( label + abstractText.strip( ) )
			
		if len(abstracts) > 0 :
			self.pubInfo['ABSTRACT'] = "|".join(abstracts)
			
	def parseVolume( self, article ) :
	
		"""Parse out the Article Volume Details, May not be Exclusively Numeric"""
				
		articleVolume = article.find( 'MedlineCitation/Article/Journal/JournalIssue/Volume' )
		if None != articleVolume :
			self.pubInfo['VOLUME'] = articleVolume.text.strip( )
			
	def parseIssue( self, article ) :
	
		"""Parse out the Article Issue Details, May not be Exclusively Numeric"""
		
		# Need to check if text is also None because sometimes the tags are in the
		# XML structure, but it's just an empty string of text
				
		articleIssue = article.find( 'MedlineCitation/Article/Journal/JournalIssue/Issue' )
		if None != articleIssue :
			if None != articleIssue.text :
				self.pubInfo['ISSUE'] = articleIssue.text.strip( )
				
				
	def parseJournal( self, article ) :
	
		"""Parse out the Article Journal Details"""
		
		# NEEDED TO MAKE JOURNAL A TEXT FIELD CAUSE VARCHAR WAS TOO SHORT
		# EXAMPLE: PUBMED 18475251
				
		articleJournal = article.find( 'MedlineCitation/Article/Journal/Title' )
		if None != articleJournal :
			self.pubInfo['JOURNAL'] = articleJournal.text.strip( )
			
		articleJournalAbbr = article.find( 'MedlineCitation/Article/Journal/ISOAbbreviation' )
		if None != articleJournalAbbr :
			self.pubInfo['JOURNAL_SHORT'] = articleJournalAbbr.text.strip( )
			
	def parsePagination( self, article ) :
		
		"""Parse out the Pagination Details, May not be Exclusively Numeric"""
				
		# COULD EXIST BUT BE EMPTY
		# EXAMPLE: PUBMED 19229185
		
		articlePagination = article.find( 'MedlineCitation/Article/Pagination/MedlinePgn' )
		if None != articlePagination :
			paginationText = articlePagination.text
			if None != paginationText :
				self.pubInfo['PAGINATION'] = paginationText.strip( )
				
	def parseArticleIDs( self, article ) :
	
		"""Parse out all Article IDs if available and specifically isolate the DOI and PMC ids"""
				
		articleIDs = { }
		for articleID in article.findall( 'PubmedData/ArticleIdList/ArticleId' ) :
			if None != articleID :
				if articleID.attrib["IdType"] == "pmc" :
					self.pubInfo['PMCID'] = articleID.text.strip( )
				elif articleID.attrib["IdType"] == "doi" :
					self.pubInfo['DOI'] = articleID.text.strip( )
					
				type = articleID.attrib["IdType"]
				if type.upper( ) != "PUBMED" :
					if type.upper( ) not in articleIDs :
						articleIDs[type.upper( )] = []
						
					articleIDs[type.upper( )].append( articleID.text.strip( ) )
		
		if len(articleIDs) > 0 :
			self.pubInfo['ARTICLE_IDS'] = json.dumps( articleIDs, ensure_ascii=False ).encode( 'utf8' )
			
	
	def parsePubDate( self, article ) :
				
		"""Parse out the publication date"""
		
		# CONVERT DATE INTO MYSQL FRIENDLY FORMAT DATE
				
		articleDate = []
		pubYear = article.find( 'MedlineCitation/Article/Journal/JournalIssue/PubDate/Year' )
		
		if None != pubYear :
			articleDate.append( pubYear.text.strip( ) )
			self.pubYearVal = pubYear.text.strip( )
		else :
			articleDate.append( '1970' )
			self.pubYearVal = "1970"
		
		pubMonth = article.find( 'MedlineCitation/Article/Journal/JournalIssue/PubDate/Month' )
		if None != pubMonth :
		
			# OCCASSIONALLY A MONTH IS NOT JAN,FEB, etc. IT
			# IS 1,2,3 etc. SWAP TO STAY CONSISTENT FOR FORMATTING
			
			month = self.textProcessor.monthSwapper( pubMonth.text.strip( ) )
			articleDate.append( month )
		else :
			articleDate.append( 'Jan' )
		
		pubDay = article.find( 'MedlineCitation/Article/Journal/JournalIssue/PubDate/Day' )
		if None != pubDay :
			articleDate.append( pubDay.text.strip( ) )
		else :
			articleDate.append( '1' )
		
		articleDate = " ".join( articleDate )
		articleDate = datetime.strptime( articleDate, '%Y %b %d' )
		self.pubInfo['PUBDATE'] = articleDate.strftime( '%Y-%m-%d' )
		
	def parseAuthors( self, article ) :
				
		"""Process all authors and build several variables based on the output"""
		
		self.pubInfo['AUTHORS'] = []
		for author in article.findall( 'MedlineCitation/Article/AuthorList/Author' ) :
		
			# Skip Authors with authorValid = "N" because
			# this means that there was a correction and this
			# name was replaced by another
			
			authorValid = "Y"
			if "ValidYN" in author.attrib :
				authorValid = author.attrib["ValidYN"].upper( ).strip( )
				
			if "Y" == authorValid :
		
				authorRecord = { }
				authorRecord['FIRSTNAME'] = "-"
				authorRecord['LASTNAME'] = "-"
				authorRecord['INITIALS'] = "-"
				authorRecord['AFFILIATION'] = "-"
				
				authorFirstname = author.find( 'ForeName' )
				if None != authorFirstname :
					authorRecord['FIRSTNAME'] = authorFirstname.text.strip( )
					
				authorLastname = author.find( 'LastName' )
				if None != authorLastname :
					authorRecord['LASTNAME'] = authorLastname.text.strip( )
					
				authorInitials = author.find( 'Initials' )
				if None != authorInitials :
					if None != authorInitials.text :
						authorRecord['INITIALS'] = authorInitials.text.strip( )
					else :
						authorInitials = author.find( 'Suffix' )
						if None != authorInitials :
							if None != authorInitials.text :
								authorRecord['INITIALS'] = authorInitials.text.strip( )
				
				if authorRecord['INITIALS'] == "-" :
					authorRecord['INITIALS'] = "UU"
	
				# Build Universal List of Affiliations as well as attach affiliation
				# to the author entry
		
				authorAffiliation = author.find( 'AffiliationInfo/Affiliation' )
				if None != authorAffiliation :
					authorRecord['AFFILIATION'] = authorAffiliation.text.strip( )
					self.pubInfo['AFFILIATIONS'].append( authorRecord['AFFILIATION'] )
			
				# SKIP COLLECTIVES
				if authorRecord['LASTNAME'] != "-" :
					self.pubInfo['AUTHORS'].append( authorRecord )
		
		# Build a short version of the Authors
		# and account for situations where no authors are found
		
		if len(self.pubInfo['AUTHORS']) > 0 :
			firstAuthor = self.pubInfo['AUTHORS'].pop(0)
			self.pubInfo['AUTHOR_SHORT'] = firstAuthor['LASTNAME'] + u" " + firstAuthor['INITIALS'] + u" (" + self.pubYearVal + u")"
			self.pubInfo['AUTHORS'] = [firstAuthor] + self.pubInfo['AUTHORS']
		else :
			self.pubInfo['AUTHOR_SHORT'] = "Unknown Authors " + " (" + self.pubYearVal + ")"
				
	def parseMeshTerms( self, article ) :
		
		"""Parse out Mesh Terms and Mesh Term Qualifiers"""
				
		self.pubInfo['MESH'] = []
		for meshtag in article.findall( 'MedlineCitation/MeshHeadingList/MeshHeading' ) :
		
			meshRecord = { }
			meshRecord['DESCRIPTOR'] = "-"
			meshRecord['ID'] = "-"
			meshRecord['MAJOR'] = "N"
			meshRecord['QUALIFIERS'] = []
			
			meshDescriptor = meshtag.find( 'DescriptorName' )
			if None != meshDescriptor :
				meshRecord['DESCRIPTOR'] = meshDescriptor.text
				meshRecord['ID'] = meshDescriptor.attrib["UI"]
				meshRecord['MAJOR'] = meshDescriptor.attrib["MajorTopicYN"]
				
				for meshQualifier in meshtag.findall( 'QualifierName' ) :
					
					meshQualifierRecord = { }
					meshQualifierRecord['NAME'] = meshQualifier.text
					meshQualifierRecord['ID'] = meshQualifier.attrib["UI"]
					meshQualifierRecord['MAJOR'] = meshQualifier.attrib["MajorTopicYN"]
					
					meshRecord['QUALIFIERS'].append( meshQualifierRecord )
				
			self.pubInfo['MESH'].append( meshRecord )
			
	def parseCommentCorrectionsList( self, article ) :
	
		"""Parse out whether the paper has corrections or is retracted""" 
		
		for correction in article.findall( 'MedlineCitation/CommentsCorrectionsList/CommentsCorrections' ) :
			refType = correction.attrib["RefType"].upper( )
					
			if "RETRACTIONIN" == refType :
				self.pubInfo['STATUS'] = "retracted"
			
			if "PARTIALRETRACTIONIN" == refType and self.pubInfo['STATUS'] != "retracted" :
				self.pubInfo['STATUS'] = "partialretracted"
			
			if "ERRATUMIN" == refType and self.pubInfo['STATUS'] != "retracted" and self.pubInfo['STATUS'] != "partialretracted" :
				self.pubInfo['STATUS'] = "erratum"