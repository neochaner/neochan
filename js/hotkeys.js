
$(document).ready(function(){

    if(is_mobile)
    {
        return true;
    }

    document.onkeydown = function(e) {

        if((e.keyCode == 37 || e.keyCode == 39) && document.activeElement.tagName != 'TEXTAREA')
        {
            player.playNext(e.keyCode == 37);
        }

        if(e.keyCode == 27)
        {
            if(player.is_active)
                player.stopAll();
        }

            
        if (e.ctrlKey && e.keyCode == 13) 
        {
            $('#replybox_form').submit();
            return false;
        }

        if(e.altKey)
        {
            switch(e.keyCode)
            {

                case 66:
                    AddTag('[b]','[/b]','replybox_text');
                    break;
                case 73:
                    AddTag('[i]','[/i]','replybox_text')
                    break;
                case 84:
                    AddTag('[s]','[/s]','replybox_text');
                    break;
                case 80:
                    AddTag('[spoiler]','[/spoiler]','replybox_text'); 
                    break;
                case 76:
                    AddTag('[love]','[/love]','replybox_text')
                    break;
                case 67:
                    AddQuote('replybox_text');
                    break;
                case 78:
                    lastReply();
                    break;
                case 79:
                    $('.reply-attach-control').trigger( "click" );
                    break;
                    
            }
        }

        
    };

});


function next_image(inverse = false)
{
    var src = $('#fullscreen-container').find('img').attr('src');
    var plus = inverse ? -1 : 1;

    if(src === undefined)
        src = $('.video').attr('src');

    if(src === undefined)
        src = $('#fullscreen-container').find('iframe').attr('orig');


    if(src !== undefined)
    {

       var files = $('.post-file-link,.y-link').not('.no-content');
  
       for(var i=0; i<files.length; i++)
       {
            if($(files[i]).attr('href') == src)
            {

                $(files[i+plus]).click();
/*
                for(var j=i+plus; j >= 0 && j < files.length; j+=plus)
                {
                    var next = $(files[j]).attr('href');
                    if(next != src)
                    {
                        $(files[j]).click();
                        return;
                    }
                }*/
            }
       }
    }
}



 