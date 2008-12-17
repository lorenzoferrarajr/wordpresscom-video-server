/**
 * @package     com.wordpress.wpp.core
 * @class       com.wordpress.wpp.core.VCore
 *
 * @description   Main Video UI and logic
 * @author      automattic
 * @created:     Jul 19, 2008
 * @modified:     Oct 29, 2008  
 *   
 */


package com.wordpress.wpp.core
{
  import com.wordpress.wpp.events.WPPEvents;
  import com.wordpress.wpp.gui.*;
  import com.wordpress.wpp.ui.UILayoutManager;
  
  import flash.display.Sprite;
  import flash.events.AsyncErrorEvent;
  import flash.events.Event;
  import flash.events.NetStatusEvent;
  import flash.events.SecurityErrorEvent;
  import flash.media.SoundTransform;
  import flash.media.Video;
  import flash.net.NetConnection;
  import flash.net.NetStream;
  import flash.text.TextField;
  import flash.text.TextFormat;
  
  public class VCore extends Sprite
  {
    private const NET_STREAM_PLAY_START:String = "NetStream.Play.Start"
    private const NET_STREAM_PLAY_STOP:String = "NetStream.Play.Stop";
    private const NET_STREAM_BUFFER_EMPTY:String = "NetStream.Buffer.Empty";
    private const NET_STREAM_BUFFER_FULL:String = "NetStream.Buffer.Full";
    private const NET_STREAM_BUFFER_FLUSH:String = "NetStream.Buffer.Flush";
    private const NET_STREAM_BUFFER_STREAM_NOT_FOUND:String = "NetStream.Play.StreamNotFound";
    
    private const NOT_FOUND_MESSAGE:String = "Sorry, the video is currently not available";
    
    private var doc:WPPDocument;
    
    // The essential components of our wordpress video player    
    private var videoInstance:Video;
    private var video_ns:NetStream;
    private var video_nc:NetConnection;
    
    private var video_loading:GUIVideoLoading;
    //private var video_pausing:GUIVideoPausing;
    private var video_message:TextField = new TextField();
    
    
    // The FLV related variables
    private var flv_url:String;
    private var init_flv_url:String;
    
    public var video_width:int;
    public var video_height:int;
    
    private var mutevol:Number = 1;
    private var ratio:Number;
    
    
    
    // The random seeking related variables
    private var offsetSeconds:int = 0;
    private var offsetPercentage:Number = 0;
    private var wholeDuration:Number;
    
    public function get time():Number
    {
      return this.offsetSeconds + video_ns.time;
    }
    
    private var _isPlaying:Boolean = false;
    public function get isPlaying():Boolean
    {
      return _isPlaying;
    }
    
    private var _isStopped:Boolean = false;
    public function get isStopped():Boolean
    {
      return _isStopped;
    }
    
    
    public function replay():void
    {
      seek(0, true);
      vplay();
    }
    
    
    private var _duration:Number = 0;
    // The set of duration is only called inside this package (to specify the duration from the ClientClass)
    public function set _dur(value:Number):void
    {
      if (!wholeDuration)
      {
        wholeDuration = value;
      }
      _duration = value;
    }
    public function get duration():Number
    {
      return _duration;
    }
    
    public function get vLoadPercentage():Number
    {
      
      return offsetPercentage + video_ns.bytesLoaded/video_ns.bytesTotal;
      /* return this.offset/this.duration + video_ns.bytesLoaded/video_ns.bytesTotal; */
      
    }
    
    public function get volume():Boolean
    {
      return (video_ns.soundTransform.volume == 0 ) ? true : false ;
    }
    
    public function setVolume(v:Number):void
    {
      var stf:SoundTransform = new SoundTransform();
      stf.volume = v;
      video_ns.soundTransform = stf;
    }
    
    public function getVolume():Number
    {
      return video_ns.soundTransform.volume;
    }
    
    // Constructor
    function VCore(_doc:WPPDocument, _flv_url:String, width:Number, height:Number)
    {
      doc = _doc;
      flv_url = _flv_url;
      video_width = width;
      video_height = height;
      ratio = width/height;
      video_loading = new GUIVideoLoading();
      addChild(video_loading)
      init();
      
    }
    
    /**
     * 
     * @param _flv_url (String)
     * @param width (Number)
     * @param height (Number)
     * @param normalSeeking (Boolean)
     */    
    public function playFLV(_flv_url:String, width:Number, height:Number, normalSeeking:Boolean = true):void
    {
      if (normalSeeking)
      {
        offsetPercentage = 0;
        offsetSeconds = 0;
        init_flv_url = _flv_url;
      }
      
      killStop();
      
      video_width = width;
      video_height = height;
      ratio = width/height;
      video_ns.close();
      
      if (this.contains(videoInstance))
        this.removeChild(videoInstance);
      
      flv_url = _flv_url;
      
      video_nc.removeEventListener(NetStatusEvent.NET_STATUS, netStatusHandler);
      video_nc.removeEventListener(SecurityErrorEvent.SECURITY_ERROR, securityErrorHandler);
      video_ns.removeEventListener(NetStatusEvent.NET_STATUS, netStatusHandler);
      video_ns.removeEventListener(AsyncErrorEvent.ASYNC_ERROR, asyncErrorHandler);
      
      init();
    }
    
    private function init():void
    {
      video_loading.visible = true;
      if(video_message)
        video_message.visible = false;
      
      // Initial the essential components for video playing
      
      video_nc = new NetConnection();
      video_nc.connect(null);
      
      video_ns = new NetStream(video_nc);
      video_ns.client = new ClientDispatcher(this);
      
      // Play at once!
      if (!init_flv_url) init_flv_url = flv_url;
      start(flv_url);
      
      // Listen the events
      video_nc.addEventListener(NetStatusEvent.NET_STATUS, netStatusHandler);
      video_nc.addEventListener(SecurityErrorEvent.SECURITY_ERROR, securityErrorHandler);
      video_ns.addEventListener(NetStatusEvent.NET_STATUS, netStatusHandler);
      video_ns.addEventListener(AsyncErrorEvent.ASYNC_ERROR, asyncErrorHandler);
      
      if (!doc.contains(this))
        this.addEventListener(Event.ADDED_TO_STAGE, addedHandler);
      else
        initInstances();

    }
    
    // The event handler for automatically resizing the video (to fit the screen)
    private function addedHandler(event:Event):void
    {
      this.removeEventListener(Event.ADDED_TO_STAGE, addedHandler);
      initInstances();
    }
    
    private function initInstances():void
    {
      
      this.stage.addEventListener(Event.RESIZE, resizeHandler);
      autoSetSize();
      
      UILayoutManager.addTarget(videoInstance,{
        "centerx":0,
        "centery":0
        });
      
      UILayoutManager.addTarget(video_loading,{
          "centerx":0,
          "centery":-20
          });
    }
    
    // resize the video when the stage is resizing
    private function resizeHandler(event:Event):void
    {
      autoSetSize();
    }
    
    // set the video to fit the screen
    private function autoSetSize():void
    {
      if (doc.stage.displayState != "fullScreen" )
      {
        videoInstance.width = this.stage.stageWidth;
        videoInstance.height = this.stage.stageHeight;
        return;
      }
      
      if ( this.stage.stageWidth/ this.stage.stageHeight > ratio) 
      {
        videoInstance.width = this.stage.stageHeight*ratio;
        videoInstance.height = this.stage.stageHeight;
      }
      else
      {
        videoInstance.width = this.stage.stageWidth;
        videoInstance.height = this.stage.stageWidth/ratio;
      }
    }
    
    public function seek(t:Number, isReplay:Boolean = false):void
    {
      if(wholeDuration == 0 || isNaN(wholeDuration)) return;
      killStop();
      var fetchedSeconds:Number = vLoadPercentage * wholeDuration - offsetSeconds;
      if (isReplay)
      {
        // Seek From Replay Button
        if (offsetSeconds == 0)
        {
          video_ns.seek(0);
        }
        else
        {
          playFLV(init_flv_url, width, height, true);
        }
        
      }
      else if ( t - offsetSeconds < fetchedSeconds && t > offsetSeconds )
      {
        video_ns.seek(t-offsetSeconds);
      }
      else
      {
        return;
      }
    }

    private function killStop():void
    {
      if (_isStopped)
      {
        _isStopped = false;
        alpha = 1;
        if (doc.menuScreen)
        {
          // doc killall.
          doc.menuScreen.removeAllListeners();
        }
      }
    }

    public function start(flv_url:String):void
    {
      video_ns.play(flv_url);
      videoInstance = new Video(video_width, video_height);
      videoInstance.width = video_width;
      videoInstance.height = video_height;
      videoInstance.attachNetStream(video_ns);
      videoInstance.smoothing = true;
      addChild(videoInstance);
      addChild(video_loading);
    }
    
    public function vplay():void
    {
      killStop();
      video_ns.resume();
      _isPlaying = !_isPlaying;
      
      dispatchCustomEvent(WPPEvents.VCORE_PLAY);
    }
    
    public function vpause(b:Boolean = false):void
    {
      if (!b)
      {
        video_ns.togglePause();
        _isPlaying = !_isPlaying;
      }
      else
      {
        video_ns.pause();
        _isPlaying = false;
      }
      dispatchCustomEvent(WPPEvents.VCORE_PAUSED);
    }
    
    public function vstop():void
    {
      video_ns.close();
    }
    
    
    public function mute():void
    {
      mutevol = video_ns.soundTransform.volume;
      var stf:SoundTransform = new SoundTransform();
      stf.volume = 0;
      video_ns.soundTransform = stf;
    }
    public function speaker():void
    {
      var stf:SoundTransform = new SoundTransform();
      stf.volume = mutevol;
      video_ns.soundTransform = stf;
    }
    
    
    
    // The listeners for our events
    private function asyncErrorHandler(event:AsyncErrorEvent):void
    {
    }
    
    
    private function netStatusHandler(event:NetStatusEvent):void
    {
      switch (event.info.code)
      {
        case NET_STREAM_PLAY_START:
          dispatchCustomEvent(WPPEvents.VCORE_PLAY);
          video_loading.visible = false;
          _isPlaying = true;
          if (!video_message)
            if (this.contains(video_message))
              video_message.visible = false;
          break;
        
        case NET_STREAM_PLAY_STOP:
          dispatchCustomEvent(WPPEvents.VCORE_STOP);
          _isPlaying = false;
          _isStopped = true;
          break;
          
        case NET_STREAM_BUFFER_EMPTY:
          doc.statsReporter.hold();
          break;
          
        case NET_STREAM_BUFFER_FULL:
          dispatchCustomEvent(WPPEvents.VCORE_PLAY);
          video_loading.visible = false;
          break;
          
        case NET_STREAM_BUFFER_FLUSH:
          video_loading.visible = false;
          break;
          
        case NET_STREAM_BUFFER_STREAM_NOT_FOUND:
          dispatchCustomEvent(WPPEvents.VCORE_STREAM_NOT_FOUND);
          video_message = new TextField();
          video_message.text = NOT_FOUND_MESSAGE;
          video_message.selectable = false;
          video_message.width = 300;
          var format:TextFormat = new TextFormat();
          format.font = "Verdana";
                format.color = 0xffffff;
                format.size = 14;
                video_message.setTextFormat(format);
          addChild(video_message);
          UILayoutManager.addTarget(video_message,{
            "centerx":0,
            "centery":-20
            });
          doc.statsReporter.killAll();
          video_loading.visible = false;
          break;
      }
    }
    
    private function dispatchCustomEvent(eventType:String):void
    {
      var event:Event = new Event(eventType);
      dispatchEvent(event); 
    }
    
    
    private function securityErrorHandler(event:SecurityErrorEvent):void
    {
    }
    
  }
}

import com.wordpress.wpp.core.VCore;
class ClientDispatcher
{
  public function onMetaData (info:Object):void
  {
    vc._dur = info.duration;
  }
  public function onCuePoint (info:Object):void
  {
    return;
  }
  private var vc:VCore;
  public function ClientDispatcher(v:VCore)
  {
    vc = v;
  }
}