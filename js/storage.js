/*
    Обёртка над localStorage, хранит:
    
    * любые типы данных [ в отличии от localStorage ] ( getKey, setKey )  
    * параметров скрытия постов ( isPost, addPost, delPost, isTrip, addTrip, delTrip, isAnon, enableAnon, disableAnon )
    * ссылки на оболожки аудио/видео ( getMedia, addMedia )


    Особенности:

    Функции ( getMedia, addMedia ) хранят ограниченное кол-во названий (this.maxTitles)
    (так как это самая часто используемая функция, память localStorage может быстро кончится)
    при добавлении новой строки - самая старая будет удалена 

*/

class Storage{
	
	constructor(){	
	
		this.keyTrips = 'hideTrips';
		this.keyAnon = 'hideAnon';
		this.keyThreads='hideThreads';
		this.keyPosts='hidePosts';
		this.keyTitles= 'cacheMedia';
		this.maxTitles = 1000;

		this.cache = {};

	
		this.cache[this.keyTrips] = localStorage.getItem(this.keyTrips);
    	this.cache[this.keyAnon] = localStorage.getItem(this.keyAnon);
		this.cache[this.keyTitles] = localStorage.getItem(this.keyTitles);
		this.cache[this.keyThreads] = localStorage.getItem(this.keyThreads);
		this.cache[this.keyPosts] = localStorage.getItem(this.keyPosts);
		
		if(this.cache[this.keyTrips] === null)
			this.cache[this.keyTrips] = null;
		else 
			this.cache[this.keyTrips] = JSON.parse(this.cache[this.keyTrips]);
		
	
		if(this.cache[this.keyAnon] === null)
			this.cache[this.keyAnon] = null;
		else 
			this.cache[this.keyAnon] = JSON.parse(this.cache[this.keyAnon]);
			
			
		if(this.cache[this.keyTitles] === null)
			this.cache[this.keyTitles] = { };
		else 
			this.cache[this.keyTitles] = JSON.parse(this.cache[this.keyTitles]);

		if(this.cache[this.keyThreads] === null)
			this.cache[this.keyThreads] = { };
		else 
			this.cache[this.keyThreads] = JSON.parse(this.cache[this.keyThreads]);


		if(this.cache[this.keyPosts] === null)
			this.cache[this.keyPosts] = { };
		else 
			this.cache[this.keyPosts] = JSON.parse(this.cache[this.keyPosts]);


	}
	






	/* проверить скрывается ли трип */
	isTrip(tripcode){

		if(this.cache[this.keyTrips] == null){
			return false;
		}

		return this.cache[this.keyTrips].includes(tripcode);

	}
	
	/* добавить трип в скрытые */
	addTrip(tripcode){
		
		if(!this.cache[this.keyTrips]){
			this.cache[this.keyTrips] = [tripcode];
			localStorage.setItem(this.keyTrips, JSON.stringify(this.cache[this.keyTrips]));
		}
		else{
			if(!this.cache[this.keyTrips].includes(tripcode)){
				this.cache[this.keyTrips].push(tripcode);
				localStorage.setItem(this.keyTrips, JSON.stringify(this.cache[this.keyTrips]));
			}
		}
	
	}
	
	/* удалить трип из скрытых */
	delTrip(tripcode){

		if(!this.cache[this.keyTrips])
			return true;
		
		let index = this.cache[this.keyTrips].indexOf(tripcode);
		
		if(index == -1)
			return true;
		
		this.cache[this.keyTrips].splice(index, 1);
		localStorage.setItem(this.keyTrips, JSON.stringify(this.cache[this.keyTrips]));
	}


	/* скрыт ли пост */
	isPost(board, thread){

		if(!this.cache[this.keyPosts][board]){
			return false;
		}
		else{
			return this.cache[this.keyPosts][board].includes(thread);
		}

	}
	
	/* добавить пост в скрытые */
	addPost(board, thread){ 

		if(!this.cache[this.keyPosts][board]){
			this.cache[this.keyPosts][board] = [thread];
			localStorage.setItem(this.keyPosts, JSON.stringify(this.cache[this.keyPosts]));
		}
		else {
			if(!this.cache[this.keyPosts][board].includes(thread)){
				this.cache[this.keyPosts][board].push(thread)
				localStorage.setItem(this.keyPosts, JSON.stringify(this.cache[this.keyPosts]));
			}
		}
	
	}
	
	/* удалить пост из скрытых */
	delPost(board, thread){

		if(this.cache[this.keyPosts][board]){
			let index = this.cache[this.keyPosts][board].indexOf(thread);
			
			if (index != -1) {
				this.cache[this.keyPosts][board].splice(index, 1);
				localStorage.setItem(this.keyPosts, JSON.stringify(this.cache[this.keyPosts]));
			}
		}

	}



	/* проверить скрывается ли тред */
	isThread(board, thread){

		if(!this.cache[this.keyThreads][board]){
			return false;
		}
		else{
			return this.cache[this.keyThreads][board].includes(thread);
		}

	}
	
	/* добавить тред в скрытые */
	addThread(board, thread){ 

		if(!this.cache[this.keyThreads][board]){
			this.cache[this.keyThreads][board] = [thread];
			localStorage.setItem(this.keyThreads, JSON.stringify(this.cache[this.keyThreads]));
		}
		else {
			if(!this.cache[this.keyThreads][board].includes(thread)){
				this.cache[this.keyThreads][board].push(thread)
				localStorage.setItem(this.keyThreads, JSON.stringify(this.cache[this.keyThreads]));
			}
		}
	
	}
	
