<div id='curationWorkflow' class=''>

	{% for BLOCK in CURATION_BLOCKS %}
		<div class='curationPanel panel panel-primary' id='{{ BLOCK.id }}'>
			<div class='panel-heading'>{{ BLOCK.title }} <i class='fa fa-angle-down fa-lg pull-right'></i></div>
			<div class='panel-body'>
				{{ BLOCK.content|raw }}
				<div class='curationErrors'>{{ BLOCK.errors }}</div>
			</div>
		</div>
	{% endfor %}
	
</div>

<div id='curationRightSidebar' class='clearfix'>
	<div id='curationMenu'>
		<div class='list-group'>
		
			{% for LINK in SIDEBAR_LINKS %}
				<a href='{{ LINK.url }}' class='list-group-item {{ LINK.class }}'>
					{{ LINK.title }}
					
					{% if LINK.icon %}
						<i class='fa fa-{{ LINK.icon }} fa-lg pull-right'></i>
					{% endif %}
				</a>
			{% endfor %}
		
		</div>
	</div>
</div>