/**
 * @package     com.wordpress.wpp.ui
 * @class       com.wordpress.wpp.ui.UISlider
 *
 * @description   
 * @author        automattic
 * @created:      Jul 30, 2008
 * @modified:     Sep 09, 2008  
 *   
 */


package com.wordpress.wpp.ui
{
  import com.wordpress.wpp.gui.*;
  
  import flash.display.MovieClip;
  import flash.display.Sprite;
  import flash.events.MouseEvent;
  
  public class UIVolumeSlider extends UISlider
  {    
    public var volume:Number;
    public var cursor:MovieClip;
    
    public override function seekPosAt(_p:Number):void
    {
      super.seekPosAt(_p);
      volume = _p;
      cursor.x = int(_p*current.width);
    }
    
    public function UIVolumeSlider(sprite_bar:Sprite)
    {
      super(sprite_bar);
      initIDEStage();
      initMask();
    }

    private function initIDEStage():void
    {
      current = svolume.seek_current;
      cursor = svolume.cursor;
      seek_hitArea = new Sprite();
      seek_hitArea.graphics.lineStyle(0,0,0);
      seek_hitArea.graphics.beginFill(0,0);
      seek_hitArea.graphics.drawRect(0,0,current.width,current.height);
      seek_hitArea.graphics.endFill();        
      seek_hitArea.useHandCursor = true;
      seek_hitArea.buttonMode = true;
      seek_hitArea.addEventListener(MouseEvent.MOUSE_DOWN,seekPressHandler,false,0,true);
      
      svolume.addChild(seek_hitArea);
      cursor.addEventListener(MouseEvent.MOUSE_DOWN,seekPressHandler,false,0,true);
      cursor.useHandCursor = true;
      cursor.buttonMode = true;
    }
    
    protected override function initMask():void
    {
      super.initMask();
      current.mask = current_mask;
      svolume.addChild(current_mask);
    }

  }
}