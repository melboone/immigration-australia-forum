<?php

/**
 * MyBB 1.6 Spanish Language Pack
 * Copyright 2011 Edson Ordaz, All Rights Reserved
 * 
 * downloads V2.0
 *
 * $Id: download.php 2011-07-11 Edson Ordaz $
 */
 
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$lang->load('downloads');

$page->add_breadcrumb_item($lang->downloads, 'index.php?module=downloads/archives');
if($mybb->input['action'] == "edit")
{
	$page->add_breadcrumb_item($lang->editdownloads);
}
elseif($mybb->input['action'] == "images")
{
	$query = $db->simple_select("downloads", "*", "did=".$mybb->input['did']);
	$download = $db->fetch_array($query);
	$page->add_breadcrumb_item($download['name'], "index.php?module=downloads/archives&action=edit&did=".$mybb->input['did']);
	$page->add_breadcrumb_item($lang->edit, "index.php?module=downloads/archives&action=edit&did=".$mybb->input['did']);
	$page->add_breadcrumb_item($lang->editimages);
}
elseif($mybb->input['action'] == "links")
{
	$query = $db->simple_select("downloads", "*", "did=".$mybb->input['did']);
	$download = $db->fetch_array($query);
	$page->add_breadcrumb_item($download['name'], "index.php?module=downloads/archives&action=edit&did=".$mybb->input['did']);
	$page->add_breadcrumb_item($lang->edit, "index.php?module=downloads/archives&action=edit&did=".$mybb->input['did']);
	$page->add_breadcrumb_item($lang->editlinks);
}

$page->output_header($lang->downloads);
$tabs["downloads"] = array(
	'title' => $lang->downloads,
	'link' => "index.php?module=downloads/archives",
	'description' => $lang->tab_down1_des
);
$tabs["new"] = array(
	'title' => $lang->newarchive,
	'link' => "index.php?module=downloads/archives&amp;action=new",
	'description' => $lang->tab_down2_des
);
	
switch($mybb->input['action'])
{
	case 'downloads':
		$page->output_nav_tabs($tabs, 'downloads');
	break;
	case 'new':
		$page->output_nav_tabs($tabs, 'new');
	break;
	default:
		$page->output_nav_tabs($tabs, 'downloads');
}

