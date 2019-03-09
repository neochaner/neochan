<?php
    
$theme = array();
	
	// Theme name
$theme['name'] = 'Страница не найдена';
$theme['description'] = '404 error page';
$theme['version'] = 'v.1';

$theme['config'] = array();
	
$theme['build_function'] = 'page404_build';
$theme['install_callback'] = 'page404_install';
    
 
if (!function_exists('page404_install')) {
    function page404_install() {
       
    }
}
