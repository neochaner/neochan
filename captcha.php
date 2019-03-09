<?php
include "inc/functions.php";


if(isset($_GET['board'])) {
	
	if(empty($_GET['board'])) {
		init_locale();
		chanCaptcha::get();
	}
	else if(openBoard($_GET['board']))
	{ 
		chanCaptcha::get();
	}
}