if(!$mybb->input['action'] && !$mybb->input['customimages'] && !$mybb->input['links']) 
{	
	$queryq = $db->simple_select('downloads_cat', 'COUNT(dcid) AS dids', '', array('limit' => 1));
	$quantity = $db->fetch_field($queryq, "dids");
	$pagina = intval($mybb->input['page']);
	$perpage = 2;
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
	$pageurl = "index.php?module=downloads/archives";
	$table = new Table;
	$table->construct_header($lang->namearchive);
	$table->construct_header($lang->images, array("width" => "10%","class" => "align_center"));
	$table->construct_header($lang->active, array("width" => "10%","class" => "align_center"));
	$table->construct_header($lang->orden, array("width" => "10%","class" => "align_center"));
	$table->construct_header($lang->options, array("width" => "10%","class" => "align_center"));
	$table->construct_row();
	
	
	$query = $db->query('SELECT * FROM '.TABLE_PREFIX.'downloads_cat ORDER BY orden ASC LIMIT '.$start.', '.$perpage);
	while($category = $db->fetch_array($query))
	{
		$table->construct_cell("<a href='index.php?module=downloads/category'><font color=black><b>".$lang->category.":</b></font><font color=red> ".$category['name']."</font></a>",array("colspan" => "5"));
		$table->construct_row();
		$query1 = $db->query('SELECT * FROM '.TABLE_PREFIX.'downloads WHERE validate="0" AND category="'.$category['dcid'].'" ORDER BY orden ASC');
		while($downloads = $db->fetch_array($query1))
		{
			if($downloads['active'] == 1)
			{
				$state = "tick.gif";
				$title = $lang->activada;
				$mod = "0";
				$popup_state = $lang->desactivar;
			}else{
				$state = "cross.gif";
				$title = $lang->desactivada;
				$mod = "1";
				$popup_state = $lang->activar;
			}
			$lang->deletepopup = $lang->sprintf($lang->deletepop, $downloads['name']);
			$table->construct_cell("<a href=\"index.php?module=downloads/archives&action=edit&amp;did=".$downloads['did']."\"><strong>".$downloads[name]."</strong></a>");
			$table->construct_cell($downloads['pics'],array("class" => "align_center"));
			$table->construct_cell("<a href=\"index.php?module=downloads/archives&action=activate&state=".$mod."&did=".$downloads['did']."&my_post_key={$mybb->post_code}\"><img src=\"styles/default/images/icons/".$state."\" title=\"".$title."\" /></a>",array("class" => "align_center"));
			$table->construct_cell("<input type=\"text\" value=\"".$downloads['orden']."\" readonly='readonly' class=\"text_input align_center\" style=\"width: 80%; font-weight: bold;\" />", array("class" => "align_center"));
			$popup = new PopupMenu("did_{$downloads['did']}", $lang->options);
			$popup->add_item($lang->edit, "index.php?module=downloads/archives&action=edit&amp;did=".$downloads['did']);
			$popup->add_item($lang->delete, "index.php?module=downloads/archives&action=delete&amp;did={$downloads['did']}&my_post_key={$mybb->post_code}\" target=\"_self\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->deletepopup}')");
			$popup->add_item($popup_state." ".$lang->download, "index.php?module=downloads/archives&action=activate&state=".$mod."&did=".$downloads['did']."&my_post_key={$mybb->post_code}\" target=\"_self");
			$popup->add_item($lang->editlinks, "index.php?module=downloads/archives&action=links&did=".$downloads['did']."&my_post_key={$mybb->post_code}\" target=\"_self");
			$popup->add_item($lang->editimages, "index.php?module=downloads/archives&action=images&did=".$downloads['did']."&my_post_key={$mybb->post_code}\" target=\"_self");
			$popup->add_item($lang->deleteimages, "index.php?module=downloads/archives&action=deleteimages&did=".$downloads['did']."&my_post_key={$mybb->post_code}\" target=\"_self");
			$Popuss = $popup->fetch();
			$table->construct_cell($Popuss, array('class' => 'align_center'));
			$table->construct_row();
		}
	}
	if($table->num_rows() == 1)
	{
		$table->construct_cell($lang->emptytabledownloads, array('colspan' => 5, 'class' => 'align_center'));
		$table->construct_row();
	}
	$table->output($lang->downloads);
	echo multipage($quantity, (int)$perpage, (int)$pagina, $pageurl);
}	
elseif($mybb->input['action'] == "delete")
{
	$query_diids = $db->query("
		SELECT i.diid
		FROM ".TABLE_PREFIX."downloads_images i
		LEFT JOIN ".TABLE_PREFIX."downloads d ON (d.did=i.did)
		WHERE i.did='".$mybb->input['did']."'
	");
	$query_links = $db->query("
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
	while($links = $db->fetch_array($query_links))
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
	if($mybb->settings['downloads_counthreads'] == 1)
	{
		$edit_user = array(
			"postnum" => intval(--$mybb->user['postnum']),
			"threads" => intval(--$mybb->user['threads']),
			"downloads" => intval(--$mybb->user['downloads'])
		);
	}	
	else
	{
		$edit_user = array(
			"downloads" => intval(--$mybb->user['downloads'])
		);
	}
	$db->update_query("users", $edit_user,"uid=".$download['uid']);
	$lang->succesdelete = $lang->sprintf($lang->deletesuccessdownload, $download['name']);
	$db->query("DELETE FROM ".TABLE_PREFIX."downloads WHERE did='".intval($mybb->input['did'])."'");
	$db->free_result($query);
	flash_message($lang->succesdelete, 'success');
	admin_redirect("index.php?module=downloads/archives");
}
elseif($mybb->input['action'] == "edit")
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
				"name" => $db->escape_string($mybb->input['name']),
				"orden" => intval($mybb->input['orden']),
				"shortdesc" => $db->escape_string($mybb->input['shortdesc']),
				"description" => $db->escape_string($mybb->input['description']),
				"image" => $db->escape_string($mybb->input['image']),
				"comments" => intval($mybb->input['comments']),
				"dateline" => TIME_NOW,
				"active" => $mybb->input['active'],
				"groups" => $insert_groups_view,
				"category" => intval($mybb->input['category'])
			);
			$db->update_query("downloads", $insert,"did=".$mybb->input['did']);
			flash_message($lang->editarchivesuccess, 'success');
			admin_redirect("index.php?module=downloads/archives");
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
	$form = new Form("index.php?module=downloads/archives&action=edit", "post");
	echo $form->generate_hidden_field("did", $download[did]);
	$form_container = new FormContainer($lang->edit." ".$download['name']);
	$form_container->output_row($lang->name."<em>*</em>",$lang->name_des, $form->generate_text_box('name',$name, array('id' => 'name')), 'name');
	$form_container->output_row($lang->shortdescription."<em>*</em>", $lang->shortdescriptiondes, $form->generate_text_box('shortdesc',$shortdesc,array('id' => 'shortdesc',)), 'shortdesc');
	$form_container->output_row($lang->description."<em>*</em>",$lang->descriptiondes, $form->generate_text_area('description',$description,array('id' => 'description','class'=>'codepress mybb','style'=>'width:100%;height:200px;')), 'description');
	$form_container->output_row($lang->portada."<em>*</em>",$lang->portadades, $form->generate_text_box('image',$image, array('id' => 'image')), 'image');
	$form_container->output_row($lang->comments."<em>*</em>",$lang->commentsdes, $form->generate_yes_no_radio('comments',$comments, array('id' => 'comments')), 'comments');
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
elseif($mybb->input['action'] == "activate")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->notpostcode, 'error');
		admin_redirect("index.php?module=downloads/archives");
	}
	$state = intval($mybb->input['state']);
	$edit_update = array(
		"active" => $state
	);
	$db->update_query("downloads", $edit_update,"did=".$mybb->input['did']);
	$query = $db->simple_select("downloads", "*", "did=".$mybb->input['did']);
	$download = $db->fetch_array($query);
	if($state == 1)
	{
		$text = $lang->activado;
	}else{
		$text = $lang->desactivado;
	}
	$lang->deletedownload = $lang->sprintf($lang->deletdownloadssuccess, $text, $download['name']);
	flash_message($lang->deletedownload, 'success');
	admin_redirect("index.php?module=downloads/archives");
}
elseif($mybb->input['action'] == "images")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->notpostcode, 'error');
		admin_redirect("index.php?module=downloads/archives");
	}
	if($mybb->request_method == "post")
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
				$error[] = $lang->shortpathimage;
			}
		}
		if(!$error)
		{
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
			admin_redirect("index.php?module=downloads/archives");
		}
	}
	$query = $db->simple_select("downloads", "*", "did=".$mybb->input['did']);
	$download = $db->fetch_array($query);
	$num = intval($download['pics']);
	if(empty($num) || $num < 1)
	{
		flash_message($lang->emptyimagesedit, 'error');
		admin_redirect("index.php?module=downloads/archives");
	}
	if($error)
	{
		$page->output_inline_error($error);
	}
	$lang->imagesofdownload = $lang->sprintf($lang->imgsofdownload, $download['name']);
	$form = new Form("index.php?module=downloads/archives&action=images", "post");
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
	$buttons[] = $form->generate_submit_button($lang->editimages);
	$form->output_submit_wrapper($buttons);
	$form->end();
}
elseif($mybb->input['action'] == "links")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->notpostcode, 'error');
		admin_redirect("index.php?module=downloads/archives");
	}
	if($mybb->request_method == "post")
	{
		for($i = 1; ; $i++) 
		{
			if($i > intval($mybb->input['linksnum'])) 
			{
				break;
			}
			$lang->shortpathlinkurl = $lang->sprintf($lang->shortpathlink, $i);
			if(strlen($mybb->input['url_'.$i]) < 4)
			{
				$error[] = $lang->shortpathlinkurl;
			}
			if(strlen($mybb->input['name_'.$i]) < 3)
			{
				$lang->linknamelinkshortby = $lang->sprintf($lang->linknamelinkshort, $i);
				$error[] = $lang->linknamelinkshortby;
			}
		}
		if(!$error)
		{
			for($i = 1; ; $i++) 
			{
				if($i > intval($mybb->input['linksnum'])) 
				{
					break;
				}
				$edit_links = array(
					"url" => $db->escape_string($mybb->input['url_'.$i]),
					"text" => $db->escape_string($mybb->input['name_'.$i])
				);
				$db->update_query("downloads_urls", $edit_links,"did='".$mybb->input['did']."' AND orden='".$i."'");
			}
			flash_message($lang->editedlinks, 'success');
			admin_redirect("index.php?module=downloads/archives");
		}
	}
	$query = $db->simple_select("downloads", "*", "did=".$mybb->input['did']);
	$download = $db->fetch_array($query);
	$links = intval($download['urls']);
	if(empty($links) || $links < 1)
	{
		flash_message($lang->emptylinksedit, 'error');
		admin_redirect("index.php?module=downloads/archives");
	}
	if($error)
	{
		$page->output_inline_error($error);
	}
	$lang->linksofdownloadlan = $lang->sprintf($lang->linksofdownload, $download['name']);
	$form = new Form("index.php?module=downloads/archives&action=links", "post");
	echo $form->generate_hidden_field("did", $download['did']);
	echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
	echo $form->generate_hidden_field("linksnum", $links);
	$form_container = new FormContainer($lang->linksofdownloadlan);
	for($i = 1; ; $i++) 
	{
		if ($i > $links) 
		{
			break;
		}
		$queryd = $db->simple_select('downloads_urls', '*', 'did="'.$download['did'].'" AND orden='.$i);
		while($form_edit = $db->fetch_array($queryd))
		{
			$lang->linknumberby = $lang->sprintf($lang->linknumber, $i);
			$form_container->output_row($lang->linknumberby,'', $lang->namelink."&nbsp;".$form->generate_text_box('name_'.$i,$form_edit['text'], array('id' => 'name_'.$i))."<br /><br />&nbsp;&nbsp;".$lang->enlace."&nbsp;".$form->generate_text_box('url_'.$i,$form_edit['url'], array('id' => 'url_'.$i)), 'url_'.$i);
		}
	}
	$form_container->end();
	$buttons[] = $form->generate_submit_button($lang->editlinks);
	$form->output_submit_wrapper($buttons);
	$form->end();
}
elseif($mybb->input['action'] == "deleteimages")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->notpostcode, 'error');
		admin_redirect("index.php?module=downloads/archives");
	}
	$query_diids = $db->query("
		SELECT i.diid
		FROM ".TABLE_PREFIX."downloads_images i
		LEFT JOIN ".TABLE_PREFIX."downloads d ON (d.did=i.did)
		WHERE i.did='".$mybb->input['did']."'
	");
	$dids = array();
	while($downloads = $db->fetch_array($query_diids))
	{
		$dids[] = $downloads['diid'];
	}
	if($dids)
	{
		$dids = implode(',', $dids);
		$db->delete_query("downloads_images", "diid IN ($dids)");
	}
	$db->update_query("downloads", array("pics" => 0),"did=".$mybb->input['did']);
	flash_message($lang->imagesdeletesuccess, 'success');
	admin_redirect("index.php?module=downloads/archives");
}
elseif($mybb->input['action'] == "new") 
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
		if(intval($mybb->input['url']) < 1)
		{
			$error[] = $lang->urlarchshort;
		}
		if(intval($mybb->input['picks']) > 10)
		{
			$error[] = $lang->mosttenimages;
		}
		if(empty($mybb->input['category']))
		{
			$error[] = $lang->notcategoryselect;
		}
		if(!$error)
		{
			$db->query("DELETE FROM ".TABLE_PREFIX."datacache WHERE title='downloads_cache'");
			if(!$mybb->input['groups'])
			{
				$insert_groups_view = 0;
			}else{
				$insert_groups_view = implode(",",$mybb->input['groups']);
			}
			$insert = array(
				"name" => $db->escape_string($mybb->input['name']),
				"orden" => intval($mybb->input['orden']),
				"uid" => intval($mybb->user['uid']),
				"shortdesc" => $db->escape_string($mybb->input['shortdesc']),
				"description" => $db->escape_string($mybb->input['description']),
				"image" => $db->escape_string($mybb->input['image']),
				"comments" => intval($mybb->input['comments']),
				"dateline" => TIME_NOW,
				"active" => intval($mybb->input['active']),
				"groups" => $insert_groups_view,
				"category" => intval($mybb->input['category'])
			);
			$did = $db->insert_id();
			$update_cache = array(
				"name" => $mybb->input['name']
			);
			$cache->update("downloads_cache", $update_cache);
			$db->insert_query("downloads", $insert);
			if($mybb->settings['downloads_counthreads'] == 1)
			{
				$edit_user = array(
					"postnum" => intval(++$mybb->user['postnum']),
					"threads" => intval(++$mybb->user['threads']),
					"downloads" => intval(++$mybb->user['downloads'])
				);
			}
			else
			{
				$edit_user = array(
					"downloads" => intval(++$mybb->user['downloads'])
				);
			}
			$db->update_query("users", $edit_user,"uid=".$mybb->user['uid']);
			$piks = intval($mybb->input['picks']);
			$links = intval($mybb->input['url']);
			$cache_read = $cache->read("downloads_cache");
			$query = $db->simple_select("downloads", "*", "name=\"".$cache_read['name']."\"");
			$download = $db->fetch_array($query);
			$edit_update = array(
				"pics" => 0,
				"urls" => 0
			);
			$db->update_query("downloads", $edit_update,"did=".$download['did']);
			flash_message($lang->archsave_imagesnew, "success");
			admin_redirect("index.php?module=downloads/archives&links=".$links."&newimagesc=".intval($mybb->input['picks'])."&postcode=".$mybb->input['postcode']);
		}
	}
	$category_select = "<select name=\"category\" style=\"width: 310px;\" size=\"5\">";
	$query_category = $db->simple_select("downloads_cat", "*", "");
	while($category = $db->fetch_array($query_category))
	{
		$category_select .= "<option value=\"{$category['dcid']}\">{$category['name']}</option>";
	}
	$category_select .= "</select>";
	if($error)
	{
		$page->output_inline_error($error);
	}
	$form = new Form("index.php?module=downloads/archives&action=new", "post");
	echo $form->generate_hidden_field("postcode", $mybb->post_code);
	$form_container = new FormContainer($lang->newarchive);
	$form_container->output_row($lang->name."<em>*</em>",$lang->name_des, $form->generate_text_box('name',$mybb->input['name'], array('id' => 'name')), 'name');
	$form_container->output_row($lang->shortdescription."<em>*</em>", $lang->shortdescriptiondes, $form->generate_text_box('shortdesc',$mybb->input['shortdesc'],array('id' => 'shortdesc',)), 'shortdesc');
	$form_container->output_row($lang->description."<em>*</em>", $lang->descriptiondes, $form->generate_text_area('description',$mybb->input['description'],array('id' => 'description','class'=>'codepress mybb','style'=>'width:100%;height:200px;')), 'description');
	$form_container->output_row($lang->portada."<em>*</em>", $lang->portadades, $form->generate_text_box('image',$mybb->input['image'], array('id' => 'image')), 'image');
	$form_container->output_row($lang->comments."<em>*</em>",$lang->commentsdes, $form->generate_yes_no_radio('comments',$mybb->input['comments'], array('id' => 'comments')), 'comments');
	$form_container->output_row($lang->urlarchive."<em>*</em>",$lang->urlarchivedes, $form->generate_text_box('url',$mybb->input['url'], array('id' => 'url')), 'url');
	$form_container->output_row($lang->category."<em>*</em>", $lang->categorysdesnew, $category_select);
	$form_container->output_row($lang->orden."<em>*</em>",$lang->ordendes, $form->generate_text_box('orden',$mybb->input['orden'], array('id' => 'orden')), 'orden');
	$form_container->output_row($lang->active."<em>*</em>",$lang->activedes, $form->generate_yes_no_radio('active',$mybb->input['active'], array('id' => 'active')), 'active');
	$form_container->output_row($lang->images."<em>*</em>",$lang->imagesdesnewarchive, $form->generate_text_box('picks',$mybb->input['picks'], array('id' => 'picks')), 'picks');
	$form_container->output_row($lang->groupsuser."<em>*</em>", $lang->groupsuserdes, $form->generate_group_select('groups[]','', array('id' => 'groups[]', 'size' => 6, 'multiple' => 'multiple')), 'groups');
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->save);
	$buttons[] = $form->generate_reset_button($lang->reset);
	$form->output_submit_wrapper($buttons);
	$form->end();
}	
elseif($mybb->input['links'])
{
	if($mybb->request_method == "post")
	{
		for($i = 1; ; $i++) 
		{
			if ($i > intval($mybb->input['links'])) 
			{
				break;
			}
			if(strlen($mybb->input['url_'.$i]) < 4)
			{
				$lang->urlnameshortby = $lang->sprintf($lang->urlnameshort, $i);
				$error[] = $lang->urlnameshortby;
			}
			if(strlen($mybb->input['name_'.$i]) < 3)
			{
				$lang->linknamelinkshortby = $lang->sprintf($lang->linknamelinkshort, $i);
				$error[] = $lang->linknamelinkshortby;
			}
		}
		if(!$error)
		{
			for($i = 1; ; $i++) 
			{
				if ($i > intval($mybb->input['links'])) 
				{
					break;
				}
				$insert = array(
					"did" => $mybb->input['did'],
					"dcid" => intval($mybb->input['dcid']),
					"url" => $db->escape_string($mybb->input['url_'.$i]),
					"text" => $db->escape_string($mybb->input['name_'.$i]),
					"generate" => random_str(10),
					"orden" => $i
				);	
				$duid = $db->insert_id();
				$db->insert_query("downloads_urls", $insert);
			}
			$edit_update = array(
				"urls" => intval($mybb->input['links'])
			);
			$db->update_query("downloads", $edit_update,"did=".$mybb->input['did']);
			flash_message($lang->linksagregesuccess, "success");
			if(empty($mybb->input['images']) || $mybb->input['images'] < 1)
			{
				admin_redirect("index.php?module=downloads/archives");
			}
			else
			{			
				admin_redirect("index.php?module=downloads/archives&customimages=".intval($mybb->input['images'])."&postcode=".$mybb->input['postcode']);
			}
		}
	}
	if(!verify_post_check($mybb->input['postcode']))
	{
		flash_message($lang->notpostcode, 'error');
		admin_redirect("index.php?module=downloads/archives");
	}
	if($error)
	{
		$page->output_inline_error($error);
	}
	$cache_read = $cache->read("downloads_cache");
	$query = $db->simple_select("downloads", "*", "name=\"".$cache_read['name']."\"");
	$download = $db->fetch_array($query);
	if(!$download['did'])
	{
		flash_message($lang->notselectedarchive, 'error');
		admin_redirect("index.php?module=downloads/archives");
	}
	if($mybb->input['images'])
	{
		$mybb->input['newimagesc'] = intval($mybb->input['images']);
	}
	$links = intval($mybb->input['links']);
	$lang->linksofthe = $lang->sprintf($lang->linksof, $download['name']);
	$page->add_breadcrumb_item($lang->linksofthe);
	$form = new Form("index.php?module=downloads/archives&links=".$links."&images=".$mybb->input['newimagesc']."&postcode=".$mybb->input['postcode'], "post");
	echo $form->generate_hidden_field("postcode", $mybb->post_code);
	echo $form->generate_hidden_field("links", $links);
	echo $form->generate_hidden_field("did", $download['did']);
	echo $form->generate_hidden_field("dcid", $download['category']);
	$lang->linksofdownloadthe = $lang->sprintf($lang->linksofdownload, $download['name']);
	$form_container = new FormContainer($lang->linksofdownloadthe);
	for($i = 1; ; $i++) 
	{
		if ($i > $links) 
		{
			break;
		}
		$lang->linknumer_byfor_downloads = $lang->sprintf($lang->linknumber, $i);
		$lang->linknumerdes_byfor_downloads = $lang->sprintf($lang->linknumerdes, $i);
		$form_container->output_row($lang->linknumer_byfor_downloads,$lang->linknumerdes_byfor_downloads, $lang->namelink."&nbsp;".$form->generate_text_box('name_'.$i,$mybb->input['name_'.$i], array('id' => 'name_'.$i))."<br /><br />&nbsp;&nbsp;".$lang->enlace."&nbsp;".$form->generate_text_box('url_'.$i,$mybb->input['url_'.$i], array('id' => 'url_'.$i)), 'url_'.$i);
	}
	
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->savelinks);
	$form->output_submit_wrapper($buttons);
	$form->end();

}
elseif($mybb->input['customimages'])
{
	if($mybb->request_method == "post")
	{
		for($i = 1; ; $i++) 
		{
			if ($i > intval($mybb->input['num'])) 
			{
				break;
			}
			if(strlen($mybb->input['image_'.$i]) < 4)
			{
				$lang->urlimageshbyort = $lang->sprintf($lang->urlimageshort, $i);
				$error[] = $lang->urlimageshbyort;
			}
		}
		if(!$error)
		{
			for($i = 1; ; $i++) 
			{
				if ($i > intval($mybb->input['num'])) 
				{
					break;
				}
				$insert = array(
					"did" => $mybb->input['did'],
					"dcid" => intval($mybb->input['dcid']),
					"image" => $mybb->input['image_'.$i],
					"orden" => $i
				);	
				$diid = $db->insert_id();
				$db->insert_query("downloads_images", $insert);
			}
			$edit_update = array(
				"pics" => intval($mybb->input['num'])
			);
			$db->update_query("downloads", $edit_update,"did=".$mybb->input['did']);
			$db->query("DELETE FROM ".TABLE_PREFIX."datacache WHERE title='downloads_cache'");
			flash_message($lang->imagesagregesuccess, "success");
			admin_redirect("index.php?module=downloads/archives");
		}
	}
	if(!verify_post_check($mybb->input['postcode']))
	{
		flash_message($lang->notpostcode, 'error');
		admin_redirect("index.php?module=downloads/archives");
	}
	if($error)
	{
		$page->output_inline_error($error);
	}
	$cache_read = $cache->read("downloads_cache");
	$query = $db->simple_select("downloads", "*", "name=\"".$cache_read['name']."\"");
	$download = $db->fetch_array($query);
	if(!$download['did'])
	{
		flash_message($lang->notselectedarchive, 'error');
		admin_redirect("index.php?module=downloads/archives");
	}
	$num = intval($mybb->input['customimages']);
	if($num > 10)
	{
		flash_message($lang->mosttenimages, 'error');
		admin_redirect("index.php?module=downloads/archives");
	}
	$lang->imagesofthe = $lang->sprintf($lang->imagesof, $download['name']);
	$page->add_breadcrumb_item($lang->imagesofthe);
	$form = new Form("index.php?module=downloads/archives&customimages=".$num, "post");
	echo $form->generate_hidden_field("postcode", $mybb->post_code);
	echo $form->generate_hidden_field("num", $num);
	echo $form->generate_hidden_field("did", $download['did']);
	echo $form->generate_hidden_field("dcid", $download['category']);
	$lang->imgssofdownload = $lang->sprintf($lang->imgsofdownload, $download['name']);
	$form_container = new FormContainer($lang->imgssofdownload);
	for($i = 1; ; $i++) 
	{
		if ($i > $num) 
		{
			break;
		}
		$lang->imagenumer_byfor_downloads = $lang->sprintf($lang->imagenumer, $i);
		$lang->imagenumerdes_byfor_downloads = $lang->sprintf($lang->imagenumerdes, $i);
		$form_container->output_row($lang->imagenumer_byfor_downloads,$lang->imagenumerdes_byfor_downloads, $form->generate_text_box('image_'.$i,$mybb->input['image_'.$i], array('id' => 'image_'.$i)), 'image_'.$i);
	}
	
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->saveimages);
	$form->output_submit_wrapper($buttons);
	$form->end();
}

$page->output_footer();
exit;

?>
