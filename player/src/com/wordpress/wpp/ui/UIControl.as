/**
 * @package     com.wordpress.wpp.ui
 * @class       com.wordpress.wpp.ui.UIControl
 *
 * @description   Controller manager base class
 * @author        automattic
 * @created:      Aug 14, 2008
 * @modified:     Sep 29, 2008  
 *   
 */


package com.wordpress.wpp.ui
{  
  
  import com.wordpress.wpp.events.WPPEvents;
  import com.wordpress.wpp.gui.*;
  
  import flash.display.MovieClip;
  import flash.display.SimpleButton;
  import flash.events.Event;
  import flash.events.EventDispatcher;
  import flash.events.MouseEvent;
  import flash.net.URLRequest;
  import flash.net.navigateToURL;
  
  /**
   * 
   * UI Control base class for VControl and UISplashControl
   * 
   */  
  public class UIControl extends EventDispatcher
  {
    // Doc Reference
    protected var doc:WPPDocument;
    
    // GUI Objects
    protected var gc:GUIControl;
    protected var play_btn:SimpleButton;
    protected var pause_btn:SimpleButton;
    protected var fullscreen_btn:SimpleButton;
    protected var hd_on:SimpleButton;
    protected var hd_off:SimpleButton;
    protected var hd_switch:MovieClip;
    protected var info_button:SimpleButton;
    
    protected var seek_bar:GUIVideoSeek;
    protected var volume_bar:GUIVolumeSeek;

    /**
     * Whether or not to display the HD choice 
     * @param hasHD
     * 
     */    
    public function toggleHDChoice(hasHD:Boolean):void
    {
      alterHDEvents(hasHD);
      gc.alterHDDisplay(hasHD);
    }
    
    /**
     * alter HD Buttons 
     * @param b
     * 
     */    
    protected function alterHDEvents(b:Boolean):void
    {
      if (b)
      {
        hd_on.addEventListener(MouseEvent.CLICK, hdOnHandler);
        hd_off.addEventListener(MouseEvent.CLICK, hdOffHandler);
      }
      else
      {
        hd_on.removeEventListener(MouseEvent.CLICK, hdOnHandler);
        hd_off.removeEventListener(MouseEvent.CLICK, hdOffHandler);
      }
    }
    
    /**
     * Turn on HD mode handler 
     * @param event
     * 
     */  
    protected function hdOnHandler(event:MouseEvent):void
    {
      hd_on.visible = false;
      hd_off.visible = true;
      hd_switch.gotoAndStop(1);
      var newE:Event = new Event(WPPEvents.TURN_ON_HD);
      this.dispatchEvent(newE);
    }
    
    /**
     * Turn off HD mode handler 
     * @param event
     * 
     */    
    protected function hdOffHandler(event:MouseEvent):void
    {
      hd_on.visible = true;
      hd_off.visible = false;
      hd_switch.gotoAndStop(2);
      var newE:Event = new Event(WPPEvents.TURN_OFF_HD);
      this.dispatchEvent(newE);
    }
    
    /**
     * FullScreen handler
     * @param event
     * 
     */    
    protected function fullScreenHandler(event:MouseEvent):void
    {
      switch(doc.stage.displayState)
      {
                case "normal":
                    doc.stage.displayState = "fullScreen";
                    break;
                case "fullScreen":
                default:
                     doc.stage.displayState = "normal";
                    break;
            }
    }
    
    /**
     * Constructor
     * @param guiCtr
     * 
     */    
    function UIControl(guiCtr:GUIControl)
    {      
      // Controller
      gc = guiCtr;
      
      // Document Class Reference
      doc = gc.root as WPPDocument;
      
      // IDE GUI Objects mapping
      // The gc.- stands for the objects in the IDE stage
      play_btn       = gc.play_btn;
      pause_btn      = gc.pause_btn;
      fullscreen_btn = gc.fullscreen_btn;
      seek_bar       = gc.seek_bar;
      volume_bar     = gc.volume_bar;
      hd_on          = gc.hd_on;
      hd_off         = gc.hd_off;
      hd_switch      = gc.hd_switch;
      info_button    = gc.info_button;
      
      // Turn of the pause button at first
      pause_btn.visible = false;
      alterHDEvents(doc.info.hasHD);
      fullscreen_btn.addEventListener(MouseEvent.CLICK, fullScreenHandler);
      var infoButtonPopup:Function = function(event:MouseEvent):void {
        navigateToURL(new URLRequest("http://support.wordpress.com/videos"),"_blank");
      }
      info_button.addEventListener(MouseEvent.CLICK, infoButtonPopup);
    }

  }
}