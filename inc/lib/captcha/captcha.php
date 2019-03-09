<?php

include_once 'inc/lib/captcha/cool-php-captcha-0.3.1/captcha.php';

class chanCaptcha {

	public static function get() {

		global $config;

		$captcha = chanCaptcha::generate_captcha();
		$cookie  = $captcha['cookie'];
		$html    = $captcha['html'];

		$query = "";
		$id = self::get_id($_GET, $query);
		
		if(empty($query))
			$query='board=&thread';

		setcookie($id, $cookie, time() + $config['captcha']['expires_in']);
		echo '<a title="' . _('Click to update') . '" href="?' . $query . '" id="captcha_img">' . $html . '</a>';
		exit;
	}

	public static function check() {
		if (!isset($_POST['captcha_text'])) {
			return false;
		}

		$id = self::get_id($_POST);

		if (!isset($_COOKIE[$id])) {
			return false;
		}

		$result = cache::get('captcha_' . $_COOKIE[$id]);

		if ($result && $result == strtolower($_POST['captcha_text'])) {
			setcookie($id, NULL);
			return true;
		}

		return false;
	}

	public static function get_id($method, &$query=null) {
		$id = 'captcha';
		$q = [];

		if (isset($method['board']) && !empty($method['board'])) {			
			$id .= '_' . strval($method['board']);
			$q[] = "board=".strval($method['board']);
		}

		if(!is_null($query))
			$query = implode('&', $q);

		return preg_replace('/\W/', '', $id); // remove any "non-word" character
	}

	public static function generate_captcha() {

		global $config;

		$text = self::rand_string($config['captcha']['length'], $config['captcha']['extra']);

		$captcha         = new SimpleCaptcha();
		$captcha->width  = $config['captcha']['width'];
		$captcha->height = $config['captcha']['height'];

		$cookie = chanCaptcha::rand_string(16, "abcdefghijklmnopqrstuvwxyz");

		ob_start();
		$captcha->CreateImage($text);
		$image = ob_get_contents();
		ob_end_clean();
		$html = '<img src="data:image/png;base64,' . base64_encode($image) . '" border="0">';

		cache::set('captcha_' . $cookie, $text, $config['captcha']['expires_in'] + 10);

		return array("cookie" => $cookie, "html" => $html, "raw_image" => $image);
	}
	

	public static function rand_string($length, $charset) {
		$ret = "";
		while ($length--) {
			$ret .= mb_substr($charset, mt_rand(0, mb_strlen($charset, 'utf-8') - 1), 1, 'utf-8');
		}
		return $ret;
	}




}
