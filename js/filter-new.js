 
/** global: Api, store */


Api.addPostMenu(addFilterMenu);
Api.onLoadPost(filterProcess);
Api.onNewPost(filterProcess);



function filterReloadAll(){
	for(let i=0, l=Api.postStore.length; i<l;i++) {
		filterProcess(Api.postStore[i]);
	}
}

function filterProcess(obj) {
	
	let needFiltered = false;
	let isFiltered = obj.el.classList.contains('post-filter');
	
	// check trip
	if(obj.trip && store.isTrip(obj.trip)) {
		needFiltered=true;
	}
	if(obj.name && store.isTrip(obj.name)) {
		needFiltered=true;
	}
	
	
	if(needFiltered && !isFiltered) {
		obj.el.classList.add('post-filter');
	}
	if(!needFiltered && isFiltered) {
		obj.el.classList.remove('post-filter');
	}
}
 
function addFilterMenu(obj){
	 
	 let args = '("'+obj.board+'", '+obj.post+')';
	 let menu = {
		 submenu: 'фильтр',
		 items: []
	 };

	 if(obj.trip) {
		if(store.isTrip(obj.trip))
			menu.items.push({name:'del tripcode', onclick:'filterDelTrip'+args});
		else
			menu.items.push({name:'add tripcode', onclick:'filterAddTrip'+args});
	 }
	 
	 if(obj.name) {
		if(store.isTrip(obj.name))
			menu.items.push({name:'del name', onclick:'filterDelName'+args});
		else
			menu.items.push({name:'add name', onclick:'filterAddName'+args});
	 }
	 
	 if(menu.items.length > 0) {
		return [menu];
	 } else {
	 	return [];
	 }
 
 }
 
function filterAddTrip(board, post) {
	
	store.addTrip(Api.getPost(board, post).trip);
	filterReloadAll();
}
 
function filterDelTrip(board, post) {
	
	store.delTrip(Api.getPost(board, post).trip);
	filterReloadAll();
}

function filterAddName(board, post) {
	
	store.addTrip(Api.getPost(board, post).name);
	filterReloadAll();
}
 
function filterDelName(board, post) {
	
	store.delTrip(Api.getPost(board, post).name);
	filterReloadAll();
}

 
 function post_menu(event)
{
	event.preventDefault();
	return Api.callPostMenu(event.target);
}

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
 
 
 
 
 
 
 
 
 
 