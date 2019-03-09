/* 
    DEPEND : 

    - vanilla.js
    - storage.js
*/


class MediaPlayer{

	constructor(){	

        this.is_active=false; 
        this.is_active_video=false;
        this.is_active_image=false;
        this.is_active_sound=false;

        /*
        this.active_video=false;
        this.active_image=false;
        */
        this.volume = getKey('webm_vol', 0.5);
        this.mute = this.volume == 0;
        this.plyr = null;
        this.using_plyr = true;; // использование plyr вместо нативного
        this.container=null;
        this.container_id='player-container';
        this.overlay_id='container-overlay';
        this.container_padding = 2;
        this.container_border = 1;
        this.is_firefox = (/Firefox/i.test(navigator.userAgent)); 
        this.loop = false; 
        this.resizeByCenter=false; 

        this.ext_videos = ['mp4', 'webm'];
        this.ext_images = ['jpg', 'jpeg', 'bmp', 'png', 'gif', 'webp', 'tiff'];
        this.ext_sounds = ['mp3', 'wav', 'flac'];
        


        /* temporary vars */
        this.content_num = -1;
        this.download_link=false;
        this.multiplier=1;
        this.vid_width=0;
        this.vid_height=0;
        this.mouse_is_hover=false;
        this.mouse_pos_x=0;
        this.mouse_pos_y=0;
        
        
    }

    load(){

        this.container = document.createElement('div');
        this.container.style.display = 'none';
        this.container.style.position= 'fixed'
        this.container.style.boxSizing= 'content-box'
        
        this.container.id = this.container_id;

        document.body.appendChild(this.container);

        set_draggable(this.container_id, function(){ player.stopAll();}, ['input', 'button', 'i', '.fa', '.plyr__time']);
    }


     
    showContainer(width, height){


        this.vid_width = width;
        this.vid_height = height;

        let win_width =document.body.clientWidth; - this.container_padding;
        let win_height = document.body.clientHeight - this.container_padding;

        let left = ((win_width - width)/2);
        let top =  ((win_height- height)/2); 


        this.container.style.left = left + 'px';
        this.container.style.top = top + 'px';
        this.container.style.width = width + 'px';
        this.container.style.height = height + 'px';
        this.container.style.display='block';

        // resize
        if(width > win_width || height > win_height) 
        {
            var multiplier_width =  ((win_width)/(width -this.container_border));
            var multiplier_height = ((win_height)/(height - this.container_border));
            var count = multiplier_width<multiplier_height ? multiplier_width : multiplier_height;

            this.resizeContainer(count);
        }

        this.saveContainerPos();


    }

    
    hideContainer(){
        this.is_active=false;
        this.container.style.display='none';
    }

    relocContainer(new_multiplier){

        var scroll_top = $(window).scrollTop();
        var scroll_left =  $(window).scrollLeft();
        var container_offset = $(this.container).offset();
        var x_on_container;
        var y_on_container;

        if(this.resizeByCenter) {
            x_on_container = this.vid_width/2;
            y_on_container = this.vid_height/2;
        }else{
            x_on_container = (this.mouse_pos_x-container_offset.left+scroll_left);
            y_on_container = (this.mouse_pos_y-container_offset.top+scroll_top);
        }
        var container_top = container_offset.top-scroll_top;
        var container_left = container_offset.left-scroll_left;
        var delta_multiplier = new_multiplier-this.multiplier ;
        var delta_top = delta_multiplier*y_on_container/this.multiplier ;
        var delta_left = delta_multiplier*x_on_container/this.multiplier ;
    

        this.container.style.left = container_left-delta_left + 'px';
        this.container.style.top = container_top-delta_top + 'px';

    }

    resizeContainer(new_multiplier){

        if(new_multiplier < 0.01 || new_multiplier > 5) return;
 
        this.relocContainer(new_multiplier);
        this.multiplier = new_multiplier;

        this.container.style.width = (this.vid_width * new_multiplier)+'px';
        this.container.style.height = ( this.vid_height * new_multiplier)+'px';
  
        this.saveContainerPos();
    }

    loadPlayerPos(){

    }

    saveContainerPos(){

    }

    addOverlay(){

        let el = document.createElement('div');
        el.id = 'container-overlay';
        el.style.position = 'absolute'
        el.style.left='0';
        el.style.right='0';
        el.style.top='0';
        el.style.bottom='50px';
        
        this.container.appendChild(el);
    }

    downloadSource(){

        if(!this.download_link)
            return;

        if(this.plyr ){

            let a = document.createElement('a');
            a.href = this.download_link;
            a.target ="_blank";
            a.style.display='none';
            a.download='';


            document.body.appendChild(a);
            a.click();

            a.parentElement.removeChild(a);


            this.plyr.stop();
        }
    }

