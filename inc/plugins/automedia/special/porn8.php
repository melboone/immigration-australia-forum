<?php
###################################
# Plugin AutoMedia 2.0  for MyBB 1.6.*#
# (c) 2011 by doylecc    #
# Website: http://mods.mybb.com/profile/14694 #
###################################


// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />
		Please make sure IN_MYBB is defined.");
}

function automedia_porn8($message)
{
	global $mybb, $width, $height;

	if($mybb->settings['av_sizeall'] == 1)
	{
		$w = $width;
		$h = $height; 
	} else {
		$w = "450";
		$h = "370";
	}

/**
 *Example:
 *http://www.porn8.com/free-porn/video/3b878c4e2c/Hailey-Jade-in-POV-Casting-Couch-11-Scene-3-from-VideosZ.video
*/
  if(preg_match('<a href=\"(http://)(?:www\.)?porn8\.com/free-porn/video/(?:\w{1,15})/(.*?)\">isU',$message))
  {
    $message = preg_replace("#(\[automedia\]|(<a href=\")?(http://)(?:www\.)?porn8\.com/free-porn/video/([0-9a-f]{1,15})/(.*?)(\[/automedia\]|\" target=\"_blank\">)(.*?)</a>)#i", "<div class=\"am_embed\"><object width=\"$w\" height=\"$h\" ><param name=\"movie\" value=\"http://www.porn8.com/flv/flvplayer.swf\" /><param name=\"FlashVars\" value=\"config=http://www.porn8.com/vidcolem/$4/\" /><param name=\"quality\" value=\"high\" /><param name=\"bgcolor\" value=\"#000000\" /><param name=\"allowFullScreen\" value=\"true\" /><param name=\"allowScriptAccess\" value=\"always\" /><embed src=\"http://www.porn8.com/flv/flvplayer.swf\" allowscriptaccess=\"always\" allowfullscreen=\"true\" width=\"$w\" height=\"$h\" quality=\"high\" bgcolor=\"#000000\" FlashVars=\"config=http://www.porn8.com/vidcolem/$4/\" type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\"></embed></object></div>", $message);
  }
	return $message;
}
?>
