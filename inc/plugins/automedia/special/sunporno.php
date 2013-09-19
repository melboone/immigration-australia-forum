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

function automedia_sunporno($message)
{
	global $mybb, $width, $height;

	if($mybb->settings['av_sizeall'] == 1)
	{
		$w = $width;
		$h = $height; 
	} else {
		$w = "650";
		$h = "518";
	}

/**
 *Example:
 *http://www.sunporno.com/tube/videos/424147/pink-titted-jessica-kramer-exposes-her-meaty-jugs-and-loves-it.html
*/
  if(preg_match('<a href=\"(http://)(?:www\.)?sunporno\.com/tube/videos/(.*?)\">isU',$message))
  {
    $message = preg_replace("#(\[automedia\]|(<a href=\")?(http://)(?:www\.)?sunporno\.com/tube/videos/([0-9]{1,16})/(.*?)\.html(\[/automedia\]|\" target=\"_blank\">)(.*?)</a>)#i", "<div class=\"am_embed\"><iframe src=\"http://embeds.sunporno.com/embed/$4\" frameborder=0 width=$w height=$h scrolling=no></iframe></div>", $message);
  }
	return $message;
}
?>
