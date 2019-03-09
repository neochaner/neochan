var $container = $('<div id="fullscreen-container" style="display:none"></div>');
var $win = $( window );
//var $controls = $('<div id="fullscreen-container-controls"><i class="fa-thumb-tack fa"></i><i class="fa-times fa"></i></div>');
var active = false;
var mouse_on_container = false;
var img_width, img_height;
var multiplier = 1;
var container_mouse_pos_x = 0;
var container_mouse_pos_y = 0;
var webm = false;
var mp4 = false;
var mp3 = false;
var video = false;
var border_offset = 1; //magic number
var youtube_iframe=false;

var youtube_width = 640;
var youtube_height = 360;


function reset_popups(elem)
{
	if(elem.length==0)
		return;

	var name = elem[0].localName;
	var divs = ['article',  'section', 'nav', 'header', 'div', 'blockquote', 'h1', 'h2'];
	var reset = false;

	for(var i=0; i < divs.length; i++)
	{
		if(divs[i] == name)
		{
			reset=true;
			break;
		}
	}

	if(reset)
	{
		if($('#options').css('display') != 'none')
			$('#options').hide();
		if($('.post-menu').length != 0)
			$('.post-menu').remove();

	}

	return  reset;
}

function youtube_resize(multiplier)
{

    if(multiplier== 1 || multiplier== 0)
        return false;

	var width  = multiplier > 1 ? (youtube_width / 10) : (0-youtube_width) / 10;
	var height = multiplier > 1 ? (youtube_height / 10) : (0-youtube_height) / 10;

	var container = $('#fullscreen-container');
	var iframe = $('#fullscreen-container').find('iframe');


	width =  container.width() + width;
	height = container.height() + height;

	container.width(width);
	container.height(height);

	iframe.width(width);
	iframe.height(height);

}


$(document).ready(function() {


	if(getKey('testKey', false))
		return

		$('body').append($container);

		$win = $( window );

		$win.click(function(e){

			var elem = $(e.target);
			console.log(elem.closest('#fullscreen-container').length);
			savePlayerPos();

	 

			var reset = reset_popups(elem);
 
			if(!active) return;
			if(e.which != 1) return;
			if(elem.closest('.img').length) return;
			//if($(e.target).attr('name') == 'expandfunc') return;
			if(elem.closest('#fullscreen-container').length) return;

			if(reset)
			{
				hide();
			} 

		});

		$win.on(is_firefox ? "DOMMouseScroll" : "mousewheel", function(e){

			if($('.plyr:hover').length == 1)
			{
				var evt1 = window.event || e; //equalize event object
				evt1 = evt1.originalEvent ? evt1.originalEvent : evt1; //convert to originalEvent if possible
				var delta1 = evt1.detail ? evt1.detail*(-40) : evt1.wheelDelta; //check for detail first, because it is used by Opera and FF
		
				resizeAudioPLayer(delta1);
				return false;
			}

			if(!active) return;
			if(!mouse_on_container) return;
			e.preventDefault();
			var evt = window.event || e; //equalize event object
			evt = evt.originalEvent ? evt.originalEvent : evt; //convert to originalEvent if possible
			var delta = evt.detail ? evt.detail*(-40) : evt.wheelDelta; //check for detail first, because it is used by Opera and FF
	
			if(delta > 0) {
				resize(multiplier+0.1);
			}
			else{
				resize(multiplier-0.1);
			}
		});
	

		draggable($container, {
			click: function(){
				hide(); 
			},
			mousedown: function(e_x,e_y){
				if(!video) return; //@todo упаковать типы
				var container_top = parseInt($container.css('top'));
				var container_height = $container.height();
	
				if((container_top+container_height) - e_y < 35) return false;
			}});
		

})


function $id(id) {
	return document.getElementById(id);
}

