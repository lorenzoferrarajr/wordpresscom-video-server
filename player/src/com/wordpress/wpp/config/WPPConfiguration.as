 /**
 * @package       com.wordpress.wpp.config
 * @class         com.wordpress.wpp.config.WPPConfiguration
 *
 * @description   XML parsers
 * @author        automattic
 * @created:      Dec 11, 2008
 * @modified:     Dec 13, 2008  
 *   
 */
 

package com.wordpress.wpp.config
{
  import flash.display.DisplayObject;
  import flash.system.Capabilities;
  
  // TODO will let the static functions and variables "instancable" so that
  // we can dynamically modify these values.
  public class WPPConfiguration
  {
    // Local player   
    public static const LOCAL_MODE:Boolean = true; 
    public static const LOCAL_MODE_PATH:String = "clips";
    public static const LOCAL_MODE_XML_FILENAME:String = "video.xml"; 
    
    public static const DEFAULT_GUID:String = "OUzHUPL9";
    public static const DEFAULT_AUTOPLAY:Boolean = false;
    
    // Web player
    public static const XML_URL_BASE:String = "http://v.wordpress.com/wp-content/plugins/video/video-xml.php";
    
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