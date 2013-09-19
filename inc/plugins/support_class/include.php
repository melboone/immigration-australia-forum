<?php
/*
Class to support plugins:
Adding settings, changing templates, adding settings groups, adding templates - installing and deinstalling.
"A few steps to clean code..."
(c) 2010 by Victor
Website: http://www.victor.org.pl/support_class
License: Free to use, edit, etc.
*/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

global $ps, $mybb, $db;
class plugin_support {
	private $name;
	protected $prefix;

	private $mybb;
	public $db;

	private $plugin_info;

	private $settings = array();
	private $tchanges = array();
	private $newts = array();

	public function __construct($name, $mybb, $db)
	{
		$this->name = (string)$name;
		$this->prefix = $this->name."_";


		$this->mybb = $mybb;
		$this->db = $db;

		@$this->plugin_info = call_user_func($this->prefix."info");
	}

	public function addSetting($name, $title, $value = "", $description = "", $optionscode = "text")
	{
		if (!$name || !$title)
		{
			return false;
		}

		$this->settings[] =
			array(
				"name" => $name,
				"title" => $title,
				"value" => $value,
				"description" => $description,
				"optionscode" => $optionscode
			);
	}

	public function addTemplateChange($title, $what, $on)
	{
		$this->tchanges[] =
			array(
				"title" => $title,
				"what" => $what,
				"on" => $on
			);
	}

	public function addNewTemplate($title, $template)
	{
		$this->newts[] =
			array(
				"title" => $title,
				"template" => $template
			);
	}
			

	public function is_installed()
	{
		$query = $this->db->simple_select("settinggroups", "name", "name = '".$this->name."'");

		if ($this->db->num_rows($query) == 0)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	public function install()
	{
		# SETTINGSGROUP
		$last_order = $this->db->simple_select("settinggroups", "disporder", "", array('order_by' => 'disporder', 'order_dir' => 'DESC', 'limit' => '1'));

		$settinggroup = array(
			"gid" => NULL,
			"name" => $this->name,
			"title" => $this->plugin_info['name'],
			"description" => "",
			"disporder" => ($this->db->fetch_field($last_order, "disporder") + 1),
        );
		$gid = $this->db->insert_query("settinggroups", $settinggroup);

		# SETTINGS
		$disp = 1;
		foreach ($this->settings as $setting)
		{
			$additional = array(
				"sid" => NULL,
				"disporder" => $disp,
				"gid" => $gid
				);

			$setting = array_merge($setting, $additional);
			$setting['name'] = $this->prefix.$setting['name'];

			$this->db->insert_query("settings", $setting);

			$disp++;
		}

		# NEW TEMPLATES
		foreach ($this->newts as $newt)
		{
			$additional = array(
				"tid" => NULL,
				"sid" => "-1",
				"version" => "1400",
				"status" => NULL,
				"dateline" => time()
			);

			$newt = array_merge($newt, $additional);
			$newt['title'] = $this->prefix.$newt['title'];
			$newt['template'] = $this->db->escape_string($newt['template']);

			$this->db->insert_query("templates", $newt);

			$disp++;
		}
	}

	public function uninstall()
	{
		$this->db->delete_query("settinggroups", "name = '".$this->name."'");
		$this->db->delete_query("settings", "name LIKE '%".$this->prefix."%'");
		$this->db->delete_query("templates", "title LIKE '%".$this->prefix."%'");
	}

	public function activate()
	{
		require MYBB_ROOT."inc/adminfunctions_templates.php";

		# TEMPLATE CHANGES
		foreach ($this->tchanges as $tchange)
		{
			find_replace_templatesets($tchange['title'], "#".preg_quote($tchange['what'])."#Si", $tchange['what'].$tchange['on']);
		}
	}

	public function deactivate()
	{
		require MYBB_ROOT."inc/adminfunctions_templates.php";

		foreach ($this->tchanges as $tchange)
		{
			find_replace_templatesets($tchange['title'], "#".preg_quote($tchange['what'].$tchange['on'])."#Si", $tchange['what'], 0);
		}
	}
	
	public function close()
	{
		rebuild_settings();
	}
}
?>