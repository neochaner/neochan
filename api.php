<?php
require_once "./inc/functions.php";
require_once './inc/mod/pages.php';
require_once 'inc/lib/webm/ffmpeg.php';

if (get_magic_quotes_gpc()) {
	function strip_array($var) {
		return is_array($var) ? array_map('strip_array', $var) : stripslashes($var);
	}
	
	$_GET = strip_array($_GET);
	$_POST = strip_array($_POST);
}



if (isset($_POST['login'])) {
    mod_login();
} else if (isset($_POST['register'])) {

    server_response("Temporay Disabled", array());
    
    $data = "<script src='https://www.google.com/recaptcha/api.js' async defer></script>";
    $data .= "<div class='g-recaptcha' ID='recaptcha' data-sitekey='{$config['captcha']['recaptcha_public_key']}'></div>";

  
    if (!preg_match('/^[A-Za-z0-9_]+$/', $_POST['username'])) {
        json_response(array('invalid_username_1' => true));
    }
        
    if (!isset($_POST['username']) || strlen($_POST['username']) < 5) {
        json_response(array('invalid_username_2' => true));
    }
        
    if (!isset($_POST['password']) || strlen($_POST['password']) < 5) {
        json_response(array('invalid_password_1' => true));
    }

 
    $username =  $_POST['username'];
    $password =  $_POST['password']; 

    // check username exists
    $query = prepare('SELECT ``username`` FROM ``mods`` WHERE ``username`` = :username');
    $query->bindValue(':username', $username);
    $query->execute() or error(db_error($query));
    $users = $query->fetchAll(PDO::FETCH_ASSOC);

    if (sizeof($users) > 0) {
        json_response(array('invalid_username_3' => true));
    }

    $salt = generate_salt();
    $password = hash('sha256', $salt . sha1($password));

    $query = prepare('INSERT INTO ``mods`` VALUES (NULL, :username, :password, :salt, :type, :boards, :email)');
    $query->bindValue(':username', $username);
    $query->bindValue(':password', $password);
    $query->bindValue(':salt', $salt);
    $query->bindValue(':type', 0);
    $query->bindValue(':boards', '');
    $query->bindValue(':email', '');
    $query->execute() or error(db_error($query));
        
    json_response(array('register_success'=>true));


} else if (isset($_POST['logout'])) {
    mod_logout();
} else if (isset($_POST['update_profile'])) {
    profile_update();
} else if (isset($_POST['logout'])) {
    mod_logout(false);
} else if (isset($_REQUEST['vote'])) {
 
    global $config, $board;

    if (!isset($_REQUEST['board']) || !openBoard($_REQUEST['board'])) {
        server_reponse('No board', array('success'=>false, 'error'=>'l_error_noboard'));
    }

    if (Session::isAllowVote()) {
        server_reponse('No access', array('success'=>false, 'error'=>'l_error_noaccess'));
    }

    $query = prepare(sprintf("SELECT * FROM `posts_%s` WHERE `id` = :id", $board['uri']));
    $query->bindValue(':id', (int)$_REQUEST['post'], PDO::PARAM_INT);
    $query->execute() or error(db_error($query));

    if ($post = $query->fetch(PDO::FETCH_ASSOC)) {

        if (is_string($post['poll']) == NULL) {
            server_reponse('Poll not found', array('success'=>false, 'error'=>'l_poll_notfound'));
        }

        $post['poll'] = json_decode($post['poll'], TRUE);
        $vote = (int)$_REQUEST['vote'];

        if (count($post['poll']) <= $vote) {
            server_reponse('Invalid vote number', array('success'=>false, 'error'=>'l_invalidvote'));
        }

        // check double vote
        if (in_array(Session::getIdentity(), $post['poll'][$vote]['ids'])) {
            server_reponse('Already voted', array('success'=>false, 'error'=>'l_alreadyvoted'));
        }
        
        $post['poll'][$vote]['ids'][] = Session::getIdentity();

        if (isset($_REQUEST['media']) && (int)$_REQUEST['media'] == 0) {
            $post['poll'][$vote]['votes']++;
        }

        recalculate_votes($post['poll']);

        
	    $template = get_post_template($post);
        
        $query = prepare(sprintf("UPDATE `posts_%s` SET `template`=:template, `poll`=:poll, `changed_at` = UNIX_TIMESTAMP(NOW()) WHERE `id` = :id", $board['uri']));
        $query->bindValue(':poll', json_encode($post['poll']), PDO::PARAM_STR);
        $query->bindValue(':id', (int)$_REQUEST['post'], PDO::PARAM_INT);
        $query->bindValue(':template', $template, PDO::PARAM_STR);
        $query->execute() or error(db_error($query));


        buildThread($post['thread'] ? $post['thread'] : (int)$_REQUEST['post']); 

        if (isset($_REQUEST['media']) && (int)$_REQUEST['media'] == 1) {
            server_reponse('Already voted!', array('success'=>true, 'info'=>'l_alreadyvoted'));
        }

        server_reponse('Voted!', array('success'=>true, 'info'=>'l_vote_success'));
    } else {
        server_reponse('Invalid post', array('success'=>false, 'error'=>'l_invalidpost'));
    }

}
else if (isset($_POST['get_playlists'], $_POST['board'])){

    if (!isset($_POST['board']) || !openBoard($_POST['board'])) {
        server_reponse('No board', array('success'=>false, 'error'=>'l_error_noboard'));
    }

    Neotube::init($_POST['board'], '0');
    $list = Neotube::getBoardPlaylists();

    if ($list == null || count($list) == 0) {
        json_response(array('success'=> false, 'error' => 'l_board_notubes'));
    } else {

        $html='';

        foreach ($list as $track) {
            $html .= "<a href='{$track['link']}?#neotube' style='display:block;padding: 10px;'>#{$track['thread']} - {$track['title']}</a>";
        }

        json_response(array('success'=> true, 'html' => $html));
    }


} else {
    json_response(array('alert'=> 'unkhown action'));
}




