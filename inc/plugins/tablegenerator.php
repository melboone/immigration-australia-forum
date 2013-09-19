<?php
//###########################################################################
//#																			#
//#				 		 	 Table Generator 1.0								#
//#					 	  Copyright © 2011 Harest							#
//#																			#
//#						An easy way to create tables.						#
//#																			#
//#		This program is free software: you can redistribute it and/or 		#
//#		modify it under the terms of the GNU General Public License as 		#
//#		published by the Free Software Foundation, either version 3 of 		#
//#		the License, or (at your option) any later version.					#
//#																			#
//#		This program is distributed in the hope that it will be useful, 	#
//#		but WITHOUT ANY WARRANTY; without even the implied warranty of 		#
//#		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  				#
//#		See the GNU General Public License for more details.				#
//#																			#
//#		You should have received a copy of the GNU General Public License 	#
//#		along with this program.  											#
//#		If not, see <http://www.gnu.org/licenses/>.							#
//#																			#
//###########################################################################

if(!defined("IN_MYBB"))
	die("Bow ties are cool.");

$plugins->add_hook("newreply_end", "tablegenerator_button");
$plugins->add_hook("newthread_end", "tablegenerator_button");
$plugins->add_hook("misc_start", "tablegenerator_popup");

function tablegenerator_info() {
	global $lang;

	$lang->load("tablegenerator");
	
	return array(
		"name"			=> $lang->tg_name,
		"description"	=> $lang->tg_desc,
		"author"		=> "Harest",
		"version"		=> "1.0",
		"compatibility"	=> "16*",
		"guid"			=> "a7eb3ec90ae9948d83c6f10913a35920"
	);
}
// check if plugin is installed
function tablegenerator_is_installed() {
	global $db;
	
	$check=$db->fetch_array($db->simple_select("mycode", "*", "title='Table'"));
	
	if($check == true) {
		return true;
	}
	else {
		return false;
	}

}

// install plugin
function tablegenerator_install() {
	global $db, $mybb, $cache;
	
	$tableArray = array(
			'cid' => 'NULL',
			'title' => 'Table',
			'description' => 'Table tags',
			'regex' => '\\\\[TABLE=(.*?)\\\\](.*?)\\\\[/TABLE\\\\]',
			'replacement' => '<table border="0" cellspacing="1" cellpadding="3" class="tborder" style="width:$1%;">$2</table>',
			'active' => '1',
			'parseorder' => '0',
	);
	$db->insert_query("mycode", $tableArray);
	
	$tableRowArray = array(
			'cid' => 'NULL',
			'title' => 'Table Row',
			'description' => 'Table row tags',
			'regex' => '\\\\[TR\\\\](.*?)\\\\[/TR\\\\]',
			'replacement' => '<tr>$1</tr>',
			'active' => '1',
			'parseorder' => '0',
	);
	$db->insert_query("mycode", $tableRowArray);
	
	$tableHeadArray = array(
			'cid' => 'NULL',
			'title' => 'Table Head',
			'description' => 'Table head tags',
			'regex' => '\\\\[TH\\\\](.*?)\\\\[/TH\\\\]',
			'replacement' => '<th class="tcat" valign="middle"><strong>$1</strong></th>',
			'active' => '1',
			'parseorder' => '0',
	);
	$db->insert_query("mycode", $tableHeadArray);
	
	$tableDataArray = array(
			'cid' => 'NULL',
			'title' => 'Table Data',
			'description' => 'Table data tags',
			'regex' => '\\\\[TD\\\\](.*?)\\\\[/TD\\\\]',
			'replacement' => '<td class="trow1" valign="top" align="center">$1</td>',
			'active' => '1',
			'parseorder' => '0',
	);
	$db->insert_query("mycode", $tableDataArray);
	
	$cache->update_mycode();
}

// uninstall plugin
function tablegenerator_uninstall() {
	global $db, $cache;
	
	$db->delete_query("mycode", "title='Table'");
	$db->delete_query("mycode", "title='Table Row'");
	$db->delete_query("mycode", "title='Table Head'");
	$db->delete_query("mycode", "title='Table Data'");
	
	$cache->update_mycode();
}