window.fullscreenExpand = function(num, src, thumb_src, image_width, image_height, cloud, youtube_id=null, timecode=0) {
	abortWebmDownload();
	if(active == src) {
		hide();
		 
		return false;
	}


	//$container = $('<div id="fullscreen-container"></div>');
	//$('body').append($container);

	youtube_iframe = false;
	var win_width = $win.width();
	var win_height = $win.height();

	img_width = image_width;
	img_height = image_height;
	multiplier = 1;
	active = src;
	webm = src.substr(-5) == '.webm';
	mp4 = src.substr(-4) == '.mp4';
	mp3 = src.substr(-4) == '.mp3';
	video = webm || mp4 || mp3;
	mouse_on_container = false;

	var divContainer = video?'<video id="html5video" onplay="webmPlayStarted(this)" onvolumechange="webmVolumeChanged(this)" name="media" loop="1" ' + ' controls="" autoplay="" height="100%" width="100%"><source class="video" height="100%" width="100%" type="' + (mp4?'video/mp4':'video/webm') +'" src="' + src + '"></source></video>':'<img src="' + src + '" width="100%" height="100%" />';

	if(youtube_id != null)
	{
		image_width = youtube_width;
		image_height = youtube_height;
		let timeArg = timecode == 0 ? "" : 'start='+timecode+'&';
		divContainer = '<iframe id="playvideo" width="'+image_width+'" height="'+image_height+'" src="https://www.youtube.com/embed/'+youtube_id+'?'+timeArg+'autoplay=1&rel=0&enablejsapi=1&origin=*" orig="'+src+'" frameborder="0" allowfullscreen></iframe>';

		divContainer+='<div class="video-overlay" style="position: absolute;left: 0;right: 0;top: 0;bottom: 50px"></div>';
		youtube_iframe = true;
	}
	else
	{
		if(video && is_firefox)
			divContainer+='<div class="video-overlay" style="position: absolute;left: 0;right: 0;top: 0;bottom: 50px;"></div>';
		else
			$('.video-overlay').remove();
	}


	$container
		.html(divContainer)
		//.append(!cloud?$controls:'')
		.css('top', (((win_height-image_height)/2) - border_offset) + 'px')
		.css('left', (((win_width-image_width)/2) - border_offset) + 'px')
		//.css('background-color', (cloud?'transparent':'#555555'))
		.width(image_width)
		.height(!mp3?image_height:'200px')
		.show();
	
	if(image_width > win_width || image_height > win_height) 
	{
		var multiplier_width =  ((win_width-2)/image_width);
		var multiplier_height = ((win_height-2)/image_height);

		resize(multiplier_width<multiplier_height ? multiplier_width : multiplier_height, true);
	}

	return false;
};

var hide = function() {
	abortWebmDownload();
	active = false;
	mouse_on_container = false;

	$container.hide();
	if(video) {
		$container.html('');
	}
};

var resize = function(new_multiplier, center)
{
	
	if(new_multiplier < 0.01) return;
	if(new_multiplier > 5) return;
	
 
	if(youtube_iframe)
	{
		repos(new_multiplier, center);
		multiplier = new_multiplier;

		var container = $('#fullscreen-container');
		var iframe = $('#fullscreen-container').find('iframe');

		container.width(youtube_width * multiplier).height(youtube_height * multiplier);
		iframe.width(youtube_width * multiplier).height(youtube_height * multiplier);


	}
	else
	{
		repos(new_multiplier, center);
		multiplier = new_multiplier;

		$container.width(img_width * multiplier).height(img_height * multiplier);
	}

	savePlayerPos();
	
};

var repos = function(new_multiplier, center) {
	var scroll_top = $win.scrollTop();
	var scroll_left = $win.scrollLeft();
	var container_offset = $container.offset();
	var x_on_container;
	var y_on_container;
	if(center) {
		x_on_container = img_width/2;
		y_on_container = img_height/2;
	}else{
		x_on_container = (container_mouse_pos_x-container_offset.left+scroll_left);
		y_on_container = (container_mouse_pos_y-container_offset.top+scroll_top);
	}
	var container_top = container_offset.top-scroll_top;
	var container_left = container_offset.left-scroll_left;
	var delta_multiplier = new_multiplier-multiplier;
	var delta_top = delta_multiplier*y_on_container/multiplier;
	var delta_left = delta_multiplier*x_on_container/multiplier;

	$container
		.css('left', (container_left-delta_left) + 'px')
		.css('top', (container_top-delta_top) + 'px');
};

$container.mouseover(function(){
	mouse_on_container = true;
});

$container.mouseout(function(){
	mouse_on_container = false;
});

$container.mousemove(function(e){
	container_mouse_pos_x = e.clientX;
	container_mouse_pos_y = e.clientY;
});

//операции с вебм
function abortWebmDownload() 
{ 
	
	$('#fullscreen-container').html('');


    var el = $("#html5video");
    if(!el.length) return;

    var video = el.get(0);
    video.pause(0);
    video.src = "";
    video.load();
    el.remove();
}

function webmPlayStarted(el) 
{
	var video = $(el).get(0);

	try
	{
		video.volume = getKey('webm_vol', 0.5);
		video.muted = getKey('webm_muted', false);
	}
	catch(err)
	{

	} 
}

function webmVolumeChanged(el) 
{
	var video = $(el).get(0);
	
	setKey('webm_vol', video.volume);
	setKey('webm_muted', video.muted);
	
}

