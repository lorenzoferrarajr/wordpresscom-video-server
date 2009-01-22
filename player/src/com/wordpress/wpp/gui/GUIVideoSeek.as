/**
 * @package     com.wordpress.wpp.gui
 * @class       com.wordpress.wpp.gui.GUIVideoSeek
 *
 * @description   (linkage via FLA) Video Seek components
 * @author        automattic
 * @created:      Jul 27, 2008
 * @modified:     Sep 09, 2008  
 *   
 */

package com.wordpress.wpp.gui
{
  import flash.display.MovieClip;
  import flash.display.Sprite;
  
  public class GUIVideoSeek extends Sprite
  {
    public var loading_bar:Sprite;
    public var _cursor:GUIVideoCursor;
    public var _seek_current:GUISeekSymbol;
    public var _seek_loader:GUISeekLoadSymbol;
    public var _back_mc:MovieClip;
    private var _sha:Sprite;
    public function GUIVideoSeek()
    {
      super();
      _cursor = cursor;
      _seek_current = seek_current;
      _seek_loader = seek_loader;
      _back_mc = seek_bar_back;
    }
    
    public function get hitarea():Sprite
    {
      return _sha; 
    }
    
    public function set seekHitArea(value:Sprite):void
    {
      _sha = value;
    }
  }
}
