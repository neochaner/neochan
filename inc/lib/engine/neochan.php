<?php




class NeochanEngine extends Engine {
	
	


function buildHead($js_variables = array()){

global $config, $board, $mod; 

if($config['url_favicon'])
	$this->favicon = $config['root'] . $config['url_favicon']; 

// meta
$this->insert_head('<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">');

if($config['meta_keywords'])
	$this->insert_head('<meta name="keywords" content="' . $config['meta_keywords'] . '">');

if($config['meta_description'])
	$this->insert_head('<meta name="description" content="' . $config['meta_description'] . '">');

if($board && !$board['indexed'])
	$this->insert_head('<meta name="robots" content="noindex">');


//css
$this->insert_head_css('/stylesheets/all.css', 'overall_theme'); 

// css noscript
$this->insert_head_css('/stylesheets/light_blue.css', false, true); 
$this->insert_head_css('/stylesheets/lang/en.css', 'language-css', true); 


// css flags
if($config['country_flags_condensed'])
	$this->insert_head_css($config['country_flags_condensed_css']); 


// js
$this->insert_js_link('/timejs.php');
// katex
if($config['katex'])
	$this->insert_js_link('/js/mathjax/MathJax.js?config=8chanTeX');
// additional_javascript_compile
if($config['additional_javascript_compile'] === false){
	foreach($config['additional_javascript'] as $link)
		$this->insert_js_link($link);
}



// js code
$sup_languages = '';
foreach($config['sup_languages'] as $key=>$value)
	$sup_languages .= '\'' . $key . '\',';

	
// js variables
foreach($js_variables as $name=>$value){
	
	$this->insert_js('var '.$name.'='. (is_string($value) ? '"'.$value.'"':$value).';');
}
	
$this->insert_js('

var config = { max_images : ' .$config['max_images'] . ', test : 0} ;
var theme = localStorage.getItem("theme-css");
var neotube = ' . ($config['neotube']['enable'] ? 'true;':'false;') . '
var lang = navigator.language || navigator.userLanguage;	
var sup_languages = [ ' . $sup_languages . '];
var set_language = true;
var selected_language = localStorage.getItem("language");
var browser_language = (navigator.language || navigator.userLanguage).substr(0, 2);

if(browser_language == "ua" || browser_language == "be" ||browser_language == "kz")
{
		browser_language="ru";
}

if(selected_language == null){
		selected_language = browser_language;
}

for(var i=0; i<sup_languages.length; i++){
	if(sup_languages[i] == selected_language){
		set_language = false;
	}
}


if(set_language){
	selected_language = "en";
}

var style_time = 1557525841;
var css_theme 	 = "<link rel=\'stylesheet\' id=\'theme-css\' type=\'text/css\' href=\'/stylesheets/"+ (theme === null ? "light_blue" : theme) +".css?" + style_time + "\'>";
var css_language = "<link rel=\'stylesheet\' id=\'language-css\' type=\'text/css\' href=\'/stylesheets/lang/"+ selected_language +".css?" + style_time + "\'>";


document.write(css_theme);
document.write(css_language);
document.close();



var configRoot="'.$config['root']. '";
var inMod = ' . ($mod ? 'true':'false') . ';
var modRoot="'. $config['root'] . '"+(inMod ? "mod.php?/" : "");
var max_images='.$config['max_images'].';
var allow_watch_deleted = '.( $config['allow_watch_deleted'] ? 'true':'false').';

');


$this->insert_js('var styles = { ');

foreach($config['stylesheets'] as $name=>$uri)
	$this->insert_js('"' . $name . '":"' . $uri . '", ');

$this->insert_js(' };');




}


function buildHeader(){
		global $config, $board, $mod; 

		$this->insert_header('

		<header class="header">
<a class="header-item header-logo" href="/" title="Главная"><i class="logo"></i></a>

');



foreach($config['menu_pages'] as $name=>$uri){
	
	if($name[0]=='l'&&$name[1]=='_')
		$this->insert_header('<a class="header-item header-board '.$name.'" href="'.$uri.'"></a>');
	else
		$this->insert_header('<a class="header-item header-board" href="'.$uri.'">'.$name.'</a>');
	
}


$this->insert_header('

<div class="header-spacer"></div>

<a class="header-item header-icon" id="btn-neotube" title="Кинотеатр" onclick="searchTubes()" style="display: none"><i class="fa fa-film"></i></a>
<a class="header-item header-icon header-options-icon" title="Вход" id="btn-login"><i class="fa fa-user"></i></a>
<a class="header-item header-icon" title="Справка" href="/faq.html"><i class="fa fa-question"></i></a>  
<a class="header-item header-icon header-options-icon" title="Настройки" id="btn-options"><i class="fa fa-gear"></i></a>

</header>
		
		
		
<aside class="modal-container">

<div id="options" class="modal" style="display: none;">
<span class="l_option" style="font-weight: bold;"></span>
<span  style="position: absolute;right: 20px; cursor: pointer;font-size: 13px;">
	<a class="l_optionSave" style="padding: 0 5px;" onclick="loadSettings()"></a>
	<a class="l_optionLoad" style="padding: 0 5px;" onclick="saveSettings()"></a>
</span>
<hr>


<div class="options-tab">

<div class="options-item">
	<select name="language" id="language" class="option-select" style="width:95px">
	'.
	$this->array_assoc_map($config['sup_languages'], 
	'<option value="' , '">' , '</option>') 
	.'
	</select> 
	<label class="option-label ml5 l_language" for="language"></label>
</div>

<div class="options-item">
	<select name="theme" id="theme" class="option-select" style="width:95px">
	');
	foreach($config['stylesheets'] as $name=>$css){
		$this->insert_header('<option value="'.explode('.', $css)[0]. '">'.$name.'</option>');
	}
	
$this->insert_header('
	</select> 
	<label class="option-label ml5 l_sitetheme" for="theme" ></label>
</div>
<br>


</div>
</div>


<div id="login" class="modal" style="display: none;min-width: 218px;">
		<span class="l_board_control"></span>
		<hr style="margin:10px 0!important">
		<br>

		<form action="api.php" method="POST">
			<aside style="padding-left: 28px;">

				<label for="email" class="l_login_label" style="margin: 5px;font-size: 12px;"></label>
				<br>
				<input class="theme-textbox" style="margin: 5px; width: 150px;" type="email" id="luser">
				<p></p>
		
				<label for="password" class="l_passwd_label" style="margin: 5px;font-size: 12px;"></label>
				<br>
				<input class="theme-textbox" style="margin: 5px;width: 150px;" type="password" id="lpass">
		
			</aside>

			<br>
			<div id="login_captcha"></div>
			<div style="text-align: center;">
				<div class="l_login_enter button send-button" style="text-align: center" onclick="login()"></div>
			</div>
			<br>

			<!--
			<br>
			<div id="login_captcha"></div>
			<br>

			<div style="padding:20px 2px 5px;text-align: center;">
					<div class="l_login_enter button send-button" style="margin-right:14px" onclick="login()"></div>
					<div class="l_login_reg button send-button" onclick="register()"></div>
			</div>

			!-->
		</form>
	
</div>



<div id="profile" class="modal" style="display: none; ">
	<span id="profileName" style="font-family: system-ui;font-size: 16px;"></span>
	<hr>
	<br>


	<div class="account-tabs">
		<a href="/mod.php?/"> Панель управления</a>
		<a href="#"> Репорты <span class="pull-right alert-numb account-reports">0</span></a>
		<a href="/mod.php?/inbox"> Сообщения <span class="pull-right alert-numb account-messages">0</span></a>
	</div>


	<br>
	<div style="padding:20px 10px 5px;">
			<div class="l_login_out button send-button" onclick="logOut()"></div>
	</div>
</div>


</aside>




<aside class="alerts-container">
<div class="alerts-container-inner">
<div id="alert" class="modal" style="display: none;"></div>
</div>
</aside>
	
<nav class="page-nav">
	<a class="page-nav-item page-nav-top page-nav-item_active" href="#top"><i class="fa fa-chevron-up"></i></a>
	<a class="page-nav-item page-nav-bottom page-nav-item_active" href="#footer"><i class="fa fa-chevron-down"></i></a>
</nav>

		
		
		
		
		
		
		
		');
		
		
		
		
		
	}


function buildFooter(){
	
	
	$this->insert_footer('
	<br>
<footer style="margin:0px 5px 15px 5px;text-align:center;" id="footer">
<p class="footer-text" style="font-size: 11px;">'.$config['footer_title'].'</p>
<p class="footer-text" style="font-size: 9px;"></p>
</footer>
<aside class="hover-container hover-container_thread"></aside>


');

	// css or js
	
	$this->insert_footer_css($config['root'] . 'stylesheets/smiles.css');
	$this->insert_footer_css($config['root'] . 'stylesheets/plyr/plyr3.5.2.css', 'plyr_theme');

}
	
	
	
	
	
	
	
	function buildThread($board, $subject){
		
		
		
	}
	
}




$e = new NeochanEngine('/var/www/neochan', true, true);
$e->buildHead(['active_page'=>'thread', 'maxxi'=>55]);
$e->buildHeader();
$e->buildFooter();
echo $e->display();


































