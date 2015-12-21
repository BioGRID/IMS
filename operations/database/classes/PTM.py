import sys, string
import Config
import datetime
import re

from classes import Maps, Lookups, DBProcessor

class PTM( ) :

	"""Tools for Handling the Migration of PTM Data from IMS 2 to IMS 4"""
	
	def __init__( self, db, cursor ) :
		self.db = db
		self.cursor = cursor
		self.maps = Maps.Maps( )
		self.lookups = Lookups.Lookups( db, cursor )
		self.dbProcessor = DBProcessor.DBProcessor( db, cursor )
		self.quoteWrap = re.compile( '^[\'\"](.*)[\"\']$' )
		
		# Build Quick Reference Data Structures
		self.validDatasets = self.lookups.buildValidDatasetSet( )
		self.modHash = self.lookups.buildModificationHash( )
		self.sourceNameHash = self.lookups.buildSourceNameHash( )
		self.ptmIdentHash = self.lookups.buildPTMIdentityHash( )
		
		# A set of newly mapped 
		self.ptm2interaction = { }
		self.ptm2orphanInteraction = { }
		self.usedRelationships = set( )
		
	def migratePTMs( self ) :
		
		"""
		Copy Operation 
		     -> IMS2: ptms to 
			 -> IMS4: interactions
		"""
		
		intCount = 0
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".ptms ORDER BY ptm_id ASC" )
		for row in self.cursor.fetchall( ) :
			if str(row['publication_id']) in self.validDatasets :
				
				intCount += 1
				self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".interactions VALUES( %s, %s, %s, %s )", ['0', row['publication_id'], "3", "normal"] )
				interactionID = str(self.cursor.lastrowid)
				
				self.ptm2interaction[str(row['ptm_id'])] = interactionID

				userID = "1"
				addedDate = row['ptm_addeddate']
				status = row['ptm_status']
				
				# History
				self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".history VALUES( '0', 'ACTIVATED', %s, %s, 'New PTM', '1', %s )", [interactionID, userID, addedDate] )
				if status.upper( ) == "INACTIVE" :
					self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".history VALUES( '0', 'DISABLED', %s, %s, 'No Longer Valid PTM', '7', %s )", [interactionID, userID, addedDate] )
					
				# Residue Location
				# No residue location is added for unassigned PTMs
				location = str(row['ptm_residue_location'])
				if location != "0" :
					self.dbProcessor.processInteractionAttribute( interactionID, location, "24", addedDate, "0", userID, "active" )
					
				# Remap Modification ID into Attributes Entry
				modificationID = self.modHash[str(row['modification_id'])]
				self.dbProcessor.processInteractionAttribute( interactionID, modificationID, "12", addedDate, "0", userID, "active" )
				
				# Source
				ptmSourceName = self.maps.convertPTMSources( str(row['ptm_source_id']) )
				sourceID = self.sourceNameHash[ptmSourceName.lower( )]
				self.dbProcessor.processInteractionAttribute( interactionID, sourceID, "14", addedDate, "0", userID, "active" )
				
				# Substrate Participant
				self.dbProcessor.processParticipant( row['refseq_protein_id'], interactionID, "12", "3", addedDate )
				
				# Process Enzymes
				self.processEnzymes( row['ptm_id'], interactionID, row['publication_id'] )

				if (intCount % 10000) == 0 :
					self.db.commit( )
					
		self.db.commit( )
		
	def processEnzymes( self, ptmID, interactionID, pubID ) :
	
		"""Insert matching enzymes to match PTM"""
		
		processedGenes = set( )
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".ptm_relationships WHERE ptm_id=%s AND publication_id=%s", [ptmID, pubID] )
		for row in self.cursor.fetchall( ) :
			if str(row['gene_id']) not in processedGenes :
				intParticipantID = self.dbProcessor.processParticipant( row['gene_id'], interactionID, "11", "1", row['ptm_relationship_addeddate'] )
				processedGenes.add( str(row['gene_id']) )
				self.usedRelationships.add( str(row['ptm_relationship_id']) )
				
				if str(row['relationship_identity'].lower( )) in self.ptmIdentHash :
					self.dbProcessor.processInteractionParticipantAttribute( intParticipantID, self.ptmIdentHash[str(row['relationship_identity'].lower( ))], "29", row['ptm_relationship_addeddate'], "0", "1", "active" )
						
	def migratePTMOrphanRelationships( self ) :
		
		"""
		Copy Operation 
		     -> IMS2: ptm_relationships to 
			 -> IMS4: interactions
		"""
		
		intCount = 0
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".ptm_relationships ORDER BY ptm_relationship_id ASC" )
		for row in self.cursor.fetchall( ) :
			if str(row['ptm_relationship_id']) not in self.usedRelationships :
				if str(row['publication_id']) in self.validDatasets :
				
					intCount += 1
					self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".interactions VALUES( %s, %s, %s, %s )", ['0', row['publication_id'], "3", "normal"] )
					interactionID = str(self.cursor.lastrowid)
					
					self.ptm2orphanInteraction[str(row['ptm_id'])] = interactionID
					ptmInfo = self.fetchPTM( row['ptm_id'] )

					userID = "1"
					addedDate = ptmInfo['ptm_addeddate']
					status = ptmInfo['ptm_status']
					
					# History
					self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".history VALUES( '0', 'ACTIVATED', %s, %s, 'New PTM', '1', %s )", [interactionID, userID, addedDate] )
					if status.upper( ) == "INACTIVE" :
						self.cursor.execute( "INSERT INTO " + Config.DB_IMS + ".history VALUES( '0', 'DISABLED', %s, %s, 'No Longer Valid PTM', '7', %s )", [interactionID, userID, addedDate] )
						
					# Residue Location
					# No residue location is added for unassigned PTMs
					location = str(ptmInfo['ptm_residue_location'])
					if location != "0" :
						self.dbProcessor.processInteractionAttribute( interactionID, location, "24", addedDate, "0", userID, "active" )
						
					# Remap Modification ID into Attributes Entry
					modificationID = self.modHash[str(ptmInfo['modification_id'])]
					self.dbProcessor.processInteractionAttribute( interactionID, modificationID, "12", addedDate, "0", userID, "active" )
					
					# Source
					ptmSourceName = self.maps.convertPTMSources( str(ptmInfo['ptm_source_id']) )
					sourceID = self.sourceNameHash[ptmSourceName.lower( )]
					self.dbProcessor.processInteractionAttribute( interactionID, sourceID, "14", addedDate, "0", userID, "active" )
					
					# Substrate Participant
					self.dbProcessor.processParticipant( ptmInfo['refseq_protein_id'], interactionID, "12", "3", addedDate )
					
					# Process Enzymes
					self.processEnzymes( ptmInfo['ptm_id'], interactionID, row['publication_id'] )

					if (intCount % 10000) == 0 :
						self.db.commit( )
					
		self.db.commit( )
						
	def fetchPTM( self, ptmID ) :
		
		"""Grab PTM info from the database by PTM ID"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".ptms WHERE ptm_id=%s LIMIT 1", [ptmID] )
		return self.cursor.fetchone( )
		
	def migratePTMNotes( self ) :
	
		"""
		Copy Operation
			-> IMS2: ptm_notes
			-> IMS4: attributes and interaction_attributes
		"""
		
		self.cursor.execute( "SELECT * FROM " + Config.DB_IMS_OLD + ".ptm_notes" )
		
		qualCount = 0
		for row in self.cursor.fetchall( ) :
			
			if str(row['ptm_id']) in self.ptm2interaction :
				interactionID = self.ptm2interaction[str(row['ptm_id'])]
			
				qualCount += 1
				
				qualification = row['ptm_note'].strip( "\\" ).decode( 'string_escape' ).strip( )
				
				matchSet = self.quoteWrap.match( qualification )
				if matchSet :
					qualification = matchSet.group(1)
					
				if len(qualification) > 0 :
					self.dbProcessor.processInteractionAttribute( interactionID, qualification, "22", row['ptm_note_addeddate'], "0", "1", row['ptm_note_status'] )
					
					if str(row['ptm_id']) in self.ptm2orphanInteraction :
						interactionID = self.ptm2orphanInteraction[str(row['ptm_id'])]
						self.dbProcessor.processInteractionAttribute( interactionID, qualification, "22", row['ptm_note_addeddate'], "0", "1", row['ptm_note_status'] )
					
				if (qualCount % 10000) == 0 :
					self.db.commit( )
				
		self.db.commit( )