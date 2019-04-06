<?php
require 'inc/functions.php';
require 'inc/mod/pages.php';


header('Content-Type: text/json; charset=utf-8');
$_POST['json_response'] = 1;

 

if (isset($_POST['post_id']) && !is_numeric($_POST['post_id'])) {
    error($config['error']['wrong_params']);
}

if (empty($_POST['board']) || !openBoard($_POST['board'])) {
    error($config['error']['wrong_params']);
}

if (isset($_POST['thread_id']) && !is_numeric($_POST['thread_id'])) {
    error($config['error']['wrong_params']);
}

if ($_POST['action'] == 'get_deleted') {
    get_posts_from_hell($_POST['thread_id']);
    exit;
}


// авторизируемся модератором
check_login(false, true);

if ($mod != null) {

    if(
    !hasPermission($config['mod']['ban']) ||
    !hasPermission($config['mod']['unban']) ||
    !hasPermission($config['mod']['delete']) ||
    !hasPermission($config['mod']['unban']))
        error($config['error']['noaccess']);

}

 

if ($mod == null) {
    // авторизируемся оп-модератором
    check_opmod_login();

    if ($mod == null) {
        error($config['error']['noaccess'] );
    }

    $thread_access_id = "{$board['uri']}_{$_POST['thread_id']}";

    // проверяем можем ли мы модерировать тред
    if (isset($_POST['thread_id'])) {
        if (!in_array($thread_access_id , $mod['threads'])) {
            error($config['error']['noaccess']);
        }
    }

    // проверяем относится ли пост к треду
    if (isset($_POST['post_id'])) {

        $query = prepare(sprintf("SELECT * FROM `posts_%s` WHERE `id` = :post_id", $board['uri']));
        $query->bindValue(':post_id', $_POST['post_id'], PDO::PARAM_INT);
        $query->execute() or error(db_error($query));

        if ($post = $query->fetch(PDO::FETCH_ASSOC)) { 
            if ($post['thread'] !== $_POST['thread_id']) {
                error($config['error']['noaccess']);
            }
        } else {
            error($config['error']['noaccess']);
        }
    }
}



