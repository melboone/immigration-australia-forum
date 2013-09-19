<?php
/**
 * MySupport 0.4.2
 * © MattRogowski 2010
 * http://mattrogowski.co.uk
 * You may edit for your own personal forum but must not redistribute it in any form without my permission.

 * Features
  ** Choose which forums to enable MySupport in.
  ** Mark threads as solved.
  ** Mark threads as technical.
   *** Alert of technical threads in header.
   *** List of technical threads in Mod CP.
   *** Option to hide a technical status from people who can't mark as technical; display technical threads as simply not solved to regular users.
  ** Display the status of threads as either an image, or as text. Configurable on a per-user basis as well as a global basis.
  ** Assign threads.
   *** Alert of assigned threads in header.
   *** List of assigned threads in User CP.
   *** Icon on forum display to see what threads have been assigned and what threads are assigned to you.
   *** PM/subscribe to thread when assigned.
  ** Give threads priorities.
   *** Highlighted on forum display in colour representing priority.
  ** Mark the best answer in the thread.
   *** Only thread author able to do this.
   *** Highlights the best answer and includes quick access link to jump straight to best answer, both at the top of the thread and on the forum display.
  ** List of users' support threads in User CP.
  ** Highlight staff responses.
  ** Deny users support.
   *** Configurable reasons.
   *** Unable to make threads in MySupport forum.
  ** Configure a points system to receive points for MySupport actions.
   *** Receive points for having a post marked as the best answer.
 * Settings
  ** Settings are added when installed and removed when uninstalled. Activating and deactivating does nothing to settings.
  ** When mysupport_activate() is called, mysupport_upgrade() is called. If settings need to be added/removed it will add/remove them, and rebuild the sort orders.
  ** This is done so you will never lose the values of settings when you deactivate/upgrade and settings will be automatically updated.
 * Templates
  ** Templates are added when installed and removed when uninstalled. Activating and deactivating does nothing to templates, apart from making changes to existing templates.
  ** When mysupport_activate() is called, mysupport_upgrade() is called. If any of the upgrade processes had changed templates, it will delete only the master copies of MySupport templates, leaving any edited ones, and then reimport the new masters.
  ** This is done so you will never lose template edits when you deactivate/upgrade and templates will be automatically updated.
 * Upgrading
  ** mysupport_activate() calls mysupport_upgrade() which deals with upgrades between versions.
   *** The new version is defined at the top of the file, current version would have been cached upon deactivation.
   *** Goes through each upgrade code block and sees if it needs to be executed.
    **** Database changes performed whilst executing each code block.
    **** New settings added to an array, all inserted at once after all upgrade blocks have been checked.
    **** Templates reimported if any step requires it after all upgrade blocks have been checked.
    **** Any deleted settings or templates added to array in each upgrade block and deleted at once at the end.
    **** New version cached, upgrade done.
  ** Means no user intervention needed and nothing gets lost in the process.
**/

if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}

define("MYSUPPORT_VERSION", "0.4.2");

$plugins->add_hook('showthread_start', 'mysupport_showthread');
$plugins->add_hook('forumdisplay_start', 'mysupport_forumdisplay_searchresults');
$plugins->add_hook('search_results_start', 'mysupport_forumdisplay_searchresults');
$plugins->add_hook('forumdisplay_thread', 'mysupport_threadlist_thread');
$plugins->add_hook('search_results_thread', 'mysupport_threadlist_thread');
$plugins->add_hook('moderation_start', 'mysupport_do_inline_thread_moderation');
$plugins->add_hook('newthread_start', 'mysupport_newthread');
$plugins->add_hook('usercp_start', 'mysupport_usercp_options');
$plugins->add_hook('postbit', 'mysupport_postbit');
$plugins->add_hook('global_start', 'mysupport_notices');
$plugins->add_hook('modcp_start', 'mysupport_navoption');
$plugins->add_hook('usercp_menu_built', 'mysupport_navoption');
$plugins->add_hook('modcp_start', 'mysupport_thread_list');
$plugins->add_hook('usercp_start', 'mysupport_thread_list');
$plugins->add_hook('modcp_start', 'mysupport_modcp_support_denial');
$plugins->add_hook("fetch_wol_activity_end", "mysupport_friendly_wol");
$plugins->add_hook("build_friendly_wol_location_end", "mysupport_build_wol");
$plugins->add_hook("admin_config_plugins_activate_commit", "mysupport_settings_redirect");
$plugins->add_hook("admin_page_output_footer", "mysupport_settings_footer");
$plugins->add_hook("admin_config_menu", "mysupport_admin_config_menu");
$plugins->add_hook("admin_config_action_handler", "mysupport_admin_config_action_handler");
$plugins->add_hook("admin_config_permissions", "mysupport_admin_config_permissions");

global $templatelist;
if(isset($templatelist))
{
	$templatelist .= ',';
}
$mysupport_templates = mysupport_templates();
$mysupport_templates = implode(",", $mysupport_templates);
$templatelist .= $mysupport_templates;

/**
 * These are just here for when I'm debugging or updating templates, it just re-does all template stuff at runtime
**/
if(!defined("IN_ADMINCP"))
{
	//mysupport_do_templates(0, true);
	//mysupport_do_templates(1);
	//mysupport_template_edits(0);
	//mysupport_template_edits(1);
}

function mysupport_info()
{
	return array(
		'name' => 'MySupport',
		'description' => 'Add features to your forum to help with giving support. Allows you to mark a thread as solved or technical, assign threads to users, give threads priorities, mark a post as the best answer in a thread, and more to help you run a support forum.',
		'website' => 'http://mattrogowski.co.uk/mybb/plugins/plugin/mysupport',
		'author' => 'MattRogowski',
		'authorsite' => 'http://mattrogowski.co.uk/mybb/',
		'version' => MYSUPPORT_VERSION,
		'compatibility' => '16*',
		'guid' => '3ebe16a9a1edc67ac882782d41742330'
	);
}

