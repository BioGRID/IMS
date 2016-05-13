
{% for TERM_NAME, TERM_DETAILS in TERMS %}
	<div class='popularOntologyTerm col-lg-4 col-md-6 col-sm-12 col-xs-12' data-termid='{{ TERM_DETAILS.ontology_term_id }}' data-termname='{{ TERM_DETAILS.ontology_term_name }}'>
		<div class='ontologyTermWrap'>
			{{ TERM_DETAILS.ontology_term_name }}
		</div>
	</div>
{% endfor %}