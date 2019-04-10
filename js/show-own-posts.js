/*
 * show-own-posts.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/show-op.js
 *
 * Adds "(You)" to a name field when the post is yours. Update references as well.
 *
 * Released under the MIT license
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/ajax.js';
 *   $config['additional_javascript'][] = 'js/show-own-posts.js';
 *
 */



var update_own = function() 
{


	if(this.classList.contains('you') || this.classList.contains('post_op'))
		return;
  
    var board = this.dataset.board;
    var id = this.dataset.post;

	
    var posts = JSON.parse(localStorage.own_posts || '{}');; 
  
    if (posts[board] && posts[board].indexOf(id) !== -1) 
    {
		    $(document).trigger('new_own_post', [this, board, id]);
        $(this).addClass('post-you');
        return true;
    }
  
    // Update references
    var bpost = this;
	  var pbody = this.getElementsByClassName('post-body');
	  var plinks = pbody[0].getElementsByClassName('post-link');
    var is_answer = false;
  
    for(var i=0; i<plinks.length; i++)
    {
      
		  var link_text = plinks[i].innerHTML;
		  var postID = plinks[i].dataset.id;
  
      if (posts[board] && posts[board].indexOf(postID) !== -1) 
      {

        bpost.classList.add('post-marker');

        if(!link_text.includes('('))
        {
		      plinks[i].innerHTML +=  _T(' (You)');
          is_answer = true; 
        }
      }
    }

    if(is_answer)
    {
      $(document).trigger('new_answer', bpost);
    }

  };
  

  var update_all = function() 
  {
    $('div[id^="thread_"], article.post').each(update_own);
  };

  function reloadOwnPosts()
  {
    $('.post').removeClass('post-you post-marker');
    update_all();
  }
  
  var board = null;
  
  $(function() {
    board = $('input[name="board"]').first().val();
    update_all();
  });
  
  $(document).on('ajax_after_post', function(e, r) 
  {
    var posts = JSON.parse(localStorage.own_posts || '{}');
    posts[board] = posts[board] || [];
    posts[board].push(r.id);
    localStorage.own_posts = JSON.stringify(posts);
  });
  
  $(document).on('new_post', function(e,post) {
    var $post = $(post);
    if ($post.is('article.post')) { // it's a reply
      $post.each(update_own);
    }
    else {
		
      $post.each(update_own); // first OP
      $post.find('article.post').each(update_own); // then replies

    }
  });
  
  

  
  
  