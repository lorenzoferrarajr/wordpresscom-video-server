<?php
/** 
 * video-lib: video lib functions
 * 
 * Description: 
 * this file contains various video related functions
 * 
 * Author:  Automattic Inc
 * Version: 1.0
 */

define( 'FFMPEG_BINARY', '/usr/bin/ffmpeg' );
define( 'FASTSTART', '/usr/bin/qt-faststart' );
define( 'VIDEO_MAX_PASS_NUMBER', 3 ); 
define( 'FFMPEG2THEORY_BINARY', '/usr/local/bin/ffmpeg2theora' ); 

/**
 * Given a guid, returns the video image url
 * if a particular format does not exist, return the default format
 * Ex: given guid=hFr8Nyar, fmt_hd, type=original, then returns
 * http://cdn.videos.wordpress.com/hFr8Nyar/coltrane-2641_hd.original.jpg
 */
function video_image_url_by_guid( $guid, $type='thumbnail', $format='fmt_std' ) {
	global $current_blog; 
	
	$info = video_get_info_by_guid( $guid ); 

	if ( empty( $info ) )
		return ''; 
		
	$name = video_preview_image_name( $format, $info ); 
	
	if ( defined('IS_WPCOM') && IS_WPCOM ) {
		
		$image_url = 'http://cdn.videos.wordpress.com/' . $guid . '/' . $name; 
	
	} else {
		
		$different_blog = false; 
		if ( $info->blog_id != $current_blog->blog_id ) { 
			$different_blog = true; 
			switch_to_blog( $info->blog_id ); 
		}
		
		$v_url = get_the_guid( $info->post_id ); 
		$image_url = preg_replace( '#[^/]+\.\w+$#', $name, $v_url ); 
		
		if ( $different_blog ) {
			restore_current_blog(); 
		} 
	}
	
	return $image_url; 
	
} 

/** 
 * choose a file server that is live, preferrable located in the given dc
 * If one is down, email system admin and try another one
 * If none is alive, return ''
 */
function pick_fileserver( $dc = 'luv') {
	
	//return 'http://' . MY_VIDEO_FILE_SERVER; //if your site has a dedicated file server

	$fileserver = get_option('siteurl');
	return $fileserver;
}

function pick_transcoder() {
	
	return 'http://' . MY_VIDEO_TRANSCODER . '/wp-content/plugins/video/video-transcoder.php'; 
	
} 

/**
 * check the response time of a domain
 * returns -1 if the domain is down; or the roundtrip time
 */
function ping_domain( $url ){
 	
	$e = parse_url( $url ); 
	$domain = $e[ 'host' ]; 
	$port = isset( $e['port' ]) ? $e['port' ] : 80; 
	
	$starttime = video_microtime_float(); 
	$file      = fsockopen ( $domain, $port, $errno, $errstr, 10 );
	$stoptime  = video_microtime_float(); 
 
	if ( !$file ) 
		$status = -1;  
	else {
		fclose( $file );
		$status = ( $stoptime - $starttime ) * 1000;
		$status = ceil( $status );
	}
	return $status;
}

function video_microtime_float() {
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}
	
	
/**
 * Sends raw video, and other info to remote video transcoding server for processing
 */
function remote_transcode_one_video( $post_id ) {
	global $wpdb, $current_blog, $current_user; 
	
	$blog_id = $current_blog->blog_id; 
	
	/* 
	 * sanity check, make sure the type is video, 
	 * the attachment exists and it has not been transcoded already
	 */
	if ( !$post = get_post( $post_id ) )
		return false; 

	if ( false === strpos( get_post_mime_type( $post_id ), 'video/' ) ) 
		return false;
	
	$info = video_get_info_by_blogpostid( $blog_id, $post_id ); 
	if ( $info != false && video_format_done( $info, 'fmt_std' ) )
		return false; 
		
	$dc = DATACENTER; 
		
	/* 
	 * video_url should indicate the current file server 
	 * so that the video is immediately available for download, 
	 * right after the initial upload
	 * eg: http://files1.luv.wordpress.com/wp-content/blogs.dir/8e7/2168894/files/2008/04/clip5-matt.mp4
	 */
	$path = get_attached_file( $post_id ); 

	preg_match( '|/wp-content/blogs.dir\S+?files(.+)$|i', $path, $matches ); 
	
	$fileserver = pick_fileserver( $dc ); 
	
	$video_url = $fileserver . $matches[0]; 
	$short_path = $matches[1]; 
	
	video_create_info( $blog_id, $post_id, $short_path, $dc ); 
	
	sleep( 3 ); //allow db write to complete
	
	/*
	 * wpcom uses jobs system to handle the transcoding request. jobs system is a separate open source project
	 * the crux of jobs system is that each transcoder can request and get a 'job' from the queue, 
	 * and call 'transcode_video'. 
	 * For open source framework, we simply use video-upload.php and video-transcoder.php 
	 */
	if ( defined('IS_WPCOM') && IS_WPCOM ) {
		
		//put the video job into queue
		$data = new stdClass();
		$data->blog_id     = (int)$blog_id;
		$data->post_id     = (int)$post_id;
		$data->video_url   = $video_url; 
		$data->pass_number = 0; 
		$data->upload_dc   = $dc; 
		$data->user_id     = (int) $current_user->ID;
		$data->msg         = ''; 
	
		queue_video_job( $data, 'transcode_video' ); 
	
		// testing, direct call
		/*$job = new stdClass(); 
		$job->data = $data; 
		transcode_video( $job );
		*/
		
	} else { // open source framework 

		$transcoder = pick_transcoder(); 
			
		if ( empty( $transcoder ) ) {
			update_video_info( $blog_id, $post_id, 'fmt_std', 'error_no_transcoder' ); 
			return false; 
		}
	
		// fork a background child process to handle the request
		$php_exe = "/usr/local/bin/php  " ; 
		$cmd = $php_exe . ABSPATH . "wp-content/plugins/video/video-upload.php $video_url $blog_id $post_id $dc $transcoder > /dev/null 2>&1 &"; 
		error_log("cmd=$cmd"); 
	
		exec($cmd); 
	}
}

