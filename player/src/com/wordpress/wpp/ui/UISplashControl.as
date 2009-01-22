/**
 * @package     com.wordpress.wpp.ui
 * @class       com.wordpress.wpp.ui.UISlider
 *
 * @description   
 * @author        automattic
 * @created:      Aug 14, 2008
 * @modified:     Sep 09, 2008  
 *   
 */

// FYI This class is still under construction :)

package com.wordpress.wpp.ui
{
  
  import com.wordpress.wpp.events.ObjectEvent;
  import com.wordpress.wpp.events.WPPEvents;
  import com.wordpress.wpp.gui.*;
  import com.wordpress.wpp.utils.TimeText;
  
  import flash.events.Event;
  import flash.events.MouseEvent;
  
  /**
   * 
   * A very simliar class like "VControl", maybe we'll merge these two later to a same super class
   * 
   */  
  public class UISplashControl extends UIControl
  {
    private var splashVol:Number;
     
    /**
     * UI Controllers 
     */    
    private var videoSlider:UIVideoSlider;
    private var volumeSlider:UIVolumeSlider;
    
    
    /**
     * Get the UIVideoSlider instance for the future use. 
     * @return UIVideoSlider instance
     * 
     */    
    public function get videoslider():UIVideoSlider
    {
      return videoSlider;
    }
    
    /**
     * Get the UIVolumeSlider instance for the future use. 
     * @return UIVolumeSlider instance
     * 
     */    
    public function get volumeslider():UIVolumeSlider
    {
      return volumeSlider;
    }
    
    /**
     * Constructor 
     * @param guiCtr Controller instance
     * 
     */    
    function UISplashControl(guiCtr:GUIControl)
    {
      super(guiCtr);
      
      // Initialize the seeking slider
      videoSlider = new UIVideoSlider(seek_bar);
      volumeSlider = new UIVolumeSlider(volume_bar);
      volumeSlider.seekPosAt(doc.info.volume);
      
      videoSlider.cursor.cursorTime = TimeText.getTimeText(doc.info.mainDuration);//"00:00";
      
      videoSlider.addEventListener (WPPEvents.SLIDER_SEEKING, seekVideoHandler, false,0,true);
      volumeSlider.addEventListener(WPPEvents.SLIDER_SEEKING, seekVolumeHandler,false,0,true);
      play_btn.addEventListener(MouseEvent.CLICK, playButtonHandler,false, 0, true);
    }
    
    /**
     * Video seeking handler 
     * @param event
     * 
     */    
    private function seekVideoHandler(event:ObjectEvent):void
    {
      dispatchPlayEvent()
    }
    
    /**
     * Video playing handler 
     * @param event
     * 
     */    
    private function playButtonHandler(event:MouseEvent):void
    {
      dispatchPlayEvent();
    }
    
    /**
     * Dispatch the play event in splash screen
     * 
     */    
    private function dispatchPlayEvent():void
    {
      var startEvent:Event = new Event(WPPEvents.SPLASH_VIDEO_PLAY);
      dispatchEvent(startEvent); 
    }
    
    /**
     * Set volume
     * @param event
     * 
     */    
    private function seekVolumeHandler(event:ObjectEvent):void
    {
      volumeSlider.seekPosAt(event.data);
      doc.info.volume = event.data;
    }
    
    /**
     * Remove the listeners for splash control use
     * 
     */    
    public function unregister():void
    {
      play_btn.removeEventListener(MouseEvent.CLICK, playButtonHandler);
      videoSlider.removeEventListener (WPPEvents.SLIDER_SEEKING, seekVideoHandler);
      volumeSlider.removeEventListener(WPPEvents.SLIDER_SEEKING, seekVolumeHandler);
      hd_on.removeEventListener(MouseEvent.CLICK, hdOffHandler)
      hd_off.removeEventListener(MouseEvent.CLICK, hdOnHandler)
      fullscreen_btn.removeEventListener(MouseEvent.CLICK, fullScreenHandler);
    }
    
    /**
     * Turn on HD in splash screen
     * @param event
     * 
     */    
    protected override function hdOnHandler(event:MouseEvent):void
    {
      hd_on.visible = false;
      hd_off.visible = true;
      hd_switch.gotoAndStop(1);
      var newE:Event = new Event(WPPEvents.SPLASH_TURN_ON_HD);
      this.dispatchEvent(newE);
    }
    
    /**
     * Turn off HD in splash screen
     * @param event
     * 
     */    
    protected override function hdOffHandler(event:MouseEvent):void
    {
      hd_on.visible = true;
      hd_off.visible = false;
      hd_switch.gotoAndStop(2);
      var newE:Event = new Event(WPPEvents.SPLASH_TURN_OFF_HD);
      this.dispatchEvent(newE);
    }
    
  }
}