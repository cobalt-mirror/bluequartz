<?php

// phpMyAdmin Redirector
// (C) Project BlueOnyx 2008 - All rights reserved.

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$loginName = $serverScriptHelper->getLoginName();
 
$cceClient = $serverScriptHelper->getCceClient();
 
if($loginName == "admin") {
    $systemOid = $cceClient->getObject("System", array(), "mysql");
    $loginName = $systemOid{'mysqluser'};
    $mysqlOid = $cceClient->find("MySQL");
    $mysqlData = $cceClient->get($mysqlOid[0]);
    $sql_rootpassword = $mysqlData{'sql_rootpassword'};
} 
else {
    $loginName = "";
}
 
// Get FQDN of the server:
$dn = $_SERVER['SERVER_NAME'];
 
if ($_SERVER['HTTPS']) {
     // User logged in by HTTPS - redirect to HTTPS URL:
    if($loginName) {
	header ("Location: https://$loginName:$sql_rootpassword@$dn:81/phpMyAdmin/");
    } 
    else {
	header ("Location: https://$dn:81/phpMyAdmin/");
    }
}
else {
     // User logged in by HTTP - redirect to HTTP URL:
    if($loginName){
	header ("Location: http://$loginName:$sql_rootpassword@$dn:444/phpMyAdmin/");
    } 
    else {
	header ("Location: http://$dn:81/phpMyAdmin/");
    }
}

?>
