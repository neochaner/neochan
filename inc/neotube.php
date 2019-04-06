<?php

require_once 'inc/lib/webm/ffmpeg.php';

class Neotube 
{
	public static $tmp_path;
    public static $cache_sec = 60;
    public static $paused_delete_sec = 60 * 60 * 24 * 3; // удаление видео поставленных на паузу
    public static $switch_sec = 3;
	public static $board;
	public static $thread_id; 
	public static $nkey; 
	public static $board_key;  

    public static function init($board, $thread_id) 
    {  
        self::$board = $board;
        self::$thread_id = $thread_id;
        self::$nkey = 'nkey_' . $board . '_' . $thread_id;
        self::$board_key = 'nkey_' . $board ; 
        self::$tmp_path = 'tmp/neotube/';
    }

    public static function addYoutubeLink($link)
    {

        global $config, $board, $pdo;

        if(!$config['neotube']['enable'])
            return false;
    
        // check youtube - link
        $pattern1 = "/https?:\/\/(?:www\\.)?(?:youtube\\.com\/).*(?:\\?|&)v=([\\w-]+)/";
        $pattern2 = "/https?:\/\/(?:www\\.)?youtu\\.be\/([\\w-]+)/";
    
    
        $id = null;
    
        if(preg_match($pattern1, $link, $m1))
            $id = $m1[1];
        else
            if(preg_match($pattern2, $link, $m1))
                $id = $m1[1];
    
        if($id == null)
            return false;
    
        $info = GetYoutubeInfo($id);
    
        if(!isset($info['items'][0]['snippet']['title']))
            return false;
    
        if(!isset($info['items'][0]['contentDetails']['duration']))
            return false;
    
        // calculate duration
        $interval = new DateInterval($info['items'][0]['contentDetails']['duration']);
        $seconds = ($interval->s) + ($interval->i * 60) + ($interval->h * 60 * 60) + ($interval->d * 60 * 60 * 24);
        $title = $info['items'][0]['snippet']['title'];
    
        
        if(self::addToPlaylist('youtube', $id, '', $seconds, $title, 'video/mp4', $outputList)){
    
        }
     
        return true;


    }

    public static function addPlaylistFile($file)
    {
        $webminfo = get_webm_info($file['tmp_name']);

        if (!empty($webminfo['error']))
            return false;
    
        $track= array();
    
        $track['path'] = $file['tmp_name'];
        $track['width'] = $webminfo['width'];
        $track['height'] = $webminfo['height'];
        $track['duration'] = floor($webminfo['duration']);

        return self::addToPlaylist('file', 'id' . time() ,  $file['tmp_name'], $track['duration'], $file['name'], $file['mime'], $outputList);
    }

    public static function addToPlaylist($type, $id, $path, $duration_sec, $title, $mime, &$output_list)
    {
        global $config;

        if (!$config['neotube']['enable']) {
            return false;
        }
    
        $query = prepare("SELECT `json` FROM `playlist` WHERE `nkey` = :nkey");
        $query->bindValue(':nkey', self::$nkey, PDO::PARAM_STR);
        $query->execute() or error(db_error($query));
    
        $list = $query->fetch(PDO::FETCH_ASSOC);
        $tracks = null;
        $lastTrackEnd=0;

        if ($list) {
            $tracks = json_decode($list['json'], TRUE);
            $tracks = self::clearPlayList($tracks);
        } 

        if ($tracks==null) {
            $tracks = array();
            $lastTrackEnd=time();
        } else {
            $lastTrackEnd= $tracks[count($tracks)-1]['end'];
        }

        $newTrack = array( 
            'start' => $lastTrackEnd + self::$switch_sec,
            'pause' => -1,
            'duration' => $duration_sec,
            'end'=> $lastTrackEnd + self::$switch_sec + $duration_sec,
            'type'=> $type,
            'path'=> $path,
            'id' => $id,
            'title'=> $title,
            'mime'=>$mime,
        );
    
        array_push($tracks, $newTrack);
    
        $json = json_encode($tracks);
        self::setPlayList($json);
    
        $output_list = $tracks;
        return true;
    }

