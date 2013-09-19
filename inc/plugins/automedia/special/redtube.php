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

function automedia_redtube($message)
{
	global $mybb, $width, $height;

	if($mybb->settings['av_sizeall'] == 1)
	{
		$w = $width;
		$h = $height; 
	} else {
		$w = "434";
		$h = "344";
	}

/**
 *Example:
 *http://www.redtube.com/12528
*/
	if(preg_match('<a href=\"(http://)(?:www\.)?redtube\.com/(?:\d{1,8})\">isU',$message))
	{
		$message = preg_replace("#(\[automedia\]|(<a href=\")?(http://)(?:www\.)?redtube\.com/(.{1,8})(\[/automedia\]|\" target=\"_blank\">)(.*?)</a>)#i", "<div class=\"am_embed\"><object height=\"$h\" width=\"$w\"><param name=\"allowfullscreen\" value=\"true\"><param name=\"movie\" value=\"http://embed.redtube.com/player/\"><param name=\"FlashVars\" value=\"id=$4&amp;style=redtube&amp;autostart=false\"><embed src=\"http://embed.redtube.com/player/?id=$4&amp;style=redtube\" allowfullscreen=\"true\" flashvars=\"autostart=false\" pluginspage=\"http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash\" type=\"application/x-shockwave-flash\" height=\"$h\" width=\"$w\" /></object></div>", $message);
	}
	return $message;
}
?>
