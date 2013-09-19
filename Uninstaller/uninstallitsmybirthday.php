<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by 
 * the Free Software Foundation, either version 3 of the License, 
 * or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 * See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License 
 * along with this program.  
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * $Id: uninstallitsmybirthday.php 10 2010-09-15 23:01:04Z - G33K - $
 */
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function uninstallitsmybirthday_info()
{
	global $db, $plugins_cache;
	
    $info = array(
        "name"				=> "Its My Birthday! Uninstaller",
        "description"		=> "Completely Uninstalls the Its My Birthday! Plugin.",
        "website"			=> "http://g33k.host-ed.net/",
        "author"			=> "- G33K -",
        "authorsite"		=> "http://community.mybboard.net/user-19236.html",
        "version"			=> "1.3",
		"guid" 				=> "",
		"compatibility" 	=> "14*"
    );
    
    if(($db->field_exists('next_bday_year', 'users')) || (is_array($plugins_cache) && is_array($plugins_cache['active']) && $plugins_cache['active']['itsmybirthday']))
	{
		$info['description'] = "<ul><li style=\"list-style-image: url(styles/default/images/icons/error.gif)\">Its My Birthday! Plugin is still Installed or Activated, Please Uninstall and Deactivate Its My Birthday! Plugin before Installing and Activating this Uninstaller Plugin or else Its My Birthday will not be removed.</li></ul>";
	}
	else if(is_array($plugins_cache) && is_array($plugins_cache['active']) && $plugins_cache['active']['uninstallitsmybirthday'])
    {
		$info['description'] = "<br />Deactivate this Uninstaller to COMPLETELY remove Its My Birthday! Plugin and all the data associated with the plugin, including the Happy Birthday Wishes added to posts.<br />";
    }
    else
    {
	    $info['description'] = "<br />Activate and Deactivate this plugin once to COMPLETELY remove Its My Birthday! Plugin and all the data associated with the plugin, including the Happy Birthday Wishes added to posts.<br />";
    }
		
    return $info;
}

function uninstallitsmybirthday_deactivate()
{
	global $db, $mybb, $cache, $plugins_cache;
	
	if(($db->field_exists('next_bday_year', 'users')) || (is_array($plugins_cache) && is_array($plugins_cache['active']) && $plugins_cache['active']['itsmybirthday']))
	{
		// imb Plugin is still Installed/Active, do nothing
	}
	else
	{
		// Do Uninstall
		if($db->field_exists('next_bday_year', 'users'))
		{
			$db->query("ALTER TABLE ".TABLE_PREFIX."users DROP column `next_bday_year`");
		}
		if($db->field_exists('itsmybirthday_bdaypostfor_uid', 'posts'))
		{
			$db->query("ALTER TABLE ".TABLE_PREFIX."posts DROP column `itsmybirthday_bdaypostfor_uid`");
		}
		if($db->field_exists('itsmybirthday_bdaypostfor_username', 'posts'))
		{
			$db->query("ALTER TABLE ".TABLE_PREFIX."posts DROP column `itsmybirthday_bdaypostfor_username`");
		}
		if($db->table_exists('g33k_itsmybirthday_bdaywishes'))
		{
			$db->drop_table('g33k_itsmybirthday_bdaywishes');
		}
		if($db->table_exists('g33k_itsmybirthday_runtime'))
		{
			$db->drop_table('g33k_itsmybirthday_runtime');
		}
		
		// The stuff below should have been removed when uninstalling imb, but we will go over it once more just to be sure
		// Remove Post Icon
		$db->delete_query("icons", "iid='{$mybb->settings[$prefix.'thread_post_iid']}' OR path='images/icons/itsmybirthday.gif'");
		$cache->update_posticons();
		
		// Remove Templates
		$db->delete_query("templates", "title='itsmybirthday_wishes'");
		$db->delete_query("templates", "title='itsmybirthday_wishes_button_add'");
		$db->delete_query("templates", "title='itsmybirthday_wishes_button_del'");
		$db->delete_query("templates", "title='itsmybirthday_wishes_users'");
		
		include MYBB_ROOT."/inc/adminfunctions_templates.php";
	
		find_replace_templatesets("headerinclude", "#".preg_quote('<script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/itsmybirthday.js?ver=220"></script>
<script type="text/javascript">
<!--
	var imb_wishesEnabled = "{$mybb->settings[\'g33k_itsmybirthday_wishes_enabled\']}";
	var imb_wishesRemovable = "{$mybb->settings[\'g33k_itsmybirthday_wishes_removable\']}";
-->
</script>
')."#i", '', 0);
		find_replace_templatesets("postbit", "#".preg_quote('	<tr style="{$post[\'imb_display\']};" id="imb_{$post[\'pid\']}">
			{$post[\'itsmybirthday_wishes_data\']}
		</tr>
	')."#i", '', 0);
		find_replace_templatesets("postbit", "#".preg_quote('{$post[\'button_bday\']}')."#i", '', 0);
		
		// Lets also check for any remnants from previous versions and remove them
	
	//v2.1
	find_replace_templatesets("showthread", "#".preg_quote('<script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/itsmybirthday.js?ver=200"></script>
<script type="text/javascript">
<!--
	var imb_wishesEnabled = "{$mybb->settings[\'g33k_itsmybirthday_wishes_enabled\']}";
	var imb_wishesRemovable = "{$mybb->settings[\'g33k_itsmybirthday_wishes_removable\']}";
-->
</script>
')."#i", '', 0);

	//v2.0
	find_replace_templatesets("headerinclude", "#".preg_quote('<script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/itsmybirthday.js?ver=200"></script>
<script type="text/javascript">
<!--
	var imb_wishesEnabled = "{$mybb->settings[\'g33k_itsmybirthday_wishes_enabled\']}";
	var imb_wishesRemovable = "{$mybb->settings[\'g33k_itsmybirthday_wishes_removable\']}";
-->
</script>
')."#i", '', 0);
	find_replace_templatesets("postbit", "#".preg_quote('	<tr style="{$post[\'imb_display\']};" id="imb_{$post[\'pid\']}">
			{$post[\'itsmybirthday_wishes_data\']}
		</tr>
	')."#i", '', 0);
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'button_bday\']}')."#i", '', 0);
		
		// Remove Settings
		$result = $db->simple_select('settinggroups', 'gid', "name = '{$prefix}settings'", array('limit' => 1));
		$group = $db->fetch_array($result);
	
		if(!empty($group['gid']))
		{
			$db->delete_query('settinggroups', "gid='{$group['gid']}'");
			$db->delete_query('settings', "gid='{$group['gid']}'");
			rebuild_settings();
		}
	}			
}
?>