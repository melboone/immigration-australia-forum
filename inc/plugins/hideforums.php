<?php
/**
 * Hide Forums
 * Hide forums the user set to hide. The user can set at the User CP which forums he want to set as visible and which to set as hidden.
 * This is done by adding a little CSS code to each forum which should be hidden.
 * 
 * @access public
 * @author Jan Malte Gerth
 * @category MyBB Plugin
 * @copyright Copyright (c) 2010 Jan Malte Gerth (http://www.malte-gerth.de)
 * @license GPL3
 * @package Hide Forums
 * @since Version 0.0.1
 * @version 0.8.5
 */
 
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// add the plugin actions to the hooks
$plugins->add_hook("build_forumbits_forum", "hide_run");
$plugins->add_hook("global_start", "load_lang");
$plugins->add_hook("admin_config_settings_begin", "load_lang");

/**
 * information function
 */
function hideforums_info() {
	global $lang;
	$lang->load('hideforums',false,true);
	/**
	 * Array of information about the plugin.
	 * name: The name of the plugin
	 * description: Description of what the plugin does
	 * website: The website the plugin is maintained at (Optional)
	 * author: The name of the author of the plugin
	 * authorsite: The URL to the website of the author (Optional)
	 * version: The version number of the plugin
	 * guid: Unique ID issued by the MyBB Mods site for version checking
	 * compatibility: A CSV list of MyBB versions supported. Ex, "121,123", "12*". Wildcards supported.
	 */
	return array(
		"name"			=> $lang->hideforums_name,
		"description"	=> $lang->hideforums_description,
		"website"		=> "http://www.mybbcoder.de",
		"author"		=> "Jan Malte Gerth",
		"authorsite"	=> "http://www.malte-gerth.de/mybb.html",
		"version"		=> "0.8.5",
		"guid" 			=> "0b34ec0649208ee44eae07a3325c41e4",
		"compatibility" => "14*,16*"
	);
}

/** _install():
 *   Called whenever a plugin is installed by clicking the "Install" button in the plugin manager.
 *   If no install routine exists, the install button is not shown and it assumed any work will be
 *   performed in the _activate() routine.
 */
function hideforums_install() {
	global $db;
	
	// add new profile field
	$new_profile_field = array(
		"name" => $db->escape_string('Hidden forums'),
		"description" => $db->escape_string('A comma seperated list of forum id\'s you want to hide'),
		"disporder" => (int) 100,
		"type" => $db->escape_string('text'),
		"length" => (int) 100,
		"maxlength" => (int) 100,
		"required" => $db->escape_string(0),
		"editable" => $db->escape_string(1),
		"hidden" => $db->escape_string(1),
	);	
	$fid = $db->insert_query("profilefields", $new_profile_field);
	$db->write_query("ALTER TABLE ".TABLE_PREFIX."userfields ADD fid{$fid} TEXT");
	
	// Add Settings
	$query = $db->simple_select("settinggroups", "gid", "name='forumdisplay'");
	$gid = $db->fetch_field($query, "gid");
	$insertarray = array(
		'name' => 'hideforum_fid',
		'title' => 'Profil field hidden forums',
		'description' => $db->escape_string('Set to the profil field id which contains the forums we want to hide. This should be done automatically by install the plugin'),
		'optionscode' => 'text',
		'value' => $fid,
		'disporder' => 100,
		'gid' => $gid
	);
	$db->insert_query("settings", $insertarray);
	
	rebuild_settings();
}

/** _is_installed():
 *   Called on the plugin management page to establish if a plugin is already installed or not.
 *   This should return TRUE if the plugin is installed (by checking tables, fields etc) or FALSE
 *   if the plugin is not installed.
 */
function hideforums_is_installed() {
	global $mybb;
	
	if(empty($mybb->settings['hideforum_fid'])) {
		return false;
	}
	return true;
}
 
/** _uninstall():
 *    Called whenever a plugin is to be uninstalled. This should remove ALL traces of the plugin
 *    from the installation (tables etc). If it does not exist, uninstall button is not shown.
 */
