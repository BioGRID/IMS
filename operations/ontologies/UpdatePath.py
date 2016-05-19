#!/bin/env python

# This script will update the parent path stored
# in json for each ontology term

import sys, string
import Config
import Database
import argparse
import json

from classes import OntologyHash, Parents

# Process Command Line Input
argParser = argparse.ArgumentParser( description = 'Update the OBO Ontologies with Latest Terms' )
argParser.add_argument( '--verbose', '-v', action='store_true', help='Display output while the process is running' )
argParser.add_argument( '--ontology', '-o', action='store', nargs='*', help='The IDs of which ontologies to wpdate. Pass no ids to process all ontologies.' )
inputArgs = argParser.parse_args( )

with Database.db as cursor :

	ontologyHash = OntologyHash.OntologyHash( Database.db, cursor )
	termDetails = ontologyHash.buildTermDetailHash( "" )
	parents = Parents.Parents( Database.db, cursor )

	query = "SELECT ontology_id, ontology_name FROM " + Config.DB_IMS + ".ontologies WHERE ontology_status='active'"
	if None != inputArgs.ontology :
		query = query + " AND ontology_id IN ('" + "','".join(inputArgs.ontology) + "')"
	
	cursor.execute( query )
	
	for ontRow in cursor.fetchall( ) :
	
		if inputArgs.verbose :
			print "Building Parent Path => " + ontRow['ontology_name']
			print "---------------------------------------------------"
	
		cursor.execute( "SELECT ontology_term_id, ontology_id FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_term_status = 'active' AND ontology_id=%s", [ontRow['ontology_id']] )
		
		#cursor.execute( "SELECT ontology_term_id, ontology_id FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_term_status = 'active' AND ontology_term_id=%s", [str(159312)] )
		
		# 159312
		
		termCount = 0
		for termRow in cursor.fetchall( ) :
			
			termCount = termCount + 1
			
			pathSet = []
			path = parents.fetchParentPath( termRow['ontology_term_id'], [], pathSet, True )
			
			annotatedPaths = []
			for path in pathSet :
				annotatedPath = []
				for milestone in path :
					termInfo = termDetails[str(milestone)]
					annotatedPath.append( termInfo )
				annotatedPaths.append( list(annotatedPath) )
				
			if inputArgs.verbose :
				print "Built Path for " + str(termRow['ontology_term_id']) + " | # of Paths: " + str(len(annotatedPaths))
				
			# for annotatedPath in annotatedPaths :
				# print annotatedPath
				# print "\n\n\n"
				
			cursor.execute( "UPDATE " + Config.DB_IMS + ".ontology_terms SET ontology_term_path=%s WHERE ontology_term_id=%s", [json.dumps( annotatedPaths ), termRow['ontology_term_id']] )
			
			if (termCount % 10000) == 0 :
				Database.db.commit( )
				
		Database.db.commit( )
	Database.db.commit( )
		
sys.exit(0)