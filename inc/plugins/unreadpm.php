<?php
/**
 * Board Message Plugin for MyBB
 * Copyright © 2006-2007 MyBB Mods
 *
 * By: destroyer (originally written by Shochu)
 * Special thanks to MusicalMidget as the mod was based off his boardmsg mod.
 * Website: http://www.chat2b.be
 */
if(!defined("IN_MYBB"))
{
    die("This file cannot be accessed directly.");
}

$plugins->add_hook('global_start', 'unreadpm');

function unreadpm_info()
{
    return array(
        'name'            => 'Unread PM Notification',
        'description'    => 'Informs users with a banner at the top when they have any unread messages.',
        'title' =>           'Unread PM Notification',
        'website'        => 'http://www.chat2b.be',
        'author'        => 'destroyer',
        'authorsite'    => 'http://www.chat2b.be',
        'version'        => '1.0.4',
        'guid'        => 'de5851b4dc91ee79c07aa3212df62684',
        'compatibility' => '16*',

    );
}

function unreadpm_activate()
{
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
    global $db;
    
    $unreadpm_group = array(
        "name"            => "Unread PM Notification Settings",
        "title"         => "Unread PM Notification Settings",
        "description"    => "Settings for the Unread PM Notification.",
        "disporder"        => "3",
        "isdefault"        => "no",
    );
    
    $db->insert_query("settinggroups", $unreadpm_group);
    $gid = $db->insert_id();
    
    $unreadpm_setting_1 = array(
        "name"            => "showunreadpm",
        "title"            => "Enable Unread PM Notification Mod",
        "description"    => "Enable the Unread PM Notification message?",
        "optionscode"    => "onoff",
        "value"            => "on",
        "disporder"        => "1",
        "gid"            => intval($gid),
    );
    
    $unreadpm_setting_2 = array(
        "name"            => "unreadpmmsg",
        "title"            => "Unread PM Notification Message",
        "description"    => "Enter the message you would like to be displayed in the forum header when a user has an unread PM.",
        "optionscode"    => "textarea",
        "value"            => "<center><b>You have unread private messages!  Click <a href=\"private.php\">here</a> to read them</b></center>",
        "disporder"        => "2",
        "gid"            => intval($gid),
    );
    
    $unreadpm_setting_3 = array(
        "name"            => "bgcolor",
        "title"            => "Notification Background Colour",
        "description"    => "This is the background colour in hexadecimal form of the notification table",
        "optionscode"    => "text",
        "value"            => "#efefef",
        "disporder"        => "3",
        "gid"            => intval($gid),
    );
    
    $unreadpm_setting_4 = array(
        "name"            => "bordercolor",
        "title"            => "Notification Border Colour",
        "description"    => "This is the border colour in hexadecimal form of the notification table",
        "optionscode"    => "text",
        "value"            => "#4874a3",
        "disporder"        => "4",
        "gid"            => intval($gid),
    );
    
    $db->insert_query("settings", $unreadpm_setting_1);
    $db->insert_query("settings", $unreadpm_setting_2);
    $db->insert_query("settings", $unreadpm_setting_3);
    $db->insert_query("settings", $unreadpm_setting_4);
    
    $unreadpm_template = array(
        "title"        => "global_unreadpm",
        "template"    => "<table border=\"0\" cellspacing=\"1\" cellpadding=\"4\" class=\"tborder\" style=\"background:\$temp_bordercolor;\">
<tbody>
<tr>
<td class=\"trow1\" style=\"background:\$temp_bgcolor;\">\$temp_unreadpmmsg</td>
</tr>
</tbody>
</table>
<br />",
        "sid"        => "-1",
    );
    
    $db->insert_query("templates", $unreadpm_template);
    find_replace_templatesets('header', '#<navigation>#', "{\$unreadpmmsg}\n\t\t\t<navigation>"); 
    find_replace_templatesets('header', '#\n\t\t\t{\$pm_notice}#', ""); 
    
    // Rebuilt settings.php
    rebuild_settings();
}

function unreadpm_deactivate()
{
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
    global $db;
    
    $db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN('showunreadpm', 'unreadpmmsg', 'bgcolor', 'bordercolor')");
    $db->write_query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='Unread PM Notification Settings'");
    $db->write_query("DELETE FROM ".TABLE_PREFIX."templates WHERE title='global_unreadpm'");
    
    find_replace_templatesets('header', '#{$unreadpmmsg}#', ""); 
    find_replace_templatesets('header', '#{\$bannedwarning}#', "{\$pm_notice}\n\t\t\t{\$bannedwarning}"); 
    
    // Rebuilt settings.php
    rebuild_settings();
}

function unreadpm()
{
    global $mybb, $templates, $unreadpmmsg;
    
    if(($mybb->settings['showunreadpm'] != 'off') && (my_number_format($mybb->user['pms_unread']) > 0 ))
    {
        $temp_unreadpmmsg = $mybb->settings['unreadpmmsg'];
        $temp_bgcolor = $mybb->settings['bgcolor'];
        $temp_bordercolor = $mybb->settings['bordercolor'];
        eval("\$unreadpmmsg = \"".$templates->get('global_unreadpm')."\";");
    }
}

if(!function_exists("rebuild_settings"))
{
    function rebuild_settings()
    {
        global $db;
        $query = $db->write_query("SELECT * FROM ".TABLE_PREFIX."settings ORDER BY title ASC");
        while($setting = $db->fetch_array($query))
        {
            $setting['value'] = addslashes($setting['value']);
            $settings .= "\$settings['".$setting['name']."'] = \"".$setting['value']."\";\n";
        }
        $settings = "<?php\n/*********************************\ \n  DO NOT EDIT THIS FILE, PLEASE USE\n  THE SETTINGS EDITOR\n\*********************************/\n\n$settings\n?>";
        $file = fopen(MYBB_ROOT."/inc/settings.php", "w");
        fwrite($file, $settings);
        fclose($file);
    }
}
?>