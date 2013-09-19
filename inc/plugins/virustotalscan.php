<?php
/*
    @author     : Surdeanu Mihai ;
    @date       : 22 decembrie 2011 ;
    @version    : 1.0;
    @mybb       : compatibilitate cu MyBB 1.6 (orice versiuni) ;
    @description: Aceasta modificare va permite scanarea atasamentelor si a legaturilor din cadrul forumului dvs. impotriva virusilor!
    @homepage   : http://mybb.ro ! Te rugam sa ne vizitezi pentru ca nu ai ce pierde!
    @copyright  : Aceasta modificare este premium si nu poate fi publica sau redistribuita fara acordul MyBB Romania.
    ====================================
    Ultima modificare a codului : 22.12.2011 14:32
*/

// Poate fi accesat direct fisierul?
if(!defined("IN_MYBB")) {
    	die("This file cannot be accessed directly.");
}

// Carligele modificarii
$plugins->add_hook('admin_load', 'virustotalscan_admin');
$plugins->add_hook('admin_tools_menu', 'virustotalscan_admin_tools_menu');
$plugins->add_hook('admin_tools_action_handler', 'virustotalscan_admin_tools_action_handler');
$plugins->add_hook('admin_tools_permissions', 'virustotalscan_admin_permissions');
$plugins->add_hook('global_start', 'virustotalscan_replace_style');
$plugins->add_hook('upload_attachment_do_insert', 'virustotalscan_scan');

// Informatii legate de modificare
function virustotalscan_info()
{
    return array(
		"name"			=> "Virus Total Scanner",
		"description"	=> "Scans new attachments and links by using VirusTotal.com's API.",
		"website"		=> "http://mybb.ro",
		"author"		=> "Surdeanu Mihai",
		"authorsite"	=> "http://mybb.ro",
		"version"		=> "1.1",
        "guid"          => "1572096dc083bc7f00f2b4f5ae3837f7",
		"compatibility"	=> "16*"
	);
}

