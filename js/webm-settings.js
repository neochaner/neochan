/* This file is dedicated to the public domain; you may do as you wish with it. */

 


$(document).ready(function(){

menu_add_raw('<br><input type="range" id="videovolume" min="0" max="1" step="0.01" style="width: 4em; height: 1ex; vertical-align: middle; margin: 0px;" value="'+getKey('webm_vol', 1)+'" onchange="setKey(\'webm_vol\', this.value);"><label class="l_video_vol ml10"></label>');


/*
var div = prefix
    + '<div style="'+style+'">'
    + '<label><input type="checkbox" name="videoexpand">'+_('Expand videos inline')+'</label><br>'
    + '<label><input type="checkbox" name="videohover">'+_('Play videos on hover')+'</label><br>'
    + '<label><input type="checkbox" name="videoloop">'+_('Loop videos by default')+'</label><br>'
    + '<label><input type="range" name="videovolume" min="0" max="1" step="0.01" style="width: 4em; height: 1ex; vertical-align: middle; margin: 0px;">'+_('Default volume')+'</label><br>'
    + suffix;*/


});



