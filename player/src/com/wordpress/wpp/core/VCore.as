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
  import com.wordpress.wpp.config.WPPConfiguration;
  import com.wordpress.wpp.events.ObjectEvent;
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
    // Video size
    public var video_width:int;
    public var video_height:int;
    
    // Get the current time
    public function get time():Number
    {
      return offsetSeconds + video_ns.time;
    }
    
    // Get the status of whether the video is playing
    public function get isPlaying():Boolean
    {
      return isVideoPlaying;
    }
    
    // Get the status of whether the video is stopped
    public function get isStopped():Boolean
    {
      return isVideoStopped;
    }
    
    // Get the duration of this video
    public function get duration():Number
    {
      return videoDuration;
    }
    
    // Get the loaded (downloaded) bytes' percentage
    public function get vLoadPercentage():Number
    {
      return offsetPercentage + video_ns.bytesLoaded/video_ns.bytesTotal;
      /* return this.offset/this.duration + video_ns.bytesLoaded/video_ns.bytesTotal; */
    }
    
    public function get volume():Boolean
    {
      return (video_ns.soundTransform.volume == 0 ) ? true : false ;
    }
    
    
    private const NET_STREAM_PLAY_START:String = "NetStream.Play.Start"
    private const NET_STREAM_PLAY_STOP:String = "NetStream.Play.Stop";
    private const NET_STREAM_BUFFER_EMPTY:String = "NetStream.Buffer.Empty";
    private const NET_STREAM_BUFFER_FULL:String = "NetStream.Buffer.Full";
    private const NET_STREAM_BUFFER_FLUSH:String = "NetStream.Buffer.Flush";
    private const NET_STREAM_BUFFER_STREAM_NOT_FOUND:String = "NetStream.Play.StreamNotFound";
    
    private const NOT_FOUND_MESSAGE:String = "Sorry, the video is currently not available";
    
    // A reference to the main class
    //     Maybe we will deprecate this class variable later and let the components 
    //     works in an event driven model
    private var doc:WPPDocument;
    
    // The essential components of our wordpress video player
    // The video instance
    private var videoInstance:Video;
    
    // The net stream instance
    protected var video_ns:NetStream;
    
    // The net connection instance
    private var video_nc:NetConnection;
    
    // The loading assets
    private var video_loading:GUIVideoLoading;
    
    // The message text field
    private var video_message:TextField = new TextField();
    
    // The FLV URL
    private var flv_url:String;
    
    // The init FLV URL
    protected var init_flv_url:String;
    
    private var mutevol:Number = 1;
    private var ratio:Number;
    
    
    
    // The dynamic seeking related variables
    // 
    protected var offsetSeconds:int = 0;
    protected var offsetPercentage:Number = 0;
    protected var initializedDuration:Number;
    
    // Fetched seconds of this stream
    protected var fetchedSeconds:Number = 0;
    
    // Video duration of this video
    private var videoDuration:Number = 0;
    
    
    private var isVideoPlaying:Boolean = false;
    
    
    private var isVideoStopped:Boolean = false;
    
    
    // Replay the video
    public function replay():void
    {
      seek(0, true);
      vplay();
    }
    
   
    
    // Set the duration
    // NOTE: It will be only called inside this package
    //       to specify the duration from a ClientClass
    public function initializeVCoreDuration(value:Number):void
    {
      trace("flush new duration!");
      videoDuration = value;
      if (isNaN(initializedDuration))
      {
        initializedDuration = value;
      }
      else
      {
        offsetPercentage = (initializedDuration - value) / initializedDuration;
        offsetSeconds = initializedDuration - value;
      }
    }
    
    // Set the volume of this video
    public function setVolume(v:Number):void
    {
      var stf:SoundTransform = new SoundTransform();
      stf.volume = v;
      video_ns.soundTransform = stf;
    }
    
    // Get the volume of this video
    public function getVolume():Number
    {
      return video_ns.soundTransform.volume;
    }
    
    // Constructor
    /**
     * 
     * @param _doc     (WPPDocument)
     * @param _flv_url (String)
     * @param width    (Number)
     * @param height   (Number)
     * 
     */    
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
        // Reset the offset position in percentage
        offsetPercentage = 0;
        
        // Reset the offset position in second
        offsetSeconds = 0;
        
        // Re-initialze the init flv url 
        init_flv_url = _flv_url;
      }
      
      cleanEndStatus();
      
      // Reset the video variables
      flv_url = _flv_url;
      video_width = width;
      video_height = height;
      ratio = width/height;
      
      // Reset the video instance
      if (this.contains(videoInstance))
        this.removeChild(videoInstance);
      
      
      // Reset the Net Stream and Net Connection
      video_ns.close();
      
      video_nc.removeEventListener(NetStatusEvent.NET_STATUS, netStatusHandler);
      video_nc.removeEventListener(SecurityErrorEvent.SECURITY_ERROR, securityErrorHandler);
      video_ns.removeEventListener(NetStatusEvent.NET_STATUS, netStatusHandler);
      video_ns.removeEventListener(AsyncErrorEvent.ASYNC_ERROR, asyncErrorHandler);
      
      init();
    }
    
    
    
    public function start(flv_url:String):void
    {
      video_ns.play(flv_url);
      //video_ns.play("http://hailindev.videos.wordpress.com/Bq3EbJL1/video/fmt_hd?"+Math.random().toString());
      
      trace(flv_url);
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
      cleanEndStatus();
      video_ns.resume();
      isVideoPlaying = !isVideoPlaying;
      dispatchCustomEvent(WPPEvents.VCORE_PLAY, true);
    }
     
    /**
     * Pause the video
     * @param isForcePause (Boolean) Whether force the video paused
     * 
     */    
    public function vpause(isForcePause:Boolean = false):void
    {
      if (isForcePause)
      {
        video_ns.pause();
        isVideoPlaying = false;
      }
      else
      {
        video_ns.togglePause();
        isVideoPlaying = !isVideoPlaying;
      }
      dispatchCustomEvent(WPPEvents.VCORE_PAUSED);
    }
    
    // Stop this video, close the Net Stream connection
    public function vstop():void
    {
      video_ns.close();
    }
    
    // Set the volume to zero
    public function mute():void
    {
      mutevol = video_ns.soundTransform.volume;
      var stf:SoundTransform = new SoundTransform();
      stf.volume = 0;
      video_ns.soundTransform = stf;
    }
    
    // Set the volume back
    public function speaker():void
    {
      var stf:SoundTransform = new SoundTransform();
      stf.volume = mutevol;
      video_ns.soundTransform = stf;
    }
    
    /**
     * 
     * @param seekTime     (Number)  Seek this time in the current video
     * @param isReplay (Boolean) Will be true only when the user
     *                 clicks the play button at the menu screen.
     * 
     */    
    public function seek(seekTime:Number, isReplay:Boolean = false):void
    {
      // Whether we can seek this video
      if(initializedDuration == 0 || isNaN(initializedDuration)) 
      {
        return;
      }
      // Kill the stop status
      cleanEndStatus();
      // Get the fetched position in second
      fetchedSeconds = vLoadPercentage * initializedDuration - offsetSeconds;
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
      else if ( seekTime - offsetSeconds < fetchedSeconds && seekTime > offsetSeconds )
      {
        video_ns.seek(seekTime-offsetSeconds);
      }
      else
      {
        // VCoreDynamicSeeking class will implement this section
        return;
      }
    }
    
    // Init function
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
    
    // Kill the stop status
    private function cleanEndStatus():void
    {
      if (!isVideoStopped) return;
      toggleCurtain(false);
      if (doc.endScreen)
      {
        doc.endScreen.unregister();
      }
      isVideoStopped = false;
    }
    
    private function toggleCurtain(isCurtainOn:Boolean):void
    {
      alpha = isCurtainOn ? WPPConfiguration.VCORE_CURTAIN_ALPHA : 1;
    }
    
    private function netStatusHandler(event:NetStatusEvent):void
    {
      switch (event.info.code)
      {
        case NET_STREAM_PLAY_START:
          trace("dispatched from NET_STREAM_PLAY_START !!! ");
          dispatchCustomEvent(WPPEvents.VCORE_PLAY, true);
          video_loading.visible = false;
          isVideoPlaying = true;
          if (!video_message)
            if (this.contains(video_message))
              video_message.visible = false;
          break;
        
        case NET_STREAM_PLAY_STOP:
          dispatchCustomEvent(WPPEvents.VCORE_STOP);
          isVideoPlaying = false;
          isVideoStopped = true;
          break;
          
        case NET_STREAM_BUFFER_EMPTY:
          trace("hold() - NET_STREAM_BUFFER_EMPTY");
          //doc.statsReporter.hold();
          break;
          
        case NET_STREAM_BUFFER_FULL:
          trace("dispatched from NET_STREAM_BUFFER_FULL !!! ");
          dispatchCustomEvent(WPPEvents.VCORE_PLAY, true);
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
    
    private function dispatchCustomEvent(eventType:String, updateStatsReporter:Boolean = false):void
    {
      var event:ObjectEvent = new ObjectEvent(eventType, updateStatsReporter);
      dispatchEvent(event); 
    }
    
    
    // The listeners for our events
    private function asyncErrorHandler(event:AsyncErrorEvent):void
    {
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
    vc.initializeVCoreDuration(info.duration);
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