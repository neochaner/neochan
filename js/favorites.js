/*
 global: Api, config, store
*/

Api.onLoadPage(favMain);
Api.onLoadPost(addFavBtn);


Api.addTranslate({
	en: 'Favorites',
	ru: 'Избранное',
	pl: 'Ulubione',
	de: 'Favoriten',
	ko: '즐겨 찾기',
	jp: 'お気に入り'
});



var THREAD_UPDATE_TIMEOUT = 10;
var favStore = {};
var favEl;	

function favMain(){

	favStore = store.getKey('favorites', {});
	
	if(Object.keys(favStore).length !== 0){
		createFavMenu();
	}


	let keys = Object.keys(favStore);
	for(let i=0, l=keys.length; i<l;i++) {
		if(Api.isThreadPage && Api.thread == favStore[keys[i]].thread) {
			// if now in fav thread  
			Api.onLoadPost(favThreadCheck);
			Api.onNewPost(favThreadCheck); 

			
			favStore[keys[i]].miss = 0;
			store.setKey('favorites', favStore);
		}

		updFav(favStore[keys[i]]); 
	}
	
	setInterval(updFavThreads, 3000);
}

function favThreadCheck(obj) {

	let keys = Object.keys(favStore);
	for(let i=0, l=keys.length; i<l;i++) {
		if(
			favStore[keys[i]].board == obj.board && 
			favStore[keys[i]].thread == obj.thread &&
			favStore[keys[i]].last < obj.post)
		{
			favStore[keys[i]].miss = 0;
			favStore[keys[i]].last = obj.post;
			favStore[keys[i]].update = parseInt(new Date().getTime()/1000);

			store.setKey('favorites', favStore);
			updFav(favStore[keys[i]]);
		}	
	}
}

function createFavMenu() {
	
	favEl = Api.addMenuIcon('star-o', toggleFavMenu, _T('Favorites'));
	
	let el = document.createElement('div');
	el.className="modal";
	el.id="fav-panel";
	el.style.display="none";
	el.style.minWidth="210px"; 
	el.innerHTML="<span>"+_T('Favorites')+"</span><hr><div id='fav-items'></div><br>";
	
	document.getElementsByClassName('modal-container')[0].appendChild(el);
}

function toggleFavMenu() {

	let panel = $id('fav-panel');

	if(panel.style.display != 'none') {
		panel.style.display = 'none'
		return;
	}
	
	let modals = document.querySelectorAll('.modal-container>div');
	
	for (let i=0, l=modals.length; i<l;i++) {
		modals[i].style.display='none';
	}

	panel.style.display = 'block';
}



function buildFavBtn(board, thread, order) {
	
	let id = 'favc-'+board+'_'+thread;
	let args = "'"+board+"', "+thread;

	if(config.theme == 'native-makaba') {

		if(isFav(board, thread)){
			return '<i id="'+id+'" class="post-fav-rem" onclick="remFav('+args+')" style="order:'+order+';margin-left:10px;cursor:pointer"></i>';
		} else {
			return '<i id="'+id+'" class="post-fav-add" onclick="addFav('+args+')" style="order:'+order+';margin-left:10px;cursor:pointer"></i>';
		}
	}

	if(isFav(board, thread)){
		return '<i id="'+id+'" class="fa fa-star" onclick="remFav('+args+')" style="order:'+order+';margin-left:10px;cursor:pointer"></i>';
	} else {
		return '<i id="'+id+'" class="fa fa-star-o" onclick="addFav('+args+')" style="order:'+order+';margin-left:10px;cursor:pointer"></i>';
	}
}

function addFavBtn(postObj) {

	if(!postObj.op) {
		return;
	}
			
	let head = $(postObj.el).find('.post-header')[0];
	let func = isFav(postObj.board, postObj.num) ? 'remFav' : 'addFav';
	let btn ;
	switch(config.theme){
		case 'native-makaba':
			btn = buildFavBtn(postObj.board, postObj.thread, 8);
			break;		
		case 'native-lolifox':
			btn = buildFavBtn(postObj.board, postObj.thread, 8);
			break;
		
		default:
			btn = buildFavBtn(postObj.board, postObj.thread, 0);
	}
	

	$(head).append(btn);
}

