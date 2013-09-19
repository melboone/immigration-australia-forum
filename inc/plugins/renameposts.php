<?php
/*
 * Rename Posts
 * Author: Prtik
 * Copyright: © 2013 Prtik
 *
 * $Id: renameposts.php 3991 2013-03-20 14:33:02Z Prtik $
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Tell MyBB when to run the hooks
$plugins->add_hook("moderation_start", "renameposts_run");
$plugins->add_hook("showthread_start", "renameposts_lang");

// The information that shows up on the plugin manager
function renameposts_info()
{
	return array(
		"name"				=> "Rename Posts",
		"description"		=> "Allows moderators to rename posts in thread.",
		"website"			=> "http://community.mybb.com/thread-136397.html",
		"author"			=> "Prtik",
		"authorsite"		=> "http://mods.mybb.com/profile/22590",
		"version"			=> "1.0",
		"guid"				=> "69db0ab377c23a195f906f014924f9e6",
		"compatibility"		=> "16*"
	);
}

// This function runs when the plugin is activated.
function renameposts_activate()
{
	global $db;
	$insert_array = array(
		'title'		=> 'moderation_inline_renameposts',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->rename_posts}</title>
{$headerinclude}
</head>
<body>
{$header}
<form action="moderation.php" method="post">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>{$lang->rename_posts}</strong></td>
</tr>
<tr>
<td class="tcat" colspan="2"><span class="smalltext"><strong>{$lang->rename_posts_info}</strong></span></td>
</tr>
{$loginbox}
<tr>
<td class="trow2"><strong>{$lang->new_subject_name}</strong><br /><span class="smalltext">{$lang->rename_posts_note}</span></td>
<td class="trow2" width="60%"><input type="text" class="textbox" name="newname" value="{$new_name}" size="40" />
</tr>
</table>
<br />
<div align="center"><input type="submit" class="button" name="submit" value="{$lang->rename_posts}" /></div>
<input type="hidden" name="action" value="do_multirenameposts" />
<input type="hidden" name="tid" value="{$tid}" />
<input type="hidden" name="posts" value="{$inlineids}" />
</form>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("showthread_inlinemoderation", "#".preg_quote('</optgroup>')."#i", '<option value="multirenameposts">{$lang->rename_posts}</option></optgroup>');
}

// This function runs when the plugin is deactivated.
function renameposts_deactivate()
{
	global $db;
	$db->delete_query("templates", "title IN('moderation_inline_renameposts')");

	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("showthread_inlinemoderation", "#".preg_quote('<option value="multirenameposts">{$lang->rename_posts}</option>')."#i", '', 0);
}

// Rename posts - Inline moderation tool
function renameposts_run()
{
	global $db, $mybb, $lang, $templates, $theme, $headerinclude, $header, $footer, $loginbox, $inlineids, $parser;
	$lang->load("renameposts");

	if($mybb->input['action'] != "multirenameposts" && $mybb->input['action'] != "do_multirenameposts")
	{
		return;
	}

	if($mybb->user['uid'] != 0)
	{
		eval("\$loginbox = \"".$templates->get("changeuserbox")."\";");
	}
	else
	{
		eval("\$loginbox = \"".$templates->get("loginbox")."\";");
	}

	$tid = intval($mybb->input['tid']);
	$thread = get_thread($tid);

	$thread['subject'] = htmlspecialchars_uni($parser->parse_badwords($thread['subject'])); 

	if($mybb->input['action'] == "multirenameposts" && $mybb->request_method == "post")
	{
		// Verify incoming POST request
		verify_post_check($mybb->input['my_post_key']);

		build_forum_breadcrumb($thread['fid']);
		add_breadcrumb($thread['subject'], get_thread_link($thread['tid']));
		add_breadcrumb($lang->nav_multi_renameposts);

		if($mybb->input['inlinetype'] == 'search')
		{
			$posts = getids($mybb->input['searchid'], 'search');
		}
		else
		{
			$posts = getids($tid, 'thread');
		}

		if(count($posts) < 1)
		{
			error($lang->error_inline_nopostsselected);
		}

		if(!is_moderator_by_pids($posts, "canmanagethreads"))
		{
			error_no_permission();
		}
		$posts = array_map('intval', $posts);
		$pidin = implode(',', $posts);

		$inlineids = implode("|", $posts);
		if($mybb->input['inlinetype'] == 'search')
		{
			clearinline($mybb->input['searchid'], 'search');
		}
		else
		{
			clearinline($tid, 'thread');
		}
		$new_name = "RE: " . $thread['subject'];
		eval("\$multirename = \"".$templates->get("moderation_inline_renameposts")."\";");
		output_page($multirename);
	}

	if($mybb->input['action'] == "do_multirenameposts" && $mybb->request_method == "post")
	{
		// Verify incoming POST request
		verify_post_check($mybb->input['my_post_key']);

		$postlist = explode("|", $mybb->input['posts']);
		$pid1 = "";
		foreach($postlist as $pid)
		{
			if ($pid1 == "")
			{
				$pid1 = $pid;
			}
			
			$pid = intval($pid);
			$plist[] = $pid;
		}

		if(!is_moderator_by_pids($plist, "canmanagethreads"))
		{
			error_no_permission();
		}
		
		if($mybb->input['newname']=="")
		{
			error($lang->error_badnewname);
		}

		// Make sure we only have valid values
		$pids = array_map('intval', $plist);
		$pids_list = implode(',', $pids);

			// Move the selected posts over
		$sqlarray = array(
			"subject" => $db->escape_string($mybb->input['newname'])
		);
		$db->update_query("posts", $sqlarray, "pid IN ($pids_list)");
		
		$lang->renamed_selective_posts = $lang->sprintf($lang->renamed_selective_posts, $pids_list, $tid);

		log_moderator_action(array("tid" => $tid, "fid" => $thread['fid']), $lang->renamed_selective_posts);
		
		$url = get_post_link($pid1, $tid)."#pid{$pid1}";
		moderation_redirect($url, $lang->redirect_postsrenamed);
	}
	exit;
}

// Show language on show thread
function renameposts_lang()
{
	global $lang;
	$lang->load("renameposts");
}

?>