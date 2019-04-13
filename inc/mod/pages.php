<?php

/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

defined('TINYBOARD') or exit;

function mod_page($title, $template, $args, $subtitle = false) {
	global $config, $mod;
	
	echo Element('page.html', array(
			'config'              => $config,
			'mod'                 => $mod,
			'hide_dashboard_link' => $template == 'mod/dashboard.html',
			'title'               => $title,
			'subtitle'            => $subtitle,
			'boardlist'           => createBoardlist($mod),
			'body'                => Element($template,
				array_merge(
					array('config' => $config, 'mod' => $mod),
					$args
				)
			),
		)
	);
}

function mod_login($redirect = false) 
{
	global $config, $mod;
	
	$args = array();
	
	if (isset($_POST['login'])) {
		// Check if inputs are set and not empty
		if (!isset($_POST['username'], $_POST['password']) || $_POST['username'] == '' || $_POST['password'] == '') {
			$args['error'] = $config['error']['invalid'];
			
			if(isset($_POST['json_response']))
				json_response(array('error'=>  $config['error']['invalid_auth']));
		
		} elseif (!login($_POST['username'], $_POST['password'])) {
			if ($config['syslog'])
				_syslog(LOG_WARNING, 'Unauthorized login attempt!');
			
			$args['error'] = $config['error']['invalid'];
					
			if(isset($_POST['json_response']))
				json_response(array('error'=>  $config['error']['invalid_auth']));

			mod_page('', 'mod/login.html', $args);
				
		} else {
			modLog('Logged in');
			
			// Login successful
			// Set cookies
			setCookies();
					
			if(isset($_POST['json_response']))
				json_response(array('success'=>true, 'login_success'=> true, 'auth'=>true, 'username'=> $mod['username'] ));
			
			if ($redirect)
				header('Location: ?' . $redirect, true, $config['redirect_http']);
			else
				header('Location: ?/', true, $config['redirect_http']);
		}
	}
	
	if (isset($_POST['username']))
		$args['username'] = $_POST['username'];

	mod_page('', 'mod/login.html', $args);
}


function mod_confirm($request) {
	mod_page(_('Confirm action'), 'mod/confirm.html', array('request' => $request, 'token' => make_secure_link_token($request)));
}

function mod_logout($redirect = true) {
	global $config;
	destroyCookies();
	
	if(isset($_POST['json_response']))
		json_response(array('success'=>true, 'logout'=> true));
	
	header('Location: ?/', true, $config['redirect_http']);
}

function mod_dashboard() {
	global $config, $mod;
	
	$args = array();
	
	$args['boards'] = listBoards();
	
	if (hasPermission($config['mod']['noticeboard'])) {
		if (!$config['cache']['enabled'] || !$args['noticeboard'] = cache::get('noticeboard_preview')) {
			$query = prepare("SELECT ``noticeboard``.*, `username` FROM ``noticeboard`` LEFT JOIN ``mods`` ON ``mods``.`id` = `mod` ORDER BY `id` DESC LIMIT :limit");
			$query->bindValue(':limit', $config['mod']['noticeboard_dashboard'], PDO::PARAM_INT);
			$query->execute() or error(db_error($query));
			$args['noticeboard'] = $query->fetchAll(PDO::FETCH_ASSOC);
			
			if ($config['cache']['enabled'])
				cache::set('noticeboard_preview', $args['noticeboard']);
		}
	}
	
	if (!$config['cache']['enabled'] || ($args['unread_pms'] = cache::get('pm_unreadcount_' . $mod['id'])) === false) {
		$query = prepare('SELECT COUNT(*) FROM ``pms`` WHERE `to` = :id AND `unread` = 1');
		$query->bindValue(':id', $mod['id']);
		$query->execute() or error(db_error($query));
		$args['unread_pms'] = $query->fetchColumn();
		
		if ($config['cache']['enabled'])
			cache::set('pm_unreadcount_' . $mod['id'], $args['unread_pms']);
	}
	
	$query = prepare('SELECT COUNT(*) AS `total_reports` FROM ``reports``' . (($mod["type"] < GLOBALVOLUNTEER) ? " WHERE board = :board" : "")); 

	if ($mod['type'] < GLOBALVOLUNTEER) {
		$query->bindValue(':board', $mod['boards'][0]);
	} else {
		$query = prepare('SELECT (SELECT COUNT(id) FROM reports WHERE global = 0) AS total_reports, (SELECT COUNT(id) FROM reports WHERE global = 1) AS global_reports');
	}
	
	$query->execute() or error(db_error($query));
	$row = $query->fetch();
	$args['reports'] = $row['total_reports'];
	$args['global_reports'] = isset($row['global_reports']) ? $row['global_reports'] : false;
	
	$args['logout_token'] = make_secure_link_token('logout');


	modLog('Looked at dashboard', false);
	
	mod_page(_('Dashboard'), 'mod/dashboard.html', $args);
}

function mod_search_redirect() {
	global $config;
	
	if (!hasPermission($config['mod']['search']))
		error($config['error']['noaccess']);
	
	if (isset($_POST['query'], $_POST['type']) && in_array($_POST['type'], array('posts', 'IP_notes', 'bans', 'log'))) {
		$query = $_POST['query'];
		$query = urlencode($query);
		$query = str_replace('_', '%5F', $query);
		$query = str_replace('+', '_', $query);
		
		if ($query === '') {
			header('Location: ?/', true, $config['redirect_http']);
			return;
		}
		
		header('Location: ?/search/' . $_POST['type'] . '/' . $query, true, $config['redirect_http']);
	} else {
		header('Location: ?/', true, $config['redirect_http']);
	}
}

function mod_search($type, $search_query_escaped, $page_no = 1) {
	global $pdo, $config;
	
	if (!hasPermission($config['mod']['search']))
		error($config['error']['noaccess']);
	
	// Unescape query
	$query = str_replace('_', ' ', $search_query_escaped);
	$query = urldecode($query);
	$search_query = $query;
	
	// Form a series of LIKE clauses for the query.
	// This gets a little complicated.
	
	// Escape "escape" character
	$query = str_replace('!', '!!', $query);
	
	// Escape SQL wildcard
	$query = str_replace('%', '!%', $query);
	
	// Use asterisk as wildcard instead
	$query = str_replace('*', '%', $query);
	
	$query = str_replace('`', '!`', $query);
	
	// Array of phrases to match
	$match = array();

	// Exact phrases ("like this")
	if (preg_match_all('/"(.+?)"/', $query, $exact_phrases)) {
		$exact_phrases = $exact_phrases[1];
		foreach ($exact_phrases as $phrase) {
			$query = str_replace("\"{$phrase}\"", '', $query);
			$match[] = $pdo->quote($phrase);
		}
	}
	
	// Non-exact phrases (ie. plain keywords)
	$keywords = explode(' ', $query);
	foreach ($keywords as $word) {
		if (empty($word))
			continue;
		$match[] = $pdo->quote($word);
	}
	
	// Which `field` to search?
	if ($type == 'posts')
		$sql_field = array('body_nomarkup', 'files', 'subject', 'filehash', 'ip', 'name', 'trip');
	if ($type == 'IP_notes')
		$sql_field = 'body';
	if ($type == 'bans')
		$sql_field = 'reason';
	if ($type == 'log')
		$sql_field = 'text';

	// Build the "LIKE 'this' AND LIKE 'that'" etc. part of the SQL query
	$sql_like = '';
	foreach ($match as $phrase) {
		if (!empty($sql_like)){
			$sql_like .= ' AND ';
		}
		$phrase = preg_replace('/^\'(.+)\'$/', '\'%$1%\'', $phrase);
		if (is_array($sql_field)) {
			foreach ($sql_field as $field) {
				$sql_like .= '`' . $field . '` LIKE ' . $phrase . ' ESCAPE \'!\' OR';
			}
			$sql_like = preg_replace('/ OR$/', '', $sql_like);
		} else {
			$sql_like .= '`' . $sql_field . '` LIKE ' . $phrase . ' ESCAPE \'!\'';
		}
	}
	
	// Compile SQL query
	
	if ($type == 'posts') {
		$query = '';
		$boards = listBoards();
		if (empty($boards)){
			error(_('There are no boards to search!'));
		}
			
		foreach ($boards as $board) {
			openBoard($board['uri']);
			if (!hasPermission($config['mod']['search_posts'], $board['uri']))
				continue;
			
			if (!empty($query)){
				$query .= ' UNION ALL ';
			}
			$query .= sprintf("SELECT *, '%s' AS `board` FROM ``posts_%s`` WHERE %s", $board['uri'], $board['uri'], $sql_like);
		}
		
		// You weren't allowed to search any boards
		if (empty($query)) {
				error($config['error']['noaccess']);
		}
		
		$query .= ' ORDER BY `sticky` DESC, `id` DESC';
	}
	
	if ($type == 'IP_notes') {
		$query = 'SELECT * FROM ``ip_notes`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE ' . $sql_like . ' ORDER BY `time` DESC';
		$sql_table = 'ip_notes';
		if (!hasPermission($config['mod']['view_notes']) || !hasPermission($config['mod']['show_ip'])){
			error($config['error']['noaccess']);
		}
	}
	
	if ($type == 'bans') {
		$query = 'SELECT ``bans``.*, `username` FROM ``bans`` LEFT JOIN ``mods`` ON `creator` = ``mods``.`id` WHERE ' . $sql_like . ' ORDER BY (`expires` IS NOT NULL AND `expires` < UNIX_TIMESTAMP()), `created` DESC';
		$sql_table = 'bans';
		if (!hasPermission($config['mod']['view_banlist'])) {
			error($config['error']['noaccess']);
		}
	}
	
	if ($type == 'log') {
		$query = 'SELECT `username`, `mod`, `ip`, `board`, `time`, `text` FROM ``modlogs`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE ' . $sql_like . ' ORDER BY `time` DESC';
		$sql_table = 'modlogs';
		if (!hasPermission($config['mod']['modlog'])) {
			error($config['error']['noaccess']);
		}
	}
		
	// Execute SQL query (with pages)
	$q = query($query . ' LIMIT ' . (($page_no - 1) * $config['mod']['search_page']) . ', ' . $config['mod']['search_page']) or error(db_error());
	$results = $q->fetchAll(PDO::FETCH_ASSOC);
	
	// Get total result count
	if ($type == 'posts') {
		$q = query("SELECT COUNT(*) FROM ($query) AS `tmp_table`") or error(db_error());
		$result_count = $q->fetchColumn();
	} else {
		$q = query('SELECT COUNT(*) FROM `' . $sql_table . '` WHERE ' . $sql_like) or error(db_error());
		$result_count = $q->fetchColumn();
	}
	
	if ($type == 'bans') {
		foreach ($results as &$ban) {
			$ban['mask'] = Bans::range_to_string(array($ban['ipstart'], $ban['ipend']));
			if (filter_var($ban['mask'], FILTER_VALIDATE_IP) !== false) {
				$ban['single_addr'] = true;
			}

		}
	}
	
	if ($type == 'posts') {
		foreach ($results as &$post) {
			$post['snippet'] = pm_snippet($post['body']);
		}
	}
	
	// $results now contains the search results
		
	mod_page(_('Search results'), 'mod/search_results.html', array(
		'search_type'          => $type,
		'search_query'         => $search_query,
		'search_query_escaped' => $search_query_escaped,
		'result_count'         => $result_count,
		'results'              => $results,
	));
}

function mod_edit_board($boardName) {
	global $board, $config;
	
	if (!openBoard($boardName))
		error($config['error']['noboard']);
	
	if (!hasPermission($config['mod']['manageboards'], $board['uri']))
			error($config['error']['noaccess']);
	
	if (isset($_POST['title'], $_POST['subtitle'])) {
		if (isset($_POST['delete'])) {
			if (!hasPermission($config['mod']['manageboards'], $board['uri']))
				error($config['error']['deleteboard']);
			
			$query = prepare('DELETE FROM ``boards`` WHERE `uri` = :uri');
			$query->bindValue(':uri', $board['uri']);
			$query->execute() or error(db_error($query));
			
			if ($config['cache']['enabled']) {
				cache::delete('board_' . $board['uri']);
				cache::delete('all_boards');
				cache::delete('all_boards_uri');
				cache::delete('all_boards_indexed');
			}
			
			modLog('Deleted board: ' . sprintf($config['board_abbreviation'], $board['uri']), false);
			
			// Delete posting table
			query(sprintf('DROP TABLE IF EXISTS ``posts_%s``', $board['uri'])) or error(db_error());
			
			// Clear reports
			$query = prepare('DELETE FROM ``reports`` WHERE `board` = :id');
			$query->bindValue(':id', $board['uri'], PDO::PARAM_STR);
			$query->execute() or error(db_error($query));
			
			// Delete from table
			$query = prepare('DELETE FROM ``boards`` WHERE `uri` = :uri');
			$query->bindValue(':uri', $board['uri'], PDO::PARAM_STR);
			$query->execute() or error(db_error($query));
			
			$query = prepare("SELECT `board`, `post` FROM ``cites`` WHERE `target_board` = :board ORDER BY `board`");
			$query->bindValue(':board', $board['uri']);
			$query->execute() or error(db_error($query));
			while ($cite = $query->fetch(PDO::FETCH_ASSOC)) {
				if ($board['uri'] != $cite['board']) {
					if (!isset($tmp_board))
						$tmp_board = $board;
					openBoard($cite['board']);
					rebuildPost($cite['post']);
				}
			}
			
			if (isset($tmp_board))
				$board = $tmp_board;
			
			$query = prepare('DELETE FROM ``cites`` WHERE `board` = :board OR `target_board` = :board');
			$query->bindValue(':board', $board['uri']);
			$query->execute() or error(db_error($query));
			
			$query = prepare('DELETE FROM ``antispam`` WHERE `board` = :board');
			$query->bindValue(':board', $board['uri']);
			$query->execute() or error(db_error($query));
			
			// Remove board from users/permissions table
			$query = query('SELECT `id`,`boards` FROM ``mods``') or error(db_error());
			while ($user = $query->fetch(PDO::FETCH_ASSOC)) {
				$user_boards = explode(',', $user['boards']);
				if (in_array($board['uri'], $user_boards)) {
					unset($user_boards[array_search($board['uri'], $user_boards)]);
					$_query = prepare('UPDATE ``mods`` SET `boards` = :boards WHERE `id` = :id');
					$_query->bindValue(':boards', implode(',', $user_boards));
					$_query->bindValue(':id', $user['id']);
					$_query->execute() or error(db_error($_query));
				}
			}
			
			// Delete entire board directory
			rrmdir($board['uri'] . '/');
			// To reiterate: HAAAAAX
			if($config['dir']['img_root'] != '')
				rrmdir($config['dir']['img_root'] . $board['uri']);
		} else {
			$query = prepare('UPDATE ``boards`` SET `title` = :title, `subtitle` = :subtitle WHERE `uri` = :uri');
			$query->bindValue(':uri', $board['uri']);
			$query->bindValue(':title', $_POST['title']);
			$query->bindValue(':subtitle', $_POST['subtitle']);
			$query->execute() or error(db_error($query));
			
			modLog('Edited board information for ' . sprintf($config['board_abbreviation'], $board['uri']), false);
		}
		
		if ($config['cache']['enabled']) {
			cache::delete('board_' . $board['uri']);
			cache::delete('all_boards');
			cache::delete('all_boards_uri');
		}
		
		rebuildThemes('boards');
		
		header('Location: ?/', true, $config['redirect_http']);
	} else {
		mod_page(sprintf('%s: ' . $config['board_abbreviation'], _('Edit board'), $board['uri']), 'mod/board.html', array(
			'board' => $board,
			'token' => make_secure_link_token('edit/' . $board['uri'])
		));
	}
}

function mod_new_board() {
	global $config, $board;
	
	if (!hasPermission($config['mod']['newboard']))
		error($config['error']['noaccess']);
	
	if (isset($_POST['uri'], $_POST['title'], $_POST['subtitle'])) {
		if ($_POST['uri'] == '')
			error(sprintf($config['error']['required'], 'URI'));
		
		if ($_POST['title'] == '')
			error(sprintf($config['error']['required'], 'title'));
		
		if (!preg_match('/^' . $config['board_regex'] . '$/u', $_POST['uri']))
			error(sprintf($config['error']['invalidfield'], 'URI'));
		
		$bytes = 0;
		$chars = preg_split('//u', $_POST['uri'], -1, PREG_SPLIT_NO_EMPTY);
		foreach ($chars as $char) {
			$o = 0;
			$ord = ordutf8($char, $o);
			if ($ord > 0x0080)
				$bytes += 5; // @01ff
			else
				$bytes ++;
		}
		$bytes + strlen('posts_.frm');
		
		if ($bytes > 255) {
			error('Your filesystem cannot handle a board URI of that length (' . $bytes . '/255 bytes)');
			exit;
		}
		
		if (openBoard($_POST['uri'])) {
			error(sprintf($config['error']['boardexists'], $board['url']));
		}
		foreach ($config['banned_boards'] as $i => $w) {
			if ($w[0] !== '/') {
				if (strpos($_POST['uri'],$w) !== false)
					error(_("Cannot create board with banned word $w"));
			} else {
				if (preg_match($w,$_POST['uri']))
					error(_("Cannot create board matching banned pattern $w"));
			}
		}
		$query = prepare('INSERT INTO `boards` (`uri`, `title`, `subtitle`) VALUES (:uri, :title, :subtitle)');

		$query->bindValue(':uri', $_POST['uri']);
		$query->bindValue(':title', $_POST['title']);
		$query->bindValue(':subtitle', $_POST['subtitle']);

		
		$query->execute() or error(db_error($query));
		
		modLog('Created a new board: ' . sprintf($config['board_abbreviation'], $_POST['uri']));
		
		if (!openBoard($_POST['uri']))
			error(_("Couldn't open board after creation."));
		
		$query = Element('posts.sql', array('board' => $board['uri']));
		
		if (mysql_version() < 50503)
			$query = preg_replace('/(CHARSET=|CHARACTER SET )utf8mb4/', '$1utf8', $query);
		
		query($query) or error(db_error());
		
		if ($config['cache']['enabled']){
			cache::delete('all_boards');
			cache::delete('all_boards_indexed');
		}
		
		// Build the board
		buildIndex();
		
		rebuildThemes('boards');
		
		header('Location: ?/' . $board['uri'] . '/' . $config['file_index'], true, $config['redirect_http']);
	}
	
	mod_page(_('New board'), 'mod/board.html', array('new' => true, 'token' => make_secure_link_token('new-board')));
}

