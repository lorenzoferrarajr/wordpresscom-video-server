<?php
/*
 * This program verifies whether ffmpeg is installed correctly to 
 * transcode h.264 videos. 
 * It downloads a test clip, then tries to encode it into h.264
 * 
 * Usage:  /usr/local/bin/php video-verify-ffmpeg.php
 */

define('FFMPEG_BINARY', '/usr/bin/ffmpeg');
define('FASTSTART', '/usr/bin/qt-faststart');


$video_url = "http://hailin.wordpress.com/files/2008/07/baby.wmv"; 
preg_match( '|([^/]+)\.\w+$|', $video_url, $m ); 

$file = '/tmp/'. $m[1] . '_1234'; 

$r = file_download( $video_url, $file ); 

if ( !$r ) {
	cleanup(); 
	log_die("video $video_url does not exist");
}

if ( !file_exists( FFMPEG_BINARY ) ){
	cleanup();
	$msg = FFMPEG_BINARY . ' does not exist ';
	log_die( $msg );
}

if ( !file_exists( FASTSTART )) {
	cleanup();
	$msg = FASTSTART . ' does not exist ';
	log_die( $msg );
}

$temp_video_file = $file . '_temp.mp4';
$video_file      = $file . '.mp4'; 
	
$cmd = FFMPEG_BINARY . " -i $file -y -acodec libfaac -ar 48000 -s 400x300 -vcodec libx264 -threads 4 -b 668k -flags +loop -cmp +chroma -partitions +parti4x4+partp8x8+partb8x8 -flags2 +mixed_refs -me_method  epzs -subq 5 -trellis 1 -refs 5 -bf 3 -b_strategy 1 -coder 1 -me_range 16 -g 250 -keyint_min 25 -sc_threshold 40 -i_qfactor 0.71 -rc_eq 'blurCplx^(1-qComp)' -qcomp 0.6 -qmin 5 -qmax 51 -qdiff 4 $temp_video_file"; 
exec( $cmd );

if ( !file_exists( $temp_video_file ) || filesize( $temp_video_file ) < 500000 ) {
	
	cleanup(); 
	log_die( "$video_url can not be transcoded into h.264, ffmpeg issue???" );
}

echo "Congratulations! ffmpeg is installed correctly";

cleanup(); 
die(); 

//clean up the residual files
function cleanup() {
	global $file;  
	
	$cmd = 'rm ' . $file . '*'; 
	exec( $cmd ); 
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
	echo $msg ; 
	die(); 
}

?>
