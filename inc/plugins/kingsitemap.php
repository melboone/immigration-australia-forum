<?php
/*
*
* kingsitemap Plugin
* Copyright 2011 mostafa shirali
* http://www.kingofpersia.ir
* No one is authorized to redistribute or remove copyright without my expressed permission.
*
*/

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}
$plugins->add_hook("newthread_end", "kingsitemap_do");
// The information that shows up on the plugin manager
function kingsitemap_info()
{
return array(
"name" => "kingsitemap",
"description" => "this plugin let you Create sitemap and update Automatically",
"website" => "http://www.kingofpersia.ir",
"author" => "Mostafa shirali",
"authorsite" => "http://www.kingofpersia.ir",
"version" => "1.0",
'guid'        => 'fb8fdd060e612a8b20fe33a5303d3b0e',
);
}

// This function runs when the plugin is activated.
function kingsitemap_activate()
{
	global $mybb, $db, $templates;
    require MYBB_ROOT.'/inc/adminfunctions_templates.php';
 $settings_group = array(
        "gid" => "",
        "name" => "kingsitemap",
        "title" => "kingsitemap",
        "description" => "kingsitemap settings",
        "disporder" => "88",
        "isdefault" => "0",
        );
    $db->insert_query("settinggroups", $settings_group);
    $gid = $db->insert_id();

	$setting_1 = array("sid" => "","name" => "kingsitemapenable","title" => "active","description" => "do you want active plugin?","optionscode" => "yesno","value" => "0","disporder" => 1,"gid" => intval($gid),);
    $setting_2 = array( 'sid'=> "",'name'=> 'kingsitemapfre','title'=> 'frequancy','description'	=> 'insert your sitemap frequancy.can insert always hourly daily weekly monthly yearly','optionscode'=> 'text','value'=> 'daily','disporder'=> 2,'gid'=> intval($gid),);
    $setting_3 = array( 'sid'=> "",'name'=> 'kingsitemapnum','title'=> 'post number','description'	=> 'Select number of thread','optionscode'=> 'text','value'=> '1000','disporder'=> 3,'gid'=> intval($gid),);

$db->insert_query("settings", $setting_1);
$db->insert_query("settings", $setting_2);
$db->insert_query("settings", $setting_3);

}

function kingsitemap_deactivate()
{
	global $mybb, $db, $templates;
    require MYBB_ROOT.'/inc/adminfunctions_templates.php';
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='kingsitemap'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='kingsitemapenable'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='kingsitemapfre'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='kingsitemapnum'");


}
function kingsitemap_do()
{
global $mybb, $db, $templates;

if($mybb->settings['kingsitemapenable'] == 1)
{ 
    if (!file_exists(MYBB_ROOT . 'sitemap.xml'))
    {
        $works = @fopen(MYBB_ROOT . 'sitemap.xml', 'w');
        fclose($works);
					$threads=$db->query("SELECT * FROM ".TABLE_PREFIX."threads  ORDER BY tid DESC LIMIT 0,".$mybb->settings['kingsitemapnum']." ");
					$threads_num=$db->num_rows($threads);
					for($i=0;$i<$threads_num;$i++)
					{
					$threads_info=$db->fetch_array($threads);
					$threadslink = get_thread_link($threads_info['tid']);
					$content.='<url><loc>'.$mybb->settings['bburl'].'/'.$threadslink.'</loc><changefreq>'.$mybb->settings['kingsitemapfre'].'</changefreq></url>';
					}
					$sitemap="<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<urlset xmlns=\"http://www.google.com/schemas/sitemap/0.84\">".$content."</urlset>";
	$handle = fopen(MYBB_ROOT . 'sitemap.xml', 'w');
	fwrite($handle, $sitemap);
	fclose($handle);
					
					
    }
	else
	{
	$threads=$db->query("SELECT * FROM ".TABLE_PREFIX."threads  ORDER BY tid DESC LIMIT 0,".$mybb->settings['kingsitemapnum']." ");
					$threads_num=$db->num_rows($threads);
					for($i=0;$i<$threads_num;$i++)
					{
					$threads_info=$db->fetch_array($threads);
					$threadslink = get_thread_link($threads_info['tid']);
					$content.='<url><loc>'.$mybb->settings['bburl'].'/'.$threadslink.'</loc><changefreq>'.$mybb->settings['kingsitemapfre'].'</changefreq></url>';
					}
					$sitemap="<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<urlset xmlns=\"http://www.google.com/schemas/sitemap/0.84\">".$content."</urlset>";
	$handle = fopen(MYBB_ROOT . 'sitemap.xml', 'w');
	fwrite($handle, $sitemap);
	fclose($handle);
					
	}
}
}

?>