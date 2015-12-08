#!/bin/env python

# This script generate the count of children for each ontology term

import sys, string
import Config
import Database
import argparse
import json

from classes import Children

# Process Command Line Input
argParser = argparse.ArgumentParser( description = 'Update the OBO Ontologies with Latest Terms' )
argParser.add_argument( '--verbose', '-v', action='store_true', help='Display output while the process is running' )
argParser.add_argument( '--ontology', '-o', action='store', nargs='*', help='The IDs of which ontologies to wpdate. Pass no ids to process all ontologies.' )
inputArgs = argParser.parse_args( )

with Database.db as cursor :

	children = Children.Children( Database.db, cursor )

	query = "SELECT ontology_id, ontology_name FROM " + Config.DB_IMS + ".ontologies WHERE ontology_status='active'"
	if None != inputArgs.ontology :
		query = query + " AND ontology_id IN ('" + "','".join(inputArgs.ontology) + "')"
	
	cursor.execute( query )
	
	for ontRow in cursor.fetchall( ) :
	
		if inputArgs.verbose :
			print "Counting Children => " + ontRow['ontology_name']
	
		cursor.execute( "SELECT ontology_term_id, ontology_term_name FROM " + Config.DB_IMS + ".ontology_terms WHERE ontology_term_status = 'active' AND ontology_id=%s", [ontRow['ontology_id']] )
		
		for termRow in cursor.fetchall( ) :
			
			childrenSet = children.fetchChildren( termRow['ontology_term_id'] )
			childCount = len(set(childrenSet))
			
			if inputArgs.verbose :
				print " ----> Term " + str(termRow['ontology_term_id']) + " : " + termRow['ontology_term_name'] + " : Found " + str(childCount) + " Children"
			
			cursor.execute( "UPDATE " + Config.DB_IMS + ".ontology_terms SET ontology_term_childcount=%s WHERE ontology_term_id=%s", [childCount, termRow['ontology_term_id']] )
		
		if inputArgs.verbose :
			print "---------------------------------------------------"
			
		Database.db.commit( )
	Database.db.commit( )
	
	
def fetchChildren( parentID ) :

	childSet = set( )
	
		
sys.exit(0)