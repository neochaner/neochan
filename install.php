<?php

if(php_sapi_name() !== 'cli'){
    exit;
}


if(count($argv) != 5){
    echo 'Wrong arguments';
    exit;
}

$www_dir = $argv[1];
$db_name = $argv[2];
$db_user = $argv[3];
$db_pass = $argv[4];
$secrets_template = 'inc/secrets.example.php';
$secrets_file = 'inc/secrets.php';

chdir($www_dir); 



 

message("create rsa keys");
$keys = generate_keys();

message("generate trip/cookie salt");
$secure_trip_salt = gen_uuid();
$cookies_salt = gen_uuid();


file_put_contents($secrets_file, "<?php

\$config['db']['server'] = 'localhost';
\$config['db']['database'] = '$db_name';
\$config['db']['prefix'] = '';
\$config['db']['user'] = '$db_user';
\$config['db']['password'] = '$db_pass';

\$config['fat_system'] = false;
\$config['fat_size'] = 10 * 1024 * 1024;
\$config['fat_server'] = 'http://s.site.ru';
\$config['fat_ftpip'] = '15.18.183.3';
\$config['fat_ftpuser'] = 'ftpuser';
\$config['fat_ftppass'] = 'ftppass';
\$config['fat_ftp_timeout'] = 5;

// defaul timezone
\$config['timezone'] = 'UTC';
// defaul cache
\$config['cache']['enabled'] = 'memcached';


\$config['secure_trip_salt'] = '$secure_trip_salt';
\$config['cookies']['salt'] = '$cookies_salt';


\$config['encryption']['key_length'] = 2048;
\$config['encryption']['key_alg'] = 'sha512';
\$config['encryption']['public_key'] = '{$keys['public']}';
\$config['encryption']['private_key'] = '{$keys['private']}';


?>");
chmod($secrets_file, 0444);

create_board();


function message($text){
    echo "$text\n";
    sleep(1);
}

function generate_keys(){

    $rsaKey = openssl_pkey_new(array( 
        'private_key_bits' => 2048, 
        'private_key_type' => OPENSSL_KEYTYPE_RSA));

    $privKey = openssl_pkey_get_private($rsaKey); 
    openssl_pkey_export($privKey, $pem); //Private Key
    $key = openssl_pkey_get_details($privKey);

    return array('public'=> $key['key'], 'private'=> $pem);
}

function gen_uuid() {

    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0x0fff ) | 0x4000,
        mt_rand( 0, 0x3fff ) | 0x8000,
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

function create_board(){
    
    require 'inc/functions.php';
    require 'inc/mod/pages.php';

    global $config, $mod;
    
    $mod = array('id'=>1, 'username'=> 'admin', 'type'=> 30, 'boards' => '*');

    $_POST = ['uri'=> 'b', 'title'=> 'Random', 'subtitle' => ''];
    mod_new_board();

	
	 
	$_POST = [		
			'rebuild' => true, 
			'rebuild_cache' => true, 
			'rebuild_themes' => true, 
			'rebuild_javascript' => true, 
			'boards_all' => true,
			'rebuild_index' => true
			];
		buildJavascript();		
	rebuildThemes('all');	
	
   // mod_rebuild();
	
	include 'boards.php';
}



















?>
