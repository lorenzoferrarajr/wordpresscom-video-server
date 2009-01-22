/**
 * @package     com.wordpress.wpp.ui
 * @class       com.wordpress.wpp.ui.UILayoutRender
 *
 * @description   
 * @author        automattic
 * @created:      Aug 04, 2008
 * @modified:     Sep 09, 2008  
 *   
 */



package com.wordpress.wpp.ui
{  
  
  import com.wordpress.wpp.gui.*;
  
  import flash.display.DisplayObject;
  import flash.display.Sprite;
  import flash.display.Stage;
  import flash.events.Event;
  import flash.geom.Point;
  
  public class UILayoutRender
  {
    public static const LEFT:String = "left";
    public static const RIGHT:String = "right";
    public static const TOP:String = "top";
    public static const BOTTOM:String = "bottom";
    public static const WIDTH:String = "width";
    public static const HEIGHT:String = "height";
    public static const CENTERX:String = "centerx";
    public static const CENTERY:String = "centery";
    public static const MARGIN_RIGHT:String = "marginright"
    
    private var s:Stage;
    private var o:Object;
    private var tar:DisplayObject;
    private var vseektar:GUIVideoSeek;
    private var initPoint:Point;
    private var globalOffsetX:int = 0;
    private var globalOffsetY:int = 0;
    private var isSeekBar:Boolean = false;
    
    
    
    /**
     * 
     * @param _tar The target DisplayObject to be resized.
     * @param _o The configuration information.
     * 
     */    
    public function UILayoutRender(_tar:DisplayObject, _o:Object)
    {
      if (_tar is GUIVideoSeek)
      {
        isSeekBar = true;
        vseektar = _tar as GUIVideoSeek;
      }
      s = _tar.stage;
      o = _o;
      tar =  _tar;
      
      initPoint = new Point(0,0);
      globalOffsetX = tar.localToGlobal(initPoint).x;
      globalOffsetY = tar.localToGlobal(initPoint).y;
      
      renderObj();
      
      if (_tar is GUIVideoSeek)
      {
        s.addEventListener(Event.RESIZE, resizeHandler,false,1);
      }
      else
      {
        s.addEventListener(Event.RESIZE, resizeHandler,false,0);//1 will cause some fullscreen switching bug.
      }
      tar.addEventListener(Event.REMOVED_FROM_STAGE, removedStageHandler, false, 0 ,true);
    }
    
    public function removeRender():void
    {
      s.removeEventListener(Event.RESIZE, resizeHandler);
      tar.removeEventListener(Event.REMOVED_FROM_STAGE, removedStageHandler);
    }
    
    private function removedStageHandler(event:Event):void
    {
      removeRender();
    }
    
    private function renderObj():void
    {
      var m:String;
      var style:String;
      var value:*;
      if (isSeekBar)
      {
        for (m in o)
        {
          style = m;
          value = o[m];
          switch (style)
          {
            case "seekbottom":
            vseektar.y = Math.round(s.stageHeight - value - tar.height)+25;
            break;
            
            case MARGIN_RIGHT:
            var newWidth:Number = s.stageWidth - vseektar.parent.x - vseektar.x - value-5;
            vseektar._seek_current.width = newWidth;
            vseektar._seek_loader.load_width = newWidth;
            vseektar._back_mc.width = newWidth+3;
            vseektar._back_mc.x = -2;
            vseektar._back_mc.y = -2;
            
            if (vseektar.hitarea != null) 
            {
              var hitarea:Sprite = vseektar.hitarea;
              var oldHeight:Number = hitarea.height;
              hitarea.graphics.clear();
              hitarea.graphics.lineStyle(0,0,0);
              hitarea.graphics.beginFill(0,0);
              hitarea.graphics.drawRect(0,0,newWidth,oldHeight);
              hitarea.graphics.endFill();
            }  
            break;
            
            case LEFT:
            tar.x = getLocalX((value));
            break;
            
            case RIGHT:
            tar.x = getLocalX(Math.round(s.stageWidth - value - tar.width));
            break;
            
            case TOP:
            tar.y = Math.round(value);
            break;
            
            case BOTTOM:
            tar.y = getLocalY(Math.round(s.stageHeight - value - tar.height));
            break;
            
            case WIDTH:
            tar.width = s.stageWidth - o.marginLeft - o.marginRight;
            tar.x = o.marginLeft;
            break;
            
            case HEIGHT:
            tar.height = s.stageHeight;
            break;
            
            case CENTERX:
            tar.x = getLocalX(Math.round((s.stageWidth - tar.width)/2 + value));
            break;
            
            case CENTERY:
            tar.y = getLocalY(Math.round((s.stageHeight - tar.height)/2 + value));
            break;
            
            default: break;
          }
        }
      }
      else 
      {
        for (m in o)
        {
          style = m;
          value = o[m];
          switch (style)
          {
            case LEFT:
            tar.x = (value);
            break;
            
            case RIGHT:
            tar.x = Math.round(s.stageWidth - value - tar.width);
            break;
            
            case TOP:
            tar.y = Math.round(value);
            break;
            
            case BOTTOM:
            tar.y = Math.round(s.stageHeight - value - tar.height);
            break;
            
            case WIDTH:
            tar.width = s.stageWidth - o.marginLeft - o.marginRight;
            tar.x = o.marginLeft;
            break;
            
            case HEIGHT:
            tar.height = s.stageHeight;
            break;
            
            case CENTERX:
            tar.x = Math.round((s.stageWidth - tar.width)/2 + value);
            break;
            
            case CENTERY:
            tar.y = Math.round((s.stageHeight - tar.height)/2 + value);
            break;
            
            default: break;
          }
        }
      }
    }
    
    private function getLocalX(_x:Number):int
    {
      var p:Point = new Point(_x, 0);
      return int(Math.round(tar.parent.globalToLocal(p).x));
    }
    
    private function getLocalY(_y:Number):int
    {
      var p:Point = new Point(0, _y);
      return int(Math.round(tar.parent.globalToLocal(p).y));
    }
    
    private function resizeHandler(event:Event):void
    {
      renderObj();      
    }
    
  }
}