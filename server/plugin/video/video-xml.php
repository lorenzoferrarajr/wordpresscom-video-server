<?php
/**
 * video-xml:  generate XML video information  used by video player
 * 
 * Description: 
 * given a video guid, retrieve the pertinent information in XML 
 * to be used by the video player. Since this is a frequent read operation, we will cache it
 * 
 * Author:  Automattic Inc
 * Version: 0.9
 */
 
require('../../../wp-config.php');

header("Content-type: text/xml "); 

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>"; 

$guid = $wpdb->escape( $_GET['guid'] );

//try to get the xml info from cache first
$key = 'video-xml-by-' . $guid; 

$video_xml = wp_cache_get( $key, 'video-info' ); 

if ( ! empty( $video_xml ) ) {
	echo $video_xml;
	die(); 	
}

//make sure it is a valid guid
$info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM videos WHERE guid=%s", $guid ) );

if ( empty( $info ) ) {
	echo "<xml>\n";
	echo "<error>clip_not_found</error>";
	echo "</xml>";
	die(); 
} else if ( !video_format_done( $info, 'flv' ) && !video_format_done( $info, 'fmt_std' ) ){
	echo "<xml>\n";
	echo "<error>clip_not_ready</error>";
	echo "</xml>";
	die(); 
} 

if ( defined('IS_WPCOM') && IS_WPCOM ) {
	
	if ( is_suspended( $info->blog_id ) ){
		echo "<xml>\n";
		echo "<error>video suspended due to terms of service violation</error>";
		echo "</xml>";
		die(); 
	}
}

//not in the cache, generate it, then set the cache

$guid        = $info->guid; 
$blog_id     = $info->blog_id; 
$post_id     = $info->post_id; 
$domain      = $info->domain; 
$caption     = htmlspecialchars( $info->title, ENT_QUOTES );
$description = htmlspecialchars( $info->description, ENT_QUOTES ); 
$v_name      = preg_replace( '/\.\w+/', '', basename( $info->path ) ); 

switch_to_blog( $blog_id );

$n = preg_match( '/(\d+):(\d+):(\d+)./', $info->duration, $match); 
$total_seconds = 3600 * $match[1] + 60 * $match[2] + $match[3]; 

$logged_in = 0; 
if ( is_user_logged_in() )
	$logged_in = 1; 
	
mt_srand();
$rand = mt_rand( 1000000000001, 9999999999998 );

$xml = ''; 
$xml .= "<xml>\n";
$xml .= "<video>\n";

$xml .="<video_id>$guid</video_id>\n"; 
$xml .= "<caption>$caption</caption>\n";

$xml .= "<duration>$total_seconds</duration>\n"; 
$xml .= "<blog_domain>http://$domain</blog_domain>\n";
$xml .= "<default_volume>94</default_volume>\n";
if ( $info->rating != 'G' )
	$xml .= "<rating>$info->rating</rating>\n";
$xml .= "<is_private>0</is_private>\n";
$xml .= "<is_logged_in>$logged_in</is_logged_in>\n"; 
$xml .= "<status_interval>15</status_interval>\n"; 

if ( defined('IS_WPCOM') && IS_WPCOM ) {
	$admin_upload_url  = 'http://v.wordpress.com/wp-content/plugins/video/video-update-thumbnail.php'; 
} else {
	$admin_upload_url  = 'http://' .  MY_VIDEO_SERVER . '/wp-content/plugins/video/video-update-thumbnail.php'; 
}

$xml .= "<admin_upload_url>$admin_upload_url</admin_upload_url>\n"; 

if ( defined('IS_WPCOM') && IS_WPCOM ) {
	
	//dotsub translations
	if ( $domain == 'wptv.wordpress.com') {
		$dotsub_username = 'wordpresstv'; 
	}

	if ( !empty( $dotsub_username) ){
	
		$meta_url = 'http://dotsub.com/api/user/'. $dotsub_username .  '/media/' . $info->guid . '/metadata'; 
	
		if ( is_valid_url( $meta_url )  ) { 

			$caption_url =  'http://dotsub.com/api/user/'. $dotsub_username .  '/media/' . $info->guid . '/captions?language='; 
			$xml .= "<dotsub_metadata>$meta_url</dotsub_metadata>\n";
			$xml .= "<dotsub_caption>$caption_url</dotsub_caption>\n"; 
		}
	}
} 

