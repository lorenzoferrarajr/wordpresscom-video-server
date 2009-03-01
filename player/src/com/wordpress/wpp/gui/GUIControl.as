/**
 * @package     com.wordpress.wpp.gui
 * @class       com.wordpress.wpp.gui.GUIControl
 *
 * @description   (linkage via FLA) Main controller
 * @author        automattic
 * @created:      Aug 04, 2008
 * @modified:     Sep 09, 2008  
 *   
 */

package com.wordpress.wpp.gui
{
  import com.wordpress.wpp.ui.UILayoutManager;
  
  import flash.display.MovieClip;
  import flash.display.SimpleButton;
  import flash.display.Sprite;
  import flash.events.Event;
  
  dynamic public class GUIControl extends Sprite
  {
    private var _play_btn:SimpleButton;
    private var _pause_btn:SimpleButton;
    private var _fullscreen_btn:SimpleButton;
    private var _seek_bar:GUIVideoSeek;
    private var _speaker:MovieClip;
    private var _volume_bar:GUIVolumeSeek;
    private var _bg_mc:MovieClip;
    private var _top_bar:MovieClip;
    private var _info_button:SimpleButton;
    
    public var _hd_on:SimpleButton;
    public var _hd_off:SimpleButton;
    public var _hd_switch:MovieClip;
    
    public function GUIControl()
    {
      super();

      if( play_btn )       _play_btn     = play_btn;
      if( pause_btn )     _pause_btn     = pause_btn;
      if( fullscreen_btn )  _fullscreen_btn = fullscreen_btn;
      if( seek_bar )      _seek_bar     = seek_bar;
      if( volume_bar )    _volume_bar   = volume_bar;
      if( bar_bg )      _bg_mc      = bar_bg;
      if( top_bar )      _top_bar    = top_bar;
      if( hd_on )      _hd_on     = hd_on;
      if( hd_off )    _hd_off    = hd_off;
      if( speaker )    _speaker = speaker;
      if( hd_switch )    _hd_switch = hd_switch;
      if( info_button )  _info_button = info_button;
      
      _hd_switch.gotoAndStop(2);
      _hd_off.visible = false;
      
      var vbright:Number = this.width - this._volume_bar.x - this._volume_bar.width;
      var vbbottom:Number = 19;

      var sbbottom:Number = this.height - this._seek_bar.y - this._seek_bar.height;
      var sbmarginright:Number = this.width - this._seek_bar.width - this._seek_bar.x;
      
      UILayoutManager.addTarget(_info_button,{
        "left":71,
        "top":5
      });
      
      UILayoutManager.addTarget(_hd_off,{
      "right":11,
      "top":5
      });
      
      UILayoutManager.addTarget(_hd_on,{
      "right":41,
      "top":5
      });
      
      UILayoutManager.addTarget(_fullscreen_btn,{
      "right":6,
      "bottom":6
      });
      
      UILayoutManager.addTarget(_pause_btn,{
      "left":0,
      "bottom":0
      });

      UILayoutManager.addTarget(_speaker,{
      "right":61,
      "bottom":8
      });
      UILayoutManager.addTarget(_play_btn,{
      "left":0,
      "bottom":0
      });
      UILayoutManager.addTarget(_volume_bar,{
      "right":32,
      "bottom":4
      });
      UILayoutManager.addTarget(_bg_mc,{
      "width":1,
      "bottom":0,
      "marginLeft":51,
      "marginRight":0
      });
      UILayoutManager.addTarget(_top_bar,{
      "width":1,
      "top":0,
      "marginLeft":66,
      "marginRight":98
      });
      UILayoutManager.addTarget(_hd_switch,{
      "right":0,
      "top":0
      });
      UILayoutManager.addTarget(_seek_bar,{
      "seekbottom":7,
      "marginright":74
      });
    }
    
    public function resetInfoButtonToLeftTop():void {
      UILayoutManager.removeTarget(_info_button);
      UILayoutManager.addTarget(_info_button,{
        "left":5,
        "top":5
      });
    }
    
    public function initFrameHandler():void
    {
      renderFrame();
      this.stage.addEventListener(Event.RESIZE, renderFrameHandler);
    }
    
    private function renderFrameHandler(event:Event):void
    {
      renderFrame();
    }
    
    private function renderFrame():void
    {
      var w:Number = this.stage.stageWidth;
      var h:Number = this.stage.stageHeight;
      this.graphics.clear();
      this.graphics.lineStyle(1,0xffffff,.15);
      this.graphics.moveTo(0,24)
      this.graphics.lineTo(w,24);
      
      if ((this.root as WPPDocument).info.embededCode)
      {
        this.graphics.moveTo(65,24);
        this.graphics.lineTo(65,0);
      }
      
      this.graphics.moveTo(0,h-30);
      this.graphics.lineTo(w,h-30);
      this.graphics.moveTo(50,h-30);
      this.graphics.lineTo(50,h);
      if ((this.root as WPPDocument).info.hasHD)
      {
        this.graphics.moveTo(w-98,0);
        this.graphics.lineTo(w-98,24);
      }
    }
    
    public function alterHDDisplay(b:Boolean)
    {
      UILayoutManager.removeTarget(_top_bar);
      var leftByEmbed:Number = 66;
      if (!(this.root as WPPDocument).info.embededCode)
        leftByEmbed = 0;
      
      
      _hd_switch.visible = b;
      if (b)
      {
        UILayoutManager.addTarget(_top_bar,{
        "width":1,
        "top":0,
        "marginLeft":leftByEmbed,
        "marginRight":98
        });
        if((this.root as WPPDocument).isHD)
        {
          _hd_off.visible = true;
          _hd_on.visible = false;
          _hd_switch.visible = true;
          _hd_switch.gotoAndStop(1);
        }
      }
      else
      {
        UILayoutManager.addTarget(_top_bar,{
        "width":1,
        "top":0,
        "marginLeft":leftByEmbed,
        "marginRight":0
        });
        _hd_off.visible = false;
        _hd_on.visible = false;
      
      }
    }
  }
}