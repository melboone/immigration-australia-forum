<?php
###################################
# Plugin AutoMedia 2.1  for MyBB 1.6.*#
# (c) 2009-2012 by doylecc    #
# Website: http://mods.mybb.com/profile/14694 #
###################################

$l['av_plugin_descr'] = "Embeds automatically videos and music from different sites in posts.<br />For more information view <a href=\"../inc/plugins/automedia/automedia_doc_en.html\" target=\"_blank\">documentation</a>.";
$l['av_unsupported'] = "cURL is not supported on your server! NOT ALL videos can be shown. For more information view Readme.txt - \"cURL support.\"";
$l['av_php_version'] = "PHP Version too old! AutoMedia 2.x requires PHP 5.1.0 or higher.";
$l['av_group_global_descr'] = "Global Settings for the AutoMedia Plugin";
$l['av_enable_title'] = "Enable AutoMedia for \"AutoMedia Sites\"?";
$l['av_enable_descr'] = "Choose if you want the selected media from \"AutoMedia Sites\" to be shown.";
$l['av_guest_title'] = "Enable AutoMedia for Guests?";
$l['av_guest_descr'] = "Choose if you want the selected media to be shown for guests.";
$l['av_groups_title'] = "Disallow AutoMedia for the following Usergroups:";
$l['av_groups_descr'] = "Please enter the group ids (gid) of the usergroup(s) you wish the selected media not to be shown. Separate them with a comma (,).";
$l['av_forums_title'] = "Show AutoMedia in the following Forums only:";
$l['av_forums_descr'] = "Please enter the forum ids (fid) of the forum(s) you wish to enable AutoMedia in. Separate them with a comma (0 = all forums enabled).";
$l['av_adultsites_title'] = "Enable embedding of Adult Sites?";
$l['av_adultsites_descr'] = "Choose if you want the adult media to be shown.";
$l['av_adultguest_title'] = "Enable Adult Site Videos for Guests?";
$l['av_adultguest_descr'] = "Choose if you want the adult media to be shown for guests.";
$l['av_adultgroups_title'] = "Allow Adult Site Videos for the following Usergroups:";
$l['av_adultgroups_descr'] = "Please enter the group ids (gid) of the usergroup(s) you wish the adult media to be shown. (0 = all groups allowed) Separate them with a comma (,).";
$l['av_adultforums_title'] = "Allow Adult Site Videos for the following Forums only:";
$l['av_adultforums_descr'] = "Please enter the forum ids (fid) of the forum(s) you wish the adult media to be shown. (0 = all forums allowed) Separate them with a comma (,).";
$l['av_signature_title'] = "Enable AutoMedia for \"AutoMedia Sites\" in signatures?";
$l['av_signature_descr'] = "Choose if you want the selected media from \"AutoMedia Sites\" to be shown in signatures.";
$l['av_flashadmin_title'] = "Permission for embedding of flash files";
$l['av_flashadmin_descr'] = "Choose, who is allowed to embed FLV and SWF flash files.";
$l['av_flashwidth_title'] = "Width of shown FLV and SWF files.";
$l['av_flashwidth_descr'] = "Width in pixel, setting applying for all FLV and SWF files.";
$l['av_flashheight_title'] = "Height of shown FLV and SWF files.";
$l['av_flashheight_descr'] = "Height in pixel, setting applying for all FLV and SWF files.";
$l['av_sizeall_title'] = "Same width and height for all videos";
$l['av_sizeall_descr'] = "Apply width and height of FLV and SWF files on ALL video sites?";
$l['av_codebuttons_title'] = "Show codebuttons for MP3 Playlist and Deactivation MyCodes";
$l['av_codebuttons_descr'] = "Choose if you want the codebuttons for inserting the MP3 Playlist ([ampl][/ampl]) and Deactivation ([amoff][/amoff]) MyCodes to be shown.";
$l['av_quote_title'] = "AutoMedia in Quotes?";
$l['av_quote_descr'] = "Choose if you want the videos to be shown in quoted posts.";

$l['automedia'] = "AutoMedia";
$l['automedia_settings'] = "Plugin settings";
$l['can_view_automedia'] = "Can view AutoMedia modules";
$l['automedia_modules'] = "Manage installed AutoMedia modules";
$l['automedia_modules_description1'] = "Shows currently installed and active modules.<br /> To remove deactivated modules delete the according PHP files in the folder <strong>inc/plugins/automedia/sites</strong> via FTP.<br />To add new modules upload the according PHP files into the same folder and activate it here.";
$l['automedia_modules_description2'] = "Shows currently installed and active modules.";
$l['automedia_adult'] = "Installed Adult Sites modules";
$l['automedia_adult_description1'] = "Shows currently installed and active adult modules.<br /> To remove deactivated modules delete the according PHP files in the folder <strong>inc/plugins/automedia/special</strong> via FTP.<br />To add new modules upload the according PHP files into the same folder and activate it here.";
$l['automedia_adult_description2'] = "List modules:";
$l['automedia_modules_options'] = "Options";
$l['automedia_modules_viewcode'] = "Shows the embed code";
$l['automedia_modules_showcode'] = "Show code";
$l['automedia_modules_deleted'] = "Module successful deactivated";
$l['automedia_modules_active'] = "Module successful activated";
$l['automedia_modules_notfound'] = "Module not found!";
$l['automedia_modules_activate'] = "<span style=\"color:#EE0000;\">Activate</span>";
$l['automedia_modules_activateall'] = "<strong>Activate all</strong>";
$l['automedia_modules_deactivate'] = "<span style=\"color:#00AA00;\">Deactivate</span>";
$l['automedia_modules_missing_sitesfolder'] = "<span style=\"color:#EE0000;\">Folder inc/plugins/automedia/<strong>sites</strong> doesn't exist!</span>";
$l['automedia_modules_missing_specialfolder'] = "<span style=\"color:#EE0000;\">Folder inc/plugins/automedia/<strong>special</strong> doesn't exist!</span>";
$l['automedia_template_edits1'] = "Reapply template edits";
$l['automedia_template_edits2'] = "(e.g. after reverting your templates)";

?>
