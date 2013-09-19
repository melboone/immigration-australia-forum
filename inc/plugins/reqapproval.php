<?php

$plugins->add_hook('newreply_do_newreply_start', 'reqapp_do');
$plugins->add_hook('newthread_do_newthread_start', 'reqapp_do');

function reqapproval_info()
{
	return array(
		'name'			=> 'Required Approval',
		'description'	=> 'Causes new users posts to require approval, until a specified amount of the users posts have been approved.',
		'website'		=> 'http://www.coderzplanet.net',
		'author'		=> 'Jammerx2',
		'authorsite'	=> 'http://www.coderzplanet.net',
		'version'		=> '1.1',
		'guid'        => '4d65a68dcd928c545b785279664a40ef'
	);
}

function reqapproval_activate()
{
global $db, $mybb;

	$reqapp_group = array(
		"gid"			=> "NULL",
		"name"			=> "reqapproval",
		"title" =>"Required Approval Settings",
		"description"	=> "Settings for the Required Approval plugin.",
		"disporder"		=> "1234567890",
		"isdefault"		=> "no",
	);

	$db->insert_query("settinggroups", $reqapp_group);
	$gid = $db->insert_id();

	$reqapp_setting = array(
		"sid"			=> "NULL",
		'name'			=> 'reqapp',
		'title'			=> 'Required Approvals',
		'description'	=> 'How many approved posts must a user have in order to post freely.',
		'optionscode'	=> 'text',
		'value'			=> '1',
		'disporder'		=> '1',
		'gid'			=> intval($gid),
	);

	$db->insert_query('settings', $reqapp_setting);
	
	$reqapp_setting = array(
		"sid"			=> "NULL",
		'name'			=> 'reqappforums',
		'title'			=> 'Forums',
		'description'	=> 'IDs of forums this will be active in, seperated by comma. (Leave blank for all.)',
		'optionscode'	=> 'text',
		'value'			=> '',
		'disporder'		=> '1',
		'gid'			=> intval($gid),
	);

	$db->insert_query('settings', $reqapp_setting);
	
	rebuild_settings();

}

function reqapproval_deactivate()
{
global $db, $mybb;
$db->delete_query("settinggroups","name='reqapproval'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='reqapp'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='reqappforums'");
}


function reqapp_do() {
global $mybb, $fid;
if($mybb->settings['reqapp'] > $mybb->user['postnum'] && (in_array($fid, explode(',', $mybb->settings['reqappforums'])) || !$mybb->settings['reqappforums'])) {
$mybb->user['moderateposts'] = 1;
}
}


?>