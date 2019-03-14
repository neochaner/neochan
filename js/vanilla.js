

/*

    Таскалка элементов
    - (элемент) перетаскиваемый элемент
    - (функция) вызывается если произошёл клик, а не перетаскивание
    - (массив ид) потомки на которых не распространяется действие
*/
function set_draggable(elem_id, clickEvent = false, excludeElements = []) {
    
    
  let elmnt = document.getElementById(elem_id);
  let startX, startY, curX, curY, chkX, chkY;

  elmnt.onmousedown = dragMouseDown;

  function dragMouseDown(e) {
    e = e || window.event; 

    if(e.which != 1)
      return;

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

    if(clickEvent && startX == chkX && startY == chkY)
      clickEvent();
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

function win_size(){

  let width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
  let height = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;

  return {'width': width, 'height': height };

}