<?php
/*
    @author     : Surdeanu Mihai ;
    @date       : 20 septembrie 2011 ;
    @version    : 1.0 BETA ;
    @mybb       : compatibilitate cu MyBB 1.6 (orice versiuni) ;
    @description: Aceasta modificare va permite scanarea atasamentelor din cadrul forumului dvs. imotriva virusilor!
    @homepage   : http://mybb.ro ! Te rugam sa ne vizitezi pentru ca nu ai ce pierde!
    @copyright  : Aceasta modificare este premium si nu poate fi publica sau redistribuita fara acordul MyBB Romania.
    ====================================
    Ultima modificare a codului : 20.09.2011 14:54
*/

$l['virustotalscan_data'] = "<b>File Scanned:</b> {1}<br/><b>File Status:</b> <span style=\"color: #D71A1A\"><strong>Infected with <i>{2}</i></strong></span><br /><b>Result:</b> {3}";
$l['virustotalscan_error_data'] = "<table style=\"border: 1px solid #D4D4D4;\" width=\"100%\"><tr><td width=\"55%\"><b>File Scanned:</b> {1}</td><td width=\"20%\"><b>File Size:</b> {2}</td><td width=\"25%\"><b>Submission Date:</b> {3}</td><tr><td width=\"55%\"><b>File Status:</b> <span style=\"color: #D71A1A\"><strong>Infected with <i>{4}</i></strong></span></td><td width=\"20%\"><b>Result:</b> {5}</td><td width=\"25%\"><b>More Details:</b> <a href=\"{6}\" target=\"_blank\">here</a></td></tr></table>";
$l['virustotalscan_error_infected'] = 'Your file has been scanned by <a href="http://www.virustotal.com">VirusTotal.com</a> and the report says it is infected.<br />{1}According to this, your attachment was deleted!';
$l['virustotalscan_link_found'] = '<a class="virustotalscan_found" href="{1}"{2}>{3}<span>Infected website! <a href="http://www.virustotal.com/file-scan/report.html?id={4}" target="_blank">Here</a> you can find a file report about this problem!</span></a>';
$l['virustotalscan_link_notfound'] = '<a class="virustotalscan_notfound" href="{1}"{2}>{3}<span>Clean website!</span></a>';
$l['virustotalscan_email_subject'] = 'Virus detected!';
$l['virustotalscan_email_message'] = '{1} uploaded an infected file at {2} (board time).<br /><br />-- <strong>Full Report</strong> --<br />{3}';
?>