function mod_noticeboard($page_no = 1) {
	global $config, $pdo, $mod;
	
	if ($page_no < 1)
		error($config['error']['404']);
	
	if (!hasPermission($config['mod']['noticeboard']))
		error($config['error']['noaccess']);
	
	if (isset($_POST['subject'], $_POST['body'])) {
		if (!hasPermission($config['mod']['noticeboard_post']))
			error($config['error']['noaccess']);
		
		$_POST['body'] = escape_markup_modifiers($_POST['body']);
		markup($_POST['body']);
		
		$query = prepare('INSERT INTO ``noticeboard`` VALUES (NULL, :mod, :time, :subject, :body)');
		$query->bindValue(':mod', $mod['id']);
		$query->bindvalue(':time', time());
		$query->bindValue(':subject', $_POST['subject']);
		$query->bindValue(':body', $_POST['body']);
		$query->execute() or error(db_error($query));
		
		if ($config['cache']['enabled'])
			cache::delete('noticeboard_preview');
		
		modLog('Posted a noticeboard entry');
		
		header('Location: ?/noticeboard#' . $pdo->lastInsertId(), true, $config['redirect_http']);
	}
	
	$query = prepare("SELECT ``noticeboard``.*, `username` FROM ``noticeboard`` LEFT JOIN ``mods`` ON ``mods``.`id` = `mod` ORDER BY `id` DESC LIMIT :offset, :limit");
	$query->bindValue(':limit', $config['mod']['noticeboard_page'], PDO::PARAM_INT);
	$query->bindValue(':offset', ($page_no - 1) * $config['mod']['noticeboard_page'], PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	$noticeboard = $query->fetchAll(PDO::FETCH_ASSOC);
	
	if (empty($noticeboard) && $page_no > 1)
		error($config['error']['404']);
	
	foreach ($noticeboard as &$entry) {
		$entry['delete_token'] = make_secure_link_token('noticeboard/delete/' . $entry['id']);
	}
	
	$query = prepare("SELECT COUNT(*) FROM ``noticeboard``");
	$query->execute() or error(db_error($query));
	$count = $query->fetchColumn();
	
	mod_page(_('Noticeboard'), 'mod/noticeboard.html', array(
		'noticeboard' => $noticeboard,
		'count' => $count,
		'token' => make_secure_link_token('noticeboard')
	));
}

function mod_noticeboard_delete($id) {
	global $config;
	
	if (!hasPermission($config['mod']['noticeboard_delete']))
			error($config['error']['noaccess']);
	
	$query = prepare('DELETE FROM ``noticeboard`` WHERE `id` = :id');
	$query->bindValue(':id', $id);
	$query->execute() or error(db_error($query));
	
	modLog('Deleted a noticeboard entry');
	
	if ($config['cache']['enabled'])
		cache::delete('noticeboard_preview');
	
	header('Location: ?/noticeboard', true, $config['redirect_http']);
}

function mod_news($page_no = 1) {
	global $config, $pdo, $mod;
	
	if ($page_no < 1)
		error($config['error']['404']);
	
	if (isset($_POST['subject'], $_POST['body'])) {
		if (!hasPermission($config['mod']['news']))
			error($config['error']['noaccess']);
		
		$_POST['body'] = escape_markup_modifiers($_POST['body']);
		markup($_POST['body']);
		
		$query = prepare('INSERT INTO ``news`` VALUES (NULL, :name, :time, :subject, :body)');
		$query->bindValue(':name', isset($_POST['name']) && hasPermission($config['mod']['news_custom']) ? $_POST['name'] : $mod['username']);
		$query->bindvalue(':time', time());
		$query->bindValue(':subject', $_POST['subject']);
		$query->bindValue(':body', $_POST['body']);
		$query->execute() or error(db_error($query));
		
		modLog('Posted a news entry');
		
		rebuildThemes('news');
		
		header('Location: ?/edit_news#' . $pdo->lastInsertId(), true, $config['redirect_http']);
	}
	
	$query = prepare("SELECT * FROM ``news`` ORDER BY `id` DESC LIMIT :offset, :limit");
	$query->bindValue(':limit', $config['mod']['news_page'], PDO::PARAM_INT);
	$query->bindValue(':offset', ($page_no - 1) * $config['mod']['news_page'], PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	$news = $query->fetchAll(PDO::FETCH_ASSOC);
	
	if (empty($news) && $page_no > 1)
		error($config['error']['404']);
	
	foreach ($news as &$entry) {
		$entry['delete_token'] = make_secure_link_token('edit_news/delete/' . $entry['id']);
	}
	
	$query = prepare("SELECT COUNT(*) FROM ``news``");
	$query->execute() or error(db_error($query));
	$count = $query->fetchColumn();
	
	mod_page(_('News'), 'mod/news.html', array('news' => $news, 'count' => $count, 'token' => make_secure_link_token('edit_news')));
}

function mod_news_delete($id) {
	global $config;
	
	if (!hasPermission($config['mod']['news_delete']))
			error($config['error']['noaccess']);
	
	$query = prepare('DELETE FROM ``news`` WHERE `id` = :id');
	$query->bindValue(':id', $id);
	$query->execute() or error(db_error($query));
	
	modLog('Deleted a news entry');
	
	header('Location: ?/edit_news', true, $config['redirect_http']);
}

function mod_log($page_no = 1) {
	global $config;
	
	if ($page_no < 1)
		error($config['error']['404']);
	
	if (!hasPermission($config['mod']['modlog']))
		error($config['error']['noaccess']);
	
	$query = prepare("SELECT `username`, `mod`, `ip`, `board`, `time`, `text` FROM ``modlogs`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` ORDER BY `time` DESC LIMIT :offset, :limit");
	$query->bindValue(':limit', $config['mod']['modlog_page'], PDO::PARAM_INT);
	$query->bindValue(':offset', ($page_no - 1) * $config['mod']['modlog_page'], PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	$logs = $query->fetchAll(PDO::FETCH_ASSOC);
	
	if (empty($logs) && $page_no > 1)
		error($config['error']['404']);
	
	$query = prepare("SELECT COUNT(*) FROM ``modlogs``");
	$query->execute() or error(db_error($query));
	$count = $query->fetchColumn();
	
	mod_page(_('Board log'), 'mod/log.html', array('logs' => $logs, 'count' => $count));
}

function mod_user_log($username, $page_no = 1) {
	global $config;
	
	if ($page_no < 1)
		error($config['error']['404']);
	
	if (!hasPermission($config['mod']['modlog']))
		error($config['error']['noaccess']);
	
	$query = prepare("SELECT `username`, `mod`, `ip`, `board`, `time`, `text` FROM ``modlogs`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE `username` = :username ORDER BY `time` DESC LIMIT :offset, :limit");
	$query->bindValue(':username', $username);
	$query->bindValue(':limit', $config['mod']['modlog_page'], PDO::PARAM_INT);
	$query->bindValue(':offset', ($page_no - 1) * $config['mod']['modlog_page'], PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	$logs = $query->fetchAll(PDO::FETCH_ASSOC);
	
	if (empty($logs) && $page_no > 1)
		error($config['error']['404']);
	
	$query = prepare("SELECT COUNT(*) FROM ``modlogs`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE `username` = :username");
	$query->bindValue(':username', $username);
	$query->execute() or error(db_error($query));
	$count = $query->fetchColumn();
	
	mod_page(_('Board log'), 'mod/log.html', array('logs' => $logs, 'count' => $count, 'username' => $username));
}

function mod_board_log($board, $page_no = 1, $hide_names = false, $public = false) {
	global $config;
	
	if ($page_no < 1)
		error($config['error']['404']);
	
	if (!hasPermission($config['mod']['mod_board_log'], $board) && !$public)
		error($config['error']['noaccess']);
	
	$query = prepare("SELECT `username`, `mod`, `ip`, `board`, `time`, `text` FROM ``modlogs`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE `board` = :board ORDER BY `time` DESC LIMIT :offset, :limit");
	$query->bindValue(':board', $board);
	$query->bindValue(':limit', $config['mod']['modlog_page'], PDO::PARAM_INT);
	$query->bindValue(':offset', ($page_no - 1) * $config['mod']['modlog_page'], PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	$logs = $query->fetchAll(PDO::FETCH_ASSOC);
	
	if (empty($logs) && $page_no > 1)
		error($config['error']['404']);


	if (!hasPermission($config['mod']['show_ip'])) {
		// Supports ipv4 only!
		foreach ($logs as $i => &$log) {
			$log['text'] = preg_replace_callback(array(
					'#(?:<a id="ip"[^>]*>)([^</]*)(/\d+)?(?:</a>)#',
					'/(?:<a href="\?\/IP\/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}">)?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(?:<\/a>)?/'
				), function ($matches) {
				global $board;
				return less_ip($matches[1], $board['uri']) . (count($matches) > 2 ? $matches[2] : "");
			}, $log['text']);
		}
	}
	
	$query = prepare("SELECT COUNT(*) FROM ``modlogs`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE `board` = :board");
	$query->bindValue(':board', $board);
	$query->execute() or error(db_error($query));
	$count = $query->fetchColumn();
	
	mod_page(_('Board log'), 'mod/log.html', array('logs' => $logs, 'count' => $count, 'board' => $board, 'hide_names' => $hide_names, 'public' => $public));
}

function mod_view_board($boardName, $page_no = 1) {
	global $config, $mod;
	
	if (!openBoard($boardName))
		error($config['error']['noboard']);
	
	if (!$page = index($page_no, $mod)) {
		error($config['error']['404']);
	}
	
	$page['pages'] = getPages(true);
	$page['pages'][$page_no-1]['selected'] = true;
	$page['btn'] = getPageButtons($page['pages'], true);
	$page['mod'] = true;
	$page['config'] = $config;
	
	echo Element('index.html', $page);
}

function mod_view_thread($boardName, $thread) {
	global $config, $mod;
	
	if (!openBoard($boardName))
		error($config['error']['noboard']);
	
	$page = buildThread($thread, true, $mod);
	echo $page;
}

function mod_view_thread50($boardName, $thread) {
	global $config, $mod;
	
	if (!openBoard($boardName))
		error($config['error']['noboard']);
	
	$page = buildThread50($thread, true, $mod);
	echo $page;
}

function mod_ip_remove_note($ip, $id) {
	global $config, $mod;
	
	if (!hasPermission($config['mod']['remove_notes']))
			error($config['error']['noaccess']);
	
	if (filter_var($ip, FILTER_VALIDATE_IP) === false)
		error("Invalid IP address.");
	
	$query = prepare('DELETE FROM ``ip_notes`` WHERE `ip` = :ip AND `id` = :id');
	$query->bindValue(':ip', $ip);
	$query->bindValue(':id', $id);
	$query->execute() or error(db_error($query));
	
	modLog("Removed a note for " . ip_link($ip));

	header('Location: ?/IP/' . $ip . '#notes', true, $config['redirect_http']);
}

function mod_page_ip($ip) {

	global $config, $mod;

	if (!hasPermission($config['mod']['show_ip'])) {
		error($config['error']['noaccess']);
	}

	if ($ip[0] !=  '!'  && filter_var($ip, FILTER_VALIDATE_IP) === false) {
		error("Invalid IP address.");
	}

	if (isset($_POST['ban_id'], $_POST['unban'])) {
		if (!hasPermission($config['mod']['unban'])) {
			error($config['error']['noaccess']);
		}

		Bans::delete($_POST['ban_id'], true, $mod['boards']);

		header('Location: ?/IP/' . $ip . '#bans', true, $config['redirect_http']);
		return;
	}

	if (isset($_POST['note'])) {
		if (!hasPermission($config['mod']['create_notes'])) {
			error($config['error']['noaccess']);
		}

		$_POST['note'] = escape_markup_modifiers($_POST['note']);
		markup($_POST['note']);
		$query = prepare('INSERT INTO ``ip_notes`` VALUES (NULL, :ip, :mod, :time, :body)');
		$query->bindValue(':ip', $ip);
		$query->bindValue(':mod', $mod['id']);
		$query->bindValue(':time', time());
		$query->bindValue(':body', $_POST['note']);
		$query->execute() or error(db_error($query));

		modLog("Added a note for " . ip_link($ip));

		header('Location: ?/IP/' . $ip . '#notes', true, $config['redirect_http']);
		return;
	}

	$args          = array();
	$args['ip']    = $ip;
	$args['posts'] = array();

	$args['hostname'] = NULL;
	if ($config['mod']['dns_lookup'] && $ip[0] != '!') {
		$args['hostname'] = rDNS($ip);
	}

	$boards = listBoards();
	foreach ($boards as $board) {
		openBoard($board['uri']);
		if (!hasPermission($config['mod']['show_ip'], $board['uri'])) {
			continue;
		}

		$query = prepare(sprintf('SELECT * FROM ``posts_%s`` WHERE `ip` = :ip ORDER BY `sticky` DESC, `id` DESC LIMIT :limit', $board['uri']));
		$query->bindValue(':ip', $ip);
		$query->bindValue(':limit', $config['mod']['ip_recentposts'], PDO::PARAM_INT);
		$query->execute() or error(db_error($query));

		while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
			if (!$post['thread']) {
				$po = new Thread($post, '?/', $mod, false);
			} else {
				$po = new Post($post, '?/', $mod);
			}

			if (!isset($args['posts'][$board['uri']])) {
				$args['posts'][$board['uri']] = array('board' => $board, 'posts' => array());
			}

			$args['posts'][$board['uri']]['posts'][] = $po->build(true);
		}
	}

	$args['boards'] = $boards;
	$args['token']  = make_secure_link_token('ban');

	if (hasPermission($config['mod']['view_ban'])) {
		$args['bans'] = Bans::find($ip, false, true);
	}

	if (hasPermission($config['mod']['view_notes'])) {
		$query = prepare("SELECT ``ip_notes``.*, `username` FROM ``ip_notes`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE `ip` = :ip ORDER BY `time` DESC");
		$query->bindValue(':ip', $ip);
		$query->execute() or error(db_error($query));
		$args['notes'] = $query->fetchAll(PDO::FETCH_ASSOC);
	}

	if (hasPermission($config['mod']['modlog_ip'])) {
		$query = prepare("SELECT `username`, `mod`, `ip`, `board`, `time`, `text` FROM ``modlogs`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE `text` LIKE :search ORDER BY `time` DESC LIMIT 50");
		$query->bindValue(':search', '%' . $ip . '%');
		$query->execute() or error(db_error($query));
		$args['logs'] = $query->fetchAll(PDO::FETCH_ASSOC);
	} else {
		$args['logs'] = array();
	}

	$args['security_token'] = make_secure_link_token('IP/' . $ip);

	mod_page(sprintf('%s: %s', _('IP'), htmlspecialchars($ip)), 'mod/view_ip.html', $args, $args['hostname']);
}

function mod_page_ip_less($b, $id)
{
	global $config, $mod;


	if (!hasPermission($config['mod']['show_ip_less'], $b)) {
		error($config['error']['noaccess']);
	}

	if (!openBoard($b)) {
		error('No board.');
	}
 
	$query = prepare(sprintf('SELECT `ip`,`range_ip_hash` FROM ``posts_%s`` WHERE `id` = :id', $b));
	$query->bindValue(':id', $id);
	$query->execute() or error(db_error($query));
	
	$result = $query->fetch(PDO::FETCH_ASSOC);
 
	if ($result) {
		$ip = $result['ip'];
	} else {
		error(_('Could not find that post.'));
	}

	if ($ip[0] != '!' && filter_var($ip, FILTER_VALIDATE_IP) === false) {
		error("Invalid IP address.");
	}
	
	if (isset($_POST['ban_id'], $_POST['unban'])) {
		if (!hasPermission($config['mod']['unban']))
			error($config['error']['noaccess']);

		Bans::delete($_POST['ban_id'], true);
		
		header('Location: ?/IP_less/' . $b . '/' . $id . '#bans', true, $config['redirect_http']);
		return;
	}
	
	if (isset($_POST['note'])) {
		if (!hasPermission($config['mod']['create_notes']))
			error($config['error']['noaccess']);
		
		$_POST['note'] = escape_markup_modifiers($_POST['note']);
		markup($_POST['note']);
		$query = prepare('INSERT INTO ``ip_notes`` VALUES (NULL, :ip, :mod, :time, :body)');
		$query->bindValue(':ip', $ip);
		$query->bindValue(':mod', $mod['id']);
		$query->bindValue(':time', time());
		$query->bindValue(':body', $_POST['note']);
		$query->execute() or error(db_error($query));
		
		modLog("Added a note for " . ip_link($ip));

		header('Location: ?/IP_less/' . $b . '/' . $id . '#notes', true, $config['redirect_http']);
		return;
	}
	
	$args = array();
	$args['ip'] = $ip;
	$args['posts'] = array();
	
	if ($config['mod']['dns_lookup'])
		$args['hostname'] = rDNS($ip);

	$query = prepare(sprintf('SELECT * FROM ``posts_%s`` WHERE `ip` = :ip ORDER BY `sticky` DESC, `id` DESC LIMIT :limit', $b));
	$query->bindValue(':ip', $ip);
	$query->bindValue(':limit', $config['mod']['ip_less_recentposts'], PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	
	while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
		if (!$post['thread']) {
			$po = new Thread($post, '?/', $mod, false);
		} else {
			$po = new Post($post, '?/', $mod);
		}
		
		if (!isset($args['posts'][$b]))
			$args['posts'][$b] = array('board' => $b, 'posts' => array());
		$args['posts'][$b]['posts'][] = $po->build(true);
	}
	
	$args['boards'] = listBoards();
	$args['token'] = make_secure_link_token('ban');
	
	if (hasPermission($config['mod']['view_ban'])) {
		$args['bans'] = Bans::find($ip, false, true);
	}
	
	if (hasPermission($config['mod']['view_notes'])) {
		$query = prepare("SELECT ``ip_notes``.*, `username` FROM ``ip_notes`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE `ip` = :ip ORDER BY `time` DESC");
		$query->bindValue(':ip', $ip);
		$query->execute() or error(db_error($query));
		$args['notes'] = $query->fetchAll(PDO::FETCH_ASSOC);
	}
	
	if (hasPermission($config['mod']['modlog_ip'])) {
		$query = prepare("SELECT `username`, `mod`, `ip`, `board`, `time`, `text` FROM ``modlogs`` LEFT JOIN ``mods`` ON `mod` = ``mods``.`id` WHERE `text` LIKE :search ORDER BY `time` DESC LIMIT 50");
		$query->bindValue(':search', '%' . $ip . '%');
		$query->execute() or error(db_error($query));
		$args['logs'] = $query->fetchAll(PDO::FETCH_ASSOC);
	} else {
		$args['logs'] = array();
	}
	
	$args['security_token'] = make_secure_link_token('IP_less/' . $b . '/' . $id);
	
	mod_page(sprintf('%s: %s', _('IP'), less_ip($ip, $b)), 'mod/view_ip_less.html', $args);
}

function mod_ban() {
	global $config, $mod;

	if (!hasPermission($config['mod']['ban']))
		error($config['error']['noaccess']);

	if (!isset($_POST['ip'], $_POST['reason'], $_POST['length'], $_POST['board'])) {
		mod_page(_('New ban'), 'mod/ban_form.html', array('token' => make_secure_link_token('ban')));
		return;
	}

	require_once 'inc/mod/ban.php';
	
	Bans::new_ban($_POST['ip'], $_POST['reason'], $_POST['length'], $_POST['board'] == '*' ? false : $_POST['board']);

	if (isset($_POST['redirect']))
		header('Location: ' . $_POST['redirect'], true, $config['redirect_http']);
	else
		header('Location: ?/', true, $config['redirect_http']);
}

function mod_bans() {
	global $config;
	global $mod;
	
	if (!hasPermission($config['mod']['view_banlist']))
		error($config['error']['noaccess']);
	
	if (isset($_POST['unban'])) {
		if (!hasPermission($config['mod']['unban']))
			error($config['error']['noaccess']);
		
		$unban = array();
		foreach ($_POST as $name => $unused) {
			if (preg_match('/^ban_(\d+)$/', $name, $match))
				$unban[] = $match[1];
		}
		if (isset($config['mod']['unban_limit']) && $config['mod']['unban_limit'] && count($unban) > $config['mod']['unban_limit'])
			error(sprintf($config['error']['toomanyunban'], $config['mod']['unban_limit'], count($unban)));
		
		foreach ($unban as $id) {
			Bans::delete($id, true, $mod['boards'], true);
		}
                rebuildThemes('bans');
		header('Location: ?/bans', true, $config['redirect_http']);
		return;
	}
	
	mod_page(_('Ban list'), 'mod/ban_list.html', array(
		'mod' => $mod,
		'boards' => json_encode($mod['boards']),
		'token' => make_secure_link_token('bans'),
		'token_json' => make_secure_link_token('bans.json')
	));
}

function mod_bans_json() 
{
    global $config, $mod;

    if (!hasPermission($config['mod']['ban']))
        error($config['error']['noaccess']);

	// Compress the json for faster loads
	if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) ob_start("ob_gzhandler");

	Bans::stream_json(false, !hasPermission($config['mod']['show_ip']), !hasPermission($config['mod']['view_banstaff']), $mod['boards']);
}


function mod_ban_appeals(){

	global $config, $board, $mod;
	
	if (!hasPermission($config['mod']['view_ban_appeals']))
		error($config['error']['noaccess']);


	if (isset($_POST['appeal_id']) && (isset($_POST['unban']) || isset($_POST['deny']))) {
		if (!hasPermission($config['mod']['ban_appeals'])) {
			error($config['error']['noaccess']);
		}

		$ban = Bans::get_ban($_POST['appeal_id']);

		if (!$ban) {
			error(_('Ban appeal not found!'));
		}

		if (!in_array($ban['board'], $mod['boards']) && $mod['boards'][0] != '*') {
			error($config['error']['noaccess']);
		}

		$ban['mask'] = Bans::range_to_string(array($ban['ipstart'], $ban['ipend']));

		if (isset($_POST['unban'])) {
			modLog('Accepted ban appeal #' . $ban['id'] . ' for ' . $ban['mask']);
			Bans::delete($ban['id'], true);
			syslog(1, json_encode($ban));
		} else {
			modLog('Denied ban appeal #' . $ban['id'] . ' for ' . $ban['mask']);
			Bans::deny_appeal($ban['id']);
		}

		header('Location: ?/ban-appeals', true, $config['redirect_http']);
		return;
	}


	$local = ($mod['type'] < GLOBALVOLUNTEER);

	$ban_appeals = Bans::get_requested_appeals($local ?  $mod['boards'][0] : false);

	foreach ($ban_appeals as &$ban) {
		if ($ban['post']) {
			$ban['post'] = json_decode($ban['post'], true);
		}

		$ban['mask'] = Bans::range_to_string(array($ban['ipstart'], $ban['ipend']));

		if ($ban['post'] && isset($ban['post']['board'], $ban['post']['id'])) {
			openBoard($ban['post']['board']);

			if ($ban['post']['thread']) {
				$po          = new Post($ban['post']);
				$ban['post'] = $po->build(true);
			} else {
				$po          = new Thread($ban['post'], null, false, false);
				$ban['post'] = $po->build(true);
			}
		}
	}

	mod_page(_('Ban appeals'), 'mod/ban_appeals.html', array(
		'ban_appeals' => $ban_appeals,
		'token'       => make_secure_link_token('ban-appeals'),
	));

}


function mod_lock($board, $unlock, $post) {
	global $config;
	
	if (!openBoard($board))
		error($config['error']['noboard']);
	
	if (!hasPermission($config['mod']['lock'], $board))
		error($config['error']['noaccess']);
	
	$query = prepare(sprintf('UPDATE ``posts_%s`` SET `locked` = :locked WHERE `id` = :id AND `thread` IS NULL', $board));
	$query->bindValue(':id', $post);
	$query->bindValue(':locked', $unlock ? 0 : 1);
	$query->execute() or error(db_error($query));
	if ($query->rowCount()) {
		modLog(($unlock ? 'Unlocked' : 'Locked') . " thread #{$post}");
		buildThread($post);
		buildIndex();
	}
	
	if ($config['mod']['dismiss_reports_on_lock']) {
		$query = prepare('DELETE FROM ``reports`` WHERE `board` = :board AND `post` = :id');
		$query->bindValue(':board', $board);
		$query->bindValue(':id', $post);
		$query->execute() or error(db_error($query));
	}
	
	header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);
	
	if ($unlock)
		event('unlock', $post);
	else
		event('lock', $post);
}

function mod_sticky($board, $unsticky, $post) {
	global $config;
	
	if (!openBoard($board))
		error($config['error']['noboard']);
	
	if (!hasPermission($config['mod']['sticky'], $board))
		error($config['error']['noaccess']);
	
	$query = prepare(sprintf('UPDATE ``posts_%s`` SET `sticky` = :sticky WHERE `id` = :id AND `thread` IS NULL', $board));
	$query->bindValue(':id', $post);
	$query->bindValue(':sticky', $unsticky ? 0 : 1);
	$query->execute() or error(db_error($query));
	if ($query->rowCount()) {
		modLog(($unsticky ? 'Unstickied' : 'Stickied') . " thread #{$post}");
		buildThread($post);
		buildIndex();
	}
	
	header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);
}

function mod_cycle($board, $uncycle, $post) {
	global $config;
	
	if (!openBoard($board))
		error($config['error']['noboard']);
	
	if (!hasPermission($config['mod']['cycle'], $board))
		error($config['error']['noaccess']);
	
	$query = prepare(sprintf('UPDATE ``posts_%s`` SET `cycle` = :cycle WHERE `id` = :id AND `thread` IS NULL', $board));
	$query->bindValue(':id', $post);
	$query->bindValue(':cycle', $uncycle ? 0 : 1);
	$query->execute() or error(db_error($query));
	if ($query->rowCount()) {
		modLog(($uncycle ? 'Uncycled' : 'Cycled') . " thread #{$post}");
		buildThread($post);
		buildIndex();
	}
	
	header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);
}

