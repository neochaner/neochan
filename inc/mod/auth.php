<?php

/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

defined('TINYBOARD') or exit;
require_once 'inc/functions.php';

// create a hash/salt pair for validate logins
function mkhash($username, $password, $salt = false)
{
	global $config;
	
	if (!$salt) {
		// create some sort of salt for the hash
		$salt = substr(base64_encode(sha1(rand() . time(), true) . $config['cookies']['salt']), 0, 15);
		
		$generated_salt = true;
	}
	
	// generate hash (method is not important as long as it's strong)
	$identity = Session::getIdentity();
	$hash = substr(base64_encode(md5($username . $config['cookies']['salt'] . sha1($username . $password . $salt . ($config['mod']['lock_ip'] ? $identity : ''), true), true)), 0, 20);
	
	if (isset($generated_salt))
		return array($hash, $salt);
	else
		return $hash;
}

function generate_salt()
{
	mt_srand(microtime(true) * 100000 + memory_get_usage(true));
	return md5(uniqid(mt_rand(), true));
}

function login($username, $password, $makehash=true)
{
	global $mod;
	
	// SHA1 password
	if ($makehash) {
		$password = sha1($password);
	}

	$query = prepare("SELECT `id`, `type`, `boards`, `password`, `salt` FROM ``mods`` WHERE BINARY `username` = :username");
	$query->bindValue(':username', $username);
	$query->execute() or error(db_error($query));
 

	if ($user = $query->fetch(PDO::FETCH_ASSOC)) {
		if ($user['password'] === hash('sha256', $user['salt'] . $password)) {

			return $mod = array(
				'id' => $user['id'],
				'type' => $user['type'],
				'username' => $username,
				'hash' => mkhash($username, $user['password']),
				'boards' => explode(',', $user['boards'])
			);
		}
	}
	
	return false;
}

