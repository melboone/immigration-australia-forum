<?php

/**
 * MyBB 1.6 Spanish Language Pack
 * Copyright 2011 Edson Ordaz, All Rights Reserved
 * 
 * templates V1.0
 *
 * $Id: templates.php 2011-07-11 Edson Ordaz $
 */
 
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$lang->load('downloads');

$page->add_breadcrumb_item($lang->downloads, 'index.php?module=downloads');
$page->add_breadcrumb_item($lang->templates, 'index.php?module=downloads/templates');
if($mybb->input['action'] == "edit")
{	
	$query = $db->simple_select('templates', '*', 'title LIKE "downloads_%" AND title="'.$mybb->input['title'].'"');
	$template = $db->fetch_array($query);
	$template['title'] = str_replace("downloads_",'',$template['title']);
	$page->add_breadcrumb_item($lang->edittemplate.": ".$template['title']);
	$db->free_result($query);
}
$page->output_header($lang->templates);

if(!$mybb->input['action']) 
{	
	$table = new Table;
	$table->construct_header($lang->nametemmplates);
	$table->construct_header($lang->controls, array("width" => "10%","class" => "align_center"));
	$table->construct_row();
	
	$query = $db->simple_select('templates', '*', 'title LIKE "downloads_%" AND sid="-1"', array('order_by' => 'title', 'order_dir' => 'ASC'));
	while($template = $db->fetch_array($query))
	{
		$name_recorted = str_replace("downloads_",'',$template['title']);
		$table->construct_cell("<a href='index.php?module=downloads/templates&action=edit&title=".$template['title']."' />".$name_recorted."</a>");
		$popup = new PopupMenu("tid_".$template['title'], $lang->options);
		$popup->add_item($lang->editemplate, "index.php?module=downloads/templates&action=edit&title=".$template['title']);
		$popup->add_item($lang->revertoriginal, "index.php?module=downloads/templates&action=revert&title=".$template['title']);
		$Popuss = $popup->fetch();
		$table->construct_cell($Popuss, array('class' => 'align_center'));
		$table->construct_row();
	}
	
	$table->output("Downloads Templates");
}	
elseif($mybb->input['action'] == "edit")
{
	if($mybb->request_method == "post")
	{
		$edit_update = array(
			"template" => $db->escape_string($mybb->input['template'])
		);
		$db->update_query("templates", $edit_update,"title='".$mybb->input['title']."'");
		flash_message($lang->successtemplatesave, 'success');
		if($mybb->input['savecontinue'])
		{
			$continue = "&action=edit&title=".$mybb->input['title'];
		}
		admin_redirect("index.php?module=downloads/templates".$continue);
	}
	$query = $db->simple_select('templates', '*', 'title LIKE "downloads_%" AND title="'.$mybb->input['title'].'"');
	$template = $db->fetch_array($query);
	if(!$template['tid'])
	{
		flash_message($lang->notexisttemplate, 'error');
		admin_redirect("index.php?module=downloads/templates");
	}
	if($admin_options['codepress'] != 0)
	{
		$page->extra_header .= '
	<link type="text/css" href="./jscripts/codepress/languages/codepress-mybb.css" rel="stylesheet" id="cp-lang-style" />
	<script type="text/javascript" src="./jscripts/codepress/codepress.js"></script>
	<script type="text/javascript">
		CodePress.language = \'mybb\';
	</script>';
	}
	$template['title'] = str_replace("downloads_",'',$template['title']);
	$form = new Form("index.php?module=downloads/templates&action=edit", "post");
	echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
	echo $form->generate_hidden_field("title", $mybb->input['title']);
	$form_container = new FormContainer($lang->edittemplate.": ".$template['title']);
	$form_container->output_row($lang->nametemplate,$lang->nametemplatedes, "<input type=\"text\" readonly=\"readonly\" name=\"name\" value=\"".htmlspecialchars($template['title'])."\" class=\"text_input\" />");
	$form_container->output_row($lang->settemplates,$lang->settemplatesdes, "<select><option>Downloads Templates</option></select>");
	$form_container->output_row("", "", $form->generate_text_area('template', $template['template'], array('id' => 'template', 'class' => 'codepress php', 'style' => 'width: 100%; height: 500px;')), 'template');$form_container->end();
	
	$buttons[] = $form->generate_submit_button($lang->saveandcontinuedit,array('name' => 'savecontinue', 'id' => 'savecontinue'));
	$buttons[] = $form->generate_submit_button($lang->saveandexit,array('name' => 'saveexit', 'id' => 'saveexit'));
	$form->output_submit_wrapper($buttons);
	$form->end();
	$db->free_result($query);
	if($admin_options['codepress'] != 0)
	{
		echo "<script type=\"text/javascript\">
	Event.observe('edit_template', 'submit', function()
	{
		if($('template_cp')) {
			var area = $('template_cp');
			area.id = 'template';
			area.value = template.getCode();
			area.disabled = false;
		}
	});
</script>";
	}
}
elseif($mybb->input['action'] == "revert")
{
	$query = $db->simple_select('templates', '*', 'title LIKE "downloads_%" AND title="'.$mybb->input['title'].'"');
	$template_check = $db->fetch_array($query);
	if(!$template_check['tid'])
	{
		flash_message($lang->invalidtemplate, 'error');
		admin_redirect("index.php?module=downloads/templates");
	}
	if($mybb->input['title'] == "downloads_home")
	{
		$title = "downloads_home";
		$template = $db->escape_string('<html>
<head><title>{$lang->downloads}</title>
{$headerinclude}
</head>
{$header}
<body>
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="4"><div class="expcolimage"><img src="{$theme[\'imgdir\']}/collapse.gif" id="categorys_img" class="expander" alt="[-]" title="[-]" /></div>
<strong>{$lang->categorys}</strong></td>
</tr>
<tr>
<td class="tcat" colspan="2"><span class="smalltext"><strong>Descargas</strong></span></td>
<td class="tcat" width="10%" align="center"><span class="smalltext"><strong>Temas</strong></span></td>
<td class="tcat" width="20%" align="center"><span class="smalltext"><strong>&Uacute;ltima Descarga</span></strong></td>
</tr>
<tbody id="categorys_e">
{$categorys}
</tbody>
</table>
{$pagination}
</body>
{$footer}
</html>');
	}
	if($mybb->input['title'] == "downloads_category")
	{
		$title = "downloads_category";
		$template = $db->escape_string('<tr>
<td class="{$color}" width="5%" align="center"><img src="{$category[\'ficon\']}" /></td>
<td class="{$color}"><strong><a href="downloads.php?category={$category[\'dcid\']}">{$category[\'name\']}</a></strong><br /><span class="smalltext">{$category[\'description\']}</span></td>
<td class="{$color}" align="center">{$numthreads}</td>
<td class="{$color}" align="right" valign="top" width="22%"><span class="smalltext"><a href="downloads.php?archive={$download[\'did\']}" /><strong>{$lastdownload}</strong></a>
<br />
{$date} {$time}
<br />
por {$username}
</span>
</td>
</tr>');
	}
	if($mybb->input['title'] == "downloads_archives")
	{
		$title = "downloads_archives";
		$template = $db->escape_string('<html>
<head><title>{$lang->title_category}</title>
{$headerinclude}
</head>
{$header}
<body>
{$newdownload}
<br />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><div class="expcolimage"><img src="{$theme[\'imgdir\']}/collapse.gif" id="category_{$category[\'dcid\']}_img" class="expander" alt="[-]" title="[-]" /></div>
<strong>{$category[\'name\']}</strong></td>
</tr>
<tr>
{$tcatimage}
<td class="tcat"><strong><span class="smalltext">{$lang->name}/{$lang->description}</span></strong></td>
<td class="tcat" align="center"><strong><span class="smalltext">{$lang->views}</span></strong></td>
<td class="tcat" align="center"><strong><span class="smalltext">{$lang->downloads}</span></strong></td>
<td class="tcat" align="center"><strong><span class="smalltext">{$lang->uploadby}</span></strong></td>
</tr>
<tbody id="category_{$category[\'dcid\']}_e">
{$archives_list}
</tbody>
</table>
{$pagination}
<br />
{$newdownload}
</body>
{$footer}
</html>');
	}
	if($mybb->input['title'] == "downloads_archives_list")
	{
		$title = "downloads_archives_list";
		$template = $db->escape_string('<tr><td class="{$color}">
<a href="downloads.php?archive={$archive[\'did\']}" /><strong>{$archive[\'name\']}</strong></a>
<br /><span class="smalltext">{$archive[\'shortdesc\']}</span></td>
<td class="{$color}" width="10%" align="center">{$archive[\'views\']}</td>
<td class="{$color}" width="10%" align="center">{$archive[\'downloads\']}</td>
<td class="{$color}" width="10%" align="center">{$username}</td>
</tr></tr>');
	}
	if($mybb->input['title'] == "downloads_archives_list_front")
	{
		$title = "downloads_archives_list_front";
		$template = $db->escape_string('<tr>
<td class="{$color}" width="5%"><a href="downloads.php?archive={$archive[\'did\']}" /><img src="{$archive[\'image\']}" width="{$width}"  /></a></td>
<td class="{$color}">
<a href="downloads.php?archive={$archive[\'did\']}" /><strong>{$archive[\'name\']}</strong></a>
<br /><span class="smalltext">{$archive[\'shortdesc\']}</span></td>
<td class="{$color}" width="10%" align="center">{$archive[\'views\']}</td>
<td class="{$color}" width="10%" align="center">{$archive[\'downloads\']}</td>
<td class="{$color}" width="10%" align="center">{$username}</td>
</tr>');
	}
	if($mybb->input['title'] == "downloads_archives_view")
	{
		$title = "downloads_archives_view";
		$template = $db->escape_string('<html>
<head><title>{$archive[\'name\']}</title>
{$headerinclude}
<style>
.avatar_view {
	margin: 5px;
	padding: 7px;
	border:1px solid #CCCCCC;
}
</style>
</head>
{$header}
<body>
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="3"><strong>{$archive[\'name\']}</strong></td>
</tr>
<tr>
<td class="tcat" colspan="2"><strong><span class="smalltext">{$lang->download}</span></strong></td>
<td class="tcat" align="center"><strong><span class="smalltext">{$lang->uploader}</span></strong></td>
</tr>
<tr>
<td class="trow1" width="10px"><img src="{$archive[\'image\']}" {$front_width_height}/></td>
<td class="trow1" valign="top"><font color="blue" size="6"><strong>{$archive[\'name\']}</strong></font>
<br /><span class="smalltext">
{$lang->date}: {$date} at {$time}
<br />
{$lang->visits}: {$archive[\'views\']}
<br />
{$lang->downloads}: {$archive[\'downloads\']}
</span></td>
<td class="trow1" width="15%" align="center" valign="top">
{$avatar}
<br />{$username}
<span class="smalltext">
<br />{$lang->messages}: {$user[\'postnum\']}
<br />{$lang->reputation}: {$reputation}
<br />{$lang->register}: {$register} 
</span>
<br />
<a href="member.php?action=emailuser&uid={$user[\'uid\']}"><img src="images/english/postbit_email.gif" /></a>&nbsp;&nbsp;&nbsp;<a href="private.php?action=send&uid={$user[\'uid\']}"><img src="images/english/postbit_pm.gif" /></a>
</td>
</tr>
<tr>
<td colspan="3" class="trow2">
<strong><font size="5" color="blue">{$lang->description}:</font></strong><br />
{$archive[\'description\']}
{$screenshots}
<br /><br /><br />
<font color="blue" size="5"><strong>{$lang->download}</strong></font>
<br />
{$downloadslinks}
</td>
</tr>
</table>
{$comments}
</body>
{$footer}
</html>');
	}
	if($mybb->input['title'] == "downloads_archives_screenshots")
	{
		$title = "downloads_archives_screenshots";
		$template = $db->escape_string('<br />
<br />
<font color="blue" size="5"><strong>{$lang->images}</strong></font>
<br />
{$images}');
	}
	if($mybb->input['title'] == "downloads_archives_comments")
	{
		$title = "downloads_archives_comments";
		$template = $db->escape_string('<br />
<table id="comments" name="comments" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>{$lang->edicomentsby}</strong></td>
</tr>
{$comments_list}
<tr><td class="trow2" colspan="2">
{$pagination}
</td>
</tr>
<form action="downloads.php" method="post">
<tr>
<td class="trow1" align="center" colspan="2">
<textarea name="message" id="message" rows="10" cols="90" tabindex="2">{$message}</textarea>
{$codebuttons}
</td>
</tr>
<tr>
<td class="trow2" align="center" colspan="2">
<input type="hidden" name="did" value="{$archive[\'did\']}" />
<input type="hidden" name="category" value="{$archive[\'category\']}" />
<input type="hidden" name="action" value="do_comment" />
<input type="submit" class="button" name="submit" value="{$lang->sendcomment}" />
</td>
</tr>
</form>
</table>');
	}
	if($mybb->input['title'] == "downloads_archives_comments_list")
	{
		$title = "downloads_archives_comments_list";
		$template = $db->escape_string('<tr>
<td class="{$color}" rowspan="2" width="100" style="text-align: center; vertical-align: top;">
<img style="width: 90px;" src="{$user[\'avatar\']}" />
</td>
<td class="{$color}" >
{$username}<small style="font-size: 10px;"> ({$date} at {$time})</small>
<span style="font-size: 10px;">
{$comment_manage}
</span>
</td>
</tr>
<tr>
<td class="{$color}" >
{$text}
</td></tr>');
	}
	if($mybb->input['title'] == "downloads_archives_comments_moderation")
	{
		$title = "downloads_archives_comments_moderation";
		$template = $db->escape_string('<br />
<a href="downloads.php?action=comment&manage=edit&dcid={$comment[\'dcid\']}" /><strong>{$lang->edit}</strong></a> - <a href="downloads.php?action=comment&manage=delete&dcid={$comment[\'dcid\']}" /><strong>{$lang->delete}</strong></a>');
	}
	if($mybb->input['title'] == "downloads_comments_edit")
	{
		$title = "downloads_comments_edit";
		$template = $db->escape_string('<html>
<head><title>{$lang->editcomment}</title>
{$headerinclude}
</head>
{$header}
<body>
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><strong>{$lang->edicomentsby}</strong></td>
</tr>
<form action="downloads.php?action=comment" method="post">
<input type="hidden" name="manage" value="do_editcomment" />
<input type="hidden" name="dcid" value="{$comments[\'dcid\']}" />
<input type="hidden" name="did" value="{$archive[\'did\']}" />
<tr>
<td class="trow1" align="center">
<textarea name="message" id="message" rows="15" cols="90" tabindex="2">{$comments[\'comment\']}</textarea>
{$codebuttons}
</td>
</tr>
<tr>
<td class="trow2" align="center">
<input type="submit" class="button" name="submit" value="{$lang->sendcomment}" />
</td>
</tr>
</form>
</table>
</body>
{$footer}
</html>');
	}
	if($mybb->input['title'] == "downloads_download")
	{
		$title = "downloads_download";
		$template = $db->escape_string('<html>
<head>
<title>{$lang->downloadarchive}</title>
{$headerinclude}
</head>
<body>
<table>
<tr>
<td align="left">
<a href="{$mybb->settings[\'bburl\']}/index.php"><img src="{$theme[\'logo\']}" alt="{$mybb->settings[\'bbname\']}" title="{$mybb->settings[\'bbname\']}" /></a>
</td>
<td align="left" valign="middle">
{$lang->pageexterna} {$mybb->settings[\'bbname\']}.
<br />
Estas descargando el archivo <a href="downloads.php?archive={$archive[\'did\']}" /><strong>{$archive[\'name\']}</strong></a>.
</td>
</tr>
</table>
<iframe src="{$link[\'url\']}" marginwidth="1" marginheight="1" name="marco" border="0" width="100%" frameborder="0" height="600"></iframe>
</body>
</html>');
	}
	if($mybb->input['title'] == "downloads_newdownload_button")
	{
		$title = "downloads_newdownload_button";
		$template = $db->escape_string('<div style="float: right">
<a href="downloads.php?newdownload={$category[\'dcid\']}"><img src="images/newdownload.gif" /></a>
</div>
<br />');
	}
	if($mybb->input['title'] == "downloads_newdownload")
	{
		$title = "downloads_newdownload";
		$template = $db->escape_string('<html>
<head><title>{$lang->newdownloads}</title>
{$headerinclude}
</head>
<body>
{$header}
{$preview}
{$downloads_errors}
<form action="downloads.php?newdownload={$dcid}" method="post" enctype="multipart/form-data" name="input">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>{$lang->newdownloads}</strong></td>
</tr>
<tr>
<td class="trow1" width="20%"><strong>{$lang->titledownloads}</strong></td>
<td class="trow1"><input type="text" class="textbox" name="name" size="40" maxlength="85" value="{$name}" tabindex="1" /></td>
</tr>
<tr>
<td class="trow2" width="20%"><strong>{$lang->shortdesc}</strong></td>
<td class="trow2"><input type="text" class="textbox" name="shortdesc" size="40" maxlength="85" value="{$shortdesc}" tabindex="1" /></td>
</tr>
<tr>
<td class="trow1" valign="top"><strong>{$lang->descriptiondownload}</strong>{$smilieinserter}</td>
<td class="trow1">
<textarea name="description" id="message" rows="20" cols="70" tabindex="2">{$description}</textarea>
{$codebuttons}
</td>
</tr>
<tr>
<td class="trow2" width="20%"><strong>{$lang->front}</strong>
<br />
<span class="smalltext">{$lang->enterurlimage}</span>
</td>
<td class="trow2"><input type="text" class="textbox" name="image" size="40" maxlength="85" value="{$image}" tabindex="1" /></td>
</tr>
<tr>
<td class="trow1" width="20%"><strong>{$lang->urldownload}</strong><br /><span class="smalltext">{$lang->urlsnumberenter}</span></td>
<td class="trow1"><input type="text" class="textbox" name="url" size="10" maxlength="85" value="1" tabindex="1" /></td>
</tr>
<tr>
<td class="trow2" valign="top">
<strong>{$lang->images}</strong><br /><span class="smalltext">{$lang->imagesarchive}</span>
</td>
<td class="trow2" valign="top">
<span class="smalltext"><label><input type="checkbox" class="checkbox" name="chekimages" value="1" /><strong>{$lang->wishenterimages}</strong></label><br />
{$lang->numberimages} <input type="text" class="textbox" name="numimages" value="4" size="10" /> ({$lang->maximiumten})</span>
</td>
</tr>
</table>
<br />
<div style="text-align:center"><input type="submit" class="button" name="submit" value="{$lang->publicdownload}" tabindex="4" accesskey="s" /></div>
</form>
</body>
{$footer}
</html>');
	}
	if($mybb->input['title'] == "downloads_newdownload_addimages")
	{
		$title = "downloads_newdownload_addimages";
		$template = $db->escape_string('<html>
<head><title>{$lang->addimages}</title>
{$headerinclude}
</head>
<body>
{$header}
{$image_errors}
<form action="downloads.php?newimages={$did}&images={$images}" method="post" enctype="multipart/form-data" name="input">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>{$lang->addimages}</strong></td>
</tr>
{$imagesbox}
</table>
<br />
<div style="text-align:center"><input type="submit" class="button" name="submit" value="{$lang->addimages}" tabindex="4" accesskey="s" />  </div>
</form>
</body>
{$footer}
</html>');
	}
	if($mybb->input['title'] == "downloads_newdownload_addlinks")
	{
		$title = "downloads_newdownload_addlinks";
		$template = $db->escape_string('<html>
<head><title>{$lang->addlinks}</title>
{$headerinclude}
</head>
<body>
{$header}
{$image_errors}
<form action="downloads.php?newlinks={$download[\'did\']}&urls={$mybb->input[\'urls\']}&boximg={$mybb->input[\'boximg\']}&images={$mybb->input[\'images\']}" method="post" enctype="multipart/form-data" name="input">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>{$lang->addlinks}</strong></td>
</tr>
{$linksbox}
</table>
<br />
<div style="text-align:center"><input type="submit" class="button" name="submit" value="{$lang->addlinks}" tabindex="4" accesskey="s" />  </div>
</form>
</body>
{$footer}
</html>');
	}
	if($mybb->input['title'] == "downloads_newdownload_addlinks_box")
	{
		$title = "downloads_newdownload_addlinks_box";
		$template = $db->escape_string('<tr>
<td class="{$color}">
<strong>{$lang->link} {$number}:</strong>
<br />
<dd>
<span class="smalltext">{$lang->name}:</span>
<input type="text" class="textbox" name="name_{$number}" size="60" maxlength="85" value="{$name}" tabindex="1" />
<br /><br />
<span class="smalltext">&nbsp;&nbsp;{$lang->enlace}:</span>
<input type="text" class="textbox" name="url_{$number}" size="60" maxlength="85" value="{$url}" tabindex="1" />
</dd>
<br />
</td>
</tr>');
	}
	if($mybb->input['title'] == "downloads_newdownload_addimages_box")
	{
		$title = "downloads_newdownload_addimages_box";
		$template = $db->escape_string('<tr>
<td class="{$color}" width="10%">Imagen {$number}:</td>
<td class="{$color}"><input type="text" class="textbox" name="image_{$number}" size="60" maxlength="85" value="{$image}" tabindex="1" /></td>
</tr>');
	}
	if($mybb->input['title'] == "downloads_archives_links")
	{
		$title = "downloads_archives_links";
		$template = $db->escape_string('<a href="downloads.php?download={$link[\'generate\']}" />{$link[\'text\']}</a>
<br />');
	}
	
	
	$edit_template = array(
		"template" => $template
	);
	$db->update_query("templates", $edit_template,"title='".$title."'");
	flash_message($lang->templaterevertsuccess, 'success');
	admin_redirect("index.php?module=downloads/templates");
}


$page->output_footer();
exit;

?>
