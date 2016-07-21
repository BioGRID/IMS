#!/bin/env python

# This script will build a test participant
# hash based on the input interaction id

import sys, string
import Config
import Database

from classes import Hashes

with Database.db as cursor :

	hashes = Hashes.Hashes( Database.db, cursor )
	print hashes.createParticipantHashByInteractionID( "441727" );
