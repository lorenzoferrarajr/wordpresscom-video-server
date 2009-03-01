/**
 * @package     com.wordpress.wpp.utils
 * @class       com.wordpress.wpp.utils.EmbedManager
 *
 * @description   Embed UI
 * @author      automattic
 * @created:     Jul 19, 2008
 * @modified:     Oct 29, 2008  
 *   
 */
 
 
 package com.wordpress.wpp.utils
{
  import com.wordpress.wpp.gui.GUICopy;
  import com.wordpress.wpp.gui.GUIEmbedMain;
  import com.wordpress.wpp.gui.GUIEmbedToggleButton;
  import com.wordpress.wpp.ui.UILayoutManager;
  
  import flash.display.MovieClip;
  import flash.display.SimpleButton;
  import flash.events.EventDispatcher;
  import flash.events.MouseEvent;
  import flash.system.System;
  import flash.text.TextField;

  
  public class EmbedManager extends EventDispatcher
  {
    private var e_html:String = "";
    private var e_blog:String = "";
    private var e_wp:String = "";
    private var e_wp_large:String = "";
    
    public var toggleButton:GUIEmbedToggleButton;
    public var embedMain:GUIEmbedMain;
    private var doc:WPPDocument;
    
    public function EmbedManager(documentInstance:WPPDocument)
    {
      doc = documentInstance;
    }
    
    private function copy_wpHandler(event:MouseEvent):void
    {
      if (event.target is SimpleButton)
      {
        embedMain.copy_wp.gotoAndStop(1);
        embedMain.copy_html.gotoAndStop(1);
        embedMain.copy_large.gotoAndStop(1);
        ((event.target as SimpleButton).parent as MovieClip).gotoAndStop(2); 
        System.setClipboard(e_wp);
      }
    }
    
    private function copy_wpLargeHandler(event:MouseEvent):void
    {
      if (event.target is SimpleButton)
      {
        embedMain.copy_wp.gotoAndStop(1);
        embedMain.copy_html.gotoAndStop(1);
        embedMain.copy_large.gotoAndStop(1);
        ((event.target as SimpleButton).parent as MovieClip).gotoAndStop(2); 
        System.setClipboard(e_wp_large);
      }
    }
    
    
    private function copy_htmlHandler(event:MouseEvent):void
    {
      if (event.target is SimpleButton)
      {
        embedMain.copy_wp.gotoAndStop(1);
        embedMain.copy_html.gotoAndStop(1);
        embedMain.copy_large.gotoAndStop(1);
        ((event.target as SimpleButton).parent as MovieClip).gotoAndStop(2);
        System.setClipboard(e_html);
      }
    }
    
    public function initEmbedding():void
    {
      resetEmbedding();
      
      // Check whether we need to show the embedded toggle button 
      if (!doc.info.embededCode)
        return;
      
      toggleButton = new GUIEmbedToggleButton();
      doc.addChild(toggleButton);
      UILayoutManager.addTarget(toggleButton,{
        "top":0,
        "left":0
        });
      toggleButton.addEventListener(MouseEvent.CLICK, toggleEmbedHandler);
      
      
      embedMain = new GUIEmbedMain();
      doc.addChild(embedMain);
      
      e_html = doc.info.embededCode;
      e_wp = doc.info.embededWp;
        
      embedMain.copy_wp.addEventListener(MouseEvent.CLICK, copy_wpHandler);
      embedMain.copy_html.addEventListener(MouseEvent.CLICK, copy_htmlHandler);
      
      embedMain.embed_html.text = e_html;
      embedMain.embed_wp.text = e_wp;
      
      if (doc.info.embededLargeCode)
      {
        embedMain.gotoAndStop(2);
        (embedMain.copy_large as GUICopy).addEventListener(MouseEvent.CLICK, copy_wpLargeHandler);
        (embedMain.embed_large as TextField).text = doc.info.embededLargeCode;
        e_wp_large = doc.info.embededLargeCode;
        
        (embedMain.copy_large as GUICopy).visible = true;
        (embedMain.embed_large as TextField).visible = true;
      }
      else
      {
        embedMain.gotoAndStop(1);
        (embedMain.copy_large as GUICopy).visible = false;
        (embedMain.embed_large as TextField).visible = false;
      }
      
      UILayoutManager.addTarget(embedMain,{
        "top":25,
        "left":0
        });
      embedMain.visible = false;
    }

    private function toggleEmbedHandler(event:MouseEvent):void
    {
      toggleEmbed();
      if (embedMain.visible)
        doc.stage.addEventListener(MouseEvent.MOUSE_DOWN, stageToggleHandler);
      
    }
    
    private function toggleEmbed(b:Boolean = false)
    {
      if(!b)
      {
        embedMain.visible = !embedMain.visible;
        embedMain.copy_wp.gotoAndStop(1);
        embedMain.copy_html.gotoAndStop(1);
      }
      else
      {
        embedMain.visible = false;
        embedMain.copy_wp.gotoAndStop(1);
        embedMain.copy_html.gotoAndStop(1);
      }
    }
    
    private function stageToggleHandler(event:MouseEvent)
    {
      if(!embedMain.getBounds(doc).contains(event.stageX, event.stageY) && !toggleButton.getBounds(doc).contains(event.stageX, event.stageY))
      {
        toggleEmbed(true);
        doc.stage.removeEventListener(MouseEvent.MOUSE_DOWN, stageToggleHandler);
      }
    }

    public function resetEmbedding():void
    {
      try{
        toggleButton.removeEventListener(MouseEvent.CLICK, toggleEmbedHandler);
        if (doc.contains(toggleButton))
          doc.removeChild(toggleButton);
        if (doc.contains(embedMain))
          doc.removeChild(embedMain);
          
        embedMain.copy_wp.removeEventListener(MouseEvent.CLICK, copy_wpHandler);
        embedMain.copy_html.removeEventListener(MouseEvent.CLICK, copy_htmlHandler);

      }catch(e){}
    }
  }
}