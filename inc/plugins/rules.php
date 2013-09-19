<?php
/**
Plugin Rules v1.2
(c) 2011 by Victor
Website: http://www.victor.org.pl/rules
Version: 1.2 (@08.01.2011)
Please read README.TXT and LICENSE.TXT!
*/

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

require_once MYBB_ROOT."inc/plugins/support_class/include.php";

$plugins->add_hook("global_end", "rules_start");
$plugins->add_hook("misc_start", "rules_accept");
$plugins->add_hook("misc_start", "rules_view");
$plugins->add_hook("member_register_agreement", "rules_agreement");

function rules_info()
{
	global $lang;
	$lang->load("rules");

	return array(
		"name"			=> "Rules",
		"description"	=> $lang->rules_description,
		"author"		=> "Victor",
		"authorsite"	=> "http://www.victor.org.pl/",
		"version"		=> "v1.2",
		"compatibility" => "16*",
		"guid"			=> "01371fa00ebbdf1a6f5aa85b78776409"
	);
}

$ps = new plugin_support("rules", $mybb, $db);

$ps->addSetting("serial", $lang->serial_title, "1", $lang->serial_desc);
$ps->addSetting("rules", $lang->rules_title, "None.", $lang->rules_desc, "textarea");
$ps->addSetting("parse", $lang->parse_title, "0", $lang->parse_desc, "onoff");
$ps->addSetting("adnotations", $lang->adnotations_title, "", $lang->adnotations_desc, "textarea");

$template = <<<EOT
<html>
<head>
<title>{\$lang->rules_rulesof} {\$mybb->settings[bbname]}</title>
{\$headerinclude}
</head>
<body>
{\$header}
<table border="0" cellspacing="1" cellpadding="6" class="tborder">
<thead>
<tr>
<td class="thead"><span class="smalltext"><strong>{\$lang->rules}</strong></span></td>
</tr>
</thead>
<tbody>
<tr>
<td class="trow1" valign="top">
{\$mybb->settings['rules_rules']}
</td>
</tr>
</tbody>
</table>
<br />
{\$footer}
</body>
</html>
EOT;
$ps->addNewTemplate("index", $template);

$template = <<<EOT
{\$lang->rules_changed_by_last_visit}<br />
{\$adnotations}<br />
<div class="codeblock">
<div class="title">{\$lang->rules_its_newest_version}</div>
<div class="body" dir="ltr"><code>{\$mybb->settings['rules_rules']}</code></div>
</div>
<form action="misc.php?action=rules_accept" method="post">
<input type="hidden" name="serial" value="{\$mybb->settings['rules_serial']}" />
<input type="hidden" name="referer" value="{\$referer}" />
<input type="submit" value="{\$lang->rules_agree}" /><input type="button" value="{\$lang->rules_decline}" onclick="window.location = '{\$mybb->settings['bburl']}/member.php?action=logout&amp;logoutkey={\$mybb->user['logoutkey']}'; return false;" />
</form>
EOT;
$ps->addNewTemplate("changed", $template);

function rules_activate()
{
	global $ps;
	
	$ps->db->query("ALTER TABLE `".TABLE_PREFIX."users` ADD `rules_serial` FLOAT NOT NULL");

	$ps->install();
	$ps->activate();	
}

function rules_deactivate()
{
	global $ps;
	
	$ps->db->query("ALTER TABLE `".TABLE_PREFIX."users` DROP `rules_serial`");

	$ps->uninstall();
	$ps->deactivate();
}