    webmSarFix(vid_width, vid_height) {
        


        var video = document.getElementById('html5media');
        video.removeAttribute('oncanplay');
        /*

        var real_width = video.offsetWidth;
        var real_height = video.offsetHeight;
        let win_width =document.body.clientWidth; - this.container_padding;
        let win_height = document.body.clientHeight - this.container_padding;
        var cur_ratio = vid_width / vid_height;
        var real_ratio = real_width / real_height;


        console.log('webmSarFix ' +cur_ratio + ' / ' + real_ratio);

        if(cur_ratio != real_ratio) {
            img_height = vid_width / real_ratio;

            this.container.style.width = real_width + 'px';
            this.container.style.height = real_height + 'px';
            this.container.style.top = (((win_height-real_height)/2) - border_offset) + 'px'
            this.container.style.left = (((win_width-real_width)/2) - border_offset) + 'px';
            
        }*/


    }

    /*
        Просмотр картинки
    */
    playContent(e, url, vid_width, vid_height, embed_provider = false, content_num = -1){
 

        e.preventDefault();

        this.stopAll();
        this.is_active=true;
        this.content_num = content_num;
 
        let vol = getKey('volume', 0.5);
        let muted = getKey('muted', false);
        let video_loop = getKey('loop', false);
        let ext = embed_provider ? 'embed' : url.split('.').pop().toLowerCase();


        if(this.ext_videos.includes(ext)){
 
            this.is_active_video=true;
            this.download_link  =url;
            if(this.using_plyr){

                // get thumb
                let link = document.querySelector('#content-'+content_num + ' img');
                let thumb_src = link.getAttribute('src');
                 


                this.container.innerHTML = `
                <video id="html5media" width="100%" height="100%" poster="` + thumb_src + `" controls autoplay 
                oncanplay="player.webmSarFix(`+vid_width+`, `+vid_height+`, );" 
                onplay="player.eventPlayStarted(this);" 
                onvolumechange="player.eventVolumeChanged(this);"><source src="` + url + `" type="video/` + ext + `"></video>`;

           
	
                this.plyr = new Plyr(('#html5media'), {
                    controls: ['play', 'progress', 'current-time', 'mute', 'volume', 'download', 'fullscreen'],
                    classNames: {tabFocus: 'plyr__nullclass'}, 
                    clickToPlay: false,
                    disableContextMenu: false,
                    keyboard: {focused: false, global: false},
                    invertTime: false,
                    toggleInvert: false,
                    storage: {enabled: false},
                    playsinline: true,
                    loop: {active: video_loop},
                    volume: vol,
                    muted: muted
                });

                this.plyr.on('ready', () => {
                    $(`<button type="button" class="plyr__control" onclick="player.downloadSource()">
                        <i class="fa fa-lg fa-download"></i></button>`).insertAfter('.plyr__volume')
                });
    
                this.plyr.on('volumechange', () => {
                    setKey('volume', player.plyr.volume);
                    setKey('muted', player.plyr.muted);
                });


            } else {

            this.container.innerHTML = 
            `<video id="html5video" onplay="player.eventPlayStarted(this)" onvolumechange="player.eventVolumeChanged(this)" name="media" loop="`+(video_loop ? '1' : '0')+`" controls="" autoplay="" height="100%" width="100%">
                <source class="video" height="100%" width="100%" type="video/`+ext+`" src=`+url+`></source>
             </video>`;

             if(this.is_firefox)
                 this.addOverlay();
            }
 

        } else if(this.ext_images.includes(ext)){

            this.is_active_image=true;
            this.container.innerHTML = `<img src="` + url + `" width="100%" height="100%" />`;

        } else if (embed_provider){
            
            this.is_active_video=true;

            vid_width = 640;
            vid_height = 360;
            
            if(embed_provider == 'vimeo'){

                this.download_link = 'https://vimeo.com/' + url;
                this.container.innerHTML  =
                `<div id="youtubevideo" class="plyr__video-embed"><iframe src="https://player.vimeo.com/video/` + url + 
                `" allowfullscreen allow="autoplay; encrypted-media; picture-in-picture"></iframe></div>`;
            } else {
     
                this.download_link = 'https://youtube.com/watch?v=' + url;
                this.container.innerHTML  =
                `<div id="youtubevideo" class="plyr__video-embed"><iframe src="https://www.youtube.com/embed/` + url + 
                `" allowfullscreen allow="autoplay; encrypted-media; picture-in-picture"></iframe></div>`;
    
            }
	
            this.plyr = new Plyr(('#youtubevideo'), {
                controls: ['play', 'progress', 'current-time', 'mute', 'volume', 'download', 'fullscreen'],
                classNames: {tabFocus: 'plyr__nullclass'}, 
                clickToPlay: false,
                disableContextMenu: false,
                keyboard: {focused: false, global: false},
                invertTime: false,
                toggleInvert: false,
                storage: {enabled: false},
                playsinline: true,
                loop: {active: video_loop},
                volume: vol,
                muted: muted,
                youtube : { noCookie: true, rel: 0, showinfo: 0, iv_load_policy: 3, modestbranding: 1 }
            });

            this.plyr.on('ready', () => {
                player.plyr.play(); // youtube random-block-autoplay bug.

                if(embed_provider == 'youtube'){
                    $(`<button type="button" class="plyr__control" onclick="player.downloadSource()">
                    <i class="fa fa-lg fa-youtube-play"></i></button>`).insertAfter('.plyr__volume')
                } else if(embed_provider == 'vimeo'){
                    $(`<button type="button" class="plyr__control" onclick="player.downloadSource()">
                    <i class="fa fa-lg fa-vimeo-square"></i></button>`).insertAfter('.plyr__volume')
                }
                
            });

            this.plyr.on('volumechange', () => {
                setKey('volume', player.plyr.volume);
                setKey('muted', player.plyr.muted);
            });

        }





        this.vid_width = vid_width;
        this.vid_height = vid_height;


        let win_width =document.body.clientWidth; - this.container_padding;
        let win_height = document.body.clientHeight - this.container_padding;

        let left = ((win_width - vid_width)/2);
        let top =  ((win_height- vid_height)/2); 

        this.container.style.left = left + 'px';
        this.container.style.top = top + 'px';
        this.container.style.width = vid_width + 'px';
        this.container.style.height = vid_height + 'px';
        this.container.style.display='block';


        // resize
        if(vid_width > win_width || vid_height > win_height) 
        {
            var multiplier_width =  ((win_width)/(vid_width -this.container_border));
            var multiplier_height = ((win_height)/(vid_height - this.container_border));
            var count = multiplier_width<multiplier_height ? multiplier_width : multiplier_height;

            this.resizeByCenter=true;
            this.resizeContainer(count);
            this.resizeByCenter=false;
        }
 
        this.saveContainerPos();


    }

