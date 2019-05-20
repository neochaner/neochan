<?php
define('TINYBOARD', true);

$config['twig_cache'] = false;
$config['dir']['template'] = getcwd() . '/templates';
$error_message = false;

require_once 'inc/template.php';

load_twig();


if(file_exists('inc/secrets.php')) {

	echo Element('installer/already_installed.html',  array(
		'title' => 'ALREADY INSTALLED!',
		));
	exit;
}

if(!isset($_GET['step'])) { // check requirments
	
	$can_exec = allow_exec();
	$ffmpeg = allow_ffmpeg();
	
	$tests = array(
		array(
			'name' => 'File permissions  ' . getcwd() ,
			'result' => is_writable('.'),
			'required' => true,
			'fail' => 'neochan does not have permission to create directories (boards) here. You will need to <code>chmod</code> (or operating system equivalent) appropriately.',
			'success' => 'ok'
		),
		array(
			'name' => 'File  permissions  ' . getcwd() . '/tmp/cache',
			'result' => is_dir('tmp/cache') && is_writable('tmp/cache'),
			'required' => true,
			'fail' => 'You must give neochan permission to write to the <code>tmp/cache</code> directory.',
			'success' => 'ok'
		),
		array(
			'name' => 'PHP',
			'result' => version_compare(PHP_VERSION, '7.0.0') >= 0,
			'required' => true,
			'fail' => 'neochan requires PHP 7.0 or better.',
			'success' => ('current version ' . phpversion())
		),
		array(
			'name' => 'shell_exec access',
			'result' => $can_exec,
			'required' => false,
			'fail' => 'not access',
			'success' => 'ok'
		),
		array(
			'name' => 'ffmpeg',
			'result' => $ffmpeg,
			'required' => false,
			'fail' => 'not available',
			'success' => 'ok'
		),
		array(
			'name' => 'php-bcmath extension',
			'result' => extension_loaded('bcmath'),
			'required' => true,
			'fail' => 'You must install the PHP <a href="https://www.php.net/manual/en/bc.installation.php">bcmath</a> extension.',
			'success' => 'ok'
		),array(
			'name' => 'php-mbstring extension',
			'result' => extension_loaded('mbstring'),
			'required' => true,
			'fail' => 'You must install the PHP <a href="http://www.php.net/manual/en/mbstring.installation.php">mbstring</a> extension.',
			'success' => 'ok'
		),
		array(
			'name' => 'php-pdo extension',
			'result' => extension_loaded('pdo'),
			'required' => true,
			'fail' => 'You must install the PHP <a href="http://www.php.net/manual/en/intro.pdo.php">PDO</a> extension.',
			'success' => 'ok'
		),
		array(
			'name' => 'MySQL PDO driver',
			'result' => extension_loaded('pdo') && in_array('mysql', PDO::getAvailableDrivers()),
			'required' => true,
			'fail' => 'The required <a href="http://www.php.net/manual/en/ref.pdo-mysql.php">PDO MySQL driver</a> is not installed.',
			'success' => 'ok'
		),
		array(
			'name' => 'php-gd extension',
			'result' => extension_loaded('gd'),
			'required' => true,
			'fail' => 'You must install the PHP <a href="http://www.php.net/manual/en/intro.image.php">GD</a> extension. GD is a requirement even if you have chosen another image processor for thumbnailing.',
			'success' => 'ok'
		),
		array(
			'name' => 'cache',
			'result' => (extension_loaded('apc') && ini_get('apc.enabled')),
			'required' => false,
			'fail' => 'php-apc cache extension not available',
			'success' => 'ok'
		)
		
		
	);

	echo Element('installer/index.html',  array(
			'title' => 'Pre-installation test',
			'tests' => $tests,
			));
}
else if($_GET['step'] == 1){ // setup config or check config


	if(isset($_POST['dbhost'])) {

		$config['db']['type'] 		= 'mysql';
		$config['db']['timeout'] 	= 10;
		$config['db']['server'] 	= $_POST['dbhost'];
		$config['db']['database'] 	= $_POST['dbname'];
		$config['db']['prefix']		= '';
		$config['db']['user'] 		= $_POST['dbuser'];
		$config['db']['password'] 	= $_POST['dbpass'];	
		$config['db']['dsn'] 		= '';
		$config['db']['persistent'] = false;
		
		$config['board_regex'] 		= '[0-9a-zA-Z\+$_\x{0080}-\x{FFFF}]{1,58}';
		$config['mask_db_error'] 	= false;

		$config['fat_system'] = false;
		$config['fat_size'] = 10 * 1024 * 1024;
		$config['fat_server'] = 'http://s.site.ru';
		$config['fat_ftpip'] = '15.18.183.3';
		$config['fat_ftpuser'] = 'ftpuser';
		$config['fat_ftppass'] = 'ftppass';
		$config['fat_ftp_timeout'] = 5;

		$config['encryption']['key_length'] = 2048;
		$config['encryption']['key_alg'] = 'sha512';
		$config['encryption']['public_key'] = null;
		$config['encryption']['private_key'] = null;
		

		$config['secure_trip_salt'] = gen_uuid();
		$config['secure_salt'] = gen_uuid();
		$config['cookies']['salt'] = gen_uuid();

		$config['cache']['enabled'] = (extension_loaded('apc') && ini_get('apc.enabled')) ? 'apc':'php';


		include 'inc/database.php';

		global $pdo;
		sql_open();


		if(!$error_message) {


			// sql connect is ok

			// import database  
			if(!$pdo->query(file_get_contents('install.sql'))){

				echo Element('installer/install.html',  array(
					'title' => 'Installation...',
					'post' => $_POST ?? ['dbhost'=>'localhost'],
					'db_error' => 'ERROR IMPORT DATABASE!',
				));
				exit;
			}

			// write secrets.php
$ffm = !allow_ffmpeg() ? "\$config['webm']['use_ffmpeg'] = false;":'';
		
$php_code = <<<CONFIG
<?php
\$config['db']['type'] 		= 'mysql';
\$config['db']['timeout'] 	= 10;
\$config['db']['server'] 	= '{$_POST['dbhost']}';
\$config['db']['database'] 	= '{$_POST['dbname']}';
\$config['db']['prefix']	= '';
\$config['db']['user'] 		= '{$_POST['dbuser']}';
\$config['db']['password'] 	= '{$_POST['dbpass']}';
\$config['db']['dsn'] 		= '';
\$config['db']['persistent'] = false;

\$config['fat_system'] = false;
\$config['fat_size'] = 10 * 1024 * 1024;
\$config['fat_server'] = 'http://s.site.ru';
\$config['fat_ftpip'] = '15.18.183.3';
\$config['fat_ftpuser'] = 'ftpuser';
\$config['fat_ftppass'] = 'ftppass';
\$config['fat_ftp_timeout'] = 5;

\$config['encryption']['key_length'] = 2048;
\$config['encryption']['key_alg'] = 'sha512';
\$config['encryption']['public_key'] = null;
\$config['encryption']['private_key'] = null;

\$config['secure_trip_salt'] = '{$config['secure_trip_salt']}';
\$config['secure_salt'] = '{$config['secure_trip_salt']}';
\$config['cookies']['salt'] = '{$config['secure_trip_salt']}';

\$config['cache']['enabled'] = '{$config['cache']['enabled']}';



\$config['encryption']['key_length'] = 2048;
\$config['encryption']['key_alg'] = 'sha512';
\$config['encryption']['public_key'] = '';
\$config['encryption']['private_key'] = '';

$ffm 
CONFIG;

 

			// generate new admin password
			$salt = generate_salt();
			$newPass = substr(sha1(mt_rand()), 0, 8);
			$hashPass = hash('sha256', $salt . sha1($newPass));

					
			$query = prepare('UPDATE ``mods`` SET `password` = :password, `salt` = :salt WHERE `username` = "admin"');
			$query->bindValue(':password', $hashPass);
			$query->bindValue(':salt', $salt);
			
			if(!$query->execute()){
				$error_message = db_error($query);
			} else {
				file_put_contents('inc/secrets.php',  $php_code );

				echo Element('installer/success.html',  array(
					'title' => 'Installation...',
					'username' => 'admin',
					'password' => $newPass,
				
				));
				exit;
			}

		}
		

		echo Element('installer/install.html',  array(
				'title' => 'Installation...',
				'post' => $_POST ?? ['dbhost'=>'localhost'],
				'db_error' => $error_message,
			
			));

	} else {


	
		echo Element('installer/install.html',  array(
			'title' => 'Installation...',
			'db_error' => false,
		
		));

	}
	
 
	
} else if($_GET['step'] == 2){ // done
	

}




function error($message){
	global $error_message;
	$error_message = $message;
}

function allow_exec(){
	
	$can_exec = true;
	
	if (!function_exists('shell_exec'))
		$can_exec = false;
	elseif (in_array('shell_exec', array_map('trim', explode(', ', ini_get('disable_functions')))))
		$can_exec = false;
	elseif (ini_get('safe_mode'))
		$can_exec = false;
	elseif (trim(shell_exec('echo "TEST"')) !== 'TEST')
		$can_exec = false;
	

	return $can_exec;
}


function allow_ffmpeg(){
	return allow_exec() && trim(shell_exec('type -P ffmpeg'));
}


function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

function selected_cache(){

	if(extension_loaded('apc') && ini_get('apc.enabled'))
		return 'apc';
	else 
		return 'php';
}

function generate_salt()
{
	mt_srand(microtime(true) * 100000 + memory_get_usage(true));
	return md5(uniqid(mt_rand(), true));
}





