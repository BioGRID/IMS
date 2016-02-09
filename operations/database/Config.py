
# Load the config file, and create variables
# that can be referenced within other files

import json
import os

BASE_DIR = os.path.dirname(os.path.realpath(__file__))

with open( BASE_DIR + "/../../config/config.json", "r" ) as configFile :
	data = configFile.read( )

data = json.loads( data )

# DATABASE VARS
DB_HOST = data['DB']['DB_HOST']
DB_USER = data['DB']['DB_USER']
DB_PASS = data['DB']['DB_PASS']
DB_QUICK = data['DB']['DB_QUICK']
DB_IMS = data['DB']['DB_IMS']

# MONGO VARS
MONGO_HOST = data['MONGO']['MONGO_HOST']
MONGO_PORT = data['MONGO']['MONGO_PORT']
MONGO_USER = data['MONGO']['MONGO_USER']
MONGO_PASS = data['MONGO']['MONGO_PASS']
MONGO_IMS = data['MONGO']['MONGO_IMS']

with open( BASE_DIR + "/config/config.json", "r" ) as configFile :
	data = configFile.read( )

data = json.loads( data )

# DATABASE CONVERSION
DB_IMS_OLD = data['DB']['DB_IMS_OLD']
DB_IMS_TRANSITION = data['DB']['DB_IMS_TRANSITION']