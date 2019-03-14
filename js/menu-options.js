

/*
		require store.js

*/

class MenuManager {
	
	constructor(){	
		this.options={};	 
	}

	addCheckBox(key, default_bool, text, tooltip, callback=false, add_div='')
	{
	
		var rValue  = getKey(key, default_bool);
		var state = rValue ? 'checked' : '';
		
		var div = `<div class='options-item'>
		<label class='checktainer_xs'><input type='checkbox' `+state+` id='`+key+`' onclick='Menu.toggle("`+key+`")'>
		<span class='checkmark_xs'></span>`+text+`</label>`+add_div+`</div>`;
		
		let box = document.getElementsByClassName('options-tab')[0];
		box.innerHTML+=div;

		this.options[key] = {
			'callback':callback,
			'something.... ' :''
		};

		
		return rValue;
	
	}	
	
	toggle(key){

		let value = document.getElementById(key).checked;
		
		setKey(key, value);
		
		if(this.options[key].callback){
			this.options[key].callback(value);
		}
		
	}
	
}

var Menu = new MenuManager();










/* OLD CODE, WILL BE REMOVE */

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





