function mysupport_install()
{
	global $db, $cache, $mysupport_uninstall_confirm_override;
	
	// this is so we override the confirmation when trying to uninstall, so we can just run the uninstall code
	$mysupport_uninstall_confirm_override = true;
	mysupport_uninstall();
	
	mysupport_table_columns(1);
	
	if(!$db->table_exists("mysupport"))
	{
		$db->write_query("
			CREATE TABLE  " . TABLE_PREFIX . "mysupport (
				`mid` SMALLINT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`type` VARCHAR(20) NOT NULL ,
				`name` VARCHAR(255) NOT NULL ,
				`description` VARCHAR(500) NOT NULL,
				`extra` VARCHAR(255) NOT NULL
			) ENGINE = MYISAM ;
		");
	}
	
	$settings_group = array(
		"name" => "mysupport",
		"title" => "MySupport Settings",
		"description" => "Settings for the MySupport plugin.",
		"disporder" => "28",
		"isdefault" => "no"
	);
	$db->insert_query("settinggroups", $settings_group);
	
	mysupport_import_settings();
	
	mysupport_do_templates(1);
	
	// insert some default priorities
	$priorities = array();
	$priorities[] = array(
		"type" => "priority",
		"name" => "Low",
		"description" => "Low priority threads.",
		"extra" => "ADCBE7"
	);
	$priorities[] = array(
		"type" => "priority",
		"name" => "Normal",
		"description" => "Normal priority threads.",
		"extra" => "D6ECA6"
	);
	$priorities[] = array(
		"type" => "priority",
		"name" => "High",
		"description" => "High priority threads.",
		"extra" => "FFF6BF"
	);
	$priorities[] = array(
		"type" => "priority",
		"name" => "Urgent",
		"description" => "Urgent priority threads.",
		"extra" => "FFE4E1"
	);
	foreach($priorities as $priority)
	{
		$db->insert_query("mysupport", $priority);
	}
	
	// set some values for the staff groups
	$update = array(
		"canmarksolved" => 1,
		"canmarktechnical" => 1,
		"canseetechnotice" => 1,
		"canassign" => 1,
		"canbeassigned" => 1,
		"cansetpriorities" => 1,
		"canseepriorities" => 1,
		"canmanagesupportdenial" => 1
	);
	$db->update_query("usergroups", $update, "gid IN ('3','4','6')");
	
	change_admin_permission("config", "mysupport", 1);
	
	$cache->update_forums();
	$cache->update_usergroups();
	$cache->update("mysupport_version", MYSUPPORT_VERSION);
}

function mysupport_is_installed()
{
	global $db;
	
	return $db->table_exists("mysupport");
}

function mysupport_uninstall()
{
	global $mybb, $db, $cache, $mysupport_uninstall_confirm_override;
	
	// this is a check to make sure we want to uninstall
	// if 'No' was chosen on the confirmation screen, redirect back to the plugins page
	if($mybb->input['no'])
	{
		admin_redirect("index.php?module=config-plugins");
	}
	else
	{
		// there's a post request so we submitted the form and selected yes
		// or the confirmation is being overridden by the installation function; this is for when mysupport_uninstall() is called at the start of mysupport_install(), we just want to execute the uninstall code at this point
		if($mybb->request_method == "post" || $mysupport_uninstall_confirm_override === true)
		{
			mysupport_table_columns(-1);
			
			if($db->table_exists("mysupport"))
			{
				$db->drop_table("mysupport");
			}
			
			$db->delete_query("settinggroups", "name = 'mysupport'");
			$settings = mysupport_setting_names();
			$settings = "'" . implode("','", array_map($db->escape_string, $settings)) . "'";
			// have to use $db->escape_string above instead of around $settings directly because otherwise it escapes the ' around the names, which are important
			$db->delete_query("settings", "name IN ({$settings})");
			
			rebuild_settings();
			
			mysupport_do_templates(0, false);
			
			$cache->update_forums();
			$cache->update_usergroups();
		}
		// need to show the confirmation
		else
		{
			global $lang, $page;
			
			$lang->load("config_mysupport");
			$page->output_confirm_action("index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=mysupport&my_post_key={$mybb->post_code}", $lang->mysupport_uninstall_warning);
		}
	}
}

function mysupport_activate()
{
	mysupport_template_edits(0);
	
	mysupport_template_edits(1);
	
	mysupport_upgrade();
}

function mysupport_deactivate()
{
	global $cache;
	
	mysupport_template_edits(0);
	
	$cache->update("mysupport_version", MYSUPPORT_VERSION);
}

// function called upon activation to check if anything needs to be upgraded
// upgrade process is deactivate, upload new files, activate - this function checks for the old version upon re-activation and performs any necessary upgrades
// if settings/templates need to be added/edited/deleted, it'd be taken care of here
// would also deal with any database changes etc
function mysupport_upgrade()
{
	global $mybb, $db, $cache;
	
	$old_version = $cache->read("mysupport_version");
	
	// only need to run through this if the version has actually changed
	if(!empty($old_version) && $old_version != MYSUPPORT_VERSION)
	{
		$settings_changed = false;
		$templates_changed = false;
		$table_columns_added = false;
		$deleted_settings = array();
		$deleted_templates = array();
		
		// go through each upgrade process; versions are only listed here if there were changes FROM that version to the next
		// it will go through the ones it needs to, mark what standard changes (settings, templates, table columns) are necessary, and make any extra changes necessary
		if($old_version <= 0.1)
		{
			// this is where stuff would go to upgrade from 0.1
			$settings_changed = true;
		}
		if($old_version <= 0.2)
		{
			$templates_changed = true;
		}
		if($old_version <= 0.3)
		{
			$settings_changed = true;
			$templates_changed = true;
			// made some mistakes with the original table column additions, 3 of the fields weren't long enough... I do apologise
			$db->modify_column("threads", "statusuid", "INT(10) NOT NULL DEFAULT '0'");
			$db->modify_column("users", "deniedsupportreason", "INT(5) NOT NULL DEFAULT '0'");
			$db->modify_column("users", "deniedsupportuid", "INT(10) NOT NULL DEFAULT '0'");
			// maybe 255 isn't big enough for this after all
			$db->modify_column("mysupport", "description", "VARCHAR(500) NOT NULL");
		}
		
		if($settings_changed)
		{
			// reimport the settings to add any new ones and refresh the current ones
			mysupport_import_settings();
		}
		if($templates_changed)
		{
			// remove the current templates, but only the master versions
			mysupport_do_templates(0, true);
			
			// re-import the master templates
			mysupport_do_templates(1);
		}
		if($table_columns_added)
		{
			// I could put this function directly in the upgrade steps for each version above if columns are added in that upgrade step
			// even if 5 upgrade steps had columns added, it would only add the columns once as it'd just add all of them in the first function call and do nothing during subsequent calls
			// but to save running through the function more times than is necessary, just do it once here
			mysupport_table_columns(1);
		}
		if(!empty($deleted_settings))
		{
			$deleted_settings = "'" . implode("','", array_map($db->escape_string, $deleted_settings)) . "'";
			// have to use $db->escape_string above instead of around $deleted_settings directly because otherwise it escapes the ' around the names, which are important
			$db->delete_query("settings", "name IN ({$deleted_settings})");
			
			mysupport_update_setting_orders();
			
			rebuild_settings();
		}
		if(!empty($deleted_templates))
		{
			$deleted_templates = "'" . implode("','", array_map($db->escape_string, $deleted_templates)) . "'";
			// have to use $db->escape_string above instead of around $deleted_templates directly because otherwise it escapes the ' around the names, which are important
			$db->delete_query("templates", "title IN ({$deleted_templates})");
		}
		
		// now we can update the cache with the new version
		$cache->update("mysupport_version", MYSUPPORT_VERSION);
	}
} 

function mysupport_table_columns($action = 0)
{
	global $db;
	
	$mysupport_columns = array(
		"forums" => array(
			"mysupport" => array(
				"size" => 1
			),
			"mysupportmove" => array(
				"size" => 1
			)
		),
		"threads" => array(
			"status" => array(
				"size" => 1
			),
			"statusuid" => array(
				"size" => 10
			),
			"statustime" => array(
				"size" => 10
			),
			"bestanswer" => array(
				"size" => 10
			),
			"assign" => array(
				"size" => 10
			),
			"assignuid" => array(
				"size" => 10
			),
			"priority" => array(
				"size" => 5
			)
		),
		"users" => array(
			"deniedsupport" => array(
				"size" => 1
			),
			"deniedsupportreason" => array(
				"size" => 5
			),
			"deniedsupportuid" => array(
				"size" => 10
			),
			"mysupportdisplayastext" => array(
				"size" => 1
			)
		),
		"usergroups" => array(
			"canmarksolved" => array(
				"size" => 1
			),
			"canmarktechnical" => array(
				"size" => 1
			),
			"canseetechnotice" => array(
				"size" => 1
			),
			"canassign" => array(
				"size" => 1
			),
			"canbeassigned" => array(
				"size" => 1
			),
			"cansetpriorities" => array(
				"size" => 1
			),
			"canseepriorities" => array(
				"size" => 1
			),
			"canmanagesupportdenial" => array(
				"size" => 1
			)
		)
	);
	
	foreach($mysupport_columns as $table => $columns)
	{
		foreach($columns as $column => $details)
		{
			// this is called when installing or upgrading
			// if installing, all columns get added, if upgrading, it'll add any new columns
			if($action == 1)
			{
				if(!$db->field_exists($column, $table))
				{
					// most of the columns are INT with a default of 0, so only specify type/default in the array above if it's different, else use int/0
					if(!$details['type'])
					{
						$details['type'] = "int";
					}
					if(!$details['default'])
					{
						$details['default'] = 0;
					}
					$db->add_column($table, $column, $db->escape_string($details['type']) . " (" . $db->escape_string($details['size']) . ") NOT NULL DEFAULT " . $db->escape_string($details['default']));
				}
			}
			// this is called when uninstalling, to remove all columns
			elseif($action == -1)
			{
				if($db->field_exists($column, $table))
				{
					$db->drop_column($table, $column);
				}
			}
		}
	}
}

function mysupport_setting_names()
{
	return array(
		'enablemysupport',
		'mysupportdisplaytype',
		'mysupportdisplaytypeuserchange',
		'mysupportdisplayto',
		'mysupportauthor',
		'mysupportclosewhensolved',
		'mysupportmoveredirect',
		'mysupportunsolve',
		'enablemysupportbestanswer',
		'enablemysupporttechnical',
		'mysupporthidetechnical',
		'mysupporttechnicalnotice',
		'enablemysupportassign',
		'mysupportassignpm',
		'mysupportassignsubscribe',
		'enablemysupportpriorities',
		'enablemysupportsupportdenial',
		'mysupportmodlog',
		'mysupporthighlightstaffposts',
		'mysupportthreadlist',
		'mysupportstats',
		'mysupportrelativetime',
		'mysupportpointssystem',
		'mysupportpointssystemname',
		'mysupportpointssystemcolumn',
		'mysupportbestanswerpoints'
	);
}

function mysupport_settings_info()
{
	$settings = array();
	$settings[] = array(
		"name" => "enablemysupport",
		"title" => "Global On/Off setting.",
		"description" => "Turn MySupport on or off here.",
		"optionscode" => "onoff",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportdisplaytype",
		"title" => "How to display the status of a thread??",
		"description" => "'Image' will show a red, green, or blue icon depending on whether a thread is unsolved, solved, or marked as technical. If '[Solved]' is selected, the text '[Solved]' will be displayed before the thread titles (or '[Technical]' if marked as such), while not editing the thread title itself. 'Image' is default as it is intended to be clear but unobtrusive. This setting will be overwridden by a user's personal setting if you've let them change it with the setting below; to force the current setting to all current users, <a href='index.php?module=config-mysupport&amp;action=forcedisplaytype'>click here</a>.",
		"optionscode" => "radio
image=Image
text=Text",
		"value" => "image"
	);
	$settings[] = array(
		"name" => "mysupportdisplaytypeuserchange",
		"title" => "Let users change how threads are displayed??",
		"description" => "Do you want to allow users to change how the status is displayed?? If yes, they will have an setting in their User CP Options to choose how the status will be shown, which will override the setting you choose above.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportdisplayto",
		"title" => "Who should the status of a thread be shown to??",
		"description" => "This setting enables you to show the statuses of threads globally, only to people who can mark as solved, or to people who can mark as solved and the author of a thread. This means you can only show people the statuses of their own threads (to save clutter for everybody else) or hide them from view completely so users won't even know the system is in place.",
		"optionscode" => "radio
all=Everybody
canmas=Those who can mark as solved
canmasauthor=Those who can mark as solved and the author of the thread",
		"value" => "all"
	);
	$settings[] = array(
		"name" => "mysupportauthor",
		"title" => "Can the author mark their own threads as solved??",
		"description" => "If this is set to Yes, they will be able to mark their own threads as solved even if their usergroup cannot mark threads as solved.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportclosewhensolved",
		"title" => "Close threads when marked as solved??",
		"description" => "Should the thread be closed when it is marked as solved?? <strong>Note:</strong> if a thread is closed when it is marked as solved, 'unsolving' the thread will <strong>not</strong> re-open the thread.",
		"optionscode" => "radio
always=Always
option=Optional
never=Never",
		"value" => "never"
	);
	$settings[] = array(
		"name" => "mysupportmoveredirect",
		"title" => "Move Redirect.",
		"description" => "How long to leave a thread redirect in the original forum for?? For this to do anything you must have chosen a forum to move threads to, by going to ACP > Configuration > MySupport > General.",
		"optionscode" => "select
none=No redirect
1=1 Day
2=2 Days
3=3 Days
5=5 Days
10=10 days
28=28 days
forever=Forever",
		"value" => "0"
	);
	$settings[] = array(
		"name" => "mysupportunsolve",
		"title" => "Can a user 'unsolve' a thread??",
		"description" => "If a user marks a thread as solved but then still needs help, can the thread author mark it as not solved?? <strong>Note:</strong> if the thread was closed and/or moved when it was originally marked as solved, this will <strong>not</strong> undo that change, therefore it is not recommended to allow this if you choose to close/move a thread when it is solved.",
		"optionscode" => "yesno",
		"value" => "0"
	);
	$settings[] = array(
		"name" => "enablemysupportbestanswer",
		"title" => "Enable ability to highlight the best answer??",
		"description" => "When a thread is solved, can the author choose to highlight the best answer in the thread, i.e. the post that solved the thread for them?? Only the thread author can do this, it can be undone, and will highlight the post with the 'mysupport_bestanswer_highlight' class in mysupport.css. If this feature is used when a thread has not yet been marked as solved, choosing to highlight a post will mark it as solved as well, provided they have the ability to.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "enablemysupporttechnical",
		"title" => "Enable the 'Mark as Technical' feature??",
		"description"=> "This will mark a thread as requiring technical attention. This is useful if a thread would be better answered by someone with more knowledge/experience than the standard support team. Configurable below.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupporthidetechnical",
		"title" => "'Hide' technical status if cannot mark as technical??",
		"description" => "Do you want to only show a thread as being technical if the logged in user can mark as technical?? Users who cannot mark as technical will see the thread as 'Not Solved'. For example, if a moderator can mark threads as technical and regular users cannot, when a thread is marked technical, moderators will see it as technical but regular users will see it as 'Not Solved'. This can be useful if you want to hide the fact the technical threads feature is in use or that a thread has been marked technical.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupporttechnicalnotice",
		"title" => "Where should the technical threads notice be shown??",
		"description" => "If set to global, it will show in the header on every page. If set to specific, it will only show in the relevant forums; for example, if fid=2 has two technical threads, the notice will only show in that forum.",
		"optionscode" => "radio
off=Nowhere (Disabled)
global=Global
specific=Specific",
		"value" => "global"
	);
	$settings[] = array(
		"name" => "enablemysupportassign",
		"title" => "Enable the ability to assign threads??",
		"description" => "If set to yes, you will be able to assign threads to people. They will have access to a list of threads assigned to them, a header notification message, and there's the ability to send them a PM when they are assigned a new thread. All configurable below.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportassignpm",
		"title" => "PM when assigned thread",
		"description" => "Should users receive a PM when they are assigned a thread?? They will not get one if they assign a thread to themselves.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportassignsubscribe",
		"title" => "Subscribe when assigned",
		"description" => "Should a user be automatically subscribed to a thread when it's assigned to them?? If the user's options are setup to receive email notifications for subscriptions then they will be subscribed to the thread by email, otherwise they will be subscribed to the thread without email.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "enablemysupportpriorities",
		"title" => "Enable the ability to add a priority threads??",
		"description" => "If set to yes, you will be able to give threads priorities, which will highlight threads in a specified colour on the forum display.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "enablemysupportsupportdenial",
		"title" => "Enable support denial??",
		"description" => "If set to yes, you will be able to deny support to selected users, meaning they won't be able to make threads in MySupport forums.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportmodlog",
		"title" => "Add moderator log entry??",
		"description" => "Do you want to log changes to the status of a thread?? These will show in the Moderator CP Moderator Logs list. Separate with a comma. Leave blank for no logging.<br /><strong>Note:</strong> <strong>0</strong> = Mark as Not Solved, <strong>1</strong> = Mark as Solved, <strong>2</strong> = Mark as Technical, <strong>4</strong> = Mark as Not Technical, <strong>5</strong> = Add/change assign, <strong>6</strong> = Remove assign, <strong>7</strong> = Add/change priority, <strong>8</strong> = Remove priority, <strong>9</strong> = Add/change category, <strong>10</strong> = Remove category, <strong>11</strong> = Deny support/revoke support denial. <strong>For a better method of managing this setting, <a href=\"index.php?module=config-mysupport&action=general\">click here</a>.</strong>",
		"optionscode" => "text",
		"value" => "0,1,2,4,5,6,7,8,9,10,11"
	);
	$settings[] = array(
		"name" => "mysupporthighlightstaffposts",
		"title" => "Highlight staff posts??",
		"description" => "This will highlight posts made by staff, using the 'mysupport_staff_highlight' class in mysupport.css.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportthreadlist",
		"title" => "Enable the list of support threads??",
		"description" => "If this is enabled, users will have an option in their User CP showing them all their threads in any forums where the Mark as Solved feature is enabled, and will include the status of each thread.",
		"optionscode" => "onoff",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportstats",
		"title" => "Small stats section on support/technical lists",
		"description" => "This will show a small stats section at the top of the list of support/technical threads. It will show a simple bar and counts of the amount of solved/unsolved/techncial threads.",
		"optionscode" => "onoff",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportrelativetime",
		"title" => "Display status times with a relative date??",
		"description"=> "If this is enabled, the time of a status will be shown as a relative time, e.g. 'X Months, Y Days ago' or 'X Hours, Y Minutes ago', rather than a specific date.",
		"optionscode" => "yesno",
		"value" => "1"
	);
	$settings[] = array(
		"name" => "mysupportpointssystem",
		"title" => "Points System",
		"description" => "Which points system do you want to integrate with MySupport?? MyPS and NewPoints are available. If you have another points system you would like to use, choose 'Other' and fill in the new options that will appear.",
		"optionscode" => "select
myps=MyPS
newpoints=NewPoints
other=Other
none=None (Disabled)",
		"value" => "none"
	);
	$settings[] = array(
		"name" => "mysupportpointssystemname",
		"title" => "Custom Points System name",
		"description"=> "If you want to use a points system that is not supported in MySupport by default, put the name of it here. The name is the same as the name of the file for the plugin in <em>./inc/plugins/</em>. For example, if the plugin file was called <strong>mypoints.php</strong>, you would put <strong>mypoints</strong> into this setting.",
		"optionscode" => "text",
		"value" => ""
	);
	$settings[] = array(
		"name" => "mysupportpointssystemcolumn",
		"title" => "Custom Points System database column",
		"description" => "If you want to use a points system that is not supported in MySupport by default, put the name of the column from the users table which stores the number of points here. if you are unsure what to put here, please contact the author of the points plugin you want to use.",
		"optionscode" => "text",
		"value" => ""
	);
	$settings[] = array(
		"name" => "mysupportbestanswerpoints",
		"title" => "Give points to the author of the best answer??",
		"description" => "How many points do you want to give to the author of the best answer?? The same amount of points will be removed should the post be removed as the best answer. Leave blank to give none.",
		"optionscode" => "text",
		"value" => ""
	);
	
	return $settings;
}

/**
 * Import the settings.
**/
function mysupport_import_settings()
{
	global $mybb, $db;
	
	$settings = mysupport_settings_info();
	$settings_gid = mysupport_settings_gid();
	
	foreach($settings as $setting)
	{
		// we're updating an existing setting - this would be called during an upgrade
		if(array_key_exists($setting['name'], $mybb->settings))
		{
			// here we want to update the title, description, and options code in case they've changed, but we don't change the value so it doesn't change what people have set
			$update = array(
				"title" => $db->escape_string($setting['title']),
				"description" => $db->escape_string($setting['description']),
				"optionscode" => $db->escape_string($setting['optionscode'])
			);
			$db->update_query("settings", $update, "name = '" . $db->escape_string($setting['name']) . "'");
		}
		// we're inserting a new setting - either we're installing, or upgrading and a new setting's been added
		else
		{
			$insert = array(
				"name" => $db->escape_string($setting['name']),
				"title" => $db->escape_string($setting['title']),
				"description" => $db->escape_string($setting['description']),
				"optionscode" => $db->escape_string($setting['optionscode']),
				"value" => $db->escape_string($setting['value']),
				"gid" => intval($settings_gid),
			);
			$db->insert_query("settings", $insert);
		}
	}
	
	mysupport_update_setting_orders();
	
	rebuild_settings();
}

/**
 * Update the display order of settings if settings
**/
function mysupport_update_setting_orders()
{
	global $db;
	
	$settings = mysupport_setting_names();
	
	$i = 1;
	foreach($settings as $setting)
	{
		$update = array(
			"disporder" => $i
		);
		$db->update_query("settings", $update, "name = '" . $db->escape_string($setting) . "'");
		$i++;
	}
	
	rebuild_settings();
}

function mysupport_templates()
{
	return array(
		'mysupport_assigned',
		'mysupport_assigned_toyou',
		'mysupport_bestanswer',
		'mysupport_deny_support',
		'mysupport_deny_support_deny',
		'mysupport_deny_support_list',
		'mysupport_deny_support_post',
		'mysupport_deny_support_post_linked',
		'mysupport_form',
		'mysupport_jumpto_bestanswer',
		'mysupport_nav_option',
		'mysupport_notice',
		'mysupport_status_image',
		'mysupport_status_text',
		'mysupport_threadlist',
		'mysupport_threadlist_footer',
		'mysupport_threadlist_list',
		'mysupport_threadlist_stats',
		'mysupport_threadlist_thread',
		'mysupport_deny_support_list_user',
		'mysupport_usercp_options',
		'mysupport_inline_thread_moderation'
	);
}

/**
 * Import or delete templates.
 * When upgrading MySupport we want to be able to keep edited versions of templates. This function allows us to only delete the master copies when deactivating, whilst deleting everything when uninstalling.
 * Basically so that when you upgrade you'd deactivate, it'd delete the master copies, and reactivating would import the new master copies, so your edits would be saved. Same as normal MyBB templates work except it's not done with an upgrade script.
 * Then the edited copies would sit hidden in the database ready for when you activate again.
 *
 * @param int Importing/deleting - 1/0
 * @param bool If $type == 0, are we fully deleting them (uninstalling) or just removing the master copies (deactivating).
**/
function mysupport_do_templates($type, $master_only = false)
{
	global $db;
	
	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
	
	if($type == 1)
	{
		$template_group = array(
			"prefix" => "mysupport",
			"title" => "<lang:mysupport>"
		);
		$db->insert_query("templategroups", $template_group);
		
		$templates = array();
		$templates[] = array(
			"title" => "mysupport_form",
			"template" => "<form action=\"showthread.php\" method=\"post\" style=\"display: inline;\">
	<input type=\"hidden\" name=\"tid\" value=\"{\$tid}\" />
	<input type=\"hidden\" name=\"action\" value=\"mysupport\" />
	<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
	{\$status_list}
	{\$assigned_list}
	{\$priorities_list}
	{\$categories_list}
	{\$gobutton}
</form>"
		);
		$templates[] = array(
			"title" => "mysupport_bestanswer",
			"template" => " <a href=\"{\$mybb->settings['bburl']}/showthread.php?action=bestanswer&amp;pid={\$post['pid']}&amp;my_post_key={\$mybb->post_code}\"><img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/{\$bestanswer_img}.gif\" alt=\"{\$bestanswer_alt}\" title=\"{\$bestanswer_title}\" /> {\$bestanswer_desc}</a>"
		);
		$templates[] = array(
			"title" => "mysupport_status_image",
			"template" => "<img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/mysupport_{\$status_img}.png\" alt=\"{\$status_text}\" title=\"{\$status_text}\" /> "
		);
		$templates[] = array(
			"title" => "mysupport_status_text",
			"template" => "<span class=\"mysupport_status_{\$status_class}\" title=\"{\$status_title}\">[{\$status_text}]</span> "
		);
		$templates[] = array(
			"title" => "mysupport_notice",
			"template" => "<table border=\"0\" cellspacing=\"1\" cellpadding=\"4\" class=\"tborder\">
	<tr>
		<td class=\"trow1\" align=\"right\"><a href=\"{\$mybb->settings['bburl']}/{\$notice_url}\"><span class=\"smalltext\">{\$notice_text}</span></a></td>
	</tr>
</table><br />"
		);
		$templates[] = array(
			"title" => "mysupport_threadlist_thread",
			"template" => "<tr{\$priority_class}>
	<td class=\"{\$bgcolor}\" width=\"30%\">
		<div>
			<span><a href=\"{\$thread['threadlink']}\">{\$thread['subject']}</a></span>
			<div class=\"author smalltext\">{\$thread['profilelink']}</div>
		</div>
	</td>
	<td class=\"{\$bgcolor}\" width=\"25%\">{\$thread['forumlink']} <a href=\"{\$mybb->settings['bburl']}/{\$view_all_forum_link}\"><img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/mysupport_arrow_right.gif\" alt=\"{\$view_all_forum_text}\" title=\"{\$view_all_forum_text}\" /></a></td>
	<td class=\"{\$bgcolor}\" width=\"25%\">{\$status_time}</td>
	<td class=\"{\$bgcolor}\" width=\"20%\" style=\"white-space: nowrap; text-align: right;\">
		<span class=\"lastpost smalltext\">{\$lastpostdate} {\$lastposttime}<br />
		<a href=\"{\$thread['lastpostlink']}\">{\$lang->thread_list_lastpost}</a>: {\$lastposterlink}</span>
	</td>
</tr>"
		);
		$templates[] = array(
			"title" => "mysupport_threadlist",
			"template" => "<html>
<head>
<title>{\$mybb->settings['bbname']} - {\$thread_list_title}</title>
{\$headerinclude}
</head>
<body>
	{\$header}
	<table width=\"100%\" border=\"0\" align=\"center\">
		<tr>
			{\$navigation}
			<td valign=\"top\">
				{\$stats}
				{\$threads_list}
			</td>
		</tr>
	</table>
	{\$footer}
</body>
</html>"
		);
		$templates[] = array(
			"title" => "mysupport_threadlist_list",
			"template" => "{\$mysupport_priority_classes}
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" width=\"100%\" colspan=\"4\"><strong>{\$thread_list_heading}</strong></td>
	</tr>
	<tr>
		<td class=\"tcat\" width=\"30%\"><strong>{\$lang->thread_list_threadauthor}</strong></td>
		<td class=\"tcat\" width=\"25%\"><strong>{\$lang->forum}</strong></td>
		<td class=\"tcat\" width=\"25%\"><strong>{\$status_heading}</strong></td>
		<td class=\"tcat\" width=\"20%\" ><strong>{\$lang->thread_list_lastpost}:</strong></td>
	</tr>
	{\$threads}
	{\$view_all}
</table>"
		);
		$templates[] = array(
			"title" => "mysupport_threadlist_footer",
			"template" => "<tr>
	<td class=\"tfoot\" colspan=\"4\"><a href=\"{\$mybb->settings['bburl']}/{\$view_all_url}\"><strong>{\$view_all}</strong></a></td>
</tr>"
		);
		$templates[] = array(
			"title" => "mysupport_nav_option",
			"template" => "<tr><td class=\"trow1 smalltext\"><a href=\"{\$mybb->settings['bburl']}/{\$nav_link}\" class=\"{\$class1} {\$class2}\">{\$nav_text}</a></td></tr>"
		);
		$templates[] = array(
			"title" => "mysupport_threadlist_stats",
			"template" => "<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" width=\"100%\"><strong>{\$title_text}</strong></td>
	</tr>
	<tr>
		<td class=\"trow1\" width=\"100%\">{\$overview_text}</td>
	</tr>
	<tr>
		<td class=\"trow2\" width=\"100%\">
			<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
				<tr>
					{\$solved_row}
					{\$notsolved_row}
					{\$technical_row}
				</tr>
			</table>
		</td>
	</tr>
</table><br />"
		);
		$templates[] = array(
			"title" => "mysupport_jumpto_bestanswer",
			"template" => "<a href=\"{\$mybb->settings['bburl']}/{\$jumpto_bestanswer_url}\"><img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/{\$bestanswer_image}\" alt=\"{\$lang->jump_to_bestanswer}\" title=\"{\$lang->jump_to_bestanswer}\" /></a>"
		);
		$templates[] = array(
			"title" => "mysupport_assigned",
			"template" => "<img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/mysupport_assigned.png\" alt=\"{\$lang->assigned}\" title=\"{\$lang->assigned}\" />"
		);
		$templates[] = array(
			"title" => "mysupport_assigned_toyou",
			"template" => "<a href=\"{\$mybb->settings['bburl']}/usercp.php?action=assignedthreads\" target=\"_blank\"><img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/mysupport_assigned_toyou.png\" alt=\"{\$lang->assigned_toyou}\" title=\"{\$lang->assigned_toyou}\" /></a>"
		);
		$templates[] = array(
			"title" => "mysupport_deny_support_post",
			"template" => "<img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/mysupport_no_support.gif\" alt=\"{\$denied_text_desc}\" title=\"{\$denied_text_desc}\" /> {\$denied_text}"
		);
		$templates[] = array(
			"title" => "mysupport_deny_support_post_linked",
			"template" => "<a href=\"{\$mybb->settings['bburl']}/modcp.php?action=supportdenial&amp;do=denysupport&amp;uid={\$post['uid']}&amp;tid={\$post['tid']}\" title=\"{\$denied_text_desc}\"><img src=\"{\$mybb->settings['bburl']}/{\$theme['imgdir']}/mysupport_no_support.gif\" alt=\"{\$denied_text_desc}\" title=\"{\$denied_text_desc}\" /> {\$denied_text}</a>"
		);
		$templates[] = array(
			"title" => "mysupport_deny_support",
		       "template" => "<html>
<head>
<title>{\$lang->support_denial}</title>
{\$headerinclude}
</head>
<body>
	{\$header}
	<table width=\"100%\" border=\"0\" align=\"center\">
		<tr>
			{\$modcp_nav}
			<td valign=\"top\">
				{\$deny_support}
			</td>
		</tr>
	</table>
	{\$footer}
</body>
</html>"
		);
		$templates[] = array(
			"title" => "mysupport_deny_support_deny",
			"template" => "<form method=\"post\" action=\"modcp.php\">
	<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
		<tr>
			<td class=\"thead\"><strong>{\$deny_support_to}</strong></td>
		</tr>
		<tr>
			<td class=\"trow1\" align=\"center\">{\$lang->deny_support_desc}</td>
		</tr>
		<tr>
			<td class=\"trow1\" align=\"center\">
				<label for=\"username\">{\$lang->username}</label> <input type=\"text\" name=\"username\" id=\"username\" value=\"{\$username}\" />
			</td>
		</tr>
		<tr>
			<td class=\"trow2\" width=\"80%\" align=\"center\">
				<input type=\"hidden\" name=\"action\" value=\"supportdenial\" />
				<input type=\"hidden\" name=\"do\" value=\"do_denysupport\" />
				<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
				<input type=\"hidden\" name=\"tid\" value=\"{\$tid}\" />
				{\$deniedreasons}
			</td>
		</tr>
		<tr>
			<td class=\"trow2\" width=\"80%\" align=\"center\">
				<input type=\"submit\" value=\"{\$lang->deny_support}\" />
			</td>
		</tr>
	</table>
</form>"
		);
		$templates[] = array(
			"title" => "mysupport_deny_support_list",
			"template" => "<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" colspan=\"5\">
			<div class=\"float_right\"><a href=\"modcp.php?action=supportdenial&amp;do=denysupport\">{\$lang->deny_support}</a></div>
			<strong>{\$lang->users_denied_support}</strong>
		</td>
	</tr>
	<tr>
		<td class=\"tcat\" align=\"center\" width=\"20%\"><strong>{\$lang->username}</strong></td>
		<td class=\"tcat\" align=\"center\" width=\"30%\"><strong>{\$lang->support_denial_reason}</strong></td>
		<td class=\"tcat\" align=\"center\" width=\"20%\"><strong>{\$lang->support_denial_user}</strong></td>
		<td class=\"tcat\" colspan=\"2\" align=\"center\" width=\"30%\"><strong>{\$lang->controls}</strong></td>
	</tr>
	{\$denied_users}
</table>"
		);
		
		$templates[] = array(
			"title" => "mysupport_deny_support_list_user",
			"template" => "<tr>
	<td class=\"{\$bgcolor}\" align=\"center\" width=\"20%\">{\$support_denied_user}</td>
	<td class=\"{\$bgcolor}\" align=\"center\" width=\"30%\">{\$support_denial_reason}</td>
	<td class=\"{\$bgcolor}\" align=\"center\" width=\"20%\">{\$support_denier_user}</td>
	<td class=\"{\$bgcolor}\" align=\"center\" width=\"15%\"><a href=\"{\$mybb->settings['bburl']}/modcp.php?action=supportdenial&amp;do=denysupport&amp;uid={\$denieduser['support_denied_uid']}\">{\$lang->edit}</a></td>
	<td class=\"{\$bgcolor}\" align=\"center\" width=\"15%\"><a href=\"{\$mybb->settings['bburl']}/modcp.php?action=supportdenial&amp;do=do_denysupport&amp;uid={\$denieduser['support_denied_uid']}&amp;deniedsupportreason=-1&amp;my_post_key={\$mybb->post_code}\">{\$lang->revoke}</a></td>
</tr>"
		);
		
		$templates[] = array(
			"title" => "mysupport_usercp_options",
			"template" => "<fieldset class=\"trow2\">
	<legend><strong>{\$lang->mysupport_options}</strong></legend>
	<table cellspacing=\"0\" cellpadding=\"2\">
		<tr>
			<td valign=\"top\" width=\"1\">
				<input type=\"checkbox\" class=\"checkbox\" name=\"mysupportdisplayastext\" id=\"mysupportdisplayastext\" value=\"1\" {\$mysupportdisplayastextcheck} />
			</td>
			<td>
				<span class=\"smalltext\"><label for=\"mysupportdisplayastext\">{\$lang->mysupport_show_as_text}</label></span>
			</td>
		</tr>
	</table>
</fieldset>
<br />"
		);
		
		$templates[] = array(
			"title" => "mysupport_inline_thread_moderation",
			"template" => "<optgroup label=\"{\$lang->mysupport}\">
	<option disabled=\"disabled\">{\$lang->markas}</option>
	{\$mysupport_solved}
	{\$mysupport_solved_and_close}
	{\$mysupport_technical}
	{\$mysupport_not_technical}
	{\$mysupport_not_solved}
	<option disabled=\"disabled\">{\$lang->assign_to}</option>
	{\$mysupport_assign}
	<option value=\"mysupport_assign_0\">-- {\$lang->assign_to_nobody}</option>
	<option disabled=\"disabled\">{\$lang->priority}</option>
	{\$mysupport_priorities}
	<option value=\"mysupport_priority_0\">-- {\$lang->priority_none}</option>
	<option disabled=\"disabled\">{\$lang->category}</option>
	{\$mysupport_categories}
	<option value=\"mysupport_category_0\">-- {\$lang->category_none}</option>
</optgroup>"
		);
		
		foreach($templates as $template)
		{
			$insert = array(
				"title" => $db->escape_string($template['title']),
				"template" => $db->escape_string($template['template']),
				"sid" => "-2",
				"version" => "1600",
				"status" => "",
				"dateline" => TIME_NOW
			);
			
			$db->insert_query("templates", $insert);
		}
	}
	else
	{
		$db->delete_query("templategroups", "prefix = 'mysupport'");
		
		$where_sql = "";
		if($master_only)
		{
			$where_sql = " AND sid = '-2'";
		}
		
		$templates = mysupport_templates();
		$templates = "'" . implode("','", array_map($db->escape_string, $templates)) . "'";
		// have to use $db->escape_string above instead of around $templates directly because otherwise it escapes the ' around the names, which are important
		$db->delete_query("templates", "title IN ({$templates}){$where_sql}");
	}
}

/**
 * Make the template edits necessary for MySupport to work. In a function as it's all in one place.
 *
 * @param int Activating/deactivating - 1/0
**/
function mysupport_template_edits($type)
{
	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
	
	if($type == 1)
	{
		find_replace_templatesets("showthread", "#".preg_quote('{$multipage}')."#i", '{$multipage}{$mysupport_jumpto_bestanswer}{$mysupport_form}');
		find_replace_templatesets("postbit", "#".preg_quote('trow1')."#i", 'trow1{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}');
		find_replace_templatesets("postbit", "#".preg_quote('trow2')."#i", 'trow2{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}');
		find_replace_templatesets("postbit_classic", "#".preg_quote('{$altbg}')."#i", '{$altbg}{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}');
		find_replace_templatesets("postbit", "#".preg_quote('{$post[\'subject_extra\']}')."#i", '{$post[\'subject_extra\']}<div class="float_right">{$post[\'mysupport_bestanswer\']}{$post[\'mysupport_deny_support_post\']}</div>');
		find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'subject_extra\']}')."#i", '{$post[\'subject_extra\']}<div class="float_right">{$post[\'mysupport_bestanswer\']}{$post[\'mysupport_deny_support_post\']}</div>');
		find_replace_templatesets("postbit", "#".preg_quote('{$post[\'icon\']}')."#i", '{$post[\'mysupport_status\']}{$post[\'icon\']}');
		find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'icon\']}')."#i", '{$post[\'mysupport_status\']}{$post[\'icon\']}');
		find_replace_templatesets("showthread", "#".preg_quote('<strong>{$thread[\'subject\']}</strong>')."#i", '<strong>{$mysupport_status}{$thread[\'subject\']}</strong>');
		find_replace_templatesets("header", "#".preg_quote('{$unreadreports}')."#i", '{$unreadreports}{$mysupport_tech_notice}{$mysupport_assign_notice}');
		find_replace_templatesets("forumdisplay", "#".preg_quote('{$header}')."#i", '{$header}{$mysupport_priority_classes}');
		find_replace_templatesets("search_results_threads ", "#".preg_quote('{$header}')."#i", '{$header}{$mysupport_priority_classes}');
		find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$prefix}')."#i", '{$mysupport_status}{$mysupport_bestanswer}{$mysupport_assigned}{$prefix}');
		find_replace_templatesets("search_results_threads_thread ", "#".preg_quote('{$prefix}')."#i", '{$mysupport_status}{$mysupport_bestanswer}{$mysupport_assigned}{$prefix}');
		find_replace_templatesets("forumdisplay_thread", "#".preg_quote('<tr>')."#i", '<tr{$priority_class}>');
		find_replace_templatesets("search_results_threads_thread", "#".preg_quote('<tr>')."#i", '<tr{$priority_class}>');
		find_replace_templatesets("forumdisplay_inlinemoderation", "#".preg_quote('{$customthreadtools}')."#i", '{$customthreadtools}{$mysupport_inline_thread_moderation}');
		find_replace_templatesets("search_results_threads_inlinemoderation", "#".preg_quote('{$customthreadtools}')."#i", '{$customthreadtools}{$mysupport_inline_thread_moderation}');
		find_replace_templatesets("modcp_nav", "#".preg_quote('{$lang->mcp_nav_modlogs}</a></td></tr>')."#i", '{$lang->mcp_nav_modlogs}</a></td></tr>{mysupport_nav_option}');
		find_replace_templatesets("usercp_nav_misc", "#".preg_quote('{$lang->ucp_nav_forum_subscriptions}</a></td></tr>')."#i", '{$lang->ucp_nav_forum_subscriptions}</a></td></tr>{mysupport_nav_option}');
		find_replace_templatesets("usercp", "#".preg_quote('{$latest_warnings}')."#i", '{$latest_warnings}<br />{$threads_list}');
	}
	else
	{
		find_replace_templatesets("showthread", "#".preg_quote('{$mysupport_jumpto_bestanswer}{$mysupport_form}')."#i", '', 0);
		find_replace_templatesets("postbit", "#".preg_quote('{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}')."#i", '', 0);
		find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'mysupport_bestanswer_highlight\']}{$post[\'mysupport_staff_highlight\']}')."#i", '', 0);
		find_replace_templatesets("postbit", "#".preg_quote('<div class="float_right">{$post[\'mysupport_bestanswer\']}{$post[\'mysupport_deny_support_post\']}</div>')."#i", '', 0);
		find_replace_templatesets("postbit_classic", "#".preg_quote('<div class="float_right">{$post[\'mysupport_bestanswer\']}{$post[\'mysupport_deny_support_post\']}</div>')."#i", '', 0);
		find_replace_templatesets("postbit", "#".preg_quote('{$post[\'mysupport_status\']}')."#i", '', 0);
		find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'mysupport_status\']}')."#i", '', 0);
		find_replace_templatesets("showthread", "#".preg_quote('{$mysupport_status}')."#i", '', 0);
		find_replace_templatesets("header", "#".preg_quote('{$mysupport_tech_notice}{$mysupport_assign_notice}')."#i", '', 0);
		find_replace_templatesets("forumdisplay", "#".preg_quote('{$mysupport_priority_classes}')."#i", '', 0);
		find_replace_templatesets("search_results_threads ", "#".preg_quote('{$mysupport_priority_classes}')."#i", '', 0);
		find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$mysupport_status}{$mysupport_bestanswer}{$mysupport_assigned}')."#i", '', 0);
		find_replace_templatesets("search_results_threads_thread ", "#".preg_quote('{$mysupport_status}{$mysupport_bestanswer}{$mysupport_assigned}')."#i", '', 0);
		find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$priority_class}')."#i", '', 0);
		find_replace_templatesets("search_results_threads_thread", "#".preg_quote('{$priority_class}')."#i", '', 0);
		find_replace_templatesets("forumdisplay_inlinemoderation", "#".preg_quote('{$mysupport_inline_thread_moderation}')."#i", '', 0);
		find_replace_templatesets("search_results_threads_inlinemoderation", "#".preg_quote('{$mysupport_inline_thread_moderation}')."#i", '', 0);
		find_replace_templatesets("modcp_nav", "#".preg_quote('{mysupport_nav_option}')."#i", '', 0);
		find_replace_templatesets("usercp_nav_misc", "#".preg_quote('{mysupport_nav_option}')."#i", '', 0);
		find_replace_templatesets("usercp", "#".preg_quote('<br />{$threads_list}')."#i", '', 0);
	}
}

