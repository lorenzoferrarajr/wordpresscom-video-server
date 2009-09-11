<?php
/** 
 * video-ajax: display a previously non-ready video
 * Description: 
 * This little script displays the video whose shortcode was insreted before
 * the video has finished processing
 * 
 * Author:  Automattic Inc
 * Version:  1.0
 */
require('../../../wp-config.php');
if ( !isset($_GET['action']) )
	return;
	
$act = $wpdb->escape( $_GET['action'] ); 

switch ( $act ) {
	case 'embed':
		echo video_embed($_GET);
		break;
}

?>
