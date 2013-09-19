<?php

/**
 * MyBB 1.6 Spanish Language Pack
 * Copyright 2011 Edson Ordaz, All Rights Reserved
 * 
 * validate V2.0
 *
 * $Id: validate.php 2011-07-11 Edson Ordaz $
 */
 
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$lang->load('downloads');

$page->add_breadcrumb_item($lang->downloads, 'index.php?module=downloads');
$page->add_breadcrumb_item($lang->validatedownloads);
$page->output_header($lang->validatedownloads);

$validatecache = $cache->read("downloads_validate");
$update_cache = array(
	"name" => "Validate",
	"code" => 0
);
$cache->update("downloads_validate", $update_cache);

if(!$mybb->input['action'])
{
	$queryq = $db->simple_select('downloads', 'COUNT(did) AS dids', '', array('limit' => 1));
	$quantity = $db->fetch_field($queryq, "dids");
	$pagina = intval($mybb->input['page']);
	$perpage = 10;
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
	$pageurl = "index.php?module=downloads/validate";
	$table = new Table;
	$table->construct_header($lang->namearchive);
	$table->construct_header($lang->author, array("width" => "10%","class" => "align_center"));
	$table->construct_header($lang->images, array("width" => "10%","class" => "align_center"));
	$table->construct_header($lang->orden, array("width" => "10%","class" => "align_center"));
	$table->construct_header($lang->options, array("width" => "10%","class" => "align_center"));
	$table->construct_row();
	
	$query1 = $db->simple_select("downloads", "*", "validate='1'",array('order_by' => 'orden', 'order_dir' => 'ASC', 'limit' => $start.','.$perpage));
	while($downloads = $db->fetch_array($query1))
	{
		$lang->deletepopup = $lang->sprintf($lang->deletepop, $downloads['name']);
		$user = get_user($downloads['uid']);
		$username = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
		$username = build_profile_link($username, $user['uid'], "_blank");
		$table->construct_cell("<a href=\"index.php?module=downloads/validate&action=details&amp;did=".$downloads['did']."\"><strong>".$downloads[name]."</strong></a>");
		$table->construct_cell($username,array("class" => "align_center"));
		$table->construct_cell($downloads['pics'],array("class" => "align_center"));
		$table->construct_cell("<input type=\"text\" value=\"".$downloads['orden']."\" readonly='readonly' class=\"text_input align_center\" style=\"width: 80%; font-weight: bold;\" />", array("class" => "align_center"));
		$popup = new PopupMenu("did_{$downloads['did']}", $lang->options);
		$popup->add_item($lang->viewdetails, "index.php?module=downloads/validate&action=details&amp;did=".$downloads['did']);
		$popup->add_item($lang->delete, "index.php?module=downloads/validate&action=delete&amp;did={$downloads['did']}&my_post_key={$mybb->post_code}\" target=\"_self\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->deletepopup}')");
		$popup->add_item($lang->validate, "index.php?module=downloads/validate&action=validate&amp;did={$downloads['did']}&my_post_key={$mybb->post_code}\" target=\"_self");
		$popup->add_item($lang->viewimages, "index.php?module=downloads/validate&action=viewimages&amp;did={$downloads['did']}&my_post_key={$mybb->post_code}\" target=\"_self");
		$popup->add_item($lang->viewlinks, "index.php?module=downloads/validate&action=viewlinks&amp;did={$downloads['did']}&my_post_key={$mybb->post_code}\" target=\"_self");
		$Popuss = $popup->fetch();
		$table->construct_cell($Popuss, array('class' => 'align_center'));
		$table->construct_row();
	}
	if($table->num_rows() == 1)
	{
		$table->construct_cell($lang->emptytabledownloadsvalidate, array('colspan' => 5, 'class' => 'align_center'));
		$table->construct_row();
	}
	$table->output($lang->downloads);
	echo multipage($quantity, (int)$perpage, (int)$pagina, $pageurl);
}
elseif($mybb->input['action'] == "details")
{
	if($mybb->request_method == "post")
	{
		if(strlen($mybb->input['name']) < 2)
		{
			$error[] = $lang->namearchshort;
		}
		if(strlen($mybb->input['shortdesc']) < 5)
		{
			$error[] = $lang->desarchshortdesc;
		}
		if(strlen($mybb->input['description']) < 10)
		{
			$error[] = $lang->desarchshort;
		}
		if(empty($mybb->input['image']))
		{
			$error[] = $lang->portadaempty;
		}
		if(strlen($mybb->input['url']) < 5)
		{
			$error[] = $lang->urlarchshort;
		}
		if(empty($mybb->input['category']))
		{
			$error[] = $lang->notcategoryselect;
		}
		if(!$error)
		{
			if(!$mybb->input['groups'])
			{
				$insert_groups_view = 0;
			}else{
				$insert_groups_view = implode(",",$mybb->input['groups']);
			}
			$insert = array(
				"name" => $mybb->input['name'],
				"orden" => intval($mybb->input['orden']),
				"shortdesc" => $mybb->input['shortdesc'],
				"description" => $mybb->input['description'],
				"image" => $mybb->input['image'],
				"comments" => intval($mybb->input['comments']),
				"dateline" => TIME_NOW,
				"url" => $mybb->input['url'],
				"active" => $mybb->input['active'],
				"groups" => $insert_groups_view,
				"category" => intval($mybb->input['category'])
			);
			$db->update_query("downloads", $insert,"did=".$mybb->input['did']);
			flash_message($lang->editarchivesuccess, 'success');
			admin_redirect("index.php?module=downloads/validate");
		}
	}
	if($error)
	{
		$page->output_inline_error($error);
	}
	$query = $db->simple_select("downloads", "*", "did=".$mybb->input['did']);
	$download = $db->fetch_array($query);
	if($mybb->input['name'])
	{
		$name = $mybb->input['name'];
	}else{
		$name = $download['name'];
	}
	if($mybb->input['description'])
	{
		$description = $mybb->input['description'];
	}else{
		$description = $download['description'];
	}
	if($mybb->input['shortdesc'])
	{
		$shortdesc = $mybb->input['shortdesc'];
	}else{
		$shortdesc = $download['shortdesc'];
	}
	if($mybb->input['image'])
	{
		$image = $mybb->input['image'];
	}else{
		$image = $download['image'];
	}
	if($mybb->input['comments'])
	{
		$comments = $mybb->input['comments'];
	}else{
		$comments = $download['comments'];
	}
	if($mybb->input['url'])
	{
		$url = $mybb->input['url'];
	}else{
		$url = $download['url'];
	}
	if($mybb->input['orden'])
	{
		$orden = $mybb->input['orden'];
	}else{
		$orden = $download['orden'];
	}
	if($mybb->input['active'])
	{
		$active = $mybb->input['active'];
	}else{
		$active = $download['active'];
	}
	$category_select = "<select name=\"category\" style=\"width: 160px;\" size=\"5\">";
	$query_category = $db->simple_select("downloads_cat", "*", "");
	while($category = $db->fetch_array($query_category))
	{
		$select_add = '';
		if($category['dcid'] == $download['category'])
		{
			$select_add = " selected=\"selected\""; 
		}
		$category_select .= "<option value=\"{$category['dcid']}\"{$select_add}>{$category['name']}</option>";
	}
	$category_select .= "</select>";
	$groups = explode(",",$download['groups']);
	$groups_selected = array_map(intval,$groups);
	$form = new Form("index.php?module=downloads/validate&action=details", "post");
	echo $form->generate_hidden_field("did", $download[did]);
	$form_container = new FormContainer($lang->edit." ".$download['name']);
	$form_container->output_row($lang->name."<em>*</em>",$lang->name_des, $form->generate_text_box('name',$name, array('id' => 'name')), 'name');
	$form_container->output_row($lang->shortdescription."<em>*</em>", $lang->shortdescriptiondes, $form->generate_text_box('shortdesc',$shortdesc,array('id' => 'shortdesc',)), 'shortdesc');
	$form_container->output_row($lang->description."<em>*</em>",$lang->descriptiondes, $form->generate_text_area('description',$description,array('id' => 'description','class'=>'codepress mybb','style'=>'width:100%;height:200px;')), 'description');
	$form_container->output_row($lang->portada."<em>*</em>",$lang->portadades, $form->generate_text_box('image',$image, array('id' => 'image')), 'image');
	$form_container->output_row($lang->comments."<em>*</em>",$lang->commentsdes, $form->generate_yes_no_radio('comments',$comments, array('id' => 'comments')), 'comments');
	$form_container->output_row($lang->urlarchive."<em>*</em>",$lang->urlarchivedes, $form->generate_text_box('url',$url, array('id' => 'url')), 'url');
	$form_container->output_row($lang->orden."<em>*</em>",$lang->ordendes, $form->generate_text_box('orden',$orden, array('id' => 'orden')), 'orden');
	$form_container->output_row($lang->active."<em>*</em>",$lang->activedes, $form->generate_yes_no_radio('active',$active, array('id' => 'active')), 'active');
	$form_container->output_row($lang->groupsuser."<em>*</em>",$lang->groupsuserdes, $form->generate_group_select('groups[]',$groups_selected, array('id' => 'groups[]', 'size' => 6, 'multiple' => 'multiple')), 'groups');
	$form_container->output_row($lang->categorys."<em>*</em>", $lang->categorysdesnew, $category_select);
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->save);
	$buttons[] = $form->generate_reset_button($lang->reset);
	$form->output_submit_wrapper($buttons);
	$form->end();
}
elseif($mybb->input['action'] == "delete")
{
	$query_diids = $db->query("
		SELECT i.diid
		FROM ".TABLE_PREFIX."downloads_images i
		LEFT JOIN ".TABLE_PREFIX."downloads d ON (d.did=i.did)
		WHERE i.did='".$mybb->input['did']."'
	");
	$query_duids = $db->query("
		SELECT i.duid
		FROM ".TABLE_PREFIX."downloads_urls i
		LEFT JOIN ".TABLE_PREFIX."downloads d ON (d.did=i.did)
		WHERE i.did='".$mybb->input['did']."'
	");
	$query_comment = $db->query("
		SELECT c.dcid
		FROM ".TABLE_PREFIX."downloads_comments c
		LEFT JOIN ".TABLE_PREFIX."downloads d ON (d.did=c.did)
		WHERE c.did='".$mybb->input['did']."'
	");
	$dids = array();
	$dcid = array();
	$duid = array();
	while($downloads = $db->fetch_array($query_diids))
	{
		$dids[] = $downloads['diid'];
	}
	while($comment = $db->fetch_array($query_comment))
	{
		$dcid[] = $comment['dcid'];
	}
	while($links = $db->fetch_array($query_duids))
	{
		$duid[] = $links['duid'];
	}
	if($dids)
	{
		$dids = implode(',', $dids);
		$db->delete_query("downloads_images", "diid IN ($dids)");
	}
	if($dcid)
	{
		$dcid = implode(',', $dcid);
		$db->delete_query("downloads_comments", "dcid IN ($dcid)");
	}
	if($duid)
	{
		$duid = implode(',', $duid);
		$db->delete_query("downloads_urls", "duid IN ($duid)");
	}
	$query = $db->simple_select("downloads", "*", "did=".$mybb->input['did']);
	$download = $db->fetch_array($query);
	$lang->succesdelete = $lang->sprintf($lang->deletesuccessdownload, $download['name']);
	$db->query("DELETE FROM ".TABLE_PREFIX."downloads WHERE did='".intval($mybb->input['did'])."'");
	$db->free_result($query);
	flash_message($lang->succesdelete, 'success');
	admin_redirect("index.php?module=downloads/validate");
}
elseif($mybb->input['action'] == "validate")
{
	$validateinsert = array(
		"validate" => 0
	);
	$db->update_query("downloads", $validateinsert,"did=".$mybb->input['did']);
	$query = $db->simple_select("downloads", "*", "did=".$mybb->input['did']);
	$download = $db->fetch_array($query);
	$usuario = get_user($download['uid']);
	$edit_user = array(
		"downloads" => intval(++$usuario['downloads'])
	);
	$db->update_query("users", $edit_user,"uid=".$usuario['uid']);
	$lang->yesvalidate = $lang->sprintf($lang->successvalidate, $download['name']);
	flash_message($lang->yesvalidate, 'success');
	admin_redirect("index.php?module=downloads/validate");
}
elseif($mybb->input['action'] == "viewimages")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->notpostcode, 'error');
		admin_redirect("index.php?module=downloads/archives");
	}
	if($mybb->request_method == "post")
	{
		if($mybb->input['return'])
		{
			admin_redirect("index.php?module=downloads/validate");
		}
		elseif($mybb->input['editing'])
		{
			$query = $db->simple_select("downloads", "*", "did=".$mybb->input['did']);
			$download = $db->fetch_array($query);
			$num = intval($mybb->input['images_num']);
			if(empty($num) || $num < 1)
			{
				flash_message($lang->emptyimagesedit, 'error');
				admin_redirect("index.php?module=downloads/archives");
			}
			$lang->imagesofdownload = $lang->sprintf($lang->imgsofdownload, $download['name']);
			$form = new Form("index.php?module=downloads/validate&action=viewimages", "post");
			echo $form->generate_hidden_field("did", $download['did']);
			echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
			echo $form->generate_hidden_field("images_num", $num);
			$form_container = new FormContainer($lang->imagesofdownload);
			for($i = 1; ; $i++) 
			{
				if ($i > $num) 
				{
					break;
				}
				$queryd = $db->simple_select('downloads_images', '*', 'did="'.$download['did'].'" AND orden='.$i);
				while($form_edit = $db->fetch_array($queryd))
				{
					$lang->imagenumerby = $lang->sprintf($lang->imagenumer, $i);
					$lang->imagenumerbydes = $lang->sprintf($lang->imagenumerdes, $i);
					$form_container->output_row($lang->imagenumerby,$lang->imagenumerbydes, $form->generate_text_box('image_'.$i,$form_edit['image'], array('id' => 'image_'.$i)), 'image_'.$i);
				}
			}
			$form_container->end();
			$buttons[] = $form->generate_submit_button($lang->editimages, array("name" => "saveimages"));
			$form->output_submit_wrapper($buttons);
			$form->end();
			$page->output_footer();
		}
		elseif($mybb->input['saveimages'])
		{
			for($i = 1; ; $i++) 
			{
				if($i > intval($mybb->input['images_num'])) 
				{
					break;
				}
				$lang->shortpathimage = $lang->sprintf($lang->shortpathimg, $i);
				if(strlen($mybb->input['image_'.$i]) < 4)
				{
					flash_message($lang->shortpathimage, 'error');
					admin_redirect("index.php?module=downloads/validate");
				}
			}
			for($i = 1; ; $i++) 
			{
				if($i > intval($mybb->input['images_num'])) 
				{
					break;
				}
				$edit_images = array(
					"image" => $mybb->input['image_'.$i]
				);
				$db->update_query("downloads_images", $edit_images,"did='".$mybb->input['did']."' AND orden='".$i."'");
			}
			flash_message($lang->editedimages, 'success');
			admin_redirect("index.php?module=downloads/validate");
		}
	}
	$query = $db->simple_select("downloads", "*", "did=".$mybb->input['did']);
	$download = $db->fetch_array($query);
	$num = intval($download['pics']);
	if(empty($num) || $num < 1)
	{
		flash_message($lang->emptyimagesview, 'error');
		admin_redirect("index.php?module=downloads/validate");
	}
	$lang->imagesofdownload = $lang->sprintf($lang->imgsofdownload, $download['name']);
	$form = new Form("index.php?module=downloads/validate&action=viewimages", "post");
	echo $form->generate_hidden_field("did", $download['did']);
	echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
	echo $form->generate_hidden_field("images_num", $num);
	$form_container = new FormContainer($lang->imagesofdownload);
	for($i = 1; ; $i++) 
	{
		if ($i > $num) 
		{
			break;
		}
		$queryd = $db->simple_select('downloads_images', '*', 'did="'.$download['did'].'" AND orden='.$i);
		while($form_edit = $db->fetch_array($queryd))
		{
			list($width, $height) = @getimagesize($form_edit['image']);
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
			$lang->imagenumerby = $lang->sprintf($lang->imagenumer, $i);
			$form_container->output_row($lang->imagenumerby,'', "<img src='{$form_edit['image']}' {$images_width_height}/>");
		}
	}
	$form_container->end();
	$buttons[] = $form->generate_submit_button($lang->editimages, array("name" => "editing"));
	$buttons[] = $form->generate_submit_button($lang->back, array('name' => 'return'));
	$form->output_submit_wrapper($buttons);
	$form->end();
}
elseif($mybb->input['action'] == "viewlinks")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->notpostcode, 'error');
		admin_redirect("index.php?module=downloads/archives");
	}
	if($mybb->request_method == "post")
	{
		if($mybb->input['return'])
		{
			admin_redirect("index.php?module=downloads/validate");
		}
		elseif($mybb->input['editing'])
		{
			$query = $db->simple_select("downloads", "*", "did=".$mybb->input['did']);
			$download = $db->fetch_array($query);
			$num = intval($mybb->input['links_num']);
			if(empty($num) || $num < 1)
			{
				flash_message($lang->emptylinksedit, 'error');
				admin_redirect("index.php?module=downloads/archives");
			}
			$lang->linksofdownloadprin = $lang->sprintf($lang->linksofdownload, $download['name']);
			$form = new Form("index.php?module=downloads/validate&action=viewlinks", "post");
			echo $form->generate_hidden_field("did", $download['did']);
			echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
			echo $form->generate_hidden_field("links_num", $num);
			$form_container = new FormContainer($lang->linksofdownloadprin);
			for($i = 1; ; $i++) 
			{
				if ($i > $num) 
				{
					break;
				}
				$queryd = $db->simple_select('downloads_urls', '*', 'did="'.$download['did'].'" AND orden='.$i);
				while($form_edit = $db->fetch_array($queryd))
				{
					$lang->linknumberby = $lang->sprintf($lang->linknumber, $i);
					$lang->linknumerdesdescr = $lang->sprintf($lang->linknumerdes, $i);
					$form_container->output_row($lang->linknumberby,$lang->linknumerdesdescr, $form->generate_text_box('url_'.$i,$form_edit['url'], array('id' => 'url_'.$i)), 'url_'.$i);
				}
			}
			$form_container->end();
			$buttons[] = $form->generate_submit_button($lang->editlinks, array("name" => "savelinks"));
			$form->output_submit_wrapper($buttons);
			$form->end();
			$page->output_footer();
		}
		elseif($mybb->input['savelinks'])
		{
			for($i = 1; ; $i++) 
			{
				if($i > intval($mybb->input['links_num'])) 
				{
					break;
				}
				$lang->shortpathlinkshort = $lang->sprintf($lang->shortpathlink, $i);
				if(strlen($mybb->input['url_'.$i]) < 4)
				{
					flash_message($lang->shortpathlinkshort, 'error');
					admin_redirect("index.php?module=downloads/validate");
				}
			}
			for($i = 1; ; $i++) 
			{
				if($i > intval($mybb->input['links_num'])) 
				{
					break;
				}
				$edit_links = array(
					"url" => $mybb->input['url_'.$i]
				);
				$db->update_query("downloads_urls", $edit_links,"did='".$mybb->input['did']."' AND orden='".$i."'");
			}
			flash_message($lang->editedlinks, 'success');
			admin_redirect("index.php?module=downloads/validate");
		}
	}
	$query = $db->simple_select("downloads", "*", "did=".$mybb->input['did']);
	$download = $db->fetch_array($query);
	$num = intval($download['urls']);
	if(empty($num) || $num < 1)
	{
		flash_message($lang->emptylinksview, 'error');
		admin_redirect("index.php?module=downloads/validate");
	}
	$lang->linksofdownloadpri = $lang->sprintf($lang->linksofdownload, $download['name']);
	$form = new Form("index.php?module=downloads/validate&action=viewlinks", "post");
	echo $form->generate_hidden_field("did", $download['did']);
	echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
	echo $form->generate_hidden_field("links_num", $num);
	$form_container = new FormContainer($lang->linksofdownloadpri);
	for($i = 1; ; $i++) 
	{
		if ($i > $num) 
		{
			break;
		}
		$queryd = $db->simple_select('downloads_urls', '*', 'did="'.$download['did'].'" AND orden='.$i);
		while($form_edit = $db->fetch_array($queryd))
		{
			$lang->linknumberby = $lang->sprintf($lang->linknumber, $i);
			$form_container->output_row($lang->linknumberby,'', "<a href='{$form_edit['url']}' target='_blank'/>{$form_edit['url']}</a>");
		}
	}
	$form_container->end();
	$buttons[] = $form->generate_submit_button($lang->editlinks, array("name" => "editing"));
	$buttons[] = $form->generate_submit_button($lang->back, array('name' => 'return'));
	$form->output_submit_wrapper($buttons);
	$form->end();
}
$page->output_footer();
exit;

?>
