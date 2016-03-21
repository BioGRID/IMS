<div id='curationWorkflow' class=''></div>

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
		<button class='btn btn-sm btn-success' disabled>Submit <i class='fa fa-check fa-lg'></i></button>
		<button class='btn btn-sm btn-primary'>Add Item <i class='fa fa-plus-square-o fa-lg'></i></button>
	</div>
</div>