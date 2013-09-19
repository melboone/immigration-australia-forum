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

function automedia_keezmovies($message)
{
	global $mybb, $width, $height;

	if($mybb->settings['av_sizeall'] == 1)
	{
		$w = $width;
		$h = $height; 
	} else {
		$w = "508";
		$h = "416";
	}

/**
 *Example:
 *http://www.keezmovies.com/video/amateur-brunette-blows-hubby-447981 or http://www.keezmovies.com/447981
*/
  if(preg_match('<a href=\"(http://)(?:www\.)?keezmovies\.com/(.*)\">isU',$message))
  {
    $message = preg_replace("#(\[automedia\]|(<a href=\")?(http://)(?:www\.)?keezmovies\.com/([0-9]{1,15})(\[/automedia\]|\" target=\"_blank\">)(.*?)</a>)#i", "<div class=\"am_embed\"><object type=\"application/x-shockwave-flash\" data=\"http://km-static.phncdn.com/flash/player_embed.swf?cache=002\" width=\"$w\" height=\"$h\" ><param name=\"movie\" value=\"http://cdn1.static.keezmovies.phncdn.com/flash/player_embed.swf?cache=005\" /><param name=\"bgcolor\" value=\"#000000\" /><param name=\"allowfullscreen\" value=\"true\" /><param name=\"allowScriptAccess\" value=\"always\" /><param name=\"FlashVars\" value=\"options=http://www.keezmovies.com/embed_player.php?id=$4\" /></object></div>", $message);
    $message = preg_replace("#(\[automedia\]|(<a href=\")?(http://)(?:www\.)?keezmovies\.com/video/(.*?)-([0-9]{1,15})(\[/automedia\]|\" target=\"_blank\">)(.*?)</a>)#i", "<div class=\"am_embed\"><object type=\"application/x-shockwave-flash\" data=\"http://km-static.phncdn.com/flash/player_embed.swf?cache=005\" width=\"$w\" height=\"$h\" ><param name=\"movie\" value=\"http://cdn1.static.keezmovies.phncdn.com/flash/player_embed.swf?cache=005\" /><param name=\"bgcolor\" value=\"#000000\" /><param name=\"allowfullscreen\" value=\"true\" /><param name=\"allowScriptAccess\" value=\"always\" /><param name=\"FlashVars\" value=\"options=http://www.keezmovies.com/embed_player.php?id=$5\" /></object></div>", $message);
  }
	return $message;
}
?>
