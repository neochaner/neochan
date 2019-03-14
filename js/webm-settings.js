
$(document).ready(function(){

menu_add_raw('<br><input type="range" id="videovolume" min="0" max="1" step="0.01" style="width: 4em; height: 1ex; vertical-align: middle; margin: 0px;" value="'+getKey('webm_vol', 1)+'" onchange="setKey(\'webm_vol\', this.value);"><label class="l_video_vol ml10"></label>');

});



