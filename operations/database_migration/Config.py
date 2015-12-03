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