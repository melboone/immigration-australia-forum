<?php

$where_form_is="http://".$_SERVER['SERVER_NAME'].strrev(strstr(strrev($_SERVER['PHP_SELF']),"/"));

session_start();
if( ($_SESSION['security_code']==$_POST['security_code']) && (!empty($_POST['security_code'])) ) { 
mail("support@immigrationaustralia.info","Support Form - immigration Australia","Form data:

Your name: " . $_POST['field_1'] . " 
e-mail: " . $_POST['field_2'] . " 
Message: " . $_POST['field_3'] . " 
");

include("confirm.html");
}
else {
echo "Invalid Captcha String. <p>Please go back and try again or return to the <a href=/ title='Return to immigrationaustralia.info'>to the front page</a>.</p>";
}

?>
