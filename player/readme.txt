=== WordPress Video Player ===

Contributors: Automattic Inc
Tags: WordPress video, video solution
Requires at least: 2.5
Tested up to: 2.7

== Description ==
This directory contain the source code for the video player.

It was written in actionscript 3 using Adobe Flash CS3. 
The player takes an argument, video guid, then queries a predefined
server for video XML information.  Then it displays and plays the video.
For example, if guid = hFr8Nyar, the player queries the following URL to retrieve all the relevant information in xml:
http://v.wordpress.com/wp-content/plugins/video/video-xml.php?guid=hFr8Nyar

Modify it so that it retrieves it from your domain:
Eg, http://v.your_domain/wp-content/plugins/video/wpp.swf?guid=OUzHUPL9

To Test:
Load /public_html/wpp.html or simply click the /public_html/wpp.swf to play the video.

If you modify the player, copy wpp.swf to your_wordpress_root_dir/wp-content/plugins/video/flvplayer.swf

Note:
If the domain from which you loaded your player is different from the domain the player queries the XML,
you need to place crossdomain.xml file on the server to allow query form the flash player.
In the recommended setup, player is loaded from v.mydomain.com, and the query goes to v.mydomain.com too,
so you don't need to worry about crossdomain.xml. 

The software contained in this package is under GNU GENERAL PUBLIC LICENSE.
http://www.gnu.org/copyleft/gpl.html

