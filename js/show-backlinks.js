/*
 * show-backlinks.js
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   // $config['additional_javascript'][] = 'js/post-hover'; (optional; must come first)
 *   $config['additional_javascript'][] = 'js/show-backlinks.js';
 *
 */
var optionDisableHideStyleKey = 'optionDisableHideStyle';
var optionDisableHideStyleValue = false;
var BLCache = {};

$(document).ready(function() 
{
	

	optionDisableHideStyleValue = menu_add_checkbox(optionDisableHideStyleKey, false, 'l_DisableHideStyle');
 
	//$('article.post').each(showBackLinks);

    $(document).on('new_post', function(e, post) {

		if ($(post).hasClass("post")) 
		{
			showBackLinks.call(post);
		}
		else {
			$(post).find('.article.post').each(showBackLinks);
		}
	});


});

	 
$(document).on(optionDisableHideStyleKey, function(e, value) {	
	optionDisableHideStyleValue = value;

	filter_reload(); 
})

function showBackLinksNew() 
{

	$spost = $(this);
	var reply_id = this.dataset.post;
	var needHide = undefined;

 
	// search backlinks in post
	$spost.find('section.post-body a:not([rel="nofollow"])').each(function() {

		let text = $(this).text().match(/^>>(\d+)($|\s)/);
		if(!text)
			return;

		let id = text[1];
		$post = get_reply(id);
		if($post.length == 0)
			return;

		
		if(needHide == undefined){
			 
			if(!optionDisableHideStyleValue){
				var div_trip = $spost.find('.post-trip');
				var trip = div_trip.text();
				var is_anonymous = div_trip.length == 0;
				var is_oppost = this.classList.contains('post_op'); 
				needHide = !is_oppost && store.hideCheck(this.dataset.board, this.dataset.thread, this.dataset.post, is_anonymous, trip);
			} else {
				needHide = false;
			}


		}


		let key = $post[0].dataset.board + '_' + $post[0].dataset.post;
		
		if(!BLCache.hasOwnProperty(key)){
			BLCache[key]=[this.dataset.board, this.dataset.post];
		} else {

			let contains=false;

			for(let i=0; i<BLCache[key].length; i+=2){
				if(BLCache[key][i] == this.dataset.board && BLCache[key][i+1] == this.dataset.post)
					contains=true;
			}

			if(!contains)
				BLCache[key].push(this.dataset.board, this.dataset.post);

		}

	});




}

function showBackLinks() 
{


	$spost = $(this);
	var reply_id = this.dataset.post;

	var needHide = false;

	if(!optionDisableHideStyleValue){
		var div_trip = $spost.find('.post-trip');
		var trip = div_trip.text();
		var is_anonymous = div_trip.length == 0;
		var is_oppost = this.classList.contains('post_op'); 
		needHide = !is_oppost && store.hideCheck(this.dataset.board, this.dataset.thread, this.dataset.post, is_anonymous, trip);
	}


	$spost.find('section.post-body a:not([rel="nofollow"])').each(function() 
	{
		var id, post, $mentioned;
	
		if(id = $(this).text().match(/^>>(\d+)($|\s)/))
			id = id[1];
		else
			return;

	
		$post = get_reply(id);

		if($post.length == 0)
			return;
			
	
		$mentioned = $post.find('.post-backlinks');


		var boLink = '';
 
		if(needHide)
			boLink = ' a-off';
		

		let blink = '<a class="post-link'+boLink+'" data-id='+reply_id+' onclick="highlightReply(\'' + reply_id + '\', event);" href="#' + reply_id + '">&gt;&gt;' + reply_id + '</a>  ';
		let blinks =  $mentioned[0].innerHTML;
		 
		 
		if(!blinks.includes(blink)){

			var $link = $(blink);
			$link.appendTo($mentioned);
		}
		
		 
		if (window.init_hover) {
			$link.each(init_hover);
		}
	});
}

function reloadBacklinks()
{
	$('.post-backlinks').empty();
	$('article.post').each(showBackLinks);
} 


 

 














