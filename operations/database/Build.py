#!/bin/env python

# This script will build the database for the IMS based
# on a custom set of submitted parameters, like a Makefile.

import sys, string
import Config
import Database
import argparse

from classes import SQL

# Process Command Line Input
argParser = argparse.ArgumentParser( description = 'Build the IMS Database' )
argParser.add_argument( '--verbose', '-v', action='store_true', help='Display output while the process is running' )
argParser.add_argument( '--clean', '-c', action='store_true', help='Perform the clean operations before the build operations' )
argGroup = argParser.add_mutually_exclusive_group( required=True )
argGroup.add_argument( '--build', '-b', nargs='*', action='store', help='Comma separated list of build processes to run' )
argGroup.add_argument( '--list', '-l', action='store_true', help='List all of the build options available for the build command' )
inputArgs = argParser.parse_args( )

validOptions = [ "interactions", "ontologies", "participants", "projects", "ptms", "datasets", "core", "all" ]

if inputArgs.list :
	print "Build Options: " + ", ".join( validOptions )
else :

	for buildOption in inputArgs.build :
		
		buildOption = buildOption.lower( )
		if buildOption not in validOptions :
			continue
	
		with Database.db as cursor :
			
			sqlProcessor = SQL.SQL( Database.db, cursor, inputArgs.verbose )
			
			if inputArgs.verbose :
				print ""
				
			if inputArgs.clean :
				toClean = "clean_" + buildOption
				getattr(sqlProcessor, toClean)( )
				
			toBuild = "build_" + buildOption
			getattr(sqlProcessor, toBuild)( )
	
sys.exit(0)