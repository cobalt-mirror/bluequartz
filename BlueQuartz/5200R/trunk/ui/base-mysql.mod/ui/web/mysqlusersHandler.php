<?php

// Author: Michael Stauber <mstauber@solarspeed.net>
// Copyright 2006, Stauber Multimedia Design. All rights reserved.

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
//Only users with adminUser capability should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
    header("location: /error/forbidden.html");
    return;
}
$cceClient = $serverScriptHelper->getCceClient();

if ($_PagedBlock_selectedId_mysql_head == "MySQL_TAB_TWO") {
//if ($MySQL_TAB_TWO == "true") {
    $whichtab = "two";
    $quelle_zwo = $my_cnf;
    $cfg_file_zwo = "/tmp/my.cnf";
    $fp = fopen($cfg_file_zwo,"w");
    flock($fp,2);
    $array_zwo = $quelle_zwo;
    fputs($fp, $quelle_zwo);
    flock($fp,3);
    fclose($fp);
}
else {
    $whichtab = "one";
}

$cceClient->setObject("System",
        array(
              "soltab" => $whichtab,
              "solsitemysql" => $udb_switch,
              "SELECT" => $SELECT,
              "INSERT" => $INSERT,
              "UPDATE" => $UPDATE,
              "DELETE" => $DELETE,
              "FILE" => $FILE,
              "CREATE" => $CREATE,
              "ALTER" => $ALTER,
              "INDEX" => $INDEX,
              "DROP" => $DROP,
              "TEMPORARY" => $TEMPORARY,
              "GRANT" => $GRANT,
              "REFERENCE" => $REFERENCE,
              "LOCK" => $LOCK,
              "CREATE_VIEW" => $CREATE_VIEW,
              "SHOW_VIEW" => $SHOW_VIEW,
              "CREATE_ROUTINE" => $CREATE_ROUTINE,
              "ALTER_ROUTINE" => $ALTER_ROUTINE,
              "EXECUTE" => $EXECUTE,
              "MAX_QUERIES_PER_HOUR" => $MAX_QUERIES_PER_HOUR,
              "MAX_CONNECTIONS_PER_HOUR" => $MAX_CONNECTIONS_PER_HOUR,
              "MAX_UPDATES_PER_HOUR" => $MAX_UPDATES_PER_HOUR,
              "force_update" => $force_update),
	      "MYSQLUSERS_DEFAULTS");

$errors = $cceClient->errors();

print($serverScriptHelper->toHandlerHtml("/base/mysql/mysqlusers.php", $errors));

$serverScriptHelper->destructor();

?>

