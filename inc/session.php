<?php

class Session {

	public static $data = [
		'create'=>0,
		'capchas_left'=>0,
		'posts_left'=>0,
		'spam'=> array(),
	];

	public static $cookie_id = 0;
	public static $cookie_key = null; 
	public static $cache_time = 60*5;
	public static $ip; 
	public static $ip_range;
	public static $is_onion; 
	public static $is_i2p;
	public static $is_darknet;
	public static $is_exit_node;
	public static $initialized = false;

	public static function init() 
	{
		global $config;

		if (self::$initialized) {
			return;
		}

		self::$initialized = true;

		self::$data['capchas_left'] = 0;
		self::$data['posts_left'] = $config['tor']['max_posts'];
		self::$data['posts_max'] = $config['tor']['max_posts'];
		
		self::$data['create'] = time();

		self::$ip = $_SERVER['REMOTE_ADDR'];
		self::$ip_range = self::$ip;
		self::$is_onion = in_array($_SERVER['REMOTE_ADDR'], $config['tor_service_ips']);
		self::$is_i2p = in_array($_SERVER['REMOTE_ADDR'], $config['i2p_service_ips']);
		self::$is_darknet = (self::$is_onion || self::$is_i2p); 

		if (isset($_COOKIE[$config['cookies']['general']])) {
			$arr = explode('_', $_COOKIE[$config['cookies']['general']]);
 
			if (count($arr) == 3 && $arr[0] == 'c') { 
				self::$cookie_id =$arr[1];
				self::$cookie_key = $arr[2];
			}
		} 
	}

	/*
		возвращает состояние антиспам проверки:

		-1 = проверка отключена
		 0 = проверка требуется
		 1 = проверка пройдена
	*/
	public static function getAntispamState()
	{
		global $config;

		$www_cap = $config['captcha']['antispam']['enable_www'];
		$darknet_cap = $config['captcha']['antispam']['enable_www'];
		
		if ($www_cap == 0 && $darknet_cap == 0) {
			return -1;
		}

		if ($www_cap > 0 && !self::$is_darknet) {
			return ($www_cap > self::$data['capchas_left']) ? 0 : 1;
		}

		if ($darknet_cap > 0 && self::$is_darknet) {
			return ($darknet_cap > self::$data['capchas_left']) ? 0 : 1;
		}


	}

	public static function load()
	{

		if (!self::$initialized) {
			self::init();
		}
  
		if (self::$cookie_id == 0) {
			return;
		}
		
		$data = cache::get('cookie_' . self::$cookie_id);

		if (!is_array($data)) {

			$query = prepare("SELECT `data` FROM `cookie` WHERE `id` = :id");
			$query->bindValue(':id', self::$cookie_id, PDO::PARAM_INT);
			$query->execute() or error(db_error($query));

			$db_data = $query->fetch(PDO::FETCH_ASSOC);

			if ($db_data) {  
				self::$data = json_decode($db_data['data'], TRUE);
				cache::set('cookie_' . self::$cookie_id, self::$data, self::$cache_time);
			} else {
				self::$cookie_id = 0;
			}

		} else {
			self::$data = $data;
		}
	}

	public static function save()
	{
		global $config, $pdo;

		if (!self::$initialized) {
			self::init();
		}

		if (self::$cookie_id == 0) {
			self::$cookie_key = 'cookie' . bin2hex(random_bytes(18));
			self::$data['create'] = time(); 
		}

		$query = prepare("INSERT INTO `cookie` (`key`,`created`,`data`, `last`) VALUES (:key, NOW(), :data, NOW())" . 
			" ON DUPLICATE KEY UPDATE `data`=:data, `last`=NOW()");
		$query->bindValue(':key', self::$cookie_key, PDO::PARAM_STR);
		$query->bindValue(':data', json_encode(self::$data));
		$query->execute() or error(db_error($query));

		if (self::$cookie_id == 0) {
			self::$cookie_id = $pdo->lastInsertId();
			setcookie($config['cookies']['general'], 'c_' . self::$cookie_id . '_' . self::$cookie_key, time()+60*60*24*30);
		} 
		
		if (self::$cookie_id == 0) {
			syslog(LOG_ERR, '[neochan] [error] Session:save, cookie_id == 0!');
		}

		cache::set('cookie_' . self::$cookie_id, self::$data, self::$cache_time);
	}

	public static function captchaSolved()
	{
		if (!self::$initialized) {
			self::init();
		}

		self::$data['capchas_left']++;
		self::save();
	}

	public static function isAllowPost()
	{
		global $config;

		if (!self::$initialized) {
			self::init();
		}

		if (self::$is_onion || self::$is_i2p) {

			return self::$data['capchas_left'] >= $config['tor']['need_capchas'];
		}
		
		return true;
	}

	public static function isAllowVote()
	{

		global $config;

		if (!self::$initialized) {
			self::init();
		}

		if (!$config['polls']['enable']) {
			return false;
		}

		if ((self::$is_i2p || self::$is_onion) && !$config['polls']['darknet_enable']) {
			return false;
		}

		if ($config['polls']['ro_min_sec'] > 0 && $config['polls']['ro_min_sec'] < self::$data['create']) {
			return false;
		}

		if ($config['polls']['postcount_min'] > 0 && count(self::$data['spam']) >= $config['polls']['postcount_min']) {
			return false;
		}

		return true;
	}

	public static function getIdentity()
	{
		global $config;

		if (!self::$initialized) {
			self::init();
		}

		if (self::$is_onion || self::$is_i2p) {
			return self::$cookie_key;
		}

		switch ($config['security_mode']) {
			case 1:
				return '!s1' . self::encrypt(self::$ip, $config['security_salt']);
			case 2:
				return '!s2' . self::encrypt(self::$ip);
			default:
				return self::$ip;
		}
		
	}

	public static function getIdentityRange()
	{
		global $config;

		if (!self::$initialized) {
			self::init();
		}

		if (self::$is_onion || self::$is_i2p) {
			return self::$cookie_key;
		}

		switch ($config['security_mode']) {
			case 1:
				return '!r1' . self::encrypt(self::$ip_range, $config['security_salt']);
			case 2:
				return '!r2' . self::encrypt(self::$ip_range);
			default:
				return self::$ip_range;
		}
	}

	private static function getKey()
	{

		global $config;

		$key = cache::get('CryptKey');

		if (!$key) {
			cache::set('CryptKey', bin2hex(random_bytes(128)), $config['security_mode_time']);
		}
	
		return $key;
	}
	
	public static function encrypt($string, $key = NULL)
	{
		global $config;

		if ($key == NULL) {
			$key = self::getKey();
		}
		
        $iv = $config['security_salt'] ?? 'none';
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $key );
        $iv = substr( hash( 'sha256', $iv ), 0, 16 );

        return base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
    }

	public static function decrypt($string, $key = NULL)
	{
		global $config;

		if ($key == NULL) {
			$key = self::getKey();
		}
			
        $iv = $config['security_salt'] ?? 'none';
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $key );
        $iv = substr( hash( 'sha256', $iv ), 0, 16 );
     
        return openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
    }



}