// get the gid of the MySupport settings group
function mysupport_settings_gid()
{
	global $db;
	
	$query = $db->simple_select("settinggroups", "gid", "name = 'mysupport'", array("limit" => 1));
	$gid = $db->fetch_field($query, "gid");
	
	return intval($gid);
}

// redirect to the settings page after activating
function mysupport_settings_redirect()
{
	global $mybb, $db, $lang, $installed;
	
	if($installed === true && $mybb->input['plugin'] == "mysupport")
	{
		$lang->load("mysupport");
		
		$gid = mysupport_settings_gid();
		
		flash_message($lang->mysupport_activated, 'success');
		admin_redirect("index.php?module=config-settings&action=change&gid={$gid}");
	}
}

// show the form in the thread to change the status of the thread
function mysupport_showthread()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $cache, $lang, $templates, $theme, $thread, $forum, $mysupport_status, $mysupport_form, $mysupport_jumpto_bestanswer, $support_denial_reasons, $gobutton, $mod_log_action, $redirect;
	
	$lang->load("mysupport");

	$tid = intval($thread['tid']);
	$fid = intval($thread['fid']);
	
	if(mysupport_forum($forum['fid']))
	{
		// load the denied reasons so we can display them to staff if necessary
		if($mybb->settings['enablemysupportsupportdenial'] == 1 && mysupport_usergroup("canmanagesupportdenial"))
		{
			$support_denial_reasons = array();
			$query = $db->simple_select("mysupport", "mid, name", "type = 'deniedreason'");
			while($deniedreason = $db->fetch_array($query))
			{
				$support_denial_reasons[$deniedreason['mid']] = htmlspecialchars_uni($deniedreason['name']);
			}
		}
		
		if($mybb->settings['enablemysupportbestanswer'] == 1)
		{
			if($thread['bestanswer'] != 0)
			{
				$post = intval($thread['bestanswer']);
				$jumpto_bestanswer_url = get_post_link($post, $tid) . "#pid" . $post;
				$bestanswer_image = "mysupport_arrow_down.png";
				eval("\$mysupport_jumpto_bestanswer = \"".$templates->get('mysupport_jumpto_bestanswer')."\";");
			}
		}
		
		// start a count of how many options they'll have access to, will be used later
		$count = 0;
		// if it's not already solved
		if($thread['status'] != 1)
		{
			// can they mark as solved??
			if(mysupport_usergroup("canmarksolved") || ($mybb->settings['mysupportauthor'] == 1 && $thread['uid'] == $mybb->user['uid']))
			{
				// is the ability to close turned on??
				if($mybb->settings['mysupportclosewhensolved'] != "never")
				{
					// if the close setting isn't never, this option would show regardless of whether it's set to always or optional
					$mysupport_solved_and_close = "<option value=\"3\">" . $lang->solved_close . "</option>";
					++$count;
					// if it's set to always close we don't need to show the option without the close text
					if($mybb->settings['mysupportclosewhensolved'] == "always")
					{
						$mysupport_solved = "";
					}
					// if it's an option, it needs to be shown
					elseif($mybb->settings['mysupportclosewhensolved'] == "option")
					{
						$mysupport_solved = "<option value=\"1\">" . $lang->solved . "</option>";
						++$count;
					}
				}
				else
				{
					// closing is set to never, just need the option to mark it as solved
					$mysupport_solved = "<option value=\"1\">" . $lang->solved . "</option>";
					++$count;
					$mysupport_solved_and_close = "";
				}
			}
			
			// is the technical threads feature on??
			if($mybb->settings['enablemysupporttechnical'] == 1)
			{
				// can they mark as techincal??
				if(mysupport_usergroup("canmarktechnical"))
				{
					if($thread['status'] != 2)
					{
						// if it's not marked as technical, give an option to mark it as such
						$mysupport_technical = "<option value=\"2\">" . $lang->technical . "</option>";
					}
					else
					{
						// if it's already marked as technical, have an option to put it back to normal
						$mysupport_technical = "<option value=\"4\">" . $lang->not_technical . "</option>";
					}
					++$count;
				}
			}
		}
		// if it's solved, all you can do is mark it as not solved
		else
		{
			// are they allowed to mark it as not solved if it's been marked solved already??
			if($mybb->settings['mysupportunsolve'] == 1 && (mysupport_usergroup("canmarksolved") || ($mybb->settings['mysupportauthor'] == 1 && $thread['uid'] == $mybb->user['uid'])))
			{
				$mysupport_not_solved = "<option value=\"0\">" . $lang->not_solved . "</option>";
				++$count;
			}
		}
		
		$status_list = "";
		// if the current count is more than 0 there's some status options to show
		if($count > 0)
		{
			$current_status = mysupport_get_friendly_status($thread['status']);
			$status_list .= "<label for=\"status\">" . $lang->markas . "</label> <select name=\"status\">\n";
			// show the current status but have the value as -1 so it's treated as not submitting a status
			// doing this because the assigning and priority menus show their current values, so do it here too for consistency
			$status_list .= "<option value=\"-1\">" . htmlspecialchars_uni($current_status) . "</option>\n";
			// also show a blank option with a value of -1
			$status_list .= "<option value=\"-1\"></option>\n";
			$status_list .= $mysupport_not_solved . "\n";
			$status_list .= $mysupport_solved . "\n";
			$status_list .= $mysupport_solved_and_close . "\n";
			$status_list .= $mysupport_technical . "\n";
			$status_list .= "</select>\n";
		}
		
		// check if assigning threads is enabled and make sure you can assign threads to people
		// also check if the thread is currently not solved, or if it's solved but you can unsolve it; if any of those are true, you may want to assign it
		if($mybb->settings['enablemysupportassign'] == 1 && mysupport_usergroup("canassign") && ($thread['status'] != 1 || ($thread['status'] == 1 && $mybb->settings['mysupportunsolve'] == 1)))
		{
			$assign_users = mysupport_get_assign_users();
			
			// only continue if there's one or more users that can be assigned threads
			if(!empty($assign_users))
			{
				$assigned_list = "";
				$assigned_list .= "<label for=\"assign\">" . $lang->assign_to . "</label> <select name=\"assign\">\n";
				$assigned_list .= "<option value=\"0\"></option>\n";
				
				foreach($assign_users as $assign_userid => $assign_username)
				{
					$selected = "";
					if($thread['assign'] == $assign_userid)
					{
						$selected = " selected=\"selected\"";
					}
					$assigned_list .= "<option value=\"" . intval($assign_userid) . "\"{$selected}>" . htmlspecialchars_uni($assign_username) . "</option>\n";
					++$count;
				}
				if($thread['assign'] != 0)
				{
					$assigned_list .= "<option value=\"-1\">" . $lang->assign_to_nobody . "</option>\n";
				}
				
				$assigned_list .= "</select>\n";
			}
		}
		
		// are priorities enabled and can this user set priorities??
		if($mybb->settings['enablemysupportpriorities'] == 1 && mysupport_usergroup("cansetpriorities"))
		{
			$query = $db->simple_select("mysupport", "*", "type = 'priority'");
			$priorities_list = "";
			if($db->num_rows($query) > 0)
			{
				$priorities_list .= "<label for=\"priority\">" . $lang->priority . "</label> <select name=\"priority\">\n";
				$priorities_list .= "<option value=\"0\"></option>\n";
				
				while($priority = $db->fetch_array($query))
				{
					$option_style = "";
					if(!empty($priority['extra']))
					{
						$option_style = " style=\"background: #" . htmlspecialchars_uni($priority['extra']) . "\"";
					}
					$selected = "";
					if($thread['priority'] == $priority['mid'])
					{
						$selected = " selected=\"selected\"";
					}
					$priorities_list .= "<option value=\"" . intval($priority['mid']) . "\"{$option_style}{$selected}>" . htmlspecialchars_uni($priority['name']) . "</option>\n";
					++$count;
				}
				if($thread['priority'] != 0)
				{
					$priorities_list .= "<option value=\"-1\">" . $lang->priority_none . "</option>\n";
				}
				$priorities_list .= "</select>\n";
			}
		}
		
		if(mysupport_usergroup("canmarksolved"))
		{
			$categories = mysupport_get_categories($forum);
			$categories_list = "";
			if(!empty($categories))
			{
				$categories_list .= "<label for=\"category\">" . $lang->category . "</label> <select name=\"category\">\n";
				$categories_list .= "<option value=\"0\"></option>\n";
				
				foreach($categories as $category_id => $category)
				{
					$selected = "";
					if($thread['prefix'] == $category_id)
					{
						$selected = " selected=\"selected\"";
					}
					$categories_list .= "<option value=\"" . intval($category_id) . "\"{$selected}>" . htmlspecialchars_uni($category) . "</option>\n";
					++$count;
				}
				if($thread['prefix'] != 0)
				{
					$categories_list .= "<option value=\"-1\">" . $lang->category_none . "</option>\n";
				}
				$categories_list .= "</select>\n";
			}
		}
		
		// are there actually any options to show for this user??
		if($count > 0)
		{
			eval("\$mysupport_form = \"".$templates->get('mysupport_form')."\";");
		}
		
		$mysupport_status = mysupport_get_display_status($thread['status'], $thread['statustime'], $thread['uid']);
	}
	
	if($mybb->input['action'] == "mysupport")
	{
		verify_post_check($mybb->input['my_post_key']);
		$status = $db->escape_string($mybb->input['status']);
		$assign = $db->escape_string($mybb->input['assign']);
		$priority = $db->escape_string($mybb->input['priority']);
		$category = $db->escape_string($mybb->input['category']);
		$tid = intval($thread['tid']);
		$fid = intval($thread['fid']);
		$old_status = intval($thread['status']);
		$old_assign = intval($thread['assign']);
		$old_priority = intval($thread['priority']);
		$old_category = intval($thread['prefix']);
		
		// we need to make sure they haven't edited the form to try to perform an action they're not allowed to do
		// we check everything in the entire form, if any part of it is wrong, it won't do anything
		if(!mysupport_forum($fid))
		{
			error($lang->error_not_mysupport_forum);
			exit;
		}
		// are they trying to assign the same status it already has??
		elseif($status == $old_status)
		{
			$duplicate_status = mysupport_get_friendly_status($status);
			error($lang->sprintf($lang->error_same_status, $duplicate_status));
			exit;
		}
		elseif($status == 0)
		{
			// either the ability to unsolve is turned off,
			// they don't have permission to mark as not solved via group permissions, or they're not allowed to mark it as not solved even though they authored it
			if($mybb->settings['mysupportunsolve'] != 1 || (!mysupport_usergroup("canmarksolved") && ($mybb->settings['mysupportauthor'] != 1 && $thread['uid'] == $mybb->user['uid'])))
			{
				error($lang->no_permission_mark_notsolved);
				exit;
			}
			else
			{
				$valid_action = true;
			}
		}
		elseif($status == 1)
		{
			// either they're not in a group that can mark as solved
			// or they're not allowed to mark it as solved even though they authored it
			if(!mysupport_usergroup("canmarksolved") && ($mybb->settings['mysupportauthor'] != 1 && $thread['uid'] == $mybb->user['uid']))
			{
				error($lang->no_permission_mark_solved);
				exit;
			}
			else
			{
				$valid_action = true;
			}
		}
		elseif($status == 2)
		{
			// they don't have the ability to mark threads as technical
			if(!mysupport_usergroup("canmarktechnical"))
			{
				error($lang->no_permission_mark_technical);
				exit;
			}
			else
			{
				$valid_action = true;
			}
		}
		elseif($status == 3)
		{
			// either closing of threads is turned off altogether
			// or it's on, but they're not in a group that can't mark as solved
			if($mybb->settings['mysupportclosewhensolved'] == "never" || ($mybb->settings['mysupportclosewhensolved'] != "never" && (!mysupport_usergroup("canmarksolved") || ($mybb->settings['mysupportauthor'] != 1 && $thread['uid'] == $mybb->user['uid']))))
			{
				error($lang->no_permission_mark_solved_close);
				exit;
			}
			else
			{
				$valid_action = true;
			}
		}
		elseif($status == 4)
		{
			// they don't have the ability to mark threads as not technical
			if(!mysupport_usergroup("canmarktechnical"))
			{
				error($lang->no_permission_mark_nottechnical);
				exit;
			}
			else
			{
				$valid_action = true;
			}
		}
		if($assign != 0)
		{
			// trying to assign a thread to someone
			
			// trying to assign a solved thread
			// this is needed to see if we're trying to assign a currently solved thread whilst at the same time changing the status of it
			// the option to assign will still be there if it's solved as you may want to unsolve it and assign it again, but we can't assign it if it's staying solved, we have to be unsolving it
			if($thread['status'] == 1 && $status != 0)
			{
				error($lang->assign_solved);
				exit;
			}
			
			if(!mysupport_usergroup("canassign"))
			{
				error($lang->assign_no_perms);
				exit;
			}
			
			$assign_users = mysupport_get_assign_users();
			// -1 is what's used to unassign a thread so we need to exclude that
			if(!array_key_exists($assign, $assign_users) && $assign != "-1")
			{
				error($lang->assign_invalid);
				exit;
			}
			// if they've got this far and haven't exited...
			$valid_action = true;
		}
		if($priority != 0)
		{
			if(!mysupport_usergroup("cansetpriorities"))
			{
				error($lang->priority_no_perms);
				exit;
			}
			
			if($thread['status'] == 1 && $status != 0)
			{
				error($lang->priority_solved);
				exit;
			}
			
			$query = $db->simple_select("mysupport", "mid", "type = 'priority'");
			$mids = array();
			while($mid = $db->fetch_field($query, "mid"))
			{
				$mids[] = intval($mid);
			}
			if(!in_array($priority, $mids) && $priority != "-1")
			{
				error($lang->priority_invalid);
				exit;
			}
		}
		if($category != 0)
		{
			$categories = mysupport_get_categories($forum);
			if(!array_key_exists($category, $categories) && $category != "-1")
			{
				error($lang->category_invalid);
				exit;
			}
		}
		// it didn't hit an error with any of the above, it's a valid action
		if($valid_action !== false)
		{
			// if you're choosing the same status or choosing none
			// and assigning the same user or assigning none (as in the empty option, not choosing 'Nobody' to remove an assignment)
			// and setting the same priority or setting none (as in the empty option, not choosing 'None' to remove a priority)
			// then you're not actually doing anything, because you're either choosing the same stuff, or choosing nothing at all
			if(($status == $old_status || $status == "-1") && ($assign == $old_assign || $assign == 0) && ($priority == $old_priority || $priority == 0) && ($category == $old_category || $category == 0))
			{
				error($lang->error_no_action);
				exit;
			}
			
			$mod_log_action = "";
			$redirect = "";
			
			// change the status and move/close
			if($status != $old_status && $status != "-1")
			{
				mysupport_change_status($thread, $status);
			}
			
			// we need to see if the same user has been submitted so it doesn't run this for no reason
			// we also need to check if it's being marked as solved, if it is we don't need to do anything with assignments, it'll just be ignored
			if($assign != $old_assign && ($assign != 0 && $status != 1))
			{
				mysupport_change_assign($thread, $assign);
			}
			
			// we need to see if the same priority has been submitted so it doesn't run this for no reason
			// we also need to check if it's being marked as solved, if it is we don't need to do anything with priorities, it'll just be ignored
			if($priority != $old_priority && ($priority != 0  && $status != 1))
			{
				mysupport_change_priority($thread, $priority);
			}
			
			// we need to see if the same category has been submitted so it doesn't run this for no reason
			if($category != $old_category)
			{
				mysupport_change_category($thread, $category);
			}
			
			if(!empty($mod_log_action))
			{
				$mod_log_data = array(
					"fid" => intval($fid),
					"tid" => intval($tid)
				);
				log_moderator_action($mod_log_data, $mod_log_action);
			}
			// where should they go to afterwards??
			$thread_url = get_thread_link($tid);
			redirect($thread_url, $redirect);
		}
	}
	elseif($mybb->input['action'] == "bestanswer")
	{
		verify_post_check($mybb->input['my_post_key']);
		if($mybb->settings['enablemysupportbestanswer'] != 1)
		{
			error($lang->bestanswer_not_enabled);
			exit;
		}
		
		$pid = intval($mybb->input['pid']);
		// we only have a pid so we need to get the tid, fid, uid, and mysupport information of the thread it belongs to
		$query = $db->query("
			SELECT t.fid, t.tid, t.uid AS author_uid, p.uid AS bestanswer_uid, t.status, t.bestanswer
			FROM " . TABLE_PREFIX . "threads t
			INNER JOIN " . TABLE_PREFIX . "forums f
			INNER JOIN " . TABLE_PREFIX . "posts p
			ON (t.tid = p.tid AND t.fid = f.fid AND p.pid = '" . $pid . "')
		");
		$post_info = $db->fetch_array($query);
		
		// is this post in a thread that isn't within an allowed forum??
		if(!mysupport_forum($post_info['fid']))
		{
			error($lang->bestanswer_invalid_forum);
			exit;
		}
		// did this user author this thread??
		elseif($mybb->user['uid'] != $post_info['author_uid'])
		{
			error($lang->bestanswer_not_author);
			exit;
		}
		// is this post already the best answer??
		elseif($pid == $post_info['bestanswer'])
		{
			// this will mark it as the best answer
			$status_update = array(
				"bestanswer" => 0
			);
			// update the bestanswer column for this thread with 0
			$db->update_query("threads", $status_update, "tid = '" . intval($post_info['tid']) . "'");
			
			// are we removing points for this??
			if(mysupport_points_system_enabled())
			{
				if(!empty($mybb->settings['mysupportbestanswerpoints']) && $mybb->settings['mysupportbestanswerpoints'] != 0)
				{
					mysupport_update_points($mybb->settings['mysupportbestanswerpoints'], $post_info['bestanswer_uid'], true);
				}
			}
			
			$redirect = "";
			mysupport_redirect_message($lang->unbestanswer_redirect);
			
			// where should they go to afterwards??
			$thread_url = get_thread_link($post_info['tid']);
			redirect($thread_url, $redirect);
		}
		// mark it as the best answer
		else
		{
			$status_update = array(
				"bestanswer" => intval($pid)
			);
			// update the bestanswer column for this thread with the pid of the best answer
			$db->update_query("threads", $status_update, "tid = '" . intval($post_info['tid']) . "'");
			
			// are we adding points for this??
			if(mysupport_points_system_enabled())
			{
				if(!empty($mybb->settings['mysupportbestanswerpoints']) && $mybb->settings['mysupportbestanswerpoints'] != 0)
				{
					mysupport_update_points($mybb->settings['mysupportbestanswerpoints'], $post_info['bestanswer_uid']);
				}
			}
			
			// if this thread isn't solved yet, do that too whilst we're here
			// if they're marking a post as the best answer, it must have solved the thread, so save them marking it as solved manually
			if($post_info['status'] != 1 && mysupport_usergroup("canmarksolved"))
			{
				$mod_log_action = "";
				$redirect = "";
				
				// change the status
				mysupport_change_status($post_info, 1);
				
				if(!empty($mod_log_action))
				{
					$mod_log_data = array(
						"fid" => intval($post_info['fid']),
						"tid" => intval($post_info['tid'])
					);
					log_moderator_action($mod_log_data, $mod_log_action);
				}
				mysupport_redirect_message($lang->bestanswer_redirect);
			}
			else
			{
				$redirect = "";
				mysupport_redirect_message($lang->bestanswer_redirect);
			}
			
			// where should they go to afterwards??
			$thread_url = get_thread_link($post_info['tid']);
			redirect($thread_url, $redirect);
		}
	}
}

