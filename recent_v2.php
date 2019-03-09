<?php
require "./inc/functions.php";
 

$cache_time = 60;
$listBoardsKey = 'boardsArray';

$boards = Cache::get($listBoardsKey);


if($boards == null){
	$boards = listBoards();
	$arr = array();
		
	foreach($boards as $board)
		$arr[] = $board['uri'];
	
	Cache::set($listBoardsKey, $arr, 30);
	$boards = $arr;
}

 
if (!isset($_GET['disable_mod']) && isset($_COOKIE[$config['cookies']['mod']]))
    check_login();

 

if(isset($_GET['rebuild_all']))
{
    $boards = listBoards();

    foreach($boards as $board)
    {

        $query = prepare("SELECT * FROM `posts_{$board['uri']}`");
        $query->execute() or error(db_error($query));
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($data as $post)
        {

            $template = get_post_template($post);
            $mtime = ($post['edited_at'] && $post['time'] < $post['edited_at']) ? $post['edited_at'] :  $post['time'];

   
            $query = prepare("UPDATE `posts_{$board['uri']}` SET `template`=:t, `changed_at`=:mtime WHERE id=:id");
            $query->bindValue(":t", $template, PDO::PARAM_STR);
            $query->bindValue(':id', $post['id'], PDO::PARAM_INT);
            $query->bindValue(':mtime', $mtime, PDO::PARAM_INT);
            $query->execute() or error(db_error($query));

      
        }
    }

    exit;
}






if(isset($_GET['active_page']) && $_GET['active_page'] == 'mega')
{
	
    $boards = explode(',', $_GET['board']);
    $default_times = array();
    $default_posts = array();

    foreach($boards as $b)
    {

        if(!in_array($b, $boards))
            exit;

        $default_times[] = 0;
        $default_posts[] = 0;
    }


    $indexTimes = isset($_GET['time']) ? explode(',',$_GET['time']) : $default_times;
    $indexPosts = isset($_GET['post']) ? explode(',',$_GET['post']) : $default_posts;

    $allPosts=array();


    for($i=0; $i < count($boards); $i++)
    {
        $posts = GetBoard($boards[$i], (int)$indexTimes[$i], (int)$indexPosts[$i]);
        $allPosts = array_merge($allPosts, $posts);
    }

    $result = array('posts'=> $allPosts, 'post_len'=> count($allPosts));
    echo json_encode($result);
	
}

else if(isset($_GET['thread']) && isset($_GET['board']) && in_array($_GET['board'], $boards ))
{

    $indexTime = isset($_GET['time']) ? (int)$_GET['time'] : 0;
    $indexPost = isset($_GET['post']) ? (int)$_GET['post'] : 0;

    $data = isset($mod) && $mod['type'] > USER ?
     GetModThread($_GET['board'], $_GET['thread'], $indexTime, $indexPost) : GetThread($_GET['board'], $_GET['thread'], $indexTime, $indexPost);

    $posts = array();

    foreach($data as $post)
    {
        if($post['changed_at'] > $_GET['time'] || ($post['changed_at'] == $_GET['time'] && $post['id'] != $_GET['post']))
            $posts[] = $post;
    }


    if(isset($_GET['neotube']) && $_GET['neotube'] > 0){

        neotube::init($_GET['board'], $_GET['thread']);
        $playlist = neotube::getPlaylist();
 
        $result = array('posts'=> $posts, 'post_len'=> count($posts), 'playlist' => is_array($playlist) ? $playlist : null);
        echo json_encode($result); 

    } else{

        $result = array('posts'=> $posts, 'post_len'=> count($posts));
        echo json_encode($result);
    }

}


function GetThread($board, $thread, $indexTime, $indexPost){

 
    global $cache_time;

    $key = '_cachev2_' . $board . '_' . $thread;
    $data = Cache::get($key);

 
    if($data == null)
    {
        $query = prepare("SELECT `id`, `template`, `changed_at`, `hide`, `deleted`, `thread`, `time` FROM `posts_$board` WHERE `thread`=:thread OR `id`=:thread ORDER BY `changed_at` DESC");
        $query->bindValue(':thread', $thread, PDO::PARAM_INT);
		$query->execute() or error(db_error($query));
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        $data = ProcessPosts($data, $board);

        Cache::set($key, $data, $cache_time);
    }

    $posts = array();

    foreach($data as $post)
    {
        if($post['changed_at'] > $indexTime || ($post['changed_at'] == $indexTime && $post['id'] != $indexPost))
            $posts[] = $post;
    }

    return  $posts;
}

function GetModThread($board_uri, $thread, $indexTime, $indexPost){

    global $mod, $board, $config;

    if(!openBoard($board_uri))
        return array();


    $query = prepare("SELECT * FROM `posts_$board_uri` WHERE `thread`=:thread OR `id`=:thread ORDER BY `changed_at` DESC");
    $query->bindValue(':thread', $thread, PDO::PARAM_INT);
    $query->execute() or error(db_error($query));
    $data = $query->fetchAll(PDO::FETCH_ASSOC);
    $data = ProcessPosts($data, $board);

    $posts = array();

    foreach($data as $post)
    {

        $po = new Post($post, '?/', $mod);
        $template = $po->build();
        
        $mpost = array(
            'id'=> $post['id'],
            'template'=> $template,
            'changed_at'=> $post['changed_at'],
            'hide'=> $post['hide'],
            'deleted'=> $post['deleted'],
            'thread'=> $post['thread'],
            'time'=> $post['time'],
        );

        $posts[] = $mpost;
    
    }
    
    $posts = ProcessPosts($posts, $board_uri);
    return $posts;
}
 
function GetBoard($board, $indexTime=0, $indexPost=0){

    global $cache_time;

    $limit = 200;
    $key = '_cachev2_' . $board;

    $data = Cache::get($key);

    if($data == null)
    {
        $query = prepare("SELECT `id`, `template`, `changed_at`, `hide`, `deleted`, `thread`, `time` FROM `posts_$board` ORDER BY `changed_at` DESC LIMIT $limit");
        $query->execute() or error(db_error($query));
        $data = $query->fetchAll(PDO::FETCH_ASSOC);
        $data = ProcessPosts($data, $board);
 
        Cache::set($key, $data, $cache_time);

    }


    $posts = array();

    foreach($data as $post)
    {
        if($post['changed_at'] > $indexTime || ($post['changed_at'] == $indexTime && $post['id'] != $indexPost))
            $posts[] = $post;
    }

    return $posts;
}

function ProcessPosts($data, $board_uri)
{

    for($i=0;$i<count($data);$i++)
    {
        $data[$i]['board'] = $board_uri; 
        
        if($data[$i]['hide'] || $data[$i]['deleted'])
            $data[$i]['template'] = null;

        if($data[$i]['thread']==null)
        {
            $data[$i]['thread'] = $data[$i]['id'];
            
    
            // remove 
            $t = $data[$i]['template'];
            $start = strpos( $t, '<article class="post');
            $t = substr($t, $start);
            
            $omit = strpos( $t, '<div class="omit');
            $t = substr($t, 0, $omit);
            
            $data[$i]['template'] = $t;
            
        }
            

        $data[$i]['id'] = (int)$data[$i]['id'];
        $data[$i]['thread'] = (int)$data[$i]['thread'];
        $data[$i]['hide'] = (int)$data[$i]['hide'];
        $data[$i]['deleted'] = (int)$data[$i]['deleted'];
        $data[$i]['changed_at'] = (int)$data[$i]['changed_at'];
        $data[$i]['time'] = (int)$data[$i]['time'];
        
    }

    return $data;

}











































?>