function expand(num, src, thumb_src, n_w, n_h, o_w, o_h, minimize,cloud, event) 
{

	$('.video-overlay').remove();
	youtube_iframe = false;
	var spoiler = $(event.target);

	if(spoiler.length != 0 && spoiler.hasClass('spoilered'))
	{ 
		spoiler.attr('src', spoiler.parent().attr('href'));
		spoiler.removeClass('spoilered');
		return false;
	}


    var $win = $(window);
    if($win.width() < 320 || $win.height() < 320) return;
    if(!minimize && !window.expand_all_img) return fullscreenExpand(num, src, thumb_src, n_w, n_h,cloud);
	
    /*******/
    var element = $('#exlink-' + num).closest('.images');
    if(element.length) {
        if(element.hasClass('images-single')) {
            element.removeClass('images-single');
            element.addClass('images-single-exp');
        }else if(element.hasClass('images-single-exp')) {
            element.addClass('images-single');
            element.removeClass('images-single-exp');
        }
    }
    //todo screen был не так и плох
	var win_width = $win.width();
    var win_height = $win.height();
	var k = n_w/n_h;
	
    if(n_w > win_width || n_h > win_height){
        n_h = win_height - 10;
        n_w = n_w*k;
    } 
    var filetag, parts, ext;
    parts = src.split("/").pop().split(".");
    ext = (parts).length > 1 ? parts.pop() : "";
    if (((ext == 'webm') || (ext == 'mp3') || (ext == 'mp4')) && n_w > o_w && n_h > o_h) {
		closeWebm = $new('a',
		{
			'href': src,
			'id': 'close-webm-' + num,
			'class': 'close-webm',
			'html': '[Закрыть]',
			'onclick': ' return expand(\'' + num + "\','" + src + "','" + thumb_src + "'," + o_w + ',' + o_h + ',' + n_w + ',' + n_h + ', 1);'
		});
		refElem = $id('title-' + num);
		refElem.parentNode.insertBefore(closeWebm, refElem.nextSibling);
		$('#exlink-' + num).prev().css('width','auto');
		if(ext == 'mp3') {
			filetag = ' <audio controls><source src="' + src + '" type="audio/mpeg"></audio> ';
		} else {
			filetag = '<video id="html5video" onplay="webmPlayStarted(this)" onvolumechange="webmVolumeChanged(this)" controls="" autoplay="" width="' + n_w + '" height="' + n_h + '"' + ' loop="1" name="media"><source src="' + src + '" type="video/webm" class="video" ></video>';
		}
		
	} else {
		if (ext == 'webm') {
			var el = document.getElementById('close-webm-' + num);
			el.parentNode.removeChild(el);
		}
        filetag = '<a href="' + src + '" onClick="return expand(\'' + num + "\','" + src + "','" + thumb_src + "'," +
            o_w + ',' + o_h + ',' + n_w + ',' + n_h + ',' + (minimize?0:1) + ',' + cloud + ');"><img src="' + (!minimize ? src : thumb_src) + '" width="' + n_w + '" height="' + n_h + '" class="img ' + (!minimize ? 'fullsize' : 'preview') +  ((ext=='webm') ? ' webm-file' : '') + '" /></a>';
		if(minimize && Store.get('other.expand_autoscroll', true)) {
            var post = Post(num);
            var post_el;
            if(post.isRendered()) {
                post_el = post.el();
            }else{
                post_el = $('#preview-' + parseInt(num));
            }

            var doc = $(document);
            var pos = post_el.offset().top;
            var scroll = doc.scrollTop();

            if(scroll > pos) doc.scrollTop(pos);

        }
    }
    $id('exlink-' + num).innerHTML = filetag;
    return false;
}


function draggable(el, events) {
    var in_drag = false;
    var moved = 0;
    var last_x, last_y;

    var win = $(window);

    el.mousedown(function(e){ 


        if(e.which != 1) return;
		if(events && events.mousedown && events.mousedown(e.clientX, e.clientY) === false) return;
		
		
        e.preventDefault();
        in_drag = true;
        moved = 0;

        last_x = e.clientX;
		last_y = e.clientY;
		
    });

    win.mousemove(function(e){


		
        var delta_x, delta_y;
        var el_top, el_left;

		if(!in_drag) return;
		

        delta_x = e.clientX-last_x;
        delta_y = e.clientY-last_y;
        moved += Math.abs(delta_x) + Math.abs(delta_y);

        last_x = e.clientX;
        last_y = e.clientY;

        el_top = parseInt(el.css('top'));
        el_left = parseInt(el.css('left'));

        el.css({
            top: (el_top+delta_y) + 'px',
            left: (el_left+delta_x) + 'px'
		});
		
		
	});
	

    win.mouseup(function(e) {
        if(!in_drag) return;
		in_drag = false;
		
		if(moved < 6 && events && events.click) {
		
			//events.click(last_x, last_y); // crash chrome
	
			active = false;
			mouse_on_container = false; 
	
			setTimeout(hide, 50);
		}

    });
}



function loadPlayerPos()
{

	var iframe = $('#fullscreen-container').find('iframe');

	if(iframe.length != 0)
	{
		var window_width = $(window).width();
		var window_height = $(window).height();
		var coords = getKey('youtube_player_pos', null); 

		if(coords != null && coords.length == 7 && coords[0] == window_width && coords[1] == window_height)
		{

			img_width=coords[2];
			img_width=coords[3];
			multiplier=coords[6];

	
			iframe.width(coords[2]);
			iframe.height(coords[3]);
			$("#fullscreen-container").width(coords[2]).height(coords[3]).css({top: coords[4], left: coords[5]});
		}
	}


}

function savePlayerPos()
{
	var iframe = $('#fullscreen-container').find('iframe');

	if(iframe.length != 0)
	{	
		var fpos = $('#fullscreen-container').position();

		var window_width = $(window).width();
		var window_height = $(window).height();
		var iframe_width = iframe.width();
		var iframe_height = iframe.height();
	 
		setKey('youtube_player_pos', [window_width , window_height , iframe_width , iframe_height, fpos.top, fpos.left, multiplier]);
	}

}







