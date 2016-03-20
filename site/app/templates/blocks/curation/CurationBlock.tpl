<div class='curationBlock panel panel-primary' id='{{ ID }}'>
	<div class='panel-heading'>{{ TITLE }} <i class='fa fa-angle-down fa-lg pull-right'></i></div>
	<div class='panel-body'>
	
		<div class='row'>
			<div class='col-lg-12 paddingLeftNone paddingRightNone'>
				{{ CONTENT|raw }}
			</div>
		</div>
		
		<div class='row'>
			<div class='curationErrors marginTopSm col-lg-12'>
				<textarea class="form-control" id='{{ID}}-errors' rows="5">{{ ERRORS|raw }}</textarea>
			</div>
		</div>
		
	</div>
</div>