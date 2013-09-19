<?php
/**
 * MyCode Installer
 *
 * Install MyCodes directly from your ACP
 *
 * Copyright 2011 Nickman @ MyBBSource.com 
*/

if(!defined("IN_MYBB")) {
    die("This file cannot be accessed directly.");
}

$plugins->add_hook("admin_config_menu", "mycode_installer_nav");
$plugins->add_hook("admin_config_action_handler", "mycode_installer_action_handler");

function mycode_installer_info() {
    return array(
        "name"				=> "MyCode Installer",
        "description"		=> "Install MyCodes from MyBBSource Directly From Your ACP",
        "website"			=> "http://mybbsource.com",
        "author"			=> "Nickman",
        "authorsite"		=> "http://mybbsource.com",
        "version"			=> "1.0",
        'guid'        => '984564271762d3f2b87671323d4a2853'
    );
}

function mycode_installer_activate() {
	global $db;

}

function mycode_installer_deactivate() {
	global $db;

}

function mycode_installer_action_handler(&$action)
{
	$action['mycode_installer'] = array('active' => 'mycode_installer', 'file' => 'mycode_installer.php');
}
function mycode_installer_nav(&$sub_menu)
{
	global $mybb, $lang;
	end($sub_menu);
	$key = (key($sub_menu))+10;
	if(!$key)
	{
		$key = '50';
	}
	$sub_menu[$key] = array('id' => 'mycode_installer', 'title' => "MyCode Installer", 'link' => "index.php?module=config-mycode_installer");
}

?>