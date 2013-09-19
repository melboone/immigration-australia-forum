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

function automedia_xxxymovies($message)
{
	global $mybb, $width, $height;

	if($mybb->settings['av_sizeall'] == 1)
	{
		$w = $width;
		$h = $height; 
	} else {
		$w = "480";
		$h = "384";
	}

/**
 *Example:
 *http://www.xxxymovies.com/watch/8379/Latin_wife_to_do_his_taxes_or_him.html
*/
  $pattern = "<http://www.xxxymovies.com/watch/(.*)\" target>";
  if(preg_match($pattern, $message))
  {
    preg_match_all($pattern, $message, $links);
    $link = $links[1];
    foreach ($link as $url)
    {
      $site = htmlspecialchars_uni("http://www.xxxymovies.com/watch/$url");
      //Use cURL and find the video id
      if(!function_exists('curl_init') || !$c = curl_init())
        return false;
        curl_setopt($c, CURLOPT_URL, $site);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_TIMEOUT, 3);
        $data = utf8_encode(curl_exec($c));
        if(!$data)
          $data = 'not available';
          curl_close($c);

        if($data) {
          $nrxxx = get_avmatch('/fo\.addVariable\(\"videoid\", \"([0-9a-f]{1,50}?)/isU',$data);
          $vid = array($nrxxx);
        }
        $limit = 1;
        foreach ($vid as $id)
        {
          $n = htmlspecialchars_uni($id);
          $message = preg_replace("#(\[automedia\]|<a href=\"(http://)(?:www\.)?xxxymovies\.com/watch/(.*?)\.html(\[/automedia\]|\" target=\"_blank\">)(.*?)</a>)#i", "<div class=\"am_embed\"><embed src=\"http://www.xxxymovies.com/embed/$n/\" loop=\"false\" width=\"$w\" height=\"$h\" allowfullscreen=\"true\" allowScriptAccess=\"always\" type=\"application/x-shockwave-flash\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\" /></div>", $message, $limit);
        }
    }
  }
	return $message;
}
?>