// Functia de activare a modificarii
function virustotalscan_activate()
{
	global $db;
	// grupul de setari
	$group = array(
        "name"          => "virustotalscan_group", 
		"title"         => "Virus Total Scanner", 
		"description"   => "Settings for \"Virus Total Scanner\" plugin.", 
		"disporder"     => 100, 
		"isdefault"     => 0
	);
	$gid = $db->insert_query("settinggroups", $group);
    // setarile modificarii
   	$setting_1 = array(
		"sid"			=> NULL,
		"name"			=> "virustotalscan_setting_key",
		"title"			=> "Public Key",
		"description"	=> "Enter your VirusTotal.com public key. Without this key you cannot use \"Virus Total Scanner\" application. You can get one by visiting <a href=\"http://www.virustotal.com/vt-community/register.html\" target=\"_blank\">this</a> page.",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> 1,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting_1);
   	$setting_2 = array(
		"sid"			=> NULL,
		"name"			=> "virustotalscan_setting_attach_enable",
		"title"			=> "Is \"Attachment Scan\" module enabled?",
		"description"	=> "If it is set to \"Yes\" then all your new attachments will be checked before they are inserted in database.",
		"optionscode"	=> "yesno",
		"value"			=> "yes",
		"disporder"		=> 2,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting_2);
	$setting_3 = array(
		"sid"			=> NULL,
		"name"			=> "virustotalscan_setting_bantime",
		"title"			=> "Ban Time for \"Attachment Scan\" module :",
		"description"	=> "For how much time a user will be banned from the administration panel. Leave blank or enter negative values if you want the ban to be permanently. (Default : \"\")",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> 3,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting_3);
	$setting_4 = array(
		"sid"			=> NULL,
		"name"			=> "virustotalscan_setting_bangroup",
		"title"			=> "After banning user, he will be moved to usergroup :",
		"description"	=> "When a member is banned then it will be moved to the group below. The group is represented by its id. (Default : 7)",
		"optionscode"	=> "text",
		"value"			=> "7",
		"disporder"		=> 4,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting_4);
	$setting_5 = array(
		"sid"			=> NULL,
		"name"			=> "virustotalscan_setting_email",
		"title"			=> "Email",
		"description"	=> "Enter the email address of the person who receives an email once someone tries to upload an infected file. Leave blank to disable this feature.",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> 5,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting_5);
   	$setting_6 = array(
		"sid"			=> NULL,
		"name"			=> "virustotalscan_setting_url_enable",
		"title"			=> "Is \"Link Scan\" module enabled?",
		"description"	=> "If it is set to \"Yes\" then all links from messages will be checked for viruses.",
		"optionscode"	=> "yesno",
		"value"			=> "yes",
		"disporder"		=> 6,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting_6);
	$setting_7 = array(
		"sid"			=> NULL,
		"name"			=> "virustotalscan_setting_url_protection",
		"title"			=> "Protection level :",
		"description"	=> "Set a protection level for your \"Link Scan\" module. (Default : Medium Protection)",
		"optionscode"	=> "select\n25=Low Protection\n50=Medium Protection\n75=Maximum Protection\n",
		"value"			=> "50",
		"disporder"		=> 7,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting_7);
	$setting_8 = array(
		"sid"			=> NULL,
		"name"			=> "virustotalscan_setting_url_rescan",
		"title"			=> "Rescan frequency :",
		"description"	=> "Set a rescan frequency for your \"Link Scan\" module. (Default : 1 Month)",
		"optionscode"	=> "select\n7=7 Days\n14=14 Days\n30=1 Month\n90=3 Months\n180=6 Months\n",
		"value"			=> "30",
		"disporder"		=> 8,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting_8);
	$setting_9 = array(
		"sid"			=> NULL,
		"name"			=> "virustotalscan_setting_url_loadlimit",
		"title"			=> "Server load limit :",
		"description"	=> "Set an upper limit for your server load time over the module \"Link Scan\" stop working. (Default : 5)",
		"optionscode"	=> "select\n1=1.0\n3=3.0\n5=5.0\n7=7.0\n9=9.0\n",
		"value"			=> "5",
		"disporder"		=> 9,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting_9);
	$setting_10 = array(
		"sid"			=> NULL,
		"name"			=> "virustotalscan_setting_url_groups",
		"title"			=> "Groups Except :",
		"description"	=> "User groups defined below will not benefit from the scanning links. (Default : 7)",
		"optionscode"	=> "text",
		"value"			=> "7",
		"disporder"		=> 10,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $setting_10);
	// se actualizeaza toate setarile
	rebuild_settings();
    // inainte de a crea eventuale tabele vom vedea ce colocatie avem...
	$collation = $db->build_create_table_collation();
	// daca tabelul cu log-uri exista atunci nu se va mai crea
	if(!$db->table_exists("virustotalscan_log")) {
        // daca nu exista se purcede la crearea lui
        $db->write_query("CREATE TABLE `".TABLE_PREFIX."virustotalscan_log` (
            `lid` bigint(30) UNSIGNED NOT NULL auto_increment,
            `uid` bigint(30) UNSIGNED NOT NULL default '0',
            `username` varchar(128) NOT NULL default '',
            `date` bigint(30) UNSIGNED NOT NULL default '0',
            `data` TEXT NOT NULL,
            PRIMARY KEY  (`lid`), KEY(`date`)
                ) ENGINE=MyISAM{$collation}");
    }
    // se adauga in baza de date noi stiluri
    $css_url_template = array(
        "title"     => "virustotalscan_url_css",
        "template"  => $db->escape_string('
            <style type="text/css">
            a.virustotalscan_found, a.virustotalscan_notfound 
            {
                position: relative;
                z-index:24; 
                text-decoration:none
            }
            a.virustotalscan_found:hover
            {
                z-index:25; 
                background-color:#800000
            }
            a.virustotalscan_notfound:hover
            {
                z-index:25; 
                background-color:#008000
            }
            a.virustotalscan_found span, a.virustotalscan_notfound span
            {
                display: none
            }
            a.virustotalscan_found:hover span            
            { 
                display:block;
                position:absolute;
                top:2em; left:2em; width:15em;
                border:1px solid #0cf;
                background-color:#cff; 
                color:#FF0000;
                text-align: center
            }
            a.virustotalscan_notfound:hover span            
            { 
                display:block;
                position:absolute;
                top:2em; left:2em; width:15em;
                border:1px solid #0cf;
                background-color:#cff; 
                color:#000;
                text-align: center
            }
            </style>
        '),
        "sid"        => "-1",
        "version"    => "1.0",
        "dateline"    => TIME_NOW
    );
    $db->insert_query('templates', $css_url_template); 
}

// Functia de dezactivare a modificarii
function virustotalscan_deactivate()
{
    global $db;
    // se sterg setarile din baza de date
    $db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name = 'virustotalscan_group'");
   	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name LIKE 'virustotalscan_setting_%'");
    // se actualizeaza toate setarile
	rebuild_settings();
    // daca tabela "virustotalscan_log" exista in baza de date atunci se sterge!
	if ($db->table_exists('virustotalscan_log'))
        $db->drop_table('virustotalscan_log');
    // se sterge din baza de date stil-urile adaugate
    $db->delete_query('templates', 'title = "virustotalscan_url_css"');  
}

// Functie care adauga stilul CSS in cadrul paginii tale
function virustotalscan_replace_style()
{
    global $mybb, $templates, $virustotalscan_url_css, $plugins;
    $groups = explode(",", $mybb->settings['virustotalscan_setting_url_groups']);
    // doar in cazul in care modulul de scanare al legaturilor este activ se trece la adaugarea codului CSS in forum
    if ($mybb->settings['virustotalscan_setting_url_enable'] && virustotalscan_check_server_load($mybb->settings['virustotalscan_setting_url_loadlimit']) && !empty($mybb->settings['virustotalscan_setting_key']) && !in_array($mybb->user['usergroup'], $groups))
    {
        eval("\$virustotalscan_url_css = \"".$templates->get("virustotalscan_url_css")."\";");    
        // in acest caz va exista scanarea URL-urilor
        $plugins->add_hook('parse_message_end', 'virustotalscan_scan_url');
    }
}

// Functie care verifica permisiunile unor anumite grupuri
function virustotalscan_check_permissions($groups_comma)
{
    global $mybb;
    // daca nu a fost trimis niciun grup ca si parametru
    if ($groups_comma == '') return false;
    // se verifica posibilitatea ca nu cumva sa fie mai multe grupuri trimise ca parametru
    $groups = explode(",", $groups_comma);
    // se creaza vectori cu acestee grupuri
    $add_groups = explode(",", $mybb->user['additionalgroups']);
    // se fac teste de apartenenta
    if (!in_array($mybb->user['usergroup'], $groups)) { 
        // in grupul primar nu este
        // verificam mai departe daca este in cel aditional, secundar
        if ($add_groups) {
            if (count(array_intersect($add_groups, $groups)) == 0)
                return false;
            else
                return true;
        }
        else 
            return false;
    }
    else
        return true;
}

// Functie care verifica daca un text este o adresa de email valida
function virustotalscan_valid_email($email) 
{
	if (function_exists("filter_var") && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    else {
        // daca functia exista atunci se returneaza true
        if (function_exists("filter_var")) return true;
        else {
            // altfel inseamna ca functia nu exista si trebuie sa utilizam o alta metoda
            return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email);
        }
    }
}

// Functia care scaneaza un fisier de pe disk si intoarce un hash pentru raport
function virustotalscan_scan_file($filepath, $key)
{
	$post = array('key' => $key, 'file' => '@'.$filepath);
	// se initializeaza o cerere
	$ch = curl_init();
    // parametrii cererii
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
	curl_setopt($ch, CURLOPT_URL, 'http://www.virustotal.com/api/scan_file.json');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   
	// rezultatul cererii 
	$result = curl_exec($ch);
	// se inchide cererea
	curl_close($ch);
	// se decodeaza rezultatul
	$result = json_decode($result, true);
    // se intoarce rezultatul
	if(isset($result['scan_id'][0])) {
        return $result;
	} 
    else {
        return false;
	}	
}

// Functia care intoarce raportul unei scanari de fisier, raport primit de functia de mai sus
function virustotalscan_get_report($resource, $key)
{
    $url = 'https://www.virustotal.com/api/get_file_report.json';
	$fields = array('resource' => $resource, 'key' => $key);
    $fields_string = "";
	foreach($fields as $key => $value) {
        $fields_string .= $key.'='.$value.'&'; 
    }
	$fields_string = rtrim($fields_string, '&');
    // se initializeaza o cerere
	$ch = curl_init();
    // se seteaza optiunile cererii
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_POST,count($fields));
	curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // se intoarce rezultatul cererii
	$result = curl_exec($ch);
    // se inchide cererea
	curl_close($ch);
    // se decodeaza rezultatul de pe server
	$result = json_decode($result, true);
    // se intoarce rezultatul
	if(isset($result['result'])) {
        return $result;
	}
    else {
		return false;
	}
}

