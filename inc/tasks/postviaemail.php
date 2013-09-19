<?php
function task_postviaemail($task)
{
	global $mybb,$db,$mbox,$lang,$task;

	add_task_log($task,"Began script.");

	$pve['user'] =  $mybb->settings['pve_imap_user'];
	$pve['pass'] =  $mybb->settings['pve_imap_pass'];
	$pve['fldrnum'] = $mybb->settings['pve_imap_folder'];
	$pve['imapserv'] = $mybb->settings['pve_imap_server'];
	$pve['port'] = $mybb->settings['pve_imap_port'];
	$pve['authhost'] = "{imap.gmail.com:993/imap/ssl/novalidate-cert}";
	$mbox = imap_open($pve['authhost'], $pve['user'], $pve['pass']);

	if(!$mbox) {add_task_log($task, "Connection to mailbox failed.  Error: ".imap_last_error());}
	//number of messages

	$num = imap_num_msg($mbox);
	$MC = imap_check($mbox);
	$result = imap_fetch_overview($mbox,"{$MC->Nmsgs}:1");

	if(!$result) {add_task_log($task, "Overview fail.");}

	foreach ($result as $overview) {
		if ($overview->seen == 0) {
			$darp = mail2post($overview,$overview->msgno);
		}
	}

	imap_close($mbox);
	add_task_log($task, "Done. $darp");
}


//Get the From email
function extract_email($email_string) {
	preg_match("/<?([^<]+?)@([^>]+?)>?$/", $email_string, $matches);
	return $matches[1] . "@" . $matches[2];
}


//convert email to post and post it
function mail2post($overview,$msgnum)
{
	global $mybb,$mbox,$db,$cache,$task;

	$from = extract_email($overview->from);

	$subject = $overview->subject;

	//get message
	$msg = imap_fetchbody($mbox,$msgnum,1.2);

	//get clean message
	if(!strlen($msg)>0){
		$msg = imap_fetchbody($mbox,$msgnum,1);
	}

	$msg = quoted_printable_decode($msg)."\r\n\r\n[url=http://wbcu.tk/PostViaEmail]Sent via Email[/url]";

	$fid = $mybb->settings['pve_forum'];

	if(preg_match("^\[fid\](0*[1-9][0-9]*)\[/fid\]^",$msg,$matches)) {
		$fid = $matches[1];
		$msg = preg_replace("^\[fid\](0*[1-9][0-9]*)\[/fid\]^",'',$msg);
		$query = $db->simple_select("forums", "*", "fid='".$fid."'", array('order_by' => 'disporder'));
		if(!$finfo) {$fid = $mybb->settings['pve_forum'];}
	}

 
	if(preg_match("^\[fname\](.*?)\[/fname\]^",$msg,$matches)) {
		$fname = $matches[1];
		$query = $db->simple_select("forums", "*", "name='".$fname."'", array('order_by' => 'disporder'));
		$finfo = $db->fetch_array($query);
		if(!$finfo) {$fid = $mybb->settings['pve_forum'];} else {$fid = $finfo['fid'];}
		$msg = preg_replace("^\[fname\](.*?)\[/fname\]^",'',$msg);
	}


	//Who is sending this?
	$userderp = getuserfromemail($from);


	//for debug
	//output_page("{$from}, {$subject}, {$msg},".var_dump($userderp)."<br /><debugstuff>");

	//post this baby
 	// Set up posthandler.
	require_once MYBB_ROOT."inc/datahandlers/post.php";
	$posthandler = new PostDataHandler("insert");
	$posthandler->action = "thread";

	// Set the thread data that came from the input to the $thread array.
	$new_thread = array(
		"fid" => $fid,
		"subject" => $subject,
		"prefix" => NULL,
		"icon" => NULL,
		"uid" => $userderp['uid'],
		"username" => $userderp['username'],
		"message" => $msg,
		"ipaddress" => get_ip(),
		"posthash" => md5($userderp['loginkey'].$userderp['salt'].$userderp['regdate'])
	);
	
	if($pid != '')
	{
		$new_thread['pid'] = $pid;
	}

		$new_thread['savedraft'] = 0;
	
	// Is this thread already a draft and we're updating it?
	if(isset($thread['tid']) && $thread['visible'] == -2)
	{
		$new_thread['tid'] = $thread['tid'];
	}

	// Set up the thread options from the input.
	$new_thread['options'] = array(
		"signature" => $mybb->input['postoptions']['signature'],
		"subscriptionmethod" => $mybb->input['postoptions']['subscriptionmethod'],
		"disablesmilies" => $mybb->input['postoptions']['disablesmilies']
	);
	
	// Apply moderation options if we have them
	$new_thread['modoptions'] = $mybb->input['modoptions'];

	$posthandler->set_data($new_thread);
	
	// Now let the post handler do all the hard work.
	$valid_thread = $posthandler->validate_thread();
	
	$post_errors = array();
	// Fetch friendly error messages if this is an invalid thread
	if(!$valid_thread)
	{
		$post_errors = $posthandler->get_friendly_errors();
		return var_export($post_errors,TRUE)."fail";
	}
	
	
	// No errors were found, it is safe to insert the thread.
	else
	{
		$thread_info = $posthandler->insert_thread();
		$tid = $thread_info['tid'];
		$visible = $thread_info['visible'];
		return "YAY $fidtest ".var_export($thread_info,TRUE)." $msg";


}
}

//get user info from their email address
function getuserfromemail($email)
{
	global $db;
	$query = $db->simple_select("users", "*", "email='{$email}'");
	$derp = $db->fetch_array($query);
	return $derp;
}
?>
