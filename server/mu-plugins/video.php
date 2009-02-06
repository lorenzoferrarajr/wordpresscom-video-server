<?php 
/** 
 * video: logic to parse video shortcode and admin interface
 * Description: 
 * This file contains functions to parse video short code, such as [wpvideo hFr8Nyar], 
 * and produce embed code. It also produces media library interface
 * 
 * Author:  Automattic Inc
 * Version: 0.9
 */

// Define Regular Expression Constants 
define( 'WP_VIDEO_TAG', '/\[wpvideo +([a-zA-Z0-9,\#,\&,\/,;,",=, ]*?)\]/i' );
define( 'VIDEO_TAG_GUID',    '/([0-9A-Za-z]+)/i' );
define( 'VIDEO_TAG_ID',      '/id="?([0-9]*)[;,", ]?/i' );
define( 'VIDEO_TAG_WD',      '/w="?([0-9]*)[;,", ]?/i' );
define( 'VIDEO_TAG_HT',      '/h="?([0-9]*)[;,", ]?/i' );


add_shortcode( 'wpvideo', wp_video_tag_replace ); 
add_shortcode( 'video', vidavee_video_tag_replace ); 

/** 
 * replaces [wpvideo hFr8Nyar w=400] or [wpvideo hFr8Nyar w=400 h=200] 
 * with <embed> tags so that the browser knows to play the video
 */
