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

function automedia_tube8($message)
{
	global $mybb, $width, $height;

	if($mybb->settings['av_sizeall'] == 1)
	{
		$w = $width;
		$h = $height; 
	} else {
		$w = "608";
		$h = "481";
	}

/**
 *Example:
 *http://www.tube8.com/fetish/anya-long-hair-godess/2583/
*/
  if(preg_match('<a href=\"(http://)(?:www\.)?tube8\.com/(.*?)/([0-9]{1,16})(\W?)\">isU',$message))
  {
    $message = preg_replace("#(\[automedia\]|(<a href=\")?(http://)(?:www\.)?tube8\.com/(.*?)/([0-9]{1,16})(\W?)(\[/automedia\]|\" target=\"_blank\">)(.*?)</a>)#i", "<div class=\"am_embed\"><iframe src=\"http://www.tube8.com/embed/$4/$5/\" frameborder=0 height=$h width=$w scrolling=no name=\"t8_embed_video\"><a href=\"http://www.tube8.com/\">Tube8</a></iframe></div>", $message);
  }
	return $message;
}
?>
