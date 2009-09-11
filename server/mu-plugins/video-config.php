<?php
/*
 * Configue the following constants for the open source video solution framework
 * note that define should not include leading http:// or trailing /
 * For single server setup (video server, transcoder, fileserver all on one physical web server),
 * you can set them to the same domain name
 */
define ( 'MY_VIDEO_SERVER',       'example.com' ); //server to load video player
define ( 'MY_VIDEO_STATS_SERVER', 'example.com' ); //server to load stats beacon image
define ( 'MY_VIDEO_FILE_SERVER',  'example.com' ); //server accept transcoded files
define ( 'MY_VIDEO_TRANSCODER',   'example.com' ); //server to transcoder raw video clip

if ( !defined( 'VIDEO_AUTH_SECRET' ) )
	define ( 'VIDEO_AUTH_SECRET', 'my_video_auth_secret' ); 

define ( 'VIDEO_ADMIN_EMAIL', 'youremail@example.com' ); 


?>
