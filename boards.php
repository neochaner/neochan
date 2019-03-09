<?php

include "inc/functions.php";
   
$admin         = isset($mod["type"]) && $mod["type"]<=30;

if (php_sapi_name() == 'fpm-fcgi' && !$admin && count($_GET) == 0) 
{
	error('Cannot be run directly.');
}


$statistics = [];
$statistics_hours = 24;
$sort_by = 'unical_count';
$query = prepare(sprintf("SELECT * FROM ``boards`` WHERE `indexed`=1"));
$query->execute() or error(db_error($query));
$boards = $query->fetchAll(PDO::FETCH_ASSOC);



foreach($boards as $board){

	$sta = getBoardStatistics($board['uri'], $statistics_hours);
	$sta['uri'] = $board['uri'];
	$sta['title'] = $board['title'];
	

	for($i=0; $i<count($statistics); $i++){

		if($sta[$sort_by] > $statistics[$i][$sort_by]){
			array_splice($statistics, $i, 0, array($sta));
			$sta = null;
			break;
		}
	}

	if($sta){
		array_push($statistics, $sta);
	}
}




$HTML = Element("site/index.html", array(
	"config"		=> $config,
	"boards"		=> $statistics,
	"date" => date("H:i:s / j-m-Y"),
));

file_put_contents("index.html", $HTML);





function getBoardStatistics($uri, $hours){


	$min_time = time() - ($hours * 3600);
	$table_name = "posts_$uri";
	$query = prepare("SELECT count(`id`) as post_count,count(DISTINCT(`ip`)) as unical_count FROM `$table_name` WHERE `time`> :min_time");
	$query->bindParam(':min_time', $min_time, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	$boards = $query->fetchAll(PDO::FETCH_ASSOC);


	$query = prepare("SELECT max(`id`) as 'post_total' FROM `$table_name`");
	$query->execute() or error(db_error($query));
	$max = $query->fetchAll(PDO::FETCH_ASSOC);

	return array(
		'post_count' => $boards[0]['post_count'],
		'unical_count' => $boards[0]['unical_count'],
		'post_total' => $max[0]['post_total'],

	);


 
}

?>