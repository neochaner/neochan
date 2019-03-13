
var youtube_counter =0;




$(document).ready(function(){

    $('.main').append("<div id='media-thumbnail'></div>")

    processMediaContent(document);
});

$(document).on('new_post', function(e,post) {

    processMediaContent(post);
})



$(document).on('change_post', function(e,post) {

    processMediaContent(post);
})




function youtube_preview(e, id)
{
    $('#'+id).trigger('click');
    e.preventDefault();
} 


function youtubize_post(post)
{
    if($(post).find('.post-files').length != 0)
        return ;

    var hrefs = $(post).find('.y-link');
    
    if(hrefs.length > 0)
    {
        for(var i=0; i<hrefs.length; i++)
        {
            var $href = $(hrefs[i]);
            var id = $href.attr('id');
         
            add_image(post, '/embed/youtube320/' + $href.data('id') + '.jpg', id);
            return;
        }
    }
}

function add_image(post, src, id)
{

    var $post = $(post);
    var post_body = $post.find('.post-body');
    var post_files = post_body.find('.post-body');

    if(post_files.length == 0)
    {
        var sh = '<div class="post-files"><figure class="post-file"><figcaption class="post-file-info"><span class="post-file-info-item post-file-size">Youtube</span></figcaption><a class="post-file-link no-content" href="'+src+'" target="_blank"><img class="img" onclick="youtube_preview(event, \''+id+'\')" src="'+src+'" style="width:200px;height:114px"></a></figure></div>';
        $(post_body).prepend(sh);
        $post.width($post.width()+205);

    }

}
 







/* NEW */

var content_counter=0;


function processMediaContent(div){

    let on_hover = true;
    let links = div.querySelectorAll('.y-link,.vlive-link,.vimeo-link,.post-file-link');

    for(let i=0, l=links.length; i<l; i++){
 
        let link =  links[i];
        let is_youtube = link.className == 'y-link';
        let is_vlive = link.className == 'vlive-link';
        let is_vimeo = link.className == 'vimeo-link';
        let href = link.getAttribute('href');

        switch(link.className){


            case 'y-link':
                let time_stamp = 0;
                let regx = link.href.match(/&t=(\d+)/i);

                if(regx != null && regx.length == 2){
                    time_stamp = regx[1];
                }

                link.id = 'content-' + (++content_counter);
                link.setAttribute('onclick', 'playContent(event, "'+link.dataset.id+'", 0,0,"youtube", '+ content_counter +', '+time_stamp+')'); 
                var parentPost = $(link).closest('.post');
                youtubize_post(parentPost);
            break;

            case 'vimeo-link':
                link.id = 'content-' + (++content_counter);
                link.setAttribute('onclick', 'playContent(event, "'+link.dataset.id+'", 0,0,"vimeo", '+ content_counter +')'); 

            break;

            case 'post-file-link':
                let ext = href.split('.').pop().toLowerCase();

                if(['mp4', 'webm', 'jpg', 'jpeg', 'bmp', 'png', 'gif', 'webp', 'tiff'].includes(ext)){

                    let oldOnclick = link.getAttribute("onclick");
                    let args = oldOnclick.split(',');

                    link.id ='content-' + (++content_counter);
                    link.setAttribute('onclick', 'playContent(event, "'+href+'", '+args[3]+', '+args[4]+', false, '+ content_counter +')');

                }

            break;


        }

    
    
        if(!is_mobile && on_hover && (is_youtube || is_vimeo)){

        
            let thumb = document.createElement('div');
            thumb.classList.add("media-thumbnail");
            thumb.id = "content-thumb-"+content_counter;
            thumb.style.display='none';
            thumb.style.position = 'absolute';
            thumb.style.zIndex = 999;

            if(is_youtube)
                thumb.innerHTML = '<img src="/embed/youtube320/'+link.dataset.id+'.jpg" width="320" height="180"></img>';
            else if(is_vlive)
                thumb.innerHTML = '<img src="/embed/vlive/'+link.dataset.id+'.jpg" width="320"></img>';
            else if(is_vimeo)
                thumb.innerHTML = '<img src="/embed/vimeo/'+link.dataset.id+'.jpg" width="320"></img>';
            else
                continue;

            document.body.appendChild(thumb);

            link.onmouseover = showContentThumb;
            link.onmousemove = moveContentThumb; 
            link.onmouseout = hideContentThumb; 

        }

    }
   
}


function showContentThumb(event){

    let thumb_id = event.target.id.replace('content', 'content-thumb');
    let thumb = document.getElementById(thumb_id);

    thumb.style.left = event.pageX + 'px';
    thumb.style.top = event.pageY+ 'px';
    thumb.style.display='block';
}

function moveContentThumb(event){

    let thumb_id = event.target.id.replace('content', 'content-thumb');
    let thumb = document.getElementById(thumb_id);

    thumb.style.left = (event.pageX+20) + 'px';
    thumb.style.top = (event.pageY-20)+ 'px';
}


function hideContentThumb(event){
    let thumb = event.target.id.replace('content', 'content-thumb');
    document.getElementById(thumb).style.display='none';
}
