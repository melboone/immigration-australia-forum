<?php
/**
 * MyCode Installer
 *
 * Copyright 2011 Nickman @ MyBBSource.com
 */

define("IN_MYBB", 1);
global $mybb, $db, $page, $lang,$table;
$act=$mybb->input['act'];
if ($act == '')
{
  $table=new Table();
  $page->add_breadcrumb_item("Recently Added");
  $page->output_header("MyCode Installer");
  require_once MYBB_ROOT."inc/class_feedparser.php";
  $feed_parser = new FeedParser();
  $feed_parser->parse_feed("http://mybbsource.com/mycoderss.php");
  if($feed_parser->error == '')
  {
	  $i=1;
	  foreach($feed_parser->items as $item)
	  {
		  if($item['date_timestamp'])
		  {
			  $stamp = my_date($mybb->settings['dateformat'], $item['date_timestamp']).", ".my_date($mybb->settings['timeformat'], $item['date_timestamp']);
		  }
		  else
		  {
			  $stamp = '';
		  }
		  if($item['content'])
		  {
			  $content = $item['content'];
		  }
		  else
		  {
			  $content = $item['description'];
		  }
		  //Check to see if installed BETA
		  $check=$db->simple_select("mycode","title,cid","title='{$item['title']}' LIMIT 1");
		  if ($db->num_rows($check) > 0 )
		  {
			  $code=$db->fetch_array($check);
			  $install="<b>Installed!</b> <a href='index.php?module=config-mycode&action=delete&cid={$code['cid']}&my_post_key={$mybb->post_code}'>Delete</a>";
		  }
		  else
		  {
			  $install="<a href=\"index.php?module=config-mycode_installer&act=install&mid={$item['link']}\">&raquo; Install</a>";
		  }
		  $table->construct_cell("<span style=\"font-size: 16px;\"><strong>".$item['title']."</strong></span><br /><br />{$content}<strong><br /><br />$install</strong>");
		  if ($i == 2)
		  {
		  $table->construct_row();
		  $i=1;
		  }
		  else
		  {
			  $i++;
		  }
	  }
	  
  }
  else
	  {
		  $table->construct_cell("MyCodes could not be fetched :(");
		  $table->construct_row();
	  }
	  
	  $table->output("Latest MyCodes");
}
elseif ($act == 'install')
{
	$mid=intval($mybb->input['mid']);
	$content=file_get_contents("http://mybbsource.com/mycodes.php?act=install&mid=$mid");
	$mycode=explode("<break||>",$content);
	$new_mycode = array(
				'title'	=> $mycode[0],
				'description' => $mycode[1],
		'regex' =>$mycode[2],
		'replacement' => $mycode[3],
		'active' => $db->escape_string("1")
			);
	$link="&description={$new_mycode['description']}&title={$new_mycode['title']}&regex={$new_mycode['regex']}&replacement={$new_mycode['replacement']}";
			header("location:index.php?module=config-mycode&action=add".$link);
}
