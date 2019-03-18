/* 
  FLOATING SUPPORT FOR REPLYBOX 

  Depend : jquery


*/


var REPLY_FLOATING = false;

if(config.active_page == 'thread'){
    docReady(enable_replybox_float);
}


function enable_replybox_float(){
  let replyDraggers = document.getElementsByClassName('reply-dragger');
  let replyBox = document.getElementById('replybox');

  let startX, startY, curX, curY, chkX, chkY;

  replyDraggers[0].onmousedown = dragReplyMouseDown;
  replyDraggers[1].onmousedown = dragReplyMouseDown;

  function dragReplyMouseDown(e) {
    e = e || window.event; 

    if(e.which != 1)
      return;

    e.preventDefault();

    chkX= e.clientX;
    chkY= e.clientY;

    startX = e.clientX;
    startY = e.clientY;
    
    document.onmouseup = closeDragReply;
    // call a function whenever the cursor moves:
    document.onmousemove = elementDragReply;
  }

  function elementDragReply(e) {
    e = e || window.event;
    e.preventDefault();


    if(!REPLY_FLOATING){

      REPLY_FLOATING = true; 

      var rect = replyBox.getBoundingClientRect();
      replyBox.style.position = 'fixed'; 
      replyBox.style.top = Math.floor(rect.y)+'px';
      replyBox.style.zIndex=100;

    } else {

    

      // calculate the new cursor position:
      curX = startX - e.clientX;
      curY = startY - e.clientY;
      startX = e.clientX;
      startY = e.clientY;

  
      // set the element's new position:
      replyBox.style.top = (replyBox.offsetTop - curY) + "px";
      replyBox.style.left = (replyBox.offsetLeft - curX) + "px";
    }
  }

  function closeDragReply() {
      document.onmouseup = null;
      document.onmousemove = null;
  }

}


function disable_replybox_float(set_bottom = false){

  REPLY_FLOATING = false;
 
  let replyBox = document.getElementById('replybox');
  let posts = document.getElementsByClassName('post'); 

  if(set_bottom){

    $(replyBox).insertAfter(posts[posts.length-1]);

  } else {

    let Top = replyBox.offsetTop +  document.documentElement.scrollTop
   
    // insert in centter reply 
  
    for(let i=0, l=posts.length; i<l;i++){
  
      if(posts[i].offsetTop > Top){
  
        $(replyBox).insertAfter(posts[i]);
        break;
      }
    }
  
  }
 

  replyBox.style.removeProperty('position')
  replyBox.style.removeProperty('top')
  replyBox.style.removeProperty('zIndex')




} 