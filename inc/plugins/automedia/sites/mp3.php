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

function automedia_mp3($message)
{
	global $mybb;

/**
 *Example:
 *http://www.birding.dk/galleri/stemmermp3/Luscinia%20megarhynchos%201.mp3
 */

  if(preg_match('<a href=\"(http://)?(www.)?(.*)\.mp3\">isU',$message))
  {
    $message = preg_replace("#(\[automedia\]|<a href=\"(http://)?(www.)?(.*)\.(.*)/([\w/ &;%\.-]+\.mp3)(\[/automedia\]|\" target=\"_blank\">)(.*?)</a>)#i", "<div class=\"am_embed\"><object type=\"application/x-shockwave-flash\" data=\"{$mybb->settings['bburl']}/inc/plugins/automedia/mediaplayer/emff_position_blue.swf\" width=\"100\" height=\"50\" /><param name=\"movie\" value=\"{$mybb->settings['bburl']}/inc/plugins/automedia/mediaplayer/emff_position_blue.swf\" /><param name=\"FlashVars\" value=\"src=$2$3$4.$5/$6\" /></object></div>", $message);
  }
	return $message;
}
?>
