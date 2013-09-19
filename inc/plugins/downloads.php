<?php

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("datahandler_post_insert_thread", "download_count_threads");
$plugins->add_hook("class_moderation_delete_thread_start", "download_deletenum_thread");

function downloads_info()
{
	global $lang;
	$lang->load('downloads_config');
	return array(
		"name"			=> $lang->downloads_plugin,
		"description"	=> $lang->downloads_plugin_des,
		"website"		=> "mailto:nicedo_eeos@hotmail.com",
		"author"		=> "Edson Ordaz",
		"authorsite"	=> "mailto:nicedo_eeos@hotmail.com",
		"version"		=> "2.0.1",
		"guid"			=> "3a7f27be7a6e2d67dab4ee3b5a36f29c",
		"compatibility"   => "16*"
	);
}

function downloads_is_installed(){
	global $mybb, $db;
  	if($db->table_exists("downloads"))
	{
		return true;
	}
}

function downloads_install() 
{
	global $mybb, $db, $lang,$cache;
	
	$lang->load('downloads_config');
	$lang->load('downloads');
	
  	if(!$db->table_exists("downloads"))
	{
		$db->query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."downloads` (
  `did` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL DEFAULT '',
  `orden` int(10) NOT NULL,
  `uid` int(10) NOT NULL,
  `shortdesc` text NOT NULL,
  `description` text NOT NULL,
  `image` varchar(220) NOT NULL DEFAULT '',
  `comments` int(10) NOT NULL,
  `dateline` int(20) NOT NULL,
  `downloads` int(10) NOT NULL default '0',
  `urls` int(10) NOT NULL default '0',
  `views` int(10) NOT NULL default '0',
  `pics` int(10) NOT NULL,
  `active` int(10) NOT NULL,
  `groups` varchar(220) NOT NULL DEFAULT '',
  `category` int(20) NOT NULL,
  `bbcode` int(10) NOT NULL default '0',
  `validate` int(10) NOT NULL default '0',
  `posts` int(10) NOT NULL default '0',
  `threads` int(10) NOT NULL default '0',
  `reputation` int(10) NOT NULL default '0',
  `timeonline` int(20) NOT NULL default '0',
  `hour` int(10) NOT NULL default '0',
  `day` int(10) NOT NULL default '0',
  `month` int(10) NOT NULL default '0',
  `year` int(10) NOT NULL default '0',
  PRIMARY KEY (`did`)
) ENGINE=MyISAM;");
	}
  	if(!$db->table_exists("downloads_cat"))
	{
		$db->query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."downloads_cat` (
  `dcid` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL DEFAULT '',
  `ficon` text NOT NULL,
  `description` text NOT NULL,
  `orden` int(20) NOT NULL,
  `active` int(10) NOT NULL,
  PRIMARY KEY (`dcid`)
) ENGINE=MyISAM;");
	}
  	if(!$db->table_exists("downloads_images"))
	{
		$db->query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."downloads_images` (
  `diid` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `did` int(10) NOT NULL,
  `dcid` int(10) NOT NULL,
  `image` text NOT NULL,
  `orden` int(20) NOT NULL,
  PRIMARY KEY (`diid`)
) ENGINE=MyISAM;");
	}
  	if(!$db->table_exists("downloads_urls"))
	{
		$db->query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."downloads_urls` (
  `duid` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `did` int(10) NOT NULL,
  `dcid` int(10) NOT NULL,
  `url` text NOT NULL,
  `text` text NOT NULL,
  `generate` text NOT NULL,
  `orden` int(20) NOT NULL,
  PRIMARY KEY (`duid`)
) ENGINE=MyISAM;");
	}
  	if(!$db->table_exists("downloads_comments"))
	{
		$db->query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."downloads_comments` (
  `dcid` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `did` int(10) NOT NULL,
  `category` int(10) NOT NULL,
  `uid` int(10) NOT NULL,
  `comment` text NOT NULL,
  `dateline` int(20) NOT NULL,
  PRIMARY KEY (`dcid`)
) ENGINE=MyISAM;");
	}
	
	if(!$db->field_exists("threads", "users"))  
		$db->add_column("users", "threads", "int(10) unsigned NOT NULL default '0'"); 
	if(!$db->field_exists("downloads", "users"))  
		$db->add_column("users", "downloads", "int(10) unsigned NOT NULL default '0'"); 
		
	$update_cache = array(
		"name" => "Validate",
		"code" => 0
	);
	$cache->update("downloads_validate", $update_cache);
		
	$query = $db->simple_select("users", "uid");
	while($user = $db->fetch_array($query))
	{
		$users[$user['uid']] = $user;
	}
	foreach($users as $user)
	{
		$query = $db->simple_select("threads", "COUNT(tid) AS threads", "uid = '".$user['uid']."'");
		$threads_count = intval($db->fetch_field($query, "threads"));
		$db->update_query("users", array("threads" => $threads_count), "uid = '".$user['uid']."'");
	}
	
	$downloads_settings = array(
		array(
			"name"			=> "downloads_active",
			"title"			=> $lang->activedownloads,
			"description"	=> $lang->activedownloadsdes,
			"optionscode"	=> "yesno",
			"value"			=> 0,
			"disporder"		=> 1,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_usercreatedownloads",
			"title"			=> $lang->createdownsusers,
			"description"	=> $lang->createdownsusersdes,
			"optionscode"	=> "yesno",
			"value"			=> 0,
			"disporder"		=> 2,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_validatedownloads",
			"title"			=> $lang->validatedownloads,
			"description"	=> $lang->validatedownloadsdes,
			"optionscode"	=> "yesno",
			"value"			=> 1,
			"disporder"		=> 3,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_groupscreatedownloads",
			"title"			=> $lang->groupssenddowns,
			"description"	=> $lang->groupssenddownsdes,
			"optionscode"	=> "text",
			"value"			=> "2,3,4,6",
			"disporder"		=> 4,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_canhtmlcomments",
			"title"			=> $lang->canhtmlcomments,
			"description"	=> $lang->canhtmlcommentsdes,
			"optionscode"	=> "yesno",
			"value"			=> "no",
			"disporder"		=> 5,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_canbbcodecomments",
			"title"			=> $lang->canbbcodecomments,
			"description"	=> $lang->canbbcodecommentsdes,
			"optionscode"	=> "yesno",
			"value"			=> 1,
			"disporder"		=> 6,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_cansmiliescomments",
			"title"			=> $lang->cansmiliescomments,
			"description"	=> $lang->cansmiliescommentsdes,
			"optionscode"	=> "yesno",
			"value"			=> 1,
			"disporder"		=> 7,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_canimgcomments",
			"title"			=> $lang->canimgcomments,
			"description"	=> $lang->canimgcommentsdes,
			"optionscode"	=> "yesno",
			"value"			=> "no",
			"disporder"		=> 8,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_canbadwordcomments",
			"title"			=> $lang->canbadwordcomments,
			"description"	=> $lang->canbadwordcomments,
			"optionscode"	=> "yesno",
			"value"			=> "no",
			"disporder"		=> 9,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_showeditor",
			"title"			=> $lang->showbbcodeeditor,
			"description"	=> $lang->showbbcodeeditordes,
			"optionscode"	=> "yesno",
			"value"			=> 0,
			"disporder"		=> 10,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_counthreads",
			"title"			=> $lang->counbyt,
			"description"	=> $lang->counbytdes,
			"optionscode"	=> "yesno",
			"value"			=> "0",
			"disporder"		=> 11,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_maxcreated",
			"title"			=> $lang->downspermitid,
			"description"	=> $lang->downspermitiddes,
			"optionscode"	=> "text",
			"value"			=> "0",
			"disporder"		=> 12,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_threadsrequest",
			"title"			=> $lang->threadsrequer,
			"description"	=> $lang->threadsrequerdes,
			"optionscode"	=> "text",
			"value"			=> "0",
			"disporder"		=> 13,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_postsrequest",
			"title"			=> $lang->postrequest,
			"description"	=> $lang->postrequestdes,
			"optionscode"	=> "text",
			"value"			=> "0",
			"disporder"		=> 14,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_reputationrequest",
			"title"			=> $lang->reputationrequest,
			"description"	=> $lang->reputationrequestdes,
			"optionscode"	=> "text",
			"value"			=> "0",
			"disporder"		=> 15,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_timeonlinerequest",
			"title"			=> $lang->timeonlinerequest,
			"description"	=> $lang->timeonlinerequestdes,
			"optionscode"	=> "text",
			"value"			=> "0",
			"disporder"		=> 16,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_timeonlinerequest_hour",
			"title"			=> $lang->hour,
			"description"	=> $lang->hour,
			"optionscode"	=> "text",
			"value"			=> "0",
			"disporder"		=> 17,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_timeonlinerequest_day",
			"title"			=> $lang->day,
			"description"	=> $lang->day,
			"optionscode"	=> "text",
			"value"			=> "0",
			"disporder"		=> 18,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_timeonlinerequest_month",
			"title"			=> $lang->month,
			"description"	=> $lang->month,
			"optionscode"	=> "text",
			"value"			=> "0",
			"disporder"		=> 19,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_timeonlinerequest_year",
			"title"			=> $lang->year,
			"description"	=> $lang->year,
			"optionscode"	=> "text",
			"value"			=> "0",
			"disporder"		=> 20,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_showportada",
			"title"			=> $lang->showportadaincategory,
			"description"	=> $lang->showportadaincategorydes,
			"optionscode"	=> "yesno",
			"value"			=> 0,
			"disporder"		=> 21,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_portadacategory",
			"title"			=> $lang->maxsizeportadacategory,
			"description"	=> $lang->maxsizeportadacategorydes,
			"optionscode"	=> "text",
			"value"			=> "80x80",
			"disporder"		=> 22,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_sizeportada",
			"title"			=> $lang->sizeportada,
			"description"	=> $lang->sizeportadades,
			"optionscode"	=> "text",
			"value"			=> "300x300",
			"disporder"		=> 23,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_sizeimages",
			"title"			=> $lang->sizeimages,
			"description"	=> $lang->sizeimagesdes,
			"optionscode"	=> "text",
			"value"			=> "300x300",
			"disporder"		=> 24,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_paginationcategorys",
			"title"			=> $lang->paginationcategorys,
			"description"	=> $lang->paginationcategorysdes,
			"optionscode"	=> "text",
			"value"			=> "15",
			"disporder"		=> 25,
			"gid"			=> 0,
		),
		array(
			"name"			=> "downloads_paginationarchive",
			"title"			=> $lang->paginationarchive,
			"description"	=> $lang->paginationarchivedes,
			"optionscode"	=> "text",
			"value"			=> "15",
			"disporder"		=> 26,
			"gid"			=> 0,
		)
	);
	foreach($downloads_settings as $insert_settings)
	{
		$db->insert_query("settings", $insert_settings);
	}
	rebuildsettings();
	change_admin_permission("downloads", true, 1);
	change_admin_permission("downloads", "downloads", 1);
	change_admin_permission("downloads", "category", 1);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_home',
		"template"	=> $db->escape_string('<html>
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
</html>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_category',
		"template"	=> $db->escape_string('<tr>
<td class="{$color}" width="5%" align="center"><img src="{$category[\'ficon\']}" /></td>
<td class="{$color}"><strong><a href="downloads.php?category={$category[\'dcid\']}">{$category[\'name\']}</a></strong><br /><span class="smalltext">{$category[\'description\']}</span></td>
<td class="{$color}" align="center">{$numthreads}</td>
{$lastdownloadbox}
</tr>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_archives',
		"template"	=> $db->escape_string('<html>
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
</html>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_archives_list',
		"template"	=> $db->escape_string('<tr><td class="{$color}">
<a href="downloads.php?archive={$archive[\'did\']}" /><strong>{$archive[\'name\']}</strong></a>
<br /><span class="smalltext">{$archive[\'shortdesc\']}</span></td>
<td class="{$color}" width="10%" align="center">{$archive[\'views\']}</td>
<td class="{$color}" width="10%" align="center">{$archive[\'downloads\']}</td>
<td class="{$color}" width="10%" align="center">{$username}</td>
</tr></tr>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_archives_list_front',
		"template"	=> $db->escape_string('<tr>
<td class="{$color}" width="5%"><a href="downloads.php?archive={$archive[\'did\']}" /><img src="{$archive[\'image\']}" width="{$width}"  /></a></td>
<td class="{$color}">
<a href="downloads.php?archive={$archive[\'did\']}" /><strong>{$archive[\'name\']}</strong></a>
<br /><span class="smalltext">{$archive[\'shortdesc\']}</span></td>
<td class="{$color}" width="10%" align="center">{$archive[\'views\']}</td>
<td class="{$color}" width="10%" align="center">{$archive[\'downloads\']}</td>
<td class="{$color}" width="10%" align="center">{$username}</td>
</tr>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_archives_view',
		"template"	=> $db->escape_string('<html>
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
</html>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_archives_screenshots',
		"template"	=> $db->escape_string('<br />
<br />
<font color="blue" size="5"><strong>{$lang->images}</strong></font>
<br />
{$images}'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_archives_comments',
		"template"	=> $db->escape_string('<br />
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
</table>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_archives_comments_list',
		"template"	=> $db->escape_string('<tr>
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
</td></tr>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_archives_comments_moderation',
		"template"	=> $db->escape_string('<br />
<a href="downloads.php?action=comment&manage=edit&dcid={$comment[\'dcid\']}" /><strong>{$lang->edit}</strong></a> - <a href="downloads.php?action=comment&manage=delete&dcid={$comment[\'dcid\']}" /><strong>{$lang->delete}</strong></a>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_comments_edit',
		"template"	=> $db->escape_string('<html>
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
</html>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_download',
		"template"	=> $db->escape_string('<html>
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
</html>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_newdownload_button',
		"template"	=> $db->escape_string('<div style="float: right">
<a href="downloads.php?newdownload={$category[\'dcid\']}"><img src="images/newdownload.gif" /></a>
</div>
<br />'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_newdownload',
		"template"	=> $db->escape_string('<html>
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
</html>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_newdownload_addimages',
		"template"	=> $db->escape_string('<html>
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
</html>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_newdownload_addlinks',
		"template"	=> $db->escape_string('<html>
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
</html>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_newdownload_addlinks_box',
		"template"	=> $db->escape_string('<tr>
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
</tr>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_newdownload_addimages_box',
		"template"	=> $db->escape_string('<tr>
<td class="{$color}" width="10%">Imagen {$number}:</td>
<td class="{$color}"><input type="text" class="textbox" name="image_{$number}" size="60" maxlength="85" value="{$image}" tabindex="1" /></td>
</tr>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_category_lastdownload',
		"template"	=> $db->escape_string('<td class="{$color}" align="right" valign="top" width="22%"><span class="smalltext"><a href="downloads.php?archive={$download[\'did\']}" /><strong>{$lastdownload}</strong></a>
<br />
{$date} {$time}
<br />
por {$username}
</span>
</td>'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	$downloads_templates[] = array(
		"title"		=> 'downloads_archives_links',
		"template"	=> $db->escape_string('<a href="downloads.php?download={$link[\'generate\']}" />{$link[\'text\']}</a>
<br />'),
		"sid"		=> -1,
		"version"	=> 1604,
		"dateline"	=> TIME_NOW,
	);
	
	foreach($downloads_templates as $insert_templates)
	{
		$db->insert_query("templates", $insert_templates);
	}
	
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets('header', '#{\$lang->toplinks_memberlist}</a></li>#', '{\$lang->toplinks_memberlist}</a></li><!-- Downloads -->
					<li><a href="{\$mybb->settings[\'bburl\']}/downloads.php"><img src="{\$theme[\'imgdir\']}/paperclip.gif" alt="" title="" />Downloads</a></li><!-- /Downloads -->');
}

function downloads_uninstall(){
	global $db;
	if($db->table_exists("downloads"))
		$db->drop_table("downloads");
	if($db->table_exists("downloads_cat"))
		$db->drop_table("downloads_cat");
	if($db->table_exists("downloads_images"))
		$db->drop_table("downloads_images");
	if($db->table_exists("downloads_urls"))
		$db->drop_table("downloads_urls");
	if($db->table_exists("downloads_comments"))
		$db->drop_table("downloads_comments");
		
	if($db->field_exists("threads", "users"))  
		$db->drop_column("users", "threads");
	if($db->field_exists("downloads", "users"))  
		$db->drop_column("users", "downloads");
		
	$db->delete_query("settings","name LIKE 'downloads_%'");
	$db->delete_query("templates","title LIKE 'downloads_%'");
	
	$db->query("DELETE FROM ".TABLE_PREFIX."datacache WHERE title='downloads_validate'");
	$db->query("DELETE FROM ".TABLE_PREFIX."datacache WHERE title='downloads_cache'");
	
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets('header', '#\<!--\sDownloads\s--\>(.+)\<!--\s/Downloads\s--\>#is', '', 0);
	
	change_admin_permission("downloads", false, -1);
	change_admin_permission("downloads", "downloads", -1);
	change_admin_permission("downloads", "category", -1);
}

function download_count_threads($thread)
{
	global $mybb, $db;
	if(!$thread->data['uid'])
	{
		return false;
	}
	if($thread->data['uid'] == $mybb->user['uid'])
	{
		$uid = $mybb->user['uid'];
		$value = $mybb->user['threads'];
	}
	else
	{
		$query = $db->simple_select('users', 'threads', 'uid = ' . $thread['username'], array('limit' => 1));
		$value = $db->fetch_field('threads', $query);
		$uid = $thread->data['uid'];
	}
	$new_value = ++$value;
	$db->update_query('users', array('threads' => $new_value), 'uid = ' . $uid);
}

function download_deletenum_thread($tid)
{
	global $mybb, $db, $thread;

	$query = $db->query('SELECT t.uid, u.threads FROM ' . TABLE_PREFIX . 'threads t JOIN ' . TABLE_PREFIX . 'users u ON t.uid = u.uid WHERE t.tid = ' . $tid . ' LIMIT 1');
	$result = $db->fetch_array($query);
	if(!$result['uid'])
	{
		return false;
	}

	$threadscounts = $result['threads'] - 1;
	$uid = $result['uid'];

	$db->update_query('users', array('threads' => $threadscounts), 'uid = ' . $uid);
}
?>