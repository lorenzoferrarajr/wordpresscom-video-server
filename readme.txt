=== WordPress Video Solution Framework ===

Contributors: Automattic, Inc.  
Tags: WordPress video, video solution
Requires at least: 2.6
Tested up to: 2.7
Stable tag: trunk

Video solutions framework, including player, transcoder and administration interface utilities as WordPress MU plugin. 
It powers WordPress.com video solutions and supports multiple formats including HD. 
It is designed as a video solution for *large scale self-hosted WordPress MU systems*. 
You will need to customize it with your own infrastructure configurations in order to use it.

== Description ==

This package contains the video solutions framework, including transcoder and administration interface utilities, written in PHP. 
The code was developed by Automattic, Inc., and powers WordPress.com video solutions. 
It supports multiple formats, including HD. 
It is an open source project, which means you can reuse it, build upon it, and share it with the community.

**Warning**
*This plugin is different from other plugins because it can not be used "out-of-the-box". 
It is intended for self-hosted large scale WordPress MU sites that want to develop their own customized video solutions. 
In addition to Web servers for WordPress MU , it requires at least one file server and one dedicated video transcoder. 
Considerable amount of PHP coding and system administration skills are required to install, customize and deploy this plugin.*

Contained is a whole package of video solution code. 
It can be used as the foundation for a full-fledged video system. 
Individual components such as the video player and transcoder are reusable as well.

The video architecture has three key steps: 

1. User uploads raw video to admin web server

2. Transcoder transcodes the video into h.264 mp4 (standard, DVD, and HD), and produces thumbnails

3. File server downloads the mp4 and thumbnail images, then replicates files 

The screenshots section contains the video architecture diagram.

Send your feedback or questions to: hailin AT automattic.com

== Installation ==

Steps to modify and deploy the video solution: 

1.
Make sure the following directories under your WordPress root directory already exist. 
Create them if necessary: wp-content/mu-plugins  and wp-content/plugins/video/

Copy files in server/mu-plugins/ to your_WP_root/wp-content/mu-plugins; 
and copy files in server/plugins/video/ to your_WP_root/wp-content/plugins/video/

Carefully study the source code, understand your system environment, and modify places marked "CUSTOMIZE".

2.
Create a database and table named "videos," which is used to store individual video meta information.
The sql script file is server/setup/videos-table.txt.
Every video corresponds to one row in the table.

3.
Set up a dedicated transcoder server with Linux/Unix operation system.
You'll need to first install the whole WPMU package and this plugin code because the transcoder uses general blog functions as well.
In addition, follow server/setup/transcoder-setup.txt to install the transcoding utilities.
Once the entire transcoding utilities are installed, go to server/setup directory and run the following command to verify the installation:

php video-verify-ffmpeg.php

The above command downloads a sample video, and tries to use the ffmpeg you just installed to transcode it.
If it can successfully transcode the video, it prints out the message "Congratulations! ffmpeg is installed correctly." 

If you see an error message, make sure the transcoder is installed successfully.
The transcoder is the heart of any video system, and it must work correctly.

4. 
Determine your file serving infrastructure and file serving URL schemes.
Set up your system environments. One URL serving sample is described in the extra section.

5. 
Testing and customization
Because this is an entire video solution, it will take some time to test and tailor it to your system. 

6. 
Video Player
The video player source code is also released. The player is written in actionscript 3 using Adobe Flash CS3. 
The source code is located at directory player/ and you don't need to deploy it to your servers. 
Refer to player/readme.txt for more details. 

The software contained in this package is under GNU GENERAL PUBLIC LICENSE.
http://www.gnu.org/copyleft/gpl.html

== Frequently Asked Questions ==

= Why does this plugin seem so complex ? = 

It is not a "regular" plugin. It's a complete video solutions framework,
which handles video upload, transcoding, serving and video player.
If you just use WordPress for your own personal blog, this is not for you.
It's designed for *large scale WordPress MU systems* which host at least thousands of blogs.

This plugin can also be used as the foundation for a video startup company. 


== Screenshots ==

1. This screenshot description corresponds to video architecture screenshot-1.png

== Arbitrary section ==

Sample Video URL Structure

Suppose a user uploads a video, which is transcoded and processed successfully by the video system
Internally, the video is assigned the guid: hFr8Nyar. The internal shortcode produced is [wpvideo hFr8Nyar].

Before the video is displayed on a WordPress blog, the parsing function in video.php converts the shortcode into the following embed code:

`<embed src="http://v.mydomain.com/hFr8Nyar" type="application/x-shockwave-flash" width="400" height="224" allowscriptaccess="always" allowfullscreen="true"></embed>`

http://v.mydomain.com/hFr8Nyar is rewritten into
http://v.mydomain.com/wp-content/plugins/video/flvplayer.swf?guid=f6n7RD5B
by the following rewrite rule defined in .htaccess:

RewriteCond %{HTTP_HOST} ^v\.mydomain\.com
RewriteCond %{REQUEST_URI} !/videofile/
RewriteCond %{REQUEST_URI} !/wp-content/
RewriteRule ^([^/]+) /wp-content/plugins/video/flvplayer.swf?guid=$1 [L,R=302]

Once the player (flvplayer.swf) is loaded, it receives the video guid,
then the flash player queries video-xml.php to retrieve all the relevant information about a video, 
including different formats, thumbnail images, duration, and other meta information.

For example, for the above guid hFr8Nyar, the video player queries and obtains the following xml data.

Here's a link to [video xml information for guid hFr8Nyar](http://v.wordpress.com/wp-content/plugins/video/video-xml.php?guid=hFr8Nyar "video xml information")

The player then intelligently loads the most suitable video files and plays the video.
For example, if the player checks the embed width and height, and decides that it is best to fetch and play the smaller version, 
it will use the fmt_std section of the xml file and request the corresponding movie file and original thumbnail images.

movie_file:  http://michaelpick.videos.mydomain.com/hFr8Nyar/video/fmt_std
original_image: http://michaelpick.videos.mydomain.com/hFr8Nyar/original/fmt_std
thumbnail_imiage: http://michaelpick.videos.mydomain.com/hFr8Nyar/thumbnail/fmt_std</thumbnail_img>

As you can see in this example, the video files URL structure has the following format:
blog_domain.videos.mydomain/guid/format

The important thing is to use a consistent structure and you can serve the files accordingly.

On the system side, you need to make sure you can serve the video files according to the URL structure.

The following rules are configured in .htaccess for this purpose:

RewriteCond %{REQUEST_URI} ^/$
RewriteCond %{HTTP_HOST} ^(.+)\.videos\.wordpress\.com
RewriteRule (.*) http://%1.mydomain.com/ [R,L]

RewriteCond %{HTTP_HOST} ^(.+)\.videos\.mydomain\.com
RewriteCond %{REQUEST_URI} !blogs.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^([^/]+)/(.*)/(.*)$  /wp-content/blogs.php?video_guid=$1&type=$2&format=$3 [L]

In the above, the video is served by /wp-content/blogs.php. This is a purely system-specific example. 
You will need to determine how you will serve your videos, either from your own local data server or using CDN.
