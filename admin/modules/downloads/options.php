<?php

/**
 * MyBB 1.6 Spanish Language Pack
 * Copyright 2011 Edson Ordaz, All Rights Reserved
 * 
 * options V1.0
 *
 * $Id: options.php 2011-07-11 Edson Ordaz $
 */
 
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$lang->load('downloads');

$page->add_breadcrumb_item($lang->options, 'index.php?module=downloads/options');
$page->output_header($lang->options);

$tabs["options"] = array(
	'title' => $lang->options,
	'link' => "index.php?module=downloads/options",
	'description' => $lang->taboptions1
);
$tabs["editmassive"] = array(
	'title' => $lang->editmassive,
	'link' => "index.php?module=downloads/options&amp;action=editmassive",
	'description' => $lang->taboptions2
);
$tabs["search"] = array(
	'title' => $lang->search,
	'link' => "index.php?module=downloads/options&amp;action=search",
	'description' => $lang->taboptions3
);
	
switch($mybb->input['action'])
{
	case 'options':
		$page->output_nav_tabs($tabs, 'options');
	break;
	case 'editmassive':
		$page->output_nav_tabs($tabs, 'editmassive');
	break;
	case 'search':
		$page->output_nav_tabs($tabs, 'search');
	break;
	default:
		$page->output_nav_tabs($tabs, 'options');
}

