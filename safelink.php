<?php
/**************************************************************************\
||========================================================================||
|| MyBB SafeLink ||
|| Copyright 2011-2013 ||
|| Version 1.3.2 ||
|| Made by fizz on the official MyBB board ||
|| http://community.mybb.com/user-36020.html ||
|| I don't take responsibility for any errors caused by this plugin. ||
|| Always keep MyBB up to date and always keep this plugin up to date. ||
|| You may NOT redistribute this plugin, sell it, 
|| remove copyrights, or claim it as your own in any way. ||
||========================================================================||
\*************************************************************************/

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'safelink.php');

require_once("./global.php");

$lang->load("safelink");

// Add link in breadcrumb
add_breadcrumb($lang->safelink, "safelink.php");
if($mybb->settings['safelink_enabled'] == 1)
{
	if($mybb->input['url'])
	{
		
		if(!filter_var($mybb->input['url'], FILTER_VALIDATE_URL))
		{
			$error = $lang->badurl;
		}
		else
		{
			$v = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], "safelink.php?url=")); // these 2 lines filter out the url to continue to
			$url = str_replace('safelink.php?url=', '', $v); // only problem with these is it will replace all occurrencesof 'safelink.php?url=' in the url, and I don't know how to fix this (preg_replace perhaps?)
			$group = $mybb->user['usergroup'];
			$groups = explode(",", $mybb->settings['safelink_groups']);
			$excludes = explode("\n", $mybb->settings['safelink_urls']);
			foreach($excludes as $exclude)
			{
				if(!preg_match("#^".trim($exclude)."+[\d\w]*#i", $url) && !in_array($group, $groups)) // not an excluded site, go to safelink page and link intended URL
				{
					$warning = $lang->warning;
					$continue = $lang->continue;
				}
				else // site excluded from safelink OR user is in excluded usergroup
				{
					header("location:$url");
				}
			}
		}
	}
	else
	{
		$error = $lang->nourl;
	}
}
else
{
	$error = $lang->disabled;
}
eval("\$safelink = \"".$templates->get("safelink")."\";");

output_page($safelink);

exit;
?>