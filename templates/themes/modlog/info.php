<?php

$theme = array();
	
// Theme name
$theme['name'] = 'Mod log';
$theme['description'] = '';
$theme['version'] = 'v0.1';


$theme['config'][] = array(
	'title' => 'Log board',
	'name' => 'dboard',
	'type' => 'text',
	'comment' => 'example: delete'
);

$theme['config'][] = array(
	'title' => 'Included boards',
	'name' => 'boards',
	'type' => 'text',
	'comment' => '(space seperated)'
);

$theme['config'][] = array(
	'title' => 'Excluded users',
	'name' => 'users',
	'type' => 'text',
	'comment' => '(space seperated)'
);



	
$theme['build_function'] = 'modlog_build';
	
