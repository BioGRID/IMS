{% for TERM_NAME, TERM_DETAILS in TERMS %}
	<div class='popularOntologyTerm' data-termid='{{ TERM_DETAILS.ontology_term_id }}' data-termname='{{ TERM_DETAILS.ontology_term_name }}'>
		{{ TERM_DETAILS.ontology_term_name }}
	</div>
{% endfor %}