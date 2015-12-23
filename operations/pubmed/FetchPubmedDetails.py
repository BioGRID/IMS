#!/bin/env python
# -*- coding: utf-8 -*-

# This script will parse out pubmed records needing annotation
# from the database, and update the fields as appropriate

import sys, string
import Config
import Database
import urllib, urllib2
import time
import xml.etree.ElementTree as ET

from datetime import datetime
from classes import TextProcessor, DatabaseProcessor

textProcessor = TextProcessor.TextProcessor( )

baseURL = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi"
params = { }
params['db'] = "pubmed"
params['retMax'] = 1000000
params['rettype'] = "text"
params['retmode'] = "xml"
params['email'] = Config.PUBMED_EMAIL
params['tool'] = Config.PUBMED_TOOL

attempts = { }
	
with Database.db as cursor :

	dbProcessor = DatabaseProcessor.DatabaseProcessor( Database.db, cursor )
	
	batchCount = 1
	while True :
	
		cursor.execute( "SELECT pubmed_id FROM " + Config.DB_IMS + ".pubmed WHERE pubmed_isannotated='0' ORDER BY pubmed_id ASC LIMIT 100" )
		
		if cursor.rowcount <= 0 :
			break
		else :
		
			print "Processing Batch " + str(batchCount)
			batchCount += 1
		
			pubmeds = []
			for row in cursor.fetchall( ) :
				pubmeds.append( str(row['pubmed_id']) )
				
				if str(row['pubmed_id']) not in attempts :
					attempts[str(row['pubmed_id'])] = 0
					
				attempts[str(row['pubmed_id'])] += 1
				if attempts > 3 :
					cursor.execute( "UPDATE " + Config.DB_IMS + ".pubmed SET pubmed_isannotated='3' WHERE pubmed_id=%s", [row['pubmed_id']] )
				
			#pubmeds.append( "9811642" )
			
			params['id'] = ",".join( pubmeds )
				
			data = urllib.urlencode( params )
			request = urllib2.Request( baseURL, data )
			response = urllib2.urlopen( request )
			fetchData = response.read( )
				
			content = ET.fromstring( fetchData )
			for article in content.findall( 'PubmedArticle' ) :
			
				pubInfo = { }
				pubInfo['PUBMED_ID'] = "-"
				pubInfo['TITLE'] = "-"
				pubInfo['ABSTRACT'] = "-"
				pubInfo['FULLTEXT'] = "-"
				pubInfo['AUTHOR_SHORT'] = "-"
				pubInfo['VOLUME'] = "-"
				pubInfo['ISSUE'] = "-"
				pubInfo['PUBDATE'] = "0000-00-00"
				pubInfo['JOURNAL'] = "-"
				pubInfo['JOURNAL_SHORT'] = "-"
				pubInfo['PAGINATION'] = "-"
				pubInfo['PMCID'] = "-"
				pubInfo['AFFILIATIONS'] = []
				
				pubmedID = article.find( 'MedlineCitation/PMID' )
				if None != pubmedID :
					pubInfo['PUBMED_ID'] = pubmedID.text.strip( )
			
				#--------------------------------------------
				# FETCH PUBLICATION MEDLINE DETAILS
			
				articleTitle = article.find( 'MedlineCitation/Article/ArticleTitle' )
				if None != articleTitle :
					pubInfo['TITLE'] = articleTitle.text.strip( )
				
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
					pubInfo['ABSTRACT'] = "|".join(abstracts)
					
				articleVolume = article.find( 'MedlineCitation/Article/Journal/JournalIssue/Volume' )
				if None != articleVolume :
					pubInfo['VOLUME'] = articleVolume.text.strip( )
				
				articleIssue = article.find( 'MedlineCitation/Article/Journal/JournalIssue/Issue' )
				if None != articleIssue :
					pubInfo['ISSUE'] = articleIssue.text.strip( )
				
				# NEEDED TO MAKE JOURNAL A TEXT FIELD CAUSE VARCHAR WAS TOO SHORT
				# EXAMPLE: PUBMED 18475251
				
				articleJournal = article.find( 'MedlineCitation/Article/Journal/Title' )
				if None != articleJournal :
					pubInfo['JOURNAL'] = articleJournal.text.strip( )
					
				articleJournalAbbr = article.find( 'MedlineCitation/Article/Journal/ISOAbbreviation' )
				if None != articleJournalAbbr :
					pubInfo['JOURNAL_SHORT'] = articleJournalAbbr.text.strip( )
				
				# COULD EXIST BUT BE EMPTY
				# EXAMPLE: PUBMED 19229185
				
				articlePagination = article.find( 'MedlineCitation/Article/Pagination/MedlinePgn' )
				if None != articlePagination :
					paginationText = articlePagination.text
					if None != paginationText :
						pubInfo['PAGINATION'] = paginationText.strip( )
				
				# GET PMCID FROM ONE
				for articleID in article.findall( 'PubmedData/ArticleIdList/ArticleId' ) :
					if None != articleID :
						if articleID.attrib["IdType"] == "pmc" :
							pubInfo['PMCID'] = articleID.text.strip( )
							break
				
				#--------------------------------------------
				# CONVERT DATE INTO MYSQL FORMAT DATE
				
				articleDate = []
				pubYear = article.find( 'MedlineCitation/Article/Journal/JournalIssue/PubDate/Year' )
				pubYearVal = ""
				if None != pubYear :
					articleDate.append( pubYear.text.strip( ) )
					pubYearVal = pubYear.text.strip( )
				else :
					articleDate.append( '1970' )
					pubYearVal = "1970"
				
				pubMonth = article.find( 'MedlineCitation/Article/Journal/JournalIssue/PubDate/Month' )
				if None != pubMonth :
					# OCCASSIONALLY A MONTH IS NOT JAN,FEB, etc. IT
					# IS 1,2,3 etc. SWAP TO STAY CONSISTENT FOR FORMATTING
					month = textProcessor.monthSwapper( pubMonth.text.strip( ) )
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
				pubInfo['PUBDATE'] = articleDate.strftime( '%Y-%m-%d' )
				
				#--------------------------------------------
				# PROCESS AUTHORS 
				
				pubInfo['AUTHORS'] = []
				for author in article.findall( 'MedlineCitation/Article/AuthorList/Author' ) :
				
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
						authorRecord['INITIALS'] = authorInitials.text.strip( )
						
					authorAffiliation = author.find( 'AffiliationInfo/Affiliation' )
					if None != authorAffiliation :
						authorRecord['AFFILIATION'] = authorAffiliation.text.strip( )
						pubInfo['AFFILIATIONS'].append( authorRecord['AFFILIATION'] )
					
					# SKIP COLLECTIVES
					if authorRecord['LASTNAME'] != "-" :
						pubInfo['AUTHORS'].append( authorRecord )
					
				if len(pubInfo['AUTHORS']) > 0 :
					firstAuthor = pubInfo['AUTHORS'].pop(0)
					pubInfo['AUTHOR_SHORT'] = firstAuthor['LASTNAME'] + u" " + firstAuthor['INITIALS'] + u" (" + pubYearVal + u")"
					pubInfo['AUTHORS'] = [firstAuthor] + pubInfo['AUTHORS']
				
				#--------------------------------------------
				# PROCESS MESH TERMS AND MESH TERM QUALIFIERS
				
				pubInfo['MESH'] = []
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
						
					pubInfo['MESH'].append( meshRecord )
					
				#-------------------------------------------
				# LOAD DATA INTO THE DATABASE
				
				dbProcessor.processPubmed( pubInfo )
				
				cursor.execute( "UPDATE " + Config.DB_IMS + ".pubmed SET pubmed_isannotated='1' WHERE pubmed_id=%s", [pubInfo['PUBMED_ID']] )
				attempts.pop( str(pubInfo['PUBMED_ID']), None )
				Database.db.commit( )
			
			for book in content.findall( 'PubmedBookArticle' ) :
				
				pubmedID = book.find( 'BookDocument/PMID' )
				if None != pubmedID :
					pubmedID = pubmedID.text.strip( )
					
				cursor.execute( "UPDATE " + Config.DB_IMS + ".pubmeds SET pubmed_isannotated='2' WHERE pubmed_id=%s", [pubmedID] )
				attempts.pop( str(pubmedID), None )
				Database.db.commit( )
				
			time.sleep(5)
			Database.db.commit( )
			
			# if (batchCount % 100) == 0 :
				# break
			
	Database.db.commit( )
		
sys.exit( )