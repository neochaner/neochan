<?php
/*
* ffmpeg.php
* A barebones ffmpeg based webm implementation for vichan.
*/

function get_webm_info($filename) 
{
  global $board, $config;

  $filename = escapeshellarg($filename);
  $ffprobe = $config['webm']['ffprobe_path'];
  $ffprobe_out = array();
  $webminfo = array();

  exec("$ffprobe -v quiet -print_format json -show_format -show_streams $filename", $ffprobe_out);
  $ffprobe_out = json_decode(implode("\n", $ffprobe_out), 1);
  $ffprobe_out = ffprobe_format($ffprobe_out);

  $webminfo['error'] = is_valid_webm($ffprobe_out);

  if(empty($webminfo['error'])) 
  {
    $webminfo['width'] = $ffprobe_out['video_stream']['width'];
    $webminfo['height'] = $ffprobe_out['video_stream']['height'];
    $webminfo['duration'] = $ffprobe_out['format']['duration'];
  }


  if(isset($ffprobe_out['format']['tags']['title']) && strlen($ffprobe_out['format']['tags']['title']) < 1024*5)
  { 
    $webminfo['title'] = str_replace( array( '\'', '"', ',' , ';', '<', '>' ), ' ', $ffprobe_out['format']['tags']['title']);
  }

 
  return $webminfo;
}

function ffprobe_format($ffprobe_out)
{

  foreach($ffprobe_out['streams'] as $stream)
      $ffprobe_out[$stream['codec_type'] . '_stream'] = $stream;
 
  return $ffprobe_out;

}

function is_valid_webm($ffprobe_out) {
  global $board, $config;

  if (empty($ffprobe_out))
    return array('code' => 1, 'msg' => $config['error']['genwebmerror']);

  $extension = pathinfo($ffprobe_out['format']['filename'], PATHINFO_EXTENSION);

  
  if ($extension === 'webm') 
  {
    /*
    if ($ffprobe_out['format']['format_name'] != 'matroska,webm')
    {
      syslog(LOG_WARNING, 'INVALID WEBM 1 -' . json_encode($ffprobe_out));
      return array('code' => 2, 'msg' => $config['error']['invalidwebm']);
    }*/

  } 
  else if ($extension === 'mp4') 
  {
    if ($ffprobe_out['video_stream']['codec_name'] != 'h264' && $ffprobe_out['audio_stream']['codec_name'] != 'aac')
    {
      return array('code' => 2, 'msg' => $config['error']['invalidwebm']);
    }


  } else {
    return array('code' => 1, 'msg' => $config['error']['genwebmerror']);  
  }

  if ((count($ffprobe_out['streams']) > 1) && (!$config['webm']['allow_audio']))
    return array('code' => 3, 'msg' => $config['error']['webmhasaudio']);


  if (empty($ffprobe_out['video_stream']['width']) || empty($ffprobe_out['video_stream']['height']))
  {
    
    return array('code' => 2, 'msg' => $config['error']['invalidwebm']);
  }

  if ($ffprobe_out['format']['duration'] > $config['webm']['max_length'])
    return array('code' => 4, 'msg' => sprintf($config['error']['webmtoolong'], $config['webm']['max_length']));
}

function make_webm_thumbnail($filename, $thumbnail, $width, $height, $duration) {

  global $board, $config;



  $filename = escapeshellarg($filename);
  $thumbnailfc = escapeshellarg($thumbnail); // Should be safe by default but you

  // can never be too safe.
  $ffmpeg = $config['webm']['ffmpeg_path'];

  $ret = 0;
  $ffmpeg_out = array();
  $dur = floor($duration / 2);

  if($duration == "77.340000")
  {
    $dur = 73;
  }

  exec("$ffmpeg -strict -2 -ss " . $dur . " -i $filename -v quiet -an -vframes 1 -vf scale=$width:$height $thumbnailfc 2>&1", $ffmpeg_out, $ret);
 
  if (!file_exists($thumbnail) || filesize($thumbnail) === 0) 
  {
    // try again with first frame
    exec("$ffmpeg -y -strict -2 -i $filename -v quiet -an -vframes 1 -vf scale=$width:$height $thumbnailfc 2>&1", $ffmpeg_out, $ret);
    
    clearstatcache();

    if (!file_exists($thumbnail) || filesize($thumbnail) === 0) 
      $ret = 1;

  }
 
  return $ret;
}
