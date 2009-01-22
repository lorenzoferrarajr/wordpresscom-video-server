/**
 * @package     com.wordpress.wpp.gui
 * @class       com.wordpress.wpp.gui.GUIEmbedMain
 *
 * @description   (linkage via FLA) The embedding components
 * @author        automattic
 * @created:      Aug 14, 2008
 * @modified:     Sep 09, 2008  
 *   
 */
package com.wordpress.wpp.gui
{
  import flash.display.MovieClip;
  import flash.text.TextField;

  public class GUIEmbedMain extends MovieClip
  {
    public var embed_wp:TextField;
    public var embed_blog:TextField;
    public var embed_html:TextField;
    public var embed_large:TextField;
    public var copy_blog:GUICopy;
    public var copy_wp:GUICopy;
    public var copy_html:GUICopy;
    public var copy_large:GUICopy;

    public function GUIEmbedMain()
    {
      super();
      
      if( _embed_wp )     embed_wp     = _embed_wp;
      if( _embed_html )     embed_html     = _embed_html;
      if( _copy_wp )       copy_wp     = _copy_wp;
      if( _copy_html )     copy_html     = _copy_html;
      
      if( _copy_large )     copy_large     = _copy_large;
      if( _embed_large )     embed_large   = _embed_large;
      
    }
    
  }
}