if ( video_format_done( $info, 'flv' ) )
	$types[] = 'flv'; 
if ( video_format_done( $info, 'fmt_std' ) )
	$types[] = 'fmt_std';
if ( video_format_done( $info, 'fmt_dvd' ) )
	$types[] = 'fmt_dvd';
if ( video_format_done( $info, 'fmt_hd' ) )
	$types[] = 'fmt_hd';
if ( video_format_done( $info, 'fmt1_ogg' ) )
	$types[] = 'fmt1_ogg';
	
foreach ( $types as $type ){
	
	if ( $type == 'flv' ) //treat flv as fmt_std to simplify parsing logic in player
		$xml .= "<fmt_std>\n"; 
	else 
		$xml .= "<$type>\n"; 
	
	if ( $type == 'flv'	) {
		
		$width  = 400;
		if ( empty($info->height) || empty($info->width) ) //handle db error case
			$height = 300; 
		else 
			$height = (int)( 400 * ($info->height/$info->width) ); 
		
		$files = unserialize( $info->flv_files ); 
		
	} else if ( $type == 'fmt_std'	) {
		
		$width  = 400;
		if ( empty($info->height) || empty($info->width) ) 
			$height = 300; 
		else 
			$height = (int)( 400 * ($info->height/$info->width) ); 
			
		$files = unserialize( $info->std_files ); 
		
	} else if ( $type == 'fmt_dvd'	) {
		
		$width  = 640;
		if ( empty($info->height) || empty($info->width) ) 
			$height = 360; 
		else 
			$height = (int)( 640 * ($info->height/$info->width) );
		
		$files = unserialize( $info->dvd_files ); 
		
	} else if ( $type == 'fmt_hd'	) {
		
		$width  = 1280;
		if ( empty($info->height) || empty($info->width) ) 
			$height = 720; 
		else 
			$height = (int)( 1280 * ($info->height/$info->width) ); 
			
		$files = unserialize( $info->hd_files ); 
		
	} else if ( $type == 'fmt1_ogg'	) {
		
		$width  = 400;
		if ( empty($info->height) || empty($info->width) ) 
			$height = 300; 
		else 
			$height = (int)( 400 * ($info->height/$info->width) ); 
			
		$video_name = $v_name . '_fmt1.ogv'; 
		
	}
	
	if ( $width %2 == 1 )   $width--; //in sync with logic in transcoder 
	if ( $height %2 == 1 )  $height--; 
	
	$xml .= "<width>$width</width>\n";
	$xml .= "<height>$height</height>\n";
	
	if ( defined('IS_WPCOM') && IS_WPCOM ) { 
		$stats_url = "http://stats.wordpress.com/g.gif?v=wpcomv&amp;blog=$blog_id&amp;post=$post_id&amp;video_fmt=$type&amp;sid=0";
	} else { 
		//open source framework, also make sure you have beacon image g.gif on your stats server
		$stats_url = 'http://' . MY_VIDEO_STATS_SERVER .  "/g.gif?v=" . MY_VIDEO_SERVER .  "&amp;blog=$blog_id&amp;post=$post_id&amp;video_fmt=$type";
	}
	$xml .= "<status_url>$stats_url</status_url>\n"; 
	
	$v_url = get_the_guid( $info->post_id ); 
	
	if ( $type == 'flv' || $type == 'fmt_std' || $type == 'fmt_dvd' || $type == 'fmt_hd' ){ 
		
		$preview_img_name = video_preview_image_name( $type, $info ); 
		
		if ( defined('IS_WPCOM') && IS_WPCOM ) {
			
			$movie_file       = 'http://cdn.videos.wordpress.com/' . $guid . '/' . $files[ 'video_file' ]; 
			$original_img     = 'http://cdn.videos.wordpress.com/' . $guid . '/' . $preview_img_name; 
			
		} else { // open source framework
		
			$movie_file   = preg_replace( '#[^/]+\.\w+$#', $files[ 'video_file' ], $v_url ); 
			$original_img = preg_replace( '#[^/]+\.\w+$#', $preview_img_name, $v_url ); 
		}
		
		$xml .= "<movie_file>$movie_file</movie_file>\n"; 
		$xml .= "<original_img>$original_img</original_img>\n"; 
		
	} else if ( $type == 'fmt1_ogg' ){
		
		if ( defined('IS_WPCOM') && IS_WPCOM ) { 
			
			$movie_file  = 'http://cdn.videos.wordpress.com/' . $guid . '/' . $video_name; 
			
		} else { // open source framework
		
			$movie_file   = preg_replace( '#[^/]+\.\w+$#', $video_name, $v_url ); 
		}
		
		$xml .= "<movie_file>$movie_file</movie_file>\n"; 
	}
	
	if ( $type == 'flv' ) //treat flv as fmt_std to simplify parsing logic in player
		$xml .= "</fmt_std>\n"; 
	else 
		$xml .= "</$type>\n"; 
	
}

