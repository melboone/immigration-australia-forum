<?php
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}


function postviaemail_info()
{
	return array(
		"name"			=> "Post Via Email",
		"description"	=> "Allow your users to post by sending email you specify.",
		"website"		=> "http://wbcu.tk/PVE",
		"author"		=> "PJGIH",
		"authorsite"	=> "http://wbcu.tk/PJGIH",
		"version"		=> "0.2",
		"guid"          => "",
	);
}


function postviaemail_activate()
{
  global $db,$plugins,$cache,$mybb;
  $pve_group = array(
		'gid'			=> 'NULL',
		'name'			=> 'pvegroup',
		'title'			=> 'Post Via Email Settings',
		'description'	=> 'Settings for the Post Via Email plugin.  To edit when the script is run, go to <a href="index.php?module=tools-tasks">tasks</a>.',
		'disporder'		=> "1",
		'isdefault'		=> 'no',
	);
	$db->insert_query('settinggroups', $pve_group);
	$gid = $db->insert_id();
	
	$pve_setting = array(
		'name'			=> 'pve_imap_user',
		'title'			=> 'IMAP username',
		'description'	=> 'The IMAP username of the email users will send email to.',
		'optionscode'	=> 'text',
		'value'			=> 'example@gmail.com',
		'disporder'		=> 1,
		'gid'			=> intval($gid),
	);
	$db->insert_query('settings', $pve_setting);

	$pve_setting = array(
		'name'			=> 'pve_imap_pass',
		'title'			=> 'IMAP Password',
		'description'	=> 'The IMAP password of the above email',
		'optionscode'	=> 'text',
		'value'			=> 'password',
		'disporder'		=> 2,
		'gid'			=> intval($gid),
	);
	$db->insert_query('settings', $pve_setting);	

	$pve_setting = array(
		'name'			=> 'pve_imap_server',
		'title'			=> 'IMAP Server',
		'description'	=> 'The IMAP server of the above email.  DO NOT add http:// before the server.',
		'optionscode'	=> 'text',
		'value'			=> 'imap.gmail.com',
		'disporder'		=> 3,
		'gid'			=> intval($gid),
	);
	$db->insert_query('settings', $pve_setting);		

	$pve_setting = array(
		'name'			=> 'pve_imap_port',
		'title'			=> 'IMAP Port',
		'description'	=> 'The IMAP port of the above email',
		'optionscode'	=> 'text',
		'value'			=> '465',
		'disporder'		=> 4,
		'gid'			=> intval($gid),
	);
	$db->insert_query('settings', $pve_setting);		
			
			
	$pve_setting = array(
		'name'			=> 'pve_imap_folder',
		'title'			=> 'IMAP Folder',
		'description'	=> 'The IMAP folder number you want to retreive emails from.  Default = 0 (Inbox)',
		'optionscode'	=> 'text',
		'value'			=> '0',
		'disporder'		=> 5,
		'gid'			=> intval($gid),
	);
	$db->insert_query('settings', $pve_setting);	
	
	$pve_setting = array(
		'name'			=> 'pve_forum',
		'title'			=> 'Forum',
		'description'	=> 'What forum the thread should be posted in if the MyCodes [fid] or [fname] are not specified or invalid.',
		'optionscode'	=> 'text',
		'value'			=> '2',
		'disporder'		=> 6,
		'gid'			=> intval($gid),
	);
	$db->insert_query('settings', $pve_setting);

	

	rebuild_settings();

	//add task
  require_once  MYBB_ROOT."/inc/functions_task.php";
	$new_task = array(
		"title" => $db->escape_string("Post Via Email"),
		"description" => $db->escape_string("Checks the specified email for new mail and attempts to post it"),
		"file" => $db->escape_string("postviaemail"),
		"minute" => $db->escape_string("0,10,20,30,40,50"),
		"hour" => $db->escape_string("*"),
		"day" => $db->escape_string("*"),
		"month" => $db->escape_string("*"),
		"weekday" => $db->escape_string("*"),
		"enabled" => intval("1"),
		"logging" => intval("1")
	);
   
	$new_task['nextrun'] = fetch_next_run($new_task);
	$tid = $db->insert_query("tasks", $new_task);
	$cache->update_tasks();
               
	$plugins->run_hooks("admin_tools_tasks_add_commit");
   
	// Log admin action
	log_admin_action($tid, $mybb->input['title']);
        

}


function postviaemail_deactivate()
{
  global $db,$plugins,$cache;
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN ('pve_imap_user', 'pve_imap_pass','pve_imap_server','pve_imap_port','pve_imap_folder','pve_forum')");
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='pvegroup'");
	rebuild_settings(); 


	//delete task
	$plugins->run_hooks("admin_tools_tasks_delete");
	  require_once  MYBB_ROOT."/inc/functions_task.php";
	$query = $db->simple_select("tasks", "*", "file='postviaemail'");
	$task = $db->fetch_array($query);

	// Delete the task & any associated task log entries
	$db->delete_query("tasks", "tid='{$task['tid']}'");
	$db->delete_query("tasklog", "tid='{$task['tid']}'");

	// Fetch next task run
	$cache->update_tasks();
	
	$plugins->run_hooks("admin_tools_tasks_delete_commit");

	// Log admin action
	log_admin_action($task['tid'], $task['title']);
}

?>
