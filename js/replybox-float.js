/* 
  FLOATING SUPPORT FOR REPLYBOX 

  Depend : jquery


*/


var REPLY_FLOATING = false;

if(config.active_page == 'thread'){
    docReady(function(){

      enable_replybox_float();

      if(['native-makaba', 'native-lolifox'].includes(config.theme)){
        set_replybox_float_default();
      }

  

    });

}



function set_replybox_float_default(){
 
  let size = win_size();

  if(size.width > 900 && size.height>900){

    REPLY_FLOATING = true;

    var replyBox = document.getElementById('replybox');
    var reply = document.querySelector('#replybox .reply');

    replyBox.style.position = 'fixed'; 
    replyBox.style.top = '40%';
    replyBox.style.left = '50%';
    reply.style.width = '400' + 'px';
    reply.style.height =  '250' + 'px';
    replyBox.style.zIndex=100;
  }

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

      if(document.getElementById('replybox').offsetLeft < 150){
        disable_replybox_float();
      }
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
  
        $(replyBox).insertBefore(posts[i]);
        break;
      }
    }
  
  }
 

  replyBox.style.removeProperty('position')
  replyBox.style.removeProperty('top')
  replyBox.style.removeProperty('zIndex')




} 