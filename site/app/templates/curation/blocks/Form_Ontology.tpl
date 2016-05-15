<div class='ontologySelector'>
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
							{% if ONT_GROUPS %}
								<optgroup label="Ontology Groups">
								{% for ONT_NAME, ONT_ID in ONT_GROUPS %}
									<option value='{{ ONT_ID }}' {% if ONT_ID == SELECTED_ONT %}selected{% endif %}>{{ ONT_NAME }}</option>
								{% endfor %}
								</optgroup>
								<optgroup label="Ontologies">
							{% endif %}
							{% for ONT_NAME, ONT_ID in ONTOLOGIES %}
								<option value='{{ ONT_ID }}' {% if ONT_ID == SELECTED_ONT %}selected{% endif %}>{{ ONT_NAME }}</option>
							{% endfor %}
							{% if ONT_GROUPS %}
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
						<h5>Selected Terms</h5>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>