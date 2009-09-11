<?php
/** 
 * Receive and process the thumbnail from flash player
 * 
 * Description: 
 * this is for the scrubber based thumbnail capture feature, 
 * it accepts the new jpg file, replicates it, then update thumbnail_files field in videos table
 * 
 * Author:  Automattic Inc
 * Version: 1.0
 */
 
require('../../../wp-config.php');

ignore_user_abort();

$guid = $wpdb->escape( $_GET['video_id'] );
$auth = $wpdb->escape( $_GET['auth'] );

/*
 * read from db directly because this script runs on any web server,
 * so cache sync could be an issue when user scrubbers and sends 
 * thumbnail repeatedly and quickly
 */
 
$info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM videos WHERE guid=%s ", $guid ) ); 

if ( empty( $info ) ){
	log_die("invalid video id $guid"); 
}

//verify authentication
$local_auth = md5( VIDEO_AUTH_SECRET . $guid ) ;

if ( defined('IS_WPCOM') && IS_WPCOM ) {
	if ( $auth != $local_auth ) {
		log_die("wrong auth $guid"); 
	}
} 

$blog_id = $info->blog_id; 
$post_id = $info->post_id; 

$thumbnail_content = $GLOBALS[ 'HTTP_RAW_POST_DATA' ];
if ( strlen( $thumbnail_content) < 100 ) {
	log_die("can not receive jpg content $guid"); 
}


//ready to receive the file and process it

switch_to_blog( $blog_id ); 

$file = get_attached_file( $post_id ); 

preg_match('|wp-content/blogs.dir\S+?files(.+)$|i', $file, $match);
$pathname = ABSPATH . $match[0];
$dir = dirname( $pathname ); 

if ( !file_exists( $dir ) ){
	mkdir( $dir, 0777, true ); 
}

$next_name = get_next_scrubber_thumbnail_name( $info ); 

$thumbnail_pathname = $dir . '/' . $next_name; 

$fp = fopen( $thumbnail_pathname, 'w' ); 
$r = fwrite( $fp, $thumbnail_content ); 

if ( $r === false || $r < 100 ){
	cleanup(); 
	log_die( "can not write to $thumbnail_pathname ($guid)" ); 
}
fclose( $fp ); 

//initiate file replication
if ( defined('IS_WPCOM') && IS_WPCOM ) {
	log_upload( array('file' => $thumbnail_pathname, 'type' => 'image/jpeg'), false ); 
} 

$thumbnail_files = unserialize( $info->thumbnail_files ); 
$thumbnail_files[] = $next_name; 

update_video_info( $blog_id, $post_id, 'thumbnail_files', serialize( $thumbnail_files ) ); 

cleanup(); 

die( 'success' ); 


//clean up the residual files
function cleanup() {
	//placeholder 
}
 
function log_die( $msg ) {
	error_log( "update thumbnail: " . $msg ); 
	echo $msg; 
	die(); 
}

?>