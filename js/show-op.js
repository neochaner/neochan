/*
 * show-op
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/show-op.js
 *
 * Adds "(OP)" to >>X links when the OP is quoted.
 *
 * Released under the MIT license
 * Copyright (c) 2012 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/show-op.js';
 *
 */


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

$(document).ready(function() {
	
	showOpLinks( document.getElementsByClassName('post-link'));

});

$(document).on('new_post', function(e, post) {
	
	showOpLinks(post.getElementsByClassName('post-link'));

});

