function mod_bumplock($board, $unbumplock, $post) {
	global $config;
	
	if (!openBoard($board))
		error($config['error']['noboard']);
	
	if (!hasPermission($config['mod']['bumplock'], $board))
		error($config['error']['noaccess']);
	
	$query = prepare(sprintf('UPDATE ``posts_%s`` SET `sage` = :bumplock WHERE `id` = :id AND `thread` IS NULL', $board));
	$query->bindValue(':id', $post);
	$query->bindValue(':bumplock', $unbumplock ? 0 : 1);
	$query->execute() or error(db_error($query));
	if ($query->rowCount()) {
		modLog(($unbumplock ? 'Unbumplocked' : 'Bumplocked') . " thread #{$post}");
		buildThread($post);
		buildIndex();
	}
	
	header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);
}

function mod_move_reply($originBoard, $postID) { 
	global $board, $config, $mod;

	if (!openBoard($originBoard))
		error($config['error']['noboard']);
	
	if (!hasPermission($config['mod']['move'], $originBoard))
		error($config['error']['noaccess']);

	$query = prepare(sprintf('SELECT * FROM ``posts_%s`` WHERE `id` = :id', $originBoard));
	$query->bindValue(':id', $postID);
	$query->execute() or error(db_error($query));
	if (!$post = $query->fetch(PDO::FETCH_ASSOC))
		error($config['error']['404']);

	if (isset($_POST['board'])) {
		$targetBoard = $_POST['board'];

		if ($_POST['target_thread']) {
			$query = prepare(sprintf('SELECT * FROM ``posts_%s`` WHERE `id` = :id', $targetBoard));
			$query->bindValue(':id', $_POST['target_thread']);
			$query->execute() or error(db_error($query)); // If it fails, thread probably does not exist
			$post['op'] = false;
			$post['thread'] = $_POST['target_thread'];
		}
		else {
			$post['op'] = true;
		}
		
		if ($post['files']) {
			$post['files'] = json_decode($post['files'], TRUE);
			$post['has_file'] = true;
			foreach ($post['files'] as $i => &$file) {
				$file['file_path'] = sprintf($config['board_path'], $config['dir']['img_root'] . $board['uri']) . $config['dir']['img'] . $file['file'];
				$file['thumb_path'] = sprintf($config['board_path'], $config['dir']['img_root'] . $board['uri']) . $config['dir']['thumb'] . $file['thumb'];
			}
		} else {
			$post['has_file'] = false;
		}
		
		// allow thread to keep its same traits (stickied, locked, etc.)
		$post['mod'] = true;
		
		if (!openBoard($targetBoard))
			error($config['error']['noboard']);
		
		// create the new post 
		$newID = post($post);
		
		if ($post['has_file']) {
			foreach ($post['files'] as $i => &$file) {
				// move the image
				rename($file['file_path'], sprintf($config['board_path'], $config['dir']['img_root'] . $board['uri']) . $config['dir']['img'] . $file['file']);
				if ($file['thumb'] != 'spoiler') { //trying to move/copy the spoiler thumb raises an error
					rename($file['thumb_path'], sprintf($config['board_path'], $config['dir']['img_root'] . $board['uri']) . $config['dir']['thumb'] . $file['thumb']);
				}
			}
		}

		// build index
		buildIndex();
		// build new thread
		buildThread($newID);
		
		// trigger themes
		rebuildThemes('post', $targetBoard);
		// mod log
		modLog("Moved post #${postID} to " . sprintf($config['board_abbreviation'], $targetBoard) . " (#${newID})", $originBoard);
		
		// return to original board
		openBoard($originBoard);

		// delete original post
		deletePost($postID);
		buildIndex();

		// open target board for redirect
		openBoard($targetBoard);

		// Find new thread on our target board
		$query = prepare(sprintf('SELECT thread FROM ``posts_%s`` WHERE `id` = :id', $targetBoard));
		$query->bindValue(':id', $newID);
		$query->execute() or error(db_error($query));
		$post = $query->fetch(PDO::FETCH_ASSOC);

		// redirect
		header('Location: ?/' . sprintf($config['board_path'], $board['uri']) . $config['dir']['res'] . sprintf($config['file_page'], $post['thread'] ? $post['thread'] : $newID) . '#' . $newID, true, $config['redirect_http']);
	}

	else {
		$boards = listBoards();
		
		$security_token = make_secure_link_token($originBoard . '/move_reply/' . $postID);
		
		mod_page(_('Move reply'), 'mod/move_reply.html', array('post' => $postID, 'board' => $originBoard, 'boards' => $boards, 'token' => $security_token));

	}

}

function mod_move($originBoard, $postID) {
	global $board, $config, $mod, $pdo;
	
	if (!openBoard($originBoard))
		error($config['error']['noboard']);
	
	if (!hasPermission($config['mod']['move'], $originBoard))
		error($config['error']['noaccess']);
	
	$query = prepare(sprintf('SELECT * FROM ``posts_%s`` WHERE `id` = :id AND `thread` IS NULL', $originBoard));
	$query->bindValue(':id', $postID);
	$query->execute() or error(db_error($query));
	if (!$post = $query->fetch(PDO::FETCH_ASSOC))
		error($config['error']['404']);
	
	if (isset($_POST['board'])) {
		$targetBoard = $_POST['board'];
		$shadow = isset($_POST['shadow']);
		
		if ($targetBoard === $originBoard)
			error(_('Target and source board are the same.'));
		
		// copy() if leaving a shadow thread behind; else, rename().
		$clone = $shadow ? 'copy' : 'rename';
		
		// indicate that the post is a thread
		$post['op'] = true;
		
		if ($post['files']) {
			$post['files'] = json_decode($post['files'], TRUE);
			$post['has_file'] = true;
			foreach ($post['files'] as $i => &$file) {
				if ($file['file'] === 'deleted') 
					continue;
				$file['file_path'] = sprintf($config['board_path'], $config['dir']['img_root'] . $board['uri']) . $config['dir']['img'] . $file['file'];
				$file['thumb_path'] = sprintf($config['board_path'], $config['dir']['img_root'] . $board['uri']) . $config['dir']['thumb'] . $file['thumb'];
			}
		} else {
			$post['has_file'] = false;
		}
		
		// allow thread to keep its same traits (stickied, locked, etc.)
		$post['mod'] = true;
		
		if (!openBoard($targetBoard))
			error($config['error']['noboard']);
		
		// create the new thread
		$newID = post($post);
		
		if ($post['has_file']) {
			// copy image
			foreach ($post['files'] as $i => &$file) {
				if ($file['file'] !== 'deleted') 
					$clone($file['file_path'], sprintf($config['board_path'], $config['dir']['img_root'] . $board['uri']) . $config['dir']['img'] . $file['file']);
				if (isset($file['thumb']) && !in_array($file['thumb'], array('spoiler', 'deleted', 'file')))
					$clone($file['thumb_path'], sprintf($config['board_path'], $config['dir']['img_root'] . $board['uri']) . $config['dir']['thumb'] . $file['thumb']);
			}
		}
		
		// go back to the original board to fetch replies
		openBoard($originBoard);
		
		$query = prepare(sprintf('SELECT * FROM ``posts_%s`` WHERE `thread` = :id ORDER BY `id`', $originBoard));
		$query->bindValue(':id', $postID, PDO::PARAM_INT);
		$query->execute() or error(db_error($query));
		
		$replies = array();
		
		while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
			$post['mod'] = true;
			$post['thread'] = $newID;
			
			if ($post['files']) {
				$post['files'] = json_decode($post['files'], TRUE);
				$post['has_file'] = true;
				foreach ($post['files'] as $i => &$file) {
					$file['file_path'] = sprintf($config['board_path'], $config['dir']['img_root'] . $board['uri']) . $config['dir']['img'] . $file['file'];
					$file['thumb_path'] = sprintf($config['board_path'], $config['dir']['img_root'] . $board['uri']) . $config['dir']['thumb'] . $file['thumb'];
				}
			} else {
				$post['has_file'] = false;
			}
			
			$replies[] = $post;
		}
		
		$newIDs = array($postID => $newID);
		
		openBoard($targetBoard);
		
		foreach ($replies as &$post) {
			$query = prepare('SELECT `target` FROM ``cites`` WHERE `target_board` = :board AND `board` = :board AND `post` = :post');
			$query->bindValue(':board', $originBoard);
			$query->bindValue(':post', $post['id'], PDO::PARAM_INT);
			$query->execute() or error(db_error($query));
			
			// correct >>X links
			while ($cite = $query->fetch(PDO::FETCH_ASSOC)) {
				if (isset($newIDs[$cite['target']])) {
					$post['body_nomarkup'] = preg_replace(
							'/(>>(>\/' . preg_quote($originBoard, '/') . '\/)?)' . preg_quote($cite['target'], '/') . '/',
							'>>' . $newIDs[$cite['target']],
							$post['body_nomarkup']);
					
					$post['body'] = $post['body_nomarkup'];
				}
			}
			
			$post['body'] = $post['body_nomarkup'];
			
			$post['op'] = false;
			$post['tracked_cites'] = markup($post['body'], true);
			
			if ($post['has_file']) {
				// copy image
				foreach ($post['files'] as $i => &$file) {
					if ($file['file'] !== 'deleted') 
						$clone($file['file_path'], sprintf($config['board_path'], $config['dir']['img_root'] . $board['uri']) . $config['dir']['img'] . $file['file']);
					if (isset($file['thumb']) && !in_array($file['thumb'], array('spoiler', 'deleted', 'file')))
						$clone($file['thumb_path'], sprintf($config['board_path'], $config['dir']['img_root'] . $board['uri']) . $config['dir']['thumb'] . $file['thumb']);
				}
			}
			// insert reply
			$newIDs[$post['id']] = $newPostID = post($post);
			
			
			if (!empty($post['tracked_cites'])) {
				$insert_rows = array();
				foreach ($post['tracked_cites'] as $cite) {
					$insert_rows[] = '(' .
						$pdo->quote($board['uri']) . ', ' . $newPostID . ', ' .
						$pdo->quote($cite[0]) . ', ' . (int)$cite[1] . ')';
				}
				query('INSERT INTO ``cites`` VALUES ' . implode(', ', $insert_rows)) or error(db_error());
			}
		}
		
		modLog("Moved thread #${postID} to " . sprintf($config['board_abbreviation'], $targetBoard) . " (#${newID})", $originBoard);
		
		// build new thread
		buildThread($newID);
		
		clean();
		buildIndex();
		
		// trigger themes
		rebuildThemes('post', $targetBoard);
		
		// return to original board
		openBoard($originBoard);
		
		if ($shadow) {
			// lock old thread
			$query = prepare(sprintf('UPDATE ``posts_%s`` SET `locked` = 1 WHERE `id` = :id', $originBoard));
			$query->bindValue(':id', $postID, PDO::PARAM_INT);
			$query->execute() or error(db_error($query));
			
			// leave a reply, linking to the new thread
			$post = array(
				'mod' => true,
				'subject' => '',
				'email' => '',
				'name' => (!$config['mod']['shadow_name'] ? $config['anonymous'] : $config['mod']['shadow_name']),
				'capcode' => $config['mod']['shadow_capcode'],
				'trip' => '',
				'password' => '',
				'has_file' => false,
				// attach to original thread
				'thread' => $postID,
				'op' => false
			);

			$post['body'] = $post['body_nomarkup'] =  sprintf($config['mod']['shadow_mesage'], '>>>/' . $targetBoard . '/' . $newID);
			
			markup($post['body']);
			
			$botID = post($post);
			buildThread($postID);
			
			buildIndex();
			
			header('Location: ?/' . sprintf($config['board_path'], $originBoard) . $config['dir']['res'] .sprintf($config['file_page'], $postID) .
				'#' . $botID, true, $config['redirect_http']);
		} else {
			deletePost($postID);
			buildIndex();
			
			openBoard($targetBoard);
			header('Location: ?/' . sprintf($config['board_path'], $board['uri']) . $config['dir']['res'] . sprintf($config['file_page'], $newID), true, $config['redirect_http']);
		}
	}
	
	$boards = listBoards();
	if (count($boards) <= 1)
		error(_('Impossible to move thread; there is only one board.'));
	
	$security_token = make_secure_link_token($originBoard . '/move/' . $postID);
	
	mod_page(_('Move thread'), 'mod/move.html', array('post' => $postID, 'board' => $originBoard, 'boards' => $boards, 'token' => $security_token));
}

function mod_ban_post($board, $delete, $post, $token = false) {

	global $config;

	if (!openBoard($board)) {
		error($config['error']['noboard']);
	}

	if (!hasPermission($config['mod']['delete'], $board)) {
		error($config['error']['noaccess']);
	}

	$security_token = make_secure_link_token($board . '/ban/' . $post);

	$query = prepare(sprintf('SELECT ' . ($config['ban_show_post'] ? '*' : '`ip`, `thread`') .
		' FROM ``posts_%s`` WHERE `id` = :id', $board));
	$query->bindValue(':id', $post);
	$query->execute() or error(db_error($query));
	if (!$_post = $query->fetch(PDO::FETCH_ASSOC)) {
		error($config['error']['404']);
	}

	$thread = $_post['thread'];
	$ip     = $_post['ip'];
	$tor    = false;//!!! Восстановить значение - если пост отправлен из тор ноды, иначе красное предупреждение пр бане не будет работать checkDNSBL($ip);

	if (isset($_POST['new_ban'], $_POST['reason'], $_POST['length'], $_POST['board'])) {
		require_once 'inc/mod/ban.php';

		if (isset($_POST['ip'])) {
			$ip = $_POST['ip'];
		}

		if (isset($_POST['range'])) {
			$ip = $ip . $_POST['range'];
		}

		Bans::new_ban($ip, $_POST['reason'], $_POST['length'], $_POST['board'] == '*' ? false : $_POST['board'],
			false, $config['ban_show_post'] ? $_post : false);

		if (isset($_POST['public_message'], $_POST['message'])) {
			// public ban message
			$length_english   = @Bans::parse_time($_POST['length']) ? 'for ' . until(@Bans::parse_time($_POST['length'])) : 'permanently';
			$_POST['message'] = preg_replace('/[\r\n]/', '', $_POST['message']);
			$_POST['message'] = str_replace('%length%', $length_english, $_POST['message']);
			$_POST['message'] = str_replace('%LENGTH%', strtoupper($length_english), $_POST['message']);
			$query            = prepare(sprintf('UPDATE ``posts_%s`` SET `body_nomarkup` = CONCAT(`body_nomarkup`, :body_nomarkup) WHERE `id` = :id', $board));
			$query->bindValue(':id', $post);
			$query->bindValue(':body_nomarkup', sprintf("\n<tinyboard ban message>%s</tinyboard>", utf8tohtml($_POST['message'])));
			$query->execute() or error(db_error($query));
			rebuildPost($post);

			modLog("Attached a public ban message to post #{$post}: " . utf8tohtml($_POST['message']));
			buildThread($thread ? $thread : $post);
			buildIndex();
		} elseif (isset($_POST['delete']) && (int) $_POST['delete']) {
			// Delete post
			deletePost($post);
			modLog("Deleted post #{$post}");
			// Rebuild board
			buildIndex();
			// Rebuild themes
			rebuildThemes('post-delete', $board);
		}

		header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);
	}

	$args = array(
		'ip'      => $ip,
		'hide_ip' => !hasPermission($config['mod']['show_ip'], $board),
		'post'    => $post,
		'board'   => $board,
		'tor'     => $tor,
		'delete'  => (bool) $delete,
		'boards'  => listBoards(),
		'token'   => $security_token,
	);

	mod_page(_('New ban'), 'mod/ban_form.html', $args);
}