// generate CSS classes for the priorities and select the categories, and load inline thread moderation
function mysupport_forumdisplay_searchresults()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $foruminfo, $priorities, $mysupport_priority_classes;
	
	// basically it's much easier (and neater) to generate makeshift classes for priorities for highlighting threads than adding inline styles
	$query = $db->simple_select("mysupport", "*", "type = 'priority'");
	// build an array of all the priorities
	$priorities = array();
	// start the CSS classes
	$mysupport_priority_classes = "";
	$mysupport_priority_classes .= "\n<style type=\"text/css\">\n";
	while($priority = $db->fetch_array($query))
	{
		// add the name to the array, then we can get the relevant name for each priority when looping through the threads
		$priorities[$priority['mid']] = strtolower(htmlspecialchars_uni($priority['name']));
		// add the CSS class
		if(!empty($priority['extra']))
		{
			$mysupport_priority_classes .= ".mysupport_priority_" . strtolower(htmlspecialchars_uni(str_replace(" ", "_", $priority['name']))) . " td {\n";
			$mysupport_priority_classes .= "\tbackground: #" . htmlspecialchars_uni($priority['extra']) . ";\n";
			$mysupport_priority_classes .= "}\n";
		}
	}
	$mysupport_priority_classes .= "</style>\n";
	
	$mysupport_forums = mysupport_forums();
	// if we're viewing a forum which has MySupport enabled, or we're viewing search results and there's at least 1 MySupport forum, show the MySupport options in the inline moderation menu
	if((THIS_SCRIPT == "forumdisplay.php" && mysupport_forum($mybb->input['fid'])) || (THIS_SCRIPT == "search.php" && !empty($mysupport_forums)))
	{
		mysupport_inline_thread_moderation();
	}
}

// show the status of a thread for each thread on the forum display or a list of search results
function mysupport_threadlist_thread()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $lang, $templates, $theme, $foruminfo, $thread, $is_mysupport_forum, $mysupport_status, $mysupport_assigned, $mysupport_bestanswer, $priorities, $priority_class;
	
	// this function is called for the thread list on the forum display and the list of threads for search results, however the source of the fid is different
	// if this is the forum display, get it from the info on the forum we're in
	if(THIS_SCRIPT == "forumdisplay.php")
	{
		$fid = $foruminfo['fid'];
	}
	// if this is a list of search results, get it from the array of info about the thread we're looking at
	// this means that out of all the results, only threads in MySupport forums will show this information
	elseif(THIS_SCRIPT == "search.php")
	{
		$fid = $thread['fid'];
	}
	
	// need to reset these outside of the the check for if it's a MySupport forum, otherwise they don't get unset in search results where the forum of the next thread may not be a MySupport forum
	$mysupport_status = "";
	$priority_class = "";
	$mysupport_assigned = "";
	$mysupport_bestanswer = "";
	
	if(mysupport_forum($fid))
	{
		if($thread['priority'] != 0 && $thread['visible'] == 1)
		{
			$priority_class = " class=\"mysupport_priority_" . htmlspecialchars_uni(str_replace(" ", "_", $priorities[$thread['priority']])) . "\"";
		}
		
		// the only thing we might want to do with sticky threads is to give them a priority, to highlight them; they're not going to have a status or be assigned to anybody
		// after we've done the priority, we can exit
		if($thread['sticky'] == 1)
		{
			return;
		}
		
		$mysupport_status = mysupport_get_display_status($thread['status'], $thread['statustime'], $thread['uid']);
		
		if($thread['assign'] != 0)
		{
			if($thread['assign'] == $mybb->user['uid'])
			{
				eval("\$mysupport_assigned = \"".$templates->get('mysupport_assigned_toyou')."\";");
			}
			else
			{
				eval("\$mysupport_assigned = \"".$templates->get('mysupport_assigned')."\";");
			}
		}
		
		if($mybb->settings['enablemysupportbestanswer'] == 1)
		{
			if($thread['bestanswer'] != 0)
			{
				$post = intval($thread['bestanswer']);
				$jumpto_bestanswer_url = get_post_link($post, $tid) . "#pid" . $post;
				$bestanswer_image = "mysupport_bestanswer.gif";
				eval("\$mysupport_bestanswer = \"".$templates->get('mysupport_jumpto_bestanswer')."\";");
			}
		}
	}
}

// loads the dropdown menu for inline thread moderation
function mysupport_inline_thread_moderation()
{
	global $mybb, $db, $lang, $templates, $foruminfo, $mysupport_inline_thread_moderation;
	
	$lang->load("mysupport");
	
	if(mysupport_usergroup("canmarksolved"))
	{
		$mysupport_solved = "<option value=\"mysupport_status_1\">-- " . $lang->solved . "</option>";
		$mysupport_not_solved = "<option value=\"mysupport_status_0\">-- " . $lang->not_solved . "</option>";
		if($mybb->settings['mysupportclosewhensolved'] != "never")
		{
			$mysupport_solved_and_close = "<option value=\"mysupport_status_3\">-- " . $lang->solved_close . "</option>";
		}
	}
	if(mysupport_usergroup("canmarktechnical"))
	{
		$mysupport_technical = "<option value=\"mysupport_status_2\">-- " . $lang->technical . "</option>";
		$mysupport_not_technical = "<option value=\"mysupport_status_4\">-- " . $lang->not_technical . "</option>";
	}
	
	$mysupport_assign = "";
	$assign_users = mysupport_get_assign_users();
	// only continue if there's one or more users that can be assigned threads
	if(!empty($assign_users))
	{
		foreach($assign_users as $assign_userid => $assign_username)
		{
			$mysupport_assign .= "<option value=\"mysupport_assign_" . intval($assign_userid) . "\">-- " . htmlspecialchars_uni($assign_username) . "</option>\n";
		}
	}
	
	$mysupport_priorities = "";
	$query = $db->simple_select("mysupport", "*", "type = 'priority'");
	$priorities_list = "";
	// only continue if there's any priorities
	if($db->num_rows($query) > 0)
	{
		while($priority = $db->fetch_array($query))
		{
			$mysupport_priorities .= "<option value=\"mysupport_priority_" . intval($priority['mid']) . "\">-- " . htmlspecialchars_uni($priority['name']) . "</option>\n";
		}
	}
	
	$mysupport_categories = "";
	$categories_users = mysupport_get_categories($foruminfo['fid']);
	// only continue if there's any priorities
	if(!empty($categories_users))
	{
		foreach($categories_users as $category_id => $category_name)
		{
			$mysupport_categories .= "<option value=\"mysupport_priority_" . intval($category_id) . "\">-- " . htmlspecialchars_uni($category_name) . "</option>\n";
		}
	}
	
	eval("\$mysupport_inline_thread_moderation = \"".$templates->get('mysupport_inline_thread_moderation')."\";");
}

// perform inline thread moderation on multiple threads
function mysupport_do_inline_thread_moderation()
{
	global $mybb;
	
	// we're hooking into the start of moderation.php, so if we're not submitting a MySupport action, exit now
	if(strpos($mybb->input['action'], "mysupport") === false)
	{
		return false;
	}
	
	verify_post_check($mybb->input['my_post_key']);
	
	global $db, $lang, $mod_log_action, $redirect;
	
	$lang->load("mysupport");
	
	$fid = intval($mybb->input['fid']);
	if(!is_moderator($fid, 'canmanagethreads'))
	{
		error_no_permission();
	}
	if($mybb->input['inlinetype'] == "search")
	{
		$type = "search";
		$id = $mybb->input['searchid'];
		$redirect_url = "search.php?action=results&sid=" . rawurlencode($id);
	}
	else
	{
		$type = "forum";
		$id = $fid;
		$redirect_url = get_forum_link($fid);
	}
	$threads = getids($id, $type);
	if(count($threads) < 1)
	{
		error($lang->error_inline_nothreadsselected);
	}
	clearinline($id, $type);
	
	// in a list of search results, you could see threads that aren't from a MySupport forum, but the MySupport options will always show in the inline moderation options regardless of this
	// this is a way of determining which of the selected threads from a list of search results are in a MySupport forum
	// this isn't necessary for inline moderation via the forum display, as the options only show in MySupport forums to begin with
	if($type == "search")
	{
		// list of MySupport forums
		$mysupport_forums = implode(",", array_map("intval", mysupport_forums()));
		$tids = implode(",", array_map("intval", $threads));
		// query all the threads that are in the list of TIDs and where the FID is also in the list of MySupport forums
		// this will knock out the non-MySupport threads
		$mysupport_threads = array();
		$query = $db->simple_select("threads", "tid", "fid IN (" . $db->escape_string($mysupport_forums) . ") AND tid IN (" . $db->escape_string($tids) . ")");
		while($tid = $db->fetch_field($query, "tid"))
		{
			$mysupport_threads[] = intval($tid);
		}
		$threads = $mysupport_threads;
		// if the new list of threads is empty, no MySupport threads have been selected
		if(count($threads) < 1)
		{
			error($lang->no_mysupport_threads_selected);
		}
	}
	
	$mod_log_action = "";
	$redirect = "";
	
	if(strpos($mybb->input['action'], "status") !== false)
	{
		$status = str_replace("mysupport_status_", "", $mybb->input['action']);
		if($status == 2 || $status == 4)
		{
			$perm = "canmarktechnical";
		}
		else
		{
			$perm = "canmarksolved";
		}
		// they don't have permission to perform this action, so go through the different statuses and show an error for the right one
		if(!mysupport_usergroup($perm))
		{
			switch($status)
			{
				case 1:
					error($lang->no_permission_mark_solved_multi);
					break;
				case 2:
					error($lang->no_permission_mark_technical_multi);
					break;
				case 3:
					error($lang->no_permission_mark_solved_close_multi);
					break;
				case 4:
					error($lang->no_permission_mark_nottechnical_multi);
					break;
				default:
					error($lang->no_permission_mark_notsolved_multi);
			}
		}
		else
		{
			mysupport_change_status($threads, $status, true);
		}
	}
	elseif(strpos($mybb->input['action'], "assign") !== false)
	{
		if(!mysupport_usergroup("canassign"))
		{
			error($lang->assign_no_perms);
			exit;
		}
		$assign = str_replace("mysupport_assign_", "", $mybb->input['action']);
		if($assign == 0)
		{
			// in the function to change the assigned user, -1 means removing; 0 is easier to put into the form than -1, so change it back here
			$assign = -1;
		}
		else
		{
			$assign_users = mysupport_get_assign_users();
			// -1 is what's used to unassign a thread so we need to exclude that
			if(!array_key_exists($assign, $assign_users))
			{
				error($lang->assign_invalid);
				exit;
			}
		}
		
		mysupport_change_assign($threads, $assign, true);
	}
	elseif(strpos($mybb->input['action'], "priority") !== false)
	{
		if(!mysupport_usergroup("cansetpriorities"))
		{
			error($lang->priority_no_perms);
			exit;
		}
		$priority = str_replace("mysupport_priority_", "", $mybb->input['action']);
		if($priority == 0)
		{
			// in the function to change the priority, -1 means removing; 0 is easier to put into the form than -1, so change it back here
			$priority = -1;
		}
		else
		{
			$query = $db->simple_select("mysupport", "mid", "type = 'priority'");
			$mids = array();
			while($mid = $db->fetch_field($query, "mid"))
			{
				$mids[] = intval($mid);
			}
			if(!in_array($priority, $mids))
			{
				error($lang->priority_invalid);
				exit;
			}
		}
		
		mysupport_change_priority($threads, $priority, true);
	}
	elseif(strpos($mybb->input['action'], "category") !== false)
	{
		$category = str_replace("mysupport_category_", "", $mybb->input['action']);
		if($category == 0)
		{
			// in the function to change the category, -1 means removing; 0 is easier to put into the form than -1, so change it back here
			$category = -1;
		}
		else
		{
			$categories = mysupport_get_categories($forum);
			if(!array_key_exists($category, $categories) && $category != "-1")
			{
				error($lang->category_invalid);
				exit;
			}
		}
		
		mysupport_change_category($threads, $category, true);
	}
	$mod_log_data = array(
		"fid" => intval($fid)
	);
	log_moderator_action($mod_log_data, $mod_log_action);
	redirect($redirect_url, $redirect);
}

