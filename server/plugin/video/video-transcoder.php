<?php 
/**
 * video-transcoder: transcodes video 
 * 
 * Description: 
 * This program does video transcoding - it transcodes video into h.264 mp4
 * and creates thumbnails. Afterwards, it sends the files and meta info to file server for final touch. 
 * 
 * Author:  Automattic Inc
 * Version: 0.9
 */
 
/* 
 * CUSTOMIZE: include your header 
 * require('../../../wp-config.php');
 */
 

define('FFMPEG_BINARY', '/usr/bin/ffmpeg');
define('FASTSTART', '/usr/bin/qt-faststart');

ignore_user_abort(); 

$video_url = $wpdb->escape( $_POST['video_url'] );
$blog_id   = $wpdb->escape( $_POST['blog_id'] );
$post_id   = $wpdb->escape( $_POST['post_id'] ); 
$dc        = $wpdb->escape( $_POST['dc'] ); 
$auth      = $wpdb->escape( $_POST['auth'] ); 

$info = "blog:$blog_id, post:$post_id"; 

switch_to_blog( $blog_id );

update_video_info( $blog_id, $post_id, 'fmt_std', 'transcoder_received_request' ); 

//verify authentication 
$auth = trim( $_POST['auth'] );
$local_auth = trim( 'saltedmd5' . md5("your local secret" ) );

if ( $auth != $local_auth ) {
	$status = 'error_auth_with_transcoder'; 
	update_video_info( $blog_id, $post_id, 'fmt_std', $status ); 
	cleanup(); 
	log_die("video($info): $status"); 
}

/* 
 * create a random file (eg, /tmp/clip1-hiking_7fEd98yC)
 * to hold the video, which is to be downloaded
 */
preg_match( '|([^/]+)\.\w+$|', $video_url, $m ); 
$random_str = video_generate_id(); 

$file = '/tmp/'. $m[1] . '_' . $random_str; 

$r = file_download( $video_url, $file ); 

if ( !$r ) {
	$status = 'error_transcoder_cannot_download_video'; 
	update_video_info( $blog_id, $post_id, 'fmt_std', $status ); 
	cleanup(); 
	log_die("video($info): $status");
}

/* 
 * try to get video dimensions
 * obtain the width and height from line. eg, 
 * Stream #0.0: Video: mjpeg, yuvj422p, 640x480 [PAR 0:1 DAR 0:1], 10.00 tb(r)
 * Also obtain the duration from line: " Duration: 00:02:41.5, start: 0.000000, bitrate: 3103 kb/s";
 */ 

$cmd = FFMPEG_BINARY . ' -i ' . $file  . ' 2>&1'; 
$lines = array(); 
exec( $cmd, $lines ); 

$width = $height = 0; 
$thumbnail_width = $thumbnail_height = 0; 

foreach ( $lines as $line ) {
	if ( preg_match( '/Stream.*Video:.* (\d+)x(\d+).* (\d+\.\d+) tb\(r\)$/', $line, $matches ) ) {
		$width      = $matches[1]; 
		$height     = $matches[2]; 
		$frame_rate = $matches[3];
	}
	if ( preg_match( '/Duration:\s*([\d:.]+),/', $line, $matches ) ) 
		$duration = $matches[1]; 
}

if ( $width == 0 || $height == 0 ) {
	$status = 'error_cannot_obtain_width_height'; 
	update_video_info( $blog_id, $post_id, 'fmt_std', $status ); 
	cleanup(); 
	log_die("video($info): $status $video_url");
	
} 

update_video_info( $blog_id, $post_id, 'width',  $width );
update_video_info( $blog_id, $post_id, 'height', $height );

$n = preg_match( '/(\d+):(\d+):(\d+)./', $duration, $match); 
if ( $n == 0) { 
	$status = 'error_cannot_obtain_duration'; 
	update_video_info( $blog_id, $post_id, 'fmt_std', $status ); 
	cleanup(); 
	log_die("video($info): $status $video_url");
}

update_video_info( $blog_id, $post_id, 'duration', $duration );

$total_seconds = 3600 * $match[1] + 60 * $match[2] + $match[3]; 

/*
 * 1 hour of fmt_std ~= 350 MB, fmt_dvd ~=700 MB, fmt_hd~= 1.5 GB
 * due to server limits, produce at most 2 hours of fmt_dvd, 1 hour of fmt_hd
 */
if ( $width >= 1280 && $height >= 720 ) {
	
	transcode_and_send( 'fmt_std' );
	
	if ( $total_seconds <= 2*60*60 ) 
		transcode_and_send( 'fmt_dvd' );
		
	if ( $total_seconds <= 60*60 )
		transcode_and_send( 'fmt_hd' );
		
} else if ( $width >= 640 && $height >= 360 ) {
	
	transcode_and_send( 'fmt_std' );
	
	if ( $total_seconds <= 2*60*60 )
		transcode_and_send( 'fmt_dvd' );
	
} else {
	transcode_and_send( 'fmt_std' );
} 

