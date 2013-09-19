<?php
// Restrict Moderators Editing
// By "wingsofdeath" aka "computerkidt" from "Army of Fate"
// Version 1.1
//
//Specail Thanks users on phpbb at http://www.phpbb.com/community/viewtopic.php?f=64&t=935605
//
// This plugin (C) "Army of Fate" 2010.  You may not redistribute this plugin without the permission from wingsofdeath aka computerkidt.
function Bots_info()
{
    return array(
        "name"        => "Spider Bots",
        "description" => "This plugin adds more spider bots to your forum database so you can detect more spider bots",
        "website"     => "http://www.armyoffate.net",
        "author"      => "computerkidt",
        "authorsite"  => "http://www.armyoffate.net",
        "version"     => "1.1",
		"compatibility"		=> "16*",
		"guid" => "cf64554b99bc21cabc70fb57dfcd7c2a",
    );


}
function Bots_activate()
{
  global $db;
  $db->write_query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."spiders` (
	  `sid` int(10) unsigned NOT NULL auto_increment,
	  `name` varchar(100) NOT NULL default '',
	  `theme` int(10) unsigned NOT NULL default '0',
	  `language` varchar(20) NOT NULL default '',
	  `usergroup` int(10) unsigned NOT NULL default '0',
	  `useragent` varchar(200) NOT NULL default '',
	  `lastvisit` bigint(30) NOT NULL default '0',
	  PRIMARY KEY  (`sid`)
			) TYPE=MyISAM");

	$db->insert_query('spiders', array('name' => "Google Adsense [Bot]", 'useragent' => "Mediapartners-Google")); //11
	$db->insert_query('spiders', array('name' => "Google Desktop", 'useragent' => "Google Desktop")); //12
	$db->insert_query('spiders', array('name' => "Google Feedfetcher", 'useragent' => "Feedfetcher-Google")); //13
	$db->insert_query('spiders', array('name' => "Gigabot [Bot]", 'useragent' => "Mediapartners-Google")); //14
	$db->insert_query('spiders', array('name' => "Heise IT-Markt [Crawler]", 'useragent' => "heise-IT-Markt-Crawler")); //15
	$db->insert_query('spiders', array('name' => "Heritrix [Crawler]", 'useragent' => "heritrix/1.")); //16
	$db->insert_query('spiders', array('name' => "IBM Research [Bot]", 'useragent' => "ibm.com/cs/crawler")); //17
	$db->insert_query('spiders', array('name' => "ICCrawler - ICjobs", 'useragent' => "ICCrawler - ICjobs")); //18
	$db->insert_query('spiders', array('name' => "ichiro [Crawler]", 'useragent' => "ichiro/2")); //19
	$db->insert_query('spiders', array('name' => "ajestic-12 [Bot]", 'useragent' => "MJ12bot/")); //20
	$db->insert_query('spiders', array('name' => "Metager [Bot]", 'useragent' => "MetagerBot/")); //21
	$db->insert_query('spiders', array('name' => "MSN NewsBlogs", 'useragent' => "msnbot-NewsBlogs/")); //22
	$db->insert_query('spiders', array('name' => "BING/MSN [Bot]", 'useragent' => "msnbot/")); //23
	$db->insert_query('spiders', array('name' => "MSNbot Media", 'useragent' => "msnbot-media/")); //24
	$db->insert_query('spiders', array('name' => "NG-Search [Bot]", 'useragent' => "NG-Search/")); //25
	$db->insert_query('spiders', array('name' => "Nutch [Bot]", 'useragent' => "http://lucene.apache.org/nutch/")); //26
	$db->insert_query('spiders', array('name' => "Nutch/CVS [Bot]", 'useragent' => "NutchCVS/")); //27
	$db->insert_query('spiders', array('name' => "OmniExplorer [Bot]", 'useragent' => "OmniExplorer_Bot/")); //28
	$db->insert_query('spiders', array('name' => "Online link [Validator]", 'useragent' => "online link validator")); //29
	$db->insert_query('spiders', array('name' => "psbot [Picsearch]", 'useragent' => "psbot/0")); //30
	$db->insert_query('spiders', array('name' => "Seekport [Bot]", 'useragent' => "Seekbot/")); //31
	$db->insert_query('spiders', array('name' => "Sensis [Crawler]", 'useragent' => "Sensis Web Crawler")); //32
	$db->insert_query('spiders', array('name' => "SEO Crawler", 'useragent' => "SEO search Crawler/")); //33
	$db->insert_query('spiders', array('name' => "Seoma [Crawler]", 'useragent' => "Seoma [SEO Crawler]")); //34
	$db->insert_query('spiders', array('name' => "SEOSearch [Crawler]", 'useragent' => "SEOsearch/")); //35
	$db->insert_query('spiders', array('name' => "Snappy [Bot]", 'useragent' => "Snappy/1.1 ( http://www.urltrends.com/ )")); //36
	$db->insert_query('spiders', array('name' => "Steeler [Crawler]", 'useragent' => "http://www.tkl.iis.u-tokyo.ac.jp/~crawler/")); //37
	$db->insert_query('spiders', array('name' => "Synoo [Bot]", 'useragent' => "SynooBot/")); //38
	$db->insert_query('spiders', array('name' => "Telekom [Bot]", 'useragent' => "crawleradmin.t-info@telekom.de")); //39
	$db->insert_query('spiders', array('name' => "TurnitinBot [Bot]", 'useragent' => "TurnitinBot/")); //40
	$db->insert_query('spiders', array('name' => "Voyager [Bot]", 'useragent' => "voyager/1.0")); //41
	$db->insert_query('spiders', array('name' => "W3 [Sitesearch]", 'useragent' => "W3 SiteSearch Crawler")); //42
	$db->insert_query('spiders', array('name' => "W3C [Linkcheck]", 'useragent' => "W3C-checklink/")); //43
	$db->insert_query('spiders', array('name' => "W3C [Validator]", 'useragent' => "W3C_*Validator")); //44
	$db->insert_query('spiders', array('name' => "WiseNut [Bot]", 'useragent' => "http://www.WISEnutbot.com")); //45
	$db->insert_query('spiders', array('name' => "YaCy [Bot]", 'useragent' => "yacybot")); //46
	$db->insert_query('spiders', array('name' => "Yahoo MMCrawler [Bot]", 'useragent' => "Yahoo-MMCrawler/")); //47
	$db->insert_query('spiders', array('name' => "Yahoo Slurp [Bot]", 'useragent' => "Yahoo! DE Slurp")); //48
	$db->insert_query('spiders', array('name' => "Francis [Bot]", 'useragent' => "http://www.neomo.de/")); //49
	$db->insert_query('spiders', array('name' => "YahooSeeker [Bot]", 'useragent' => "YahooSeeker/")); //50
	$db->insert_query('spiders', array('name' => "AdsBot [Google]", 'useragent' => "AdsBot-Google")); //51
	$db->insert_query('spiders', array('name' => "Baidu [Spider]", 'useragent' => "Baiduspider+(")); //52
	$db->insert_query('spiders', array('name' => "Exabot [Bot]", 'useragent' => "Exabot/")); //53
	$db->insert_query('spiders', array('name' => "FAST Enterprise [Crawler]", 'useragent' => "FAST Enterprise Crawler")); //54
	$db->insert_query('spiders', array('name' => "FAST WebCrawler [Crawler]", 'useragent' => "FAST-WebCrawler/")); //55
	$db->insert_query('spiders', array('name' => "Twiceler [Bot]", 'useragent' => "Twiceler")); //56
	$db->insert_query('spiders', array('name' => "Voila [Bot]", 'useragent' => "VoilaBot")); //57
	$db->insert_query('spiders', array('name' => "Omgili [BADBot]", 'useragent' => "omgilibot")); //58
	$db->insert_query('spiders', array('name' => "Noxtrum [Bot]", 'useragent' => "noxtrumbot")); //59
	$db->insert_query('spiders', array('name' => "Spinn3r [Bot]", 'useragent' => "Spinn3r")); //60
	$db->insert_query('spiders', array('name' => "Furl [Bot]", 'useragent' => "FurlBot")); //61
	$db->insert_query('spiders', array('name' => "CommonCrawl [Bot]", 'useragent' => "CCBot")); //62
	$db->insert_query('spiders', array('name' => "Naver [Bot]", 'useragent' => "Yeti")); //63
	$db->insert_query('spiders', array('name' => "BDProtect [Bot]", 'useragent' => "BPImageWalker")); //64
	$db->insert_query('spiders', array('name' => "Snap Shots [Bot]", 'useragent' => "Snapbot")); //65
	$db->insert_query('spiders', array('name' => "Whitevector [Bot]", 'useragent' => "Whitevector Crawler")); //66
	$db->insert_query('spiders', array('name' => "Hatena Antenna [Bot]", 'useragent' => "Hatena Antenna")); //67
	$db->insert_query('spiders', array('name' => "Snap Shots Preview [Bot]", 'useragent' => "SnapPreviewBot")); //68
	$db->insert_query('spiders', array('name' => "Ilse [Bot]", 'useragent' => "IlseBot")); //69
	$db->insert_query('spiders', array('name' => "ImageShack [Bot]", 'useragent' => "ImageShack Image Fetcher")); //70
	$db->insert_query('spiders', array('name' => "Entireweb [Bot]", 'useragent' => "Speedy Spider")); //71
	$db->insert_query('spiders', array('name' => "Yandex [Bot]", 'useragent' => "Yandex")); //72
	$db->insert_query('spiders', array('name' => "WebCorp [Bot]", 'useragent' => "WebCorp")); //73
	$db->insert_query('spiders', array('name' => "WebAlta [Bot]", 'useragent' => "WebAlta")); //74
	$db->insert_query('spiders', array('name' => "Powerset [Bot]", 'useragent' => "zermelo")); //75
	$db->insert_query('spiders', array('name' => "Boston Project [SpamBot]", 'useragent' => "Boston Project")); //76
	$db->insert_query('spiders', array('name' => "Startpagina [Bot]", 'useragent' => "Startpagina")); //77
	$db->insert_query('spiders', array('name' => "Heeii [Bot]", 'useragent' => "Heeii")); //78
	$db->insert_query('spiders', array('name' => "Wget [SpamBot]", 'useragent' => "Wget")); //79
	$db->insert_query('spiders', array('name' => "Yodao [Bot]", 'useragent' => "YodaoBot")); //80
}
function Bots_deactivate()
{
	global $db;
	$db->delete_query('spiders', "name = 'Google Adsense [Bot]'"); //11
	$db->delete_query('spiders', "name = 'Google Desktop'"); //12
	$db->delete_query('spiders', "name = 'Google Feedfetcher'"); //13
	$db->delete_query('spiders', "name = 'Gigabot [Bot]'"); //14
	$db->delete_query('spiders', "name = 'Heise IT-Markt [Crawler]'"); //15
	$db->delete_query('spiders', "name = 'Heritrix [Crawler]'"); //16
	$db->delete_query('spiders', "name = 'IBM Research [Bot]'"); //17
	$db->delete_query('spiders', "name = 'ICCrawler - ICjobs'"); //18
	$db->delete_query('spiders', "name = 'ichiro [Crawler]'"); //19
	$db->delete_query('spiders', "name = 'ajestic-12 [Bot]'"); //20
	$db->delete_query('spiders', "name = 'Metager [Bot]'"); //21
	$db->delete_query('spiders', "name = 'MSN NewsBlogs'"); //22
	$db->delete_query('spiders', "name = 'MSN [Bot]'"); //23
	$db->delete_query('spiders', "name = 'BING/MSN [Bot]'"); //23
	$db->delete_query('spiders', "name = 'MSNbot Media'"); //24
	$db->delete_query('spiders', "name = 'NG-Search [Bot]'"); //25
	$db->delete_query('spiders', "name = 'Nutch [Bot]'"); //26
	$db->delete_query('spiders', "name = 'Nutch/CVS [Bot]'"); //27
	$db->delete_query('spiders', "name = 'OmniExplorer [Bot]'"); //28
	$db->delete_query('spiders', "name = 'Online link [Validator]'"); //29
	$db->delete_query('spiders', "name = 'psbot [Picsearch]'"); //30
	$db->delete_query('spiders', "name = 'Seekport [Bot]'"); //31
	$db->delete_query('spiders', "name = 'Sensis [Crawler]'"); //32
	$db->delete_query('spiders', "name = 'SEO Crawler'"); //33
	$db->delete_query('spiders', "name = 'Seoma [Crawler]'"); //34
	$db->delete_query('spiders', "name = 'SEOSearch [Crawler]'"); //35
	$db->delete_query('spiders', "name = 'Snappy [Bot]'"); //36
	$db->delete_query('spiders', "name = 'Steeler [Crawler]'"); //37
	$db->delete_query('spiders', "name = 'Synoo [Bot]'"); //38
	$db->delete_query('spiders', "name = 'Telekom [Bot]'"); //39
	$db->delete_query('spiders', "name = 'TurnitinBot [Bot]'"); //40
	$db->delete_query('spiders', "name = 'Voyager [Bot]'"); //41
	$db->delete_query('spiders', "name = 'W3 [Sitesearch]'"); //42
	$db->delete_query('spiders', "name = 'W3C [Linkcheck]'"); //43
	$db->delete_query('spiders', "name = 'W3C [Validator]'"); //44
	$db->delete_query('spiders', "name = 'WiseNut [Bot]'"); //45
	$db->delete_query('spiders', "name = 'YaCy [Bot]'"); //46
	$db->delete_query('spiders', "name = 'Yahoo MMCrawler [Bot]'"); //47
	$db->delete_query('spiders', "name = 'Yahoo Slurp [Bot]'"); //48
	$db->delete_query('spiders', "name = 'Francis [Bot]'"); //49
	$db->delete_query('spiders', "name = 'YahooSeeker [Bot]'"); //50
	$db->delete_query('spiders', "name = 'AdsBot [Google]'"); //51
	$db->delete_query('spiders', "name = 'Baidu [Spider]'"); //52
	$db->delete_query('spiders', "name = 'Exabot [Bot]'"); //53
	$db->delete_query('spiders', "name = 'FAST Enterprise [Crawler]'"); //54
	$db->delete_query('spiders', "name = 'FAST WebCrawler [Crawler]'"); //55
	$db->delete_query('spiders', "name = 'Twiceler [Bot]'"); //56
	$db->delete_query('spiders', "name = 'Voila [Bot]'"); //57
	$db->delete_query('spiders', "name = 'Omgili [BADBot]'"); //58
	$db->delete_query('spiders', "name = 'Noxtrum [Bot]'"); //59
	$db->delete_query('spiders', "name = 'Spinn3r [Bot]'"); //60
	$db->delete_query('spiders', "name = 'Furl [Bot]'"); //61
	$db->delete_query('spiders', "name = 'CommonCrawl [Bot]'"); //62
	$db->delete_query('spiders', "name = 'Naver [Bot]'"); //63
	$db->delete_query('spiders', "name = 'BDProtect [Bot]'"); //64
	$db->delete_query('spiders', "name = 'Snap Shots [Bot]'"); //65
	$db->delete_query('spiders', "name = 'Whitevector [Bot]'"); //66
	$db->delete_query('spiders', "name = 'Hatena Antenna [Bot]'"); //67
	$db->delete_query('spiders', "name = 'Snap Shots Preview [Bot]'"); //68
	$db->delete_query('spiders', "name = 'Ilse [Bot]'"); //69
	$db->delete_query('spiders', "name = 'ImageShack [Bot]'"); //70
	$db->delete_query('spiders', "name = 'Entireweb [Bot]'"); //71
	$db->delete_query('spiders', "name = 'Yandex [Bot]'"); //72
	$db->delete_query('spiders', "name = 'WebCorp [Bot]'"); //73
	$db->delete_query('spiders', "name = 'WebAlta [Bot]'"); //74
	$db->delete_query('spiders', "name = 'Powerset [Bot]'"); //75
	$db->delete_query('spiders', "name = 'Boston Project [SpamBot]'"); //76
	$db->delete_query('spiders', "name = 'Startpagina [Bot]'"); //77
	$db->delete_query('spiders', "name = 'Heeii [Bot]'"); //78
	$db->delete_query('spiders', "name = 'Wget [SpamBot]'"); //79
	$db->delete_query('spiders', "name = 'Yodao [Bot]'"); //80
}
?>