// activate plugin
function tablegenerator_activate() {
	global $db, $mybb, $lang;
	
	$lang->load("tablegenerator");
	
	$tg_template[0] = array(
		"title" 	=> "tg_button",
		"template"	=> '<div style="margin:auto; width: 170px; margin-top: 20px;">
							<table border="0" cellspacing="{$theme[\\\'borderwidth\\\']}" cellpadding="{$theme[\\\'tablespace\\\']}" class="tborder" width="150">
								<tr>
									<td class="trow2" align="center">
										<span class="smalltext">
											<strong><a href="#" onclick="MyBB.popupWindow(\\\'{$mybb->settings[\\\'bburl\\\']}/misc.php?action=tablegenerator\\\', \\\'TableGenerator\\\', 660, 450);">{$lang->tg_open}</a></strong>
										</span>
									</td>
								</tr>
							</table>
						</div>',
		"sid"		=> -1,
		"version"	=> 1.0,
		"dateline"	=> TIME_NOW
	);
	$tg_template[1] = array(
		"title" 	=> "tg_generator",
		"template"	=> '<html>
						<header>
							<title>{$mybb->settings[\\\'bbname\\\']} - {$lang->tg_title}</title>
							{$headerinclude}
							<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.js"></script>
							<script type="text/javascript" src="{$mybb->settings[\\\'bburl\\\']}/jscripts/tablegenerator.js"></script>
						</header>
						<body>
						<table align="center" class="tborder" cellspacing="0" height="430px">
							<tr>
								<th class="thead" colspan="3" height="30px">{$lang->tg_title}</th>
							</tr>
							<tr>
								<td class="trow1"></td>
								<td class="trow1" align="left">{$lang->tg_width} <input type="text" size="3" id="tabWidth" /> %</td>
								<td class="trow1" style="height:25px;text-align:right;"><input type="button" value="&larr; TD" id="delCol" /><input type="button" value="TD &rarr;" id="addCol" /></td>
							</tr>
							<tr>
								<td class="trow1" valign="bottom" style="width:1%;"><input type="button" value="TR &uarr;" id="delRow" /><input type="button" value="TR &darr;" id="addRow" style="margin-bottom:2px;" /></td>
								<td class="trow2" valign="top" colspan="2" id="container">
									<table>
										<tr>
											<td><input type="checkbox" /> TH</td>
											<td><textarea cols="15" rows="3"></textarea></td>
											<td><textarea cols="15" rows="3"></textarea></td>
										</tr>
										<tr>
											<td><input type="checkbox" /> TH</td>
											<td><textarea cols="15" rows="3"></textarea></td>
											<td><textarea cols="15" rows="3"></textarea></td>
										</tr>
									</table>
								</td>	
							</tr>
							<tr>
								<td class="tfoot" colspan="3" height="30px">
									<input type="button" value="{$lang->tg_generate}" id="tabCreate" />
									<input type="button" value="{$lang->tg_close}" id="tabClose" />
								</td>
							</tr>
						</table>
						</body>
						</html>',
		"sid"		=> -1,
		"version"	=> 1.0,
		"dateline"	=> TIME_NOW
	);
	foreach ($tg_template as $row) {
		$db->insert_query("templates", $row);
	}
	
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets('newreply', '#{\$smilieinserter}#', '{\$smilieinserter}<!-- TableGenerator -->{$tabgen_button}<!-- /TableGenerator -->');
	find_replace_templatesets('newthread', '#{\$smilieinserter}#', '{\$smilieinserter}<!-- TableGenerator -->{$tabgen_button}<!-- /TableGenerator -->');
}
// deactivate plugin
function tablegenerator_deactivate() {
	global $db;
	
	$db->delete_query("templates", "`title` = 'tg_button'");
	$db->delete_query("templates", "`title` = 'tg_generator'");
	
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets('newreply', '#\<!--\sTableGenerator\s--\>(.+)\<!--\s/TableGenerator\s--\>#is', '', 0);
	find_replace_templatesets('newthread', '#\<!--\sTableGenerator\s--\>(.+)\<!--\s/TableGenerator\s--\>#is', '', 0);
}

//########## FUNCTIONS ##########

// adds button
function tablegenerator_button() {
	global $db, $mybb, $lang, $templates, $theme, $tabgen_button;
	
	$lang->load("tablegenerator");
	
	eval("\$tabgen_button .= \"".$templates->get("tg_button")."\";");
}

// opens popup
function tablegenerator_popup() {
	global $mybb, $db, $headerinclude, $lang, $templates, $theme;

	if($mybb->input['action'] == "tablegenerator") {
		
		$lang->load("tablegenerator");
		
		eval("\$tablegenerator = \"".$templates->get("tg_generator")."\";");
		output_page($tablegenerator);
	}
}
?>