if ( $info->display_embed == 1 ) {
	
	if ( empty($info->height) || empty($info->width) ) 
		$embed_height = 300; 
	else 
		$embed_height = (int)( 400 * ($info->height/$info->width) ); 
	
	if ( $embed_height %2 == 1 )  $embed_height--; 

	if ( defined('IS_WPCOM') && IS_WPCOM ) {
		$src = "http://v.wordpress.com/$guid"; 
	} else { 
		$src = "http://" . MY_VIDEO_SERVER . "/wp-content/plugins/video/flvplayer.swf?guid=$guid" . "&video_info_path=http://" . MY_VIDEO_SERVER . "/wp-content/plugins/video/video-xml.php";
	}

	$xml .= "<embed_code><![CDATA[<embed src=\"$src\" type=\"application/x-shockwave-flash\" width=\"400\" height=\"$embed_height\" allowscriptaccess=\"always\" allowfullscreen=\"true\"></embed>]]></embed_code>\n"; 

	if ( video_format_done( $info, 'fmt_dvd' ) ) { 
	
		$large_embed_height = (int)( 640 * ($info->height/$info->width) ); 
		if ( $large_embed_height %2 == 1 )  $large_embed_height--; 
		$xml .= "<large_embed_code><![CDATA[<embed src=\"$src\" type=\"application/x-shockwave-flash\" width=\"640\" height=\"$large_embed_height\" allowscriptaccess=\"always\" allowfullscreen=\"true\"></embed>]]></large_embed_code>\n"; 
	} 

	$xml .= "<wp_embed>[wpvideo $guid]</wp_embed>\n"; 
}

/*
 *if embed is turned off, the video is only allowed on 
 *v.wordrpess.com, the original blog, and any mapped domains 
 */
if ( $info->display_embed == 0 ) {
	
	$allowed_sites = $domain . ',' . 'v.wordpress.com'; 
	
	if ( defined('IS_WPCOM') && IS_WPCOM ) {
		
		$sql_m = $wpdb->prepare( "SELECT domain FROM domain_mapping WHERE blog_id=%d", $blog_id ); 
		$mapped_domains = $wpdb->get_col( $sql_m ); 
	
		if ( !empty( $mapped_domains ) ){
			foreach ( $mapped_domains as $d )
				$allowed_sites .= ",{$d}"; 
		}
	} 
	
	$xml .= "<allowed_embed_sites>$allowed_sites</allowed_embed_sites>\n"; 
}

$xml .= "</video>\n"; 

// flash default vars
$xml .= "<flash_default_vars>\n";

$xml .= "<show_title>1</show_title>\n";
$xml .= "<color>01AAEA</color>\n"; 
$xml .= "<full_screen>1</full_screen>\n"; 

$xml .= "</flash_default_vars>\n";

$xml .= "</xml>";

echo $xml; 
wp_cache_set( $key, $xml, 'video-info', 24*60*60 ); 

die(); 

/**
 * check to see if a given url is valid (200 OK)
 * return true if it is a valid url, false if not
 */
function is_valid_url( $url ) { 
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_HEADER, true );
	//curl_setopt( $ch, CURLOPT_NOBODY, true ); //must include body somehow for dotsub
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 ); //follow up to 10 redirections - avoids loops
	
	$data = curl_exec( $ch );
	curl_close( $ch );
	preg_match_all( "/HTTP\/1\.[1|0]\s(\d{3})/", $data, $matches );
	$code = end( $matches[1] );

	if( !$data ) {
		return false; 
	} 
	
	if( $code == 200 ) {
		return true; 
 	 } elseif( $code == 404 ) {
 	 	return false; 
  	} else 
  		return true;  
} 

?>





