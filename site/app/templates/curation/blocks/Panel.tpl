<div class='panel {% if SUBPANEL %} panel-default {% else %} panel-primary {% endif %}'>
	<div class='panel-heading'>
		{% if not SUBPANEL %}
		<div class='btnBox pull-right'>
			{% if not REQUIRED %}
				<button class='btn btn-danger btn-sm removeBlockBtn'>Remove <i class='fa fa-lg fa-remove'></i></button>
			{% endif %}
			<button class='btn btn-success btn-sm validateBlockBtn'>Validate <i class='fa fa-lg fa-refresh'></i></button>
		</div>
		{% endif %}
		{{ TITLE }} 
	</div>
	<div class='panel-body'>
	
		<div class='row'>
			<div class='col-lg-12 paddingLeftNone paddingRightNone'>
				{{ CONTENT|raw }}
			</div>
		</div>
		
	</div>
</div>