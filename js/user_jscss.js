

var optKeyCustomJs = 'custom_js';
var optKeyCustomCSS = 'custom_css';


$(document).ready(function(){

	let jsOpt = `<a onclick='openJSCSSBox("l_custom_js", "`+optKeyCustomJs+`")' style="cursor: pointer;"><i class="fa fa-pencil"></i></a>`;
	let cssOpt = `<a onclick='openJSCSSBox("l_custom_css", "`+optKeyCustomCSS+`")' style="cursor: pointer;"><i class="fa fa-pencil"></i></a>`;
	
	let enableJS = Menu.addCheckBox('optCustomJs', false, 'Enable Custom JS', '', false, jsOpt); 
	let enableCSS = Menu.addCheckBox('optCustomCss', false, 'Enable Custom CSS', '', false, cssOpt); 


	let userCSS = localStorage.getItem('custom_css');
	let userJS = localStorage.getItem('custom_js');
	
	if(enableCSS && userCSS !== null){

		var css = document.createElement('style'); 
    css.type="text/css"; 
    css.innerHTML = userCSS
    document.body.appendChild(css);

	
	}

	if(enableJS && userJS !== null){
 
		var f=new Function (userJS);
    setTimeout(f, 1);
		
		/*try {
			eval(userJS);
		} catch (e) {
	
			console.log("CUSTOM JS ERROR");
		}*/
	}

});

 


function openJSCSSBox(text, key){

	let data = localStorage.getItem(key)

	if(data == null){
		data = ''
	}

	let winID = key + '_win';
	let headID = key + '_head';
	let textareaID = key + '_win_textarea';


	let el = document.getElementById(winID);
	if(el != null){
		el.parentElement.removeChild(el);
	} 



	let div = `
	<div class="win" id='`+winID+`' style="display:none">
			<div class="win-head" id='`+headID+`'>
				<span>`+text+`</span>
			</div>
			<div class="win-body">
				<textarea style="flex:1" id='`+textareaID+`'>
				</textarea>
			</div>
			<div class="win-footer">
			<label class="button l_btn_Cancel" onclick='cancelJSCSS("`+winID+`")'></label>
			<label class="button l_btn_Save" onclick='saveJSCSS("`+winID+`", "`+key+`", "`+textareaID+`")'></label> 
			</div>
	</div>
	`;
	
	$(div).appendTo('body');
	
	let rect = win_size();

	let width = (rect.width/1.6);
	let height = (rect.height/3); 
	let top = (rect.height/2) - (height/2);
	let left = (rect.width/2) - (width/2);
	


	elWin = document.getElementById(winID);
 
	elWin.style.left= left+'px';
	elWin.style.top= top+'px'; 


	elArea = document.getElementById(textareaID);
	elArea.style.width= width+'px';
	elArea.style.height= height+'px';
	elArea.value=data;


	elWin.style.display='block';

	set_draggable(winID, false, ['textarea', 'button']);
	
}

function saveJSCSS(winID, Key, textareaID){

	let elWin = document.getElementById(winID);
	let elText = document.getElementById(textareaID);

	localStorage.setItem(Key, elText.value);

	elWin.parentElement.removeChild(elWin);
}

function cancelJSCSS(winID){
	let el = document.getElementById(winID);
	el.parentElement.removeChild(el);
}























































































































