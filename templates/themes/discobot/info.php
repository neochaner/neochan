<?php

$theme = array();
	
// Theme name
$theme['name'] = 'Discord AntiSpam bot';
$theme['description'] = 'Use webhook to report abnormal posting activities and prevent ban bypass';
$theme['version'] = 'v0.1';

$theme['config'][] = array(
	'title' => 'Domain',
	'name' => 'domain',
	'type' => 'text',
	'comment' => 'https://example.com'
);

$theme['config'][] = array(
	'title' => 'Webhook url',
	'name' => 'webhook',
	'type' => 'text',
	'comment' => 'https://discordapp.com/api/webhooks/xxx/xxxx'
);

$theme['config'][] = array(
	'title' => 'Webhook for reports',
	'name' => 'webhook_reports',
	'type' => 'text',
	'comment' => 'https://discordapp.com/api/webhooks/xxx/xxxx'
);



$theme['config'][] = array(
	'title' => 'Included boards',
	'name' => 'boards',
	'type' => 'text',
	'comment' => '(space seperated)'
);

$theme['config'][] = array(
	'title' => ' Excluded boards',
	'name' => 'excluded',
	'type' => 'text',
	'comment' => '(space seperated)'
);


$theme['config'][] = array(
	'title' => 'Report new thread',
	'name' => 'report_thread',
	'type' => 'checkbox',
	'default' => false
);
	
	
$theme['config'][] = array(
	'title' => 'Report new user',
	'name' => 'report_new_user',
	'type' => 'checkbox',
	'default' => false
);

$theme['config'][] = array(
	'title' => 'Forward reports',
	'name' => 'forward_reports',
	'type' => 'checkbox',
	'default' => false
);


	
$theme['build_function'] = 'discobot_build';
	
	

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	