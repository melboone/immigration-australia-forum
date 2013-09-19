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
 * $Id: itsmybirthday.php 8 2010-09-15 22:58:18Z - G33K - $
 */
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}
$plugins->add_hook("global_start", "itsmybirthday_templatelist");
$plugins->add_hook("global_end", "itsmybirthday_run");
$plugins->add_hook("postbit","itsmybirthday_wishes");
$plugins->add_hook("admin_config_settings_change","itsmybirthday_settings_page");
$plugins->add_hook("admin_page_output_footer","itsmybirthday_settings_peeker");


function itsmybirthday_info()
{
	global $plugins_cache, $db;
	
	$codename = basename(__FILE__, ".php");
	$prefix = 'g33k_'.$codename.'_';
	
    $info = array(
        "name"				=> "Its My Birthday!",
        "description"		=> "Sends Mail/PM to user on his/her Birthday. Fully customizable including option to start a thread or post and having users add wishes without posting replys.",
        "website"			=> "http://geekplugins.com/",
        "author"			=> "- G33K -",
        "authorsite"		=> "http://community.mybboard.net/user-19236.html",
        "version"			=> "2.2",
		"intver"			=> "220",
		"guid" 				=> "f62ae455756d0a7ab081b8fdd68f7581",
		"compatibility" 	=> "14*,16*"
    );
    
    if(is_array($plugins_cache) && is_array($plugins_cache['active']) && $plugins_cache['active'][$codename])
    {
	    $result = $db->simple_select('settinggroups', 'gid', "name = '{$prefix}settings'", array('limit' => 1));
		$group = $db->fetch_array($result);
	
		if(!empty($group['gid']))
		{
			$info['description'] = "<i><small>[<a href=\"index.php?module=config/settings&action=change&gid=".$group['gid']."\">Configure Settings</a>]</small></i><br />".$info['description'];
		}
	}
	
    return $info;
}

function itsmybirthday_install()
{
	global $db;
	
	$codename = basename(__FILE__, ".php");
	$prefix = 'g33k_'.$codename.'_';

	if(!$db->field_exists('next_bday_year', 'users'))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."users ADD `next_bday_year` smallint(4) NOT NULL DEFAULT '0' AFTER `birthday`");
	}
	
	if(!$db->field_exists('itsmybirthday_bdaypostfor_uid', 'posts'))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."posts ADD `itsmybirthday_bdaypostfor_uid` int NOT NULL DEFAULT '0'");
	}
	
	if(!$db->field_exists('itsmybirthday_bdaypostfor_username', 'posts'))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."posts ADD `itsmybirthday_bdaypostfor_username` varchar(80) NOT NULL DEFAULT ''");
	}
	
	if(!$db->table_exists($prefix.'bdaywishes'))
	{
		$db->query("CREATE TABLE ".TABLE_PREFIX.$prefix."bdaywishes (
				wid int unsigned NOT NULL auto_increment,
  				pid int unsigned NOT NULL default '0',
  				uid int unsigned NOT NULL default '0',
  				tid int unsigned NOT NULL default '0',
  				fid int unsigned NOT NULL default '0',
  				username varchar(80) NOT NULL default '',
  				bdayuser varchar(80) NOT NULL default '',
  				dateline bigint(30) NOT NULL default '0',
  				KEY pid (pid, uid),
  				PRIMARY KEY (wid)
				) TYPE=MyISAM;");
	}
	
	if(!$db->table_exists($prefix.'runtime'))
	{
		$db->query("CREATE TABLE ".TABLE_PREFIX.$prefix."runtime (
  				date bigint(30) NOT NULL default '0',
				hash varchar(50) NOT NULL default '',
  				done int(1) NOT NULL default '0'
				) TYPE=MyISAM;");
	}
}

function itsmybirthday_is_installed()
{
	global $db;
	
	$codename = basename(__FILE__, ".php");
	$prefix = 'g33k_'.$codename.'_';
	
	if($db->field_exists('next_bday_year', 'users') && $db->field_exists('itsmybirthday_bdaypostfor_uid', 'posts') && $db->field_exists('itsmybirthday_bdaypostfor_username', 'posts') && $db->table_exists($prefix.'bdaywishes') && $db->table_exists($prefix.'runtime'))
	{
		return true;
	}
	return false;

}

