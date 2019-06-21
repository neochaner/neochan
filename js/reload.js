
var autoLoadSecDefault=5;
var autoLoadSec=5;
var autoLoadSecCurrent=autoLoadSec;
var fullReload=10;
var post_count=0;
var post_count_max=250;
var postStore = {};
var megaurl = "";

var POST_AUTO_SCROLL = false;
var POST_AUTO_SCROLL_BOTTOM = 300;

Api.onLoadPage(reloadMain, ['thread']);

function reloadMain() {

	if(location.hostname.endsWith('.onion')) {
		autoLoadSecDefault = 15;
		autoLoadSecCurrent = 15;
		autoLoadSec = 15;
	} else if(location.hostname.endsWith('.i2p')) {
		autoLoadSecDefault = 30;
		autoLoadSecCurrent = 30;
		autoLoadSec = 30;
	}
	
	setInterval(function(){ autoLoadCycle() }, 1000);
}


function infoAlert(text, time=3000)
{

	$('.alert-modal').remove();

	var div = "<div id='alert-modal' class='modal' style='display: block;padding:10px;margin-right:10px'><i class='fa fa-exclamation'></i><p style='margin-left:10px;display:inline'>"+text+"</p></div>";
	$('.modal-container').append(div);

	setTimeout(function(){$('#alert-modal').remove();}, time);
}

function autoLoadAll()
{
	fullReload=0;
	autoLoadSecCurrent=0;
}

function getAutoloadSecs() {
	if( is_mobile ) 
		return autoLoadSec*2;
	return autoLoadSec;
}

function autoLoadCycle()
{

	if(autoLoadSecCurrent-- <= 0)
	{

		$("#update_text").text(autoLoadSecCurrent);
		$('#update_text').hide();
		$('#spinner').show();
		autoLoad();

		autoLoadSecCurrent = getAutoloadSecs();
		
		setTimeout(function(){
		
			$('#spinner').hide();
			$('#update_text').show();
		
		}, 1000);

	}
	else
	{
		$("#update_text").text(autoLoadSecCurrent);
	}
}

function manualLoad(){
	autoLoadSecCurrent = 0;
	autoLoadCycle();
}


function autoLoad(fullReloadPage=false)
{


	let maxID =0;
	let maxEdit=0;

	for (let i=0, l=Api.postStore.length; i<l; i++) {

		if(maxID < Api.postStore[i].post)
			maxID = Api.postStore[i].post;
		if(maxEdit < Api.postStore[i].edit)
			maxEdit = Api.postStore[i].edit;
	}

	if(fullReloadPage){
		maxID = 0;
		maxEdit = 0;
	}

	var uripath = window.location.origin + '/recent_v2.php?'+
	'board=' + config.board_uri + 
	'&thread=' + Api.thread + 
	'&post=' + maxID + 
	'&time=' + maxEdit+
	'&neotube=' + NTUBE_STATE;


	$.ajax({
		url: uripath
	}).done(function(data) {

		if (data.length < 5) {
			console.log('update empty');
			return;
		}

		let result = JSON.parse(data);

		if (result.post_len > 0) { //????????

			let posts = result.posts;

			for (let i=0; i<posts.length;i++) {

				if (posts[i].template == null) {
					Api.noticeHidePost(posts[i].board, posts[i].id);
				} else {
					Api.noticeNewPost($(posts[i].template)[0]);
				} 
			}
		}

		if (NTUBE_STATE > 0 && typeof result.playlist != 'undefined') {
			neotubeUpdatePlayList(result.playlist);
		}
		
		
	}).fail(function(jqXHR, textStatus, errorStatus) {
		
		infoAlert(textStatus + " : " + errorStatus);

		if(jqXHR.status == 503) // may be Cloudflare under attack mode on
		{
			setTimeout(window.location.reload.bind(window.location), 3000);
		}

	});

}

function reloadControls()
{
	$('.post-edit-control').not('.post-mod-control').remove();
	reloadBacklinks();
	reloadOwnPosts();
}

function postAutoCroll(){

	if(!POST_AUTO_SCROLL)
		return;

	var bottomSize = $(document).height() - $(window).scrollTop() - window.innerHeight;
	bottomSize -= $('.board .post').last().height();

	if(bottomSize < POST_AUTO_SCROLL_BOTTOM){ 
		document.getElementById( 'footer' ).scrollIntoView();
	}
}
 
 
 
 