function wp_video_tag_replace( $attr ) {
	if ( faux_faux() )
		return '';
	global $current_blog, $post; 
	
	$guid   = $attr[0]; 
	$width  = $attr['w']; 
	$height = $attr['h']; 
	
	$info = video_get_info_by_guid( $guid );
	
	if ( false === $info )
		return video_error_placeholder( array( 'text' => __( 'This video doesn&#8217;t exist' ) ) );

 	$different_blog = false;
	if ( $info->blog_id != $current_blog->blog_id ) {
		$different_blog = true;
		switch_to_blog( $info->blog_id );
	}

	//use some intelligence to load higher format 
	if ( $info->flv == 'done' )
		$format = 'flv';
	else $format = 'fmt_std';
	
	if ( $width >= 1280 ){
		if ( $info->fmt_hd == 'done' )
			$format = 'fmt_hd'; 
	} else if ( $width >= 640 ){
		if ( $info->fmt_dvd == 'done' )
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

function video_placeholder($args) {
	$required = array('text');
	$defaults = array('just_inner' => false, 'subtext' => '', 'width' => 320, 'height' => 240, 'context' => 'blog', 'after' => '');
	$null_defaults = array('ins_id');
	$args = named_args( $args, $required, $defaults, $null_defaults );
	if ( is_wp_error( $args ) )
		return '';
	extract( $args, EXTR_SKIP );
	$class = $width >= 256? 'video-plh-full' : 'video-plh-thumb';
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

/** CUSTOMIZE: you can just call video_embed_flash() if you don't care about the ajax thing
 * This function a little messy, with the inlined JS
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

	if ( is_feed() || $post_id <= 0 ) 
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

	$status = $info->$format;
	if ( is_null( $status ) )
		return $error_placeholder(  __( 'This video doesn&#8217;t exist' ) );

	list( $width, $height ) = video_calc_embed_dimensions( $width, $height, $info->width, $info->height );	

	if ( $status == 'done' ) {
		// video is ready, embed the flash
		$loop = "<span id='$loop_span_id' class='hidden'>done</span>";
		return $loop.video_embed_flash($format, $info->guid, $width, $height, $just_inner);
	}

	// call the functions, which need switching in the beginning
	// so that we don't to restore before each return
	switch_to_blog( $blog_id );
	$can_edit_post = current_user_can( 'edit_post', $post_id );
	$md5_path = md5( preg_replace( '|^.*?wp-content/blogs.dir|', '', get_attached_file( $post_id ) ) );
	restore_current_blog();

	// do not show the video to normal users until it's ready
	if ( !$can_edit_post )
		return '';

	if ( 'error_cannot_transcode' == $status )
		return $error_placeholder( __('This video could not be processed because the codec is not supported.') );

	if ( in_array( $status, array( 'error_cannot_obtain_width_height', 'error_cannot_obtain_duration' ) ) )
		return $error_placeholder( __('This video you uploaded is encoded incorrectly, so we are unable to process it. Please try using a standard format (.avi, .mp4, .mov, .wmv,.mpg), or different encoding software.') );

	if ( in_array( $status, array( 'error_no_transcoder', 'error_no_fileserver' ) ) )
		return $error_placeholder( __('Video processing system is temporarily unavailable. Please contact support or try later.') );

	if ( 0 === strpos( $status, 'error_' ) )
		return $error_placeholder( sprintf( __('Could not process video. Error code: %s. Please try uploading it later or contact support.'), get_video_status_code( $status ) ) );

	$text = __( 'This video is being processed' );
	$subtext = '';
	if ($eta = video_estimate_remaining_time( $info->guid, video_get_filesize( $md5_path ) ) ) {
		$subtext .= '<p>'.sprintf( __( 'It will be ready in about <strong>%s</strong>.' ), $eta ).'</p>';
	}
	$subtext .= ( $context == 'blog' )? '<p>'._( 'Normal users won&#8217;t see this notice' ).'</p>' : '';
	$js = '';
	if (!$jah_included) {
		$js .= get_jah(true);
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

	$results .= "<ins style='text-decoration:none;'>\n"; 
	$results .= "<div class='video-player' id='x-video-$embed_seq'>\n";

	$no_js_code = "<embed id='video-$embed_seq' src='http://mydomain.com/$guid' type='application/x-shockwave-flash' width='$width' height='$height' allowscriptaccess='always' allowfullscreen='true' flashvars='javascriptid=video-$embed_seq&width=$width&height=$height'> </embed>";

	if ($no_js)
		return $results.$no_js_code.'</div></ins>';

	$results .= "<script type='text/javascript'>\n";

	$results .= "var vars = {javascriptid: 'video-$embed_seq', width: '$width', height: '$height', locksize: 'no'};\n";
	$results .= "var params = {allowfullscreen: 'true', allowscriptaccess: 'always', seamlesstabbing: 'true', overstretch: 'true'};\n";
	$results .= "swfobject.embedSWF('http://mydomain.com/". $guid . "', 'video-$embed_seq', '$width', '$height', '9.0.115','http://mydomain.com/wp-content/plugins/video/expressInstall2.swf', vars, params);\n";
	
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
	
	$key3 = 'video-xml2-by-' . $info->guid; 
	wp_cache_delete( $key3, 'video-info' ); 
	
	$sql = $wpdb->prepare( "DELETE FROM videos WHERE blog_id=%d AND post_id=%d", $blog_id, $post_id ); 
	$wpdb->query( $sql ); 
	
	if ( $info->flv == 'done' ){
		
		$video_file      = preg_replace( '/\.[^.]+$/', ".flv", $file );
		$original_image  = preg_replace( '/\.[^.]+$/', ".original.jpg", $file );
		$thumbnail_image = preg_replace( '/\.[^.]+$/', ".thumbnail.jpg", $file );
		
		/* CUSTOMIZE: delete those files on your system
		 * log_file_deletion( $video_file ); 
		 * log_file_deletion( $original_image ); 
		 * log_file_deletion( $thumbnail_image ); 
		 */ 
		
	} 
	if ( $info->fmt_std == 'done' ){ 
		$video_file      = preg_replace( '/\.[^.]+$/', ".mp4", $file );
		$original_image  = preg_replace( '/\.[^.]+$/', ".original.jpg", $file );
		$thumbnail_image = preg_replace( '/\.[^.]+$/', ".thumbnail.jpg", $file );
		
		/* CUSTOMIZE: delete those files on your system
		 * log_file_deletion( $video_file ); 
		 * log_file_deletion( $original_image ); 
		 * log_file_deletion( $thumbnail_image ); 
		 */ 
	}
	if ( $info->fmt_dvd == 'done' ){ 
		$video_file      = preg_replace( '/\.[^.]+$/', "_dvd.mp4", $file );
		$original_image  = preg_replace( '/\.[^.]+$/', "_dvd.original.jpg", $file );
		$thumbnail_image = preg_replace( '/\.[^.]+$/', "_dvd.thumbnail.jpg", $file );
		
		/* CUSTOMIZE: delete those files on your system
		 * log_file_deletion( $video_file ); 
		 * log_file_deletion( $original_image ); 
		 * log_file_deletion( $thumbnail_image ); 
		 */ 
	}
	if ( $info->fmt_hd == 'done' ){ 
		$video_file      = preg_replace( '/\.[^.]+$/', "_hd.mp4", $file );
		$original_image  = preg_replace( '/\.[^.]+$/', "_hd.original.jpg", $file );
		$thumbnail_image = preg_replace( '/\.[^.]+$/', "_hd.thumbnail.jpg", $file );
		
		/* CUSTOMIZE: delete those files on your system
		 * log_file_deletion( $video_file ); 
		 * log_file_deletion( $original_image ); 
		 * log_file_deletion( $thumbnail_image ); 
		 */ 
	}

} 

function get_video_status_code( $error_string ) { 
	
	$video_status = array ( 
		0 => 'done', 
		1 => 'initiated',
		2 => 'sending_to_fileserver', 
		3 => 'error_auth_with_transcoder',
		4 => 'error_transcoder_cannot_download_video',
		5 => 'error_cannot_obtain_width_height',
		6 => 'error_cannot_transcode',
		7 => 'error_cannot_get_thumbnail',
		8 => 'error_auth_with_fileserver',
		9 => 'error_fileserver_cannot_receive_all_files',
		10 => 'error_move_uploaded_file', 
		11 => 'error_cannot_obtain_duration', 
		
		20 => 'error_no_transcoder', 
		21 => 'error_no_fileserver', 
		
		30 => 'transcoder_received_request',
		31 => 'fileserver_received_request' ); 
	
	foreach ( $video_status as $c => $v ) { 
		if ( $error_string == $v )
			return $c; 
	} 
	
	return -1; 
}

 // produce HTML for the display embedcode checkbox 
function video_display_embed_choice( $post_id, $info ) {
	
	$checked = (1 == $info->display_embed)? ' checked="checked"' : '';
	$id = "video-display-embed-$post_id"; 
	$out  = "<input type='checkbox' name='attachments[$post_id][display_embed]' id='$id' $checked />";
	$out .= "<label for='$id'>" . __( 'Display Embed Code' ) . "</label>";
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
		$id = "video-rating-$post_id-$r";
		$out .= "<input type='radio' name='attachments[$post_id][rating]' id='$id' value='$r' $checked />"; 
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

	$info = video_get_info_by_blogpostid( $blog_id, $post_id ); 

	if ( empty( $info ) ) // earlier flv videos which have failed in transcoding are not stored in videos table
		$format = 'flv'; 
	else {
		if ( $info->flv == 'done' )
			$format = 'flv';
		else 
			$format = 'fmt_std'; 
	}
	
	$status = video_get_status( $blog_id, $post_id, $format );
	$message = '';
	
	$embed_args = array( 'format' => $format, 'blog_id' => $blog_id, 'post_id' => $post_id,
		                 'width' => 256, 'context' => 'admin' );


	if ( $status != '' ) {

		$fields['video-display'] = array(
			'label' => __('Display'),
			'input' => 'html',
			'html'  => video_display_embed_choice( $post->ID, $info )
		);
		
		$fields['video-rating'] = array(
			'label' => __('Rating'),
			'input' => 'html',
			'html'  => video_display_rating( $post->ID, $info )
		);
		
		//on Mac/Firefox 2.x, display only thumbnail as flash flickers
		$ua = $_SERVER['HTTP_USER_AGENT']; 
		if ( 'done' == $status && strpos( $ua, 'Macintosh' ) != false && strpos( $ua, 'Firefox/2.' ) != false) { 
			$image_url = video_image_url( $post_id, 'original' ); 
			$video_html = "<div class='video-thumbnail' >" .
				"<img src=$image_url width='256' /> </div>"; 
		} else {
			$video_html = video_embed( $embed_args ); 
		}

		if ( 0 === strpos( $status, 'error_' ) ) {
			$video_html .= '<script type="text/javascript">jQuery(function($){$("[name=\'send['.$post_id.']\']").hide();});</script>';
		} else {
			$video_html = '<p>'.__('Shortcode for embedding:' ).' <strong><code>'.video_send_to_editor_shortcode( '', $post_id, '' ).'</code></strong></p>'.$video_html;
		}

		$fields['video-preview'] = array(
			'label' => __( 'Preview and Insert' ),
			'input' => 'html',
			'html'  => $video_html,
		); 
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
		
	if ( $info->flv == 'done' )
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
					<li>'.sprintf( __('<a href="%s" target="_blank">YouTube instructions</a> %s'), 'http://support.mydomain.com/videos/youtube/', '<code>[youtube=http://www.youtube.com/watch?v=AgEmZ39EtFk]</code>' ).'</li>
					<li>'.sprintf( __('<a href="%s" target="_blank">Google instructions</a> %s') , 'http://support.mydomain.com/videos/google-video/', '<code>[googlevideo=http://video.google.com/googleplayer.swf?docId=-8459301055248673864]</code>' ).'</li>
					<li>'.sprintf( __('<a href="%s" target="_blank">DailyMotion instructions</a> %s'), 'http://support.mydomain.com/videos/dailymotion/', '<code>[dailymotion id=5zYRy1JLhuGlP3BGw]</code>' ).'</li>
					<li>'.sprintf( __('<a href="%s" target="_blank">Post to WordPress button</a> %s'), 'http://support.mydomain.com/videos/vodpod/', 'Use VodPod to post videos from hundreds of sites (beta)' ).'</li>
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

function video_label_css() { ?>
<style type="text/css">#wpbody-content .describe td label { display: inline; }</style>
<?php
}

/**
 * include wp video links in feeds
 */
 function wp_add_videolink_in_excerpt( $text ){ 
 
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
 		
 		$post_url = get_permalink(); 
 		$domain_prefix = substr( $info->domain, 0, strpos($info->domain, '.'));
 		$image_url = 'http://' . $domain_prefix . '.videos.mydomain.com/' . $guid . '/thumbnail/fmt_std'; 
 		$name = preg_replace( '/\.\w+/', '', basename( $info->path ) ); 
 		
 		$vlink .= "<br /><a href='$post_url'><img width='160' height='120' src='$image_url' /> Video: $name </a>";
 	}
 	
 	return $vlink; 
 }
 
function video_init() {
	
	add_filter( 'type_url_form_video', 'video_shortcodes_help');
	add_filter( 'video_send_to_editor_url', 'video_send_to_editor_url', 10, 3 );

	add_action('add_attachment', 'remote_transcode_one_video');

	add_action( 'delete_related_video_files', 'delete_related_video_files', 10, 2 );
	add_filter( 'media_send_to_editor', 'video_send_to_editor_shortcode', 10, 3 );
	add_filter( 'attachment_fields_to_edit', 'video_fields_to_edit', 10, 2 );
	add_filter( 'attachment_fields_to_save', 'video_set_display_embed', 10, 2 );
	add_filter( 'attachment_fields_to_save', 'video_set_rating', 10, 2 );
	add_action( 'admin_head-media.php', 'video_label_css' );
	add_action( 'admin_head-media-new.php', 'video_label_css' );
	
	add_filter('get_the_excerpt', 'wp_add_videolink_in_excerpt', 20);
}

add_action( 'init', 'video_init' );

?>
