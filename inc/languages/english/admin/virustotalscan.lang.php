<?php
/*
    @author     : Surdeanu Mihai ;
    @date       : 20 septembrie 2011 ;
    @version    : 1.0 BETA;
    @mybb       : compatibilitate cu MyBB 1.6 (orice versiuni) ;
    @description: Aceasta modificare va permite scanarea atasamentelor din cadrul forumului dvs. imotriva virusilor!
    @homepage   : http://mybb.ro ! Te rugam sa ne vizitezi pentru ca nu ai ce pierde!
    @copyright  : Aceasta modificare este premium si nu poate fi publica sau redistribuita fara acordul MyBB Romania.
    ====================================
    Ultima modificare a codului : 20.09.2011 14:55
*/
// plugin
$l['virustotalscan_title'] = 'Virus Total Scanner';
$l['virustotalscan_canmanage'] = 'Can manage Virus Total Scanner?';
// tabel de log-uri
$l['virustotalscan_logs'] = 'Logs';
$l['virustotalscan_logs_desc'] = 'Here you can view all logs made by this plugin.';
// antet tabel de log-uri
$l['virustotalscan_logs_user'] = 'User';
$l['virustotalscan_logs_data'] = 'Data';
$l['virustotalscan_logs_date'] = 'Date';
$l['virustotalscan_logs_options'] = 'Options';
// stergerea tuturor log-urilor
$l['virustotalscan_prune'] = 'Prune Log';
$l['virustotalscan_prune_days'] = 'Older than';
$l['virustotalscan_prune_days_desc'] = 'Prune log entries older than the number of days you enter.';
$l['virustotalscan_submit'] = 'Submit';
$l['virustotalscan_reset'] = 'Reset';
// optiuni
$l['virustotalscan_delete'] = 'Delete Log';
$l['virustotalscan_ban'] = 'Ban User';
// erori sau mesaje posibile
$l['virustotalscan_error'] = 'An unknown error has occurred.';
$l['virustotalscan_nologs'] = 'No logs found in your database.';
$l['virustotalscan_log_invalid'] = 'Invalid log entry.';
$l['virustotalscan_log_deleted'] = 'Log entry deleted successfully.';
$l['virustotalscan_log_pruned']= 'A number of {1} logs has been pruned successfully.';
$l['virustotalscan_ban_invalid'] = 'Invalid user ID entry!';
$l['virustotalscan_ban_yourself'] = "You can not ban yourself!";
$l['virustotalscan_ban_already'] = 'User already banned!';
$l['virustotalscan_ban_succes'] = 'User banned successfully.';
$l['virustotalscan_ban_reason'] = 'You have been banned because you tried to upload a virus file with ID {1}.';
// confirmari
$l['virustotalscan_logs_deleteconfirm'] = 'Are you sure you want to delete the selected log entry?';
$l['virustotalscan_logs_pruneconfirm'] = 'Are you sure you want to prune the log?';
$l['virustotalscan_logs_banconfirm'] = 'Are you sure you want to ban this user?';
?>
