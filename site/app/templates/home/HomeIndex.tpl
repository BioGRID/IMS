<div class='primaryContent'>
	<div class='container-fluid'>
	
		<div class='pull-right col-lg-3 col-md-4 col-sm-5 col-xs-6'>
			<form class="form-search" role="form" method="GET" action="{{ WEB_URL }}/Publication/">
				<div class='input-group marginBotSm marginTopSm'>
					<input type="text" name='pubmed' id='pubmed' maxlength='8' class="form-control" placeholder="Enter Pubmed" value="" autofocus>
					<span class='input-group-btn'>
						<button class='btn btn-success' type='submit'>Search</button>
					</span>
				</div>
			</form>
		</div>
	
		<h3>{{ WEB_NAME_ABBR }} Dashboard</h3>
		<div class='subhead'>Welcome Back <strong>{{FIRSTNAME}} {{LASTNAME}}</strong></div>
	</div>
</div>

<div class='greyBG marginTopSm paddingLg marginBotSm'>
	<div class='container-fluid'>
		<div class='text-center'>
			<a href="#" class="shortcut-link">
				<span class="shortcut-icon">
					<i class="fa fa-hourglass-half"></i>
					<span class="shortcut-alert">
						17
					</span>	
				</span>
				<span class="text">In Progress</span>
			</a>
			<a href="#" class="shortcut-link">
				<span class="shortcut-icon">
					<i class="fa fa-envelope-o"></i>
					<span class="shortcut-alert">
						9
					</span>	
				</span>
				<span class="text">Messages</span>
			</a>
			<a href="#" class="shortcut-link">
				<span class="shortcut-icon">
					<i class="fa fa-bell"></i>
					<span class="shortcut-alert">
						6
					</span>	
				</span>
				<span class="text">Alerts</span>
			</a>
			<a href="#" class="shortcut-link">
				<span class="shortcut-icon">
					<i class="fa fa-gear"></i>
				</span>
				<span class="text">Settings</span>
			</a>
			<a href="#" class="shortcut-link">
				<span class="shortcut-icon">
					<i class="fa fa-history"></i>
					<span class="shortcut-alert">
						9
					</span>
				</span>
				<span class="text">Quality Control</span>
			</a>
		</div>
	</div>
	<div class='container-fluid {{ SHOW_WARNING }}'>
		<div class='alert alert-danger marginTopSm marginBotNone text-center'>
			<strong><i class="fa fa-exclamation-circle fa-lg"></i> Warning! Danger!{{ ALERT_MESSAGE }}</strong>
		</div>
	</div>
</div>

<div>
	<div class='container-fluid'>
		<h4 class='marginBotSm'><span class='groupName'>{{ GROUP_NAME }}</span> - Dataset Queues</h4>
		<div class='col-lg-3 col-md-3 col-sm-6'>
			<div class='high-queue-panel'>
				<i class="fa fa-arrow-circle-o-up fa-3x pull-right"></i>
				<h3><strong>2,000</strong></h3>
				<h5>High Priority Publications<h5>
			</div>
		</div>
		<div class='col-lg-3 col-md-3 col-sm-6'>
			<div class='standard-queue-panel'>
				<i class="fa fa-star fa-3x pull-right"></i>
				<h3><strong>2,000</strong></h3>
				<h5>Standard Publications<h5>
			</div>
		</div>
		<div class='col-lg-3 col-md-3 col-sm-6'>
			<div class='error-queue-panel'>
				<i class="fa fa-exclamation-triangle fa-3x pull-right"></i>
				<h3><strong>2,000</strong></h3>
				<h5>Erroneous Publications<h5>
			</div>
		</div>
		<div class='col-lg-3 col-md-3 col-sm-6'>
			<div class='recent-queue-panel'>
				<i class="fa fa-globe fa-3x pull-right"></i>
				<h3><strong>2,000</strong></h3>
				<h5>Recent Publications<h5>
			</div>
		</div>
	</div>
</div>