// Functia care intoarce raportul unei scanari de link
function virustotalscan_get_url_report($resource, $key, $scan = 0)
{   
    $url = 'http://www.virustotal.com/api/get_url_report.json';
    $fields = array('resource' => $resource, 'key' => $key, 'scan' => $scan);
    $fields_string = '';
    foreach($fields as $key => $value) { 
        $fields_string .= $key.'='.$value.'&'; 
    }
    $fields_string = rtrim($fields_string, '&');
    // se initializeaza o cerere       
    $ch = curl_init();
    // se seteaza optiunile cererii
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // se intoarce rezultatul cererii      
    $result = curl_exec($ch);
    // se inchide cererea
    curl_close($ch);
    // se decodeaza rezultatul de pe server        
    $result = json_decode($result, true);
    // se intoarce rezultatul       
    if($scan && $result['result'] == 0) {
        // atunci s-a obtinut un scan_id
        return $result['scan_id'];
    } 
    else {
        // altfel se obtine un raport
        return $result;
    }   
}

// Functia care intoarce timpul de incarcare al unui server Linux
function virustotalscan_check_server_load($level)
{
    $load = 0;
    // daca functia exista atunci se intorc timpii de incarcare a severului
    if (function_exists('get_server_load')) {
        $load = get_server_load();
    }
    if (floatval($load) > floatval($level)) {
        // timpul de incarcare e mai mare si deci scanarea nu se va efectua
        return false;
    }
    else {
        // timpul de incarcare e mai mic decat cel trimis ca parametru, iar ca rezultat scanarea va incepe
        return true;
    }
}

// Functia care intoarce marimea unui fisier in functie de numarul de biti
function virustotalscan_get_size($size)
{
    $standard = floatval(1024);
    if (floatval($size) < $standard) {
        // atunci sunt biti
        return $size." bytes";
    }
    else {
        // sunt mai mult de 1024 de biti
        if (floatval(floatval($size)/1024) > $standard) {
            // atunci este de ordinul MB-itilor si se iau doar doua zecimale semnificative
            return number_format(floatval($size)/1024/1024, 2). " MB";
        }
        else {
            // este de ordinul KB-itilor si se iau doar doua zecimale semnificative
            return number_format(floatval($size)/1024, 2). " KB";
        }
        // restul nu ne intereseaza pentru ca nu pot fi incarcate fisiere mai mari de 1 GB
    }	
}

