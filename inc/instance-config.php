<?php

/*
*  Instance Configuration
*  ----------------------
*  Edit this file and not config.php for imageboard configuration.
*
*  You can copy values from config.php (defaults) and paste them here.
*/

	
	$config['cookies']['mod'] = 'mod';
	$config['cookies']['salt'] = '';
	
	$config['spam']['hidden_inputs_max_pass'] = 128;
	$config['spam']['hidden_inputs_expire'] = 60 * 60 * 4; // three hours
	
	$config['flood_time'] = 5;
	$config['flood_time_ip'] = 30;
	$config['flood_time_same'] = 2;
	$config['max_body'] = 5000;
	$config['reply_limit'] = 500;
	$config['thumb_width'] = 200;
	$config['thumb_height'] = 200;
	$config['max_width'] = 10000;
	$config['max_height'] = 10000;
	$config['threads_per_page'] = 15;
	$config['max_pages'] =50;
	$config['threads_preview'] = 3;
	$config['root'] = '/';
	$config['secure_trip_salt'] = '';
	$config['always_noko'] = true;
	$config['allow_no_country'] = true;
	$config['thread_subject_in_title'] = true;
	$config['spam']['hidden_inputs_max_pass'] = 128;	

	// Image shit
	$config['thumb_method'] = 'convert';
	$config['thumb_ext'] = 'jpg';
	$config['thumb_keep_animation_frames'] = 1;
	$config['show_ratio'] = true;
	//$config['allow_upload_by_url'] = true;
	$config['max_filesize'] = 1024 * 1024 * 99; // 8MB
	$config['spoiler_images'] = true;
	$config['image_reject_repost'] = false;
	$config['allowed_ext_files'][] = 'webm';
	$config['allowed_ext_files'][] = 'mp4';
	$config['webm']['use_ffmpeg'] = true;
	$config['webm']['allow_audio'] = true;
	$config['webm']['max_length'] = 60 * 120;


	//$config['mod']['view_banlist'] = GLOBALVOLUNTEER;
	$config['mod']['recent_reports'] = 65535;
	$config['mod']['ip_less_recentposts'] = 75;
	$config['ban_show_post'] = true;

	// Board shit
	$config['max_links'] = 40;
	$config['poster_id_length'] = 5;
	$config['url_banner'] = 'https://banners.neochan.ru';	
	$config['markup_paragraphs'] = true;
	$config['markup_rtl'] = true;

	$config['syslog'] = true;
	

$config['hash_masked_ip'] = true;
$config['force_subject_op'] = false;
$config['min_links'] = 0;
$config['min_body'] = 0;
$config['early_404'] = false;
$config['early_404_page'] = 5;
$config['early_404_replies'] = 10;
$config['cron_bans'] = true;
$config['mask_db_error'] = false;
$config['ban_appeals'] = true;
$config['show_sages'] = false;
$config['katex'] = false;
$config['enable_antibot'] = false;
$config['spam']['unicode'] = false;
$config['twig_cache'] = false;
$config['report_captcha'] = false;
$config['no_top_bar_boards'] = false;

$config['convert_args'] = '-size %dx%d %s -thumbnail %dx%d -quality 85%% -background \'#d6daf0\' -alpha remove -auto-orient +profile "*" %s';

// Flavor and design.
$config['site_name'] = "Neochan";
#$config['site_logo'] = "/static/logo_33.svg";

// 8chan specific mod pages
require 'config-permission.php';

// Load instance functions later on
require_once 'instance-functions.php';
	
// Load database credentials
require "secrets.php";

