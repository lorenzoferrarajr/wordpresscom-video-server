/**
 * @package     com.wordpress.wpp.gui
 * @class       com.wordpress.wpp.gui.GUIAgeChecker
 *
 * @description   (linkage via FLA) Age verification components
 * @author        automattic
 * @created:      Jan 10, 2009
 * @modified:     Feb 16, 2009
 * @change log    Feb 16,
 *                Fixed some bugs that may mess up the UI if a user did not
 *                close a combobox after providing his birth date.
 */
package com.wordpress.wpp.gui
{
  import com.wordpress.wpp.events.ObjectEvent;
  import com.wordpress.wpp.events.WPPEvents;
  import com.wordpress.wpp.ui.UIAgeChecker;
  
  import fl.controls.Button;
  import fl.controls.ComboBox;
  import fl.data.DataProvider;
  
  import flash.display.Sprite;
  import flash.events.Event;
  import flash.events.MouseEvent;
  
  // This class is binded with a symbol from the FLA source
  dynamic public class GUIAgeChecker extends Sprite
  {
    // Comboboxes for selecting birthday
    private var yearComboBox:ComboBox;
    private var monthComboBox:ComboBox;
    private var dayComboBox:ComboBox;
    private var yearComboBoxInitialized:Boolean = false;
    private var monthComboBoxInitialized:Boolean = false;
    private var dayComboBoxInitialized:Boolean = false;
    
    // Submit button
    private var submitButton:Button;
    
    public function GUIAgeChecker() {
      // initialize the components from stage
      super();
      this.addEventListener(Event.ADDED_TO_STAGE, initHandler);
    }
    
    public function unregister():void {
      // Close the comboboxes
      yearComboBox.close();
      monthComboBox.close();
      dayComboBox.close();
      submitButton.removeEventListener(MouseEvent.CLICK, checkBirthHandler);
    }
    
    private function initHandler(event:Event):void
    {
      this.removeEventListener(Event.ADDED_TO_STAGE, initHandler);
      yearComboBox = year_box;
      monthComboBox = month_box;
      dayComboBox = day_box;
      submitButton = submit_button;
      
      // Disable it first
      submitButton.enabled = false;
      
      yearComboBox.addItem({"label":"year"});
      yearComboBox.addItem({"label":""});
      yearComboBox.addItem({"label":""});
      yearComboBox.addItem({"label":""});
      yearComboBox.addItem({"label":""});
      yearComboBox.addItem({"label":""});
      monthComboBox.addItem({"label":"month"});
      monthComboBox.addItem({"label":""});
      monthComboBox.addItem({"label":""});
      monthComboBox.addItem({"label":""});
      monthComboBox.addItem({"label":""});
      monthComboBox.addItem({"label":""});
      dayComboBox.addItem({"label":"day"});
      dayComboBox.addItem({"label":""});
      dayComboBox.addItem({"label":""});
      dayComboBox.addItem({"label":""});
      dayComboBox.addItem({"label":""});
      dayComboBox.addItem({"label":""});
      
      var resizeHandler:Function = function(event:Event)
      {
        if (stage.stageHeight < 300)
        {
          yearComboBox.rowCount = 3;
          monthComboBox.rowCount = 3;
          dayComboBox.rowCount = 3;
        }
        else
        {
          yearComboBox.rowCount = 5;
          monthComboBox.rowCount = 5;
          dayComboBox.rowCount = 5;
        }
        yearComboBox.close();
        monthComboBox.close();
        dayComboBox.close();
      }
      this.stage.addEventListener(Event.RESIZE, resizeHandler);
      
      // Year combobox
      var dp_year:DataProvider = new DataProvider();
      for (var year:Number = 2009;year>1900;year--)
      {
        dp_year.addItem({label:year.toString(),value:year});
      }
      var updateYearProvider:Function = function(event:MouseEvent)
      {
        yearComboBox.dataProvider = dp_year;
        yearComboBox.open();
        yearComboBox.removeEventListener(MouseEvent.CLICK, updateYearProvider);
        yearComboBoxInitialized = true;
        smartSubmitManager();
      }
      yearComboBox.addEventListener(MouseEvent.CLICK,updateYearProvider);
      
      // Month combobox 
      var monthPool:Array = [
         "Jan",
         "Feb",
         "Mar",
         "Apr",
         "May",
         "Jun",
         "Jul",
         "Aug",
         "Sep",
         "Oct",
         "Nov",
         "Dec"
      ];
      var dp_month:DataProvider = new DataProvider();
      for (var month:Number = 0;month<12;month++)
      {
        dp_month.addItem({label:monthPool[month].toString(),value:month});
      }
      var updateMonthProvider:Function = function(event:MouseEvent):void
      {
        monthComboBox.dataProvider = dp_month;
        monthComboBox.open();
        monthComboBox.removeEventListener(MouseEvent.CLICK, updateMonthProvider);
        monthComboBoxInitialized = true;
        smartSubmitManager();
      }
      monthComboBox.addEventListener(MouseEvent.CLICK,updateMonthProvider);
      
      // Day combobox
      var dp_day:DataProvider = new DataProvider();
      for (var day:Number = 1;day<32;day++)
      {
        dp_day.addItem({label:day.toString(),value:day});
      }
      var updateDayProvider:Function = function(event:MouseEvent):void
      {
        dayComboBox.dataProvider = dp_day;
        dayComboBox.open();
        dayComboBox.removeEventListener(MouseEvent.CLICK, updateDayProvider);
        dayComboBoxInitialized = true;
        smartSubmitManager();
      }
      dayComboBox.addEventListener(MouseEvent.CLICK,updateDayProvider);
      
    }
    
    private function smartSubmitManager():void
    {
      if (yearComboBoxInitialized && 
          monthComboBoxInitialized && 
          dayComboBoxInitialized)
      { 
        submitButton.enabled = true;
        submitButton.addEventListener(MouseEvent.CLICK,checkBirthHandler);
      }
    }
    
    private function checkBirthHandler(event:MouseEvent):void
    {
      var userSpecifiedAge:Object = {
          year : yearComboBox.selectedItem.value,
          month : monthComboBox.selectedItem.value,
          day : dayComboBox.selectedItem.value};
      var ageEvent:ObjectEvent = new ObjectEvent(WPPEvents.SPLASH_AGE_VERIFICATION, userSpecifiedAge);
      dispatchEvent(ageEvent);
    }
    
    private function getAge(birthDate:Date):Number
    {
      return Math.floor(((new Date()).getTime()-birthDate.getTime())/31536000000);
    }
    
  }
}