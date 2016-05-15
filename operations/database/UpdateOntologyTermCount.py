#!/bin/env python

# Update the ontology term count column in ontology_terms
# to store the number of times that term has occurred in 
# the attributes table

import sys, string
import Config
import Database

with Database.db as cursor :

	attributeList = []
	cursor.execute( "SELECT attribute_id, attribute_value FROM " + Config.DB_IMS + ".attributes WHERE attribute_type_id IN ( SELECT attribute_type_id FROM " + Config.DB_IMS + ".attribute_types WHERE attribute_type_category_id='1' AND attribute_type_status='active' )" )
	for row in cursor.fetchall( ) :
		attributeList.append( { "id" : row['attribute_id'], "value" : row['attribute_value'] } )
		
	print "Found " + str(len(attributeList)) + " attributes that are mapped"
		
	for attribute in attributeList :

		id = attribute['id']
		value = attribute['value']

		cursor.execute( "SELECT count(*) as attCount FROM " + Config.DB_IMS + ".interaction_attributes WHERE attribute_id=%s AND interaction_attribute_status='active'", [id] )
		
		row = cursor.fetchone( )
		
		cursor.execute( "UPDATE " + Config.DB_IMS + ".ontology_terms SET ontology_term_count=%s WHERE ontology_term_id=%s", [row['attCount'], value] )
		Database.db.commit( )
		
	Database.db.commit( )
print "FINISHED"
	
sys.exit(0)