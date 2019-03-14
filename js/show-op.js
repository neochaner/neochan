
 

$(document).ready(function() {
	
	showOpLinks( document.getElementsByClassName('post-link'));

});

$(document).on('new_post', function(e, post) {
	
	showOpLinks(post.getElementsByClassName('post-link'));

});



function showOpLinks(links) {
	let thread =  document.getElementById('thread_id');

	if(!thread)
		return;

	let OP = thread.dataset.id;
	
	for(let i = 0, l=links.length; i < l; i++) {
		if( links[i].dataset.id == OP && 
		!links[i].innerHTML.includes('(') )
			links[i].innerHTML += ' (OP)';
	}
}






















