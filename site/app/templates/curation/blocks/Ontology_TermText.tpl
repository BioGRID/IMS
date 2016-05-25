<div class='popularOntologyTerm col-lg-12' data-termid='{{ TERM_DETAILS.ontology_term_id }}' data-termname='{{ TERM_DETAILS.ontology_term_name }}'>
	<div class='ontologyTermWrap clearfix'>
	
		<div class='ontologyTermButtons pull-right'>
			<button type='button' class='btn btn-success btn-sm ontologyTermButton ontologyTermButtonAdd' data-btntext='Add Term'><i class='fa fa-plus fa-lg'></i> <span class='btnText'></span></button>
			<button type='button' class='btn btn-primary btn-sm ontologyTermButton ontologyTermButtonQualifier' data-btntext='Add Qualifier'><i class='fa fa-clone fa-lg'></i> <span class='btnText'></span></button>
			
			{% if ALLOW_TREE %}
				<button type='button' class='btn btn-warning btn-sm ontologyTermButton ontologyTermButtonTree' data-btntext='View Tree'><i class='fa fa-tree fa-lg'></i> <span class='btnText'></span></button>
			{% endif %}
			
		</div>
		
		<div class='ontologyTermText'>
		
			{% if ALLOW_EXPAND %}
				{% if TERM_DETAILS.ontology_term_childcount > 0 %}
					<span class='ontologyTermFolder' data-termid='{{ TERM_DETAILS.ontology_term_id }}'><i class='ontologyTreeIcon fa fa-angle-double-right fa-lg'></i></span>
				{% else %}
					<span class='ontologyTermNoClick' data-termid='{{ TERM_DETAILS.ontology_term_id }}'><i class='ontologyTreeIcon fa fa-leaf text-success'></i></span>
				{% endif %}
			{% endif %}

			<a class='ontologyTermDetails' data-termid='{{ TERM_DETAILS.ontology_term_id }}'>
				{{ TERM_DETAILS.ontology_term_name }}
			</a>
			{% if TERM_DETAILS.ontology_term_childcount > 0 %}
				[<i class='fa fa-leaf text-success'></i> {{TERM_DETAILS.ontology_term_childcount}}]
			{% endif %}
			
		</div>
		
	</div>
	
	{% if ALLOW_EXPAND %}
		<div class='ontologyTermExpand' id='ontologyTermExpand-{{ TERM_DETAILS.ontology_term_id }}'>{{ ONTOLOGY_EXPAND }}</div>
	{% endif %}
	
</div>