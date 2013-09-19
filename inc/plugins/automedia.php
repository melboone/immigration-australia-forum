<?php
/**
 * Plugin Name: AutoMedia 2.1 for MyBB 1.6.*
 * Copyright © 2009-2012 doylecc
 * http://mybbplugins.de.vu
 *
 *This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>
 */
 
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
} 
 
//Caching template 
if(my_strpos($_SERVER['PHP_SELF'], 'usercp.php'))
{
	global $templatelist;
	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	$templatelist .= 'usercp_automedia';
} 
 
//Add Hooks 
$plugins->add_hook("parse_message", "automedia_run");
$plugins->add_hook("parse_message", "automedia_adult_run");
$plugins->add_hook("parse_message", "automedia_oldyt_run");
$plugins->add_hook("usercp_start", "automedia_ucp_status");
$plugins->add_hook("usercp_start", "automedia_usercp");
$plugins->add_hook("postbit", "automedia_hide");
$plugins->add_hook("postbit_prev", "automedia_hide");
$plugins->add_hook("postbit_pm", "automedia_hide");
$plugins->add_hook("postbit", "automedia_count");
$plugins->add_hook("postbit_prev", "automedia_count");
$plugins->add_hook("postbit_pm", "automedia_count");
$plugins->add_hook("pre_output_page", "automedia_sigpreview");
$plugins->add_hook("pre_output_page", "automedia_codebutton");
$plugins->add_hook("newreply_end", "automedia_quote");
$plugins->add_hook("xmlhttp", "automedia_multiquote");

$plugins->add_hook("admin_load", "automedia_admin");
$plugins->add_hook("admin_tools_menu", "automedia_admin_tools_menu");
$plugins->add_hook("admin_tools_action_handler", "automedia_admin_tools_action_handler");
$plugins->add_hook("admin_tools_permissions", "automedia_admin_tools_permissions");
$plugins->add_hook("admin_style_themes_add_commit", "automedia_reapply_template_edits");
$plugins->add_hook("admin_style_themes_import_commit", "automedia_reapply_template_edits");



//Plugin Info 
function automedia_info()
{
	global $lang, $plugins_cache;

	$lang->load("automedia");

	$am_info = array(
		"name"			=> "Auto Media",
		"description"	=> $lang->av_plugin_descr,
		"website"		=> "http://mods.mybb.com/",
		"author"		=> "doylecc",
		"authorsite"	=> "http://mybbplugins.de.vu",
		"version"		=> "2.1",
		"guid"			=> "ed9c97754efa977edba8a463ab98272a",
		"compatibility"	=> "16*"
		);

	//PHP 5.1 is required for the plugin
	if(version_compare(PHP_VERSION, '5.1.0', '<')) 
	{
		$am_info['description'] .= "  <ul><li style=\"list-style-image: url(styles/default/images/icons/error.gif)\">"
		.$lang->av_php_version
		."</li></ul>"; 
	}
	else
	{
		//Add cURL status to info
		if(automedia_is_installed() && is_array($plugins_cache) && is_array($plugins_cache['active']) && $plugins_cache['active']['automedia'])
		{
			$am_info['description'] .= @automedia_curl_status();
		}
	}
	return $am_info;
}

//Get cURL status 
function automedia_curl_status()
{
	global $lang, $db, $mybb;

	$result = $db->simple_select('settinggroups', 'gid', "name = 'AutoMedia Global'");
	$set = $db->fetch_array($result);
	$unsupported = $lang->av_unsupported;

	if(!function_exists('curl_init'))
	{
		$status .= "  <ul><li style=\"list-style-image: url(styles/default/images/icons/warning.gif)\">"
		.$unsupported
		."</li>
		<li style=\"list-style-image: url(styles/default/images/icons/default.gif)\"><a href=\"index.php?module=tools-automedia\">"
		.$lang->automedia_modules
		."</a></li>
		<li style=\"list-style-image: url(styles/default/images/icons/world.gif)\"><a href=\"index.php?module=config-settings&amp;action=change&amp;gid=".intval($set['gid'])."\">".$lang->automedia_settings."</a></li>
		<li style=\"list-style-image: url(styles/default/images/icons/custom.gif)\"><a href=\"index.php?module=tools-automedia&amp;action=templateedits&amp;my_post_key=".$mybb->post_code."\">".$lang->automedia_template_edits1."</a> ".$lang->automedia_template_edits2."</li>
		</ul>\n";
	}
	else
	{
		$status = "<ul></li>
		<li style=\"list-style-image: url(styles/default/images/icons/default.gif)\"><a href=\"index.php?module=tools-automedia\">"
		.$lang->automedia_modules
		."</a></li>
		<li style=\"list-style-image: url(styles/default/images/icons/world.gif)\"><a href=\"index.php?module=config-settings&amp;action=change&amp;gid=".intval($set['gid'])."\">".$lang->automedia_settings."</a></li>
		<li style=\"list-style-image: url(styles/default/images/icons/custom.gif)\"><a href=\"index.php?module=tools-automedia&amp;action=templateedits&amp;my_post_key=".$mybb->post_code."\">".$lang->automedia_template_edits1."</a> ".$lang->automedia_template_edits2."</li>
		</ul>\n";
	}
	return $status;
}


//Plugin installed? 
function automedia_is_installed()
{
	global $db;

	$query = $db->simple_select('settings','*','name="av_signature"');
	$installed = $db->fetch_array($query);

	if($installed)
	{
		return true;
	}
		return false;
}


