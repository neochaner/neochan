var optionHideVideoKey = 'optionHideVideo';
var optionHideVideo;
var optionHideVideoOpacity = 0.01;

Api.onLoadPage(mainHideVideo)
Api.onNewPost(processHideVideo);	
Api.onChangePost(processHideVideo);	


function mainHideVideo() {

	optionHideVideo = Api.addOptCheckbox(optionHideVideoKey, false, 'l_nsfmvideo', '', toggleHideVideo);
 
    if(optionHideVideo) {
        let links= document.body.getElementsByClassName('post-file-link');

        for (let i=0, l=links.length; i<l; i++) {
       
            if((links[i].href.endsWith("webm") || links[i].href.endsWith("mp4"))) {
                let $img =  $(links[i]).find("img");

                $img.css("opacity", optionHideVideoOpacity)

                $img.hover(function(){
                    $(this).css("opacity", 1);
                    }, function(){
                    $(this).css("opacity", optionHideVideoOpacity);
                });
            }
        }
    }

}


function toggleHideVideo(value)
{  
    location.reload();
}

function processHideVideo(obj){

    if(optionHideVideo) {
        let links= obj.el.getElementsByClassName('post-file-link');

        for (let i=0, l=links.length; i<l; i++) {
            if(links[i].href.endsWith("webm") || links[i].href.endsWith("mp4")) {
                
                let $img =  $(links[i]).find("img");

                $img.css("opacity", optionHideVideoOpacity)

                $img.hover(function(){
                    $(this).css("opacity", 1);
                    }, function(){
                    $(this).css("opacity", optionHideVideoOpacity);
                });
            }
        }
    }
}

 