if ( defined('IS_WPCOM') && IS_WPCOM ) { 
	add_action('videos_transcode_video', 'transcode_video' ); 
} 

/**
 * create an initial row in videos table
 *
 * @param int $blog_id blog id of the attachment
 * @param int $post_id post_id of the attachment
 * @param string $path short attachment file path in blog, like /2008/07/video 1.avi
 * @param int $dc originating data center
 */
function video_create_info( $blog_id, $post_id, $path, $dc ) {
	global $wpdb;

	//make sure it is a new entry
	$sql_c = $wpdb->prepare( "SELECT * FROM videos WHERE blog_id=%d AND post_id=%d", $blog_id, $post_id ); 
	$r_c = $wpdb->get_row( $sql_c );
	if ( !empty( $r_c ) )
		return false; 
	
	// generate a unique guid 
	$r = 'dummy';
	while ( !empty( $r ) ){
		$guid = video_generate_id();
		$sql_s = $wpdb->prepare( "SELECT * FROM videos WHERE guid=%s", $guid ); 
		$r = $wpdb->get_row( $sql_s ); 
	}	
	
	$date_gmt = gmdate( 'Y-m-d H:i:s' );
	$domain = $wpdb->get_var( $wpdb->prepare(" SELECT domain FROM wp_blogs where blog_id = %d", $blog_id) );
	
	$sql =  $wpdb->prepare( "INSERT INTO videos SET guid=%s, domain=%s, blog_id=%d, post_id=%d, path=%s, date_gmt=%s, dc=%s, fmt_std=%s ", $guid, $domain, $blog_id, $post_id, $path, $date_gmt, $dc, 'initiated' );
	$res = $wpdb->query( $sql );

	return ( $res !== false ); 
}


/**
 * Generates random video id.
 *
 * Generates random alphanumeric id and DOESN'T make sure it is unique. 
 *
 * @param int $length length of the id (default: 8)
 */
function video_generate_id($length = 8) {
    $allowed = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $guid = '';
    for ( $i = 0; $i < $length; ++$i )
        $guid .= $allowed[mt_rand(0, 61)];
    return $guid;
}

/**
 * Retrieves the corresponding row in videos table, given the guid
 *
 * @param string $guid video guid
 * @return mixed object or false on failure
 */
function video_get_info_by_guid( $guid ) {
	global $wpdb;
	
	$key = 'video-info-by-' . $guid; 
	
	$info = wp_cache_get( $key, 'video-info' ); 
	
	if ( $info == false ) {
		
		$info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM videos WHERE guid=%s", $guid ) );
		
		if ( is_null( $info ) )
			$info = false; 
		else 
			wp_cache_set( $key, $info, 'video-info', 12*60*60 ); 
	} 
	
	return $info; 
} 

/**
 * Retrieves the corresponding row in videos table, given the blog_id and post_id
 *
 * @param string $blog_id and $post_id
 * @return mixed object or false on failure
 */
function video_get_info_by_blogpostid( $blog_id, $post_id ) {
	global $wpdb;
	
	$key = 'video-info-by-' . $blog_id . '-' . $post_id; 
	
	$info = wp_cache_get( $key, 'video-info' ); 
	
	if ( $info == false ) {
		
		$info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM videos WHERE blog_id=%d AND post_id=%d", $blog_id, $post_id) );
		
		if ( is_null( $info ) )
			$info = false; 
		else 
			wp_cache_set( $key, $info, 'video-info', 12*60*60 ); 
	}
	return $info; 
} 

/**
 * update a particular entry of a row in videos table
 * return true if successful; false if not
 */
function update_video_info( $blog_id, $post_id, $column, $value ){
	global $wpdb; 
	
	//make sure the row exists
	if ( method_exists( $wpdb, 'send_reads_to_masters') ) 
		$wpdb->send_reads_to_masters();
		
	$sql_s = $wpdb->prepare( "SELECT * FROM videos WHERE blog_id=%d AND post_id=%d", $blog_id, $post_id ); 
	$r = $wpdb->get_row( $sql_s ); 
	
	if ( empty( $r ) ) {
		error_log("video ROW DOES NOT EXIST: sql = $sql_s"); 
		return false; 
	}
	
	if ( $column == 'fmt1_ogg' ){
		
		$existing_val = $r->fmts_ogg; 
		$this_val = 'fmt1_ogg:' . $value . ';'; 
		
		if ( empty( $existing_val ) )
			$new_val = $this_val; 
		else 
			$new_val = preg_replace( '/fmt1_ogg:[\w-]+;/', $this_val, $existing_val ); 
		
		$sql_u = $wpdb->prepare( "UPDATE videos SET fmts_ogg =%s WHERE blog_id=%d AND post_id=%d", $new_val, $blog_id, $post_id ); 
		
	} else { 
		$sql_u = $wpdb->prepare( "UPDATE videos SET $column=%s WHERE blog_id=%d AND post_id=%d", $value, $blog_id, $post_id ); 
	} 
	
	$r = $wpdb->query( $sql_u ); 
	
	//remove relevant cache  
	$info = video_get_info_by_blogpostid( $blog_id, $post_id );
	$key1 = 'video-info-by-' . $blog_id . '-' . $post_id; 
	wp_cache_delete( $key1, 'video-info' ); 
	
	$key2 = 'video-info-by-' . $info->guid; 
	wp_cache_delete( $key2, 'video-info' ); 
	
	$key3 = 'video-xml-by-' . $info->guid; 
	wp_cache_delete( $key3, 'video-info' ); 

	return true; 
}

