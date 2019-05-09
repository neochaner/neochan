
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

var uStore={

	'b':	// board
	[0,		// - last time
	0,		// - last id
	0]		// - min post id
	,
	'kpop':[0,0,0],
	'mu':[0,0,0],
	'jp':[0,0,0],

 }; 

$(document).ready(function(){


	if(config.active_page != 'thread')
		return;
	

	setInterval(function(){ autoLoadCycle() }, 1000);

	uStore = {};

	let posts = document.getElementsByClassName('post');
	let boardUri, postID, eTime, postTime;
			
	for(let i=0;i<posts.length;i++){

		boardUri =  posts[i].dataset.board;
		postID = posts[i].dataset.board+'_'+posts[i].dataset.thread+'_'+posts[i].dataset.post;
		eTime = posts[i].getElementsByTagName("time");

		if(!uStore.hasOwnProperty(boardUri)){
			uStore[boardUri] = [0, 0, 0];
		}
					
		if(eTime.length > 0){
			postTime = eTime[0].getAttribute('edit');
			uStore[ posts[i].dataset.board] = [postTime,  posts[i].dataset.post, 0];
		}
	}

});


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



	let argBoards='';
	let argLastTimes='';
	let argLastIds=''; 

	for(let key in uStore){
		argBoards = argBoards + (argBoards.length ? ',' : '') + key;
		argLastTimes = argLastTimes + (argLastTimes.length ? ',' : '') + uStore[key][0] ;
		argLastIds = argLastIds + (argLastIds.length ? ',' : '') +  uStore[key][1] ;

		if(fullReloadPage){
			argLastTimes = 0;
			argLastIds = 0;
		}
	}
	
	var uripath = window.location.origin + '/recent_v2.php?'+
	'board=' + config.board_uri + 
	'&thread=' + $('#thread_id').data('id') + 
	'&post=' + argLastIds + 
	'&time=' + argLastTimes+
	'&active_page=' + config.active_page +
	'&neotube=' + NTUBE_STATE;

	if(getKey('disableModPosts', false))
		uripath += '&disable_mod';


	$.ajax({
		url: uripath
	}).done(function(data) {

	
		if(data.length < 5){	
			console.log('update empty');
			return
		}

		let result = JSON.parse(data);

		if(result.post_len > 0){

			let posts = sortPosts(result.posts);

			for(let i=0; i<posts.length;i++)
				updatePost(posts[i]);
		}

		if(NTUBE_STATE > 0 && typeof result.playlist != 'undefined'){
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

/* сортируем посты по времени создания */
function sortPosts(posts)
{

	for (let i = 0, end_i = posts.length - 1; i < end_i; i++) 
	{
		for (let j = 0, end_j = end_i - i; j < end_j; j++) 
		{
			if (posts[j].time > posts[j + 1].time) 
			{
				var tmp = posts[j];
				
                posts[j] = posts[j + 1];
                posts[j + 1] = tmp;
            }
        }
	}

	return posts;
}

function addNewPost(post, createTime)
{

	if(post == '')
		return false;


	// check double
	let dpost = $(post);
	let dclass = 'post_' + dpost[0].dataset.board + '_' + dpost[0].dataset.thread + '_' + dpost[0].dataset.post;
	let dcheck = document.getElementsByClassName(dclass);

	if(dcheck.length > 0){
		dcheck[0].parentNode.removeChild(dcheck[0]);
	}


	let posts = $('.post').not('.hover');// document.querySelectorAll('.post');
	
	for(let i=posts.length-1; i>=0;i--){
		
		let curTime =  posts[i].getElementsByTagName('time')[0].getAttribute('unixtime');
		
		if(createTime > parseInt(curTime))
		{

			var nPost = $(post).insertAfter(posts[i]).addClass('new_post');
			$(document).trigger('new_post', nPost[0]);
		
			postAutoCroll();
			return true;
		}
		
	}


}

function updatePost(post, forceReplace = false)
{

	let time =  post.changed_at;
	let postID = 'post_' + post.board + '_' + post.thread + '_' + post.id ;
	let oldPost = $('.'+ postID).not('.hover');
	let oldPostTime = 0;
	
	if(!forceReplace && oldPost.length ==1)
	{
		oldPostTime = oldPost.find('time')[0].getAttribute('edit');
	}


	if(!forceReplace && uStore[post.board][2] >= post.id)
		return false;

			
	// hide or deleted
	if(post.template == null)
	{

		if(oldPost.length != 0 && !enable_devil)
		{
			console.log('update: post '+postID+' hide or deleted');
			oldPost.remove();
			reloadControls();
		}

	}
	// new post
	else if(oldPost.length == 0)
	{
		console.log('update: new post '+ postID);
		addNewPost(post.template, post.time);
	}
	// changed post
	else if(oldPostTime < time)
	{

		uStore[post.board][0] = time;
		uStore[post.board][1] = post.id;

		console.log('Post #' + postID + ' changed'); 
			
		var nPost = $(post.template).insertAfter(oldPost);
		oldPost.remove();
		reloadControls();
		$(document).trigger('change_post', nPost[0]);

		postAutoCroll();

	}

	
	if(time > uStore[post.board][0])
	{
		uStore[post.board][0] = time;
		uStore[post.board][1] = post.id;
	}
		
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
 
 
 
 