function mod_edit_post($board, $edit_raw_html, $postID) {
	global $config, $mod;

	if (!openBoard($board))
		error($config['error']['noboard']);

	if (!hasPermission($config['mod']['editpost'], $board))
		error($config['error']['noaccess']);
	
	if ($edit_raw_html && !hasPermission($config['mod']['rawhtml'], $board))
		error($config['error']['noaccess']);

	$security_token = make_secure_link_token($board . '/edit' . ($edit_raw_html ? '_raw' : '') . '/' . $postID);
	
	$query = prepare(sprintf('SELECT * FROM ``posts_%s`` WHERE `id` = :id', $board));
	$query->bindValue(':id', $postID);
	$query->execute() or error(db_error($query));

	if (!$post = $query->fetch(PDO::FETCH_ASSOC))
		error($config['error']['404']);
	
	if (isset($_POST['name'], $_POST['email'], $_POST['subject'], $_POST['body'])) {
		$trip = isset($_POST['remove_trip']) ? ' `trip` = NULL,' : '';

		// Remove any modifiers they may have put in
		$_POST['body'] = remove_modifiers($_POST['body']);

		// Add back modifiers in the original post
		$modifiers = extract_modifiers($post['body_nomarkup']);
		foreach ($modifiers as $key => $value) {
			if($key == 'raw html' && !hasPermission($config['mod']['rawhtml'], $board)) {
				continue;
			}
			$_POST['body'] .= "<tinyboard $key>$value</tinyboard>";
		}

		// Handle embed edits...
		foreach ($config['embedding'] as &$embed) {
			if (preg_match($embed[0], $_POST['embed'])) {
				$embed_link = $_POST['embed'];
			}
		}

		if ($edit_raw_html)
			$query = prepare(sprintf('UPDATE ``posts_%s`` SET `name` = :name,' . $trip . ' `email` = :email, `subject` = :subject, `body` = :body, `body_nomarkup` = :body_nomarkup, `embed` = :embed, `edited_at` = UNIX_TIMESTAMP(NOW()) WHERE `id` = :id', $board));
		else
			$query = prepare(sprintf('UPDATE ``posts_%s`` SET `name` = :name,' . $trip . ' `email` = :email, `subject` = :subject, `body_nomarkup` = :body, `embed` = :embed, `edited_at` = UNIX_TIMESTAMP(NOW()) WHERE `id` = :id', $board));
		$query->bindValue(':id', $postID);
		$query->bindValue(':name', $_POST['name'] ? $_POST['name'] : $config['anonymous']);
		$query->bindValue(':email', $_POST['email']);
		$query->bindValue(':subject', $_POST['subject']);
		$query->bindValue(':body', $_POST['body']);
		if ($edit_raw_html) {
			$body_nomarkup = $_POST['body'] . "\n<tinyboard raw html>1</tinyboard>";
			$query->bindValue(':body_nomarkup', $body_nomarkup);
		}
		if (isset($embed_link)) {
			$query->bindValue(':embed', $embed_link);
		} else {
			$query->bindValue(':embed', NULL, PDO::PARAM_NULL);
		}
		$query->execute() or error(db_error($query));
		
		if($config['clean']['edits_remove_local'] || $config['clean']['edits_remove_global'] ) {
			
			$query_global     = "`clean_global` = :clean";
			$query_global_mod = "`clean_global_mod_id` = :mod";
			$query_local      = "`clean_local` = :clean";
			$query_local_mod  = "`clean_local_mod_id` = :mod";
			
			if( $config['clean']['edits_remove_local'] && $config['clean']['edits_remove_global'] ) {
				$query = prepare("UPDATE `post_clean` SET {$query_global}, {$query_global_mod}, {$query_local}, {$query_local_mod} WHERE `board_id` = :board AND `post_id` = :post");
			}
			else if( $config['clean']['edits_remove_global'] ) {
				$query = prepare("UPDATE `post_clean` SET {$query_global}, {$query_global_mod} WHERE `board_id` = :board AND `post_id` = :post");
			}
			else {
				$query = prepare("UPDATE `post_clean` SET {$query_local}, {$query_local_mod} WHERE `board_id` = :board AND `post_id` = :post");
			}
			
			$query->bindValue( ':clean', false );
			$query->bindValue( ':mod',   NULL );
			$query->bindValue( ':board', $board );
			$query->bindValue( ':post',  $postID );
			
			$query->execute() or error(db_error($query));
			
			// Finally, run a query to tidy up our records.
			$cleanup = prepare("DELETE FROM `post_clean` WHERE `clean_local` = FALSE AND `clean_global` = FALSE");
			$query->execute() or error(db_error($query));
		}
		
		if ($edit_raw_html) {
			modLog("Edited raw HTML of post #{$postID}");
		} else {
			modLog("Edited post #{$postID}");
			rebuildPost($postID);
		}
		
		buildIndex();
		
		rebuildThemes('post', $board);
		
		header('Location: ?/' . sprintf($config['board_path'], $board) . $config['dir']['res'] . sprintf($config['file_page'], $post['thread'] ? $post['thread'] : $postID) . '#' . $postID, true, $config['redirect_http']);
	} else {
		// Remove modifiers
		$post['body_nomarkup'] = remove_modifiers($post['body_nomarkup']);
				
		$post['body_nomarkup'] = utf8tohtml($post['body_nomarkup']);
		$post['body'] = utf8tohtml($post['body']);
		if ($config['minify_html']) {
			$post['body_nomarkup'] = str_replace("\n", '&#010;', $post['body_nomarkup']);
			$post['body'] = str_replace("\n", '&#010;', $post['body']);
			$post['body_nomarkup'] = str_replace("\r", '', $post['body_nomarkup']);
			$post['body'] = str_replace("\r", '', $post['body']);
			$post['body_nomarkup'] = str_replace("\t", '&#09;', $post['body_nomarkup']);
			$post['body'] = str_replace("\t", '&#09;', $post['body']);
		}

		$preview = new Post($post);
		$html = $preview->build(true);

		mod_page(_('Edit post'), 'mod/edit_post_form.html', array('token' => $security_token, 'board' => $board, 'raw' => $edit_raw_html, 'post' => $post, 'preview' => $html));
	}
}

function mod_delete($board, $post) 
{
	global $config, $mod;
	
	if (!openBoard($board))
		error($config['error']['noboard']);

	if (!hasPermission($config['mod']['delete'], $board))
		error($config['error']['noaccess']);
	
	// Delete post
	deletePost($post);
	// Record the action
	modLog("Deleted post #{$post}");
	// Rebuild board
	buildIndex();
	// Rebuild themes
	rebuildThemes('post-delete', $board);
	// Redirect

	header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);	
}

function mod_deletefile($board, $post, $file) {
	global $config, $mod;
	
	if (!openBoard($board))
		error($config['error']['noboard']);
	
	if (!hasPermission($config['mod']['deletefile'], $board))
		error($config['error']['noaccess']);
	
	// Delete file
	deleteFile($post, TRUE, $file);
	// Record the action
	modLog("Deleted file from post #{$post}");
	
	// Rebuild board
	buildIndex();
	// Rebuild themes
	rebuildThemes('post-delete', $board);
	
	// Redirect
	header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);
}

function mod_banhashfile($board, $post, $file) {

	global $config;

	if (!openBoard($board)) {
		error($config['error']['noboard']);
	}

	if (!hasPermission($config['mod']['deletefile'], $board)) {
		error($config['error']['noaccess']);
	}

	// Delete file thumbnail
	$query = prepare(sprintf("SELECT `files`, `thread` FROM ``posts_%s`` WHERE id = :id", $board));
	$query->bindValue(':id', $post, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	$result = $query->fetch(PDO::FETCH_ASSOC);
	$files  = json_decode($result['files']);

	if(!isset($files[$file])) {
		error(_('Banhash: File not exists'));
	}

	$hash = $files[$file]->hash;
	$type_hash = 'md5';
	if($files[$file]->is_an_image) {
		include_once 'inc/lib/imagehash/imagehash.php';
		$hasher = new Jenssegers\ImageHash\ImageHash;
		$hash = $hasher->hash($files[$file]->file_path);
		$type_hash = 'imagehash';
	}

	$query = prepare('INSERT INTO ``filters`` VALUES (NULL, :board, :type, :reason, :value)');
	$query->bindValue(':board', $board);
	$query->bindValue(':type', $type_hash);
	$query->bindValue(':reason', "Add to filter hash file from post #{$post}");
	$query->bindValue(':value', $hash);
	$query->execute() or error(db_error($query));
	
	// Record the action
	modLog("Add to filter hash file from post #{$post}");

	// Redirect
	header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);
}

function mod_spoiler_image($board, $post, $file) {
	global $config, $mod;
	   
	if (!openBoard($board))
		error($config['error']['noboard']);
	   
	if (!hasPermission($config['mod']['spoilerimage'], $board))
		error($config['error']['noaccess']);

	// Delete file thumbnail
	$query = prepare(sprintf("SELECT `files`, `thread` FROM ``posts_%s`` WHERE id = :id", $board));
	$query->bindValue(':id', $post, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	$result = $query->fetch(PDO::FETCH_ASSOC);
	$files = json_decode($result['files']);

	$size_spoiler_image = @getimagesize($config['spoiler_image']);
	file_unlink($config['dir']['img_root'] . $board . '/' . $config['dir']['thumb'] . $files[$file]->thumb);
	$files[$file]->thumb = 'spoiler';
	$files[$file]->thumbwidth = $size_spoiler_image[0];
	$files[$file]->thumbheight = $size_spoiler_image[1];
	
	// Make thumbnail spoiler
	$query = prepare(sprintf("UPDATE ``posts_%s`` SET `files` = :files WHERE `id` = :id", $board));
	$query->bindValue(':files', json_encode($files));
	$query->bindValue(':id', $post, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));

	// Record the action
	modLog("Spoilered file from post #{$post}");

	// Rebuild thread
	buildThread($result['thread'] ? $result['thread'] : $post);

	// Rebuild board
	buildIndex();

	// Rebuild themes
	rebuildThemes('post-delete', $board);
	   
	// Redirect
	header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);
}

function mod_spoiler_images($board, $post) {
	global $config, $mod;
	   
	if (!openBoard($board))
		error($config['error']['noboard']);
	   
	if (!hasPermission($config['mod']['spoilerimage'], $board))
		error($config['error']['noaccess']);

	// Delete file thumbnails
	$query = prepare(sprintf("SELECT `files`, `thread` FROM ``posts_%s`` WHERE id = :id", $board));
	$query->bindValue(':id', $post, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));
	$result = $query->fetch(PDO::FETCH_ASSOC);
	$files = json_decode($result['files']);
	
	if(!count($files)) {
		error(_('That post has no files.'));
	}

	foreach ($files as $file => $name) {
		$size_spoiler_image = @getimagesize($config['spoiler_image']);
		file_unlink($config['dir']['img_root'] . $board . '/' . $config['dir']['thumb'] . $files[$file]->thumb);
		$files[$file]->thumb = 'spoiler';
		$files[$file]->thumbwidth = $size_spoiler_image[0];
		$files[$file]->thumbheight = $size_spoiler_image[1];
	}
	// Make thumbnail spoiler
	$query = prepare(sprintf("UPDATE ``posts_%s`` SET `files` = :files WHERE `id` = :id", $board));
	$query->bindValue(':files', json_encode($files));
	$query->bindValue(':id', $post, PDO::PARAM_INT);
	$query->execute() or error(db_error($query));

	// Record the action
	modLog("Spoilered file from post #{$post}");

	// Rebuild thread
	buildThread($result['thread'] ? $result['thread'] : $post);

	// Rebuild board
	buildIndex();

	// Rebuild themes
	rebuildThemes('post-delete', $board);
	   
	// Redirect
	header('Location: ?/' . sprintf($config['board_path'], $board) . $config['file_index'], true, $config['redirect_http']);
}

function mod_deletebyip($boardName, $post, $global = false) 
{
	global $config, $mod, $board;
	
	$global = (bool)$global;
	
	if (!openBoard($boardName))
		error($config['error']['noboard']);
	
	if (!$global && !hasPermission($config['mod']['deletebyip'], $boardName))
		error($config['error']['noaccess']);
	
	if ($global && !hasPermission($config['mod']['deletebyip_global'], $boardName))
		error($config['error']['noaccess']);
	
	// Find IP address
	$query = prepare(sprintf('SELECT `ip` FROM ``posts_%s`` WHERE `id` = :id', $boardName));
	$query->bindValue(':id', $post);
	$query->execute() or error(db_error($query));
	if (!$ip = $query->fetchColumn())
		error($config['error']['invalidpost']);
	
	$boards = $global ? listBoards() : array(array('uri' => $boardName));
	
	$query = '';
	foreach ($boards as $_board) {
		$query .= sprintf("SELECT `thread`, `id`, '%s' AS `board` FROM ``posts_%s`` WHERE `ip` = :ip UNION ALL ", $_board['uri'], $_board['uri']);
	}
	$query = preg_replace('/UNION ALL $/', '', $query);
	
	$query = prepare($query);
	$query->bindValue(':ip', $ip);
	$query->execute() or error(db_error($query));
	
	if ($query->rowCount() < 1)
		error($config['error']['invalidpost']);
	
	@set_time_limit($config['mod']['rebuild_timelimit']);
	
	$threads_to_rebuild = array();
	$threads_deleted = array();
	while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
		openBoard($post['board']);
		
		deletePost($post['id'], false, false);

		rebuildThemes('post-delete', $board['uri']);

		buildIndex();

		if ($post['thread'])
			$threads_to_rebuild[$post['board']][$post['thread']] = true;
		else
			$threads_deleted[$post['board']][$post['id']] = true;
	}
	
	foreach ($threads_to_rebuild as $_board => $_threads) {
		openBoard($_board);
		foreach ($_threads as $_thread => $_dummy) {
			if ($_dummy && !isset($threads_deleted[$_board][$_thread]))
				buildThread($_thread);
		}
		buildIndex();
	}

	// Record the action
	modLog("Deleted all posts by IP address: " . ip_link($ip));

	// Redirect
	header('Location: ?/' . sprintf($config['board_path'], $boardName) . $config['file_index'], true, $config['redirect_http']);
}

function mod_user($uid) {
	global $config, $mod;
	
	if (!hasPermission($config['mod']['editusers']) && !(hasPermission($config['mod']['edit_profile']) && $uid == $mod['id']))
		error($config['error']['noaccess']);

	if (in_array($mod['boards'][0], array('infinity', 'z')))
		error('This board has profile changing disabled.');
	
	$query = prepare('SELECT * FROM ``mods`` WHERE `id` = :id');
	$query->bindValue(':id', $uid);
	$query->execute() or error(db_error($query));
	if (!$user = $query->fetch(PDO::FETCH_ASSOC))
		error($config['error']['404']);
	
	if (hasPermission($config['mod']['editusers']) && isset($_POST['username'], $_POST['password'])) {
		if (isset($_POST['allboards'])) {
			$boards = array('*');
		} else {
			$_boards = listBoards();
			foreach ($_boards as &$board) {
				$board = $board['uri'];
			}
		
			$boards = array();
			foreach ($_POST as $name => $value) {
				if (preg_match('/^board_(' . $config['board_regex'] . ')$/u', $name, $matches) && in_array($matches[1], $_boards))
					$boards[] = $matches[1];
			}
		}
		
		if (isset($_POST['delete'])) {
			if (!hasPermission($config['mod']['deleteusers']))
				error($config['error']['noaccess']);
			
			$query = prepare('DELETE FROM ``mods`` WHERE `id` = :id');
			$query->bindValue(':id', $uid);
			$query->execute() or error(db_error($query));
			
			modLog('Deleted user ' . utf8tohtml($user['username']) . ' <small>(#' . $user['id'] . ')</small>');
			
			header('Location: ?/users', true, $config['redirect_http']);
			
			return;
		}
		
		if ($_POST['username'] == '')
			error(sprintf($config['error']['required'], 'username'));
		
		$query = prepare('UPDATE ``mods`` SET `username` = :username, `boards` = :boards WHERE `id` = :id');
		$query->bindValue(':id', $uid);
		$query->bindValue(':username', $_POST['username']);
		$query->bindValue(':boards', implode(',', $boards));
		$query->execute() or error(db_error($query));
		
		if ($user['username'] !== $_POST['username']) {
			// account was renamed
			modLog('Renamed user "' . utf8tohtml($user['username']) . '" <small>(#' . $user['id'] . ')</small> to "' . utf8tohtml($_POST['username']) . '"');
		}
		
		if ($_POST['password'] != '') {
			$salt = generate_salt();
			$password = hash('sha256', $salt . sha1($_POST['password']));
			
			$query = prepare('UPDATE ``mods`` SET `password` = :password, `salt` = :salt WHERE `id` = :id');
			$query->bindValue(':id', $uid);
			$query->bindValue(':password', $password);
			$query->bindValue(':salt', $salt);
			$query->execute() or error(db_error($query));
			
			modLog('Changed password for ' . utf8tohtml($_POST['username']) . ' <small>(#' . $user['id'] . ')</small>');
			
			if ($uid == $mod['id']) {
				login($_POST['username'], $_POST['password']);
				setCookies();
			}
		}
		
		if (hasPermission($config['mod']['manageusers']))
			header('Location: ?/users', true, $config['redirect_http']);
		else
			header('Location: ?/', true, $config['redirect_http']);
		
		return;
	}
	
	if (hasPermission($config['mod']['edit_profile']) && $uid == $mod['id']) {
		if (isset($_POST['password']) && $_POST['password'] != '') {
			$salt = generate_salt();
			$password = hash('sha256', $salt . sha1($_POST['password']));

			$query = prepare('UPDATE ``mods`` SET `password` = :password, `salt` = :salt WHERE `id` = :id');
			$query->bindValue(':id', $uid);
			$query->bindValue(':password', $password);
			$query->bindValue(':salt', $salt);
			$query->execute() or error(db_error($query));
			
			modLog('Changed own password');
			
			login($user['username'], $_POST['password']);
			setCookies();
		}

		if (isset($_POST['username']) && $user['username'] !== $_POST['username']) {
			if ($_POST['username'] == '')
				error(sprintf($config['error']['required'], 'username'));

			if (!preg_match('/^[a-zA-Z0-9._]{1,30}$/', $_POST['username']))
				error(_('Invalid username'));
			
			$query = prepare('SELECT `username`,`id` FROM ``mods``');
			$query->execute() or error(db_error($query));
			$users = $query->fetchAll(PDO::FETCH_ASSOC);

			foreach ($users as $i => $v) {
				if (strtolower($_POST['username']) == strtolower($v['username']) && $v['id'] !== $uid) {
					error(_('Refusing to change your username because another user is already using it.'));
				}
			}

			$query = prepare('UPDATE ``mods`` SET `username` = :username WHERE `id` = :id');
			$query->bindValue(':id', $uid);
			$query->bindValue(':username', $_POST['username']);
			$query->execute() or error(db_error($query));
		
			modLog('Renamed user "' . utf8tohtml($user['username']) . '" <small>(#' . $user['id'] . ')</small> to "' . utf8tohtml($_POST['username']) . '"');
		}
	
		if (isset($_POST['email']) && $user['email'] !== $_POST['email'] && (empty($_POST['email']) || filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))) {
			// account was renamed
			$query = prepare('UPDATE ``mods`` SET `email` = :email WHERE `id` = :id');
			$query->bindValue(':id', $uid);
			$query->bindValue(':email', $_POST['email']);
			$query->execute() or error(db_error($query));
		
			modLog('Changed user\'s email "' . utf8tohtml($user['email']) . '" <small>(#' . $user['id'] . ')</small> to "' . utf8tohtml($_POST['email']) . '"');
		}
		
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			if (hasPermission($config['mod']['manageusers']))
				header('Location: ?/users', true, $config['redirect_http']);
			else
				header('Location: ?/', true, $config['redirect_http']);
			
			return;
		}
	}
	
	if (hasPermission($config['mod']['modlog'])) {
		$query = prepare('SELECT * FROM ``modlogs`` WHERE `mod` = :id ORDER BY `time` DESC LIMIT 5');
		$query->bindValue(':id', $uid);
		$query->execute() or error(db_error($query));
		$log = $query->fetchAll(PDO::FETCH_ASSOC);
	} else {
		$log = array();
	}
	
	if ($mod['type'] >= ADMIN){
		$boards = listBoards();
	} else {
		$boards2 = explode(',', $user['boards']);

		foreach ($boards2 as $string){
			$boards[] = array("uri"=>$string, "title" => _("My board"));
		}
	}

	$user['boards'] = explode(',', $user['boards']);
	
	mod_page(_('Edit user profile'), 'mod/user.html', array(
		'user' => $user,
		'logs' => $log,
		'boards' => $boards,
		'token' => make_secure_link_token('users/' . $user['id'])
	));
}


