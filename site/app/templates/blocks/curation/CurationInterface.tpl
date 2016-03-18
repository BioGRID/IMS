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
		
		$links[] = array( "block" => "participant", "options" => array( "role" => "2", "type" => "1", "organism" => "1" ));
				$links[] = array( "block" => "participant", "options" => array( "role" => "3", "type" => "1", "organism" => "1" ));
				$links[] = array( "block" => "attribute", "attribute_type_id" => "11", "options" => array( ));
				$links[] = array( "block" => "attribute", "attribute_type_id" => "13", "options" => array( ));
				$links[] = array( "block" => "attribute", "attribute_type_id" => "22", "options" => array( ));
		
			{% for LINK in SIDEBAR_LINKS %}
				<li>
					<div><i class='fa fa-angle-right listIcon'></i> 
					<i class='fa fa-square-o pull-right fa-lg activityIcon'></i><a class='{{ LINK.class }} workflowLink' data-block='{{ LINK.block }}'
						{% for NAME, VALUE in LINK.data %}
							data-{{ NAME }}='{{ VALUE }}' 
						{% endfor %}
					>{{ LINK.title }}</a></div>
				</li>
			{% endfor %}
		
		</ul>
		<button class='btn btn-success' disabled>Submit <i class='fa fa-check fa-lg'></i></button>
	</div>
</div>