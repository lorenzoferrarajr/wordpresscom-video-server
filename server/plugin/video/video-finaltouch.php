<?php
/** 
 * video-finaltouch: receive the transcoded video and process them
 * 
 * Description: 
 * This is the last step of video processing chain. 
 * It receives the transcoded video and thumbnails from transcoder, 
 * copies them to the same directory as the original video file, 
 * initiates file replication, and updates tables. 
 * 
 * Author:  Automattic Inc
 * Version: 0.9
 */
 
require('CUSTOMIZE: your configuration header');

ignore_user_abort();

$blog_id  = $wpdb->escape( $_POST['blog_id'] );
$post_id  = $wpdb->escape( $_POST['post_id'] ); 
$format   = $wpdb->escape( $_POST['format'] ); 
$auth     = $wpdb->escape( $_POST['auth'] ); 

$info = "blog:$blog_id, post:$post_id"; 

//verify authentication 
$auth = trim( $_POST['auth'] );
$local_auth = trim( 'saltedmd5' . md5("your local secret" ) );

if ( $auth != $local_auth ) {
	$status = 'error_auth_with_fileserver'; 
	update_video_info( $blog_id, $post_id, $format, $status ); 
	cleanup(); 
	log_die("video($info): $status $format"); 
}

$video_file    = $_FILES['video_file']['tmp_name'];  
$thumbnail_jpg = $_FILES['thumbnail_jpg']['tmp_name'];  
$original_jpg  = $_FILES['original_jpg']['tmp_name'];  

/*
 * sanity check 
 * if user deleted the video by this step, don't process it further
 */
$wpdb->send_reads_to_masters();
$sql = $wpdb->prepare( "SELECT * FROM videos WHERE blog_id=%d AND post_id=%d", $blog_id, $post_id); 
$r = $wpdb->get_row( $sql );

if ( empty( $r ) ){
	$status = 'error_no_video_info_at_fileserver'; 
	cleanup(); 
	log_die("video($info): $status $format"); 
}

switch_to_blog( $blog_id );

update_video_info( $blog_id, $post_id, $format, 'fileserver_received_request'  ); 

if ( !is_uploaded_file( $video_file ) || !is_uploaded_file( $thumbnail_jpg ) || !is_uploaded_file( $original_jpg )) {
	$status =  'error_fileserver_cannot_receive_all_files'; 
	update_video_info( $blog_id, $post_id, $format, $status );
	cleanup(); 
	log_die("video($info): $status $format"); 
}

// now we receive the video and thumbnails, copy  and replicate them

/* CUSTOMIZE: 
 * construct the pathname of the original video file on your system 
 * $file = get_attached_file( $post_id ); 
 * preg_match('|wp-content/blogs.dir\S+?files(.+)$|i', $file, $match);
 * $pathname = ABSPATH . $match[0];
*/ 

if ( $format == 'fmt_std' ){
	
	$video_pathname         = preg_replace( '/\.[^.]+$/', ".mp4", $pathname ); 
	$thumbnail_jpg_pathname = preg_replace( '/\.[^.]+$/', '.thumbnail.jpg', $pathname ); 
	$original_jpg_pathname  = preg_replace( '/\.[^.]+$/', '.original.jpg', $pathname ); 
	
} else if ( $format == 'fmt_dvd' ){
	
	$video_pathname         = preg_replace( '/\.[^.]+$/', "_dvd.mp4", $pathname ); 
	$thumbnail_jpg_pathname = preg_replace( '/\.[^.]+$/', '_dvd.thumbnail.jpg', $pathname ); 
	$original_jpg_pathname  = preg_replace( '/\.[^.]+$/', '_dvd.original.jpg', $pathname ); 
	
} else if ( $format == 'fmt_hd' ){
	
	$video_pathname         = preg_replace( '/\.[^.]+$/', "_hd.mp4", $pathname ); 
	$thumbnail_jpg_pathname = preg_replace( '/\.[^.]+$/', '_hd.thumbnail.jpg', $pathname ); 
	$original_jpg_pathname  = preg_replace( '/\.[^.]+$/', '_hd.original.jpg', $pathname ); 
	
} else if ( $format == 'flv' ){
	
	$video_pathname         = preg_replace( '/\.[^.]+$/', ".flv", $pathname ); 
	$thumbnail_jpg_pathname = preg_replace( '/\.[^.]+$/', '.thumbnail.jpg', $pathname ); 
	$original_jpg_pathname  = preg_replace( '/\.[^.]+$/', '.original.jpg', $pathname ); 
	
}

$r1 = move_uploaded_file( $video_file, $video_pathname ); 
$r2 = move_uploaded_file( $thumbnail_jpg, $thumbnail_jpg_pathname ); 
$r3 = move_uploaded_file( $original_jpg, $original_jpg_pathname ); 

if ( !$r1 || !$r2 || !$r3 ) {
	$status = 'error_move_uploaded_file'; 
	update_video_info( $blog_id, $post_id, $format, $status );
	cleanup(); 
	log_die("video($info): $status $format"); 
}

/*
 * CUSTOMIZE: 
 * you can replicate your video files here, or push them to CDN
 * log_upload( array('file' => $thumbnail_jpg_pathname, 'type' => 'image/jpeg') ); 
 * log_upload( array('file' => $original_jpg_pathname,  'type' => 'image/jpeg') ); 
 * log_upload( array('file' => $video_pathname,         'type' => $video_type ) ); 
*/

update_video_info( $blog_id, $post_id, $format, 'done' );

$finish_date_gmt = gmdate( 'Y-m-d H:i:s' );
update_video_info( $blog_id, $post_id, 'finish_date_gmt', $finish_date_gmt );

cleanup(); 
die("fileserver says it is finished"); 


//clean up the residual files
function cleanup() {
	
	global $video_file, $thumbnail_jpg, $original_jpg; 
	
	if (file_exists($video_file)) 
		unlink($video_file);
	if (file_exists($thumbnail_jpg)) 
		unlink($thumbnail_jpg); 
	if (file_exists($original_jpg)) 
		unlink($original_jpg); 
}
 
function log_die( $msg ) {
	error_log("finaltouch:" . $msg ); 
	echo $msg; 
	die(); 
}
 
?>