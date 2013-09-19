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

function automedia_porn($message)
{
	global $mybb, $width, $height;

	if($mybb->settings['av_sizeall'] == 1)
	{
		$w = $width;
		$h = $height; 
	} else {
		$w = "600";
		$h = "476";
	}

/**
 *Example:
 *http://www.porn.com/videos/redhead-in-chains-manhandled-in-rough-sex-68157.html
*/
  if(preg_match('<a href=\"(http://)(?:www\.)?porn\.com/videos/(.*?)\">isU',$message))
  {
    $message = preg_replace("#(\[automedia\]|(<a href=\")?(http://)(?:www\.)?porn\.com/videos/(.*?)-([0-9]{1,16})\.html(.*?)(\[/automedia\]|\" target=\"_blank\">)(.*?)</a>)#i", "<div class=\"am_embed\"><iframe title=\"$4\" scrolling=\"no\" width=\"$w\" height=\"$h\" src=\"http://www.porn.com/videos/embed/$5.html\" frameborder=\"0\"></iframe></div>", $message);
  }
	return $message;
}
?>
