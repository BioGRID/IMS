<li>

	<i class='fa fa-angle-right listIcon'></i>
	<div class='pull-right activityIcons'>
		<span class='activityIcon activityIconNEW'><i class='fa fa-square-o fa-lg text-primary'></i></span>
		<span class='activityIcon activityIconERROR noShow'><i class='fa fa-close fa-lg text-danger'></i></span>
		<span class='activityIcon activityIconVALID noShow'><i class='fa fa-check-square-o fa-lg text-success'></i></span>
		<span class='activityIcon activityIconWARNING noShow'><i class='fa fa-check-square-o fa-lg text-warning'></i></span>
		<span class='activityIcon activityIconPROCESSING noShow'><i class='fa fa-refresh fa-lg fa-spin text-info'></i></span>
	</div>
	<a id='workflowLink-{{ ID }}' data-blockid='{{ ID }}' class='{{ CLASS }} workflowLink' data-block='{{ BLOCK }}'
		{% for NAME, VALUE in DATA %}
			data-{{ NAME }}='{{ VALUE }}' 
		{% endfor %}
	>
		{{ TITLE }}
	</a>
	
	<ul class='curationSubmenu'>
		{% for ITEM in SUBMENU %}
			<li class='{{ ITEM.class }} curationSubmenuItem'><i class='fa fa-angle-double-right'></i> {{ ITEM.value|raw}}</li>
		{% endfor %}
		{% if IS_PARTICIPANT %}
			<li class='subattributeLink'><i class='fa fa-angle-double-right'></i> <a class='addSubAttribute' id='workflowSubLink-{{ ID }}' data-parenttitle='{{ TITLE }}' data-parentblockid='{{ ID }}' data-subcount='1'>Add Sub-Attribute <i class='fa fa-plus-square-o'></i></a></li>
		{% endif %}
	</ul>
	
</li>