function mod_user_new() {
	global $pdo, $config;
	
	if (!hasPermission($config['mod']['createusers']))
		error($config['error']['noaccess']);
	
	if (isset($_POST['username'], $_POST['password'], $_POST['type'])) {
		if ($_POST['username'] == '')
			error(sprintf($config['error']['required'], 'username'));
		if ($_POST['password'] == '')
			error(sprintf($config['error']['required'], 'password'));
		
		if (isset($_POST['allboards'])) {
			$boards = array('*');
		} else {
			$_boards = listBoards();
			foreach ($_boards as &$board) {
				$board = $board['uri'];
			}
			
			$boards = array();
			foreach ($_POST as $name => $value) {
				if (preg_match('/^board_(' . $config['board_regex'] . ')$/u', $name, $matches) && in_array($matches[1], $_boards))
					$boards[] = $matches[1];
			}
		}
		
		$type = (int)$_POST['type'];
		if (!isset($config['mod']['groups'][$type]) || $type == DISABLED) {
			error(sprintf($config['error']['invalidfield'], 'type'));
		}

		$query = prepare('SELECT ``username``,``id`` FROM ``mods`` WHERE ``username`` = :username');
		$query->bindValue(':username', $_POST['username']);
		$query->execute() or error(db_error($query));

		if (($users = $query->fetch(PDO::FETCH_ASSOC))) {
			error(sprintf(_($config['error']['modexists']), $config['file_mod'] . '?/users/' . $users['id']));
		}
		
		$salt = generate_salt();
		$password = hash('sha256', $salt . sha1($_POST['password']));
		
		$query = prepare('INSERT INTO ``mods`` VALUES (NULL, :username, :password, :salt, :type, :boards, :email)');
		$query->bindValue(':username', $_POST['username']);
		$query->bindValue(':password', $password);
		$query->bindValue(':salt', $salt);
		$query->bindValue(':type', $type);
		$query->bindValue(':boards', implode(',', $boards));
		$query->bindValue(':email', (isset($_POST['email']) ? $_POST['email'] : ''));
		$query->execute() or error(db_error($query));
		
		$userID = $pdo->lastInsertId();
		
		modLog('Created a new user: ' . utf8tohtml($_POST['username']) . ' <small>(#' . $userID . ')</small>');
		
		header('Location: ?/users', true, $config['redirect_http']);
		return;
	}
		
	mod_page(_('New user'), 'mod/user.html', array('new' => true, 'boards' => listBoards(), 'token' => make_secure_link_token('users/new')));
}

function mod_users()
{
	global $config;
	
	if (!hasPermission($config['mod']['manageusers'])) {
		error($config['error']['noaccess']);
	}

	$users = query("SELECT `id`, `username`, `type`, `boards`, `email` FROM `mods` ORDER BY `type` DESC")->fetchAll(PDO::FETCH_ASSOC);

	foreach ($users as &$user) {

		$res = query("SELECT `time`, `text` FROM `modlogs` WHERE `mod`={$user['id']}  ORDER BY `time` DESC LIMIT 1")->fetch();

		$user['last'] =  $res['time'];
		//$user['text'] =  $res['text'];
		$user['promote_token'] = make_secure_link_token("users/{$user['id']}/promote");
		$user['demote_token'] = make_secure_link_token("users/{$user['id']}/demote");
	}

	mod_page(sprintf('%s (%d)', _('Manage users'), count($users)), 'mod/users.html', array('users' => $users));
}


function mod_user_promote($uid, $action)
{
	global $config;
	
	if (!hasPermission($config['mod']['promoteusers'])) {
		error($config['error']['noaccess']);
	}
	
	$query = prepare("SELECT `type`, `username` FROM ``mods`` WHERE `id` = :id");
	$query->bindValue(':id', $uid);
	$query->execute() or error(db_error($query));
	
	if (!$mod = $query->fetch(PDO::FETCH_ASSOC)) {
		error($config['error']['404']);
	}
	
	$new_group = false;
	
	$groups = $config['mod']['groups'];
	if ($action == 'demote')
		$groups = array_reverse($groups, true);
	
	foreach ($groups as $group_value => $group_name) {
		if ($action == 'promote' && $group_value > $mod['type']) {
			$new_group = $group_value;
			break;
		} elseif ($action == 'demote' && $group_value < $mod['type']) {
			$new_group = $group_value;
			break;
		}
	}
	
	if ($new_group === false || $new_group == DISABLED) {
		error(_('Impossible to promote/demote user.'));
	}
	
	$query = prepare("UPDATE ``mods`` SET `type` = :group_value WHERE `id` = :id");
	$query->bindValue(':id', $uid);
	$query->bindValue(':group_value', $new_group);
	$query->execute() or error(db_error($query));
	
	modLog(($action == 'promote' ? 'Promoted' : 'Demoted') . ' user "' .
		utf8tohtml($mod['username']) . '" to ' . $config['mod']['groups'][$new_group]);
	
	header('Location: ?/users', true, $config['redirect_http']);
}

function mod_pm($id, $reply = false) {
	global $mod, $config;
	
	if ($reply && !hasPermission($config['mod']['create_pm']))
		error($config['error']['noaccess']);
	
	$query = prepare("SELECT ``mods``.`username`, `mods_to`.`username` AS `to_username`, ``pms``.* FROM ``pms`` LEFT JOIN ``mods`` ON ``mods``.`id` = `sender` LEFT JOIN ``mods`` AS `mods_to` ON `mods_to`.`id` = `to` WHERE ``pms``.`id` = :id");
	$query->bindValue(':id', $id);
	$query->execute() or error(db_error($query));
	
	if ((!$pm = $query->fetch(PDO::FETCH_ASSOC)) || ($pm['to'] != $mod['id'] && !hasPermission($config['mod']['master_pm'])))
		error($config['error']['404']);
	
	if (isset($_POST['delete'])) {
		$query = prepare("DELETE FROM ``pms`` WHERE `id` = :id");
		$query->bindValue(':id', $id);
		$query->execute() or error(db_error($query));
		
		if ($config['cache']['enabled']) {
			cache::delete('pm_unread_' . $mod['id']);
			cache::delete('pm_unreadcount_' . $mod['id']);
		}
		
		header('Location: ?/', true, $config['redirect_http']);
		return;
	}
	
	if ($pm['unread'] && $pm['to'] == $mod['id']) {
		$query = prepare("UPDATE ``pms`` SET `unread` = 0 WHERE `id` = :id");
		$query->bindValue(':id', $id);
		$query->execute() or error(db_error($query));
		
		if ($config['cache']['enabled']) {
			cache::delete('pm_unread_' . $mod['id']);
			cache::delete('pm_unreadcount_' . $mod['id']);
		}
		
		modLog('Read a PM');
	}
	
	if ($reply) {
		if (!$pm['to_username'])
			error($config['error']['404']); // deleted?
		
		mod_page(sprintf('%s %s', _('New PM for'), $pm['to_username']), 'mod/new_pm.html', array(
			'username' => $pm['username'],
			'id' => $pm['sender'],
			'message' => quote($pm['message']),
			'token' => make_secure_link_token('new_PM/' . $pm['username'])
		));
	} else {
		mod_page(sprintf('%s &ndash; #%d', _('Private message'), $id), 'mod/pm.html', $pm);
	}
}

function mod_inbox() {
	global $config, $mod;
	
	$query = prepare('SELECT `unread`,``pms``.`id`, `time`, `sender`, `to`, `message`, `username` FROM ``pms`` LEFT JOIN ``mods`` ON ``mods``.`id` = `sender` WHERE `to` = :mod ORDER BY `unread` DESC, `time` DESC');
	$query->bindValue(':mod', $mod['id']);
	$query->execute() or error(db_error($query));
	$messages = $query->fetchAll(PDO::FETCH_ASSOC);
	
	$query = prepare('SELECT COUNT(*) FROM ``pms`` WHERE `to` = :mod AND `unread` = 1');
	$query->bindValue(':mod', $mod['id']);
	$query->execute() or error(db_error($query));
	$unread = $query->fetchColumn();
	
	foreach ($messages as &$message) {
		$message['snippet'] = pm_snippet($message['message']);
	}
	
	mod_page(sprintf('%s (%s)', _('PM inbox'), count($messages) > 0 ? $unread . ' unread' : 'empty'), 'mod/inbox.html', array(
		'messages' => $messages,
		'unread' => $unread
	));
}


function mod_new_pm($username) {
	global $config, $mod;
	
	if (!hasPermission($config['mod']['create_pm']))
		error($config['error']['noaccess']);
	
	$query = prepare("SELECT `id`, `boards` FROM ``mods`` WHERE `username` = :username");
	$query->bindValue(':username', $username);
	$query->execute() or error(db_error($query));
	if (!$row = $query->fetch()) {
		error($config['error']['404']);
	}

	// Rate limit for PMs
	if (!hasPermission($config['mod']['bypass_pm_ratelimit'])) {
		$ratelimit = prepare('SELECT `id` FROM ``pms`` WHERE FROM_UNIXTIME(`time`) > DATE_SUB(NOW(), INTERVAL 1 HOUR) AND `sender` = :sender');
		$ratelimit->bindValue(':sender', $mod['id']);
		$ratelimit->execute() or error(db_error($ratelimit));

		if ($ratelimit->rowCount() >= $config['mod']['pm_ratelimit']) {
			error(_('You are sending too many PMs per hour. Try again later.'));
		}
	}

	// Lock users into only being able to message users assigned to their board.
	if (!hasPermission($config['mod']['pm_all'])) {
		if ($mod['boards'][0] != $row['boards'] && !($row['boards'] === '*')) {
			error(_('You may only PM users assigned to your board'));
		}

		if ($row['boards'] === '*') {
			// If the global user PM'd them first within the last month, they can reply.
			$check = prepare('SELECT * FROM ``pms`` WHERE FROM_UNIXTIME(`time`) > DATE_SUB(NOW(), INTERVAL 1 MONTH) AND `sender` = :sender AND `to` = :to');
			$check->bindValue(':sender', $row['id']);
			$check->bindValue(':to', $mod['id']);
			$check->execute() or error(db_error($check));
			if (!$check->rowCount()) {
				error(_('You may not PM a member of global staff who did not PM you within the last month. Try posting on /operate/ or emailing us instead: admin@8chan.co'));
			}
		}
	}
	
	if (isset($_POST['message'])) {
		$id = $row['id'];

		if (strlen($_POST['message']) > $config['mod']['pm_maxsize']) {
			error(sprintf(_('Your message exceeds %d characters, please shorten it.'), $config['mod']['pm_maxsize']));
		}

		$_POST['message'] = escape_markup_modifiers($_POST['message']);
		markup($_POST['message']);
		
		$query = prepare("INSERT INTO ``pms`` VALUES (NULL, :me, :id, :message, :time, 1)");
		$query->bindValue(':me', $mod['id']);
		$query->bindValue(':id', $id);
		$query->bindValue(':message', $_POST['message']);
		$query->bindValue(':time', time());
		$query->execute() or error(db_error($query));
		
		if ($config['cache']['enabled']) {
			cache::delete('pm_unread_' . $id);
			cache::delete('pm_unreadcount_' . $id);
		}
		
		modLog('Sent a PM to ' . utf8tohtml($username));
		
		header('Location: ?/', true, $config['redirect_http']);
	}
	
	mod_page(sprintf('%s %s', _('New PM for'), $username), 'mod/new_pm.html', array(
		'username' => $username,
		'id' => $row['id'],
		'token' => make_secure_link_token('new_PM/' . $username)
	));
}

function mod_rebuild() {
	global $config, $twig;
	
	if (!hasPermission($config['mod']['rebuild']))
		error($config['error']['noaccess']);

	if(ini_get("max_execution_time") < 120)
		ini_set('max_execution_time', 120);

	
	if (isset($_POST['rebuild'])) {
		@set_time_limit($config['mod']['rebuild_timelimit']);
				
		$log = array();
		$boards = listBoards();
		$rebuilt_scripts = array();
		
		if (isset($_POST['rebuild_cache'])) {
			if ($config['cache']['enabled']) {
				$log[] = 'Flushing cache';
				Cache::flush();
			}
			
			$log[] = 'Clearing template cache';
			load_twig();
			$twig->clearCacheFiles();
		}
		
		if (isset($_POST['rebuild_themes'])) {
			$log[] = 'Regenerating theme files';
			rebuildThemes('all');
		}
		
		if (isset($_POST['rebuild_javascript'])) {
			$log[] = 'Rebuilding <strong>' . $config['file_script'] . '</strong>';
			buildJavascript();
			$rebuilt_scripts[] = $config['file_script'];
		}

		//Save conig
		$_config = $config;
		
		foreach ($boards as $board) {
			if (!(isset($_POST['boards_all']) || isset($_POST['board_' . $board['uri']])))
				continue;
			
			openBoard($board['uri']);
			$config['try_smarter'] = false;
			
			if (isset($_POST['rebuild_index'])) {
				buildIndex();
				$log[] = '<strong>' . sprintf($config['board_abbreviation'], $board['uri']) . '</strong>: Creating index pages';
			}
			
			if (isset($_POST['rebuild_javascript']) && !in_array($config['file_script'], $rebuilt_scripts)) {
				$log[] = '<strong>' . sprintf($config['board_abbreviation'], $board['uri']) . '</strong>: Rebuilding <strong>' . $config['file_script'] . '</strong>';
				buildJavascript();
				$rebuilt_scripts[] = $config['file_script'];
			}

			if (isset($_POST['rebuild_posts'])) {
				$query = query(sprintf("SELECT `id` FROM ``posts_%s``", $board['uri'])) or error(db_error());
				while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
					$log[] = '<strong>' . sprintf($config['board_abbreviation'], $board['uri']) . '</strong>: Rebuilding post #' . $post['id'];
					rebuildPost($post['id']);
				}
			}
			
			if (isset($_POST['rebuild_thread'])) {
				$query = query(sprintf("SELECT `id` FROM ``posts_%s`` WHERE `thread` IS NULL", $board['uri'])) or error(db_error());
				while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
					$log[] = '<strong>' . sprintf($config['board_abbreviation'], $board['uri']) . '</strong>: Rebuilding thread #' . $post['id'];
					buildThread($post['id']);
				}
			}

	
		}
		
		$config = $_config;
		mod_page(_('Rebuild'), 'mod/rebuilt.html', array('logs' => $log));
		return;
	}
	
	mod_page(_('Rebuild'), 'mod/rebuild.html', array(
		'boards' => listBoards(),
		'token' => make_secure_link_token('rebuild')
	));
}


function mod_reports() {
	global $config, $mod;
	
	// Parse arguments.
	$urlArgs = func_get_args();
	$global  = in_array( "global", $urlArgs );
	$json  = in_array( "json", $urlArgs );
	
	if( !hasPermission($config['mod']['reports']) ) {
		error($config['error']['noaccess']);
	}
	
	if( ($mod['type'] < GLOBALVOLUNTEER) and $global) {
		error($config['error']['noaccess']);
	}
	
	// Get REPORTS.
	$query = prepare("SELECT * FROM ``reports`` WHERE " . (($mod["type"] < GLOBALVOLUNTEER) ? "board = :board AND" : "") . " ``".($global ? "global" : "local")."`` = TRUE  LIMIT :limit");
	
	// Limit reports by board if the moderator is local.
	if( $mod['type'] < GLOBALVOLUNTEER ) {
		$query->bindValue(':board', $mod['boards'][0]);
	}
	
	// Limit by config ceiling.
	$query->bindValue( ':limit', $config['mod']['recent_reports'], PDO::PARAM_INT );
	
	$query->execute() or error(db_error($query));
	$reports = $query->fetchAll(PDO::FETCH_ASSOC);
	
	// Cut off here if we don't have any reports.
	$reportCount = 0;
	$reportHTML = '';
	if ( count( $reports ) > 0 ) {
		
		// Build queries to fetch content.
		$report_queries = array();
		foreach ($reports as $report) {
			if (!isset($report_queries[$report['board']]))
				$report_queries[$report['board']] = array();
			$report_queries[$report['board']][] = $report['post'];
		}
		
		// Get reported CONTENT.
		$report_posts = array();
		foreach ($report_queries as $board => $posts) {
			$report_posts[$board] = array();
			
			$query = query(sprintf('SELECT * FROM ``posts_%s`` WHERE `id` = ' . implode(' OR `id` = ', $posts), $board)) or error(db_error());
			while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
				$report_posts[$board][$post['id']] = $post;
			}
		}
		
		// Develop an associative array of posts to reports.
		$report_index = array();
		foreach( $reports as &$report ) {
			
			// Delete reports which are for removed content.
			if( !isset( $report_posts[ $report['board'] ][ $report['post'] ] ) ) {
				// Invalid report (post has since been deleted)
				$query = prepare("DELETE FROM ``reports`` WHERE `post` = :id AND `board` = :board");
				$query->bindValue(':id', $report['post'], PDO::PARAM_INT);
				$query->bindValue(':board', $report['board']);
				$query->execute() or error(db_error($query));
				continue;
			}
			
			// Build a unique ID.
			$content_key = "{$report['board']}.{$report['post']}";
			
			// Create a dummy array if it doesn't already exist.
			if( !isset( $report_index[ $content_key ] ) ) {
				$report_index[ $content_key ] = array(
					"board_id" => $report['board'],
					"post_id"  => $report['post'],
					"content"  => $report_posts[ $report['board'] ][ $report['post'] ],
					"reports"  => array(),
				);
			}
			
			// Add the report to the list of reports.
			$report_index[ $content_key ]['reports'][ $report['id'] ] = $report;
			
			// Increment the total report count.
			++$reportCount;
		}
		
		// Only continue if we have something to do.
		// If there are no valid reports left, we're done.
		if( $reportCount > 0 && !$json ) {
			
			// Sort this report index by number of reports, desc.
			usort( $report_index, function( $a, $b ) {
				$ra = count( $a['reports'] );
				$rb = count( $b['reports'] );
				
				if( $ra < $rb ) {
					return 1;
				}
				else if( $rb > $ra ) {
					return -1;
				}
				else {
					return 0;
				}
			} );
			
			// Loop through the custom index.
			foreach( $report_index as &$report_item ) {
				$content = $report_item['content'];
				
				// Load board content.
				openBoard($report_item['board_id']);
				
				// Load the reported content.
				if( !$content['thread'] ) {
					// Still need to fix this:
					$po = new Thread($content, '?/', $mod, false);
				}
				else {
					$po = new Post($content, '?/', $mod);
				}
				
				// Fetch clean status.
				$po->getClean(true);
				$clean = $po->clean;
				
				
				// Add each report's template to this container.
				$report_html = "";
				$reports_can_demote = false;
				$reports_can_promote = false;
				$content_reports = 0;
				foreach( $report_item['reports'] as $report ) {
					$uri_report_base = "reports/" . ($global ? "global/" : "" ) . $report['id'];
					$report_html .= Element('mod/report.html', array(
						'report'        => $report,
						'config'        => $config,
						'mod'           => $mod,
						'global'        => $global,
						'clean'         => $clean,
						
						'uri_dismiss'   => "?/{$uri_report_base}/dismiss",
						'uri_ip'        => "?/{$uri_report_base}/dismissall",
						'uri_demote'    => "?/{$uri_report_base}/demote",
						'uri_promote'   => "?/{$uri_report_base}/promote",
						'token_dismiss' => make_secure_link_token( $uri_report_base . '/dismiss' ),
						'token_ip'      => make_secure_link_token( $uri_report_base . '/dismissall' ),
						'token_demote'  => make_secure_link_token( $uri_report_base . '/demote' ),
						'token_promote' => make_secure_link_token( $uri_report_base . '/promote'  ),
					));
					
					// Determines if we can "Demote All" / "Promote All"
					// This logic only needs one instance of a demotable or promotable report to work.
					// DEMOTE can occur when we're global and the report has a 1 for local (meaning locally, it's not dismissed)
					// PROMOTE can occur when we're local and the report has a 0 for global (meaning it's not global).
					if( $global && $report['local'] == "1" ) {
						$reports_can_demote = true;
					}
					else if( !$global && $report['global'] != "1" ) {
						$reports_can_promote = true;
					}
					
					++$content_reports;
				}
				
				// Build the ">>>/b/ thread reported 3 times" title.
				$report_title = sprintf(
					_('<a href="%s" title="View content" target="_new">&gt;&gt;&gt;/%s/</a> %s reported %d time(s).'),
					"?/{$report_item['board_id']}/res/" . ( $content['thread'] ?: $content['id'] ) . ".html#{$content['thread']}",
					$report_item['board_id'],
					_( $content['thread'] ? "reply" : "thread" ),
					$content_reports
				);
				
				
				// Figure out some stuff we need for the page.
				$reports_can_demote  = ( $clean['clean_local'] ? false : $reports_can_demote );
				$reports_can_promote = ( $clean['clean_global'] ? false : $reports_can_promote );
				$uri_content_base    = "reports/" . ($global ? "global/" : "" ) . "content/";
				$uri_clean_base      = "reports/" . ($global ? "global/" : "" ) . "{$report_item['board_id']}/clean/{$content['id']}";
				
				// Build the actions page.
				$content_html = Element('mod/report_content.html', array(
					'reports_html'          => $report_html,
					'reports_can_demote'    => $reports_can_demote,
					'reports_can_promote'   => $reports_can_promote,
					'report_count'          => $content_reports,
					'report_title'          => $report_title,
					
					'content_html'          => $po->build(true),
					'content_board'         => $report_item['board_id'],
					'content'               => (array) $content,
					
					'clean'                 => $clean,
					
					'go_to_thread'          => "?/{$report_item['board_id']}/res/" . ( $content['thread'] ?: $content['id'] ) . ".html",
					
					'uri_content_demote'    => "?/{$uri_content_base}{$report_item['board_id']}/{$content['id']}/demote",
					'uri_content_promote'   => "?/{$uri_content_base}{$report_item['board_id']}/{$content['id']}/promote",
					'uri_content_dismiss'   => "?/{$uri_content_base}{$report_item['board_id']}/{$content['id']}/dismiss",
					'token_content_demote'  => make_secure_link_token( "{$uri_content_base}{$report_item['board_id']}/{$content['id']}/demote" ),
					'token_content_promote' => make_secure_link_token( "{$uri_content_base}{$report_item['board_id']}/{$content['id']}/promote" ),
					'token_content_dismiss' => make_secure_link_token( "{$uri_content_base}{$report_item['board_id']}/{$content['id']}/dismiss" ),
					
					'uri_clean'             => "?/{$uri_clean_base}/local",
					'uri_clean_global'      => "?/{$uri_clean_base}/global",
					'uri_clean_both'        => "?/{$uri_clean_base}/global+local",
					'token_clean'           => make_secure_link_token( $uri_clean_base . '/local' ),
					'token_clean_global'    => make_secure_link_token( $uri_clean_base . '/global' ),
					'token_clean_both'      => make_secure_link_token( $uri_clean_base . '/global+local' ),
					
					'global'                => $global,
					'config'                => $config,
					'mod'                   => $mod,
				));
				
				$reportHTML .= $content_html;
			}
		}

		if ( $reportCount > 0 && $json ) {
			array_walk($reports, function(&$v, $k, $ud) {
				$global = $ud['global'];
				$report_posts = $ud['report_posts'];

				$board = ($v['board'] ? $v['board'] : NULL);

				if (isset($v['ip']) && !$global) {
					$v['ip'] = less_ip($v['ip'], ($board?$board:''));
				}

				if (isset($report_posts[ $v['board'] ][ $v['post'] ])) {
					$post_content = $report_posts[ $v['board'] ][ $v['post'] ];
					unset($post_content['password']);
					if (!$global) {
						$post_content['ip'] = less_ip($post_content['ip'], ($board?$board:''));
					}
					$v['post_content'] = $post_content;
				}
			}, array('global' => $global, 'report_posts' => $report_posts));
		}
	}
	
	$pageArgs = array(
		'count'   => $reportCount,
		'reports' => $reportHTML,
		'global'  => $global,
	);
	
	if ($json) {
		header('Content-Type: application/json');
		echo json_encode($reports);
	} else {
		mod_page( sprintf('%s (%d)', _( ( $global ? 'Global report queue' : 'Report queue' ) ), $reportCount), 'mod/reports.html', $pageArgs );
	}
}

