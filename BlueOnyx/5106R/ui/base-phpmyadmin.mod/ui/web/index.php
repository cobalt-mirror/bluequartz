<?php

// phpMyAdmin Redirector
// (C) Bluapp AB and Project BlueOnyx 2009 - All rights reserved.

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$loginName = $serverScriptHelper->getLoginName();
$cceClient = $serverScriptHelper->getCceClient();

if ($loginName == "admin") {
    $systemOid = $cceClient->getObject("System", array(), "mysql");
    $db_username = $systemOid{'mysqluser'};
    $mysqlOid = $cceClient->find("MySQL");
    $mysqlData = $cceClient->get($mysqlOid[0]);
    $db_pass = $mysqlData{'sql_rootpassword'};
    $db_host = $mysqlData{'sql_host'};
} 
elseif ($serverScriptHelper->getAllowed('siteAdmin')) {
    
    // get user:
    $oids = $cceClient->find("User", array("name" => $loginName));
    $useroid = $oids[0];

    // determine site he belongs to:
    $user = $cceClient->get($oids[0]);
    $group = $user["site"];

    // Get MYSQL_Vsite settings for this site:
    list($sites) = $cceClient->find("Vsite", array("name" => $group));
    $MYSQL_Vsite = $cceClient->get($sites, 'MYSQL_Vsite');

    // Fetch MySQL details for this site:
    $db_enabled = $MYSQL_Vsite['enabled'];
    $db_username = $MYSQL_Vsite['username'];
    $db_pass = $MYSQL_Vsite['pass'];
    $db_host = $MYSQL_Vsite['host'];

    if ($db_enabled == "0") {
        $db_host = "localhost";
        $db_username = "";
        $db_pass = "";
    }
}
else {
  $loginName = "";
}

// Sanity checks:
if (!$db_host) {
    $db_host = "localhost";
}

print "<form action='signon.php' method='post' name='frm' onLoad='document.frm.submit()'>";
print "<input type='hidden' name='PMA_user' value='$db_username'>";
print "<input type='hidden' name='PMA_password' value='$db_pass'>";
print "<input type='hidden' name='hostname' value='$db_host'>";
print "<input type='image' name='' value=''>";
print "</form>";
print '
<script language="JavaScript">
       document.frm.submit();
</script>
';

?>
