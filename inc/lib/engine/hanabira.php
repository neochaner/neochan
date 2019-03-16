<?php







class HanabiraEngine {
	
    
    public $adminBarHTML="";
    public $styleBar = "";
    public $postFormHTML="";
    public $html="";

	public function __construct($listBoards){

        $this->adminBarHTML = '<div class="adminbar"> [';
         

        foreach($listBoards as $b){

            $this->adminBarHTML .= "<a href='/".$b['uri']."/index.xhtml' title='".$b['title']."'>/".$b['uri']."/</a>\n";

        }

        $this->adminBarHTML .= '] [ 
        <a href="/">Главная</a> | 
        <a href="/settings">Настройки</a> | 
        <a href="/bookmarks">Закладки</a> | 
        <a onclick="toggle_music_player();">Плеер</a> 
        ]
        </div>';

        $this->styleBar = '      <div class="stylebar">
        <select onchange="set_stylesheet_all($(\'#stylebar\').val())" id="stylebar">
        <option>Futaba</option>
        <option>Photon</option>
        <option>Snow</option>
        <option>Snow[animated]</option>
        </select>
        </div>';
    }

    private function fixBody($body){

        $fixed = str_replace('</blockquote><br>', '</blockquote>', $body);

        return $fixed;
    }
    
    private function utf8tohtml($utf8, $remove_extented=false) {

		$utf8 = htmlspecialchars($utf8, ENT_NOQUOTES, 'UTF-8');

		if($remove_extented)
			$utf8 = mb_encode_numericentity($utf8, array(0x010000, 0xffffff, 0, 0xffffff), 'UTF-8');

		return $utf8;
    }
    
    function markupFiles($b, $t, $p, $files){

        if(!is_array($files))
            return;

        $html = '';
        $one = count($files) == 1;

 
        foreach($files as $file){


            $src = '/' . $file->file_path;
            $thumb = '/' . $file->thumb_path;
            $w = $file->width;
            $h = $file->height;
            $name = $file->name;



            $info =   '<div class="fileinfo limited">
            Файл: <a href="'.$src.'" target="_blank">'.$name.'</a>
             <br>
             <em>'.$file->extension.', '.round(($file->size/1024), 2).' KB, '.$w.'×'.$h.'</em>
        <br>
        <a class="edit_ icon" href="/utils/image/edit/481443/8368129"><img src="/static/hanabira/blank.png" title="edit" alt="edit"></a>
        
        <a class="search_google icon" onclick="window.open(this.href, \'_blank\');return false;" 
        href="http://www.google.com/searchbyimage?image_url=https://neochan.ru'.$src.'">
        <img src="/static/hanabira/blank.png" title="Find source with google" alt="Find source with google">
        </a>

        <a class="search_iqdb icon" onclick="window.open(this.href, \'_blank\');return false;" 
        href="http://iqdb.org/?url=https://neochan.ru'.$src.'">
        <img src="/static/hanabira/blank.png" title="Find source with iqdb" alt="Find source with iqdb">
        </a>
        
        </div>';


            if($one){
                return ($info .
                '<div id="file_'.$p.'_'.$file->file_id.'" class="file">
                <a href="'.$src.'" target="_blank"><img src="'.$thumb.'" width="'.$file->thumbwidth.'" height="'.$file->thumbheight.'" class="thumb" alt="'.$name.'" 
                onclick="expand_image(event, '.$w.', '.$h.')"></a>
                </div>');
            } else {
                $html .= 
                ('<div id="file_'.$p.'_'.$file->file_id.'" class="file">'
                . $info . 
                '<a href="'.$src.'" target="_blank"><img src="'.$thumb.'" width="'.$file->thumbwidth.'" height="'.$file->thumbheight.'" class="thumb" alt="'.$name.'" 
                onclick="expand_image(event, '.$w.', '.$h.')"></a>
                </div>');
            }

        }
        
        return $html . '<br style="clear: both">';

    }

    function createPostForm($b, $t, $captcha = false){

        $html = '';
        $captcha = '';

        $html .= ('<div id="hideinfodiv" class="hideinfo rightaligned" style="display:none">
        [
        <a href="/api/board/hide/mu.redir" onclick="hide_info(event, "'.$b.'")">
          Раскрыть форму
        </a>
        ]
        <hr></div>');

        $html .= ('
<div id="postform_placeholder">
  <div class="postarea" id="postFormDiv">
    <table>
    <tbody>
	<tr>
	  <td class="hideinfo" id="hideinfotd">
	    &nbsp;[<a href="/api/board/hide/mu.redir" onclick="hide_info(event, \''.$b.'\')">
	    Скрыть форму
	    </a>]
	  </td>
	</tr>
	<tr class="topformtr">
	  <td>
	    <form id="postform" action="/post.php" method="post" enctype="multipart/form-data">
	      <input type="hidden" name="thread_id" value="'.$t.'">
	      <input type="hidden" name="task" value="post">
	      <table>
		<tbody>
		  <tr id="trname">
		    <td class="postblock">Имя</td>
		    <td>
		      <input type="text" name="name" size="35" value="Анонимус">
		    </td>
          </tr>
          
		  <tr id="trsage">
		    <td class="postblock">Не поднимать тред&nbsp;</td>
		    <td><input type="checkbox" name="sage"></td>
		  </tr>
		  <tr id="trmessage">
		    <td class="postblock">Сообщение</td>
		    <td><textarea id="replyText" name="message" cols="60" rows="6"></textarea></td>
		  </tr>
		  
		  '.$captcha.'
		  
		  <tr id="trrempass">
		    <td class="postblock">Пароль</td>
		    <td>
		      <input type="password" name="password" size="35" value="">
		    </td>
		  </tr>
		  
		  <tr id="trfile">
		    <td class="postblock">Файл</td>
		    <td id="files_parent">
		      <input id="post_files_count" type="hidden" name="post_files_count" value="1">
		      <div id="file_1_div">
			<input id="file_1" onchange="update_file_fields(event, this);" type="file" name="file_1" size="28"><select name="file_1_rating"><option>SFW</option><option>R-15</option><option>R-18</option><option>R-18G</option></select>
		      </div> 
		      <div id="file_2_div" style="display:none">
			<input id="file_2" onchange="update_file_fields(event, this);" type="file" name="file_2" size="28"><select name="file_2_rating"><option>SFW</option><option>R-15</option><option>R-18</option><option>R-18G</option></select>
		      </div> 
		      <div id="file_3_div" style="display:none">
			<input id="file_3" onchange="update_file_fields(event, this);" type="file" name="file_3" size="28"><select name="file_3_rating"><option>SFW</option><option>R-15</option><option>R-18</option><option>R-18G</option></select>
		      </div> 
		      <div id="file_4_div" style="display:none">
			<input id="file_4" onchange="update_file_fields(event, this);" type="file" name="file_4" size="28"><select name="file_4_rating"><option>SFW</option><option>R-15</option><option>R-18</option><option>R-18G</option></select>
		      </div> 
		      <div id="file_5_div" style="display:none">
			<input id="file_5" onchange="update_file_fields(event, this);" type="file" name="file_5" size="28"><select name="file_5_rating"><option>SFW</option><option>R-15</option><option>R-18</option><option>R-18G</option></select>
		      </div> 
		      <div id="file_6_div" style="display:none">
			<input id="file_6" onchange="update_file_fields(event, this);" type="file" name="file_6" size="28"><select name="file_6_rating"><option>SFW</option><option>R-15</option><option>R-18</option><option>R-18G</option></select>
		      </div> 
		      <div id="file_7_div" style="display:none">
			<input id="file_7" onchange="update_file_fields(event, this);" type="file" name="file_7" size="28"><select name="file_7_rating"><option>SFW</option><option>R-15</option><option>R-18</option><option>R-18G</option></select>
		      </div> 
		      <div id="file_8_div" style="display:none">
			<input id="file_8" onchange="update_file_fields(event, this);" type="file" name="file_8" size="28"><select name="file_8_rating"><option>SFW</option><option>R-15</option><option>R-18</option><option>R-18G</option></select>
		      </div> 
		      <div id="file_9_div" style="display:none">
			<input id="file_9" onchange="update_file_fields(event, this);" type="file" name="file_9" size="28"><select name="file_9_rating"><option>SFW</option><option>R-15</option><option>R-18</option><option>R-18G</option></select>
		      </div> 
		      <div id="file_10_div" style="display:none">
			<input id="file_10" onchange="update_file_fields(event, this);" type="file" name="file_10" size="28"><select name="file_10_rating"><option>SFW</option><option>R-15</option><option>R-18</option><option>R-18G</option></select>
		      </div> 
		    </td>
		  </tr>
		</tbody>
	      </table>
	    </form>
	  </td>
	</tr>
	<tr class="topformtr">
	  <td>
	    
<div class="rules">
<script type="text/javascript">
var files_max_qty = 10;
var upload_handler = $t()*10000;
</script>
</div>
</td>
	</tr>
      </tbody>
    </table>
  </div>
</div>
<hr class="topformtr">');

            return $html;

    }
 
    function markupPost($b, $post){

        $html = '';

        if (isset($post['files']))
            $post['files'] = is_string($post['files']) ? json_decode($post['files']) : $post['files'];

        if (isset($post['poll']))
            $post['poll'] = is_string($post['poll']) ? json_decode($post['poll'], TRUE) : $post['poll'];


        $subject = utf8tohtml($post['subject']);
        $name = utf8tohtml($post['name']);
        $trip = utf8tohtml($post['trip']);
        $modifiers = extract_modifiers($post['body_nomarkup']);
        $id = $post['id'];
        $thread = $post['thread'] == NULL ? $id : $post['thread'];
        $body = $this->fixBody($post['body']);

        $timestamp = $post['time'];
        $dt = new DateTime();
        $dt->setTimestamp($timestamp);
        $would_be = $dt->format('Y-m-d H:i:sP');


        if($post['thread'] == NULL){

            $html .= '
            <div id="post_'.$post['id'].'" class="oppost post">
            <a name="i'.$id.'"></a>
            <label>
                <a class="hide icon" onclick="hide_thread(event, \''.$b.'\','.$id.');" href="#"><img src="/static/hanabira/blank.png" title="Hide" alt="Hide"></a>
                <a class="delete icon"><input type="checkbox" name="'.$id.'" value="?????" class="delete_checkbox" id="delbox_'.$id.'"><img src="/static/hanabira/blank.png" title="Mark to delete" alt="Удалить"></a>
                <a class="unsigned icon" onclick="sign_thread(event, \''.$b.'\','.$id.');"><img src="/static/hanabira/blank.png" title="Subscribe" alt="Subscribe"></a>
                <span class="replytitle">'.$subject.'</span>
                <span class="postername">'.$name.'</span>
                <span class="postername">'.$trip.'</span>
                <time datetime="'.$would_be.'">11 May 2017 (Thu) 20:33</time>
            </label>

            <span class="reflink"><a href="/'.$b.'/res/'.$id.'.xhtml#i'.$id.'">No.'.$id.'</a></span>

            <span class="cpanel">
            <a class="reply_ icon" onclick="GetReplyForm(event, \''.$b.'\', '.$id.', '.$id.')">
            <img src="/static/hanabira/blank-double.png" style="vertical-align:sub" title="Ответ" alt="Ответ"></a>
            </span>
            <br>
            ';

  
            $html .= $this->markupFiles('kpop', $b, $thread, $post['files']);

            $html .= '<div class="postbody"><div class="message">'.$body.'</div></div>';
            $html .= '<div class="abbrev"></div>';
            $html .= '</div>';

        } else {


            $html .= '
            <table id="post_'.$id.'" class="replypost post">
            <tbody><tr>
            <td class="doubledash">&gt;&gt;</td>
            <td class="reply" id="reply'.$id.'">
            <a name="i'.$id.'"></a>

            <label>
                <a class="delete icon"><input type="checkbox" name="'.$id.'" value="323591" class="delete_checkbox" id="delbox_'.$id.'">
                <img src="/static/hanabira/blank.png" title="Mark to delete" alt="Удалить"></a>
                <span class="postername">'.$name.'</span>
                <span class="postername">'.$trip.'</span>
                <time datetime="'.$would_be.'">11 May 2017 (Thu) 20:33</time>
            </label>

            <span class="reflink">
            <a href="/'.$b.'/res/'.$thread.'.xhtml#'.$id.'" onclick="Highlight(0, '.$id.')">No.'.$id.'</a>
            </span>

            <span class="cpanel">
            <a class="reply_ icon" onclick="GetReplyForm(event, \''.$b.'\', '.$id.', '.$id.')">
            <img src="/static/hanabira/blank-double.png" style="vertical-align:sub" title="Ответ" alt="Ответ"></a>
            </span>
            <br>';

            $html .= $this->markupFiles('kpop', $b, $thread, $post['files']);

            $html .= '<div class="postbody"><div class="message">'.$body.'</div></div>';
            $html .= '<div class="abbrev"></div>';
            $html .= '</div> </td></tr></tbody></table>';


        }

  
 
        return $html;


    }

    function buildThread($posts, $board_uri,  $board_title, $board_desc){

        global $config, $board, $mod; 

        $thread_id = $posts[0]['id'];


        $postFotm = $this->createPostForm($board_uri, $thread_id);
    
        $html = ('
            <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
            <head>

            <title>'. htmlentities($board_title). '</title>

            <meta http-equiv="Content-Type" content="text/html;charset=utf-8"> 

            <link href="/static/hanabira/all.css" type="text/css" rel="stylesheet"> 
            <link href="/static/hanabira/common-0.6.1320-.css" type="text/css" rel="stylesheet">
            <link title="Futaba" href="/static/hanabira/futaba-0.6.1320.css" type="text/css" rel="stylesheet" disabled="">
            <link title="Photon" href="/static/hanabira/photon-0.6.1320.css" type="text/css" rel="alternate stylesheet">
            <link title="Snow" href="/static/hanabira/snow_static-0.6.1320.css" type="text/css" rel="alternate stylesheet" disabled="">
            <link title="Snow[animated]" href="/static/hanabira/snow-0.6.1320.css" type="text/css" rel="alternate stylesheet" disabled="">

            <script type="text/javascript" src="/static/hanabira/all.js"></script>
            <script type="text/javascript" src="/static/hanabira/jquery-1.3.2.js"></script>
            <script type="text/javascript" src="/static/hanabira/jquery.form-3.51.js"></script>
            <script type="text/javascript" src="/static/hanabira/jquery.progressbar-2.0.js"></script>
            <script type="text/javascript" src="/static/hanabira/jquery.jplayer.mod-0.2.5.js"></script>
            <script type="text/javascript" src="/static/hanabira/music-0.6.1320-.js"></script>
            <script type="text/javascript" src="/static/hanabira/hanabira-0.6.1320-.js"></script>
            <script type="text/javascript">
            Hanabira.LC_ru = 1;
            Hanabira.ScrollAny = 0;
            var play_list = [];

            </script>
            </head>

            <body onload="initialize()">' .  $this->adminBarHTML .  $this->styleBar . '
            
       
            <div class="logo">
     	    <br>
     Неочан — '. $board_title . '
	        <br><span class="description">'. $board_desc .'</span>
            </div>


            <div id="jquery_jplayer" style="position: absolute; top: 0px; left: 0px;">
            <audio id="jqjp_audio_1"></audio><div id="jqjp_force_1" style="text-indent: -9999px;"></div>
            </div>

            
            <div id="music_player" style="display:none;">
            <hr/>
            <div id="player_container">
              <ul id="player_controls">
                <li id="player_play">play</li>
                <li id="player_pause">pause</li>
                <li id="player_stop">stop</li>
                <li id="player_volume_min">min volume</li>
                <li id="player_volume_max">max volume</li>
                <li id="ctrl_prev">previous</li>
                <li id="ctrl_next">next</li>
              </ul>
          
              <div id="play_time"></div>
              <div id="total_time"></div>
              <div id="player_progress">
                <div id="player_progress_load_bar">
              <div id="player_progress_play_bar"></div>
                </div>
              </div>
              <div id="player_volume_bar">
                <div id="player_volume_bar_value"></div>
              </div>
          
          
            </div>
            <div id="playlist_list">
              <ul></ul>
            </div>
            <div id="jplayer_info"></div>
          </div>
          <hr>
            ' . $this->createPostForm($board_uri, $thread_id));



        $html .= '<form id="delete_form" action="/wn/delete" method="post">';
        $html .= '<div class="thread" id="thread_23003">';

        foreach($posts as $post){
            $html .= $this->markupPost('kpop', $post);
        }
         

        $html .= '</div>
        <br clear="left">
        <hr>
        </form>';


        $html .=$this->adminBarHTML;
        $html .=$this->styleBar;


        $html .= '<br><p class="footer">
        - hanabira 0.6.1320- + <a href="http://wakaba.c3.cx/">wakaba</a> + <a href="http://www.1chan.net/futallaby/">futallaby</a> + <a href="http://www.2chan.net/">futaba</a> -
        </p>
        </body>';
 
        return $html;

        

    }
}