/*
 * Interfata cu Utilizatorul
 * =========================
 * Cod scris de : Surdeanu Mihai
 */
 
// Functia care realizeaza scanarea propriu-zisa a unui atasament
function virustotalscan_scan(&$attacharray)
{
	global $mybb, $db, $lang;	
    // daca cheia nu este definita sau daca atasamentele nu sunt verificate atunci nu se intampla nimic
    if ($mybb->settings['virustotalscan_setting_attach_enable'] && !empty($mybb->settings['virustotalscan_setting_key']))
    {
        // in acest moment fisierul a fost deja incarcat pe server
        // se construieste URL-ul catre atasamentul incarcat pe server
        $file = ".".substr($mybb->settings['uploadspath'], 1)."/".$attacharray['attachname'];
        $key = $mybb->settings['virustotalscan_setting_key'];
        $scan = virustotalscan_scan_file($file, $key);
        // se intoarce ID scanarii din string-ul primit ca rezultat
        $scan_id = explode('-', $scan['scan_id']);
        $scan_id = $scan_id[0];
        $retrieve = virustotalscan_get_report($scan_id, $key);
        if ($retrieve && $retrieve['result'] == 1) 
        {
      		$lang->load('virustotalscan');
            $total = count($retrieve['report'][1]);
            $count_array = array_count_values($retrieve['report'][1]);
            // se sorteaza vectorul dupa valori in ordine descrescatoare
            arsort($count_array);
            // acum se alege prima valoare nenula a cheii din vector
            $virus = "-";
            foreach ($count_array as $key => $val) {
                if ($key != "") {
                    // prima valoare gasita e numele virusului ce va fi afisat pe forum
                    $virus = $key;
                    // dupa ce se gaseste se iese din structura repetitiva
                    break;
                }
                // daca nu se cauta mai departe
            }
            $percent = "<font color=\"#D71A1A\">".intval($total - $count_array[''])."</font>/".$total." (".number_format(floatval(floatval($total - $count_array[''])/floatval($total))*100, 2)."%)";
            // informatie folosita pentru log-uri
            $data = $lang->sprintf($lang->virustotalscan_data, $attacharray['attachname'], $virus, $percent);
            // ce se va afisa pe ecranul userului
            $size = virustotalscan_get_size($attacharray['filesize']);
			$error_data = $lang->sprintf($lang->virustotalscan_error_data, $attacharray['attachname'], $size, $retrieve['report'][0], $virus, $percent, addslashes($retrieve['permalink']));
			// se sterge fisierul de pe disc! Astfel el nu va putea ajunge in baza de date!
			@unlink($mybb->settings['uploadspath']."/".$attacharray['attachname']);
            // se insereaza un log in sistem
            $insert_array = array(
                'uid' => intval($mybb->user['uid']),
                'username' => $db->escape_string($mybb->user['username']),
                'date' => TIME_NOW,
                'data' => $db->escape_string($data)
            );
            // se insereaza in baza de date
            $db->insert_query('virustotalscan_log', $insert_array);
            // se trimite email ?
			if (!empty($mybb->settings['virustotalscan_setting_email']) && virustotalscan_valid_email($mybb->settings['virustotalscan_setting_email'])) {
                my_mail($mybb->settings['virustotalscan_setting_email'], $lang->virustotalscan_email_subject, $lang->sprintf($lang->virustotalscan_email_message, htmlspecialchars_uni($mybb->user['username']), my_date($mybb->settings['timeformat'], TIME_NOW), $data), $mybb->settings['bbname'], '', '', false, 'html');
			}	
			// in fine se afiseaza eroare si pe ecranul utilizatorului
			error($lang->sprintf($lang->virustotalscan_error_infected, nl2br($error_data)));
        }
        else {
            // nu s-a depistat niciun virus
        }
    } else {
        // daca nu e definita o cheie publica atunci nu se intampla nimic
        // daca pluginul nu e activ nu se intampla iar nimic
    }
}

