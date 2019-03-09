
$(document).ready(function(){
    
    var posts = document.getElementsByClassName('post')

    for(var i =0; i<posts.length; i++)
    {
        reloadPlatList(posts[i])
    }	
	
});

$(document).on('new_post', function(e,post ) {
	reloadPlatList(post);
});



function reloadPlatList(post)
{

    var audios = post.getElementsByTagName('audio');
	if(audios.length==0)
		return;
    var defaultVolume = getKey('webm_vol', 0.5);

    for(var j =0; j<audios.length; j++)
    {
        audios[j].volume = defaultVolume;
        audios[j].setAttribute('id', post.dataset.post + 'Audio__'+ j);

        audios[j].addEventListener("ended", function(e) {
            this.currentTime = 0;
            play_next_tag(this);
        }, false);

        audios[j].onplaying = function() {
                stopAllAudio(this.id);
        };
    }
}

function stopAllAudio(exclude_id)
{
   
    var audios = document.getElementsByTagName('audio');

    for(var j =0; j<audios.length; j++)
    {
        if(audios[j].currentTime > 0 && audios[j].id != exclude_id)
        {
            audios[j].currentTime = 0;
            audios[j].pause();
        }
    }
}

 

function play_next_tag(audio)
{
    var ptag = $(audio).attr('id');
    var ptagArray = ptag.split('__');
    var next = ptagArray[0] + '__' + (parseInt(ptagArray[1])+1);
    var nextAudio = $('#' +next);

    if(nextAudio.length == 1)
        nextAudio[0].play();

}



