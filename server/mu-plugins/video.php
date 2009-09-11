<?php 
/** 
 * video: logic to parse video shortcode and admin interface
 * Description: 
 * This file contains functions to parse video short code, such as [wpvideo hFr8Nyar], 
 * and produce embed code. It also produces media library interface
 * 
 * Author:  Automattic Inc
 * Version: 1.0
 */
 
// Define Regular Expression Constants 
define( 'WP_VIDEO_TAG', '/\[wpvideo +([a-zA-Z0-9,\#,\&,\/,;,",=, ]*?)\]/i' );
define( 'VIDEO_TAG_GUID',    '/([0-9A-Za-z]+)/i' );
define( 'VIDEO_TAG_ID',      '/id="?([0-9]*)[;,", ]?/i' );
define( 'VIDEO_TAG_WD',      '/w="?([0-9]*)[;,", ]?/i' );
define( 'VIDEO_TAG_HT',      '/h="?([0-9]*)[;,", ]?/i' );

define("FLV_DATARATE", 796 ); 
define("STD_DATARATE", 796 ); 
define("DVD_DATARATE", 1528 ); 
define("HD_DATARATE",  3128 ); 
define("FMT1_OGG_DATARATE", 1300 ); 

add_shortcode( 'wpvideo', wp_video_tag_replace );  

/** 
 * replaces [wpvideo hFr8Nyar w=400] or [wpvideo hFr8Nyar w=400 h=200] 
 * with <embed> tags so that the browser knows to play the video
 */
function wp_video_tag_replace( $attr ) {
	global $current_blog, $post; 
	
	if ( function_exists( 'faux_faux' ) ) { 
		if ( faux_faux() )
			return '';
	} 
	
	$guid   = $attr[0]; 
	$width  = $attr['w']; 
	$height = $attr['h']; 
	
	$info = video_get_info_by_guid( $guid );
	
	if ( false === $info ){
		return video_error_placeholder( array( 'text' => __( 'This video doesn&#8217;t exist' ) ) );
	} 
	
	if ( defined('IS_WPCOM') && IS_WPCOM ) { 
		if ( is_suspended( $info->blog_id ) ){
			return video_error_placeholder( array( 'text' => __( 'This video is suspended due to terms of service violation' ) ) );
		}
	} 
	
 	$different_blog = false;
	if ( $info->blog_id != $current_blog->blog_id ) {
		$different_blog = true;
		switch_to_blog( $info->blog_id );
	}

	//use some intelligence to load higher format 
	if ( video_format_done( $info, 'flv' ) )
		$format = 'flv';
	else $format = 'fmt_std';
	
	if ( $width >= 1280 ){
		if ( video_format_done( $info, 'fmt_hd' ) )
			$format = 'fmt_hd'; 
	} else if ( $width >= 640 ){
		if ( video_format_done( $info, 'fmt_dvd' ) )
			$format = 'fmt_dvd'; 
	}
	
	$para = array( 'format'  => $format,
                   'blog_id' => $info->blog_id,
                   'post_id' => $info->post_id,
                   'width'   => $width, 
                   'height'  => $height,
                   'context' => 'blog' ); 
                   
	$results = video_embed( $para );

	if ( $different_blog ) {
		restore_current_blog();
	}

	return $results;
}

