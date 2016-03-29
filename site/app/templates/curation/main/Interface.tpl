<div id='curationWorkflow' class=''></div>

<div id='curationRightSidebar' class='clearfix'>
	<div id='curationMenu'>
		<h4>Curation Checklist</h4>
		<ul id='curationChecklist'>
		
			{% for LINK in SIDEBAR_LINKS %}
				{{ LINK|raw }}
			{% endfor %}
		
		</ul>
		<button class='btn btn-sm btn-success' disabled>Submit <i class='fa fa-check fa-lg'></i></button>
		<button class='btn btn-sm btn-primary' id='addNewChecklistItem'>Add Item <i class='fa fa-plus-square-o fa-lg'></i></button>
		<input type='hidden' id='checklistBlockCount' name='checklistBlockCount' value='{{ CHECKLIST_BLOCK_COUNT }}' />
		<input type='hidden' id='checklistPartCount' name='checklistPartCount' value='{{ CHECKLIST_PART_COUNT }}' />
		<input type='hidden' id='lastParticipant' name='lastParticipant' value='workflowLink-{{ LAST_PARTICIPANT }}' />
	</div>
	
	<div id='curationHidden'>
	
		<div id='fullAttributeHTML'>
			<select class='form-control attributeAddSelect' id='fullAttributeSelect'>
				<optgroup label='Participants'>
					{% for ATTRIBUTE_ID, ATTRIBUTE_NAME in CHECKLIST_PARTICIPANTS %}
						<option value='{{ATTRIBUTE_ID}}'>{{ATTRIBUTE_NAME}}</option>
					{% endfor %}
				</optgroup>
				<optgroup label='Attributes'>
					{% for ATTRIBUTE_ID, ATTRIBUTE_NAME in CHECKLIST_ATTRIBUTES %}
						<option value='{{ATTRIBUTE_ID}}'>{{ATTRIBUTE_NAME}}</option>
					{% endfor %}
				</optgroup>
				<optgroup label='Quantitative Scores'>
					{% for ATTRIBUTE_ID, ATTRIBUTE_NAME in CHECKLIST_SCORES %}
						<option value='{{ATTRIBUTE_ID}}'>{{ATTRIBUTE_NAME}}</option>
					{% endfor %}
				</optgroup>
			</select>
			<button type='button' id='fullAttributeSubmit' class='btn btn-success btn-block marginTopSm'>ADD <i class='fa fa-lg fa-plus-square-o'></i></button>
		</div>

		<div id='subAttributeHTML'>
			<select class='form-control attributeAddSelect' id='subAttributeSelect'>
				{% for ATTRIBUTE_ID, ATTRIBUTE_NAME in CHECKLIST_SUBATTRIBUTES %}
					<option value='{{ATTRIBUTE_ID}}'>{{ATTRIBUTE_NAME}}</option>
				{% endfor %}
			</select>
			<input type='hidden' class='subAttributeCount' value='1' />
			<input type='hidden' class='subAttributeParent' value='' />
			<input type='hidden' class='subAttributeParentName' value='' />
			<button type='button' id='subAttributeSubmit' class='btn btn-success btn-block marginTopSm'>ADD <i class='fa fa-lg fa-plus-square-o'></i></button>
		</div>
		
	</div>
</div>