/**
 * @package 		com.wordpress.wpp.events
 * @class 			com.wordpress.wpp.events.SeekEvent
 *
 * @description 	
 * @author			automattic
 * @created: 		Jul 30, 2008
 * @modified: 		Sep 09, 2008  
 *   
 */
 
package com.wordpress.wpp.events
{
	import flash.events.Event;
	public class ObjectEvent extends Event
	{
		public var data:*;
		public function ObjectEvent(type:String, data:*)
		{
			this.data= data;
			super(type);
		}
	}
}