 /**
 * @package       com.wordpress.wpp.utils
 * @class         com.wordpress.wpp.utils.WPVideoXMLParser
 *
 * @description   XML parsers
 * @author        automattic
 * @created:      Aug 09, 2008
 * @modified:     Dec 13, 2008  
 *   
 */
 
package com.wordpress.wpp.utils
{
  import com.wordpress.wpp.config.VideoFormat;
  import com.wordpress.wpp.config.VideoInfo;
  import com.wordpress.wpp.config.WPPConfiguration;
  import com.wordpress.wpp.events.ObjectEvent;
  import com.wordpress.wpp.events.WPPEvents;
  import com.wordpress.wpp.gui.*;
  
  import flash.events.Event;
  import flash.events.EventDispatcher;
  import flash.events.IOErrorEvent;
  import flash.events.SecurityErrorEvent;
  import flash.net.URLLoader;
  import flash.net.URLRequest;
  
  public class WPVideoXMLParser extends EventDispatcher
  {
    
    private var guid:String;

    // The XML Information
    private var xmlLoader:URLLoader;
    private var xmlInfoObj:VideoInfo;
    
    public function WPVideoXMLParser(_guid:String)
    {
      guid = _guid;
      xmlInfoObj = new VideoInfo();
      
      var requesetTargetURL:String = "";
      
      // Determine which mode to use
      if (WPPConfiguration.LOCAL_MODE)
      {
        requesetTargetURL = WPPConfiguration.LOCAL_MODE_PATH+"/"+
            WPPConfiguration.LOCAL_MODE_XML_FILENAME;
      }
      else
      {
        requesetTargetURL = WPPConfiguration.XML_URL_BASE+"?guid="+guid;
      }
      requestRemoteData(requesetTargetURL);
    }
    
    private function requestRemoteData(xmlURL:String):void
    {
      xmlLoader = new URLLoader();
      var xmlRequest:URLRequest = new URLRequest(xmlURL);
      xmlLoader.load(xmlRequest);
      xmlLoader.addEventListener(Event.COMPLETE,
          xmlCompleteHandler);
      xmlLoader.addEventListener(SecurityErrorEvent.SECURITY_ERROR,
          xmlSecurityErrorHandler);
      xmlLoader.addEventListener(IOErrorEvent.IO_ERROR,
          xmlIoErrorHandler);
    }
    
    private function xmlCompleteHandler(event:Event):void
    {
      removeXMLListeners();
      
      var xmlData:XML = new XML();
      xmlData = new XML(event.target.data);
      
      // base info of the video from the XML
      xmlInfoObj.mainCaption = xmlData.video.caption.toString();
      xmlInfoObj.mainDuration = Number(xmlData.video.duration.toString());
      xmlInfoObj.mainDomain   = xmlData.video.blog_domain.toString();
      xmlInfoObj.volume = Number(xmlData.video.default_volume.toString())/100;
            
      // meta info of the video from the XML
      xmlInfoObj.mainUser = xmlData.video.username.toString();
      xmlInfoObj.mainAvatar = xmlData.video.avatar.toString();
      xmlInfoObj.mainDescription = xmlData.video.description.toString();
      
      // Stats info of the video from the XML
      xmlInfoObj.status_interval = Number(xmlData.video.status_interval);
      xmlInfoObj.status_url = "";
      
      // Security and privacy info of the video from the XML
      xmlInfoObj.isPrivate = (xmlData.video.is_private.toString() == '0') ? false : true ;
      xmlInfoObj.isLogin = (xmlData.video.is_logged_in.toString() == '0') ? false : true ;
      
      // Externally embedding info of the video from the XML
      xmlInfoObj.embededCode = xmlData.video.embed_code.toString();
      xmlInfoObj.embededLargeCode = xmlData.video.large_embed_code.toString();
      xmlInfoObj.embededWp = xmlData.video.wp_embed.toString();

      xmlInfoObj.hasHD = false;
      
      if ( xmlData.video.fmt_std.movie_file.toString().length > 0 ) 
      {
        var fmt_std:VideoFormat = new VideoFormat();
        fmt_std.movie_file    = xmlData.video.fmt_std.movie_file.toString();
        fmt_std.width         = Number(xmlData.video.fmt_std.width.toString());
        fmt_std.height        = Number(xmlData.video.fmt_std.height.toString());
        fmt_std.original_img  = xmlData.video.fmt_std.original_img.toString();
        fmt_std.thumbnail_img = xmlData.video.fmt_std.thumbnail_img.toString();
        if (com.wordpress.wpp.config.WPPConfiguration.LOCAL_MODE)
        {
          fmt_std.movie_file = WPPConfiguration.LOCAL_MODE_PATH+"/"+
              fmt_std.movie_file;
          fmt_std.original_img = WPPConfiguration.LOCAL_MODE_PATH+"/"+
              fmt_std.original_img;
          fmt_std.thumbnail_img = WPPConfiguration.LOCAL_MODE_PATH+"/"+
              fmt_std.thumbnail_img;
        }
        fmt_std.status_url    = xmlData.video.fmt_std.status_url.toString();
        xmlInfoObj.fmt_std = fmt_std; 
      }
      
      if ( xmlData.video.fmt_dvd.movie_file.toString().length > 0 ) 
      {
        var fmt_dvd:VideoFormat    = new VideoFormat();
        fmt_dvd.movie_file    = xmlData.video.fmt_dvd.movie_file.toString();
        fmt_dvd.width         = Number(xmlData.video.fmt_dvd.width.toString());
        fmt_dvd.height        = Number(xmlData.video.fmt_dvd.height.toString());
        fmt_dvd.original_img  = xmlData.video.fmt_dvd.original_img.toString();
        fmt_dvd.thumbnail_img = xmlData.video.fmt_dvd.thumbnail_img.toString();
        if (com.wordpress.wpp.config.WPPConfiguration.LOCAL_MODE)
        {
          fmt_dvd.movie_file = WPPConfiguration.LOCAL_MODE_PATH+"/"+
              fmt_dvd.movie_file;
          fmt_dvd.original_img = WPPConfiguration.LOCAL_MODE_PATH+"/"+
              fmt_dvd.original_img;
          fmt_dvd.thumbnail_img = WPPConfiguration.LOCAL_MODE_PATH+"/"+
              fmt_dvd.thumbnail_img;
        }
        fmt_dvd.status_url    = xmlData.video.fmt_dvd.status_url.toString();      
        xmlInfoObj.fmt_dvd = fmt_dvd;
      }
      
      if ( xmlData.video.fmt_hd.movie_file.toString().length > 0 ) 
      {
        var fmt_hd:VideoFormat    = new VideoFormat();
        fmt_hd.movie_file    = xmlData.video.fmt_hd.movie_file.toString();
        fmt_hd.width         = Number(xmlData.video.fmt_hd.width.toString());
        fmt_hd.height        = Number(xmlData.video.fmt_hd.height.toString());
        fmt_hd.original_img  = xmlData.video.fmt_hd.original_img.toString();
        fmt_hd.thumbnail_img = xmlData.video.fmt_hd.thumbnail_img.toString();
        if (com.wordpress.wpp.config.WPPConfiguration.LOCAL_MODE)
        {
          fmt_hd.movie_file = WPPConfiguration.LOCAL_MODE_PATH+"/"+
              fmt_hd.movie_file;
          fmt_hd.original_img = WPPConfiguration.LOCAL_MODE_PATH+"/"+
              fmt_hd.original_img;
          fmt_hd.thumbnail_img = WPPConfiguration.LOCAL_MODE_PATH+"/"+
              fmt_hd.thumbnail_img;
        }
        fmt_hd.status_url    = xmlData.video.fmt_hd.status_url.toString();
        xmlInfoObj.fmt_hd = fmt_hd; 
        xmlInfoObj.hasHD = true;
      }

      var objectEvent:ObjectEvent = new ObjectEvent(WPPEvents.VIDEO_XML_LOADED, xmlInfoObj);
      dispatchEvent(objectEvent);
      
    }
    
    private function xmlSecurityErrorHandler(event:SecurityErrorEvent):void
    {
      trace(event);
    }
    
    private function xmlIoErrorHandler(event:IOErrorEvent):void
    {
      trace(event);
    }
    
    public function removeXMLListeners():void
    {
      xmlLoader.removeEventListener(Event.COMPLETE, xmlCompleteHandler);
      xmlLoader.removeEventListener(SecurityErrorEvent.SECURITY_ERROR, xmlSecurityErrorHandler);
      xmlLoader.removeEventListener(IOErrorEvent.IO_ERROR, xmlIoErrorHandler);
    }
  }
}