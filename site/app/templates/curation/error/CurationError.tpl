{% for ERROR in ERRORS %}
	<div class='curationError alert alert-{{ ERROR.class }} marginTopNone marginBotSm' role='alert'>
		<a class='closeError pull-right marginLeftSm'><i class='fa fa-lg text-danger fa-close'></i></a>
		<i class='fa fa-lg {% if ERROR.class == "warning" %} fa-exclamation-triangle {% else %} fa-exclamation-circle {% endif %} text-{{ ERROR.class }}'></i> 
		<strong>{% if ERROR.class == "warning" %} WARNING {% else %} ERROR {% endif %}</strong> : 
		{{ ERROR.message | raw }}
	</div>
{% endfor %}