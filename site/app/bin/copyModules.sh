#!/bin/sh -e

# MAKE DIRECTORIES
mkdir -p ../www/css
mkdir -p ../www/fonts
mkdir -p ../www/js

# FONT AWESOME
cp -rf node_modules/font-awesome/css/font-awesome.min.css ../www/css/
cp -rf node_modules/font-awesome/fonts/* ../www/fonts/

# BOOTSTRAP
cp -rf node_modules/bootstrap/dist/fonts/* ../www/fonts/
cp -rf node_modules/bootstrap/dist/js/bootstrap.min.js ../www/js/

# JQUERY
cp -rf node_modules/jquery/dist/jquery.min.js ../www/js/

# DATATABLES
cp -rf node_modules/datatables.net/js/jquery.dataTables.js ../www/js/
cp -rf node_modules/datatables.net-bs/js/dataTables.bootstrap.js ../www/js/
cp -rf node_modules/datatables.net-bs/css/dataTables.bootstrap.css ../www/css/

# QTIP2
cp -rf node_modules/qtip2/dist/jquery.qtip.min.js ../www/js/
cp -rf node_modules/qtip2/dist/jquery.qtip.min.css ../www/css/