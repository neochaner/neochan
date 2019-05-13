

/*

    Drag&Drop элементов
    - (элемент) перетаскиваемый элемент
    - (функция) вызывается если произошёл клик, а не перетаскивание
    - (массив ид) потомки на которых не распространяется действие
*/
function set_draggable(elem_id, clickEvent = false, excludeElements = [], endDragEvent=false) {
  
  let elmnt = elem_id;

  if (typeof elem_id === 'string'){
    elmnt = document.getElementById(elem_id);
  }
 
  let startX, startY, curX, curY, chkX, chkY;

  elmnt.onmousedown = dragMouseDown;

  function dragMouseDown(e) {
    e = e || window.event; 

    if(e.which != 1){
      return;
    }

    if(e.target && isDisableDrag(e.target)){
      return;
    }
 
    e.preventDefault();

    chkX= e.clientX;
    chkY= e.clientY;

    startX = e.clientX;
    startY = e.clientY;
    
    document.onmouseup = closeDragElement;
    // call a function whenever the cursor moves:
    document.onmousemove = elementDrag;
  }

  function elementDrag(e) {
    e = e || window.event;
    e.preventDefault();


    // calculate the new cursor position:
    curX = startX - e.clientX;
    curY = startY - e.clientY;
    startX = e.clientX;
    startY = e.clientY;

    // set the element's new position:
    elmnt.style.top = (elmnt.offsetTop - curY) + "px";
    elmnt.style.left = (elmnt.offsetLeft - curX) + "px";
  }

  function closeDragElement() {
    document.onmouseup = null;
    document.onmousemove = null;

    if(clickEvent && startX == chkX && startY == chkY){
      clickEvent();
    }
    else if(endDragEvent){
      endDragEvent();
    }
    
  }

  function isDisableDrag(elem){

    for(let i=0; i < excludeElements.length; i++){
      let mod = excludeElements[i][0];

      if(mod == '.' && elem.classList.contains(excludeElements[i].substr(1))){ // class
        return true;
      } else if(mod=='#' && elem.id == excludeElements[i].substr(1)){ // id
        return true;
      } else if(elem.nodeName.toUpperCase() == excludeElements[i].toUpperCase()){
        return true;
      }
    }

    return false;

  }

}


function $rand(length) {
  var text = "";
  var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

  for (var i = 0; i < length; i++)
    text += possible.charAt(Math.floor(Math.random() * possible.length));

  return text;
}


function win_size(){

  let width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
  let height = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;

  return {'width': width, 'height': height };

}
























/* 
    DocReady 

*/

(function(funcName, baseObj) {
  "use strict";
  // The public function name defaults to window.docReady
  // but you can modify the last line of this function to pass in a different object or method name
  // if you want to put them in a different namespace and those will be used instead of 
  // window.docReady(...)
  funcName = funcName || "docReady";
  baseObj = baseObj || window;
  var readyList = [];
  var readyFired = false;
  var readyEventHandlersInstalled = false;
  
  // call this when the document is ready
  // this function protects itself against being called more than once
  function ready() {
      if (!readyFired) {
          // this must be set to true before we start calling callbacks
          readyFired = true;
          for (var i = 0; i < readyList.length; i++) {
              // if a callback here happens to add new ready handlers,
              // the docReady() function will see that it already fired
              // and will schedule the callback to run right after
              // this event loop finishes so all handlers will still execute
              // in order and no new ones will be added to the readyList
              // while we are processing the list
              readyList[i].fn.call(window, readyList[i].ctx);
          }
          // allow any closures held by these functions to free
          readyList = [];
      }
  }
  
  function readyStateChange() {
      if ( document.readyState === "complete" ) {
          ready();
      }
  }
  
  // This is the one public interface
  // docReady(fn, context);
  // the context argument is optional - if present, it will be passed
  // as an argument to the callback
  baseObj[funcName] = function(callback, context) {
      if (typeof callback !== "function") {
          throw new TypeError("callback for docReady(fn) must be a function");
      }
      // if ready has already fired, then just schedule the callback
      // to fire asynchronously, but right away
      if (readyFired) {
          setTimeout(function() {callback(context);}, 1);
          return;
      } else {
          // add the function and context to the list
          readyList.push({fn: callback, ctx: context});
      }
      // if document already ready to go, schedule the ready function to run
      // IE only safe when readyState is "complete", others safe when readyState is "interactive"
      if (document.readyState === "complete" || (!document.attachEvent && document.readyState === "interactive")) {
          setTimeout(ready, 1);
      } else if (!readyEventHandlersInstalled) {
          // otherwise if we don't have event handlers installed, install them
          if (document.addEventListener) {
              // first choice is DOMContentLoaded event
              document.addEventListener("DOMContentLoaded", ready, false);
              // backup is window load event
              window.addEventListener("load", ready, false);
          } else {
              // must be IE
              document.attachEvent("onreadystatechange", readyStateChange);
              window.attachEvent("onload", ready);
          }
          readyEventHandlersInstalled = true;
      }
  }
})("docReady", window);