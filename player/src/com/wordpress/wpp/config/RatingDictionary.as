 /**
 * @package       com.wordpress.wpp.config
 * @class         com.wordpress.wpp.config.RatingDictionary
 *
 * @description   Rating rules, mapping from rating code to required age
 * @author        automattic
 * @created:      Jan 12, 2009
 * @modified:     Feb 16, 2009
 *   
 */

package com.wordpress.wpp.config
{
  public class RatingDictionary
  {
    public static const RATING_DICT:Object = {
                                              "PG-13":13,
                                              "R-17":17,
                                              "X-18":18,
                                              "G":0
                                              }
    
  }
}