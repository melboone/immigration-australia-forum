<?php
/***************************************************************************
 *
 *  Downloads plugin (/admin/modules/downloads/module_meta.php)
 *  Author: Edson Ordaz
 *  Copyright: © 2011 Edson Ordaz
 *  
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function downloads_meta()
{
	global $page, $lang, $plugins, $cache;
	$validatecache = $cache->read("downloads_validate");
	if($validatecache['code'] > 0)
	{
		$validate = " (".$validatecache['code'].")";
	}

	$sub_menu = array();
	$sub_menu['5'] = array("id" => "downloads", "title" => $lang->archives, "link" => "index.php?module=downloads/archives");
	$sub_menu['10'] = array("id" => "category", "title" => $lang->categorys, "link" => "index.php?module=downloads/category");
	$sub_menu['15'] = array("id" => "validate", "title" => $lang->validates.$validate, "link" => "index.php?module=downloads/validate");
	$sub_menu['20'] = array("id" => "options", "title" => $lang->moreoptions, "link" => "index.php?module=downloads/options");
	$sub_menu['25'] = array("id" => "settings", "title" => $lang->settingsdownload, "link" => "index.php?module=downloads/settings");
	$sub_menu['30'] = array("id" => "templates", "title" => $lang->templatesdownload, "link" => "index.php?module=downloads/templates");
	
	$plugins->run_hooks_by_ref("admin_downloads_menu", $sub_menu);
	
	$page->add_menu_item($lang->downloads.$validate, "downloads", "index.php?module=downloads", 70, $sub_menu);
	return true;
}

function downloads_action_handler($action)
{
	global $page, $lang, $plugins;
	
	$page->active_module = "downloads";
	
	$actions = array(
		'downloads' => array('active' => 'downloads', 'file' => 'downloads.php'),
		'category' => array('active' => 'category', 'file' => 'category.php'),
		'validate' => array('active' => 'validate', 'file' => 'validate.php'),
		'options' => array('active' => 'options', 'file' => 'options.php'),
		'settings' => array('active' => 'settings', 'file' => 'settings.php'),
		'templates' => array('active' => 'templates', 'file' => 'templates.php')
	);
	
	$plugins->run_hooks_by_ref("admin_downloads_action_handler", $actions);
	
	if(isset($actions[$action]))
	{
		$page->active_action = $actions[$action]['active'];
		return $actions[$action]['file'];
	}
	else
	{
		$page->active_action = "downloads";
		return "downloads.php";
	}
}

function downloads_admin_permissions()
{
	global $lang, $plugins;
	
	$admin_permissions = array(
		"downloads"	=> $lang->permissions_downloads,
		"category"	=> $lang->permissions_category,
		"validate"	=> $lang->permissions_validate,
		"options"	=> $lang->permissions_options,
		"settings"	=> $lang->permissions_settings,
		"templates"	=> $lang->permissions_templatesdownloads
	);
	
	$plugins->run_hooks_by_ref("admin_downloads_permissions", $admin_permissions);
	
	return array("name" => $lang->downloads, "permissions" => $admin_permissions, "disporder" => 70);
}
?>