	/* удалить тред из скрытых */
	delThread(board, thread){

		if(this.cache[this.keyThreads][board]){
			let index = this.cache[this.keyThreads][board].indexOf(thread);
			
			if (index != -1) {
				this.cache[this.keyThreads][board].splice(index, 1);
				localStorage.setItem(this.keyThreads, JSON.stringify(this.cache[this.keyThreads]));
			}
		}

	}
	
	
	/* скрывать анонимные посты? */
	isAnon(){
		return this.cache[this.keyAnon];
	}
		
	
	/* включить скрытие анонимных постов  */
	addAnon(){
		this.cache[this.keyAnon] = true;
		localStorage.setItem(this.keyAnon, JSON.stringify(true));
	}
	
	/* выключить скрытие анонимных постов  */
	delAnon(){
		this.cache[this.keyAnon] = false;
		localStorage.setItem(this.keyAnon, JSON.stringify(false));
	}

	hideCheck(board, thread, post, is_anonymous, trip){

		if(is_anonymous && this.cache[this.keyAnon])
			return true;
		else{
			if(this.isTrip(trip))
				return true;
		}


		if(this.isThread(board, thread))
			return true;
		if(this.isPost(board, post))
			return true;

		return false;
	}
	
	
	/* получить название ролика из кэша */ 
	getMedia(key){
		return this.cache[this.keyTitles][key];
	}

	 
	/* добавить информацию о медиа файле */
	addMedia(key, value){
		let keys = Object.keys(this.cache[this.keyTitles]);

		if(keys.length > this.maxTitles){
			delete this.cache[this.keyTitles][keys[0]];
		}
				
		this.cache[this.keyTitles][key] = value;
		let med = this.cache[this.keyTitles]
		let json = JSON.stringify( med);
		localStorage.setItem(this.keyTitles, json);
    }

    /* получить данные */
    getKey(key, default_value=null){
        if(this.cache[key] === undefined){
            var value = localStorage.getItem(key);

            if(value === null)
                this.cache[key] = null;
            else
                this.cache[key] = JSON.parse(value);
        }

        if(this.cache[key] == null)
            return default_value;
        else 
            return this.cache[key];
    }
    
    /* сохранить данные */
    setKey(key, value){
		console.log('store setKey ='+key+ ' : ' + JSON.stringify(favStore));


        this.cache[key] = value;
        localStorage.setItem(key, JSON.stringify(value));
    }
	

}




// Обьявляем класс  
var store = new Storage();


// и заменяем некоторые старые функции + добавляем новые

function getKey(key, default_value=null){
	return store.getKey(key, default_value);
}

function setKey(key, value){
	store.setKey(key, value);
}



function getMedia(key){
	return store.getMedia(key);
}

function addMedia(key, value){
	store.addMedia(key, value);
}


function isThreadHidden(board, thread){
	return store.isThread(board, thread);
}

function hideThread(board, thread){
	store.addThread(board, thread)
	filter_reload();
}

function showThread(board, thread){
	store.delThread(board, thread)
	filter_reload();
}

function toggleThread(event){

	let el = event.target;
	let post = el.parentElement.parentElement;
	let board = post.dataset.board;
	let thread = post.dataset.post;

	if (store.isThread(board, thread)) {
		showThread(board, thread);
		el.classList.remove('toggle-show');
		el.classList.add('toggle-hide');
		
		$(post).find('.post-body').show();
		$(post).find('.post-footer').show();
	} else {
		hideThread(board, thread);
		el.classList.remove('toggle-hide');
		el.classList.add('toggle-show');
		
		$(post).find('.post-body').hide();
		$(post).find('.post-footer').hide();
	}

	filter_reload();
}

function isThreadHiddenByTrip(tripcode){
	return store.isTrip('thread_' + tripcode);
}

function hideThreadTrip(tripcode){
	store.addTrip('thread_' + tripcode);
	filter_reload();
}

function showThreadTrip(tripcode){
	store.delTrip('thread_' + tripcode)
	filter_reload();
}

function isPostHidden(board, post){
	return store.isPost(board, post);
}

function hidePost(board, post){

	store.addPost(board, post)	
	
	$('.post').each(function(){
		if(this.dataset.board == board && this.dataset.post == post){
			if(deleteHiddenPostsConfig.value){ 	
				$(this).hide();
			}
			else{
				$(this).find('.post-body').hide();
				$(this).find('.post-footer').hide();
			}

			return false;
		}
	});
}

function showPost(board, post){
	store.delPost(board, post)
	
	$('.post').each(function(){
		if(this.dataset.board == board && this.dataset.post == post){	
			$(this).show();
			$(this).find('.post-body').show();
			$(this).find('.post-footer').show();

			return false;
		}
	});
}

	
function isTripHidden(tripcode){
	return store.isTrip(tripcode);
}

function hideTrip(tripcode){
	store.addTrip(tripcode);
	filter_reload();
}


function showTrip(tripcode){
	store.delTrip(tripcode);
	filter_reload();
}

	
function isAnonHidden(){
	return store.isAnon();
}

function hideAnon(){
	store.addAnon();
	filter_reload();
}
	
function showAnon(){
	store.delAnon();
	filter_reload();
}
