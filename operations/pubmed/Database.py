
# Basic Database Connection Setup

import MySQLdb
import MySQLdb.cursors
import Config

db = MySQLdb.connect( Config.DB_HOST, Config.DB_USER, Config.DB_PASS, Config.DB_IMS, cursorclass=MySQLdb.cursors.DictCursor, use_unicode=True, charset="utf8" )