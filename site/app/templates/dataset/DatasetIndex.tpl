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
		<div id='datasetDetails'>
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
			
			<h3>{{ TITLE }}</h3>
			<p class='marginTopSm'><strong>{{ AUTHOR_LIST }}</strong></p>
			<p class='marginTopSm'>{{ ABSTRACT }}</p>
			
		</div>
		
		{% for SECTION in SUBSECTIONS %}
			<div id='section-{{ SECTION.type }}' class='datasetSubsection' data-type='{{ SECTION.type }}'>
				<h3 class='marginBotSm'>{{ SECTION.text }}</h3>
				<div class='section-body'>
					<table id='dataTable-{{ SECTION.type }}' class='table table-striped table-bordered table-responsive table-condensed' width="100%"></table>
				</div>
			</div>
		{% endfor %}
		
	</div>
	
	<input type='hidden' id='datasetID' value='{{ DATASET_ID }}' />

</div>