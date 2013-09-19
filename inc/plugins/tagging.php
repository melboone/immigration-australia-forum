<?PHP

function tagging_info()
{
	return array(
		"name"			=> "Tagging Plugin!",
		"description"	=> "Allows you to tag other users in your posts",
		"author"		=> "flash.tato",
		"authorsite"	=> "http://www.tatodev.host22.com",
		"version"		=> "1.3.4",
		"compatibility" => "14*,16*",
		"guid" 			=> "04eeda1eecb9cffea06f152f204be13f"
	);
}

function tagging_is_installed()
{
	global $db;
	return $db->field_exists("tags", "posts") && $db->field_exists("allowtags", "users");
}

function tagging_install()
{
	global $db;
	$db->query("ALTER TABLE  `" . $db->table_prefix . "posts` ADD  `tags` TEXT NOT NULL DEFAULT  ''");
	$db->query("ALTER TABLE  `" . $db->table_prefix . "users` ADD  `allowtags` INT( 1 ) NOT NULL DEFAULT  '" . TAG_ALL . "'");
	$db->insert_query("templates", array('sid' => '-1', 'dateline' => TIME_NOW, 'title' => 'usercp_tags', 'template' => $db->escape_string('<html>
<head>
	<title>{$mybb->settings[\'bbname\']} - {$lang->tagging_usercp_settings}</title>
	{$headerinclude}
</head>
<body>
	{$header}
	<table width="100%" border="0" align="center">
		<tr>
		<form action="usercp.php?action=tags" method="POST">
			{$usercpnav}
			<td valign="top">
				<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
					<tr>
						<td class="thead" align="center"><strong>{$lang->tagging_usercp_settings}</strong></td>
					</tr>
					<tr>
						<td class="trow1">
							{$error}
							<fieldset>
								<legend><strong>{$lang->tagging_who_can}</strong></legend>
								<input type="radio" name="allowtags" value="0" {$notagcheck}/> {$lang->tagging_usercp_noone}<br />
								<input type="radio" name="allowtags" value="1" {$allcheck}/> {$lang->tagging_usercp_all}<br />
								<input type="radio" name="allowtags" value="2" {$buddycheck}/> {$lang->tagging_usercp_buddy} 
							</fieldset>
							<input type="submit" class="button" name="submit" value="{$lang->tagging_usercp_update_settings}" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	</form>
</body>
</html>')));
	$setting_group = array(
		"gid"            => "NULL",
		"title"          => "Tagging Plugin! Settings",
		"name"           => "tagging",
		"description"    => "Manage every single aspect of the Tagging Plugin!",
		"disporder"      => '4',
		"isdefault"      => "0"
	);
	$db->insert_query("settinggroups", $setting_group);
	$gid = $db->insert_id();
	$setting_1 = array(
		'name' => 'tagging_tag_show',
		'title' => 'Tag appareance',
		'description' => 'Here you can set how a tag appears (Variables you can use {profileurl}, {username})',
		'optionscode' => 'text',
		'value' => '<a href="{profileurl}">{username}</a>',
		'disporder' => 1,
		'gid' => $gid
	);
	$db->insert_query("settings", $setting_1);
	$setting_2 = array(
		'name' => 'tagging_tag_pm_title',
		'title' => 'PM Subject',
		'description' => 'Write the subject of the PM that it is sent to the user',
		'optionscode' => 'text',
		'value' => $db->escape_string('You\'ve been tagged'),
		'disporder' => 2,
		'gid' => $gid
	);
	$db->insert_query("settings", $setting_2);
	$setting_3 = array(
		'name' => 'tagging_tag_pm_message',
		'title' => 'PM Message',
		'description' => 'Write the PM Message which will be sent to the tagged user',
		'optionscode' => 'textarea',
		'value' => $db->escape_string('You\'ve been tagged in this [URL={bburl}{posturl}]post[/URL]'),
		'disporder' => 3,
		'gid' => $gid
	);
	$db->insert_query("settings", $setting_3);
	rebuild_settings();
}

function tagging_uninstall()
{
	global $db, $plugins;
	$db->query("ALTER TABLE  `" . $db->table_prefix . "posts` DROP  `tags`");
	$db->query("ALTER TABLE  `" . $db->table_prefix . "users` DROP  `allowtags`");
	$db->delete_query("templates", "title='usercp_tags'");
	$db->delete_query("settings", "name IN ('tagging_tag_show', 'tagging_tag_pm_title', 'tagging_tag_pm_message')");
	$db->delete_query("settinggroups", "name='tagging'");
	rebuild_settings();
}

function tagging_activate()
{
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	find_replace_templatesets('usercp_nav_misc', '#' . preg_quote('<tr><td class="trow1 smalltext"><a href="usercp.php?action=editlists" class="usercp_nav_item usercp_nav_editlists">{$lang->ucp_nav_editlists}</a></td></tr>') . '#si', '<tr><td class="trow1 smalltext"><a href="usercp.php?action=editlists" class="usercp_nav_item usercp_nav_editlists">{$lang->ucp_nav_editlists}</a></td></tr><tr><td class="trow1 smalltext"><a href="usercp.php?action=tags" class="usercp_nav_item usercp_nav_editlists">Tags</a></td></tr>');

}

function tagging_deactivate()
{
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
	find_replace_templatesets('usercp_nav_misc', '#' . preg_quote('<tr><td class="trow1 smalltext"><a href="usercp.php?action=tags" class="usercp_nav_item usercp_nav_editlists">Tags</a></td></tr>') . '#si', '', 0);
}

//TAGGING API: an external plugin could include this file and then using these functions

function get_tags_from_message($message)
{
	if(!preg_match_all("#@\[(.*?)\]#", $message, $matches))
		return array();
	return array_unique($matches[1]);
}

function tagging_get_user($p)
{
	global $db;
	$p = $db->escape_string($p);
	if(is_numeric($p))
		$extra = "OR (uid='{$p}')";
	$query = $db->simple_select("users", "uid,username", "(username = BINARY '{$p}') {$extra}");
	if($db->num_rows($query))
		return $db->fetch_array($query);
}

define('NO_TAG', 0);
define('TAG_ALL', 1);
define('TAG_BUDDY', 2);
function tagging_can_tag_user($fromid, $uid)
{
	global $db;
	$user = get_user($uid);
	switch($user['allowtags'])
	{
		case NO_TAG:
			return false;
		case TAG_ALL:
			return true;
		case TAG_BUDDY:
			$buddies = explode(",", $user['buddylist']);
			return in_array($fromid, $buddies);
	}
}

function tagging_send_pm($to, $from, $subject, $message)
{
	require_once MYBB_ROOT."inc/datahandlers/pm.php";

	$pmhandler = new PMDataHandler();
	$pm = array(
		"subject" => $subject,
		"message" => $message,
		"toid" => $to,
		"fromid" => $from
	);
	$pmhandler->set_data($pm);
	if(!$pmhandler->validate_pm())
		return false;
	else
	{
		$pmhandler->insert_pm();
		return true;
	}
}

//TAGGING HELPER FUNCTIONS

function tagging_strip_post($message)
{
	//TODO: inserting a hook in case someone creates a MyCode which doesn't want messed with tags
	return preg_replace(array("#\[quote(.*?)pid='([0-9]+)'(.*?)\](.*?)\[/quote\]#si", "#\[code\](.*?)\[/code\]#si"), "", $message);
}

function tagging_get_tag_text($uid, $username)
{
	global $mybb, $parser;
	$str_find = array('{profileurl}', '{username}', '{uid}');
	$str_replace = array(get_profile_link($uid), $username, $uid);
	return str_replace($str_find, $str_replace, $mybb->settings['tagging_tag_show']);
}

function tagging_load_lang()
{
	global $lang;
	if(file_exists(MYBB_ROOT . "inc/languages/" . $lang->language . "/tagging.lang.php") && $lang->language != "english")
	{
		$lang->load("tagging");
	}
	elseif(!isset($lang->tagging_usercp_noone))
	{
		$lang->tagging_usercp_noone = "No one";
		$lang->tagging_usercp_all = "Everyone";
		$lang->tagging_usercp_buddy = "Only your buddies";
		$lang->tagging_usercp_settings = "Tagging settings";
		$lang->tagging_usercp_update_settings = "Update your tagging settings";
		$lang->tagging_usercp_setting_invalid = "We're sorry but the setting is invalid, retry!";
		$lang->tagging_usercp_setting_updated = "You updated successfully your tagging settings";
		$lang->tagging_who_can = "Who can tag you?";
	}
}

//TAGGING CORE
$plugins->add_hook("datahandler_post_insert_thread_post", "tagging_on_insert_post");
$plugins->add_hook("datahandler_post_insert_post", "tagging_on_insert_post");
$plugins->add_hook("datahandler_post_update", "tagging_on_update_post");
//Ok we can't send PMs via datahandler insert as we don't know the id of the post
$plugins->add_hook("newthread_do_newthread_end", "tagging_on_insert_post_send_pm");
$plugins->add_hook("newreply_do_newreply_end", "tagging_on_insert_post_send_pm");

function tagging_on_insert_post(&$datahandler)
{
	global $mybb, $db, $plugins;
	$message = tagging_strip_post($datahandler->data['message']);
	$found_tags = get_tags_from_message($message);
	$invalid_keys = array($datahandler->data['uid'], $datahandler->data['username']);
	if(!count($found_tags))
		return;
	$found_tags = array_diff($found_tags, $invalid_keys);
	if(count($found_tags))
	{
		$tags = array();
		$uids = array();
		foreach($found_tags as $tag)
		{
			$user = tagging_get_user($tag);
			if(!in_array(intval($user['uid']), $uids))
			{
				if(tagging_can_tag_user($datahandler->data['uid'], $user['uid']))
				{
					$tags[] = array('uid' => $user['uid'], 'username' => $user['username']);
					$uids[] = $user['uid'];
				}
			}
		}
		if(count($uids))
		{
			$datahandler->post_insert_data['tags'] = $db->escape_string(serialize($tags));
		}
	}
}

function tagging_on_insert_post_send_pm()
{
	global $posthandler, $datahandler, $mybb, $plugins;
	$pid = $posthandler->pid;
	$tid = $posthandler->tid;
	if(!$tid)
		$tid = intval($mybb->input['tid']);
	$tags = unserialize(stripslashes($posthandler->post_insert_data['tags']));
	if(is_array($tags) && count($tags))
	{
		$uids = array();
		foreach($tags as $tag)
		{
			$uids[] = $tag['uid'];
		}
		tagging_send_pm($uids, $mybb->user['uid'], $mybb->settings['tagging_tag_pm_title'], str_replace(array("{bburl}", "{posturl}"), array($mybb->settings['bburl'] . "/", get_post_link($pid) . "#pid" . $pid), $mybb->settings['tagging_tag_pm_message']));
		$params = array('pid' => $pid, 'tid' => $tid, 'users' => $uids);
		$plugins->run_hooks("tagging_tag_added", $params);
	}
}

//Updating post could mean less tags, more tags or none of both, basically the function checks:
//1) If new tags are added
//2) If some tags are removed
function tagging_on_update_post(&$datahandler)
{
	global $mybb, $post, $plugins, $db;
	if($post['uid'] != $mybb->user['uid'])
		return;
	$pid = $datahandler->data['pid'];
	$tid = $datahandler->data['tid'];
	$old_tags = unserialize($post['tags']);
	if(!is_array($old_tags))
		$old_tags = array();
	$old_tags_tag = array();
	$old_tags_uid = array();
	foreach($old_tags as $key =>  $t)
	{
		if(preg_match("#".preg_quote("@[{$t['username']}")."#", $post['message']))
		{
			$old_tags_tag[$key] = $t['username'];
			$old_tags_uid[$key] = $t['uid'];
		}
		if(preg_match("#".preg_quote("@[{$t['uid']}")."#", $post['message']))
		{
			$old_tags_tag[$key] = $t['uid'];
			$old_tags_uid[$key] = $t['uid'];
		}
	}
	$tags = get_tags_from_message(tagging_strip_post($datahandler->data['message']));
	$exclude = array($post['uid'], $post['username']);
	$u_tags = array_diff($tags, $exclude, $old_tags_tag);
	$n_tags = array();
	if(count($u_tags))
	{
		$uids = array();
		foreach($u_tags as $tag)
		{
			$user = tagging_get_user($tag);
			if(!in_array(intval($user['uid']), $uids))
			{
				if(tagging_can_tag_user($post['uid'], $user['uid']))
				{
					$uids[] = $user['uid'];
					$n_tags[] = array('uid' => $user['uid'], 'username' => $user['username']);
				}
			}
		}
		if(count($uids))
		{
			$params = array('pid' => $pid, 'tid' => $tid, 'users' => $uids);
			$plugins->run_hooks("tagging_tag_added", $params);
			tagging_send_pm($uids, $post['uid'], $mybb->settings['tagging_tag_pm_title'], str_replace(array("{bburl}", "{posturl}"), array($mybb->settings['bburl'] . "/", get_post_link($pid) . "#pid" . $pid), $mybb->settings['tagging_tag_pm_message']));
		}
	}
	$r_tags = array_diff($old_tags_tag, $tags);
	$r_uids = array();
	foreach($r_tags as $key => $tag)
	{
		$r_uids[] = $old_tags_uid[$key];
		unset($old_tags[$key]);
	}
	if(count($r_uids))
	{
		$params = array('pid' => $pid, 'tid' => $tid, 'users' => $uids);
		$plugins->run_hooks("tagging_tag_removed", $params);
	}
	$data = array_merge($old_tags, $n_tags);
	if(count($data))
		$datahandler->post_update_data['tags'] = $db->escape_string(serialize($data));
	else
		$datahandler->post_update_data['tags'] = "";
}

//What if one or more posts are merged? We've to merge tags too!
//For threads itsn't needed as the tags are a post's field
$plugins->add_hook("moderation_start", "tagging_on_merge_posts");

function tagging_on_merge_posts()
{
	global $db, $mybb, $plist, $plugins;
	if(THIS_SCRIPT == "moderation.php" && $mybb->input['action'] == "do_multimergeposts")
	{
		verify_post_check($mybb->input['my_post_key']);
		$mergepost = $mybb->input['mergepost'];
		$pids = array();
		foreach($mergepost as $pid => $yes)
		{
			$pids[] = intval($pid);
		}
		if(count($pids) <= 1)
			return;
		if(is_moderator_by_pids($pids, "canmanagethreads"))
		{
			$master_pid = $pids[0];
			$pidsin = "'" . implode("','", $pids) . "'";
			$query = $db->simple_select("posts", "tags", "pid IN ({$pidsin})");
			$uids = array();
			$tags = array();
			while($post = $db->fetch_array($query))
			{
				$t = unserialize($post['tags']);
				if(is_array($t) && count($t) > 0)
				{
					foreach($t as $tag)
					{
						if(!in_array($t['uid'], $uids))
						{
							$uids[] = $tag['uid'];
							$tags[] = $tag;
						}
					}
				}
			}
			if(count($tags))
			{
				$db->update_query("posts", array('tags' => $db->escape_string(serialize($tags))), "pid='{$master_pid}'");
				$post = get_post($master_pid);
				$params = array('pid' => $master_pid, 'tid' => $post['tid'], 'users' => $uids);
				$plugins->run_hooks("tagging_tag_merged", $params);
			}
		}
	}
}

//Hook threads deletion and post deletion so i can launch tagging_tag_removed hook!
$plugins->add_hook("class_moderation_delete_thread_start", "tagging_on_delete_thread");
$plugins->add_hook("class_moderation_delete_post_start", "tagging_on_delete_post");

function tagging_on_delete_thread($tid)
{
	global $db, $plugins;
	$tid = intval($tid);
	$query = $db->simple_select("posts", "pid,tags", "tid='{$tid}'");
	if($db->num_rows($query))
	{
		while($post = $db->fetch_array($query))
		{
			$tags = unserialize($post['tags']);
			if(is_array($tags) && count($tags))
			{
				$uids = array();
				foreach($tags as $tag)
					$uids[] = intval($tag['uid']);
				$params = array('pid' => $post['pid'], 'tid' => $tid, 'users' => $uids);
				$plugins->run_hooks("tagging_tag_removed", $params);
			}
		}
	}
}

function tagging_on_delete_post($pid)
{
	global $db, $plugins;
	$pid = intval($pid);
	$query = $db->simple_select("posts", "tid,tags", "pid='{$pid}'");
	if($db->num_rows($query))
	{
		$post = $db->fetch_array($query);
		$tags = unserialize($post['tags']);
		if(is_array($tags) && count($tags))
		{
			$uids = array();
			foreach($tags as $tag)
				$uids[] = intval($tag['uid']);
			$params = array('pid' => $pid, 'tid' => $post['tid'], 'users' => $uids);
			$plugins->run_hooks("tagging_tag_removed", $params);
		}
	}
}

//TAGGING PARSING POSTS
$plugins->add_hook("postbit", "tagging_show_post"); //If you wonder why i don't use parse_message hook is because i want to be sure that the plugin works only on posts!
$plugins->add_hook("archive_thread_post", "tagging_show_post_archive");

function tagging_parse($message, $tags)
{
	if(!$tags) return $message;
	if(is_string($tags))
		$tags = unserialize($tags);
	if(is_array($tags) && count($tags) > 0)
	{
		$replacement = array();
		foreach($tags as $tag)
		{
			$replacements["@[{$tag['uid']}]"] = tagging_get_tag_text($tag['uid'], $tag['username']);
			$replacements["@[{$tag['username']}]"] = tagging_get_tag_text($tag['uid'], $tag['username']);
		}
		return strtr($message, $replacements);
	}
	return $message;
}

function tagging_show_post($post)
{
	$post['message'] = tagging_parse($post['message'], $post['tags']);
	return $post;
}

function tagging_show_post_archive()
{
	global $post;
	$post['message'] = tagging_parse($post['message'], $post['tags']);
}

/* 
 * Little trick for supporting AJAX Editing, the problem is that postbit hook is not called as it hasn't to return the postbit itself so what do we do?
 * We intercept parse_message event, to be sure that it works after post insertion we add the hook only after that post is inserted
 */
global $mybb;
if(THIS_SCRIPT == "xmlhttp.php" && $mybb->input['do'] == "update_post")
{
	$plugins->add_hook("datahandler_post_update", "tagging_trick_first");
	
	function tagging_trick_first($datahandler)
	{
		global $plugins;
		$plugins->add_hook("parse_message", "tagging_trick_second");
	}
	
	function tagging_trick_second($message)
	{
		global $post, $mybb, $db, $post, $plugins;
		$query = $db->simple_select("posts", "tags", "pid='{$post['pid']}'");
		$result = $db->fetch_array($query);
		$message = tagging_parse($message, $result['tags']);
		return $message;
	}
	
}

//We want to display tags also in search results
$plugins->add_hook("search_results_post", "tagging_search_result_post");

function tagging_search_result_post()
{
	global $prev, $post;
	$prev = tagging_parse($prev, $post['tags']);
}

//UserCP Settings for tagging

$plugins->add_hook("usercp_start", "tagging_usercp");

function tagging_usercp()
{
	extract($GLOBALS); //Little trick for getting all variables in global scope, useful for templates
	if($mybb->input['action'] == "tags")
	{
		tagging_load_lang();
		if(isset($mybb->input['submit']))
		{
			$val = intval($mybb->input['allowtags']);
			if(is_numeric($mybb->input['allowtags']) && ($val > -1 && $val < 3))
			{
				$db->update_query("users", array('allowtags' => $db->escape_string($mybb->input['allowtags'])), "uid='" . $mybb->user['uid'] . "'");
				$mybb->user['allowtags'] = $mybb->input['allowtags'];
				redirect("usercp.php", $lang->tagging_usercp_setting_updated);
			}
			else
			{
				$error = inline_error($lang->tagging_usercp_setting_invalid);
			}
		}
		$c = 'checked="checked" ';
		switch($mybb->user['allowtags'])
		{
			case 0:
				$notagcheck = $c;
				break;
			case 1:
				$allcheck = $c;
				break;
			case 2:
				$buddycheck = $c;
				break;
		}
		eval("\$page = \"".$templates->get("usercp_tags")."\";");
		output_page($page);
	}
}

?>