function mod_report_dismiss() {
	global $config, $mod;
	
	// Parse arguments.
	$arguments = func_get_args();
	$global    = in_array( "global", $arguments );
	$content   = in_array( "content", $arguments );
	
	if( ($mod['type'] < GLOBALVOLUNTEER ) and $global ) {
		error($config['error']['noaccess']);
	}
	
	if( $content ) {
		$board = @$arguments[2];
		$post  = @$arguments[3];
		
		if( !hasPermission($config['mod']['report_dismiss_content'], $board) ) {
			error($config['error']['noaccess']);
		}
		
		if( $board != "" && $post != "" ) {
			
			$query = prepare("SELECT `id` FROM `reports` WHERE `board` = :board AND `post` = :post");
			$query->bindValue(':board', $board);
			$query->bindValue(':post', $post);
			$query->execute() or error(db_error($query));
			if( count( $reports = $query->fetchAll(PDO::FETCH_ASSOC) ) > 0 ) {
				$report_ids = array();
				foreach( $reports as $report ) {
					$report_ids[ $report['id'] ] = $report['id'];
				}
				
				if( $global ) {
					$scope = "``global`` = FALSE AND ``local`` = FALSE";
				}
				else {
					$scope = "``local`` = FALSE";
				}
				
				$query = prepare("UPDATE ``reports`` SET {$scope} WHERE `id` IN (".implode(',', array_map('intval', $report_ids)).")");
				$query->execute() or error(db_error($query));
				
				// Cleanup - Remove reports that have been completely dismissed.
				$query = prepare("DELETE FROM `reports` WHERE `local` = FALSE AND `global` = FALSE");
				$query->execute() or error(db_error($query));

				modLog("Dismissed " . count($report_ids) . " local report(s) for post #{$post}", $board);
			}
			else {
				error($config['error']['404']);
			}
		}
		else {
			error($config['error']['404']);
		}
	}
	else {
		$report = @$arguments[1];
		$all    = in_array( "all", $arguments );
		
		if( $report != "" ) {
			
			$query = prepare("SELECT `post`, `board`, `ip` FROM ``reports`` WHERE `id` = :id");
			$query->bindValue(':id', $report);
			$query->execute() or error(db_error($query));
			if ($reportobj = $query->fetch(PDO::FETCH_ASSOC)) {
				$ip = $reportobj['ip'];
				$board = $reportobj['board'];
				$post = $reportobj['post'];
				
				if( !$all && !hasPermission($config['mod']['report_dismiss'], $board) ) {
					error($config['error']['noaccess']);
				}
				if( $all && !hasPermission($config['mod']['report_dismiss_ip'], $board) ) {
					error($config['error']['noaccess']);
				}
				
				// Determine scope (local and global or just local) based on /global/ being in URI.
				if( $global ) {
					$scope = "`global` = FALSE";
					$boards = "";
				}
				else {
					$scope = "`local` = FALSE";
					$boards = "AND `board` = '{$board}'";
				}
				
				// Prepare query.
				// We don't delete reports, only modify scope.
				if( $all ) {
					$query = prepare("UPDATE ``reports`` SET {$scope} WHERE `ip` = :ip {$boards}");
					$query->bindValue(':ip', $ip);
				}
				else {
					$query = prepare("UPDATE ``reports`` SET {$scope} WHERE `id` = :id {$boards}");
					$query->bindValue(':id', $report);
				}
				
				$query->execute() or error(db_error($query));
				
				
				// Cleanup - Remove reports that have been completely dismissed.
				$query = prepare("DELETE FROM `reports` WHERE `local` = FALSE AND `global` = FALSE");
				$query->execute() or error(db_error($query));
				
				if ($all) {
					modLog("Dismissed all reports by " . ip_link($ip));
				} else {
					modLog("Dismissed a report for post #{$post}", $board);
				}
			}
			else {
				error($config['error']['404']);
			}
		}
		else {
			error($config['error']['404']);
		}
	}
	
	if( $global ) {
		header('Location: ?/reports/global', true, $config['redirect_http']);
	}
	else {
		header('Location: ?/reports', true, $config['redirect_http']);
	}
}

function mod_report_demote() {
	global $config, $mod;
	
	if( $mod['type'] < GLOBALVOLUNTEER ) {
		error($config['error']['noaccess']);
	}
	
	// Parse arguments.
	$arguments = func_get_args();
	$content = in_array( "content", $arguments );
	
	if( $content ) {
		$board = @$arguments[2];
		$post  = @$arguments[3];
		
		if( !hasPermission($config['mod']['report_demote'], $board) ) {
			error($config['error']['noaccess']);
		}
		
		if( $board != "" && $post != "" ) {
			
			$query = prepare("SELECT `id` FROM `reports` WHERE `global` = TRUE AND `board` = :board AND `post` = :post");
			$query->bindValue(':board', $board);
			$query->bindValue(':post', $post);
			$query->execute() or error(db_error($query));
			if( count( $reports = $query->fetchAll(PDO::FETCH_ASSOC) ) > 0 ) {
				
				$report_ids = array();
				foreach( $reports as $report ) {
					$report_ids[ $report['id'] ] = $report['id'];
				}
				
				$query = prepare("UPDATE ``reports`` SET ``global`` = FALSE WHERE `id` IN (".implode(',', array_map('intval', $report_ids)).")");
				$query->execute() or error(db_error($query));
				
				modLog("Demoted " . count($report_ids) . " global report(s) for post #{$post}", $board);
			}
			else {
				error($config['error']['404']);
			}
		}
		else {
			error($config['error']['404']);
		}
	}
	else {
		$report = @$arguments[1];
		
		if( $report != "" ) {
			
			$query = prepare("SELECT `post`, `board`, `ip` FROM ``reports`` WHERE `id` = :id AND ``global`` = TRUE");
			$query->bindValue(':id', $report);
			$query->execute() or error(db_error($query));
			if( $reportobj = $query->fetch(PDO::FETCH_ASSOC) ) {
				$ip = $reportobj['ip'];
				$board = $reportobj['board'];
				$post = $reportobj['post'];
				
				if( !hasPermission($config['mod']['report_demote'], $board) ) {
					error($config['error']['noaccess']);
				}
				
				$query = prepare("UPDATE ``reports`` SET ``global`` = FALSE WHERE `id` = :id");
				$query->bindValue(':id', $report);
				$query->execute() or error(db_error($query));
				
				modLog("Demoted a global report for post #{$report}", $board);
			}
			else {
				error($config['error']['404']);
			}
		}
		else {
			error($config['error']['404']);
		}
	}
	
	header('Location: ?/reports/global', true, $config['redirect_http']);
}

function mod_report_promote() {
	global $config, $mod;
	
	// Parse arguments.
	$arguments = func_get_args();
	$content = in_array( "content", $arguments );
	
	if( $content ) {
		$board = @$arguments[2];
		$post  = @$arguments[3];
		
		if( !hasPermission($config['mod']['report_promote'], $board) ) {
			error($config['error']['noaccess']);
		}
		
		if( $board != "" && $post != "" ) {
			$query = prepare("SELECT `id` FROM `reports` WHERE `global` = FALSE AND `board` = :board AND `post` = :post");
			$query->bindValue(':board', $board);
			$query->bindValue(':post', $post);
			$query->execute() or error(db_error($query));
			if( count( $reports = $query->fetchAll(PDO::FETCH_ASSOC) ) > 0 ) {
				
				$report_ids = array();
				foreach( $reports as $report ) {
					$report_ids[ $report['id'] ] = $report['id'];
				}
				
				$query = prepare("UPDATE ``reports`` SET ``global`` = TRUE WHERE `id` IN (".implode(',', array_map('intval', $report_ids)).")");
				$query->execute() or error(db_error($query));
				
				modLog("Promoted " . count($report_ids) . " local report(s) for post #{$post}", $board);
			}
			else {
				error($config['error']['404']);
			}
		}
		else {
			error($config['error']['404']);
		}
	}
	else {
		$report = @$arguments[1];
		
		if( $report != "" ) {
			$query = prepare("SELECT `post`, `board`, `ip` FROM ``reports`` WHERE `id` = :id AND ``global`` = FALSE");
			$query->bindValue(':id', $report);
			$query->execute() or error(db_error($query));
			if ($reportobj = $query->fetch(PDO::FETCH_ASSOC)) {
				$ip = $reportobj['ip'];
				$board = $reportobj['board'];
				$post = $reportobj['post'];
				
				if( !hasPermission($config['mod']['report_promote'], $board) ) {
					error($config['error']['noaccess']);
				}
				
				$query = prepare("UPDATE ``reports`` SET ``global`` = TRUE WHERE `id` = :id");
				$query->bindValue(':id', $report);
				$query->execute() or error(db_error($query));
				
				modLog("Promoted a local report for post #{$report}", $board);
			}
			else {
				error($config['error']['404']);
			}
		}
		else {
			error($config['error']['404']);
		}
	}
	
	header('Location: ?/reports', true, $config['redirect_http']);
}

function mod_recent_posts($lim) {
	global $config, $mod, $pdo;

	if (!hasPermission($config['mod']['recent']))
		error($config['error']['noaccess']);

	$limit = (is_numeric($lim))? $lim : 25;
	$last_time = (isset($_GET['last']) && is_numeric($_GET['last'])) ? $_GET['last'] : 0;
	if ($limit > 100) $limit = 100;

	$mod_boards = array();
	$boards = listBoards();

	//if not all boards
	if ($mod['boards'][0]!='*') {
		foreach ($boards as $board) {
			if (in_array($board['uri'], $mod['boards']))
				$mod_boards[] = $board;
		}
	} else {
		$mod_boards = $boards;
	}

	// Manually build an SQL query
	$query = 'SELECT * FROM (';
	foreach ($mod_boards as $board) {
		$query .= sprintf('SELECT *, %s AS `board` FROM ``posts_%s`` UNION ALL ', $pdo->quote($board['uri']), $board['uri']);
	}
	// Remove the last "UNION ALL" seperator and complete the query
	$query = preg_replace('/UNION ALL $/', ') AS `all_posts` WHERE (`time` < :last_time OR NOT :last_time) ORDER BY `time` DESC LIMIT ' . $limit, $query);
	$query = prepare($query);
	$query->bindValue(':last_time', $last_time);
	$query->execute() or error(db_error($query));
	$posts = $query->fetchAll(PDO::FETCH_ASSOC);

	foreach ($posts as &$post) {
		openBoard($post['board']);
		if (!$post['thread']) {
			// Still need to fix this:
			$po = new Thread($post, '?/', $mod, false);
			$post['built'] = $po->build(true);
		} else {
			$po = new Post($post, '?/', $mod);
			$post['built'] = $po->build(true);
		}
		$last_time = $post['time'];
	}

	echo mod_page(_('Recent posts'), 'mod/recent_posts.html',  array(
			'posts' => $posts,
			'limit' => $limit,
			'last_time' => $last_time
		)
	);

}

function mod_report_clean( $global_reports, $board, $unclean, $post, $global, $local ) {
	global $config, $mod;
	
	if( !openBoard($board) ) {
		error($config['error']['noboard']);
	}
	
	$query_global = "";
	$query_global_mod = "";
	if( $global ) {
		if( !hasPermission($config['mod']['clean_global'], $board) ) {
			error($config['error']['noaccess']);
		}
		
		$query_global = "`clean_global` = :clean";
		$query_global_mod = "`clean_global_mod_id` = :mod";
	}
	
	$query_local = "";
	$query_local_mod = "";
	if( $local ) {
		if( !hasPermission($config['mod']['clean'], $board) ) {
			error($config['error']['noaccess']);
		}
		
		$query_local = "`clean_local` = :clean";
		$query_local_mod = "`clean_local_mod_id` = :mod";
	}
	
	
	// Marking this post as "Clean" (report immune?)
	if( !$unclean ) {
		// Attempt to find a `post_clean` row for this content.
		$query = prepare("SELECT * FROM `post_clean` WHERE `board_id` = :board AND `post_id` = :post");
		$query->bindValue( ':board', $board );
		$query->bindValue( ':post',  $post );
		
		$query->execute() or error(db_error($query));
		
		// If the $clean object doesn't exist we need to insert a row for this post.
		if( !($cleanRecord = $query->fetch(PDO::FETCH_ASSOC)) ) {
			$query = prepare("INSERT INTO `post_clean` (`post_id`, `board_id`) VALUES ( :post, :board )");
			$query->bindValue( ':board', $board );
			$query->bindValue( ':post',  $post );
			
			$query->execute() or error(db_error($query));
			
			if( $query->rowCount() == 0 ) {
				error("The database failed to create a record for this content in `post_clean` to record clean status.");
			}
			
			$cleanRecord = true;
		}
	}
	// Revoking clean status (open it to reports?)
	else {
		// Attempt to find a `post_clean` row for this content.
		$query = prepare("SELECT * FROM `post_clean` WHERE `board_id` = :board AND `post_id` = :post");
		$query->bindValue( ':board', $board );
		$query->bindValue( ':post',  $post );
		
		$query->execute() or error(db_error($query));
		
		if( !($cleanRecord = $query->fetch(PDO::FETCH_ASSOC)) ) {
			error($config['error']['404']);
		}
	}
	
	// Update the `post_clean` row represented by $clean.
	if( $cleanRecord ) {
		// Build our query based on the URI arguments.
		if( $global && $local ) {
			$query = prepare("UPDATE `post_clean` SET {$query_global}, {$query_global_mod}, {$query_local}, {$query_local_mod} WHERE `board_id` = :board AND `post_id` = :post");
		}
		else if( $global ) {
			$query = prepare("UPDATE `post_clean` SET {$query_global}, {$query_global_mod} WHERE `board_id` = :board AND `post_id` = :post");
		}
		else {
			$query = prepare("UPDATE `post_clean` SET {$query_local}, {$query_local_mod} WHERE `board_id` = :board AND `post_id` = :post");
		}
		
		$query->bindValue( ':clean', !$unclean );
		$query->bindValue( ':mod',   $unclean ? NULL : $mod['id'] );
		$query->bindValue( ':board', $board );
		$query->bindValue( ':post',  $post );
		
		$query->execute() or error(db_error($query));
		
		// Finally, run a query to tidy up our records.
		if( $unclean ) {
			// Query is removing clean status from content.
			// Remove any clean records that are now null.
			$cleanup = prepare("DELETE FROM `post_clean` WHERE `clean_local` = FALSE AND `clean_global` = FALSE");
			$query->execute() or error(db_error($query));
		}
		else {
			// Content is clean, auto-handle all reports.
			
			// If this is a total clean, we don't need to update records first. 
			if( !($global && $local) ) {
				$query  = prepare("UPDATE `reports` SET `" . ($local ? "local" : "global") . "` = FALSE WHERE `board` = :board AND `post` = :post");
				$query->bindValue( ':board', $board );
				$query->bindValue( ':post',  $post );
				
				$query->execute() or error(db_error($query));
				
				// If we didn't hit anything, this content doesn't have reports, so don't run the delete query.
				$require_delete = ($query->rowCount() > 0);
				
				if( $require_delete ) {
					$query = prepare("DELETE FROM `reports` WHERE `local` = FALSE and `global` = FALSE");
					
					$query->execute() or error(db_error($query));
				}
			}
			// This is a total clean, so delete content by ID rather than via cleanup.
			else {
				$query = prepare("DELETE FROM `reports` WHERE `board` = :board AND `post` = :post");
				
				$query->bindValue( ':board', $board );
				$query->bindValue( ':post',  $post );
				
				$query->execute() or error(db_error($query));
			}
		}
		
		// Log the action.
		// Having clear wording of ths log is very important because of the nature of clean status.
		$log_action = ($unclean ? "Closed" : "Re-opened" );
		$log_scope  = ($local && $global ? "local and global" : ($local ? "local" : "global" ) );
		modLog( "{$log_action} reports for post #{$post} in {$log_scope}.", $board);
		if ($config['cache']['enabled']) {
			cache::delete("post_clean_{$board}_{$post}");
		}
		
		rebuildPost( $post );
	}
	
	// Redirect
	if( $global_reports ) {
		header('Location: ?/reports/global', true, $config['redirect_http']);
	}
	else {
		header('Location: ?/reports', true, $config['redirect_http']);
	}
}