    playPrev(){
        playNext(true);
    }

    playNext(revert = false){

        let start = this.content_num;
        let end = revert ? (start - 100) : (start + 100);
        let inc = revert ? -1 : 1;
         
        start += inc;

        for(; start != end && start >= 0; start += inc){

            let content = document.getElementById('content-'+start);

            if(!content)
                continue;

            content.click();
            return;
        }

    }

    stopAll(){

        this.is_active = false;
        this.is_active_video=false;
        this.is_active_image=false;
        this.is_active_sound=false;

        if(this.plyr !== null) {
			this.plyr.destroy();
			this.plyr = null;
        }
        
        let video = document.getElementById("html5media");

		if(video) {
            video.pause();
			video.src = '';
			video.load();
			video.parentElement.removeChild(video);
		}

        this.container.innerHTML='';
        this.container.style.display='none'; 
        this.download_link=false;
        this.multiplier = 1;
    }


    eventPlayStarted(){
        console.log('eventPlayStarted');
    }

    eventVolumeChanged(){
        console.log('eventVolumeChanged');
    }

}


var player = null;

function playContent(e, url, vid_width, vid_height, embed, content_id){
    player.playContent(e, url, vid_width, vid_height, embed, content_id);
}



$(document).ready(function() {

    if(!getKey('testKey', false))
        return


    player = new MediaPlayer();
    player.load();

    // initialize listeners
    let $container = $(player.container);

    $container.on(player.is_firefox ? 'DOMMouseScroll' : 'mousewheel', function(e){

        if(!player.is_active || !player.mouse_is_hover)
            return;
            
        e.preventDefault();
        
        var evt = window.event || e; //equalize event object
        evt = evt.originalEvent ? evt.originalEvent : evt; //convert to originalEvent if possible
        var delta = evt.detail ? evt.detail*(-40) : evt.wheelDelta; //check for detail first, because it is used by Opera and FF
        if(delta > 0) {
            player.resizeContainer(player.multiplier+0.1);
        } else {
            player.resizeContainer(player.multiplier-0.1);
        }
    });

    $container.mouseover(function(){
        player.mouse_is_hover = true;
    });

    $container.mouseout(function(){
        player.mouse_is_hover = false;
    });

    $container.mousemove(function(e){
        player.mouse_pos_x = e.clientX;
        player.mouse_pos_y = e.clientY;
    });




    /*

    let links = document.getElementsByClassName('post-file-link');

    for(let i=0, l=links.length; i<l; i++){

        let source = links[i].getAttribute("href");
        let ext = source.split('.').pop(); 

        if(['mp4', 'webm', 'jpg', 'jpeg', 'bmp', 'png', 'gif', 'webp', 'tiff'].includes(ext)){

            let oldOnclick = links[i].getAttribute("onclick");
            let args = oldOnclick.split(',');

            links[i].setAttribute('onclick', 'playContent(event, "'+source+'", '+args[3]+', '+args[4]+')');
        }

    }


    let vimeo_links = document.getElementsByClassName('vimeo-link');

    for(let i=0, l=vimeo_links.length; i<l; i++)
        vimeo_links[i].setAttribute('onclick', 'playContent(event, "'+vimeo_links[i].dataset.id+'", "640", "360", "vimeo")');

*/


});