//legacy function, keep here just in case we need it again
function wpcom_vidavee_asset_to_id( $asset_id ) {
	$asset_id = (int) $asset_id;
	if ( !$posts =& get_posts( array('meta_key' => '_vidavee', 'meta_value' => $asset_id, 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_parent' => 'post') ) )
		return false;
	return $posts[0]->ID;
}

function video_placeholder($args) {
	$required = array('text');
	$defaults = array('just_inner' => false, 'subtext' => '', 'width' => 400, 'height' => 300, 'context' => 'blog', 'after' => '');
	$null_defaults = array('ins_id');
	$args = named_args( $args, $required, $defaults, $null_defaults );
	if ( is_wp_error( $args ) )
		return '';
	extract( $args, EXTR_SKIP );
	$class = $width >= 380? 'video-plh-full' : 'video-plh-thumb';
	$align = $context == 'blog'? 'center' : 'left';
	$margin = $context == 'blog'? 'margin: auto' : '';
	$mid_width = $width - 16;
	$res = '';
	$ins_id = $ins_id? "id='$ins_id'" : '';
	if (!$just_inner)
		$res = <<<STYLE
	<style type="text/css">
		.video-plh {font-family: Trebuchet MS, Arial, sans-serif; text-align: $align; margin: 3px;}
		.video-plh-notice { background-color: black; color: white; display: table; #position: relative; line-height: 1.0em; text-align: $align; $margin;}
		.video-plh-mid {text-align: $align; display: table-cell; #position: absolute; #top: 50%; #left: 0; vertical-align: middle; padding: 8px;}
		.video-plh-text {#position: relative; #top: -50%; text-align: center; line-height: 35px;}
		.video-plh-sub {}
		.video-plh-full {font-size: 28px;}
		.video-plh-full .video-plh-sub {font-size: 14px;}
		.video-plh-thumb {font-size: 18px;}
		.video-plh-thumb .video-plh-sub {font-size: 12px;}
		.video-plh-sub {line-height: 120%; margin-top: 1em;}
		.video-plh-error {color: #f2643d;}
	</style>
	<ins style='text-decoration: none;' $ins_id>
STYLE;
	$res .= <<<BODY
	<div class="video-plh $class">
		<div class="video-plh-notice" style='width: {$width}px; height: {$height}px;'>
			<div class="video-plh-mid" style='width: {$mid_width}px;'>
				<div class="video-plh-text">
					$text
					<div class="video-plh-sub">$subtext</div>
				</div>
			</div>
		</div>
	</div>
	$after
BODY;
	if (!$just_inner) {
		$res .= "\t</ins>\n";
	}
	return $res;
}

function video_error_placeholder( $args ) {
	$args = named_args( $args, array( 'text' ) ); 
	$args['subtext'] = $args['text'];
	$args['text'] = '<span class="video-plh-error">'.__( 'Error' ).'</span>';
	return call_user_func('video_placeholder', $args);
}

/**
 * Given user specified dimensions, and the real dimensions
 * calculate the display width and height
 */
function video_calc_embed_dimensions( $specified_width, $specified_height, $real_width, $real_height ) {
	global $content_width;
	
	//handle null value resulted from db errors
	if ( empty($real_width) || empty($real_height) ){
		$real_width  = 400; 
		$real_height = 300;
	}
	
	$width  = (int)$specified_width; 
	$height = (int)$specified_height; 
	
	if ( 0 == $width && 0 == $height ) {
		
		$width = 400; 
		
		if ( isset( $content_width ) && $content_width > 0 ) { //scale down or up by theme
		    if ( $content_width < 400 ) 
				$width  = $content_width;
			else if ( $content_width > 630 ) 
				$width  = 640;
		}	
	}

	if ( 0 == $width ) {
		$width = (int)( ( $real_width*$height ) / $real_height );
		if ( $width %2 == 1 )   $width--;  //in sync with transcoder logic
	} elseif (0 == $height) {
		$height = (int)( ( $real_height*$width ) / $real_width );
		if ( $height %2 == 1 )  $height--; //in sync with transcoder logic
	}
	return array( $width, $height );
}

/** THIS function is so messy, after the JS change!! 
 * Checks for various states of video, and display notice accordingly
 * It also uses ajax to query and update display
 * Returns html code for embedding a video
 */
function video_embed( $args ) {
	global $wpdb, $current_blog;
	static $embed_seq = 0, $jah_included = false;

	$required = array( 'format', 'blog_id', 'post_id' );
	$default = array( 'context' => 'blog', 'just_inner' => false );
	$null_defaults = array( 'width', 'height', 'ins_id' ); 
	$args = named_args( $args, $required, $default, $null_defaults );
	if ( is_wp_error( $args ) )
		return '';
	extract( $args, EXTR_SKIP );

	if ( $post_id <= 0 )
		return '';
		
	if ( !$ins_id ) {
		$ins_id = "video-embed-$embed_seq";
		$embed_seq++;
	}
	$loop_span_id = 'plh-loop-'.$ins_id;
	
	
	$error_placeholder_body = sprintf('return video_error_placeholder(array("text" => $text, "just_inner" => %1$s, "ins_id" => %2$s, "context" => %3$s, "after" => "<span id=\'%4$s\' class=\'hidden\'>error</span>"));',
			var_export( $just_inner, true ), var_export( $ins_id, true ), var_export( $context, true ), $loop_span_id );
	$error_placeholder = create_function('$text', $error_placeholder_body);

	$info = video_get_info_by_blogpostid( $blog_id, $post_id );
	if ( $info == false)
		return $error_placeholder( __( 'This video doesn&#8217;t exist' ) );

	$status = video_format_status( $info, $format ); 
	
	if ( empty( $status ) ) { 
		return $error_placeholder(  __( 'This video doesn&#8217;t exist' ) );
	} 
	
	list( $width, $height ) = video_calc_embed_dimensions( $width, $height, $info->width, $info->height );

	if ( video_format_done( $info, $format ) ) {
	
		// video is ready, embed the flash
		if ( !is_feed() ) {
		    $loop = "<span id='$loop_span_id' class='hidden'>done</span>";
		    return $loop.video_embed_flash($format, $info->guid, $width, $height, $just_inner);
		} else {
		    // for feeds show a plain <embed>
		    return video_embed_flash($format, $info->guid, $width, $height, true);
		}
	}

	// call the functions, which need switching in the beginning, so that we don't to restore before each return
	switch_to_blog( $blog_id );
	
	$can_edit_post = current_user_can( 'edit_post', $post_id );
	$pathname = get_attached_file( $post_id ); 
	$md5_path = md5( preg_replace( '|^.*?wp-content/blogs.dir|', '', $pathname ) );
	
	if ( defined('IS_WPCOM') && IS_WPCOM ) { 
		$file_size = video_get_filesize( $md5_path ); 
	} else {
		$file_size = filesize( $pathname ); 
	}
	
	restore_current_blog();

	// do not show the video to normal users or agregators until it's ready
	if ( !$can_edit_post || is_feed() )
		return '';

	if ( 'error_cannot_transcode' == $status )
		return $error_placeholder( __('This video could not be processed because the codec is not supported.') );

	if ( in_array( $status, array( 
			'error_cannot_obtain_width_height', 
			'error_cannot_obtain_duration', 
			'error_cannot_get_thumbnail', 
			'error_ffmpeg_binary_transcode', 
			'error_ffmpeg_binary_faststart' ) ) )
		return $error_placeholder( __('This video you uploaded is encoded incorrectly, so we are unable to process it. Please try using a standard format (.avi, .mp4, .m4v, .mov, .wmv,.mpg), or different encoding software.') );

	if ( in_array( $status, array (
			'error_transcoder_cannot_download_video',
			'error_no_fileserver',
			'error_cannot_sendto_fileserver',
			'error_ffmpeg_binary_info'
		)))
		return $error_placeholder( __('Video processing system is busy. Please contact support or try later.') );

	if ( 0 === strpos( $status, 'error_' ) )
		return $error_placeholder( sprintf( __('Could not process video. Error code: %s. Please try uploading it later or contact support.'), get_video_status_code( $status ) ) );

	$text = __( 'This video is being processed' );
	$subtext = '';
	if ($eta = video_estimate_remaining_time( $info->guid, $file_size ) ) {
		$subtext .= '<p>'.sprintf( __( 'It will be ready in about <strong>%s</strong>.' ), $eta ).'</p>';
	}
	$subtext .= ( $context == 'blog' )? '<p>'._( 'Normal users won&#8217;t see this notice' ).'</p>' : '';
	$js = '';
	if (!$jah_included) {
		$js .= get_jah( 'with-script-tags' );
		$jah_included = true;
	}
	$embed_args = compact('format', 'blog_id', 'post_id', 'width', 'height', 'context', 'just_inner', 'ins_id');
	$embed_args['just_inner'] = 1;
	$query_str = '';
	foreach($embed_args as $k => $v)
		$query_str .= "&$k=".(!$v? 0 : $v);
	$js_safe_ins_id = str_replace('-', '_', $ins_id);
	$func_name = 'update_plh_'.$js_safe_ins_id;
	$js .= <<<JS
<script type="text/javascript">
function $func_name() {
	var loop = document.getElementById('$loop_span_id');
	if (!loop || loop.innerHTML != 'continue')
		return;
	jah('/wp-content/plugins/video/video-ajax.php?action=embed$query_str'+'&x='+Math.random(), '$ins_id');
	setTimeout('$func_name();', 5000);
}
setTimeout('$func_name();', 5000);
</script>
JS;
	$loop = "<span id='$loop_span_id' class='hidden'>continue</span>";
	$placeholder = video_placeholder( array( 'text' => $text, 'just_inner' => $just_inner, 'ins_id' => $ins_id, 
		'subtext' => $subtext, 'width' => $width, 'height' => $height, 'context' => $context, 'after' => $loop ) );
	return $placeholder.($just_inner? '' : $js);
}

function video_get_filesize( $md5_path ) {
	global $wpdb;
	$bytes = $wpdb->get_var("SELECT `bytes` FROM file_log WHERE md5_path = '{$md5_path}'");
	if ( intval( $bytes ) ) 
		return intval( $bytes );
	return false;
}

// how many seconds does processing of a megabyte of video takes on average
define('VIDEO_SECS_PER_MB', 0.000005);
// safety margin beyond the calculated ETA, e.g. 1.2 means 20% more
define('VIDEO_SAFETY_COEFF', 1.2);
/**
 * A rudimentary way to estimate the video processing time based on its size.
 * a better way is to survey the jobs queue and use real time metrics
 */
function video_estimate_remaining_time( $guid, $size ) {
	$info = video_get_info_by_guid( $guid );
	if ( !$info || !isset($info->date_gmt) || ($info->finish_date_gmt && $info->finish_date_gmt != '0000-00-00 00:00:00' ) ) {
		return false;
	}
	$size = intval( $size );
	if ( $size < 100*1024 )
		return false;

	$secs_per_byte = VIDEO_SECS_PER_MB;
	$eta = $size*$secs_per_byte*VIDEO_SAFETY_COEFF - (gmdate('U') - strtotime($info->date_gmt));
	$five_mins = ceil( $eta / 300 );
	if ( $five_mins <= 0)
		return __('a couple of minutes');
	if ($five_mins >= 12) {
		$hours = floor( $five_mins / 12 );
		$minutes = ( $five_mins % 12 )*5;
		if ( $minutes )
			return sprintf( __ngettext( 'one hour and %2$s minutes', '%1$s hours and %2$s minutes', $hours ), $hours, $minutes );
		else
			return sprintf( __ngettext( 'one hour', '%1$s hours', $hours ), $hours );
	} else {
		return sprintf( __ngettext( 'one minute', '%s minutes', $five_mins*5), $five_mins*5 );
	}
}

function video_embed_flash($format, $guid, $width, $height, $no_js = false) {
	static $embed_seq = -1;
	static $loaded_swfobject = false;

	$results = '';
	$embed_seq++;


	if (!$loaded_swfobject && !$no_js) {
		$results .= "<script type='text/javascript' src='/wp-content/plugins/video/swfobject2.js'></script>";
		$loaded_swfobject = true;
	}

	/*
	 * wpcom uses redirect syntax to make the url shorter
	 * open source version uses the full url to ease up configuration setups
	 */
	if ( defined('IS_WPCOM') && IS_WPCOM ) {
		
		$src = "http://v.wordpress.com/$guid"; 
		$express_install = 'http://v.wordpress.com/wp-content/plugins/video/expressInstall2.swf'; 
		
	} else {
		
		$src = "http://" . MY_VIDEO_SERVER . "/wp-content/plugins/video/flvplayer.swf?guid=$guid" . "&video_info_path=http://" . MY_VIDEO_SERVER . "/wp-content/plugins/video/video-xml.php";
		$express_install = "http://" . MY_VIDEO_SERVER . '/wp-content/plugins/video/expressInstall2.swf'; 
	}

	$results .= "<ins style='text-decoration:none;'>\n"; 
	$results .= "<div class='video-player' id='x-video-$embed_seq'>\n";

	$no_js_code = "<embed id='video-$embed_seq' src='$src' type='application/x-shockwave-flash' width='$width' height='$height' allowscriptaccess='always' allowfullscreen='true' flashvars='javascriptid=video-$embed_seq&width=$width&height=$height'> </embed>";

	if ($no_js)
		return $results.$no_js_code.'</div></ins>';

	$results .= "<script type='text/javascript'>\n";

	$results .= "var vars = {javascriptid: 'video-$embed_seq', width: '$width', height: '$height', locksize: 'no'};\n";
	$results .= "var params = {allowfullscreen: 'true', allowscriptaccess: 'always', seamlesstabbing: 'true', overstretch: 'true'};\n";
	$results .= "swfobject.embedSWF('$src', 'video-$embed_seq', '$width', '$height', '9.0.115','$express_install', vars, params);\n";
	
	$results .= "</script>\n";
	$results .= "<p id='video-$embed_seq' />";
	$results .= '</div>';
	$results .= '</ins>';

	return $results;
}

 
// little function to do some regex matching with utf8 mushing
function video_preggetit($source, $regex) {
	$match=array();
	$result = preg_match($regex, $source, $match);
	if(!$result===false) return $match[1];
	else return false;
}

/**
 * when a video is deleted, log file deletion for its associated files: 
 * video and thumbnail images, and the guid
 * so that deletion is carried out across data centers
 */
function delete_related_video_files( $post_id, $file ) { 
	
 	global $current_blog, $wpdb; 

	$blog_id = $current_blog->blog_id; 
	
	$info = video_get_info_by_blogpostid( $blog_id, $post_id );
	if ( $info == false )
		return; 
		
	//delete cache entries 
	$info = video_get_info_by_blogpostid( $blog_id, $post_id );
	$key1 = 'video-info-by-' . $blog_id . '-' . $post_id; 
	wp_cache_delete( $key1, 'video-info' ); 
	
	$key2 = 'video-info-by-' . $info->guid; 
	wp_cache_delete( $key2, 'video-info' ); 
	
	$key3 = 'video-xml-by-' . $info->guid; 
	wp_cache_delete( $key3, 'video-info' ); 
	
	$sql = $wpdb->prepare( "DELETE FROM videos WHERE blog_id=%d AND post_id=%d", $blog_id, $post_id ); 
	$wpdb->query( $sql ); 
	
	$path = dirname( $file ); 
	
	if ( video_format_done( $info, 'flv' ) ){
		$files           = unserialize( $info->flv_files ); 
		$video_file      = $path . '/' . $files['video_file']; 
		$original_image  = $path . '/' . $files['original_img']; 
		$thumbnail_image = $path . '/' . $files['thumbnail_img']; 

		if ( defined('IS_WPCOM') && IS_WPCOM ) { 
			
			log_file_deletion( $video_file, false ); 
			log_file_deletion( $original_image, false ); 
			log_file_deletion( $thumbnail_image, false ); 	
			
		} else {
			
			@unlink( $video_file ); 
			@unlink( $original_image ); 
			@unlink( $thumbnail_image ); 
		}
	} 
	
	if ( video_format_done( $info, 'fmt_std' ) ){ 
		$files           = unserialize( $info->std_files ); 
		$video_file      = $path . '/' . $files['video_file']; 
		$original_image  = $path . '/' . $files['original_img']; 
		$thumbnail_image = $path . '/' . $files['thumbnail_img']; 

		if ( defined('IS_WPCOM') && IS_WPCOM ) { 
			
			//don't update disk usage since these are internal files
			log_file_deletion( $video_file, false  ); 
			log_file_deletion( $original_image, false  ); 
			log_file_deletion( $thumbnail_image, false  ); 
			
		} else {
			
			@unlink( $video_file ); 
			@unlink( $original_image ); 
			@unlink( $thumbnail_image ); 
		}
	}
	
	if ( video_format_done( $info, 'fmt_dvd' ) ){ 
		$files           = unserialize( $info->dvd_files ); 
		$video_file      = $path . '/' . $files['video_file']; 
		$original_image  = $path . '/' . $files['original_img']; 
		$thumbnail_image = $path . '/' . $files['thumbnail_img']; 

		if ( defined('IS_WPCOM') && IS_WPCOM ) { 
			
			log_file_deletion( $video_file, false  ); 
			log_file_deletion( $original_image, false  ); 
			log_file_deletion( $thumbnail_image, false  ); 
			
		} else {
			
			@unlink( $video_file ); 
			@unlink( $original_image ); 
			@unlink( $thumbnail_image ); 
		}
	}
	
	if ( video_format_done( $info, 'fmt_hd' ) ){ 
		$files           = unserialize( $info->hd_files ); 
		$video_file      = $path . '/' . $files['video_file']; 
		$original_image  = $path . '/' . $files['original_img']; 
		$thumbnail_image = $path . '/' . $files['thumbnail_img']; 
		
		if ( defined('IS_WPCOM') && IS_WPCOM ) { 
			
			log_file_deletion( $video_file, false  ); 
			log_file_deletion( $original_image, false  ); 
			log_file_deletion( $thumbnail_image, false  ); 
			
		} else {
			
			@unlink( $video_file ); 
			@unlink( $original_image ); 
			@unlink( $thumbnail_image ); 
		}
	}
	
	if ( video_format_done( $info, 'fmt1_ogg' ) ){ 
		
		$video_file = preg_replace( '/\.[^.]+/', '_fmt1.ogv', $file ); 
		
		if ( defined('IS_WPCOM') && IS_WPCOM ) { 
			log_file_deletion( $video_file, false  ); 
		} else { 
			@unlink( $video_file ); 
		}
	} 
	
	if ( !empty( $info->thumbnail_files ) ){ 
		$files = unserialize( $info->thumbnail_files ); 
		foreach ( $files as $f ){
			$t_file = $path . '/' . $f; 
			
			if ( defined('IS_WPCOM') && IS_WPCOM ) 
				log_file_deletion( $t_file, false  ); 
			else 
				@unlink( $t_file ); 
		}
	}

} 

function get_video_status_code( $error_string ) { 
	
	$video_status = array ( 
		0 => 'done', 
		1 => 'initiated',
		2 => 'sending_to_fileserver', 
		3 => 'error_transcoder_cannot_download_video',
		4 => 'error_cannot_obtain_width_height',
		5 => 'error_cannot_transcode',
		6 => 'error_cannot_get_thumbnail',
		7 => 'error_auth_with_fileserver',
		8 => 'error_fileserver_cannot_receive_all_files',
		9 => 'error_move_uploaded_file', 
		10 => 'error_cannot_obtain_duration', 
		
		20 => 'error_no_fileserver',
		21 => 'error_cannot_sendto_fileserver',
		22 => 'error_ffmpeg_binary_info', 
		23 => 'error_ffmpeg_binary_transcode', 
		24 => 'error_ffmpeg_binary_faststart', 
		25 => 'error_ffmpeg2theora_binary_transcode', 
		
		30 => 'transcoder_received_request',
		31 => 'fileserver_received_request', 
		32 => 'try_sendto_fileserver_later',
		33 => 'try_download_later' ); 
	
	foreach ( $video_status as $c => $v ) { 
		if ( $error_string == $v )
			return $c; 
	} 
	
	return -1; 
}

// produce HTML for the video title
function video_display_title( $post_id, $info ) {
	
	$id = "attachments[$post_id][video-title]"; 
	$out = "<input type='text' name='$id' id='$id' value='" .  attribute_escape( $info->title ) .  "' />"; 
	
	return $out; 
}

function video_set_title( $post, $attachment ) {
	global $current_blog;
	
	if ( isset($_POST['attachments'][$post['ID']]) ) {
		
		$value = $_POST['attachments'][$post['ID']]['video-title']; 
		$value = stripslashes( $value ); 
		
		if ( !empty( $value )){ 
			
			$info = video_get_info_by_blogpostid( $current_blog->blog_id, $post['ID'] ); 
			if ( $value != $info->title ) 
				update_video_info( $current_blog->blog_id, $post['ID'], 'title', $value );
		}
	}
	// do not break the filter
	return $post;
}

// produce HTML for the video description
function video_display_description( $post_id, $info ) {
	
	$id = "attachments[$post_id][video-description]"; 
	$out = "<textarea name='$id' id='$id' />" . attribute_escape( $info->description) . "</textarea>"; 
	
	return $out; 
}

function video_set_description( $post, $attachment ) {
	global $current_blog;
	
	if ( isset($_POST['attachments'][$post['ID']]) ) {
		
		$value = $_POST['attachments'][$post['ID']]['video-description']; 
		$value = stripslashes( $value ); 
		
		if ( !empty( $value )){ 
			
			$info = video_get_info_by_blogpostid( $current_blog->blog_id, $post['ID'] ); 
			if ( $value != $info->description )
				update_video_info( $current_blog->blog_id, $post['ID'], 'description', $value );
		}
	}
	// do not break the filter
	return $post;
}

 // produce HTML for the display embedcode checkbox 
function video_display_embed_choice( $post_id, $info ) {
	
	$checked = (1 == $info->display_embed)? ' checked="checked"' : '';
	$id = "attachments[$post_id][display_embed]"; 
	$out  = "<input type='checkbox' name='$id' id='$id' $checked />";
	$out .= "<label for='$id'>" . __( 'Display embed code and allow external sites to embed this video' ) . "</label>";
	return $out;	
}

function video_set_display_embed( $post, $attachment ) {
	global $current_blog;
	
	if ( isset($_POST['attachments'][$post['ID']]) ) {
		
		$value = (isset($_POST['attachments'][$post['ID']]['display_embed']))? 1 : 0;
		
		$info = video_get_info_by_blogpostid( $current_blog->blog_id, $post['ID'] ); 
		
		if ( $value != $info->display_embed )
			update_video_info( $current_blog->blog_id, $post['ID'], 'display_embed', $value );
	}
	// do not break the filter
	return $post;
}

// produce HTML for the rating radio button
function video_display_rating( $post_id, $info ) {
	
	$ratings = array('G', 'PG-13', 'R-17', 'X-18' ); 
	
	foreach( $ratings as $r ) {
		$checked = ( $info->rating == $r ) ? ' checked="checked"' : '';
		$id = "attachments[$post_id][rating]";
		$out .= "<input type='radio' name='$id' id='$id' value='$r' $checked />"; 
		$out .="<label for='$id'>" . __( $r ) . "</label>";
	}
	return $out;	
}

function video_set_rating( $post, $attachment ) {
	global $current_blog;
	
	if ( isset($_POST['attachments'][$post['ID']]) ) {
		
		$value = $_POST['attachments'][$post['ID']]['rating']; 
		if ( !empty( $value )){ 
			
			$info = video_get_info_by_blogpostid( $current_blog->blog_id, $post['ID'] ); 
			if ( $value != $info->rating )
				update_video_info( $current_blog->blog_id, $post['ID'], 'rating', $value );
		}
	}
	// do not break the filter
	return $post;
}

function video_fields_to_edit( $fields, $post ) {
	global $current_blog;
	
	if ( !is_video( $post ) ) {
		return $fields;
	}
	
	$blog_id = $current_blog->blog_id; 
	$post_id = $post->ID; 
	
	unset($fields['url']);
	unset( $fields['post_title'] ); 
	unset( $fields['post_excerpt'] ); 
	unset( $fields['post_content'] ); 
	
	$info = video_get_info_by_blogpostid( $blog_id, $post_id ); 

	if ( empty( $info ) ) // earlier flv videos which have failed in transcoding are not stored in videos table
		$format = 'flv'; 
	else {
		if ( video_format_done( $info, 'flv' ) )
			$format = 'flv';
		else 
			$format = 'fmt_std'; 
	}
	
	$status = video_get_status( $blog_id, $post_id, $format );
	$message = '';
	
	$embed_args = array( 'format' => $format, 'blog_id' => $blog_id, 'post_id' => $post_id,
		                 'width' => 360, 'context' => 'admin' );


	if ( $status != 'vidavee' ) {

		$fields['video-title'] = array(
			'label' => __('Title'),
			'input' => 'html',
			'html'  => video_display_title( $post->ID, $info ),
			'helps' => __('Title will appear on the first frame of your video'),
		);
		
		$fields['video-description'] = array(
			'label' => __('Description'),
			'input' => 'html',
			'html'  => video_display_description( $post->ID, $info )
		);
		
		$fields['display_embed'] = array(
			'label' => __('Embed'),
			'input' => 'html',
			'html'  => video_display_embed_choice( $post->ID, $info )
		);
		
		$fields['video-rating'] = array(
			'label' => __('Rating'),
			'input' => 'html',
			'html'  => video_display_rating( $post->ID, $info )
		);
		
		$video_html = video_embed( $embed_args ); 
		
		$video_html .= '<br /><p>To change the default thumbnail image, play the video and click "Capture Thumbnail" button.</p>';
	

		if ( 0 === strpos( $status, 'error_' ) ) {
			$video_html .= '<script type="text/javascript">jQuery(function($){$("[name=\'send['.$post_id.']\']").hide();});</script>';
		} else {
			
			//videopress plugin access point
			if ( (isset( $_REQUEST['video_plugin'] ) && $_REQUEST['video_plugin'] == 1) || (isset( $_SERVER['HTTP_REFERER'] ) && preg_match( "|video_plugin=1|", $_SERVER['HTTP_REFERER'] )) ) {
				
				$embed = "[videopress {$info->guid}]";

				$video_html = '<p><strong>&nbsp;&nbsp;&nbsp;' . __( 'Shortcode for embedding' ) . ": </strong> <input type='text' id='plugin-embed' style='width: 180px;' value='{$embed}' onclick='this.focus();this.select();' /><br />&nbsp;&nbsp;&nbsp;copy and paste this into your post</p>" . $video_html;
			
			} else { 
				$video_html = '<p>'.__('Shortcode for embedding:' ).' <strong><code>'.video_send_to_editor_shortcode( '', $post_id, '' ).'</code></strong></p>'.$video_html;
			} 
		}

		$fields['video-preview'] = array(
			'label' => __( 'Preview and Insert' ),
			'input' => 'html',
			'html'  => $video_html,
		); 
	} else if ( $status == 'vidavee' ) {
		
		//convertion routine missed a handful of old vidavee videos, and we don't know where they are, need to manually update it 
		$message = __('This video is currently served by Vidavee, we will convert it for you soon. '); 
		wp_mail( VIDEO_ADMIN_EMAIL, "[Unconverted vidavee video]", "$current_blog->domain, blog_id:$blog_id, post_id:$post_id" ); 
		
	}	
	
	if ( $message ) {
		$fields['video-status'] = array(
			'label' => __( 'Status '),
			'input' => 'html',
			'html' => $message,
		);
	}

	return $fields;
}

function video_get_status( $blog_id, $post_id, $format='fmt_std' ) {
	global $wpdb;
	
	$info = video_get_info_by_blogpostid( $blog_id, $post_id );
	
	if ( !empty( $info ) )	
		return $info->$format; 
		
	// below are kept here for backward compatibility reasons
	$status = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_wp_flv_status' AND post_id = %d",  $post_id ));
	if ( !empty( $status ))
		return $status;
	
	$v = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_vidavee' AND post_id = %d",  $post_id ));
	if ( !empty( $v ))
		return 'vidavee';
		
	return 'unknown';
}

function video_send_to_editor_shortcode( $html, $post_id, $attachment ) {
	global $current_blog;
	
	$blog_id = $current_blog->blog_id; 
	
	if ( !is_video( $post_id ) )  {
		return $html;
	}
	
	$info = video_get_info_by_blogpostid( $blog_id, $post_id );
	
	if ( empty( $info ) )
		return '';
		
	if ( video_format_done( $info, 'flv' ) )
		return "[wpvideo $info->guid]"; 
	
	$status = $info->fmt_std; 
	if ( is_null( $status ) || substr( $status, 0, 5 )=='error' )
		return '';
	
	//display the guid even when the video is under processing
	return "[wpvideo $info->guid]"; 
	
}

function video_send_to_editor_url( $html, $url, $title ) {
	
	$url = trim( $url );
	$url = preg_replace( '/^\[youtube[= ](.+)\]/i', '$1', $url );
	$url = preg_replace( '/^\[googlevideo[= ](.+)\]/i', '$1', $url );

	if ( preg_match( '|^(?:http://)?(\[dailymotion id=.+\])|i', $url, $matches ) )
		return $matches[1];

	$shortcode = '';
	if ( preg_match( '/^http:\/\/video\.google\.[a-z]+/i', $url ) )
		$shortcode = "[googlevideo=$url]";
	elseif ( preg_match( '/^http:\/\/[a-z]{2,3}\.youtube\.com\//i', $url ) || preg_match( '/^http:\/\/youtube.com\//i', $url ) )
		$shortcode = "[youtube=$url]";
	elseif ( preg_match( '/^http:\/\/www.livevideo.com\//i', $url ) || preg_match( '/^http:\/\/livevideo.com\//i', $url ) ) {
		$id = preg_replace( '/http:\/\/www.livevideo.com\/video\/([0-9a-z]+)\/.*/i', '$1', $url );
		if ( $id ) $shortcode = "[livevideo id=$id]";
	}
	//TODO: error if no suitable video found?
	return $shortcode;
}

function video_shortcodes_help($video_form) {
	return '
	<table class="describe"><tbody>
		<tr>
			<th valign="top" scope="row" class="label">
				<span class="alignleft"><label for="insertonly[href]">' . __('URL') . '</label></span>
				<span class="alignright"><abbr title="required" class="required">*</abbr></span>
			</th>
			<td class="field"><input id="insertonly[href]" name="insertonly[href]" value="" type="text"></td>
		</tr>
		<tr>
			<td colspan="2">
				<p>Paste your YouTube or Google Video URL above, or use the examples below.</p>
				<ul class="short-code-list">
					<li>'.sprintf( __('<a href="%s" target="_blank">YouTube instructions</a> %s'), 'http://support.wordpress.com/videos/youtube/', '<code>[youtube=http://www.youtube.com/watch?v=AgEmZ39EtFk]</code>' ).'</li>
					<li>'.sprintf( __('<a href="%s" target="_blank">Google instructions</a> %s') , 'http://support.wordpress.com/videos/google-video/', '<code>[googlevideo=http://video.google.com/googleplayer.swf?docId=-8459301055248673864]</code>' ).'</li>
					<li>'.sprintf( __('<a href="%s" target="_blank">DailyMotion instructions</a> %s'), 'http://support.wordpress.com/videos/dailymotion/', '<code>[dailymotion id=5zYRy1JLhuGlP3BGw]</code>' ).'</li>
					<li>'.sprintf( __('<a href="%s" target="_blank">Post to WordPress button</a> %s'), 'http://support.wordpress.com/videos/vodpod/', 'Use VodPod to post videos from hundreds of sites (beta)' ).'</li>
				</ul>
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="submit" class="button" name="insertonlybutton" value="' . attribute_escape(__('Insert into Post')) . '" />
			</td>
		</tr>
	</tbody></table>
	';
}

/**
 * include wp video links in feed <description>
 * and <content:encoded>
 */
 function wp_add_videolink( $text ){ 
 	
 	if ( !is_feed() ) 
 		return $text; 
 		
 	$post_content = get_the_content(); 
 	
 	$r = preg_match_all( WP_VIDEO_TAG, $post_content, $matches, PREG_SET_ORDER ); 
 	
 	if ( $r === false || $r === 0 ) 
 		return $text; 
 
 	$vlink = $text; 
 	foreach ( $matches as $m ) { 
 		
 		$guid = video_preggetit( $m[1], VIDEO_TAG_GUID ); 
 		$info = video_get_info_by_guid( $guid );
 		if ( empty($info) )
 			continue; 
 			
 		$v_name  = preg_replace( '/\.\w+/', '', basename( $info->path ) ); 
 		
 		$post_url = get_permalink(); 
	
		$img_name = video_preview_image_name( 'fmt_std', $info ); 
		
		if ( defined('IS_WPCOM') && IS_WPCOM ) {
			$image_url = 'http://cdn.videos.wordpress.com/' . $guid . '/' . $img_name; 
		} else { 
			$v_url = get_the_guid( $info->post_id ); 
			$image_url = preg_replace( '#[^/]+\.\w+$#', $img_name, $v_url ); 
		}
 		
 		$name = preg_replace( '/\.\w+/', '', basename( $info->path ) ); 
 		
 		$vlink .= "<br /><a href='$post_url'><img width='160' height='120' src='$image_url' /> </a>";
 	}
 	
 	return $vlink; 
 }
 
 /**
 * if a video has multiple formats, generate <media:group>; otherwise generate <media:content>
 * and other mrss entries
 */
function one_video_mrss( $blog_id, $guid ){
	
	$mp4_types = array( 'fmt_std', 'fmt_dvd', 'fmt_hd' ); 
	
	$info = video_get_info_by_guid( $guid );
	
	if ( empty($info) )
		return; 

	if ( $info->display_embed == 0 )
		return; 
	
	$blog_id     = $info->blog_id; 
	$post_id     = $info->post_id; 
	$title       = $info->title;
	$description = $info->description; 
	$v_name   = preg_replace( '/\.\w+/', '', basename( $info->path ) );
	
	switch_to_blog( $blog_id );

	$n = preg_match( '/(\d+):(\d+):(\d+)./', $info->duration, $match); 
	$total_seconds = 3600 * $match[1] + 60 * $match[2] + $match[3]; 
	
	if ( video_format_done( $info, 'fmt_hd' ) ){
		
		$types[] = 'fmt_hd';
		if ( !isset( $highest_resolution ) )
			$highest_resolution = 'fmt_hd'; 
	} 
	
	if ( video_format_done( $info, 'fmt_dvd' ) ) {
		
		$types[] = 'fmt_dvd';
		if ( !isset( $highest_resolution ) )
			$highest_resolution = 'fmt_dvd'; 
	} 
	
	if ( video_format_done( $info, 'fmt_std' ) ){ 
		
		$types[] = 'fmt_std';
		if ( !isset( $highest_resolution ) )
			$highest_resolution = 'fmt_std'; 
	}
	
	if ( video_format_done( $info, 'flv' ) ){ 
		
		$types[] = 'flv'; 
		if ( !isset( $highest_resolution ) )
			$highest_resolution = 'flv'; 
	} 
	
	if ( video_format_done( $info, 'fmt1_ogg' ) ){ 
		
		$types[] = 'fmt1_ogg'; 
		if ( !isset( $highest_resolution ) )
			$highest_resolution = 'fmt1_ogg'; 
	} 

	$num = -1; 
	foreach ( $types as $type ){
		
		$num++; 
		if ( $type == 'flv'	) {
		
			$width  = 400;
			if ( empty($info->height) || empty($info->width) ) //handle db error case
				$height = 300; 
			else 
				$height = (int)( 400 * ($info->height/$info->width) ); 
				
			$fileSize  = FLV_DATARATE * $total_seconds * 1024/8; 
			
			$files = unserialize( $info->flv_files ); 
			$video_name = $files[ 'video_file' ]; 
			
		} else if ( $type == 'fmt_std'	) {
		
			$width  = 400;
			if ( empty($info->height) || empty($info->width) ) 
				$height = 300; 
			else 
				$height = (int)( 400 * ($info->height/$info->width) ); 
			
			$fileSize  = STD_DATARATE * $total_seconds * 1024/8; 
			
			$files = unserialize( $info->std_files ); 
			$video_name = $files[ 'video_file' ]; 
			
		} else if ( $type == 'fmt_dvd'	) {
		
			$width  = 640;
			if ( empty($info->height) || empty($info->width) ) 
				$height = 360; 
			else 
				$height = (int)( 640 * ($info->height/$info->width) );
				
			$fileSize  = DVD_DATARATE * $total_seconds * 1024/8; 
						
			$files = unserialize( $info->dvd_files ); 
			$video_name = $files[ 'video_file' ]; 
			
		} else if ( $type == 'fmt_hd'	) {
		
			$width  = 1280;
			if ( empty($info->height) || empty($info->width) ) 
				$height = 720; 
			else 
				$height = (int)( 1280 * ($info->height/$info->width) ); 
			
			$fileSize  = HD_DATARATE * $total_seconds * 1024/8; 
			
			$files = unserialize( $info->hd_files ); 
			$video_name = $files[ 'video_file' ]; 
			
		} else if ( $type == 'fmt1_ogg'	) {
		
			$width  = 400;
			if ( empty($info->height) || empty($info->width) ) 
				$height = 400; 
			else 
				$height = (int)( 400 * ($info->height/$info->width) ); 
			
			$fileSize  = FMT1_OGG_DATARATE * $total_seconds * 1024/8; 
		
			$video_name = $v_name . '_fmt1.ogv'; 
		} 
		
		if ( $width %2 == 1 )   $width--; //in sync with logic in transcoder 
		if ( $height %2 == 1 )  $height--; 
	
		if ( defined('IS_WPCOM') && IS_WPCOM ) {
			$url = 'http://cdn.videos.wordpress.com/' . $guid . '/' . $video_name; 
		} else {
			$v_url = get_the_guid( $info->post_id ); 
			$url = preg_replace( '#[^/]+\.\w+$#', $video_name, $v_url ); 
		}
		
		$isDefault  = 'false'; 
		if ( $type == $highest_resolution ) {
			
			$isDefault  = 'true'; 
			
			if ( $type == 'flv' ) 
				echo "\t<enclosure url=\"$url\" length=\"$fileSize\" type=\"video/x-flv\" />\n"; 
			else  if ( in_array( $type, $mp4_types ) )
				echo "\t<enclosure url=\"$url\" length=\"$fileSize\" type=\"video/mp4\" />\n"; 
			else if ( $type == 'fmt1_ogg')
				echo "\t<enclosure url=\"$url\" length=\"$fileSize\" type=\"video/ogg\" />\n"; 
		}
	
		$all_formats[$num]['content']['attr']['url'] = $url; 
		$all_formats[$num]['content']['attr']['fileSize'] = $fileSize; 
		
		if ( $type == 'flv')
			$all_formats[$num]['content']['attr']['type'] = 'video/x-flv'; 
		else if ( in_array( $type, $mp4_types ) )
			$all_formats[$num]['content']['attr']['type'] = 'video/mp4'; 
		else if ( $type == 'fmt1_ogg')
			$all_formats[$num]['content']['attr']['type'] = 'video/ogg'; 
			
		$all_formats[$num]['content']['attr']['medium']    = 'video';
		$all_formats[$num]['content']['attr']['isDefault'] = $isDefault; 
		$all_formats[$num]['content']['attr']['duration']  = $total_seconds; 
		$all_formats[$num]['content']['attr']['width']     = $width; 
		$all_formats[$num]['content']['attr']['height']    = $height; 
	}
	
	$mpaa = array('G'     => 'g', 
	              'PG-13' => 'pg-13',
	              'R-17'  => 'nc-17',
	              'X-18'  => 'r' ); 
	$aux['rating']['attr']['scheme'] = 'urn:mpaa';
	$aux['rating']['children'][] = $mpaa[ $info->rating ]; 
	
	if ( empty( $title ) ){ 
		$title = $v_name; 
	} 
	
	$aux['title']['attr']['type'] = 'plain';
	$aux['title']['children'][0] = esc_html( $title ); 
	
	if ( !empty( $description ) ) {
		$aux['description']['attr']['type'] = 'plain';
		$aux['description']['children'][0] = $description; 
	} 
	
	$img_name = video_preview_image_name( $type, $info ); 
	
	if ( defined('IS_WPCOM') && IS_WPCOM ) {
		$thumbnail_img = 'http://cdn.videos.wordpress.com/' . $guid . '/' . $img_name; 
	} else {
		$v_url = get_the_guid( $info->post_id ); 
		$thumbnail_img = preg_replace( '#[^/]+\.\w+$#', $img_name, $v_url ); 
	}
		
	$aux['thumbnail']['attr']['url'] = $thumbnail_img; 
	
	$thumb_width = 256; 
	if ( empty($info->height) || empty($info->width) ) 
		$thumb_height = 192; 
	else 
		$thumb_height = (int)( $thumb_width * ($info->height/$info->width) ); 
		
	$aux['thumbnail']['attr']['width']  = $thumb_width;
	$aux['thumbnail']['attr']['height'] = $thumb_height; 
	
	if ( defined('IS_WPCOM') && IS_WPCOM ) {
		$player = "http://v.wordpress.com/$guid"; 
	} else {
		$player = "http://" . MY_VIDEO_SERVER . "/wp-content/plugins/video/flvplayer.swf?guid=$guid" . "&video_info_path=http://" . MY_VIDEO_SERVER . "/wp-content/plugins/video/video-xml.php";
	}
	
	$aux['player']['attr']['url'] = $player; 
	$aux['player']['attr']['width']  = 400; 
	$aux['player']['attr']['height'] = 300; 
	
	if ( count( $types ) == 1 ){

		$all_formats[0]['content']['children'] = $aux; 
		$v_mrss['content'] = $all_formats[0]['content']; 
		
	} else {
		
		for ( $i=0; $i <= $num; $i++ ){
			$v_mrss['group']['children'][$i] = $all_formats[$i]; 
		} 
		$v_mrss['group']['children'][$i] = $aux; 
	}
	
	restore_current_blog();
	
	return $v_mrss ; 
}

function mrss_video( $media ) {
	global $current_blog; 
	
	$blog_id = $current_blog->blog_id; 
	$post_content = get_the_content(); 
 	
 	$r = preg_match_all( WP_VIDEO_TAG, $post_content, $matches, PREG_SET_ORDER ); 
 	
 	if ( $r === false || $r === 0 ) 
 		return $media; 
 
 	foreach ( $matches as $m ) { 
 		
 		$guid = video_preggetit( $m[1], VIDEO_TAG_GUID ); 
 		$media[] = one_video_mrss( $blog_id, $guid ); 
 	}
 	
 	return $media; 
	
}
add_filter( 'mrss_media', 'mrss_video' ); 


function video_init() {
	
	add_filter( 'type_url_form_video', 'video_shortcodes_help');
	add_filter( 'video_send_to_editor_url', 'video_send_to_editor_url', 10, 3 );

	add_action('add_attachment', 'remote_transcode_one_video');

	add_action( 'delete_related_video_files', 'delete_related_video_files', 10, 2 );
	add_filter( 'media_send_to_editor', 'video_send_to_editor_shortcode', 10, 3 );
	add_filter( 'attachment_fields_to_edit', 'video_fields_to_edit', 10, 2 );
	add_filter( 'attachment_fields_to_save', 'video_set_title', 10, 2 );
	add_filter( 'attachment_fields_to_save', 'video_set_description', 10, 2 );
	add_filter( 'attachment_fields_to_save', 'video_set_display_embed', 10, 2 );
	add_filter( 'attachment_fields_to_save', 'video_set_rating', 10, 2 );
	
	add_filter('get_the_excerpt', 'wp_add_videolink', 20);
	add_filter('the_content', 'wp_add_videolink', 20); 
	
}

add_action( 'init', 'video_init' );

?>
