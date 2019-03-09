var has_focus = true;
var saved_title='';
var notice_timer;
var new_posts = 0;
var new_you_posts = 0;

var icon_new = configRoot+"static/favicon-new.ico";
var icon_you = configRoot+"static/favicon-you.ico";
var icon_default = configRoot+"static/favicon.ico";


var noticeConfig = {
    key: 'optionNotice',
    value: false
};


$(document).ready(function(){


	if (("Notification" in window)) 
	{
		noticeConfig.value = menu_add_checkbox(noticeConfig.key, getKey(noticeConfig.key, false) , 'l_notify');
	}

	saved_title = document.title;

	window.onblur = function()
	{  

		has_focus=false;  

		window.document.onmousemove = function() 
		{
			has_focus=true; 
			window.document.onmousemove = null;
			title_notice_stop();
			setTimeout(post_notice_stop, 5000);
		}
	}

	window.onfocus = function()
	{  
		has_focus=true;  
		title_notice_stop();
		setTimeout(post_notice_stop, 5000);
	}

});

$(document).on(noticeConfig.key, function(e, value) 
{
    noticeConfig.value = value;
	
	if(value)
	{	

		if(Notification.permission.toLowerCase() === "granted")
			return false;
	
		Notification.requestPermission(function(permission){
			
		if(permission !== 'granted')
			noticeConfig.key = false;
			setKey(noticeConfig.key , false);
		});
	}
});



$(document).on('new_post', function(e,post) {	
	if(!is_my_reply(board_name, post.dataset.post))
		notice_new_reply(post, false);
});

$(document).on('new_answer', function(e,post) {	
	notice_new_reply(post, true);
});

 






function makeIcon(ico)
{
	var favicon = $("link[rel='shortcut icon']");

	if (!favicon.length) {
		$('<link rel="shortcut icon"></link>').appendTo('head');
	}

	$("link[rel='shortcut icon']").attr("href", ico);
}

function notice_new_reply(post, answer)
{

    if(!has_focus)
    {


		var $post = $(post);
		var div_trip = $post.find('.post-trip');
		var trip = div_trip.text();
		var is_anonymous = div_trip.length == 0;
		var is_oppost = post.classList.contains('post_op'); 
		var need_hide = is_oppost ? store.isThread(post.dataset.thread) :  store.hideCheck(post.dataset.board, post.dataset.thread, post.dataset.post, is_anonymous, trip);

		if(need_hide)
		{
			return false;
		}

		$(post).addClass('post_notice');

		if(answer)
		{ 
			makeIcon(icon_you);
			new_you_posts++;
		}
		else
		{
			new_posts++;
			document.title = "( " + new_posts + " ) "+saved_title;

			if(new_you_posts==0)
				makeIcon(icon_new);
		}


		if(noticeConfig.value)
		{

			var message = $(post).find('.post-message').text();
			var tripDiv  = $(post).find('.post-trip');
			var name = tripDiv.length==0 ? 'Anon' : tripDiv[0].innerHTML;
			var img = $(post).find('img');
			var icon = window.location.origin + '/static/logo3.png';

			if(img.length != 0)
			{
				icon = img[0].src;
			}
			
			var notification = new Notification(name, { 
				tag : "neo-event",
				body: message.replace(/>>\d+/, ''), 
				dir: 'auto', 
				icon: icon 
		
			});

			notification.onclick = function(e) {

				window.focus();
				this.close();
			}


		}
 
    }

}

function title_notice_stop()
{
	new_you_posts=0;
    new_posts=0;
    makeIcon(icon_default);
    document.title = saved_title;
}

function post_notice_stop()
{ 
	var divs = $('.post_notice');
 
	if(divs.length == 0)
		return true;

	$(divs[0]).removeClass('post_notice')
	setTimeout(post_notice_stop, 2000);
}
 
 
 