// Functia care scaneaza URL-ul
function virustotalscan_check_url($link, $text, $more = " ", $key)
{
    global $lang;
    // se adauga fisierul de limba
    $lang->load('virustotalscan');
    // mai intai se verifica daca nu cumva existe un raport in baza de date
    $retrieve = virustotalscan_get_url_report($link, $key);  
    // daca exista se verifica ca nu cumva sa fie vechi
    if ($retrieve && isset($retrieve['report'][0])) 
    {
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set('UTC');
        } 
        // verificam timpul
        $report_date = strtotime($retrieve['report'][0]);
        // crearea intervalul de rescanare
        $interval = "+".intval($mybb->settings['virustotalscan_setting_url_rescan'])." day";
        $max = strtotime($interval, $report_date);
        //$max_time = date('Y-m-d H:i:s', $max);
        if ($max < TIME_NOW) {
            // daca timpul e depasit, atunci se realizeaza o rescanare
            $retrieve = virustotalscan_get_url_report($link, $key, 1);
        }
        if ($retrieve && isset($retrieve['scan_id'])) 
        {
            // dupa rescanare se intoarce raportul
            $retrieve = virustotalscan_get_url_report($retrieve['scan_id'], $key);
            if ($retrieve && isset($retrieve['report'][0])) 
            {
                // totul e bine
                // exista virusi ?
                // numarul de elemente
                $total = count($retrieve['report'][1]);
                // innumaram de cate ori apare textul "Clean site" si "Error"
                $count_array = array_count_values($retrieve['report'][1]);
                $number_clean = 0;
                if (isset($count_array['Clean site'])) {
                    $number_clean = intval($count_array['Clean site']);
                }
                $number_error = 0;
                if (isset($count_array['Error'])) {
                    $number_error = intval($count_array['Error']);
                }               
                $protection = ($mybb->settings['virustotalscan_setting_url_protection']) ? intval($mybb->settings['virustotalscan_setting_url_protection']) : 50;
                if (floatval($number_clean - $number_error) >= floatval($protection) * floatval($total - $number_error) / 100) {
                    // in acest caz adresa URL este curata
                    return $lang->sprintf($lang->virustotalscan_link_notfound, $link, $more, $text);
                }
                else {
                    // altfel exista virusi
                    return $lang->sprintf($lang->virustotalscan_link_found, $link, $more, $text, $retrieve['file-report']);
                }
            }
            else {
                // daca raportul primt nu e corect, se considera a fi o adresa web in regula
                return $lang->sprintf($lang->virustotalscan_link_notfound, $link, $more, $text);
            }
        }
        else {
            // daca nu a fost nevoie de o rescanare
            // facem teste pentru existenta virusilor ?
            // numarul de elemente din vector
            $total = count($retrieve['report'][1]);
            // innumaram de cate ori apare textul "Clean site" si "Error"
            $count_array = array_count_values($retrieve['report'][1]);
            $number_clean = 0;
            if (isset($count_array['Clean site'])) {
                $number_clean = intval($count_array['Clean site']);
            }
            $number_error = 0;
            if (isset($count_array['Error'])) {
                $number_error = intval($count_array['Error']);
            }               
            $protection = ($mybb->settings['virustotalscan_setting_url_protection']) ? intval($mybb->settings['virustotalscan_setting_url_protection']) : 50;
            if (floatval($number_clean - $number_error) >= floatval($protection) * floatval($total - $number_error) / 100) {
                // in acest caz adresa URL este curata
                return $lang->sprintf($lang->virustotalscan_link_notfound, $link, $more, $text);
            }
            else {
                // altfel exista virusi
                return $lang->sprintf($lang->virustotalscan_link_found, $link, $more, $text, $retrieve['file-report']);
            }
        }
    }
    else {
        // ce se intampla daca nu exista un raport in sistem ?
        // raspuns : se face o scanare
        $retrieve = virustotalscan_get_url_report($link, $key, 1);
        if ($retrieve && isset($retrieve['scan_id'])) 
        {
            // dupa rescanare se intoarce raportul
            $retrieve = virustotalscan_get_url_report($retrieve['scan_id'], $key);
            if ($retrieve && isset($retrieve['report'][0])) 
            {
                // totul e bine
                // exista virusi ?
                // numarul de elemente
                $total = count($retrieve['report'][1]);
                // innumaram de cate ori apare textul "Clean site" si "Error"
                $count_array = array_count_values($retrieve['report'][1]);
                $number_clean = 0;
                if (isset($count_array['Clean site'])) {
                    $number_clean = intval($count_array['Clean site']);
                }
                $number_error = 0;
                if (isset($count_array['Error'])) {
                    $number_error = intval($count_array['Error']);
                }               
                $protection = ($mybb->settings['virustotalscan_setting_url_protection']) ? intval($mybb->settings['virustotalscan_setting_url_protection']) : 50;
                if (floatval($number_clean - $number_error) >= floatval($protection) * floatval($total - $number_error) / 100) {
                    // in acest caz adresa URL este curata
                    return $lang->sprintf($lang->virustotalscan_link_notfound, $link, $more, $text);
                }
                else {
                    // altfel exista virusi
                    return $lang->sprintf($lang->virustotalscan_link_found, $link, $more, $text, $retrieve['file-report']);
                }
            }
            else {
                // daca raportul primt nu e corect, se considera a fi o adresa web in regula
                return $lang->sprintf($lang->virustotalscan_link_notfound, $link, $more, $text);
            }
        }
        else {
            // nu s-a intors niciun scan_id
            // presupunem ca totul e in regula
            return $lang->sprintf($lang->virustotalscan_link_notfound, $link, $more, $text);
        }
    }
}

// Functia de scanare a URL-urilor introduse la postarea unor mesaje
function virustotalscan_scan_url($message)
{
	global $mybb;
    // in momentul in care se apeleaza aceasta functie toate testele au fost facute
    // altfel se incepe scanarea prin construirea cheii
    $key = $mybb->settings['virustotalscan_setting_key'];
    // se parseaza eventuale legaturi
    $message = preg_replace('#<a href="(.*?)"(.*?)>(.*?)</a>#s', virustotalscan_check_url('\1', '\3', '\2', $key), $message);
    // dupa prelucrari se iese din functie
    return;
}

