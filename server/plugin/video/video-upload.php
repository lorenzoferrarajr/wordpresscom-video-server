<?php
/* video-upload: upload a video to transcoder for processing
 * 
 * Description: 
 * it is ran as a background child process called after original raw video upload is completed
 * 
 * Author:  Automattic Inc
 * Version: 1.0
 */

$video_url  = $_SERVER["argv"][1]; 
$blog_id    = $_SERVER["argv"][2]; 
$post_id    = $_SERVER["argv"][3]; 
$dc         = $_SERVER["argv"][4]; 
$transcoder = $_SERVER["argv"][5]; 

$form = array(); 
$form['video_url'] = $video_url; 
$form['blog_id']   = $blog_id; 
$form['post_id']   = $post_id; 
$form['dc']        = $dc; 
$form['auth']      = trim( 'saltedmd5' . md5( 'your local secret' ) );

$r = my_post_form( $transcoder, $form ); 

 function my_post_form( $action, $form ) {

	$args = array( 'CURLOPT_RETURNTRANSFER' => 1, 'CURLOPT_TIMEOUT' => 2*60*60 ); 
	
	$ch = curl_init($action);
	
	foreach ( $args as $k => $v )
		curl_setopt($ch, constant($k), $v);
		
	curl_setopt($ch, CURLOPT_POSTFIELDS, $form);
	$r = curl_exec($ch);
	curl_close($ch);
	
	return $r;
}
?>
