<?php
include_once $config['dir']['themes'] . '/404page/info.php';

function page404_build()
{
    global $config;

    $html = Element('site/404.html', array(

     'config' => $config, 

    ));

    file_write('404.html', $html);
    
}