function profile_update()
{
    global $mod, $config;

    $data = check_profile();

    if (!is_array($data)){

        json_response(array(
            'success'=> false, 
            'update_profile'=>true,
            'auth' => false,
            'data' => array(),
        ));
    }

    $permissions = array(
        'show_id' => $mod['type'] < $config['mod']['show_ip'],
        'show_id_less' => $mod['type'] < $config['mod']['show_ip_less'],
       /*'manageusers' => $mod['type'] < $config['mod']['manageusers'],
        'noticeboard_post' => $mod['type'] < $config['mod']['noticeboard_post'],
        'search' => $mod['type'] < $config['mod']['search'],
        'clean_global' => $mod['type'] < $config['mod']['clean_global'],
        'modlog' => $mod['type'] < $config['mod']['modlog'],
        'mod_board_log' => $mod['type'] < $config['mod']['mod_board_log'],
        'boardvolunteer_board_log' => $mod['type'] < $config['mod']['boardvolunteer_board_log'],
        'editpost' => $mod['type'] < $config['mod']['editpost'],
        'edit_banners' => $mod['type'] < $config['mod']['edit_banners'],
        'edit_assets' => $mod['type'] < $config['mod']['edit_assets'],
        'edit_flags' => $mod['type'] < $config['mod']['edit_flags'],
        'edit_settings' => $mod['type'] < $config['mod']['edit_settings'],
        'edit_volunteers' => $mod['type'] < $config['mod']['edit_volunteers'],
        'edit_tags' => $mod['type'] < $config['mod']['edit_tags'],
        'clean' => $mod['type'] < $config['mod']['clean'],
        'bandeletebyid' => $mod['type'] < $config['mod']['bandeletebyip'],
        'bandeletebyid_thread' => $mod['type'] < $config['mod']['bandeletebyip_thread'],
        'ban' => $mod['type'] < $config['mod']['ban'],
        'bandelete' => $mod['type'] < $config['mod']['bandelete'],
        'unban' => $mod['type'] < $config['mod']['unban'],
        'deletebyid' => $mod['type'] < $config['mod']['deletebyip'],
        'sticky' => $mod['type'] < $config['mod']['sticky'],
        'cycle' => $mod['type'] < $config['mod']['cycle'],
        'lock' => $mod['type'] < $config['mod']['lock'],
        'postinlocked' => $mod['type'] < $config['mod']['postinlocked'],
        'bumplock' => $mod['type'] < $config['mod']['bumplock'],
        'view_bumplock' => $mod['type'] < $config['mod']['view_bumplock'],
        'bypass_field_disable' => $mod['type'] < $config['mod']['bypass_field_disable'],
        'view_banlist' => $mod['type'] < $config['mod']['view_banlist'],
        'view_banstaff' => $mod['type'] < $config['mod']['view_banstaff'],
        'public_ban' => $mod['type'] < $config['mod']['public_ban'],
        'recent' => $mod['type'] < $config['mod']['recent'],
        'ban_appeals' => $mod['type'] < $config['mod']['ban_appeals'],
        'view_ban_appeals' => $mod['type'] < $config['mod']['view_ban_appeals'],
        'view_ban' => $mod['type'] < $config['mod']['view_ban'],
        'deletefilebyid' => $mod['type'] < $config['mod']['deletefilebyip'],
        'deletefilebyid_thread' => $mod['type'] < $config['mod']['deletefilebyip_thread'],
        'reassign_board' => $mod['type'] < $config['mod']['reassign_board'],
        'move' => $mod['type'] < $config['mod']['move'],
        'pm_all' => $mod['type'] < $config['mod']['pm_all'],*/


    );


    $data['success']= true;
    $data['auth'] = true;
    $data['type'] = (int)$data['type'];
    $data['update_profile'] = true;
    
    $result = array_merge($data,  $permissions);

    json_response( $result );
}

function recalculate_votes(&$poll)
{

    $votes = 0;
    foreach ($poll as $variant) {
        $votes += $variant['votes'];
    }

    if ($votes == 0) {
        return;
    }

    foreach ($poll as &$variant) {
        $variant['percent'] = (int)(($variant['votes'] / $votes) * 100);
    }
}














