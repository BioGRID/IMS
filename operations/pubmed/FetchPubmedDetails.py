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
from classes import DatabaseProcessor, PubmedArticle, PubmedBook

pubmedArticle = PubmedArticle.PubmedArticle( )
pubmedBook = PubmedBook.PubmedBook( )

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
	
		cursor.execute( "SELECT pubmed_id FROM " + Config.DB_IMS + ".pubmed WHERE pubmed_isannotated='0' ORDER BY pubmed_id ASC LIMIT 500" )
		
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
				if attempts[str(row['pubmed_id'])] > 3 :
					cursor.execute( "UPDATE " + Config.DB_IMS + ".pubmed SET pubmed_isannotated='3' WHERE pubmed_id=%s", [row['pubmed_id']] )
			
			params['id'] = ",".join( pubmeds )
				
			data = urllib.urlencode( params )
			request = urllib2.Request( baseURL, data )
			response = urllib2.urlopen( request )
			fetchData = response.read( )
				
			content = ET.fromstring( fetchData )
			for article in content.findall( 'PubmedArticle' ) :
			
				pubInfo = pubmedArticle.parse( article )
				dbProcessor.processPubmedArticle( pubInfo )
				
				cursor.execute( "UPDATE " + Config.DB_IMS + ".pubmed SET pubmed_isannotated='1' WHERE pubmed_id=%s", [pubInfo['PUBMED_ID']] )
				attempts.pop( str(pubInfo['PUBMED_ID']), None )
				Database.db.commit( )
			
			for book in content.findall( 'PubmedBookArticle' ) :
				
				pubInfo = pubmedBook.parse( book )
				dbProcessor.processPubmedArticle( pubInfo )
					
				cursor.execute( "UPDATE " + Config.DB_IMS + ".pubmed SET pubmed_isannotated='1' WHERE pubmed_id=%s", [pubInfo['PUBMED_ID']] )
				attempts.pop( str(pubInfo['PUBMED_ID']), None )
				Database.db.commit( )
				
			time.sleep(5)
			Database.db.commit( )
			
			if (batchCount % 100) == 0 :
				break
			
	Database.db.commit( )
		
sys.exit( )