cleanup(); 
die(); 

/*
 * encode the raw video into h.264 standard, dvd or hd format,
 * also produce images. Then send them to file server
 */ 
function transcode_and_send( $format ){
	
	global $wpdb, $file, $dc, $video_url, $info, $blog_id, $post_id, $total_seconds, $width, $height, $frame_rate, $auth; 
	
	if ( $format == 'fmt_std' ){
		
		$video_output_width  = 400;
		$video_output_height = (int)( 400 * ($height/$width) );
		$thumbnail_width     = 256;
		$thumbnail_height    = (int)( 256 * ($height/$width) );
		$bitrate = ' -b 668k '; 
		 
	} else if ( $format == 'fmt_dvd' ){
		
		$video_output_width  = 640;
		$video_output_height = (int)( 640 * ($height/$width) );
		$thumbnail_width     = 256;
		$thumbnail_height    = (int)( 256 * ($height/$width) );
		$bitrate = ' -b 1400k '; 
		
	} else if ( $format == 'fmt_hd' ){
		
		$video_output_width  = 1280;
		$video_output_height = (int)( 1280 * ($height/$width) );
		$thumbnail_width     = 256;
		$thumbnail_height    = (int)( 256 * ($height/$width) );
		$bitrate = ' -b 3000k '; 
		
	} else {
		$status = 'wrong parameter: $format in transcode_and_send'; 
		cleanup(); 
		log_die("video($info): $status $video_url $format");
	}
	
	//frame size has to be multiple of 2
	if ( $video_output_width %2 == 1 )  $video_output_width--; 
	if ( $video_output_height %2 == 1 ) $video_output_height--; 
	if ( $thumbnail_width  %2 == 1 )    $thumbnail_width--; 
	if ( $thumbnail_height %2 == 1 )    $thumbnail_height--; 

	$temp_video_file = $file . '_temp.mp4';
	$video_file      = $file . '.mp4'; 
	$thumbnail_jpg   = $file . '.thumbnail.jpg'; 
	$original_jpg    = $file . '.original.jpg'; 
	
	$cmd = FFMPEG_BINARY . " -i $file -y -acodec libfaac -ar 48000 -ab 128k -async 1 -s {$video_output_width}x{$video_output_height} -vcodec libx264 -threads 2 $bitrate -flags +loop -cmp +chroma -partitions +parti4x4+partp8x8+partb8x8 -flags2 +mixed_refs -me_method  epzs -subq 5 -trellis 1 -refs 5 -bf 3 -b_strategy 1 -coder 1 -me_range 16 -g 250 -keyint_min 25 -sc_threshold 40 -i_qfactor 0.71 -rc_eq 'blurCplx^(1-qComp)' -qcomp 0.6 -qmin 5 -qmax 51 -qdiff 4 "; 
	
	if ( $frame_rate > 100 ) //correct wrong frame rate resulted from corrupted meta data
		$cmd .= ' -r 30 '; 
	
	$cmd .= $temp_video_file; 
	exec( $cmd );

	if ( !file_exists( $temp_video_file ) || filesize( $temp_video_file ) < 100 ) {
		$status = 'error_cannot_transcode'; 
		update_video_info( $blog_id, $post_id, $format, $status ); 
		cleanup(); 
		log_die( "video($info): $status $video_url $format" );
	}

	$cmd = FASTSTART . " $temp_video_file $video_file";
	exec( $cmd ); 

	$result  = safe_get_thumbnail($file, $total_seconds, $thumbnail_jpg,  $thumbnail_width, $thumbnail_height ); 
	$result2 = safe_get_thumbnail($file, $total_seconds, $original_jpg,  $video_output_width, $video_output_height ); 

	if ( !($result && $result2) ) {
		cleanup(); 
		$status = 'error_cannot_get_thumbnail'; 
		update_video_info( $blog_id, $post_id, $format, $status ); 
		log_die("video($info): $status $video_url $format"); 
	}
	
	$r1 = send_to_fileserver( $format, $video_file, $thumbnail_jpg, $original_jpg, 1 ); 
	
	//retry once if it fails for the first time
	if ( $r1 === false ) {
		sleep( 2*60 ); 
		$r2 = send_to_fileserver( $format, $video_file, $thumbnail_jpg, $original_jpg, 2 ); 
	}
	
}

/*
 * POST video file and images to fileserver for final processing
 * return true if successful, false if not
 */
