<?php

if(php_sapi_name() !== 'cli'){
    exit;
}


$options = getopt("hd:u:p:");

if(isset($options['h'])){

    echo "
    NEOCHAN - install script

    usage: php install.php
    
    Options: 
            -h  show this message
            -w  site directory
            -d  db name
            -u  db user
            -p  db password  

    Example:
            php backup.php --user=root --password=secret --database=blog
    ";
    exit;
}

if(!isset($options['w'],$options['d'], $options['u'], $options['p'])){
    echo 'Wrong arguments';
    var_dump();
    exit;
}

$www_dir = $options['w'];
$db_name = $options['d'];
$db_user = $options['u'];
$db_pass = $options['p'];
$secrets_template = 'inc/secrets.example.php';
$secrets_file = 'inc/secrets.test.php';

chdir($www_dir); 

include $secrets_template;

$config['db']['database'] = $db_name;
$config['db']['user'] = $db_user;
$config['db']['password'] = $db_pass;

message("create rsa keys");
$keys = generate_keys();

$config['encryption']['key_length'] = 2048;
$config['encryption']['key_alg'] = 'sha512';
$config['encryption']['public_key'] =  $keys['public'];
$config['encryption']['private_key'] =  $keys['private'];


message("generate trip/cookie salt");
$config['secure_trip_salt'] = gen_uuid();
$config['cookies']['salt'] = gen_uuid();

$var_str = var_export($config, true);
$var = "<?php\n\n\$config = $var_str;\n\n?>";

file_put_contents($secrets_file, $var);
chmod($secrets_file, 0444);



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
?>