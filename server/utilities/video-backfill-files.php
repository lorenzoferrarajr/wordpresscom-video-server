<?php
/*
 * backfill std_files, dvd_files, hd_files and flv_files in videos table
 */

require('wp-config.php');

ini_set("memory_limit","512M");

$sql = "SELECT * FROM videos WHERE std_files is NULL order by date_gmt desc"; 


$rows = $wpdb->get_results( $sql ); 

$x = count( $rows ); 
echo "# of rows: $x  ";   

$i = 0; 
foreach ( $rows as $r) {
	$i++; 
	echo "$i "; 
	
	$blog_id = $r->blog_id;
	$post_id = $r->post_id;
	$name    = preg_replace( '/\.\w+/', '', basename( $r->path ) ); 
	
	if ( $r->flv == 'done' ){
		
		$video_file    = $name . '.flv';  
		$original_img  = $name .  ".original.jpg";  
		$thumbnail_img = $name .  ".thumbnail.jpg";  
		
		$files_info = array( 'video_file'    => $video_file, 
                             'original_img'  => $original_img, 
                             'thumbnail_img' => $thumbnail_img ); 
                             		 
		update_video_info( $blog_id, $post_id, 'flv_files', serialize( $files_info ) ); 
	} 
	
	if ( $r->fmt_std == 'done' ){ 
		
		$video_file    = $name . '.mp4';  
		$original_img  = $name .  ".original.jpg";  
		$thumbnail_img = $name .  ".thumbnail.jpg";  
		
		$files_info = array( 'video_file'    => $video_file, 
                             'original_img'  => $original_img, 
                             'thumbnail_img' => $thumbnail_img ); 
					 
		update_video_info( $blog_id, $post_id, 'std_files', serialize( $files_info ) ); 
	} 
	
	if ( $r->fmt_dvd == 'done' ){ 
		
		$video_file    = $name . '_dvd.mp4';  
		$original_img  = $name .  "_dvd.original.jpg";  
		$thumbnail_img = $name .  "_dvd.thumbnail.jpg";  
		
		$files_info = array( 'video_file'    => $video_file, 
                             'original_img'  => $original_img, 
                             'thumbnail_img' => $thumbnail_img ); 
					 
		update_video_info( $blog_id, $post_id, 'dvd_files', serialize( $files_info ) ); 
	}
	
	if ( $r->fmt_hd == 'done' ){ 
		
		$video_file    = $name . '_hd.mp4';  
		$original_img  = $name .  "_hd.original.jpg";  
		$thumbnail_img = $name .  "_hd.thumbnail.jpg";  
		
		$files_info = array( 'video_file'    => $video_file, 
                             'original_img'  => $original_img, 
                             'thumbnail_img' => $thumbnail_img ); 
					 
		update_video_info( $blog_id, $post_id, 'hd_files', serialize( $files_info ) ); 
	}
		
}

?>
