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

function automedia_xvideos($message)
{
	global $mybb, $width, $height;

	if($mybb->settings['av_sizeall'] == 1)
	{
		$w = $width;
		$h = $height; 
	} else {
		$w = "510";
		$h = "400";
	}

/**
 *Example:
 *http://www.xvideos.com/video221033/daisy_marie_is_so_cute
*/
  if(preg_match('<a href=\"(http://)(?:www\.)?xvideos\.com/video(?:\d{1,12})/(.*?)\">isU',$message))
  {
    $message = preg_replace("#(\[automedia\]|(<a href=\")?(http://)(?:www\.)?xvideos\.com/video([0-9]{1,12})/(.*?)(\[/automedia\]|\" target=\"_blank\">)(.*?)</a>)#i", '<div class=\'am_embed\'><object width=\'{$w}\' height=\'{$h}\' classid=\'clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\' codebase=\'http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0\' ><param name=\'quality\' value=\'high\' /><param name=\'bgcolor\' value=\'#000000\' /><param name=\'allowScriptAccess\' value=\'always\' /><param name=\'movie\' value=\'http://static.xvideos.com/swf/flv_player_site_v4.swf\' /><param name=\'allowFullScreen\' value=\'true\' /><param name=\'flashvars\' value=\'id_video=${4}\' /><embed src=\'http://static.xvideos.com/swf/flv_player_site_v4.swf\'	allowscriptaccess=\'always\' width=\'510\' height=\'400\' menu=\'false\' quality=\'high\' bgcolor=\'#000000\' allowfullscreen=\'true\' flashvars=\'id_video=${4}\' type=\'application/x-shockwave-flash\' pluginspage=\'http://www.macromedia.com/go/getflashplayer\'></embed></object></div>', $message);
  }
	return $message;
}
?>
