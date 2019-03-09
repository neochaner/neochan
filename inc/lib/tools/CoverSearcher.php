<?php



class CoverSearcher {  

    public static $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36';
    public static $timeout = 1200;

    
    public static function SearchAt($track, $album, $artist){



    }


    public static function Search($query, $artist = null){

        if (!function_exists('curl_init'))
            return NULL;

        $release_data = self::requestReleaseData($query, $artist);
        $mbid = self::parseIdFromJSON($release_data);

        
        if($mbid !== null) {

            $img_url = self::getImageUrlByMBID($mbid);

            $ch = curl_init ($img_url);
        
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, self::$agent);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::$timeout*2);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, self::$timeout*2);
        
            $raw=curl_exec($ch);
            curl_close ($ch);

            if($raw == NULL || strlen($raw) < 1048){
                return NULL;
            }

            return $raw;
        }


        return NULL;

    } 

    public static function requestReleaseData($query, $artist = null) {

        if($artist) {
            $url = 'http://musicbrainz.org/ws/2/recording/?query=' . urlencode($query . ' AND artist:' . $$artist);
        } else {
            $url = 'http://musicbrainz.org/ws/2/recording/?query=' . urlencode($query);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_USERAGENT, self::$agent);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::$timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, self::$timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept:application/json'
        ));
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public static function checkIfAlbumCoverAvailable($id) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://coverartarchive.org/release/' . $id . '/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_USERAGENT, self::$agent);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::$timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, self::$timeout);


        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($httpcode === 200) {
            return true;
        }

        return false;
    }

    public static function parseIdFromJSON($json) {
        $response = json_decode($json, true);
        $releases = $response['recordings'][0]['releases'];

        foreach($releases as $release) {
            if(self::checkIfAlbumCoverAvailable($release['id']) === true) {
                return $release['id'];
            }
        }

        if(intval($response['count']) > 0) {
            return $response['recordings'][0]['releases'][0]['id'];
        }

        return null;
    }

    public static function getImageUrlByMBID($mbid) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://coverartarchive.org/release/' . $mbid . '/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_USERAGENT, self::$agent);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::$timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, self::$timeout);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if($httpcode !== 200) {
            return null;
        }

        $image_data = json_decode($response, true);

        if(count($image_data['images']) > 0) {
            if(array_key_exists('250', $image_data['images'][0]['thumbnails'])) {
                return $image_data['images'][0]['thumbnails']['250'];
            } else {
                return $image_data['images'][0]['image'];
            }
        } else {
            return null;
        }
    }
}



?>