<div>
	<div class='col-lg-3 col-md-3 col-sm-4 col-xs-4'>
		<textarea class="form-control" placeholder='{{ PLACEHOLDER_MSG }}' name='{{BASE_NAME}}-participants' id='{{BASE_NAME}}-participants' rows="10"></textarea>
	</div>
	<div class='col-lg-3 col-md-3 col-sm-5 col-xs-5'>
		<div class='clearfix'>
		<div class='form-group'>
			<label class='col-sm-3 control-label marginTopSm'>Role:</label>
			<div class='col-sm-9 marginBotSm'>
				<select class="form-control" name='{{BASE_NAME}}-role'>
					{% for ROLEID, ROLENAME in ROLES %}
						<option value='{{ ROLEID }}' {% if ROLEID == SELECTED_ROLE %}selected{% endif %}>{{ ROLENAME }}</option>
					{% endfor %}
				</select>
			</div>
		</div>
		<div class='form-group'>
			<label class='col-sm-3 control-label marginTopSm'>Taxa:</label>
			<div class='col-sm-9 marginBotSm'>
				<select class="form-control" name='{{BASE_NAME}}-organism'>
				  <option>1</option>
				  <option>2</option>
				  <option>3</option>
				  <option>4</option>
				  <option>5</option>
				</select>
			</div>
		</div>
		<div class='form-group'>
			<label class='col-sm-3 control-label marginTopSm'>IDs:</label>
			<div class='col-sm-9 marginBotSm'>
				<select class="form-control" name='{{BASE_NAME}}-id_type'>
				  <option>1</option>
				  <option>2</option>
				  <option>3</option>
				  <option>4</option>
				  <option>5</option>
				</select>
			</div>
		</div>
		<div class='form-group'>
			<label class='col-sm-3 control-label marginTopSm'>Type:</label>
			<div class='col-sm-9 marginBotSm'>
				<select class="form-control" name='{{BASE_NAME}}-participant_type'>
					{% for TYPEID, TYPENAME in PARTICIPANT_TYPES %}
						<option value='{{ TYPEID }}' {% if ROLEID == SELECTED_PTYPE %}selected{% endif %}>{{ TYPENAME }}</option>
					{% endfor %}
				</select>
			</div>
		</div>
		</div>
		<div class='marginTopSm text-center'>
			Add Attribute <i class='fa fa-plus-square-o fa-lg'></i>
		</div>
	</div>
</div>