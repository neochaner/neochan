

console.log("favMain");
Api.events.register('ready', favMain);
Api.events.register('load-post', addFavBtn);

if(config.active_page=='thread') {
	
}

var favStore = {};
var favEl;	

function favMain(obj){

	favStore = store.getKey('favorites', {});
	favEl = Api.addMenuIcon('heart-o', toggleFavPanel, 'Favorites');
	
	let el = document.createElement('div');
	el.className="modal";
	el.id="fav-panel";
	el.style.display="none";
	el.style.minWidth="210px"; 
	
	el.innerHTML="<span>Favorites</span><hr><div id='fav-items'></div><br>";
	
	document.getElementsByClassName('modal-container')[0].appendChild(el);
	
	let keys = Object.keys(favStore);
	for(let i=0, l=keys.length; i<l;i++) {
		updFav(favStore[keys[i]]); 
	}
	
	setInterval(updFavItems, 5000);
}

function buildFavBtn(board, thread){
	
	let id = 'favc-'+board+'_'+thread;
	let bid = 'favb-'+board+'_'+thread;
	let args = "'"+board+"', "+thread;

	if(isFav(board, thread)){
		return '<i id="'+id+'" class="fa fa-star" onclick="remFav('+args+')" style="margin-left:10px;cursor:pointer"></i>';
	} else {
		return '<i id="'+id+'" class="fa fa-star-o" onclick="addFav('+args+')" style="margin-left:10px;cursor:pointer"></i>';
	}
}

function addFavBtn(postObj) {

	if(postObj.op) {
		let head = $(postObj.el).find('.post-header')[0];
		let func = isFav(postObj.board, postObj.num) ? 'remFav' : 'addFav';
		
		$(head).append(buildFavBtn(postObj.board, postObj.thread));
	}
}

function isFav(board, thread){
	
	let keys = Object.keys(favStore);
	
	for(let i=0, l=keys.length; i<l;i++) {
		if(favStore[keys[i]].board == board && favStore[keys[i]].thread == thread)
			return true;
	}
	
	return false;
}

function addFav(board, thread){
	
	// get thread name
	let key = board+'_'+thread;
	let id = 'favc-'+key;
	let el = document.getElementById(id);
	
	let subject = $(el.parentNode).find('.post-subject').text();
	let lastID = $(el).closest('.thread').find('.post').last()[0].dataset.post;
	
	
	favStore[key] = {board: board, thread: thread, subject: subject, last: parseInt(lastID), miss: 0};
	store.setKey('favorites', favStore);
	
	if(el) {
		$(el).after(buildFavBtn(board, thread));
		el.parentNode.removeChild(el);
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

	
	$(favEl).find('.badge')[0].innerHTML= newTotal ==0? '': newTotal;

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
	
	if(favStore.length == 0){
		$('.fav-menu').remove();
	}
}


function toggleFavPanel() {

	let panel = document.getElementById('fav-panel');

	if(panel.style.display != 'none') {
		panel.style.display = 'none'
		return;
	}
	
	let modals = document.querySelectorAll('.modal-container>div');
	
	for(let i=0, l=modals.length; i<l;i++)
		modals[i].style.display='none';

	panel.style.display = 'block';

}

function updFavItems() {
	
	let el = document.getElementById("fav-items");
	let keys = Object.keys(favStore);
	
	for(let i=0, l=keys.length; i<l;i++) {
		let key = keys[i];
		let url = '/'+favStore[key].board+'/res/'+favStore[key].thread+'.json';
		
		
		console.log('Fetching '+url+'...');
		 
		$.ajax({
		url: url,
		cache: false,
		contentType: false,
		processData: false,
		dataType: 'json'
		}).done(function(response) {

			console.log(response);	

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































