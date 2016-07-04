<div class='ontologySelector' data-singleselect='{{SINGLE_SELECT}}' data-singlequal='{{SINGLE_QUAL}}' data-allowqual='{{ALLOW_QUAL}}'>
	<div class='col-lg-12 col-md-12 col-sm-12 col-xs-12'>
		<h4 class='marginBotSm'>Ontology Search</h4>
		<table class='ontologySearchTable'>
			<thead>
			<tr>
				<th colspan='2'>
					<div class='col-lg-8 col-sm-8 paddingLeftNone paddingRightSm'>
						<div class='pull-right marginTopSm marginLeftSm'> <h5>in Group: </h5> </div>
						<div class='input-group'>
							<input type="text" name='ontologySearch' id='ontologySearch' class="form-control ontologySearchTxt" placeholder="Enter Search Term or ID" value="" autofocus>
							<span class='input-group-btn'>
								<button class='btn btn-success ontologySearchBtn' type='submit'>Search <i class='fa fa-search'></i></button>
							</span>
						</div>
					</div>
			
					<div class='col-lg-4 col-sm-4 paddingLeftNone paddingRightNone'>
						<select class='form-control ontologySelect' id='ontologySelect'>
							{% if TERMS %}
								<optgroup label="Available Term Ontologies">
								{% for ONT_NAME, ONT_ID in TERMS %}
									<option value='{{ ONT_ID }}' {% if ONT_ID == SELECTED_ONT %}selected{% endif %}>{{ ONT_NAME }}</option>
								{% endfor %}
								</optgroup>
							{% endif %}
							{% if QUALIFIERS %}
								<optgroup label="Available Qualifier Ontologies">
								{% for ONT_NAME, ONT_ID in QUALIFIERS %}
									<option value='{{ ONT_ID }}' {% if ONT_ID == SELECTED_ONT %}selected{% endif %}>{{ ONT_NAME }}</option>
								{% endfor %}
								</optgroup>
							{% endif %}
						</select>
					</div>
				</th>
			</tr>
			</thead>
			<tbody>
				<tr>
					<td class='ontologyLeft'>
					
						<div class='ontologyViewOptions pull-right marginRightSm'>
							<button type='button' class='btn btn-warning btn-sm ontologyResetTree'>Reset Tree <i class='fa fa-lg fa-tree'></i></button>
						</div>
					
						<h5>Ontology View: 
						<div class='btn-group ontologyViewBtns' role='group'>
							<button type='button' data-show='ontologyViewPopular' class='btn btn-default btn-sm ontologyViewBtn ontologyViewPopularBtn active'>Popular</button>
							<button type='button' data-show='ontologyViewSearch' class='btn btn-default btn-sm ontologyViewBtn ontologyViewSearchBtn'>Search</button>
							<button type='button' data-show='ontologyViewTree' class='btn btn-default btn-sm ontologyViewBtn ontologyViewTreeBtn'>Tree</button>
						</div>
						</h5>
						<div class='ontologyViews well well-sm clearfix marginTopSm marginRightSm'>
							<div class='ontologyView ontologyViewPopular'>Popular</div>
							<div class='ontologyView ontologyViewSearch' style='display:none'>Search for terms above to populate this list...</div>
							<div class='ontologyView ontologyViewTree' style='display:none'>Tree</div>
						</div>
					</td>
					<td class='ontologyRight'>
						<div class='clearfix rightHeader'>
							<div class='ontologySelectedTools pull-right'>
								<div class='btn-group'>
									<button type='button' class='btn btn-success btn-sm dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-has-expanded='false'><i class='fa fa-cog'></i> Tools <span class='caret'></span></button>
									<ul class='dropdown-menu'>
										<li><a class='selectedToolLink ontologyClearAllLink'>Clear All</a></li>
										<li><a class='selectedToolLink ontologyClearChecked'>Clear Checked Terms</a></li>
										<li><a class='selectedToolLink ontologyClearAllQualifiers'>Clear All Qualifiers</a></li>
										<li><a class='selectedToolLink ontologyClearCheckedQualifiers'>Clear Checked Qualifiers</a></li>
										<li role="separator" class="divider"></li>
										<li><a class='selectedToolLink ontologyTermCheck'>Check All Terms</a></li>
										<li><a class='selectedToolLink ontologyTermUncheck'>UnCheck All Terms</a></li>
										<li role="separator" class="divider"></li>
										<li><a class='selectedToolLink ontologyQualifierCheck'>Check All Qualifiers</a></li>
										<li><a class='selectedToolLink ontologyQualifierUncheck'>UnCheck All Qualifiers</a></li>
									</ul>
								</div>
							</div>
							<h5>Selected Terms</h5>
						</div>
						<div class='ontologySelectedTerms'></div>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>