function isFav(board, thread){
	
	let keys = Object.keys(favStore);
	
	for(let i=0, l=keys.length; i<l;i++) {
		if(favStore[keys[i]].board == board && favStore[keys[i]].thread == thread)
			return true;
	}
	
	return false;
}

function addFav(board, thread) {
	
	if(Object.keys(favStore).length == 0){
		createFavMenu();
	}
	
	// get thread name
	let key = board+'_'+thread;
	let id = 'favc-'+key;
	let starElem = document.getElementById(id);
	
	let subject = Api.getPost(board, thread).subject;
	let lastID = $(starElem).closest('.thread').find('.post').last()[0].dataset.post;
	let update = parseInt(new Date().getTime()/1000);

	
	favStore[key] = {board: board, thread: thread, subject: subject, last: parseInt(lastID), miss: 0, update: update};
	store.setKey('favorites', favStore);
	
	if(starElem) {
		$(starElem).after(buildFavBtn(board, thread));
		$remove(starElem);
	}
	
	updFav(favStore[key]);
}


function updFav(obj) {
	
	let key = obj.board +'_'+obj.thread;
	let id='fav-item-'+key;
	let item = document.getElementById(id);
	let html ='<span onclick="remFav(\''+obj.board+'\', '+obj.thread+')">[X]</span> <span>( '+obj.miss+' )</span> <a href="/'+obj.board+'/res/'+obj.thread+'.html">'+obj.subject+'</a>';
	let newTotal =0;
	
	if (item) {
		item.innerHTML=html;
	} else {
		
		item=document.createElement('div');
		item.className = 'fav-item';
		item.id=id;
		item.innerHTML=html;
		document.getElementById('fav-items').appendChild(item);
	}
	
	let keys = Object.keys(favStore);
	
	for(let i=0, l=keys.length; i<l;i++) {

		if(favStore[keys[i]].board == obj.board && favStore[keys[i]].thread == obj.thread ) {
			
			if(obj.hasOwnProperty('subject'))
				favStore[keys[i]].subject = obj.subject;
			if(obj.hasOwnProperty('last'))
				favStore[keys[i]].last = obj.last;
			if(obj.hasOwnProperty('miss'))
				favStore[keys[i]].miss = obj.miss;
			
			store.setKey('favorites', favStore);
		}

		newTotal += favStore[keys[i]].miss;		
	}

	
	$(favEl).find('.header-badge')[0].innerHTML = (newTotal === 0? '': ('+' + newTotal));

}

function remFav(board, thread) {
	let key = board+'_'+thread;
	let id = 'favc-'+key;
	let el = document.getElementById(id);
	
	delete favStore[key];
	store.setKey('favorites', favStore);

	if(el) {
		$(el).after(buildFavBtn(board, thread));
		el.parentNode.removeChild(el);
	}
	
	let item = document.getElementById('fav-item-'+key);
	item.parentNode.removeChild(item);
	

	if(Object.keys(favStore).length == 0){
		$remove(favEl);
		$remove('fav-panel');
	}
}
 

function updFavThreads() {
	
	let keys = Object.keys(favStore);
	let timeoutSec =  keys.length * THREAD_UPDATE_TIMEOUT;
	let currentSec = parseInt(new Date().getTime()/1000);

	for(let i=0, l=keys.length; i<l;i++) {

		let key = keys[i];

		if (favStore[key].update + timeoutSec > currentSec) {
			continue;
		}

		if (Api.isThreadPage && Api.thread == favStore[key].thread) {
			continue;
		}

		let url = '/'+favStore[key].board+'/res/'+favStore[key].thread+'.json';
		favStore[key].update = currentSec;
		store.setKey('favorites', favStore);
 
		console.log('Update favorites: '+url+'...');
		 
		$.ajax({
		url: url,
		cache: false,
		contentType: false,
		processData: false,
		dataType: 'json'
		}).done(function(response) {

			let upd= false;
			
			for(let j=0, ll=response.posts.length; j<ll;j++) {
				if(response.posts[j].no > favStore[key].last){
					favStore[key].last = response.posts[j].no;
					favStore[key].miss++;
					upd=true;
				}
			}
			
			if(upd) {
				updFav(favStore[key]);
			}

		}).fail(function(jqXHR, textStatus, errorStatus) {

			infoAlert(textStatus + " : " + errorStatus);

		}).always(function() {

		});
		
		
	}
}































