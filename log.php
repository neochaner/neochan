<?php
include 'inc/functions.php';
include 'inc/mod/pages.php';

if (!isset($_GET['board']) || !preg_match("/". $config['board_regex'] . "/u", $_GET['board'])) {
	http_response_code(400);
	error(_('Bad board.'));
}
if (!openBoard($_GET['board'])) {
	http_response_code(404);
	error(_('No board.'));
}

if ($board['public_logs'] == 0)
	error(_('This board has public logs disabled. Ask the board owner to enable it.'));

$page = !isset($_GET['page']) ? 1 : (int)$_GET['page'];

mod_board_log($board['uri'], $page, $board['public_logs'] == 1 ? false : true , true);
