<?php
require "./inc/functions.php";

if(!isset($GET['md5']))
{
    
 
}


echo Element('info.html', array('config' => $config, 'global' => isset($_POST['global'])));
?>