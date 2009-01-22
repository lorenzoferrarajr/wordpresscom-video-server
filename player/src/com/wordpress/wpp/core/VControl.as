/**
 * @package     com.wordpress.wpp.core
 * @class       com.wordpress.wpp.core.VControl
 *
 * @description   Main Controller sets
 * @author      automattic
 * @created:     Jul 19, 2008
 * @modified:     Oct 30, 2008  
 *   
 */


package com.wordpress.wpp.core
{
  import com.wordpress.wpp.events.ObjectEvent;
  import com.wordpress.wpp.events.WPPEvents;
  import com.wordpress.wpp.gui.*;
  import com.wordpress.wpp.ui.UIControl;
  import com.wordpress.wpp.ui.UIVideoSlider;
  import com.wordpress.wpp.ui.UIVolumeSlider;
  import com.wordpress.wpp.utils.TimeText;
  
  import flash.events.Event;
  import flash.events.MouseEvent;
  import flash.events.TimerEvent;
  import flash.utils.Timer;
  
  
  public class VControl extends UIControl
  {
    private var v:VCore;

    // UI Controllers
    private var videoSlider:UIVideoSlider;
    private var volumeSlider:UIVolumeSlider;
    
    private var hideDaemonTimer:Timer;
    
    /* private var pop_menu:GUIPopUpMenu; */
    
    /**
     * 
     * @param guiCtr      (GUIControl)     The GUIControl instance, including all the essential assets 
     * @param vTarget     (VCore)          The VCore instance, on which will the controller implment
     * @param vslider     (UIVideoSlider)  The Video Slider
     * @param volslider   (UIVolumeSlider) The Volume Slider
     * 
     */   
    function VControl(guiCtr:GUIControl, vTarget:VCore, vslider:UIVideoSlider, volslider:UIVolumeSlider) 
    {
      super(guiCtr);
      
      // Initialize the video Core instance
      v = vTarget;
      videoSlider = vslider;
      volumeSlider = volslider;

      // Set the volume to a given value
      setVideoVolume(doc.info.volume);
      
      // Initialize listeners
      initListeners();

    }
    
    /**
     * Get the value of the volume slider 
     * @return 
     * 
     */    
    public function get volume():Number
    {
      return this.volumeSlider.volume;
    }
    
    /**
     * Triggered when the video is paused 
     * @param event
     * 
     */    
    private function togglePauseHandler(event:ObjectEvent):void
    {
      trace("togglePauseHandler:"+event.data);
      if (v.isPlaying)
      {
        play_btn.visible = false;
        pause_btn.visible = true;
        if (event.data) {
          //trace("send")
          doc.statsReporter.resume();
        }
      }
      else
      {
        play_btn.visible = true;
        pause_btn.visible = false;
        //trace("hold - togglePauseHandler"+event.type);
        if (!event.data) {
          doc.statsReporter.hold();
        }
      }
    }
    
    private function switchToPauseHandler(event:Event):void
    {
      play_btn.visible = true;
      pause_btn.visible = false;
    }
    
    private function initListeners():void
    {
      v.addEventListener(WPPEvents.VCORE_PLAY, togglePauseHandler);
      v.addEventListener(WPPEvents.VCORE_PAUSED, togglePauseHandler);
      v.addEventListener(WPPEvents.VCORE_STOP, switchToPauseHandler);
      v.addEventListener(WPPEvents.VCORE_STREAM_NOT_FOUND, disableControllerHandler);
      play_btn.addEventListener(MouseEvent.CLICK, playHandler);
      pause_btn.addEventListener(MouseEvent.CLICK, pauseHandler);
      seek_bar.addEventListener(Event.ENTER_FRAME,daemonHandler,false,0,true);
      
      /* Start the hider if we are already in a fullscreen mode */
      if (doc.stage.displayState == "fullScreen")
      {
        startHideDaemon();
      }
       
    }
    
    private function disableControllerHandler(event:Event):void
    {
      play_btn.removeEventListener(MouseEvent.CLICK, playHandler);
      pause_btn.removeEventListener(MouseEvent.CLICK, pauseHandler);
      seek_bar.removeEventListener(Event.ENTER_FRAME,daemonHandler);
    }
    
    protected override function fullScreenHandler(event:MouseEvent):void
    {
      super.fullScreenHandler(event);
            
      if (doc.stage.displayState == "fullScreen")
      {
        startHideDaemon();
      }

      if (doc.stage.displayState != "fullScreen")
      {
        if (hideDaemonTimer)
          hideDaemonTimer.stop();
        doc.stage.removeEventListener(MouseEvent.MOUSE_MOVE, interfereHandler);
      }
    }
    
    private function startHideDaemon():void
    {
      hideDaemonTimer = new Timer(5000,0);
      hideDaemonTimer.addEventListener(TimerEvent.TIMER, hideCtrHandler);
      hideDaemonTimer.start();
      doc.stage.addEventListener(MouseEvent.MOUSE_MOVE, interfereHandler);
    }
    
    private function interfereHandler(event:MouseEvent):void
    {
      hideDaemonTimer.removeEventListener(TimerEvent.TIMER, hideCtrHandler);
      hideDaemonTimer.stop();
      hideDaemonTimer = new Timer(5000,0);
      hideDaemonTimer.addEventListener(TimerEvent.TIMER, hideCtrHandler);
      hideDaemonTimer.start();
      doc.toggleController(true);
    }
    
    
    private function hideCtrHandler(event:TimerEvent)
    {
      doc.toggleController(false);
    }
    
    
    private function daemonHandler(event:Event):void
    {
      videoSlider.loadPosAt(v.vLoadPercentage);
      videoSlider.seekPosAt(v.time/v.duration);
      videoSlider.cursor.cursorTime = TimeText.getTimeText(v.time);
      videoSlider.addEventListener(WPPEvents.SLIDER_SEEKING,sliderSeekingHandler,false,0,true);
      volumeSlider.addEventListener(WPPEvents.SLIDER_SEEKING,sliderAdjustingHandler,false,0,true);
    }
    
    private function sliderAdjustingHandler(event:ObjectEvent)
    {
      setVideoVolume(event.data);
    }
    
    private function setVideoVolume(value:Number)
    {
      v.setVolume(value);
      volumeSlider.seekPosAt(value/1);
    }
    
    private function sliderSeekingHandler(event:ObjectEvent)
    {
      v.seek((event.data*v.duration));
    }
    private function playHandler(event:MouseEvent):void
    {
      if (v.isStopped)
      {
        v.seek(0,true);
      }
      v.vplay();
    }
    
    private function pauseHandler(event:MouseEvent):void
    {
      v.vpause();
    }
    
    private function stopHandler(event:MouseEvent):void
    {
      v.vstop();
    }
  }
}