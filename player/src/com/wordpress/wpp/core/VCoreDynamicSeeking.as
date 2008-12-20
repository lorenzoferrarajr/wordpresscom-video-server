package com.wordpress.wpp.core
{
  public class VCoreDynamicSeeking extends VCore
  {
    public function VCoreDynamicSeeking(_doc:WPPDocument, _flv_url:String, width:Number, height:Number)
    {
      super(_doc, _flv_url, width, height);
    }
    
    override public function seek(t:Number, isReplay:Boolean=false):void
    {
      trace("time:"+t.toString()+"isReplay"+isReplay.toString());
      super.seek(t,isReplay);
    }
    
    
  }
}