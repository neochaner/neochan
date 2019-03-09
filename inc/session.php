<?php



class session {

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
	public static $initialized = false;


	public static function init() {

		global $config;

		if(self::$initialized)
			return;

		self::$initialized = true;

		self::$data['capchas_left'] = 0;
		self::$data['posts_left'] = $config['tor']['max_posts'];
		self::$data['posts_max'] = $config['tor']['max_posts'];
		
		self::$data['create'] = time() ;

		self::$is_onion = in_array($_SERVER['REMOTE_ADDR'], $config['tor_service_ips']);
		self::$is_i2p = in_array($_SERVER['REMOTE_ADDR'], $config['i2p_service_ips']);
		self::$is_darknet = (self::$is_onion || self::$is_i2p);

		// ! don't use /filter_var(  FILTER_VALIDATE_IP/
		if(strpos($_SERVER['REMOTE_ADDR'], ":") === false)
		{
			self::$ip = $_SERVER['REMOTE_ADDR'];
		}
		else
		{

			if ($_SERVER['REMOTE_ADDR'][0] == ':' && preg_match('/^\:\:(ffff\:)?(\d+\.\d+\.\d+\.\d+)$/', $_SERVER['REMOTE_ADDR'], $m))
				self::$ip = $m[2];
			else
			{
				$b = explode(':', $_SERVER['REMOTE_ADDR']);
				self::$ip = $b[0] . ':' . $b[1] . ':' . $b[2] . ':' .  $b[3] ;
			}
		}

		$b = explode('.', self::$ip);

		if(count($b) == 4){
			self::$ip_range = $b[0] . '.' . $b[1] . '.' . $b[2] . '0/24';
		}
		else{
			self::$ip_range = self::$ip;
		}

		if(isset($_COOKIE[$config['cookies']['general']]))
		{
			$arr = explode('_', $_COOKIE[$config['cookies']['general']]);
 
			if(count($arr) == 3 && $arr[0] == 'c')
			{ 
				self::$cookie_id =$arr[1];
				self::$cookie_key = $arr[2];
			}
		} 
	}

	public static function capchas_left(){
		return $data['capchas_left'];
	}

	/*
		возвращает состояние антиспам проверки:

		-1 = проверка отключена
		 0 = проверка требуется
		 1 = проверка пройдена
	*/
	public static function antispam_state(){

		global $config;

		$www_cap = $config['captcha']['antispam']['enable_www'];
		$darknet_cap = $config['captcha']['antispam']['enable_www'];
		

		if($www_cap == 0 && $darknet_cap == 0)
			return -1;


		if($www_cap > 0 && !self::$is_darknet){

			return ($www_cap > self::$data['capchas_left']) ? 0 : 1;
		}

		if($darknet_cap > 0 && self::$is_darknet){

			return ($darknet_cap > self::$data['capchas_left']) ? 0 : 1;
		}


	}

	public static function Load(){

		if(!self::$initialized)
			self::init();
  
		if(self::$cookie_id == 0)
			return;
		
		$data = cache::get('cookie_' . self::$cookie_id);

		if(!is_array($data))
		{
			$query = prepare("SELECT `data` FROM `cookie` WHERE `id` = :id");
			$query->bindValue(':id', self::$cookie_id, PDO::PARAM_INT);
			$query->execute() or error(db_error($query));

			$db_data = $query->fetch(PDO::FETCH_ASSOC);

			if($db_data)
			{  
				self::$data = json_decode($db_data['data'], TRUE);
				cache::set('cookie_' . self::$cookie_id, self::$data, self::$cache_time);
			}
			else
			{
				self::$cookie_id = 0;
			}
		}
		else
		{
			self::$data = $data;
		}
	}

