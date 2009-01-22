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
  import flash.display.Shape;
  import flash.display.Sprite;
  import flash.events.MouseEvent;
  
  import com.wordpress.wpp.gui.*
  
  /**
   * VideoSlider is a class handles the interactive seeking logic. 
   */  
  public class UIVideoSlider extends UISlider
  {
    private var loading_mask:Shape;
    private var loading:Sprite;
    public var cursor:GUIVideoCursor;
    
    public function UIVideoSlider(sprite_bar:Sprite)
    {
      super(sprite_bar);
      initIDEStage();
      initMask();
    }
    public function loadPosAt(_p:Number):void
    {
      loading_mask.width = _p*current.width;
      if (_p == 0)
      {
        loading.visible = false;
      }
      else
      {
        loading.visible = true;
      }
    }
    protected override function initMask():void
    {
      super.initMask();
      current.mask = current_mask;
      svideo.addChild(current_mask);
      loading_mask = new Shape();
      loading_mask.graphics.beginFill(0,1);
      loading_mask.graphics.drawRect(0,0,.1,current.height);
      loading_mask.graphics.endFill();
      svideo.addChild(loading_mask);
      loading.mask = loading_mask;
    }
    public override function seekPosAt(_p:Number):void
    {
      super.seekPosAt(_p);
      cursor.x = int(_p*current.width);
    }
    private function initIDEStage():void
    {
    
      current = svideo.seek_current;
      loading = svideo.seek_loader;
      cursor = svideo.cursor;
      seek_hitArea = new Sprite();
      seek_hitArea.graphics.lineStyle(0,0,0);
      seek_hitArea.graphics.beginFill(0,0);
      seek_hitArea.graphics.drawRect(0,0,current.width,current.height);
      seek_hitArea.graphics.endFill();        
      seek_hitArea.useHandCursor = true;
      seek_hitArea.buttonMode = true;
      seek_hitArea.addEventListener(MouseEvent.MOUSE_DOWN,seekPressHandler,false,0,true);
      
      svideo.addChild(seek_hitArea);
      svideo.seekHitArea = seek_hitArea;
      
      cursor.addEventListener(MouseEvent.MOUSE_DOWN,seekPressHandler,false,0,true);
      cursor.useHandCursor = true;
      cursor.buttonMode = true;
      cursor.cursor_txt.mouseEnabled = false;
      
    }
    
  }
}