/*
 * Panoul de Administrare
 * ======================
 * Cod scris de : Surdeanu Mihai
 */
 
// Crearea meniului modificarii in panoul de administrare
function virustotalscan_admin_tools_menu(&$sub_menu)
{
    global $lang;
    // se incarca fisierul de limba
    $lang->load('virustotalscan');
	$sub_menu[] = array('id' => 'virustotalscan', 'title' => $lang->virustotalscan_title, 'link' => 'index.php?module=tools-virustotalscan');
}

// Controller-ul pentru administrarea acestei modificari
function virustotalscan_admin_tools_action_handler(&$actions)
{
	$actions['virustotalscan'] = array('active' => 'virustotalscan', 'file' => 'virustotalscan');
}

// Permisiunile pentru aceasta modificare
function virustotalscan_admin_permissions(&$admin_permissions)
{
  	global $lang;
    // se incarca fisierul de limba
   	$lang->load("virustotalscan", false, true);
    // se seteaza textul permisiunii
	$admin_permissions['virustotalscan'] = $lang->virustotalscan_canmanage;
}

// Functia de administrare
function virustotalscan_admin()
{
	global $db,$lang,$mybb,$cache,$page,$run_module,$action_file,$mybbadmin,$plugins;
    // in primul rand se incarca fisierul de limba
   	$lang->load("virustotalscan", false, true);	
    // in al doilea rand se verifica daca ne aflam in sectiunea care trebuie si pagina de actiune este cea corecta
	if($run_module == 'tools' && $action_file == 'virustotalscan')
	{
        // se pare ca ne aflam acolo unde trebuie
		if (!$mybb->input['action'])
		{
            // daca nu este definita nicio actiune se afiseaza informatiile de baza
            // pentru inceput este adaugat un breadcrumb
			$page->add_breadcrumb_item($lang->virustotalscan_title, 'index.php?module=tools-virustotalscan');
			// se afiseaza antetul paginii			
			$page->output_header($lang->virustotalscan_title);
			// se creaza meniul orizontal	
			$sub_tabs['virustotalscan_logs'] = array(
				'title'			=> $lang->virustotalscan_logs,
				'link'			=> 'index.php?module=tools-virustotalscan',
				'description'	=> $lang->virustotalscan_logs_desc
			);
		}
		// pagina principala ?
		if (!$mybb->input['action'])
		{
            // se afiseaza meniul orizontal
			$page->output_nav_tabs($sub_tabs, 'virustotalscan_logs');
            // se realizeaza paginarea in vederea afisarii tabelului
			$per_page = 10; // in mod implicit
			if($mybb->input['page'] && intval($mybb->input['page']) > 1) {
				$mybb->input['page'] = intval($mybb->input['page']);
				$start = ($mybb->input['page'] * $per_page) - $per_page;
			}
			else {
				$mybb->input['page'] = 1;
				$start = 0;
			}
			// acum paginarea este in regula, se trece la obtinerea datelor din tabel
			$query = $db->simple_select("virustotalscan_log", "COUNT(lid) as logs");
            // variabila ce retine numarul de randuri obtinute din interogare
			$total_rows = $db->fetch_field($query, "logs");
            // se realizeaza paginarea
			echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=tools-virustotalscan&amp;page={page}");
            // se construieste tabelul se urmeaza sa fie afisat
			$table = new Table;
			$table->construct_header($lang->virustotalscan_logs_user, array('width' => '15%'));
			$table->construct_header($lang->virustotalscan_logs_data, array('width' => '50%'));
			$table->construct_header($lang->virustotalscan_logs_date, array('width' => '20%', 'class' => 'align_center'));
			$table->construct_header($lang->virustotalscan_logs_options, array('width' => '15%', 'class' => 'align_center'));
			// se creaza interogarea
			$query = $db->query("
				SELECT u.*, u.username AS userusername, l.*
				FROM ".TABLE_PREFIX."virustotalscan_log l
				LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=l.uid)
				ORDER BY l.date DESC LIMIT {$start}, {$per_page}
			");
			// se creaza tabelul rand cu rand
			while ($log = $db->fetch_array($query))
            {
				$table->construct_cell(build_profile_link(htmlspecialchars_uni($log['username']), intval($log['uid'])), array('class' => 'align_center'));
				$table->construct_cell($db->escape_string($log['data']));
				$table->construct_cell(my_date($mybb->settings['dateformat'], intval($log['date']), '', false).", ".my_date($mybb->settings['timeformat'], intval($log['date'])), array('class' => 'align_center'));
				$table->construct_cell("<a href=\"index.php?module=tools-virustotalscan&amp;action=delete_log&amp;lid=".intval($log['lid'])."\">".$lang->virustotalscan_delete."</a><br/><a href=\"index.php?module=tools-virustotalscan&amp;action=ban_user&amp;uid=".intval($log['uid'])."&amp;lid=".intval($log['lid'])."\">".$lang->virustotalscan_ban."</a>", array('class' => 'align_center'));
				$table->construct_row();
			}
			// in cazul in care nu a existat niciun rand intors din baza de date atunci se afiseaza un mesaj central
			if($table->num_rows() == 0) {
				$table->construct_cell($lang->virustotalscan_nologs, array('class' => 'align_center', 'colspan' => 4));
				$table->construct_row();
			}
			// in final se afiseaza tabelul pe ecranul utilizatorului
			$table->output($lang->virustotalscan_logs);
			echo "<br />";
            // formular prin care pot fi sterse o serie de log-uri
			$form = new Form("index.php?module=tools-virustotalscan&amp;action=prune", "post", "viruscan");
			// se genereaza o cheie de tip post
			echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
			// numele formularului	
			$form_container = new FormContainer($lang->virustotalscan_prune);
			$form_container->output_row($lang->virustotalscan_prune_days, $lang->virustotalscan_prune_days_desc, $form->generate_text_box('days', 30, array('id' => 'days')), 'days');
			$form_container->end();
            // butoanele din cadrul formularului		
			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->virustotalscan_submit);
			$buttons[] = $form->generate_reset_button($lang->virustotalscan_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif ($mybb->input['action'] == 'delete_log')
		{
			if($mybb->input['no']) {
                // userul nu a mai confirmat
				admin_redirect("index.php?module=tools-virustotalscan");
			}
            // se verifica cererea
			if($mybb->request_method == "post")
			{
                // daca codul cererii nu e corect atunci se afiseaza o eroare pe ecran
				if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key']) {
					$mybb->request_method = "get";
					flash_message($lang->virustotalscan_error, 'error');
					admin_redirect("index.php?module=tools-virustotalscan");
				}
				// exista id-ul log-ului specificat in cerere in sistem?
				if (!$db->fetch_field($db->simple_select('virustotalscan_log', 'data', 'lid = '.intval($mybb->input['lid']), array('limit' => 1)), 'data'))
				{
					flash_message($lang->virustotalscan_log_invalid, 'error');
					admin_redirect('index.php?module=tools-virustotalscan');
				}
				else {				
				    // daca se ajunge pe aceasta ramura inseamna ca se poate sterge log-ul
					$db->delete_query('virustotalscan_log', 'lid = '.intval($mybb->input['lid']));
					flash_message($lang->virustotalscan_log_deleted, 'success');
					admin_redirect('index.php?module=tools-virustotalscan');
				}
			}
			else
			{
                // pagina de confirmare
				$page->add_breadcrumb_item($lang->virustotalscan_logs, 'index.php?module=tools-virustotalscan');
				// se afiseaza antetul paginii	
				$page->output_header($lang->virustotalscan_logs);
                // se converteste inputul la intreg
				$mybb->input['lid'] = intval($mybb->input['lid']);
				$form = new Form("index.php?module=tools-virustotalscan&amp;action=delete_log&amp;lid={$mybb->input['lid']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->virustotalscan_logs_deleteconfirm}</p>\n";
				echo "<br />\n";
				echo "<p class=\"buttons\">\n";
				echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
				echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
				echo "</p>\n";
				echo "</div>\n";
				$form->end();
			}
		}
		elseif ($mybb->input['action'] == 'prune')
		{
			if($mybb->input['no']) {
                // userul nu a mai confirmat
				admin_redirect("index.php?module=tools-virustotalscan");
			}
            // se verifica cererea
			if($mybb->request_method == "post")
			{
                // daca codul cererii nu e corect atunci se afiseaza o eroare pe ecran
				if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key']) {
					$mybb->request_method = "get";
					flash_message($lang->virustotalscan_error, 'error');
					admin_redirect("index.php?module=tools-virustotalscan");
				}
				// mai departe se incearca stergerea tuturor log-urilor mai vechi decat data specificata
				$db->delete_query('virustotalscan_log', 'date < '.(TIME_NOW - intval($mybb->input['days']) * 60 * 60 * 24));
				$count_deleted = $db->affected_rows();
                flash_message($lang->sprintf($lang->virustotalscan_log_pruned, intval($count_deleted)), 'success');
				admin_redirect('index.php?module=tools-virustotalscan');
			}
			else
			{
                // pagina de confirmare
				$page->add_breadcrumb_item($lang->virustotalscan_logs, 'index.php?module=tools-virustotalscan');
				// se afiseaza antetul paginii	
				$page->output_header($lang->virustotalscan_logs);
				// se converteste inputul la intreg
				$mybb->input['days'] = intval($mybb->input['days']);
				$form = new Form("index.php?module=tools-virustotalscan&amp;action=prune&amp;days={$mybb->input['days']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->virustotalscan_logs_pruneconfirm}</p>\n";
				echo "<br />\n";
				echo "<p class=\"buttons\">\n";
				echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
				echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
				echo "</p>\n";
				echo "</div>\n";
				$form->end();
			}
		}
		elseif ($mybb->input['action'] == 'ban_user')
		{
			if($mybb->input['no']) {
                // userul nu a mai confirmat banarea
				admin_redirect("index.php?module=tools-virustotalscan");
			}
            // se verifica cererea
			if($mybb->request_method == "post")
			{
                // daca codul cererii nu e corect atunci se afiseaza o eroare pe ecran
				if(!isset($mybb->input['my_post_key']) || $mybb->post_code != $mybb->input['my_post_key']) {
					$mybb->request_method = "get";
					flash_message($lang->virustotalscan_error, 'error');
					admin_redirect("index.php?module=tools-virustotalscan");
				}
                // nu te poti bana singur
                if(intval($mybb->input['uid']) == intval($mybb->user['uid'])) {
 					flash_message($lang->virustotalscan_ban_yourself, 'error');
					admin_redirect('index.php?module=tools-virustotalscan');                   
                }
				// userul dorit este deja banat...
				if ($db->num_rows($db->simple_select('banned', 'dateline', 'uid = '.intval($mybb->input['uid']), array('limit' => 1))) > 0)
				{
					flash_message($lang->virustotalscan_ban_already, 'error');
					admin_redirect('index.php?module=tools-virustotalscan');
				}
				else {	
                    // daca se ajunge pe aceasta ramura inseamna ca se poate bana userul
                    // mai intai se intorc detalii despre user
 			        $query = $db->simple_select("users", "usergroup,additionalgroups,displaygroup", "uid = '".intval($mybb->input['uid'])."'", array('limit' => 1));
                    if ($db->num_rows($query) > 0) 
                    {
                        // vectorul cu informatii despre un utilizator
                        $user = $db->fetch_array($query);
                        // informatii aditionale, ce pot fi la o adica nule
                        $additionalgroups = (intval($user['additionalgroups']) > 0) ? intval($user['additionalgroups']) : '';
                        $displaygroup = (intval($user['displaygroup']) > 0) ? intval($user['displaygroup']) : '';
                        // pe ce perioada de timp e banat userul ?
                        // in mod implicit e banat pe viata
                        $bantime = "---";
                        $lifted = 0;              
                        if (intval($mybb->settings['virustotalscan_setting_bantime']) > 0) {
                            // inseamna ca va fi banat pe o perioada de timp, de zile
                            $bantime = "";
                            $lifted = TIME_NOW + intval($mybb->settings['virustotalscan_setting_bantime']) * 24 * 60 * 60;
                        }
                        // grupul nou al userului
                        $usergroups = $cache->read("usergroups");
                        if (is_array($usergroups) && in_array(intval($mybb->settings['virustotalscan_setting_bangroup']), $usergroups)) {
                            $gid = intval($mybb->settings['virustotalscan_setting_bangroup']);
                        }
                        else {
                            // standard usergroup
                            $gid = 7;
                        }
                        // vectorul ce va fi inserat in tabelul "banned"
                        $insert_array = array(
                            'uid' => intval($mybb->input['uid']),
                            'gid' => $gid,
                            'oldgroup' => intval($user['usergroup']),
                            'oldadditionalgroups' => $additionalgroups,
                            'olddisplaygroup' => $displaygroup,
                            'admin' => intval($mybb->user['uid']),
                            'dateline' => TIME_NOW,
                            'bantime' => $bantime,
                            'lifted' => $lifted,
                            'reason' => $lang->sprintf($lang->virustotalscan_ban_reason, intval($mybb->input['lid']))
                        );
                        // se insereaza in baza de date
                        $db->insert_query('banned', $insert_array);
                        // se muta userul in grupul dorit
                        $db->query("UPDATE ".TABLE_PREFIX."users SET usergroup = '".$gid."' WHERE uid = '".intval($mybb->input['uid'])."'");
                        // se actualizeaza cache-ul cu cei banati
                        $cache->update_banned();
                        // se afiseaza un mesaj pe ecran
                        flash_message($lang->virustotalscan_ban_succes, 'success');
					    admin_redirect('index.php?module=tools-virustotalscan');
                    }
                    else {
                        flash_message($lang->virustotalscan_ban_invalid, 'error');
					    admin_redirect('index.php?module=tools-virustotalscan');                        
                    }
				}
			}
			else
			{
                // pagina de confirmare
				$page->add_breadcrumb_item($lang->virustotalscan_logs, 'index.php?module=tools-virustotalscan');
				// se afiseaza antetul paginii	
				$page->output_header($lang->virustotalscan_logs);
                // se converteste inputul la intreg
                $mybb->input['uid'] = intval($mybb->input['uid']);
				$mybb->input['lid'] = intval($mybb->input['lid']);
				$form = new Form("index.php?module=tools-virustotalscan&amp;action=ban_user&amp;uid={$mybb->input['uid']}&amp;lid={$mybb->input['lid']}&amp;my_post_key={$mybb->post_code}", 'post');
				echo "<div class=\"confirm_action\">\n";
				echo "<p>{$lang->virustotalscan_logs_banconfirm}</p>\n";
				echo "<br />\n";
				echo "<p class=\"buttons\">\n";
				echo $form->generate_submit_button($lang->yes, array('class' => 'button_yes'));
				echo $form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'));
				echo "</p>\n";
				echo "</div>\n";
				$form->end();
			}
		}
		// in fine se afiseaza si subsolul paginii
		$page->output_footer();
		exit;
	}
}
?>
