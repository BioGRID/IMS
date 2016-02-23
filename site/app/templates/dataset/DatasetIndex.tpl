<div class='datasetBody'>

	<aside class='datasetSidebar'>
		<div class='sidebarBlock text-center'>
			<a class='iconLink' href='{{ WEB_URL }}/Dataset/?datasetID={{ DATASET_SOURCE_ID }}' title='Dataset Homepage' >
				<span class='fa-stack fa-2x'>
					<i class='fa fa-circle fa-stack-2x text-info'></i>
					<i class='fa fa-home fa-stack-1x'></i>
				</span>
			</a>
			<h2>Dataset Summary</h2>
			<h3>{{ TYPE_NAME }}: {{ DATASET_SOURCE_ID }}</h3>
			<div class='datasetDetails'>
				<div class='datasetDetail marginBotSm'><span id='availabilitySwitch'><span class='label label-{{ AVAILABILITY_LABEL }}'><span class='datasetDetailText'>{{ AVAILABILITY }}</span></span></span></div>
			</div>
		</div>
		<div class='sidebarBlock text-center'>
			<h2>Current Status</h2>
			<div class='datasetDetails'>
				<div class='datasetDetail marginTopNone marginBotSm'><span id='statusSwitch' class='label label-{{ STATUS_LABEL }}'><span class='datasetDetailText'>{{ STATUS }}</span></span></div>
			</div>
			<h3>{{ HISTORY_NAME }}</h3>
			<h4 class='paddingBotXs'>{{ HISTORY_DATE }}</h4>
		</div>
		
		{% for SECTION in SUBSECTIONS %}
			<div class='sidebarLink' data-type='{{ SECTION.type }}'>
				<i class='fa fa-lg fa-angle-right pull-right'></i>
				{{ SECTION.text }} 
			</div>
		{% endfor %}
		
	</aside>
	
	<div class='datasetContent'>
	
		<div class='datasetHeader'>
			<div class='datasetLinkouts pull-right'>LINKOUTS {{ LINKOUTS }}</div>
			<h2 class='heading-line marginBotSm'>{{ TYPE_NAME }} ({{ DATASET_SOURCE_ID }})</h2>
			
			<div class='container-fluid marginBotSm {{ SHOW_ACCESSED }}'>
				<div class='alert alert-info marginTopSm marginBotNone text-center'>
					<strong><i class="fa fa-exclamation-circle fa-lg"></i> Warning! ACCESSED!{{ ALERT_MESSAGE }}</strong>
				</div>
			</div>
			
			<div class='container-fluid marginBotSm {{ SHOW_INPROGRESS }}'>
				<div class='alert alert-danger marginTopSm marginBotNone text-center'>
					<strong><i class="fa fa-exclamation-circle fa-lg"></i> Warning! IN PROGRESS!{{ ALERT_MESSAGE }}</strong>
				</div>
			</div>
			
			<div class='datasetDetailsWrap'>
				<h3>{{ TITLE }}</h3>
				<p class='marginTopSm'><strong>{{ AUTHOR_LIST }}</strong></p>
				<p class='marginTopSm'>{{ ABSTRACT }}</p>
			</div>
			
			<div class='text-center'><a id='datasetDetailsToggle'><i class='fa fa-lg fa-angle-double-up'></i> Collapse Dataset Details <i class='fa fa-lg fa-angle-double-up'></i></a></div>
			
		</div>
		
		{% for SECTION in SUBSECTIONS %}
		
			<div id='section-{{ SECTION.type }}' class='datasetSubsection' data-type='{{ SECTION.type }}' data-activated='{{ SECTION.activated }}' data-disabled='{{ SECTION.disabled }}' data-combined='{{ SECTION.combined }}'>
			
				<hr />
			
				<div class='pull-right col-lg-3 col-md-4 col-sm-5 col-xs-6' style='padding-right: 0'>
					<div class='input-group marginBotSm marginTopSm'>
						<input type="text" name='dataTable-{{ SECTION.type }}-filterTerm' id='dataTable-{{ SECTION.type }}-filterTerm' class="form-control" placeholder="Enter Filter Term" value="" autofocus>
						<span class='input-group-btn'>
							<button class='btn btn-success' id='dataTable-{{ SECTION.type }}-submit'>Filter <i class='fa fa-check'></i></button>
						</span>
					</div>
				</div>
			
				<h3>{{ SECTION.text }}</h3>
				<div class='subhead dataTable-info marginBotSm'></div>
				<div class='dataTable-tools'>
					<button type='button' class='btn btn-primary btn-sm'>Check All</button>
					<div class='pull-right col-lg-2' style='padding-right: 0'>
						<select class='form-control input-sm statusSelect' id='dataTable-{{ SECTION.type }}-statusSelect'>
							<option value='activated' selected>View Activated Interactions</option>
							<option value='disabled'>View Disabled Interactions</option>
						</select>
					</div>
				</div>
				<div class='section-body'>
					<table id='dataTable-{{ SECTION.type }}' class='table table-striped table-bordered table-responsive table-condensed' width="100%"></table>
				</div>
			</div>
		{% endfor %}
		
	</div>
	
	<input type='hidden' id='datasetID' value='{{ DATASET_ID }}' />

</div>