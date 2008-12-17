/**
 * @package     com.wordpress.wpp.utils
 * @class       com.wordpress.wpp.utils.TimeText
 *
 * @description   
 * @author      automattic
 * @created:     Jul 28, 2008
 * @modified:     Sep 29, 2008  
 *   
 */
 
package com.wordpress.wpp.utils
{
  public class TimeText
  {  
    /**
     *
     * Convert seconds into hour:minute:second 
     * ex: 3700 => "1:01:40"  
     * ex: 300 => "5:00"
     *
     * @param t
     * @return 
     * 
     */    
    public static function getTimeText(t:Number):String
    {  
      var hour:Number   = Math.floor( t/3600 );
      var minute:Number = Math.floor( t/60 ) - 60*hour; 
      var second:Number = Math.floor( t ) - 3600*hour - 60*minute; 
      
      var hour_str:String = '';
      if ( hour > 0 )
        hour_str = hour.toString() + ':';
      
      var minute_str:String = minute>9? (minute.toString()) : (''+minute); 
      var second_str:String = second>9? (second.toString()) : ('0'+second); 
      
      return ( hour_str + minute_str + ':' + second_str ); 
    }
  }
}
