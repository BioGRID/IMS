<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="description" content="{{META_DESC}}">
	<meta name="keywords" content="{{META_KEYWORDS}}">
	<meta name="author" content="Mike Tyers (TyersLab.com)">
	<meta name="copyright" content="Copyright &copy; {{YEAR}}, Mike Tyers (TyersLab.com), All Rights Reserved.">
	<meta name="application-name" content="BioGRID">
	<meta name="robots" content="INDEX,FOLLOW">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	
	{{CANONICAL|raw}}
	
	<!-- IMS Stylesheets -->
	<link rel="stylesheet" type="text/css" href="{{CSS_URL}}/ims.min.css" />
	<link rel="stylesheet" type="text/css" href="{{CSS_URL}}/font-awesome.min.css" />

	{% for STYLESHEET in ADDON_CSS %}
		<link rel="stylesheet" type="text/css" href="{{CSS_URL}}/{{STYLESHEET}}" />
	{% endfor %}
	
	<!-- IMS Favicon -->
	<link rel="shortcut icon" href="{{IMG_URL}}/favicon.ico">
	
	<title>{{TITLE}} ({{ABBR}})</title>
</head>
<body>