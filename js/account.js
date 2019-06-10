
/*
 global: Api, config, store


*/
var elAccountMenu = false;
var idAccountPanel = 'account-panel';
var idAccountItems = 'account-panel-items';

Api.addTranslate({
	en: 'Authorization',
	ru: 'Вход',
	pl: 'Autoryzacja',
	de: 'Autorisierung',
	ko: '로그인',
	jp: 'ログイン'
});


Api.onLoadPage(accountMain);


function accountMain(){

    // create account icon and modal panel 

    elAccountMenu = Api.addMenuIcon('user', toggleAccountMenu, _T('Authorization'));
    
    $before($id('btn-login'), elAccountMenu);
    $remove('btn-login');


	let el = document.createElement('div');
	el.className="modal";
	el.id= idAccountPanel;
	el.style.display="none";
	el.style.minWidth="160px"; 
	el.innerHTML="<span>"+_T('Authorization')+"</span><hr><div id='"+idAccountItems+"'></div><br>";
	
	document.getElementsByClassName('modal-container')[0].appendChild(el);

}



function toggleAccountMenu() {

	let panel = $id(idAccountPanel);

	if(panel.style.display != 'none') {
		panel.style.display = 'none'
		return;
	}
	
	let modals = document.querySelectorAll('.modal-container>div');
	
	for (let i=0, l=modals.length; i<l;i++) {
		modals[i].style.display='none';
    }
    
    panel.innerHTML = '<img id="login-progress" src="/stylesheets/img/progress-ani.gif" style="display: block;margin-left: auto;margin-right: auto;">';
    panel.style.display = 'block';
    

    api_request({'update_profile':1}, function(json){

        $remove('login-progress');

        if(!json.auth) {

            // show login modal
            panel.innerHTML = `

            <span>`+_T('Authorization')+`</span>
            <hr style="margin: 10px 0px 20px 0px;">
            


            <form action="/mod.php" id='login-form' method="POST">
            <aside style="padding: 0px 10px;">

                <label for="username" class="l_login_label" style="margin: 5px;font-size: 12px;"></label>
                <br>
                <input class="theme-textbox" name='username' style="margin: 5px; width: 150px;" type="username" id="luser">
                <p></p>
            
                <label for="password" class="l_passwd_label" style="margin: 5px;font-size: 12px;"></label>
                <br>
                <input class="theme-textbox" name='password' style="margin: 5px;width: 150px;" type="password" id="lpass">
                <br>
            
          
                <button type="submit" class="button l_btn_Login" style="text-align: center;margin: 10px;width: 150px;" name="login"></button>

            </aside>
         
            <!--
            <br><div id='login_captcha'></div>
            <br>
            <div style='padding:20px 2px 5px;text-align: center;'>
            <div class="l_login_enter button send-button" style="margin-right:14px" onclick='login()'></div>
            <div class="l_login_reg button send-button" onclick='register()'></div></div>
            !-->

            </form>`;



        } else {

            // show account panel
            document.getElementById(idAccountPanel).innerHTML = `
            
            <span id="profileName" style="font-family: system-ui;font-size: 16px;">`+json.username+`</span>
            <hr style="margin: 10px 0px 20px 0px;">

            <div class="account-tabs">
                <a id="account-link" class="l_Dashboard" href="/mod.php?/"></a>

                <a class="l_Messages" href="/mod.php?/inbox">
                    <span id="account-pm" class="pull-right alert-numb account-messages">0</span>
                </a>
            </div>

            <br>
            
            <div style="padding:20px 10px 5px;"><div class="l_login_out button send-button" onclick="accountExit()"></div>
            `;




        }



    });
}

function accountLogin(){
    document.getElementById("login-form").submit();
}


function accountExit(){


    api_request({'logout':true}, function(){

        $id(idAccountPanel).style.display = 'none';
        toggleAccountMenu();
    });
    
 

 
}









 




function api_request(array, func, link = '/api.php'){
	
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
		success: func,
		error: function(xhr, status, er) {
            
		},
		contentType: false,
		processData: false
	}, 'json');
	
}
