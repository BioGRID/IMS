{% for ERROR in ERRORS %}
	<div class='alert alert-{{ ERROR.class }} marginTopNone marginBotSm' role='alert'>{{ ERROR.message | raw }}</div>
{% endfor %}