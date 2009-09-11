<?php 
/**
 * video-transcoder: transcodes video 
 * 
 * Description: 
 * This program does video transcoding - it is run on an transcoder and basically calls transcode_video()
 * 
 * Author:  Automattic Inc
 * Version: 1.0 
 */
 

require('../../../wp-config.php');

ignore_user_abort(); 

$blog_id   = $wpdb->escape( $_POST['blog_id'] );
$post_id   = $wpdb->escape( $_POST['post_id'] ); 
$video_url = $wpdb->escape( $_POST['video_url'] );
$dc        = $wpdb->escape( $_POST['dc'] ); 
$auth      = $wpdb->escape( $_POST['auth'] ); 

error_log( "transcoder gets request ($blog_id, $post_id) " ); 

switch_to_blog( $blog_id );

$data = new stdClass();
$data->blog_id     = (int)$blog_id;
$data->post_id     = (int)$post_id;
$data->video_url   = $video_url; 
$data->upload_dc   = $dc; 

$job = new stdClass(); 
$job->data = $data; 
		
transcode_video( $job );

die(); 
		
?>