switch ($_POST['action']) {

    case 'login':
        $boards = isset($mod['boards']) ? $mod['boards'] : [];
        $threads = isset($mod['threads']) ? $mod['threads'] : [];
        response(array('login_success'=> '1', 'boards' => $boards, 'threads' => $threads));
        break;
    case 'hide_post':
        hide_post($_POST['post_id']);
        break;
    case 'delete_post':
        deletePost($_POST['post_id']);
        json_response(array('post_delete_success'=> true));
        break;
    case 'spoiler_file':
        spoiler_file($_POST['post_id'], $_POST['param1']);
        break;
    case 'delete_file':
        delete_file($_POST['post_id'], $_POST['param1']);
        break;
    case 'ban_post':

        if(!is_numeric($_POST['post_id']) && !empty($_POST['post_id']))
            error($config['error']['wrong_params']);

        if(!is_numeric($_POST['param1']) && !empty($_POST['param1']))
            error($config['error']['wrong_params']);


        $delete_all = isset($_POST['param3']) && $_POST['param3'] == 'true';
        $range_ban =  (isset($_POST['param4']) && $_POST['param4'] == 'true') ? 'range':''; // бан по подсети

        $_POST['reason'] = $_POST['param2'];
        $_POST['length'] = $_POST['param1'];
        $_POST['message'] =  $_POST['reason']; // public message
        $_POST['public_message'] =  $_POST['reason']; // public message
        $_POST['new_ban']='';

        if ($delete_all) { 
            // сначала удаляем все посты, кроме того за который баним
            $query = prepare(sprintf("SELECT * FROM ``posts_%s`` WHERE `id` = :id", $board['uri']));
            $query->bindValue(':id', $_POST['post_id'], PDO::PARAM_INT);
            $query->execute() or error(db_error($query));
        
            if ($post = $query->fetch(PDO::FETCH_ASSOC)) { 
                
                if ($post['thread']){
 
                    $query = prepare(sprintf("SELECT * FROM `posts_%s` WHERE `thread` = :thread AND `ip` = :ip AND `id` != :id" , $board['uri']));
                    $query->bindValue(':thread', $post['thread'], PDO::PARAM_INT);   
                    $query->bindValue(':ip', $post['ip'], PDO::PARAM_STR);

                    if($post['ip'] != NULL && strpos($post['ip'] != '127.0.0') === FALSE){

                        $query->bindValue(':id', $_POST['post_id'], PDO::PARAM_INT);
                        $query->execute() or error(db_error($query));
                        $deleted_posts = $query->rowCount();
                        json_response_add("deleted_count",  $deleted_posts);

                        if ($deleted_posts > 0) {
                            json_response_add("need_reload",  true);
                        }

                        modLog("Start delete $deleted_posts posts by IP address");

                        if ($ip_posts = $query->fetchAll(PDO::FETCH_ASSOC)) { 
                            foreach ($ip_posts as $ip_post) {
                                send_post_to_hell($ip_post['id'], false, true);
                            }
                        }
                    }
                }
            }
        }

        $result = nban::new($board['uri'], $_POST['post_id'], $_POST['reason'],  (int)$_POST['length']);

        response(array('success'=> $result, 'ban_success'=>$result ));
        
        
        //mod_ban_post($board['uri'], $range_ban, $_POST['post_id'], false, $mod['username']);
 

        break;
    case 'unban_post': 
        if(bans::delete((int)$_POST['param1']) )//OpBans::delete(str_replace('#', '', $_POST['param1']), $mod['username']))
            response(array('unban_success'=> 1));
        response(array('fail'=> 'unban fail'));
        break;  
    case 'banlist':
        response(array('banlist'=> get_all_bans()));
        break;
    case 'add_playlist':
  
        Neotube::init($_POST['board'], $_POST['thread_id']);

        if(substr($_POST['link'], 0, 4) != 'http'){

            if(!Neotube::uploadLocalTrack($_POST['link']))
                server_reponse('Fail change playlist', array('success'=> false));
            
            $json = Neotube::getPlaylist(); 
            json_response(array('success'=>  $json != null, 'playlist' => $json, 'time'=> time()+1));
            break;
        }

        if(!Neotube::addYoutubeLink( $_POST['link'])) {
            server_reponse('Fail change playlist', array('success'=> false));
        }

        $json = Neotube::getPlaylist(); 
        json_response(array('success'=>  $json != null, 'playlist' => $json, 'time'=> time()+1));

        break;
    case 'remove_track':
        Neotube::init($_POST['board'], $_POST['thread_id']);
        $json = Neotube::removeFromPlaylist($_POST['id']);

        json_response(array('success'=> true,  'playlist' => $json));
        break;

    case 'upload_track':
        Neotube::init($_POST['board'], $_POST['thread_id']);
        $result = Neotube::uploadTrack();

        json_response(array('success'=>  $result != null, 'playlist' => Neotube::getPlaylistJson()));
        break;

    case 'pause_track':
        Neotube::init($_POST['board'], $_POST['thread_id']);
        $result = Neotube::pauseTrack();

        json_response(array('success'=>  $result != null, 'playlist' => Neotube::getPlaylistJson()));
        break;
    case 'neotube_rights':
        json_response(array('success'=> true, 'rights' => true));
        break;
    default:
        response(array('error'=> "Неизвестный запрос '" . $_POST['action']. "'"));
}



/* Отправить пост в ад */
function hide_post($post_id, $rebuild = true, $return = false)
{ 

    global $config, $board, $mod_trip;

    $query = prepare(sprintf("SELECT * FROM `posts_%s` WHERE `id` = :id", $board['uri']));
    $query->bindValue(':id', $post_id, PDO::PARAM_INT);
    $query->execute() or error(db_error($query));

    if ($post = $query->fetch(PDO::FETCH_ASSOC)) {
        
        if($post['thread'] < 0) {
            json_reponse(array('fail'=>'Нельзя удалять посты из ада'));
        }
        
        if ($post['files']) {
			// Delete file
			foreach (json_decode($post['files']) as $i => $f) {
				if (isset($f->file, $f->thumb) && $f->file !== 'deleted') {
					@file_unlink($config['dir']['img_root'] . $board['dir'] . $config['dir']['img'] . $f->file);
                    @file_unlink($config['dir']['img_root'] . $board['dir'] . $config['dir']['thumb'] . $f->thumb);
                    
                    delete_fat_file($f);
				}
			}
		}

        modLog("Hide post #$post_id");
        $query = prepare(sprintf("UPDATE ``posts_%s`` SET `hide` = 1, `changed_at` = UNIX_TIMESTAMP(NOW()) WHERE `id` = :id OR `thread` = :id", $board['uri']));
        $query->bindValue(':id', $post_id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));
        $query = $query->fetch(PDO::FETCH_ASSOC);

        if ($rebuild) {
            buildThread($post['thread']);
            buildIndex();
        }

        if (!$return) {
            json_response(array('post_delete_success'=> true));
        }

    } else {
        if (!$return) {
            json_response(array('fail'=> 'Произошла ошибка'));
        }
    }

    

}

