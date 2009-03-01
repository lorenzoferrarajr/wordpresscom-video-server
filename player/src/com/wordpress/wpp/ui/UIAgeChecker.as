/**
 * @package     com.wordpress.wpp.ui
 * @class       com.wordpress.wpp.ui.UIAgeChecker
 *
 * @description   Age checker manager
 * @author        automattic
 * @created:      Jan 10, 2009
 * @modified:     Feb 16, 2009  
 *   
 */
package com.wordpress.wpp.ui
{
  import com.wordpress.wpp.config.RatingDictionary;
  import com.wordpress.wpp.events.ObjectEvent;
  import com.wordpress.wpp.events.WPPEvents;
  import com.wordpress.wpp.gui.GUIAgeChecker;
  
  import fl.controls.Button;
  
  import flash.display.Sprite;
  import flash.events.EventDispatcher;
  import flash.events.MouseEvent;
  import flash.events.NetStatusEvent;
  import flash.events.TimerEvent;
  import flash.external.ExternalInterface;
  import flash.net.SharedObject;
  import flash.text.TextField;
  import flash.text.TextFormat;
  import flash.text.TextFormatAlign;
  import flash.utils.Timer;
  
  public class UIAgeChecker extends EventDispatcher
  {
    public static const CHECK_AGE_SHAREDOBJECT_NAME = "checkage";
    // Message strings
    private static const DISALLOW_MESSAGE:String = "Sorry, you are not allowed to watch this video!";
    
    // UI elements
    private var doc:WPPDocument;
    private var overlapSprite:Sprite;
    private var ageChecker:GUIAgeChecker;
    private var notAllowedHint:TextField = new TextField();
    
    // Realtime check daemon timer, used for checking a user's input in 
    // one player from all other players embedded in a single web page.
    private var checkDaemonTimer:Timer = new Timer(1000);
    
    // The required age for watching this video
    private var minAge:Number;
    
    /**
     * Age checker manager
     * 
     * @constructor 
     * @param _doc WPPDocument Application class reference
     * @param _minAge Number   The required age for watching this video
     * 
     */    
    public function UIAgeChecker(_doc:WPPDocument, _minAge:Number)
    {
      doc = _doc;
      minAge = _minAge;
      init();
    }
    
    /**
     * Initialization function 
     * 
     */    
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
      UILayoutManager.addTarget(ageChecker, {"centerx":0, "centery":0});
      ageChecker.addEventListener(WPPEvents.SPLASH_AGE_VERIFICATION, confirmBirthdateHandler);
      
      // Start realtime checker
      realTimeCheck();
    }
    
    
    /**
     * Invoke the check local age in every interval  
     * @param event TimerEvent
     * 
     */
    private function checkTimerHandler(event:TimerEvent):void {
      checkLocalAge()
    }
    private function realTimeCheck():void {
      checkDaemonTimer.addEventListener(TimerEvent.TIMER, checkTimerHandler);
      checkDaemonTimer.start();
      checkLocalAge();
    }
    
    private function checkLocalAge():void {
      var currentUserAgeSharedObject:SharedObject = SharedObject.getLocal(UIAgeChecker.CHECK_AGE_SHAREDOBJECT_NAME);
      var localMonth:Number = Number(currentUserAgeSharedObject.data.localMonth);
      var localDay:Number = Number(currentUserAgeSharedObject.data.localDay);
      var localYear:Number = Number(currentUserAgeSharedObject.data.localYear);
      try{ExternalInterface.call("console.info", localYear.toString());}catch(e){}
      if (isNaN(localMonth)) return;
      if (isNaN(localDay)) return;
      if (isNaN(localYear)) return;
      confirmBirthDate(localMonth, localDay, localYear);
    }
    
    private function confirmBirthdateHandler(event:ObjectEvent):void
    {
      // Remove the confirm listener to release the memory
      
      var userSelectedAgeDate:Object = event.data;
      
      // update sharedobject (cookie)
      var currentUserAgeSharedObject:SharedObject = SharedObject.getLocal(UIAgeChecker.CHECK_AGE_SHAREDOBJECT_NAME);
      var localYear:Number = userSelectedAgeDate.year;
      var localMonth:Number = userSelectedAgeDate.month;
      var localDay:Number = userSelectedAgeDate.day;
      currentUserAgeSharedObject.data.localMonth = localMonth.toString();
      currentUserAgeSharedObject.data.localDay = localDay.toString();
      currentUserAgeSharedObject.data.localYear = localYear.toString();
      currentUserAgeSharedObject.data.test2 = "haha";
      
      
      try{ExternalInterface.call("console.info","* "+currentUserAgeSharedObject.data.localYear);}catch(e){}
      var a111:Function = function(event:NetStatusEvent):void {
        try{ExternalInterface.call("console.info","* "+event.info.code);}catch(error){}
      }
      currentUserAgeSharedObject.addEventListener(NetStatusEvent.NET_STATUS, a111);
      var flushResult:String = currentUserAgeSharedObject.flush();
      confirmBirthDate(userSelectedAgeDate.month, 
          userSelectedAgeDate.day,
          userSelectedAgeDate.year);
    }
    
    // Confirm the user's birth date, to check whether he/she is allowed to watch this video 
    private function confirmBirthDate(month:Number, day:Number, year:Number):void {
      // Check whether we need to check the video
      if (RatingDictionary.RATING_DICT[doc.info.rating] != undefined) {
        var minAge:Number = RatingDictionary.RATING_DICT[doc.info.rating];
        if (isValidateAge(minAge, month, day, year)) {
          // valid!
          trace("passed");
          unregisterAgeChecker();
          doc.removeChild(overlapSprite);
        } else {
          trace("not allowd");
          // invalid
          //unregisterAgeChecker();
          notAllowedToWatch();
        }
      } else {
        trace("3rd");
        // invalid
        //unregisterAgeChecker();
        notAllowedToWatch();
      }
    }
    
    
    /**
     * When the user is not allowed to watch this video, 
     * show some information for him/her
     */
    private function notAllowedToWatch():void
    {
      checkDaemonTimer.stop();
      notAllowedHint.text = DISALLOW_MESSAGE;
      notAllowedHint.width = 450;
      notAllowedHint.selectable = false;
      var format:TextFormat = new TextFormat();
      format.font = "Verdana";
      format.color = 0xffffff;
      format.align = TextFormatAlign.CENTER;
      format.size = 12;
      notAllowedHint.setTextFormat(format);
      doc.addChild(notAllowedHint);
      UILayoutManager.addTarget(notAllowedHint, {"centery":-35, "centerx":0});
      
      ageChecker.visible = false;
      notAllowedHint.visible = true;
    }
    
    /**
     * Unregister age checker components 
     * 
     */    
    private function unregisterAgeChecker():void {
      // Release the age checker
      ageChecker.removeEventListener(WPPEvents.SPLASH_AGE_VERIFICATION, confirmBirthdateHandler);
      ageChecker.unregister();
      doc.removeChild(ageChecker);
      
      // Stop the check daemon timer, which is set up to check user verification action
      // from other players in a same web page. 
      checkDaemonTimer.stop();
      checkDaemonTimer.removeEventListener(TimerEvent.TIMER, checkTimerHandler);
    }
    
    /**
     * Check 
     * @param minAge - required age
     * @param month - user specified month
     * @param day - user specified day
     * @param year - user specified year
     * @return (Boolean) - Whether the specified age is allowed to watch this video
     * 
     */    
    public static function isValidateAge(minAge:Number, month:Number, day:Number, year:Number):Boolean
    {
      // Get today's information
      var todayDate:Date = new Date();
      var currentMonth:Number = todayDate.getMonth();
      var currentDay:Number = todayDate.getDate();
      var currentYear:Number = todayDate.getFullYear();
      var diffYear:Number = currentYear - year;
      if (diffYear == minAge) 
      {
        var diffMonth:Number = currentMonth - month;
        if (diffMonth == 0) 
        {// Check day when month is equalled
          var diffDay:Number = currentDay - day;
          if (diffDay >= 0) 
          { // Passed the check
            return true;
          }
          else 
          { //too young
            return false;
          }
        }
        else if(diffMonth < 0) 
        { // too young
          return false;
        }
        else 
        { //AGE PASS
          return true;
        }
        
      } 
      else if (diffYear < minAge) 
      { // too young
        return false;
      }
      else 
      { // Passed the check
        return true;
      }
    }
  }
}