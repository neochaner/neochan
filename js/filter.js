
var lastMenuID=0;
var POST_MENU_OPENED=false;
var anonymous_user ='anonymous_user';
var filter_prefix = 'filter_';

var deleteHiddenPostsConfig = {
    key: 'optionDeleteHiddenPosts',
    value: false
};



$(document).ready(function() {
    var v = menu_add_checkbox(deleteHiddenPostsConfig.key, false, 'l_delHiddenPosts');
	deleteHiddenPostsConfig.value = v;
});

$(document).on(deleteHiddenPostsConfig.key, function(e, value) {
	deleteHiddenPostsConfig.value = value;

	if(!value)
		$('.post').each(function(){
			if($(this).css('display') == 'none')
				$(this).show();
		});

	filter_reload();
});





$(document).ready(function(){

	if(['general'].includes(config.active_page)){
		return;
	}

	filter_reload();

	document.body.addEventListener("click", function(e){

		if(POST_MENU_OPENED && !e.target.classList.contains('post-id-open')){ 
			post_menu_close();
		}

	}, false);
});

$(document).on('new_post', function(e,post) {	
	filter_post(post);
});

$(document).on('change_post', function(e,post) {	
	filter_post(post);
});

var hiddenPosts = [];

function AddHidden(post){
	if(!IsHidden(post))
		hiddenPosts.push(post);
}	

function IsHidden(post){

	let length = hiddenPosts.length;

	for(; length--;)
		if(post === hiddenPosts[length])
			return true;
		
	return false;
}

function RemHidden(post){

	let length = hiddenPosts.length;

	for(; length--;)
		if(post === hiddenPosts[length])
			hiddenPosts.splice(length, 1);
}


function filter_post(post, reload = false)
{
 
 
	var div_trip = post.querySelector('.post-trip');
	var trip = div_trip != null ? div_trip.innerHTML : '';
	var is_anonymous = div_trip == null;
	var is_oppost = post.classList.contains('post_op'); 
	var need_hide = is_oppost ? store.isThread(post.dataset.thread) : store.hideCheck(post.dataset.board, post.dataset.thread, post.dataset.post, is_anonymous, trip);

	if(is_oppost)
	{

	}
	else if(need_hide)
	{
		if(!is_oppost && deleteHiddenPostsConfig.value)
			post.style.display = 'none';
		else
		{
			$(post).find('.post-body').hide();
			$(post).find('.post-footer').hide();
		}

		AddHidden(post.dataset.board + post.dataset.post);
	}
	else
	{
		
		
		if(reload)
		{
	 
			let body = post.querySelector('.post-body');
			var is_hidden = post.style.display == 'none' || body.style.display =='none';

			if(is_hidden)
			{
				var $post = $(post);
				$post.show();
				$post.find('.post-body').show();
				$post.find('.post-footer').show();
			}
		}
		
		RemHidden(post.dataset.board + post.dataset.post);
	}
	
	// check links in post-body
	let links = post.getElementsByClassName('post-link');
	
	for(var i =0; i<links.length; i++){
		
		if(!optionDisableHideStyleValue && IsHidden(post.dataset.board + links[i].dataset.id)){
			$(links[i]).addClass('a-off');
		}
	
	}
	


	
}


function post_menu(event)
{
	event.preventDefault();
	
	let target = event.target;

	if(target.classList.contains('post-id-open')){
		post_menu_close();
		return;
	} else {
		post_menu_close();
		target.classList.add('post-id-open') 
		POST_MENU_OPENED = true;
	}
 

	var post = $(event.target).closest('.post')[0];
	var $post = $(post);
	var trip = $post.find('.post-trip').text();
	var hidden_post = $post.find('.post-body').css('display') == 'none';
	var is_oppost = post.classList.contains('post_op');
	var menu ="<div class='post-menu' id='post-menu' style='z-index: 2'><ul>";
	var hiddenByThread = !is_oppost && isThreadHidden(post.dataset.board, post.dataset.thread);
	var hiddenByTrip = trip.length < 2 ? false : isThreadHiddenByTrip(trip);
	
	// скрытие по номеру поста
	if(!hiddenByThread && !is_oppost)
	{
		if(isPostHidden(post.dataset.board, post.dataset.post))
			menu += "<li class='post-menu-item l_show_post' onclick=\"showPost('"+post.dataset.board+"', '"+post.dataset.post+"')\"></li>"
		else	
			menu += "<li class='post-menu-item l_hide_post' onclick=\"hidePost('"+post.dataset.board+"', '"+post.dataset.post+"')\"></li>"

	}
	
	// Скрытие по треду
	if(is_oppost || config.active_page == 'mega' || hiddenByThread)
	{
		if(isThreadHidden(post.dataset.board, post.dataset.thread))
			menu += "<li class='post-menu-item l_show_thread' onclick=\"showThread('"+post.dataset.board+"', '"+post.dataset.thread+"')\"></li>"
		else
			menu += "<li class='post-menu-item l_hide_thread' onclick=\"hideThread('"+post.dataset.board+"', '"+post.dataset.thread+"')\"></li>"
	}
	
	
	// СКРЫТИЕ ПО ТРИПКОДУ
	if(!hiddenByThread && trip)
	{
		if(isTripHidden(trip))
			menu += "<li class='post-menu-item l_show_trip' onclick=\"showTrip('"+trip+"')\"></li>"
		else
			menu += "<li class='post-menu-item l_hide_trip' onclick=\"hideTrip('"+trip+"')\"></li>" 
	}
	else if(!hiddenByThread && !is_oppost)// hide by anonimouse
	{ 
		if(isAnonHidden())
			menu += "<li class='post-menu-item l_show_anon' onclick=\"showAnon()\"></li>"
		else
			menu += "<li class='post-menu-item l_hide_anon' onclick=\"hideAnon()\"></li>" 
	}

	
	// СКРЫТИЕ ТРЕДА ПО ТРИПУ
	if(!hiddenByThread && !hiddenByTrip && trip && is_oppost)
		menu += "<li class='post-menu-item l_hide_thread_trip' onclick=\"hideThreadTrip('"+trip+"')\"></li>";
	else if(!hiddenByThread && hiddenByTrip && trip && is_oppost)
		menu += "<li class='post-menu-item l_show_thread_trip' onclick=\"showThreadTrip('"+trip+"')\"></li>";

	
	
	
	 
	menu += "<li class='post-menu-item l_report' onclick=\"report_toggle('"+post.dataset.board+"','"+post.dataset.thread+"','"+post.dataset.post+"')\"></li>"
	menu +='</ul></div>';
 
	var menuID = post.dataset.board+'_'+post.dataset.thread+'_'+post.dataset.post;


	lastMenuID = menuID;

	$(menu).appendTo('.main');
	var pos = $(post).offset();

    $("#post-menu").offset({ top: pos.top+18, left: pos.left});

	$( "#post-menu" ).click(function() {      
		$('#post-menu').remove();
    });
}

