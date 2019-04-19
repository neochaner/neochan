<?php

// 8chan specific mod pages
require 'config-permission.php';

// Load instance functions later on
require_once 'instance-functions.php';
	
// Load database credentials
require "secrets.php";



/*
*  Instance Configuration
*  ----------------------
*  Edit this file and not config.php for imageboard configuration.
*
*  You can copy values from config.php (defaults) and paste them here.
*/           	
  

$config['neotube']['enable'] = false;
$config['allow_create_userboards'] = true;
$config['modern_update_system'] = true;

for ($i=0; $i<count($config['additional_javascript']); $i++) {
    if ($config['additional_javascript'][$i] == 'js/reload.js') {
        $config['additional_javascript'][$i] ='js/reload.modern.js';
    }
}