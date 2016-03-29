<div>
	<div class='col-lg-6 col-md-5 col-sm-6 col-xs-6'>
		<textarea class="form-control" placeholder='{{ PLACEHOLDER_MSG }}' name='{{BASE_NAME}}-participants' id='{{BASE_NAME}}-participants' rows="12"></textarea>
	</div>
	<div class='col-lg-6 col-md-7 col-sm-6 col-xs-6'>
		<div class='form-group'>
			<label class='col-sm-12 control-label paddingTopSm'>Role:</label>
			<div class='col-sm-12'>
				<select class="form-control input-sm" name='{{BASE_NAME}}-role'>
					{% for ROLEID, ROLENAME in ROLES %}
						<option value='{{ ROLEID }}' {% if ROLEID == SELECTED_ROLE %}selected{% endif %}>{{ ROLENAME }}</option>
					{% endfor %}
				</select>
			</div>
		</div>
		<div class='form-group'>
			<label class='col-sm-12 control-label paddingTopSm'>Type:</label>
			<div class='col-sm-12'>
				<select class="form-control input-sm" name='{{BASE_NAME}}-participant_type'>
					{% for TYPEID, TYPENAME in PARTICIPANT_TYPES %}
						<option value='{{ TYPEID }}' {% if ROLEID == SELECTED_PTYPE %}selected{% endif %}>{{ TYPENAME }}</option>
					{% endfor %}
				</select>
			</div>
		</div>
		<div class='form-group'>
			<label class='col-sm-12 control-label paddingTopSm'>Taxa:</label>
			<div class='col-sm-12'>
				<select class='form-control partOrgSelect input-sm' name='{{BASE_NAME}}-organism'>
					{% for ORGID, ORGNAME in ORGANISMS %}
						<option value='{{ ORGID }}' {% if ORGID == SELECTED_ORG %}selected{% endif %}>{{ ORGNAME }}</option>
					{% endfor %}
				</select>
			</div>
		</div>
		<div class='form-group'>
			<label class='col-sm-12 control-label paddingTopSm'>IDs:</label>
			<div class='col-sm-12'>
				<select class="form-control input-sm" name='{{BASE_NAME}}-id_type'>
					{% for TYPEID, TYPENAME in ID_TYPES %}
						<option value='{{ TYPEID }}' {% if TYPEID == SELECTED_TYPE %}selected{% endif %}>{{ TYPENAME }}</option>
					{% endfor %}
				</select>
			</div>
		</div>
	</div>
</div>