/**
 * @package     com.wordpress.wpp.ui
 * @class       com.wordpress.wpp.ui.UISlider
 *
 * @description   
 * @author        automattic
 * @created:      Jul 27, 2008
 * @modified:     Sep 09, 2008  
 *   
 */



package com.wordpress.wpp.ui
{
  import com.wordpress.wpp.events.ObjectEvent;
  import com.wordpress.wpp.events.WPPEvents;
  import com.wordpress.wpp.gui.*;
  
  import flash.display.Shape;
  import flash.display.Sprite;
  import flash.events.EventDispatcher;
  import flash.events.MouseEvent;
  import flash.events.TimerEvent;
  import flash.utils.Timer;
  
  public class UISlider extends EventDispatcher
  {
    protected var svolume:GUIVolumeSeek;
    protected var svideo:GUIVideoSeek;
    protected var isVideoSlider:Boolean;
    
    /**
     * The initial resource on stage 
     */    
    protected var current:Sprite;
    
    /**
     * The mask is to let you watch a more smooth GUI 
     */    
    protected var current_mask:Shape;
    
    /**
     * The hit area of a seek bar 
     */    
    protected var seek_hitArea:Sprite;
    
    /**
     * The seek timer, used for seeking logic 
     */    
    protected var seek_timer:Timer;
    
    /**
     * Constructor 
     * @param sprite_bar
     * 
     */    
    public function UISlider(sprite_bar:Sprite)
    {
      super();
      svolume = sprite_bar as GUIVolumeSeek;
      
      if (svolume == null)
        svideo = sprite_bar as GUIVideoSeek;
    }

    /**
     * seek position 
     * @param _p
     * 
     */
    public function seekPosAt(_p:Number):void
    {
      if (isNaN(_p)) _p = 0;
      current_mask.width = _p*current.width;
      if (_p == 0)
      {
        current.visible = false;
      }
      else
      {
        current.visible = true;
      }
    }
    
    /**
     * Initialize the mask 
     * 
     */    
    protected function initMask():void
    {
      current_mask = new Shape();
      current_mask.graphics.beginFill(0,1);
      current_mask.graphics.drawRect(0,0,.1,current.height);
      current_mask.graphics.endFill();
    }
    
    /**
     * When the mouse is pressed for a seek action
     * @param event
     * 
     */    
    protected function seekPressHandler(event:MouseEvent):void
    {
      seeking();
      seek_timer = new Timer(100);
      seek_timer.addEventListener(TimerEvent.TIMER,seekingTimerHandler,false,0,true);
      seek_timer.start();
      event.target.stage.addEventListener(MouseEvent.MOUSE_UP, seekUpHandler);
    }
    
    /**
     * When the mouse is released for applying the seek action
     * @param event
     * 
     */    
    protected function seekUpHandler(event:MouseEvent):void
    {
      seek_timer.stop();
      seek_timer.removeEventListener(TimerEvent.TIMER,seekingTimerHandler);
    }
    
    /**
     * seeking timer 
     * @param event
     * 
     */    
    private function seekingTimerHandler(event:TimerEvent):void
    {
      seeking();
    }
    
    /**
     * seeking 
     * 
     */
    private function seeking():void
    {
      var smx:Number = seek_hitArea.mouseX;
      var mousePercentage:Number;
      
      
      if ( smx < 0 )
      {
        mousePercentage = 0;
        
        
      }
      else if ( smx > current.width)
      {
        mousePercentage = 1;
        
      }
      else
      {
        mousePercentage = seek_hitArea.mouseX / current.width;
      }
      var seekEvent:ObjectEvent = new ObjectEvent(WPPEvents.SLIDER_SEEKING, mousePercentage); 
      dispatchEvent(seekEvent);
    }
    

  }
}