//Install the Plugin 
function automedia_install()
{
	global $db, $mybb, $lang, $cache;

	if($db->field_exists('automedia_use', 'users'))
	{
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."users DROP COLUMN automedia_use");
	}

  $db->drop_table('automedia');

  //Create sites table
	$db->write_query("
		CREATE TABLE ".TABLE_PREFIX."automedia (
			`amid` int(10) unsigned NOT NULL auto_increment,
			`name` varchar(255) NOT NULL,
			`class` varchar(255) NOT NULL,
			PRIMARY KEY (amid)
		) ENGINE=MyISAM;
	");


	// DELETE ALL SETTINGS TO AVOID DUPLICATES 
	$query = $db->simple_select('settinggroups','gid','name="AutoMedia Sites"');
	$ams = $db->fetch_array($query);
	$db->delete_query('settinggroups',"gid='".$ams['gid']."'");
	$query2 = $db->simple_select('settinggroups','gid','name="AutoMedia Global"');
	$amg = $db->fetch_array($query2);
	$db->delete_query('settinggroups',"gid='".$amg['gid']."'");
	$query3 = $db->simple_select('settinggroups','gid','name="AutoMedia"');
	$am = $db->fetch_array($query3);
	$db->delete_query('settinggroups',"gid='".$am['gid']."'");
	$db->delete_query('settings',"gid='".$ams['gid']."'");
	$db->delete_query('settings',"gid='".$amg['gid']."'");
	$db->delete_query('settings',"gid='".$am['gid']."'");

/**
 *
 * Add Settings  
 *
 **/
	$query = $db->simple_select("settinggroups", "COUNT(*) as rows");
	$rows = $db->fetch_field($query, "rows");

	//Add Settinggroup for Global Settings
	$automedia_group = array(
		"name" => "AutoMedia Global",
		"title" => "AutoMedia Global",
		"description" => $lang->av_group_global_descr,
		"disporder" => $rows+1,
		"isdefault" => 0
	);
	$db->insert_query("settinggroups", $automedia_group);
	$gid2 = $db->insert_id();

	// Add Settings for Global Settinggroup
	$automedia_1 = array(
		"name" => "av_enable",
		"title" => $lang->av_enable_title,
		"description" => $lang->av_enable_descr,
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 1,
		"gid" => intval($gid2)
		);
	$db->insert_query("settings", $automedia_1);

	$automedia_2 = array(
		"name" => "av_guest",
		"title" => $lang->av_guest_title,
		"description" => $lang->av_guest_descr,
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 2,
		"gid" => intval($gid2)
		);
	$db->insert_query("settings", $automedia_2);

	$automedia_3 = array(
		"name" => "av_groups",
		"title" => $lang->av_groups_title,
		"description" => $lang->av_groups_descr,
		"optionscode" => "text",
		"value" => 0,
		"disporder" => 3,
		"gid" => intval($gid2)
		);
	$db->insert_query("settings", $automedia_3);

	$automedia_4 = array(
		"name" => "av_forums",
		"title" => $lang->av_forums_title,
		"description" => $lang->av_forums_descr,
		"optionscode" => "text",
		"value" => 0,
		"disporder" => 4,
		"gid" => intval($gid2)
		);
	$db->insert_query("settings", $automedia_4);

	$automedia_5= array(
		"name" => "av_adultsites",
		"title" => $lang->av_adultsites_title,
		"description" => $lang->av_adultsites_descr,
		"optionscode" => "yesno",
		"value" => 0,
		"disporder" => 5,
		"gid" => intval($gid2)
		);
	$db->insert_query("settings", $automedia_5);

	$automedia_6 = array(
		"name" => "av_adultguest",
		"title" => $lang->av_adultguest_title,
		"description" => $lang->av_adultguest_descr,
		"optionscode" => "yesno",
		"value" => 0,
		"disporder" => 6,
		"gid" => intval($gid2)
		);
	$db->insert_query("settings", $automedia_6);

	$automedia_7 = array(
		"name" => "av_adultgroups",
		"title" => $lang->av_adultgroups_title,
		"description" => $lang->av_adultgroups_descr,
		"optionscode" => "text",
		"value" => 0,
		"disporder" => 7,
		"gid" => intval($gid2)
		);
	$db->insert_query("settings", $automedia_7);

	$automedia_8 = array(
		"name" => "av_adultforums",
		"title" => $lang->av_adultforums_title,
		"description" => $lang->av_adultforums_descr,
		"optionscode" => "text",
		"value" => 0,
		"disporder" => 8,
		"gid" => intval($gid2)
		);
	$db->insert_query("settings", $automedia_8);

	$automedia_9 = array(
		"name" => "av_signature",
		"title" => $lang->av_signature_title,
		"description" => $lang->av_signature_descr,
		"optionscode" => "yesno",
		"value" => 0,
		"disporder" => 9,
		"gid" => intval($gid2)
		);
	$db->insert_query("settings", $automedia_9);

// setting if admins only, admins and mods or all users can embed flash files
	$automedia_10 = array(
		"name" => "av_flashadmin",
		"title" => $lang->av_flashadmin_title,
		"description" => $lang->av_flashadmin_descr,
		"optionscode" => "radio
admin=Admins
mods=Admins, Supermods, Mods
all=All Users",
		"value" => "admin",
		"disporder" => 10,
		"gid" => intval($gid2)
		);
	$db->insert_query("settings", $automedia_10);

// add setting for width of flash files
	$automedia_11 = array(
		"name" => "av_flashwidth",
		"title" => $lang->av_flashwidth_title,
		"description" => $lang->av_flashwidth_descr,
		"optionscode" => "text",
		"value" => "480",
		"disporder" => 11,
		"gid" => intval($gid2)
		);
	$db->insert_query("settings", $automedia_11);

// add setting for height of flash files
	$automedia_12 = array(
		"name" => "av_flashheight",
		"title" => $lang->av_flashheight_title,
		"description" => $lang->av_flashheight_descr,
		"optionscode" => "text",
		"value" => "360",
		"disporder" => 12,
		"gid" => intval($gid2)
		);
	$db->insert_query("settings", $automedia_12);

	$automedia_13 = array(
		"name" => "av_sizeall",
		"title" => $lang->av_sizeall_title,
		"description" => $lang->av_sizeall_descr,
		"optionscode" => "yesno",
		"value" => 0,
		"disporder" => 13,
		"gid" => intval($gid2)
		);
	$db->insert_query("settings", $automedia_13);
		
	$automedia_14 = array(
		"name" => "av_codebuttons",
		"title" => $lang->av_codebuttons_title,
		"description" => $lang->av_codebuttons_descr,
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 14,
		"gid" => intval($gid2)
		);
	$db->insert_query("settings", $automedia_14);
	
	$automedia_15 = array(
		"name" => "av_quote",
		"title" => $lang->av_quote_title,
		"description" => $lang->av_quote_descr,
		"optionscode" => "yesno",
		"value" => 1,
		"disporder" => 15,
		"gid" => intval($gid2)
		);
	$db->insert_query("settings", $automedia_15);

	//Add users setting
	$db->write_query("ALTER TABLE ".TABLE_PREFIX."users ADD COLUMN automedia_use VARCHAR(1) NOT NULL DEFAULT'Y'");

	// Refresh settings.php
	rebuild_settings();

/**
 * Add template
 *
 **/	
	$template = array(
		"title"		=> "usercp_automedia",
		"template"	=> $db->escape_string('<html>
			<head>
				<title>{$mybb->settings[bbname]} - Automedia in Posts</title>
				{$headerinclude}
			</head>
			<body>
				{$header}
				<form action="usercp.php" method="post">
				<table width="100%" border="0" align="center">
					<tr>
						{$usercpnav}
						<td valign="top">
							<table border="0" cellspacing="{$theme[borderwidth]}" cellpadding="{$theme[tablespace]}" class="tborder">
								<tr>
									<td class="thead" colspan="2"><strong>Automedia in Posts</strong></td>
								</tr>
								<tr>
									<td align="center" class="trow1" width="95%">
									<select name="automedia">
									<option>Yes</option>
									<option>No</option>
									</select></td>
									<td align="center" class="trow1" width="5%">
									<div style="float:left">{$ucpset}</div>
									</td>
								</tr>
							</table>
							<br />
							<div align="center">
							<input type="hidden" name="action" value="do_automedia" />
							<input type="submit" class="button" name="submit" value="OK" />
							</div>
						</td>
					</tr>
				</table>
				</form>
				{$footer}
			</body>
		</html>
		'),
		"sid"		=> -1
	);

	$db->insert_query("templates", $template);
	
	// Add MyCode to parse [amquote] tags if plugin is deactivated or uninstalled (inactive)
	$amquoteresult = $db->simple_select('mycode', 'cid', "title = 'AutoMedia Quotes (AutoMedia Plugin)'", array('limit' => 1));
	$amquotegroup = $db->fetch_array($amquoteresult);
	
	if(empty($amquotegroup['cid']))
	{
		$amquote_mycode = array(
				'title'	=> $db->escape_string('AutoMedia Quotes (AutoMedia Plugin)'),
				'description' => $db->escape_string('Parse AutoMedia quote tags'),
				'regex' => $db->escape_string('\[amquote\](.*?)\[/amquote\]'),
				'replacement' => $db->escape_string('$1'),
				'active' => 0,
				'parseorder' => 0
			);
		$cid1 = $db->insert_query("mycode", $amquote_mycode);
		$cache->update_mycode();
	}
	
	// Add MyCode to parse [amoff] tags if plugin is deactivated or uninstalled (inactive)
	$amoffresult = $db->simple_select('mycode', 'cid', "title = 'AutoMedia Links (AutoMedia Plugin)'", array('limit' => 1));
	$amoffgroup = $db->fetch_array($amoffresult);
	
	if(empty($amoffgroup['cid']))
	{
		$amoff_mycode = array(
				'title'	=> $db->escape_string('AutoMedia Links (AutoMedia Plugin)'),
				'description' => $db->escape_string('Parse AutoMedia link tags'),
				'regex' => $db->escape_string('\[amoff\](http://)(.*?)\[/amoff\]'),
				'replacement' => $db->escape_string('<a href="http://$2" target="_blank">http://$2</a>'),
				'active' => 0,
				'parseorder' => 0
			);
		$cid2 = $db->insert_query("mycode", $amoff_mycode);
		$cache->update_mycode();
	}

}


//Uninstall the Plugin 
function automedia_uninstall()
{
	global $db, $cache;
	
	//Remove the extra column
	if($db->field_exists('automedia_use', 'users'))
	{
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."users DROP COLUMN automedia_use");
	}

	$db->drop_table('automedia');

	// DELETE ALL SETTINGS
	$query = $db->simple_select('settinggroups','gid','name="AutoMedia Sites"');
	$ams = $db->fetch_array($query);
	$db->delete_query('settinggroups',"gid='".$ams['gid']."'");
	$query2 = $db->simple_select('settinggroups','gid','name="AutoMedia Global"');
	$amg = $db->fetch_array($query2);
	$db->delete_query('settinggroups',"gid='".$amg['gid']."'");
	$query3 = $db->simple_select('settinggroups','gid','name="AutoMedia"');
	$am = $db->fetch_array($query3);
	$db->delete_query('settinggroups',"gid='".$am['gid']."'");
	$db->delete_query('settings',"gid='".$ams['gid']."'");
	$db->delete_query('settings',"gid='".$amg['gid']."'");
	$db->delete_query('settings',"gid='".$am['gid']."'");

	// Refresh settings.php
	rebuild_settings();

  /**
   *
   * Delete template
   *
   **/
	$db->delete_query("templates", "title = 'usercp_automedia'");

  /**
   *
   * Delete cache
   *
   **/	
	if(is_object($cache->handler))
	{
		$cache->handler->delete('automedia');
	}
	// Delete database cache
	$db->delete_query("datacache", "title='automedia'"); 
}


