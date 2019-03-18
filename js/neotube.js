var PLAYER_IS_READY = false;
var PLAYER_IS_RESET = false;
var PSTATE_NOT_INITIALIZED=-2;
var PSTATE_INITIALIZATING=-1;
var PSTATE_PLAYING = 1;
var PSTATE_PAUSE = 2;
var PSTATE_ENDED = 3;
var PSTATE_STOPPED = 4;
var PSTATE_UNKHOWN = 10;

var NTUBE_STATE = 0;
var NSTATE_NOT_LOADED = 0;
var NSTATE_LOADED_SMALL = 1;
var NSTATE_LOADED_MEDIUM = 2;

var savedCss = [0,0,0,0,0,0,0,0,0,0,0,0];



var player;
var activeTrack=false;
var currentList=[]; 

var playerDiv = false; 
var oldPLayerWidth=false;
var resizeInterval = false;
var syncInterval=false;
var neotubeAccess=false;



$(document).ready(function() {

    if(!config.neotube)
        return;

    savedCss[0] = $('.board').css('padding');
    savedCss[1] = $('hr').css('margin');


    if(config.active_page == 'thread'){

        if(document.location.href.includes('#neotube'))  
            neotubeSetup();

        $('#toggle-neotube').show();
    }
  
    $('#btn-neotube').show();
    

});

$(document).on('new_post', function(e,post) {

    if(NTUBE_STATE == NSTATE_LOADED_SMALL)
        $(post).addClass('post-small');
});
  
$(window).resize(function() {
    if(NTUBE_STATE != NSTATE_NOT_LOADED)
        resizeNeotubePlayer();
});

 
  

function neotubeSetup(){

    neotubeAccess = false;

    let thread_id = $('#thread_id').data('id');

    var fdata = new FormData();        
    fdata.append( 'action',  'neotube_rights');
    fdata.append( 'board', config.board_uri);
    fdata.append( 'thread_id', thread_id);
    fdata.append( 'posts_id',  thread_id);
    fdata.append( 'trip',   localStorage.name);
    fdata.append( 'json_response', true);
    
    $.ajax({
        url: config.root+'opmod.php',
        type: 'POST',
        contentType: 'multipart/form-data', 
        data: fdata,
        success: function(response, textStatus, xhr) {
    
            //if(!response.success && response.error)
            //    lalert(response);

            if(response.success)
                neotubeAccess = response.rights;

            initializeNeoTube();
     
        },
        error: function(xhr, status, er) {
            alert(_T('Сервер вернул ошибку: ') + er);
        },
        contentType: false,
        processData: false
    }, 'json');


}

