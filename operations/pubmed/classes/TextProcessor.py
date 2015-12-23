# -*- coding: utf-8 -*-
# Text Processing Utilities for Repeated Use

import sys, string
import Config
import unicodedata, traceback

class TextProcessor( ) :

	"""Utilities for helpful processing of Text Data"""
	
	def stripAccents( self, inputStr ) :
	
		"""Strip Accent Characters from Strings"""
	
		# inputStr = unicode(inputStr)
		# inputStr = ''.join(c for c in unicodedata.normalize('NFD', inputStr) if unicodedata.category(c) != 'Mn')
		# inputStr = filter(lambda x: x in string.printable, inputStr)
		return inputStr
		
	def monthSwapper( self, month ) :
	
		"""Swap a Poorly Formatted Month to One we Can translate"""
	
		month = str(month)
		if len(month) == 1 :
			month = "0" + month
			
		monthList = { 
			"01" 	: "Jan",
			"02"	: "Feb",
			"03"	: "Mar",
			"04"	: "Apr",
			"05"	: "May",
			"06"	: "Jun",
			"07"	: "Jul",
			"08"	: "Aug",
			"09"	: "Sep",
			"10"	: "Oct",
			"11"	: "Nov",
			"12"	: "Dec"
		}
	
		if month in monthList :
			return monthList[month]
			
		return month