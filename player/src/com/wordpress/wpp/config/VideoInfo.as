 /**
 * @package       com.wordpress.wpp.config
 * @class         com.wordpress.wpp.config.VideoInfo
 *
 * @description   XML parsers
 * @author        automattic
 * @created:      Dec 11, 2008
 * @modified:     Dec 13, 2008  
 *   
 */
 
package com.wordpress.wpp.config
{
  public class VideoInfo
  {
    public var mainCaption:String;
    public var mainDuration:Number;
    public var mainDomain:String;
    
    public var mainUser:String;
    public var mainAvatar:String;
    public var mainDescription:String;
    
    public var rating:String;
    
    public var status_interval:Number;
    public var status_url:String;
    
    public var volume:Number = WPPConfiguration.DEFAULT_VOLUME/100;
    public var isPrivate:Boolean;
    public var isLogin:Boolean;
    
    public var embededCode:String;
    public var embededLargeCode:String;
    public var embededWp:String;
    
    public var hasHD:Boolean;
    
    public var fmt_std:VideoFormat;
    public var fmt_dvd:VideoFormat;
    public var fmt_hd:VideoFormat;    
  }
}