/* 
    size :
        1 = small size
        2 = medium size
*/
function initializeNeoTube(size = 1){

    if(NTUBE_STATE == NSTATE_LOADED_SMALL)
        return;

    if(typeof (disable_replybox_float) != 'undefined'){
        disable_replybox_float(true);
    }

    NTUBE_STATE = NSTATE_LOADED_SMALL;
    POST_AUTO_SCROLL = true;
    POST_AUTO_SCROLL_BOTTOM=300;
    autoLoadSec = 2;

    $('hr').css('margin', '');
    $('.threads-container').css('width', '300px'); 
    $('.board').css('padding', '0 16px 0 8px');
    $('.post').addClass('post-small');
    $('.reply').addClass('reply-small');
    $('.captcha-box').addClass('captcha-box-small');
    
    $('.reply-footer-control').css('margin-right', '10px');
    $('.board').css('float', 'right');
    $('.board').css('max-width', '312px');
    $('#nav_bottom').find('.button').hide()
    $('#nav_bottom').css('height', '14px');
    $('#neotube').remove();
    $('#replybox').css('padding-left', '2px');
    
     
    var palyerWidth = $(window).width() - $('.board').width() - 30;

    $('body').append(
    `<div id='neotube' style='position: fixed; height:100px; width: `+palyerWidth+`px; top:34px; left:5px'>
        <div class='neotube-header' style='background-color: #50505069;display:none'></div>
        <div id='neotube-player' style=' display: block;margin: 0 auto;max-width: 100%;'><video id='pplayer' controls crossorigin playsinline poster='/stylesheets/img/cinema_background.jpg'></div>
        <div class='plyr-playlist-wrapper'><ul class='plyr-playlist'></ul></div>
    </div>`);

    resizeNeotubePlayer();

    set_draggable('neotube');

    controls =  `<div class="plyr__controls">`;

    if(neotubeAccess){
  
    controls += `

    <button type="button" class="plyr__control" aria-label="Play, {title}" data-plyr="play" onclick="neotubePlayOrPause()">
        <i class="fa fa-lg fa-play" role="presentation" id='pl-play'></i>
        <span class="plyr__tooltip" role="tooltip" id='plt-play'>`+_T('Воспроизвести')+`</span>
    </button>

    <button type="button" class="plyr__control" onclick="neotubeSkipCurrent()" id='neotubeSkipCurrent'>
        <svg role="presentation"><use xlink:href="#plyr-fast-forward"></use></svg>
        <span class="plyr__tooltip" role="tooltip">`+_T('Следующее видео')+`</span>
    </button>
    <button type="button" class="plyr__control" onclick="neotubeAddYoutubeVideo()">
    <i class='fa fa-lg fa-youtube'></i>
    <span class="plyr__tooltip" role="tooltip">`+_T('Добавить Youtube видео')+`</span>
    </button>

    <button type="button" class="plyr__control">
    <form id='neotube-upload-form' enctype="multipart/form-data" action="/opmod.php" method="POST">
    <label class='hide_upload'><input type="file" name="file" id='neotube-file'>
           <i class='fa fa-lg fa-upload' style='cursor:pointer'></i>
           <span class="plyr__tooltip" role="tooltip">`+_T('Загрузить видео')+`</span>
    </label>
    </form>
    </button>`;

    }
    
    controls += `
    <div class="plyr__progress">
        <input data-plyr="seek" type="range" min="0" max="100" step="0.01" value="0" aria-label="Seek">
        <progress class="plyr__progress__buffer" min="0" max="100" value="0">% buffered</progress>
        <span role="tooltip" class="plyr__tooltip">00:00</span>
    </div>
    <div class="plyr__time plyr__time--current" aria-label="Current time" `+ (neotubeAccess ? '' : "style='padding-left: 12px;'") +`>00:00</div>

    <button type="button" class="plyr__control" aria-label="Mute" data-plyr="mute">
        <svg class="icon--pressed" role="presentation"><use xlink:href="#plyr-muted"></use></svg>
        <svg class="icon--not-pressed" role="presentation"><use xlink:href="#plyr-volume"></use></svg>
        <span class="label--pressed plyr__tooltip" role="tooltip">Unmute</span>
        <span class="label--not-pressed plyr__tooltip" role="tooltip">Mute</span>
    </button>
    <div class="plyr__volume">
        <input data-plyr="volume" type="range" min="0" max="1" step="0.05" value="1" autocomplete="off" aria-label="Volume">
    </div>

    <button type="button" class="plyr__control" data-plyr="fullscreen">
        <svg class="icon--pressed" role="presentation"><use xlink:href="#plyr-exit-fullscreen"></use></svg>
        <svg class="icon--not-pressed" role="presentation"><use xlink:href="#plyr-enter-fullscreen"></use></svg>
        <span class="label--pressed plyr__tooltip" role="tooltip">`+_T('Полноэкранный режим')+`</span>
        <span class="label--not-pressed plyr__tooltip" role="tooltip">`+_T('Полноэкранный режим')+`</span>
    </button>
</div>
`;






    player = new Plyr('#pplayer', { controls });


    lastReply();
    manualLoad();


    player.on('ready', event => {
        PLAYER_IS_READY = true;
        player.config.invertTime=false;

        let fileUpload = document.getElementById("neotube-file");
        
        if(fileUpload){
            fileUpload.onchange = function() {
                neotubeUploadFile();
            };
        }

        oldPLayerWidth = playerDiv.css('width').replace('px', '');
    });


    player.on('play', event => {

        $('#pl-play').removeClass('fa-play').addClass('fa-pause');
        $('#plt-play').html(_T('Пауза'));
    });

    player.on('pause', event => {

        $('#pl-play').removeClass('fa-pause').addClass('fa-play');
        $('#plt-play').html(_T('Воспроизвести'));
    });



    playerDiv = $('#neotube').find('.plyr');

    if(resizeInterval)
        clearInterval(resizeInterval);

    if(syncInterval)
        clearInterval(syncInterval);

    resizeInterval = setInterval(() => {
        
        if(oldPLayerWidth){

            var width = playerDiv.css('width').replace('px', '');

            if(width != oldPLayerWidth){
                oldPLayerWidth = width;
                resizeNeotubePlayer();
            }
        }
    }, 500);
     

    syncInterval = setInterval(neotubeSync, 1000);
     

} 

