<?php
require_once 'inc/session.php'; 

class nban 
{


   public static function getid($id){

        $query = prepare('SELECT * FROM `nbans` WHERE id=:id');
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));

        if($ban = $query->fetch(PDO::FETCH_ASSOC)){
            return $ban;
        }

        return null;

   }

   public static function send_appear($id, $text){

        $query = prepare('UPDATE `nbans` SET `appeal_time`=:time, `appeal_state`=1,`appeal_text`=:text WHERE `id`=:id');
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->bindValue(':text', utf8tohtml($text), PDO::PARAM_STR);
        $query->bindValue(':time', time(), PDO::PARAM_INT);

        $query->execute() or error(db_error($query));

   }



    public static function check($thread, &$refban){

        global $config, $board;

        $checkopban = "";
        $optrip = NULL;

        if($config['opmod']['enable'] && $thread){
 
            $query = prepare("SELECT `trip` FROM `posts_{$board['uri']}` WHERE `id` = :thread");
            $query->bindValue(':thread', $thread, PDO::PARAM_INT);
            $query->execute() or error(db_error($query));
    
            if($thread = $query->fetch(PDO::FETCH_ASSOC)){

                if($thread['trip'] != NULL && strlen($thread['trip']) > 0){
                    $checkopban = "OR `board`=:optrip";
                    $optrip = $thread['trip'];
                }
            }
        }


        if(session::$is_onion || session::$is_i2p){

            $query = prepare('SELECT * FROM `nbans` WHERE ' .
            '(`expires` IS NULL OR `expires` > :time) AND ' .
            "(`board` IS NULL OR `board`=:board $checkopban) AND " .
            '`ident`=:ident' );
   
            $query->bindValue(':time', time(), PDO::PARAM_INT);
            $query->bindValue(':ident', session::GetIdentity(), PDO::PARAM_STR);
        }
        else{

            $query = prepare('SELECT * FROM `nbans` WHERE ' .
            '(`expires` IS NULL OR `expires` > :time) AND ' .
            "(`board` IS NULL OR `board`=:board $checkopban) AND " .
            '(`ident`=:ip OR `ident`=:iprange OR `ident`=:ident)');
   
            $query->bindValue(':time', time(), PDO::PARAM_INT);
            $query->bindValue(':ip', session::$ip, PDO::PARAM_STR);
            $query->bindValue(':iprange', session::$ip_range, PDO::PARAM_STR);
            $query->bindValue(':ident', session::GetIdentity(), PDO::PARAM_STR);
        }
		
		$query->bindValue(':board', $board['uri'], PDO::PARAM_STR);

        if($optrip){
            $query->bindValue(':optrip', $optrip, PDO::PARAM_STR);
		}
      
        $query->execute() or error(db_error($query));
        $refban = $query->fetchAll(PDO::FETCH_ASSOC);

        return (count($refban) > 0);

    }

    public static function purge() {
        global $config;

        $query = prepare("DELETE FROM `nbans` WHERE `expires` IS NOT NULL AND `expires` < :time");
        $query->bindValue(':time', time(), PDO::PARAM_INT);
        $query->execute() or error(db_error($query));
        if (!$config['cron_bans']) rebuildThemes('bans');
    }

    public static function new($board, $post, $reason, $banTimeSec, $withSubnet = false){

        global $config, $mod, $pdo;

        if($reason)
            $reason = escape_markup_modifiers($reason);

        $query = prepare("SELECT * FROM `posts_$board` WHERE `id` = :post");
        $query->bindValue(':post', $post, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));


        if($post = $query->fetch(PDO::FETCH_ASSOC)){
 
            $ident = $post['ip'];
			

            // cookie
            if($post['ip'][1] == 'i' && $post['ip'][2] == '1'){

                $ident = session::Decrypt(substr($post['ip'], 4));
            }
            // ci2
            else if($post['ip'][1] == 'i' && $post['ip'][2] == '2'){

                $ident = session::Decrypt(substr($post['ip'], 4));
            } 

            if(!$ident)
                $ident = $post['ip'];

            //if($ident == NULL || strpos($ident != '127.0.0') !== FALSE)
             //   return false;
                        
            $query = prepare("INSERT INTO `nbans` VALUES (NULL, :ident, :created, :expires, :board, :creator, :reason, 0, :post, 0, NULL, 0)");

            $query->bindValue(':ident',  $ident, PDO::PARAM_STR);
            $query->bindValue(':created', time(), PDO::PARAM_INT);
            $query->bindValue(':expires', $banTimeSec == 0 ? NULL : time() + $banTimeSec, PDO::PARAM_INT);
            $query->bindValue(':board', is_opmod() ? $mod['username'] : $board, PDO::PARAM_STR);
            $query->bindValue(':creator', $mod['username'], PDO::PARAM_STR);
            $query->bindValue(':reason', $reason, PDO::PARAM_STR);
            $query->bindValue(':post', json_encode($post), PDO::PARAM_STR);

            $query->execute() or error(db_error($query));

            return true;
        }

        return false;

    }


    public static function delete($ban_id){


        global $config, $mod, $pdo, $board;
                
        $query = prepare("SELECT * FROM `nbans` WHERE `id` = :id");
        $query->bindValue(':id', $ban_id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));
 
        if ($ban = $query->fetch(PDO::FETCH_ASSOC)){

            $op_rights = is_opmod() && $ban['creator'] == $mod['username'];
            $mod_rights = isset($mod['boards']) && (in_array($mod['boards'], $ban['board']) || $mod['boards'][0] == '*');

            if($op_rights || $mod_rights){

                $query = prepare("DELETE FROM `nbans` WHERE `id` = :id");
                $query -> bindValue(':id', $ban_id, PDO::PARAM_INT);
                $query->execute() or error(db_error($query));
            
                return true;
            }
        }

        return false;
        
    
    }


    public static function is_hashban($board_uri, $filesize, $md5){


        $query = prepare('SELECT `board` FROM `banned_files` WHERE `size`=:size AND `md5`=:md5');
        $query->bindValue(':size', $filesize, PDO::PARAM_INT);
        $query->bindValue(':md5', $md5, PDO::PARAM_STR);

        $query->execute() or error(db_error($query));

        if($board = $query->fetch(PDO::FETCH_ASSOC)){

            if($board['board'] == '*' || $board['board'] == NULL || $board_uri == $board['board'])
                return true;
        }

        return false;

    }

    public static function add_hashban($board_uri, $filesize, $md5){

        global $config, $mod;

        if(self::is_hashban($board_uri, $filesize, $md5))
            return false;

        $query = prepare("INSERT INTO `banned_files` VALUES (NULL, :md5, :size, :board, :created, :creator)");
        
        $query->bindValue(':md5', $md5, PDO::PARAM_STR);
        $query->bindValue(':size', $filesize, PDO::PARAM_INT);
        $query->bindValue(':board', $board_uri, PDO::PARAM_STR);

        $query->bindValue(':created', time(), PDO::PARAM_INT);
        $query->bindValue(':creator', $mod['username'], PDO::PARAM_STR);

        $query->execute() or error(db_error($query));

        return true;


    }


} 












