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