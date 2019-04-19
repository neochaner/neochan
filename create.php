<?php
include "inc/functions.php";

checkBan('*');

if (!$config['allow_create_userboards']) {
	error(_('Create user boards temporarily is not allowed. Contact admin(a)lolifox.org for this. '));
}

if (Session::$is_darknet && !$config['allow_create_userboards_from_darknet']) {
	error(_('Create user boards is not allowed for you.'));
}


$query = prepare('SELECT COUNT(*) FROM `boards` WHERE `created_at` > DATE_SUB(NOW(), INTERVAL :timeout_min MINUTE)');
$query->bindValue(':timeout_min', $config['allow_create_userboards_timeout']);
$query->execute() or error(db_error($query));

$count = $query->fetch();

if (!is_array($count) || $count[0] > 0){
	error("please, try later");
}


if (!isset($_POST['uri'], $_POST['title'], $_POST['subtitle'], $_POST['username'], $_POST['password'])) {
	$password = strtr(base64_encode(random_bytes(9)), '+', '.');
	$body     = Element("8chan/create.html", array("config" => $config, "password" => $password));
	echo Element("page.html", array(
		"config" => $config,
		"body" => $body,
		"title" => _("Create your board"),
		"subtitle" => _("before someone else does"),
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
		error(_('The username you\'ve tried to enter already exists!'));
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
