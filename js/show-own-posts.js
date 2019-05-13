Api.onLoadPage(function(){

	Api.onLoadPost(makeOwnMarker);
	Api.onNewPost(makeOwnMarker);
	Api.onChangePost(makeOwnMarker);

}, ['all']);



function makeOwnMarker(obj) {

  if (obj.own && !obj.op) { 
    $(document).trigger('new_own_post', [obj.el, obj.board, obj.post]);
    obj.el.classList.add('post-you');
    return;
  }
 
  for(let i = 0, l=obj.plinks.length; i < l; i++) {

      if(Api.isOwnPost(obj.board, obj.plinks[i].dataset.id)) {
        obj.el.classList.add('post-marker');
        obj.plinks[i].innerHTML +=  _T(' (You)');

      }
  }
}

function reloadOwnPosts()
{


}
