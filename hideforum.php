<?php
/**
 * Hide Forums
 * Hide forums the user set to hide. This is a very early version and just shows
 * the basic idea behind this feature. But it should work already more or less
 * 
 * @access public
 * @author Jan Malte Gerth
 * @category MyBB Plugin
 * @copyright Copyright (c) 2010 Jan Malte Gerth (http://www.malte-gerth.de)
 * @license GPL3
 * @package Hide Forums
 * @since Version 0.0.1
 * @version 0.8.5
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'hideforum.php');

require_once "./global.php";
require_once MYBB_ROOT."inc/functions_user.php";

// Load global language phrases
$lang->load("usercp");
$lang->load("hideforums");

// if the user is not logged in show an permission error
if($mybb->user['uid'] == 0 || $mybb->usergroup['canusercp'] == 0) {
	error_no_permission();
}

// if the plugin is not active, show an error message
$plugins_cache = $cache->read("plugins");
$active_plugins = $plugins_cache['active'];
if(!isset($active_plugins['hideforums'])) {
	$page = <<<PAGECONTENT
<html>
	<head>
		<title>{$lang->hideforums}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<table width="100%" border="0" align="center">
			<tr>
				<td valign="top">
					<h1>{$lang->plugininactive}</h1>
				</td>
			</tr>
		</table>
		{$footer}
	</body>
</html>
PAGECONTENT;
	output_page($page);
	exit;
}

// get the PM folders to build the User CP menu
if(!$mybb->user['pmfolders']) {
	$mybb->user['pmfolders'] = "1**".$lang->folder_inbox."$%%$2**".$lang->folder_sent_items."$%%$3**".$lang->folder_drafts."$%%$4**".$lang->folder_trash;
	$db->update_query("users", array('pmfolders' => $mybb->user['pmfolders']), "uid='".$mybb->user['uid']."'");
}

usercp_menu();

$plugins->run_hooks("usercp_start");

// Make navigation
add_breadcrumb($lang->nav_usercp, "usercp.php");
add_breadcrumb($lang->hideforums, "hideforum.php");

if($mybb->input['action'] == "post_form" && $mybb->request_method == "post") {
	if(isset($mybb->input['hideforums'])) {
		// get all existing forums
		if(!is_array($forums_by_parent)) {
			$forum_cache = cache_forums();
			foreach($forum_cache as $forum) {
				// the current forum is marked as visible
				if(in_array($forum['fid'], $mybb->input['visible'])) {
					// do nothing
					continue;
				} else {
					// otherwise add the current forum to the hidden forums list
					$hidden_forums[] = $forum['fid'];
				}
			}
		}
		$mybb->user['fid'.$mybb->settings['hideforum_fid']] = implode(',',(array)$hidden_forums);
		$db->update_query("userfields", array('fid'.$mybb->settings['hideforum_fid'] => $mybb->user['fid'.$mybb->settings['hideforum_fid']]), "ufid = '{$mybb->user['uid']}'");
	
	}
	redirect("hideforum.php", $lang->forumsupdated);
	
} elseif($mybb->input['action'] == "hide" && $mybb->input['fid']) {	
	$fid = intval($mybb->input['fid']);
	// Get forum info
	$foruminfo = get_forum($fid);
	
	$forumpermissions = forum_permissions($fid);
	
	$hidden_forums = (array) explode(',', $mybb->user['fid'.$mybb->settings['hideforum_fid']]);
	$hidden_forums[] = $fid;
	$mybb->user['fid'.$mybb->settings['hideforum_fid']] = implode(',',$hidden_forums);
	$db->update_query("userfields", array('fid'.$mybb->settings['hideforum_fid'] => $mybb->user['fid'.$mybb->settings['hideforum_fid']]), "ufid = '{$mybb->user['uid']}'");
	
	redirect("hideforum.php", "Hidden Forums updated");
} elseif($mybb->input['action'] == "unhide" && $mybb->input['fid']) {	
	$fid = intval($mybb->input['fid']);
	// Get forum info
	$foruminfo = get_forum($fid);
	
	$forumpermissions = forum_permissions($fid);
	
	$hidden_forums = (array) explode(',', $mybb->user['fid'.$mybb->settings['hideforum_fid']]);
	foreach($hidden_forums as $forum) {
		if($forum == $fid) {
			unset($forum);
		} else {
			$new_hidden_forums[] = $forum;
		}
	}
	$mybb->user['fid'.$mybb->settings['hideforum_fid']] = implode(',',$new_hidden_forums);
	$db->update_query("userfields", array('fid'.$mybb->settings['hideforum_fid'] => $mybb->user['fid'.$mybb->settings['hideforum_fid']]), "ufid = '{$mybb->user['uid']}'");
	
	redirect("hideforum.php", "Hidden Forums updated");
}
// show the page to set the hidden forums
elseif (!$mybb->input['action']) {
	/* 
	 * Get forum info; not used at the moment.
	 * 
	 * $fid = intval($mybb->input['fid']);
	 * $foruminfo = get_forum($fid);
	 */
	// get permissions
	$forumpermissions = forum_permissions();
	/*
	 * add the forum to the breadcrumb navigation; not used at the moment.
	 * if($fid) {
		add_breadcrumb($foruminfo['name'], "hideforum.php?fid=".$fid);
	 * }
	 */
	
	// render the form and build the neccessary stuff
	$content = build_forums_list();
	
	// the template for the page
	$page = <<<PAGECONTENT
<html>
<head>
<title>{$lang->hideforums}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
{$usercpnav}
<td valign="top">
<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->hideforums}</strong></td>
</tr>
<tr>
<td class="trow2">

