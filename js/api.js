
class EventCallback
{
	
	constructor()
	{	
		this.data={};
	}

	register(name, callback)
	{	
		if (!this.data.hasOwnProperty(name)) {
			this.data[name] = [];
		}
		
		this.data[name].push(callback);
	} 
	
	awake(name, obj)
	{
		if (this.data.hasOwnProperty(name)) {
			for (let i=0, l=this.data[name].length; i<l; i++) {
				this.data[name][i](obj);
			}
		}
	}

}


class BoardApi
{

	constructor(){	
		this.postStore=[];
		let oldf  = JSON.parse(localStorage.own_posts || '{}');

		let keys = Object.keys(oldf);
		for (let i=0,l=keys.length;i<l;i++) {
			let newArr = [];
			for(let j=0, ll=oldf[keys[i]].length;j<ll; j++){
				newArr.push(parseInt(oldf[keys[i]][j]));
			}
			oldf[keys[i]]=newArr;
		}


		this.own_posts = oldf;
		this.events = new EventCallback();
		this.thread = null;
		this.threads=[];

		this.maxPostID=0;
		this.maxEditTime=0;
	}

	addTranslates(obj){

		if (Array.isArray(obj)){
			for (let i=0,l=obj.length; i<l;i++) {
				this.addTranslate(obj[i]);
			}
		}
	}

	addTranslate(obj){

		if (!obj.hasOwnProperty('en')) {
			return false;
		} 
	
		let line = [];
	
		for (let i=0, l=langData[0].length; i<l; i++) {
			let code = langData[0][i];
			line.push(obj.hasOwnProperty(code) ? obj[code] : obj['en']);
		}
	
		langData.push(line);
	}


	/* ADD ELEMENTS */

	addThreadBtn(icon, callback, title) {
		
		let cts = document.querySelector('.post_op .post-controls');
		
		let c = document.createElement('a');
		c.className = 'control post-control';
		c.innerHTML = '<i class="fa fa-'+icon+'"></i>'
		c.onclick = callback;
		
		cts.appendChild(c);
	}

	addMenuIcon(icon, callback, title, mobile_view = true) {
		
		let el = document.getElementById('header-icons');
		
		let item = document.createElement('a');
		item.className="header-item header-icon";
		item.innerHTML = '<i class="fa fa-'+icon+'"></i><span class="badge"></span>';
		item.title = _T(title);
		item.onclick = callback;
		
		el.insertBefore(item, el.childNodes[0]);
		
		return item;
	}

	/*******************/




	isOwnPost(board, num) {

		if (typeof num === 'string') {
			num = parseInt(num);
		}
		
		return (this.own_posts[board] && this.own_posts[board].indexOf(num) !== -1);
	}

	processLoadPosts() {
		
		let posts = document.getElementsByClassName('post');
	
		for (let i=0, l=posts.length; i<l; i++) {
			let obj = this.parsePostElem(posts[i]);
			this.postStore.push(obj);
		}
		
		for (let i=0, l=this.postStore.length; i<l; i++) {
			this.events.awake('load-post', this.postStore[i]);
		}
	}

	parsePostElem(el) {
		
		let timeEl = el.getElementsByTagName("time")[0];
		let trip = el.querySelector('.post-trip');
		let pbody = el.getElementsByClassName('post-body');
	    let plinksEl = pbody[0].getElementsByClassName('post-link');
		
		var postObj = {
			'el'		: null,
			'board'		: '',	// board
			'thread'	: 0,	// thread number
			'op'		: false,// post is thread
			'post'		: 0,	// post number
			'id'		: '',	// post unical id
			'time'		: 0,	// creation time
			'edit'		: 0,	// edit time
			'changed'	: 0,	// last changed
			'trip'		: false,// trip code
			'name'		: false,// poster name

		};
		
		postObj.el = el;
		postObj.plinks = plinksEl;
		postObj.board = el.dataset.board;
		postObj.thread = parseInt(el.dataset.thread);
		postObj.post = parseInt(el.dataset.post);
		postObj.id = el.dataset.board+'_'+el.dataset.thread+'_'+el.dataset.post;
		postObj.op = el.dataset.thread == el.dataset.post;
		postObj.trip = trip != null ? trip.innerHTML : false;
		postObj.own = this.isOwnPost(postObj.board, postObj.post);
		postObj.time = parseInt(timeEl.getAttribute('unixtime'));
		postObj.edit = parseInt(timeEl.getAttribute('edit'));

		return postObj; 
	
	}


	loadScript(path, callback){

		if (!this.loadedScripts.includes(path)) {

			this.loadedScripts.push(path);

			let script = document.createElement('script');
			script.onload=callback;
			script.src = path;
			document.body.appendChild(script); 

		} else {  
			callback(); 
		}
	}



	noticeNewPost (el) {

		let obj = this.parsePostElem(el);
		let prevObj = null;

		for (let i=0, l=this.postStore.length; i<l; i++) {

			let orig = this.postStore[i];

			if(orig.board == obj.board) {

				if(orig.post == obj.post && orig.edit != obj.edit) {
					
					// POST CHANGED!
					$before(orig.el, obj.el)
					$remove(orig.el);
					this.postStore[i] = obj;

					this.events.awake('change-post', this.postStore[i]);
					$(document).trigger('change_post', obj.el);

					console.log('CHANGE POST ' + obj.el);
					return;
				} else if(orig.post == obj.post && orig.edit == obj.edit) {

					console.log('DOUBLE POST! ' + obj.el);
					return;
				}

				if(prevObj == null || prevObj.post < orig.post) {
					prevObj = this.postStore[i];
				}
			}
		}

		// ADD NEW POST

		if(prevObj == null) {
			let thread = document.getElementById('thread_' + obj.thread).appendChild(obj.el);
		} else {

			$after(prevObj.el, obj.el);
		}

		this.postStore.push(obj);
		this.events.awake('new-post', obj);
		
		$(document).trigger('new_post', obj.el);
		console.log('NEW POST ' + obj.el);
	}

	noticeHidePost (board, id) {
		for (let i=0, l=postStore.length; i<l; i++) {
			if (postStore[i].board == board && postStore[i].id ==id) {
				postStore[i].el.classList.add('post-hide');
				break;
			}
		}
	}



	/* Events */



	/**
	 * Subscribe on load page events
	 * @param {function} callback - function
	 * @param {array} pages - all, index, thread, ukko, other
	 */
	onLoadPage (callback, pages=['all']) {

		if (!Array.isArray(pages)) {
			pages = [pages];
		}

		for (let i=0; i<pages.length; i++) {
			this.events.register('load-'+pages[i], callback);
		}
	}

	onLoadPost (callback) {
		this.events.register('load-post', callback);
	}

	onNewPost (callback) {
		this.events.register('new-post', callback);
	}

	onChangePost (callback) {
		this.events.register('change-post', callback);
	}





}

var Api = new BoardApi();

docReady(function(){

	if(config.active_page == 'thread') {
		Api.thread = parseInt(document.getElementById('thread_id').dataset.id);
	}

	if(config.active_page == 'index') {
		
		let threads = document.getElementsByClassName('thread_index');
		
		for (let i=0, l=threads.length; i<l; i++) {
			Api.threads.push(parseInt(threads[i].dataset.thread));
		}
	}

	Api.events.awake('load-all');
	Api.events.awake('load-' + config.active_page);

	if(config.active_page != 'general') {
		Api.processLoadPosts();
	}
});






















































