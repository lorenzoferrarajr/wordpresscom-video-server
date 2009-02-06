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
 
/* 
 * CUSTOMIZE: include your header 
 * require('../../../wp-config.php');
 */

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
} else if ( $info->flv != 'done' && $info->fmt_std != 'done' ){
	echo "<xml>\n";
	echo "<error>clip_not_ready</error>";
	echo "</xml>";
	die(); 
}

//not in the cache, generate it, then set the cache

$blog_id = $info->blog_id; 
$post_id = $info->post_id; 
$domain  = $info->domain; 

switch_to_blog( $blog_id );

$post = get_post( $post_id ); 

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

$caption = $post->post_excerpt; 

$xml .= "<caption>$caption</caption>\n";

$xml .= "<duration>$total_seconds</duration>\n"; 
$xml .= "<blog_domain>http://$domain</blog_domain>\n";
$xml .= "<default_volume>94</default_volume>\n";
if ( $info->rating != 'G' )
	$xml .= "<rating>$info->rating</rating>\n";
$xml .= "<is_private>0</is_private>\n";
$xml .= "<is_logged_in>$logged_in</is_logged_in>\n"; 
$xml .= "<status_interval>15</status_interval>\n"; 

if ( $info->flv == 'done' )
	$types[] = 'flv'; 
if ( $info->fmt_std == 'done' )
	$types[] = 'fmt_std';
if ( $info->fmt_dvd == 'done' )
	$types[] = 'fmt_dvd';
if ( $info->fmt_hd == 'done' )
	$types[] = 'fmt_hd';

$domain_prefix = substr( $domain, 0, strpos($domain, '.'));

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
			
	} else if ( $type == 'fmt_std'	) {
		
		$width  = 400;
		if ( empty($info->height) || empty($info->width) ) 
			$height = 300; 
		else 
			$height = (int)( 400 * ($info->height/$info->width) ); 
			
	} else if ( $type == 'fmt_dvd'	) {
		
		$width  = 640;
		if ( empty($info->height) || empty($info->width) ) 
			$height = 360; 
		else 
			$height = (int)( 640 * ($info->height/$info->width) );
			
	} else if ( $type == 'fmt_hd'	) {
		
		$width  = 1280;
		if ( empty($info->height) || empty($info->width) ) 
			$height = 720; 
		else 
			$height = (int)( 1280 * ($info->height/$info->width) ); 
	} 
	
	if ( $width %2 == 1 )   $width--; //in sync with logic in transcoder 
	if ( $height %2 == 1 )  $height--; 
	
	//CUSTOMIZE: modify mydomain
	$movie_file    = 'http://' . $domain_prefix . '.videos.mydomain.com/' . $guid . '/video/' . $type; 
	$original_img  = 'http://' . $domain_prefix . '.videos.mydomain.com/' . $guid . '/original/' . $type; 
	$thumbnail_img = 'http://' . $domain_prefix . '.videos.mydomain.com/' . $guid . '/thumbnail/' . $type; 
	
	$xml .= "<width>$width</width>\n";
	$xml .= "<height>$height</height>\n";
	
	$xml .= "<status_url>$stats_url</status_url>\n"; 
	
	$xml .= "<movie_file>$movie_file</movie_file>\n"; 
	$xml .= "<original_img>$original_img</original_img>\n"; 
	$xml .= "<thumbnail_img>$thumbnail_img</thumbnail_img>\n"; 
	
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
	
	//CUSTOMIZE: modify mydomain
	$xml .= "<embed_code><![CDATA[<embed src=\"http://v.mydomain.com/$guid\" type=\"application/x-shockwave-flash\" width=\"400\" height=\"$embed_height\" allowscriptaccess=\"always\" allowfullscreen=\"true\"></embed>]]></embed_code>\n"; 

	if ( $info->fmt_dvd == 'done' ) { 
	
		$large_embed_height = (int)( 640 * ($info->height/$info->width) ); 
		if ( $large_embed_height %2 == 1 )  $large_embed_height--; 
		$xml .= "<large_embed_code><![CDATA[<embed src=\"http://v.mydomain.com/$guid\" type=\"application/x-shockwave-flash\" width=\"640\" height=\"$large_embed_height\" allowscriptaccess=\"always\" allowfullscreen=\"true\"></embed>]]></large_embed_code>\n"; 
	} 

	$xml .= "<wp_embed>[wpvideo $guid]</wp_embed>"; 
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
wp_cache_set( $key, $xml, 'video-info', 12*60*60 ); 

die(); 

?>