function removeNeoTube(){

    NTUBE_STATE = NSTATE_NOT_LOADED;
    config.replybox_close = false;
    POST_AUTO_SCROLL = false;
    autoLoadSec = autoLoadSecDefault;

    $('.board').css('padding', savedCss[0]);
    $('hr').css('margin', savedCss[1]);

    $('.threads-container').css('width', '');  
    $('.post').removeClass('post-small');
    $('.reply').removeClass('reply-small');
    $('.captcha-box').removeClass('captcha-box-small');
    $('.post').removeClass('post-medium');
    $('.reply').removeClass('reply-medium');
    $('.board').css('float', '');
    $('.board').css('max-width', '');
    $('#nav_bottom').find('.button').show()
    $('#nav_bottom').css('height', '');
    $('#neotube').remove();
    $('#replybox').css('padding-left', '');

    clearInterval(resizeInterval);
    clearInterval(syncInterval);
    
}

function resizeNeotubePlayer(){

    var playerWidth = $(window).width() - $('.board').width() - 30;
    $('#neotube').width(playerWidth);
    $('#neotube-player').width(playerWidth);


    //$('#neotube').width(playerWidth);
    //$('#neotube-player').width(playerWidth);

    if(!playerDiv)
        return;

    $('.plyr-playlist-wrapper').css('left', playerDiv.css('left'));
    $('.plyr-playlist-wrapper').css('top', playerDiv.css('top'));


    var width = playerDiv.css('width').replace('px', '');

    if(width >  playerWidth)
        $('.plyr-playlist-wrapper').css('width', (playerWidth)+'px');
    else 
        $('.plyr-playlist-wrapper').css('width', (width-11)+'px');

 
}

function toggleNeoTube() {

    if(config.active_page == 'thread'){
        switch(NTUBE_STATE){
            case NSTATE_NOT_LOADED:
                neotubeSetup();
                //initializeNeoTube();
                break;
            case NSTATE_LOADED_SMALL:
                removeNeoTube();
                break;
        }
    } 

}

function searchTubes(){

    var fdata = new FormData();        
    fdata.append( 'get_playlists',  '');
    fdata.append( 'board', config.board_uri);

    $.ajax({
        url: config.root+'api.php',
        type: 'POST',
        contentType: 'multipart/form-data', 
        data: fdata,
        success: function(response, textStatus, xhr) {

            if(!response.success && response.error)
                lalert(response);
            else if(response.html)
                alert(response.html);
        },
        error: function(xhr, status, er) {
            alert(_T('Сервер вернул ошибку: ') + er);
        },
        contentType: false,
        processData: false
    }, 'json');
}
 

 


function neotubeSkipCurrent(){

    $('#neotubeSkipCurrent').hide();
    setTimeout(function(){$('#neotubeSkipCurrent').show();}, 5000);

    neotubeRemoveTrack(activeTrack.id);
}


function neotubeAddYoutubeVideo(){

    var youtubeLink = prompt(_T("Введите ссылку на ролик Youtube"), '');

    if (youtubeLink != null && youtubeLink == "") 
        return false;

    let thread_id = $('#thread_id').data('id');

    var fdata = new FormData();        
    fdata.append( 'action',  'add_playlist');
    fdata.append( 'board', config.board_uri);
    fdata.append( 'thread_id', thread_id);
    fdata.append( 'posts_id',  thread_id);
    fdata.append( 'trip',   localStorage.name);
    fdata.append( 'link', youtubeLink);
    fdata.append( 'json_response', true);
    
    
    $.ajax({
        url: config.root+'opmod.php',
        type: 'POST',
        contentType: 'multipart/form-data', 
        data: fdata,
        success: function(response, textStatus, xhr) {
    
            if(!response.success && response.error)
                lalert(response);

            if(response.success && response.playlist != null)
                neotubeUpdatePlayList(response.playlist);
     
        },
        error: function(xhr, status, er) {
            alert(_T('Сервер вернул ошибку: ') + er);
        },
        contentType: false,
        processData: false
    }, 'json');


}


function neotubePlayEvent(){
   player.play();
   neotubePlayOrPause();
}

function neotubePauseEvent(){
   player.pause();
   neotubePlayOrPause();
}

