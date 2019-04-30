<?php

class Session {

	public static $data = [
		'create'=>0,
		'capchas_left'=>0,
		'posts_left'=>0,
		'spam'=> array(),
	];

	public static $cookie_key = null; 
	public static $cache_time = 60*5;
	public static $ip; 
	public static $ip_range;
	public static $is_onion; 
	public static $is_i2p;
	public static $is_darknet;
	public static $initialized = false;

	public static $country_data = array("T1" => "Tor Network", "BD"=>"Bangladesh", "BE"=>"Belgium", "BF"=>"Burkina Faso", "BG"=>"Bulgaria", "BA"=>"Bosnia and Herzegovina", "BB"=>"Barbados", "WF"=>"Wallis and Futuna", "BL"=>"Saint Barthelemy", "BM"=>"Bermuda", "BN"=>"Brunei", "BO"=>"Bolivia", "BH"=>"Bahrain", "BI"=>"Burundi", "BJ"=>"Benin", "BT"=>"Bhutan", "JM"=>"Jamaica", "BV"=>"Bouvet Island", "BW"=>"Botswana", "WS"=>"Samoa", "BQ"=>"Bonaire, Saint Eustatius and Saba ", "BR"=>"Brazil", "BS"=>"Bahamas", "JE"=>"Jersey", "BY"=>"Belarus", "BZ"=>"Belize", "RU"=>"Russia", "RW"=>"Rwanda", "RS"=>"Serbia", "TL"=>"East Timor", "RE"=>"Reunion", "TM"=>"Turkmenistan", "TJ"=>"Tajikistan", "RO"=>"Romania", "TK"=>"Tokelau", "GW"=>"Guinea-Bissau", "GU"=>"Guam", "GT"=>"Guatemala", "GS"=>"South Georgia and the South Sandwich Islands", "GR"=>"Greece", "GQ"=>"Equatorial Guinea", "GP"=>"Guadeloupe", "JP"=>"Japan", "GY"=>"Guyana", "GG"=>"Guernsey", "GF"=>"French Guiana", "GE"=>"Georgia", "GD"=>"Grenada", "GB"=>"United Kingdom", "GA"=>"Gabon", "SV"=>"El Salvador", "GN"=>"Guinea", "GM"=>"Gambia", "GL"=>"Greenland", "GI"=>"Gibraltar", "GH"=>"Ghana", "OM"=>"Oman", "TN"=>"Tunisia", "JO"=>"Jordan", "HR"=>"Croatia", "HT"=>"Haiti", "HU"=>"Hungary", "HK"=>"Hong Kong", "HN"=>"Honduras", "HM"=>"Heard Island and McDonald Islands", "VE"=>"Venezuela", "PR"=>"Puerto Rico", "PS"=>"Palestinian Territory", "PW"=>"Palau", "PT"=>"Portugal", "SJ"=>"Svalbard and Jan Mayen", "PY"=>"Paraguay", "IQ"=>"Iraq", "PA"=>"Panama", "PF"=>"French Polynesia", "PG"=>"Papua New Guinea", "PE"=>"Peru", "PK"=>"Pakistan", "PH"=>"Philippines", "PN"=>"Pitcairn", "PL"=>"Poland", "PM"=>"Saint Pierre and Miquelon", "ZM"=>"Zambia", "EH"=>"Western Sahara", "EE"=>"Estonia", "EG"=>"Egypt", "ZA"=>"South Africa", "EC"=>"Ecuador", "IT"=>"Italy", "VN"=>"Vietnam", "SB"=>"Solomon Islands", "ET"=>"Ethiopia", "SO"=>"Somalia", "ZW"=>"Zimbabwe", "SA"=>"Saudi Arabia", "ES"=>"Spain", "ER"=>"Eritrea", "ME"=>"Montenegro", "MD"=>"Moldova", "MG"=>"Madagascar", "MF"=>"Saint Martin", "MA"=>"Morocco", "MC"=>"Monaco", "UZ"=>"Uzbekistan", "MM"=>"Myanmar", "ML"=>"Mali", "MO"=>"Macao", "MN"=>"Mongolia", "MH"=>"Marshall Islands", "MK"=>"Macedonia", "MU"=>"Mauritius", "MT"=>"Malta", "MW"=>"Malawi", "MV"=>"Maldives", "MQ"=>"Martinique", "MP"=>"Northern Mariana Islands", "MS"=>"Montserrat", "MR"=>"Mauritania", "IM"=>"Isle of Man", "UG"=>"Uganda", "TZ"=>"Tanzania", "MY"=>"Malaysia", "MX"=>"Mexico", "IL"=>"Israel", "FR"=>"France", "IO"=>"British Indian Ocean Territory", "SH"=>"Saint Helena", "FI"=>"Finland", "FJ"=>"Fiji", "FK"=>"Falkland Islands", "FM"=>"Micronesia", "FO"=>"Faroe Islands", "NI"=>"Nicaragua", "NL"=>"Netherlands", "NO"=>"Norway", "NA"=>"Namibia", "VU"=>"Vanuatu", "NC"=>"New Caledonia", "NE"=>"Niger", "NF"=>"Norfolk Island", "NG"=>"Nigeria", "NZ"=>"New Zealand", "NP"=>"Nepal", "NR"=>"Nauru", "NU"=>"Niue", "CK"=>"Cook Islands", "XK"=>"Kosovo", "CI"=>"Ivory Coast", "CH"=>"Switzerland", "CO"=>"Colombia", "CN"=>"China", "CM"=>"Cameroon", "CL"=>"Chile", "CC"=>"Cocos Islands", "CA"=>"Canada", "CG"=>"Republic of the Congo", "CF"=>"Central African Republic", "CD"=>"Democratic Republic of the Congo", "CZ"=>"Czech Republic", "CY"=>"Cyprus", "CX"=>"Christmas Island", "CR"=>"Costa Rica", "CW"=>"Curacao", "CV"=>"Cape Verde", "CU"=>"Cuba", "SZ"=>"Swaziland", "SY"=>"Syria", "SX"=>"Sint Maarten", "KG"=>"Kyrgyzstan", "KE"=>"Kenya", "SS"=>"South Sudan", "SR"=>"Suriname", "KI"=>"Kiribati", "KH"=>"Cambodia", "KN"=>"Saint Kitts and Nevis", "KM"=>"Comoros", "ST"=>"Sao Tome and Principe", "SK"=>"Slovakia", "KR"=>"South Korea", "SI"=>"Slovenia", "KP"=>"North Korea", "KW"=>"Kuwait", "SN"=>"Senegal", "SM"=>"San Marino", "SL"=>"Sierra Leone", "SC"=>"Seychelles", "KZ"=>"Kazakhstan", "KY"=>"Cayman Islands", "SG"=>"Singapore", "SE"=>"Sweden", "SD"=>"Sudan", "DO"=>"Dominican Republic", "DM"=>"Dominica", "DJ"=>"Djibouti", "DK"=>"Denmark", "VG"=>"British Virgin Islands", "DE"=>"Germany", "YE"=>"Yemen", "DZ"=>"Algeria", "US"=>"United States", "UY"=>"Uruguay", "YT"=>"Mayotte", "UM"=>"United States Minor Outlying Islands", "LB"=>"Lebanon", "LC"=>"Saint Lucia", "LA"=>"Laos", "TV"=>"Tuvalu", "TW"=>"Taiwan", "TT"=>"Trinidad and Tobago", "TR"=>"Turkey", "LK"=>"Sri Lanka", "LI"=>"Liechtenstein", "LV"=>"Latvia", "TO"=>"Tonga", "LT"=>"Lithuania", "LU"=>"Luxembourg", "LR"=>"Liberia", "LS"=>"Lesotho", "TH"=>"Thailand", "TF"=>"French Southern Territories", "TG"=>"Togo", "TD"=>"Chad", "TC"=>"Turks and Caicos Islands", "LY"=>"Libya", "VA"=>"Vatican", "VC"=>"Saint Vincent and the Grenadines", "AE"=>"United Arab Emirates", "AD"=>"Andorra", "AG"=>"Antigua and Barbuda", "AF"=>"Afghanistan", "AI"=>"Anguilla", "VI"=>"U.S. Virgin Islands", "IS"=>"Iceland", "IR"=>"Iran", "AM"=>"Armenia", "AL"=>"Albania", "AO"=>"Angola", "AQ"=>"Antarctica", "AS"=>"American Samoa", "AR"=>"Argentina", "AU"=>"Australia", "AT"=>"Austria", "AW"=>"Aruba", "IN"=>"India", "AX"=>"Aland Islands", "AZ"=>"Azerbaijan", "IE"=>"Ireland");
	public static $country_code = '';
	public static $country_name = '';


