/* POLYFILLS */

(function() {

  // проверяем поддержку
  if (!Element.prototype.matches) {

    // определяем свойство
    Element.prototype.matches = Element.prototype.matchesSelector ||
      Element.prototype.webkitMatchesSelector ||
      Element.prototype.mozMatchesSelector ||
      Element.prototype.msMatchesSelector;

  }

})();

(function() {

  // проверяем поддержку
  if (!Element.prototype.closest) {

    // реализуем
    Element.prototype.closest = function(css) {
      var node = this;

      while (node) {
        if (node.matches(css)) return node;
        else node = node.parentElement;
      }
      return null;
    };
  }

})();














function is_visible(id){
    return $(id).css('display') !== 'none';
}

function toggle(el){
    var el = $(el);

    if(el.length)
    {
        if(el.css('display') === 'none')
            el.show();
        else
            el.hide();
    }
}

function getPost(board, thread_id, post_id)
{
    var post = $('.post_' + board + '_' + thread_id + '_' + post_id );

    if(post.length == 0 )
	{
		post = $('.post_' + board + '__' + post_id );
    }

    return post;
}

function get_reply(reply_id, include_op_posts = true)
{

    var reply = $('#reply_'+reply_id);
	
	if(reply.length == 0 && include_op_posts)
	{
		reply = $('#post'+reply_id);
    }
    
    return reply;

}

function is_my_reply(board, id)
{

    var posts = JSON.parse(localStorage.own_posts || '{}');

    if (posts[board] && posts[board].indexOf(id) !== -1) 
    {
        return true;
    }

    return false;
} 

 