<?php
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}


$plugins->add_hook("postbit", "similarthreadsinfirstpost_main");

function similarthreadsinfirstpost_info()
{
	global $plugins_cache, $mybb, $db;
	
	$codename = basename(__FILE__, ".php");
	$prefix = $codename.'_';
	
    $info = array(
        "name"				=> "Similar threads in first post",
        "description"		=> "Show similar threads in first post of thread.",
        "website"			=> "http://sv-it.co.cc",
        "author"			=> "htclub",
        "authorsite"		=> "http://sv-it.co.cc",
        "version"			=> "1.0",
		"compatibility" 	=> "*"
    );
    if(is_array($plugins_cache) && is_array($plugins_cache['active']) && $plugins_cache['active'][$codename])
    {
	    $result = $db->simple_select('settinggroups', 'gid', "name = '$codename'", array('limit' => 1));
		$group = $db->fetch_array($result);
	
		if(!empty($group['gid']))
		{
			$info['description'] = "<i><small>[<a href=\"index.php?module=config/settings&action=change&gid=".$group['gid']."\">Configure Settings</a>]</small></i><br />".$info['description'];
		}
	}	
	if (file_exists(MYBB_ROOT."images/htclub.gif")) {
		$info["description"] = '<div style="float:left;display:block; position:relative; margin-right:10px;"><a href="http://sv-it.co.cc/donate"><img src="'.$mybb->settings['bburl'].'/images/htclub.gif"></a></div>'.$info["description"];
	}	
	return $info;	
}

function similarthreadsinfirstpost_activate()
{
	global $mybb, $db;

	$codename = basename(__FILE__, ".php");
	$prefix = $codename.'_';

    $group = array(
		"name" =>			$codename,
		"title" =>			"Similar threads in first post",
		"description" =>	"Show similar threads in first post of thread.",
	);
    $db->insert_query("settinggroups", $group);
	$gid = $db->insert_id();
	
	$settings = array(
		'enable' 		=> array(
				'title' 			=> 'Enable plugin', 
				'optionscode'		=> 'yesno',
				'value'				=> '1'),
		'similarityrating' 		=> array(
				'title' 			=> 'Similar Threads Relevancy Rating', 
				'description' 		=> 'This allows you to limit similar threads to ones more relevant (0 being not relevant). This number should not be over 10 and should not be set low (<5) for large forums.',
				'optionscode'		=> 'text',
				'value'				=> '1'),				
		'similarlimit' 		=> array(
				'title' 			=> 'Similar Threads Limit', 
				'description' 		=> 'Here you can change the total amount of similar threads to be shown in the similar threads table. It is recommended that it is not over 15 for 56k users.',
				'optionscode'		=> 'text',
				'value'				=> '10')				
	);
	
	$x = 1;
	foreach($settings as $name => $setting)
	{
		$insert_settings = array(
			'name' => $db->escape_string($prefix.$name),
			'title' => $db->escape_string($setting['title']),
			'description' => $db->escape_string($setting['description']),
			'optionscode' => $db->escape_string($setting['optionscode']),
			'value' => $db->escape_string($setting['value']),
			'disporder' => $x,
			'gid' => $gid,
			'isdefault' => 0
			);
		$db->insert_query('settings', $insert_settings);
		$x++;
	}
	rebuild_settings();

}

function similarthreadsinfirstpost_deactivate()
{
	global $db;

	$codename = basename(__FILE__, ".php");
	$setting_groupname = $codename; 
	// Delete settings
	$query = $db->query("SELECT gid FROM ".TABLE_PREFIX."settinggroups WHERE name='$setting_groupname' LIMIT 1");
	$qinfo = $db->fetch_array($query);
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE gid='$qinfo[gid]'");
	// Delete settings group
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='$setting_groupname'");
	rebuild_settings();		

}

function similarthreadsinfirstpost_main($post)
{
	global $mybb,$db,$templates,$thread,$lang;
	$codename = basename(__FILE__, ".php");	
	$prefix = $codename.'_';
	if($post['pid'] == $thread['firstpost'])
		{
		$query = $db->query("
			SELECT t.*, t.username AS threadusername, u.username, MATCH (t.subject) AGAINST ('".$db->escape_string($thread['subject'])."') AS relevance
			FROM ".TABLE_PREFIX."threads t
			LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid = t.uid)
			WHERE t.fid='{$thread['fid']}' AND t.tid!='{$thread['tid']}' AND t.visible='1' AND t.closed NOT LIKE 'moved|%' AND MATCH (t.subject) AGAINST ('".$db->escape_string($thread['subject'])."') >= '{$mybb->settings[$prefix.'similarityrating']}'
			ORDER BY t.lastpost DESC
			LIMIT 0, {$mybb->settings[$prefix.'similarlimit']}
		");
		
		$count = 0;
		$similarthreadbits = '';
		$comma = "<br />\n";
		require_once MYBB_ROOT."inc/class_parser.php";
		$parser = new postParser;
		while($similar_thread = $db->fetch_array($query))
		{
			++$count;
			$trow = alt_trow();
			
			if(!$similar_thread['username'])
			{
				$similar_thread['username'] = $similar_thread['threadusername'];
				$similar_thread['profilelink'] = $similar_thread['threadusername'];
			}
			else
			{
				$similar_thread['profilelink'] = build_profile_link($similar_thread['username'], $similar_thread['uid']);
			}
			
			if($similar_thread['prefix'] != 0)
			{
				$prefix = build_prefixes($similar_thread['prefix']);
				$similar_thread['threadprefix'] = $prefix['displaystyle'].'&nbsp;';
			}
			
			$similar_thread['subject'] = $parser->parse_badwords($similar_thread['subject']);
			$similar_thread['subject'] = htmlspecialchars_uni($similar_thread['subject']);
			$similar_thread['threadlink'] = get_thread_link($similar_thread['tid']);
			$similarthreads .= $comma.'<a href="'.$similar_thread['threadlink'].'"  title="'.$similar_thread['subject'].'">'.$similar_thread['subject'].'</a>';

		}
		if($count)
		{
			$post['message'] .= $comma.$comma."<strong>".$lang->similar_threads."</strong>".$similarthreads;
		}
		return $post;
	}
}

?>