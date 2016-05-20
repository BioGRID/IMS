<h5 class='marginBotSm'>{{COUNT}} {{TYPE}} Terms Displayed</h5>
{% for TERM_NAME, TERM_DETAILS in TERMS %}
	<div class='popularOntologyTerm col-lg-12' data-termid='{{ TERM_DETAILS.ontology_term_id }}' data-termname='{{ TERM_DETAILS.ontology_term_name }}'>
		<div class='ontologyTermWrap clearfix'>
			<div class='ontologyTermButtons pull-right'>
				<button type='button' class='btn btn-success btn-sm ontologyTermButton ontologyTermButtonAdd' data-btntext='Add Term'><i class='fa fa-plus fa-lg'></i> <span class='btnText'></span></button>
				<button type='button' class='btn btn-primary btn-sm ontologyTermButton ontologyTermButtonQualifier' data-btntext='Add Qualifier'><i class='fa fa-clone fa-lg'></i> <span class='btnText'></span></button>
			</div>
			<div class='ontologyTermText'>
				<a class='ontologyTermDetails' data-termid='{{ TERM_DETAILS.ontology_term_id }}'>
					{{ TERM_DETAILS.ontology_term_name }}
				</a>
				{% if TERM_DETAILS.ontology_term_childcount > 0 %}
					[<i class='fa fa-leaf text-success'></i> {{TERM_DETAILS.ontology_term_childcount}}]
				{% endif %}
			</div>
		</div>
	</div>
{% endfor %}