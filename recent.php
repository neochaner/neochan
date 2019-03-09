<?php

 
$boards = array('b', 'd', 'test', 'kpop', 'mu');
$cache_time = 15;


if(isset($_REQUEST['mega']))
{

	echo "<html><body><main>";

	foreach($_REQUEST as $key => $value)
	{
		if(!is_numeric($value) || !in_array($key, $boards))
			continue;

		show_board_posts($key, $value);
	}

	echo "</main></body></html>";
	exit;
}




if(isset($_REQUEST['board']) && isset($_REQUEST['thread']))
{	

	$board = $_REQUEST['board'];
	$thread = $_REQUEST['thread'];
	$max = isset($_REQUEST['max']) ? $_REQUEST['max'] : 0;
	
	if(!in_array($board, $boards) || !is_numeric($thread))
	{
		die('wrong params');
	}

	echo "<html><body><main>";
	echo show_thread_posts($board, $thread, $max);
	echo "</main></body></html>";
}
else
{
	die('wrong params board or thread');
}


function build_megapage()
{
	require "./inc/functions.php";

	global $config;
	var_dump(listBoards());
	file_write('./all/index.html', Element('all.html', array("config"=> $config, 'is_mega' => true, 'id'=> 1, 'boards' => listBoards())));

}

function show_board_posts($board, $max)
{

	global $cache_time;
	$posts;
	$max_post_count = 40;
	$cache_key = "div_thread_active_$board";
	$cache_key2 = "last_post_id_$board";
	$threads = apc_fetch($cache_key);
	$last_id = apc_fetch($cache_key2);

	if(!$threads)
	{

		$threads = array();
		$json = json_decode(file_get_contents("$board/threads.json"), true);

		if($json == null || !isset($json[0]['threads']))
			return;

		$day_ago = time() - ((60*60*24) * 1);
		$last_active_thread=0;
		$tmp=0;
	
		foreach($json[0]['threads'] as $thread)
		{
			if($thread['last_modified'] < $day_ago)
				continue;
	
			$threads[] = $thread['no'];

			if($thread['last_modified'] > $tmp)
			{
				$tmp = $thread['last_modified'];
				$last_active_thread=$thread['no'];
			}
		}
	
		apc_store($cache_key, $threads, $cache_time);

		// получаем номер последнего активного поста 
		$json = json_decode(file_get_contents("$board/res/$last_active_thread.json"), true);
		$array = $json['posts'];

		end($array);
		$key = key($array);
		$last_id = $array[(int)$key]['no'];

		apc_store($cache_key2, $last_id, $cache_time++);
	}

	$min_post_id = $last_id-$max_post_count;


	if($max < $min_post_id)
	{
		$max = $min_post_id;
	}
 
	foreach($threads as $id)
	{
		show_thread_posts($board, $id, $max, true);
	}
 
}

function file_get($path)
{
	$body = apc_fetch($path);

	if(!$body)
	{
		return file_get_contents($path);
	}
}

function show_thread_posts($board, $thread, $max, $mega = false)
{

	if(!is_numeric($thread))
		return;
	
	$cache_key = $mega ? "div_megaposts_$board"."_$thread" : "div_posts_$board"."_$thread";
	
	$cached = apc_fetch($cache_key);

	if(true)//!$cached)
	{
		$cached = cache_thread($board, $thread, $mega);
	}

	$filtered = array_filter($cached, function($k) use($max) {
    return $k > $max;
	}, ARRAY_FILTER_USE_KEY);

	if(count($filtered) == 0)
		return;


	foreach($filtered as $key=> $value)
	{
		if(!empty($value) && strlen($value)>5)
			echo $value;
	}


}



function get_articles($html)
{

	$arr = null;

	$start_pattern="<article class=\"post";
	$end_pattern="</article>";
	$end_pattern_length =  strlen($end_pattern);
	$num_pattern2="id=\"post";
	$num_pattern1="=\"reply_"; 
	$index=0;

	while(true)
	{


		$start = strpos($html,  $start_pattern, $index);

		if ($start === false)
			break;

		$end = strpos($html,  $end_pattern, $index);

		if ($end === false)
				break;
	
		$num = strpos($html,  $num_pattern1, $start);

		if ($num === false || $num > $end) 
			$num = strpos($html,  $num_pattern2, $start);
		if ($num === false)
			break;

		$end_num = strpos($html,  "\"", $num+8);

		if ($end_num === false)
			break;

		$post_number = substr($html, $num+8, $end_num - $num -8);

 

		$article = substr($html, $start, ($end - $start) + $end_pattern_length );
	
		$arr["$post_number"]=$article;

	
		$index = ($end +$end_pattern_length) ;

	}

	return $arr;

}

 

function cache_thread($board, $thread, $mega = false)
{

	global $cache_time;
	$cache_key = "div_posts_$board"."_$thread";
	$cache_key_mega = "div_megaposts_$board"."_$thread";
	
	$cached = file_get_contents("/var/www/html/$board/res/$thread.html");
	$cached = get_articles($cached);

	apc_store($cache_key, $cached, $cache_time);

	$mega_cached =  $cached;
	$op_post = $cached["$thread"];
	preg_match("/post-subject\"([^\"]+\"){2}>([^<]*)/", $op_post, $output_array);
	$threadname =  $output_array[2];

	if(mb_strlen($threadname)> 10)
		$threadname = mb_substr($threadname, 0, 10, 'UTF-8').'...'; 
	$threadname = mb_strtolower($threadname, 'UTF-8');

	$threadname = "<span class='thread-name' thread='$thread' board='$board'>$threadname</span>";

	foreach($mega_cached as &$post)
	{ 
		$post = preg_replace('(<a class=\"post-header-item post-id\" href=\"#\d+\">#\d+</a>)', "$0 $threadname", $post, 1);
	}


	apc_store($cache_key_mega, $mega_cached, $cache_time);

	return $mega ? $mega_cached : $cached ;
}


function cache_board($board)
{
	
	$threads = json_decode(file_get_contents("./$board/threads.json"), true);
	$ago_2days = time() - ((60*60*24) * 2);
	
	foreach($threads[0]['threads'] as $thread)
	{
		if($thread['last_modified'] < $ago_2days)
			continue;

		$cached = cache_thread($board, $thread['no'], true);
	
	}

}


?>






























