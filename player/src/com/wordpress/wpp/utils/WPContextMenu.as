/**
 * @package     com.wordpress.wpp.utils
 * @class       com.wordpress.wpp.utils.WPContextMenu
 *
 * @description   
 * @author      automattic
 * @created:     Aug 04, 2008
 * @modified:     Sep 09, 2008  
 *   
 */
 
package com.wordpress.wpp.utils
{

  import flash.display.InteractiveObject;
  import flash.display.StageAlign;
  import flash.display.StageScaleMode;
  import flash.events.ContextMenuEvent;
  import flash.net.URLRequest;
  import flash.net.navigateToURL;
  import flash.ui.ContextMenu;
  import flash.ui.ContextMenuBuiltInItems;
  import flash.ui.ContextMenuItem;
  
  
  public class WPContextMenu
  {
    private var myContextMenu:ContextMenu;
    private var _io:InteractiveObject;
    
    function WPContextMenu(InterObj:InteractiveObject)
    {
      _io = InterObj;
      _io.stage.align = StageAlign.TOP_LEFT;
      _io.stage.scaleMode = StageScaleMode.NO_SCALE;
      myContextMenu = new ContextMenu();

      // Adjust the builtinitems
      myContextMenu.hideBuiltInItems();
      var defaultItems:ContextMenuBuiltInItems = myContextMenu.builtInItems;

      // Add your own stuff
      var item:ContextMenuItem = new ContextMenuItem("copyright");
            myContextMenu.customItems.push(item);
            item.addEventListener(ContextMenuEvent.MENU_ITEM_SELECT, menuItemSelectHandler);
            defaultItems.print = true;
      _io.contextMenu = myContextMenu; 
    }
    
    private function menuItemSelectHandler(event:ContextMenuEvent):void
    {
      var request_url:URLRequest = new URLRequest("http://www.wordpress.com");
      navigateToURL(request_url, "_blank");
    }
  }
}