/**
 * @package     com.wordpress.wpp.utils
 * @class       com.wordpress.wpp.utils.Fader
 *
 * @description   
 * @author      automattic
 * @created:     Aug 31, 2008
 * @modified:     Sep 09, 2008  
 *   
 */
 
package com.wordpress.wpp.utils
{
  import flash.display.DisplayObject;
  import flash.events.EventDispatcher;
  import flash.events.TimerEvent;
  import flash.utils.Timer;
  
  public class Fader extends EventDispatcher
  {
    private var tar:DisplayObject;
    private var timer:Timer;
    private var duration:Number;
    
    public function Fader(_do:DisplayObject, _time:Number)
    {
      super();
      tar = _do as DisplayObject;
      duration = _time;
      
      timer = new Timer(20,0);
      timer.addEventListener(TimerEvent.TIMER,timerHandler, false,0,false);
      timer.start();
    }
    
    private function timerHandler(event:TimerEvent):void
    {
      tar.alpha = tar.alpha - .2;
      if (tar.alpha <= 0.05)
      {
        tar.visible = false;
        tar.alpha = 0;
        timer.removeEventListener(TimerEvent.TIMER,timerHandler);
      }
    }
    
    public function abort():void
    {
      timer.stop();
    }

  }
}