function mod_config($board_config = false) {
	global $config, $mod, $board;
	
	if ($board_config && !openBoard($board_config))
		error($config['error']['noboard']);
	
	if (!hasPermission($config['mod']['edit_config'], $board_config))
		error($config['error']['noaccess']);
	
	$config_file = $board_config ? $board['dir'] . 'config.php' : 'inc/instance-config.php';
	
	if ($config['mod']['config_editor_php']) {
		$readonly = !(is_file($config_file) ? is_writable($config_file) : is_writable(dirname($config_file)));
		
		if (!$readonly && isset($_POST['code'])) {
			$code = $_POST['code'];
			// Save previous instance_config if php_check_syntax fails
			$old_code = file_get_contents($config_file);
			file_put_contents($config_file, $code);
			$resp = shell_exec_error('php -l ' . $config_file);
			if (preg_match('/No syntax errors detected/', $resp)) {
				header('Location: ?/config' . ($board_config ? '/' . $board_config : ''), true, $config['redirect_http']);
				return;
			}
			else {
				file_put_contents($config_file, $old_code);
				error($config['error']['badsyntax'] . $resp);
			}	
		}
		
		$instance_config = @file_get_contents($config_file);
		if ($instance_config === false) {
			$instance_config = "<?php\n\n// This file does not exist yet. You are creating it.";
		}
		$instance_config = str_replace("\n", '&#010;', utf8tohtml($instance_config));
		
		mod_page(_('Config editor'), 'mod/config-editor-php.html', array(
			'php' => $instance_config,
			'readonly' => $readonly,
			'boards' => listBoards(),
			'board' => $board_config,
			'file' => $config_file,
			'token' => make_secure_link_token('config' . ($board_config ? '/' . $board_config : ''))
		));
		return;
	}
	
	require_once 'inc/mod/config-editor.php';
	
	$conf = config_vars();
	
	foreach ($conf as &$var) {
		if (is_array($var['name'])) {
			$c = &$config;
			foreach ($var['name'] as $n)
				$c = &$c[$n];
		} else {
			$c = @$config[$var['name']];
		}
		
		$var['value'] = $c;
	}
	unset($var);
	
	if (isset($_POST['save'])) {
		$config_append = '';
		
		foreach ($conf as $var) {
			$field_name = 'cf_' . (is_array($var['name']) ? implode('/', $var['name']) : $var['name']);
			
			if ($var['type'] == 'boolean')
				$value = isset($_POST[$field_name]);
			elseif (isset($_POST[$field_name]))
				$value = $_POST[$field_name];
			else
				continue; // ???
			
			if (!settype($value, $var['type']))
				continue; // invalid
			
			if ($value != $var['value']) {
				// This value has been changed.
				
				$config_append .= '$config';
				
				if (is_array($var['name'])) {
					foreach ($var['name'] as $name)
						$config_append .= '[' . var_export($name, true) . ']';
				} else {
					$config_append .= '[' . var_export($var['name'], true) . ']';
				}
				
				
				$config_append .= ' = ';
				if (@$var['permissions'] && isset($config['mod']['groups'][$value])) {
					$config_append .= $config['mod']['groups'][$value];
				} else {
					$config_append .= var_export($value, true);
				}
				$config_append .= ";\n";
			}
		}
		
		if (!empty($config_append)) {
			$config_append = "\n// Changes made via web editor by \"" . $mod['username'] . "\" @ " . date('r') . ":\n" . $config_append . "\n";
			if (!is_file($config_file))
				$config_append = "<?php\n\n$config_append";
			if (!@file_put_contents($config_file, $config_append, FILE_APPEND)) {
				$config_append = htmlentities($config_append);
				
				if ($config['minify_html']) {
					$config_append = str_replace("\n", '&#010;', $config_append);
				}
				$page = array();
				$page['title'] = 'Cannot write to file!';
				$page['config'] = $config;
				$page['body'] = '
					<p style="text-align:center">Tinyboard could not write to <strong>' . $config_file . '</strong> with the ammended configuration, probably due to a permissions error.</p>
					<p style="text-align:center">You may proceed with these changes manually by copying and pasting the following code to the end of <strong>' . $config_file . '</strong>:</p>
					<textarea style="width:700px;height:370px;margin:auto;display:block;background:white;color:black" readonly>' . $config_append . '</textarea>
				';
				echo Element('page.html', $page);
				exit;
			}
		}
		
		header('Location: ?/config' . ($board_config ? '/' . $board_config : ''), true, $config['redirect_http']);
		
		exit;
	}

	mod_page(_('Config editor') . ($board_config ? ': ' . sprintf($config['board_abbreviation'], $board_config) : ''),
		'mod/config-editor.html', array(
			'boards' => listBoards(),
			'board' => $board_config,
			'conf' => $conf,
			'file' => $config_file,
			'token' => make_secure_link_token('config' . ($board_config ? '/' . $board_config : ''))
	));
}

function mod_themes_list() {
	global $config;

	if (!hasPermission($config['mod']['themes']))
		error($config['error']['noaccess']);

	if (!is_dir($config['dir']['themes']))
		error(_('Themes directory doesn\'t exist!'));
	if (!$dir = opendir($config['dir']['themes']))
		error(_('Cannot open themes directory; check permissions.'));

	$query = query('SELECT `theme` FROM ``theme_settings`` WHERE `name` IS NULL AND `value` IS NULL') or error(db_error());
	$themes_in_use = $query->fetchAll(PDO::FETCH_COLUMN);

	// Scan directory for themes
	$themes = array();
	while ($file = readdir($dir)) {
		if ($file[0] != '.' && is_dir($config['dir']['themes'] . '/' . $file)) {
			$themes[$file] = loadThemeConfig($file);
		}
	}
	closedir($dir);
	
	foreach ($themes as $theme_name => &$theme) {
		$theme['rebuild_token'] = make_secure_link_token('themes/' . $theme_name . '/rebuild');
		$theme['uninstall_token'] = make_secure_link_token('themes/' . $theme_name . '/uninstall');
	}

	mod_page(_('Manage themes'), 'mod/themes.html', array(
		'themes' => $themes,
		'themes_in_use' => $themes_in_use,
	));
}

function mod_theme_configure($theme_name) {
	global $config;

	if (!hasPermission($config['mod']['themes']))
		error($config['error']['noaccess']);

	if (!$theme = loadThemeConfig($theme_name)) {
		error($config['error']['invalidtheme']);
	}

	if (isset($_POST['install'])) {
		// Check if everything is submitted
		foreach ($theme['config'] as &$conf) {
			if (!isset($_POST[$conf['name']]) && $conf['type'] != 'checkbox')
				error(sprintf($config['error']['required'], $c['title']));
		}
		
		// Clear previous settings
		$query = prepare("DELETE FROM ``theme_settings`` WHERE `theme` = :theme");
		$query->bindValue(':theme', $theme_name);
		$query->execute() or error(db_error($query));
		
		foreach ($theme['config'] as &$conf) {
			$query = prepare("INSERT INTO ``theme_settings`` VALUES(:theme, :name, :value)");
			$query->bindValue(':theme', $theme_name);
			$query->bindValue(':name', $conf['name']);
			if ($conf['type'] == 'checkbox')
				$query->bindValue(':value', isset($_POST[$conf['name']]) ? 1 : 0);
			else
				$query->bindValue(':value', $_POST[$conf['name']]);
			$query->execute() or error(db_error($query));
		}

		$query = prepare("INSERT INTO ``theme_settings`` VALUES(:theme, NULL, NULL)");
		$query->bindValue(':theme', $theme_name);
		$query->execute() or error(db_error($query));

		// Clean cache
		Cache::delete("themes");
		Cache::delete("theme_settings_".$theme_name);
		
		$result = true;
		$message = false;
		if (isset($theme['install_callback'])) {
			$ret = $theme['install_callback'](themeSettings($theme_name));
			if ($ret && !empty($ret)) {
				if (is_array($ret) && count($ret) == 2) {
					$result = $ret[0];
					$message = $ret[1];
				}
			}
		}
		
		if (!$result) {
			// Install failed
			$query = prepare("DELETE FROM ``theme_settings`` WHERE `theme` = :theme");
			$query->bindValue(':theme', $theme_name);
			$query->execute() or error(db_error($query));
		}
		
		// Build themes
		rebuildThemes('all');
		
		mod_page(sprintf(_($result ? 'Installed theme: %s' : 'Installation failed: %s'), $theme['name']), 'mod/theme_installed.html', array(
			'theme_name' => $theme_name,
			'theme' => $theme,
			'result' => $result,
			'message' => $message
		));
		return;
	}

	$settings = themeSettings($theme_name);

	mod_page(sprintf(_('Configuring theme: %s'), $theme['name']), 'mod/theme_config.html', array(
		'theme_name' => $theme_name,
		'theme' => $theme,
		'settings' => $settings,
		'token' => make_secure_link_token('themes/' . $theme_name)
	));
}

function mod_theme_uninstall($theme_name) {
	global $config;

	if (!hasPermission($config['mod']['themes']))
		error($config['error']['noaccess']);

	$query = prepare("DELETE FROM ``theme_settings`` WHERE `theme` = :theme");
	$query->bindValue(':theme', $theme_name);
	$query->execute() or error(db_error($query));

	// Clean cache
	Cache::delete("themes");
	Cache::delete("theme_settings_".$theme);

	header('Location: ?/themes', true, $config['redirect_http']);
}

function mod_theme_rebuild($theme_name) {
	global $config;

	if (!hasPermission($config['mod']['themes']))
		error($config['error']['noaccess']);
	
	rebuildTheme($theme_name, 'all');

	mod_page(sprintf(_('Rebuilt theme: %s'), $theme_name), 'mod/theme_rebuilt.html', array(
		'theme_name' => $theme_name,
	));
}






// BOARD SETTINGS
function mod_settings($b) {
	
	global $config, $mod;

	//if ($b === 'infinity' && $mod['type'] !== ADMIN)
	//	error('Settings temporarily disabled for this board.');

	if (!in_array($b, $mod['boards']) and $mod['boards'][0] != '*')
		error($config['error']['noaccess']);

	if (!hasPermission($config['mod']['edit_settings'], $b))
		error($config['error']['noaccess']);

	if (!openBoard($b))
		error("Could not open board!");


	if ($_SERVER['REQUEST_METHOD'] == 'POST') {

		
		//$board_type = $_POST['board_type'];
		$board_type ='imgboard';
		$imgboard = $board_type == 'imgboard';
		$txtboard = $board_type == 'txtboard';
		$fileboard = $board_type == 'fileboard';


		$country_flags = isset($_POST['country_flags']) ? 'true' : 'false';
		$field_disable_name = isset($_POST['field_disable_name']) ? 'true' : 'false';
		$force_anon_thread = isset($_POST['force_anon_thread']) ? 'true' : 'false';
		$force_image_op = $imgboard && isset($_POST['force_image_op']) ? 'true' : 'false';
		$disable_images = $txtboard ? 'true' : 'false';
		$poster_ids = isset($_POST['poster_ids']) ? 'true' : 'false';
		$show_sages = isset($_POST['show_sages']) ? 'true' : 'false';
		$auto_unicode = isset($_POST['auto_unicode']) ? 'true' : 'false';
		$strip_combining_chars = isset($_POST['strip_combining_chars']) ? 'true' : 'false';
		$allow_roll = isset($_POST['allow_roll']) ? 'true' : 'false';
		$image_reject_repost = isset($_POST['image_reject_repost']) ? 'true' : 'false';
		$image_reject_repost_in_thread = isset($_POST['image_reject_repost_in_thread']) ? 'true' : 'false';
		$early_404 = isset($_POST['early_404']) ? 'true' : 'false';
		$allow_delete = isset($_POST['allow_delete']) ? 'true' : 'false';
		$code_tags = isset($_POST['code_tags']) ? '$config[\'additional_javascript\'][] = \'js/code_tags/run_prettify.js\';$config[\'markup\'][] = array("/\[code\](.+?)\[\/code\]/ms", "<code><pre class=\'prettyprint\' style=\'display:inline-block\'>\$1</pre></code>");' : '';
		$katex = isset($_POST['katex']) ? '$config[\'katex\'] = true;$config[\'additional_javascript\'][] = \'js/katex/katex.min.js\'; $config[\'markup\'][] = array("/\[tex\](.+?)\[\/tex\]/ms", "<span class=\'tex\'>\$1</span>"); $config[\'additional_javascript\'][] = \'js/katex-enable.js\';' : '';
		$user_flags = isset($_POST['user_flags']) ? "if (file_exists('$b/flags.php')) { include 'flags.php'; }\n" : '';
		$captcha_for_post = isset($_POST['captcha_for_post']) ? 'true' : 'false';
		$captcha_for_thread = isset($_POST['captcha_for_thread']) ? 'true' : 'false';
		
		$force_subject_op = isset($_POST['force_subject_op']) ? 'true' : 'false';
		$force_flag = isset($_POST['force_flag']) ? 'true' : 'false';
		$tor_posting = isset($_POST['tor_posting']) ? 'true' : 'false';
		$tor_image_posting = isset($_POST['tor_image_posting']) ? 'true' : 'false';
		$oekaki = ($imgboard || $fileboard) && isset($_POST['oekaki']) ? 'true' : 'false';
		$view_bumplock = isset($_POST['view_bumplock']) ? '-1' : 'MOD';
		$enable_emoji = isset($_POST['enable_emoji']) ? 'true' : 'false';

		if (($tor_image_posting === 'true') && isset($_POST['meta_noindex'])) {
			error('Please index your board to enable this.');
		}
		

		if (isset($_POST['max_images']) && (int)$_POST['max_images'] && (int)$_POST['max_images'] <= 5) {
			$_POST['max_images'] = (int)$_POST['max_images'];
			$multiimage = "\$config['max_images'] = {$_POST['max_images']};
					 \$config['additional_javascript'][] = 'js/multi-image.js';";
		} else {
			$multiimage = '';
		} 

											if (isset($_POST['enable_emoji'])) {
															$emoji_config = "
																			\$config['emoji_enable'] = true;
																			\$config['additional_javascript']['emoji'] = 'js/twemoji/twemoji.js';";
											} else {
															$emoji_config = "";
											}			
		
		if (isset($_POST['custom_assets'])) {
			$assets = "\$config['custom_assets'] = true;
								 \$config['spoiler_image'] = 'static/assets/$b/spoiler.png';
								 \$config['image_deleted'] = 'static/assets/$b/deleted.png';
								 \$config['no_file_image'] = 'static/assets/$b/no-file.png';
			";
		} else {
			$assets = '';
		}

		$file_board = '';
		if ($fileboard) {
			$force_image_op = true;

			$file_board = "\$config['threads_per_page'] = 30;
							 \$config['file_board'] = true;
							 \$config['threads_preview'] = 0;
										 \$config['threads_preview_sticky'] = 0;
							 \$config['allowed_ext_files'] = array();\n";


			if (isset ($_POST['allowed_type'])) {
				foreach ($_POST['allowed_type'] as $val) {
					if (in_array ($val, $config['fileboard_allowed_types'])) {
						$file_board .= "\$config['allowed_ext_files'][] = '$val';\n";
					}
				}
			}

			if (isset ($_POST['allowed_ext_op'])) {
				$file_board .= "\$config['allowed_ext_op'] = \$config['allowed_ext_files'];\n";

				if (isset ($_POST['allowed_ext_op_video'])) {
					$file_board .= "\$config['allowed_ext_op'][] = 'webm';
							\$config['allowed_ext_op'][] = 'mp4';\n";
				}
			}

			if (isset ($_POST['tag_id'])) {
				$file_board .= "\$config['allowed_tags'] = array();\n";
				foreach ($_POST['tag_id'] as $id => $v) {
					$file_board .= "\$config['allowed_tags'][";
						$file_board .= 'base64_decode("';
							$file_board .= base64_encode($_POST['tag_id'][$id]);
						$file_board .= '")';
					$file_board .= "] = ";
						$file_board .= 'base64_decode("';
							$file_board .= base64_encode($_POST['tag_desc'][$id]);
						$file_board .= '")';
					$file_board .= ";\n";
				}
			}
		}

		$anal_filenames = ($fileboard) && isset($_POST['anal_filenames']) ? "\$config['filename_func'] = 'filename_func';\n" : '';

		$anonymous = base64_encode($_POST['anonymous']);
		$add_to_config = @file_get_contents($b.'/extra_config.php');
		$replace = '';

		if (isset($_POST['replace'])) {
			if (sizeof($_POST['replace']) > 200 || sizeof($_POST['with']) > 200) {
				error(_('Sorry, max 200 wordfilters allowed.'));
			}
			if (count($_POST['replace']) == count($_POST['with'])) {
				foreach ($_POST['replace'] as $i => $r ) {
					if ($r !== '') {
						$w = $_POST['with'][$i];
						
						if (strlen($w) > 255) {
							error(sprintf(_('Sorry, %s is too long. Max replacement is 255 characters'), utf8tohtml($w)));
						}

						$replace .= '$config[\'wordfilters\'][] = array(base64_decode(\'' . base64_encode($r) . '\'), base64_decode(\'' . base64_encode($w) . '\'));';
					}
				}
			}
			if (is_billion_laughs($_POST['replace'], $_POST['with'])) {
				error(_('Wordfilters may not wordfilter previous wordfilters. For example, if a filters to bb and b filters to cc, that is not allowed.'));
			}
		}

		if (isset($_POST['hour_max_threads']) && (int)$_POST['hour_max_threads'] > 0 && (int)$_POST['hour_max_threads'] < 101 ) {
			$hour_max_threads = (int)$_POST['hour_max_threads'];	
		} else {
			$hour_max_threads = 'false';
		}

		if (isset($_POST['max_pages'])) {
			$mp = (int)$_POST['max_pages'];
			if ($mp > 25 || $mp < 1) {
				$max_pages = 15;
			} else {
				$max_pages = $mp;
			}
		} else {
			$max_pages = 15;
		}			

		if (isset($_POST['reply_limit'])) {
			$rl = (int)$_POST['reply_limit'];
			if ($rl > 750 || $rl < 250 || $rl % 25) {
				$reply_limit = 250;
			} else {
				$reply_limit = $rl;
			}
		} else {
			$reply_limit = 250;
		}

		if (isset($_POST['max_newlines'])) {
			$mn = (int)$_POST['max_newlines'];
			if ($mn < 20 || $mn > 300) {
				$max_newlines = 0;
			} else {
				$max_newlines = $mn;
			}
		} else {
			$max_newlines = 0;
		}

		if (isset($_POST['min_body'])) {
			$mb = (int)$_POST['min_body'];
			if ($mb < 0 || $mb > 1024) {
				$min_body = 0;
			} else {
				$min_body = $mb;
			}
		} else {
			$min_body = 0;
		}

		if (isset($_POST['title']) && strlen($_POST['title']) < 40)
			error('Invalid title');
		if (isset($_POST['subtitle']) && strlen($_POST['subtitle']) < 200)
			error('Invalid subtitle');

		$query = prepare('UPDATE ``boards`` SET ' 
			.(isset($_POST['title']) ? ' `title` = :title,':'') 
			.(isset($_POST['subtitle']) ? '`subtitle` = :subtitle, ' : '' )
			.'`indexed` = :indexed, `public_bans` = :public_bans, `public_logs` = :public_logs WHERE `uri` = :uri');


		if(isset($_POST['title']))
			$query->bindValue(':title', $title);
		if(isset($_POST['subtitle']))
			$query->bindValue(':subtitle', $subtitle);
		$query->bindValue(':uri', $b);
		$query->bindValue(':indexed', !isset($_POST['meta_noindex']), PDO::PARAM_BOOL);
		$query->bindValue(':public_bans', isset($_POST['public_bans']));
		$query->bindValue(':public_logs', (int)$_POST['public_logs']);


		//$query->execute() or error(db_error($query));
		if(!$query->execute()){
			print_r($query->errorInfo());
			exit;
		}

		$config_file = <<<EOT
<?php
\$config['country_flags'] = $country_flags;
\$config['field_disable_name'] = $field_disable_name;
\$config['force_anon_thread'] = $force_anon_thread;
\$config['force_image_op'] = $force_image_op;
\$config['disable_images'] = $disable_images;
\$config['poster_ids'] = $poster_ids;
\$config['show_sages'] = $show_sages;
\$config['auto_unicode'] = $auto_unicode;
\$config['strip_combining_chars'] = $strip_combining_chars;
\$config['allow_roll'] = $allow_roll;
\$config['image_reject_repost'] = $image_reject_repost;
\$config['image_reject_repost_in_thread'] = $image_reject_repost_in_thread;
\$config['early_404'] = $early_404;
\$config['allow_delete'] = $allow_delete;
\$config['anonymous'] = base64_decode('$anonymous');
\$config['captcha']['enabled_for_post'] = $captcha_for_post;
\$config['captcha']['enabled_for_thread'] = $captcha_for_thread;
\$config['force_subject_op'] = $force_subject_op;
\$config['force_flag'] = $force_flag;
\$config['tor_posting'] = $tor_posting;
\$config['tor_image_posting'] = $tor_image_posting;
\$config['hour_max_threads'] = $hour_max_threads;
\$config['reply_limit'] = $reply_limit;
\$config['max_pages'] = $max_pages;
\$config['max_newlines'] = $max_newlines;
\$config['oekaki'] = $oekaki;
\$config['min_body'] = $min_body;
\$config['mod']['view_bumplock'] = $view_bumplock;
\$config['enable_emoji'] = $enable_emoji;
$code_tags $katex $replace $multiimage $emoji_config $user_flags 
$assets
$anal_filenames
$file_board

if (\$config['disable_images'])
\$config['max_pages'] = 10000;

$add_to_config
EOT;


$blotter = "";

if(isset($_POST['blotter']) && !empty($_POST['blotter'])){
	//$blotter =	base64_encode(purify_html(html_entity_decode($_POST['blotter'])));
	//$config_file .= "\n\$config['blotter'] = base64_decode('$blotter');";
}

 
/*
\$config['stylesheets']['Custom'] = 'board/$b.css';
\$config['default_stylesheet'] = array('Custom', \$config['stylesheets']['Custom']);
*/


		// DISABLE CUSTOM CSS
		// Clean up our CSS...no more expression() or off-site URLs.
		/*$clean_css = !isset($_POST['css']) ? '' : preg_replace('/expression\s*\(/', '', $_POST['css']);

		$matched = array();

		preg_match_all("#{$config['link_regex']}#im", $clean_css, $matched);
		
		if (isset($matched[0])) {
			foreach ($matched[0] as $match) {
				$match_okay = false;
				foreach ($config['allowed_offsite_urls'] as $allowed_url) {
					if (strpos($match, $allowed_url) === 0) {
						$match_okay = true;
					}
				}
				if ($match_okay !== true) {
					error(sprintf(_("Off-site link \"%s\" is not allowed in the board stylesheet"), $match));
				}
			}
		}
		
		//Filter out imports from sites with potentially unsafe content
		$match_imports = '@import[^;]*';
		$matched = array();
		preg_match_all("#$match_imports#im", $clean_css, $matched);
		
		$unsafe_import_urls = array('https://a.pomf.se/');
		
		if (isset($matched[0])) {
			foreach ($matched[0] as $match) {
				$match_okay = true;
				foreach ($unsafe_import_urls as $unsafe_import_url) {
					if (strpos($match, $unsafe_import_url) !== false && strpos($match, '#') === false) {
						$match_okay = false;
					}
				}
				if ($match_okay !== true) {
					error(sprintf(_("Potentially unsafe import \"%s\" is not allowed in the board stylesheet"), $match));
				}
			}
		}

		 
		file_put_contents('stylesheets/board/'.$b.'.css', $clean_css);*/
 
 
		$_config = $config;


		unset($config['wordfilters']);
  
		$php_code = str_replace('flags.php', "$b/flags.php", $config_file);
		$eval_code = preg_replace('/^\<\?php$/m', '', $php_code);


		file_put_contents($b . '/config.php', $php_code);
		eval($eval_code);



		// be smarter about rebuilds...only some changes really require us to rebuild all threads
		if ($_config['captcha']['enabled_for_post'] != $config['captcha']['enabled_for_post']
		 || $_config['captcha']['enabled_for_thread'] != $config['captcha']['enabled_for_thread']
		 || $_config['captcha']['extra'] != $config['captcha']['extra']
		 || $_config['blotter'] != $config['blotter']
		 || $_config['field_disable_name'] != $config['field_disable_name']
		 || $_config['force_anon_thread'] != $config['force_anon_thread']
		 || $_config['show_sages'] != (isset($config['show_sages']) && $config['show_sages'])) {
			buildIndex();
			$query = query(sprintf("SELECT `id` FROM ``posts_%s`` WHERE `thread` IS NULL", $b)) or error(db_error());
			while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
				buildThread($post['id']);
			}
		}
	
		modLog('Edited board settings', $b);
	}

	$query = prepare('SELECT * FROM boards WHERE uri = :board');
	$query->bindValue(':board', $b);
	$query->execute() or error(db_error($query));
	$board = $query->fetchAll()[0];

	// Clean the cache
	if ($config['cache']['enabled']) {
		cache::delete('board_' . $board['uri']);
		cache::delete('all_boards');
		cache::delete('all_boards_indexed');

		cache::delete('config_' . $board['uri']);
		cache::delete('events_' . $board['uri']);
		unlink('tmp/cache/locale_' . $board['uri']);
	}

	$css = @file_get_contents('stylesheets/board/' . $board['uri'] . '.css');

	mod_page(_('Board configuration'), 'mod/settings/settings.html', array('board'=>$board, 'css'=>prettify_textarea($css),'allowed_urls'=>$config['allowed_offsite_urls']));
}


