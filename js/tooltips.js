




const TOOLTIP_SELECTOR = '[title]:not(.reply-smile-control):not(time)'

$(document).on('new_post', function(e, post) {
 

    if(is_mobile)
        return true;


    let titles = $(post).find(TOOLTIP_SELECTOR);

    for(var i = 0; i < titles.length; i++) {
        tippy(titles[i], { content: _T(titles[i].title) });
        titles[i].title = ''; 
    }

   
});


$(document).ready(function() {
    initializeTooltips();
});

function initializeTooltips() {

    if(is_mobile)
        return true;

    let titles = document.querySelectorAll(TOOLTIP_SELECTOR);

    for(var i = 0; i < titles.length; i++) {
        tippy(titles[i], { content: _T(titles[i].title) });
        titles[i].title = ''; 
    }
  
}

