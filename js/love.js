


$(window).bind('load', function(){


	if (localStorage.name && document.forms.post && document.forms.post.elements['neoname'])
		document.forms.post.elements['neoname'].value = localStorage.name;
	if (localStorage.email && document.forms.post.elements['email'])
		document.forms.post.elements['email'].value = localStorage.email;


	// тема
	var selectedTheme = localStorage.getItem('theme-css');
	
	if(!selectedTheme)
	{
		selectedTheme='light_blue';
	}

	let theme = document.getElementById('theme');
	theme.value = selectedTheme;

	theme.addEventListener("change", function(){	
		localStorage.setItem('theme-css', this.value);
		document.getElementById("theme-css").href = "/stylesheets/" + this.value+ ".css?neo23" + new Date().getTime();
	});


		
	// меню
	$('#btn-options').click(function(){toggleModal('options');});




	//  Форма ответ/создания
	$('.reply-quote-control').click(function(){AddQuote('replybox_text');});
	$('.reply-bold-control').click(function(){AddTag('[b]','[/b]','replybox_text');});
	$('.reply-italic-control').click(function(){AddTag('[i]','[/i]','replybox_text');});
	$('.reply-strikethrough-control').click(function(){AddTag('[s]','[/s]','replybox_text');});
	$('.reply-spoiler-control').click(function(){AddTag('[spoiler]','[/spoiler]','replybox_text');});
	$('.reply-love-control').click(function(){AddTag('[love]','[/love]','replybox_text');});
	$('.reply-hide-control').click(function(){$('.reply-container').fadeOut(300);});

	checkBumpLimit();
});

var smilesDiv = null;
let tippySmileBox;

$(document).ready(function() {

	loadMobileMenu();

	let divs = document.getElementsByClassName('onload-flex');
	for(let i=0; i<divs.length; i++)
		divs[i].style.display='flex';




	rememberStuff();
	makePollAjax(document);

	if(typeof tube_name !== 'undefined'){
		checkNeoTube();
		setInterval(checkNeoTube, 15000);
	}
});



$(document).on('new_post', function(e,post) {
	var post_count = parseInt($('#ts-count').html())+1;
	$('#ts-count').html(' ' +  post_count);

	if(post_count>499)
		checkBumpLimit();

	makePollAjax(post);
 
});


 
 
function checkBumpLimit()
{
	
	if(active_page == 'thread' && document.getElementById('bump-limit-info') == null)
	{
		var posts = document.getElementsByClassName('post');

		if(posts.length > 499)
		{
			let line = document.createElement('div');
			line.classList.add('infoline');
			line.id = 'bump-limit-info';
			line.innerText = _T('БАМПЛИМИТ');

			posts[499].parentNode.insertBefore(line, posts[499].nextSibling);
		
		}
	}
}

function checkNeoTube(){


	var fdata = new FormData();    
	fdata.append( 'board', tube_name);
	fdata.append( 'getPlaylist', 1);
	fdata.append( 'json_response', true);

	$.ajax({
		url: configRoot+'api.php',
		type: 'POST',
		contentType: 'multipart/form-data', 
		data: fdata,
		success: function(response, textStatus, xhr) {

			let activeIcon = false;

			if(response.success && response.playlist != null){
				let tracks =JSON.parse(response.playlist);
				for(let i=0;i<tracks.length;i++)
					if(tracks[i].end > getServerTime())
						activeIcon = true;
			}

			if(activeIcon && !$('#btn-neotube').hasClass('icon-active'))
				$('#btn-neotube').addClass('icon-active');
			else if(!activeIcon && $('#btn-neotube').hasClass('icon-active'))
				$('#btn-neotube').removeClass('icon-active');

		},
		error: function(xhr, status, er) {

		},
		contentType: false,
		processData: false
	}, 'json');
}

function guiAlert(text)
{
	$('#alert').html('<span>' + text + '</span>');
	$('#alert').fadeIn(300);
	setTimeout(function(){	$('#alert').fadeOut(300)}, 5000);
}

function loadMobileMenu(){

let pname = window.location.pathname;
let options = document.querySelectorAll('.mobile-menu select option');

for(let i=0; i<options.length; i++){

	if(options[i].value.length>1&& pname.indexOf( options[i].value) == 0){
		options[i].selected = true;
		break;
	}
}


}

function toggleModal(id)
{

	var modal = $('#'+id);

	if(modal.css('display') == 'none')
	{
		$('.modal').hide();
		modal.show();
	}
	else
		modal.hide();

}

 

