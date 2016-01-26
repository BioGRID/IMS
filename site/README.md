# BioGRID IMS Web Application
This directory contains the entirety of the BioGRID IMS Web Application. 

## System Requirements
To use all of the tools contained within, you require at least the following:

+ MySQL 5.5+ (https://www.mysql.com/)
+ PHP 5.5.9+ (http://www.php.net/)
+ NPM (https://www.npmjs.com/)
+ Composer (https://getcomposer.org/)
+ Lessc (to modify the look and feel of the site easily, directly in the .less files) (http://lesscss.org/)
+ A web server such as Nginx (https://www.nginx.com/) or Apache (https://httpd.apache.org/)

## Directories
+ **app** - The non-public facing tools, classes, and application components for the site. Should be inaccessible via your website URL.
+ **www** - The public facing resources for the site. This is the directory you will want available on the web.