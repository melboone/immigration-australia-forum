<?php
if(!defined("IN_MYBB"))
{
  die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function lmgtfy_info() {
	return array(
		"name"			=> "Let me google",
		"description"		=> "Let Me Google That For You MyCode allows you to use to put [google]SEARCH[/google] and it will provide a link to the search results (with a fancy animation).",
		"website"		=> "http://www.otfusion.org",
		"author"		=> "Raliuga",
		"authorsite"		=> "http://www.otfusion.org",
		"version"		=> "1.0",
		"compatibility" 	=> "1*",
		'guid'        => '82f6b3f0de90017df6cea8f50d225d6c'
	);
}

$plugins->add_hook("parse_message", "lmgtfy_run");

function lmgtfy_activate() {
	
}

function lmgtfy_deactivate() {
	
}

function lmgtfy_run($content) {
	$content = preg_replace("/\[googlethat](.*?)\[\/googlethat\]/si", '<a href="http://lmgtfy.com/?q=$1">Let me Google that for you: $1</a>', $content);
	$content = preg_replace("/\[google](.*?)\[\/google\]/si", '<a href="http://lmgtfy.com/?q=$1">Let me Google that for you: $1</a>', $content);
	$content = preg_replace("/\[google=(.*?)](.*?)\[\/google\]/si", '<a href="http://lmgtfy.com/?q=$2">$1</a>', $content);
	
	return $content;
}

?>