function AddQuote(obj)
{
	var citateText = getSelectedText(true);

	if(citateText == '')
		citateText='>';

	document.getElementById(obj).value += citateText;
}


function AddTag(tag1,tag2,obj)
{

	var is_smile = tag1[0] == ':';
	ToolbarTextarea =	document.getElementById(obj);


	if (document.selection)
	{
		var sel = document.selection.createRange();
		sel.text = tag1 + sel.text + tag2;
	}
	else
	{
		var len = ToolbarTextarea.value.length;
		var start = ToolbarTextarea.selectionStart;
		var end = ToolbarTextarea.selectionEnd;
		var scrollTop = ToolbarTextarea.scrollTop;
		var scrollLeft = ToolbarTextarea.scrollLeft;
		var sel = ToolbarTextarea.value.substring(start, end);
		var rep = tag1 + sel + tag2;

		var start_str =  ToolbarTextarea.value.substring(0,start) ;
		var end_str =  ToolbarTextarea.value.substring(end,len);
		var opt=0;

		if(is_smile && start_str.length>0 && start_str[start_str.length-1] != ' ' && start_str[start_str.length-1] != '\n' && start_str[start_str.length-1] != ']')
		{
			start_str+=' ';
			opt++;
		}

		if(is_smile && (end_str.length==0 || (end_str.length>1 && end_str[0]!=' ')))// && end_str[0]!='[')))
		{
			rep+=' ';
			opt++;
		}

		ToolbarTextarea.value = start_str+ rep + end_str;
		ToolbarTextarea.scrollTop = scrollTop;
		ToolbarTextarea.scrollLeft = scrollLeft;
		ToolbarTextarea.focus();

		let sel_start = start+tag1.length+opt;
		let sel_end =end+tag1.length+opt;
		
		ToolbarTextarea.setSelectionRange(sel_start, sel_end);
		
	}

    $('#' + obj).keyup();
}

 

function dopost(form) 
{
	if (form.elements['neoname']) {
		localStorage.name = form.elements['neoname'].value.replace(/( |^)## .+$/, '');
	}
	if (form.elements['password']) {
		localStorage.password = form.elements['password'].value;
	}
	if (form.elements['user_flag']) {
		if (localStorage.userflags) {
			var userflags = JSON.parse(localStorage.userflags);
		} else {
			localStorage.userflags = '{}';
			userflags = {};
		}
		userflags[board_name] = form.elements['user_flag'].value;
		localStorage.userflags = JSON.stringify(userflags);
	}
	if (form.elements['email'] && form.elements['email'].value != 'sage') {
		localStorage.email = form.elements['email'].value;
	}
	
	saved[document.location] = form.elements['body'].value;
	sessionStorage.body = JSON.stringify(saved);
	
	return form.elements['body'].value != "" || form.elements['file'].value != "" || (form.elements.file_url && form.elements['file_url'].value != "");
}


function hideReply(id, thread_num=null, board_str=null)
{

	var t = $('.thread');
 
	if(board_str == null)
		board_str = t.data('board');
	if(thread_num == null)
		thread_num = t.data('id');
		
	
	if(board_str == null || thread_num == null || typeof(board_str) == 'undefined'|| typeof(thread_num) == 'undefined')
	{
		var info = $(window.event.target).parent().find('.thread-name');

		if(info.length == 1)
		{
			board_str = info[0].attr('board');
			thread_num = info[0].attr('thread');
		}
	}

	var control_id = '#reply_' + id;

	var body  =  $('.post-body', control_id);
	var footer=  $('.post-footer', control_id);
	

    if(body.css('display') == 'none')
    {

		var text = $(control_id).find('.post-id').html();

		if($(control_id).find('.post-trip').text() == 'Robot') 
		{
			$(control_id).find('.post-id').html('#'+id);
			$(control_id).find('.post-id').prop("onclick", null);
		}

		body.show();
		footer.show();
    }
    else
    {
		body.hide();
		footer.hide();
    }
}
 
 
function makePollAjax(div){

	$(div).on('click', 'a.poll-control', function(event) {

		event.preventDefault(); 

		let k = getMedia(this.href);
		let media = k == 'media' ? '1' : '0';
		let link = this.href + '&json_response=1' + '&media=' + media;
		
		pollVote(link, this.href);


	});

}

function pollVote(href, orig_link){


	$.ajax({
		url: href
	}).done(function(resp) {

		lalert(resp);

		if(resp.success){
			autoLoadSecCurrent = 0;
			addMedia(orig_link, 'media');
		}

		
	}).fail(function(jqXHR, textStatus, errorStatus) {
		
		infoAlert(textStatus + " : " + errorStatus);

	});

}
















 