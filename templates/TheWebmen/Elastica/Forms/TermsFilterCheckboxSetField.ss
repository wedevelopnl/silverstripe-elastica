<ul $AttributesHTML aria-label="$Title">
	<% if $Options.Count %>
		<% loop $Options %>
			<li class="$Class" role="$Role">
				<input id="$ID" class="checkbox" name="$Name" type="checkbox" value="$Value.ATT"<% if $isChecked %> checked="checked"<% end_if %><% if $isDisabled %> disabled="disabled"<% end_if %> />
				<label for="$ID">$Title.RAW</label>
			</li>
		<% end_loop %>
	<% else %>
		<li role="$Role">No options available</li>
	<% end_if %>
</ul>