function setCookies() 
{
	global $mod, $config;
	if (!$mod)
		error('setCookies() was called for a non-moderator!');
	
	setcookie($config['cookies']['mod'],
			$mod['username'] . // username
			':' . 
			$mod['hash'][0] . // password
			':' .
			$mod['hash'][1], // salt
		time() + $config['cookies']['expire'], $config['cookies']['jail'] ? $config['cookies']['path'] : '/', null, !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off', $config['cookies']['httponly']);
}

function destroyCookies() 
{
	global $config;
	// Delete the cookies
	setcookie($config['cookies']['mod'], 'deleted', time() - $config['cookies']['expire'], $config['cookies']['jail']?$config['cookies']['path'] : '/', null, !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off', true);
}


function modLog($action, $_board=null, $mod_id=-1) 
{

	global $mod, $board, $config;
	$identity = Session::getIdentity();
	$query = prepare("INSERT INTO ``modlogs`` VALUES (:id, :ip, :board, :time, :text)");
	$query->bindValue(':id', (isset($mod['id']) ? $mod['id'] : $mod_id), PDO::PARAM_INT);
	$query->bindValue(':ip', $identity);
	$query->bindValue(':time', time(), PDO::PARAM_INT);
	$query->bindValue(':text', $action);
	if (isset($_board))
		$query->bindValue(':board', $_board);
	elseif (isset($board))
		$query->bindValue(':board', $board['uri']);
	else
		$query->bindValue(':board', null, PDO::PARAM_NULL);
	$query->execute() or error(db_error($query));
	
	if ($config['syslog'])
		_syslog(LOG_INFO, '[mod/' . $mod['username'] . ']: ' . $action);
}

function create_pm_header()
{
	global $mod, $config;
	
	if ($config['cache']['enabled'] && ($header = cache::get('pm_unread_' . $mod['id'])) != false) {
		if ($header === true)
			return false;
	
		return $header;
	}
	
	$query = prepare("SELECT `id` FROM ``pms`` WHERE `to` = :id AND `unread` = 1");
	$query->bindValue(':id', $mod['id'], PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	
	if ($pm = $query->fetch(PDO::FETCH_ASSOC))
		$header = array('id' => $pm['id'], 'waiting' => $query->rowCount() - 1);
	else
		$header = true;
	
	if ($config['cache']['enabled'])
		cache::set('pm_unread_' . $mod['id'], $header);
	
	if ($header === true)
		return false;
	
	return $header;
}

function make_secure_link_token($uri) {
	global $mod, $config;
	return substr(sha1($config['cookies']['salt'] . '-' . $uri . '-' . $mod['id']), 0, 8);
}



function check_login($prompt = false, $dont_exit = false)
{

	global $config, $mod;
	// Validate session
	if (isset($_COOKIE[$config['cookies']['mod']])) {
		// Should be username:hash:salt
		$cookie = explode(':', $_COOKIE[$config['cookies']['mod']]);
		if (count($cookie) != 3) {
			// Malformed cookies
			destroyCookies();
			if ($prompt) 
				mod_login();
			
			if(!$dont_exit)
				exit;
		}
		
		$query = prepare("SELECT * FROM ``mods`` WHERE `username` = :username");
		$query->bindValue(':username', $cookie[0]);
		$query->execute() or error(db_error($query));
		$user = $query->fetch(PDO::FETCH_ASSOC);
		
		// validate password hash
		if ($cookie[1] !== mkhash($cookie[0], $user['password'], $cookie[2])) {
			// Malformed cookies
			destroyCookies();
			if ($prompt) 
				mod_login();
				
			if(!$dont_exit)
				exit;
		}

		if(isset($user['reg_date'])) {
			$regTicks = time() - (strtotime($user['reg_date']));
			$reg_days = floor($regTicks/3600/24);
		}
		
		$mod = array(
			'id' => $user['id'],
			'type' => $user['type'],
			'username' => $cookie[0],
			'boards' => explode(',', $user['boards']),
			'post_count' => $user['post_count'] ?? 0,
			'thread_count' => $user['thread_count'] ?? 0,
			'reg_date' => $user['reg_date'] ?? date("Y-m-d H:i:s"),
			'reg_days' => $reg_days
		);
	}


	// Fix for magic quotes
	if (get_magic_quotes_gpc()) {
		function strip_array($var) {
			return is_array($var) ? array_map('strip_array', $var) : stripslashes($var);
		}
		
		$_GET = strip_array($_GET);
		$_POST = strip_array($_POST);
	}
}

function check_profile()
{

	global $config, $mod;

	if (!isset($_COOKIE[$config['cookies']['mod']]))
		return null;

	$cookie = explode(':', $_COOKIE[$config['cookies']['mod']]);

	if (count($cookie) != 3)
		return null;

	$query = prepare("SELECT `id`, `type`, `boards`, `password` FROM ``mods`` WHERE `username` = :username");
	$query->bindValue(':username', $cookie[0]);
	$query->execute() or error(db_error($query));
	$user = $query->fetch(PDO::FETCH_ASSOC);

	if ($cookie[1] !== mkhash($cookie[0], $user['password'], $cookie[2]))
		return null;

	$mod = array(
		'id' => $user['id'],
		'type' => $user['type'],
		'username' => $cookie[0],
		'boards' => explode(',', $user['boards'])
	);

	return $mod;

}

function check_opmod_login()
{

	global $config, $mod, $board;

	if(empty($_POST['trip']))
		return;


	$mod_trip=generate_tripcode($_POST['trip']);
    
    if(count($mod_trip) < 2)
		return;

	$mod_trip = $mod_trip[1];
	$mod_threads = array();

	
    $query = prepare(sprintf("SELECT id FROM `posts_%s` WHERE `thread` IS NULL AND `trip` =  :trip", $board['uri']));
    $query->bindValue(':trip', $mod_trip, PDO::PARAM_STR);
    $query->execute() or error(db_error($query));

	
    if ($threads = $query->fetchAll(PDO::FETCH_ASSOC)) 
    { 
		foreach($threads as $thread)
			$mod_threads[] = "{$board['uri']}_{$thread['id']}";
			
	}

	$mod = array(
		'id' => 0,
		'type' => BOARDVOLUNTEER,
		'username' => $mod_trip,
		'threads' => $mod_threads,
	);


}