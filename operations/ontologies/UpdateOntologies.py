#!/bin/env python

# This script will parse all the ontologies and update
# the terms in the IMS database.

import sys, string
import Config
import Database
import argparse

from classes import OBOParser, Ontologies

# Process Command Line Input
argParser = argparse.ArgumentParser( description = 'Update the OBO Ontologies with Latest Terms' )
argParser.add_argument( '--verbose', '-v', action='store_true', help='Display output while the process is running' )
argParser.add_argument( '--ontology', '-o', action='store', nargs='*', help='The IDs of which ontologies to wpdate. Pass no ids to process all ontologies.' )
inputArgs = argParser.parse_args( )

oboParser = OBOParser.OBOParser( )

with Database.db as cursor :

	query = "SELECT ontology_id, ontology_url, ontology_rootid, ontology_name FROM " + Config.DB_IMS + ".ontologies WHERE ontology_status='active'"
	if None != inputArgs.ontology :
		query = query + " AND ontology_id IN ('" + "','".join(inputArgs.ontology) + "')"
	
	cursor.execute( query )
	for row in cursor.fetchall( ) :
	
		ontologies = Ontologies.Ontologies( Database.db, cursor, row['ontology_id']	)
	
		if inputArgs.verbose :
			print "Parsing => " + row['ontology_name']
			print row['ontology_url']
			print "---------------------------------------------------"
			
		cursor.execute( "UPDATE " + Config.DB_IMS + ".ontology_terms SET ontology_term_status='inactive' WHERE ontology_term_isroot != '1' AND ontology_id = %s", [row['ontology_id']] )
		oboData = oboParser.parse( row['ontology_url'] )
		
		for (termOfficialID,termDetails) in oboData.items( ) :
			ontologies.processTerm( termOfficialID, termDetails, row['ontology_rootid'], row['ontology_id'] )
			
		cursor.execute( "UPDATE " + Config.DB_IMS + ".ontologies SET ontology_lastparsed=NOW( ) WHERE ontology_id=%s", [row['ontology_id']] )
			
		Database.db.commit( )
			
sys.exit(0)