<?php
/*********************************************************************************************
+ Advertisement in Posts v0.1 : A Plugin for MyBB 1.4 and 1.6
+ Free to Use
+ Free to Edit
+ But Not Allowed to distribute
**********************************************************************************************
*/
if(!defined("IN_MYBB")){
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}
function ads_posts_info(){
	return array(		"name"			=> "Advertisement in Posts",		"description"	=> "Allows you to show ads with several different options.",		"website"		=> "http://yaldaram.com",		"author"		=> "Yaldaram",		"authorsite"	=> "http://yaldaram.com",		"version"		=> "0.1",		"compatibility" => "14*,16*"	);
}
function ads_posts_activate(){
	global $db, $mybb;
	$ads_posts_group = array(		"gid"			=> "NULL",		"name"			=> "ads_posts",		"title" 		=> "Advertisement in Posts",		"description"	=> "Settings for the plugin.",		"disporder"		=> "1",		"isdefault"		=> "no",	);
	$db->insert_query("settinggroups", $ads_posts_group);
	$gid = $db->insert_id();
	$ads_posts_setting_1 = array(		"sid"			=> "NULL",		"name"			=> "ads_posts_power",		"title"			=> "Power",		"description"	=> "Select Yes if you really wants this plugin to run.",		"optionscode"	=> "yesno",		"value"			=> "1",		"disporder"		=> "1",		"gid"			=> intval($gid),	);
	$db->insert_query("settings", $ads_posts_setting_1);
	$ads_posts_setting_2 = array(		"sid"			=> "NULL",		"name"			=> "ads_posts_group",		"title"			=> "Allowed_Groups",		"description"	=> "Specify usergroups who must see these ads. Separate with , if more then one.",		"optionscode"	=> "text",		"value"			=> "1,2,5,7",		"disporder"		=> "2",		"gid"			=> intval($gid),	);
	$db->insert_query("settings", $ads_posts_setting_2);
	$ads_posts_setting_3 = array(		"sid"			=> "NULL",		"name"			=> "ads_posts_forum",		"title"			=> "Dis-allowed_Forums",		"description"	=> "Specify forums where these ads are hidden (e.g. where you don\'t want to show these ads). Separate with , if more then one.",		"optionscode"	=> "text",		"value"			=> "2,3",		"disporder"		=> "3",		"gid"			=> intval($gid),	);
	$db->insert_query("settings", $ads_posts_setting_3);
	$ads_posts_setting_4 = array(		"sid"			=> "NULL",		"name"			=> "ads_posts_position",		"title"			=> "Position_of_Ads",		"description"	=> "Select position of these ads.",
		"optionscode"	=> "select
float_right=Top Right Corner
float_left=Top Left Corner",
		"value"			=> "float_left",		"disporder"		=> "4",		"gid"			=> intval($gid),	);
	$db->insert_query("settings", $ads_posts_setting_4);
	$ads_posts_setting_5 = array(		"sid"			=> "NULL",		"name"			=> "ads_posts_mode",		"title"			=> "Mode_of_Ads",		"description"	=> "Select mode of ads.",
		"optionscode"	=> "select
0=First post only
1=On each post
2=After 3 posts
3=After 5 posts",
		"value"			=> "2",		"disporder"		=> "5",		"gid"			=> intval($gid),	);
	$db->insert_query("settings", $ads_posts_setting_5);
	$ads_posts_setting_6 = array(		"sid"			=> "NULL",		"name"			=> "ads_posts_code",		"title"			=> "Ads_Code",		"description"	=> "Specify ads code.",		"optionscode"	=> "textarea",		"value"			=> "Ads Code Goes Here",		"disporder"		=> "6",		"gid"			=> intval($gid),	);
	$db->insert_query("settings", $ads_posts_setting_6);
    rebuild_settings();
	require MYBB_ROOT."/inc/adminfunctions_templates.php";
	$template = array(
		"title"		=> "ads_posts",
		"template"	=> '<div style="border: 1px solid #FF0000" bgcolor="#FFCCFF" class="{$float}">{$ads_code}</div>',
		"sid"		=> -1
	);
	$db->insert_query("templates", $template);
find_replace_templatesets("postbit", "#".preg_quote('{$post[\'message\']}')."#i", '{\$post[\'ads_posts\']}{\$post[\'message\']}');
find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'message\']}')."#i", '{\$post[\'ads_posts\']}{\$post[\'message\']}');
}
function ads_posts_deactivate(){
	global $db, $mybb;
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='ads_posts'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='ads_posts_power'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='ads_posts_group'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='ads_posts_forum'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='ads_posts_position'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='ads_posts_mode'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='ads_posts_code'");
	require MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'ads_posts\']}')."#i", '', 0);
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'ads_posts\']}')."#i", '', 0);
	$db->query("DELETE FROM ".TABLE_PREFIX."templates WHERE title='ads_posts'");
    rebuild_settings();
}
$plugins->add_hook("postbit", "ads_posts");
function ads_posts($post){
	global $mybb, $templates, $postcounter;
	$power = $mybb->settings['ads_posts_power'];
	$group = $mybb->user['usergroup'];
	$groups = explode(",",$mybb->settings['ads_posts_group']);
	$forums = explode(",",$mybb->settings['ads_posts_forum']);
	if ($power != "0" && in_array($group,$groups) && !in_array($post['fid'],$forums)){
		$float = $mybb->settings['ads_posts_position'];
		$ads_code = stripslashes($mybb->settings['ads_posts_code']);
		if ($mybb->settings['ads_posts_mode'] == "1"){
			eval("\$post['ads_posts'] = \"".$templates->get("ads_posts")."\";");
		}
		elseif ($mybb->settings['ads_posts_mode'] == "2"){
			if (($postcounter - 1) % 3 == "2"){
				eval("\$post['ads_posts'] = \"".$templates->get("ads_posts")."\";");
			}
		}
		elseif ($mybb->settings['ads_posts_mode'] == "3"){
			if (($postcounter - 1) % 5 == "4"){
				eval("\$post['ads_posts'] = \"".$templates->get("ads_posts")."\";");
			}
		}
		elseif ($mybb->settings['ads_posts_mode'] == "0"){
			if (($postcounter - 1) % $mybb->settings['postsperpage'] == "0"){
				eval("\$post['ads_posts'] = \"".$templates->get("ads_posts")."\";");
			}
		}
	}
}
?>