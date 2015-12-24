#!/bin/env python
# -*- coding: utf-8 -*-

# Check invalid Pubmeds and Load them into the Ignore Publications Table

import sys, string
import Config
import Database

with Database.db as cursor :

	cursor.execute( "TRUNCATE TABLE " + Config.DB_IMS_TRANSITION + ".ignore_publications" )

	cursor.execute( "SELECT pubmed_id FROM " + Config.DB_IMS_TRANSITION + ".pubmed WHERE pubmed_isannotated='3'" )
	rowCount = 0
	for row in cursor.fetchall( ) :
	
		rowCount += 1
	
		cursor.execute( "SELECT publication_id FROM  " + Config.DB_IMS_OLD + ".publications WHERE publication_pubmed_id=%s LIMIT 1", [row['pubmed_id']] )
		pubRow = cursor.fetchone( )
		
		cursor.execute( "SELECT count(*) as intCount FROM  " + Config.DB_IMS_OLD + ".interaction_matrix WHERE publication_id=%s AND modification_type='ACTIVATED'", [pubRow['publication_id']] )
		intRow = cursor.fetchone( )
		
		if intRow['intCount'] > 0 :
			print str(rowCount) + " => " + str(pubRow['publication_id']) + " => " + str(intRow['intCount'])
		else :
			cursor.execute( "INSERT INTO  " + Config.DB_IMS_TRANSITION + ".ignore_publications VALUES ( %s )", [str(pubRow['publication_id'])] )

sys.exit( )