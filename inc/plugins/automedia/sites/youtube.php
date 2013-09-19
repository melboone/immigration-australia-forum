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

function automedia_youtube($message)
{
	global $mybb, $width, $height;

	if($mybb->settings['av_sizeall'] == 1)
	{
		$w = $width;
		$h = $height; 
	} else {
		$w = "640";
		$h = "390";
	}

/**
 *Examples:
 *http://www.youtube.com/watch?v=K2oLoBpFmho or http://www.youtube.com/watch?v=cSB2TpeY-2E&feature=related or http://youtu.be/t2EmCBDKlRo
 */

	if(preg_match('<a href=\"(http://)(?:www\.)?youtube.com/watch\?v=(.{11})">isU',$message))
	{
		$message = preg_replace("#(\[automedia\]|(<a href=\")?(http://)?(www.)?youtube.com/watch\?(.*?)v=)(.{11})((\[/automedia\]|\" target=\"_blank\">)(.*?)</a>)#i", "<div class=\"am_embed\"><iframe title=\"YouTube video player\" width=\"$w\" height=\"$h\" src=\"http://www.youtube.com/embed/$6?wmode=transparent\" frameborder=\"0\" allowfullscreen></iframe></div>", $message);
	}
	if(preg_match('<a href=\"(http://)(?:www\.)?youtube.com/watch\?(.*?)">isU',$message))
	{
		$message = preg_replace("#(\[automedia\]|(<a href=\")?(http://)?(www.)?youtube.com/watch\?(.*?)v=)(.{11})((.*?)(\[/automedia\]|\" target=\"_blank\">)(.*?)</a>)#i", "<div class=\"am_embed\"><object width=\"$w\" height=\"$h\"><param name=\"movie\" value=\"http://www.youtube.com/v/$6&amp;fs=1\" /><param name=\"allowFullScreen\" value=\"true\" /><param name=\"allowScriptAccess\" value=\"always\" /><embed src=\"http://www.youtube.com/v/$6&amp;fs=1\" type=\"application/x-shockwave-flash\" allowscriptaccess=\"always\" allowfullscreen=\"true\" width=\"$w\" height=\"$h\"></embed></object></div>", $message);
	}
	if(preg_match('<a href=\"(http://)(?:www\.)?youtu.be/(.*?)">isU',$message))
	{
		$message = preg_replace("#(\[automedia\]|(<a href=\")?(http://)?(www.)?youtu.be/)(.{11}?)((.*?)(\[/automedia\]|\" target=\"_blank\">)(.*?)</a>)#i", "<div class=\"am_embed\"><iframe title=\"YouTube video player\" width=\"$w\" height=\"$h\" src=\"http://www.youtube.com/embed/$5?wmode=transparent\" frameborder=\"0\" allowfullscreen></iframe></div>", $message);						
	}
	if(preg_match('<a href=\"(http://)(?:www\.)?youtube.com/playlist(.*?)">isU',$message))
	{
		$message = preg_replace("#(\[automedia\]|(<a href=\")?(http://)?(www.)?youtube.com/playlist\?(.*?)p=(PL)?)(\w{14,22})((.*?)(\[/automedia\]|\" target=\"_blank\">)(.*?)</a>)#i", "<div class=\"am_embed\"><iframe title=\"YouTube video player\" width=\"$w\" height=\"$h\" src=\"http://www.youtube.com/embed/p/$7?wmode=transparent\" frameborder=\"0\" allowfullscreen></iframe></div>", $message);
	}
	return $message;

}
?>
