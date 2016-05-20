<div class='ontologyTermPopup'>
	<div class='ontologyTermName'><strong>{{ ontology_term_name }} ({{ ontology_term_official_id }})</strong></div>
	{% if ontology_term_desc != "-" %}
		<div class='ontologyTermDesc'>{{ ontology_term_desc }}</div>
	{% endif %}
	{% if ontology_term_synonyms != "-" %}
		<div class='ontologyTermSynonyms'><strong>Synonyms</strong> {{ ontology_term_synonyms }}</div>
	{% endif %}
	{% for REL_DETAILS in ontology_relations %}
		<div class='ontologyTermRelation'><strong>{{ REL_DETAILS.ontology_relationship_type }}</strong> {{ REL_DETAILS.ontology_term_name }} ({{ REL_DETAILS.ontology_term_official_id }})</div>
	{% endfor %}
</div>