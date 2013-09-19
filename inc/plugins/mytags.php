<?php
/*
Copyright 2011 PJGIH 
See license here: http://wb-dev.net/Thread-Wb-Dev-License
*/


if(!defined("IN_MYBB")) {
    die("This file cannot be accessed directly.");
}
//Add Your Hooks
$plugins->add_hook("parse_message_end", "tag_parse");

//REQUIRED
function mytags_info()
{
    return array(
        'name'            => 'MyTags',
        'description'    => 'Uses # to make clickable and searchable hashtags, like on twitter.',
        'website'        => 'http://www.wb-dev.net',
        'author'        => 'PJGIH',
        'authorsite'    => 'http://www.wb-dev.net',
        'version'        => '1.0',
    );
}

function mytags_activate() {
//no settings
}

function mytags_deactivate()
{
//no settings
}

function tag_parse($message) {

    $tweet = $message;
    return preg_replace("/#([A-Za-z0-9_]+)(?=\s|\Z)/",
        '
<form method="post" action="./search.php" style="display:inline;">
<input type="hidden" name="action" value="do_search" />
<input type="hidden" name="postthread" value="1" />
<input type="hidden" name="forums" value="all" />
<input type="hidden" name="showresults" value="posts" />
<input type="hidden" class="textbox" name="keywords" value="#\1" />
<a href="#" class="mytags_hashtag" onclick="document.forms[0].submit()">#\1</a>
</form> 
        ',
        $tweet);
       
    return $message; 
}


?>