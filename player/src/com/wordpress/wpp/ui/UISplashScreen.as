/**
 * @package     com.wordpress.wpp.ui
 * @class       com.wordpress.wpp.ui.UISplashScreen
 *
 * @description   
 * @author        automattic
 * @created:      Aug 14, 2008
 * @modified:     Sep 09, 2008  
 *   
 */
 
package com.wordpress.wpp.ui
{
  import com.wordpress.wpp.config.VideoInfo;
  import com.wordpress.wpp.events.ObjectEvent;
  import com.wordpress.wpp.events.WPPEvents;
  import com.wordpress.wpp.gui.*;
  import com.wordpress.wpp.utils.WPVideoXMLParser;
  
  import flash.display.Bitmap;
  import flash.display.Loader;
  import flash.events.Event;
  import flash.events.EventDispatcher;
  import flash.events.IOErrorEvent;
  import flash.events.MouseEvent;
  import flash.events.SecurityErrorEvent;
  import flash.net.URLLoader;
  import flash.net.URLRequest;
  import flash.text.AntiAliasType;
  import flash.text.TextField;
  import flash.text.TextFormat;
  import flash.text.TextFormatAlign;
  
  public class UISplashScreen extends EventDispatcher
  {
    private var guid:String;
    private var doc:WPPDocument;
    
    // The XML Information
    private var xmlLoader:URLLoader;
    private var xmlInfoObj:VideoInfo;
    private var wpVideoInfo:WPVideoXMLParser;
    
    // The UI stuff
    private var splashButton:GUISplashButton;
    private var thumbLoader:Loader;
    private var captionTitle:TextField;
    private var wpLogo:Bitmap;
    
    public function get videoinfo():Object
    {
      return xmlInfoObj;
    }
    
    public function UISplashScreen(_guid:String, _doc:WPPDocument)
    {
      splashButton = new GUISplashButton();
      
      guid = _guid;
      doc = _doc;
      
      xmlInfoObj = new VideoInfo();
      getRemoteSettings();
    }
    
    private function getRemoteSettings():void
    {
      wpVideoInfo = new WPVideoXMLParser(guid);
      wpVideoInfo.addEventListener(WPPEvents.VIDEO_XML_LOADED, showSplashHandler);
    }
    private function showSplashHandler(event:ObjectEvent):void
    {
      xmlInfoObj = event.data;
      if (xmlInfoObj.mainDuration > 0)
        doc.max_report_time = xmlInfoObj.mainDuration*2;
      showCover();
    }
    private function showCover():void
    {
      // Add the splash thumb
      thumbLoader = new Loader();
      var thumbTargetURL:String = xmlInfoObj.fmt_std.original_img;
      if (doc.stage.stageWidth >= 1280 && xmlInfoObj.fmt_hd)
      {
        thumbTargetURL = xmlInfoObj.fmt_hd.original_img;
      }
      else if (doc.stage.stageWidth >= 640 && xmlInfoObj.fmt_dvd)
      {
        thumbTargetURL = xmlInfoObj.fmt_dvd.original_img;
      }
      var thumbImgRequest:URLRequest = new URLRequest(thumbTargetURL);
      thumbLoader.contentLoaderInfo.addEventListener(Event.COMPLETE, imgCompleteHandler, false, 0, true);
      thumbLoader.contentLoaderInfo.addEventListener(SecurityErrorEvent.SECURITY_ERROR, imgSecurityErrorHandler);
      thumbLoader.contentLoaderInfo.addEventListener(IOErrorEvent.IO_ERROR, imgIoErrorHandler);
      thumbLoader.load(thumbImgRequest);
      doc.addChild(thumbLoader);
        
      
      //Add the Splash Button
      doc.addChild(splashButton);
      splashButton.addEventListener(MouseEvent.CLICK, playVideoHandler,false, 0,true);
      UILayoutManager.addTarget(splashButton,{
          "centerx":0,
          "centery":10
          }); 
      
          
      // Add the Title text
      var format:TextFormat = new TextFormat();
            format.font = new GUIHelveticaLtStdBold().fontName;//"Helvetica";
      format.align = TextFormatAlign.CENTER;
      format.color = 0xffffff;
      format.size = 18;
      format.bold = true;
      captionTitle = new TextField();
      captionTitle.width = 800;
      captionTitle.height = 40;
      captionTitle.antiAliasType = AntiAliasType.ADVANCED;
      captionTitle.text = xmlInfoObj.mainCaption;
      captionTitle.selectable = false;
      captionTitle.embedFonts = true;
      captionTitle.setTextFormat(format);
      doc.addChild(captionTitle);
      UILayoutManager.addTarget(captionTitle,{
          "centerx":0,
          "centery":-75
          });
      // Dispatch the "INIT_VIDEO" event to tell the main application that the XML information is loaded.
      // * No need to wait for the splash image.
      var initEvent:Event = new Event(WPPEvents.SPLASH_SCREEN_INIT);
      dispatchEvent(initEvent);
    }
    
    public function get thumb():Loader
    {
      return thumbLoader;
    }
    
    private function playVideoHandler(event:MouseEvent):void
    {
      var playEvent:Event = new Event(WPPEvents.SPLASH_VIDEO_PLAY);
      dispatchEvent(playEvent);
    }

    private function imgCompleteHandler(event:Event):void
    { 
      thumbLoader.width = doc.stage.stageWidth;
      thumbLoader.height = doc.stage.stageHeight;
      thumbLoader.alpha = .75;
      UILayoutManager.addTarget(thumbLoader,{
          "width":1,
          "marginLeft":0,
          "marginRight":0,
          "height":1
          });
    }
    
    private function imgSecurityErrorHandler(event:SecurityErrorEvent):void
    {
      
    }
    
    private function imgIoErrorHandler(event:IOErrorEvent):void
    {
      
    }
    
    private function xmlSecurityErrorHandler(event:SecurityErrorEvent):void
    {
      
    }
    
    private function xmlIoErrorHandler(event:IOErrorEvent):void
    {
      
    }
    
    /**
     * Remove the listeners / instances in splash screen
     * 
     */
    public function unregister():void
    {
      // Removes the listeners for the conver loader, release the memories.
      thumbLoader.contentLoaderInfo.removeEventListener(Event.COMPLETE, imgCompleteHandler);
      thumbLoader.contentLoaderInfo.removeEventListener(SecurityErrorEvent.SECURITY_ERROR, imgSecurityErrorHandler);
      thumbLoader.contentLoaderInfo.removeEventListener(IOErrorEvent.IO_ERROR, imgIoErrorHandler);
      
      // Removes the listener for the splash button, release the memories.
      splashButton.removeEventListener(MouseEvent.CLICK, playVideoHandler);
      doc.removeChild(splashButton);
      
      // Removes the title text and thumb ( the image )
      doc.removeChild(captionTitle);
      doc.removeChild(thumbLoader);
    }
    
    

  }
}