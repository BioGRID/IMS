<div class='curationBlock' id='{{ ID }}' data-type='{{ TYPE }}' data-name='{{ TITLE }}' data-required='{{ REQUIRED }}'>

	{% include 'curation/blocks/Panel.tpl' %}
	
	<div class='curationErrors marginTopSm col-lg-12'>
		<h3 class='marginBotSm'>Errors/Warnings</h3>
		<div class="curationErrorList">{{ ERRORS|raw }}</div>
	</div>
	
</div>