	public static function Save(){

		global $config, $pdo;

		if(!self::$initialized)
			self::init();

		if(self::$cookie_id == 0)
		{
			self::$cookie_key = 'cookie' . bin2hex(random_bytes(18));
			self::$data['create'] = time(); 
		}

		$query = prepare("INSERT INTO `cookie` (`key`,`created`,`data`, `last`) VALUES (:key, NOW(), :data, NOW())" . 
			" ON DUPLICATE KEY UPDATE `data`=:data, `last`=NOW()");
		$query->bindValue(':key', self::$cookie_key, PDO::PARAM_STR);
		$query->bindValue(':data', json_encode(self::$data));
		$query->execute() or error(db_error($query));


		if(self::$cookie_id == 0){
			self::$cookie_id = $pdo->lastInsertId();
			setcookie($config['cookies']['general'], 'c_' . self::$cookie_id . '_' . self::$cookie_key, time()+60*60*24*30);
		} 
		
		
		if(self::$cookie_id == 0)
			syslog(LOG_ERR, '[neochan] [error] PostSession:Save, cookie_id == 0!');

		cache::set('cookie_' . self::$cookie_id, self::$data, self::$cache_time);
		
	}

	/* DONT USE IT, NEED REMOVED */
	public static function NeedCaptchaCount(){

		global $config;

		if(!self::$initialized)
			self::init();

		if(self::$is_onion || self::$is_i2p)
		{
			$captcha_count = $config['tor']['need_capchas'] - self::$data['capchas_left'];

			return $captcha_count < 0 ? 0 : $captcha_count ;
		}

		return 0;
	}

	public static function CaptchaSolved(){

		if(!self::$initialized)
			self::init();

		self::$data['capchas_left']++;
		self::Save();
	}

	public static function AllowPost(){

		global $config;

		if(!self::$initialized)
			self::init();

		if(self::$is_onion || self::$is_i2p){

			return self::$data['capchas_left'] >= $config['tor']['need_capchas'];
		}
		
		return true;
	}

	public static function AllowVote(){

		global $config;

		if(!self::$initialized)
			self::init();

		if(!$config['polls']['enable'])
			return false;

		if((self::$is_i2p || self::$is_onion) && !$config['polls']['darknet_enable'])
			return false;

		if($config['polls']['ro_min_sec'] > 0 && $config['polls']['ro_min_sec'] < self::$data['create'])
			return false;

		if($config['polls']['postcount_min'] > 0 && count(self::$data['spam']) >= $config['polls']['postcount_min'])
			return false;

		return true;
	}

	public static function Posted(){

		if(!self::$initialized)
			self::init();

		self::$data['spam'][] = time();
		self::Save();
	}

	public static function GetIdentity(){

		global $config;

		if(!self::$initialized)
			self::init();

		if(self::$is_onion || self::$is_i2p)
			return self::$cookie_key;

		switch($config['security_mode'])
		{
			case 1:
				return 'ci1!' . self::Encrypt(self::$ip, $config['security_salt']);
			case 2:
				return 'ci2!' . self::Encrypt(self::$ip);
			default:
				return self::$ip;
		}
		
	}

	public static function GetIdentityRange(){

		global $config;

		if(!self::$initialized)
			self::init();

		if(self::$is_onion || self::$is_i2p)
			return self::$cookie_key;

		switch($config['security_mode'])
		{
			case 1:
				return 'cr1!' . self::Encrypt(self::$ip_range, $config['security_salt']);
			case 2:
				return 'cr2!' . self::Encrypt(self::$ip_range);
			default:
				return self::$ip_range;
		}


	}





	private static function GetKey(){

		global $config;

		$key = cache::get('CryptKey');

		if(!$key)
			cache::set('CryptKey', bin2hex(random_bytes(128)), $config['security_mode_time']);
	
		return $key;
	}
	
	public static function Encrypt($string, $key = NULL) {
		
		global $config;

		if($key == NULL)
			$key = self::GetKey();
		
        $iv = $config['security_salt'] ?? 'none';
        $output = false;
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $key );
        $iv = substr( hash( 'sha256', $iv ), 0, 16 );

        return base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
    }

    public static function Decrypt($string, $key = NULL) {

		global $config;

		if($key == NULL)
			$key = self::GetKey();
			
        $iv = $config['security_salt'] ?? 'none';
        $output = false;
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $key );
        $iv = substr( hash( 'sha256', $iv ), 0, 16 );
     
        return openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
    }



}






