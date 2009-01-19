<?php

// Squirrelmail Redirector
// (C) Project BlueOnyx 2008 - All rights reserved.

include_once("ServerScriptHelper.php");

// Get login name:
$serverScriptHelper = new ServerScriptHelper();
$loginName = $serverScriptHelper->getLoginName();

// Get FQDN of the server:
$dn = $_SERVER['SERVER_NAME'];

if ($_SERVER['HTTPS']) {
    // User logged in by HTTPS - redirect to HTTPS URL:
    header ("Location: https://$dn:81/webmail/src/login.php?loginname=$loginName");
}
else {
    // User logged in by HTTP - redirect to HTTP URL:
    header ("Location: http://$dn:444/webmail/src/login.php?loginname=$loginName");
}

xadyadsdasd

?>
