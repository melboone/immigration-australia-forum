<?php
if(!defined("IN_MYBB"))
{
  die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function gdocsmycode_info() {
	return array(
		"name"			=> "Google Docs MyCode",
		"description"		=> "A simple MyCode which able you to embed Google Docs into your posts easily.",
		"website"		=> "http://www.smkcp.co.cc",
		"author"		=> "acrox999",
		"authorsite"		=> "http://www.smkcp.co.cc",
		"version"		=> "1.0",
		"compatibility" 	=> "1*",
		'guid'        => '44683124b24554f9f4452848f8a9443d'
	);
}

$plugins->add_hook("parse_message", "gdocsmycode_run");

function gdocsmycode_activate() {
	
}

function gdocsmycode_deactivate() {
	
}

function gdocsmycode_run($content) {
	$content = preg_replace("/\[gdoc](.*?)\[\/gdoc\]/si", '<div class="codeblock"><div class="title">Google Document:</div><div class="body"><iframe src="https://docs.google.com/document/pub?id=$1" width="100%" height="300px"></iframe></div></div></div>', $content);
	
	return $content;
}

?>