function hideforums_uninstall() {
	global $db,$mybb;
	
	rebuild_settings();
	// delete the userfield for hidden forums
	$db->write_query("ALTER TABLE ".TABLE_PREFIX."userfields DROP fid{$mybb->settings['hideforum_fid']}");
	$db->write_query("DELETE ".TABLE_PREFIX."profilefields FROM ".TABLE_PREFIX."profilefields, ".TABLE_PREFIX."settings 
						WHERE ".TABLE_PREFIX."settings.value =  ".TABLE_PREFIX."profilefields.fid
						AND ".TABLE_PREFIX."settings.name ='hideforum_fid'");
	// delete the settings of the plugin
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN('hideforum_fid')");
	$db->delete_query("datacache", "title = 'hideforums_update_check'");
	rebuild_settings();
}

/** _activate():
 *    Called whenever a plugin is activated via the Admin CP. This should essentially make a plugin
 *    "visible" by adding templates/template changes, language changes etc.
 */
function hideforums_activate() {
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	// add the css placeholder for the hidden information
	find_replace_templatesets("forumbit_depth1_cat", '#<table#', "<table {\$forum['hidden']}");
	find_replace_templatesets("forumbit_depth1_cat", '#<br#', "<br {\$forum['hidden']}");
	find_replace_templatesets("forumbit_depth2_forum", '#<tr#', "<tr {\$forum['hidden']}");
	find_replace_templatesets("forumbit_depth3",'#'.preg_quote("{\$comma}").'#i',"<span {\$forum['hidden']}>{\$comma}");
	find_replace_templatesets("forumbit_depth3",'#'.preg_quote("</a>").'#i',"</a></span>");
	// add the link to the User CP
	find_replace_templatesets("usercp_nav_misc",'#'.preg_quote("</tbody>").'#i',"\n\t<tr><td class=\"trow1 smalltext\"><a href=\"hideforum.php\" class=\"usercp_nav_item usercp_nav_hideforum\">{\$lang->hideforums}</a></td></tr>\n</tbody>");
}

/** _deactivate():
 *    Called whenever a plugin is deactivated. This should essentially "hide" the plugin from view
 *    by removing templates/template changes etc. It should not, however, remove any information
 *    such as tables, fields etc - that should be handled by an _uninstall routine. When a plugin is
 *    uninstalled, this routine will also be called before _uninstall() if the plugin is active.
 */
function hideforums_deactivate() {
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	// remove the css placeholders
	find_replace_templatesets("forumbit_depth1_cat", "#( )?".preg_quote("{\$forum['hidden']}")."#i", '',0);
	find_replace_templatesets("forumbit_depth2_forum", "#( )?".preg_quote("{\$forum['hidden']}")."#i", '',0);
	find_replace_templatesets("forumbit_depth3","#".preg_quote("<span {\$forum['hidden']}>")."#i",'');
	find_replace_templatesets("forumbit_depth3",'#'.preg_quote("</span>").'#i','');
	// remove the link from the User CP
	find_replace_templatesets("usercp_nav_misc","#(\n)?(\t)?".preg_quote("<tr><td class=\"trow1 smalltext\"><a href=\"hideforum.php\" class=\"usercp_nav_item usercp_nav_hideforum\">{\$lang->hideforums}</a></td></tr>")."(\n)?#i","");
}

/**
 * run the hide action
 * set css display to none for the hidden forums
 * @param array $forum
 */
function hide_run($forum) {
	global $mybb;
	
	// if the forum is set as hidden use css to hide the forum
	if(in_array($forum['fid'], (array) explode(',', $mybb->user['fid'.$mybb->settings['hideforum_fid']]))) {
		$forum['hidden']=' style="display:none;" ';
	}
	return $forum;
}

/**
 * load the language strings in global context
 * necessary to get an localized link at the User CP, because the User CP is build
 * before any User CP related hook is called
 */
function load_lang() {
	global $mybb, $lang;
	
	// prevent non existing language file
	$lang->hideforums = 'Hide Forums';
	$lang->load('hideforums',false,true);
}

?>