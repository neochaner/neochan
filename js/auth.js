var auth = null;
var tidProfileUpdate = null;

$(document).ready(function() {

	auth = getKey('auth', null);

	loadProfile();

	if(auth !== null && auth.type > 0 && !getKey('disableModPosts', false))
		autoLoad(true);


	$('#btn-login').click(function(){

		$('.modal').not('#profile').not('#login').hide();

		if(auth)
		{
			$('#login').hide();
			toggle('#profile');
		}
		else
		{
			$('#profile').hide();
			toggle('#login');
		}

	});

});



function login(){
	
	if(checkUsernamePass())
		api({ 'login':true, 'username' : el('luser').value, 'password': el('lpass').value});
}

function register(){
 
	if(checkUsernamePass()){

		let request = { 
			'register':true, 
			'username' : el('luser').value, 
			'password': el('lpass').value
		}

		let captcha = '';


		for(let i=-1; i<30; i++){

			let id = '#g-recaptcha-response' + ((i==-1) ? '': '-'+i.toString());
			let div = $('#login').find(id);

			if(div.length == 1)
			{
				request['g-recaptcha-response'] = div[0].value;
				break;
			}
		}

		api( request );
	 

	} 
}

function checkUsernamePass(){
	
	var user = el('luser').value;
	var pass = el('lpass').value;

	if(!user)
	{
		alert('Введите имя или трипкод');
		return false;
	}
	else if(user.length < 5){
		alert('Имя слишком короткое, минималная длинна - 5 символов.');
		return false;
	}
	else if(!pass || pass.length == 0)
	{
		alert('Введите пароль');
		return false;
	}

	return true;
}

function resetProfile(){
	
	auth = null;
	setKey('auth', null);

	$('#profile').hide();
	$('#login').show();

	clearTimeout(tidProfileUpdate);
	tidProfileUpdate = null;
}

function loadProfile(response = null){

	if(response)
	{ 
		if(response.auth)
		{
			auth = response;
			setKey('auth', response);
		}
		else
		{
			resetProfile();
		}
	}
	else{
		auth = getKey('auth');
	}



	if(auth){

		if(is_visible('#login') || is_visible('#profile'))
		{
			$('#login').hide();
			$('#profile').show();
		}

		$('#profileName').html(auth.username);

		// auto update profile (reports/messages/actions)
		if(tidProfileUpdate === null);
			tidProfileUpdate = setTimeout(updateProfile, 10000);
	}


}

function updateProfile(){

	api({'update_profile':true});
}



function logOut(){
	api({'logout':true});
}

 

function api(array, link = '/api.php'){
	
	var fdata = new FormData();
	fdata.append('json_response', '1');
	fdata.append('ticks', getServerTime());

	for(var index in array) {
		fdata.append(index, array[index]);
	}

	$.ajax({
		url: link,
        type: 'POST',
        data: fdata,
		success: function(response, textStatus, xhr) {

            if(response.alert)
                alert(_T(response.alert));
			if (response.error) 
				alert(_T(response.error));
			if (response.fail)              
				alert(_T(response.fail));

			if(response.captcha)  
				$('#login_captcha').html(response.data);
			if(response.login_fail)
				el('lpass').value = '';
			else if(response.login_success)
				loadProfile(response);
			else if(response.update_profile)
				loadProfile(response);
			else if(response.logout)
			{
				resetProfile();
				location.reload();
			}
			else if(response.invalid_password_1)
				alert('Пароль должен составлять 5 или более символов');
			else if(response.invalid_username_1)
				alert('Имя пользователя должно состоять только из букв и цифр');
			else if(response.invalid_username_2)
				alert('Имя пользователя должно составлять 5 или более символов');
			else if(response.invalid_username_3)
			{
				alert('Имя пользователя уже занято');
				el('luser').value = '';
			}
			 


	  
		},
		error: function(xhr, status, er) {
			alert(_T('Сервер вернул ошибку: ') + er);
		},
		contentType: false,
		processData: false
	}, 'json');
	
}

function toggleProfile(){



}

 
















