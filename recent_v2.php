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

 
if (!isset($_GET['disable_mod']) && isset($_COOKIE[$config['cookies']['mod']])) {
    check_login();
}

 
if (isset($_GET['thread']) && isset($_GET['board']) && in_array($_GET['board'], $boards )) {

    $indexTime = isset($_GET['time']) ? (int)$_GET['time'] : 0;
    $indexPost = isset($_GET['post']) ? (int)$_GET['post'] : 0;

    $data = GetThread($_GET['board'], $_GET['thread'], $indexTime, $indexPost);

    $posts = array();

    foreach ($data as $post) {
        if($post['changed_at'] > $_GET['time'] || ($post['changed_at'] == $_GET['time'] && $post['id'] != $_GET['post']))
            $posts[] = $post;
    }


    if (isset($_GET['neotube']) && $_GET['neotube'] > 0) {

        Neotube::init($_GET['board'], $_GET['thread']);
        $playlist = Neotube::getPlaylist();
 
        $result = array('posts'=> $posts, 'post_len'=> count($posts), 'playlist' => is_array($playlist) ? $playlist : null);
        echo json_encode($result); 

    } else {

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