// check if a user is denied support when they're trying to make a new thread
function mysupport_newthread()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $cache, $lang, $forum;
	
	// this is a MySupport forum and this user has been denied support
	if(mysupport_forum($forum['fid']) && $mybb->user['deniedsupport'] == 1)
	{
		// start the standard error message to show
		$deniedsupport_message = $lang->deniedsupport;
		// if a reason has been set for this user
		if($mybb->user['deniedsupportreason'] != 0)
		{
			$query = $db->simple_select("mysupport", "name", "mid = '" . intval($mybb->user['deniedsupportreason']) . "'");
			$deniedsupportreason = $db->fetch_field($query, "name");
			$deniedsupport_message .= "<br /><br />" . $lang->sprintf($lang->deniedsupport_reason, htmlspecialchars_uni($deniedsupportreason));
		}
		error($deniedsupport_message);
	}
}

// highlight the best answer from the thread and show the status of the thread in each post
function mysupport_postbit(&$post)
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $cache, $lang, $theme, $templates, $thread, $forum, $support_denial_reasons;
	
	$lang->load("mysupport");
	
	if(mysupport_forum($forum['fid']))
	{
		if($mybb->settings['enablemysupportbestanswer'] == 1)
		{
			$post['mysupport_bestanswer_highlight'] = "";
			if($thread['bestanswer'] == $post['pid'] && $post['visible'] == 1)
			{
				$post['mysupport_bestanswer_highlight'] = " mysupport_bestanswer_highlight";
			}
			
			$post['mysupport_bestanswer'] = "";
			if($mybb->user['uid'] == $thread['uid'])
			{
				if($thread['bestanswer'] == $post['pid'])
				{
					$bestanswer_img = "mysupport_bestanswer";
					$bestanswer_alt = $lang->unbestanswer_img_alt;
					$bestanswer_title = $lang->unbestanswer_img_title;
					$bestanswer_desc = $lang->unbestanswer_img_alt;
				}
				else
				{
					$bestanswer_img = "mysupport_unbestanswer";
					$bestanswer_alt = $lang->bestanswer_img_alt;
					$bestanswer_title = $lang->bestanswer_img_title;
					$bestanswer_desc = $lang->bestanswer_img_alt;
				}
				
				eval("\$post['mysupport_bestanswer'] = \"".$templates->get('mysupport_bestanswer')."\";");
			}
		}
		
		// we only want to do this if it's not been highlighted as the best answer; that takes priority over this
		if(empty($post['mysupport_bestanswer_highlight']))
		{
			if($mybb->settings['mysupporthighlightstaffposts'] == 1 && $post['visible'] == 1)
			{
				$post['mysupport_staff_highlight'] = "";
				$post_groups = array_merge(array($post['usergroup']), explode(",", $post['additionalgroups']));
				// various checks to see if they should be considered staff or not
				if(mysupport_usergroup("canmarksolved", $post_groups) || mysupport_usergroup("canmarktechnical", $post_groups) || is_moderator($forum['fid'], "", $post['uid']))
				{
					$post['mysupport_staff_highlight'] = " mysupport_staff_highlight";
				}
			}
		}
		
		if($mybb->settings['enablemysupportsupportdenial'] == 1)
		{
			$post['mysupport_deny_support_post'] = "";
			$denied_text = $denied_text_desc = "";
			
			if($post['deniedsupport'] == 1)
			{
				$denied_text = $lang->denied_support;
				if(mysupport_usergroup("canmanagesupportdenial"))
				{
					$denied_text_desc = $lang->sprintf($lang->revoke_from, htmlspecialchars_uni($post['username']));
					if(array_key_exists($post['deniedsupportreason'], $support_denial_reasons))
					{
						$denied_text .= " " . $lang->sprintf($lang->deniedsupport_reason, htmlspecialchars_uni($support_denial_reasons[$post['deniedsupportreason']]));
					}
					$denied_text .= " " . $lang->denied_support_click_to_edit_revoke;
					eval("\$post['mysupport_deny_support_post'] = \"".$templates->get('mysupport_deny_support_post_linked')."\";");
				}
				else
				{
					$denied_text_desc = $lang->denied_support;
					eval("\$post['mysupport_deny_support_post'] = \"".$templates->get('mysupport_deny_support_post')."\";");
				}
			}
			else
			{
				if(mysupport_usergroup("canmanagesupportdenial"))
				{
					$post_groups = array_merge(array($post['usergroup']), explode(",", $post['additionalgroups']));
					// various checks to see if they should be considered staff or not - if they are, don't show this for this user
					if(!(mysupport_usergroup("canmarksolved", $post_groups) || mysupport_usergroup("canmarktechnical", $post_groups) || is_moderator($forum['fid'], "", $post['uid'])))
					{
						$denied_text = $denied_text_desc = $lang->sprintf($lang->deny_support_to, htmlspecialchars_uni($post['username']));
						eval("\$post['mysupport_deny_support_post'] = \"".$templates->get('mysupport_deny_support_post_linked')."\";");
					}
				}
			}
		}
		
		$post['mysupport_status'] = mysupport_get_display_status($thread['status'], $thread['statustime'], $thread['uid']);
	}
}

// show a notice for technical and/or assigned threads
function mysupport_notices()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $cache, $lang, $theme, $templates, $forum, $thread, $mysupport_tech_notice, $mysupport_assign_notice;
	
	$lang->load("mysupport");
	
	// this function does both the technical threads alert and the assigned threads alert
	// both similar enough to keep in one function but different enough to be separated into two chunks
	
	// some code that's used in both, work out now
	
	// check for THIS_SCRIPT so it doesn't execute if we're viewing the technical threads list in the MCP or support threads in the UCP with an FID
	if(($mybb->input['fid'] || $mybb->input['tid']) && THIS_SCRIPT != "modcp.php" && THIS_SCRIPT != "usercp.php")
	{
		if($mybb->input['fid'])
		{
			$fid = intval($mybb->input['fid']);
		}
		else
		{
			$tid = intval($mybb->input['tid']);
			$thread_info = get_thread($tid);
			$fid = $thread_info['fid'];
		}
	}
	else
	{
		$fid = "";
	}
	
	// the technical threads notice
	$mysupport_tech_notice = "";
	// is it enabled??
	if($mybb->settings['enablemysupport'] == 1 && $mybb->settings['enablemysupporttechnical'] == 1 && $mybb->settings['mysupporttechnicalnotice'] != "off")
	{
		// this user is in an allowed usergroup??
		if(mysupport_usergroup("canseetechnotice"))
		{
			// the notice is showing on all pages
			if($mybb->settings['mysupporttechnicalnotice'] == "global")
			{
				// count for the entire forum
				$technical_count_global = mysupport_get_count("technical");
			}
			
			// if the notice is enabled, it'll at least show in the forums containing technical threads
			if(!empty($fid))
			{
				// count for the forum we're in now
				$technical_count_forum = mysupport_get_count("technical", $fid);
			}
			
			$notice_url = "modcp.php?action=technicalthreads";
			
			if($technical_count_forum > 0)
			{
				$notice_url .= "&amp;fid=" . $fid;
			}
			
			// now to show the notice itself
			// it's showing globally
			if($mybb->settings['mysupporttechnicalnotice'] == "global")
			{
				if($technical_count_global == 1)
				{
					$threads_text = $lang->mysupport_thread;
				}
				else
				{
					$threads_text = $lang->mysupport_threads;
				}
				
				// we're in a forum/thread, and the count for this forum, generated above, is more than 0, show the global count and forum count
				if(!empty($fid) && $technical_count_forum > 0)
				{
					$notice_text = $lang->sprintf($lang->technical_global_forum, intval($technical_count_global), $threads_text, intval($technical_count_forum));
				}
				// either there's no forum/thread, or there is but there's no tech threads in this forum, just show the global count
				else
				{
					$notice_text = $lang->sprintf($lang->technical_global, intval($technical_count_global), $threads_text);
				}
				
				if($technical_count_global > 0)
				{
					eval("\$mysupport_tech_notice = \"".$templates->get('mysupport_notice')."\";");
				}
			}
			// it's only showing in the relevant forums, if necessary
			elseif($mybb->settings['mysupporttechnicalnotice'] == "specific")
			{
				if($technical_count_forum == 1)
				{
					$threads_text = $lang->mysupport_thread;
				}
				else
				{
					$threads_text = $lang->mysupport_threads;
				}
				
				// we're inside a forum/thread and the count for this forum, generated above, is more than 0, show the forum count
				if(!empty($fid) && $technical_count_forum > 0)
				{
					$notice_text = $lang->sprintf($lang->technical_forum, intval($technical_count_forum), $threads_text);
					eval("\$mysupport_tech_notice = \"".$templates->get('mysupport_notice')."\";");
				}
			}
		}
	}
	
	if($mybb->settings['enablemysupport'] == 1 && $mybb->settings['enablemysupportassign'] == 1)
	{
		// this user is in an allowed usergroup??
		if(mysupport_usergroup("canbeassigned"))
		{
			$assigned = mysupport_get_count("assigned");
			if($assigned > 0)
			{
				if($assigned == 1)
				{
					$threads_text = $lang->mysupport_thread;
				}
				else
				{
					$threads_text = $lang->mysupport_threads;
				}
				
				$notice_url = "usercp.php?action=assignedthreads";
				
				if(!empty($fid))
				{
					$assigned_forum = mysupport_get_count("assigned", $fid);
				}
				if($assigned_forum > 0)
				{
					$notice_text = $lang->sprintf($lang->assign_forum, intval($assigned), $threads_text, intval($assigned_forum));
					$notice_url .= "&amp;fid=" . $fid;
				}
				else
				{
					$notice_text = $lang->sprintf($lang->assign_global, intval($assigned), $threads_text);
				}
				
				eval("\$mysupport_assign_notice = \"".$templates->get('mysupport_notice')."\";");
			}
		}
	}
}

