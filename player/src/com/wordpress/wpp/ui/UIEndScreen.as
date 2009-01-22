/**
 * @package     com.wordpress.wpp.ui
 * @class       com.wordpress.wpp.ui.UIEndScreen
 * @deprecated  com.wordpress.wpp.ui.UIMenuScreen
 *
 * @description   
 * @author        automattic
 * @created:      Aug 14, 2008
 * @modified:     Sep 09, 2008  
 *   
 */


package com.wordpress.wpp.ui
{  
  import com.wordpress.wpp.gui.*;
  
  import flash.display.MovieClip;
  import flash.events.EventDispatcher;
  import flash.events.MouseEvent;
  
  public class UIEndScreen extends EventDispatcher
  {
    private var doc:WPPDocument;
    private var replayButton:GUIReplayButton;
    
    public function UIEndScreen(documentInstance:WPPDocument)
    {
      doc = documentInstance;
      doc.mainVideo.vpause(true);
      replayButton = new GUIReplayButton();
      doc.addChild(replayButton);
      UILayoutManager.addTarget(replayButton, {centerx:0, centery:0});
      replayButton.addEventListener(MouseEvent.CLICK, replayHandler);
    }
    
    private function replayHandler(event:MouseEvent):void
    {
      doc.mainVideo.replay();
    }
    
    /**
     * Remove the listeners/instances for end screen 
     * 
     */    
    public function unregister():void
    {
      trace("REMOVING");
      doc.removeChild(replayButton);
      replayButton.removeEventListener(MouseEvent.CLICK, replayHandler);
    } 
  }
}