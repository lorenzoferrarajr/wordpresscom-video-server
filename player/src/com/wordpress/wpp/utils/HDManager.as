 /**
 * @package     com.wordpress.wpp.utils
 * @class       com.wordpress.wpp.utils.HDManager
 *
 * @description   HD Manager
 * @author      automattic
 * @created:     Aug 09, 2008
 * @modified:     Oct 29, 2008  
 *   
 */
 

package com.wordpress.wpp.utils
{
  import com.wordpress.wpp.config.VideoFormat;
  import com.wordpress.wpp.config.VideoInfo;
  import com.wordpress.wpp.gui.GUIControl;
  import com.wordpress.wpp.ui.UISplashControl;
  
  import flash.display.Stage;
  import flash.events.EventDispatcher;
  
  public class HDManager extends EventDispatcher
  {
    private var canvasWidth:Number;
    private var canvasHeight:Number;
    
    private var s:Stage;
    private var d:WPPDocument;
    
    private var hasSTD:Boolean = false;
    private var hasDVD:Boolean = false;
    private var hasHD:Boolean = false;
    
    private var vo_hd:VideoFormat = new VideoFormat();
    private var vo_dvd:VideoFormat = new VideoFormat();
    private var vo_std:VideoFormat = new VideoFormat();
    
    public function renderHDButtons(ctr:UISplashControl, guiCtr:GUIControl):void
    {
      ctr.toggleHDChoice(d.info.hasHD);
    }
    
    /**
     * Constructor of the HD Switching Manager
     * @param doc
     * 
     */    
    public function HDManager(doc:WPPDocument)
    {
      d = doc;
      s = doc.stage;
      
      var info:VideoInfo = doc.info;
      if (info.fmt_hd)
      {
        hasHD = true;
        vo_hd = info.fmt_hd;
        info.status_url = info.fmt_hd.status_url;
      }
      if (info.fmt_dvd)
      {
        hasDVD = true;
        vo_dvd = info.fmt_dvd;
        info.status_url = info.fmt_dvd.status_url;
      }
      if (info.fmt_std)
      {
        hasSTD = true;
        vo_std = info.fmt_std;
        info.status_url = info.fmt_std.status_url;
      }
    }
    
    public function getSTDVideo():VideoFormat
    {
      canvasWidth   = s.stageWidth;
      canvasHeight   = s.stageHeight;      
      
      var info:Object = d.info;
      if(canvasWidth >= 640 && hasDVD)
      {
        info.status_url = info.fmt_dvd.status_url;
        return vo_dvd;
      }
      info.status_url = info.fmt_std.status_url;
      return vo_std;
    }
    
    public function getHDVideo():VideoFormat
    {
      var info:Object = d.info;
      info.status_url = info.fmt_hd.status_url;
      return this.vo_hd;
    }
    

  }
}