// show a list of threads requiring technical attention, assigned threads, or support threads
function mysupport_thread_list()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $db, $cache, $lang, $theme, $templates, $forum, $headerinclude, $header, $footer, $usercpnav, $modcp_nav, $threads_list, $priorities, $mysupport_priority_classes;
	
	$lang->load("mysupport");
	
	// checks if we're in the Mod CP, technical threads are enabled, and we're viewing the technical threads list...
	// ... or we're in the User CP, the ability to view a list of support threads is enabled, and we're viewing that list
	if((THIS_SCRIPT == "modcp.php" && $mybb->settings['enablemysupporttechnical'] == 1 && $mybb->input['action'] == "technicalthreads") || (THIS_SCRIPT == "usercp.php" && (($mybb->settings['mysupportthreadlist'] == 1 && ($mybb->input['action'] == "supportthreads" || !$mybb->input['action'])) || ($mybb->settings['enablemysupportassign'] == 1 && $mybb->input['action'] == "assignedthreads"))))
	{
		// add to navigation
		if(THIS_SCRIPT == "modcp.php")
		{
			add_breadcrumb($lang->thread_list_title_tech, "modcp.php?action=technicalthreads");
		}
		elseif(THIS_SCRIPT == "usercp.php")
		{
			if($mybb->input['action'] == "assignedthreads")
			{
				add_breadcrumb($lang->thread_list_title_assign, "usercp.php?action=assignedthreads");
			}
			elseif($mybb->input['action'] == "supportthreads")
			{
				add_breadcrumb($lang->thread_list_title_solved, "usercp.php?action=supportthreads");
			}
		}
		
		// load the priorities and generate the CSS classes
		mysupport_forumdisplay_searchresults();
		
		// if we have a forum in the URL, we're only dealing with threads in that forum
		// set some stuff for this forum that will be used in various places in this function
		if($mybb->input['fid'])
		{
			$forum_info = get_forum(intval($mybb->input['fid']));
			$list_where_sql = " AND t.fid = " . intval($mybb->input['fid']);
			$stats_where_sql = " AND fid = " . intval($mybb->input['fid']);
			// if we're viewing threads from a specific forum, add that to the nav too
			if(THIS_SCRIPT == "modcp.php")
			{
				add_breadcrumb($lang->sprintf($lang->thread_list_heading_tech_forum, htmlspecialchars_uni($forum_info['name'])), "modcp.php?action=technicalthreads&fid={$fid}");
			}
			elseif(THIS_SCRIPT == "usercp.php")
			{
				if($mybb->input['action'] == "assignedthreads")
				{
					add_breadcrumb($lang->sprintf($lang->thread_list_heading_assign_forum, htmlspecialchars_uni($forum_info['name'])), "usercp.php?action=supportthreads&fid={$fid}");
				}
				elseif($mybb->input['action'] == "supportthreads")
				{
					add_breadcrumb($lang->sprintf($lang->thread_list_heading_solved_forum, htmlspecialchars_uni($forum_info['name'])), "usercp.php?action=supportthreads&fid={$fid}");
				}
			}
		}
		else
		{
			$list_where_sql = "";
			$stats_where_sql = "";
		}
		
		// what forums is this allowed in??
		$forums = $cache->read("forums");
		$mysupport_forums = array();
		foreach($forums as $forum)
		{
			if(mysupport_forum($forum['fid']))
			{
				$mysupport_forums[] = intval($forum['fid']);
			}
		}
		
		$allowed_forums = "";
		$allowed_forums = implode(",", array_map("intval", $mysupport_forums));
		// if this string isn't empty, generate a variable to go in the query
		if(!empty($allowed_forums))
		{
			$list_in_sql = " AND t.fid IN (" . $db->escape_string($allowed_forums) . ")";
			$stats_in_sql = " AND fid IN (" . $db->escape_string($allowed_forums) . ")";
		}
		else
		{
			$list_in_sql = " AND t.fid IN (0)";
			$stats_in_sql = " AND fid IN (0)";
		}
		
		if($mybb->settings['mysupportstats'] == 1)
		{
			// only want to do this if we're viewing the list of support threads or technical threads
			if((THIS_SCRIPT == "usercp.php" && $mybb->input['action'] == "supportthreads") || (THIS_SCRIPT == "modcp.php" && $mybb->input['action'] == "technicalthreads"))
			{
				// show a small stats section
				if(THIS_SCRIPT == "modcp.php")
				{
					$query = $db->simple_select("threads", "status", "1=1{$stats_in_sql}{$stats_where_sql}");
					// 1=1 here because both of these variables could start with AND, so if there's nothing before that, there'll be an SQL error
				}
				elseif(THIS_SCRIPT == "usercp.php")
				{
					$query = $db->simple_select("threads", "status", "uid = '{$mybb->user['uid']}'{$stats_in_sql}{$stats_where_sql}");
				}
				if($db->num_rows($query) > 0)
				{
					$total_count = $solved_count = $notsolved_count = $technical_count = 0;
					while($threads = $db->fetch_array($query))
					{
						switch($threads['status'])
						{
							case 2:
								// we have a technical thread, count it
								++$technical_count;
								break;
							case 1:
								// we have a solved thread, count it
								++$solved_count;
								break;
								// we have an unsolved thread, count it
							default:
								++$notsolved_count;
						}
						// count the total
						++$total_count;
					}
					// if the total count is 0, set all the percentages to 0
					// otherwise we'd get 'division by zero' errors as it would try to divide by zero, and dividing by zero would cause the universe to implode
					if($total_count == 0)
					{
						$solved_percentage = $notsolved_percentage = $technical_percentage = 0;
					}
					// work out the percentages, so we know how big to make each bar
					else
					{
						$solved_percentage = round(($solved_count / $total_count) * 100);
						if($solved_percentage > 0)
						{
							$solved_row = "<td class=\"mysupport_bar_solved\" width=\"{$solved_percentage}%\"></td>";
						}
					
						$notsolved_percentage = round(($notsolved_count / $total_count) * 100);
						if($notsolved_percentage > 0)
						{
							$notsolved_row = "<td class=\"mysupport_bar_notsolved\" width=\"{$notsolved_percentage}%\"></td>";
						}
					
						$technical_percentage = round(($technical_count / $total_count) * 100);
						if($technical_percentage > 0)
						{
							$technical_row = "<td class=\"mysupport_bar_technical\" width=\"{$technical_percentage}%\"></td>";
						}
					}
					
					// get the title for the stats table
					if(THIS_SCRIPT == "modcp.php")
					{
						if($mybb->input['fid'])
						{
							$title_text = $lang->sprintf($lang->thread_list_stats_overview_heading_tech_forum, htmlspecialchars_uni($forum_info['name']));
						}
						else
						{
							$title_text = $lang->thread_list_stats_overview_heading_tech;
						}
					}
					elseif(THIS_SCRIPT == "usercp.php")
					{
						if($mybb->input['fid'])
						{
							$title_text = $lang->sprintf($lang->thread_list_stats_overview_heading_solved_forum, htmlspecialchars_uni($forum_info['name']));
						}
						else
						{
							$title_text = $lang->thread_list_stats_overview_heading_solved;
						}
					}
					
					// fill out the counts of the statuses of threads
					$overview_text = $lang->sprintf($lang->thread_list_stats_overview, $total_count, $solved_count, $notsolved_count, $technical_count);
					
					if(THIS_SCRIPT == "usercp.php")
					{
						$query = $db->simple_select("threads", "COUNT(*) AS newthreads", "lastpost > '" . intval($mybb->user['lastvisit']) . "' OR statustime > '" . intval($mybb->user['lastvisit']) . "'");
						$newthreads = $db->fetch_field($query, "newthreads");
						// there's 'new' support threads (reply or action since last visit) so show a link to give a list of just those
						if($newthreads != 0)
						{
							$newthreads_text = $lang->sprintf($lang->thread_list_newthreads, intval($newthreads));
							$newthreads = "<tr><td class=\"trow1\" align=\"center\"><a href=\"{$mybb->settings['bburl']}/usercp.php?action=supportthreads&amp;do=new\">{$newthreads_text}</a></td></tr>";
						}
						else
						{
							$newthreads = "";
						}
					}
					
					eval("\$stats = \"".$templates->get('mysupport_threadlist_stats')."\";");
				}
			}
		}
		
		// now get the relevant threads
		// the query for if we're in the Mod CP, getting all technical threads
		if(THIS_SCRIPT == "modcp.php")
		{
			$query = $db->query("
				SELECT t.tid, t.subject, t.fid, t.uid, t.username, t.lastpost, t.lastposter, t.lastposteruid, t.status, t.statusuid, t.statustime, t.priority, f.name
				FROM " . TABLE_PREFIX . "threads t
				INNER JOIN " . TABLE_PREFIX . "forums f
				ON(t.fid = f.fid AND t.status = '2'{$list_in_sql}{$list_where_sql})
				ORDER BY t.lastpost DESC
			");
		}
		// the query for if we're in the User CP, getting all support threads
		elseif(THIS_SCRIPT == "usercp.php")
		{
			$list_limit_sql = "";
			if($mybb->input['action'] == "assignedthreads")
			{
				// viewing assigned threads
				$column = "t.assign";
			}
			elseif($mybb->input['action'] == "supportthreads")
			{
				// viewing support threads
				$column = "t.uid";
				$list_where_sql .= " AND t.visible = '1'";
				if($mybb->input['do'] == "new")
				{
					$list_where_sql .= " AND (t.lastpost > '" . intval($mybb->user['lastvisit']) . "' OR t.statustime > '" . intval($mybb->user['lastvisit']) . "')";
				}
			}
			else
			{
				$column = "t.uid";
				$list_where_sql .= " AND t.visible = '1'";
				$list_limit_sql = "LIMIT 0, 5";
			}
			$query = $db->query("
				SELECT t.tid, t.subject, t.fid, t.uid, t.username, t.lastpost, t.lastposter, t.lastposteruid, t.status, t.statusuid, t.statustime, t.assignuid, t.priority, f.name
				FROM " . TABLE_PREFIX . "threads t
				INNER JOIN " . TABLE_PREFIX . "forums f
				ON(t.fid = f.fid AND {$column} = '{$mybb->user['uid']}'{$list_in_sql}{$list_where_sql})
				ORDER BY t.lastpost DESC
				{$list_limit_sql}
			");
		}
		
		$threads = "";
		if($db->num_rows($query) == 0)
		{
			$threads = "<tr><td class=\"trow1\" colspan=\"4\" align=\"center\">{$lang->thread_list_no_results}</td></tr>";
		}
		else
		{
			while($thread = $db->fetch_array($query))
			{
				$bgcolor = alt_trow();
				$priority_class = "";
				if($thread['priority'] != 0)
				{
					$priority_class = " class=\"mysupport_priority_" . strtolower(htmlspecialchars_uni(str_replace(" ", "_", $priorities[$thread['priority']]))) . "\"";
				}
				
				$thread['subject'] = htmlspecialchars_uni($thread['subject']);
				$thread['threadlink'] = get_thread_link($thread['tid']);
				$thread['forumlink'] = "<a href=\"" . get_forum_link($thread['fid']) . "\">" . htmlspecialchars_uni($thread['name']) . "</a>";
				$thread['profilelink'] = build_profile_link(htmlspecialchars_uni($thread['username']), intval($thread['uid']));
				
				$status_time_date = my_date($mybb->settings['dateformat'], intval($thread['statustime']));
				$status_time_time = my_date($mybb->settings['timeformat'], intval($thread['statustime']));
				// if we're in the Mod CP we only need the date and time it was marked technical, don't need the status on every line
				if(THIS_SCRIPT == "modcp.php")
				{
					if($mybb->settings['mysupportrelativetime'] == 1)
					{
						$status_time = mysupport_relative_time($thread['statustime']);
					}
					else
					{
						$status_time = $status_time_date . " " . $status_time_time;
					}
					// we're viewing technical threads, show who marked it as technical
					$status_uid = intval($thread['statusuid']);
					$status_user = get_user($status_uid);
					$status_username = $status_user['username'];
					$status_user_link = build_profile_link(htmlspecialchars_uni($status_username), intval($status_uid));
					$status_time .= ", " . $lang->sprintf($lang->mysupport_by, $status_user_link);
					
					$view_all_forum_text = $lang->sprintf($lang->thread_list_link_tech, htmlspecialchars_uni($thread['name']));
					$view_all_forum_link = "modcp.php?action=technicalthreads&amp;fid=" . intval($thread['fid']);
				}
				// if we're in the User CP we want to get the status...
				elseif(THIS_SCRIPT == "usercp.php")
				{
					$status = mysupport_get_friendly_status(intval($thread['status']));
					switch($thread['status'])
					{
						case 2:
							$class = "technical";
							break;
						case 1:
							$class = "solved";
							break;
						default:
							$class = "notsolved";
					}
					$status = "<span class=\"mysupport_status_{$class}\">" . htmlspecialchars_uni($status) . "</span>";
					// ... but we only want to show the time if the status is something other than Not Solved...
					if($thread['status'] != 0)
					{
						if($mybb->settings['mysupportrelativetime'] == 1)
						{
							$status_time = $status . " - " . mysupport_relative_time($thread['statustime']);
						}
						else
						{
							$status_time = $status . " - " . $status_time_date . " " . $status_time_time;
						}
					}
					// ... otherwise, if it is not solved, just show that
					else
					{
						$status_time = $status;
					}
					//if(!($mybb->input['action'] == "supportthreads" && $thread['status'] == 0))
					// we wouldn't want to do this if a thread was unsolved
					if((($mybb->input['action'] == "supportthreads" || !$mybb->input['action']) && $thread['status'] != 0) || $mybb->input['action'] == "assignedthreads")
					{
						if($mybb->input['action'] == "supportthreads" || !$mybb->input['action'])
						{
							// we're viewing support threads, show who marked it as solved or technical
							$status_uid = intval($thread['statusuid']);
							$by_lang = "mysupport_by";
						}
						else
						{
							// we're viewing assigned threads, show who assigned this thread to you
							$status_uid = intval($thread['assignuid']);
							$by_lang = "mysupport_assigned_by";
						}
						$status_user = get_user($status_uid);
						$status_user_link = build_profile_link(htmlspecialchars_uni($status_user['username']), intval($status_uid));
						$status_time .= ", " . $lang->sprintf($lang->$by_lang, $status_user_link);
					}
					
					if($mybb->input['action'] == "assignedthreads")
					{
						$view_all_forum_text = $lang->sprintf($lang->thread_list_link_assign, htmlspecialchars_uni($thread['name']));
						$view_all_forum_link = "usercp.php?action=assignedthreads&amp;fid=" . intval($thread['fid']);
					}
					else
					{
						$view_all_forum_text = $lang->sprintf($lang->thread_list_link_solved, htmlspecialchars_uni($thread['name']));
						$view_all_forum_link = "usercp.php?action=supportthreads&amp;fid=" . intval($thread['fid']);
					}
				}
				
				$thread['lastpostlink'] = get_thread_link($thread['tid'], 0, "lastpost");
				$lastpostdate = my_date($mybb->settings['dateformat'], intval($thread['lastpost']));
				$lastposttime = my_date($mybb->settings['timeformat'], intval($thread['lastpost']));
				$lastposterlink = build_profile_link(htmlspecialchars_uni($thread['lastposter']), intval($thread['lastposteruid']));
				
				eval("\$threads .= \"".$templates->get("mysupport_threadlist_thread")."\";");
			}
		}
		
		// if we have a forum in the URL, add a table footer with a link to all the threads
		if($mybb->input['fid'] || (THIS_SCRIPT == "usercp.php" && !$mybb->input['action']))
		{
			if(THIS_SCRIPT == "modcp.php")
			{
				$thread_list_heading = $lang->sprintf($lang->thread_list_heading_tech_forum, htmlspecialchars_uni($forum_info['name']));
				$view_all = $lang->thread_list_view_all_tech;
				$view_all_url = "modcp.php?action=technicalthreads";
			}
			elseif(THIS_SCRIPT == "usercp.php")
			{
				if($mybb->input['action'] == "assignedthreads")
				{
					$thread_list_heading = $lang->sprintf($lang->thread_list_heading_assign_forum, htmlspecialchars_uni($forum_info['name']));
					$view_all = $lang->thread_list_view_all_assign;
					$view_all_url = "usercp.php?action=assignedthreads";
				}
				else
				{
					if($mybb->input['action'] == "supportthreads")
					{
						$thread_list_heading = $lang->sprintf($lang->thread_list_heading_solved_forum, htmlspecialchars_uni($forum_info['name']));
					}
					elseif(!$mybb->input['action'])
					{
						$thread_list_heading = $lang->thread_list_heading_solved_latest;
					}
					$view_all = $lang->thread_list_view_all_solved;
					$view_all_url = "usercp.php?action=supportthreads";
				}
			}
			eval("\$view_all = \"".$templates->get("mysupport_threadlist_footer")."\";");
		}
		// if there's no forum in the URL, just get the standard table heading
		else
		{
			if(THIS_SCRIPT == "modcp.php")
			{
				$thread_list_heading = $lang->thread_list_heading_tech;
			}
			elseif(THIS_SCRIPT == "usercp.php")
			{
				if($mybb->input['action'] == "assignedthreads")
				{
					$thread_list_heading = $lang->thread_list_heading_assign;
				}
				else
				{
					if($mybb->input['do'] == "new")
					{
						$thread_list_heading = $lang->thread_list_heading_solved_new;
					}
					else
					{
						$thread_list_heading = $lang->thread_list_heading_solved;
					}
				}
			}
		}
		
		//get the page title, heading for the status of the thread column, and the relevant sidebar navigation
		if(THIS_SCRIPT == "modcp.php")
		{
			$thread_list_title = $lang->thread_list_title_tech;
			$status_heading = $lang->thread_list_time_tech;
			$navigation = "$modcp_nav";
		}
		elseif(THIS_SCRIPT == "usercp.php")
		{
			if($mybb->input['action'] == "assignedthreads")
			{
				$thread_list_title = $lang->thread_list_title_assign;
				$status_heading = $lang->thread_list_time_solved;
			}
			else
			{
				$thread_list_title = $lang->thread_list_title_solved;
				$status_heading = $lang->thread_list_time_assign;
			}
			$navigation = "$usercpnav";
		}
		
		eval("\$threads_list = \"".$templates->get("mysupport_threadlist_list")."\";");
		// we only want to output the page if we've got an action; i.e. we're not viewing the list on the User CP home page
		if($mybb->input['action'])
		{
			eval("\$threads_page = \"".$templates->get("mysupport_threadlist")."\";");
			output_page($threads_page);
		}
	}
}

function mysupport_modcp_support_denial()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	if(!mysupport_usergroup("canmanagesupportdenial") && $mybb->input['action'] == "supportdenial")
	{
		error_no_permission();
	}
	
	global $db, $cache, $lang, $theme, $templates, $headerinclude, $header, $footer, $modcp_nav, $mod_log_action, $redirect;
	
	$lang->load("mysupport");
	
	if($mybb->input['action'] == "supportdenial")
	{
		add_breadcrumb($lang->support_denial, "modcp.php?action=supportdenial");
		
		if($mybb->input['do'] == "do_denysupport")
		{
			verify_post_check($mybb->input['my_post_key']);
			
			if($mybb->settings['enablemysupportsupportdenial'] != 1)
			{
				error($lang->support_denial_not_enabled);
				exit;
			}
			
			// get username from UID
			// this is if we're revoking via the list of denied users, we specify a UID here
			if($mybb->input['uid'])
			{
				$uid = intval($mybb->input['uid']);
				$user = get_user($uid);
				$username = $user['username'];
			}
			// get UID from username
			// this is if we're denying support via the form, where we give a username
			elseif($mybb->input['username'])
			{
				$username = $db->escape_string($mybb->input['username']);
				$query = $db->simple_select("users", "uid", "username = '{$username}'");
				$uid = $db->fetch_field($query, "uid");
			}
			if($uid == 0 || !$username)
			{
				error($lang->support_denial_reason_invalid_user);
				exit;
			}
			
			if(isset($mybb->input['deniedsupportreason']))
			{
				$deniedsupportreason = intval($mybb->input['deniedsupportreason']);
			}
			else
			{
				$deniedsupportreason = 0;
			}
			
			if($mybb->input['tid'] != 0)
			{
				$tid = intval($mybb->input['tid']);
				$thread_info = get_thread($tid);
				$fid = $thread_info['fid'];
				
				$redirect_url = get_thread_link($tid);
			}
			else
			{
				$redirect_url = "modcp.php?action=supportdenial";
			}
			
			$mod_log_action = "";
			$redirect = "";
			
			$query = $db->simple_select("mysupport", "name", "type = 'deniedreason' AND mid = '" . intval($deniedsupportreason) . "'");
			// -1 is if we're revoking and 0 is no reason, so those are exempt
			if($db->num_rows($query) != 1 && $deniedsupportreason != -1 && $deniedsupportreason != 0)
			{
				error($lang->support_denial_reason_invalid_reason);
			}
			elseif($deniedsupportreason == -1)
			{
				$update = array(
					"deniedsupport" => 0,
					"deniedsupportreason" => 0,
					"deniedsupportuid" => 0
				);
				$db->update_query("users", $update, "uid = '" . intval($uid) . "'");
				
				mysupport_mod_log_action(11, $lang->sprintf($lang->deny_support_revoke_mod_log, $username));
				mysupport_redirect_message($lang->sprintf($lang->deny_support_revoke_success, htmlspecialchars_uni($username)));
			}
			else
			{
				$update = array(
					"deniedsupport" => 1,
					"deniedsupportreason" => intval($deniedsupportreason),
					"deniedsupportuid" => intval($mybb->user['uid'])
				);
				$db->update_query("users", $update, "uid = '" . intval($uid) . "'");
				
				if($deniedsupportreason != 0)
				{
					$deniedsupportreason = $db->fetch_field($query, "name");
					mysupport_mod_log_action(11, $lang->sprintf($lang->deny_support_mod_log_reason, $username, $deniedsupportreason));
				}
				else
				{
					mysupport_mod_log_action(11, $lang->sprintf($lang->deny_support_mod_log, $username));
				}
				mysupport_redirect_message($lang->sprintf($lang->deny_support_success, htmlspecialchars_uni($username)));
			}
			if(!empty($mod_log_action))
			{
				$mod_log_data = array(
					"fid" => intval($fid),
					"tid" => intval($tid)
				);
				log_moderator_action($mod_log_data, $mod_log_action);
			}
			redirect($redirect_url, $redirect);
		}
		elseif($mybb->input['do'] == "denysupport")
		{
			if($mybb->settings['enablemysupportsupportdenial'] != 1)
			{
				error($lang->support_denial_not_enabled);
				exit;
			}
			
			$uid = intval($mybb->input['uid']);
			$tid = intval($mybb->input['tid']);
			
			$user = get_user($uid);
			$username = $user['username'];
			$user_link = build_profile_link(htmlspecialchars_uni($username), intval($uid), "blank");
			
			if($mybb->input['uid'])
			{
				$deny_support_to = $lang->sprintf($lang->deny_support_to, htmlspecialchars_uni($username));
			}
			else
			{
				$deny_support_to = $lang->deny_support_to_user;
			}
			
			add_breadcrumb($deny_support_to);
			
			$query = $db->simple_select("mysupport", "mid, name", "type = 'deniedreason'");
			$deniedreasons = "";
			$deniedreasons .= "<label for=\"deniedsupportreason\">{$lang->reason}:</label> <select name=\"deniedsupportreason\" id=\"deniedsupportreason\">\n";
			// if they've not been denied support yet or no reason was given, show an empty option that will be selected
			if($user['deniedsupport'] == 0 || $user['deniedsupportreason'] == 0)
			{
				$deniedreasons .= "<option value=\"0\"></option>\n";
			}
			// if there's one or more reasons set, show them in a dropdown
			while($deniedreason = $db->fetch_array($query))
			{
				$selected = "";
				// if a reason has been given, we'd be editing it, so this would select the current one
				if($user['deniedsupport'] == 1 && $user['deniedsupportreason'] == $deniedreason['mid'])
				{
					$selected = " selected=\"selected\"";
				}
				$deniedreasons .= "<option value=\"" . intval($deniedreason['mid']) . "\"{$selected}>" . htmlspecialchars_uni($deniedreason['name']) . "</option>\n";
			}
			$deniedreasons .= "<option value=\"0\">{$lang->support_denial_reasons_none}</option>\n";
			// if they've been denied support, give an option to revoke it
			if($user['deniedsupport'] == 1)
			{
				$deniedreasons .= "<option value=\"0\">-----</option>\n";
				$deniedreasons .= "<option value=\"-1\">{$lang->revoke}</option>\n";
			}
			$deniedreasons .= "</select>\n";
			
			eval("\$deny_support = \"".$templates->get('mysupport_deny_support_deny')."\";");
			eval("\$deny_support_page = \"".$templates->get('mysupport_deny_support')."\";");
			output_page($deny_support_page);
		}
		else
		{
			$query = $db->write_query("
				SELECT u1.username AS support_denied_username, u1.uid AS support_denied_uid, u2.username AS support_denier_username, u2.uid AS support_denier_uid, m.name AS support_denied_reason
				FROM " . TABLE_PREFIX . "users u
				LEFT JOIN " . TABLE_PREFIX . "mysupport m ON (u.deniedsupportreason = m.mid)
				LEFT JOIN " . TABLE_PREFIX . "users u1 ON (u1.uid = u.uid)
				LEFT JOIN " . TABLE_PREFIX . "users u2 ON (u2.uid = u.deniedsupportuid)
				WHERE u.deniedsupport = '1'
			");
			
			if($db->num_rows($query) > 0)
			{
				while($denieduser = $db->fetch_array($query))
				{
					$bgcolor = alt_trow();
					
					$support_denied_user = build_profile_link(htmlspecialchars_uni($denieduser['support_denied_username']), intval($denieduser['support_denied_uid']));
					$support_denier_user = build_profile_link(htmlspecialchars_uni($denieduser['support_denier_username']), intval($denieduser['support_denier_uid']));
					if(empty($denieduser['support_denied_reason']))
					{
						$support_denial_reason = $lang->support_denial_no_reason;
					}
					else
					{
						$support_denial_reason = $denieduser['support_denied_reason'];
					}
					eval("\$denied_users .= \"".$templates->get('mysupport_deny_support_list_user')."\";");
				}
			}
			else
			{
				$denied_users = "<tr><td class=\"trow1\" align=\"center\" colspan=\"5\">{$lang->support_denial_no_users}</td></tr>";
			}
			
			eval("\$deny_support = \"".$templates->get('mysupport_deny_support_list')."\";");
			eval("\$deny_support_page = \"".$templates->get('mysupport_deny_support')."\";");
			output_page($deny_support_page);
		}
	}
}

function mysupport_usercp_options()
{
	global $mybb, $db, $lang, $templates, $mysupport_usercp_options;
	
	if($mybb->settings['mysupportdisplaytypeuserchange'] == 1)
	{
		if($mybb->input['action'] == "do_options")
		{
			$update = array(
				"mysupportdisplayastext" => intval($mybb->input['mysupportdisplayastext'])
			);
			
			$db->update_query("users", $update, "uid = '" . intval($mybb->user['uid']) . "'");
		}
		elseif($mybb->input['action'] == "options")
		{
			$lang->load("mysupport");
			
			if($mybb->settings['enablemysupport'] == 1)
			{
				$mysupportdisplayastextcheck = "";
				if($mybb->user['mysupportdisplayastext'] == 1)
				{
					$mysupportdisplayastextcheck = " checked=\"checked\"";
				}
			}
			
			eval("\$mysupport_usercp_options = \"".$templates->get('mysupport_usercp_options')."\";");
		}
	}
}

function mysupport_navoption()
{
	global $mybb;
	
	if($mybb->settings['enablemysupport'] != 1)
	{
		return;
	}
	
	global $lang, $templates, $usercpnav, $modcp_nav, $mysupport_nav_option;
	
	$lang->load("mysupport");
	
	if(THIS_SCRIPT == "modcp.php")
	{
		$mysupport_nav_option = "";
		$something_to_show = false;
		// is the technical threads feature enabled??
		if($mybb->settings['enablemysupporttechnical'] == 1)
		{
			$class1 = "modcp_nav_item";
			$class2 = "modcp_nav_tech_threads";
			$nav_link = "modcp.php?action=technicalthreads";
			$nav_text = $lang->thread_list_title_tech;
			// we need to eval this template now to generate the nav row with the correct details in it
			eval("\$mysupport_nav_option .= \"".$templates->get("mysupport_nav_option")."\";");
			$something_to_show = true;
		}
		// is support denial enabled??
		if($mybb->settings['enablemysupportsupportdenial'] == 1)
		{
			$class1 = "modcp_nav_item";
			$class2 = "modcp_nav_deny_support";
			$nav_link = "modcp.php?action=supportdenial";
			$nav_text = $lang->support_denial;
			// we need to eval this template now to generate the nav row with the correct details in it
			eval("\$mysupport_nav_option .= \"".$templates->get("mysupport_nav_option")."\";");
			$something_to_show = true;
		}
		
		if($something_to_show)
		{
			// do a str_replace on the nav to display it; need to do a string replace as the hook we're using here is after $modcp_nav has been eval'd
			$modcp_nav = str_replace("{mysupport_nav_option}", $mysupport_nav_option, $modcp_nav);
		}
		else
		{
			// if the technical threads or support denial feature isn't enabled, replace the code in the template with nothing
			$modcp_nav = str_replace("{mysupport_nav_option}", "", $modcp_nav);
		}
	}
	// need to check for private.php too so it shows in the PM system - the usercp_menu_built hook is run after $mysupport_nav_option has been made so this will work for both
	elseif(THIS_SCRIPT == "usercp.php" || THIS_SCRIPT == "private.php")
	{
		$mysupport_nav_option = "";
		$something_to_show = false;
		// is the list of support threads enabled??
		if($mybb->settings['mysupportthreadlist'] == 1)
		{
			$class1 = "usercp_nav_item";
			$class2 = "usercp_nav_support_threads";
			$nav_link = "usercp.php?action=supportthreads";
			$nav_text = $lang->thread_list_title_solved;
			// add to the code for the option
			eval("\$mysupport_nav_option .= \"".$templates->get("mysupport_nav_option")."\";");
			$something_to_show = true;
		}
		// is assigning threads enabled??
		if($mybb->settings['enablemysupportassign'] == 1)
		{
			$class1 = "usercp_nav_item";
			$class2 = "usercp_nav_assigned_threads";
			$nav_link = "usercp.php?action=assignedthreads";
			$nav_text = $lang->thread_list_title_assign;
			// add to the code for the option
			eval("\$mysupport_nav_option .= \"".$templates->get("mysupport_nav_option")."\";");
			$something_to_show = true;
		}
		
		if($something_to_show)
		{
			// if we added either or both of the nav options above, do a str_replace on the nav to display it
			// need to do a string replace as the hook we're using here is after $usercpnav has been eval'd
			$usercpnav = str_replace("{mysupport_nav_option}", $mysupport_nav_option, $usercpnav);
		}
		else
		{
			// if we didn't add either of the nav options above, replace the code in the template with nothing
			$usercpnav = str_replace("{mysupport_nav_option}", "", $usercpnav);
		}
	}
}

function mysupport_friendly_wol(&$user_activity)
{
	global $user;
	
	if(my_strpos($user['location'], "modcp.php?action=technicalthreads") !== false)
	{
		$user_activity['activity'] = "modcp_techthreads";
	}
	elseif(my_strpos($user['location'], "usercp.php?action=supportthreads") !== false)
	{
		$user_activity['activity'] = "usercp_supportthreads";
	}
	elseif(my_strpos($user['location'], "modcp.php?action=supportdenial") !== false)
	{
		if(my_strpos($user['location'], "do=denysupport") !== false || my_strpos($user['location'], "do=do_denysupport") !== false)
		{
			$user_activity['activity'] = "modcp_supportdenial_deny";
		}
		else
		{
			$user_activity['activity'] = "modcp_supportdenial";
		}
	}
}

function mysupport_build_wol(&$plugin_array)
{
	global $lang;
	
	if($plugin_array['user_activity']['activity'] == "modcp_techthreads")
	{
		$plugin_array['location_name'] = $lang->mysupport_wol_technical;
	}
	elseif($plugin_array['user_activity']['activity'] == "usercp_supportthreads")
	{
		$plugin_array['location_name'] = $lang->mysupport_wol_support;
	}
	elseif($plugin_array['user_activity']['activity'] == "modcp_supportdenial")
	{
		$plugin_array['location_name'] = $lang->mysupport_wol_support_denial;
	}
	elseif($plugin_array['user_activity']['activity'] == "modcp_supportdenial_deny")
	{
		$plugin_array['location_name'] = $lang->mysupport_wol_support_denial_deny;
	}
}

function mysupport_settings_footer()
{
	global $mybb, $db;
	// we're viewing the form to change settings but not submitting it
	if($mybb->input["action"] == "change" && $mybb->request_method != "post")
	{
		$gid = mysupport_settings_gid();
		// if the settings group we're editing is the same as the gid for the MySupport group, or there's no gid (viewing all settings), echo the peekers
		if($mybb->input["gid"] == $gid || !$mybb->input['gid'])
		{
			echo '<script type="text/javascript">
	Event.observe(window, "load", function() {
	loadMySupportPeekers();
});
function loadMySupportPeekers()
{
	new Peeker($$(".setting_enablemysupporttechnical"), $("row_setting_mysupporthidetechnical"), /1/, true);
	new Peeker($$(".setting_enablemysupporttechnical"), $("row_setting_mysupporttechnicalnotice"), /1/, true);
	new Peeker($$(".setting_enablemysupportassign"), $("row_setting_mysupportassignpm"), /1/, true);
	new Peeker($$(".setting_enablemysupportassign"), $("row_setting_mysupportassignsubscribe"), /1/, true);
	new Peeker($("setting_mysupportpointssystem"), $("row_setting_mysupportpointssystemname"), /other/, false);
	new Peeker($("setting_mysupportpointssystem"), $("row_setting_mysupportpointssystemcolumn"), /other/, false);
	new Peeker($("setting_mysupportpointssystem"), $("row_setting_mysupportbestanswerpoints"), /[^none]/, false);
}
</script>';
		}
	}
}

function mysupport_admin_config_menu($sub_menu)
{
	global $lang;
	
	$lang->load("config_mysupport");
	
	$sub_menu[] = array("id" => "mysupport", "title" => $lang->mysupport, "link" => "index.php?module=config-mysupport");
	
	return $sub_menu;
}

function mysupport_admin_config_action_handler($actions)
{
	$actions['mysupport'] = array(
		"active" => "mysupport",
		"file" => "mysupport.php"
	);
	
	return $actions;
}

function mysupport_admin_config_permissions($admin_permissions)
{
	global $lang;
	
	$lang->load("config_mysupport");
	
	$admin_permissions['mysupport'] = $lang->can_manage_mysupport;
	
	return $admin_permissions;
}

// general functions

/**
 * Check is MySupport is enabled in this forum.
 *
 * @param int The FID of the thread.
 * @param bool Whether or not this is a MySupport forum.
**/
function mysupport_forum($fid)
{
	global $cache;
	
	$fid = intval($fid);
	$forum_info = get_forum($fid);
	
	// the parent list includes the ID of the forum itself so this will quickly check the forum and all it's parents
	// only slight issue is that the ID of this forum would be at the end of this list, so it'd check the parents first, but if it returns true, it returns true, doesn't really matter
	$forum_ids = explode(",", $forum_info['parentlist']);
	
	// load the forums cache
	$forums = $cache->read("forums");
	foreach($forums as $forum)
	{
		// if this forum is in the parent list
		if(in_array($forum['fid'], $forum_ids))
		{
			// if this is a MySupport forum, return true
			if($forum['mysupport'] == 1)
			{
				return true;
			}
		}
	}
	return false;
}

/**
 * Generates a list of all forums that have MySupport enabled.
 *
 * @param array Array of forums that have MySupport enabled.
**/
function mysupport_forums()
{
	global $cache;
	
	$forums = $cache->read("forums");
	$mysupport_forums = array();
	
	foreach($forums as $forum)
	{
		// if this forum/category has MySupport enabled, add it to the array
		if($forum['mysupport'] == 1)
		{
			if(!in_array($forum['fid'], $mysupport_forums))
			{
				$mysupport_forums[] = $forum['fid'];
			}
		}
		// if this forum/category hasn't got MySupport enabled...
		else
		{
			// ... go through the parent list...
			$parentlist = explode(",", $forum['parentlist']);
			foreach($parentlist as $parent)
			{
				// ... if this parent has MySupport enabled...
				if($forums[$parent]['mysupport'] == 1)
				{
					// ... add the original forum we're looking at to the list
					if(!in_array($forum['fid'], $mysupport_forums))
					{
						$mysupport_forums[] = $forum['fid'];
						break;
					}
					// this is for if we enable MySupport for a whole category; this will pick up all the forums inside that category and add them to the array
				}
			}
		}
	}
	
	return $mysupport_forums;
}

/**
 * Check the usergroups for MySupport permissions.
 *
 * @param string What permission we're checking.
 * @param int Usergroup of the user we're checking.
**/
function mysupport_usergroup($perm, $usergroups = array())
{
	global $mybb, $cache;
	
	// does this key even exist?? Check here if it does
	if(!array_key_exists($perm, $mybb->usergroup))
	{
		return false;
	}
	
	// if no usergroups are specified, we're checking our own usergroups
	if(empty($usergroups))
	{
		$usergroups = array_merge(array($mybb->user['usergroup']), explode(",", $mybb->user['additionalgroups']));
	}
	
	// load the usergroups cache
	$groups = $cache->read("usergroups");
	foreach($groups as $group)
	{
		// if this user is in this group
		if(in_array($group['gid'], $usergroups))
		{
			// if this group can perform this action, return true
			if($group[$perm] == 1)
			{
				return true;
			}
		}
	}
	return false;
}

/**
 * Change the status of a thread.
 *
 * @param array Information about the thread.
 * @param int The new status.
 * @param bool If this is changing the status of multiple threads.
**/
function mysupport_change_status($thread_info, $status = 0, $multiple = false)
{
	global $mybb, $db, $lang, $cache;
	
	$status = intval($status);
	if($status == 3)
	{
		// if it's 3, we're solving and closing, but we'll just check for regular solving in the list of things to log
		// saves needing to have a 3, for the solving and closing option, in the setting of what to log
		// then below it'll check if 1 is in the list of things to log; 1 is normal solving, so if that's in the list, it'll log this too
		$log_status = 1;
	}
	else
	{
		$log_status = $status;
	}
	
	$tid = intval($thread_info['tid']);
	$old_status = intval($thread_info['status']);
	
	// get the friendly version of the status for the redirect message and mod log
	$friendly_old_status = "'" . mysupport_get_friendly_status($old_status) . "'";
	$friendly_new_status = "'" . mysupport_get_friendly_status($status) . "'";
	
	if($multiple)
	{
		mysupport_mod_log_action($log_status, $lang->sprintf($lang->status_change_mod_log_multi, count($thread_info), $friendly_new_status));
		mysupport_redirect_message($lang->sprintf($lang->status_change_success_multi, count($thread_info), htmlspecialchars_uni($friendly_new_status)));
	}
	else
	{
		mysupport_mod_log_action($log_status, $lang->sprintf($lang->status_change_mod_log, $friendly_new_status));
		mysupport_redirect_message($lang->sprintf($lang->status_change_success, htmlspecialchars_uni($friendly_old_status), htmlspecialchars_uni($friendly_new_status)));
	}
	
	$move_fid = "";
	$forums = $cache->read("forums");
	foreach($forums as $forum)
	{
		if(!empty($forum['mysupportmove']) && $forum['mysupportmove'] != 0)
		{
			$move_fid = intval($forum['fid']);
			break;
		}
	}
	// are we marking it as solved and is it being moved??
	if(!empty($move_fid) && ($status == 1 || $status == 3))
	{
		if($mybb->settings['mysupportmoveredirect'] == "none")
		{
			$move_type = "move";
			$redirect_time = 0;
		}
		else
		{
			$move_type = "redirect";
			if($mybb->settings['mysupportmoveredirect'] == "forever")
			{
				$redirect_time = 0;
			}
			else
			{
				$redirect_time = intval($mybb->settings['mysupportmoveredirect']);
			}
		}
		if($multiple)
		{
			$tids = $thread_info;
		}
		else
		{
			$tids = array($thread_info['tid']);
		}
		require_once MYBB_ROOT."inc/class_moderation.php";
		$moderation = new Moderation;
		// the reason it loops through using move_thread is because move_threads doesn't give the option for a redirect
		// if it's not a multiple thread it will just loop through once as there'd only be one value in the array
		foreach($tids as $tid)
		{
			$tid = intval($tid);
			$moderation->move_thread($tid, $move_fid, $move_type, $redirect_time);
		}
	}
	
	if($status == 3 || ($status == 1 && $mybb->settings['mysupportclosewhensolved'] == "always"))
	{
		// the bit after || here is for if we're marking as solved via marking a post as the best answer, it will close if it's set to always close
		// the incoming status would be 1 but we need to close it if necessary
		$status_update = array(
			"closed" => 1,
			"status" => 1,
			"statusuid" => intval($mybb->user['uid']),
			"statustime" => TIME_NOW,
			"assign" => 0,
			"assignuid" => 0,
			"priority" => 0
		);
	}
	elseif($status == 0)
	{
		// if we're marking it as unsolved, a post may have been marked as the best answer when it was originally solved, best remove it, as well as rest everything else
		$status_update = array(
			"status" => 0,
			"statusuid" => 0,
			"statustime" => 0,
			"bestanswer" => 0
		);
	}
	elseif($status == 4)
	{
		/** if it's 4, it's because it was marked as being not technical after being marked technical
		 ** basically put back to the original status of not solved (0)
		 ** however it needs to be 4 so we can differentiate between this action (technical => not technical), and a user marking it as not solved
		 ** because both of these options eventually set it back to 0
		 ** so the mod log entry will say the correct action as the status was 4 and it used that
		 ** now that the log has been inserted we can set it to 0 again for the thread update query so it's marked as unsolved **/
		$status_update = array(
			"status" => 0,
			"statusuid" => 0,
			"statustime" => 0
		);
	}
	elseif($status == 2)
	{
		$status_update = array(
			"status" => 2,
			"statusuid" => intval($mybb->user['uid']),
			"statustime" => TIME_NOW
		);
	}
	// if not, it's being marked as solved
	else
	{
		$status_update = array(
			"status" => 1,
			"statusuid" => intval($mybb->user['uid']),
			"statustime" => TIME_NOW,
			"assign" => 0,
			"assignuid" => 0,
			"priority" => 0
		);
	}
	if($multiple)
	{
		$tids = implode(",", array_map("intval", $thread_info));
		$where_sql = "tid IN (" . $db->escape_string($tids) . ")";
	}
	else
	{
		$where_sql = "tid = '" . intval($tid) . "'";
	}
	$db->update_query("threads", $status_update, $where_sql);
}

/**
 * Change who a thread is assigned to.
 *
 * @param array Information about the thread.
 * @param int The UID of who we're assigning it to now.
 * @param bool If this is changing the assigned user of multiple threads.
**/
function mysupport_change_assign($thread_info, $assign, $multiple = false)
{
	global $mybb, $db, $lang;
	
	$fid = intval($thread_info['fid']);
	$tid = intval($thread_info['tid']);
	$old_assign = intval($thread_info['assign']);
	$assign = $db->escape_string($assign);
	
	// this'll be the same wherever so set this here
	if($multiple)
	{
		$tids = implode(",", array_map("intval", $thread_info));
		$where_sql = "tid IN (" . $db->escape_string($tids) . ")";
	}
	else
	{
		$where_sql = "tid = '" . intval($tid) . "'";
	}
	
	// if we're unassigning it
	if($assign == "-1")
	{
		$update = array(
			"assign" => 0,
			"assignuid" => 0
		);
		// remove the assignment on the thread
		$db->update_query("threads", $update, $where_sql);
		
		// get information on who it was assigned to
		$user = get_user($old_assign);
		
		if($multiple)
		{
			mysupport_mod_log_action(6, $lang->sprintf($lang->unassigned_from_success_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->unassigned_from_success_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(6, $lang->sprintf($lang->unassigned_from_success, $user['username']));
			mysupport_redirect_message($lang->sprintf($lang->unassigned_from_success, htmlspecialchars_uni($user['username'])));
		}
	}
	// if we're assigning it or changing the assignment
	else
	{
		$update = array(
			"assign" => intval($assign),
			"assignuid" => intval($mybb->user['uid'])
		);
		if($multiple)
		{
			// when assigning via the form in a thread, you can't assign a thread if it's solved
			// here, it's not as easy to check for that; instead, only assign a thread if it isn't solved
			$where_sql .= " AND status != '1'";
		}
		// assign the thread
		$db->update_query("threads", $update, $where_sql);
		
		$user = get_user($assign);
		$username = $db->escape_string($user['username']);
		
		if($mybb->settings['mysupportassignpm'] == 1)
		{
			// send the PM
			mysupport_send_assign_pm($assign, $fid, $tid);
		}
		
		if($mybb->settings['mysupportassignsubscribe'] == 1)
		{
			if($multiple)
			{
				$tids = $thread_info;
			}
			else
			{
				$tids = array($thread_info['tid']);
			}
			foreach($tids as $tid)
			{
				$query = $db->simple_select("threadsubscriptions", "*", "uid = '{$assign}' AND tid = '{$tid}'");
				// only do this if they're not already subscribed
				if($db->num_rows($query) == 0)
				{
					if($user['subscriptionmethod'] == 2)
					{
						$subscription_method = 2;
					}
					// this is if their subscription method is 1 OR 0
					// done like this because this setting forces a subscription, but we'll only subscribe them via email if the user wants it
					else
					{
						$subscription_method = 1;
					}
					require_once MYBB_ROOT . "inc/functions_user.php";
					add_subscribed_thread($tid, $subscription_method, $assign);
				}
			}
		}
		
		if($multiple)
		{
			mysupport_mod_log_action(5, $lang->sprintf($lang->assigned_to_success_multi, count($thread_info), $user['username']));
			mysupport_redirect_message($lang->sprintf($lang->assigned_to_success_multi, count($thread_info), htmlspecialchars_uni($user['username'])));
		}
		else
		{
			mysupport_mod_log_action(5, $lang->sprintf($lang->assigned_to_success, $username));
			mysupport_redirect_message($lang->sprintf($lang->assigned_to_success, htmlspecialchars_uni($username)));
		}
	}
}

/**
 * Change the priority of a thread
 *
 * @param array Information about the thread.
 * @param int The ID of the new priority.
 * @param bool If this is changing the priority of multiple threads.
**/
function mysupport_change_priority($thread_info, $priority, $multiple = false)
{
	global $db, $lang;
	
	$tid = intval($thread_info['tid']);
	$priority = $db->escape_string($priority);
	
	$query = $db->simple_select("mysupport", "mid, name", "type = 'priority'");
	$priorities = array();
	while($priority_info = $db->fetch_array($query))
	{
		$priorities[$priority_info['mid']] = $priority_info['name'];
	}
	
	$new_priority = $priorities[$priority];
	$old_priority = $priorities[$thread_info['priority']];
	
	// this'll be the same wherever so set this here
	if($multiple)
	{
		$tids = implode(",", array_map("intval", $thread_info));
		$where_sql = "tid IN (" . $db->escape_string($tids) . ")";
	}
	else
	{
		$where_sql = "tid = '" . intval($tid) . "'";
	}
	
	if($priority == "-1")
	{
		$update = array(
			"priority" => 0
		);
		$db->update_query("threads", $update, $where_sql);
		
		if($multiple)
		{
			mysupport_mod_log_action(8, $lang->sprintf($lang->priority_remove_success_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->priority_remove_success_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(8, $lang->sprintf($lang->priority_remove_success, $old_priority));
			mysupport_redirect_message($lang->sprintf($lang->priority_remove_success, htmlspecialchars_uni($old_priority)));
		}
	}
	else
	{
		$update = array(
			"priority" => intval($priority)
		);
		if($multiple)
		{
			// when setting a priority via the form in a thread, you can't give a thread a priority if it's solved
			// here, it's not as easy to check for that; instead, only set the priority if the thread isn't solved
			$where_sql .= " AND status != '1'";
		}
		$db->update_query("threads", $update, $where_sql);
		
		if($multiple)
		{
			mysupport_mod_log_action(6, $lang->sprintf($lang->priority_change_success_to_multi, count($thread_info), $new_priority));
			mysupport_redirect_message($lang->sprintf($lang->priority_change_success_to_multi, count($thread_info), $new_priority));
		}
		else
		{
			if($thread['priority'] == 0)
			{
				mysupport_mod_log_action(7, $lang->sprintf($lang->priority_change_success_to, $new_priority));
				mysupport_redirect_message($lang->sprintf($lang->priority_change_success_to, htmlspecialchars_uni($new_priority)));
			}
			else
			{
				mysupport_mod_log_action(7, $lang->sprintf($lang->priority_change_success_fromto, $old_priority, $new_priority));
				mysupport_redirect_message($lang->sprintf($lang->priority_change_success_fromto, htmlspecialchars_uni($old_priority), htmlspecialchars_uni($new_priority)));
			}
		}
	}
}

/**
 * Change the category of a thread
 *
 * @param array Information about the thread.
 * @param int The ID of the new category.
 * @param bool If this is changing the priority of multiple threads.
**/
function mysupport_change_category($thread_info, $category, $multiple = false)
{
	global $db, $lang;
	
	$tid = intval($thread_info['tid']);
	$category = $db->escape_string($category);
	
	$query = $db->simple_select("threadprefixes", "pid, prefix");
	$categories = array();
	while($category_info = $db->fetch_array($query))
	{
		$categories[$category_info['pid']] = htmlspecialchars_uni($category_info['prefix']);
	}
	
	$new_category = $categories[$category];
	$old_category = $categories[$thread_info['prefix']];
	
	// this'll be the same wherever so set this here
	if($multiple)
	{
		$tids = implode(",", array_map("intval", $thread_info));
		$where_sql = "tid IN (" . $db->escape_string($tids) . ")";
	}
	else
	{
		$where_sql = "tid = '" . intval($tid) . "'";
	}
	
	if($category == "-1")
	{
		$update = array(
			"prefix" => 0
		);
		$db->update_query("threads", $update, $where_sql);
		
		if($multiple)
		{
			mysupport_mod_log_action(10, $lang->sprintf($lang->category_remove_success_multi, count($thread_info)));
			mysupport_redirect_message($lang->sprintf($lang->category_remove_success_multi, count($thread_info)));
		}
		else
		{
			mysupport_mod_log_action(10, $lang->sprintf($lang->category_remove_success, $old_category));
			mysupport_redirect_message($lang->sprintf($lang->category_remove_success, htmlspecialchars_uni($old_category)));
		}
	}
	else
	{
		$update = array(
			"prefix" => $category
		);
		$db->update_query("threads", $update, $where_sql);
		
		if($multiple)
		{
			mysupport_mod_log_action(9, $lang->sprintf($lang->category_change_success_to_multi, count($thread_info), $new_category));
			mysupport_redirect_message($lang->sprintf($lang->category_change_success_to_multi, count($thread_info), htmlspecialchars_uni($new_category)));
		}
		else
		{
			if($thread['prefix'] == 0)
			{
				mysupport_mod_log_action(9, $lang->sprintf($lang->category_change_success_to, $new_category));
				mysupport_redirect_message($lang->sprintf($lang->category_change_success_to, htmlspecialchars_uni($new_category)));
			}
			else
			{
				mysupport_mod_log_action(9, $lang->sprintf($lang->category_change_success_fromto, $old_category, $new_category));
				mysupport_redirect_message($lang->sprintf($lang->category_change_success_fromto, htmlspecialchars_uni($old_category), htmlspecialchars_uni($new_category)));
			}
		}
	}
}

/**
 * Add to the moderator log message.
 *
 * @param int The ID of the log action.
 * @param string The message to add.
**/
function mysupport_mod_log_action($id, $message)
{
	global $mybb, $mod_log_action;
	
	$id = intval($id);
	$mysupportmodlog = explode(",", $mybb->settings['mysupportmodlog']);
	// if this action shouldn't be logged, return false
	if(!in_array($id, $mysupportmodlog))
	{
		return false;
	}
	// if the message isn't empty, add a space
	if(!empty($mod_log_action))
	{
		$mod_log_action .= " ";
	}
	$mod_log_action .= $message;
}

/**
 * Add to the redirect message.
 *
 * @param string The message to add.
**/
function mysupport_redirect_message($message)
{
	global $redirect;
	
	// if the message isn't empty, add a new line
	if(!empty($redirect))
	{
		$redirect .= "<br /><br />";
	}
	$redirect .= $message;
}

/**
 * Send a PM about a new assignment
 *
 * @param int The UID of who we're assigning it to now.
 * @param int The FID the thread is in.
 * @param int The TID of the thread.
**/
function mysupport_send_assign_pm($uid, $fid, $tid)
{
	global $mybb, $db, $lang;
	
	require_once MYBB_ROOT."inc/datahandlers/pm.php";
	$pmhandler = new PMDataHandler();
	
	$uid = intval($uid);
	$fid = intval($fid);
	$tid = intval($tid);
	
	$user_info = get_user($uid);
	$username = $user_info['username'];
	
	$forum_url = $mybb->settings['bburl'] . "/" . get_forum_link($fid);
	$forum_info = get_forum($fid);
	$forum_name = $forum_info['name'];
	
	$thread_url = $mybb->settings['bburl'] . "/" . get_thread_link($tid);
	$thread_info = get_thread($tid);
	$thread_name = $thread_info['subject'];
	
	$recipients_to = array($uid);
	$recipients_bcc = array();
	
	// are we assigning to someone other than ourselves??
	if($mybb->user['uid'] != $uid)
	{
		$assigned_by_user_url = $mybb->settings['bburl'] . "/" . get_profile_link($mybb->user['uid']);
		$assigned_by = $lang->sprintf($lang->assigned_by, $assigned_by_user_url, htmlspecialchars_uni($mybb->user['username']));
	}
	// else you assigned it to yourself
	else
	{
		$assigned_by = $lang->assigned_by_you;
	}
	
	$subject = $lang->assign_pm_subject;
	$message = $lang->sprintf($lang->assign_pm_message, htmlspecialchars_uni($username), $forum_url, htmlspecialchars_uni($forum_name), $thread_url, htmlspecialchars_uni($thread_name), $assigned_by, $mybb->settings['bburl']);
	
	$pm = array(
		"subject" => $subject,
		"message" => $message,
		"icon" => -1,
		"fromid" => 0,
		"toid" => $recipients_to,
		"bccid" => $recipients_bcc,
		"do" => '',
		"pmid" => '',
		"saveasdraft" => 0,
		"options" => array(
			"signature" => 1,
			"disablesmilies" => 0,
			"savecopy" => 0,
			"readreceipt" => 0
		)
	);
	$pmhandler->admin_override = 1;
	$pmhandler->set_data($pm);
	
	if($pmhandler->validate_pm())
	{
		$pmhandler->insert_pm();
	}
	else
	{
		error();
	}
}

/**
 * Get the relative time of when a thread was solved.
 *
 * @param int Timestamp of when the thread was solved.
 * @return string Relative time of when the thread was solved.
**/
function mysupport_relative_time($statustime)
{
	global $lang;
	
	$lang->load("mysupport");
	
	$time = TIME_NOW - $statustime;
	
	if($time <= 60)
	{
		return $lang->mysupport_just_now;
	}
	else
	{
		$options = array();
		if($time >= 864000)
		{
			$options['hours'] = false;
			$options['minutes'] = false;
			$options['seconds'] = false;
		}
		return nice_time($time) . " " . $lang->mysupport_ago;
	}
}

/**
 * Get the count of technical or assigned threads.
 *
 * @param int The FID we're in.
 * @return int The number of technical or assigned threads in this forum.
**/
function mysupport_get_count($type, $fid = 0)
{
	global $mybb, $db;
	
	$fid = intval($fid);
	
	if($type == "technical")
	{
		// there's no FID given so this is loading the total number of technical threads
		if($fid == 0)
		{
			// technical = 2
			$query = $db->simple_select("threads", "COUNT(*) AS technical", "status = '2'");
		}
		// we have an FID, so count the number of technical threads in this specific forum
		else
		{
			// technical = 2
			$query = $db->simple_select("threads", "COUNT(*) AS technical", "fid = '{$fid}' AND status = '2'");
		}
		
		$count = $db->fetch_field($query, "technical");
	}
	elseif($type == "assigned")
	{
		// there's no FID given so this is loading the total number of assigned threads
		if($fid == 0)
		{
			$query = $db->simple_select("threads", "COUNT(*) AS assigned", "assign = '{$mybb->user['uid']}'");
		}
		// we have an FID, so count the number of assigned threads in this specific forum
		else
		{
			$query = $db->simple_select("threads", "COUNT(*) AS assigned", "fid = '{$fid}' AND assign = '{$mybb->user['uid']}'");
		}
		
		$count = $db->fetch_field($query, "assigned");
	}
	
	return intval($count);
}

/**
 * Check if a points system is enabled for points system integration.
 *
 * @return bool Whether or not your chosen points system is enabled.
**/
function mysupport_points_system_enabled()
{
	global $mybb, $cache;
	
	$plugins = $cache->read("plugins");
	
	if($mybb->settings['mysupportpointssystem'] != "none")
	{
		if($mybb->settings['mysupportpointssystem'] == "other")
		{
			$mybb->settings['mysupportpointssystem'] = $mybb->settings['mysupportpointssystemname'];
		}
		return in_array($mybb->settings['mysupportpointssystem'], $plugins['active']);
	}
	return false;
}

/**
 * Update points for certain MySupport actions.
 *
 * @param int The number of points to add/remove.
 * @param int The UID of the user we're adding/removing points to/from.
 * @param bool Is this removing points?? Defaults to false as we'd be adding them most of the time.
**/
function mysupport_update_points($points, $uid, $removing = false)
{
	global $mybb, $db;
	
	$points = intval($points);
	$uid = intval($uid);
	
	switch($mybb->settings['mysupportpointssystem'])
	{
		case "myps":
			$column = "myps";
			break;
		case "newpoints":
			$column = "newpoints";
			break;
		case "other":
			$column = $db->escape_string($mybb->settings['mysupportpointssystemcolumn']);
			break;
		default:
			$column = "";
	}
	
	// if it somehow had to resort to the default option above or 'other' was selected but no custom column name was specified, don't run the query because it's going to create an SQL error, no column to update
	if(!empty($column))
	{
		if($removing)
		{
			$operator = "-";
		}
		else
		{
			$operator = "+";
		}
		
		$query = $db->write_query("UPDATE " . TABLE_PREFIX . "users SET {$column} = {$column} {$operator} '{$points}' WHERE uid = '{$uid}'");
	}
}

/**
 * Build an array of who can be assigned threads. Used to build the dropdown menus, and also check a valid user has been chosen.
 *
 * @return array Array of available categories.
**/
function mysupport_get_assign_users()
{
	global $db, $cache;
	
	// who can be assigned threads??
	$groups = $cache->read("usergroups");
	foreach($groups as $group)
	{
		if($group['canbeassigned'] == 1)
		{
			$assign_groups[] = intval($group['gid']);
		}
	}
	
	$assign_users = array();
	// only continue if there's one or more groups that can be assigned threads
	if(!empty($assign_groups))
	{
		$assigngroups = "";
		$assigngroups = implode(",", array_map("intval", $assign_groups));
		$assign_concat_sql = "";
		foreach($assign_groups as $assign_group)
		{
			if(!empty($assign_concat_sql))
			{
				$assign_concat_sql .= " OR ";
			}
			$assign_concat_sql .= "CONCAT(',',additionalgroups,',') LIKE '%,{$assign_group},%'";
		}
		
		$query = $db->simple_select("users", "uid, username", "usergroup IN (" . $db->escape_string($assigngroups) . ") OR displaygroup IN (" . $db->escape_string($assigngroups) . ") OR {$assign_concat_sql}");
		while($assigned = $db->fetch_array($query))
		{
			$assign_users[$assigned['uid']] = $assigned['username'];
		}
	}
	return $assign_users;
}

/**
 * Build an array of available categories (thread prefixes). Used to build the dropdown menus, and also check a valid category has been chosen.
 *
 * @param array Info on the forum.
 * @return array Array of available categories.
**/
function mysupport_get_categories($forum)
{
	global $mybb, $db;
	
	$forums_concat_sql = $groups_concat_sql = "";
	
	$parent_list = explode(",", $forum['parentlist']);
	foreach($parent_list as $parent)
	{
		if(!empty($forums_concat_sql))
		{
			$forums_concat_sql .= " OR ";
		}
		$forums_concat_sql .= "CONCAT(',',forums,',') LIKE '%," . intval($parent) . ",%'";
	}
	$forums_concat_sql = "(" . $forums_concat_sql . " OR forums = '-1')";
	
	$usergroup_list = $mybb->user['usergroup'];
	if(!empty($mybb->user['additionalgroups']))
	{
		$usergroup_list .= "," . $mybb->user['additionalgroups'];
	}
	$usergroup_list = explode(",", $usergroup_list);
	foreach($usergroup_list as $usergroup)
	{
		if(!empty($groups_concat_sql))
		{
			$groups_concat_sql .= " OR ";
		}
		$groups_concat_sql .= "CONCAT(',',groups,',') LIKE '%," . intval($usergroup) . ",%'";
	}
	$groups_concat_sql = "(" . $groups_concat_sql . " OR groups = '-1')";
	
	$query = $db->simple_select("threadprefixes", "pid, prefix", "{$forums_concat_sql} AND {$groups_concat_sql}");
	$categories = array();
	while($category = $db->fetch_array($query))
	{
		$categories[$category['pid']] = $category['prefix'];
	}
	return $categories;
}

/**
 * Show the status of a thread.
 *
 * @param int The status of the thread.
 * @param int The time the thread was solved.
 * @param int The TID of the thread.
**/
function mysupport_get_display_status($status = 0, $statustime, $thread_author)
{
	global $mybb, $lang, $templates, $theme, $mysupport_status;
	
	$thread_author = intval($thread_author);
	
	// if this user is logged in, we want to override the global setting for display with their own setting
	if($mybb->user['uid'] != 0 && $mybb->settings['mysupportdisplaytypeuserchange'] == 1)
	{
		if($mybb->user['mysupportdisplayastext'] == 1)
		{
			$mybb->settings['mysupportdisplaytype'] = "text";
		}
		else
		{
			$mybb->settings['mysupportdisplaytype'] = "image";
		}
	}
	
	// big check to see if either the status is to be show to everybody, only to people who can mark as solved, or to people who can mark as solved or who authored the thread
	if($mybb->settings['mysupportdisplayto'] == "all" || ($mybb->settings['mysupportdisplayto'] == "canmas" && mysupport_usergroup("canmarksolved")) || ($mybb->settings['mysupportdisplayto'] == "canmasauthor" && (mysupport_usergroup("canmarksolved") || $mybb->user['uid'] == $thread_author)))
	{
		if($mybb->settings['mysupportdisplaytype'] == "text")
		{
			// if this user cannot mark a thread as technical and people who can't mark as technical can't see that a technical thread is technical, don't execute this
			// I used the word technical 4 times in that sentence didn't I?? sorry about that
			if($status == 2 && !($mybb->settings['mysupporthidetechnical'] == 1 && !mysupport_usergroup("canmarktechnical")))
			{
				$status_class = "technical";
				$status_text = $lang->technical;
				if($mybb->settings['mysupportrelativetime'] == 1)
				{
					$date_time_technical = mysupport_relative_time($statustime);
					$status_title = htmlspecialchars_uni($lang->sprintf($lang->technical_time, $date_time_technical));
				}
				else
				{
					$date_technical = my_date(intval($mybb->settings['dateformat']), intval($statustime));
					$time_technical = my_date(intval($mybb->settings['timeformat']), intval($statustime));
					$status_title = htmlspecialchars_uni($lang->sprintf($lang->technical_time, $date_technical . " " . $time_technical));
				}
			}
			elseif($status == 1)
			{
				$status_class = "solved";
				$status_text = $lang->solved;
				if($mybb->settings['mysupportrelativetime'] == 1)
				{
					$date_time_solved = mysupport_relative_time($statustime);
					$status_title = htmlspecialchars_uni($lang->sprintf($lang->solved_time, $date_time_solved));
				}
				else
				{
					$date_solved = my_date(intval($mybb->settings['dateformat']), intval($statustime));
					$time_solved = my_date(intval($mybb->settings['timeformat']), intval($statustime));
					$status_title = htmlspecialchars_uni($lang->sprintf($lang->solved_time, $date_solved . " " . $time_solved));
				}
			}
			else
			{
				$status_class = "notsolved";
				$status_text = $status_title = $lang->not_solved;
			}
			
			eval("\$mysupport_status = \"".$templates->get('mysupport_status_text')."\";");
		}
		else
		{
			// if this user cannot mark a thread as technical and people who can't mark as technical can't see that a technical thread is technical, don't execute this
			// I used the word technical 4 times in that sentence didn't I?? sorry about that
			if($status == 2 && !($mybb->settings['mysupporthidetechnical'] == 1 && !mysupport_usergroup("canmarktechnical")))
			{
				$status_img = "technical";
				if($mybb->settings['mysupportrelativetime'] == 1)
				{
					$date_time_technical = mysupport_relative_time($statustime);
					$status_text = htmlspecialchars_uni($lang->sprintf($lang->technical_time, $date_time_technical));
				}
				else
				{
					$date_technical = my_date(intval($mybb->settings['dateformat']), intval($statustime));
					$time_technical = my_date(intval($mybb->settings['timeformat']), intval($statustime));
					$status_text = htmlspecialchars_uni($lang->sprintf($lang->technical_time, $date_technical . " " . $time_technical));
				}
			}
			elseif($status == 1)
			{
				$status_img = "solved";
				if($mybb->settings['mysupportrelativetime'] == 1)
				{
					$date_time_solved = mysupport_relative_time($statustime);
					$status_text = htmlspecialchars_uni($lang->sprintf($lang->solved_time, $date_time_solved));
				}
				else
				{
					$date_solved = my_date(intval($mybb->settings['dateformat']), intval($statustime));
					$time_solved = my_date(intval($mybb->settings['timeformat']), intval($statustime));
					$status_text = htmlspecialchars_uni($lang->sprintf($lang->solved_time, $date_solved . " " . $time_solved));
				}
			}
			else
			{
				$status_img = "notsolved";
				$status_text = $lang->not_solved;
			}
			
			eval("\$mysupport_status = \"".$templates->get('mysupport_status_image')."\";");
		}
		
		return $mysupport_status;
	}
}

/**
 * Get the text version of the status of a thread.
 *
 * @param int The status of the thread.
 * @param string The text version of the status of the thread.
**/
function mysupport_get_friendly_status($status = 0)
{
	global $lang;
	
	$lang->load("mysupport");
	
	$status = intval($status);
	switch($status)
	{
		// has it been marked as not techincal??
		case 4:
			$friendlystatus = $lang->not_technical;
			break;
		// is it a technical thread??
		case 2:
			$friendlystatus = $lang->technical;
			break;
		// no, is it a solved thread??
		case 3:
		case 1:
			$friendlystatus = $lang->solved;
			break;
		// must be not solved then
		default:
			$friendlystatus = $lang->not_solved;
	}
	
	return $friendlystatus;
}
?>