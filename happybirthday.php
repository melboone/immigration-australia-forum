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
 * $Id: happybirthday.php 2 2009-10-14 06:34:54Z - G33K - $
 */

define("IN_MYBB", 1);
define("NO_ONLINE", 1);
define('THIS_SCRIPT', 'happybirthday.php');

$templatelist = "itsmybirthday_wishes_users,itsmybirthday_wishes,itsmybirthday_wishes_button_add,itsmybirthday_wishes_button_del";

require_once "./global.php";

// Load global language phrases
$lang->load("itsmybirthday");

if($mybb->user['uid'] == 0)
{
	error_no_permission();
}

// Exit if no regular action.
if($mybb->input['action'] != "addwish" && $mybb->input['action'] != "delwish")
{
	error($lang->imb_error_invalid_action);
}

if($mybb->settings['g33k_itsmybirthday_wishes_enabled'] != "1")
{
	error($lang->imb_error_wish_disabled);
}

// Get the pid and tid
$pid = intval($mybb->input['pid']);
$tid = intval($mybb->input['tid']);

// Set up $thread and $forum.
$options = array(
	"limit" => 1
);
$query = $db->simple_select("threads", "*", "tid='".$tid."'", $options);
$thread = $db->fetch_array($query);
$fid = $thread['fid'];

// Get forum info
$forum = get_forum($fid);
if(!$forum)
{
	error($lang->error_invalidforum);
}

$forumpermissions = forum_permissions($fid);
$options = array(
		"limit" => 1
	);
$query = $db->simple_select("posts", "*", "pid='".$pid."'", $options);
$post = $db->fetch_array($query);
if(!$post['pid'])
{
	error($lang->error_invalidpost);
}

// Check if really a birthday thread/post and whos bday it is
if($post['itsmybirthday_bdaypostfor_uid'] == "0")
{
	error($lang->imb_error_not_bday_post);
}

// See if everything is valid up to here.
if(isset($post) && (($post['visible'] == 0 && !is_moderator($fid)) || $post['visible'] == 0))
{
	error($lang->error_invalidpost);
}
if(isset($thread) && (($thread['visible'] == 0 && !is_moderator($fid)) || $thread['visible'] < 0))
{
	error($lang->error_invalidthread);
}
if($forum['open'] == 0 || $forum['type'] != "f")
{
	error($lang->error_closedinvalidforum);
}
if($forumpermissions['canview'] == 0 || $forumpermissions['canpostreplys'] == 0 || $mybb->user['suspendposting'] == 1)
{
	error_no_permission();
}

// Check if this forum is password protected and we have a valid password
check_forum_password($forum['fid']);

// Check to see if the thread is closed, and if the user is a mod.
if(!is_moderator($fid, "caneditposts"))
{
	if($thread['closed'] == 1)
	{
		error($lang->redirect_threadclosed);
	}
}

$username = $mybb->user['username'];
$uid = $mybb->user['uid'];

if($mybb->input['action'] == "addwish")
{	
	// Can't wish own post
	if($post['uid'] == $uid)
	{
		error($lang->imb_error_own_bday);
	}
	
	// Can't wish own birthday
	if($post['itsmybirthday_bdaypostfor_uid'] == $uid)
	{
		error($lang->imb_error_own_post);
	}

	// Check if user has already wished this post.
	$options = array(
			"limit" => 1
		);
	$query = $db->simple_select("g33k_itsmybirthday_bdaywishes", "*", "pid='".$pid."' AND uid='".$uid."'", $options);
	$wish = $db->fetch_array($query);
	
	if(isset($wish['wid']))
	{
		error($lang->imb_error_already_wished);
	}

	// Add wish to db
	$wid_data = array(
			"pid" => intval($post['pid']),
			"uid" => intval($mybb->user['uid']),
			"tid" => intval($post['tid']),
			"fid" => intval($post['fid']),
			"username" => $db->escape_string($mybb->user['username']),
			"bdayuser" => $db->escape_string($post['itsmybirthday_bdaypostfor_username']),
			"dateline" => TIME_NOW
	);

	$wid = $db->insert_query("g33k_itsmybirthday_bdaywishes", $wid_data);
	
	if($wid)
	{
		
		if($mybb->input['ajax'])
		{
			// Do nothing here
		}
		else
		{
			// Go back to the post
			$url = get_post_link($pid, $tid)."#pid{$pid}";
			redirect($url, $lang->imb_redirect_wished.$lang->imb_redirect_back); 
			exit;
		}
	}
	else
	{
		error($lang->imb_error_unknown);
	}
}

if($mybb->input['action'] == "delwish")
{
	if($mybb->settings['g33k_itsmybirthday_wishes_removable'] != "1")
	{
		error($lang->imb_error_removal_disabled);
	}
	// Check wish owner and wish exists
	$options = array(
			"limit" => 1
		);
	$query = $db->simple_select("g33k_itsmybirthday_bdaywishes", "*", "pid='".$pid."' AND uid='".$uid."'", $options);
	$wish = $db->fetch_array($query);
	
	if(isset($wish['wid']))
	{
		if($wish['uid'] == $uid)
		{
			// process delete
			$db->delete_query("g33k_itsmybirthday_bdaywishes", "wid='".$wish['wid']."'", "1");
			
			if($mybb->input['ajax'])
			{
				// Do nothing here
			}
			else
			{
				$url = get_post_link($pid, $tid)."#pid{$pid}";
				redirect($url, $lang->imb_redirect_wish_deleted.$lang->imb_redirect_back); 
			}
		}
		else
		{
			error($lang->imb_error_own_wish_delete);
		}
	}
	else
	{
		error($lang->imb_error_wish_not_found);
	}
}

if($mybb->input['ajax'])
{
	// Get all the wishes for this post
	$query = $db->simple_select('g33k_itsmybirthday_bdaywishes', '*', "pid = '".$post['pid']."'", array('order_by' => 'username', 'order_dir' => 'ASC'));
			
	$wishes = '';
	$comma = '';
	$wished = 0;
	$count = 0;
	while($wish = $db->fetch_array($query))
	{
		$profile_link = get_profile_link($wish['uid']);
		eval("\$itsmybirthday_wishes_users = \"".$templates->get("itsmybirthday_wishes_users")."\";");
		// Strip tags and trim to fix bug where template start/end comments add an extra space before the comma
		$wishes .= trim(strip_tags($itsmybirthday_wishes_users, '<a><span>'));
		$comma = ', ';	
		// Has this user wished?
		if($wish['uid'] == $mybb->user['uid'])
		{
			$wished = 1;
		}	
		$count++;			
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
		if($mybb->settings['postlayout'] == "classic")
		{
			eval("\$itsmybirthday_wishes = \"".$templates->get("itsmybirthday_wishes_classic")."\";");
		}
		else
		{
			eval("\$itsmybirthday_wishes = \"".$templates->get("itsmybirthday_wishes")."\";");
		}		
		echo $itsmybirthday_wishes;
	}
	else
	{
		echo '';
	}
	exit;
}

?>