function neotubePlayOrPause(){


    let thread_id = $('#thread_id').data('id');

    var fdata = new FormData();        
    fdata.append( 'action',  'pause_track');
    fdata.append( 'board', config.board_uri);
    fdata.append( 'thread_id', thread_id);
    fdata.append( 'posts_id',  thread_id);
    fdata.append( 'trip',   localStorage.name);
    fdata.append( 'json_response', true);

    $.ajax({
        url: config.root+'opmod.php',
        type: 'POST',
        contentType: 'multipart/form-data', 
        data: fdata,
        success: function(response, textStatus, xhr) {

            if(!response.success && response.error)
                lalert(response);

            if(response.success && response.playlist != null)
                neotubeUpdatePlayList(JSON.parse(response.playlist));
 
        },
        error: function(xhr, status, er) {
            /*alert(_T('Сервер вернул ошибку: ') + er);*/
        },
        contentType: false,
        processData: false
    }, 'json');


}


function neotubeUpdatePlayList(playList){
 
    if(playList == null || playList.length == 0){
        // stop video
        currentList = playList;
        activeTrack = null;
        neotubeReset();
        return;
    }

    if(JSON.stringify(currentList) == JSON.stringify(playList)){

        if(activeTrack == null || activeTrack.ended + 3 < getServerTime())
            return;
    }


    currentList = [];
    let is_paused = false;
    var $plist = $('.plyr-playlist');
    $plist.empty(); 


    for(var i=0; i<playList.length; i++){

        if(playList[i].pause != -1)
            is_paused = true;

        if(playList[i].end < getServerTime() && !is_paused)
            continue;

        currentList.push(playList[i]);


        $plist.append("<li class=''><div class='track-item'><a href='#' data-type='youtube' data-video-id='"+ playList[i].id +"'><img class='plyr-miniposter' src='https://img.youtube.com/vi/"+playList[i].id+"/hqdefault.jpg'>"+secToTime(playList[i].duration) + '  ' + playList[i].title+"</a></div></li>");

    }

    if(currentList.length == 0){
        activeTrack = null;
        neotubeReset();
        return;
    }


    neotubeSync()



}



function neotubeSync(){

    let serverTime = getServerTime();
    let currentTrack = getCurrentTrack();

    if(currentTrack == null){
        return true;
    }

    PLAYER_IS_RESET = false;
	let startTime = 0;
    var endTime =  currentTrack ? currentTrack.end - serverTime : 0;
    var isPaused = currentTrack && currentTrack.pause != -1;	
 
    if(currentTrack && currentTrack.start < serverTime)
         startTime = serverTime - currentTrack.start;

    
    var state = getPlayerState();




    //  LOADING 
    if(state == PSTATE_NOT_INITIALIZED){
        //setupPlayer(currentTrack, isPaused, startTime);
        return true;

    } else if(state == PSTATE_INITIALIZATING){
        return true;
    }




    // CHANGE TRACK
    if(!isPaused && !isCurrentTrack(currentTrack)){
        
        activeTrack = currentTrack;
        neotubeLoadVideo(currentTrack, isPaused, startTime);
        return true;
    }
        




	if(isPaused){
		
		if(state == PSTATE_PLAYING){

            player.pause();
            player.currentTime = Math.round(currentTrack.pause);
        }

        if(player.currentTime != Math.round(currentTrack.pause)){
             
            //player.currentTime = 0;// Math.round(currentTrack.pause);
            //player.forward(currentTrack.pause);
            //player.currentTime = Math.round(currentTrack.pause);
        }
    
        
	}
	else if(!isPaused && state == PSTATE_PAUSE){
        player.play(); 
    }
    // check sync track
    else if(isCurrentTrack(currentTrack)){

        let currentSec =serverTime - currentTrack.start;
        let prip =  player.currentTime - currentSec;

		if( prip > 2 || prip < -2)
            player.currentTime = currentSec;

    }
	// video ended
	else if(state == PSTATE_ENDED){
        
        if(endTime < 3){
            //setTimeout(syncThread, 1000);
        } else{

        //player.loadVideoById(currentTrack.id);
        playerChangeVideo(currentTrack, isPaused, startTime);
         
        }
	}



}

 













