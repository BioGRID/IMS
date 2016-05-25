{% if SHOW_HEADING %}
	<h5 class='marginBotSm'>{{COUNT}} {{TYPE}} Terms Displayed</h5>
{% endif %}

{% for TERM_NAME, TERM_DETAILS in TERMS %}
	{% include 'curation/blocks/Ontology_TermText.tpl' %}
{% endfor %}