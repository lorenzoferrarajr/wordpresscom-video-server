/**
 * @package     com.wordpress.wpp.utils
 * @class       com.wordpress.wpp.utils.StatsReport
 *
 * @description   Stats-Report features
 * @author      automattic
 * @created:     Sep 04, 2008
 * @modified:     Nov 17, 2008 
 *   
 */

package com.wordpress.wpp.utils
{
  
  import com.wordpress.wpp.config.WPPConfiguration;
  
  import flash.events.IOErrorEvent;
  import flash.events.SecurityErrorEvent;
  import flash.events.TimerEvent;
  import flash.net.URLLoader;
  import flash.net.URLRequest;
  import flash.utils.Timer;
  
  // This is a static class
  public class StatsReport
  {
    private var stats_url_base:String;
    private var interval:Number;
    private var playingTimer:Timer;
    private var checkTime:int;
    private var doc:WPPDocument;
    
    public function videoView():void
    {
      //trace("video_play!!!");
      //ExternalInterface.call("console.info", "video_play: "+com.wordpress.wpp.config.WPPConfiguration.IS_LOCAL_MODE.toString());
      var targetURL:String = stats_url_base+"&page_url="+GetDomain.getDomain()+"&video_play=1&rand="+Math.random().toString();
      var r:URLRequest = new URLRequest(targetURL);
      var l:URLLoader = new URLLoader();
      l.addEventListener(IOErrorEvent.IO_ERROR,function():void{});
      l.addEventListener(SecurityErrorEvent.SECURITY_ERROR, securityEventHandler);
      if (!WPPConfiguration.IS_LOCAL_MODE)
      {
        //trace("video_play sent to: "+r.url);
        l.load(r);
      }
      startTimer();
    }
    
    public function StatsReport(statsUrlBase:String, _interval:Number, _doc:WPPDocument)
    {
      // 0 - init the documentation class
      doc = _doc;
      
      // 1 - init the basic configuration for current stats reporting
      stats_url_base = statsUrlBase;

      interval = _interval;
      
      // 2 - Setup a timer (once/1sec)
      playingTimer = new Timer(1000);
      playingTimer.addEventListener(TimerEvent.TIMER,timerHandler);
    }
    
    // Resume the stats reporter interval
    public function resume():void
    {
      trace("start.resume()");
      playingTimer.start();
    }
    
    public function hold():void
    {
      trace("statreport.hold()");
      playingTimer.stop();
      sendTimerRequest();
      resetCheckTime();
    }
    
    public function videoImpression():void
    {
      //trace("video impression");
      //ExternalInterface.call("console.info", "impression: "+WPPConfiguration.IS_LOCAL_MODE.toString());
      var targetURL:String = stats_url_base+"&page_url="+GetDomain.getDomain()+"&rand="+Math.random().toString()+"&video_impression=1";
      var r:URLRequest = new URLRequest(targetURL);
      var l:URLLoader = new URLLoader();
      l.addEventListener(IOErrorEvent.IO_ERROR,function():void{});
      l.addEventListener(SecurityErrorEvent.SECURITY_ERROR, securityEventHandler);
      if (!WPPConfiguration.IS_LOCAL_MODE)
      {
        //ExternalInterface.call("console.info","video impression sent to: "+r.url);
        l.load(r);
      }
    }

    private function startTimer():void
    {
      resetCheckTime();
      trace("startTimer()");
      playingTimer.start();
    }
    
    private function timerHandler(event:TimerEvent):void
    {
      
      checkTime = checkTime+1;
      if (checkTime >= interval)
      {
        sendTimerRequest(int(interval));
        resetCheckTime();
      }
      trace("TIMER:"+checkTime);
    }
    
    private function resetCheckTime():void
    {
      checkTime = 0;
    }
    
    private function securityEventHandler(event:SecurityErrorEvent):void
    {
      
    }
    
    public function killAll():void
    {
      if (playingTimer)
      {
        doc.total_report_time = 0;
        playingTimer.stop();
        playingTimer.removeEventListener(TimerEvent.TIMER,timerHandler);
        playingTimer = null;
      }
    }
    
    /**
     * 
     * @param t(int) Second to report.
     * 
     */    
    private function sendTimerRequest(t:int = -1):void
    {
      // 0 - If the "t" is not specified, send the checkTime to server.
      if (t==-1)
      {
        t = int(Math.ceil(checkTime));
      }
      
      // 1 - If the t = 0, send nothing
      if (t==0) return;
      
      // 2 - If the total sended time overflows, send nothing ( SAFEGUARD )
      doc.total_report_time += t;
      if (doc.total_report_time > doc.max_report_time)
      {
        return;
      }
      // 3 - Send!
      trace("timer:"+t);
      var targetURL:String = stats_url_base+"&page_url="+GetDomain.getDomain()+"&rand="+Math.random().toString()+"&t="+t.toString();
      var r:URLRequest = new URLRequest(targetURL);
      var l:URLLoader = new URLLoader();
      l.addEventListener(IOErrorEvent.IO_ERROR,function():void{});
      l.addEventListener(SecurityErrorEvent.SECURITY_ERROR, securityEventHandler);
      if (!WPPConfiguration.IS_LOCAL_MODE)
      {
        l.load(r);
      }
      
      // 4 - Reset the timer
      resetCheckTime();
    }
  }
}

import flash.external.ExternalInterface;

internal class GetDomain
{
  public static function getDomain():String
  {
    var embedURL:String = "";
    try
    {
      if (ExternalInterface.available)
        embedURL = encodeURI(String(ExternalInterface.call("function(){return document.location.href.toString();}")));
    }
    catch(error:SecurityError)
    {
      embedURL = "";
    }
    if (embedURL == "null")
      embedURL = "";
    return embedURL;
  }
}