function is_video( $post_id ) {
	return ( 0 === strpos( get_post_mime_type( $post_id ), 'video/' ) );
}

/**
 * get the original video name, given IDs
 * return name or '' if the info does not exist
 */
function video_get_name( $blog_id, $post_id ) { 
	 
	$info = video_get_info_by_blogpostid( $blog_id, $post_id ); 
	if ( empty( $info ) )
		return ''; 
		
	$basename = basename( $info->path ); 
	$name = preg_replace( '/\.\w+/', '', $basename ); 
	return $name; 
}

/**
 * determine whether a blog is a video blog
 * return true if it contains at least one video; return false otherwise 
 */
function is_video_blog( $blog_id ) {
	global $wpdb; 
	
	$r = $wpdb->get_results( "SELECT * FROM videos WHERE blog_id=$blog_id LIMIT 1" ); 
	
	if ( empty( $r ) )	
		return false;
	else 	
		return true; 
}
/**
 * Constructs named arguments array for a function
 *
 * @param array $args The actual arguments given to the function
 * @param array $required List of argument names, which are required for the function
 * @param array $defaults Associative array of default values for some arguments
 * @param array $null_defaults List of argument names, whose default value will be null
 *
 * @return mixed The array with the defaults set to missing arguments. If a required
 * argument is missing, WP_Error is returned.
 */
function named_args($args, $required = array(), $defaults = array(), $null_defaults = array() ) {
    $missing_required = array_diff( $required, array_keys( $args ) );
    if ( $missing_required )
        return new WP_Error('missing_required_args', 'There are missing arguments: '. implode( ', ', $missing_required ) );
	foreach( $null_defaults as $null )
		if ( !isset( $defaults[$null] ) ) $defaults[$null] = null;
    $args = array_merge($defaults, $args);
    return $args;
}

/**
 * checks videos table to determine whether a video exists or not
 * return true if it exists; false if not
 */
function video_exists( $blog_id, $post_id ){
	global $wpdb; 
	
	if ( method_exists( $wpdb, 'send_reads_to_masters') ) 
		$wpdb->send_reads_to_masters();
		
	$info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM videos WHERE blog_id=%d AND post_id=%d", $blog_id, $post_id) );
	
	if ( empty($info) )
		return false; 
	else 
		return true; 
}

/*
 * checks to see whether video processing is completed, and all formats are produced
 * return true if yes, false otherwise or error
 */
function is_video_finished( $blog_id, $post_id ){
	global $wpdb; 
	
	
	if ( method_exists( $wpdb, 'send_reads_to_masters') ) 
		$wpdb->send_reads_to_masters();
		
	$info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM videos WHERE blog_id=%d AND post_id=%d", $blog_id, $post_id) );
	
	if ( empty($info) )
		return false; 
	
	$width  = $info->width; 
	$height = $info->height; 
	
	if ( $width >= 1280 && $height >= 720 ) {
		
		if ( video_format_done( $info, 'fmt_std' ) && video_format_done( $info, 'fmt_dvd' ) && video_format_done( $info, 'fmt_hd' ) )
			return true; 
			
	} else if ( $width >= 640 && $height >= 360 ) {
		
		if ( video_format_done( $info, 'fmt_std' ) && video_format_done( $info, 'fmt_dvd' ) )
			return true; 
			
	} else {
		if ( video_format_done( $info, 'fmt_std' ) )
			return true; 
	} 

	//if the video is determined to be un-trancodeable, it's also considered job finished
	$permanent_errors = array( 'error_cannot_transcode', 
                               'error_cannot_obtain_width_height',
                               'error_cannot_get_thumbnail',
                               'error_cannot_obtain_duration' ); 
                               
	$status = video_format_status( $info, 'fmt_std' ); 	
	if ( in_array( $status, $permanent_errors ) )
		return true; 		

	return false; 
}

/**
 * callback function from video jobs queue, to be run on transcoder
 * it transcodes video into h.264 mp4 and creates thumbnails. 
 * Afterwards, it sends the files and meta info to file server for final touch. 
 * return true if successful or video can not be transcoded; false otherwise
 */
