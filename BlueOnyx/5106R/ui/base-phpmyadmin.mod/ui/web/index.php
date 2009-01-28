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
 } else {
  $loginName = "";
 }

print "<form action='signon.php' method='post' name='frm' onLoad='document.frm.submit()'>";
print "<input type='hidden' name='user' value='$loginName'>";
print "<input type='hidden' name='password' value='$sql_rootpassword'>";
print "<input type='hidden' name='host' value='localhost'>";
print "<input type='image' name='' value=''>";
print "</form>";
print '
<script language="JavaScript">
       document.frm.submit();
</script>
';

?>