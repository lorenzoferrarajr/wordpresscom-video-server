package com.wordpress.wpp.core
{
  public class VCoreDynamicSeeking extends VCore
  {
    public function VCoreDynamicSeeking(_doc:WPPDocument, _flv_url:String, width:Number, height:Number)
    {
      super(_doc, _flv_url, width, height);
    }
    
    override public function seek(seekTime:Number, isReplay:Boolean=false):void
    {
      trace("time:"+seekTime.toString()+"isReplay"+isReplay.toString());
      super.seek(seekTime, isReplay);
      if (seekTime < offsetSeconds || seekTime - offsetSeconds > fetchedSeconds)
      {
        trace("seekTime:       "+seekTime);
        trace("offsetSeconds:  "+offsetSeconds);
        trace("fetchedSeconds: "+fetchedSeconds);
        offsetSeconds = int(seekTime);
        playFLV(init_flv_url+"?offset="+offsetSeconds.toString(),
                video_width, video_height, false);
      }
      
      return;
      
    }
    
  }
}