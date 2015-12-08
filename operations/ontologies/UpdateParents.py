#!/bin/env python

# This script will update the set of parents stored
# in json for each ontology term

import sys, string
import Config
import Database
import argparse
import json

from classes import OntologyHash

# Process Command Line Input
argParser = argparse.ArgumentParser( description = 'Update the OBO Ontologies with Latest Terms' )
argParser.add_argument( '--verbose', '-v', action='store_true', help='Display output while the process is running' )
argParser.add_argument( '--ontology', '-o', action='store', nargs='*', help='The IDs of which ontologies to wpdate. Pass no ids to process all ontologies.' )
inputArgs = argParser.parse_args( )

with Database.db as cursor :

	ontologyHash = OntologyHash.OntologyHash( Database.db, cursor )
	termDetails = ontologyHash.buildTermDetailHash( "" )

	query = "SELECT ontology_id, ontology_name FROM " + Config.DB_IMS + ".ontologies WHERE ontology_status='active'"
	if None != inputArgs.ontology :
		query = query + " AND ontology_id IN ('" + "','".join(inputArgs.ontology) + "')"
	
	cursor.execute( query )
	
	for ontRow in cursor.fetchall( ) :
	
		if inputArgs.verbose :
			print "Building Parents => " + ontRow['ontology_name']
			print "---------------------------------------------------"
	
		cursor.execute( "SELECT ontology_term_id, ontology_id FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_term_status = 'active' AND ontology_id=%s", [ontRow['ontology_id']] )
		
		for termRow in cursor.fetchall( ) :
			
			cursor.execute( "SELECT ontology_parent_id, ontology_relationship_type FROM " + Config.DB_IMS + ".ontology_relationships WHERE ontology_term_id=%s AND ontology_relationship_status='active'", [termRow['ontology_term_id']] )
			
			relationships = { }
			for relRow in cursor.fetchall( ) :
			
				if relRow['ontology_relationship_type'].lower( ) not in relationships :
					relationships[relRow['ontology_relationship_type'].lower( )] = []
				
				termInfo = termDetails[str(relRow['ontology_parent_id'])]
				relationships[relRow['ontology_relationship_type'].lower( )].append( termInfo.copy( ) )
				
			if len(relationships) > 0 :
				cursor.execute( "UPDATE " + Config.DB_IMS + ".ontology_terms SET ontology_term_parent=%s WHERE ontology_term_id=%s", [json.dumps( relationships ), termRow['ontology_term_id']] )
				
		Database.db.commit( )
	Database.db.commit( )
		
sys.exit(0)