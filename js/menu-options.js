 

function menu_add_checkbox(key, default_bool, l_class, tooltip)
{
	
	var rValue  = getKey(key, default_bool);
	var state = rValue ? 'checked' : '';
	
	var div = "<div class='options-item'>";
	//div += "<label class='checktainer_xs' title='"+_T(tooltip)+"'>"+_T(text)+"<input type='checkbox' "+state+">";
	div += "<label class='checktainer_xs "+l_class+"'><input type='checkbox' "+state+">";
	div += "<span class='checkmark_xs'></span></label>";
	div += "</div>";

	$(div).appendTo('.options-tab').on("change", function() {
		
		var value = $(this).find(':checkbox').prop('checked');
		setKey(key, value);

		$(document).trigger(key, value);
	
	});
	
	return rValue;
	
}

function menu_add_raw(raw)
{
	 
	$("<div class='options-item'>" +raw+"</div>").appendTo('.options-tab');
	
}
