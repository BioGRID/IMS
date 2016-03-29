<li>

	<i class='fa fa-angle-right listIcon'></i> 
	<i class='fa fa-square-o pull-right fa-lg activityIcon'></i><a id='workflowLink-{{ ID }}' data-blockid='{{ ID }}' class='{{ CLASS }} workflowLink' data-block='{{ BLOCK }}'
		{% for NAME, VALUE in DATA %}
			data-{{ NAME }}='{{ VALUE }}' 
		{% endfor %}
	>{{ TITLE }}</a>
	
	<ul class='curationSubmenu'>
		{% for ITEM in SUBMENU %}
			<li class='{{ ITEM.class }} curationSubmenuItem'><i class='fa fa-angle-double-right'></i> {{ ITEM.value|raw}}</li>
		{% endfor %}
		{% if IS_PARTICIPANT %}
			<li class='subattributeLink'><i class='fa fa-angle-double-right'></i> <a class='addSubAttribute' id='workflowSubLink-{{ ID }}' data-parenttitle='{{ TITLE }}' data-parentblockid='{{ ID }}' data-subcount='1'>Add Sub-Attribute <i class='fa fa-plus-square-o'></i></a></li>
		{% endif %}
	</ul>
	
</li>