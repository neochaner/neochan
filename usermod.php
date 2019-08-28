<?php
require 'inc/functions.php';
require 'inc/mod/pages.php';

Session::AllowNoIPUsers();


$query = isset($_SERVER['QUERY_STRING']) ? rawurldecode($_SERVER['QUERY_STRING']) : '';
$pages = array(

    '/([0-9a-zA-Z]{1,58})\/res\/(\d+)\.html\/view\/(\w+)/'                   => 'view',          // view thread
    '/([0-9a-zA-Z]{1,58})\/res\/(\d+)\.html\/delete\/(\d+)\/(\w+)/'          => 'delete',        // delete post
    '/([0-9a-zA-Z]{1,58})\/res\/(\d+)\.html\/ban\/(\d+)\/(\w+)/'             => 'ban',           // ban poster
    '/([0-9a-zA-Z]{1,58})\/res\/(\d+)\.html\/ban_delete\/(\d+)\/(\w+)/'      => 'ban',    // ban poster
    '/([0-9a-zA-Z]{1,58})\/res\/(\d+)\.html/'                                => 'view',          // view thread
);



foreach ($pages as $uri => $handler) {
	if (preg_match($uri, $query, $matches)) {

        $board_name = $matches[1];
        $thread_id = (int)$matches[2];
        $token = count($matches) > 3 ? $matches[count($matches)-1] : NULL;


        if(!isset($_POST['trip']) && $token == NULL)
            usermod_auth();

        if(!openBoard($board_name))
            error($config['error']['noboard']);
 
        if(!$config['opmod']['enable'])
            error("OPMOD IS DISABLED");

        if(isset($_POST['trip'])){
            $result = generate_tripcode($_POST['trip']);
            $trip = $result[1];
            $token = usermod_get_token($board['uri'], $thread_id, $trip);
            array_push($matches, $token);
        } 
        else if($token != NULL){
            usermod_check_token($board['uri'], $thread_id, $token);
             
        } else {
            error($config['error']['noaccess']);
        }

 


        if (is_string($handler)){
            unset($matches[0]);
            call_user_func_array("usermod_$handler", $matches);
        } else {
			error("Usermod page '$handler' not a string, and not callable!");
        }
        
        break;
    }
}

error('unkhown error');




function usermod_check_token($board_name, $thread, $token){


    global $config, $board;

    $query = prepare(sprintf('SELECT `id` FROM ``posts_%s`` WHERE `id` = :thread AND `password`=:token' , $board['uri']));
    $query->bindParam(':token', $token, PDO::PARAM_INT);
    $query->bindParam(':thread', $thread, PDO::PARAM_INT);
    $query->execute() or error(db_error($query));

    if($query->fetch(PDO::FETCH_ASSOC) == FALSE)
        error($config['error']['noaccess']);


}

function usermod_get_token($board_name, $thread, $trip)
{
    global $config, $board;

    // make token
    $token = usermod_generate_token($trip);

    $query = prepare(sprintf('UPDATE ``posts_%s`` SET `password`=:token, `ip`=:ip WHERE `id` = :thread AND `trip`=:trip' , $board['uri']));
    $query->bindParam(':token', $token, PDO::PARAM_STR);
    $query->bindParam(':thread', $thread, PDO::PARAM_INT);
    $query->bindParam(':trip', $trip, PDO::PARAM_STR);
    $query->bindParam(':ip', Session::getIdentity(), PDO::PARAM_STR);

    $query->execute() or error(db_error($query));

    if($query->rowCount() <= 0)
        usermod_auth("wrong tripcode");

    return $token;

}

function usermod_auth($warning = '')
{
    global $config, $board;

    if (isset($_POST['disable_error'])) {
        $warning = '';
    }
  
    echo Element('page.html', array(
        'config'              => $config,
        'mod'                 => false,
        'title'               => '',
        'subtitle'            => '',
        'boardlist'           => [],
        'body'                => Element('usermod/login.html', array('error'               => $warning))
    ));

    exit;

}

function usermod_generate_token()
{
    global $config;

    $chrs = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
    $length = 12;
    $str = ''; 
  
    for ($i = 0; $i < $length; $i++) { 
        $index = rand(0, strlen($chrs) - 1); 
        $str .= $chrs[$index]; 
    } 

    $hash = md5($str . $config['hashSalt']);

    return substr($hash, 0, 16);
}

function usermod_view($board_name, $thread_id, $token)
{
    global $config, $board;

    $mod = array('type'=> 0, 'token' => $token);

	$page = buildThread($thread_id, true, $mod);
	echo $page;

    exit;
}

