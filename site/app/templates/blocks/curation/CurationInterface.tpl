<div id='curationWorkflow' class=''>

	{% for BLOCK in CURATION_BLOCKS %}
		<div class='col-lg-12 col-md-12'>
			<div class='curationPanel panel panel-primary' id='{{ BLOCK.id }}'>
				<div class='panel-heading'>{{ BLOCK.title }} <i class='fa fa-angle-down fa-lg pull-right'></i></div>
				<div class='panel-body'>
					{{ BLOCK.content|raw }}
					<div class='curationErrors'>{{ BLOCK.errors }}</div>
				</div>
			</div>
		</div>
	{% endfor %}
	
</div>

<div id='curationRightSidebar' class='clearfix'>
	<div id='curationMenu'>
		<h5>Curation Checklist</h5>
		<ul>
		
			{% for LINK in SIDEBAR_LINKS %}
				<li>
					<div><i class='fa fa-angle-right listIcon'></i> <i class='fa fa-square-o pull-right fa-lg activityIcon'></i><a class='{{ LINK.class }} workflowLink' data-type='{{ LINK.type }}' >{{ LINK.title }}</a></div>
				</li>
			{% endfor %}
		
		</ul>
		<button class='btn btn-success' disabled>Submit <i class='fa fa-check fa-lg'></i></button>
	</div>
</div>