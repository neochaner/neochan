/*
 * ajax.js
 */
var do_not_ajax = false;

$(window).bind('load', function()
{
	$('#replybox_form').attr('action', 'javascript:void(null);');
	$('#replybox_form').attr('onsubmit', 'replybox_submit(this)');


});


function rating(e, value){

	e.preventDefault();

	let el = e.target.tagName == 'I' ? e.target.parentElement : e.target;
	let rateValue = el.parentElement.parentElement.querySelector('.rate-value');
	let url = el.href + '&json_response=1'
	
    $.ajax({
		url: url,
        type: 'GET',
        contentType: 'multipart/form-data',
		success: function(response, textStatus, xhr) {
			if(response.success)
				rateValue.innerText = parseInt(rateValue.innerText) + value;
            if(response.error)
				lalert(response.error);
			if(response.message)
				lalert(response.message); 
		},
		error: function(xhr, status, er) {
			alert(_T('Server error: ') + er);
		},
		contentType: false,
		processData: false
	}, 'json');
 
	return false;
}

function strip_tags(html)
{
	var div = document.createElement("div");
	div.innerHTML = html;
	return div.innerText;
}

function replybox_submit(form) {


	// save trip
	if (form.elements['neoname']) {
		localStorage.name = form.elements['neoname'].value.replace(/( |^)## .+$/, '');
	}
	
	$(form).find('.reply-send-button').attr('disabled','disabled');

	var submit_txt = $(form).find('.reply-send-button').val();
	var formData = new FormData(form);
	let fboard = formData.get('board');
	var dontResetForm = false;

	var btn_text = (typeof(NTUBE_STATE) !== 'undefined' && NTUBE_STATE >= 1) ? '..' : _T('Wait') ;

	formData.append('json_response', '1');
	formData.append('ticks', getServerTime());
	formData.append('post', submit_txt);

	$(document).trigger("ajax_before_post", formData);

	var updateProgress = function(e) {
		var percentage;

		if (e.position === undefined) { // Firefox
			percentage = Math.round(e.loaded * 100 / e.total);
		} else { // Chrome?
			percentage = Math.round(e.position * 100 / e.total);
		}
 
		$(form).find('.reply-send-button').val((_T(btn_text)+'... (#%)').replace('#', percentage));

	};

	$.ajax({
		url: config.root + 'post.php?neo23',
		type: 'POST',
		xhr: function() {
			var xhr = $.ajaxSettings.xhr();

			if(xhr.upload)
				xhr.upload.addEventListener('progress', updateProgress, false);

			return xhr;
		},
		data: formData,
		cache: false,
		contentType: false,
		processData: false,
		dataType: 'json'
	}).done(function(response) {
 

		if(response.id) {

			if(!Api.own_posts.hasOwnProperty(fboard)) {
				Api.own_posts[fboard]=[];
			}

			Api.own_posts[fboard].push(parseInt(response.id));
			localStorage.own_posts = JSON.stringify(Api.own_posts);			
		}

		if (response.banned) {

			let start = response.page.indexOf("><body>") +7;
			let end = response.page.indexOf("></body>") + 1;
			let length = end - start;
			let body =   response.page.substr(start, length);

			$('main').html(body);
		} 
		else if(response.l_captcha_mistype) 
		{  
			resetCaptcha();
			lalert('l_captcha_mistype'); 
			dontResetForm=true;
		}
		else if(response.need_antispam_check)
			alert(response.need_antispam_check);
		else if(response.error) 
		{
			dontResetForm=true;
			response.banned ? alert(response) : alert(response.error);

		} // bAnned
		else if(response.replace_main){
			document.getElementsByTagName('main')[0].innerHTML=response.replace_main;

			$('.l_banexpires').each(function(){
				let sec =  parseInt(this.dataset.bansec);
				let expires = new Date();
				expires.setSeconds(expires.getSeconds()+sec);

				this.innerHTML = expires.toLocaleString();
			});
		}
		else if(response.redirect && response.id) 
		{
			if(config.active_page == 'index'){
				document.location = response.redirect;
			}
			else if(response.template && response.creation_time)
			{
				autoLoadSecCurrent=0;

				// force replace
				for(let i=0,l=Api.postStore.length; i<l;i++) {
					if(Api.postStore[i].id == response.id)
						delete Api.postStore[i];
				}

				Api.noticeNewPost($(response.template)[0]);
			}
			else { 
				autoLoad(true);
			}

			resetCaptcha(false);
			 
		} else {
			alert('Произошла ошибка во время отправки поста');
		}
 

	}).fail(function(jqXHR, textStatus, errorStatus) {

		infoAlert(textStatus + " : " + errorStatus);

		if(jqXHR.status == 503) // may be Cloudflare under attack mode on
		{
			setTimeout(window.location.reload.bind(window.location), 3000);
		}

	}).always(function() {
 

		if(!dontResetForm)
		{	
			$(document).trigger('clear_post_files');
			$(form).find('.files-container').empty();
			$('#replybox_text').val('');

			let replbox_not_float = $('#replybox').css('position') != 'fixed';
			let neotube_disabled = (typeof(NTUBE_STATE) !== 'undefined' || NTUBE_STATE == 0);

			if(replbox_not_float && neotube_disabled){
				$('#replybox').fadeOut(500); 
			}
		}
		 
		$(form).find('.reply-send-button').val(_T('Send'));
		$(form).find('.reply-send-button').removeAttr('disabled');
 
		
	});


}




function resetCaptcha(reset_image = true){

	var text = $('.captcha_text');

	if(text.length > 0){

		text[0].value = '';
		
		if(reset_image){
			var iframe = document.getElementById('captcha-iframe');
			iframe.src = iframe.src;
		}
	}
} 

