# PLUGIN CONKRET FUNCTIONS
function rules_start()
{
	global $mybb, $templates, $lang;
	$lang->load("rules");

	$actions = array("rules_accept", "logout", "rules");
	if (in_array($mybb->input['action'], $actions) || $mybb->user['usergroup'] == 1)
	{
		return false;
	}

	if ((float)$mybb->user['rules_serial'] < (float)$mybb->settings['rules_serial'])
	{
		$adnotations = trim ($mybb->settings['rules_adnotations']);
		if (!empty($adnotations))
		{
			$adnotations = "<b>{$lang->rules_short_change_desc}</b> <i>".$adnotations."</i><br />";
		}

		if ($mybb->settings['rules_parse'])
		{
			require_once MYBB_ROOT.'inc/class_parser.php';

			$parser = new postParser();

			$mybb->settings['rules_rules'] = $parser->parse_message(strip_tags($mybb->settings['rules_rules']), array('allow_smilies' => 1, 'allow_mycode' => 1, 'nl2br' => 1, 'filter_badwords' => 1, 'shorten_urls' => 1));
		}
	
		$referer = htmlspecialchars($_SERVER['REQUEST_URI']);

		$lang->rules_its_newest_version = $lang->sprintf($lang->rules_its_newest_version, $mybb->settings['rules_serial']);

		eval("\$output = \"".$templates->get("rules_changed")."\";");
		error($output, "{$lang->rules_changed} {$mybb->settings[bbname]}");
	}
}

function rules_accept()
{
	global $mybb, $db, $lang;
	$lang->load("rules");

	if ($mybb->input['action'] == "rules_accept")
	{
		$db->update_query("users", array("rules_serial" => (float)$mybb->input['serial']), "uid = ".$mybb->user['uid']);
		
		$lang->rules_accepting = $lang->sprintf($lang->rules_accepting, $mybb->settings['bbname']);
		redirect(htmlspecialchars_decode($mybb->input['referer']), $lang->rules_accepted, $lang->rules_accepting);
	}
}

function rules_view()
{
	global $mybb, $templates, $output, $header, $footer, $theme, $headerinclude, $lang;
	$lang->load("rules");

	if ($mybb->input['action'] == "rules")
	{
		$title = "{\$lang->rules_rulesof} {$mybb->settings['bbname']}";
		
		$timenow = my_date($mybb->settings['dateformat'], TIME_NOW) . " " . my_date($mybb->settings['timeformat'], TIME_NOW);
		
		reset_breadcrumb();
		add_breadcrumb("Rules");

		if ($mybb->settings['rules_parse'])
		{
			require_once MYBB_ROOT.'inc/class_parser.php';

			$parser = new postParser();

			$mybb->settings['rules_rules'] = $parser->parse_message(strip_tags($mybb->settings['rules_rules']), array('allow_smilies' => 1, 'allow_mycode' => 1, 'nl2br' => 1, 'filter_badwords' => 1, 'shorten_urls' => 1));
		}

		eval("\$output = \"".$templates->get("rules_index")."\";");
		output_page($output);
	}
}

function rules_agreement()
{
	global $mybb, $templates, $output, $header, $footer, $theme, $headerinclude, $lang;
	
	eval("\$agreement = \"".$templates->get("member_register_agreement")."\";");

	$lang->rules_its_newest_version = $lang->sprintf($lang->rules_its_newest_version, $mybb->settings['rules_serial']);
	
	if ($mybb->settings['rules_parse'])
	{
		require_once MYBB_ROOT.'inc/class_parser.php';

		$parser = new postParser();

		$mybb->settings['rules_rules'] = $parser->parse_message(strip_tags($mybb->settings['rules_rules']), array('allow_smilies' => 1, 'allow_mycode' => 1, 'nl2br' => 1, 'filter_badwords' => 1, 'shorten_urls' => 1));
	}

	$templates->cache['member_register_agreement'] = str_replace("<p>{$lang->agreement_1}</p>\n<p>{$lang->agreement_2}</p>\n<p>{$lang->agreement_3}</p>\n<p>{$lang->agreement_4}</p>\n<p><strong>{$lang->agreement_5}</strong></p>", "<strong>{$lang->rules_its_newest_version}</strong><p>".$mybb->settings['rules_rules']."</p><br />", $agreement);
}

$ps->close();
?>