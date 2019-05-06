var audio_player;
var selectedAudioSource;
var selectedAudioName;
var audioPlayerPosKey = 'audioPlayerPos';
var music_pseudo_bars = '<div class="bars"><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div></div>';

window.onresize = function(event) {
    fixAudioPlayerPos();
};

function getPlayerSourceElement() 
{
    var embedLink = $('.audio-url[data-url="'+audio_player.source+'"]');

    if(embedLink.length==1)
    {
        return embedLink;
    }
    else
    {
        var current_source_string = audio_player.source.split(document.location.hostname).pop();
        return $('.audio-url[data-url="' + current_source_string + '"]');
    }
}

function initAudioPlayerEventListeners() {

    audio_player.on('pause', function() {
        var current_source_element = getPlayerSourceElement();
        current_source_element.addClass('paused').find('.bars').addClass('stopped');
    });

    audio_player.on('play', function() {
        var current_source_element = getPlayerSourceElement();
        current_source_element.removeClass('paused').find('.bars').removeClass('stopped');
    });
	
	audio_player.on('ended', function() {
           var current_source_element = getPlayerSourceElement();
		   var source_elements = $(current_source_element).closest('.post-files').find('.audio-url');
		   
		   for(let i =0; i<source_elements.length-1 ; i++)
			   if(current_source_element[0].dataset.url == source_elements[i].dataset.url)
				   source_elements[i+1].click();
		   
    });
	
}

$(document).on('click', '.plyr__control.close', function() {
    closeAudioPlayer();
});

$(document).on('click', '.audio-url', function(e) {

    selectedAudioSource = $(this).attr('data-url');
    selectedAudioName =  $($(this)[0].closest('.post-file')).find('.post-file-name').text();

    var extension = selectedAudioSource.split('.').pop();
    let media_type = extension == 'm4a' ? 'audio/mpeg' : 'audio/' + extension;

    if($('*').is('.plyr--audio')) {

        var current_source_element = getPlayerSourceElement();
      
        if(e.target !== current_source_element[0] && !current_source_element[0].contains(e.target)) {

            player.stopAll(false);

            current_source_element.removeClass('playing paused').find('.bars').remove();
            $(this).addClass('playing').append(music_pseudo_bars);
          
            audio_player.source = {
                type: 'audio',
                sources: [{
                    src: selectedAudioSource,
                    type: media_type
                }]
            };

            audio_player.play();

            $('.plyr--audio > .plyr__controls').append(
                '<button class="plyr__control download" onclick="downloadCurrentTrack()"><i class="fa fa-download" aria-hidden="true"></i></button>'+
                '<button class="plyr__control close" id="close-plyr__audio"><i class="fa fa-times" aria-hidden="true"></i></button>'
            );
            $('.plyr__controls').css('padding', '6px');


        } else {
            if($(this).hasClass('playing')) {
                $(this).removeClass('playing').addClass('paused').find('.bars').addClass('stopped');
                audio_player.pause();
            } else if($(this).hasClass('paused')) {
                $(this).removeClass('paused').addClass('playing').find('.bars').removeClass('stopped');
                audio_player.play();
            }
        }
    } else {
        
        player.stopAll(false);

        $('body').append('<audio id="audio-player" controls><source src="' + selectedAudioSource + '" type="' + media_type + '"></audio>');
        const options = {
            settings: [],
        };
        audio_player = new Plyr('#audio-player', options); 
        

        audio_player.play();
        $(this).addClass('playing').append(music_pseudo_bars);

        initAudioPlayerEventListeners();

        $('.plyr--audio > .plyr__controls').append(
            '<button class="plyr__control download" onclick="downloadCurrentTrack()"><i class="fa fa-download" aria-hidden="true"></i></button>'+
            '<button class="plyr__control close" id="close-plyr__audio"><i class="fa fa-times" aria-hidden="true"></i></button>'
        );
        $('.plyr__controls').css('padding', '6px');

        loadAudioPlayerPos();
    }
});


function closeAudioPlayer() {

    if(audio_player) {
        audio_player.destroy();
        let current_source_element = getPlayerSourceElement();
        current_source_element.removeClass('playing paused').find('.bars').remove();
        $('#audio-player').remove();
        audio_player=null;
    }
  
}

function downloadCurrentTrack()
{
    var link = audio_player.media.currentSrc;

    var a = document.createElement("a")
    a.setAttribute("href", selectedAudioSource);
    a.setAttribute("download", selectedAudioName);  
    a.setAttribute("type", "hidden");
    document.body.appendChild(a);

    a.click();
    a.remove();
  
}

function resizeAudioPLayer( delta)
{
    var step = 20;
    var $plyr = $('.plyr');
    var curWidth = $plyr.width() ; 
    var left = parseFloat( $plyr.css('left').replace('px', ''));

    if(delta > 0)
        step = -step;

    $plyr.css('width', (curWidth-step)+'px');
    $plyr.css('left', (left+step/2)+'px');

    fixAudioPlayerPos();
    saveAudioPlayerPos();

}


function loadAudioPlayerPos(){

    var width = $(window).width();
    var height = $(window).height();
    var $plyr = $('.plyr');

    var plyrWidth = $plyr.width();
    var plyrHeight = $plyr.height();
    
    var default_left = width - plyrWidth - 25;
    var default_top = height - plyrHeight - 45;

    var default_coords = {
        'lastWidth' :  width, 
        'lastHeight' :  height, 
        'left' :  default_left, 
        'top' :  default_top, 
        'playerWidth' : 430,
    }
 
  
    var coords = getKey(audioPlayerPosKey, default_coords);

    if(coords['lastWidth'] != width || coords['lastHeight'] != height)
    {
        coords = default_coords;
    }
   
    $plyr.css({
        'left': coords['left'],
        'top' : coords['top'],
        'width': coords['playerWidth']+'px',
    });

    fixAudioPlayerPos();

    set_draggable($plyr[0], false, ['input'], function(){
        fixAudioPlayerPos();
        saveAudioPlayerPos();
    });

}

function saveAudioPlayerPos()
{
    setKey(audioPlayerPosKey, {
        'lastWidth' : $(window).width(), 
        'lastHeight' : $(window).height(), 
        'left' :  $('.plyr').css('left'), 
        'top' :  $('.plyr').css('top'), 
        'playerWidth' : $('.plyr').width(),

    });
}

function fixAudioPlayerPos()
{

    var $plyr=false;
    let els = document.getElementsByClassName('plyr');
    for(let i=0;  i<els.length; i++){
        if(els[i].getElementsByTagName('audio').length==1)
        $plyr = $(els[i]);
    }

    if(!$plyr)
        return false;

    var width = $(window).width();
    var height = $(window).height(); 
    var left = parseFloat( $plyr.css('left').replace('px', ''));
    var top =  parseFloat( $plyr.css('top').replace('px', ''));
    var playerWidth = parseFloat( $plyr.css('width').replace('px', ''));


    // check width 
    if(playerWidth > width)
    {
        $plyr.css('width', width+'px');
        playerWidth = width;
    }

    if(left < 0)
    {
        $plyr.css('left', '0px');
        left = 0;
    }

    if(top < 20)
    {
        $plyr.css('top', '20px');
        top = 0;
    }


    // check x position
    var x = width - (left + playerWidth);

    if(x < 0)
    {
        $plyr.css('left', (left + x)+'px');
    }

    // check y position
    var y = height - (top + 40);

    if(y<0)
    {
        $plyr.css('top', (top + y)+'px');
    }

    

}


