#!/bin/sh -e
lessc=/usr/bin/lessc
lessDir=less/
output=../www/css/ims.min.css

nice -n 9 $lessc -x $lessDir/ims-build.less > $output