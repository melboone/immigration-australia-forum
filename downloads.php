<?php
/**
 * MyBB 1.6
 * Copyright 2011 Edson Ordaz, All Rights Reserved
 *
 * Email: nicedo_eeos@hotmail.com
 * Downloads V2.0.1
 *
 * $Id: downloads.php 2011-06-22 Edson Ordaz $
 */
 
define("IN_MYBB", 1);
define('THIS_SCRIPT', 'downloads.php');
define('TABLE', 'downloads_');
define("KILL_GLOBALS", 1);
require_once "./global.php";
$lang->load("downloads");

add_breadcrumb($lang->downloads, THIS_SCRIPT);

if($mybb->settings['downloads_active'] == 0)
{
	error_no_permission();
	exit;
}

if($mybb->input['newimages'])
{
	$qdownloads = $db->simple_select('downloads', '*', 'did="'.$mybb->input['newimages'].'"');
	$archive = $db->fetch_array($qdownloads);
	$qcategory = $db->simple_select(TABLE.'cat', '*', 'dcid="'.$archive['category'].'"');
	$category = $db->fetch_array($qcategory);
	if(!$archive['did'])
	{
		error_no_permission();
	}
	if($archive['uid'] != $mybb->user['uid'])
	{
		error_no_permission();
	}
	if(!empty($archive['pics']))
	{
		error_no_permission();
	}
	if($mybb->settings['downloads_usercreatedownloads'] == 0)
	{
		error_no_permission();
		exit;
	}
	if(!in_array($mybb->user['usergroup'],explode(",",$mybb->settings['downloads_groupscreatedownloads'])))
	{
		error_no_permission();
	}
	if($mybb->input['images'] < 1 || $mybb->input['images'] > 10)
	{
		error_no_permission();
	}
	$lang->title_category = $lang->sprintf($lang->title_category, $category['name']);
	add_breadcrumb($lang->title_category, THIS_SCRIPT.'?category='.$category['dcid']);
	add_breadcrumb($archive['name'], THIS_SCRIPT.'?archive='.$archive['did']);
	add_breadcrumb($lang->addimages);
	$did = intval($archive['did']);
	$images = $mybb->input['images'];
	if($mybb->request_method == "post")
	{
		for($i = 1; ; $i++) 
		{
			if ($i > intval($mybb->input['images'])) 
			{
				break;
			}
			if(strlen($mybb->input['image_'.$i]) < 4)
			{
				$lang->imagerrorfor = $lang->sprintf($lang->image_error_for, $i);
				$errors[] = $lang->imagerrorfor;
			}
		}
		if(!$errors)
		{
			for($i = 1; ; $i++) 
			{
				if ($i > intval($mybb->input['images'])) 
				{
					break;
				}
				$insert = array(
					"did" => $did,
					"dcid" => intval($archive['category']),
					"image" => $mybb->input['image_'.$i],
					"orden" => $i
				);	
				$diid = $db->insert_id();
				$db->insert_query("downloads_images", $insert);
			}
			$edit_update = array(
				"pics" => intval($mybb->input['images'])
			);
			$db->update_query("downloads", $edit_update,"did=".$did);
			$db->query("DELETE FROM ".TABLE_PREFIX."datacache WHERE title='downloads_cache'");
			if($mybb->settings['downloads_validatedownloads'] == 0)
			{
				redirect(THIS_SCRIPT.'?archive='.$did, $lang->imagesaddsuccess);
			}
			else
			{
				redirect(THIS_SCRIPT, $lang->imagesaddsuccess_validate);
			}
		}
	}
	if($errors)
	{
		$image_errors = inline_error($errors);
	}
	for($number = 1; ; $number++) 
	{
		if ($number > $mybb->input['images']) 
		{
			break;
		}
		$color = alt_trow();
		$image .= $mybb->input['image_'.$number];
		eval("\$imagesbox .= \"".$templates->get("downloads_newdownload_addimages_box")."\";");
		unset($image);
	}
	eval("\$add_images = \"".$templates->get("downloads_newdownload_addimages")."\";");
	output_page($add_images);
	return false;
}
elseif($mybb->input['newlinks'])
{
	$qdownloads = $db->simple_select('downloads', '*', 'did="'.$mybb->input['newlinks'].'"');
	$download = $db->fetch_array($qdownloads);
	$qcategory = $db->simple_select(TABLE.'cat', '*', 'dcid="'.$download['category'].'"');
	$category = $db->fetch_array($qcategory);
	if(!$download['did'])
	{
		error_no_permission();
	}
	if($download['uid'] != $mybb->user['uid'])
	{
		error_no_permission();
	}
	if($mybb->settings['downloads_usercreatedownloads'] == 0)
	{
		error_no_permission();
		exit;
	}
	if(!in_array($mybb->user['usergroup'],explode(",",$mybb->settings['downloads_groupscreatedownloads'])))
	{
		error_no_permission();
	}
	if($mybb->input['images'] < 1 || $mybb->input['images'] > 10)
	{
		error_no_permission();
	}
	$lang->title_category = $lang->sprintf($lang->title_category, $category['name']);
	add_breadcrumb($lang->title_category, THIS_SCRIPT.'?category='.$category['dcid']);
	add_breadcrumb($download['name'], THIS_SCRIPT.'?archive='.$download['did']);
	add_breadcrumb($lang->addlinks);
	$did = intval($download['did']);
	if($mybb->request_method == "post")
	{
		for($i = 1; ; $i++) 
		{
			if ($i > intval($mybb->input['urls'])) 
			{
				break;
			}
			if(strlen($mybb->input['url_'.$i]) < 4)
			{
				$lang->urlrrorfor = $lang->sprintf($lang->url_error_for, $i);
				$errors[] = $lang->urlrrorfor;
			}
			if(strlen($mybb->input['name_'.$i]) < 3)
			{
				$lang->urlrrorforname = $lang->sprintf($lang->url_errorname_for, $i);
				$errors[] = $lang->urlrrorforname;
			}
		}
		if(!$errors)
		{
			for($i = 1; ; $i++) 
			{
				if ($i > intval($mybb->input['urls'])) 
				{
					break;
				}
				$insert_urls = array(
					"did" => $did,
					"dcid" => intval($download['category']),
					"url" => $db->escape_string($mybb->input['url_'.$i]),
					"text" => $db->escape_string($mybb->input['name_'.$i]),
					"generate" => random_str(10),
					"orden" => $i
				);	
				$duid = $db->insert_id();
				$db->insert_query("downloads_urls", $insert_urls);
			}
			$edit_update = array(
				"urls" => intval($mybb->input['urls'])
			);
			$db->update_query("downloads", $edit_update,"did=".$did);
			if($mybb->input['boximg'] == 1)
			{
				$numimages = intval($mybb->input['images']);
				$edit_update = array(
					"pics" => 0
				);
				$db->update_query("downloads", $edit_update,"did=".$download['did']);
				redirect("downloads.php?newimages=".$download['did']."&images=".$numimages, $lang->downloadcreateimages);
			}
			else
			{
				$edit_update = array(
					"pics" => intval(0)
				);
				$db->update_query("downloads", $edit_update,"did=".$download['did']);
				$db->query("DELETE FROM ".TABLE_PREFIX."datacache WHERE title='downloads_cache'");
				if($mybb->settings['downloads_validatedownloads'] == 1)
				{				
					redirect(THIS_SCRIPT, $lang->successcreatewaitval);
				}
				else
				{
					redirect(THIS_SCRIPT.'?archive='.$download['did'], $lang->successcreateredirectdown);
				}
			}
		}
	}
	if($errors)
	{
		$image_errors = inline_error($errors);
	}
	for($number = 1; ; $number++) 
	{
		if ($number > $mybb->input['urls']) 
		{
			break;
		}
		$color = alt_trow();
		$url .= $mybb->input['url_'.$number];
		$name .= $mybb->input['name_'.$number];
		eval("\$linksbox .= \"".$templates->get("downloads_newdownload_addlinks_box")."\";");
		unset($url);
		unset($name);
	}
	eval("\$add_images = \"".$templates->get("downloads_newdownload_addlinks")."\";");
	output_page($add_images);
	return false;
}
elseif($mybb->input['newdownload'])
{
	if($mybb->settings['downloads_usercreatedownloads'] == 0)
	{
		error_no_permission();
		exit;
	}
	if(!in_array($mybb->user['usergroup'],explode(",",$mybb->settings['downloads_groupscreatedownloads'])))
	{
		error_no_permission();
	}
	if(!empty($mybb->settings['downloads_maxcreated']) && $mybb->settings['downloads_maxcreated'] <= $mybb->user['downloads'] && $mybb->user['usergroup'] != 4)
	{
		error_no_permission();
	}
	$qcategory = $db->simple_select(TABLE.'cat', '*', 'dcid="'.$mybb->input['newdownload'].'"');
	$category = $db->fetch_array($qcategory);
	if(!$category['dcid'])
	{
		error_no_permission();
	}
	$dcid = intval($category['dcid']);
	$lang->title_category = $lang->sprintf($lang->title_category, $category['name']);
	add_breadcrumb($lang->title_category, THIS_SCRIPT."?category=".$category['dcid']);
	add_breadcrumb($lang->newdownloads);
	if($mybb->request_method == "post")
	{
		$query = $db->simple_select("downloads", "*", "name=\"".$mybb->input['name']."\"");
		$download_name = $db->fetch_array($query);
		
		if($download_name['name'])
		{
			$errors[] = $lang->existdownloadname;
		}
		$db->free_result($query);
		unset($query);
		unset($download_name);
		if(strlen($mybb->input['name']) < 2)
		{
			$errors[] = $lang->namedownloadshort;
		}
		if(strlen($mybb->input['shortdesc']) < 5)
		{
			$errors[] = $lang->shortdescisshort;
		}
		if(strlen($mybb->input['description']) < 10)
		{
			$errors[] = $lang->descdownloadshort;
		}
		if(empty($mybb->input['image']))
		{
			$errors[] = $lang->frondownloadempty;
		}
		$mybb->input['url'] = intval($mybb->input['url']);
		if(empty($mybb->input['url']) || $mybb->input['url'] < 1)
		{
			$errors[] = $lang->urldownloadempty;
		}
		if($mybb->input['chekimages'] == 1)
		{
			$numimages = intval($mybb->input['numimages']);
			if($numimages < 1)
			{
				$errors[] = $lang->enternumberimagesvalidate;
			}
			if($numimages > 10)
			{
				$errors[] = $lang->notmoretenimages;
			}
		}
		if(!$errors)
		{
			$db->query("DELETE FROM ".TABLE_PREFIX."datacache WHERE title='downloads_cache'");
			if($mybb->settings['downloads_validatedownloads'] == 1)
			{
				$validate = 1;
			}else{
				$validate = 0;
				$edit_user = array(
					"downloads" => intval(++$mybb->user['downloads'])
				);
				$db->update_query("users", $edit_user,"uid=".$mybb->user['uid']);
			}
			$query_order = $db->simple_select("downloads", "orden", "category=\"".$dcid."\"", array('order_by' => 'orden', 'order_dir' => 'DESC'));
			$orden = $db->fetch_array($query_order);
			$insert = array(
				"name" => $db->escape_string($mybb->input['name']),
				"orden" => intval(++$orden['orden']),
				"uid" => intval($mybb->user['uid']),
				"shortdesc" => $db->escape_string($mybb->input['shortdesc']),
				"description" => $db->escape_string($mybb->input['description']),
				"image" => $db->escape_string($mybb->input['image']),
				"comments" => 1,
				"dateline" => TIME_NOW,
				"urls" => 0,
				"active" => 1,
				"groups" => "1,5,7",
				"category" => $dcid,
				"bbcode" => 1,
				"validate" => $validate
			);
			$did = $db->insert_id();
			$update_cache = array(
				"name" => $db->escape_string($mybb->input['name']),
				"code" => $generate
			);
			unset($orden);
			$db->free_result($query_order);
			$cache->update("downloads_cache", $update_cache);
			$db->insert_query("downloads", $insert);
			if($mybb->settings['downloads_validatedownloads'] == 1)
			{
				$validatecache = $cache->read("downloads_validate");
				$update_cache = array(
					"name" => "Validate",
					"code" => ++$validatecache['code']
				);
				$cache->update("downloads_validate", $update_cache);
			}
			$boximg = intval($mybb->input['chekimages']);
			$numimages = intval($mybb->input['numimages']);
			$cache_read = $cache->read("downloads_cache");
			$query = $db->simple_select("downloads", "*", "name=\"".$cache_read['name']."\"");
			$download = $db->fetch_array($query);
			redirect("downloads.php?newlinks=".$download['did']."&urls=".$mybb->input['url']."&boximg=".$boximg."&images=".$numimages, $lang->downloadcreatelinks);
		}
	}
	$name = $mybb->input['name'];
	$shortdesc = $mybb->input['shortdesc'];
	$description = $mybb->input['description'];
	$image = $mybb->input['image'];
	$url = $mybb->input['url'];
	if($errors)
	{
		$downloads_errors = inline_error($errors);
	}
	$codebuttons = build_mycode_inserter();
	$smilieinserter = build_clickable_smilies();
	eval("\$category_view = \"".$templates->get("downloads_newdownload")."\";");
	output_page($category_view);
	return false;
}
elseif($mybb->input['download'])
{
	$qdownlink = $db->simple_select('downloads_urls', '*', 'generate="'.$mybb->input['download'].'"');
	$link = $db->fetch_array($qdownlink);
	$qdown = $db->simple_select('downloads', '*', 'did="'.$link['did'].'"');
	$archive = $db->fetch_array($qdown);
	if(!$archive['did'])
	{
		error_no_permission();
	}
	if($archive['active'] == 0)
	{
		error_no_permission();
	}
	if($archive['validate'] == 1)
	{
		error_no_permission();
	}
	if(in_array($mybb->user['usergroup'],explode(",",$archive['groups'])))
	{
		error_no_permission();
	}
	$db->update_query("downloads", array("downloads" => ++$archive['downloads']),"did=".$archive['did']);
	$lang->downloadarchive = $lang->sprintf($lang->downloadarchive, $archive['name']);
	eval("\$exitpage = \"".$templates->get("downloads_download")."\";");
	output_page($exitpage);
	return false;
}
elseif($mybb->input['action'] == "do_comment" && $mybb->request_method == "post")
{
	if(!$uid)
	{
		$uid = intval($mybb->user['uid']);
	}
	$Q = $db->simple_select('downloads', '*', 'did='.$mybb->input['did']);
	$archive = $db->fetch_array($Q);
	if($archive['comments'] == 0)
	{
		error_no_permission();
	}
	$insert_comment = array(
		"did" => $mybb->input['did'],
		"category" => $mybb->input['category'],
		"uid" => $uid,
		"comment" => $db->escape_string($mybb->input['message']),
		"dateline" => TIME_NOW
	);
	$dcid = $db->insert_id();
	$db->insert_query("downloads_comments", $insert_comment);
	redirect("downloads.php?archive=".$mybb->input['did']."#comments", $lang->addcommentssuccess);
}
elseif($mybb->input['action'] == "comment")
{
	if($mybb->input['manage'] == "do_editcomment" && $mybb->request_method == "post")
	{
		$edit_comments = array(
			"comment" => $db->escape_string($mybb->input['message'])
		);
		$db->update_query(TABLE.'comments', $edit_comments,"dcid=".$mybb->input['dcid']);
		redirect("downloads.php?archive=".$mybb->input['did'], $lang->editcommentssuccess);
	}
	elseif($mybb->input['manage'] == "edit")
	{
		$query_comments = $db->simple_select(TABLE.'comments', '*', 'dcid='.$mybb->input['dcid']);
		$comments = $db->fetch_array($query_comments);
		if(!$comments['dcid'])
		{
			error_no_permission();
		}
		if($mybb->user['uid'] != $comments['uid'])
		{
			$usergroup = $cache->read("usergroups");
			if($usergroup[$mybb->user['usergroup']]['canmodcp'] == 0)
			{
				error_no_permission();
			}
		}
		$query_archive = $db->simple_select('downloads', '*', 'did='.$comments['did']);
		$archive = $db->fetch_array($query_archive);
		$qcat = $db->simple_select(TABLE.'cat', '*', 'dcid='.$archive['category']);
		$category = $db->fetch_array($qcat);
		$lang->title_category = $lang->sprintf($lang->title_category, $category['name']);
		add_breadcrumb($lang->title_category, THIS_SCRIPT."?category=".$category['dcid']);
		add_breadcrumb($archive['name'], THIS_SCRIPT."?archive=".$archive['did']);
		add_breadcrumb($lang->editcomment);
		if($mybb->settings['downloads_showeditor'] == 1)
		{
			$codebuttons = build_mycode_inserter();
		}
		$lang->edicomentsby = $lang->sprintf($lang->edicomentsby, $archive['name']);
		eval("\$comments_edit_form = \"".$templates->get("downloads_comments_edit")."\";");
		output_page($comments_edit_form);
		return false;
	}
	elseif($mybb->input['manage'] == "delete")
	{
		$query_comments = $db->simple_select(TABLE.'comments', '*', 'dcid='.$mybb->input['dcid']);
		$comments = $db->fetch_array($query_comments);
		if(!$comments['dcid'])
		{
			error_no_permission();
		}
		if($mybb->user['uid'] != $comments['uid'])
		{
			$usergroup = $cache->read("usergroups");
			if($usergroup[$mybb->user['usergroup']]['canmodcp'] == 0)
			{
				error_no_permission();
			}
		}
		$db->query("DELETE FROM ".TABLE_PREFIX.TABLE."comments WHERE dcid='".intval($mybb->input['dcid'])."'");
		redirect("downloads.php?archive=".$comments['did'], $lang->deletecommentssuccess);
	}
	else
	{
		error_no_permission();
	}
}
elseif($mybb->input['category'])
{
	$category_id = intval($mybb->input['category']);
	$query_category = $db->simple_select(TABLE.'cat', '*', 'active="1" AND dcid="'.$category_id.'"');
	$category = $db->fetch_array($query_category);
	if(!$category['dcid'])
	{
		error_no_permission();
	}
	$query_counts = $db->simple_select('downloads', 'COUNT(did) AS dids', 'active="1" AND validate="0" AND category='.intval($category_id), array('limit' => 1));
	$quantity = $db->fetch_field($query_counts, "dids");
	$pagina = intval($mybb->input['page']);
	$perpage = $mybb->settings['downloads_paginationarchive'];
	if($pagina > 0)
	{
		$start = ($pagina - 1) * $perpage;
		$pages = $quantity / $perpage;
		$pages = ceil($pages);
		if($pagina > $pages || $pagina <= 0)
		{
			$start = 0;
			$pagina = 1;
		}
	}
	else
	{
		$start = 0;
		$pagina = 1;
	}
	$pageurl = "downloads.php?category=".$mybb->input['category'];
	$query_archives = $db->simple_select('downloads', '*', 'active="1" AND validate="0" AND category="'.$category_id.'"' ,array('order_by' => 'orden', 'order_dir' => 'ASC', 'limit' => "{$start}, {$perpage}"));
	while($archive = $db->fetch_array($query_archives))
	{
		if(!in_array($mybb->user['usergroup'],explode(",",$archive['groups'])))
		{
			if($archive['posts'] <= $mybb->user['postnum'] && $archive['threads'] <= $mybb->user['threads'] && $archive['reputation'] <= $mybb->user['reputation'] && $archive['timeonline'] <= $mybb->user['timeonline'])
			{
				$user = get_user($archive['uid']);
				$username = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
				$username = build_profile_link($username, $user['uid'], "_blank");
				$color = alt_trow();
				if($mybb->settings['downloads_showportada'] == 1)
				{
					$colspan = 5;
					$tcatimage = "<td class=\"tcat\" align=\"center\"><span class=\"smalltext\"><strong>{$lang->image}</strong></span></td>";
					list($width, $height) = @explode("x", $mybb->settings['downloads_portadacategory']);
					eval("\$archives_list .= \"".$templates->get("downloads_archives_list_front")."\";");
				}
				else
				{
					$colspan = 4;
					eval("\$archives_list .= \"".$templates->get("downloads_archives_list")."\";");
				}
			}
		}
	}
	if(!$archives_list)
	{	
		$archives_list = "<td class=\"trow1\" colspan=\"100\" align=\"center\">{$lang->dontdownloadtable}</td>";
	}
	if(!$colspan && !$tcatimage)
	{
		$colspan = 4;
		if($mybb->settings['downloads_showportada'] == 1)
		{
			$colspan = 5;
			$tcatimage = "<td class=\"tcat\" align=\"center\"><strong>{$lang->image}</strong></td>";
		}
	}
	$pagination = multipage($quantity, (int)$perpage, (int)$pagina, $pageurl);
	$lang->title_category = $lang->sprintf($lang->title_category, $category['name']);
	add_breadcrumb($lang->title_category);
	if($mybb->settings['downloads_usercreatedownloads'] == 1)
	{
		if(in_array($mybb->user['usergroup'],explode(",",$mybb->settings['downloads_groupscreatedownloads'])))
		{
			if($mybb->settings['downloads_postsrequest'] <= $mybb->user['postnum'] && $mybb->settings['downloads_threadsrequest'] <= $mybb->user['threads'] && $mybb->settings['downloads_reputationrequest'] <= $mybb->user['reputation'] && $mybb->settings['downloads_timeonlinerequest'] <= $mybb->user['timeonline'])
			{
				if($mybb->user['downloads'] < $mybb->settings['downloads_maxcreated'] || empty($mybb->settings['downloads_maxcreated']))
				{
					eval("\$newdownload = \"".$templates->get("downloads_newdownload_button")."\";");
				}
			}
		}
	}
	$lang->title_category = $lang->sprintf($lang->title_category, $category['name']);
	eval("\$category_view = \"".$templates->get("downloads_archives")."\";");
	output_page($category_view);
	return false;
}
elseif($mybb->input['archive'])
{
	$archive_id = intval($mybb->input['archive']);
	$query_archive = $db->simple_select('downloads', '*', 'active="1" AND validate="0" AND did="'.$archive_id.'"');
	$archive = $db->fetch_array($query_archive);
	$query_cat = $db->simple_select(TABLE."cat", '*', "dcid='{$archive['category']}'");
	$category = $db->fetch_array($query_cat);
	$lang->title_category = $lang->sprintf($lang->title_category, $category['name']);
	add_breadcrumb($lang->title_category, THIS_SCRIPT."?category=".$category['dcid']);
	add_breadcrumb($archive['name']);
	if(!$archive['did'])
	{
		error_no_permission();
	}
	if(in_array($mybb->user['usergroup'],explode(",",$archive['groups'])))
	{
		error_no_permission();
	}
	if($archive['posts'] > $mybb->user['postnum'] || $archive['threads'] > $mybb->user['threads'] || $archive['reputation'] > $mybb->user['reputation'] || $archive['timeonline'] > $mybb->user['timeonline'])
	{
		error_no_permission();
	}
	$user = get_user($archive['uid']);
	$username = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	$username = build_profile_link($username, $user['uid'], "_blank");
	$avatar = "<img src=\"".$user['avatar']."\" class=\"avatar_view\" alt=\"".$user['username']."\" title=\"".$user['username']."\" />";
	$time = my_date($mybb->settings['timeformat'], $archive['dateline']);
	$date = my_date($mybb->settings['dateformat'], $archive['dateline']);
	$register = my_date($mybb->settings['regdateformat'], $user['regdate']);
	$reputation = get_reputation($user['reputation'], $user['uid']);
	if($archive['pics'])
	{
		static $images_screenshots;
		$query_images = $db->simple_select(TABLE.'images', '*', 'did="'.$archive['did'].'"');
		while($image = $db->fetch_array($query_images))
		{
			$images_screenshots[$archive['did']][$image['orden']] = $image['image'];
		}
		for($i = 1; $i <= intval($archive['pics']); $i++) 
		{
			//resize image front
			list($width, $height) = @getimagesize($images_screenshots[$archive['did']][$i]);
			if($width && $height)
			{
				list($max_width, $max_height) = explode("x", my_strtolower($mybb->settings['downloads_sizeimages']));
				if($width > $max_width || $height > $max_height)
				{
					require_once MYBB_ROOT."inc/functions_image.php";
					$scaled_dimensions = scale_image($width, $height, $max_width, $max_height);
					$images_width_height = "width=\"{$scaled_dimensions['width']}\" height=\"{$scaled_dimensions['height']}\"";
				}
				else
				{
					$images_width_height = "width=\"{$width}\" height=\"{$height}\"";	
				}
			}
			$images .= "<img src=\"".$images_screenshots[$archive['did']][$i]."\" {$images_width_height}/>  ";
		}
		eval("\$screenshots = \"".$templates->get("downloads_archives_screenshots")."\";");
	}
	$db->update_query("downloads", array("views" => ++$archive['views']),"did=".$archive_id);
	
	//resize image front
	list($width, $height) = @getimagesize($archive['image']);
	if($width && $height)
	{
		list($max_width, $max_height) = explode("x", my_strtolower($mybb->settings['downloads_sizeportada']));
	 	if($width > $max_width || $height > $max_height)
		{
			require_once MYBB_ROOT."inc/functions_image.php";
			$scaled_dimensions = scale_image($width, $height, $max_width, $max_height);
			$front_width_height = "width=\"{$scaled_dimensions['width']}\" height=\"{$scaled_dimensions['height']}\"";
		}
		else
		{
			$front_width_height = "width=\"{$width}\" height=\"{$height}\"";	
		}
	}
	if($archive['comments'] == 1)
	{
		if($mybb->settings['downloads_showeditor'] == 1)
		{
			$codebuttons = build_mycode_inserter();
		}
		$query_counts = $db->simple_select(TABLE.'comments', 'COUNT(dcid) AS dcids', 'did='.intval($archive['did']), array('limit' => 1));
		$quantity = $db->fetch_field($query_counts, "dcids");
		$pagina = intval($mybb->input['page']);
		$perpage = 4;
		if($pagina > 0)
		{
			$start = ($pagina - 1) * $perpage;
			$pages = $quantity / $perpage;
			$pages = ceil($pages);
			if($pagina > $pages || $pagina <= 0)
			{
				$start = 0;
				$pagina = 1;
			}
		}
		else
		{
			$start = 0;
			$pagina = 1;
		}
		$pageurl = "downloads.php?archive=".$archive['did'];
		$query_comments = $db->simple_select(TABLE.'comments', '*', 'did="'.$archive['did'].'"',array('order_by' => 'dcid', 'order_dir' => 'DESC', 'limit' => "{$start}, {$perpage}"));
		while($comment = $db->fetch_array($query_comments))
		{
			$color = alt_trow();
			$user = get_user($comment['uid']);
			$username = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
			$username = build_profile_link($username, $user['uid'], "_blank");
			$time = my_date($mybb->settings['timeformat'], $comment['dateline']);
			$date = my_date($mybb->settings['dateformat'], $comment['dateline']);
			$user['avatar'] = (!empty($user['avatar'])) ? $user['avatar'] : "images/avatars/invalid_url.gif";
			$text = parse_text($comment['comment'],$mybb->settings['downloads_canhtmlcomments'], $mybb->settings['downloads_canbbcodecomments'], $mybb->settings['downloads_cansmiliescomments'], $mybb->settings['downloads_canimgcomments'], $mybb->settings['downloads_canbadwordcomments']);
			$usergroup = $cache->read("usergroups");
			if($usergroup[$mybb->user['usergroup']]['canmodcp'] == 1 || $mybb->user['uid'] == $comment['uid'])
			{
				eval("\$comment_manage = \"".$templates->get("downloads_archives_comments_moderation")."\";");
			}
			eval("\$comments_list .= \"".$templates->get("downloads_archives_comments_list")."\";");
			unset($comment_manage);
		}
		$pagination = multipage($quantity, (int)$perpage, (int)$pagina, $pageurl);
		$lang->edicomentsby = $lang->sprintf($lang->edicomentsby, $archive['name']);
		eval("\$comments = \"".$templates->get("downloads_archives_comments")."\";");
	}
	if($archive['bbcode'] == 1)
	{
		$archive['description'] = parse_text($archive['description'],0,1,1,1,1);
	}
	$query_links = $db->simple_select(TABLE.'urls', '*', 'did="'.$archive['did'].'"',array('order_by' => 'duid', 'order_dir' => 'ASC'));
	while($link = $db->fetch_array($query_links))
	{
		eval("\$downloadslinks .= \"".$templates->get("downloads_archives_links")."\";");
	}
	eval("\$archives_page = \"".$templates->get("downloads_archives_view")."\";");
	output_page($archives_page);
}
else
{
	$query_counts = $db->simple_select(TABLE.'cat', 'COUNT(dcid) AS dcids', 'active="1"', array('limit' => 1));
	$quantity = $db->fetch_field($query_counts, "dcids");
	$pagina = intval($mybb->input['page']);
	$perpage = $mybb->settings['downloads_paginationcategorys'];
	if($pagina > 0)
	{
		$start = ($pagina - 1) * $perpage;
		$pages = $quantity / $perpage;
		$pages = ceil($pages);
		if($pagina > $pages || $pagina <= 0)
		{
			$start = 0;
			$pagina = 1;
		}
	}
	else
	{
		$start = 0;
		$pagina = 1;
	}
	$pageurl = "downloads.php";
	$query = $db->simple_select(TABLE.'cat', '*', 'active="1"',array('order_by' => 'orden', 'order_dir' => 'ASC', 'limit' => "{$start}, {$perpage}"));
	while($category = $db->fetch_array($query))
	{
		$color = alt_trow();
		$qnthread = $db->simple_select('downloads', 'COUNT(did) AS dids', 'category="'.$category['dcid'].'"', array('limit' => 1));
		$numthreads = $db->fetch_field($qnthread, "dids");
		$qdown = $db->simple_select('downloads', '*', 'active="1" and validate="0" and category="'.$category['dcid'].'"', array('order_by' => 'did', 'order_dir' => 'DESC'));
		$download = $db->fetch_array($qdown);
		$countn = strlen($download['name']);
		if(intval($countn) > 24)
		{
			$lastdownload = substr($download['name'], 0, 25)."...";
		}
		else
		{
			$lastdownload = $download['name'];
		}
		$time = my_date($mybb->settings['timeformat'], $download['dateline']);
		$date = my_date($mybb->settings['dateformat'], $download['dateline']);
		$user = get_user($download['uid']);
		$username = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
		$username = build_profile_link($username, $user['uid'], "_blank");
		if(!$download['did'])
		{
			$lastdownloadbox .= "<td class=\"".$color."\" align=\"center\" width=\"22%\">".$lang->never."</td>";
		}
		else
		{
			eval("\$lastdownloadbox .= \"".$templates->get("downloads_category_lastdownload")."\";");
		}
		eval("\$categorys .= \"".$templates->get("downloads_category")."\";");
		unset($lastdownloadbox);
	}
	if(!$categorys)
	{
		$categorys = "<td class='trow1' align='center' colspan='100'>{$lang->dontcategorystable}</td>";
	}
	$pagination = multipage($quantity, (int)$perpage, (int)$pagina, $pageurl);
	eval("\$page = \"".$templates->get("downloads_home")."\";");
	output_page($page);
}

function parse_text($text,$html,$bbcode,$smilies)
{
	global $mybb;
	require_once MYBB_ROOT."inc/class_parser.php";
	$parser = new postParser;
	$parser_options = array(
			'allow_html' => $html,
			'allow_mycode' => $mybb->settings['downloads_canbbcodecomments'],
			'allow_smilies' => $mybb->settings['downloads_cansmiliescomments'],
			'allow_imgcode' => $mybb->settings['downloads_canimgcomments'],
			'filter_badwords' => $mybb->settings['downloads_canbadwordcomments']
		);
	$text = $parser->parse_message($text, $parser_options);
	return $text;
}

exit;
?>	