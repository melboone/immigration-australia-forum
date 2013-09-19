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

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function safelink_info()
{
	return array(
		'name'			=> 'MyBB SafeLink',
		'description'	=> 'Redirects off-site links to a warning page before they connect to another site.',
		'website'		=> 'http://community.mybb.com/user-36020.html',
		'author'		=> 'fizz',
		'authorsite'	=> 'http://community.mybb.com/user-36020.html',
		'guid'			=> '5c4cd6ecf8f7dba23407bfd9166fa5f3',
		'version'		=> '1.3.2',
		'compatibility' => '16*'
	);
}

function safelink_is_installed()
{
	global $db;
	
	if($db->num_rows($db->simple_select("settings","name","name='safelink_enabled'")) >=1 )
	{
		return true;
	}
	return false;
}

$plugins->add_hook("parse_message_end","safelink_do");

function safelink_install()
{
	global $db,$mybb;
	
	$safelink_group = array(
        "name" => "safelink",
        "title" => "MyBB SafeLink",
        "description" => "Edit the settings for MyBB SafeLink here.",
        "disporder" => "1",
        "isdefault" => "no",
        );
	$db->insert_query("settinggroups", $safelink_group);
    $gid = $db->insert_id();
	
	$psettings[] = array(
		"name" => "safelink_enabled",
        "title" => "Enabled",
        "description" => "Do you want to enable MyBB SafeLink?",
        "optionscode" => "yesno",
        "value" => "1",
        "disporder" => "1",
        "gid" => intval($gid)
        );
		
	$psettings[] = array(
		"name" => "safelink_urls",
        "title" => "URLs that SafeLink doesn\'t modify",
        "description" => "What URLs should be excluded from those being modified by SafeLink? (One per line, include the http:// or https:// and the www. if you want it! Your site\'s full URL [the url your forum is run on] is already included in the list. Examples: sub.yoursite.com, www.test.blah.yoursite.com)",
        "optionscode" => "textarea",
        "value" => "{$mybb->settings['bburl']}\nhttp://www.example.com\nhttps://subdomain.example.com",
        "disporder" => "2",
        "gid" => intval($gid)
        );
	
	$psettings[] = array(
		"name" => "safelink_forums",
        "title" => "Forums that SafeLink doesn\'t modify",
        "description" => "What forums should be excluded from those being modified by SafeLink? (Separate with a comma \',\')",
        "optionscode" => "text",
        "value" => "2,7,8",
        "disporder" => "3",
        "gid" => intval($gid)
        );
		
	$psettings[] = array(
		"name" => "safelink_groups",
        "title" => "Usergroups that SafeLink doesn\'t modify",
        "description" => "What usergroups should be excluded from SafeLink? (Separate with a comma \',\')",
        "optionscode" => "text",
        "value" => "3,4",
        "disporder" => "4",
        "gid" => intval($gid)
        );
		
		foreach($psettings as $setting)
		{
			$db->insert_query("settings", $setting);
		}
		
		rebuild_settings();
}

function safelink_activate()
{
	global $db, $lang, $mybb;
	$lang->load("safelink");

	$template = array(
		"tid" => "NULL",
		"title" => "safelink",
		"template" => $db->escape_string('
		<html>
<head>
<title>{$lang->safelink}</title>
{$headerinclude}
</head>
<body>
{$header}
<br />
<table width="100%" border="0" align="center">
<tr>
{$cpnav}
<td valign="top">
{$cptable}
</td>
<td valign="top" style="background-color:#f0f0f0;border:1px dotted #7eb6ff;padding:4px;">
<span><strong><font color="red">{$error}</font></strong></span>
<span>{$warning}</span>
<br /><br />
<span><strong>{$continue}</strong></span> <strong><a href="{$url}">{$url}</a></strong></span>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
	"sid" => "-1"
	);
	$db->insert_query("templates", $template);
}

function safelink_deactivate()
{
	global $db;
	
	$query = $db->write_query("SELECT gid FROM ".TABLE_PREFIX."settinggroups WHERE name='safelink'");
    $g = $db->fetch_array($query);
    $db->write_query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE gid='".$g['gid']."'");
    $db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE gid='".$g['gid']."'");
	
	//delete templates
	$db->delete_query('templates', 'title = \'safelink\'');
	rebuild_settings();
}

function safelink_uninstall(){}

function safelink_do($message)
{
	global $mybb, $post;
	if($mybb->settings['safelink_enabled'] == 1)
	{   /** Thanks to charafweb for the fix here **/
		$safe = strpos($message, "safelink.php"); //this checks if safelink strings exist in urls so to not safelinked it again
        $internal = strpos($message, $mybb->settings['bburl']); //This is to check for internal links and skip them
        if(trim($mybb->settings['safelink_forums']) != '')//this checks if there are excluded forums
        {
            $forums = explode(',', trim($mybb->settings['safelink_forums'])); // List of excluded forums
            if((!in_array($post['fid'], $forums)) && ($safe === false) && ($internal === false)) // Not an excluded forum, so Safelink it
            {
                $message = str_ireplace('<a href="', "<a href=\"".$mybb->settings['bburl']."/safelink.php?url=", $message);
            }
        }
        elseif (($safe === false) && ($internal === false)) // Excluded usergroup but in non excluded forum so Safelink it anyway
        {
                $message = str_ireplace('<a href="', "<a href=\"".$mybb->settings['bburl']."/safelink.php?url=", $message);
        }
	}
	
	return $message;
}
?>