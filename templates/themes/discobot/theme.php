<?php
include_once $config['dir']['themes'] . '/discobot/info.php';



function discobot_build($action, $settings, $board)
{
	
	global $config;

	if ($settings['boards'] != '*' && !in_array($board, explode(' ', $settings['boards']))) {
		return;
	}

	// excluded boards
	if (in_array($board, explode(' ', $settings['excluded']))) {
		return;
	}
	
	$post = $config['temp']['last-post'];

	if ($settings['report_thread'] &&  $action == 'post-thread') {
		(new DiscoBot($settings['domain'], $settings['webhook'], $board))->EventThread($post);
	}
	
	if ($settings['report_new_user'] &&  $action == 'post') {

		// check is new user_error
		$min_time = time() - (60*60*24*7);
		$query = prepare("SELECT * FROM posts_{$board} WHERE `id` != :id AND `time` > :min_time AND `ip`=:ip LIMIT 1");
		$query->bindParam(':ip', $post['ip'], PDO::PARAM_STR);
		$query->bindParam(':id', $post['id'], PDO::PARAM_INT);
		$query->bindParam(':min_time', $min_time, PDO::PARAM_INT);
		
		if (!$query->execute()) {
			syslog(2, 'discobot_build() FAIL database query!');
			return;
		}
		
		$res = $query->fetch();
		
		if ($res === false) {
			(new DiscoBot($settings['domain'], $settings['webhook'], $board))->EventNewUser($post);
		}
		
	}

}


class DiscoBot
{

	private $domain;
	private $webhook;
	private $board;

	function __construct($domain, $webhook, $board) 
	{
		$this->domain = $domain . '/';
		$this->webhook = $webhook;
		$this->board = $board;
	}

	public function EventThread($post)
	{
		$this->Send("Thread  **/".$this->board."**\n", $post);
	}
	
	public function EventNewUser($post)
	{
		$this->Send("Post  **/".$this->board."**\n", $post);
	}
	
	private function Send($title, $post)
	{
		$text = remove_modifiers($post['body_nomarkup']);

		if(!isset($post['thread'])) {
			$text = $post['subject'] . ' '. remove_modifiers($post['body_nomarkup']);
		}

		$embeds = array();
		if ($post['files']) {
			foreach ($post['files'] as $file) {
				if ($file['is_an_image']){
					$image = $this->domain . $file['file_path'];
					$embeds[] = [ 'type'=>'rich', 'thumbnail' => [ 'url'=> $image ] ];
				}
			}
		}

		$content =  "$title\n```$text```";

		$thread_id 		= isset($post['thread']) ? $post['thread'] : $post['id'];
		$url_board 		=  $this->domain . 'mod.php?/' . $this->board .'/res/'. $thread_id  . '.html#'. $post['id'];
		$url_bandelete 	=  $this->domain . 'mod.php?/' . $this->board  .'/ban&delete/'. $post['id'];

		$embeds[] = ['type'=>'rich', 'description' => "[Open]($url_board)  |  [Ban&Delete]($url_bandelete)"];
		
		$message = array(
			'content' => $content, 
			'tts' => false,
			'embeds' => $embeds,
		);
		
		$this->SendData($message);

	}
	
	private function SendData($json)
	{
		$make_json = json_encode($json );
		$ch = curl_init( $this->webhook );
		curl_setopt( $ch, CURLOPT_POST, 1);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $make_json);
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt( $ch, CURLOPT_HEADER, 0);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec( $ch );
	}
	
}




