//Activate the Plugin 
function automedia_activate()
{
	global $db, $mybb, $lang, $cache;

	change_admin_permission('tools','automedia');

	// DELETE OBSOLETE SETTINGS FROM OLDER VERSIONS 
	$query_sites = $db->simple_select('settinggroups','gid','name="AutoMedia Sites"');
	$ams = $db->fetch_array($query_sites);
	$db->delete_query('settinggroups',"gid='".$ams['gid']."'");
	$query_amdel = $db->simple_select('settinggroups','gid','name="AutoMedia"');
	$am = $db->fetch_array($query_amdel);
	$db->delete_query('settinggroups',"gid='".$am['gid']."'");

	$db->delete_query('settings',"gid='".$ams['gid']."'");
	$db->delete_query('settings',"gid='".$am['gid']."'");

	if(!$db->table_exists('automedia'))
	{
	//Create sites table if upgrading from version 1.1.10
	$db->write_query("
		CREATE TABLE ".TABLE_PREFIX."automedia (
			`amid` int(10) unsigned NOT NULL auto_increment,
			`name` varchar(255) NOT NULL,
			`class` varchar(255) NOT NULL,
			PRIMARY KEY (amid)
		) ENGINE=MyISAM;
	");
	}

	$folder1 = MYBB_ROOT."inc/plugins/automedia/sites/";
	$folder2 = MYBB_ROOT."inc/plugins/automedia/special/";
	if(is_dir($folder1))
	{
		$mediafiles1 = scandir($folder1);

		foreach ($mediafiles1 as $sites1) 
		{ // Fetch all files in the folder
			$siteinfo1 = pathinfo($folder1."/".$sites1);
			if($sites1 != "." && $sites1 != "..")
			{
				$filetype1 = "php";
				//We need only php files
				if($siteinfo1['extension'] == $filetype1)
				{
					$media1 = str_replace(".php", "", $sites1);
					$check1 = file_get_contents($folder1.$siteinfo1['basename']);
					if(preg_match('"function automedia_"isU', $check1))
					{
						$query_ex = $db->simple_select('automedia', 'name', "name='".htmlspecialchars_uni($media1)."'");
						$modactive = $db->fetch_array($query_ex);
						if(!$modactive)
						{
							// add site
							$automedia_site1 = array(
								"name" => htmlspecialchars_uni($media1),
								"class" => "site",
							);
							$db->insert_query("automedia", $automedia_site1);
						}
					}
				}
			}
		}
	}
	if(is_dir($folder2))
	{
		$mediafiles2 = scandir($folder2);

		foreach ($mediafiles2 as $sites2) 
		{ // Fetch all files in the folder
			$siteinfo2 = pathinfo($folder2."/".$sites2);
			if($sites2 != "." && $sites2 != "..")
			{
				$filetype2 = "php";
				//We need only php files
				if($siteinfo2['extension'] == $filetype2)
				{
					$media2 = str_replace(".php", "", $sites2);
					$check2 = file_get_contents($folder2.$siteinfo2['basename']);
					if(preg_match('"function automedia_"isU', $check2))
					{
						$query_ex2 = $db->simple_select('automedia', 'name', "name='".htmlspecialchars_uni($media2)."'");
						$modactive2 = $db->fetch_array($query_ex2);
						if(!$modactive2)
						{
							// add site
							$automedia_site2 = array(
								"name" => htmlspecialchars_uni($media2),
								"class" => "special",
							);
							$db->insert_query("automedia", $automedia_site2);
						}
					}
				}
			}
		}
	}


	automedia_cache();


/**
 * Edit templates
 *
 **/
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

	find_replace_templatesets("usercp_nav_misc", "#".preg_quote('<tr><td class="trow1 smalltext"><a href="usercp.php?action=userautomedia" class="usercp_nav_item usercp_nav_options">AutoMedia</a></td></tr>')."#s",'', '',0);
	//delete template editings by a former beta version
	find_replace_templatesets('usercp_editsig', '#\n{\$amsigpreview}<br /><br />#', '', 0);

	$db->delete_query("templates", "title = 'usercp_automedia'");

	find_replace_templatesets("usercp_nav_misc", '#</tbody>#', '<tr><td class="trow1 smalltext"><a href="usercp.php?action=userautomedia" class="usercp_nav_item usercp_nav_options">AutoMedia</a></td></tr></tbody>');


/**
 * Add template if upgrading from version 1.1.10
 *
 **/
	$query_tpl = $db->simple_select("templates", "*", "title = 'usercp_automedia'");
	$result_tpl = $db->num_rows($query_tpl); 
	if(!$result_tpl)
	{
		$template = array(
			"title"		=> "usercp_automedia",
			"template"	=> $db->escape_string('<html>
				<head>
					<title>{$mybb->settings[bbname]} - Automedia in Posts</title>
					{$headerinclude}
				</head>
				<body>
				{$header}
				<form action="usercp.php" method="post">
				<table width="100%" border="0" align="center">
					<tr>
						{$usercpnav}
						<td valign="top">
							<table border="0" cellspacing="{$theme[borderwidth]}" cellpadding="{$theme[tablespace]}" class="tborder">
								<tr>
									<td class="thead" colspan="2"><strong>Automedia in Posts</strong></td>
								</tr>
								<tr>
									<td align="center" class="trow1" width="95%">
									<select name="automedia">
									<option>Yes</option>
									<option>No</option>
									</select></td>
									<td align="center" class="trow1" width="5%">
									<div style="float:left">{$ucpset}</div>
									</td>
								</tr>
							</table>
							<br />
							<div align="center">
							<input type="hidden" name="action" value="do_automedia" />
							<input type="submit" class="button" name="submit" value="OK" />
							</div>
						</td>
					</tr>
				</table>
				</form>
				{$footer}
			</body>
		</html>
		'),
		"sid"		=> -1
	);

	$db->insert_query("templates", $template);
	}


	//Add 2 new settings if upgrading from version 1.1.10
	$query3 = $db->simple_select("settings", "gid", "name='av_enable'");
	$asgid = $db->fetch_array($query3);
	$gid4 = $asgid['gid'];
	
	$query = $db->simple_select("settings", "*", "name='av_sizeall'");
	$result = $db->num_rows($query);

	if(!$result)
	{
	 $automedia_13 = array(
			"name" => "av_sizeall",
			"title" => $lang->av_sizeall_title,
			"description" => $lang->av_sizeall_descr,
			"optionscode" => "yesno",
			"value" => 0,
			"disporder" => 13,
			"gid" => intval($gid4)
			);
		$db->insert_query("settings", $automedia_13);
	}
	
	$query2 = $db->simple_select("settings", "*", "name='av_codebuttons'");
	$result2 = $db->num_rows($query2);

	if(!$result2)
	{
		$automedia_14 = array(
			"name" => "av_codebuttons",
			"title" => $lang->av_codebuttons_title,
			"description" => $lang->av_codebuttons_descr,
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 14,
			"gid" => intval($gid4)
			);
		$db->insert_query("settings", $automedia_14);
	}
	//Update to v2.1
	$query3 = $db->simple_select("settings", "*", "name='av_quote'");
	$result3 = $db->num_rows($query3);

	if(!$result3)
	{
		$automedia_15 = array(
			"name" => "av_quote",
			"title" => $lang->av_quote_title,
			"description" => $lang->av_quote_descr,
			"optionscode" => "yesno",
			"value" => 1,
			"disporder" => 15,
			"gid" => intval($gid4)
			);
		$db->insert_query("settings", $automedia_15);
	}

	rebuild_settings();
	
	// Add / deactivate MyCode to parse [amquote] tags if plugin is deactivated or uninstalled (inactive)
	$amquoteresult = $db->simple_select('mycode', 'cid', "title = 'AutoMedia Quotes (AutoMedia Plugin)'", array('limit' => 1));
	$amquotegroup = $db->fetch_array($amquoteresult);
	
	if(empty($amquotegroup['cid']))
	{
		$amquote_mycode = array(
				'title'	=> $db->escape_string('AutoMedia Quotes (AutoMedia Plugin)'),
				'description' => $db->escape_string('Parse AutoMedia quote tags'),
				'regex' => $db->escape_string('\[amquote\](.*?)\[/amquote\]'),
				'replacement' => $db->escape_string('$1'),
				'active' => 0,
				'parseorder' => 0
			);
		$cid1 = $db->insert_query('mycode', $amquote_mycode);
		$cache->update_mycode();
	}
	else if($amquotegroup['cid'])
	{
		$updated_record = array(
		'active' => '0'
		);
		$db->update_query('mycode', $updated_record, "cid='".$amquotegroup['cid']."'");
		$cache->update_mycode();		
	}	
	
	// Add / deactivate MyCode to parse [amoff] tags if plugin is deactivated or uninstalled (inactive)
	$amoffresult = $db->simple_select('mycode', 'cid', "title = 'AutoMedia Links (AutoMedia Plugin)'", array('limit' => 1));
	$amoffgroup = $db->fetch_array($amoffresult);
	
	if(empty($amoffgroup['cid']))
	{
		$amoff_mycode = array(
				'title'	=> $db->escape_string('AutoMedia Links (AutoMedia Plugin)'),
				'description' => $db->escape_string('Parse AutoMedia link tags'),
				'regex' => $db->escape_string('\[amoff\](http://)(.*?)\[/amoff\]'),
				'replacement' => $db->escape_string('<a href="http://$2" target="_blank">http://$2</a>'),
				'active' => 0,
				'parseorder' => 0
			);
		$cid2= $db->insert_query('mycode', $amoff_mycode);
		$cache->update_mycode();
	}	
	else if($amoffgroup['cid'])
	{
		$updated_record = array(
		'active' => '0'
		);
		$db->update_query('mycode', $updated_record, "cid='".$amoffgroup['cid']."'");
		$cache->update_mycode();		
	}
}


//Deactivate the Plugin 
function automedia_deactivate()
{
	global $db, $mybb, $cache;

	change_admin_permission('tools','automedia',-1);
	automedia_cache(true);

	// DELETE OBSOLETE SETTINGS FROM OLDER VERSIONS 
	$query_sites = $db->simple_select('settinggroups','gid','name="AutoMedia Sites"');
	$ams = $db->fetch_array($query_sites);
	$db->delete_query('settinggroups',"gid='".$ams['gid']."'");
	$query_amdel = $db->simple_select('settinggroups','gid','name="AutoMedia"');
	$am = $db->fetch_array($query_amdel);
	$db->delete_query('settinggroups',"gid='".$am['gid']."'");

	$db->delete_query('settings',"gid='".$ams['gid']."'");
	$db->delete_query('settings',"gid='".$am['gid']."'");
	
	// Refresh settings.php
	rebuild_settings();

  /**
   *
   * Restore templates
   *
   **/
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

	find_replace_templatesets("usercp_nav_misc", "#".preg_quote('<tr><td class="trow1 smalltext"><a href="usercp.php?action=userautomedia" class="usercp_nav_item usercp_nav_options">AutoMedia</a></td></tr>')."#s",'', '',0);

	// Activate MyCode to parse [amquote] tags if plugin is deactivated or uninstalled
	$amquoteresult = $db->simple_select('mycode', 'cid', "title = 'AutoMedia Quotes (AutoMedia Plugin)'", array('limit' => 1));
	$amquotegroup = $db->fetch_array($amquoteresult);
	
	if($amquotegroup['cid'])
	{
		$updated_record = array(
		'active' => '1'
		);
		$db->update_query('mycode', $updated_record, "cid='".$amquotegroup['cid']."'");
		$cache->update_mycode();		
	}	
	
	// Activate MyCode to parse [amoff] tags if plugin is deactivated or uninstalled
	$amoffresult = $db->simple_select('mycode', 'cid', "title = 'AutoMedia Links (AutoMedia Plugin)'", array('limit' => 1));
	$amoffgroup = $db->fetch_array($amoffresult);
	
	if($amoffgroup['cid'])
	{
		$updated_record = array(
		'active' => '1'
		);
		$db->update_query('mycode', $updated_record, "cid='".$amoffgroup['cid']."'");
		$cache->update_mycode();		
	}

}

//Reapply template edits
function automedia_reapply_template_edits()
{
/**
 * Edit templates
 *
 **/
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";

	find_replace_templatesets("usercp_nav_misc", "#".preg_quote('<tr><td class="trow1 smalltext"><a href="usercp.php?action=userautomedia" class="usercp_nav_item usercp_nav_options">AutoMedia</a></td></tr>')."#s",'', '',0);
	find_replace_templatesets("usercp_nav_misc", '#</tbody>#', '<tr><td class="trow1 smalltext"><a href="usercp.php?action=userautomedia" class="usercp_nav_item usercp_nav_options">AutoMedia</a></td></tr></tbody>');
} 


// User CP 

/**
  *
  * UserCP Enabled Status
  *
  */
function automedia_ucp_status()
{
	global $mybb, $db;

	$auset = $db->escape_string($mybb->user['automedia_use']);

	if ($auset == "Y")
	{
		$ucpstatus = " <b>Status:</b><br /> <img src=\"images/icons/thumbsup.gif\" />";
	}
	else
	{
		$ucpstatus = " <b>Status:</b><br /> <img src=\"images/icons/thumbsdown.gif\" />";
	}
	return $ucpstatus;
}

/**
  *
  * UserCP Settings
  *
  */
function automedia_usercp()
{

	global $header, $headerinclude, $usercpnav, $footer, $mybb, $theme, $db, $lang, $templates;

	if ($mybb->input['action'] == "userautomedia")
	{
		add_breadcrumb($lang->nav_usercp, "usercp.php");
		add_breadcrumb("AutoMedia");
		$ucpset = @automedia_ucp_status();
		eval("\$editprofile = \"".$templates->get("usercp_automedia")."\";");
		output_page($editprofile);
	}
	elseif ($mybb->input['action'] == "do_automedia" && $mybb->request_method == "post")
	{
		$uid = intval($mybb->user['uid']);
		$updated_record = array(
		"automedia_use" => $db->escape_string($mybb->input['automedia'])
		);
		if($db->update_query('users', $updated_record, "uid='".$uid."'"))
		{
			redirect("usercp.php?action=userautomedia","AutoMedia Settings updated!");
		}
	}
	else {
		return;
	}
}


//ACP menu entry
function automedia_admin_tools_menu(&$sub_menu)
{
	global $lang;
	$lang->load('automedia');

	$sub_menu[] = array('id' => 'automedia', 'title' => $lang->automedia, 'link' => 'index.php?module=tools-automedia');
}

function automedia_admin_tools_action_handler(&$actions)
{
	$actions['automedia'] = array('active' => 'automedia', 'file' => 'automedia');
}


//Admin permissions
function automedia_admin_tools_permissions(&$admin_permissions)
{
	global $lang;
	$lang->load('automedia');
	$admin_permissions['automedia'] = $lang->can_view_automedia;
}


//Show installed modules in ACP
function automedia_admin()
{
	global $db, $lang, $mybb, $page, $cache, $run_module, $action_file;
	$lang->load('automedia');

	if($page->active_action!='automedia')
	{
		return false;
	}

	if($run_module == 'tools' && $action_file == 'automedia')
	{
		$page->add_breadcrumb_item($lang->automedia, 'index.php?module=tools-automedia');
		
		//Show site modules
		if($mybb->input['action'] == "" or !$mybb->input['action'])
		{
			$page->add_breadcrumb_item($lang->automedia_modules);
			$page->output_header($lang->automedia_modules.' - '.$lang->automedia_modules);
			
				$sub_tabs['automedia'] = array(
				'title'			=> $lang->automedia_modules,
				'link'			=> 'index.php?module=tools-automedia',
				'description'	=> $lang->automedia_modules_description1
			);
			if($mybb->settings['av_adultsites'] == 1)
			{
				$sub_tabs['special'] = array(
					'title'=>$lang->automedia_adult,
					'link'=>'index.php?module=tools-automedia&amp;action=adult',
					'description'=>$lang->automedia_adult_description1
				);
			}
			$page->output_nav_tabs($sub_tabs, 'automedia');
			
			$amtable=new Table;
			$amtable->construct_header('#');
			$amtable->construct_header($lang->automedia_modules_description2);
			$amtable->construct_header('<div style="text-align: center;">Status:</div>');
			$amtable->construct_header('<div style="text-align: center;">'.$lang->automedia_modules_options.':</div>');

			$folder = MYBB_ROOT."inc/plugins/automedia/sites/";
			if(is_dir($folder))
			{
				$mediafiles = scandir($folder);
				$mediatitles = str_replace(".php", "", $mediafiles);
				$query = $db->simple_select('automedia', 'name', "class='site'");
				//Find missing files for active modules 
				while($missing = $db->fetch_array($query))
				{
					if(!in_array($missing['name'], $mediatitles))
					{
						$missingfile = ucfirst(htmlspecialchars_uni($missing['name']));
						$amtable->construct_cell('<strong>!</strong>');
						$amtable->construct_cell('<strong>'.$missingfile.'</strong> (<a href="'.$sub_tabs['automedia']['link'].'&amp;action=deactivate&amp;site='.urlencode($missing['name']).'&amp;my_post_key='.$mybb->post_code.'"><strong>'.$lang->automedia_modules_deactivate.'</strong></a>)');
						$amtable->construct_cell($lang->automedia_modules_notfound.' '.$folder.''.htmlspecialchars_uni($missing['name']).'.php', array('colspan' => '2'));
						$amtable->construct_row();
					}
				}

				$i = 1;
				foreach ($mediafiles as $sites) 
				{ // Fetch all files in the folder
					$siteinfo = pathinfo($folder."/".$sites);
					if($sites != "." && $sites != "..")
					{
						$filetype = "php";
						//We need only php files
						if($siteinfo['extension'] == $filetype)
						{
							$site = str_replace(".php", "", $sites);
							$media = ucfirst(htmlspecialchars_uni($site));
							$check = file_get_contents($folder.$siteinfo['basename']);
							if(preg_match('"function automedia_"isU', $check))
							{
								$amtable->construct_cell($i);
								$amtable->construct_cell('<a href="'.$sub_tabs['automedia']['link'].'&amp;action=showsite&amp;site='.urlencode($site).'&amp;my_post_key='.$mybb->post_code.'"><strong>'.$media.'</strong></a>');
								$query2 = $db->simple_select('automedia', '*', "name='".htmlspecialchars_uni($site)."'");
								$active = $db->fetch_array($query2);
								if($active && $active['class'] == "site")
								{
									$amtable->construct_cell('<div style="text-align: center;"><img src="'.$mybb->settings['bburl'].'/inc/plugins/automedia/mod-on.png" width="29" height="25" alt="Activated" />');
									$amtable->construct_cell('<div style="text-align: center;"><a href="'.$sub_tabs['automedia']['link'].'&amp;action=deactivate&amp;site='.urlencode($site).'&amp;my_post_key='.$mybb->post_code.'"><strong>'.$lang->automedia_modules_deactivate.'</strong></a></div>');
								} else {
									$amtable->construct_cell('<div style="text-align: center;"><img src="'.$mybb->settings['bburl'].'/inc/plugins/automedia/mod-off.png" width="29" height="25" alt="Deactivated" />');
									$amtable->construct_cell('<div style="text-align: center;"><a href="'.$sub_tabs['automedia']['link'].'&amp;action=activate&amp;site='.urlencode($site).'&amp;my_post_key='.$mybb->post_code.'"><strong>'.$lang->automedia_modules_activate.'</strong></a></div>');
								}
								$amtable->construct_row();
								$i++;
							}
						}
					}
				}  
				if($amtable->num_rows() == 0)
				{
					$amtable->construct_cell($lang->automedia_modules, array('colspan' => '4'));
					$amtable->construct_row();
				}
			}
			else
			{
				$amtable->construct_cell($lang->automedia_modules_missing_sitesfolder, array('colspan' => '4'));
				$amtable->construct_row();
			}

			$amtable->output($lang->automedia_modules);
			echo '<div style="text-align: center;">
			<a href="'.$sub_tabs['automedia']['link'].'&amp;action=activateallsites&amp;my_post_key='.$mybb->post_code.'"><span style="border: 3px double #0F5C8E;	padding: 3px;	background: #fff url(images/submit_bg.gif) repeat-x top;	color: #0F5C8E;	margin-right: 3px;">'.$lang->automedia_modules_activateall.'</span></a> 
			</div>';
			
			$page->output_footer();
		}

		//Show special modules
		if($mybb->input['action'] == "adult" && $mybb->settings['av_adultsites'] == 1)
		{
			$page->add_breadcrumb_item($lang->automedia_adult);
			$page->output_header($lang->automedia_modules.' - '.$lang->automedia_adult);

			$sub_tabs['automedia'] = array(
				'title'			=> $lang->automedia_modules,
				'link'			=> 'index.php?module=tools-automedia',
				'description'	=> $lang->automedia_modules
			);
			if($mybb->settings['av_adultsites'] == 1)
			{
				$sub_tabs['special']=array(
					'title'=>$lang->automedia_adult,
					'link'=>'index.php?module=tools-automedia&amp;action=adult',
					'description'=>$lang->automedia_adult_description1
				);
			}
			$page->output_nav_tabs($sub_tabs, 'special');
			$amtable=new Table;
			$amtable->construct_header('#');
			$amtable->construct_header($lang->automedia_modules_description2);
			$amtable->construct_header('<div style="text-align: center;">Status:</div>');
			$amtable->construct_header('<div style="text-align: center;">'.$lang->automedia_modules_options.':</div>');

			$folder = MYBB_ROOT."inc/plugins/automedia/special/";
			if(is_dir($folder))
			{
				$mediafiles = scandir($folder);
				$mediatitles = str_replace(".php", "", $mediafiles);
				$query = $db->simple_select('automedia', 'name', "class='special'");
				//Find missing files for active modules 
				while($missing = $db->fetch_array($query))
				{
					if(!in_array($missing['name'], $mediatitles))
					{
						$missingfile = ucfirst(htmlspecialchars_uni($missing['name']));
						$amtable->construct_cell('<strong>!</strong>');
						$amtable->construct_cell('<strong>'.$missingfile.'</strong> (<a href="'.$sub_tabs['automedia']['link'].'&amp;action=adultdeactivate&amp;site='.urlencode($missing['name']).'&amp;my_post_key='.$mybb->post_code.'"><strong>'.$lang->automedia_modules_deactivate.'</strong></a>)');
						$amtable->construct_cell($lang->automedia_modules_notfound.' '.$folder.''.htmlspecialchars_uni($missing['name']).'.php', array('colspan' => '2'));
						$amtable->construct_row();
					}
				}

				$i = 1;
				foreach ($mediafiles as $sites) 
				{ // Fetch all files in the folder
					$siteinfo = pathinfo($folder."/".$sites);
					if($sites != "." && $sites != "..") 
					{
						$filetype = "php";
						//We need only php files
						if($siteinfo['extension'] == $filetype)
						{
							$site = str_replace(".php", "", $sites);
							$media = ucfirst(htmlspecialchars_uni($site));
							$check = file_get_contents($folder.$siteinfo['basename']);
							if(preg_match('"function automedia_"isU', $check))
							{
								$amtable->construct_cell($i);
								$amtable->construct_cell('<a href="'.$sub_tabs['automedia']['link'].'&amp;action=showspecial&amp;site='.urlencode($site).'&amp;my_post_key='.$mybb->post_code.'"><strong>'.$media.'</strong></a>');
								$query = $db->simple_select('automedia', '*', "name='".htmlspecialchars_uni($site)."'");
								$active = $db->fetch_array($query);
								if($active && $active['class'] == "special")
								{
									$amtable->construct_cell('<div style="text-align: center;"><img src="'.$mybb->settings['bburl'].'/inc/plugins/automedia/mod-on.png" width="29" height="25" alt="Activated" />');
									$amtable->construct_cell('<div style="text-align: center;"><a href="'.$sub_tabs['automedia']['link'].'&amp;action=adultdeactivate&amp;site='.urlencode($site).'&amp;my_post_key='.$mybb->post_code.'"><strong>'.$lang->automedia_modules_deactivate.'</strong></a></div>');
								} else {
									$amtable->construct_cell('<div style="text-align: center;"><img src="'.$mybb->settings['bburl'].'/inc/plugins/automedia/mod-off.png" width="29" height="25" alt="Deactivated" />');
									$amtable->construct_cell('<div style="text-align: center;"><a href="'.$sub_tabs['automedia']['link'].'&amp;action=adultactivate&amp;site='.urlencode($site).'&amp;my_post_key='.$mybb->post_code.'"><strong>'.$lang->automedia_modules_activate.'</strong></a></div>');
								}
								$amtable->construct_row();
								$i++;
							}
						}
					}
				}
				if($amtable->num_rows() == 0)
				{
					$amtable->construct_cell($lang->automedia_adult, array('colspan' => '4'));
					$amtable->construct_row();
				}
			}
			else
			{
				$amtable->construct_cell($lang->automedia_modules_missing_specialfolder, array('colspan' => '4'));
				$amtable->construct_row();
			}

			$amtable->output($lang->automedia_modules);
			echo '<div style="text-align: center;">
			<a href="'.$sub_tabs['automedia']['link'].'&amp;action=activateallspecial&amp;my_post_key='.$mybb->post_code.'"><span style="border: 3px double #0F5C8E;	padding: 3px;	background: #fff url(images/submit_bg.gif) repeat-x top;	color: #0F5C8E;	margin-right: 3px;">'.$lang->automedia_modules_activateall.'</span></a> 
			</div>';
			$page->output_footer();
		}

		//Activate site module
		if($mybb->input['action'] == 'activate')
		{
			if(!verify_post_check($mybb->input['my_post_key']))
			{
				flash_message($lang->invalid_post_verify_key2, 'error');
				admin_redirect("index.php?module=tools-automedia");
			}
			else
			{
				$site = htmlspecialchars_uni($mybb->input['site']);
				$query_act1 = $db->simple_select('automedia', '*', "name='".$site."'");
				$active1 = $db->fetch_array($query_act1); 
				if(!$active1)
				{
					$automedia_site = array(
						"name" => $site,
						"class" => "site",
					);
					$db->insert_query("automedia", $automedia_site);
					automedia_cache();

					$mybb->input['module'] = "AutoMedia";
					$mybb->input['action'] = $lang->automedia_modules_active." ";
					log_admin_action(ucfirst($site));

					flash_message($lang->automedia_modules_active, 'success');
					admin_redirect("index.php?module=tools-automedia");
				}
				else
				{
					flash_message($lang->automedia_modules_notfound,'error');
				}
			}
			exit();
		}

		//Activate special module
		if($mybb->input['action'] == 'adultactivate')
		{
			if(!verify_post_check($mybb->input['my_post_key']))
			{
				flash_message($lang->invalid_post_verify_key2, 'error');
				admin_redirect("index.php?module=tools-automedia&action=adult");
			}
			else
			{
				$site = htmlspecialchars_uni($mybb->input['site']);
				$query_act2 = $db->simple_select('automedia', '*', "name='".$site."'");
				$active2 = $db->fetch_array($query_act2); 
				if(!$active2)
				{
					$automedia_special = array(
						"name" => $site,
						"class" => "special",
					);
					$db->insert_query("automedia", $automedia_special);
					automedia_cache();

					$mybb->input['module'] = "AutoMedia";
					$mybb->input['action'] = $lang->automedia_modules_active." ";
					log_admin_action(ucfirst($site));

					flash_message($lang->automedia_modules_active, 'success');
					admin_redirect("index.php?module=tools-automedia&action=adult");
				}
				else
				{
					flash_message($lang->automedia_modules_notfound,'error');
				}
			}
			exit();
		}

		//Deactivate site module
		if($mybb->input['action'] == 'deactivate')
		{
			if(!verify_post_check($mybb->input['my_post_key']))
			{
				flash_message($lang->invalid_post_verify_key2, 'error');
				admin_redirect("index.php?module=tools-automedia");
			}
			else
			{
				$site = htmlspecialchars_uni($mybb->input['site']);
				$query_del1 = $db->simple_select('automedia', '*', "name='".$site."'");
				$delete1 = $db->fetch_array($query_del1); 
				if($delete1['name'] == $site)
				{
					$db->delete_query('automedia', "name='{$site}'");
					automedia_cache();

					$mybb->input['module'] = "AutoMedia";
					$mybb->input['action'] = $lang->automedia_modules_deleted." ";
					log_admin_action(ucfirst($site));

					flash_message($lang->automedia_modules_deleted, 'success');
					admin_redirect("index.php?module=tools-automedia");
				}
				else
				{
					flash_message($lang->automedia_modules_notfound,'error');
				}
			}
			exit();
		}

		//Deacticate special module
		if($mybb->input['action'] == 'adultdeactivate')
		{
			if(!verify_post_check($mybb->input['my_post_key']))
			{
				flash_message($lang->invalid_post_verify_key2, 'error');
				admin_redirect("index.php?module=tools-automedia&action=adult");
			}
			else
			{
				$site = htmlspecialchars_uni($mybb->input['site']);
				$query_del2 = $db->simple_select('automedia', '*', "name='".$site."'");
				$delete2 = $db->fetch_array($query_del2); 
				if($delete2['name'] == $site)
				{
					$db->delete_query('automedia', "name='{$site}'");
					automedia_cache();

					$mybb->input['module'] = "AutoMedia";
					$mybb->input['action'] = $lang->automedia_modules_deleted." ";
					log_admin_action(ucfirst($site));

					flash_message($lang->automedia_modules_deleted, 'success');
					admin_redirect("index.php?module=tools-automedia&action=adult");
				}
				else
				{
					flash_message($lang->automedia_modules_notfound,'error');
				}
			}
			exit();
		}

		//Activate all site modules
		if($mybb->input['action'] == 'activateallsites')
		{
			if(!verify_post_check($mybb->input['my_post_key']))
			{
				flash_message($lang->invalid_post_verify_key2, 'error');
				admin_redirect("index.php?module=tools-automedia");
			}
			else
			{
				$folder1 = MYBB_ROOT."inc/plugins/automedia/sites/";
				if(is_dir($folder1))
				{
					$mediafiles1 = scandir($folder1);

					foreach ($mediafiles1 as $sites1) 
					{ // Fetch all files in the folder
						$siteinfo1 = pathinfo($folder1."/".$sites1);
						if($sites1 != "." && $sites1 != "..")
						{
							$filetype1 = "php";
							//We need only php files
							if($siteinfo1['extension'] == $filetype1)
							{
								$media1 = str_replace(".php", "", $sites1);
								$check1 = file_get_contents($folder1.$siteinfo1['basename']);
								if(preg_match('"function automedia_"isU', $check1))
								{
									$query_ex = $db->simple_select('automedia', 'name', "name='".htmlspecialchars_uni($media1)."'");
									$modactive = $db->fetch_array($query_ex);
									if(!$modactive)
									{
										// activate site
										$automedia_site1 = array(
											"name" => htmlspecialchars_uni($media1),
											"class" => "site",
										);
										$db->insert_query("automedia", $automedia_site1);
									}
								}
							}
						}
					}
					automedia_cache();
				}
			}
			admin_redirect("index.php?module=tools-automedia");
			exit();
		}

		//Activate all special modules
		if($mybb->input['action'] == 'activateallspecial')
		{
			if(!verify_post_check($mybb->input['my_post_key']))
			{
				flash_message($lang->invalid_post_verify_key2, 'error');
				admin_redirect("index.php?module=tools-automedia");
			}
			else
			{
				$folder2 = MYBB_ROOT."inc/plugins/automedia/special/";
				if(is_dir($folder2))
				{
					$mediafiles2 = scandir($folder2);

					foreach ($mediafiles2 as $sites2) 
					{ // Fetch all files in the folder
						$siteinfo2 = pathinfo($folder2."/".$sites2);
						if($sites2 != "." && $sites2 != "..")
						{
							$filetype2 = "php";
							//We need only php files
							if($siteinfo2['extension'] == $filetype2)
							{
								$media2 = str_replace(".php", "", $sites2);
								$check2 = file_get_contents($folder2.$siteinfo2['basename']);
								if(preg_match('"function automedia_"isU', $check2))
								{
									$query_ex2 = $db->simple_select('automedia', 'name', "name='".htmlspecialchars_uni($media2)."'");
									$modactive2 = $db->fetch_array($query_ex2);
									if(!$modactive2)
									{
										// add site
										$automedia_site2 = array(
											"name" => htmlspecialchars_uni($media2),
											"class" => "special",
										);
										$db->insert_query("automedia", $automedia_site2);
									}	
								}
							}
						}
					}
					automedia_cache();
				}
			}
			admin_redirect("index.php?module=tools-automedia&action=adult");
			exit();
		}
		
		//Show site module code
		if($mybb->input['action'] == 'showsite')
		{
			if(!verify_post_check($mybb->input['my_post_key']))
			{
				flash_message($lang->invalid_post_verify_key2, 'error');
				admin_redirect("index.php?module=tools-automedia");
			}
			else
			{
			$site = htmlspecialchars_uni($mybb->input['site']);
			$page->add_breadcrumb_item("Embed-Code");
			$page->output_header($lang->automedia_modules_showcode);

			$sub_tabs['automedia'] = array(
				'title'			=> $lang->automedia_modules,
				'link'			=> 'index.php?module=tools-automedia',
				'description'	=> $lang->automedia_modules
			);
			if($mybb->settings['av_adultsites'] == 1)
			{
				$sub_tabs['special']=array(
					'title'=>$lang->automedia_adult,
					'link'=>'index.php?module=tools-automedia&amp;action=adult',
					'description'=>$lang->automedia_adult_description1
				);
			}
			$sub_tabs['embedcode'] = array(
				'title'			=> 'Embed Code',
				'link'			=> 'index.php?module=tools-automedia&amp;action=showsite&amp;site='.urlencode($site).'&amp;my_post_key='.$mybb->post_code.'',
				'description'	=> $lang->automedia_modules_viewcode
			);
			$page->output_nav_tabs($sub_tabs, 'embedcode');
			$amtable=new Table;
			$amtable->construct_header(ucfirst($site).' Embed Code:');

			$codefile = MYBB_ROOT."inc/plugins/automedia/sites/".$site.".php";
			if(is_file($codefile))
			{
				$embedcode = file_get_contents($codefile);
				$showcode = @highlight_string($embedcode, true);
				$amtable->construct_cell($showcode);
			}
			$amtable->construct_row();
			$amtable->output($lang->automedia_modules_showcode);
			$page->output_footer();
			}
		exit();
		}


		//Show special module code
		if($mybb->input['action'] == 'showspecial')
		{
			if(!verify_post_check($mybb->input['my_post_key']))
			{
				flash_message($lang->invalid_post_verify_key2, 'error');
				admin_redirect("index.php?module=tools-automedia");
			}
			else
			{
			$site = htmlspecialchars_uni($mybb->input['site']);
			$page->add_breadcrumb_item("Embed-Code");
			$page->output_header($lang->automedia_modules_showcode);

			$sub_tabs['automedia'] = array(
				'title'			=> $lang->automedia_modules,
				'link'			=> 'index.php?module=tools-automedia',
				'description'	=> $lang->automedia_modules
			);
			if($mybb->settings['av_adultsites'] == 1)
			{
				$sub_tabs['special']=array(
					'title'=>$lang->automedia_adult,
					'link'=>'index.php?module=tools-automedia&amp;action=adult',
					'description'=>$lang->automedia_adult_description1
				);
			}
			$sub_tabs['embedcode'] = array(
				'title'			=> 'Embed Code',
				'link'			=> 'index.php?module=tools-automedia&amp;action=showspecial&amp;site='.urlencode($site).'&amp;my_post_key='.$mybb->post_code.'',
				'description'	=> $lang->automedia_modules_viewcode
			);
			$page->output_nav_tabs($sub_tabs, 'embedcode');
			$amtable=new Table;
			$amtable->construct_header(ucfirst($site).' Embed Code:');

				$codefile = MYBB_ROOT."inc/plugins/automedia/special/".$site.".php";
				if(is_file($codefile))
				{
					$embedcode = file_get_contents($codefile);
					$showcode = @highlight_string($embedcode, true);
					$amtable->construct_cell($showcode);
				}
				$amtable->construct_row();
				$amtable->output($lang->automedia_modules_showcode);
				$page->output_footer();
			}
		exit();
		}
		
		//Reapply template edits
		if($mybb->input['action'] == "templateedits")
		{
			if(!verify_post_check($mybb->input['my_post_key']))
			{
				flash_message($lang->invalid_post_verify_key2, 'error');
				admin_redirect("index.php?module=config-plugins");
			}
			else
			{
				automedia_reapply_template_edits();
				admin_redirect("index.php?module=config-plugins");
			}	
		exit();
		}	
	}
}


//Let other youtube MyCodes still do their work 
function automedia_oldyt_run($message)
{
	global $mybb;

	$message = preg_replace("#\[youtube\]http://((?:de|www)\.)?youtube.com/watch\?v=([A-Za-z0-9\-\_]+)\[/youtube\]#i", '<div class=\'am_embed\'><object width=\'425\' height=\'350\'><param name=\'movie\' value=\'http://www.youtube.com/v/$2\' /><embed src=\'http://www.youtube.com/v/$2\' type=\'application/x-shockwave-flash\' width=\'425\' height=\'350\'></embed></object></div>', $message);
	$message = preg_replace("#\[youtube\]([A-Za-z0-9\-\_]+)\[/youtube\]#i", '<div class=\'am_embed\'><object width=\'425\' height=\'350\'><param name=\'movie\' value=\'http://www.youtube.com/v/$1\' /><embed src=\'http://www.youtube.com/v/$1\' type=\'application/x-shockwave-flash\' width=\'425\' height=\'350\'></embed></object></div>', $message);
	$message = preg_replace("#\[yt\]http://((?:de|www)\.)?youtube.com/watch\?v=([A-Za-z0-9\-\_]+)\[/yt\]#i", '<div class=\'am_embed\'><object width=\'425\' height=\'350\'><param name=\'movie\' value=\'http://www.youtube.com/v/$2\' /><embed src=\'http://www.youtube.com/v/$2\' type=\'application/x-shockwave-flash\' width=\'425\' height=\'350\'></embed></object></div>', $message);
	$message = preg_replace("#\[yt\]([A-Za-z0-9\-\_]+)\[/yt\]#i", '<div class=\'am_embed\'><object width=\'425\' height=\'350\'><param name=\'movie\' value=\'http://www.youtube.com/v/$1\' /><embed src=\'http://www.youtube.com/v/$1\' type=\'application/x-shockwave-flash\' width=\'425\' height=\'350\'></embed></object></div>', $message);
	// mp3 playlist mycode
	$message = preg_replace("#\[ampl\](.*?)\[/ampl\]#i", "<div class=\"am_embed\"><object type=\"application/x-shockwave-flash\" data=\"{$mybb->settings['bburl']}/inc/plugins/automedia/mediaplayer/emff_position_blue.swf\" width=\"100\" height=\"50\" /><param name=\"movie\" value=\"{$mybb->settings['bburl']}/inc/plugins/automedia/mediaplayer/emff_position_blue.swf\" /><param name=\"FlashVars\" value=\"src=$1\" /></object></div>", $message);

	return $message;
}


// Lookup 
function get_avmatch($regex,$content)
{
	preg_match($regex, $content, $matches);
	return $matches[1];
}


// Embed the Media Files 
function automedia_run($message)
{
	global $db, $mybb, $cache, $post, $postrow, $pmid, $memprofile, $automedia, $width, $height;


	/**
	 * Get the posts author
	 */
	if(THIS_SCRIPT=="private.php")
	{
		$pri = intval($pmid); 
		$query  = $db->simple_select("privatemessages", "fromid", "pmid='$pri'");
		$priuid = $db->fetch_array($query);
		$muid = intval($priuid['fromid']);
	}
	else if(THIS_SCRIPT=="usercp.php")
	{
		$muid = intval($mybb->user['uid']);
	}
	else if(THIS_SCRIPT=="member.php")
	{
		$muid = intval($memprofile['uid']);
	}
	else if(THIS_SCRIPT=="printthread.php")
	{
		$muid = intval($postrow['uid']);
	}	
	else
	{
		$muid = intval($post['uid']);
	}
	$muser = get_user($muid);

	/**
	 * Get the settings for the forums
	 */
	$avfid = intval($post['fid']);

	//Find the set fid's in settings
	$fids = explode(',', $mybb->settings['av_forums']);
	if (in_array($avfid,$fids))
	{
		$efid = false;
	}
	else
	{
		$efid = true;
	}


	/**
	 *Get the settings for the usergroups
	 */
	//Find the excluded groups in settings
	$gid = intval($mybb->user['usergroup']);
	$groups = explode(',', $mybb->settings['av_groups']);
	if (in_array($gid,$groups))
	{
		$egid = true;
	}
	else {
		$egid = false;
	}


	//Find the users groups
	$ag = explode(',',$mybb->user['additionalgroups']);

	foreach($ag as $a)
	{
		if (in_array($a,$groups))
		{
			$agid = true;
		}
		else
		{
			$agid = false;
		}
	}

	/**
	 * Get the settings for flash width and height
	 */	
	$width = intval($mybb->settings['av_flashwidth']);
	$height = intval($mybb->settings['av_flashheight']);

	if($width >= 10 && $width <= 1200) {
		$width = $width;
	} else {
		$width = "480";
	}

	if($height >= 10 && $height <= 1000) {
		$height = $height;
	} else {
		$height = "360";
	}

	//Add new MyCode for disabling embedding
	$message = preg_replace("#\[amoff\](<a href=\")(http://)(.*?)\" target=\"_blank\">(.*?)(</a>)\[/amoff\]#i", '<a name=\'amoff\' href=\'${2}${3}\' id= \'am\' target=\'_blank\'>${4}</a>', $message);
	$message = preg_replace("#\[amoff\](http://)(.*?)\[/amoff\]#i", '<a name=\'amoff\' href=\'${1}${2}\' id= \'am\' target=\'_blank\'>${1}${2}</a>', $message);

	//Find if embedding is disabled in quoted posts
	$startpattern = '#\[amquote\](.*?)\[\/amquote\]#esi';
	preg_match_all($startpattern, $message, $starts);
	foreach($starts[1] as $start)
	{
		$start = $start;
	}

	/**
	 * Apply the permissions
	 */

	//AutoMedia not disabled in settings?
	if ($mybb->settings['av_enable'] != 0)
	{
		//Embedding not disabled by using MyCode?
		if (!preg_match('/<a name=\"amoff\" href=\"(.*)\" id=\"am\" target=\"_blank\">/isU',$message))
		{
			//AutoMedia allowed for guests in settings?
			if ($mybb->settings['av_guest'] != 0 || ($mybb->user['uid'] != 0))
			{
				//Are only certain forums set?
				if (!$efid || $mybb->settings['av_forums'] == 0 || defined("IN_PORTAL"))
				{
					//Has the user AutoMedia enabled in User CP?
					if($mybb->user['automedia_use'] != 'N')
					{
						//Groups not excluded in settings? - Admins can't be excluded in settings
						if(!$egid && !$agid || $mybb->settings['av_groups'] == 0 || $mybb->usergroup['cancp'] == 1)
						{
							//Has the author AutoMedia enabled in User CP?
							if($muser['automedia_use'] != 'N')
							{
								/**
								* Embed the files
								**/ 
								$sitecache = $cache->read('automedia');
								if(is_array($sitecache))
								{
									foreach ($sitecache as $key => $sites)
									{
										if($sites['class'] == "site")
										{
											$site = htmlspecialchars_uni($sites['name']);
											$file = MYBB_ROOT."inc/plugins/automedia/sites/{$site}.php";
											if(file_exists($file))
											{
												require_once($file);
												$fctn = "automedia_".$site;
												$message = $fctn($message); 
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		//Embedding disabled in quoted posts?
		$endpattern = '#\[amquote\](.*?)\[\/amquote\]#esi';
		preg_match_all($endpattern, $message, $ends);
		$end = $ends[1];	
		foreach ($end as $amquote)
		{
			$message = str_replace($amquote , $start, $message);
		}
		$message = str_replace(array('[amquote]', '[/amquote]'), '', $message);
	}
	return $message;
}


//Embed Adult Site Videos  
function automedia_adult_run($message)
{
	global $db, $mybb, $cache, $post, $postrow, $pmid, $memprofile, $automedia_adult, $width, $height;
	
	$sitecache = $cache->read('automedia');

	/**
	 * Get the posts author
	 */
	if(THIS_SCRIPT=="private.php")
	{
		$pri = intval($pmid); 
		$query  = $db->simple_select("privatemessages", "fromid", "pmid='$pri'");
		$priuid = $db->fetch_array($query);
		$muid = intval($priuid['fromid']);
	}
	else if(THIS_SCRIPT=="usercp.php")
	{
		$muid = intval($mybb->user['uid']);
	}
	else if(THIS_SCRIPT=="member.php")
	{
		$muid = intval($memprofile['uid']);
	}
	else if(THIS_SCRIPT=="printthread.php")
	{
		$muid = intval($postrow['uid']);
	}
	else
	{
		$muid = intval($post['uid']);
	}
	$muser = get_user($muid);

	/**
	 * Get the settings for width and height
	 */	
	$width = intval($mybb->settings['av_flashwidth']);
	$height = intval($mybb->settings['av_flashheight']);

	if($width >= 10 && $width <= 1200) {
		$width = $width;
	} else {
		$width = "480";
	}

	if($height >= 10 && $height <= 1000) {
		$height = $height;
	} else {
		$height = "360";
	}

	//Find the set fid's in adult settings
	$avfid = intval($post['fid']);

	$adfids = explode(',', $mybb->settings['av_adultforums']);
	if (in_array($avfid,$adfids))
	{
		$adultfid = false;
	}
	else
	{
		$adultfid = true;
	}

	/**
	 *Get the settings for the usergroups
	 */
	//Find the allowed adult primary groups in settings
	$gid = intval($mybb->user['usergroup']);
	$adultgroups = explode(',', $mybb->settings['av_adultgroups']);
	if (in_array($gid,$adultgroups))
	{
		$adultgid = false;
	}
	else
	{
		$adultgid = true;
	}

	//Find the allowed adult secondary groups in settings
	$adag = explode(',', $mybb->user['additionalgroups']);
	foreach($adag as $ad)
	{
		if (in_array($ad,$adultgroups))
		{
			$adultaddgid = false;
		}
		else
		{
			$adultaddgid = true;
		}
	}

	//Add new MyCode for disabling embedding
	$message = preg_replace("#\[amoff\](<a href=\")(http://)(.*?)\" target=\"_blank\">(.*?)(</a>)\[/amoff\]#i", '<a name=\'amoff\' href=\'${2}${3}\' id= \'am\' target=\'_blank\'>${4}</a>', $message);
	$message = preg_replace("#\[amoff\](http://)(.*?)\[/amoff\]#i", '<a name=\'amoff\' href=\'${1}${2}\' id= \'am\' target=\'_blank\'>${1}${2}</a>', $message);

	//Find if embedding is disabled in quoted posts
	$startpattern = '#\[amquote\](.*?)\[\/amquote\]#esi';
	preg_match_all($startpattern, $message, $starts);
	foreach($starts[1] as $start)
	{
		$start = $start;
	}

	/**
	 * Apply the permissions
	 */

	//Adultsites enabled?
	if($mybb->settings['av_adultsites'] != 0)
	{

		//Has the user AutoMedia enabled in User CP?
		if($mybb->user['automedia_use'] != 'N')
		{

			//Embedding not disabled by using MyCode?
			if (!preg_match('/<a name=\"amoff\" href=\"(.*)\" id=\"am\" target=\"_blank\">/isU',$message))
			{
				//Adultsites allowed for guests in settings?
				if ($mybb->settings['av_adultguest'] != 0 || ($mybb->user['uid'] != 0))
				{
					// User in allowed group? Admins always allowed, 0 = all groups allowed
					if(!$adultgid || !$adultaddgid || $mybb->usergroup['cancp'] == 1 || $mybb->settings['av_adultgroups'] == 0)
					{
						//Forums set for adult sites?
						if(!$adultfid || $mybb->settings['av_adultforums'] == 0)
						{
							//Has the author AutoMedia enabled in User CP?
							if($muser['automedia_use'] != 'N')
							{
								/**
								* Embed the files
								**/ 
								$sitecache = $cache->read('automedia');
								if(is_array($sitecache))
								{
									foreach ($sitecache as $key => $sites)
									{
										if($sites['class'] == "special")
										{
											$site = htmlspecialchars_uni($sites['name']);
											$file = MYBB_ROOT."inc/plugins/automedia/special/{$site}.php";
											if(file_exists($file))
											{
												require_once($file);
												$fctn = "automedia_".$site;
												$message = $fctn($message);
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		//Embedding disabled in quoted posts?
		$endpattern = '#\[amquote\](.*?)\[\/amquote\]#esi';
		preg_match_all($endpattern, $message, $ends);
		$end = $ends[1];	
		foreach ($end as $amquote)
		{
			$message = str_replace($amquote , $start, $message);
		}
		$message = str_replace(array('[amquote]', '[/amquote]'), '', $message);
	}

	return $message;
}


//Embedding disabled in signatures 
function automedia_hide(&$post)
{
	global $mybb, $lang, $settings, $automedia;

	$lang->load("automedia");

	if ($mybb->settings['av_signature'] != 1)
	{
		$post['signature'] = preg_replace("!<div class=\'am_embed\'>(.*?)</div>!i", "{$lang->av_sigreplace}", $post['signature']);
		$post['signature'] = preg_replace("!<div class=\"am_embed\">(.*?)</div>!i", "{$lang->av_sigreplace}", $post['signature']);
		$post['signature'] = preg_replace("!<object(.*?)</object>!i", "{$lang->av_sigreplace}", $post['signature']);
		$post['signature'] = preg_replace("!<embed(.*?)</embed>!i", "{$lang->av_sigreplace}", $post['signature']);
	}
}


// Message in User CP signature preview and profile if embedding in signatures is disabled 
function automedia_sigpreview($page)
{
	global $mybb, $lang, $settings, $amsigpreview, $templates;

	$lang->load("automedia");

	if(THIS_SCRIPT=="usercp.php" || THIS_SCRIPT=="member.php")
	{
		if ($mybb->settings['av_signature'] != 1)
		{
			$page = preg_replace("!<div class=\'am_embed\'>(.*?)</div>!i", "{$lang->av_sigreplace}", $page);
			$page = preg_replace("!<div class=\"am_embed\">(.*?)</div>!i", "{$lang->av_sigreplace}", $page);
		}
	}

	return $page;
}

//Don't embed video/audio if quoting another post
function automedia_quote()
{
    global $mybb, $message;

	if($mybb->settings['av_quote'] != 1)
	{
		if(isset($mybb->input['pid']))
		{
			$message = str_replace("[amoff][amoff]", "[amoff]", $message);
			$message = str_replace("[/amoff][/amoff]", "[/amoff]", $message);
			$message = str_replace(array("\r\n[/amoff]", "\r[/amoff]", "\n[/amoff]"), "[/amoff]", $message);
			$message = str_replace(array("[amquote]", "[/amquote]"), "", $message);
			$message = "[amquote]".$message."[/amquote]";
			$message = str_replace(array("\r\n[/amquote]", "\r[/amquote]", "\n[/amquote]"), "[/amquote]", $message);			
		}
	}	
}

//Don't embed video/audio if using multiquote for quoting another post
function automedia_multiquote()
{
    global $mybb;
    
	if($mybb->settings['av_quote'] != 1)
	{
		if($mybb->input['action'] != 'get_multiquoted') return;
		ob_start();
    
		function automedia_shutdown()
		{
			global $message;
			if(!$message)
			{
				ob_end_flush();
				return;
			}
        
			ob_end_clean();

			$message = str_replace("[amoff][amoff]", "[amoff]", $message);
			$message = str_replace("[/amoff][/amoff]", "[/amoff]", $message);
			$message = str_replace(array("\r\n[/amoff]", "\r[/amoff]", "\n[/amoff]"), "[/amoff]", $message);
			$message = str_replace(array("[amquote]", "[/amquote]"), "", $message);
			$message = "[amquote]".$message."[/amquote]";
			$message = str_replace(array("\r\n[/amquote]", "\r[/amquote]", "\n[/amquote]"), "[/amquote]", $message);			
			echo $message;
		}
		register_shutdown_function('automedia_shutdown');
	}
} 	

// Build and empty cache
function automedia_cache($clear=false)
{
	global $cache;
	if($clear==true)
	{
		$cache->update('automedia',false);
	}
	else
	{
		global $db;
		$sites = array();
		$query=$db->simple_select('automedia','name,class');
		while($site=$db->fetch_array($query))
		{
			$sites[$site['name']] = $site;
		}
		$cache->update('automedia',$sites);
	}
}


//Use MyBB 1.6.* maxpostvideos settings
function automedia_count(&$post)
{
	global $mybb, $lang, $settings, $automedia;

	$lang->load("automedia");

		// Get the permissions of the user who is making this post or thread
		$permissions = user_permissions($post['uid']);

		// Check if this post contains more videos than the forum allows
		if($post['savedraft'] != 1 && $mybb->settings['maxpostvideos'] != 0 && $permissions['cancp'] != 1)
		{
			// And count the number of all videos in the message.
			$automedia_count = substr_count($post['message'], "am_embed");
			$vids_count = substr_count($post['message'], "video_embed");
			$all_count = $automedia_count + $vids_count;
			if($all_count > $mybb->settings['maxpostvideos'])
			{
				// Throw back a message if over the count as well as the maximum number of videos per post.
				$post['message'] = "<div style=\"color:#FF0000\"><strong><u>{$lang->av_vidcount} {$mybb->settings['maxpostvideos']}</u></strong></div>";
			}
		}
}


//Show codebuttons for disabling embedding and mp3-playlist MyCode
function automedia_codebutton($page) 
{
	global $mybb, $lang;

	$lang->load("automedia");

	if($mybb->settings['av_codebuttons'] == 1)
	{
		if(THIS_SCRIPT=="newthread.php" || THIS_SCRIPT=="usercp.php" && $mybb->input['action'] == "editsig" || THIS_SCRIPT=="calendar.php" || THIS_SCRIPT=="showthread.php" || THIS_SCRIPT=="newreply.php" || THIS_SCRIPT=="editpost.php")
		{
			$page = str_replace('</textarea>', '</textarea><br /><script type="text/javascript" src="'.$mybb->settings['bburl'].'/inc/plugins/automedia/automedia.js"></script>
<a id="amoff" href="javascript:void(0);"><img src="inc/plugins/automedia/amoff.png" width="29" height="25" alt="'.$lang->av_amoff.'" title="'.$lang->av_amoff.'" /></a>&nbsp;&nbsp;
<a id="ampl" href="javascript:void(0);"><img src="inc/plugins/automedia/ampl.png" width="29" height="25" alt="AutoMedia MP3 Playlist" title="AutoMedia MP3 Playlist" /></a><br /><br />', $page);  
		}
		if(THIS_SCRIPT=="private.php")
		{
			$page = str_replace('<label><input type="checkbox" class="checkbox" name="options[signature]"', '<br /><script type="text/javascript" src="'.$mybb->settings['bburl'].'/inc/plugins/automedia/automedia.js"></script>
<a id="amoff" href="javascript:void(0);"><img src="inc/plugins/automedia/amoff.png" width="29" height="25" alt="'.$lang->av_amoff.'" title="'.$lang->av_amoff.'" /></a>&nbsp;&nbsp;
<a id="ampl" href="javascript:void(0);"><img src="inc/plugins/automedia/ampl.png" width="29" height="25" alt="MP3 Playlist" title="MP3 Playlist" /></a><br /><br />
<label><input type="checkbox" class="checkbox" name="options[signature]"', $page);
		}
    return $page;
	}
}
?>
