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

function automedia_hardsextube($message)
{
	global $mybb, $width, $height;

	if($mybb->settings['av_sizeall'] == 1)
	{
		$w = $width;
		$h = $height; 
	} else {
		$w = "400";
		$h = "300";
	}

/**
 *Example:
 *http://www.hardsextube.com/video/632601/
*/
  if(preg_match('<a href=\"(http://)(?:www\.)?hardsextube\.com/video/(.*?)\">isU',$message))
  {
    $message = preg_replace("#(\[automedia\]|(<a href=\")?(http://)(?:www\.)?hardsextube\.com/video/([0-9]{1,16})(\W?)(\[/automedia\]|\" target=\"_blank\">)(.*?)</a>)#i", "<div class=\"am_embed\"><object width=\"$w\" height=\"$h\"> <param name=\"movie\" value=\"http://www.hardsextube.com/embed/$4/\"></param><param name=\"AllowScriptAccess\" value=\"always\"></param><param name=\"wmode\" value=\"transparent\"></param><embed src=\"http://www.hardsextube.com/embed/$4/\" type=\"application/x-shockwave-flash\" wmode=\"transparent\" AllowScriptAccess=\"always\" width=\"$w\" height=\"$h\"></embed></object></div>", $message);
  }
	return $message;
}
?>
