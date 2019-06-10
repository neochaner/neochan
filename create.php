<?php
include "inc/functions.php";
require 'inc/mod/pages.php';

checkBan('*');

if($_GET['account']) {

	if (!$config['allow_create_account']) {
		error('Create account is disabled.');
	}

	createAccount();

} elseif($_GET['userboard']) {


	if (!$config['allow_create_userboards']) {

		$body = "<article style='width: 60%;
		min-width: 600px;
		max-width: 90%;
		margin-left: auto;
		margin-right: auto;'><div class='post post-large' style='text-align: center;'>
		<p class='l_CreateBoardRequest'></p>
		<p><b>{$config['board_request_email']}</b></p>
		<br></div></article>";

		die(Element("page.html", array(
			"config" => $config,
			"body" => $body,
			"l_title" => 'l_Create_board',
			"subtitle" => '',
		)));
	}

	if (Session::$is_darknet && !$config['allow_create_userboards_from_darknet']) {
		error('Create user boards is not allowed for you.');
	}

	createUserBoard();

} else {
	error('Nothing for you.');
}




function createAccount(){

	global $config;

	if (!isset($_POST['username'], $_POST['password'], $_POST['password2'])) {

		die (Element("page.html", array(
			"config" => $config,
			"body" => Element('mod/register.html', array('config'=> $config)),
			"l_title" => 'l_Create_account',
			"subtitle" => '',
		)));
	} 
	
	$username = $_POST['username'];
	$password = $_POST['password'];
	$error = false;

	if (!preg_match('/^[a-zA-Z0-9._]{1,30}$/', $username)) {
		$error = 'l_usernameInvalid';
	}
	
	if (strlen($username) < 4) {
		$error = 'l_usernameIsSmall';
	}	
	
	if (strlen($password) < 4) {
		$error = 'l_passwordIsSmall';
	}
	
	if ($_POST['password'] != $_POST['password2']) {
		$error = 'l_passwordsNotEquals';
	}
	
	if (!chanCaptcha::check()) {
		$error = 'l_captcha_mistype';
	}


	if (!$error) {

		$query = prepare('SELECT ``username`` FROM ``mods`` WHERE ``username`` = :username');
		$query->bindValue(':username', $username);
		$query->execute() or error(db_error($query));
		$users = $query->fetchAll(PDO::FETCH_ASSOC);
		
		if (sizeof($users) > 0) {
			$error = 'l_usernameAlreayExists';
		}
	}

	if ($error) {
		die (Element("page.html", array(
			"config" => $config,
			"body" => Element('mod/register.html', array('config'=> $config, 'l_error'=> $error)),
			"l_title" => 'l_Create_account',
			"subtitle" => '',
		)));
	}


	$salt = generate_salt();
	$password = hash('sha256', $salt . sha1($password));
	
	$query = prepare('INSERT INTO ``mods`` VALUES (NULL, :username, :password, :salt, :type, :boards, :email)');
	$query->bindValue(':username', $username);
	$query->bindValue(':password', $password);
	$query->bindValue(':salt', $salt);
	$query->bindValue(':type', 0);
	$query->bindValue(':boards', '');
	$query->bindValue(':email', '');
	$query->execute() or error(db_error($query));

	$_POST['login'] = '1';
	mod_login('/mod.php');

	exit;
}



