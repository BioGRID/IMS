<div class='curationBlock' id='{{ ID }}' data-type='{{ TYPE }}' data-name='{{ TITLE }}'>

	{% include 'curation/blocks/Panel.tpl' %}
	
	<div class='curationErrors marginTopSm col-lg-12'>
		<h3>Errors</h3>
		<div class="curationErrorList">{{ ERRORS|raw }}</div>
	</div>
	
</div>