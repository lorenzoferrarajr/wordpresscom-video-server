<?php
/** 
 * video-lib: video lib functions
 * 
 * Description: 
 * this file contains various video related functions
 * 
 * Author:  Automattic Inc
 * Version: 0.9
 */
 
/**
 * Constructs image URL, given a video attachment id and format
 *
 * Given type format 'fmt_std' and "thumbnail" or "original", returns the jpg url
 * eg: http://hailin.files.wordpress.com/2008/02/14/clip1-hiking.original.jpg
 *     http://hailin.files.wordpress.com/2008/02/14/clip1-hiking.thumbnail.jpg
 *
 * @param int $post_id the attachment id
 * @param string $type one of 'thumbnail' or 'original'. The firss means a
 *      small thumb and the second -- one with the size of the video
 */
function video_image_url( $post_id, $format='fmt_std' , $type='thumbnail') {
	
		$v_url = wp_get_attachment_url( $post_id ); 
		
		if ( $format == 'fmt_std' )
			$url = preg_replace('/\.[^.]+$/', ".$type.jpg", $v_url); 
		else if ( $format == 'fmt_dvd' )
			$url = preg_replace('/\.[^.]+$/', "_dvd.$type.jpg", $v_url); 
		else if ( $format == 'fmt_hd' )
			$url = preg_replace('/\.[^.]+$/', "_hd.$type.jpg", $v_url); 
		
		return $url;
}

/** 
 * choose a file server that is live
 * CUSTOMIZE: add your own logic if there are multiple file servers
 */
function pick_fileserver( $dc = 'your_data_center' ) {
	
	return 'http://your_domain/your_fileserver'; 
}

/** 
 * choose a transcoder that is live
 * CUSTOMIZE: add your own logic if there are multiple transcoders
 */
function pick_transcoder( $dc = 'your_data_center') {
	
	return 'http://your_domain/your_transcoder';
	
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
	global $wpdb, $current_blog;
	
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
	if ( $info != false && $info->fmt_std == 'done' )
		return false; 
	
	if ( !defined('DATACENTER') )
		$dc = 'your_data_center';
	else 
		$dc = DATACENTER; 
		
	/* 
	 * video_url should indicate the current file server 
	 * so that the video is immediately available for download, 
	 * right after the initial upload
	 * eg: http://file.your_domain/wp-content/blogs.dir/8e7/2168894/files/2008/04/clip5-matt.mp4
	 */
	$path = get_attached_file( $post_id ); 

	preg_match( '|/wp-content/blogs.dir\S+?files(.+)$|i', $path, $matches ); 
	
	$fileserver = pick_fileserver( $dc ); 
	
	$video_url = $fileserver . $matches[0]; 
	$short_path = $matches[1]; 
	
	video_create_info( $blog_id, $post_id, $short_path, $dc ); 
	
	$transcoder = pick_transcoder( $dc ); 
	
	if ( empty( $transcoder ) ) {
		update_video_info( $blog_id, $post_id, 'fmt_std', 'error_no_transcoder' ); 
		return false; 
	}
	
	// fork a background child process to handle the request
	$php_exe = "/usr/local/bin/php  " ; 
	
	$cmd = $php_exe . ABSPATH . "wp-content/plugins/video/video-upload.php $video_url $blog_id $post_id $dc $transcoder > /dev/null 2>&1 &"; 
	//error_log("cmd=$cmd"); 
	
	exec($cmd); 
}


/**
 * create an initial row in videos table
 *
 * @param int $blog_id blog id of the attachment
 * @param int $post_id post_id of the attachment
 * @param string $path short attachment file path in blog, like /2008/07/video1.avi
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
		
		$info =  $wpdb->get_row( $wpdb->prepare( "SELECT * FROM videos WHERE guid=%s", $guid ) );
		
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
		
		$info =  $wpdb->get_row( $wpdb->prepare( "SELECT * FROM videos WHERE blog_id=%d AND post_id=%d", $blog_id, $post_id) );
		
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
	$wpdb->send_reads_to_masters();
	$sql_s = $wpdb->prepare( "SELECT * FROM videos WHERE blog_id=%d AND post_id=%d", $blog_id, $post_id ); 
	$r = $wpdb->get_row( $sql_s ); 
	
	if ( empty( $r ) ) {
		error_log("video ROW DOES NOT EXIST: sql = $sql_s"); 
		return false; 
	}
	
	$sql_u = $wpdb->prepare( "UPDATE videos SET $column=%s WHERE blog_id=%d AND post_id=%d", $value, $blog_id, $post_id ); 
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

?>
