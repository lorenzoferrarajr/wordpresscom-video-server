package com.wordpress.wpp.events
{
	public class WPPEvents
	{
		// Dispatched when a video XML file is completely loaded
		public static const VIDEO_XML_LOADED:String = "videoXMLLoaded";
		
		// These events may be used in the splash screen
		
		// Dispatched when the slash screen is generated
		public static const SPLASH_SCREEN_INIT:String = "splashScreenInit";
		
		// Dispatched when the user confirms his birth date
		public static const SPLASH_AGE_VERIFICATION:String = "splashAgeVerification"
		
		// Dispatched when the user start playing the video in a splash screen
		public static const SPLASH_VIDEO_PLAY:String = "splashVideoPlay";
		
		// Dispatched when the user switches to a higher definition resolution in splash screen
		public static const SPLASH_TURN_ON_HD:String = "splashTurnOnHD";
		
		// Dispatched when the user switches to a normal definition resoltuion in splash screen
		public static const SPLASH_TURN_OFF_HD:String = "splashTurnOffHD";
		
		
		
		// These events may be used in VCore+VControl for the main logic
		
		// Dispatched when the video is stopped
		public static const VCORE_STOP:String = "vcoreStop";
		
		// Dispatched when the video is (resumed) playing
		public static const VCORE_PLAY:String = "vcorePlay";
		
		// Dispatched when the video is paused 
		public static const VCORE_PAUSED:String = "vcorePaused";
		
		// Dispatched when the video is not found
		public static const VCORE_STREAM_NOT_FOUND:String = "vcoreStreamNotFound";
		
		// Dispatched when the user switches to a higher definition resoltuion
		public static const TURN_ON_HD:String = "turnOnHD";
		
		// Dispatched when the user switches to a normal definition resoltuion
		public static const TURN_OFF_HD:String = "turnOfHD";
		
		// Dispatched when the user seeks through the video
		public static const SLIDER_SEEKING:String = "sliderSeeking";
	}
}