function post_menu_close(){
	let el = document.getElementsByClassName('post-id-open');
	
	if(el.length>0){
		el[0].classList.remove('post-id-open');
	}

	$remove('post-menu');
	POST_MENU_OPENED = false;
}

function filter_reload()
{  
	$('.a-off').removeClass('a-off');

	hiddenPosts = [];

	var posts = document.getElementsByClassName('post');

	var separators = $('hr');
	var separator_index=0;
 
	if(config.active_page == 'index')
	{

		var hideThreads = [];

		$('.thread_index').each(function (ind, thread){

			separator_index++;

			var thread_id = thread.dataset.thread;
			$thread = $(thread);

			var $opPost = ($thread).find('.post_op');
			var trip = $opPost.find('.post-trip').text();
			var hideByTrip = trip.length > 1 && isThreadHiddenByTrip(trip);

			if(isThreadHidden( thread.dataset.board, thread.dataset.thread) || hideByTrip)
			{ 

				hideThreads.push(thread.dataset.board + '_' + thread.dataset.thread);

				$thread.find('.omit').hide();

				if(deleteHiddenPostsConfig.value){
					$thread.hide();
					$thread.next().hide(); // separator
				}
				else{
					$thread.show();
					$thread.next().show();
				}


				var posts = $thread.find('.post');

				for(var i=0; i<posts.length;i++)
				{
					var post = $(posts[i]);
					
					if(post.hasClass('post_op'))
					{
						post.find('.post-body').hide();
						post.find('.post-footer').hide();
					}
					else
					{
						post.hide();
					}
				}
				
			}
			else
			{

				$thread.show();
				$thread.next().show(); // separator
		 
				$thread.find('.omit').show();
				$thread.find('.post').show();
			}	


		});



		for(var i = 0; i < posts.length; i++) {

			let curThread = posts[i].dataset.board + '_' + posts[i].dataset.thread;

			if(!hideThreads.includes(curThread)){
				filter_post(posts[i], true);
			}
		}
	}
	else
	{

		for(var i = 0; i < posts.length; i++) {
			filter_post(posts[i], true);
		}

	}

	reloadBacklinks();

	
}

function report_toggle(board, thread, post)
{

    if($('#report_option').length != 0)
    {
        $('#report_option').remove();
        return;
	}

	var div = "<div id='report_option' class='report' style='margin:0 0 20px 0;'>" +
    "<label class='option-label'>"+_T('Причина')+"</label>" +
	"<input id='report_reason' type='text'>"+
	"<label class='checktainer_xs ml15' >"+_T('копия админу')+
	"<input type='checkbox' id='report_global'><span class='checkmark_xs'></span>"+
	"</label>"+

    "<input class='button ml10' value='"+_T('Отправить')+"' onclick=\"send_report('"+board+"','"+thread+"', '"+post+"', )\" readonly></div>";
  
	var reply =  getPost(board, thread, post)
    $(div).insertAfter(reply)

}

function send_report(board, thread, post)
{

	var reason = $('#report_reason').prop('value');

	$('#report_option').fadeOut(300, function() { $(this).remove(); });
    
	var fdata = new FormData();  
	fdata.append( 'report', 1);
	fdata.append( 'board', board);
	fdata.append( 'thread', thread);
	fdata.append( 'post', post);
    fdata.append( 'delete_'+post, 1);
    fdata.append( 'reason', reason);
	fdata.append( 'json_response', 1);

	if($('#report_global').prop('checked'))
	{
		fdata.append( 'global', 1);
	}
 

    $.ajax({
		url: config.root+'post.php?neo23',
        type: 'POST',
        contentType: 'multipart/form-data', 
        data: fdata,
		success: function(response, textStatus, xhr) {

            if(response.alert)
                alert(_T(response.alert));
            else if (response.error) 
                alert(_T(response.error));
			else if (response.fail) 
                alert(_T('Произошла ошибка'));
            else if(response.success)
				alert(_T('Репорт отправлен'));
			else
				alert(_T(response));
		},
		error: function(xhr, status, er) {
			alert(_T('Сервер вернул ошибку: ') + er);
		},
		contentType: false,
		processData: false
	}, 'json');


} 


 
 







