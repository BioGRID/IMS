
{% for TERM_NAME, TERM_DETAILS in TERMS %}
	<div class='popularOntologyTerm col-lg-12' data-termid='{{ TERM_DETAILS.ontology_term_id }}' data-termname='{{ TERM_DETAILS.ontology_term_name }}'>
		<div class='ontologyTermWrap clearfix'>
			<div class='ontologyTermButtons pull-right'>
				<button type='button' class='btn btn-success btn-sm ontologyTermButton' data-btntext='Add Term'><i class='fa fa-plus fa-lg'></i> <span class='btnText'></span></button>
				<button type='button' class='btn btn-primary btn-sm ontologyTermButton' data-btntext='Add Qualifier'><i class='fa fa-clone fa-lg'></i> <span class='btnText'></span></button>
				<button type='button' class='btn btn-info btn-sm ontologyTermButton' data-btntext='More Details'><i class='fa fa-bars fa-lg'></i> <span class='btnText'></span></button>
			</div>
			<div class='ontologyTermText'>{{ TERM_DETAILS.ontology_term_name }}</div>
		</div>
	</div>
{% endfor %}