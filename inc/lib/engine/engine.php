<?php



class Engine {
	
	private $www_dir;
	private $css_time=true;
	private $js_time=true;
	
	public $favicon = false;
	
	private $head_tags = array();
	private $head_noscript_tags = array();
	private $header = '';
	private $footer = '';
	private $js_code='';
	private $js_links = '';
	private $title="";
	
	public function __construct($www_dir, $css_time=true, $js_time = true){
	
		$this->www_dir = $www_dir;
		$this->css_time = $css_time;
		$this->js_time = $js_time;
		$this->insert_head('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">');
	}

	

	public function insert_head($tag){
		$this->head_tags[] = $tag;
	}
	
	public function insert_head_css($url, $id=false, $noscript = false){
		
		$time = "";
		
		if($this->css_time && file_exists($this->www_dir . $url)){
			$url .= ('?' . filemtime($this->www_dir . $url));
		}

		$css = '<link rel="stylesheet"' . ($id ? ' id="'.$id.'" ' : '') .  'type="text/css" href="'.$url . $time .'">';
	
			
		if($noscript)
			$this->head_noscript_tags[] = $css;
		else 
			$this->head_tags[] = $css;
	}
	
	public function insert_footer_css($url, $id=false){
		
		$time = "";
		
		if($this->css_time && file_exists($this->www_dir . $url)){
			$url .= ('?' . filemtime($this->www_dir . $url));
		}

		$this->footer .= '<link rel="stylesheet"' . ($id ? ' id="'.$id.'" ' : '') .  'type="text/css" href="'.$url . $time .'">';

	}
	
	

	public function insert_js($text){
		$this->js_code .= $text;
	}
	
	public function insert_js_link($url){
		
		$query = '';
		
		if($this->js_time && file_exists($this->www_dir . $url))
			$query = ('?'.filemtime($this->www_dir . $url));
		
		$this->js_links .= ('<script type="text/javascript" src="'.$url.$query.'"></script>');
		
	}
	
	public function title($text){
		$this->title = $this->utf8tohtml("<title>$text</title>");
	}
	
	public function insert_header($tag){
		$this->header .= $tag;
	} 
	
	public function insert_footer($text){
		$this->footer .= $text;
	}
	
	
	
	
	
	
	
	public function array_assoc_map($array, $a='', $b='', $c=''){
		
		$result="";
		
		foreach($array as $key=>$value){
			$result .= ($a . $key . $b . $value . $c);
		}
		
		return $result;
	}
	
	
	public function display(){
		
		$head = "";
		$js ="";
		$header = $this->header;
		$body="";
		$main="";
		$footer=$this->footer;
		
		
		if($this->favicon)
			$head = '<link rel="shortcut icon" href="'. $this->favicon . '">';
		
		foreach($this->head_tags as $tag)
			$head .= $tag;
			
		if(count($this->head_noscript_tags)>0){
			$head .= '<noscript>';
			
			foreach($this->head_noscript_tags as $tag)
				$head .= $tag;
				
			$head .= '</noscript>';	
		}
		
		if(!empty($this->js_code)){
			$js .= '<script>' . $this->js_code . '</script>';
		}
		
	
		$js .= $this->js_links;
		
		
		
		
		
		
		
		
		
		
		

		return "<!doctype html><html>\n<head>$head</head>\n$js\n<body>\n<header>$header</header>\n<main>$main</main>\n<footer>$footer</footer>\n</body>\n</html>";
		
	}
	
	
	private function utf8tohtml($utf8, $remove_extented=false) {

		$utf8 = htmlspecialchars($utf8, ENT_NOQUOTES, 'UTF-8');

		if($remove_extented)
			$utf8 = mb_encode_numericentity($utf8, array(0x010000, 0xffffff, 0, 0xffffff), 'UTF-8');

		return $utf8;
	}

}





























































































































?>