if(!$mybb->input['action']) 
{	
	$query = $db->simple_select('downloads', 'COUNT(did) AS dids', '', array('limit' => 1));
	$quantity = $db->fetch_field($query, "dids");
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
	$table->construct_header($lang->namearchive);
	$table->construct_header($lang->threads, array("width" => "10%","class" => "align_center"));
	$table->construct_header($lang->posts, array("width" => "10%","class" => "align_center"));
	$table->construct_header($lang->reputation, array("width" => "10%","class" => "align_center"));
	$table->construct_header($lang->timeonline, array("width" => "15%","class" => "align_center"));
	$table->construct_header($lang->options, array("width" => "10%","class" => "align_center"));
	$table->construct_row();
	
	
	$query = $db->query('SELECT * FROM '.TABLE_PREFIX.'downloads ORDER BY name ASC LIMIT '.$start.', '.$perpage);
	while($download = $db->fetch_array($query))
	{
		if($download['year'] == 1)
		{
			$años = "1 ".$lang->year;
		}elseif($download['year'] > 1){
			$años = $download['year']." ".$lang->years;
		}else{
			$años = "";
		}
		if($download['month'] == 1)
		{
			if($años){
				$meses = ", 1 ".$lang->month;
			}else{
				$meses = "1 ".$lang->month;
			}
		}elseif($download['month'] > 1){
			if($años){
				$meses = ", ".$download['month']." ".$lang->months;
			}else{
				$meses = $download['month']." ".$lang->months;
			}
		}else{
			$meses = "";
		}
		if($download['day'] == 1)
		{
			if($años || $meses){
				$dias = ", 1 ".$lang->day;
			}else{
				$dias = "1 ".$lang->day;
			}
		}elseif($download['day'] > 1){
			if($años || $meses)
			{
				$dias = ", ".$download['day']." ".$lang->days;
			}else{
				$dias = $download['day']." ".$lang->days;
			}
		}else{
			$dias = "";
		}
		if($download['hour'] == 1)
		{	
			if($años || $meses || $dias)
			{
				$horas = ", 1 ".$lang->hour;
			}else{
				$horas = "1 ".$lang->hour;
			}
		}elseif($download['hour'] > 1){
			if($años || $meses || $dias)
			{
				$horas = ", ".$download['hour']." ".$lang->hours;
			}else{
				$horas = $download['hour']." ".$lang->hours;
			}
		}else{
			$horas = "";
		}
		if(empty($años) && empty($meses) && empty($dias) && empty($horas))
		{
			$años.$meses.$dias.$horas = $lang->none;
		}
		$table->construct_cell("<a href='index.php?module=downloads/options&action=edit&did={$download['did']}'><strong>".$download['name']."</strong></a>");
		$table->construct_cell($download['threads'], array("class" => "align_center"));
		$table->construct_cell($download['posts'], array("class" => "align_center"));
		$table->construct_cell($download['reputation'], array("class" => "align_center"));
		$table->construct_cell($años.$meses.$dias.$horas, array("class" => "align_center"));
		$table->construct_cell("<a href='index.php?module=downloads/options&action=edit&did={$download['did']}'><strong>".$lang->edit."</strong></a>", array('class' => 'align_center'));
		$table->construct_row();
	}
	if($table->num_rows() == 1)
	{
		$table->construct_cell($lang->emptytabledownloads, array('colspan' => 6, 'class' => 'align_center'));
		$table->construct_row();
	}
	$table->output($lang->options);
	echo multipage($quantity, (int)$perpage, (int)$pagina, $pageurl);
}	
elseif($mybb->input['action'] == "edit")
{
	if($mybb->request_method == "post")
	{
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			$error[] = $lang->notpostcode;
		}
		$años = intval($mybb->input['year']);
		$meses = intval($mybb->input['month']);
		$dias = intval($mybb->input['day']);
		$horas = intval($mybb->input['hour']);
		$time = ($años * 31556952) + ($meses * 2551443) + ($dias * 86400) + ($horas * 3600);
		if($time < 0)
		{
			$error[] = $lang->notdatepast;
		}
		if(!$error)
		{
			$edit_update = array(
				"posts" => intval(abs($mybb->input['posts'])),
				"threads" => intval(abs($mybb->input['threads'])),
				"reputation" => intval(abs($mybb->input['reputation'])),
				"timeonline" => $time,
				"hour" => $horas,
				"day" => $dias,
				"month" => $meses,
				"year" => $años
			);
			$db->update_query("downloads", $edit_update,"did=".$mybb->input['did']);
			flash_message($lang->successoptionsmore, 'success');
			admin_redirect("index.php?module=downloads/options");
		}
	}
	if($error)
	{
		$page->output_inline_error($error);
	}
	$query = $db->simple_select('downloads', '*', 'did='.$mybb->input['did']);
	$download = $db->fetch_array($query);
	$form = new Form("index.php?module=downloads/options&amp;action=edit", "post");
	echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
	echo $form->generate_hidden_field("did", $download['did']);
	$form_container = new FormContainer($download['name']);
	$form_container->output_row($lang->threadsform."<em>*</em>",$lang->threadsformdes, $form->generate_text_box('threads',$download['threads'], array('id' => 'threads')), 'threads');
	$form_container->output_row($lang->postsform."<em>*</em>",$lang->postsformdes, $form->generate_text_box('posts',$download['posts'], array('id' => 'posts')), 'posts');
	$form_container->output_row($lang->reputationform."<em>*</em>",$lang->reputationformdes, $form->generate_text_box('reputation',$download['reputation'], array('id' => 'reputation')), 'reputation');
	//$form_container->output_row($lang->timeonline."<em>*</em>",$lang->timeonlinedes, $form->generate_text_box('timeonline',$download['timeonline'], array('id' => 'timeonline')), 'timeonline');
	$form_container->output_row($lang->timeonlineform."<em>*</em>",$lang->timeonlinedes, $lang->hours.": ".$form->generate_text_box('hour',$download['hour'], array('id' => 'hour', 'style' => 'width: 60px;'))." ".$lang->days.": ".$form->generate_text_box('day',$download['day'], array('id' => 'day', 'style' => 'width: 60px;'))." ".$lang->months.": ".$form->generate_text_box('month',$download['month'], array('id' => 'month', 'style' => 'width: 60px;'))."  ".$lang->years.": ".$form->generate_text_box('year',$download['year'], array('id' => 'year', 'style' => 'width: 60px;')), 'timeonline');
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->save);
	$buttons[] = $form->generate_reset_button($lang->reset);
	$form->output_submit_wrapper($buttons);
	$form->end();
}
elseif($mybb->input['action'] == "editmassive")
{
	if($mybb->request_method == "post")
	{
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			$error[] = $lang->notpostcode;
		}
		$años = intval($mybb->input['year']);
		$meses = intval($mybb->input['month']);
		$dias = intval($mybb->input['day']);
		$horas = intval($mybb->input['hour']);
		$time = ($años * 31556952) + ($meses * 2551443) + ($dias * 86400) + ($horas * 3600);
		if($time < 0)
		{
			$error[] = $lang->notdatepast;
		}
		if(empty($mybb->input['category']))
		{
			$error[] = $lang->notcategorieditmass;
		}
		if(!$error)
		{
			if($mybb->input['category'][0] == "-1")
			{
				$insert_massive = array(
					"posts" => intval(abs($mybb->input['posts'])),
					"threads" => intval(abs($mybb->input['threads'])),
					"reputation" => intval(abs($mybb->input['reputation'])),
					"timeonline" => $time,
					"hour" => $horas,
					"day" => $dias,
					"month" => $meses,
					"year" => $años
				);
				$db->update_query("downloads", $insert_massive);
				flash_message($lang->successmassiveall, 'success');
				admin_redirect("index.php?module=downloads/options");
			}
			else
			{
				foreach($mybb->input['category'] as $id_category)
				{
					$insert_massive_category = array(
						"posts" => intval(abs($mybb->input['posts'])),
						"threads" => intval(abs($mybb->input['threads'])),
						"reputation" => intval(abs($mybb->input['reputation'])),
						"timeonline" => $time,
						"hour" => $horas,
						"day" => $dias,
						"month" => $meses,
						"year" => $años
					);
					$query_category = $db->simple_select("downloads_cat", "*", "dcid='".intval($id_category)."'");
					$category = $db->fetch_array($query_category);
					$db->update_query("downloads", $insert_massive_category, "category='".intval($id_category)."'");
					$names_of_categorys[] = $category['name'];
				}
				$implode_names = implode(", ",$names_of_categorys);
				$lang->yescatsuccesscat_massive = $lang->sprintf($lang->massiveeditcategory, $implode_names);
				flash_message($lang->yescatsuccesscat_massive, 'success');
				admin_redirect("index.php?module=downloads/options");
			}
		}
	}
	if($error)
	{
		$page->output_inline_error($error);
	}
	$category_select = "<select name=\"category[]\" id=\"category[]\" multiple=\"multiple\" style=\"width: 310px;\" size=\"5\">";
	$category_select .= "<option value=\"-1\" selected=\"selected\">{$lang->allcategorys}</option>";
	$query_category = $db->simple_select("downloads_cat", "*", "");
	while($category = $db->fetch_array($query_category))
	{
		$category_select .= "<option value=\"{$category['dcid']}\">{$category['name']}</option>";
	}
	$category_select .= "</select>";
	$form = new Form("index.php?module=downloads/options&amp;action=editmassive", "post");
	echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
	$form_container = new FormContainer($lang->editmassive);
	$form_container->output_row($lang->threadsform."<em>*</em>",$lang->threadsformdes, $form->generate_text_box('threads',$download['threads'], array('id' => 'threads')), 'threads');
	$form_container->output_row($lang->postsform."<em>*</em>",$lang->postsformdes, $form->generate_text_box('posts',$download['posts'], array('id' => 'posts')), 'posts');
	$form_container->output_row($lang->reputationform."<em>*</em>",$lang->reputationformdes, $form->generate_text_box('reputation',$download['reputation'], array('id' => 'reputation')), 'reputation');
	$form_container->output_row($lang->timeonlineform."<em>*</em>",$lang->timeonlinedes, $lang->hours.": ".$form->generate_text_box('hour',$download['hour'], array('id' => 'hour', 'style' => 'width: 60px;'))." ".$lang->days.": ".$form->generate_text_box('day',$download['day'], array('id' => 'day', 'style' => 'width: 60px;'))." ".$lang->months.": ".$form->generate_text_box('month',$download['month'], array('id' => 'month', 'style' => 'width: 60px;'))."  ".$lang->years.": ".$form->generate_text_box('year',$download['year'], array('id' => 'year', 'style' => 'width: 60px;')), 'timeonline');
	$form_container->output_row($lang->category."<em>*</em>",$lang->categoryeditmassive, $category_select);
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->save);
	$buttons[] = $form->generate_reset_button($lang->reset);
	$form->output_submit_wrapper($buttons);
	$form->end();
}
elseif($mybb->input['action'] == "search")
{
	if($mybb->request_method == "post")
	{
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			$error[] = $lang->notpostcode;
		}
		if(empty($mybb->input['text']))
		{
			$error[] = $lang->nottextsearch;
		}
		if(empty($mybb->input['category']))
		{
			$error[] = $lang->notcategorysearch;
		}
		if(!$error)
		{
			$lang->searchbysearch = $lang->searchallcategorys;
			if($mybb->input['category'][0] != "-1")
			{
				$categorys = $mybb->input['category'];
				if($categorys)
				{
					$categorys = implode(',', $categorys);
					$category_search =  " AND category IN ($categorys)";
					$category_names =  "dcid IN ($categorys)";
				}
				foreach($mybb->input['category'] as $id_category)
				{
					$query_category = $db->simple_select("downloads_cat", "*", "dcid='".intval($id_category)."'");
					$category = $db->fetch_array($query_category);
					$namescategoryssearchs[] = $category['name'];
				}
				$implode_names = implode(", ",$namescategoryssearchs);
				$lang->searchbysearch = $lang->sprintf($lang->searchcategorysby, $implode_names);
			}
			
			$page->output_success($lang->searchbysearch);
			$table = new Table;
			$table->construct_header($lang->namearchive);
			$table->construct_header($lang->threads, array("width" => "10%","class" => "align_center"));
			$table->construct_header($lang->posts, array("width" => "10%","class" => "align_center"));
			$table->construct_header($lang->reputation, array("width" => "10%","class" => "align_center"));
			$table->construct_header($lang->timeonline, array("width" => "15%","class" => "align_center"));
			$table->construct_header($lang->options, array("width" => "10%","class" => "align_center"));
			$table->construct_row();
			
			$query = $db->simple_select('downloads', '*', 'name LIKE "%'.$mybb->input['text'].'%"'.$category_search.'', array('order_by' => 'name', 'order_dir' => 'ASC'));
			while($download = $db->fetch_array($query))
			{
				if($download['year'] == 1)
				{
					$años = "1 ".$lang->year;
				}elseif($download['year'] > 1){
					$años = $download['year']." ".$lang->years;
				}else{
					$años = "";
				}
				if($download['month'] == 1)
				{
					if($años){
						$meses = ", 1 ".$lang->month;
					}else{
						$meses = "1 ".$lang->month;
					}
				}elseif($download['month'] > 1){
					if($años){
						$meses = ", ".$download['month']." ".$lang->months;
					}else{
						$meses = $download['month']." ".$lang->months;
					}
				}else{
					$meses = "";
				}
				if($download['day'] == 1)
				{
					if($años || $meses){
						$dias = ", 1 ".$lang->day;
					}else{
						$dias = "1 ".$lang->day;
					}
				}elseif($download['day'] > 1){
					if($años || $meses)
					{
						$dias = ", ".$download['day']." ".$lang->days;
					}else{
						$dias = $download['day']." ".$lang->days;
					}
				}else{
					$dias = "";
				}
				if($download['hour'] == 1)
				{	
					if($años || $meses || $dias)
					{
						$horas = ", 1 ".$lang->hour;
					}else{
						$horas = "1 ".$lang->hour;
					}
				}elseif($download['hour'] > 1){
					if($años || $meses || $dias)
					{
						$horas = ", ".$download['hour']." ".$lang->hours;
					}else{
						$horas = $download['hour']." ".$lang->hours;
					}
				}else{
					$horas = "";
				}
				if(empty($años) && empty($meses) && empty($dias) && empty($horas))
				{
					$años.$meses.$dias.$horas = $lang->none;
				} 
				$download['name'] = str_replace($mybb->input['text'],"<strong><span style='BACKGROUND-COLOR: cyan'><font color='red'>{$mybb->input['text']}</font></span></strong>",$download['name']);
				$table->construct_cell("<a href='index.php?module=downloads/options&action=edit&did={$download['did']}'><strong>".$download['name']."</strong></a>");
				$table->construct_cell($download['threads'], array("class" => "align_center"));
				$table->construct_cell($download['posts'], array("class" => "align_center"));
				$table->construct_cell($download['reputation'], array("class" => "align_center"));
				$table->construct_cell($años.$meses.$dias.$horas, array("class" => "align_center"));
				$table->construct_cell("<a href='index.php?module=downloads/options&action=edit&did={$download['did']}'><strong>".$lang->edit."</strong></a>", array('class' => 'align_center'));
				$table->construct_row();
			}
			if($table->num_rows() == 1)
			{
				$lang->emptysearchdownloads = $lang->sprintf($lang->emptysearchdownloads, $mybb->input['text']);
				$table->construct_cell($lang->emptysearchdownloads, array('colspan' => 6, 'class' => 'align_center'));
				$table->construct_row();
			}
			$table->output($lang->options);
			$page->output_footer();
		}
	}
	if($error)
	{
		$page->output_inline_error($error);
	}
	$category_select = "<select name=\"category[]\" id=\"category[]\" multiple=\"multiple\" style=\"width: 310px;\" size=\"5\">";
	$category_select .= "<option value=\"-1\" selected=\"selected\">{$lang->allcategorys}</option>";
	$query_category = $db->simple_select("downloads_cat", "*", "");
	while($category = $db->fetch_array($query_category))
	{
		$category_select .= "<option value=\"{$category['dcid']}\">{$category['name']}</option>";
	}
	$category_select .= "</select>";
	$form = new Form("index.php?module=downloads/options&amp;action=search", "post");
	echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
	$form_container = new FormContainer($lang->searchdownload);
	$form_container->output_row($lang->name."<em>*</em>",$lang->namedownloadsearch, $form->generate_text_box('text',$mybb->input['text'], array('id' => 'text')), 'text');
	$form_container->output_row($lang->category."<em>*</em>",$lang->categorysearchtext, $category_select);
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->search);
	$form->output_submit_wrapper($buttons);
	$form->end();
}

$page->output_footer();
exit;

?>
