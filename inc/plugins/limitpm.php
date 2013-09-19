<?php
/**
 * Limit number of PMs
 * Copyright 2010 Starpaul20
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Tell MyBB when to run the hooks
$plugins->add_hook("private_send_do_send", "limitpm_run");
$plugins->add_hook("private_send_start", "limitpm_run");

$plugins->add_hook("admin_formcontainer_output_row", "limitpm_usergroup_permission");
$plugins->add_hook("admin_user_groups_edit_commit", "limitpm_usergroup_permission_commit");

// The information that shows up on the plugin manager
function limitpm_info()
{
	return array(
		"name"				=> "Limit number of PMs",
		"description"		=> "Allows you to limit the number of Private Messages that a user in a usergroup can send in a day.",
		"website"			=> "http://galaxiesrealm.com/index.php",
		"author"			=> "Starpaul20",
		"authorsite"		=> "http://galaxiesrealm.com/index.php",
		"version"			=> "2.0.3",
		"guid"				=> "c13b1bac7595d16c854a60918453499a",
		"compatibility"		=> "16*"
	);
}

// This function runs when the plugin is activated.
function limitpm_activate()
{
	global $db, $cache;
	$db->add_column("usergroups", "maxpmsday", "int(3) NOT NULL default '10'");

	$cache->update_usergroups();
}

// This function runs when the plugin is deactivated.
function limitpm_deactivate()
{
	global $db, $cache;
	if($db->field_exists("maxpmsday", "usergroups"))
	{
		$db->drop_column("usergroups", "maxpmsday");
	}

	$cache->update_usergroups();
}

// Limit Private Messages per day
function limitpm_run()
{
	global $mybb, $db, $lang;
	$lang->load("limitpm");

	// Check group limits
	if($mybb->usergroup['maxpmsday'] > 0)
	{
		$query = $db->simple_select("privatemessages", "COUNT(*) AS sent_count", "fromid='".intval($mybb->user['uid'])."' AND folder != 2 AND dateline >= '".(TIME_NOW - (60*60*24))."'");
		$sent_count = $db->fetch_field($query, "sent_count");
		if($sent_count >= $mybb->usergroup['maxpmsday'])
		{
			$lang->error_max_pms_day = $lang->sprintf($lang->error_max_pms_day, $mybb->usergroup['maxpmsday']);
			error($lang->error_max_pms_day);
		}
	}
}

// Admin CP permission control
function limitpm_usergroup_permission($above)
{
	global $mybb, $lang, $form;
	$lang->load("limitpm");

	if($above['title'] == $lang->private_messaging && $lang->private_messaging)
	{
		$above['content'] .= "<div class=\"group_settings_bit\">{$lang->maxpmday}:<br /><small>{$lang->maxpmday_desc}</small><br /></div>".$form->generate_text_box('maxpmsday', $mybb->input['maxpmsday'], array('id' => 'maxpmsday', 'class' => 'field50'));
	}

	return $above;
}

function limitpm_usergroup_permission_commit()
{
	global $mybb, $updated_group;
	$updated_group['maxpmsday'] = intval($mybb->input['maxpmsday']);
}

?>