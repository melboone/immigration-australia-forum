<?php

/**
 * MyBB 1.6 Spanish Language Pack
 * Copyright 2011 Edson Ordaz, All Rights Reserved
 * 
 * category V1.0
 *
 * $Id: category.php 2011-07-11 Edson Ordaz $
 */
 
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$lang->load('downloads');

$page->add_breadcrumb_item($lang->categorys, 'index.php?module=downloads/home');
$page->output_header($lang->categorys);

$tabs["category"] = array(
	'title' => $lang->categorys,
	'link' => "index.php?module=downloads/category",
	'description' => $lang->tabcategory1
);
$tabs["new"] = array(
	'title' => $lang->newcategory,
	'link' => "index.php?module=downloads/category&amp;action=new",
	'description' => $lang->tabcategory2
);
	
switch($mybb->input['action'])
{
	case 'category':
		$page->output_nav_tabs($tabs, 'category');
	break;
	case 'new':
		$page->output_nav_tabs($tabs, 'new');
	break;
	default:
		$page->output_nav_tabs($tabs, 'category');
}

if(!$mybb->input['action']) 
{	
	$query = $db->simple_select('downloads_cat', 'COUNT(dcid) AS dcids', '', array('limit' => 1));
	$quantity = $db->fetch_field($query, "dcids");
	$pagina = intval($mybb->input['page']);
	$perpage = 15;
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
	$pageurl = "index.php?module=downloads/category";
	$table = new Table;
	$table->construct_header($lang->ficon, array("width" => "10%","class" => "align_center"));
	$table->construct_header($lang->namecategory);
	$table->construct_header($lang->active, array("width" => "10%","class" => "align_center"));
	$table->construct_header($lang->orden, array("width" => "10%","class" => "align_center"));
	$table->construct_header($lang->options, array("width" => "10%","class" => "align_center"));
	$table->construct_row();
	
	
	$query = $db->query('SELECT * FROM '.TABLE_PREFIX.'downloads_cat ORDER BY orden ASC LIMIT '.$start.', '.$perpage);
	while($category = $db->fetch_array($query))
	{
		if($category['active'] == 1)
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
		$lang->deletepopupcategory = $lang->sprintf($lang->deletepopupcategorys, $category['name']);
		$table->construct_cell("<img src=\"../".$category['ficon']."\" />", array("class" => "align_center"));
		$table->construct_cell("<a href=\"index.php?module=downloads/category&action=edit&amp;dcid=".$category['dcid']."\"><strong>".$category[name]."</strong></a><br /><span class='smalltext'>".$category['description']."</span>");
		$table->construct_cell("<a href=\"index.php?module=downloads/category&action=activate&state=".$mod."&dcid=".$category['dcid']."&my_post_key={$mybb->post_code}\"><img src=\"styles/default/images/icons/".$state."\" title=\"".$title."\" /></a>",array("class" => "align_center"));
		$table->construct_cell("<input type=\"text\" value=\"".$category['orden']."\" readonly='readonly' class=\"text_input align_center\" style=\"width: 80%; font-weight: bold;\" />", array("class" => "align_center"));
		$popup = new PopupMenu("dcid_{$category['dcid']}", $lang->options);
		$popup->add_item($lang->edit, "index.php?module=downloads/category&action=edit&amp;dcid=".$category['dcid']);
		$popup->add_item($lang->delete, "index.php?module=downloads/category&action=delete&amp;dcid={$category['dcid']}&my_post_key={$mybb->post_code}\" target=\"_self\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->deletepopupcategory}')");
		$popup->add_item($popup_state." ".$lang->category, "index.php?module=downloads/category&action=activate&state=".$mod."&dcid=".$category['dcid']."&my_post_key={$mybb->post_code}\" target=\"_self");
		$Popuss = $popup->fetch();
		$table->construct_cell($Popuss, array('class' => 'align_center'));
		$table->construct_row();
	}
	if($table->num_rows() == 1)
	{
		$table->construct_cell($lang->emptycategoritable, array('colspan' => 5, 'class' => 'align_center'));
		$table->construct_row();
	}
	$table->output($lang->categorys);
	echo multipage($quantity, (int)$perpage, (int)$pagina, $pageurl);
}	
elseif($mybb->input['action'] == "new")
{
	if($mybb->request_method == "post")
	{
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			$error[] = $lang->notpostcode;
		}
		if(strlen($mybb->input['name']) < 2)
		{
			$error[] = $lang->namecatshort;
		}
		if(strlen($mybb->input['description']) < 10)
		{
			$error[] = $lang->descatshort;
		}
		$mybb->input['orden'] = intval($mybb->input['orden']);
		$mybb->input['orden'] = abs($mybb->input['orden']);
		if(empty($mybb->input['orden']))
		{
			$error[] = $lang->ordenempty;
		}
		if(!$error)
		{
			if(!$imagen['name'] || !$imagen['tmp_name'])
			{
				$imagen = $_FILES['ficon'];
			}
			if(!is_uploaded_file($imagen['tmp_name']))
			{
				$error[] = $lang->errorcopyimage;
			}
			if(!$error)
			{
				$ext = get_extension(my_strtolower($imagen['name']));
				if(!preg_match("#^(gif|jpg|jpeg|jpe|bmp|png)$#i", $ext)) 
				{
					$error[] = $lang->extnotpermit;
				}
				if(!$error)
				{
					$path = MYBB_ROOT."images/downloads";
					$filename = "category_".date('d_m_y_g_i_s').'.'.$ext; 
					$moved = @move_uploaded_file($imagen['tmp_name'], $path."/".$filename);
					if(!$moved)
					{
						$error[] = $lang->errorcopyimage;
					}
					if(!$error)
					{
						@my_chmod($path."/".$filename, '0644');
						if($imagen['error'])
						{
							@unlink($path."/".$filename);	
							$error[] = $lang->errorloadimage;
						}
						if(!$error)
						{
							switch(my_strtolower($imagen['type']))
							{
								case "image/gif":
									$img_type =  1;
									break;
								case "image/jpeg":
								case "image/x-jpg":
								case "image/x-jpeg":
								case "image/pjpeg":
								case "image/jpg":
									$img_type = 2;
									break;
								case "image/png":
								case "image/x-png":
									$img_type = 3;
									break;
								default:
									$img_type = 0;
							}
							if($img_type == 0)
							{
								@unlink($path."/".$filename);
								$error[] = $lang->extnotpermit;
							}
							if(!$error)
							{
								$insert = array(
									"name" => $db->escape_string($mybb->input['name']),
									"ficon" => "images/downloads/".$filename,
									"description" => $db->escape_string($mybb->input['description']),
									"orden" => intval($mybb->input['orden']),
									"active" => intval($mybb->input['active'])
								);
								$dcid = $db->insert_id();
								$db->insert_query("downloads_cat", $insert);
								flash_message($lang->successsavecat, 'success');
								admin_redirect("index.php?module=downloads/category");
							}
						}
					}
				}
			}
		}
	}
	if($error)
	{
		$page->output_inline_error($error);
	}
	$form = new Form("index.php?module=downloads/category&amp;action=new", "post", "save",1);
	echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
	$form_container = new FormContainer($lang->newcategory);
	$form_container->output_row($lang->name."<em>*</em>",$lang->namedescat, $form->generate_text_box('name',$mybb->input['name'], array('id' => 'name')), 'name');
	$form_container->output_row($lang->description."<em>*</em>",$lang->descdescat, $form->generate_text_area('description',$mybb->input['description'],array('id' => 'description')), 'description');
	$form_container->output_row($lang->orden."<em>*</em>",$lang->ordendescat, $form->generate_text_box('orden',$mybb->input['orden'], array('id' => 'orden')), 'orden');
	$form_container->output_row($lang->ficon."<em>*</em>",$lang->ficon_des, $form->generate_file_upload_box("ficon", array('style' => 'width: 310px;')), 'file');
	$form_container->output_row($lang->active."<em>*</em>",$lang->activedescat, $form->generate_yes_no_radio('active',$mybb->input['active'], array('id' => 'active')), 'active');
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->save);
	$buttons[] = $form->generate_reset_button($lang->reset);
	$form->output_submit_wrapper($buttons);
	$form->end();
}
elseif($mybb->input['action'] == "edit")
{
	if($mybb->request_method == "post")
	{
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			$error[] = $lang->notpostcode;
		}
		if(strlen($mybb->input['name']) < 2)
		{
			$error[] = $lang->namecatshort;
		}
		if(strlen($mybb->input['description']) < 10)
		{
			$error[] = $lang->descatshort;
		}
		$mybb->input['orden'] = intval($mybb->input['orden']);
		$mybb->input['orden'] = abs($mybb->input['orden']);
		if(empty($mybb->input['orden']))
		{
			$error[] = $lang->ordenempty;
		}
		if($_FILES['ficon']['error'] > 0)
		{
			$edit_update = array(
				"name" => $mybb->input['name'],
				"description" => $mybb->input['description'],
				"orden" => $mybb->input['orden'],
				"active" => $mybb->input['active']
			);
			$db->update_query("downloads_cat", $edit_update,"dcid=".$mybb->input['dcid']);
			flash_message($lang->successsavecat, 'success');
			admin_redirect("index.php?module=downloads/category");
		}
		if(!$error)
		{
			if(!$imagen['name'] || !$imagen['tmp_name'])
			{
				$imagen = $_FILES['ficon'];
			}
			if(!is_uploaded_file($imagen['tmp_name']))
			{
				$error[] = $lang->errorcopyimage;
			}
			if(!$error)
			{
				$ext = get_extension(my_strtolower($imagen['name']));
				if(!preg_match("#^(gif|jpg|jpeg|jpe|bmp|png)$#i", $ext)) 
				{
					$error[] = $lang->extnotpermit;
				}
				if(!$error)
				{
					$path = MYBB_ROOT."images/downloads";
					$filename = "category_".date('d_m_y_g_i_s').'.'.$ext; 
					$moved = @move_uploaded_file($imagen['tmp_name'], $path."/".$filename);
					if(!$moved)
					{
						$error[] = $lang->errorcopyimage;
					}
					if(!$error)
					{
						@my_chmod($path."/".$filename, '0644');
						if($imagen['error'])
						{
							@unlink($path."/".$filename);	
							$error[] = $lang->errorloadimage;
						}
						if(!$error)
						{
							switch(my_strtolower($imagen['type']))
							{
								case "image/gif":
									$img_type =  1;
									break;
								case "image/jpeg":
								case "image/x-jpg":
								case "image/x-jpeg":
								case "image/pjpeg":
								case "image/jpg":
									$img_type = 2;
									break;
								case "image/png":
								case "image/x-png":
									$img_type = 3;
									break;
								default:
									$img_type = 0;
							}
							if($img_type == 0)
							{
								@unlink($path."/".$filename);
								$error[] = $lang->extnotpermit;
							}
							if(!$error)
							{
								$query = $db->simple_select('downloads_cat', '*', 'dcid='.$mybb->input['dcid']);
								$category = $db->fetch_array($query);
								@unlink(MYBB_ROOT.$category['ficon']);
								$edit_update = array(
									"name" => $db->escape_string($mybb->input['name']),
									"ficon" => "images/downloads/".$filename,
									"description" => $db->escape_string($mybb->input['description']),
									"orden" => intval($mybb->input['orden']),
									"active" => intval($mybb->input['active'])
								);
								$db->update_query("downloads_cat", $edit_update,"dcid=".$mybb->input['dcid']);
								flash_message($lang->successsavecat, 'success');
								admin_redirect("index.php?module=downloads/category");
							}
						}
					}
				}
			}
		}
	}
	if($error)
	{
		$page->output_inline_error($error);
	}
	$query = $db->simple_select('downloads_cat', '*', 'dcid='.$mybb->input['dcid']);
	$category = $db->fetch_array($query);
	$form = new Form("index.php?module=downloads/category&amp;action=edit", "post", "save",1);
	echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
	echo $form->generate_hidden_field("dcid", $category['dcid']);
	$form_container = new FormContainer($lang->newcategory);
	$form_container->output_row($lang->name."<em>*</em>",$lang->namedescat, $form->generate_text_box('name',$category['name'], array('id' => 'name')), 'name');
	$form_container->output_row($lang->description."<em>*</em>",$lang->descdescat, $form->generate_text_area('description',$category['description'],array('id' => 'description')), 'description');
	$form_container->output_row($lang->orden."<em>*</em>",$lang->ordendescat, $form->generate_text_box('orden',$category['orden'], array('id' => 'orden')), 'orden');
	$form_container->output_row($lang->ficonused."<em>*</em>",$lang->ficonusednotremplace, "<img src=\"../".$category['ficon']."\" />", 'useimage');
	$form_container->output_row($lang->ficon."<em>*</em>",$lang->ficon_des, $form->generate_file_upload_box("ficon", array('style' => 'width: 310px;')), 'file');
	$form_container->output_row($lang->active."<em>*</em>",$lang->activedescat, $form->generate_yes_no_radio('active',$category['active'], array('id' => 'active')), 'active');
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->save);
	$buttons[] = $form->generate_reset_button($lang->reset);
	$form->output_submit_wrapper($buttons);
	$form->end();
}
elseif($mybb->input['action'] == "delete")
{
	$query_did = $db->query("
		SELECT d.did
		FROM ".TABLE_PREFIX."downloads d
		LEFT JOIN ".TABLE_PREFIX."downloads_cat c ON (c.dcid=d.category)
		WHERE d.category='".$mybb->input['dcid']."'
	");
	$query_diid = $db->query("
		SELECT i.diid
		FROM ".TABLE_PREFIX."downloads_images i
		LEFT JOIN ".TABLE_PREFIX."downloads_cat c ON (c.dcid=i.dcid)
		WHERE i.dcid='".$mybb->input['dcid']."'
	");
	$query_cat = $db->query("
		SELECT i.dcid
		FROM ".TABLE_PREFIX."downloads_comments i
		LEFT JOIN ".TABLE_PREFIX."downloads_cat c ON (c.dcid=i.category)
		WHERE i.category='".$mybb->input['dcid']."'
	");
	$did = array();
	$diid = array();
	$cat = array();
	while($downloads = $db->fetch_array($query_did))
	{
		$did[] = $downloads['did'];
	}
	while($images = $db->fetch_array($query_diid))
	{
		$diid[] = $images['diid'];
	}
	while($comments = $db->fetch_array($query_cat))
	{
		$cat[] = $comments['dcid'];
	}
	if($did)
	{
		$did = implode(',', $did);
		$db->delete_query("downloads", "did IN ($did)");
	}
	if($diid)
	{
		$diid = implode(',', $diid);
		$db->delete_query("downloads_images", "diid IN ($diid)");
	}
	if($cat)
	{
		$cat = implode(',', $cat);
		$db->delete_query("downloads_comments", "dcid IN ($cat)");
	}
	$query = $db->simple_select("downloads_cat", "*", "dcid=".$mybb->input['dcid']);
	$download = $db->fetch_array($query);
	@unlink(MYBB_ROOT.$download['ficon']);
	$lang->succesdeletecat = $lang->sprintf($lang->deletecategoryentri, $download['name']);
	$db->query("DELETE FROM ".TABLE_PREFIX."downloads_cat WHERE dcid='".intval($mybb->input['dcid'])."'");
	$db->free_result($query);
	flash_message($lang->succesdeletecat, 'success');
	admin_redirect("index.php?module=downloads/category");
}
elseif($mybb->input['action'] == "activate")
{
	if(!verify_post_check($mybb->input['my_post_key']))
	{
		flash_message($lang->notpostcode, 'error');
		admin_redirect("index.php?module=downloads/category");
	}
	$state = intval($mybb->input['state']);
	$edit_update = array(
		"active" => $state
	);
	$db->update_query("downloads_cat", $edit_update,"dcid=".$mybb->input['dcid']);
	$query = $db->simple_select("downloads_cat", "*", "dcid=".$mybb->input['dcid']);
	$category = $db->fetch_array($query);
	if($state == 1)
	{
		$text = $lang->activado;
	}else{
		$text = $lang->desactivado;
	}
	$lang->activatecatsucc = $lang->sprintf($lang->successactivatecat, $text, $category['name']);
	flash_message($lang->activatecatsucc, 'success');
	admin_redirect("index.php?module=downloads/category");
}
$page->output_footer();
exit;

?>
