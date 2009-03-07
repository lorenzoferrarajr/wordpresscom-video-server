 /**
 * @package     {band to a FLA source}
 * @class       WPPDocument
 *
 * @description   Document Class for WordPress Video Player in Flash CS3
 * @author      automattic
 * @created:     July 19, 2008
 * @modified:     Feb 16, 2009  
 * @change list   Feb 16,
 *                Rearranged the logic of age verfication. Now the 
 *                player would never ask for the user to input his/her age if he
 *                had once provided it before.
 */

package
{  
  import com.wordpress.wpp.config.RatingDictionary;
  import com.wordpress.wpp.config.VideoFormat;
  import com.wordpress.wpp.config.VideoInfo;
  import com.wordpress.wpp.config.WPPConfiguration;
  import com.wordpress.wpp.core.VControl;
  import com.wordpress.wpp.core.VCore;
  import com.wordpress.wpp.core.VCoreDynamicSeeking;
  import com.wordpress.wpp.events.WPPEvents;
  import com.wordpress.wpp.gui.*;
  import com.wordpress.wpp.ui.UIAgeChecker;
  import com.wordpress.wpp.ui.UIEndScreen;
  import com.wordpress.wpp.ui.UILayoutManager;
  import com.wordpress.wpp.ui.UISplashControl;
  import com.wordpress.wpp.ui.UISplashScreen;
  import com.wordpress.wpp.utils.EmbedManager;
  import com.wordpress.wpp.utils.Fader;
  import com.wordpress.wpp.utils.HDManager;
  import com.wordpress.wpp.utils.StatsReport;
  import com.wordpress.wpp.utils.WPContextMenu;
  
  import flash.display.Sprite;
  import flash.events.Event;
  import flash.events.MouseEvent;
  import flash.ui.Mouse;
  
  // The main class of this Flash Application
  dynamic public class WPPDocument extends Sprite
  {
    /**
     * Stats reporter instance 
     * 
     */
    public var statsReporter:StatsReport;
    
    /**
     * max_report_time and total_report_time protects
     * the stats report from sending endless requests when an unexpected error happened
     *   
     */    
    public var max_report_time:Number = 200;
    
    /**
     * The current requsts sent 
     * 
     */    
    public var total_report_time:Number = 0;

    /**
     * The information for the current video, almost everything 
     * 
     */    
    public var info:VideoInfo;
    
    
    /**
     * Whether the higher resolution is provided 
     * 
     */    
    public var isHD:Boolean = false;
    
    /**
     * Layout Manger Dictionrary Holder 
     * 
     */    
    public var layoutManager:UILayoutManager;
    
    /**
     * HD Switcher manager 
     * 
     */    
    private var hdManager:HDManager;
    
    /**
     * The default guid of a video if no guid is given externally
     * 
     */    
    private var guid:String = WPPConfiguration.DEFAULT_GUID;

    /**
     * Embed pop-up manager instance 
     * 
     */
    private var embedManager:EmbedManager;

    /**
     * GUI Controller instance (Passed in from the stage in FLA)
     * 
     */
    private var guiCtr:GUIControl;

    /**
     * Canvas width
     * 
     */    
    private var canvasWidth:Number;
    
    /**
     * Canvas height
     * 
     */    
    private var canvasHeight:Number;
    
    /**
     * splash screen instance
     * 
     */    
    private var splashScreen:UISplashScreen;
    
    /**
     * splash controller instance
     * 
     */    
    private var splashControl:UISplashControl;
    
    /**
     * menu screen instance
     * 
     */    
    public var endScreen:UIEndScreen;
    
    /**
     * Main video instance, can be VCore or VCoreDynamicSeeking
     * 
     */    
    public var mainVideo:VCore;
    
    /**
     * main video controller instance
     * 
     */    
    private var getControl:VControl;
    
    /**
     * current video format
     * 
     */    
    private var vo:VideoFormat = new VideoFormat();
    
    /**
     * Fade the controller 
     * 
     */    
    private var controllerFader:Fader;
    
    /**
     * Fade the embed button 
     * 
     */    
    private var embedmainFader:Fader;
    
    /**
     * Fade the embed popup 
     */    
    private var embedtoggleFader:Fader;

    /**
     * Document class constructor 
     * 
     */    
    function WPPDocument()
    {
      layoutManager = new UILayoutManager();
      super();
      
      // Load the settings from HTML (where the flashplayer embeded)
      loadExternalSettings();
      
      // Init the context menu
      var wpContextMenu:WPContextMenu = new WPContextMenu(this);
      
      // Initialize the Controller.
      guiCtr = controller_set; // the 'controller_set' is from the stage.
      
      // hide it at first
      guiCtr.visible = false;
      
      // Show the splash screen.
      splashScreen = new UISplashScreen(guid, this);
      
      // Fires when the information is ready
      splashScreen.addEventListener(WPPEvents.SPLASH_SCREEN_INIT, initMainVideoHandler);
      
      // Fires when the user asks to play
      splashScreen.addEventListener(WPPEvents.SPLASH_VIDEO_PLAY, splashPlayHandler);
    }
    
    /**
     * Get the information about this video from remote server when it is loaded. 
     * @param event
     * 
     */    
    private function initMainVideoHandler(event:Event):void
    {
      // to make the Thumb shown
      addChild(guiCtr);
      
      // Get the information.
      info = event.target.videoinfo;
      
      // Show the controller.
      guiCtr.visible = true;
      guiCtr.initFrameHandler();
      
      if (info.embededCode) {
        embedManager = new EmbedManager(this);
        embedManager.initEmbedding();
      } else {
        guiCtr.resetInfoButtonToLeftTop();
      }
      
      
      // Setup the controller
      splashControl = new UISplashControl(guiCtr);
      
      // Setup up the HD Manager
      hdManager = new HDManager(this);
      hdManager.renderHDButtons(splashControl, guiCtr);
      turnOffHD();

      // Setup the stats reporter
      statsReporter = new StatsReport(info.status_url, info.status_interval, this);
      statsReporter.videoImpression();
      
      if (info.mainDuration == 0)
      {
        max_report_time = 120;
      }
      else
      {
        max_report_time = info.mainDuration + 10;
      }
      
      var verifyUserAge:Boolean = false;
      if (info.rating != "" && info.rating)
      {
        verifyUserAge = true;
      }
      
      // If the autoplay flag is true, and there's no need to confirm the user's
      // birthday, then just play the video at once
      //
      if (WPPConfiguration.AUTOPLAY_WHEN_LOADED && !verifyUserAge)
      {
        playMainVideo(info);
      }
      else
      {
        // Show the splash screen and wait the action from the user
        // No matter whether it is set as "automatically playing", if we need
        // to verify the user's age, we need to show the splash screen. 
        splashControl.addEventListener(WPPEvents.SPLASH_VIDEO_PLAY, splashPlayHandler);
        splashControl.addEventListener(WPPEvents.SPLASH_TURN_ON_HD, splashHDOnHandler);
        splashControl.addEventListener(WPPEvents.SPLASH_TURN_OFF_HD, splashHDOffHandler);
        
        // Whether we need to show the verification box 
        if (verifyUserAge) {
          var minAge:Number = RatingDictionary.RATING_DICT[info.rating];
          var ageChecker:UIAgeChecker = new UIAgeChecker(this, minAge);
        }
      }
    }
    
    /**
     * Turn on HD mode
     * @param event handler for splash controller
     * 
     */    
    private function splashHDOnHandler(event:Event):void
    {
      turnOnHD();
    }
    
    /**
     * Turn off hd mode handler for splash controller
     * @param event
     * 
     */    
    private function splashHDOffHandler(event:Event):void
    {
      turnOffHD();
    }
    
    /**
     * Turn on hd(higher-definition) mode
     * 
     */    
    public function turnOnHD():void
    {
      isHD = true;
      vo = hdManager.getHDVideo();
    }
    
    /**
     * Turn off hd mode
     * 
     */  
    public function turnOffHD():void
    {
      isHD = false;
      vo = hdManager.getSTDVideo();
    }
    
    
    

    /**
     * play main video handler for splash controller 
     * @param event Event
     * 
     */
    private function splashPlayHandler(event:Event):void
    {
      playMainVideo(info);
    }
    
    /**
     * Play main video
     * @param flvInfo The main video information holder
     * 
     */
    private function playMainVideo(flvInfo:VideoInfo = null):void
    {
      
      // report a video view information to the remote server
      statsReporter.videoView();
      
      // VCore instance handles almost all the core playing mechanism
      if (WPPConfiguration.IS_DYNAMIC_SEEKING)
      {
        mainVideo = new VCoreDynamicSeeking(this, vo.movie_file, vo.width, vo.height);
      }
      else
      {
        mainVideo = new VCore(this, vo.movie_file, vo.width, vo.height);
      }

      // Attach a controller component to the VCore instance
      // the splashControl.videoslider and splashControl.volumeslider is passed into the VControl 
      // constructor for a reference
      getControl = new VControl(guiCtr, mainVideo, splashControl.videoslider, splashControl.volumeslider);
      getControl.addEventListener(WPPEvents.TURN_ON_HD, playHDHandler);
      getControl.addEventListener(WPPEvents.TURN_OFF_HD, playSTDHandler);

      // Delete the splash screen after it delivers the instances of sliders to our real controller.
      killSplashScreen();
  
      // Fade away the controller set.
      stage.addEventListener(Event.MOUSE_LEAVE, mouseLeaveHandler);

      // Here we got the Video on our screen :)
      addChild(mainVideo);
      
      // make the controller on the top of the video
      addChild(guiCtr);
      
      // If we need to show the embed code toggle
      if (info.embededCode) {
        addChild(embedManager.embedMain);
        addChild(embedManager.toggleButton);
      }
      
      // When the video stops, let's show the replay button (or playlist)
      mainVideo.addEventListener(WPPEvents.VCORE_STOP, videoStopHandler, false, 0 ,true);
    }
    
    /**
     * Play HD at once in play mode or menu screen
     * @param event
     * 
     */
    private function playHDHandler(event:Event):void
    {
      isHD = true;
      
      // Pick up the HD video and reset the info stats target
      vo = hdManager.getHDVideo();
      
      // Setup the stats reporter
      if (statsReporter)
        statsReporter.killAll();
      statsReporter = new StatsReport(info.status_url, info.status_interval, this);
      statsReporter.videoImpression();
      statsReporter.videoView();
      
      // Play it
      mainVideo.playFLV(vo.movie_file, vo.width, vo.height);
      
      // Refresh the volume by the controller, NOT by the XML
      mainVideo.setVolume(getControl.volume);
    }
    
    /**
     * Play STD at once in play mode or menu screen
     * @param event
     * 
     */    
    private function playSTDHandler(event:Event):void
    {
      isHD = false;
      
      // Pick up the STD video and reset the info stats target
      vo = hdManager.getSTDVideo();
      
      // Setup the stats reporter
      if (statsReporter)
        statsReporter.killAll();
      statsReporter = new StatsReport(info.status_url, info.status_interval, this);
      statsReporter.videoImpression();
      statsReporter.videoView();
      
      // Play it
      mainVideo.playFLV(vo.movie_file, vo.width, vo.height);
      
      // Refresh the volume by the controller, NOT by the XML
      mainVideo.setVolume(getControl.volume);
    }
    
    /**
     * When the mouse leaves the stage, fade out the controller sets 
     * @param event
     * 
     */    
    private function mouseLeaveHandler(event:Event):void
    {
      this.stage.addEventListener(MouseEvent.MOUSE_MOVE, mouseInHandler);
      toggleController(false);
    }
    
    public function toggleController(isShowController:Boolean):void
    {
      if (isShowController)
      {
        // 1 - Show the mouse
        Mouse.show();
        
        // 2 - halt the controller fader
        controllerFader.abort();
        guiCtr.alpha = 1;
        guiCtr.visible = true;
        
        // 3 - halt the embed assets  
        if (info.embededCode)
        {
          embedmainFader.abort();
          embedtoggleFader.abort();
          embedManager.embedMain.alpha = 1;
          embedManager.toggleButton.alpha = 1;
          embedManager.toggleButton.visible = true;
          addChild(embedManager.embedMain);
          addChild(embedManager.toggleButton);
        }
      }
      else
      {
        // 1 - Whether we are in Full Screen mode, if yes, hide the mouse
        if (this.stage.displayState == "fullScreen")
        {
          Mouse.hide();
        }
        
        // 2 - Hide the controller
        controllerFader = new Fader(guiCtr, 10);
        
        // 3 - Hide the embed assets if exists
        if (info.embededCode)
        {
          embedmainFader = new Fader(embedManager.embedMain,10);
          embedtoggleFader = new Fader(embedManager.toggleButton,10)
        }
      }
    }
    
    /**
     * When the mouse enters the stage from outside area, show the controller sets 
     * @param eevent
     * 
     */
    private function mouseInHandler(event:MouseEvent):void
    {
      toggleController(true);
      stage.removeEventListener(MouseEvent.MOUSE_MOVE, mouseInHandler);
    } 

    /**
     * When the main video stops 
     * @param event
     * 
     */
    private function videoStopHandler(event:Event):void
    {
      mainVideo.alpha = WPPConfiguration.VCORE_CURTAIN_ALPHA;
      endScreen = new UIEndScreen(this);
    }
    
    // Load external data
    private function loadExternalSettings():void
    {
      if (root.loaderInfo.parameters["guid"])
        guid = root.loaderInfo.parameters["guid"];
      else if (root.loaderInfo.parameters["video_guid"])
        guid = root.loaderInfo.parameters["video_guid"];

      // Maybe some day we need to be told the size ;)
      canvasWidth = this.stage.stageWidth;  
      if (root.loaderInfo.parameters["canvaswidth"])
        canvasWidth = root.loaderInfo.parameters["canvaswidth"];
      
      canvasHeight = this.stage.stageHeight;
      if (root.loaderInfo.parameters["canvasheight"])
        canvasHeight = root.loaderInfo.parameters["canvasheight"];
      
      if (root.loaderInfo.parameters["video_info_path"])
        WPPConfiguration.XML_URL_BASE = root.loaderInfo.parameters["video_info_path"];
      
      // Whether to play the video at once when it's loaded
      if (root.loaderInfo.parameters["autoplay"]=="yes")
      {
        WPPConfiguration.AUTOPLAY_WHEN_LOADED = true;
      }
      else
      {
        WPPConfiguration.AUTOPLAY_WHEN_LOADED = false;
      }
      

      if (root.loaderInfo.parameters["dynamicseek"] == "yes")
      {
        WPPConfiguration.IS_DYNAMIC_SEEKING = true;
      }
      else
      {
        WPPConfiguration.IS_DYNAMIC_SEEKING = false;
      }
      
      if (root.loaderInfo.parameters["localmode"]=="yes" || WPPConfiguration.isLocalPlayer(this))
      {
        WPPConfiguration.IS_LOCAL_MODE = true;
      }
      else
      {
        WPPConfiguration.IS_LOCAL_MODE = false; 
      }
      
    }
    
    // Kill the splash thing
    private function killSplashScreen():void
    {
      splashScreen.unregister();
      splashScreen.removeEventListener(WPPEvents.SPLASH_VIDEO_PLAY, splashPlayHandler);
      splashScreen.removeEventListener(WPPEvents.SPLASH_SCREEN_INIT, initMainVideoHandler);
      
      splashControl.unregister();
      splashControl.removeEventListener(WPPEvents.SPLASH_VIDEO_PLAY, splashPlayHandler);
      
      splashControl.removeEventListener(WPPEvents.SPLASH_TURN_ON_HD, splashHDOnHandler);
      splashControl.removeEventListener(WPPEvents.SPLASH_TURN_OFF_HD, splashHDOffHandler);
      
      splashScreen = null;
      splashControl = null;
    }
  }
}