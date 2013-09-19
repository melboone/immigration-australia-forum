<?php

$where_form_is="http://".$_SERVER['SERVER_NAME'].strrev(strstr(strrev($_SERVER['PHP_SELF']),"/"));

session_start();
if( ($_SESSION['security_code']==$_POST['security_code']) && (!empty($_POST['security_code'])) ) { 
mail("litcanu@gmail.com","Immigration Australia - Contact form","Form data:

Name: " . $_POST['field_1'] . " 
E-mail: " . $_POST['field_2'] . " 
Country: " . $_POST['field_3'] . " 
Message: " . $_POST['field_4'] . " 


");

include("http://immigrationaustralia.info/misc.php?page=confirmation-page-message-sent");
}
else {
echo "Invalid Captcha String. <p>Please go back and try again or return to the <a href=/ title='Return to immigrationaustralia.info'>to the front page</a>.</p>";
}

?>