function neotubeUploadFile(form){


    var fdata = new FormData(document.querySelector('#neotube-upload-form'))
            

    let thread_id = $('#thread_id').data('id');
    fdata.append( 'action',  'upload_track');
    fdata.append( 'board', config.board_uri);
    fdata.append( 'thread_id', thread_id);
    fdata.append( 'posts_id',  thread_id);
    fdata.append( 'trip',   localStorage.name);
    fdata.append( 'json_response', true);



	var updateProgress = function(e) {
		var percentage;

		if (e.position === undefined) { // Firefox
			percentage = Math.round(e.loaded * 100 / e.total);
		} else { // Chrome?
			percentage = Math.round(e.position * 100 / e.total);
		}

		//$(form).find('.reply-send-button').val((_T('Ждём')+'... (#%)').replace('#', percentage));
	};

	$.ajax({
		url: config.root + 'opmod.php',
		type: 'POST',
		/*xhr: function() {
			var xhr = $.ajaxSettings.xhr();

			if(xhr.upload)
				xhr.upload.addEventListener('progress', updateProgress, false);

			return xhr;
		},*/
		data: fdata,
		cache: false,
		contentType: false,
		processData: false,
		dataType: 'json'
	}).done(function(response) {

        if(!response.success && response.error)
            lalert(response);
        
         

	}).fail(function(jqXHR, textStatus, errorStatus) {

		infoAlert(textStatus + " : " + errorStatus);

	}).always(function() {
        document.getElementById("neotube-file").value = "";
	});











    

}

function neotubeLoadVideo(currentTrack, isPaused, startTime){

    if(currentTrack.type == 'youtube'){
        player.source = {
            type: 'video',
            sources: [
                {
                    src: currentTrack.id,
                    provider: 'youtube',
                },
            ],
        };
    }

    if(currentTrack.type == 'file'){
        player.source = {
            type: 'video',
            sources: [
                {
                    src: '/' + currentTrack.path,
                    type: currentTrack.mime,
                    size: 720,
                },
            ],
        };
    }

    player.play();

    if(startTime>0)
        player.currentTime = startTime;
}

function getCurrentTrack(){

    let serverTime = getServerTime();
    let currentTrack = null;

    for(var i=0; currentList && i<currentList.length; i++){

        if(currentList[i].pause != -1)
            return currentList[i];

        if(currentTrack == null && currentList[i].start < serverTime && currentList[i].end > serverTime)
            currentTrack = currentList[i];
    }
	
	if(currentTrack == null){
		for(var i=0; currentList && i<currentList.length; i++){

        if(currentTrack == null && (currentList[i].start < serverTime+1) && currentList[i].end > serverTime)
            currentTrack = currentList[i];
		}
	}
    
    return currentTrack;
}


function isCurrentTrack(currentTrack){

    if(!activeTrack)
        return false;

    return activeTrack.id == currentTrack.id;
}

function neotubeReset(){

 
    if(PLAYER_IS_RESET)
        return;

    PLAYER_IS_RESET = true;

    player.source = {
        type: 'video',
        poster: '/stylesheets/img/cinema_background.jpg',
        sources: [
            {
                src: '',
                type: '',
                size: 720,
            },
        ],
    };

    player.stop();

    $('.plyr-playlist').html('');

}

function getPlayerState(){

    if(!player)
        return PSTATE_NOT_INITIALIZED;
    
    if(!PLAYER_IS_READY)
        return PSTATE_INITIALIZATING;

    if(PLAYER_IS_RESET)
        return PSTATE_ENDED;

    if(player.playing)
        return PSTATE_PLAYING;

    if(player.paused)
        return PSTATE_PAUSE;

    if(player.ended)
        return PSTATE_ENDED;

    if(player.stopped)
        return PSTATE_STOPPED;

    return PSTATE_UNKHOWN;

} 

function neotubeRemoveTrack(id){

    let thread_id = $('#thread_id').data('id');

    var fdata = new FormData();  
    fdata.append( 'action',  'remove_track');
    fdata.append( 'board', config.board_uri);
    fdata.append( 'thread_id', thread_id);
    fdata.append( 'posts_id',  thread_id);
    fdata.append( 'trip',   localStorage.name);
    fdata.append( 'id', id);
    fdata.append( 'json_response', true);

 
    $.ajax({
        url: config.root+'opmod.php',
        type: 'POST',
        contentType: 'multipart/form-data', 
        data: fdata,
        success: function(response, textStatus, xhr) {

            if(!response.success && response.error)
                lalert(response);

                if(response.success && response.playlist != null)
                neotubeUpdatePlayList(JSON.parse(response.playlist));
 
        },
        error: function(xhr, status, er) {
            /*alert(_T('Сервер вернул ошибку: ') + er);*/
        },
        contentType: false,
        processData: false
    }, 'json');


}

function secToTime(timesec){

    let min = Math.floor(timesec/60);
    let mins = min.toString()
    let sec = timesec - (min*60);
    let secs = sec.toString()

    return '[' +  (min < 10 ? '0'+mins : mins) + ':' +  (sec < 10 ? '0'+secs : secs) + ']';

}