function transcode_video( $job ) {
	global $wpdb; 
	
	$blog_id     = $job->data->blog_id; 
	$post_id     = $job->data->post_id; 
	$video_url   = $job->data->video_url; 
	$pass_number = $job->data->pass_number; 
	
	if ( !video_exists( $blog_id, $post_id ) )
		return false; 

	if ( is_video_finished( $blog_id, $post_id ) )
		return false; 
	
	$bp = "blog:$blog_id, post:$post_id"; 
	
	update_video_info( $blog_id, $post_id, 'fmt_std', 'transcoder_received_request' ); 

	/* 
	 * create a random file (eg, /tmp/video_clip1-hiking_7fEd98yC)
	 * to hold the video, which is to be downloaded
	 */
	preg_match( '|([^/]+)\.\w+$|', $video_url, $m ); 
	$random_str = video_generate_id(); 

	$file = '/tmp/video_'. $m[1] . '_' . $random_str; 

	$r = video_file_download( $video_url, $file ); 

	if ( !$r ) {
		
		if ( defined('IS_WPCOM') && IS_WPCOM ) { 
			
			if ( $pass_number >= VIDEO_MAX_PASS_NUMBER ) {
			
				$status = 'error_transcoder_cannot_download_video'; 
				update_video_info( $blog_id, $post_id, 'fmt_std', $status ); 
			
				$msg = "video($bp): $status from $video_url after $pass_number passes" ; 
				error_log( $msg ); 
				wp_mail( VIDEO_ADMIN_EMAIL, "[can not download video file]", $msg ); 
			
				video_cleanup( $file ); 
				die(); //hard die for jobs system to notice it
			
			} else { 
			
				$job_msg = "---video($bp): download $video_url $pass_number pass"; 
				$job->data->msg .= $job_msg; 
			
				_video_try_again_later( $job );
			 
				$status = 'try_download_later'; 
				update_video_info( $blog_id, $post_id, 'fmt_std', $status ); 
			
				$msg = "video($bp): $status from $video_url $pass_number pass";
				error_log( $msg ); 
				wp_mail( VIDEO_ADMIN_EMAIL, "[can not download video file]", $msg ); 
			
				video_cleanup( $file ); 	
				return false; 
			}
			
		} else { //open source framework, no need to retry 
		
			$status = 'error_transcoder_cannot_download_video'; 
			update_video_info( $blog_id, $post_id, 'fmt_std', $status ); 
			
			$msg = "video($bp): $status from $video_url after $pass_number passes" ; 
			error_log( $msg ); 
			wp_mail( VIDEO_ADMIN_EMAIL, "[can not download video file]", $msg ); 
			
			video_cleanup( $file ); 
			return fasle; 
		}
	}
		
	/*
	 * try to get video dimensions
	 * obtain the width and height from line. eg, 
	 * Stream #0.0: Video: mjpeg, yuvj422p, 640x480 [PAR 0:1 DAR 0:1], 10.00 tb(r)
	 * Also obtain the duration from line: " Duration: 00:02:41.5, start: 0.000000, bitrate: 3103 kb/s";
	 */ 
	$cmd = FFMPEG_BINARY . ' -i ' . $file  . ' 2>&1'; 
	$lines = array(); 
	exec( $cmd, $lines, $r ); 
	
	if ( $r !== 0 && $r !== 1 ){
		//internal ffmpeg configuration issue
		$status = 'error_ffmpeg_binary_info'; 
		update_video_info( $blog_id, $post_id, 'fmt_std', $status ); 
		video_cleanup( $file ); 
		$msg = "video($bp): $status $video_url"; 
		wp_mail( VIDEO_ADMIN_EMAIL, "[video ffmpeg binary issue ]", $msg ); 
		error_log( $msg );
		die();
	}

	$width = $height = 0; 
	$thumbnail_width = $thumbnail_height = 0; 

	foreach ( $lines as $line ) {
		if ( preg_match( '/Stream.*Video:.* (\d+)x(\d+).* (\d+\.\d+) tb/', $line, $matches ) ) {
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
		video_cleanup( $file ); 
		error_log("video($bp): $status $video_url");
		return true; 
	} 

	update_video_info( $blog_id, $post_id, 'width',  $width );
	update_video_info( $blog_id, $post_id, 'height', $height );

	$n = preg_match( '/(\d+):(\d+):(\d+)./', $duration, $match); 
	if ( $n == 0) { 
		$status = 'error_cannot_obtain_duration'; 
		update_video_info( $blog_id, $post_id, 'fmt_std', $status ); 
		video_cleanup( $file ); 
		error_log("video($bp): $status $video_url");
		return true; 
	}

	update_video_info( $blog_id, $post_id, 'duration', $duration );

	$total_seconds = 3600 * $match[1] + 60 * $match[2] + $match[3]; 

	//user may delete video by now
	if ( !video_exists( $blog_id, $post_id ) ){
		video_cleanup( $file ); 
		return false; 
	}
	
	$para_array = array( 'file'          => $file, 
	                     'video_url'     => $video_url, 
	                     'bp'            => $bp,
	                     'blog_id'       => $blog_id,
	                     'post_id'       => $post_id,
	                     'total_seconds' => $total_seconds,
	                     'width'         => $width,
	                     'height'        => $height,
	                     'frame_rate'    => $frame_rate ); 
	
	/*
	 * 1 hour of fmt_std ~= 350 MB, fmt_dvd ~=700 MB, fmt_hd~= 1.5 G
	 * due to server limits, produce at most 2 hours of fmt_dvd, 1 hour of fmt_hd
	 */
	if ( $width >= 1280 && $height >= 720 ) {
	
		$r1 = transcode_and_send( 'fmt_std', $job,  $para_array );
		if ( !$r1 ){
			video_cleanup( $file ); 
			return false; 	
		}
		
		if ( $total_seconds <= 2*60*60 ) {
			$r2 = transcode_and_send( 'fmt_dvd', $job,  $para_array );
			if ( !$r2 ){
				video_cleanup( $file ); 
				return false; 	
			}
		}
		
		if ( $total_seconds <= 60*60 ){ 
			$r3 = transcode_and_send( 'fmt_hd', $job,  $para_array );
			if ( !$r3 ){
				video_cleanup( $file ); 
				return false; 	
			}
		}
		
	} else if ( $width >= 640 && $height >= 360 ) {
	
		$r1 = transcode_and_send( 'fmt_std', $job,  $para_array );
		if ( !$r1 ){
			video_cleanup( $file ); 
			return false; 	
		}
	
		if ( $total_seconds <= 2*60*60 ) {
			$r2 = transcode_and_send( 'fmt_dvd', $job,  $para_array );
			if ( !$r2 ){
				video_cleanup( $file ); 
				return false; 	
			}
		}
		
	} else {
		$r1 = transcode_and_send( 'fmt_std', $job,  $para_array );
		if ( !$r1 ){
			video_cleanup( $file ); 
			return false; 	
		}
	} 

	$r1 = ogg_transcode_and_send( 'fmt1_ogg', $job,  $para_array );
	if ( !$r1 ){
		video_cleanup( $file ); 
		return false; 	
	}
		
	video_cleanup( $file ); 
	return true; 
}

/**
 * WPCOM specific - when system glitch happens, such as file server is super busy or down,
 * try the same video job again after deferred amount of time
 */
function _video_try_again_later( $job ) {
	
	$pass_number = $job->data->pass_number; 
	
	$new_job = clone $job; 
	$new_job->data->pass_number = $pass_number + 1; 
	
	if ( $pass_number == 0 )
		$delay = 2*60; 
	else if ( $pass_number == 1 )
		$delay = 2*60; 
	else if ( $pass_number == 2 )
		$delay = 2*60; 
		
	$when = time() + $delay; 
	deferred_video_job( $new_job->data, 'transcode_video', $when ); 
}

/*
 * encode the raw video into h.264 standard, dvd or hd format,
 * also produce images. Then send them to file server
 * return true if successful, false otherwise
 */ 
function transcode_and_send( $format, $job, $para_array ){
	
	global $wpdb, $video_file_servers; 
	
	extract( $para_array );  
	
	if ( !video_exists( $blog_id, $post_id ) )
		return false; 
		
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
		video_cleanup( $file ); 
		error_log("video($bp): $status $video_url $format");
		return false; 
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
	
	exec( $cmd, $lines, $r ); 
	//error_log( "cmd = $cmd "); 

	if ( $r !== 0 && $r != 1 ){
		$status = 'error_ffmpeg_binary_transcode'; 
		update_video_info( $blog_id, $post_id, $format, $status ); 
		video_cleanup( $file ); 
		$msg = "video($bp): $status $video_url $format"; 
		wp_mail( VIDEO_ADMIN_EMAIL, "[video ffmpeg binary issue ]", $msg ); 
		error_log( $msg );
		die();
	}
	
	if ( !file_exists( $temp_video_file ) || filesize( $temp_video_file ) < 100 ) {
		$status = 'error_cannot_transcode'; 
		update_video_info( $blog_id, $post_id, $format, $status ); 
		video_cleanup( $file ); 
		error_log( "video($bp): $status $video_url $format" );
		return false; 
	}

	$cmd = FASTSTART . " $temp_video_file $video_file";
	exec( $cmd, $lines, $r ); 
	
	if ( $r !== 0 && $r != 1 ){
		$status = 'error_ffmpeg_binary_faststart'; 
		update_video_info( $blog_id, $post_id, $format, $status ); 
		video_cleanup( $file ); 
		$msg = "video($bp): $status $video_url $format"; 
		wp_mail( VIDEO_ADMIN_EMAIL, "[video ffmpeg binary issue ]", $msg ); 
		error_log( $msg );
		die();
	}

	$result  = safe_get_thumbnail($file, $total_seconds, $thumbnail_jpg,  $thumbnail_width, $thumbnail_height ); 
	$result2 = safe_get_thumbnail($file, $total_seconds, $original_jpg,  $video_output_width, $video_output_height ); 

	if ( !($result && $result2) ) {
		$status = 'error_cannot_get_thumbnail'; 
		update_video_info( $blog_id, $post_id, $format, $status ); 
		video_cleanup( $file ); 
		error_log("video($bp): $status $video_url $format"); 
		return false; 
	}
	
	//user may delete video by now
	if ( !video_exists( $blog_id, $post_id ) ){
		video_cleanup( $file ); 
		return false; 
	}
		
	$para2_array = array( 'video_file'    => $video_file,
						  'thumbnail_jpg' => $thumbnail_jpg,
						  'original_jpg'  => $original_jpg ); 
						  
	$para3_array = array_merge( $para_array, $para2_array ); 
	
	/*
	 * sending the transcoded clips to file server
	 * since one particular file server can be busy at any time, 
	 * wpcom tries multiple file servers for maximum reliability.
	 */
	if ( defined('IS_WPCOM') && IS_WPCOM ) {
	
		$all_dc = array_keys( $video_file_servers ); 
		$dc = DATACENTER; 
		$external_dc = array_diff( $all_dc, array( $dc ) );

		$r = send_to_fileserver( $dc, $format, $para3_array ); 

		// the logic below is intentionally kept verbose to help identify potential last step issue
		if ( !$r ) { 
			//try the second dc
			$next = array_slice( $external_dc, 0, 1 ); 
			$dc = $next[0];
			$r = send_to_fileserver( $dc, $format, $para3_array ); 
		}

		if ( !$r ) { 
			//try the third dc
			$next = array_slice( $external_dc, 1, 1 ); 
			$dc = $next[0];
			$r = send_to_fileserver( $dc, $format, $para3_array ); 
		}

		if ( !$r ){
			$pass_number = $job->data->pass_number; 
			if ( $pass_number >= VIDEO_MAX_PASS_NUMBER ){
			
				$status = 'error_cannot_sendto_fileserver'; 
				update_video_info( $blog_id, $post_id, $format, $status ); 
				$msg = "video($bp): $format $status after $pass_number passes" ;  
				error_log( $msg ); 
				wp_mail( VIDEO_ADMIN_EMAIL, "[video cannot send to fileserver ]", $msg ); 
			
				video_cleanup( $file );
				die(); //hard die in order for the jobs system to notice it
			
			} else {
	
				$job_msg = "---video($bp): $format try to sent to fileserver $pass_number pass";  
				$job->data->msg .= $job_msg; 
			
				_video_try_again_later( $job );
			
				$status = 'try_sendto_fileserver_later'; 
				update_video_info( $blog_id, $post_id, $format, $status ); 
			
				$msg = "video($bp): $format $status $pass_number pass";  
				error_log( $msg ); 
				wp_mail( VIDEO_ADMIN_EMAIL, "[video sent to fileserver later ]", $msg ); 
			
				video_cleanup( $file );
				return false; 
			}
		}
		
	} else { //open source framework 
		
		$r = send_to_fileserver( '', $format, $para3_array ); 
		if ( !$r ) { 
			$status = 'error_cannot_sendto_fileserver'; 
			update_video_info( $blog_id, $post_id, $format, $status ); 
			$msg = "video($bp): $format $status" ;  
			error_log( $msg ); 
			wp_mail( VIDEO_ADMIN_EMAIL, "[video cannot send to fileserver ]", $msg ); 
			
			video_cleanup( $file );
			return false; 
		} 
	}
	return true; 
}

/*
 * encode the raw video into theora/ogg 
 * No need to produce images. Then send it to file server
 * return true if successful, false otherwise
 */ 
function ogg_transcode_and_send( $format, $job, $para_array ){
	
	global $wpdb, $video_file_servers; 
	
	extract( $para_array );  
	
	if ( !video_exists( $blog_id, $post_id ) )
		return false; 
		
	if ( $format == 'fmt1_ogg' ){ 
		
		$video_output_width = 400;
	} 
	
	$video_file = $file . '_fmt1.ogv'; 
	
	/*
	 * use the default videoquality and audioquality when clip dimension is small
	 * the default rate is already pretty high (~1300 kbps according to my tests)
	 * however, when original dimension is large, need to specify the quality paras
	 */
	 $cmd = FFMPEG2THEORY_BINARY . " $file -o $video_file --width $video_output_width "; 
	 if ( $width >= 1000 ){
	 	$cmd .= ' --videoquality 9 --audioquality 6 '; 
	 }
	
	exec( $cmd, $lines, $r ); 
	//error_log( "cmd = $cmd "); 

	if ( $r !== 0 && $r != 1 ){
		$status = 'error_ffmpeg2theora_binary_transcode'; 
		update_video_info( $blog_id, $post_id, $format, $status ); 
		video_cleanup( $file ); 
		$msg = "video($bp): $status $video_url $format"; 
		wp_mail( VIDEO_ADMIN_EMAIL, "[video ffmpeg binary issue ]", $msg ); 
		error_log( $msg );
		return false; 
	}
	
	if ( !file_exists( $video_file ) || filesize( $video_file ) < 100 ) {
		$status = 'error_cannot_transcode'; 
		update_video_info( $blog_id, $post_id, $format, $status ); 
		video_cleanup( $file ); 
		error_log( "video($bp): $status $video_url $format" );
		return false; 
	}
		
	$para2_array = array( 'video_file' => $video_file ); 
						  
	$para3_array = array_merge( $para_array, $para2_array ); 
	
	/*
	 * sending the transcoded clips to file server
	 * since one particular file server can be busy at any time, 
	 * wpcom tries multiple file servers for maximum reliability.
	 */
	if ( defined('IS_WPCOM') && IS_WPCOM ) {
		
		$all_dc = array_keys( $video_file_servers ); 
		$dc = DATACENTER; 
		$external_dc = array_diff( $all_dc, array( $dc ) );

		$r = send_to_fileserver( $dc, $format, $para3_array ); 

		// the logic below is intentionally kept verbose to help identify potential last step issue
		if ( !$r ) { 
			//try the second dc
			$next = array_slice( $external_dc, 0, 1 ); 
			$dc = $next[0];
			$r = send_to_fileserver( $dc, $format, $para3_array ); 
		}

		if ( !$r ) { 
			//try the third dc
			$next = array_slice( $external_dc, 1, 1 ); 
			$dc = $next[0];
			$r = send_to_fileserver( $dc, $format, $para3_array ); 
		}

		if ( !$r ){
			$status = 'error_cannot_sendto_fileserver'; 
			update_video_info( $blog_id, $post_id, $format, $status ); 
			$msg = "video($bp): $format $status after $pass_number passes" ;  
			error_log( $msg ); 
			wp_mail( VIDEO_ADMIN_EMAIL, "[video cannot send to fileserver ]", $msg ); 
			
			video_cleanup( $file );
			return false; 	
		} 
		
	} else { //open source framework 
		
		$r = send_to_fileserver( '', $format, $para3_array ); 
		if ( !$r ) { 
			$status = 'error_cannot_sendto_fileserver'; 
			update_video_info( $blog_id, $post_id, $format, $status ); 
			$msg = "video($bp): $format $status" ;  
			error_log( $msg ); 
			wp_mail( VIDEO_ADMIN_EMAIL, "[video cannot send to fileserver ]", $msg ); 
			
			video_cleanup( $file );
			return false; 
		} 
	}
	
	return true; 
}

/*
 * POST video file and images to fileserver for final processing.
 * Ogg video only has .ogv file alone
 * return true if successful or video has been deleted;  false if not
 */
function send_to_fileserver( $dc, $format, $para_array ) {
	
	global $wpdb; 
	
	extract( $para_array ); 
	
	// if user deleted the video by this step, don't process it further
	if ( !video_exists( $blog_id, $post_id ) )
		return true; 
	
	update_video_info( $blog_id, $post_id, $format, 'sending_to_fileserver' ); 
 
	$form = array(); 
	$form['blog_id']       = $blog_id; 
	$form['post_id']       = $post_id; 
	$form['format']        = $format; 
	$form['auth']          = trim( 'saltedmd5' . md5( VIDEO_AUTH_SECRET ) );
	$form['video_file']    = "@$video_file"; 
	
	if ( $format == 'flv' || $format == 'fmt_std' ||$format == 'fmt_dvd' ||$format == 'fmt_hd' ){
		$form['thumbnail_jpg'] = "@$thumbnail_jpg"; 
		$form['original_jpg']  = "@$original_jpg"; 
	} 
	
	$fileserver = pick_fileserver( $dc ); 

	if ( empty($fileserver) ) { 
		$status = 'error_no_fileserver'; 
		update_video_info( $blog_id, $post_id, $format, $status ); 
		video_cleanup( $file ); 
		
		$msg = "video($bp): $status $video_url $format" ;
		error_log( $msg ); 
		wp_mail( VIDEO_ADMIN_EMAIL, "[video no fileserver ]", $msg ); 
		
		return false; 
	}

	$final_touch = $fileserver . '/wp-content/plugins/video/video-finaltouch.php'; 

	// append some info for debugging purpose
	$domain = $wpdb->get_var( $wpdb->prepare(" SELECT domain FROM wp_blogs where blog_id = %d", $blog_id) );
	
	$final_touch .= "?blog=$domain&amp;post_id=$post_id"; 
	//error_log("final_touch=$final_touch");

	$r = video_post_form( $final_touch, $form );

	// if user deleted the video by this step, don't process it further
	if ( !video_exists( $blog_id, $post_id ) )
		return true; 
		
	//check the db to make sure indeed everything is successful 
	sleep( 5 ); //wait for db write to take effect
	
	
	if ( method_exists( $wpdb, 'send_reads_to_masters') ) 
		$wpdb->send_reads_to_masters();
		
	$sql = $wpdb->prepare( "SELECT * FROM videos WHERE blog_id=%d AND post_id=%d", $blog_id, $post_id); 
	$info = $wpdb->get_row( $sql );
	
	if ( video_format_done( $info, $format ) )
		return true;
	else { 
		$st = video_format_status( $info, $format ); 
		$msg = "video($bp): $format sending to $final_touch failed: $st" ;
		error_log( $msg ); 
		wp_mail( VIDEO_ADMIN_EMAIL, "[video sending to file server failed ]", $msg ); 
		return false; 
	} 
}


//clean up the residual files
function video_cleanup( $file ) {
	
	$cmd = 'rm ' . $file . '*'; 
	exec( $cmd ); 
	
	//clean up residues from crash, etc over 4 days ago
	$cmd2 = 'find /tmp -name video_* -ctime 4  -print | xargs /bin/rm -f'; 
	exec( $cmd2 ); 
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
	exec( $cmd, $lines, $r ); 

	clearstatcache();
	if ( file_exists($thumbnail_jpg) && filesize($thumbnail_jpg) > 0 )
		return true; 
	else { 
		return false;
	}
}

function video_post_form( $action, $form, $args = '' ) {

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
function video_file_download($file_source, $file_target) {
	
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

/** 
 * return the next next scrubber thumbnail name with next highest suffix
 */
function get_next_scrubber_thumbnail_name( $info ){
	
	if ( empty( $info ))
		return ''; 
		
	$v_name  = preg_replace( '/\.\w+/', '', basename( $info->path ) ); 
	
	if ( empty( $info->thumbnail_files ) ) {
		$next_name = $v_name . '_scruberthumbnail_0.jpg'; 
		return $next_name; 
	}
	
	$files = unserialize( $info->thumbnail_files ); 
	
	$max = -1; 
	foreach( $files as $f ){
		
		preg_match( '/_scruberthumbnail_(\d+)\.jpg/', $f, $m ); 
		$num = (int)$m[1]; 
		
		if ( $max < $num )
			$max = $num; 
	}
	$max++; 
	
	$next_name = $v_name . '_scruberthumbnail_' . $max . '.jpg'; 
	
	return $next_name; 
}

/**
 * determine the preview image filename. 
 * give priority to scrubber bar generated files
 */
function video_preview_image_name( $format, $info ){
	
	//pick the latest scrubbed image file
	if ( !empty( $info->thumbnail_files ) ){ 
		
		$files = unserialize( $info->thumbnail_files ); 
		
		$max = -1; 
		foreach( $files as $f ){
			
			preg_match( '/_scruberthumbnail_(\d+)\.jpg/', $f, $m ); 
			$num = (int)$m[1]; 
			
			if ( $max < $num ){ 
				$max = $num; 
				$name = $f; 
			} 
		}
		return $name; 
	}
	
	//pick from default images
	if ( video_format_done( $info, 'flv' ) )
		$types[] = 'flv'; 
	if ( video_format_done( $info, 'fmt_std' ) )
		$types[] = 'fmt_std';
	if ( video_format_done( $info, 'fmt_dvd' ) )
		$types[] = 'fmt_dvd';
	if ( video_format_done( $info, 'fmt_hd' ) )
		$types[] = 'fmt_hd';
	
	if ( !in_array( $format, $types ) ){ 
		$format = 'fmt_std'; 
	} 
	
	if ( $format == 'flv' ){ 
		$files = unserialize( $info->flv_files ); 
	} else if ( $format == 'fmt_std' ){ 
		$files = unserialize( $info->std_files ); 
	} else if ( $format == 'fmt_dvd' ){ 
		$files = unserialize( $info->dvd_files ); 
	} else if ( $format == 'fmt_hd' ){ 
		$files = unserialize( $info->hd_files ); 
	}
	
	$name = $files[ 'original_img' ]; 
	
	return $name; 
}

/*
 * wrapper function to deal with potential multiple formats being stored 
 * in column fmts_ogg in the future, without having to adding new column for each future format
 * return true if a particular format is done; false otherwise
 */
function video_format_done( $info, $format ){
	
	if ( empty( $info ) || empty( $format ) )
		return false; 

	if ( $format == 'flv' || $format == 'fmt_std'  || $format == 'fmt_dvd' || $format == 'fmt_hd' ) {
		if ( $info->$format == 'done' )
			return true; 
		else 
			return false; 
	} else if ( $format == 'fmt1_ogg' ){
		if ( strpos( $info->fmts_ogg, 'fmt1_ogg:done' ) !== FALSE )
			return true; 
		else 
			return false; 
	} else {
		//undefined format
		return false; 
	}	
}

/* 
 * wrapper function to return the status of a particular clip
 * return '' if it does not exist; return its status otherwise
 */
function video_format_status( $info, $format ){
	
	if ( empty( $info ) || empty( $format ) )
		return ''; 

	if ( $format == 'flv' || $format == 'fmt_std'  || $format == 'fmt_dvd' || $format == 'fmt_hd' ) {
		
		return $info->$format; 

	} else if ( $format == 'fmt1_ogg' ){
		
		if ( empty( $info->fmts_ogg ) )
			return ''; 
		
		$r = preg_match( '/fmt1_ogg:([\w-]+);/', $info->fmts_ogg, $m ); 
		if ( $r === 0 || $r === false )
			return ''; 
		else 
			return $m[1]; 
		
	} 
}

/** 
 * WPCOM specific 
 * callback function from video jobs queue, to be run on transcoder
 * it transcodes backlog video into ogg.
 * Afterwards, it sends the ogg file to file server for final touch. 
 * return true if successful or video can not be transcoded; false otherwise
 */
function transcode_backlog_ogg( $job ) {
	global $wpdb; 
	
	$blog_id     = $job->data->blog_id; 
	$post_id     = $job->data->post_id; 
	$video_url   = $job->data->video_url; 
	$pass_number = $job->data->pass_number; 
	
	$info = video_get_info_by_blogpostid( $blog_id, $post_id ); 
	
	if ( empty( $info ) || video_format_done( $info, 'fmt1_ogg' ) || ($info->fmt_std != 'done' && $info->flv != 'done') )
		return false; 
	
	$bp = "blog:$blog_id, post:$post_id"; 

	/* 
	 * create a random file (eg, /tmp/video_clip1-hiking_7fEd98yC)
	 * to hold the video, which is to be downloaded
	 */
	preg_match( '|([^/]+)\.\w+$|', $video_url, $m ); 
	$random_str = video_generate_id(); 

	$file = '/tmp/video_'. $m[1] . '_' . $random_str; 

	$r = video_file_download( $video_url, $file ); 

	if ( !$r ) {
		
		$status = 'error_transcoder_cannot_download_video'; 
		update_video_info( $blog_id, $post_id, 'fmt1_ogg', $status ); 
			
		$msg = "video($bp): $status from $video_url" ; 
		error_log( $msg ); 
		
		video_cleanup( $file ); 
		return false; 	
	}
	
	$width  = $info->width;
	$height = $info->height; 	
	
	$para_array = array( 'file'          => $file, 
	                     'video_url'     => $video_url, 
	                     'blog_id'       => $blog_id,
	                     'post_id'       => $post_id,
	                     'bp'            => $bp, 
	                     'width'         => $width,
	                     'height'        => $height ); 
	
	$r1 = ogg_transcode_and_send( 'fmt1_ogg', $job,  $para_array );
	
	if ( !$r1 ){
		video_cleanup( $file ); 
		return false; 	
	}
		
	video_cleanup( $file ); 
	return true; 
}

if ( defined('IS_WPCOM') && IS_WPCOM ) {
	add_action( 'videos_transcode_backlog_ogg', 'transcode_backlog_ogg' ); 
} 

?>