    public static function removeFromPlaylist($id)
    {
        $list = self::getPlaylist();
    
        if (!is_array($list)) {
            return null;
        }
    
        $nlist = array();
    
        foreach ($list as $track) {
            if($track['id'] == $id)
                unlink($track['path']);
            else
                array_push($nlist, $track);
        }
    
        if (count($list) == count($nlist)) {
            return null;
        }

        $lastEnd = 0;   
    
        // rebuild time...
        for ($i=0; $i<count($nlist); $i++) {
    
            if ($i==0) {
                if($nlist[0]['start'] > time()){
    
                    $nlist[0]['start'] = time()+2;
                    $nlist[0]['end'] = time()+2+$nlist[0]['duration'];
                }
            } else {
                $lastEnd = $nlist[$i-1]['end'];
    
                $nlist[$i]['start'] = $lastEnd + self::$switch_sec;
                $nlist[$i]['end'] = $lastEnd + self::$switch_sec + $nlist[$i]['duration'];
            }
        }
     
        $json = json_encode($nlist);
        self::setPlayList($json);

        return $json;
    }
    
    public static function uploadTrack()
    {
        $file = $_FILES['file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newPath = self::$tmp_path . time() . '.' . $ext;
        $mime = mime_content_type($file['tmp_name']);

        if(!in_array($mime, array('video/webm', 'video/mp4')))
            server_reponse('Тип файла [' . $mime . '] не поддерживается', array('success'=>false, 'error'=>'l_error_extension_not_supported'));

        if(move_uploaded_file($file['tmp_name'], getcwd() . '/' . $newPath)){
 
            $file['tmp_name'] = $newPath;
            $file['path'] = $newPath;
            $file['mime'] = $mime;

            $result = self::addPlaylistFile($file);

            if (!$result) {
                unlink($newPath);
                server_reponse('Произошла ошибка при обработке файла', array('success'=>false, 'error'=>'l_error_process_file'));
            }
            
        } else {
            server_reponse('FAIL', array('success'=>false, 'error'=>'l_error_process_file'));
        }
    
        return true;


    }

    public static function uploadLocalTrack($filename)
    {
        global $config;

        $config['webm']['max_length'] = 9999999;

        $file = array(
            'tmp_name' => "tmp/$filename",
            'path' => "tmp/$filename",
            'name' => basename($filename)
        );

        $mime = mime_content_type($file['tmp_name']);

        if (!file_exists($file['tmp_name'])) {
            server_reponse('File noy dound', array('success'=>false));
        }

        if (!in_array($mime, array('video/webm', 'video/mp4', 'video/x-matroska', 'application/octet-stream'))) {
            server_reponse("Тип файла [ $mime ] не поддерживается", array('success'=>false, 'error'=>'l_error_extension_not_supported'));
        }

        if ($mime == 'application/octet-stream') {
            $mime='';
        }

        $file['mime'] = $mime;


        if (!self::addPlaylistFile($file)) {
            server_reponse('Произошла ошибка при обработке файла', array('success'=>false, 'error'=>'l_error_process_file'));
        }
        
        return true;
    }

    public static function pauseTrack()
    {
        $list = self::getPlaylist();

        if (!is_array($list)) {
            return null;
        }

        if ($list[0]['pause'] == -1) {

            $elapsed = time() - $list[0]['start'];
            $list[0]['pause'] = $elapsed;

        } else {
            // rebuild time...
            for ($i=0, $l=count($list); $i<$l; $i++) {

                if ($i==0) {
                    $list[0]['start'] = time()-$list[0]['pause'];
                    $list[0]['end'] = $list[0]['start'] + $list[0]['duration'];
                    $list[0]['pause'] =-1;
                } else {
                    $lastEnd = $list[$i-1]['end']+2;

                    $list[$i]['start'] = $lastEnd;
                    $list[$i]['end'] = $lastEnd + $list[0]['duration'];
                }
            }
        }

        $json = json_encode($list);
    
        self::setPlayList($json);
        return $json;
    }
    
    public static function setPlayList($json)
    {
        $query = prepare("INSERT INTO `playlist` VALUES (:nkey, :board, :tracks) ON DUPLICATE KEY UPDATE `json`=:tracks");
        $query->bindValue(':nkey', self::$nkey, PDO::PARAM_STR);
        $query->bindValue(':board', self::$board, PDO::PARAM_STR);
        $query->bindValue(':tracks', $json , PDO::PARAM_STR);
        $query->execute() or error(db_error($query));
    
        cache::set(self::$nkey, $json, self::$cache_sec);
        cache::delete(self::$board_key);
    }

    public static function deletePlayList()
    {
        $query = prepare("DELETE FROM `playlist` WHERE `nkey`=:nkey");
        $query->bindValue(':nkey', self::$nkey, PDO::PARAM_STR);
        $query->execute() or error(db_error($query));
    
        cache::delete(self::$nkey);
        cache::delete(self::$board_key);
    }
 
    public static function getPlaylist()
    {
        return json_decode(self::getPlaylistJson(), TRUE);
    }

    public static function getPlaylistJson()
    {
        $json = cache::get(self::$nkey);

        if ($json) {
            return $json;
        }
 
        $query = prepare("SELECT `json` FROM `playlist` WHERE `nkey`=:nkey");
        $query->bindValue(':nkey', self::$nkey, PDO::PARAM_STR);
        $query->execute() or error(db_error($query));
    
        if ($tracks = $query->fetch(PDO::FETCH_ASSOC)) {
            $list = json_decode($tracks['json'], TRUE);
            $nlist = self::clearPlayList($list);

            return json_encode($nlist);
        }
        
        return null;
    }
    
    public static function getThreadName($id)
    {
        if (openBoard(self::$board)) {
 
            $query = prepare(sprintf("SELECT `subject` FROM ``posts_%s`` WHERE `id`=:thread", self::$board));
            $query->bindValue(':thread', $id, PDO::PARAM_INT);
            $query->execute() or error(db_error($query));
            
            if ($thread = $query->fetch(PDO::FETCH_ASSOC)){
                
                if(!empty($thread['subject']) && strlen($thread['subject']) > 3)
                    return $thread['subject'];
            }

        }
 
        return '#' . self::$thread_id;
    }

    public static function getBoardPlaylists()
    {
        $plists = cache::get(self::$board_key);
    
        if($plists) {
            return $plists;
        }

        $query = prepare('SELECT * FROM `playlist` WHERE `board`=:board');
        $query->bindValue(':board', self::$board, PDO::PARAM_STR);
        $query->execute() or error(db_error($query));

        $plists = array();
    
        while ($line = $query->fetch(PDO::FETCH_ASSOC)) {

            $list = json_decode($line['json'], TRUE);
            $nboard = explode('_', $line['nkey'])[1];
            $nthread = explode('_', $line['nkey'])[2];
            $ntitle = self::getThreadName($nthread);

            $clearList = self::clearPlayList($list);
    
            if($clearList != null) {
                $plists[] = array('title' => $ntitle, 'link' => "/$nboard/res/$nthread.html", 'thread'=>$nthread);
            }
        }
  
        cache::set(self::$board_key, $plists, self::$cache_sec);
        return $plists;
    
    }
    
    /*
        Clear videos from playlist

        * return array actually tracks or null
    */
    public static function clearPlayList($list)
    {

        if (!is_array($list) || count($list) == 0) {
            return null;
        }
    
        $newList = array();
        $is_paused =false;

        foreach ($list as $track) {

            if ($track['pause'] != -1) {
                $is_paused = true;
            }

            $endTime = $track['end'] + ($is_paused ? self::$paused_delete_sec : 0);

            if ($endTime < time()) {
                unlink($track['path']);
            } else {
                array_push($newList, $track);
            }
        }

        if (count($list) != count($newList)) {
            if(count($newList) == 0)
                self::deletePlayList();
            else 
                self::setPlayList(json_encode($newList));
        }
            
        return count($newList) > 0 ? $newList : null;
    }
}

