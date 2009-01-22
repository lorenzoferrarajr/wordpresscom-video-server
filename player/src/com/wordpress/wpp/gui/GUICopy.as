/**
 * @package     com.wordpress.wpp.gui
 * @class       com.wordpress.wpp.gui.GUICopy
 *
 * @description   (linkage via FLA) The copy components in embedding box
 * @author        automattic
 * @created:      Aug 14, 2008
 * @modified:     Sep 09, 2008  
 *   
 */
package com.wordpress.wpp.gui
{
  import flash.display.MovieClip;
  import flash.display.SimpleButton;

  public class GUICopy extends MovieClip
  {
    public var copy_btn:SimpleButton;
    public function GUICopy()
    {
      super();
      if (_copy_btn)  copy_btn = _copy_btn;
    }
  }
}