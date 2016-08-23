# Elastic Search
Utilities for populating Elastic Search with quick lookup collections for fast searching and sorting of interaction data

Currently only for testing of ES

## Building Indexes
+ Go to config folder and create a file config.json similar to the config.json.example provided. Change the ES_HOST and ES_PORT parameters to match those of your elastic search server
+ Run: **composer install** - Installs vendor requirements for this software
+ Run: **php BuildDatasetIndex.php** - This will create an index of all datasets in your IMS database.