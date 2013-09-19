<?php

/*

AUTHOR'S MESSAGE:

I needed to code this, so I searched for this. Couldn't find any, so I saw one about threads by Starpaul20. I used his source, but modified it so that it works with posts instead.
I give all credits to Starpaul20, but since I made it works for "Posts" I am releasing this with my name. Hopefully Im not breaking any copyright since Im giving this for free.

~

SECURITY / TESTING:

I've tested this, it works. 

*/

// Security
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Hooks
$plugins->add_hook('newreply_start', 'limitposts_run');
$plugins->add_hook('newreply_do_newreply_start', 'limitposts_run');
$plugins->add_hook('admin_formcontainer_output_row', 'limitposts_usergroup_permission');
$plugins->add_hook('admin_user_groups_edit_commit', 'limitposts_usergroup_permission_commit');

function limitposts_info()
{
	return array(
		"name"			=> "Limit number of Posts",
		"description"	=> "Allows you to limit the number of posts that a user in a usergroup can post in a day.",
		"website"		=> "http://blackhatswag.net",
		"author"		=> "Dealin",
		"authorsite"	=> "http://shadyforums.net",
		"version"		=> "1.0",
    	"guid"			=> "678435897345746346342",
		"compatibility" => "16*"
	);
}

function limitposts_activate()
{
	global $db, $cache;
	$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups ADD maxpostsday INT(3) NOT NULL DEFAULT '100'");

	$cache->update_usergroups();
}

function limitposts_deactivate()
{
	global $db, $cache;
	$db->query("ALTER TABLE ".TABLE_PREFIX."usergroups DROP maxpostsday");

	$cache->update_usergroups();
}

function limitposts_run()
{
	global $mybb, $db, $lang;
	$lang->load("limitposts");

	if($mybb->usergroup['maxpostsday'] > 0)
	{
		$query = $db->simple_select("posts", "COUNT(*) AS post_count", "uid='{$mybb->user['uid']}' AND dateline >='".(TIME_NOW - (60*60*24))."'");
		$post_count = $db->fetch_field($query, "post_count");
		if($post_count >= $mybb->usergroup['maxpostsday'])
		{
			$lang->error_max_posts_day = $lang->sprintf($lang->error_max_posts_day, $mybb->usergroup['maxpostsday']);
			error($lang->error_max_posts_day);
		}
	}
}

// Admin CP permission control
function limitposts_usergroup_permission($above)
{
	global $mybb, $lang, $form;
	$lang->load("limitposts");

	if($above['title'] == $lang->posting_rating_options && $lang->posting_rating_options)
	{
		$above['content'] .= "<div class=\"group_settings_bit\">{$lang->maxpostsday}:<br /><small>{$lang->maxpostsday_desc}</small><br /></div>".$form->generate_text_box('maxpostsday', $mybb->input['maxpostsday'], array('id' => 'maxpostsday', 'class' => 'field50'));
	}
}

function limitposts_usergroup_permission_commit()
{
	global $mybb, $updated_group;
	$updated_group['maxpostsday'] = intval($mybb->input['maxpostsday']);
}

?>