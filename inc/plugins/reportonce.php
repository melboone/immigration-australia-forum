<?php
/*
/¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯\
|     » Copyright Notice «      |
|¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯| 
| » Report Once                 |
|   1.1 © 2012                  |
| » Released free of charge     |
| » You may edit or modify      |
|   this plugin, however you    |
|   may not redistribute it.    |
| » This notice must stay       |
|   intact for legal use.       |
|  » For support, please visit  |
|    http://vernier.me          |
/¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯\
|        » Report Once «        |
|         » 1.1 © 2012 «        |
\_______________________________/
*/

//Disallow direct Initialization for extra security.
if(!defined("IN_MYBB"))
{
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

// Hooks
$plugins->add_hook('report_start','reportonce_report_start');
$plugins->add_hook('postbit','reportonce_postbit');

// Information
function reportonce_info()
{
    global $lang;

    $lang->load('reportonce');

return array(
        "name"  => $lang->reportonce,
        "description"=> $lang->reportonce_description,
        "website"        => "http://vernier.me",
        "author"        => "Vernier",
        "authorsite"    => "http://vernier.me",
        "version"        => "1.1",
        "guid"             => "",
        "compatibility" => "16*"
    );
}

// Activate
function reportonce_activate() {
    global $db, $lang, $templates;

    $lang->load('reportonce');

$reportonce_group = array(
        'gid'    => 'NULL',
        'name'  => 'reportonce',
        'title'      => $lang->reportonce,
        'description'    => $lang->reportonce_settings,
        'disporder'    => "1",
        'isdefault'  => "0",
    );
$db->insert_query('settinggroups', $reportonce_group);
 $gid = $db->insert_id();

$reportonce_setting_1 = array(
        'sid'            => 'NULL',
        'name'        => 'reportonce_enable',
        'title'            => $lang->enable,
        'description'    => $lang->enable_description,
        'optionscode'    => 'yesno',
        'value'        => '1',
        'disporder'        => 1,
        'gid'            => intval($gid),
    );

$reportonce_setting_2 = array(
        'sid'            => 'NULL',
        'name'        => 'reportonce_hidebutton',
        'title'            => $lang->hide_button,
        'description'    => $lang->hide_button_description,
        'optionscode'    => 'yesno',
        'value'        => '1',
        'disporder'        => 2,
        'gid'            => intval($gid),
    );
$db->insert_query('settings', $reportonce_setting_1);
$db->insert_query('settings', $reportonce_setting_2);
  rebuild_settings();

  require_once MYBB_ROOT.'inc/adminfunctions_templates.php';

  find_replace_templatesets("postbit", "#".preg_quote('{$post[\'button_report\']}')."#i", '{$post[\'reportonce_button\']}');
  find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'button_report\']}')."#i", '{$post[\'reportonce_button\']}');
}

// Deactivate
function reportonce_deactivate()
  {
  global $db;
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN ('reportonce_enable')");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN ('reportonce_hidebutton')");
    $db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='reportonce'");
rebuild_settings();

require_once MYBB_ROOT.'inc/adminfunctions_templates.php';

find_replace_templatesets("postbit", "#".preg_quote('{$post[\'reportonce_button\']}')."#i", '{$post[\'button_report\']}');
find_replace_templatesets("postbit_classic", "#".preg_quote('{$post[\'reportonce_button\']}')."#i", '{$post[\'button_report\']}');
 }


 function reportonce_report_start()
 {
    global $db, $mybb, $lang, $templates;

if ($mybb->settings['reportonce_enable'] == 1)
{
    $lang->load('reportonce');

    $checkexist = $db->query("SELECT * FROM ".TABLE_PREFIX."reportedposts WHERE pid='".intval($mybb->input['pid'])."'");
    if ($db->num_rows($checkexist))
    {
        
    echo $lang->error;
    die();

    }
 }
}

function reportonce_postbit(&$post)
{
    global $mybb, $templates, $db;

    $checkexist = $db->query("SELECT * FROM ".TABLE_PREFIX."reportedposts WHERE pid='".intval($post['pid'])."'");
    if ($db->num_rows($checkexist) && $mybb->settings['reportonce_enable'] == 1 && $mybb->settings['reportonce_hidebutton'] == 1)
    {
        $post['reportonce_button'] = '';
    }

    else
    {
        $post['reportonce_button'] = $post['button_report'];
    }
}
?>