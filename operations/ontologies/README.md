# Ontologies
Operations tools for maintenance of ontology records.

## Tools
+ **python UpdateOntologies.py [ --verbose ] [ --ontology [id] [id] [id] ... [id] ]** - Downloads each of the ontologies in the **ontologies** table of the IMS database and parses in the terms and relationships specified. It will update existing terms, deprecated obsolete terms, and input new terms as required. If the --ontology parameter is not used, it will run all "active" ontologies at once.

+ **python UpdateParents.py [ --verbose ] [ --ontology [id] [id] [id] ... [id] ]** - Updates the set of parents for each term in the **ontology_terms** table of the IMS database. If the --ontology parameter is not used, it will run all "active" ontologies at once.

+ **python UpdateChildCount.py [ --verbose ] [ --ontology [id] [id] [id] ... [id] ]** - Updates the child count for each term in the **ontology_terms** table of the IMS database. If the --ontology parameter is not used, it will run all "active" ontologies at once.