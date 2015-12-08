# Ontologies
Operations tools for maintenance of ontology records.

## Tools
+ **python UpdateOntologies.py [ --verbose ] [ --ontology <id> <id> <id> ... <id> ]** - Downloads each of the ontologies in the **ontologies** table of the IMS database and parses in the terms and relationships specified. It will update existing terms, deprecated obsolete terms, and input new terms as required. If the --ontology parameter is not used, it will run all "active" ontologies at once.