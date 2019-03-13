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