function delete_file($post_id, $hash)
{ 

    global $config, $board, $mod_trip;

    $query = prepare(sprintf("SELECT * FROM `posts_%s` WHERE `id` = :id", $board['uri']));
    $query->bindValue(':id', $post_id, PDO::PARAM_INT);
    $query->execute() or error(db_error($query));

    if ($post = $query->fetch(PDO::FETCH_ASSOC)) {

        if (!$post['files']) {
            json_response(array('fail'=> 'Произошла ошибка'));
        }

        $files = json_decode($post['files']);
        $new_files = array();

        foreach ($files as $f) {
            
            if ($f->hash == $hash){

                if (isset($f->file, $f->thumb) && $f->file !== 'deleted') {
                    
					@file_unlink($config['dir']['img_root'] . $board['dir'] . $config['dir']['img'] . $f->file);
                    @file_unlink($config['dir']['img_root'] . $board['dir'] . $config['dir']['thumb'] . $f->thumb);

                    if ($f->fat) {
                        delete_fat_file($f);
                    }
                }

            } else {
                $new_files[] = $f;
            }
        }

        $json = json_encode($new_files);

        modLog("set poiler to post #$post_id");
        $query = prepare(sprintf("UPDATE `posts_%s` SET `files`=:files, `edited_at` = UNIX_TIMESTAMP(NOW()), `changed_at` = UNIX_TIMESTAMP(NOW()) WHERE `id` = :id", $board['uri']));
        $query->bindValue(':id', $post_id, PDO::PARAM_INT);
        $query->bindValue(':files', $json, PDO::PARAM_STR);

        $query->execute() or error(db_error($query));
        $query = $query->fetch(PDO::FETCH_ASSOC);

        buildThread($post['thread']);
        buildIndex();

        json_response(array('file_delete_success'=> true));

    } else {
        json_response(array('fail'=> 'Произошла ошибка'));
    }

    

}

function spoiler_file($post_id, $hash)
{ 

    global $config, $board, $mod_trip;

    $query = prepare(sprintf("SELECT * FROM `posts_%s` WHERE `id` = :id", $board['uri']));
    $query->bindValue(':id', $post_id, PDO::PARAM_INT);
    $query->execute() or error(db_error($query));

    if ($post = $query->fetch(PDO::FETCH_ASSOC)) {

        if (!$post['files']) {
            json_response(array('fail'=> 'Произошла ошибка'));
        }

        $files = json_decode($post['files']);

        foreach ($files as &$f) {

            if ($f->file !== 'deleted' && $f->hash == $hash){
                $f->thumb = 'spoiler';
            }
        }

        $json = json_encode($files);

        modLog("set spoiler to post #$post_id");
		
        $query = prepare(sprintf("UPDATE `posts_%s` SET `files`=:files, `changed_at` = UNIX_TIMESTAMP(NOW()) WHERE `id` = :id", $board['uri']));
        $query->bindValue(':id', $post_id, PDO::PARAM_INT);
        $query->bindValue(':files', $json, PDO::PARAM_STR);

        $query->execute() or error(db_error($query));
        $query = $query->fetch(PDO::FETCH_ASSOC);

        buildThread($post['thread']);
        buildIndex();

        json_response(array('spoiler_file_success'=> true));

    } else {
        json_response(array('fail'=> 'Произошла ошибка'));
    }

    

}

 
function get_posts_from_hell($thread_id)
{

    global $config, $board;

    $query = prepare(sprintf("SELECT * FROM `posts_%s` WHERE `thread` = :id AND `deleted` = 0 AND `hide` = 1", $board['uri']));
    $query->bindValue(':id', $thread_id, PDO::PARAM_INT);
    $query->execute() or error(db_error($query));

    if ($posts = $query->fetchAll(PDO::FETCH_ASSOC)) {

        $data = "<html><main>";

        foreach ($posts as $post) {
            $list =  Element('post_reply.html', array(
                'config' => $config,
                'board' => $board,
                'post' => &$post,
                'mod' => null,
                'clean' => array(),
            ));

            $data .= $list;
        }

        $data .= "</main></html>";
        json_response(array('post_delete_view' => true, 'data'=> $data));

    }
 
    json_response(array('fail' => 'Нет удалённых постов', '6'=> "SELECT * FROM `posts_{$board['uri']}` WHERE `thread` = $hell_thread_id"));

}


function response($arr)
{
    die(json_encode($arr));
}



