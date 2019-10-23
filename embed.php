<?php
header("Content-type: image/jpeg");

if (!isset($_GET['service'],  $_GET['id'])) {
    exit;
}

if(!preg_match('/^[-_A-Za-z0-9]+$/', $_GET['id']) || strlen($_GET['id']) > 16) {
	exit;
}


$path ='tmp/embed/' . $_GET['service'] . '_' . $_GET['id'];


if (file_exists($path)) {
    die(file_get_contents($path));
}

if(strlen($_GET['id']) > 12) {
	echo "wrong image id";
	exit;
}

$link = null;
 


switch ($_GET['service']) {
    case 'youtube':
        $link = "https://img.youtube.com/vi/" . $_GET['id'] . "/0.jpg";
        break;
    case 'youtube320':
        $link = "https://i.ytimg.com/vi/" . $_GET['id'] . "/mqdefault.jpg";
        break;
    case 'vimeo':
        $json = CurlRequest('http://vimeo.com/api/v2/video/' . $_GET['id'] . '.json');
        $data = json_decode($json, TRUE);

        if(isset($data[0]['thumbnail_large']))
            $link = str_replace('.webp', '.jpg', $data[0]['thumbnail_large']);
        
        break;
    case 'vlive':
        $url = 'https://www.vlive.tv/embed/' .  $_GET['id'];
        $html = CurlRequest($url);

        if(preg_match('/videoThumb = \"([^\"]+)\";/',  $html, $thumbLink))
            $link = $thumbLink[1];
        
        break;
    default:
        exit;
        break;
}

    





$image =  file_get_contents($link);

if (strlen($image) < 100) {
    die('404 not found');
}

file_put_contents($path, $image);


echo $image;


function CurlRequest($request, $timeout_ms=2000)
{

    //Проверка на правильность URL 
    if (!filter_var($request, FILTER_VALIDATE_URL)) {
        return null;
    }

    if (function_exists('curl_version')) {

        //Инициализация curl
        $ch = curl_init($request);

        curl_setopt_array($ch, array(

            CURLOPT_CONNECTTIMEOUT_MS => $timeout_ms,
            CURLOPT_TIMEOUT_MS => $timeout_ms,
            CURLOPT_HEADER => false,
            CURLOPT_NOBODY => false,
            CURLOPT_RETURNTRANSFER => true

        ));
    
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            return $response;
        }


    } else {
        //ini_set('default_socket_timeout', $timeout_ms);
        return file_get_contents($request);
    }

    return null;
}
