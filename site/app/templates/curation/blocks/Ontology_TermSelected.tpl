<div class='ontologySelectedTerm'>
	<div class='pull-right'><i class='fa fa-close fa-lg text-danger ontologyRemoveSelectedTerm'></i></div>
	<div class='checkboxLabel'><input class='ontologySelectedCheck' type='checkbox' value='{{TERM_ID}}' checked /> <strong>{{TERM_NAME}}</strong> ({{TERM_OFFICIAL}})</label>
	<div class='ontologySelectedQualifiers'>
		{% if QUALIFIER_MSG %}
			<div class='ontologyTermQualifierWarning'>{{QUALIFIER_MSG | raw}}</div>
		{% endif %}
	</div>
</div>