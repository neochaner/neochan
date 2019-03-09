
<?php
include_once 'inc/functions.php';
include_once 'inc/session.php';


if (!isset($_GET['board']) || !openBoard($_GET['board'])) {
	http_response_code(404);
	error(_('No board.'));
}

$back_link = '/' . $_GET['board'] . '/';

if($_GET['thread']){
	$back_link .= 'res/' . $_GET['thread'] . '.html';
}

session::load();
session::$is_onion = true;


if(chanCaptcha::check())
	session::CaptchaSolved();


echo Element("antispam.html", [
	'dump'=> json_encode(session::$data),
	'config' => $config,
	'board' => $board,
	'is_darknet' => session::$is_onion ||session::$is_i2p,
	'back_link' => $back_link,
	'captcha_onstart'=> true,
	'captchas_left' => session::$data['capchas_left'], 
	'captchas_need' => $config['tor']['need_capchas'],
	'verify_progress' => (session::$data['capchas_left'] * 100) / ($config['tor']['need_capchas']),
	'allow_post' => session::AllowPost(),
]);


?>