function createUserBoard(){

	global $config;

	$query = prepare('SELECT COUNT(*) FROM `boards` WHERE `created_at` > DATE_SUB(NOW(), INTERVAL :timeout_min MINUTE)');
	$query->bindValue(':timeout_min', $config['allow_create_userboards_timeout']);
	$query->execute() or error(db_error($query));
	$count = $query->fetch();

	if (!is_array($count) || $count[0] > 0){
		error("Please try later...");
	}



	if (!isset($_POST['uri'], $_POST['title'], $_POST['subtitle'], $_POST['username'], $_POST['password'])) {
		$password = strtr(base64_encode(random_bytes(9)), '+', '.');
		$body     = Element("8chan/create.html", array("config" => $config, "password" => $password));
		echo Element("page.html", array(
			"config" => $config,
			"body" => $body,
			"l_title" => 'l_Create_board',
			"subtitle" => '',
			'boardlist' => createBoardlist(),
		));
	} else {
		$uri      = $_POST['uri'];
		$title    = $_POST['title'];
		$subtitle = $_POST['subtitle'];
		$username = $_POST['username'];
		$password = $_POST['password'];
		$email    = (isset($_POST['email']) ? $_POST['email'] : '');
	
		if (!preg_match('/^[a-z0-9]{1,30}$/', $uri)) {
			error(_('Invalid URI'));
		}
	
		if (!(strlen($title) < 40)) {
			error(_('Invalid title'));
		}
	
		if (!(strlen($subtitle) < 200)) {
			error(_('Invalid subtitle'));
		}
	
		if (!preg_match('/^[a-zA-Z0-9._]{1,30}$/', $username)) {
			error(_('Invalid username'));
		}
	
		if (!chanCaptcha::check()) {
			error($config['error']['captcha']);
		}
	
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$email = '';
		}
	
		foreach (listBoards() as $i => $board) {
			if ($board['uri'] == $uri) {
				error(_('Board already exists!'));
			}
		}
	
		foreach ($config['banned_boards'] as $i => $w) {
			if ($w[0] !== '/') {
				if (strpos($uri, $w) !== false) {
					error(_("Cannot create board with banned word $w"));
				}
	
			} else {
				if (preg_match($w, $uri)) {
					error(_("Cannot create board matching banned pattern $w"));
				}
	
			}
		}
		
		$query = prepare('SELECT ``username`` FROM ``mods`` WHERE ``username`` = :username');
		$query->bindValue(':username', $username);
		$query->execute() or error(db_error($query));
		$users = $query->fetchAll(PDO::FETCH_ASSOC);
	
		if (sizeof($users) > 0) {
			error('l_usernameAlreayExists');
		}
	
		$salt = generate_salt();
		$password = hash('sha256', $salt . sha1($password));
		
		$query = prepare('INSERT INTO ``mods`` VALUES (NULL, :username, :password, :salt, :type, :boards, :email)');
		$query->bindValue(':username', $username);
		$query->bindValue(':password', $password);
		$query->bindValue(':salt', $salt);
		$query->bindValue(':type', 20);
		$query->bindValue(':boards', $uri);
		$query->bindValue(':email', $email);
		$query->execute() or error(db_error($query));
	
		
		
	
		$query = prepare('INSERT INTO ``boards`` (`uri`, `title`, `subtitle`) VALUES (:uri, :title, :subtitle)');
		$query->bindValue(':uri', $_POST['uri']);
		$query->bindValue(':title', $_POST['title']);
		$query->bindValue(':subtitle', $_POST['subtitle']);
		$query->execute() or error(db_error($query));
	
		$query = Element('posts.sql', array('board' => $uri));
		query($query) or error(db_error());
	
		if (!openBoard($_POST['uri'])) {
			error(_("Couldn't open board after creation."));
		}
	
		if ($config['cache']['enabled']) {
			Cache::delete('all_boards');
			Cache::delete('all_boards_indexed');
		}
	
		// Build the board
		buildIndex();
	
		rebuildThemes('boards');
	
		$query = prepare("INSERT INTO ``board_create``(uri) VALUES(:uri)");
		$query->bindValue(':uri', $uri);
		$query->execute() or error(db_error());
	
		_syslog(LOG_NOTICE, "New board: $uri");
	
		$body = Element("8chan/create_success.html", array("config" => $config, "password" => $_POST['password'], "uri" => $uri));
	
		echo Element("page.html", array(
			"config" => $config,
			"body" => $body,
			"title" => _("Success"),
			"subtitle" => _("This was a triumph"),
			'boardlist' => createBoardlist(),
		));
	}
	
	
}


 