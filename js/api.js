
class BoardApi
{

	constructor(){ 
		this.loadedScripts=[];
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

	addThreadBtn(icon, title, callback) {
		
		let cts = document.querySelector('.post_op .post-controls');
		
		let c = document.createElement('a');
		c.className = 'control post-control';
		c.innerHTML = '<i class="fa fa-'+icon+'"></i>'
		c.title = _T(title);
		c.onclick = callback;
		
		cts.appendChild(c);
	}

	parsePost(el) {
		
		let time = el.getElementsByTagName("time");
		let trip = el.querySelector('.post-trip');
		let pbody = el.getElementsByClassName('post-body');
	    let plinks = pbody[0].getElementsByClassName('post-link');
		
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
		postObj.board = el.dataset.board;
		postObj.thread = el.dataset.thread;
		postObj.post = el.dataset.post;
		postObj.id = el.dataset.board+'_'+el.dataset.thread+'_'+el.dataset.post;
		postObj.op = el.dataset.thread == el.dataset.post;
		postObj.trip = trip != null ? trip.innerHTML : false;
	

		if(time.length > 0){
			
		}
	
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

}


class BoardEvents
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



var Api = new BoardApi();
var Events = new BoardEvents();

























































