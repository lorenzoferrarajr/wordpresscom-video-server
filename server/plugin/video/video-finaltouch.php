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
 * Version: 1.0
 */
 
require('../../../wp-config.php');

ignore_user_abort();

$blog_id  = $wpdb->escape( $_POST['blog_id'] );
$post_id  = $wpdb->escape( $_POST['post_id'] ); 
$format   = $wpdb->escape( $_POST['format'] ); 
$auth     = $wpdb->escape( $_POST['auth'] ); 

$bp = "blog:$blog_id, post:$post_id"; 

//verify authentication 
$auth = trim( $_POST['auth'] );
$local_auth = trim( 'saltedmd5' . md5( VIDEO_AUTH_SECRET ) );

if ( $auth != $local_auth ) {
	$status = 'error_auth_with_fileserver'; 
	update_video_info( $blog_id, $post_id, $format, $status ); 
	cleanup(); 
	log_die("video($bp): $status $format"); 
}

$video_file = $_FILES['video_file']['tmp_name'];  

if ( $format == 'flv' || $format == 'fmt_std' || $format == 'fmt_dvd' || $format == 'fmt_hd' ){ 
	$thumbnail_jpg = $_FILES['thumbnail_jpg']['tmp_name'];  
	$original_jpg  = $_FILES['original_jpg']['tmp_name'];  
} 

// if user deleted the video by this step, don't process it further
if ( !video_exists( $blog_id, $post_id ) ) { 
	$status = 'error_no_video_info_at_fileserver'; 
	cleanup(); 
	log_die("video($bp): $status $format"); 
}

switch_to_blog( $blog_id );

update_video_info( $blog_id, $post_id, $format, 'fileserver_received_request'  ); 

if ( $format == 'flv' || $format == 'fmt_std' || $format == 'fmt_dvd' || $format == 'fmt_hd' )
	$r = !is_uploaded_file( $video_file ) || !is_uploaded_file( $thumbnail_jpg ) || !is_uploaded_file( $original_jpg ); 
else 
	$r = !is_uploaded_file( $video_file ); 

if ( $r ) {
	$status =  'error_fileserver_cannot_receive_all_files'; 
	update_video_info( $blog_id, $post_id, $format, $status );
	cleanup(); 
	log_die("video($bp): $status $format"); 
}

// now we receive the video and thumbnails, copy  and replicate them

$file = get_attached_file( $post_id ); 

preg_match('|wp-content/blogs.dir\S+?files(.+)$|i', $file, $match);
$pathname = ABSPATH . $match[0];

/*
 * create sub directories if they were removed before, or don't exist yet 
 * eg, "00d/1594819/files/2006/08 in /home/wpdev/public_html/wp-content/blogs.dir/00d/1594819/files/2006/08/video.avi
 */
$dir = dirname( $pathname ); 

if ( !file_exists( $dir ) ){
	mkdir( $dir, 0777, true ); 
}

if ( $format == 'fmt_std' ){
	
	$video_pathname         = preg_replace( '/\.[^.]+$/', "_std.mp4", $pathname ); 
	$thumbnail_jpg_pathname = preg_replace( '/\.[^.]+$/', '_std.thumbnail.jpg', $pathname ); 
	$original_jpg_pathname  = preg_replace( '/\.[^.]+$/', '_std.original.jpg', $pathname ); 
	$files_col = 'std_files'; 
	
} else if ( $format == 'fmt_dvd' ){
	
	$video_pathname         = preg_replace( '/\.[^.]+$/', "_dvd.mp4", $pathname ); 
	$thumbnail_jpg_pathname = preg_replace( '/\.[^.]+$/', '_dvd.thumbnail.jpg', $pathname ); 
	$original_jpg_pathname  = preg_replace( '/\.[^.]+$/', '_dvd.original.jpg', $pathname ); 
	$files_col = 'dvd_files'; 
	
} else if ( $format == 'fmt_hd' ){
	
	$video_pathname         = preg_replace( '/\.[^.]+$/', "_hd.mp4", $pathname ); 
	$thumbnail_jpg_pathname = preg_replace( '/\.[^.]+$/', '_hd.thumbnail.jpg', $pathname ); 
	$original_jpg_pathname  = preg_replace( '/\.[^.]+$/', '_hd.original.jpg', $pathname ); 
	$files_col = 'hd_files'; 
	
} else if ( $format == 'flv' ){
	
	$video_pathname         = preg_replace( '/\.[^.]+$/', ".flv", $pathname ); 
	$thumbnail_jpg_pathname = preg_replace( '/\.[^.]+$/', '.thumbnail.jpg', $pathname ); 
	$original_jpg_pathname  = preg_replace( '/\.[^.]+$/', '.original.jpg', $pathname ); 
	$files_col = 'flv_files'; 
	
} else if ( $format == 'fmt1_ogg' ){
	
	$video_pathname = preg_replace( '/\.[^.]+$/', "_fmt1.ogv", $pathname ); 
}

$r1 = $r2 = $r3 = true; 
$r1 = move_uploaded_file( $video_file, $video_pathname ); 

if ( $format == 'flv' || $format == 'fmt_std' || $format == 'fmt_dvd' || $format == 'fmt_hd' ){ 
	$r2 = move_uploaded_file( $thumbnail_jpg, $thumbnail_jpg_pathname ); 
	$r3 = move_uploaded_file( $original_jpg, $original_jpg_pathname ); 
} 

if ( !$r1 || !$r2 || !$r3 ) {
	$status = 'error_move_uploaded_file'; 
	update_video_info( $blog_id, $post_id, $format, $status );
	cleanup(); 
	log_die("video($bp): $status $format"); 
}

// initiate file replication, do video last

if ( $format == 'flv' ) 
	$video_type = 'video/x-flv'; 
else if ( $format == 'fmt_std' || $format == 'fmt_dvd' || $format == 'fmt_hd' )
	$video_type = 'video/mp4'; 
else if ( $format == 'fmt1_ogg' ) 
	$video_type = 'video/ogg'; 
	
//don't count disk usage since these are internal video files
if ( defined('IS_WPCOM') && IS_WPCOM ) { 
	log_upload( array('file' => $video_pathname,         'type' => $video_type ), false ); 
} 

if ( $format == 'flv' || $format == 'fmt_std' || $format == 'fmt_dvd' || $format == 'fmt_hd' ){ 
	
	if ( defined('IS_WPCOM') && IS_WPCOM ) {
		log_upload( array('file' => $original_jpg_pathname,  'type' => 'image/jpeg'), false ); 
		log_upload( array('file' => $thumbnail_jpg_pathname, 'type' => 'image/jpeg'), false ); 
	} 
	
	$files_info = array( 'video_file'    => basename( $video_pathname ), 
                     	 'original_img'  => basename( $original_jpg_pathname ), 
                     	 'thumbnail_img' => basename( $thumbnail_jpg_pathname) ); 
					 
	update_video_info( $blog_id, $post_id, $files_col, serialize( $files_info ) ); 
} 

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
