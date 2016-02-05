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

# WEBUI-POPVER
cp -rf node_modules/webui-popover/dist/jquery.webui-popover.min.js ../www/js/
cp -rf node_modules/webui-popover/dist/jquery.webui-popover.min.css ../www/css/