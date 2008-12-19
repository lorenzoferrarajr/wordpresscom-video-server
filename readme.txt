
=== WordPress Video Solution Framework ===

Contributors: Automattic Inc  
Tags: WordPress video, video solution
Requires at least: 2.6
Tested up to: 2.7
Stable tag: trunk

Video solutions framework, including player, transcoder and administration interface utilities as wpmu plugin. 
It powers wordpress.com video solutions. Supports multiple formats including HD. 
You need to customize it with your own infrastructure configurations in order to use it. 

== Description ==

The package contains video solutions framework, including transcoder and 
administration interface utilities, written in PHP.  The code was developed 
by Automattic Inc, and powers wordpress.com video solutions.  
It supports multiple formats including HD. 
We hereby make it open source project so that you can reuse it, build upon it, and share with the community. 

The solution is a WPMU plugin. However it can not be used as "out-of-the-box" type 
because it also depends on your file serving infrastructure, 
and your URL schemes.  Customize your pieces, then you have a full-fledged video solution. 
Or you can just reuse the individual components such as video player or transcoder. 

The graph below illustrates the implemented video architecture: 

 |-----------------|      |-----------------------|      |------------------------|
 | user uploads    |      | transcodes the video  |      |downloads the mp4 and   |
 | a raw video     | ==>  | into h.264 mp4 and    | ==>  | thumbnail images,      |
 |                 |      | produces thumbnails   |      | replicate files        |
 |-----------------|      |-----------------------|      |------------------------|
   Admin Web Server              Transcoder                  File Server


== Installation ==

Steps to modify and deploy the video solution: 

1.
Create database and table named "videos", which is used to store individual video meta information. 
 
Videos table definition

CREATE TABLE `videos` (
  `guid` varchar(32) NOT NULL default '',
  `domain` varchar(200) default NULL,
  `blog_id` bigint(20) NOT NULL default '0',
  `post_id` bigint(20) NOT NULL default '0',
  `path` varchar(255) NOT NULL default '',
  `date_gmt` datetime default '0000-00-00 00:00:00',
  `finish_date_gmt` varchar(24) default '0000-00-00 00:00:00' COMMENT 'job finish time',
  `duration` varchar(10) default NULL COMMENT 'video length',
  `width` int(11) default NULL COMMENT 'original width',
  `height` int(11) default NULL COMMENT 'original height',
  `dc` varchar(5) default 'dfw' COMMENT 'originating dc',
  `flv` varchar(50) default NULL COMMENT 'status of flv format',
  `fmt_std` varchar(50) default NULL,
  `fmt_dvd` varchar(50) default NULL,
  `fmt_hd` varchar(50) default NULL,
  `display_embed` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`blog_id`,`post_id`),
  UNIQUE KEY `guid` (`guid`),
  KEY `date_gmt` (`date_gmt`)
)

2.  
Configure video transcoder with ffmpeg and other tools. 
A sample configuration script is attached (transcoder-config.sh)

3.  
Figure out your file serving infrastructure and file serving url schemes, 
and modify the code to reflect that. The places where you need to modify are marked "CUSTOMIZE". 

4.  
Copy files in mu-plugins/ to wp-content/mu-plugins; and copy files in plugins/video/ to wp-content/ plugins/video/

5.  
Testing 
It is an entire video solution, and we've spent months developing it, 
so naturally it will take you some time to test and tailor it to your system.  

The software contained in this package is under GNU GENERAL PUBLIC LICENSE. 
http://www.gnu.org/copyleft/gpl.html