function mod_tags ($b) {
	global $board, $config;

	if (!openBoard($b))
		error("Could not open board!");

	if (!hasPermission($config['mod']['edit_tags'], $b))
		error($config['error']['noaccess']);

	if (isset($_POST['tags'])) {
		if (sizeof($_POST['tags']) > 5)
			error(_('Too many tags.'));

		$delete = prepare('DELETE FROM ``board_tags`` WHERE uri = :uri');
		$delete->bindValue(':uri', $b);
		$delete->execute();

		foreach ($_POST['tags'] as $i => $tag) {
			if ($tag) {
				if (strlen($tag) > 255)
					continue;

				$insert = prepare('INSERT INTO ``board_tags``(uri, tag) VALUES (:uri, :tag)');
				$insert->bindValue(':uri', $b);
				$insert->bindValue(':tag', utf8tohtml($tag));
				$insert->execute();
			}
		}

		$update = prepare('UPDATE ``boards`` SET sfw = :sfw WHERE uri = :uri');
		$update->bindValue(':uri', $b);
		$update->bindValue(':sfw', isset($_POST['sfw']));
		$update->execute();
	}
	$query = prepare('SELECT * FROM ``board_tags`` WHERE uri = :uri');
	$query->bindValue(':uri', $b);
	$query->execute();

	$tags = $query->fetchAll();

	$query = prepare('SELECT `sfw` FROM ``boards`` WHERE uri = :uri');
	$query->bindValue(':uri', $b);
	$query->execute();

	$sfw = $query->fetchColumn();

	mod_page(_('Edit tags'), 'mod/settings/tags.html', array('board'=>$board,'token'=>make_secure_link_token('tags/'.$board['uri']), 'tags'=>$tags, 'sfw'=>$sfw));
}


function mod_assets($b) {
	global $config, $mod, $board;
	require_once 'inc/image.php';

	if (!hasPermission($config['mod']['edit_assets'], $b))
		error($config['error']['noaccess']);

	if (!openBoard($b))
		error("Could not open board!");

	$dir = 'static/assets/'.$b;

	if (!is_dir($dir)){
		mkdir($dir, 0777, true);

		symlink(getcwd() . '/' . $config['image_deleted'], "$dir/deleted.png");
		symlink(getcwd() . '/' . $config['spoiler_image'], "$dir/spoiler.png");
		symlink(getcwd() . '/' . $config['no_file_image'], "$dir/no-file.png");
	}
	
	// "File deleted"
	if (isset($_FILES['deleted_file']) && !empty($_FILES['deleted_file']['tmp_name'])){
		$upload = $_FILES['deleted_file']['tmp_name'];
		$extension = strtolower(mb_substr($_FILES['deleted_file']['name'], mb_strrpos($_FILES['deleted_file']['name'], '.') + 1));

		if (!is_readable($upload)) {
			error($config['error']['nomove']);
		}

		if (filesize($upload) > 512000){
			error('File too large!');
		}

		if (!in_array($extension, array('png', 'gif'))) {
			error('File must be PNG or GIF format.');
		}

		if (!$size = @getimagesize($upload)) {
			error($config['error']['invalidimg']);
		}

		if ($size[0] != 140 or $size[1] != 50){
			error('Image wrong size!');
		}

		unlink("$dir/deleted.png");
		copy($upload, "$dir/deleted.png");
		purge("$dir/deleted.png", true);
	}

	// Spoiler file
	if (isset($_FILES['spoiler_file']) && !empty($_FILES['spoiler_file']['tmp_name'])){
		$upload = $_FILES['spoiler_file']['tmp_name'];
		$extension = strtolower(mb_substr($_FILES['spoiler_file']['name'], mb_strrpos($_FILES['spoiler_file']['name'], '.') + 1));

		if (!is_readable($upload)) {
			error($config['error']['nomove']);
		}

		if (filesize($upload) > 512000){
			error('File too large!');
		}


		if (!in_array($extension, array('png', 'gif'))) {
			error('File must be PNG or GIF format.');
		}

		if (!$size = @getimagesize($upload)) {
			error($config['error']['invalidimg']);
		}

		if ($size[0] != 128 or $size[1] != 128){
			error('Image wrong size!');
		}

		unlink("$dir/spoiler.png");
		copy($upload, "$dir/spoiler.png");
		purge("$dir/spoiler.png", true);
	}

	// No file
	if (isset($_FILES['nofile_file']) && !empty($_FILES['nofile_file']['tmp_name'])){
		$upload = $_FILES['nofile_file']['tmp_name'];
		$extension = strtolower(mb_substr($_FILES['nofile_file']['name'], mb_strrpos($_FILES['nofile_file']['name'], '.') + 1));

		if (!is_readable($upload)) {
			error($config['error']['nomove']);
		}

		if (filesize($upload) > 512000){
			error('File too large!');
		}

		if (!in_array($extension, array('png', 'gif'))) {
			error('File must be PNG or GIF format.');
		}

		if (!$size = @getimagesize($upload)) {
			error($config['error']['invalidimg']);
		}

		if ($size[0] != 500 or $size[1] != 500){
			error('Image wrong size!');
		}

		unlink("$dir/no-file.png");
		copy($upload, "$dir/no-file.png");
		purge("$dir/no-file.png", true);
	}

	mod_page(_('Edit board assets'), 'mod/settings/assets.html', array('board'=>$board,'token'=>make_secure_link_token('assets/'.$board['uri'])));
}

function mod_reassign($b) {
	global $board, $config;

	if (!openBoard($b))
		error("Could not open board!");

	if (!hasPermission($config['mod']['reassign_board'], $b))
		error($config['error']['noaccess']);

	$query = query("SELECT id, username FROM mods WHERE boards = '$b' AND type = 20");
	$mods = $query->fetchAll();

	if (!$mods) {
		error('No mods?');
	}

	$password = base64_encode(openssl_random_pseudo_bytes(9));
	$salt = generate_salt();
	$hashed = hash('sha256', $salt . sha1($password));

	$query = prepare('UPDATE ``mods`` SET `password` = :hashed, `salt` = :salt, `email` = NULL WHERE BINARY username = :mod');
	$query->bindValue(':hashed', $hashed);
	$query->bindValue(':salt', $salt);
	$query->bindValue(':mod', $mods[0]['username']);
	$query->execute();

	$body = "Thank you for your interest in this board. According to https://8ch.net/claim.html [INSERT ARCHIVE HERE], the board is claimable and hereby reassigned to you because the Board Owner failed to sign in for more than two weeks. Kindly find the username and password below. You can login at https://8ch.net/mod.php.<br>Username: {$mods[0]['username']}<br>Password: {$password}<br>Thank you for using 8chan, anon!";
	
	modLog("Reassigned board /$b/");
	
	mod_page(_('Edit reassign'), 'blank.html', array('board'=>$board,'token'=>make_secure_link_token('reassign/'.$board['uri']),'body'=>$body));
}



function mod_banners($b) {
	global $config, $mod, $board;

	error('Banner editing is currently disabled. Please check back later!');

	require_once 'inc/image.php';

	if (!hasPermission($config['mod']['edit_banners'], $b))
		error($config['error']['noaccess']);

	if (!openBoard($b))
		error("Could not open board!");

	$dir = 'static/banners/'.$b;

	if (!is_dir($dir)){
		mkdir($dir, 0777, true);
	}


	if (isset($_FILES['file'])){
		$upload = $_FILES['file']['tmp_name'];
		$banners = array_diff(scandir($dir), array('..', '.'));

		if (!is_readable($upload))
			error($config['error']['nomove']);

		$id = time() . substr(microtime(), 2, 3);
		$extension = strtolower(mb_substr($_FILES['file']['name'], mb_strrpos($_FILES['file']['name'], '.') + 1));

		if (!in_array($extension, array('jpg','jpeg','png','gif'))){
			error('Not an image extension.');
		}

		if (filesize($upload) > 512000){
			error('File too large!');
		}

		if (!$size = @getimagesize($upload)) {
			error($config['error']['invalidimg']);
		}

		if ($size[0] != 300 or $size[1] != 100){
			error('Image wrong size!');
		}
		if (sizeof($banners) >= 50) {
			error('Too many banners.');
		}

		copy($upload, "$dir/$id.$extension");
	}

	if (isset($_POST['delete'])){
		foreach ($_POST['delete'] as $i => $d){
			if (!preg_match('/^[0-9]+\.(png|jpeg|jpg|gif)$/', $d)){
				error('Nice try.');
			}
			unlink("$dir/$d");
		}
	}

	$banners = array_diff(scandir($dir), array('..', '.'));
	mod_page(_('Edit banners'), 'mod/settings/banners.html', array('board'=>$board,'banners'=>$banners,'token'=>make_secure_link_token('banners/'.$board['uri'])));

}



function mod_volunteers($b) {
	global $board, $config, $pdo;
	if (!hasPermission($config['mod']['edit_volunteers'], $b))
		error($config['error']['noaccess']);

	if (!openBoard($b))
		error("Could not open board!");

	if (isset($_POST['username'], $_POST['password'])) {
		$query = prepare('SELECT * FROM ``mods`` WHERE type = 19 AND boards = :board');
		$query->bindValue(':board', $b);
		$query->execute() or error(db_error($query));
		$count = $query->rowCount();
		$query = prepare('SELECT `username` FROM ``mods``');
		$query->execute() or error(db_error($query));
		$volunteers = $query->fetchAll(PDO::FETCH_ASSOC);

		if ($_POST['username'] == '')
			error(sprintf($config['error']['required'], 'username'));
		if ($_POST['password'] == '')
			error(sprintf($config['error']['required'], 'password'));
		if (!preg_match('/^[a-zA-Z0-9._]{1,30}$/', $_POST['username']))
			error(_('Invalid username'));

		if ($count > 20) {
			error(_('Too many board volunteers!'));
		}

		foreach ($volunteers as $i => $v) {
			if (strtolower($_POST['username']) == strtolower($v['username'])) {
				error(_('Refusing to create a volunteer with the same username as an existing one.'));
			}
		}

		$salt = generate_salt();
		$password = hash('sha256', $salt . sha1($_POST['password']));
		
		$query = prepare('INSERT INTO ``mods`` VALUES (NULL, :username, :password, :salt, 19, :board, "")');
		$query->bindValue(':username', $_POST['username']);
		$query->bindValue(':password', $password);
		$query->bindValue(':salt', $salt);
		$query->bindValue(':board', $b);
		$query->execute() or error(db_error($query));
		
		$userID = $pdo->lastInsertId();


		modLog('Created a new volunteer: ' . utf8tohtml($_POST['username']) . ' <small>(#' . $userID . ')</small>');
	}

	if (isset($_POST['delete'])){
		foreach ($_POST['delete'] as $i => $d){
			$query = prepare('SELECT * FROM ``mods`` WHERE id = :id');
			$query->bindValue(':id', $d);
			$query->execute() or error(db_error($query));
			
			$result = $query->fetch(PDO::FETCH_ASSOC);

			if (!$result) {
				error(_('Volunteer does not exist!'));
			}

			if ($result['boards'] != $b || $result['type'] != BOARDVOLUNTEER) {
				error($config['error']['noaccess']);
			}

			$query = prepare('DELETE FROM ``mods`` WHERE id = :id');
			$query->bindValue(':id', $d);
			$query->execute() or error(db_error($query));
		}
	}

	$query = prepare('SELECT * FROM ``mods`` WHERE type = 19 AND boards = :board');
	$query->bindValue(':board', $b);
	$query->execute() or error(db_error($query));
	$volunteers = $query->fetchAll(PDO::FETCH_ASSOC);
		
	mod_page(_('Edit volunteers'), 'mod/settings/volunteers.html', array('board'=>$board,'token'=>make_secure_link_token('volunteers/'.$board['uri']),'volunteers'=>$volunteers));

}




function mod_flags($b) {
	global $config, $mod, $board;
	require_once 'inc/image.php';
	if (!hasPermission($config['mod']['edit_flags'], $b))
		error($config['error']['noaccess']);

	if (!openBoard($b))
		error("Could not open board!");

	if (file_exists("$b/flags.ser"))
		$config['user_flags'] = unserialize(file_get_contents("$b/flags.ser"));

	$dir = 'static/custom-flags/'.$b;
	
	if (!is_dir($dir)){
		mkdir($dir, 0777, true);
	}

	function handle_file($id = false, $description, $b, $dir) {
		global $config;

		if (!isset($description) and $description)
			error(_('You must enter a flag description!'));

		if (strlen($description) > 255)
			error(_('Flag description too long!'));

		if ($id) {
			$f = 'flag-'.$id;
		} else { 
			$f = 'file';
			$id = time() . substr(microtime(), 2, 3);
		}

		$upload = $_FILES[$f]['tmp_name'];
		$banners = array_diff(scandir($dir), array('..', '.'));

		if (!is_readable($upload))
			error($config['error']['nomove']);

		$extension = strtolower(mb_substr($_FILES[$f]['name'], mb_strrpos($_FILES[$f]['name'], '.') + 1));

		if ($extension != 'png') {
			error(_('Flags must be in PNG format.'));
		}

		if (filesize($upload) > 48000){
			error(_('File too large!'));
		}

		if (!$size = @getimagesize($upload)) {
			error($config['error']['invalidimg']);
		}

		if ($size[0] > 20 or $size[0] < 11 or $size[1] > 16 or $size[1] < 11){
			error(_('Image wrong size!'));
		}
		if (sizeof($banners) > 1500) {
			error(_('Too many flags.'));
		}

		copy($upload, "$dir/$id.$extension");
		purge("$dir/$id.$extension", true);
		$config['user_flags'][$id] = utf8tohtml($description);
		file_put_contents($b.'/flags.ser', serialize($config['user_flags']));
	}

	// Handle a new flag, if any.
	if (isset($_FILES['file'])){
		handle_file(false, $_POST['description'], $b, $dir);
	}

	// Handle edits to existing flags.
	foreach ($_FILES as $k => $a) {
		if (empty($_FILES[$k]['tmp_name'])) continue;

		if (preg_match('/^flag-(\d+)$/', $k, $matches)) {
			$id = (int)$matches[1];
			if (!isset($_POST['description-'.$id])) continue;

			if (isset($config['user_flags'][$id])) {
				handle_file($id, $_POST['description-'.$id], $b, $dir);
			}
		}
	}

	// Description just changed, flag not edited.
	foreach ($_POST as $k => $v) {
		if (!preg_match('/^description-(\d+)$/', $k, $matches)) continue;
		$id = (int)$matches[1];
		if (!isset($_POST['description-'.$id])) continue;

		$description = $_POST['description-'.$id];

		if (strlen($description) > 255)
			error(_('Flag description too long!'));
		$config['user_flags'][$id] = utf8tohtml($description);
		file_put_contents($b.'/flags.ser', serialize($config['user_flags']));
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$flags = <<<FLAGS
<?php
\$config['country_flags'] = false;
\$config['country_flags_condensed'] = false;
\$config['user_flag'] = true;
\$config['uri_flags'] = '/static/custom-flags/$b/%s.png';
\$config['flag_style'] = '';
\$config['user_flags'] = unserialize(file_get_contents('$b/flags.ser'));
FLAGS;

								if ($config['cache']['enabled']) {
												cache::delete('config_' . $b);
												cache::delete('events_' . $b);
		}

		file_write($b.'/flags.php', $flags);
	}


	if (isset($_POST['delete'])){
		foreach ($_POST['delete'] as $i => $d){
			if (!preg_match('/^[0-9]+$/', $d)){
				error('Nice try.');
			}
			unlink("$dir/$d.png");
			$id = explode('.', $d)[0];
			unset($config['user_flags'][$id]);
			file_put_contents($b.'/flags.ser', serialize($config['user_flags']));
		}
	}

	if (isset($_POST['alphabetize'])) {
		asort($config['user_flags'], SORT_NATURAL | SORT_FLAG_CASE);
		file_put_contents($b.'/flags.ser', serialize($config['user_flags']));
	}

	$banners = array_diff(scandir($dir), array('..', '.'));
	mod_page(_('Edit flags'), 'mod/settings/flags.html', array('board'=>$board,'banners'=>$banners,'token'=>make_secure_link_token('banners/'.$board['uri'])));
}



