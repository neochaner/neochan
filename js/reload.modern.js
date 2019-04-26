
var autoLoadSecDefault=5;
var autoLoadSec=5;
var autoLoadSecCurrent=autoLoadSec;
var fullReload=10;
var post_count=0;
var post_count_max=250;
var POST_AUTO_SCROLL = false;
var POST_AUTO_SCROLL_BOTTOM = 300;


var mStoreCounter=0;
var mStore={}; 

$(document).ready(function(){


	if(config.active_page != 'thread')
		return;
	

	setInterval(function(){ autoLoadCycle() }, 1000);

	uStore = {};

	let posts = document.getElementsByClassName('post');
	let postID, eTime, postTime;
			
	for(let i=0;i<posts.length;i++){
		postID = posts[i].getAttribute('id');
		eTime = posts[i].getElementsByTagName("time");
					
		if(eTime.length > 0){
			postTime = eTime[0].getAttribute('edit');
			mStore[postID] = [postTime, mStoreCounter];
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

	$.ajax({
		url: document.location
	}).done(function(data) {

		mStoreCounter++;

		var virtualDocument = document.implementation.createHTMLDocument('vdoc');
		var html = virtualDocument.createElement('html');
		html.innerHTML = data;
		
		let posts = html.getElementsByClassName('post');
		
		for(let i=0, l=posts.length;i<l; i++){
			
			let post = posts[i];
			let postID = post.getAttribute('id');
			let time = post.getElementsByTagName('time');
			
			if(time.length != 1){
				console.log('WARNING!');
				continue;
			}
			
			let editTime = time[0].getAttribute('edit');

			if(!mStore.hasOwnProperty(postID)){
				console.log("new post! " + postID);
				mStore[postID] = [editTime, mStoreCounter];
				
				let last = $('article.post:not(.hover):last')[0];
				insertAfter(post, last);
				
				$(document).trigger('new_post', post);
				postAutoCroll();
				continue;
			}
			else if(mStore[postID][0] != editTime){
				console.log("changed post! " + postID);
				mStore[postID] = [editTime, mStoreCounter];
				
				let oldPost =document.getElementById(postID);
				insertAfter(post, oldPost);
				oldPost.parentElement.removeChild(oldPost);
				
				reloadControls();
				$(document).trigger('change_post', post);
				postAutoCroll();
				continue;
			} else {
				mStore[postID] = [editTime, mStoreCounter];
			}
			
	
			
			
		}	
		
		/* detect deleted posts 

			 DISABLED  IN  MODERN MODE
		
		for(var key in mStore){
			if(mStore[key][1] != mStoreCounter){
				let dPost = document.getElementById(key);
				if(dPost == null)
					console.log('WARNING!');
				else{
					delete mStore[key];
					dPost.parentElement.removeChild(dPost);
				}
			}
		}*/

	}).fail(function(jqXHR, textStatus, errorStatus) {
		
		infoAlert(textStatus + " : " + errorStatus);

		if(jqXHR.status == 503) // may be Cloudflare under attack mode on
		{
			setTimeout(window.location.reload.bind(window.location), 3000);
		}

	});

}

function insertAfter(newNode, referenceNode) {
    referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}

function addNewPost(post, createTime)
{

	


}


// call after submit ajax.js
function updatePost(post, forceReplace = false)
{

	if(post.hasOwnProperty('template')){
		post = $(post.template)[0];
	}


	let postID = post.getAttribute('id');
	let time = post.getElementsByTagName('time');
			
	if(time.length != 1){
		console.log('WARNING!');
		return;
	}
	
	let editTime = time[0].getAttribute('edit');

	if(!mStore.hasOwnProperty(postID)){
		console.log("new post! " + postID);
		mStore[postID] = [editTime, mStoreCounter];
		
		let last = $('article.post:not(.hover):last')[0];
		insertAfter(post, last);
		
		$(document).trigger('new_post', post);
		postAutoCroll();
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
 
 
 
 