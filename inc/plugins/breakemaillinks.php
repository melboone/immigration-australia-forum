<?php
/*
 *
 * Break Email Links
 * By: AJS
 * Website: http://petforums.biz
 * Version: 1.2
 * Thanks to - G33K - for the code he contributed to this plugin
 *
*/

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("parse_message_end", "breakemaillinks_break");

function breakemaillinks_info()
{
	return array(
		"name"			=> "Break Email Links",
		"description"	=> "Breaks email links in posts, replaces @ with [at] and removes the actual link if one exists.",
		"website"		=> "http://community.mybb.com/user-24328.html",
		"author"		=> "AJS",
		"authorsite"	=> "http://community.mybb.com/user-24328.html",
		"version"		=> "1.2",
		"guid" 			=> "2d2a83c66e58bbc41ed4fad20abb81bd",
		"compatibility"	=> "16*"
		);
}

function breakemaillinks_break($message)
{
	$search 	= '/<a href=\"mailto:(.*?)">(.*?)<\/a>/is'; 
	$replace 	= '$1';
	$message 	= preg_replace ($search, $replace, $message);
	
	$search 	= '/([a-zA-Z0-9&*+\-_.{}~^\?=\/]+)@([a-zA-Z0-9-]+)\.(([a-zA-Z0-9-]+\.)*[a-zA-Z0-9-]{2,})/si';
	$replace 	= '$1[at]$2.$3';
	$message 	= preg_replace ($search, $replace, $message);
	
	return $message;
}
?>