function send_to_fileserver( $format, $video_file, $thumbnail_jpg, $original_jpg, $retries ) {
	
	global $wpdb, $blog_id, $post_id, $auth, $dc, $info, $video_url;
	
	/*
	 * sanity check 
	 * if user deleted the video by this step, don't process it further
	 */
	$wpdb->send_reads_to_masters();
	$sql = $wpdb->prepare( "SELECT * FROM videos WHERE blog_id=%d AND post_id=%d", $blog_id, $post_id); 
	$r = $wpdb->get_row( $sql );
	
	if ( empty( $r ) ){
		$status = 'error_no_video_info_after_transcode'; 
		cleanup(); 
		log_die("video($info): $status $video_url $format"); 
	}
	
	update_video_info( $blog_id, $post_id, $format, 'sending_to_fileserver' ); 
 
	$form = array(); 
	$form['blog_id']       = $blog_id; 
	$form['post_id']       = $post_id; 
	$form['format']        = $format; 
	$form['auth']          = $auth; 
	$form['video_file']    = "@$video_file"; 
	$form['thumbnail_jpg'] = "@$thumbnail_jpg"; 
	$form['original_jpg']  = "@$original_jpg"; 

	/*
 	 * try to send to fileserver located at the current data center
 	 * to avoid cross-dc traffic
 	 */
	if ( defined('DATACENTER') )
		$fileserver_dc = DATACENTER; 
	else
		$fileserver_dc = $dc; 

	$fileserver = pick_fileserver( $fileserver_dc ); 

	if ( empty($fileserver) ) { 
		$status = 'error_no_fileserver'; 
		update_video_info( $blog_id, $post_id, $format, $status ); 
		cleanup(); 
		log_die( "video($info): $status $video_url $format" );
	}

	$final_touch = $fileserver . '/wp-content/plugins/video/video-finaltouch.php'; 

	// append some info for debugging purpose
	$domain = $wpdb->get_var( $wpdb->prepare(" SELECT domain FROM wp_blogs where blog_id = %d", $blog_id) );
	
	$final_touch .= "?blog=$domain&amp;post_id=$post_id";
	//error_log("final_touch=$final_touch");

	$r = wpcom_post_form( $final_touch, $form );

	if ( $r != 'fileserver says it is finished' ) {
		error_log( "video($info) $format: error_sending_to_fileserver, attemp $retries");
		return false; 
	} 
	
	//check the db to make sure indeed everything is successful
	sleep( 3 ); //wait for db propagation
	$v = video_get_info_by_blogpostid( $blog_id, $post_id );
	
	if ( $v == false) {
		error_log( "video($info) $format: can not get info to make sound judgement, attemp $retries");
		return true; 
	}
		
	if ( $v->$format == 'done' )
		return true; 
	else 
		return false; 
}
//clean up the residual files
function cleanup() {
	global $file;  
	
	$cmd = 'rm ' . $file . '*'; 
	exec( $cmd ); 
}

/*
 * handle boundary case when the video codec is malformed such as when 
 * stream 1 codec frame rate differs from container frame rate: 1498.50 (2997/2) -> 29.97 (30000/1001)
 * we have to give a smaller seek position and try to obtain the thumbnail 
 */
function safe_get_thumbnail($file, $position, $thumbnail_jpg, $thumbnail_width, $thumbnail_height) { 
	
	$try = 0; 
	$seek = $position; 
	
	while ( $try++ < 10 ) {

		$seek = max ( (int)($seek/2), 0 ); 
		
		$r = get_thumbnail($file, $seek, $thumbnail_jpg, $thumbnail_width, $thumbnail_height); 
		if ( $r )
			return true; 
	}
	return false; 
}

function get_thumbnail($file, $seek, $thumbnail_jpg, $thumbnail_width, $thumbnail_height) { 
	
	$cmd = FFMPEG_BINARY . ' -y -i ' . $file . ' -f mjpeg ' . ' -vframes 1 -r 1 ' . ' -ss ' . $seek . ' -s ' . $thumbnail_width . 'x' . $thumbnail_height . ' -an ' . $thumbnail_jpg; 
	exec($cmd);

	clearstatcache();
	if ( file_exists($thumbnail_jpg) && filesize($thumbnail_jpg) > 0 )
		return true; 
	else { 
		return false;
	}
}

function wpcom_post_form( $action, $form, $args = '' ) {

    $defaults = array( 'CURLOPT_REFERER' => get_option( 'home' ), 'CURLOPT_RETURNTRANSFER' => 1, 'CURLOPT_TIMEOUT' => 1*60*60 ); 
    
    $args = wp_parse_args( $args, $defaults );
    
    $ch = curl_init($action);
    foreach ( $args as $k => $v )
    
    	curl_setopt($ch, constant($k), $v);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $form);
    	$r = curl_exec($ch);
    	curl_close($ch);
    	
    	return $r;
}

/* 
 * given a file url, it downloads a file and saves it to file_target
 * returns true if file is downloaded successfully
 */
function file_download($file_source, $file_target) {
	
        $rh = fopen($file_source, 'rb');
        $wh = fopen($file_target, 'wb');
        if ($rh===false || $wh===false) {
           return false;
        }
        while (!feof($rh)) {
            if (fwrite($wh, fread($rh, 1024)) === FALSE) {
                   // 'Download error: Cannot write to file ('.$file_target.')';
                   return false;
               }
        }
        fclose($rh);
        fclose($wh);
        
        if ( file_exists($file_target) )
        	return true;
        else 
        	return false; 
    }

function log_die( $msg ) {
	error_log( $msg ); 
	echo $msg; 
	die(); 
}
  
?>
