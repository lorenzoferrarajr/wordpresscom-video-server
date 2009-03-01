 /**
 * @package       com.wordpress.wpp.config
 * @class         com.wordpress.wpp.config.WPPConfiguration
 *
 * @description   XML parsers
 * @author        automattic
 * @created:      Dec 11, 2008
 * @modified:     Dec 13, 2008  
 * @change list   Feb 16, 
 *                Removed the static variable 'VERIFY_USER_AGE', since our 
 *                application will check for the necessity by the incoming XML 
 *                data.
 */
 

package com.wordpress.wpp.config
{
  import flash.display.DisplayObject;
  import flash.system.Capabilities;
  
  // TODO will let the static functions and variables "instancable" so that
  // we can dynamically modify these values.
  public class WPPConfiguration
  {
    // Video settings
    public static var VCORE_CURTAIN_ALPHA:Number = .25;
    
    // Default const data
    // The default guid
    public static var DEFAULT_GUID:String = "MvIhraHG";
    
    // The default volume (when it is not given via the XML file)
    // Should always be a number of [0,100]
    public static var DEFAULT_VOLUME:Number = 80;
    
    // The default autoplay value
    public static var AUTOPLAY_WHEN_LOADED:Boolean = false;

    // Whether the backend support "dynamic(random) seeking"
    // WARNING: Set to false when your server doesn't support dynamic seeking
    public static var IS_DYNAMIC_SEEKING:Boolean = true;
    
    // Local player configurations   
    public static var IS_LOCAL_MODE:Boolean = false;
    public static var LOCAL_MODE_PATH:String = "clips";
    public static var LOCAL_MODE_XML_FILENAME:String = "video.xml"; 
    
    // The URL of video information's XML data source base
    public static var XML_URL_BASE:String = "http://v.wordpress.com/wp-content/plugins/video/video-xml.php";
    
    // Get Slash By OS
    // Not in use yet
    public static function getSlashByOS():String
    {
      if (flash.system.Capabilities.os.split("Mac").length == 2)
      {
        return "\\";
      }
      else
      {
        return "\/";
      }
      return "\/";
    }
    
    // To determine whether this is a local player
    // Not in use yet.
    public static function isLocalPlayer(displayObject:DisplayObject):Boolean
    {
      if (displayObject.loaderInfo.url.slice(0,4) == "file")
      {
        return true;
      }
      return false;
    }
  }
}