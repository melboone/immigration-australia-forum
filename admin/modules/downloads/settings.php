<?php

/**
 * MyBB 1.6 Spanish Language Pack
 * Copyright 2011 Edson Ordaz, All Rights Reserved
 * 
 * settings V1.0
 *
 * $Id: settings.php 2011-07-11 Edson Ordaz $
 */
 
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$lang->load('downloads_config');
$lang->load('downloads');

$page->add_breadcrumb_item($lang->downloads, 'index.php?module=downloads');
$page->add_breadcrumb_item($lang->settingsdownloads, 'index.php?module=downloads/settings');
$page->output_header($lang->settingsdownloads);

if(!$mybb->input['action']) 
{	
	if($mybb->request_method == "post")
	{
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			$error[] = $lang->notpostcode;
		}
		if(intval($mybb->input['paginationcategorys']) < 1)
		{
			$error[] = $lang->notpaginationcat;
		}
		if(intval($mybb->input['paginationarchive']) < 1)
		{
			$error[] = $lang->notpaginationarchive;
		}
		$mybb->input['downspermit'] = intval(abs($mybb->input['downspermit']));
		$mybb->input['threadsrequer'] = intval(abs($mybb->input['threadsrequer']));
		$mybb->input['postrequest'] = intval(abs($mybb->input['postrequest']));
		$mybb->input['reputationrequest'] = intval(abs($mybb->input['reputationrequest']));
		$mybb->input['hour'] = intval(abs($mybb->input['hour']));
		$mybb->input['day'] = intval(abs($mybb->input['day']));
		$mybb->input['month'] = intval(abs($mybb->input['month']));
		$mybb->input['year'] = intval(abs($mybb->input['year']));
		$time = ($mybb->input['year'] * 31556952) + ($mybb->input['month'] * 2551443) + ($mybb->input['day'] * 86400) + ($mybb->input['hour'] * 3600);
		if($time < 0)
		{
			$error[] = $lang->notdatepast;
		}
		if(!$error)
		{
			$save_settings = array(
				"downloads_active" => intval($mybb->input['activeplug']),
				"downloads_usercreatedownloads" => intval($mybb->input['createdownloads']),
				"downloads_validatedownloads" => intval($mybb->input['validate']),
				"downloads_groupscreatedownloads" => implode(",",$mybb->input['groupscreate']),
				"downloads_canhtmlcomments" => intval($mybb->input['html']),
				"downloads_canbbcodecomments" => intval($mybb->input['bbcode']),
				"downloads_cansmiliescomments" => intval($mybb->input['smilies']),
				"downloads_canimgcomments" => intval($mybb->input['imgcode']),
				"downloads_canbadwordcomments" => intval($mybb->input['badword']),
				"downloads_showeditor" => intval($mybb->input['editorbbcode']),
				"downloads_counthreads" => $mybb->input['counthreads'],
				"downloads_maxcreated" => $mybb->input['downspermit'],
				"downloads_threadsrequest" => $mybb->input['threadsrequer'],
				"downloads_postsrequest" => $mybb->input['postrequest'],
				"downloads_reputationrequest" => $mybb->input['reputationrequest'],
				"downloads_timeonlinerequest" => $time,
				"downloads_timeonlinerequest_hour" => $mybb->input['hour'],
				"downloads_timeonlinerequest_day" => $mybb->input['day'],
				"downloads_timeonlinerequest_month" => $mybb->input['month'],
				"downloads_timeonlinerequest_year" => $mybb->input['year'],
				"downloads_showportada" => $mybb->input['showportada'],
				"downloads_portadacategory" => $mybb->input['portadacategory'],
				"downloads_sizeportada" => $mybb->input['sizeportada'],
				"downloads_sizeimages" => $mybb->input['sizeimages'],
				"downloads_paginationcategorys" => $mybb->input['paginationcategorys'],
				"downloads_paginationarchive" => $mybb->input['paginationarchive']
			);
			
			foreach($save_settings as $name => $new_value)
			{
				$array_insert = array(
					"value" => $new_value
				);
				$db->update_query("settings", $array_insert,"name='".$name."'");
			}
			rebuildsettings();
			flash_message($lang->successsavesettings, 'success');
			admin_redirect("index.php?module=downloads/settings");
		}
	}
	if($error)
	{
		$page->output_inline_error($error);
	}
	$tabs = array(
		"download" => $lang->general,
		"comments" => $lang->comments,	
		"extras" => $lang->extras	,
		"images" => $lang->images	
	);
	$page->output_tab_control($tabs);
	
	$form = new Form("index.php?module=downloads/settings", "post");
	echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
	
	
	echo "<div id=\"tab_download\">";
	$explode = explode(",",$mybb->settings['downloads_groupscreatedownloads']);
	$array_map = array_map(intval,$explode);
	$form_container = new FormContainer($lang->general);
	$form_container->output_row($lang->activedownloads."<em>*</em>",$lang->activedownloadsdes, $form->generate_yes_no_radio('activeplug',$mybb->settings['downloads_active'], array('id' => 'activeplug')), 'activeplug');
	$form_container->output_row($lang->createdownsusers."<em>*</em>",$lang->createdownsusersdes, $form->generate_yes_no_radio('createdownloads',$mybb->settings['downloads_usercreatedownloads'], array('id' => 'createdownloads')), 'createdownloads');
	$form_container->output_row($lang->validatedownloads."<em>*</em>",$lang->validatedownloadsdes, $form->generate_yes_no_radio('validate',$mybb->settings['downloads_validatedownloads'], array('id' => 'validate')), 'validate');
	$form_container->output_row($lang->groupssenddowns."<em>*</em>",$lang->groupssenddownsdes, $form->generate_group_select('groupscreate[]', $array_map, array('id' => 'groupscreate[]', 'size' => 5, 'multiple' => 'multiple')), 'groupscreate');
	$form_container->output_row($lang->paginationcategorys."<em>*</em>",$lang->paginationcategorysdes, $form->generate_text_box('paginationcategorys', $mybb->settings['downloads_paginationcategorys'], array('id' => 'paginationcategorys')), 'paginationcategorys');
	$form_container->output_row($lang->paginationarchive."<em>*</em>",$lang->paginationarchivedes, $form->generate_text_box('paginationarchive', $mybb->settings['downloads_paginationarchive'], array('id' => 'paginationarchive')), 'paginationarchive');
	$form_container->end();
	echo "</div>";
	
	echo "<div id=\"tab_comments\">";
	$form_container = new FormContainer($lang->comments);
	$form_container->output_row($lang->canhtmlcomments."<em>*</em>",$lang->canhtmlcommentsdes, $form->generate_yes_no_radio('html',$mybb->settings['downloads_canhtmlcomments'], array('id' => 'html')), 'html');
	$form_container->output_row($lang->canbbcodecomments."<em>*</em>",$lang->canbbcodecommentsdes, $form->generate_yes_no_radio('bbcode',$mybb->settings['downloads_canbbcodecomments'], array('id' => 'bbcode')), 'bbcode');
	$form_container->output_row($lang->cansmiliescomments."<em>*</em>",$lang->cansmiliescommentsdes, $form->generate_yes_no_radio('smilies',$mybb->settings['downloads_cansmiliescomments'], array('id' => 'smilies')), 'smilies');
	$form_container->output_row($lang->canimgcomments."<em>*</em>",$lang->canimgcommentsdes, $form->generate_yes_no_radio('imgcode',$mybb->settings['downloads_canimgcomments'], array('id' => 'imgcode')), 'imgcode');
	$form_container->output_row($lang->canbadwordcomments."<em>*</em>",$lang->canbadwordcommentsdes, $form->generate_yes_no_radio('badword',$mybb->settings['downloads_canbadwordcomments'], array('id' => 'badword')), 'badword');
	$form_container->output_row($lang->showbbcodeeditor."<em>*</em>",$lang->showbbcodeeditordes, $form->generate_yes_no_radio('editorbbcode',$mybb->settings['downloads_showeditor'], array('id' => 'editorbbcode')), 'editorbbcode');
	$form_container->end();
	echo "</div>";
	
	echo "<div id=\"tab_extras\">";
	$form_container = new FormContainer($lang->extras);
	$form_container->output_row($lang->counbyt."<em>*</em>", $lang->counbytdes, $form->generate_yes_no_radio('counthreads',$mybb->settings['downloads_counthreads'], array('id' => 'counthreads')), 'counthreads');
	$form_container->output_row($lang->downspermitid."<em>*</em>",$lang->downspermitiddes, $form->generate_text_box('downspermit',$mybb->settings['downloads_maxcreated'], array('id' => 'downspermit')), 'downspermit');
	$form_container->output_row($lang->threadsrequer."<em>*</em>",$lang->threadsrequerdes, $form->generate_text_box('threadsrequer',$mybb->settings['downloads_threadsrequest'], array('id' => 'threadsrequer')), 'threadsrequer');
	$form_container->output_row($lang->postrequest."<em>*</em>",$lang->postrequestdes, $form->generate_text_box('postrequest',$mybb->settings['downloads_postsrequest'], array('id' => 'postrequest')), 'postrequest');
	$form_container->output_row($lang->reputationrequest."<em>*</em>",$lang->reputationrequestdes, $form->generate_text_box('reputationrequest',$mybb->settings['downloads_reputationrequest'], array('id' => 'reputationrequest')), 'reputationrequest');
	$form_container->output_row($lang->timeonlinerequest."<em>*</em>",$lang->timeonlinerequestdes, $lang->hours.": ".$form->generate_text_box('hour',$mybb->settings['downloads_timeonlinerequest_hour'], array('id' => 'hour', 'style' => 'width: 60px;'))." ".$lang->days.": ".$form->generate_text_box('day',$mybb->settings['downloads_timeonlinerequest_day'], array('id' => 'day', 'style' => 'width: 60px;'))." ".$lang->months.": ".$form->generate_text_box('month',$mybb->settings['downloads_timeonlinerequest_month'], array('id' => 'month', 'style' => 'width: 60px;'))."  ".$lang->years.": ".$form->generate_text_box('year',$mybb->settings['downloads_timeonlinerequest_year'], array('id' => 'year', 'style' => 'width: 60px;')), 'timeonline');
	$form_container->end();
	echo "</div>";
	
	
	echo "<div id=\"tab_images\">";
	$form_container = new FormContainer($lang->images);
	$form_container->output_row($lang->showportadaincategory."<em>*</em>",$lang->showportadaincategorydes, $form->generate_yes_no_radio('showportada',$mybb->settings['downloads_showportada'], array('id' => 'showportada')), 'showportada');
	$form_container->output_row($lang->maxsizeportadacategory."<em>*</em>",$lang->maxsizeportadacategorydes, $form->generate_text_box('portadacategory',$mybb->settings['downloads_portadacategory'], array('id' => 'portadacategory')), 'portadacategory');
	$form_container->output_row($lang->sizeportada."<em>*</em>",$lang->sizeportadades, $form->generate_text_box('sizeportada',$mybb->settings['downloads_sizeportada'], array('id' => 'sizeportada')), 'sizeportada');
	$form_container->output_row($lang->sizeimages."<em>*</em>",$lang->sizeimagesdes, $form->generate_text_box('sizeimages',$mybb->settings['downloads_sizeimages'], array('id' => 'sizeimages')), 'sizeimages');
	$form_container->end();
	echo "</div>";
	
	
	$buttons[] = $form->generate_submit_button($lang->save);
	$form->output_submit_wrapper($buttons);
	$form->end();
}	

$page->output_footer();
exit;

?>