<form action="hideforum.php?action=post_form" method="post">
<table border="0" cellspacing="1" cellpadding="4">
	<thead class="tcat">
		<tr>
			<th></th>
			<th>{$lang->forum}</th>
			<th>{$lang->visible}</th>
		</tr>
	</thead>
{$content}
</table>
<input type="submit" name="hideforums" value="{$lang->updatehiddenforums}" />
</form>

</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>
PAGECONTENT;
	
	$plugins->run_hooks("usercp_end");
	
	output_page($page);
}

/**
 * build the real content of the page
 */
function build_forums_list($pid=0, $depth=1) {
	
	global $mybb, $lang, $db, $forumpermissions;
	static $forums_by_parent;
	
	// get all existing forums
	if(!is_array($forums_by_parent)) {
		$forum_cache = cache_forums();
		foreach($forum_cache as $forum) {
			$forums_by_parent[$forum['pid']][$forum['disporder']][$forum['fid']] = $forum;
		}
	}
	
	// are there any forums for this parent
	if(!is_array($forums_by_parent[$pid])) {
		return;
	}

	foreach($forums_by_parent[$pid] as $children) {
		foreach($children as $forum) {
			
			$trow = alt_trow();
			
			// get permissions for the forum
			$fpermissions = $forumpermissions[$forum['fid']];
			
			// Fix & but allow unicode
			$forum['name'] = preg_replace("#&(?!\#[0-9]+;)#si", "&amp;", $forum['name']);
			
			// skip forums without view permission
			if($fpermissions['canview'] != 1) {
				continue;
			}
			// if the forum is inactive
			if($forum['active'] == 0) {
				$forum['name'] = "<em>".$forum['name']."</em>";
			}
			
			// we have a category
			if($forum['type'] == "c" && ($depth == 1 || $depth == 2)) {
				if($forum['description']) {
					$forum['description'] = preg_replace("#&(?!\#[0-9]+;)#si", "&amp;", $forum['description']);
           			$forum['description'] = "<br /><small>".$forum['description']."</small>";
       			}
       			$html .= '<tr><td class="'.$trow.'"><div class="expcolimage"><img title="[-]" alt="[-]" class="expander" id="cat'.$forum['fid'].'_img" src="/images/collapse.gif" style="cursor: pointer;"></div></td>';
       			
				// the forum is already hidden, show options to unhide it
       			if(in_array($forum['fid'], (array) explode(',', $mybb->user['fid'.$mybb->settings['hideforum_fid']]))) {
					$html .= '<td class="'.$trow.'"><div style="padding-left: '.(20*($depth-1)).'px;"><strike><strong>'.$forum['name'].'</strong></strike>'.$forum['description'].'</div></td>';
					#$html .= '<td>Hidden</td><td><a href="hideforum.php?fid='.$forum['fid'].'&amp;action=unhide">Unhide</a></td>';
					$html .= '<td class="'.$trow.'"><input type="checkbox" name="visible[]" value="'.$forum['fid'].'" /></td>';
       			}
       			// the forum is visible, show options to hide it
       			else {
					$html .= '<td class="'.$trow.'"><div style="padding-left: '.(20*($depth-1)).'px;\"><strong>'.$forum['name'].'</strong>'.$forum['description'].'</div></td>';
					#$html .= '<td><a href="hideforum.php?fid='.$forum['fid'].'&amp;action=hide">Hide</a></td><td>Visible</td>';
					$html .= '<td class="'.$trow.'"><input type="checkbox" name="visible[]" value="'.$forum['fid'].'" checked="checked"/></td>';
				}
				
				$html .= '</tr>';
				
				// Does this category have any sub forums?
				if($forums_by_parent[$forum['fid']]) {
					// dirty hack to get folding enabled
					$html .= '<tbody id="cat'.$forum['fid'].'_e">';
					$html .= build_forums_list($forum['fid'], $depth+1);
					$html .= '</tbody>';
				}
			}
			// we have a forum
			elseif($forum['type'] == "f")# && ($depth == 1 || $depth == 2))
			{
				if($forum['description']) {
					$forum['description'] = preg_replace("#&(?!\#[0-9]+;)#si", "&amp;", $forum['description']);
           			$forum['description'] = "<br /><small>".$forum['description']."</small>";
       			}
       			$html .= '<tr><td class="'.$trow.'"></td>';
       			
				// the forum is already hidden, show options to unhide it
       			if(in_array($forum['fid'], (array) explode(',', $mybb->user['fid'.$mybb->settings['hideforum_fid']]))) {
					$html .= '<td class="'.$trow.'"><div style="padding-left: '.(20*($depth-1)).'px;"><strike><strong>'.$forum['name'].'</strong></strike>'.$forum['description'].'</div></td>';
					#$html .= '<td>Hidden</td><td><a href="hideforum.php?fid='.$forum['fid'].'&amp;action=unhide">Unhide</a></td>';
					$html .= '<td class="'.$trow.'"><input type="checkbox" name="visible[]" value="'.$forum['fid'].'" /></td>';
       			}
       			// the forum is visible, show options to hide it
       			else {
					$html .= '<td class="'.$trow.'"><div style="padding-left: '.(20*($depth-1)).'px;\"><strong>'.$forum['name'].'</strong>'.$forum['description'].'</div></td>';
					#$html .= '<td><a href="hideforum.php?fid='.$forum['fid'].'&amp;action=hide">Hide</a></td><td>Visible</td>';
					$html .= '<td class="'.$trow.'"><input type="checkbox" name="visible[]" value="'.$forum['fid'].'" checked="checked"/></td>';
				}
				
				$html .= '</tr>';
				
				// Does this category have any sub forums?
				if(isset($forums_by_parent[$forum['fid']])) {
					$html .= build_forums_list($forum['fid'], $depth+1);
				}
			}
		}
	}
	return $html;
}
?>