<?php

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("datahandler_post_insert_post", "notifyreplies_newreplies");
$plugins->add_hook("global_start", "notifyreplies_alertreplies");
$plugins->add_hook("showthread_start", "notifyreplies_closealert");

function notifyreplies_info()
{
	return array(
		"name"			=> "Notify Replies",
		"description"	=> "Send an alert notification to the user as when someone replies to your topic.",
		"website"		=> "mailto:nicedo_eeos@hotmail.com",
		"author"		=> "Edson Ordaz",
		"authorsite"	=> "mailto:nicedo_eeos@hotmail.com",
		"version"		=> "1.0",
		"compatibility"   => "16*",
		"guid"			=> "aa175925dff4963d189aa619769d95db"
	);
}

function notifyreplies_is_installed(){
	global $mybb, $db;
  	if($db->field_exists("nr", "users"))
	{
		return true;
	}
}

function notifyreplies_install() 
{
	global $mybb, $db;
	
	if(!$db->field_exists("nr", "users"))  
		$db->add_column("users", "nr", "int(10) unsigned NOT NULL default '0'"); 
	if(!$db->field_exists("nr", "threads"))  
		$db->add_column("threads", "nr", "int(10) unsigned NOT NULL default '0'"); 
		
	$notifyreplies_template = array(
		"title"		=> 'notifyreplies_alert',
		"template"	=> $db->escape_string('<div class="pm_alert" id="comment_notice">
<div>
You have {$news} new(s) reply(s) in the thread {$thread}.
</div>
</div>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$db->insert_query("templates", $notifyreplies_template);
	
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets('header', '#{\$unreadreports}#', '{$unreadreports}<!-- NotifyReplies -->
			{$newreplies}<!-- /NotifyReplies -->');
}

function notifyreplies_uninstall(){
	global $db;
	
	if($db->field_exists("nr", "users"))  
		$db->drop_column("users", "nr");
	if($db->field_exists("nr", "threads"))  
		$db->drop_column("threads", "nr");
		
	$db->delete_query("templates","title='notifyreplies_alert'");
	
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets('header', '#\<!--\sNotifyReplies\s--\>(.+)\<!--\s/NotifyReplies\s--\>#is', '', 0);

}

function notifyreplies_newreplies($post)
{
	global $mybb,$db;
	$thread = get_thread($post->data['tid']);
	if($mybb->user['uid'] != $thread['uid'])
	{
		$user = get_user($thread['uid']);
		$db->update_query("threads", array("nr" => ++$thread['nr']),"tid='".$thread['tid']."'");
		$db->update_query("users", array("nr" => ++$user['nr']),"uid='".$user['uid']."'");
		unset($user);
		return true;
	}
	unset($thread);
	return true;
}

function notifyreplies_alertreplies()
{
	global $mybb, $db, $templates, $newreplies;
	$num_replies = $mybb->user['nr'];

	if($mybb->user['uid'] != 0 && $num_replies > 0)
	{	
		$query = $db->simple_select("threads", "*", "nr > '0' AND uid='".$mybb->user['uid']."'", array('order_by' => 'nr', 'order_dir' => 'ASC'));
		$thread = $db->fetch_array($query);
		$news = $thread['nr'];
		$turl = str_replace("{tid}", $thread['tid'], THREAD_URL);
		$thread = "<a href=\"".$mybb->settings['bburl']."/".$turl."&action=lastpost\" /><strong>".$thread['subject']."</strong></a>";
		eval("\$newreplies = \"".$templates->get("notifyreplies_alert")."\";");
		$db->free_result($query);
		unset($news);
		unset($turl);
		unset($thread);
	}
}

function notifyreplies_closealert()
{
	global $thread,$mybb,$tid,$db;
	if($mybb->user['uid'] != 0 && $mybb->user['uid'] == $thread['uid'])
	{
		$db->update_query("users", array("nr" => $mybb->user['nr'] - $thread['nr']),"uid='".$thread['uid']."'");
		$db->update_query("threads", array("nr" => 0),"tid='".$tid."'");
	}
	return true;
}

?>