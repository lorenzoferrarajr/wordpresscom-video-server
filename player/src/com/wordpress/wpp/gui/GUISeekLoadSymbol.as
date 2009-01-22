/**
 * @package     com.wordpress.wpp.gui
 * @class       com.wordpress.wpp.gui.GUISeekLoadSymbol
 *
 * @description   (linkage via FLA) The bar to hold loading texture
 * @author        automattic
 * @created:      Aug 14, 2008
 * @modified:     Sep 09, 2008  
 *   
 */
package com.wordpress.wpp.gui
{
  import flash.display.Bitmap;
  import flash.display.Sprite;

  public class GUISeekLoadSymbol extends Sprite
  {
    private var b:Bitmap = new Bitmap;
    private var bd:GUIVideoSeekBitmapTile;

    public function GUISeekLoadSymbol()
    {
      super();
      bd = new GUIVideoSeekBitmapTile(3,9)
    }

    public function set load_width(v:Number):void
    {
      this.graphics.clear();
      this.graphics.lineStyle();
      this.graphics.beginBitmapFill(bd);
      this.graphics.drawRect(0,0,v,9);
      this.graphics.endFill();
      this.width = v;
    }
  }
}