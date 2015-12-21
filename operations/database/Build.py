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
argParser.add_argument( '--clean', '-c', action='store_true', help='Perform clean operations on processes' )
argParser.add_argument( '--build', '-b', action='store_true', help='Perform build operations on processes' )
argGroup = argParser.add_mutually_exclusive_group( required=True )
argGroup.add_argument( '--operations', '-o', nargs='*', action='store', help='Comma separated list of build/clean operations to run' )
argGroup.add_argument( '--list', '-l', action='store_true', help='List all of the build options available for the build command' )
inputArgs = argParser.parse_args( )

validOptions = [ "interactions", "ontologies", "datasets", "attributes", "history", "participants", "complexes", "forced", "chemicals", "ptms", "groups", "all" ]

if inputArgs.list :
	print "Build Options: " + ", ".join( validOptions )
else :

	if inputArgs.clean :
		for operation in inputArgs.operations :
		
			operation = operation.lower( )
			if operation not in validOptions :
				continue
				
			with Database.db as cursor :
			
				sqlProcessor = SQL.SQL( Database.db, cursor, inputArgs.verbose )
				
				if inputArgs.verbose :
					print ""
					
				toClean = "clean_" + operation
				getattr(sqlProcessor, toClean)( )
				
	if inputArgs.build :
		for operation in inputArgs.operations :
		
			operation = operation.lower( )
			if operation not in validOptions :
				continue
				
			with Database.db as cursor :
			
				sqlProcessor = SQL.SQL( Database.db, cursor, inputArgs.verbose )
				
				if inputArgs.verbose :
					print ""
					
				toBuild = "build_" + operation
				getattr(sqlProcessor, toBuild)( )
	
sys.exit(0)