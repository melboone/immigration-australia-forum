<?php
//////////////////////////////////////////////
/* Author: Janota                           */
//////////////////////////////////////////////
//////////////////////////////////////////////
/* Website: http://www.mybbextras.com       */  
//////////////////////////////////////////////
//////////////////////////////////////////////
/* Plugin: Checkbox Registration Agreement  */
//////////////////////////////////////////////

	if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}	
////////////////////////////////////////////////////////////////////// Functions ///////////////////////////////////////////////////////////////////////	

function check_reg_agree_info()
{
	return array(
		"name"			=> "Checkbox Registration Agreement",
		"description"	=> "<div style=\"margin-top: 5px;\"><img src=\"{$mybb->settings['bburl']}/images/plugin.ico\" style=\"margin-right: 8px;\" align=\"left\">New users must check a checkbox before can agree to the registration agreement.</div>",
		"website"		=> "http://www.mybbextras.com",
		"author"		=> "Janota",
		"authorsite"	=> "http://www.mybbextras.com",
		"version"		=> "1.0",
		"compatibility" => "16*"
	);
}

function check_reg_agree_activate()
{
	global $db, $mybb;

	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_register_agreement", "#".preg_quote('<div align="center">')."#i",'<div align="center"><tr><td class="trow1"><input type="hidden" name="step" value="agreement" />
<input type="hidden" name="action" value="register" />
<input name="agreecheck" type="checkbox" onClick="agreesubmit(this)"><b>I agree to the above Registration Agreement</b><br>
<input type="submit" class="button" name="agree" value="{$lang->i_agree}" disabled></td><tr><!--');

find_replace_templatesets("member_register_agreement", "#".preg_quote('</form>')."#i",'--></div></form>');


	find_replace_templatesets("member_register_agreement", "#".preg_quote('{$headerinclude}')."#i",'{$headerinclude}<script>
var checkobj
function agreesubmit(el){
checkobj=el
if (document.all||document.getElementById){
for (i=0;i<checkobj.form.length;i++){  //hunt down submit button
var tempobj=checkobj.form.elements[i]
if(tempobj.type.toLowerCase()=="submit")
tempobj.disabled=!checkobj.checked
}
}
}
function defaultagree(el){
if (!document.all&&!document.getElementById){
if (window.checkobj&&checkobj.checked)
return true
else{
alert("Please read/accept terms to submit form")
return false
}
}
}
</script>');
	
	
// Rebuilding settings
    rebuild_settings();
}


function check_reg_agree_deactivate()
{
	global $db, $mybb;

    require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("member_register_agreement", "#".preg_quote('<div align="center"><tr><td class="trow1"><input type="hidden" name="step" value="agreement" />
<input type="hidden" name="action" value="register" />
<input name="agreecheck" type="checkbox" onClick="agreesubmit(this)"><b>I agree to the above Registration Agreement</b><br>
<input type="submit" class="button" name="agree" value="{$lang->i_agree}" disabled></td><tr><!--')."#i", '<div align="center">', 0);

	find_replace_templatesets("member_register_agreement", "#".preg_quote('--></div></form>')."#i", '</form>', 0);
	
	
	find_replace_templatesets("member_register_agreement", "#".preg_quote('<script>
var checkobj
function agreesubmit(el){
checkobj=el
if (document.all||document.getElementById){
for (i=0;i<checkobj.form.length;i++){  //hunt down submit button
var tempobj=checkobj.form.elements[i]
if(tempobj.type.toLowerCase()=="submit")
tempobj.disabled=!checkobj.checked
}
}
}
function defaultagree(el){
if (!document.all&&!document.getElementById){
if (window.checkobj&&checkobj.checked)
return true
else{
alert("Please read/accept terms to submit form")
return false
}
}
}
</script>')."#i", '', 0);


// Rebuilding settings
    rebuild_settings();
}

?>