function usermod_delete($board_name, $thread_id, $post_id, $token)
{
    global $config, $board;

    $query = prepare(sprintf('SELECT `id` FROM ``posts_%s`` WHERE `id` = :thread AND `password`=:token AND `ip`=:ip' , $board['uri']));
    $query->bindParam(':thread', $thread_id, PDO::PARAM_INT);
    $query->bindParam(':token', $token, PDO::PARAM_STR); 
    $query->bindParam(':ip', Session::getIdentity(), PDO::PARAM_STR);
    $query->execute() or error(db_error($query));

    if($query->fetch(PDO::FETCH_ASSOC) == FALSE)
        error($config['error']['noaccess']); 


    $query = prepare(sprintf("UPDATE ``posts_%s`` SET `hide`=1, `changed_at` = UNIX_TIMESTAMP(NOW()) WHERE `id` = :id OR `thread` = :id", $board['uri']));
    $query->bindValue(':id', $post_id, PDO::PARAM_INT);
    $query->execute() or error(db_error($query));
 
    buildThread($thread_id);
    buildIndex();
    rebuildTheme('ukko', 'post-delete');
    header("Location: /usermod.php?/$board_name/res/$thread_id.html/view/$token");
    exit;

}

function usermod_ban_delete($board_name, $thread_id, $post_id, $token)
{
    $_POST['delete'] = true;
    usermod_ban($board_name, $thread_id, $post_id, $token);
}

function usermod_ban($board_name, $thread_id, $post_id, $token)
{
    global $config, $board;

    $query = prepare(sprintf('SELECT `trip` FROM ``posts_%s`` WHERE `id` = :thread AND `password`=:token AND `ip`=:ip' , $board['uri']));
    $query->bindParam(':thread', $thread_id, PDO::PARAM_INT);
    $query->bindParam(':token', $token, PDO::PARAM_STR); 
    $query->bindParam(':ip', Session::getIdentity(), PDO::PARAM_STR);
    $query->execute() or error(db_error($query));
    $op = $query->fetch(PDO::FETCH_ASSOC);

    if ($op == FALSE) {
        error($config['error']['noaccess']);
    }

    if(isset($_POST['new_ban'])){
 
        $query = prepare(sprintf('SELECT `ip` FROM ``posts_%s`` WHERE `id` = :post_id' , $board['uri']));
        $query->bindParam(':post_id', $post_id, PDO::PARAM_STR); 
        $query->execute() or error(db_error($query));

        $result = $query->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            error(['post not found']); 
        }
  
        // ban post
        require_once 'inc/mod/ban.php';

        $ip = $result['ip'];
        $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
        $length = isset($_POST['length']) ? $_POST['length'] : '';  

        modLog("[opmod] Create new ban, reason: " . utf8tohtml($_POST['reason']));
        Bans::new_ban($result['ip'], $reason, $length, $op['trip'], -2);         
 
        // public message
        if ($config['opmod']['public_bans']) {

            $_POST['reason'] = preg_replace('/[\r\n]/', '', $_POST['reason']);

            $query = prepare(sprintf('UPDATE ``posts_%s`` SET `body_nomarkup` = CONCAT(`body_nomarkup`, :body_nomarkup) WHERE `id` = :id', $board['uri']));
            $query->bindValue(':id', $post_id, PDO::PARAM_INT);
            $query->bindValue(':body_nomarkup', sprintf("\n<tinyboard ban message>%s</tinyboard>", utf8tohtml($_POST['reason'])), PDO::PARAM_STR);
            $query->execute() or error(db_error($query));

        }

        // delete post
        $query = isset($_SERVER['QUERY_STRING']) ? rawurldecode($_SERVER['QUERY_STRING']) : '';

        if (strpos($query, "ban_delete") !== FALSE) {
            $query = prepare(sprintf("UPDATE ``posts_%s`` SET `hide`=1, `changed_at` = UNIX_TIMESTAMP(NOW()) WHERE `id` = :id OR `thread` = :id", $board['uri']));
            $query->bindValue(':id', $post_id, PDO::PARAM_INT);
            $query->execute() or error(db_error($query));
        }          
        
        
        rebuildPost($post_id);
        buildThread($thread_id);
        buildIndex();
        rebuildTheme('ukko', 'post-delete');

        
        header("Location: /usermod.php?/$board_name/res/$thread_id.html/view/$token");

    } else {

        echo Element('page.html', array(
            'config'              => $config,
            'mod'                 => false,
            'title'               => _('Ban'),
            'subtitle'            => '',
            'boardlist'           => [],
            'body'                => Element('usermod/ban_form.html', array('error'               => $warning))
    ));
         

    }
    
    exit;
 
}
