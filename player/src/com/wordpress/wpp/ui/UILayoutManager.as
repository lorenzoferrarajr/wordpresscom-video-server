/**
 * @package     com.wordpress.wpp.ui
 * @class       com.wordpress.wpp.ui.UILayoutManager
 *
 * @description   
 * @author        automattic
 * @created:      Jul 19, 2008
 * @modified:     Sep 14, 2008  
 *   
 */

package com.wordpress.wpp.ui
{
  import com.wordpress.wpp.gui.*;
  
  import flash.display.DisplayObject;
  import flash.utils.Dictionary;
  
  public class UILayoutManager
  {
    /**
     * Layout dictionary
     */    
    public var layoutDict:Dictionary;
    
    /**
     * 
     * @param target Instance of the target DisplayObject 
     * @param obj Information about the layout
     * 
     * Note: The target must be added on the display list already!
     */      
    public static function addTarget(target:DisplayObject, obj:Object ):void
    {
      var doc:WPPDocument = target.root as WPPDocument;
      doc.layoutManager.layoutDict[target] = new UILayoutRender(target, obj);
    }
    
    /**
     * 
     * @param target Instance of the target DisplayObject 
     * 
     */    
    public static function removeTarget(target:DisplayObject):void
    {
      var doc:WPPDocument = target.root as WPPDocument;
      try{(doc.layoutManager.layoutDict[target] as UILayoutRender).removeRender();}catch(error:Error){}
      delete doc.layoutManager.layoutDict[target];
    }
    
    public function UILayoutManager()
    {
      layoutDict = new Dictionary();
    }
    
  }
}