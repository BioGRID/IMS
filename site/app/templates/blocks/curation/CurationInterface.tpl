<div id='curationWorkflow' class=''>

	{% for BLOCK in CURATION_BLOCKS %}
		<div class='curationPanel panel panel-primary' id='{{ BLOCK.id }}'>
			<div class='panel-heading'>{{ BLOCK.title }} <i class='fa fa-angle-down fa-lg pull-right'></i></div>
			<div class='panel-body'>
			
				<div class='row'>
					<div class='col-lg-12'>
						{{ BLOCK.content|raw }}
					</div>
				</div>
				
				<div class='row'>
					<div class='curationErrors marginTopSm col-lg-12'>
						<textarea class="form-control" id='{{BLOCK.id}}-errors' rows="5">{{ BLOCK.errors|raw }}</textarea>
					</div>
				</div>
				
			</div>
		</div>
	{% endfor %}
	
</div>

<div id='curationRightSidebar' class='clearfix'>
	<div id='curationMenu'>
		<h4>Curation Checklist</h4>
		<ul>
		
			{% for LINK in SIDEBAR_LINKS %}
			
				<li>
					<i class='fa fa-angle-right listIcon'></i> 
					<i class='fa fa-square-o pull-right fa-lg activityIcon'></i><a data-blockid='{{ LINK.id }}' class='{{ LINK.class }} workflowLink' data-block='{{ LINK.block }}'
						{% for NAME, VALUE in LINK.data %}
							data-{{ NAME }}='{{ VALUE }}' 
						{% endfor %}
					>{{ LINK.title }}</a>
					
					<ul class='curationSubmenu'>
						{% for SUBMENU in LINK.submenu %}
							<li class='{{ SUBMENU.class }} curationSubmenuItem'><i class='fa fa-angle-double-right'></i> {{ SUBMENU.value|raw}}</li>
						{% endfor %}
						<li><i class='fa fa-angle-double-right'></i> <a class='addSubAttribute'>Add Sub-Attribute <i class='fa fa-plus-square-o'></i></a></li>
					</ul>
					
				</li>
			{% endfor %}
		
		</ul>
		<button class='btn btn-success' disabled>Submit <i class='fa fa-check fa-lg'></i></button>
	</div>
</div>