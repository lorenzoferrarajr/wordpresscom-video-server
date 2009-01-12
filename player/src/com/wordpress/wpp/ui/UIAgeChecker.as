package com.wordpress.wpp.ui
{
  import com.wordpress.wpp.config.RatingDictionary;
  import com.wordpress.wpp.events.ObjectEvent;
  import com.wordpress.wpp.events.WPPEvents;
  import com.wordpress.wpp.gui.GUIAgeChecker;
  
  import flash.display.Sprite;
  import flash.events.EventDispatcher;
  import flash.text.TextField;
  import flash.text.TextFormat;
  import flash.text.TextFormatAlign;
  
  public class UIAgeChecker extends EventDispatcher
  {
    private var overlapSprite:Sprite;
    private var ageChecker:GUIAgeChecker;
    private var doc:WPPDocument;
    
    public function UIAgeChecker(_doc:WPPDocument)
    {
      doc = _doc;
      init();
    }
    
    private function init():void
    {
      // Build an overlap layer
      overlapSprite = new Sprite();
      overlapSprite.graphics.beginFill(0,.94);
      overlapSprite.graphics.drawRect(0,0,1,1);
      overlapSprite.graphics.endFill();
      overlapSprite.mouseEnabled = true;
      doc.addChild(overlapSprite);
      UILayoutManager.addTarget(overlapSprite,{"width":1,"height":1,"marginLeft":0, "marginRight":0});
      
      // Build the main check box
      ageChecker = new GUIAgeChecker();
      doc.addChild(ageChecker);
      UILayoutManager.addTarget(ageChecker, {"top":35, "centerx":0});
      ageChecker.addEventListener(WPPEvents.SPLASH_AGE_VERIFICATION, confirmBirthdateHandler);
      
    }
    
    private function confirmBirthdateHandler(event:ObjectEvent):void
    {
      var age = event.data;
      if (RatingDictionary.RATING_DICT[doc.info.rating]!=undefined)
      {
        var minAge:Number = RatingDictionary.RATING_DICT[doc.info.rating];
        if (age >= minAge)
        {
          doc.removeChild(overlapSprite);
          doc.removeChild(ageChecker);
        }
        else
        {
          notAllowedToWatch();
        }
      }
      else
      {
        notAllowedToWatch();
      }
    }
    
    private function notAllowedToWatch():void
    {
      doc.removeChild(ageChecker);
      var notAllowedHint:TextField = new TextField();
      notAllowedHint.text = "Sorry, you are not allowed to watch this video!";
      notAllowedHint.width = 450;
      notAllowedHint.selectable = false;
      
      var format:TextFormat = new TextFormat();
      format.font = "Verdana";
      format.color = 0xffffff;
      format.align = TextFormatAlign.CENTER;
      format.size = 12;
      notAllowedHint.setTextFormat(format);
      doc.addChild(notAllowedHint);
      UILayoutManager.addTarget(notAllowedHint, {"top":35, "centerx":0});
    }

  }
}