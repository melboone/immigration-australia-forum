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

function automedia_pornhub($message)
{
	global $mybb, $width, $height;

	if($mybb->settings['av_sizeall'] == 1)
	{
		$w = $width;
		$h = $height; 
	} else {
		$w = "608";
		$h = "476";
	}

/**
 *Example:
 *http://www.pornhub.com/view_video.php?viewkey=395131153
*/
  $pattern = "<http://www.pornhub.com/(.*)\" target>";
  if(preg_match($pattern, $message))
  {
    preg_match_all($pattern, $message, $links);
    $link = $links[1];
    foreach ($link as $url)
    {
      $site = htmlspecialchars_uni("http://www.pornhub.com/$url");
      //Use cURL and find the video id
      if(!function_exists('curl_init') || !$c = curl_init())
        return false;
        curl_setopt($c, CURLOPT_URL, $site);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0");
        curl_setopt($c, CURLOPT_TIMEOUT, 3);
        $data = utf8_encode(curl_exec($c));
        if(!$data)
          $data = 'not available';
          curl_close($c);

        if($data) {
          $nrph = get_avmatch('/thumbs_up_video_([0-9]{1,10}?)_/isU',$data);
          $vid = array($nrph);
        }
        $limit = 1;
        foreach ($vid as $id)
        {
          $n = htmlspecialchars_uni($id);
          $message = preg_replace("#(\[automedia\]|<a href=\"(http://)(?:www\.)?pornhub\.com/view(.*?)(\[/automedia\]|\" target=\"_blank\">)(.*?)</a>)#i", "<div class=\"am_embed\"><object type=\"application/x-shockwave-flash\" data=\"http://cdn1.static.pornhub.phncdn.com/flash/embed_player.swf\" width=\"$w\" height=\"$h\"><param name=\"movie\" value=\"http://cdn1.static.pornhub.phncdn.com/flash/embed_player.swf\" /><param name=\"bgColor\" value=\"#000000\" /><param name=\"allowfullscreen\" value=\"true\" /><param name=\"allowScriptAccess\" value=\"always\" /><param name=\"FlashVars\" value=\"options=http://www.pornhub.com/embed_player.php?id=$n\"/></object></div>", $message, $limit);
        }
    }
  }
	return $message;
}
?>
