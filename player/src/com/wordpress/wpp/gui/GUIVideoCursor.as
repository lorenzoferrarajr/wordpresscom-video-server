/**
 * @package     com.wordpress.wpp.gui
 * @class       com.wordpress.wpp.gui.GUIVideoCursor
 *
 * @description   (linkage via FLA) Video cursor
 * @author        automattic
 * @created:      Jul 28, 2008
 * @modified:     Sep 29, 2008  
 *   
 * @bugfix:  Sep 29, fixed the bug of the font and if the length of a video is as long as an hour, the look would be more smooth
 */
 
package com.wordpress.wpp.gui
{
  import flash.display.MovieClip;
  import flash.display.Sprite;
  import flash.events.Event;
  import flash.text.TextField;
  
  public class GUIVideoCursor extends Sprite
  {
    public var cursor_txt:TextField;
    public var cursor_body:MovieClip;
    public function GUIVideoCursor()
    {
      super();
      if (_cursor_body) cursor_body = _cursor_body;
      if (_cursor_txt) cursor_txt = _cursor_txt;
      cursor_body.stop();  
    }
    
    public function set cursorTime (t:String):void
    {
      cursor_txt.text = t;
      switch(t.length)
      {
        case 4:
          cursor_body.gotoAndStop(1);
          break;
        case 5:
          cursor_body.gotoAndStop(2);
          break;
        default:
          cursor_body.gotoAndStop(3);
          break;
      }
    }
  }
}