function itsmybirthday_activate()
{
	global $db, $mybb, $cache;
	
	$codename = basename(__FILE__, ".php");
	$prefix = 'g33k_'.$codename.'_';
	
	$info = itsmybirthday_info();

	// Insert Post Icon if it doesn't exist, else update iid with existing icon
	$iid = 0;
	$result = $db->simple_select('icons', '*', "path = 'images/icons/itsmybirthday.gif'", array('limit' => 1));
	$icon = $db->fetch_array($result);
	
	if(empty($icon['iid']))
	{
		$new_icon = array(
				'name' => $db->escape_string("Its My Birthday!"),
				'path' => $db->escape_string("images/icons/itsmybirthday.gif")
			);

		$iid = $db->insert_query("icons", $new_icon);
	
		$cache->update_posticons();
	}
	else
	{
		$iid = $icon['iid'];
	}
	
	// Insert Template elements
	// Remove first to clean up any template edits left from previous installs
	$db->delete_query("templates", "title='itsmybirthday_wishes'");
	$db->delete_query("templates", "title='itsmybirthday_wishes_classic'");
	$db->delete_query("templates", "title='itsmybirthday_wishes_button_add'");
	$db->delete_query("templates", "title='itsmybirthday_wishes_button_del'");
	$db->delete_query("templates", "title='itsmybirthday_wishes_users'");
	
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	find_replace_templatesets("showthread", "#".preg_quote('<script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/itsmybirthday.js?ver='.$info['intver'].'"></script>
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
	find_replace_templatesets("postbit_classic", "#".preg_quote('<tr style="{$post[\'imb_display\']};" id="imb_{$post[\'pid\']}">
	{$post[\'itsmybirthday_wishes_data\']}
</tr>
')."#i", '', 0);
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'button_bday\']}')."#i", '', 0);
	
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
	
	// Now add
	$imb_templates = array(
		'itsmybirthday_wishes'					=> "			<td class=\"trow2 post_buttons {\$unapproved_shade}\">
				{\$post['itsmybirthday_wishes_title']}<br />
				{\$post['itsmybirthday_wishes']}
			</td>",
		'itsmybirthday_wishes_classic'					=> "	<td colspan=\"2\" class=\"trow2 post_buttons {\$unapproved_shade}\">
		{\$post['itsmybirthday_wishes_title']}<br />
		{\$post['itsmybirthday_wishes']}
	</td>",
		'itsmybirthday_wishes_button_add'		=> "<a href=\"happybirthday.php?action=addwish&tid={\$post['tid']}&pid={\$post['pid']}\" onclick=\"return itsmybirthday.addWishes({\$post['pid']}, {\$post['tid']});\" title=\"{\$lang->add_bday_wishes}\" id=\"imb_a{\$post['pid']}\"><img src=\"{\$imgdir}/postbit_bday_add.gif\" border=\"0\" alt=\"{\$lang->add_bday_wishes}\" id=\"imb_i{\$post['pid']}\" /></a>",
		'itsmybirthday_wishes_button_del'		=> "<a href=\"happybirthday.php?action=delwish&tid={\$post['tid']}&pid={\$post['pid']}\" onclick=\"return itsmybirthday.delWishes({\$post['pid']}, {\$post['tid']});\" title=\"{\$lang->del_bday_wishes}\" id=\"imb_a{\$post['pid']}\"><img src=\"{\$imgdir}/postbit_bday_del.gif\" border=\"0\" alt=\"{\$lang->del_bday_wishes}\" id=\"imb_i{\$post['pid']}\" /></a>",
		'itsmybirthday_wishes_users'			=> "<span class=\"smalltext\">{\$comma}</span><a href=\"{\$profile_link}\" class=\"smalltext\">{\$wish['username']}</a>"
					);
	
	foreach($imb_templates as $template_title => $template_data)
	{
		$insert_templates = array(
			'title' => $db->escape_string($template_title),
			'template' => $db->escape_string($template_data),
			'sid' => "-1",
			'version' => "100",
			'dateline' => TIME_NOW
			);
		$db->insert_query('templates', $insert_templates);
	}
	
	find_replace_templatesets("showthread", "#".preg_quote('</head>')."#i", '<script type="text/javascript" src="{\$mybb->settings[\'bburl\']}/jscripts/itsmybirthday.js?ver='.$info['intver'].'"></script>
<script type="text/javascript">
<!--
	var imb_wishesEnabled = "{\$mybb->settings[\'g33k_itsmybirthday_wishes_enabled\']}";
	var imb_wishesRemovable = "{\$mybb->settings[\'g33k_itsmybirthday_wishes_removable\']}";
-->
</script>
</head>');
	find_replace_templatesets("postbit", "#".preg_quote('</tbody>')."#i", '	<tr style="{$post[\'imb_display\']};" id="imb_{$post[\'pid\']}">
			{$post[\'itsmybirthday_wishes_data\']}
		</tr>
	</tbody>');
	find_replace_templatesets("postbit", "#".preg_quote('{$post[\'button_edit\']}')."#i", '{$post[\'button_bday\']}{$post[\'button_edit\']}');
	find_replace_templatesets("postbit_classic", "#".preg_quote('
</tr>
</table>')."#si", '
</tr>
<tr style="{$post[\'imb_display\']};" id="imb_{$post[\'pid\']}">
	{$post[\'itsmybirthday_wishes_data\']}
</tr>
</table>');
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'button_edit\']}')."#i", '{$post[\'button_bday\']}{$post[\'button_edit\']}');

	// Add Settings			
	$query = $db->query("SELECT disporder FROM ".TABLE_PREFIX."settinggroups ORDER BY `disporder` DESC LIMIT 1");
	$disporder = $db->fetch_field($query, 'disporder')+1;

	$setting_group = array(
		'name' 			=>	$prefix.'settings',
		'title' 		=>	'Its My Birthday!',
		'description' 	=>	'Settings to customize the "Its My Birthday!" Plugin',
		'disporder' 	=>	intval($disporder),
		'isdefault' 	=>	'no'
	);
	$db->insert_query('settinggroups', $setting_group);
	$gid = $db->insert_id();
	
	$script = "
<script type=\"text/javascript\">
function insertText(value, textarea)
{
	// Internet Explorer
	if(document.selection)
	{
		textarea.focus();
		var selection = document.selection.createRange();
		selection.text = value;
	}
	// Firefox
	else if(textarea.selectionStart || textarea.selectionStart == '0')
	{
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		textarea.value = textarea.value.substring(0, start)	+ value	+ textarea.value.substring(end, textarea.value.length);
	}
	else
	{
		textarea.value += value;
	}
}		
</script>";
		
	$replacement = array(
		"{username}" => "Username",
		"{age}" => "Age",
		"{age_nth}" => "Age Nth(eg 21st, 22nd)",
		"{bbname}" => "Board Name",
		"{bburl}" => "Board URL",
		"[noage][/noage]" => "noage",
		"[thread][/thread]" => "thread",
		"{thread_url}" => "Thread URL",
		"[post][/post]" => "post",
		"[list][/list]" => "List",
		"{post_url}" => "Post URL",
		"{bdaynum}" => "# of Birthday Users",
		"{randomquote}" => "Random Quote"
	);
	
	$sbj_personalisation = "<script type=\"text/javascript\">\n<!--\ndocument.write('Personalize: ";
	$msg_personalisation = "<script type=\"text/javascript\">\n<!--\ndocument.write('Personalize: ";
	$tpsbj_personalisation = "<script type=\"text/javascript\">\n<!--\ndocument.write('Personalize: ";
	$tptxt_personalisation = "<script type=\"text/javascript\">\n<!--\ndocument.write('Personalize: ";
	$single_sbj_personalisation = "<script type=\"text/javascript\">\n<!--\ndocument.write('Personalize: ";
	$single_msg_personalisation = "<script type=\"text/javascript\">\n<!--\ndocument.write('Personalize: ";
	$rq_personalisation = "<script type=\"text/javascript\">\n<!--\ndocument.write('Personalize: ";
	foreach($replacement as $value => $name)
	{
		$msg_personalisation .= " [<a href=\"#\" onclick=\"insertText(\'{$value}\', \$(\'setting_".$prefix."message\')); return false;\">{$name}</a>], ";
		if($value == '{username}' || $value == '{age}' || $value == '{age_nth}' || $value == '{bbname}' || $value == '{bburl}' || $value == '[noage][/noage]')
		{
			$sbj_personalisation .= " [<a href=\"#\" onclick=\"insertText(\'{$value}\', \$(\'setting_".$prefix."subject\')); return false;\">{$name}</a>], ";
			$tpsbj_personalisation .= " [<a href=\"#\" onclick=\"insertText(\'{$value}\', \$(\'setting_".$prefix."thread_post_subject\')); return false;\">{$name}</a>], ";
		}
		if($value == '{username}' || $value == '{age}' || $value == '{age_nth}' || $value == '{bbname}' || $value == '{bburl}')
		{
			$rq_personalisation .= " [<a href=\"#\" onclick=\"insertText(\'{$value}\', \$(\'setting_".$prefix."random_quotes\')); return false;\">{$name}</a>], ";
		}
		if($value == '{username}' || $value == '{age}' || $value == '{age_nth}' || $value == '{bbname}' || $value == '{bburl}' || $value == '{randomquote}' || $value == '[noage][/noage]')
		{
			$tptxt_personalisation .= " [<a href=\"#\" onclick=\"insertText(\'{$value}\', \$(\'setting_".$prefix."thread_post_text\')); return false;\">{$name}</a>], ";
		}
		if($value == '{bdaynum}')
		{
			$single_sbj_personalisation .= " [<a href=\"#\" onclick=\"insertText(\'{$value}\', \$(\'setting_".$prefix."thread_post_single_subject\')); return false;\">{$name}</a>], ";
		}
		if($value == '{username}' || $value == '{age}' || $value == '{age_nth}' || $value == '{bbname}' || $value == '{bburl}' || $value == '{randomquote}' || $value == '[noage][/noage]' || $value == '[list][/list]')
		{
			$single_msg_personalisation .= " [<a href=\"#\" onclick=\"insertText(\'{$value}\', \$(\'setting_".$prefix."thread_post_single_text\')); return false;\">{$name}</a>], ";
		}
	}
	$sbj_personalisation = substr($sbj_personalisation, 0, -2)."');\n</script>\n";
	$msg_personalisation = substr($msg_personalisation, 0, -2)."');\n</script>\n";
	$tpsbj_personalisation = substr($tpsbj_personalisation, 0, -2)."');\n</script>\n";
	$tptxt_personalisation = substr($tptxt_personalisation, 0, -2)."');\n</script>\n";
	$single_sbj_personalisation = substr($single_sbj_personalisation, 0, -2)."');\n</script>\n";
	$single_msg_personalisation = substr($single_msg_personalisation, 0, -2)."');\n</script>\n";
	$rq_personalisation = substr($rq_personalisation, 0, -2)."');\n</script>\n";
	
	$settings = array(
		'enabled' 					=> array(
				'title' 			=> 'Its My Birthday! On/Off', 
				'description' 		=> 'Turn on or off the Its My Birthday! plugin to send mails and create threads and posts on users\' birthdays.'.$script,
				'optionscode'		=> 'onoff',
				'value'				=> '0'),
		'method' 					=> array(
				'title'				=> 'Mail, PM or Both',
				'description'		=> 'Choose the medium to use to send the Birthday Wishes.',
				'optionscode'		=> 'radio
mail=Sent via Email
pm=Sent as Private Message
both=Sent as both, PM and Mail',
				'value'				=> 'mail'),
		'pm_sender' 				=> array(
				'title'				=> 'PM Sender User ID',
				'description'		=> 'Enter the User ID of the user to send the PM from. Only used if sending as a PM',
				'optionscode'		=> 'text',
				'value'				=> '1'),
		'usergroups_ignored'		=> array(
				'title'				=> 'User Groups to Ignore',
				'description'		=> 'Enter the Usergroup IDs of the groups you do not want to send the Birthday Wishes to. Separate multiple items with a comma(,).',
				'optionscode'		=> 'text',
				'value'				=> '1,5,7'),
		'subject' 					=> array(
				'title'				=> 'Mail/PM Subject',
				'description'		=> 'Enter the Subject of the Mail/PM to be sent.<br /><br /><i>Usage:</i><br />- <strong>[noage]...[/noage]</strong> to substitute for the subject if in case the age can not be determined, either because the user doesn\'t have the year of their birthday saved or they have set their privacy to not show their age.<br /><br />No MyCode, No HTML. MAXIMUM 80 Characters allowed.<br /><br />'.$sbj_personalisation,
				'optionscode'		=> 'textarea',
				'value'				=> 'Happy Birthday {username} on your {age_nth} Birthday! [noage]Happy Birthday {username}![/noage]'),
		'message' 					=> array(
				'title'				=> 'Mail/PM Content',
				'description'		=> 'Enter the Content of the Mail/PM to be sent.<br /><br /><i>Usage:</i><br />- <strong>[noage]...[/noage]</strong> to substitute for the message if in case the age can not be determined, either because the user doesn\'t have the year of their birthday saved or they have set their privacy to not show their age.<br />- <strong>[thread]...[/thread]</strong> and <strong>{thread_url}</strong> for the message to be shown if a thread is created.<br />- <strong>[post]...[/post]</strong> and <strong>{post_url}</strong> for the message to be shown if a post is added.<br /><br />MyCode accepted only for PMs, no MyCode in Email, No HTML.<br /><br />'.$msg_personalisation,
				'optionscode'		=> 'textarea',
				'value'				=> 'Its been {age} years!!! Happy Birthday {username} on your {age_nth} birthday today! Enjoy! ;)[noage]So, we hear its your birthday today! Happy Birthday {username} on your birthday! :D[/noage]

[thread]To celebrate your birthday, we have opened a thread for you on our forums. You can visit the thread by clicking on the link below.
{thread_url}[/thread][post]To celebrate your birthday, we have added a birthday post in a thread on the forums. You can view the post upon visiting the thread by clicking on the link below.
{post_url}[/post]

{randomquote}

~ {bbname} Staff ~
{bburl}'),
		'thread_post_create' 		=> array(
				'title'				=> 'Open Thread or add a Post?',
				'description'		=> 'Do you also want a thread to be opened or Post added to a thread to wish the user?<br />Note: If the user\'s privacy is set not to display the date of birth, the thread or post will not be created.',
				'optionscode'		=> 'select
none=No Thread, No Post
thread=Open a Thread
post=Add Post to a Thread',
				'value'				=> 'thread'),
		'thread_post_uid' 			=> array(
				'title'				=> 'Thread/Post Starting User',
				'description'		=> 'Enter the User ID of the user you want to be the Birthday Thread Opener or the Poster. Used only if opening of thread or adding a post is enabled.',
				'optionscode'		=> 'text',
				'value'				=> '1'),
		'thread_post_id' 			=> array(
				'title'				=> 'Forum ID/Thread ID',
				'description'		=> 'Forum ID of the Forum in which the thread should be opened. If Adding a Post is selected above then this will be the Thread ID where the post will be added. Used only if opening of thread or adding a post is enabled.<br />For example if the link to the Forum is http://example.com/forumdisplay.php?fid=3, the Forum ID will 3',
				'optionscode'		=> 'text',
				'value'				=> '1'),
		'thread_post_iid' 			=> array(
				'title'				=> 'Thread/Post Icon',
				'description'		=> 'Post Icon ID of the post icon that will be used upon thread/post creation. This field should fill automatically, make sure you uploaded the itsmybirthday.gif to your images/icons/ folder.',
				'optionscode'		=> 'text',
				'value'				=> $iid),
		'thread_post_subject' 		=> array(
				'title'				=> 'Thread/Post Subject',
				'description'		=> 'Enter the Subject of the Thread. Used only if opening of thread is enabled.<br /><br /><i>Usage:</i><br />- <strong>[noage]...[/noage]</strong> to substitute for the subject if in case the age can not be determined, either because the user doesn\'t have the year of their birthday saved or they have set their privacy to not show their age.<br /><br />No MyCode, No HTML. MAXIMUM 80 Characters allowed.<br /><br />'.$tpsbj_personalisation,
				'optionscode'		=> 'textarea',
				'value'				=> 'Happy Birthday {username} on your {age_nth} Birthday! [noage]Happy Birthday {username}![/noage]'),
		'thread_post_text' 			=> array(
				'title'				=> 'Thread/Post Contents',
				'description'		=> 'Enter the Content of the Thread. Used only if opening of thread is enabled.<br /><br /><i>Usage:</i><br />- <strong>[noage]...[/noage]</strong> to substitute for the message if in case the age can not be determined, either because the user doesn\'t have the year of their birthday saved or they have set their privacy to not show their age.<br />MyCode accepted, No HTML.<br /><br />'.$tptxt_personalisation,
				'optionscode'		=> 'textarea',
				'value'				=> 'Its been {age} years!!! Happy Birthday {username} on your {age_nth} birthday today! Enjoy! ;)[noage]So, we hear its your birthday today! Happy Birthday {username} on your birthday! :D[/noage]

[i]{randomquote}[/i]

~ {bbname} Staff ~
{bburl}'),
		'thread_post_single' 		=> array(
				'title'				=> 'Multiple or Single Thread/Post',
				'description'		=> 'If there are more than one user with Birthdays on any day, would you like a single thread/post to be opened for all the birthdays or multiple threads/posts, one for each individual Birthday.<br />Emails will still be sent individually.',
				'optionscode'		=> 'radio
multiple=Open Multiple Threads/Posts, one for each individual Birthday
single=Open a Single Thread/Post wishing all the users collectively',
				'value'				=> 'multiple'),
		'thread_post_single_subject' => array(
				'title'				=> 'Single Thread/Post Subject',
				'description'		=> 'Enter the Subject of the single thread/post that will be opened.<br />This setting is only used if there is more than one birthday and a single thread/post option is selected above.<br /><br />'.$single_sbj_personalisation,
				'optionscode'		=> 'textarea',
				'value'				=> 'Happy Birthday to these {bdaynum} users!'),
		'thread_post_single_text'	=> array(
				'title'				=> 'Single Thread/Post Contents',
				'description'		=> 'Enter the content of the single thread/post that will be opened.<br />This setting is only used if there is more than one birthday and a single thread/post option is selected above.<br /><br /><i>Usage:</i><br />- <strong>[noage]...[/noage]</strong> to substitute for the message if in case the age can not be determined, either because the user doesn\'t have the year of their birthday saved or they have set their privacy to not show their age.<br />- Content in <strong>[list]...[/list]</strong> will be looped through for each birthday user. Username, age, age_nth tags should be used only within list elements.<br />MyCode accepted, No HTML.<br /><br />'.$single_msg_personalisation,
				'optionscode'		=> 'textarea',
				'value'				=> 'Its a special day for the following users today. Happy Birthday to them all!
				
[list]Happy {age_nth} Birthday to {username}![noage]Happy Birthday {username}![/noage]

[/list]

[i]{randomquote}[/i]

~ {bbname} Staff ~
{bburl}'),
		'random_quotes' 			=> array(
				'title'				=> 'Random Quotes List',
				'description'		=> 'List of Quotes that Its My Birthday! will randomly pick from to include in the message. Separate quotes with a vertical bar ( | ).<br /><br />'.$rq_personalisation,
				'optionscode'		=> 'textarea',
				'value'				=> 'And in the end, it\'s not the years in your life that count. It\'s the life in your years.
|
Your birthday is a special time to celebrate the gift of \'you\' to the world.
|
If there\'s something that you\'re dreaming of then may it all come true, because you deserve it all...HAPPY BIRTHDAY.
|
Hope your Birthday gently breezes into your life all the choicest of things and all that your heart holds dear Have A Fun- Filled Day.
|
A birthday is just the first day of another 365-day journey around the sun.  Enjoy the trip.
|
They say that age is all in your mind.  The trick is keeping it from creeping down into your body.
|
Pleas\'d look forward, pleas\'d to look behind,
And count each birthday with a grateful mind. - Alexander Pope
|
Whatever with the past has gone,
The best is always yet to come. - Lucy Larcom
|
Birthdays are good for you. Statistics show that the people who have the most live the longest. -Reverend Larry Lorenzoni
|
Inside every older person is a younger person – wondering what the hell happened. '),
		'wishes_enabled'			=> array(
				'title' 			=> 'Enable Wishing Happy Birthday', 
				'description' 		=> 'Enable the use of the Happy Birthday button to add wishes to the post without need for replying and unnecessary bumping of thread.<br />This setting is not dependent on the Its My Birthday! plugin On/Off above. You can still use this feature while sending mails and creating threads/posts is disabled.',
				'optionscode'		=> 'yesno',
				'value'				=> '1'),
		'wishes_removable'			=> array(
				'title' 			=> 'Allow users to Remove their wishes', 
				'description' 		=> 'If adding wishes is on, would you like to allow users to remove their wishes once added? Yes will allow them removal, no will deny them removal.',
				'optionscode'		=> 'yesno',
				'value'				=> '1')
	);
	
	$x = 1;
	foreach($settings as $name => $setting)
	{
		$insert_settings = array(
			'name' => $db->escape_string($prefix.$name),
			'title' => $db->escape_string($setting['title']),
			'description' => $db->escape_string($setting['description']),
			'optionscode' => $db->escape_string($setting['optionscode']),
			'value' => $db->escape_string($setting['value']),
			'disporder' => $x,
			'gid' => $gid,
			'isdefault' => 0
			);
		$db->insert_query('settings', $insert_settings);
		$x++;
	}

	rebuild_settings();
}

function itsmybirthday_deactivate()
{
	global $db, $mybb, $cache;
	
	$codename = basename(__FILE__, ".php");
	$prefix = 'g33k_'.$codename.'_';
	
	$info = itsmybirthday_info();
		
	// Remove Templates
	$db->delete_query("templates", "title='itsmybirthday_wishes'");
	$db->delete_query("templates", "title='itsmybirthday_wishes_classic'");
	$db->delete_query("templates", "title='itsmybirthday_wishes_button_add'");
	$db->delete_query("templates", "title='itsmybirthday_wishes_button_del'");
	$db->delete_query("templates", "title='itsmybirthday_wishes_users'");
	
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	find_replace_templatesets("showthread", "#".preg_quote('<script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/itsmybirthday.js?ver='.$info['intver'].'"></script>
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
	find_replace_templatesets("postbit_classic", "#".preg_quote('<tr style="{$post[\'imb_display\']};" id="imb_{$post[\'pid\']}">
	{$post[\'itsmybirthday_wishes_data\']}
</tr>
')."#i", '', 0);
	find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'button_bday\']}')."#i", '', 0);
	
	// Lets also check for any remnants from previous versions and remove them
	
	//v2.1
	find_replace_templatesets("showthread", "#".preg_quote('<script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/itsmybirthday.js?ver='.$info['intver'].'"></script>
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

function itsmybirthday_uninstall()
{
	global $db;
	
	$codename = basename(__FILE__, ".php");
	$prefix = 'g33k_'.$codename.'_';

	if($db->field_exists('next_bday_year', 'users'))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."users DROP column `next_bday_year`");
	}
	
	if($db->table_exists('g33k_itsmybirthday_runtime'))
	{
		$db->drop_table('g33k_itsmybirthday_runtime');
	}
}

function itsmybirthday_templatelist()
{
	global $templatelist;
	
	if (THIS_SCRIPT == 'showthread.php');
	{
		$template_list = "itsmybirthday_wishes_users,itsmybirthday_wishes,itsmybirthday_wishes_classic,itsmybirthday_wishes_button_add,itsmybirthday_wishes_button_del";
		if (isset($templatelist))
		{
			$templatelist .= ",".$template_list;
		}
		else
		{
			$templatelist = $template_list;
		}
	}
}

function itsmybirthday_run()
{
	global $db, $mybb;
	
	$codename = basename(__FILE__, ".php");
	$prefix = 'g33k_'.$codename.'_';
	
	if ($mybb->settings[$prefix.'enabled'] == "1")
	{
		// format the dates
		$offset = $mybb->settings['timezoneoffset'];
		$dstcorrection = $mybb->settings['dstcorrection'];
		if($dstcorrection == 1)
		{
			++$offset;
			if(my_substr($offset, 0, 1) != "-")
			{
				$offset = "+".$offset;
			}
		}
		if($offset == "-")
		{
			$offset = 0;
		}
		$day = gmdate("j", TIME_NOW + ($offset * 3600));
		$month = gmdate("n", TIME_NOW + ($offset * 3600));
		$year = gmdate("Y", TIME_NOW + ($offset * 3600));
		$nextyear = $year+1;
		
		// Check if another instance of the script has already started/run today
		$imb_chk = $db->simple_select($prefix.'runtime', '*', "date = {$year}{$month}{$day}", array('limit' => 1));
		$imb_run = $db->fetch_array($imb_chk);
	
		if(empty($imb_run['date']))
		{
			// No prior instance found, Update db with this instance of the script 
			// This is to try and avoid another instance running and ending up with multiple threads/posts
			$imb_uniqid = md5(random_str(10));
			$imb_runtime = array(
					"date" => $year.$month.$day,
					"hash" => $imb_uniqid
				);
			$db->insert_query($prefix."runtime", $imb_runtime);
		
			$ignored_ug = explode(',', $mybb->settings[$prefix.'usergroups_ignored']);
			$usergroups_ignored = '';
			$split = '';
			foreach ($ignored_ug AS $ignored)
			{
				if (intval($ignored))
				{
					$usergroups_ignored .= $split.$ignored;
					$split = ',';
				}
			}
				
			// grab all users who's birthday is today and next_bday_year <= this year (less than in case of previous year's missed wishes) and excluding usergroups excluded
			$usergroup_query = '';
			if ($usergroups_ignored != '')
			{
				$usergroup_query = " AND usergroup NOT IN ({$usergroups_ignored})";
			}
			$query = $db->simple_select('users', 'uid, username, email, birthday, birthdayprivacy, allownotices, next_bday_year, usergroup', "birthday LIKE '{$day}-{$month}-%' AND next_bday_year<={$year}{$usergroup_query}");
				
			$bdayusers = array();
			$bdaycount = 0;
			while($bdayuser = $db->fetch_array($query))
			{
				$bdayusers[] = $bdayuser;
				$bdaycount++;
			}
			
			// Lets get a quote to use from the random quotes
			$random_quotes = $rq = '';
			$random_quotes = explode('|', $mybb->settings[$prefix.'random_quotes']);
			$rq = array_rand($random_quotes);
			
			if ($bdaycount > 1 && $mybb->settings[$prefix.'thread_post_create'] != 'none' && $mybb->settings[$prefix.'thread_post_single'] == "single")
			{
				$imb_thread_post_subject = "";
				$imb_thread_post_text = "";
				$listcount = 0;
				// We're creating single thread/post for all birthdays
				
				// Get the list elements to be looped 
				$imb_single_list = preg_replace("#(.*?)\[list\](.*?)\[/list\](.*)#si", "$2", $mybb->settings[$prefix.'thread_post_single_text']);
				$imb_list = '';
				// Loop through all the bdayusers and get the unique elements
				foreach($bdayusers AS $bdayboy)
				{
					$age = "";
					$age_nth = "";
					$membday = "";
					$search = "";
					$replace = "";
					$imb_list_el = "";
					$membday = explode("-", $bdayboy['birthday']);
					if($membday[2] && $bdayboy['birthdayprivacy'] != 'none')
					{
						$age = get_imbage($bdayboy['birthday'], $year);
						$age_nth = itsmybirthday_ordinal($age);
					}
						
					$search = array(
						'{username}',
						'{age}',
						'{age_nth}',
						'{bdaynum}'
						);
		
					$replace = array(
						htmlspecialchars_uni($bdayboy['username']),
						htmlspecialchars_uni($age),
						htmlspecialchars_uni($age_nth),
						htmlspecialchars_uni($bdaycount)
					);
					
					// Generate list with valid users sharing their birthdays
					if ($bdayboy['birthdayprivacy'] == 'all')
					{
						$imb_list_el = str_replace($search, $replace, $imb_single_list);
						// Parse noage
						$imb_list_el = itsmybirthday_parse_noage($age, $imb_list_el);
						$imb_list .= $imb_list_el;
						$listcount++;
					}
				}
				unset($bdayboy);
				// Show thread, only if we have something in the list and we're set to open a thread/post
				if ($listcount > 0 && $mybb->settings[$prefix.'thread_post_create'] != 'none')
				{
					$search = "";
					$replace = "";
					$search = array(
							'{bdaynum}',
							'{bbname}',
							'{bburl}'
						);
		
					$replace = array(
							htmlspecialchars_uni($listcount),
							htmlspecialchars_uni($mybb->settings['bbname']),
							htmlspecialchars_uni($mybb->settings['bburl'])
						);
					
					// Setup the thread/post
						
					// Replace list with list elements
					$imb_thread_post_text = preg_replace("#\[list\](.*?)\[/list\]#si", $imb_list, $mybb->settings[$prefix.'thread_post_single_text']);
					$imb_thread_post_subject = str_replace($search, $replace, $mybb->settings[$prefix.'thread_post_single_subject']);
					$imb_thread_post_text = str_replace($search, $replace, $imb_thread_post_text);
						
					// Update the message text with the quote
					$imb_thread_post_text = str_replace('{randomquote}', $random_quotes[$rq], $imb_thread_post_text);
					
					// Last check to make sure another instance of the script hasn't already added the threads/posts
					$imb_chk1 = $db->simple_select($prefix.'runtime', '*', "date = {$year}{$month}{$day} AND hash = '{$imb_uniqid}'", array('limit' => 1));
					$imb_run1 = $db->fetch_array($imb_chk1);
					
					if(!empty($imb_run1['date']))
					{
						// So which is it? Thread or Post?
						if ($mybb->settings[$prefix.'thread_post_create'] == 'thread')
						{
							$thread_url = itsmybirthday_thread($imb_thread_post_subject, $imb_thread_post_text, "-1", "");
							if (!$thread_url)
							{
								unset($thread_url);
							}
						}
						else if ($mybb->settings[$prefix.'thread_post_create'] == 'post')
						{
							$post_url = itsmybirthday_post($imb_thread_post_subject, $imb_thread_post_text, "-1", "");
							if (!$post_url)
							{
								unset($post_url);
							}
						}
					}
				}
			}
			// Last check to make sure another instance of the script hasn't already added the threads/posts
			$imb_chk2 = $db->simple_select($prefix.'runtime', '*', "date = {$year}{$month}{$day} AND hash = '{$imb_uniqid}'", array('limit' => 1));
			$imb_run2 = $db->fetch_array($imb_chk2);
				
			if(!empty($imb_run2['date']))
			{
				foreach($bdayusers AS $bdayboy)
				{
					$age = "";
					$age_nth = "";
					$imb_thread_post_subject = "";
					$imb_thread_post_text = "";
					$imb_subject = "";
					$imb_message = "";
					$membday = "";
					$search = "";
					$replace = "";
					$membday = explode("-", $bdayboy['birthday']);
					if($membday[2] && $bdayboy['birthdayprivacy'] != 'none')
					{
						$age = get_imbage($bdayboy['birthday'], $year);
						$age_nth = itsmybirthday_ordinal($age);
					}
						
					$search = array(
						'{username}',
						'{age}',
						'{age_nth}',
						'{bbname}',
						'{bburl}'
						);
			
					$replace = array(
						htmlspecialchars_uni($bdayboy['username']),
						htmlspecialchars_uni($age),
						htmlspecialchars_uni($age_nth),
						htmlspecialchars_uni($mybb->settings['bbname']),
						htmlspecialchars_uni($mybb->settings['bburl'])
					);
					if ($mybb->settings[$prefix.'thread_post_single'] == "multiple" || $bdaycount == 1)
					{
						// Let get a quote to use from the random quotes
						$random_quotes = $rq = '';
						$random_quotes = explode('|', $mybb->settings[$prefix.'random_quotes']);
						$rq = array_rand($random_quotes);
						
						// Update the message text with the quote
						$imb_thread_post_text = str_replace('{randomquote}', $random_quotes[$rq], $mybb->settings[$prefix.'thread_post_text']);
						
						// Are we creating a thread/post? Only if user is sharing date of birth
						if ($mybb->settings[$prefix.'thread_post_create'] != 'none' && $bdayboy['birthdayprivacy'] == 'all')
						{
							// Setup the message
							$imb_thread_post_subject = str_replace($search, $replace, $mybb->settings[$prefix.'thread_post_subject']);
							$imb_thread_post_text = str_replace($search, $replace, $imb_thread_post_text);
							
							// Parse noage
							$imb_thread_post_subject = itsmybirthday_parse_noage($age, $imb_thread_post_subject);
							$imb_thread_post_text = itsmybirthday_parse_noage($age, $imb_thread_post_text);
						
							// So which is it? Thread or Post?
							if ($mybb->settings[$prefix.'thread_post_create'] == 'thread')
							{
								$thread_url = itsmybirthday_thread($imb_thread_post_subject, $imb_thread_post_text, $bdayboy['uid'], $bdayboy['username']);
								if (!$thread_url)
								{
									unset($thread_url);
								}
							}
							else if ($mybb->settings[$prefix.'thread_post_create'] == 'post')
							{
								$post_url = itsmybirthday_post($imb_thread_post_subject, $imb_thread_post_text, $bdayboy['uid'], $bdayboy['username']);
								if (!$post_url)
								{
									unset($post_url);
								}
							}
						}
					}
					
					// Update the message text with the quote
					$imb_message = str_replace('{randomquote}', $random_quotes[$rq], $mybb->settings[$prefix.'message']);
					
					// Format the message
					$imb_subject = str_replace($search, $replace, $mybb->settings[$prefix.'subject']);
					$imb_message = str_replace($search, $replace, $imb_message);
					
					// Parse noage
					$imb_subject = itsmybirthday_parse_noage($age, $imb_subject);
					$imb_message = itsmybirthday_parse_noage($age, $imb_message);
					
					// Parse thread, thread_url, post, post url, remove the one not needed
					if ($mybb->settings[$prefix.'thread_post_create'] == 'thread' && $bdayboy['birthdayprivacy'] == 'all' && isset($thread_url))
					{
						$imb_message = itsmybirthday_parse_message($imb_message, $thread_url, "thread");
					}
					else if ($mybb->settings[$prefix.'thread_post_create'] == 'post' && $bdayboy['birthdayprivacy'] == 'all' && isset($post_url))
					{
						$imb_message = itsmybirthday_parse_message($imb_message, $post_url, "post");
					}
					else
					{
						$imb_message = itsmybirthday_parse_message($imb_message);			
					}
				
					// Lets send the message
					itsmybirthday_send_message($bdayboy, $imb_subject, $imb_message);
						
					// Update user as done, wished for this year, till next year
					$db->query("UPDATE ".TABLE_PREFIX."users SET next_bday_year='{$nextyear}' WHERE uid=".$bdayboy['uid']."");
					if ($mybb->settings[$prefix.'thread_post_single'] == "multiple")
					{
						unset($thread_url);
						unset($post_url);
					}
				}
			}
			unset($bdayboy);
			unset($thread_url);
			unset($post_url);
			
			// Mark this instance of the script done and invalidate any other instances that might have started up by removing them from the db
			$imb_runupdate = array("done" => 1
					);
			$db->update_query($prefix.'runtime', $imb_runupdate, "date = {$year}{$month}{$day} AND hash = '{$imb_uniqid}'", "1");
			$db->delete_query($prefix.'runtime', "date != {$year}{$month}{$day} AND hash != '{$imb_uniqid}'");
		}
	}
}

function itsmybirthday_wishes($post)
{
	global $db, $mybb, $theme, $templates, $lang;
	
	$codename = basename(__FILE__, ".php");
	$prefix = 'g33k_'.$codename.'_';
	
	$lang->load("itsmybirthday");
	
	if($mybb->settings[$prefix.'wishes_enabled'] == "1" && $post['itsmybirthday_bdaypostfor_uid'] != "0")
	{
		// Get all the wishes for this post
		$query = $db->simple_select($prefix.'bdaywishes', '*', "pid = '".$post['pid']."'", array('order_by' => 'username', 'order_dir' => 'ASC'));
					
		$wishes = '';
		$comma = '';
		$wished = 0;
		$count = 0;
		while($wish = $db->fetch_array($query))
		{
			$profile_link = get_profile_link($wish['uid']);
			eval("\$itsmybirthday_wishes_users = \"".$templates->get("itsmybirthday_wishes_users", 1, 0)."\";");
			$wishes .= trim($itsmybirthday_wishes_users);
			$comma = ', ';	
			// Has this user wished?
			if($wish['uid'] == $mybb->user['uid'])
			{
				$wished = 1;
			}	
			$count++;			
		}
		
		$thread = get_thread($post['tid']);
		if(($wished && $mybb->settings[$prefix.'wishes_removable'] != "1") || (!is_moderator($post['fid'], "caneditposts") && $thread['closed'] == 1) || $post['uid'] == $mybb->user['uid'] || $post['itsmybirthday_bdaypostfor_uid'] == $mybb->user['uid'])
		{
			// Show no button for poster or bdayboy or disabled wishes_removable
			$post['button_bday'] = '';
		}
		else if($wished && $mybb->settings[$prefix.'wishes_removable'] == "1")
		{
			// Fallback to the english button if the theme image lang button is not there
			$imgdir = is_file($theme['imglangdir'].'/postbit_bday_del.gif') ? $theme['imglangdir'] : "images/english";
			eval("\$post['button_bday'] = \"".$templates->get("itsmybirthday_wishes_button_del")."\";");
		}
		else
		{
			// Fallback to the english button if the theme image lang button is not there
			$imgdir = is_file($theme['imglangdir'].'/postbit_bday_add.gif') ? $theme['imglangdir'] : "images/english";
			eval("\$post['button_bday'] = \"".$templates->get("itsmybirthday_wishes_button_add")."\";");
		}	
		
		if($count>0)
		{
			if ($post['itsmybirthday_bdaypostfor_username'] != '')
			{
				$post['itsmybirthday_wishes_title'] = $lang->sprintf($lang->itsmybirthday_wishes_title, $post['itsmybirthday_bdaypostfor_username']);
			}
			else
			{
				$post['itsmybirthday_wishes_title'] = $lang->itsmybirthday_wishes_title_nouser;
			}
			$post['itsmybirthday_wishes'] = $wishes;
			$post['imb_display'] = "";
			if($mybb->settings['postlayout'] == "classic")
			{
				eval("\$itsmybirthday_wishes = \"".$templates->get("itsmybirthday_wishes_classic")."\";");
			}
			else
			{
				eval("\$itsmybirthday_wishes = \"".$templates->get("itsmybirthday_wishes")."\";");
			}
			$post['itsmybirthday_wishes_data'] = $itsmybirthday_wishes;
		}
		else
		{
			$post['itsmybirthday_wishes_title'] = "";
			$post['itsmybirthday_wishes'] = "";
			$post['imb_display'] = "display: none";
			if($mybb->settings['postlayout'] == "classic")
			{
				eval("\$itsmybirthday_wishes = \"".$templates->get("itsmybirthday_wishes_classic")."\";");
			}
			else
			{
				eval("\$itsmybirthday_wishes = \"".$templates->get("itsmybirthday_wishes")."\";");
			}
			$post['itsmybirthday_wishes_data'] = $itsmybirthday_wishes;
		}
	}
	return $post;
}

function get_imbage($birthday, $thisyear)
{
	$bday = explode("-", $birthday);
	if(!$bday[2])
	{
		return;
	}

	$age = $thisyear-$bday[2];

	return $age;
}

function itsmybirthday_ordinal($num)
{
    // Special case "teenth"
    if ( ($num / 10) % 10 != 1 )
    {
        // Handle 1st, 2nd, 3rd
        switch( $num % 10 )
        {
            case 1: return $num . 'st';
            case 2: return $num . 'nd';
            case 3: return $num . 'rd'; 
        }
    }
    // Everything else is "nth"
    return $num . 'th';
}

function itsmybirthday_parse_noage($age, $text)
{
	// Parse noage to use if age can not be determined or is hidden for privacy
	if ($age == '')
	{
		$text = preg_replace("#(.*?)\[noage\](.*?)\[/noage\]#si", "$2", $text);
	}
	else
	{
		$text = preg_replace("#\[noage\](.*?)\[/noage\]#si", "", $text);
	}
	
	return $text;
}

function itsmybirthday_parse_message($msg, $url='', $tp='')
{
	global $mybb;
	
	$codename = basename(__FILE__, ".php");
	$prefix = 'g33k_'.$codename.'_';
	
	if ($tp == 'thread')
	{
		$msg = str_replace('{thread_url}', $mybb->settings['bburl']."/".$url, $msg);
		$msg = str_replace('{post_url}', "", $msg);
		$msg = preg_replace("#\[thread\](.*?)\[/thread\]?#si", "$1", $msg);
		$msg = preg_replace("#\[post\](.*?)\[/post\]#si", "", $msg);
	}
	else if ($tp == 'post')
	{
		$msg = str_replace('{post_url}', $mybb->settings['bburl']."/".$url, $msg);
		$msg = str_replace('{thread_url}', "", $msg);
		$msg = preg_replace("#\[post\](.*?)\[/post\]?#si", "$1", $msg);
		$msg = preg_replace("#\[thread\](.*?)\[/thread\]#si", "", $msg);
	}
	else
	{
		$msg = str_replace('{post_url}', "", $msg);
		$msg = str_replace('{thread_url}', "", $msg);
		$msg = preg_replace("#\[thread\](.*?)\[/thread\]#si", "", $msg);
		$msg = preg_replace("#\[post\](.*?)\[/post\]#si", "", $msg);	
	}
	
	return $msg;	
}

function itsmybirthday_thread($subject, $text, $bdayuid, $bdayusername)
{
	global $db, $mybb;
	
	$codename = basename(__FILE__, ".php");
	$prefix = 'g33k_'.$codename.'_';
	
	// Thread
	// Set up posthandler.
	mt_srand((double) microtime() * 1000000);
	$posthash = md5(intval($mybb->settings[$prefix.'thread_post_uid']).mt_rand());
	require_once MYBB_ROOT."inc/datahandlers/post.php";
	$posthandler = new PostDataHandler("insert");
	$posthandler->action = "thread";
					
	// Set the thread data 
	$new_thread = array(
		"fid" => intval($mybb->settings[$prefix.'thread_post_id']),
		"subject" => $subject,
		"icon" => intval($mybb->settings[$prefix.'thread_post_iid']),
		"uid" => intval($mybb->settings[$prefix.'thread_post_uid']),
		"username" => '',
		"message" => $text,
		"posthash" => $posthash
		);
			
	$new_thread['savedraft'] = 0;
	$new_thread['options'] = array(
		"signature" => "0",
		"subscriptionmethod" => "",
		"disablesmilies" => "0"
	);
				
	$posthandler->admin_override = 1;
	$posthandler->set_data($new_thread);
	$valid_thread = $posthandler->validate_thread();
	$post_errors = array();
	if(!$valid_thread)
	{
		$post_errors = $posthandler->get_friendly_errors();
	}
	if(count($post_errors) > 0)
	{
		// There was an error in the creating the thread
		$thread_url = false;		
	}
	else
	{
		$thread_info = $posthandler->insert_thread();
		$tid = $thread_info['tid'];
		$pid = $thread_info['pid'];
		// Also set this post to be recognized as a birthday post
		$postupdate = array("itsmybirthday_bdaypostfor_uid" => $bdayuid,
							"itsmybirthday_bdaypostfor_username" => $db->escape_string($bdayusername)
					);
		$db->update_query('posts', $postupdate, "pid='".$pid."'", "1");
		
		$thread_url = get_thread_link($tid);		
	}
	unset($posthandler);
	unset($new_thread);
	unset($postupdate);
	
	return $thread_url;
}

function itsmybirthday_post($subject, $text, $bdayuid, $bdayusername)
{
	global $db, $mybb;
	
	$codename = basename(__FILE__, ".php");
	$prefix = 'g33k_'.$codename.'_';
	
	// Post
	$query = $db->simple_select("threads", "*", "tid='".$mybb->settings[$prefix.'thread_post_id']."'", array('limit' => 1));
	$thread = $db->fetch_array($query);
					
	// Manually disable post merge if it is enabled, we'll set it back to its original value after inserting the post
	$temp_postmergemins = $mybb->settings['postmergemins'];
	$mybb->settings['postmergemins'] = '0';

	// Set up posthandler.
	mt_srand((double) microtime() * 1000000);
	$posthash = md5(intval($mybb->settings[$prefix.'thread_post_uid']).mt_rand());
	require_once MYBB_ROOT."inc/datahandlers/post.php";
	$posthandler = new PostDataHandler("insert");

	$post = array(
		"tid" => intval($mybb->settings[$prefix.'thread_post_id']),
		"replyto" => '',
		"fid" => $thread['fid'],
		"subject" => $subject,
		"icon" => intval($mybb->settings[$prefix.'thread_post_iid']),
		"uid" => intval($mybb->settings[$prefix.'thread_post_uid']),
		"username" => '',
		"message" => $text,
		"posthash" => $posthash
	);

	$post['savedraft'] = 0;
	$post['options'] = array(
		"signature" => "0",
		"subscriptionmethod" => "",
		"disablesmilies" => "0"
	);

	$posthandler->admin_override = 1;
	$posthandler->set_data($post);

	$valid_post = $posthandler->validate_post();

	$post_errors = array();
	if(!$valid_post)
	{
		$post_errors = $posthandler->get_friendly_errors();
	}
	if(count($post_errors) > 0)
	{
		// Error Adding Post
		$post_url = false;
	}
	else
	{
		$postinfo = $posthandler->insert_post();
		$pid = $postinfo['pid'];
		// Also set this post to be recognized as a birthday post
		$postupdate = array("itsmybirthday_bdaypostfor_uid" => $bdayuid,
							"itsmybirthday_bdaypostfor_username" => $db->escape_string($bdayusername)
					);
		$db->update_query('posts', $postupdate, "pid='".$pid."'", "1");
		
		$post_url = get_post_link($pid, $mybb->settings[$prefix.'thread_post_id'])."#pid{$pid}";	
	}
	// Reset Post merge 
	$mybb->settings['postmergemins'] = $temp_postmergemins;
	$temp_postmergemins = '';					
	unset($posthandler);
	unset($post);
	unset($postupdate);
	
	return $post_url;
}

function itsmybirthday_send_message($to, $subject, $message)
{
	global $db, $mybb, $cache;
	
	$codename = basename(__FILE__, ".php");
	$prefix = 'g33k_'.$codename.'_';
	
	if ($mybb->settings[$prefix.'method'] == 'pm' || $mybb->settings[$prefix.'method'] == 'both')
	{
		require_once MYBB_ROOT."inc/datahandlers/pm.php";
		$pmhandler = new PMDataHandler();

		$pm = array(
			"subject" => $subject,
			"message" => $message,
			"icon" => $mybb->settings[$prefix.'thread_post_iid'],
			"toid" => array($to['uid']),
			"fromid" => $mybb->settings[$prefix.'pm_sender'],
			"do" => '',
			"pmid" => ''
		);
		$pm['options'] = array(
			"signature" => "0",
			"disablesmilies" => "0",
			"savecopy" => "0",
			"readreceipt" => "0"
		);
				
		$pmhandler->set_data($pm);

		if(!$pmhandler->validate_pm())
		{
			// There some problem sending the PM
		}
		else
		{
			$pminfo = $pmhandler->insert_pm();
		}
		unset($pm);
		unset($pmhandler);
	}
	if (($mybb->settings[$prefix.'method'] == 'mail' || $mybb->settings[$prefix.'method'] == 'both') && $to['email'] != '' && $to['allownotices'] == 1)
	{
		// Lets use mailqueue instead of direct mailing for faster page loads
		$new_email = array(
				"mailto" => $db->escape_string($to['email']),
				"mailfrom" => '',
				"subject" => $db->escape_string($subject),
				"message" => $db->escape_string($message),
				"headers" => ''
			);
		$db->insert_query("mailqueue", $new_email);
		$cache->update_mailqueue();
		unset($new_email);
	}
}

function itsmybirthday_settings_page()
{
	global $db, $mybb, $g33k_settings_peeker;
	
	$codename = basename(__FILE__, ".php");
	$prefix = 'g33k_'.$codename.'_';
	
	$query = $db->simple_select("settinggroups", "gid", "name='{$prefix}settings'", array('limit' => 1));
	$group = $db->fetch_array($query);
	$g33k_settings_peeker = ($mybb->input["gid"] == $group["gid"]) && ($mybb->request_method != "post");
}

function itsmybirthday_settings_peeker()
{
	global $g33k_settings_peeker;
	
	$codename = basename(__FILE__, ".php");
	$prefix = 'g33k_'.$codename.'_';
	
	if($g33k_settings_peeker)
		echo '<script type="text/javascript">
	Event.observe(window,"load",function(){
		load'.$prefix.'Peekers();
	});
	function load'.$prefix.'Peekers(){
		new Peeker($$(".setting_'.$prefix.'enabled"), $("row_setting_'.$prefix.'method"), /1/, true);
		new Peeker($$(".setting_'.$prefix.'enabled"), $("row_setting_'.$prefix.'pm_sender"), /1/, true);
		new Peeker($$(".setting_'.$prefix.'enabled"), $("row_setting_'.$prefix.'usergroups_ignored"), /1/, true);
		new Peeker($$(".setting_'.$prefix.'enabled"), $("row_setting_'.$prefix.'subject"), /1/, true);
		new Peeker($$(".setting_'.$prefix.'enabled"), $("row_setting_'.$prefix.'message"), /1/, true);
		new Peeker($$(".setting_'.$prefix.'enabled"), $("row_setting_'.$prefix.'thread_post_create"), /1/, true);
		new Peeker($$(".setting_'.$prefix.'enabled"), $("row_setting_'.$prefix.'thread_post_uid"), /1/, true);
		new Peeker($$(".setting_'.$prefix.'enabled"), $("row_setting_'.$prefix.'thread_post_id"), /1/, true);
		new Peeker($$(".setting_'.$prefix.'enabled"), $("row_setting_'.$prefix.'thread_post_iid"), /1/, true);
		new Peeker($$(".setting_'.$prefix.'enabled"), $("row_setting_'.$prefix.'thread_post_subject"), /1/, true);
		new Peeker($$(".setting_'.$prefix.'enabled"), $("row_setting_'.$prefix.'thread_post_text"), /1/, true);
		new Peeker($$(".setting_'.$prefix.'enabled"), $("row_setting_'.$prefix.'thread_post_single"), /1/, true);
		new Peeker($$(".setting_'.$prefix.'enabled"), $("row_setting_'.$prefix.'thread_post_single_subject"), /1/, true);
		new Peeker($$(".setting_'.$prefix.'enabled"), $("row_setting_'.$prefix.'thread_post_single_text"), /1/, true);
		new Peeker($$(".setting_'.$prefix.'enabled"), $("row_setting_'.$prefix.'random_quotes"), /1/, true);
		new Peeker($$(".setting_'.$prefix.'wishes_enabled"), $("row_setting_'.$prefix.'wishes_removable"), /1/, true);
	}
</script>';
}
?>