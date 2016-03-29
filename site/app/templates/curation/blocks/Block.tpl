<div class='curationBlock' id='{{ ID }}'>

	{% include 'curation/blocks/Panel.tpl' %}
	
	<div class='curationErrors marginTopSm col-lg-12'>
		<h3>Errors</h3>
		<textarea class="form-control" id='{{ID}}-errors' rows="5">{{ ERRORS|raw }}</textarea>
	</div>
	
</div>