	public static function init() 
	{
		global $config;

		if (self::$initialized) {
			return;
		}

		self::$initialized = true;

		self::$data['capchas_left'] = 0;
		self::$data['posts_left'] = 0;
		self::$data['posts_max'] =$config['tor']['max_posts'];
		
		self::$data['create'] = time();

		self::$ip = $_SERVER['REMOTE_ADDR'];
		self::$ip_range = self::$ip;
		self::$is_onion = in_array($_SERVER['REMOTE_ADDR'], $config['tor_service_ips']);
		self::$is_i2p = in_array($_SERVER['REMOTE_ADDR'], $config['i2p_service_ips']);
		self::$is_darknet = (self::$is_onion || self::$is_i2p); 

		if (isset($_COOKIE[$config['cookies']['general']])) {
			self::$cookie_key = $_COOKIE[$config['cookies']['general']];
		}

		if (self::$is_darknet) {
			self::$country_code = 'T1';
			self::$country_name = 'Tor Network';
		} elseif (isset($config['geoip_cloudflare_enable'], $_SERVER['HTTP_CF_IPCOUNTRY']) && $config['geoip_cloudflare_enable']){
			
			self::$country_code = $_SERVER['HTTP_CF_IPCOUNTRY'];
			self::$country_name = isset(self::$country_data[$country_code]) ? self::$country_data[$country_code] : 'Unkhown';

			if (self::$country_code == "T1") {
				self::$is_onion = true;
				self::$is_darknet = true;
			}
			
		} elseif (!self::$is_darknet && isset($config['geoip_nginx_enable'], $_SERVER['GEOIP_COUNTRY_CODE'], $_SERVER['GEOIP_COUNTRY_NAME']) && $config['geoip_nginx_enable']){
			self::$country_code = $_SERVER['GEOIP_COUNTRY_CODE'];
			self::$country_name = $_SERVER['GEOIP_COUNTRY_NAME'];
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
		$darknet_cap = $config['captcha']['antispam']['enable_darknet'];

		if(self::$is_darknet && $darknet_cap > 0){
			return ($darknet_cap > self::$data['capchas_left']) ? 0 : 1; 
		}
		
		if(!self::$is_darknet && $www_cap > 0){
			return ($www_cap > self::$data['capchas_left']) ? 0 : 1; 
		}

		return -1;
	}

	/* Загружает информацию о пройденных проверках из кэша или базы данных */
	public static function load()
	{

		if (self::$cookie_key == null) {
			return;
		}
		
		// load from cache
		$data = Cache::get(self::$cookie_key);

		// load from base
		if(!is_array($data)){
 
			$query = prepare("SELECT `data` FROM `cookie` WHERE `key` = :cookie");
			$query->bindValue(':cookie', self::$cookie_key, PDO::PARAM_STR);
			$query->execute() or error(db_error($query));
			$db_data = $query->fetch(PDO::FETCH_ASSOC);

			if ($db_data) { 
				$data = json_decode($db_data['data'], TRUE);
				Cache::set(self::$cookie_key, self::$data, self::$cache_time);
			}
		}

		// data not found
		if(!is_array($data)){
			return;
		}


		// check post limit
		if($data['posts_left'] >= $data['posts_max']){

			$data = Cache::delete(self::$cookie_key);
			
			$query = prepare("DELETE FROM `cookie` WHERE `key` = :cookie");
			$query->bindValue(':cookie', self::$cookie_key, PDO::PARAM_STR);
			$query->execute() or error(db_error($query));

		} else {
			self::$data = $data;
		}



	}

	public static function save()
	{
		global $config, $pdo;

		if (self::$cookie_key == NULL) {
			self::$cookie_key = '!c' . bin2hex(random_bytes(8));
			self::$data['create'] = time(); 
			setcookie($config['cookies']['general'], self::$cookie_key, time()+60*60*24*30);
		}

		Cache::set(self::$cookie_key, self::$data, self::$cache_time);

		$query = prepare("INSERT INTO `cookie` (`key`, `created`, `data`, `last`) VALUES (:key, NOW(), :data, NOW())" . 
			" ON DUPLICATE KEY UPDATE `data`=:data, `last`=NOW()");
		$query->bindValue(':key', self::$cookie_key, PDO::PARAM_STR);
		$query->bindValue(':data', json_encode(self::$data));
		$query->execute() or error(db_error($query));
	}

	public static function captchaSolved()
	{
		self::$data['capchas_left']++;
		self::save();
	}

	public static function IncreasePost()
	{
		if (self::$is_darknet && self::$cookie_key != NULL) {
			self::$data['posts_left']++;
			self::save();
		}
	}

	public static function isAllowPost()
	{
		global $config;

		if (self::$is_darknet) {
			return self::$data['capchas_left'] >= $config['tor']['need_capchas'];
		}
		
		return true;
	}

	public static function isAllowVote()
	{

		global $config;

		if (!$config['polls']['enable']) {
			return false;
		}

		if (self::$is_darknet && !$config['polls']['darknet_enable']) {
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

		if (self::$is_darknet) {
			return self::$cookie_key;
		}

		switch ($config['security_mode']) {
			case 1:
				$hash = sha1(sha1(self::$ip) . $config['secure_salt']);
				return '!s1' .  substr($hash, 0, 16);
			case 2:
				// now no used
				return '!s2' . self::encrypt(self::$ip);
			default:
				return self::$ip;
		}
		
	}

	public static function getIdentityRange()
	{
		return self::getIdentity();
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
	
	private static function encrypt($string, $key = NULL)
	{
		global $config;

		if ($key == NULL) {
			$key = self::getKey();
		}
		
        $iv = $config['secure_salt'] ?? 'none';
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $key );
        $iv = substr( hash( 'sha256', $iv ), 0, 16 );

        return base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
    }

	private static function decrypt($string, $key = NULL)
	{
		global $config;

		if ($key == NULL) {
			$key = self::getKey();
		}
			
        $iv = $config['secure_salt'] ?? 'none';
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $key );
        $iv = substr( hash( 'sha256', $iv ), 0, 16 );
     
        return openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
    }



}
 



